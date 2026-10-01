import { NAV, BLOCKERS, OBSTACLES, SEATS, BED_SPOT, STAND_SPOTS } from './plan.js';
import { clamp } from './lib.js';

/* ================================================================== *
 *  Navigation
 *
 *  The apartment is a real floor plan, so walking a straight line at a
 *  target does not work: the pantry is behind a partition and the only
 *  way in is a 1.05 m cased opening.  This is a uniform-grid A* over the
 *  plan's own NAV/BLOCKERS/OBSTACLES data — 81 x 54 cells, which is tiny,
 *  so a full A* per request costs well under a millisecond.
 *
 *  Two details do most of the work in making the result look intentional:
 *
 *    1. Obstacles are inflated by `clearance`, not by the agent radius.
 *       The plan's doors are 0.75-1.10 m wide, so inflating by 0.32 m
 *       would seal the bathroom shut. 0.16 m leaves a 0.4-0.6 m channel,
 *       which is still wider than a shoulder.
 *
 *    2. Seats and prop anchors sit *inside* obstacles (nobody can stand
 *       in the sofa). Those cells are registered as terminals: A* may
 *       finish there but may never route *through* them, so an agent
 *       walking from the hall to the third sofa seat does not stroll
 *       across the cushions to get there.  Each anchor declares the side
 *       it is approached from, and only furniture — never a wall — is
 *       carved away to make that approach exist.
 * ================================================================== */

const SQRT2 = Math.SQRT2;

export class NavGrid {
    constructor({ clearance = 0.16, wallClearance = 0.1, cell = NAV.cell } = {}) {
        this.cell = cell;
        this.clearance = clearance;
        // Walls get a smaller inflation than furniture. A wall cell is the
        // test "is a body's centre here clipping the plaster", so it wants
        // roughly half a torso of padding, not the full working clearance
        // used to decide whether a chair is in the way.
        this.wallClearance = wallClearance;

        this.minX = NAV.minX;
        this.maxX = NAV.maxX;
        this.minZ = NAV.minZ;
        this.maxZ = NAV.maxZ;

        this.cols = Math.floor((this.maxX - this.minX) / cell) + 1;
        this.rows = Math.floor((this.maxZ - this.minZ) / cell) + 1;
        this.size = this.cols * this.rows;

        /* Walls and furniture are tracked separately. Walls are absolute
         * and nothing ever carves them; furniture may be notched away to
         * let a character step into a seat. */
        this.wall = new Uint8Array(this.size);
        this.furn = new Uint8Array(this.size);
        this.blocked = new Uint8Array(this.size);
        this.terminal = new Uint8Array(this.size);
        this.softCost = new Uint8Array(this.size);

        this._g = new Float32Array(this.size);
        this._f = new Float32Array(this.size);
        this._from = new Int32Array(this.size);
        this._state = new Uint8Array(this.size); // 0 new, 1 open, 2 closed
        this._stamp = new Int32Array(this.size);
        this._run = 0;

        this._heap = new Int32Array(this.size);
        this._heapLen = 0;

        this._build();
    }

    /* ---------------- grid maths ---------------- */

    /**
     * Which cell a world coordinate falls inside.
     *
     * This is a span lookup, not a nearest-centre one: cell `c` occupies
     * [xOf(c), xOf(c) + cell). Rounding instead sent the *centre* of a cell
     * to the next index, biasing every point lookup half a cell east. That
     * used to cancel out because _fillRect rounded both ends the same way,
     * but with _span doing true overlap the two disagreed, and bodies
     * standing in a widened doorway came out "inside a wall" while the
     * pixels showed them in the clear.
     */
    colOf(x) {
        return clamp(Math.floor((x - this.minX) / this.cell), 0, this.cols - 1);
    }

    rowOf(z) {
        return clamp(Math.floor((z - this.minZ) / this.cell), 0, this.rows - 1);
    }

    xOf(col) {
        return this.minX + col * this.cell;
    }

    zOf(row) {
        return this.minZ + row * this.cell;
    }

    index(col, row) {
        return row * this.cols + col;
    }

    colOfIndex(i) {
        return i % this.cols;
    }

    rowOfIndex(i) {
        return (i / this.cols) | 0;
    }

    inBounds(col, row) {
        return col >= 0 && col < this.cols && row >= 0 && row < this.rows;
    }

    isFree(i) {
        return !this.blocked[i];
    }

    /**
     * Is this world point inside a structural wall? Furniture is *not*
     * included on purpose: agents stand at anchors that sit inside desks,
     * so only a wall means "this body has gone somewhere impossible" and
     * the caller should undo the move.
     */
    inWall(x, z) {
        const i = this.index(this.colOf(x), this.rowOf(z));
        return this.wall[i] === 1;
    }

    /* ---------------- build ---------------- */

    _build() {
        // structural walls first — these are absolute
        for (const r of BLOCKERS) {
            const wc = this.wallClearance;
            this._fillRect(this.wall, r.x0 - wc, r.z0 - wc, r.x1 + wc, r.z1 + wc);
        }

        // furniture, inflated
        const c = this.clearance;
        for (const o of OBSTACLES) {
            this._fillRect(this.furn, o.x0 - c, o.z0 - c, o.x1 + c, o.z1 + c);
        }
        this._rebuild();

        // then re-open the door gaps that inflation may have pinched shut.
        // Each of these is a real opening in the plan; if a body fits
        // through it, the grid has to agree — which for a partition means
        // cutting the structural layer too, not just the furniture one.
        for (const gap of DOORWAYS) {
            this._clearRect(gap.x0, gap.z0, gap.x1, gap.z1, gap.margin, true);
        }

        // built-in seats, each with the side it is entered from
        for (const key of Object.keys(SEATS)) {
            this.addAnchor({
                ...SEATS[key],
                id: key,
                seat: true,
                approach: SEAT_APPROACH[key],
            });
        }
        this.addAnchor({ ...BED_SPOT, id: 'bed', seat: true, approach: SEAT_APPROACH.bed });
    }

    _rebuild() {
        for (let i = 0; i < this.size; i++) this.blocked[i] = this.wall[i] | this.furn[i];
    }

    /**
     * Cell range that genuinely overlaps [x0,x1].
     *
     * Rounding to the nearest cell (colOf) made every wall a full cell
     * fatter than the plan on each side: a partition ending at x=4.875
     * claimed the cell spanning 5.00..5.22 as well. Doorways came out
     * narrower than they are drawn, and a body standing legally in one
     * read as being inside a wall.
     */
    _span(x0, x1, origin, limit) {
        const a = Math.min(x0, x1);
        const b = Math.max(x0, x1);
        return [
            clamp(Math.floor((a - origin) / this.cell), 0, limit - 1),
            clamp(Math.ceil((b - origin) / this.cell) - 1, 0, limit - 1),
        ];
    }

    _fillRect(layer, x0, z0, x1, z1) {
        const [c0, c1] = this._span(x0, x1, this.minX, this.cols);
        const [r0, r1] = this._span(z0, z1, this.minZ, this.rows);
        for (let r = r0; r <= r1; r++) {
            for (let c = c0; c <= c1; c++) {
                const i = this.index(c, r);
                layer[i] = 1;
                this.blocked[i] = 1;
            }
        }
    }

    /**
     * Open a rect back up. `walls: true` also cuts through the structural
     * layer, which is what a real doorway needs — punching only the
     * furniture layer left the kitchen pass-through sealed and the grid
     * walkable only by accident, through whichever cells the wall-fill
     * quantisation happened to miss.
     */
    _clearRect(x0, z0, x1, z1, margin = 0, walls = false) {
        const [c0, c1] = this._span(x0 - margin, x1 + margin, this.minX, this.cols);
        const [r0, r1] = this._span(z0 - margin, z1 + margin, this.minZ, this.rows);
        for (let r = r0; r <= r1; r++) {
            for (let c = c0; c <= c1; c++) {
                if (!this.inBounds(c, r)) continue;
                const i = this.index(c, r);
                this.furn[i] = 0;
                if (walls) this.wall[i] = 0;
                this.blocked[i] = this.wall[i];
            }
        }
    }

    /**
     * Register an activity anchor.
     *
     * `approach` is the world point the character is expected to arrive
     * from. Only the furniture layer is carved along the way, so a seat
     * gains a small notch you step in through while the partition behind
     * it stays solid.
     */
    addAnchor(anchor) {
        if (!anchor || typeof anchor.x !== 'number') return;
        const i = this.index(this.colOf(anchor.x), this.rowOf(anchor.z));
        this.terminal[i] = 1;
        this.furn[i] = 0;
        this.blocked[i] = this.wall[i];

        const from = anchor.approach;
        if (!from) return;

        const steps = Math.max(
            1,
            Math.ceil(Math.hypot(from.x - anchor.x, from.z - anchor.z) / (this.cell * 0.5))
        );
        for (let s = 0; s <= steps; s++) {
            const t = s / steps;
            const c = this.colOf(anchor.x + (from.x - anchor.x) * t);
            const r = this.rowOf(anchor.z + (from.z - anchor.z) * t);
            if (!this.inBounds(c, r)) continue;
            const j = this.index(c, r);
            this.furn[j] = 0;
            this.blocked[j] = this.wall[j];
        }

        // and make sure the far end actually touches open floor
        if (this.nearestFree(from.x, from.z, 5) < 0) this._clearRect(from.x, from.z, from.x, from.z, 0.55);
    }

    /** Raise traversal cost near busy props so paths prefer open floor. */
    addSoftArea(x0, z0, x1, z1, amount = 1) {
        const c0 = this.colOf(x0);
        const c1 = this.colOf(x1);
        const r0 = this.rowOf(z0);
        const r1 = this.rowOf(z1);
        for (let r = r0; r <= r1; r++) {
            for (let c = c0; c <= c1; c++) {
                if (!this.inBounds(c, r)) continue;
                const i = this.index(c, r);
                this.softCost[i] = Math.min(255, this.softCost[i] + amount);
            }
        }
    }

    /* ---------------- lookups ---------------- */

    /** Nearest walkable cell to a world point, searched in rings. */
    nearestFree(x, z, maxRing = 14) {
        const c0 = this.colOf(x);
        const r0 = this.rowOf(z);
        for (let ring = 0; ring <= maxRing; ring++) {
            let best = -1;
            let bestD = Infinity;
            for (let dr = -ring; dr <= ring; dr++) {
                for (let dc = -ring; dc <= ring; dc++) {
                    if (ring > 0 && Math.abs(dr) !== ring && Math.abs(dc) !== ring) continue;
                    const c = c0 + dc;
                    const r = r0 + dr;
                    if (!this.inBounds(c, r)) continue;
                    const i = this.index(c, r);
                    if (this.blocked[i]) continue;
                    const dx = this.xOf(c) - x;
                    const dz = this.zOf(r) - z;
                    const d = dx * dx + dz * dz;
                    if (d < bestD) {
                        bestD = d;
                        best = i;
                    }
                }
            }
            if (best >= 0) return best;
        }
        return -1;
    }

    /** True when nothing blocks the straight segment a -> b. */
    lineOfSight(x0, z0, x1, z1) {
        const dx = x1 - x0;
        const dz = z1 - z0;
        const dist = Math.hypot(dx, dz);
        if (dist < 1e-4) return true;
        const steps = Math.max(2, Math.ceil(dist / (this.cell * 0.5)));
        for (let s = 0; s <= steps; s++) {
            const t = s / steps;
            const c = this.colOf(x0 + dx * t);
            const r = this.rowOf(z0 + dz * t);
            if (!this.inBounds(c, r)) return false;
            if (this.blocked[this.index(c, r)]) return false;
        }
        return true;
    }

    /* ---------------- A* ---------------- */

    _heapPush(i) {
        const heap = this._heap;
        const f = this._f;
        let n = this._heapLen++;
        heap[n] = i;
        while (n > 0) {
            const p = (n - 1) >> 1;
            if (f[heap[p]] <= f[heap[n]]) break;
            const t = heap[p];
            heap[p] = heap[n];
            heap[n] = t;
            n = p;
        }
    }

    _heapPop() {
        const heap = this._heap;
        const f = this._f;
        const top = heap[0];
        const last = heap[--this._heapLen];
        if (this._heapLen > 0) {
            heap[0] = last;
            let n = 0;
            for (;;) {
                const l = n * 2 + 1;
                const r = l + 1;
                let s = n;
                if (l < this._heapLen && f[heap[l]] < f[heap[s]]) s = l;
                if (r < this._heapLen && f[heap[r]] < f[heap[s]]) s = r;
                if (s === n) break;
                const t = heap[s];
                heap[s] = heap[n];
                heap[n] = t;
                n = s;
            }
        }
        return top;
    }

    _heuristic(i, goal) {
        const dc = Math.abs(this.colOfIndex(i) - this.colOfIndex(goal));
        const dr = Math.abs(this.rowOfIndex(i) - this.rowOfIndex(goal));
        // octile distance, in cells
        return (dc + dr) + (SQRT2 - 2) * Math.min(dc, dr);
    }

    /**
     * A* over the grid.  Returns raw cell centres, or null when there is
     * genuinely no route (a bug in the plan, not a transient failure).
     */
    _search(startIdx, goalIdx) {
        if (startIdx < 0 || goalIdx < 0) return null;
        if (startIdx === goalIdx) return [startIdx];

        const { cols, blocked, terminal, softCost, _g, _f, _from, _state, _stamp } = this;
        const run = ++this._run;
        this._heapLen = 0;

        _stamp[startIdx] = run;
        _g[startIdx] = 0;
        _f[startIdx] = this._heuristic(startIdx, goalIdx);
        _from[startIdx] = -1;
        _state[startIdx] = 1;
        this._heapPush(startIdx);

        let guard = 0;
        const guardMax = this.size * 4;

        while (this._heapLen > 0) {
            if (++guard > guardMax) break;
            const cur = this._heapPop();
            if (_state[cur] === 2) continue;
            _state[cur] = 2;
            if (cur === goalIdx) break;

            const cc = cur % cols;
            const cr = (cur / cols) | 0;

            for (let dr = -1; dr <= 1; dr++) {
                for (let dc = -1; dc <= 1; dc++) {
                    if (dc === 0 && dr === 0) continue;
                    const nc = cc + dc;
                    const nr = cr + dr;
                    if (nc < 0 || nc >= cols || nr < 0 || nr >= this.rows) continue;
                    const ni = nr * cols + nc;
                    if (_stamp[ni] === run && _state[ni] === 2) continue;

                    const isGoal = ni === goalIdx;
                    if (blocked[ni] && !(terminal[ni] && isGoal)) continue;
                    // no cutting a diagonal through a wall corner
                    if (dc !== 0 && dr !== 0) {
                        if (blocked[cr * cols + nc] || blocked[nr * cols + cc]) continue;
                    }

                    const step = dc !== 0 && dr !== 0 ? SQRT2 : 1;
                    const g = _g[cur] + step + softCost[ni] * 0.35;
                    if (_stamp[ni] !== run) {
                        _stamp[ni] = run;
                        _state[ni] = 0;
                        _g[ni] = Infinity;
                    }
                    if (g < _g[ni]) {
                        _g[ni] = g;
                        _f[ni] = g + this._heuristic(ni, goalIdx) * 1.02; // mild weight
                        _from[ni] = cur;
                        _state[ni] = 1;
                        this._heapPush(ni);
                    }
                }
            }
        }

        if (_stamp[goalIdx] !== run || _from[goalIdx] === -1 && goalIdx !== startIdx) {
            if (goalIdx !== startIdx && !(_stamp[goalIdx] === run)) return null;
        }
        if (_f[goalIdx] === Infinity) return null;

        const out = [];
        let n = goalIdx;
        let hops = 0;
        while (n !== -1 && hops++ < this.size) {
            out.push(n);
            if (n === startIdx) break;
            n = _from[n];
        }
        if (out[out.length - 1] !== startIdx) return null;
        out.reverse();
        return out;
    }

    /**
     * String-pulled path from (sx, sz) to (gx, gz).
     *
     * Raw A* output is a staircase of cell centres; walking that literally
     * looks robotic. This walks the list and keeps only the corners that
     * are genuinely necessary — everything in between is replaced by a
     * straight leg, which the character controller already turns into
     * smooth damped heading.
     */
    findPath(sx, sz, gx, gz) {
        const goalTerminal = this.terminal[this.index(this.colOf(gx), this.rowOf(gz))] === 1;
        const startIdx = this.nearestFree(sx, sz);
        let goalIdx = this.index(this.colOf(gx), this.rowOf(gz));

        if (startIdx < 0) return null;
        if (this.blocked[goalIdx] && !goalTerminal) {
            const alt = this.nearestFree(gx, gz);
            if (alt < 0) return null;
            goalIdx = alt;
        }
        // already there and the destination is walkable
        if (startIdx === goalIdx && !this.blocked[goalIdx]) {
            return [{ x: gx, z: gz }];
        }

        const cells = this._search(startIdx, goalIdx);
        if (!cells) return null;

        let pts = cells.map((i) => ({ x: this.xOf(i % this.cols), z: this.zOf((i / this.cols) | 0) }));
        // snap to the real endpoints so the character leaves from where it
        // actually is and lands exactly on the anchor
        pts[0] = { x: sx, z: sz };
        pts[pts.length - 1] = { x: gx, z: gz };

        return this._stringPull(pts);
    }

    _stringPull(pts) {
        if (pts.length <= 2) return pts;
        const out = [pts[0]];
        let anchor = 0;
        for (let i = 2; i < pts.length; i++) {
            const canSee = this.lineOfSight(pts[anchor].x, pts[anchor].z, pts[i].x, pts[i].z);
            if (!canSee) {
                out.push(pts[i - 1]);
                anchor = i - 1;
            }
        }
        out.push(pts[pts.length - 1]);
        return out;
    }

    /* ---------------- diagnostics ---------------- */

    /**
     * Flood fill from one cell.  Used by the self-test and by the debug
     * overlay to prove every anchor is actually reachable from the rest
     * of the flat — a sealed room is invisible until an agent tries it.
     */
    reachableFrom(sx, sz) {
        const start = this.nearestFree(sx, sz);
        if (start < 0) return new Set();
        const seen = new Set([start]);
        const queue = [start];
        while (queue.length) {
            const cur = queue.pop();
            const cc = cur % this.cols;
            const cr = (cur / this.cols) | 0;
            for (let dr = -1; dr <= 1; dr++) {
                for (let dc = -1; dc <= 1; dc++) {
                    if (dc === 0 && dr === 0) continue;
                    const nc = cc + dc;
                    const nr = cr + dr;
                    if (!this.inBounds(nc, nr)) continue;
                    const ni = nr * this.cols + nc;
                    if (seen.has(ni)) continue;
                    if (this.blocked[ni]) continue;
                    if (dc !== 0 && dr !== 0) {
                        if (this.blocked[cr * this.cols + nc] || this.blocked[nr * this.cols + cc]) continue;
                    }
                    seen.add(ni);
                    queue.push(ni);
                }
            }
        }
        return seen;
    }

    /** Every anchor the brain may target, for the self-test. */
    static auditAnchors(anchors) {
        const out = [];
        for (const a of anchors) {
            if (!a || typeof a.x !== 'number') continue;
            out.push(a);
        }
        return out;
    }
}

/* ------------------------------------------------------------------ *
 *  Doorways.
 *
 *  BLOCKERS are authored with an explicit gap at every opening, but
 *  obstacle inflation (fridge, island, cabinets) can still pinch a gap
 *  shut.  Re-opening them by hand keeps the grid honest instead of
 *  quietly making a room unreachable.
 * ------------------------------------------------------------------ */

/**
 * Real openings, mirrored from the partitions in plan.js.
 *
 * `margin` is deliberately the same size as the wall inflation: carving any
 * wider would hand back the jamb padding the inflation exists to provide,
 * and a body whose centre can reach the plaster is a body that eventually
 * gets its shoulder into it and wedges.
 */
const DOORWAYS = [
    /* bedroom door, on x = partBedroom */
    { x0: -5.95, z0: -3.05, x1: -5.65, z1: -2.15, margin: 0.1 },
    /* bathroom door */
    { x0: -5.95, z0: 0.55, x1: -5.65, z1: 1.65, margin: 0.1 },
    /* hall | office cased opening */
    { x0: -4.6, z0: -3.8, x1: -4.2, z1: -2.6, margin: 0.1 },
    /* office | pantry cased opening */
    { x0: 1.8, z0: -5.0, x1: 2.2, z1: -3.8, margin: 0.1 },
    /* pantry | lounge glazed door */
    { x0: 4.85, z0: -1.8, x1: 5.95, z1: -1.4, margin: 0.1 },
    /* front door */
    { x0: -7.2, z0: 5.7, x1: -6.0, z1: 6.1, margin: 0.1 },
];

/* ------------------------------------------------------------------ *
 *  Approach hints for the built-in seats.
 *
 *  A seated character's root sits 0.34 m behind the hips (see
 *  CharacterController._updateSeated), and the hips have to be inside the
 *  sofa, so the walkable target is behind the seat on the open side of
 *  the room.  The character then steps in during the sit transition.
 * ------------------------------------------------------------------ */

const SEAT_APPROACH = {
    /* desk chairs: approach from the front of the desk, i.e. +Z of the seat */
    dewiDesk: { x: -2.6, z: 2.35 },
    singgihDesk: { x: -1.2, z: -2.35 },
    anderaDesk: { x: -0.55, z: 2.35 },
    /* sofa backs onto the +X wall, so the open side is -X */
    dewiSofa: { x: 6.5, z: 0.55 },
    singgihSofa: { x: 6.5, z: 1.8 },
    anderaSofa: { x: 6.5, z: 3.0 },
    /* bed sits in the far corner of the bedroom */
    bed: { x: -7.75, z: -2.9 },
};

/** Where a character should stand to *use* something, given its footprint. */
export function standInFrontOf(obstacle, side, margin = 0.42) {
    if (!obstacle) return null;
    const cx = (obstacle.x0 + obstacle.x1) / 2;
    const cz = (obstacle.z0 + obstacle.z1) / 2;
    switch (side) {
        case 'north':
            return { x: cx, z: obstacle.z0 - margin, rotY: 0 };
        case 'south':
            return { x: cx, z: obstacle.z1 + margin, rotY: Math.PI };
        case 'west':
            return { x: obstacle.x0 - margin, z: cz, rotY: Math.PI / 2 };
        case 'east':
            return { x: obstacle.x1 + margin, z: cz, rotY: -Math.PI / 2 };
        default:
            return { x: cx, z: cz, rotY: 0 };
    }
}

export { STAND_SPOTS };
