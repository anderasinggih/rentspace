import * as THREE from 'three';
import {
    clamp, lerp, damp, dampAngle, smoothstep, TAU, Rng, fmtLen, setShadowRecursive,
} from './lib.js';
import { P, BODY, WALLS, OBSTACLES, BLOCKERS, NAV, SEATS, BED_SPOT, STAND_SPOTS } from './plan.js';
import {
    createMaterials, buildShell, buildDoor, buildWindow, buildCeilingLamp, buildCeilingSpotRow,
} from './architecture.js';
import {
    buildDeskBench, buildTaskChair, buildMonitor, buildDeskInput, buildMug, buildPaperStack,
    buildLaptop, buildDeskLamp, buildSofa, buildCoffeeTable, buildMediaConsole, buildTV,
    buildFloorLamp, buildKitchenRun, buildUpperCabinets, buildSink, buildHob, buildFridge,
    buildIsland, buildStool, buildKitchenClutter, buildDishRack, buildBed, buildNightstand,
    buildTableLamp, buildWardrobe, buildShower, buildVanity, buildToilet, buildTowelRail,
    buildFiddleLeafPlant, buildSnakePlant, buildSmallPlant, buildBookshelf, buildServerRack,
    buildACUnit, buildWallClock, buildConsoleTable, buildCoatRack, buildShoeRack, buildRug,
    buildCurtains, buildFrame, drawPoster, drawClockFace,
} from './props.js';
import { buildHumanoid, CharacterController } from './character.js';
import { artworkCanvas } from './textures.js';

const addons = window.OFFICE_ADDONS || {};

/* ==================================================================== *
 *  NAVIGATION — uniform grid + A* over the real floor plan.
 *
 *  Cells come from BLOCKERS (walls) and OBSTACLES (furniture), both
 *  inflated by the agent radius, so a path can never clip a wall or walk
 *  through a desk.  This is the piece that makes the motion read as
 *  "someone walking through a real office" rather than a ghost.
 * ==================================================================== */

class NavGrid {
    constructor() {
        this.cell = NAV.cell;
        this.minX = NAV.minX;
        this.minZ = NAV.minZ;
        this.w = Math.ceil((NAV.maxX - NAV.minX) / this.cell);
        this.h = Math.ceil((NAV.maxZ - NAV.minZ) / this.cell);
        this.blocked = new Uint8Array(this.w * this.h);
        this._bake();
    }

    idx(cx, cz) {
        return cz * this.w + cx;
    }

    cellCenterX(cx) {
        return this.minX + (cx + 0.5) * this.cell;
    }

    cellCenterZ(cz) {
        return this.minZ + (cz + 0.5) * this.cell;
    }

    toCellX(x) {
        return clamp(Math.floor((x - this.minX) / this.cell), 0, this.w - 1);
    }

    toCellZ(z) {
        return clamp(Math.floor((z - this.minZ) / this.cell), 0, this.h - 1);
    }

    /** Is this cell (and its 8-neighbour clearance) free? */
    isOpen(cx, cz) {
        if (cx < 0 || cz < 0 || cx >= this.w || cz >= this.h) return false;
        return this.blocked[this.idx(cx, cz)] === 0;
    }

    _bake() {
        const r = NAV.agentRadius;
        const rects = [];
        for (const b of BLOCKERS) rects.push(b);
        for (const o of OBSTACLES) {
            // only waist-height-and-up furniture blocks walking; anything
            // shorter than the knee is stepped over, not routed around
            if (o.h < 0.35) continue;
            rects.push(o);
        }

        for (let cz = 0; cz < this.h; cz++) {
            for (let cx = 0; cx < this.w; cx++) {
                const x = this.cellCenterX(cx);
                const z = this.cellCenterZ(cz);
                let hit = 0;
                for (const b of rects) {
                    if (x > b.x0 - r && x < b.x1 + r && z > b.z0 - r && z < b.z1 + r) {
                        hit = 1;
                        break;
                    }
                }
                if (!hit) {
                    // keep agents off the walls themselves even where a
                    // BLOCKER rect was not declared
                    if (x < P.outer.minX + 0.28 || x > P.outer.maxX - 0.28 ||
                        z < P.outer.minZ + 0.28 || z > P.outer.maxZ - 0.28) {
                        hit = 1;
                    }
                }
                this.blocked[this.idx(cx, cz)] = hit;
            }
        }
    }

    /** Nearest open cell to a world point, searched in expanding rings. */
    nearestOpen(x, z, maxRing = 26) {
        const cx0 = this.toCellX(x);
        const cz0 = this.toCellZ(z);
        if (this.isOpen(cx0, cz0)) return [cx0, cz0];
        for (let ring = 1; ring <= maxRing; ring++) {
            for (let dz = -ring; dz <= ring; dz++) {
                for (let dx = -ring; dx <= ring; dx++) {
                    if (Math.max(Math.abs(dx), Math.abs(dz)) !== ring) continue;
                    const cx = cx0 + dx;
                    const cz = cz0 + dz;
                    if (this.isOpen(cx, cz)) return [cx, cz];
                }
            }
        }
        return null;
    }

    /** 8-connected A*. Returns an array of world-space waypoints. */
    findPath(sx, sz, tx, tz) {
        const start = this.nearestOpen(sx, sz);
        const goal = this.nearestOpen(tx, tz);
        if (!start || !goal) return null;

        const [scx, scz] = start;
        const [gcx, gcz] = goal;

        const n = this.w * this.h;
        const gScore = new Float32Array(n).fill(Infinity);
        const fScore = new Float32Array(n).fill(Infinity);
        const cameFrom = new Int32Array(n).fill(-1);
        const closed = new Uint8Array(n);

        const si = this.idx(scx, scz);
        const gi = this.idx(gcx, gcz);
        gScore[si] = 0;
        fScore[si] = this._h(scx, scz, gcx, gcz);

        // binary heap
        const heap = [si];
        const push = (i) => {
            heap.push(i);
            let c = heap.length - 1;
            while (c > 0) {
                const p = (c - 1) >> 1;
                if (fScore[heap[p]] <= fScore[heap[c]]) break;
                [heap[p], heap[c]] = [heap[c], heap[p]];
                c = p;
            }
        };
        const pop = () => {
            const top = heap[0];
            const last = heap.pop();
            if (heap.length) {
                heap[0] = last;
                let p = 0;
                for (;;) {
                    const l = p * 2 + 1;
                    const r = l + 1;
                    let s = p;
                    if (l < heap.length && fScore[heap[l]] < fScore[heap[s]]) s = l;
                    if (r < heap.length && fScore[heap[r]] < fScore[heap[s]]) s = r;
                    if (s === p) break;
                    [heap[p], heap[s]] = [heap[s], heap[p]];
                    p = s;
                }
            }
            return top;
        };

        const NB = [
            [1, 0, 1], [-1, 0, 1], [0, 1, 1], [0, -1, 1],
            [1, 1, Math.SQRT2], [1, -1, Math.SQRT2], [-1, 1, Math.SQRT2], [-1, -1, Math.SQRT2],
        ];

        let guard = 0;
        while (heap.length && guard++ < 200000) {
            const cur = pop();
            if (cur === gi) break;
            if (closed[cur]) continue;
            closed[cur] = 1;

            const cx = cur % this.w;
            const cz = (cur - cx) / this.w;

            for (const [dx, dz, cost] of NB) {
                const nx = cx + dx;
                const nz = cz + dz;
                if (!this.isOpen(nx, nz)) continue;
                // no corner cutting through a diagonal gap
                if (dx !== 0 && dz !== 0 &&
                    (!this.isOpen(cx + dx, cz) || !this.isOpen(cx, cz + dz))) continue;
                const ni = this.idx(nx, nz);
                if (closed[ni]) continue;
                const tentative = gScore[cur] + cost * this.cell;
                if (tentative < gScore[ni]) {
                    cameFrom[ni] = cur;
                    gScore[ni] = tentative;
                    fScore[ni] = tentative + this._h(nx, nz, gcx, gcz);
                    push(ni);
                }
            }
        }

        if (cameFrom[gi] === -1 && gi !== si) return null;

        // reconstruct
        const cells = [];
        let cur = gi;
        while (cur !== -1) {
            const cx = cur % this.w;
            cells.push([cx, (cur - cx) / this.w]);
            if (cur === si) break;
            cur = cameFrom[cur];
        }
        cells.reverse();

        // string-pull: drop waypoints we can walk straight past
        const pts = cells.map(([cx, cz]) => new THREE.Vector3(this.cellCenterX(cx), 0, this.cellCenterZ(cz)));
        pts.push(new THREE.Vector3(tx, 0, tz));

        const out = [];
        let anchor = new THREE.Vector3(sx, 0, sz);
        let i = 0;
        while (i < pts.length) {
            let best = i;
            for (let j = pts.length - 1; j > i; j--) {
                if (this._lineClear(anchor, pts[j])) {
                    best = j;
                    break;
                }
            }
            out.push(pts[best]);
            anchor = pts[best];
            if (best === pts.length - 1) break;
            i = best + 1;
        }
        return out;
    }

    _h(ax, az, bx, bz) {
        const dx = Math.abs(ax - bx);
        const dz = Math.abs(az - bz);
        return (dx + dz) + (Math.SQRT2 - 2) * Math.min(dx, dz);
    }

    /** Supercover line walk; true if no blocked cell is crossed. */
    _lineClear(a, b) {
        const steps = Math.ceil(a.distanceTo(b) / (this.cell * 0.5));
        if (steps === 0) return true;
        for (let s = 0; s <= steps; s++) {
            const t = s / steps;
            const x = lerp(a.x, b.x, t);
            const z = lerp(a.z, b.z, t);
            if (!this.isOpen(this.toCellX(x), this.toCellZ(z))) return false;
        }
        return true;
    }
}

/* ==================================================================== *
 *  CAMERA RIG
 * ==================================================================== */

const VIEWS = {
    iso: { pos: [11.4, 8.2, 12.6], look: [-0.4, 1.1, 0.2], fov: 42 },
    top: { pos: [0.2, 19.5, 0.6], look: [0, 0, 0], fov: 46 },
    desk: { pos: [-0.5, 1.62, 3.9], look: [-2.3, 0.95, 0.4], fov: 40 },
    bedroom: { pos: [-3.9, 2.6, 0.4], look: [-7.6, 1.0, -3.6], fov: 46 },
    pantry: { pos: [7.9, 2.5, -0.6], look: [4.4, 1.0, -4.4], fov: 48 },
    lounge: { pos: [1.2, 2.3, 6.2], look: [6.6, 0.9, 2.2], fov: 46 },
    bathroom: { pos: [-4.5, 2.1, 3.2], look: [-7.4, 1.1, 0.8], fov: 48 },
    free: { pos: [13.5, 9.5, 14.0], look: [0, 1.0, 0], fov: 45 },
    front: { pos: [-6.6, 1.62, 9.6], look: [-6.0, 1.25, 1.0], fov: 42 },
};

class CameraRig {
    constructor(camera, dom) {
        this.camera = camera;
        this.dom = dom;

        this.pos = new THREE.Vector3().fromArray(VIEWS.iso.pos);
        this.target = new THREE.Vector3().fromArray(VIEWS.iso.look);
        this.desiredPos = this.pos.clone();
        this.desiredTarget = this.target.clone();
        this.zoom = 1;

        // orbit state for free-cam drag
        this.yaw = 0;
        this.pitch = 0;
        this.dragging = false;
        this._bind();
        this._applyView('iso', true);
    }

    _bind() {
        const el = this.dom;
        let lastX = 0;
        let lastY = 0;
        let pointerId = null;

        el.addEventListener('pointerdown', (e) => {
            if (e.button !== 0 && e.button !== 2) return;
            this.dragging = true;
            pointerId = e.pointerId;
            lastX = e.clientX;
            lastY = e.clientY;
            el.setPointerCapture?.(e.pointerId);
            el.style.cursor = 'grabbing';
        });
        el.addEventListener('pointermove', (e) => {
            if (!this.dragging || e.pointerId !== pointerId) return;
            const dx = e.clientX - lastX;
            const dy = e.clientY - lastY;
            lastX = e.clientX;
            lastY = e.clientY;
            this.yaw -= dx * 0.0055;
            this.pitch = clamp(this.pitch - dy * 0.0045, -0.62, 0.9);
            this._orbit();
        });
        const end = (e) => {
            if (!this.dragging) return;
            this.dragging = false;
            el.releasePointerCapture?.(pointerId);
            el.style.cursor = 'grab';
            void e;
        };
        el.addEventListener('pointerup', end);
        el.addEventListener('pointercancel', end);
        el.addEventListener('contextmenu', (e) => e.preventDefault());
        el.style.cursor = 'grab';
    }

    _orbit() {
        // rotate the current offset around the look target
        const off = this.desiredPos.clone().sub(this.desiredTarget);
        const r = off.length();
        let phi = Math.atan2(off.x, off.z);
        let theta = Math.asin(clamp(off.y / r, -1, 1));
        phi += this.yaw;
        theta = clamp(theta + this.pitch, 0.06, Math.PI / 2 - 0.02);
        off.set(
            r * Math.sin(theta) * Math.sin(phi),
            r * Math.cos(theta),
            r * Math.sin(theta) * Math.cos(phi)
        );
        this.desiredPos.copy(this.desiredTarget).add(off);
        this.yaw = 0;
        this.pitch = 0;
    }

    _applyView(name, instant = false) {
        const v = VIEWS[name] || VIEWS.iso;
        this.yaw = 0;
        this.pitch = 0;
        this.desiredPos.fromArray(v.pos);
        this.desiredTarget.fromArray(v.look);
        this.zoom = 1;
        if (v.fov) this.fov = v.fov;
        if (instant) {
            this.pos.copy(this.desiredPos);
            this.target.copy(this.desiredTarget);
        }
        this.view = name;
    }

    setView(name) {
        if (name === 'dewi_pov') {
            const a = this.agents?.dewi;
            if (a) {
                const p = a.group.position;
                const yaw = a.controller.facing;
                this.desiredTarget.set(
                    p.x + Math.sin(yaw) * 3,
                    1.5,
                    p.z + Math.cos(yaw) * 3
                );
                this.desiredPos.set(
                    p.x + Math.sin(yaw) * 0.55,
                    1.62,
                    p.z + Math.cos(yaw) * 0.55
                );
                this.view = 'dewi_pov';
                return;
            }
        }
        this._applyView(name);
    }

    zoomIn() {
        this.zoom = clamp(this.zoom * 0.82, 0.35, 2.6);
        this._recompute();
    }

    zoomOut() {
        this.zoom = clamp(this.zoom / 0.82, 0.35, 2.6);
        this._recompute();
    }

    _recompute() {
        const base = VIEWS[this.view] || VIEWS.iso;
        const off = this.desiredPos.clone().sub(this.desiredTarget);
        off.multiplyScalar(this.zoom);
        this.desiredPos.copy(this.desiredTarget).add(off);
    }

    update(dt) {
        const k = 7.5;
        this.pos.x = damp(this.pos.x, this.desiredPos.x, k, dt);
        this.pos.y = damp(this.pos.y, this.desiredPos.y, k, dt);
        this.pos.z = damp(this.pos.z, this.desiredPos.z, k, dt);
        this.target.x = damp(this.target.x, this.desiredTarget.x, k, dt);
        this.target.y = damp(this.target.y, this.desiredTarget.y, k, dt);
        this.target.z = damp(this.target.z, this.desiredTarget.z, k, dt);

        this.camera.position.copy(this.pos);
        this.camera.lookAt(this.target);
        if (this.fov && Math.abs(this.camera.fov - this.fov) > 0.01) {
            this.camera.fov = damp(this.camera.fov, this.fov, 8, dt);
            this.camera.updateProjectionMatrix();
        }
    }
}

/* ==================================================================== *
 *  AGENTS — three workers wired to the Livewire status contract.
 * ==================================================================== */

const AGENT_SPEC = {
    dewi: {
        role: 'Customer Service',
        shirtColor: 0xd9587f,
        pantsColor: 0x2f3646,
        hairColor: 0x53301d,
        skinColor: 0xe8bd9b,
        shoeColor: 0x2a2d33,
        hair: 'long',
        bangs: true,
        deskSeat: 'dewiDesk',
        sofaSeat: 'dewiSofa',
        seed: 21,
    },
    singgih: {
        role: 'Core Dispatcher',
        shirtColor: 0x0d9488,
        pantsColor: 0x1e2a3a,
        hairColor: 0x181a1f,
        skinColor: 0xd8a884,
        shoeColor: 0x1c1e22,
        hair: 'short',
        bangs: false,
        deskSeat: 'singgihDesk',
        sofaSeat: 'singgihSofa',
        seed: 34,
    },
    andera: {
        role: 'Report & Finance',
        shirtColor: 0xd97706,
        pantsColor: 0x3a2a17,
        hairColor: 0x1f1710,
        skinColor: 0xc9946c,
        shoeColor: 0x241c15,
        hair: 'short',
        bangs: false,
        deskSeat: 'anderaDesk',
        sofaSeat: 'anderaSofa',
        seed: 57,
    },
};

/* ------------------------------------------------------------------ *
 *  Speech bubble — a CSS-free canvas sprite so it survives in-scene.
 * ------------------------------------------------------------------ */
function makeBubble() {
    const canvas = document.createElement('canvas');
    canvas.width = 512;
    canvas.height = 256;
    const tex = new THREE.CanvasTexture(canvas);
    tex.colorSpace = THREE.SRGBColorSpace;
    const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: false });
    const sprite = new THREE.Sprite(mat);
    sprite.scale.set(0.9, 0.45, 1);
    sprite.renderOrder = 999;
    sprite.visible = false;

    const state = { text: '', until: 0, canvas, tex, sprite };

    state.draw = (text, who) => {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, 512, 256);

        // bubble body
        const pad = 18;
        ctx.font = '600 27px ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
        const maxW = 512 - pad * 2 - 46;
        const words = String(text).split(/\s+/);
        const lines = [];
        let line = '';
        for (const word of words) {
            const test = line ? line + ' ' + word : word;
            if (ctx.measureText(test).width > maxW && line) {
                lines.push(line);
                line = word;
            } else {
                line = test;
            }
        }
        if (line) lines.push(line);
        const shown = lines.slice(0, 4);
        if (lines.length > 4) shown[3] = shown[3].replace(/\s*\S*$/, '…');

        const lineH = 36;
        const bodyH = shown.length * lineH + 26;
        const bodyW = Math.min(
            maxW + pad * 2,
            Math.max(180, ...shown.map((l) => ctx.measureText(l).width)) + pad * 2
        );
        const x0 = (512 - bodyW) / 2;
        const y0 = 26;

        ctx.fillStyle = 'rgba(255,255,255,0.97)';
        ctx.strokeStyle = 'rgba(24,28,34,0.22)';
        ctx.lineWidth = 2;
        const r = 18;
        ctx.beginPath();
        ctx.moveTo(x0 + r, y0);
        ctx.arcTo(x0 + bodyW, y0, x0 + bodyW, y0 + bodyH, r);
        ctx.arcTo(x0 + bodyW, y0 + bodyH, x0, y0 + bodyH, r);
        ctx.arcTo(x0, y0 + bodyH, x0, y0, r);
        ctx.arcTo(x0, y0, x0 + bodyW, y0, r);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        // tail
        ctx.beginPath();
        ctx.moveTo(256 - 15, y0 + bodyH - 1);
        ctx.lineTo(256 + 4, y0 + bodyH + 24);
        ctx.lineTo(256 + 16, y0 + bodyH - 1);
        ctx.closePath();
        ctx.fillStyle = 'rgba(255,255,255,0.97)';
        ctx.fill();

        ctx.fillStyle = '#1a1f26';
        ctx.textBaseline = 'top';
        shown.forEach((l, i) => ctx.fillText(l, x0 + pad, y0 + 13 + i * lineH));

        // speaker chip
        if (who) {
            ctx.font = '700 18px ui-sans-serif, system-ui, sans-serif';
            const label = who;
            const lw = ctx.measureText(label).width;
            ctx.fillStyle = 'rgba(26,31,38,0.9)';
            ctx.beginPath();
            ctx.roundRect(18, 4, lw + 22, 30, 15);
            ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.textBaseline = 'middle';
            ctx.fillText(label, 29, 20);
        }

        tex.needsUpdate = true;
    };

    state.show = (text, who, seconds = 7, now = 0) => {
        state.text = text;
        state.until = now + seconds;
        state.draw(text, who);
        sprite.visible = true;
    };

    return state;
}

/* ==================================================================== *
 *  MAIN ENGINE
 * ==================================================================== */

export class OfficeApp {
    constructor() {
        this.ready = false;
        this.agents = {};
        this.doors = {};
        this.clock = new THREE.Clock();
        this.time = 0;
        this.rng = new Rng(1337);
        this._disposed = false;
    }

    /* ---------------- public API (Livewire contract) ---------------- */

    init(container, initialStatus = 'working') {
        if (!container) return this;

        // Take over cleanly: the legacy inline engine may already own a
        // render loop on this container. Stop it before we mount ours, or two
        // scenes end up composited on top of each other.
        this._takeoverLegacy(container);

        // Livewire re-runs Alpine init(), which calls init() again. Tear down
        // our own previous renderer first so the container holds exactly one
        // canvas.
        if (this.ready) this.dispose();
        this._disposed = false;

        this.container = container;
        this.container.style.position = 'relative';
        this.container.style.overflow = 'hidden';

        this._initThree();
        this._buildWorld();
        this._buildAgents();
        this._initComposer();

        this.nav = new NavGrid();

        this.rig = new CameraRig(this.camera, this.renderer.domElement);
        this.rig.agents = this.agents;

        // Livewire assigns currentCsStatus / currentSinggihStatus /
        // currentAnderaStatus BEFORE calling init(), so honour those instead of
        // blindly stamping every agent with the single initialStatus.
        this.currentCsStatus = this.currentCsStatus || initialStatus || 'working';
        this.currentSinggihStatus = this.currentSinggihStatus || initialStatus || 'working';
        this.currentAnderaStatus = this.currentAnderaStatus || initialStatus || 'working';

        this.applyStatus('dewi', this.currentCsStatus);
        this.applyStatus('singgih', this.currentSinggihStatus);
        this.applyStatus('andera', this.currentAnderaStatus);

        this.ready = true;
        this._loop();

        // tell Livewire the scene is alive so it can stop showing a spinner
        window.dispatchEvent(new CustomEvent('three-office-ready'));
        return this;
    }

    /** Stop and unmount the engine that shipped inline in the Blade file. */
    _takeoverLegacy(container) {
        const legacy = this.legacy;
        if (!legacy) return;
        this.legacy = null;
        try {
            if (legacy.animationFrameId) cancelAnimationFrame(legacy.animationFrameId);
            legacy.animationFrameId = null;
            for (const id of legacy._rafs || []) cancelAnimationFrame(id);
            legacy.renderer?.dispose?.();
            legacy.composer?.dispose?.();
            legacy.scene?.traverse?.((o) => {
                if (o.isMesh) {
                    o.geometry?.dispose?.();
                    const mats = Array.isArray(o.material) ? o.material : [o.material];
                    for (const m of mats) m?.dispose?.();
                }
            });
            legacy.renderer?.domElement?.remove?.();
            window.removeEventListener('resize', legacy.onResize);
        } catch (err) {
            console.warn('[office3d] legacy takeover', err);
        }
        container.innerHTML = '';
    }

    /**
     * The single funnel every WebSocket/Livewire update goes through.
     * Maps the wire payload onto our three agents.
     */
    requestAgentStatus(character, status) {
        const name = String(character || '').toLowerCase();
        if (!name) return;
        const normalised = status === 'idle' ? 'break' : status;
        const aliases = {
            dewi: 'dewi', cs: 'dewi', customer: 'dewi', 'customer service': 'dewi',
            singgih: 'singgih', core: 'singgih', dispatcher: 'singgih',
            andera: 'andera', report: 'andera', finance: 'andera',
        };
        const key = aliases[name];
        if (!key) return;

        if (key === 'dewi') this.currentCsStatus = normalised;
        if (key === 'singgih') this.currentSinggihStatus = normalised;
        if (key === 'andera') this.currentAnderaStatus = normalised;

        this.applyStatus(key, normalised);
    }

    updateCsPosition(status) {
        this.currentCsStatus = status;
        this.applyStatus('dewi', status);
    }

    updateSinggihPosition(status) {
        this.currentSinggihStatus = status;
        this.applyStatus('singgih', status);
    }

    updateAnderaPosition(status) {
        this.currentAnderaStatus = status;
        this.applyStatus('andera', status);
    }

    setView(name) {
        this.rig?.setView(name);
        return this;
    }

    zoomIn() {
        this.rig?.zoomIn();
    }

    zoomOut() {
        this.rig?.zoomOut();
    }

    toggleExpand() {
        const el = this.container;
        if (!el) return;
        el.classList.toggle('is-expanded');
        // let the layout settle a frame, then match the drawing buffer
        requestAnimationFrame(() => this._resize());
    }

    updateLiveBubble(agent, text) {
        const a = this.agents[agent];
        if (!a || !text) return;
        a.bubble.show(text, a.label, 7, this.time);
        // talking: the agent turns toward whoever it is speaking to instead
        // of staring at the monitor
        a.controller.lookAt(
            a.group.position.x + Math.sin(this.time * 0.6 + a.monitor) * 1.6,
            1.42,
            a.group.position.z + Math.cos(this.time * 0.5 + a.monitor) * 1.6
        );
    }

    dispose() {
        this._disposed = true;
        cancelAnimationFrame(this._raf);
        window.removeEventListener('resize', this._resize);
        this.renderer?.dispose();
        this.scene?.traverse?.((o) => {
            if (o.isMesh) {
                o.geometry?.dispose?.();
                const mats = Array.isArray(o.material) ? o.material : [o.material];
                for (const m of mats) m?.dispose?.();
            }
        });
        this.composer?.dispose?.();
        if (this.renderer?.domElement?.parentNode) {
            this.renderer.domElement.remove();
        }
        this.ready = false;
    }

    /* ---------------- status -> behaviour ---------------- */

    applyStatus(name, status) {
        const a = this.agents[name];
        if (!a) return;
        a.status = status;
        const c = a.controller;

        if (status === 'sleeping') {
            const spot = name === 'dewi' ? BED_SPOT : { x: 7.3, y: 0.45, z: 3.0 };
            this._goTo(a, spot.x, spot.z, 'sleep');
            a.bubble.sprite.visible = false;
        } else if (status === 'break') {
            const seat = SEATS[`${name}Sofa`];
            if (seat) this._goTo(a, seat.x, seat.z, 'sit');
            else this._goTo(a, STAND_SPOTS[0].x, STAND_SPOTS[0].z, 'read');
        } else {
            const seat = SEATS[`${name}Desk`];
            if (seat) this._goTo(a, seat.x, seat.z, 'type');
        }
        void c;
    }

    /** Route the agent to (x,z) with A*, then hand the path to the rig. */
    _goTo(a, x, z, finalPose) {
        const c = a.controller;
        const path = this.nav
            ? this.nav.findPath(c.pos.x, c.pos.z, x, z)
            : [new THREE.Vector3(x, 0, z)];

        const points = (path && path.length ? path : [new THREE.Vector3(x, 0, z)])
            .map((p) => new THREE.Vector3(p.x, 0, p.z));
        points[points.length - 1] = new THREE.Vector3(x, 0, z);

        c.path = points;
        c.pathIndex = 1;
        c.finalPose = finalPose;

        const first = points[1] || points[0];
        c.walkTo(first.x, first.z);
    }

    /* ---------------- three.js bootstrap ---------------- */

    _initThree() {
        const w = Math.max(this.container.clientWidth, 320);
        const h = Math.max(this.container.clientHeight, 240);

        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0b0e12);
        this.scene.fog = new THREE.Fog(0x11151b, 26, 62);

        this.camera = new THREE.PerspectiveCamera(42, w / h, 0.08, 220);

        this.renderer = new THREE.WebGLRenderer({
            antialias: false,
            powerPreference: 'high-performance',
        });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.setSize(w, h);
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.02;
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.container.appendChild(this.renderer.domElement);

        this._resize = this._resize.bind(this);
        window.addEventListener('resize', this._resize);

        this._initLighting();
    }

    _resize() {
        if (!this.renderer || !this.container) return;
        const w = Math.max(this.container.clientWidth, 320);
        const h = Math.max(this.container.clientHeight, 240);
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(w, h);
        this.composer?.setSize?.(w, h);
    }

    /**
     * Lighting is what separates "a 3D render" from "a photograph of a room".
     * Key: one sun through the +X glazing. Fill: sky/ground hemisphere. Practicals:
     * warm ceiling pendants + a cool cove strip. Everything is real geometry with
     * real shadow casting, so contact darkening appears on its own.
     */
    _initLighting() {
        const scene = this.scene;

        // --- image-based ambient: RoomEnvironment gives PBR materials a
        //     believable indirect response without hand-tuning every light.
        if (addons.RoomEnvironment) {
            const pmrem = new THREE.PMREMGenerator(this.renderer);
            const envScene = new addons.RoomEnvironment();
            scene.environment = pmrem.fromScene(envScene, 0.04).texture;
            scene.environmentIntensity = 0.42;
            pmrem.dispose();
        }

        // --- sky / ground hemisphere fill
        const hemi = new THREE.HemisphereLight(0xbcd4f2, 0x6b5f52, 0.55);
        hemi.position.set(0, P.ceilH, 0);
        scene.add(hemi);

        // --- key: low afternoon sun raking in through the lounge + pantry glazing
        const sun = new THREE.DirectionalLight(0xfff0d4, 2.35);
        sun.position.set(16.5, 8.4, 7.5);
        sun.target.position.set(-1.5, 0.6, -0.8);
        sun.castShadow = true;
        sun.shadow.mapSize.set(2048, 2048);
        sun.shadow.camera.near = 1;
        sun.shadow.camera.far = 46;
        const S = 15;
        sun.shadow.camera.left = -S;
        sun.shadow.camera.right = S;
        sun.shadow.camera.top = S;
        sun.shadow.camera.bottom = -S;
        sun.shadow.bias = -0.0006;
        sun.shadow.normalBias = 0.022;
        sun.shadow.radius = 2.4;
        scene.add(sun);
        scene.add(sun.target);
        this.sun = sun;

        // --- bounce: dim upward fill so ceilings/floors aren't dead black
        const bounce = new THREE.DirectionalLight(0xd8e4f2, 0.34);
        bounce.position.set(-9, -4, -7);
        scene.add(bounce);

        // --- warm practical over the office desk run
        const office = new THREE.PointLight(0xffdcab, 9.5, 8.4, 2);
        office.position.set(-2.2, P.ceilH - 0.42, 0.1);
        scene.add(office);

        // --- cool practical over the lounge
        const lounge = new THREE.PointLight(0xfff1dc, 7.2, 7.6, 2);
        lounge.position.set(6.6, P.ceilH - 0.5, 2.0);
        scene.add(lounge);

        // --- pantry strip
        const pantry = new THREE.PointLight(0xf4f7ff, 6.0, 6.6, 2);
        pantry.position.set(5.4, P.ceilH - 0.45, -3.9);
        scene.add(pantry);

        // --- bathroom: warm downlight so the WC reads as a real room
        const bath = new THREE.PointLight(0xffe6c2, 5.2, 4.4, 2);
        bath.position.set(-7.4, P.ceilH - 0.35, 0.95);
        scene.add(bath);

        // --- bedroom lamp practical
        const bed = new THREE.PointLight(0xffcf95, 4.0, 4.6, 2);
        bed.position.set(-7.6, 1.05, -4.2);
        scene.add(bed);

        // --- entry
        const entry = new THREE.PointLight(0xffe8c8, 5.0, 5.4, 2);
        entry.position.set(-6.8, P.ceilH - 0.4, 4.0);
        scene.add(entry);
    }

    /* ---------------- world assembly ---------------- */

    _buildWorld() {
        const M = createMaterials();
        this.M = M;

        this.shell = buildShell(this.scene, M);
        this._buildOpenings(M);
        this._buildFurniture(M);
        this._buildFixtures(M);
    }

    /**
     * Doors + windows. This is what the bathroom-door complaint was about:
     * the wall has a real punched opening, and now a real leaf + frame fills it.
     */
    _buildOpenings(M) {
        const group = new THREE.Group();
        group.name = 'openings';

        const DOOR_STYLE = {
            bathroom: { style: 'panel', color: 0xf1efe9, hingeSide: 1, swing: -1, open: true, label: 'Bathroom' },
            bedroom: { style: 'panel', color: 0xefece4, hingeSide: -1, swing: 1, open: false, label: 'Bedroom' },
            pantry: { style: 'glass', color: 0xe8ecef, hingeSide: 1, swing: -1, open: false, label: 'Pantry' },
        };

        for (const wall of WALLS) {
            for (const o of wall.openings || []) {
                const isDoor = o.kind === 'door' || o.kind === 'glassdoor';
                const isCased = o.kind === 'cased';

                if (isDoor) {
                    const s = DOOR_STYLE[o.id] || { style: 'panel', color: 0xf1efe9, hingeSide: 1, swing: -1, open: false };
                    const door = buildDoor(M, {
                        id: o.id || 'door',
                        axis: wall.axis,
                        at: wall.at,
                        cx: o.cx,
                        w: o.w,
                        h: o.h,
                        wallT: wall.t,
                        ...s,
                    });
                    setShadowRecursive(door, true, true);
                    group.add(door);
                    this.doors[o.id || 'door'] = door;
                } else if (o.kind === 'window') {
                    const win = buildWindow(M, {
                        axis: wall.axis,
                        at: wall.at,
                        cx: o.cx,
                        w: o.w,
                        h: o.h,
                        sill: o.sill,
                        frosted: !!o.frosted,
                        wallT: wall.t,
                    });
                    setShadowRecursive(win, false, true);
                    group.add(win);
                } else if (isCased) {
                    // a cased opening gets a lining + architrave but no leaf
                    const cas = buildDoor(M, {
                        id: 'cased-' + wall.axis + wall.at,
                        axis: wall.axis,
                        at: wall.at,
                        cx: o.cx,
                        w: o.w,
                        h: o.h,
                        wallT: wall.t,
                        style: 'flush',
                        color: 0xefece4,
                        noLeaf: true,
                        hingeSide: 1,
                        swing: -1,
                        open: false,
                    });
                    setShadowRecursive(cas, true, true);
                    group.add(cas);
                }
            }
        }

        // front door, on the +Z exterior wall
        const front = buildDoor(M, {
            id: 'front',
            axis: 'x',
            at: P.outer.maxZ,
            cx: P.openFrontDoor.x,
            w: P.openFrontDoor.w,
            h: P.doorH + P.doorHead,
            wallT: P.wallExt,
            style: 'panel',
            color: 0x3f4a56,
            hingeSide: -1,
            swing: -1,
            open: false,
            label: 'Front Door',
        });
        setShadowRecursive(front, true, true);
        group.add(front);
        this.doors.front = front;

        this.scene.add(group);
        this.openings = group;
    }

    _buildFurniture(M) {
        const group = new THREE.Group();
        group.name = 'furniture';
        const put = (obj, x, y, z, ry = 0) => {
            obj.position.set(x, y, z);
            obj.rotation.y = ry;
            setShadowRecursive(obj, true, true);
            group.add(obj);
            return obj;
        };

        /* ---------- office ---------- */
        const desk = buildDeskBench(M);
        put(desk, -2.25, 0, 0.0, 0);

        put(buildTaskChair(M), SEATS.dewiDesk.x, 0, SEATS.dewiDesk.z, Math.PI);
        put(buildTaskChair(M), SEATS.singgihDesk.x, 0, SEATS.singgihDesk.z, 0);
        put(buildTaskChair(M), SEATS.anderaDesk.x, 0, SEATS.anderaDesk.z, Math.PI);

        this.monitors = [];
        const monSpecs = [
            { x: -2.6, ry: Math.PI, w: 0.6 },
            { x: -1.2, ry: 0, w: 0.56 },
            { x: -0.55, ry: Math.PI, w: 0.52 },
        ];
        for (const [i, m] of monSpecs.entries()) {
            const screen = artworkCanvas((ctx, w, h) => drawDeskScreen(ctx, w, h, i), 512, 320);
            const mon = buildMonitor(M, { w: m.w, h: 0.34, screenTex: screen });
            put(mon, m.x, 0.755, 0.12, m.ry);
            this.monitors.push(screen);
        }

        put(buildDeskInput(M), -2.6, 0.755, 0.30, Math.PI);
        put(buildDeskInput(M), -1.2, 0.755, -0.28, 0);
        put(buildDeskInput(M), -0.55, 0.755, 0.30, Math.PI);

        put(buildLaptop(M, true), -1.95, 0.755, -0.14, 0.14);
        put(buildLaptop(M, true), -0.9, 0.755, 0.2, Math.PI - 0.2);

        put(buildDeskLamp(M, 0x2f3742), -3.5, 0.755, -0.2, 0.5);
        put(buildDeskLamp(M, 0x8a5a2b), 0.0, 0.755, -0.22, -0.4);

        put(buildMug(M, { color: 0xf3f1ec }), -2.28, 0.755, 0.22, 0.6);
        put(buildMug(M, { color: 0xe0796b }), -1.42, 0.755, -0.2, 0.2);
        put(buildMug(M, { color: 0x6f9f8f }), -0.78, 0.755, 0.16, 1.1);

        put(buildPaperStack(M, { sheets: 8, seed: 4 }), -3.0, 0.755, 0.06, 0.12);
        put(buildPaperStack(M, { sheets: 5, seed: 9 }), -1.62, 0.755, 0.2, -0.3);
        put(buildPaperStack(M, { sheets: 11, seed: 12 }), -0.3, 0.755, 0.02, 0.5);

        put(buildRug(M, { w: 3.4, d: 2.2, color: 0x8d8175, seed: 3 }), -2.2, 0.001, 0.55, 0);
        put(buildRug(M, { w: 3.0, d: 2.0, color: 0x9a8c7a, seed: 8 }), -2.2, 0.001, -1.4, 0);

        put(buildServerRack(M), -3.82, 0, -5.72, Math.PI / 2);
        put(buildBookshelf(M), -2.3, 0, -5.78, 0);
        put(buildWallClock(M), -4.31, 2.05, -2.0, Math.PI / 2);
        put(buildACUnit(M), 0.9, 2.5, -5.78, 0);
        put(buildFrame(M, {
            w: 0.62, h: 0.8, seed: 2,
            draw: (ctx, w, h) => drawPoster(ctx, w, h, {
                title: 'OPS', sub: 'live floor', bg: '#1d2530', fg: '#f4efe6', accent: '#e8a33d',
            }),
        }), -4.28, 1.62, 0.4, Math.PI / 2);
        put(buildFrame(M, {
            w: 0.46, h: 0.58, seed: 6,
            draw: (ctx, w, h) => drawPoster(ctx, w, h, {
                title: '24/7', sub: 'dispatch', bg: '#22303a', fg: '#eaf2f6', accent: '#57b8c8',
            }),
        }), -4.28, 1.55, -1.6, Math.PI / 2);

        /* ---------- lounge ---------- */
        put(buildSofa(M, { seats: 3, w: 3.0, fabricColor: 0x8d949c }), 7.5, 0, 1.8, -Math.PI / 2);
        put(buildCoffeeTable(M), 5.35, 0, 1.7, 0);
        put(buildMediaConsole(M), 3.2, 0, 1.7, Math.PI / 2);
        put(buildTV(M, { w: 1.35, h: 0.78 }), 3.35, 1.35, 1.7, Math.PI / 2);
        put(buildRug(M, { w: 3.6, d: 2.8, color: 0x7f8a94, seed: 15 }), 5.9, 0.001, 2.2, 0);
        put(buildFloorLamp(M), 7.75, 0, 4.85, 0);
        put(buildSnakePlant(M, { h: 1.05, seed: 5 }), 6.8, 0, 3.95, 0);
        put(buildBookshelf(M, { w: 1.2 }), 8.7, 0, -3.9, -Math.PI / 2);

        /* ---------- pantry / kitchen ---------- */
        put(buildKitchenRun(M, { len: 6.2 }), 5.5, 0, -5.55, 0);
        put(buildUpperCabinets(M, { len: 5.4 }), 5.5, 0, -5.68, 0);
        put(buildSink(M), 3.5, 0.9, -5.5, 0);
        put(buildHob(M), 6.4, 0.9, -5.5, 0);
        put(buildFridge(M), 2.65, 0, -2.42, 0);
        put(buildIsland(M, { w: 2.0, d: 1.0 }), 5.4, 0, -2.85, 0);
        put(buildStool(M), 5.0, 0, -2.0, 0);
        put(buildStool(M), 5.8, 0, -2.0, 0);
        put(buildKitchenClutter(M, { seed: 5 }), 5.5, 0.9, -5.5, 0);
        put(buildDishRack(M), 5.95, 0.9, -5.5, 0.3);
        put(buildSmallPlant(M, { seed: 9 }), 4.35, 0.92, -5.5, 0);

        /* ---------- bedroom ---------- */
        put(buildBed(M), BED_SPOT.x, 0, BED_SPOT.z, Math.PI / 2);
        put(buildNightstand(M), -8.68, 0, -5.32, 0);
        put(buildTableLamp(M), -8.68, 0.52, -5.32, 0);
        put(buildWardrobe(M), -6.2, 0, -4.6, -Math.PI / 2);
        put(buildRug(M, { w: 2.4, d: 1.7, color: 0xa08b78, seed: 21 }), -7.3, 0.001, -2.2, 0);
        put(buildCurtains(M, { w: 1.35, h: 1.85, side: 1 }), -8.72, 0.9, -3.6, Math.PI / 2);
        put(buildFiddleLeafPlant(M, { h: 1.5, seed: 2 }), -6.6, 0, -1.4, 0);
        put(buildFrame(M, {
            w: 0.5, h: 0.64, seed: 11,
            draw: (ctx, w, h) => drawPoster(ctx, w, h, {
                title: 'quiet', sub: 'bedroom', bg: '#3a3038', fg: '#f2e8e4', accent: '#c98f9f',
            }),
        }), -5.87, 1.6, -4.2, -Math.PI / 2);

        /* ---------- bathroom ---------- */
        put(buildShower(M), -8.2, 0, 0.22, 0);
        put(buildVanity(M), -6.8, 0, -0.2, 0);
        put(buildToilet(M), -8.6, 0, 1.7, Math.PI);
        put(buildTowelRail(M, { w: 0.6 }), -5.95, 1.25, 1.3, -Math.PI / 2);
        put(buildSmallPlant(M, { seed: 4 }), -6.15, 0.86, -0.28, 0);

        /* ---------- entry ---------- */
        put(buildConsoleTable(M), -6.6, 0, 5.66, Math.PI);
        put(buildCoatRack(M), -8.0, 0, 4.3, Math.PI / 2);
        put(buildShoeRack(M), -6.6, 0, 3.3, Math.PI);
        put(buildFiddleLeafPlant(M, { h: 1.6, seed: 7 }), -8.35, 0, 4.3, 0);
        put(buildRug(M, { w: 1.8, d: 1.2, color: 0x8b7f70, seed: 33 }), -6.6, 0.001, 4.5, 0);

        /* ---------- plants against the office walls ---------- */
        put(buildFiddleLeafPlant(M, { h: 1.45, seed: 3 }), 1.42, 0, -5.65, 0);
        put(buildSnakePlant(M, { h: 1.2, seed: 8 }), 1.05, 0, 4.95, 0);
        put(buildFiddleLeafPlant(M, { h: 1.35, seed: 5 }), -4.0, 0, 4.55, 0);

        this.furniture = group;
        this.scene.add(group);
    }

    _buildFixtures(M) {
        const group = new THREE.Group();
        group.name = 'fixtures';
        const put = (obj, x, y, z) => {
            obj.position.set(x, y, z);
            setShadowRecursive(obj, true, false);
            group.add(obj);
        };

        // office pendants over the desk run
        for (const x of [-3.3, -2.25, -1.2, -0.15]) {
            put(buildCeilingLamp(M, { x, z: 0.1, drop: 0.42, shade: 0.2 }), x, P.ceilH, 0.1);
        }
        // cove spots down the hall
        put(buildCeilingSpotRow(M, { x: -5.1, z: -1.2, count: 3, spacing: 1.1 }), -5.1, P.ceilH, -1.2);
        // lounge pendant
        put(buildCeilingLamp(M, { x: 6.6, z: 2.0, drop: 0.5, shade: 0.24 }), 6.6, P.ceilH, 2.0);
        // pantry linear run
        put(buildCeilingSpotRow(M, { x: 5.4, z: -3.9, count: 4, spacing: 0.9 }), 5.4, P.ceilH, -3.9);
        // entry + bath + bedroom
        put(buildCeilingLamp(M, { x: -6.8, z: 4.0, drop: 0.34, shade: 0.18 }), -6.8, P.ceilH, 4.0);
        put(buildCeilingLamp(M, { x: -7.4, z: 0.95, drop: 0.3, shade: 0.16 }), -7.4, P.ceilH, 0.95);
        put(buildCeilingLamp(M, { x: -7.6, z: -4.3, drop: 0.34, shade: 0.18 }), -7.6, P.ceilH, -4.3);

        this.fixtures = group;
        this.scene.add(group);
    }

    /* ---------------- agents ---------------- */

    _buildAgents() {
        const M = this.M;
        for (const [name, spec] of Object.entries(AGENT_SPEC)) {
            const group = new THREE.Group();
            group.name = 'agent-' + name;

            const hum = buildHumanoid(M, {
                shirtColor: spec.shirtColor,
                pantsColor: spec.pantsColor,
                hairColor: spec.hairColor,
                skinColor: spec.skinColor,
                shoeColor: spec.shoeColor,
                hair: spec.hair,
                bangs: spec.bangs,
                seed: spec.seed,
            });

            const body = new THREE.Group();
            body.name = 'body';
            hum.remove(hum.userData.body);
            body.add(hum.userData.body);
            group.add(body);
            hum.userData.body = body;

            setShadowRecursive(hum, true, false);
            group.add(hum);

            const bubble = makeBubble();
            bubble.sprite.position.set(0, 2.28, 0);
            group.add(bubble.sprite);

            this.scene.add(group);

            const controller = new CharacterController(hum, { seed: spec.seed });
            const seat = SEATS[spec.deskSeat];
            controller.setPosition(seat.x, seat.z, seat.rotY);
            controller.setPose('type');

            this.agents[name] = {
                name,
                label: spec.role,
                spec,
                group,
                rig: hum,
                controller,
                bubble,
                status: 'working',
                wanderTimer: this.rng.float(4, 14),
                monitor: 0,
            };
        }

        // name tags, so you always know who you are looking at
        for (const [name, a] of Object.entries(this.agents)) {
            a.tag = this._makeNameTag(a.label);
            a.tag.position.set(0, 2.62, 0);
            a.group.add(a.tag);
            void name;
        }
    }

    _makeNameTag(text) {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 64;
        const ctx = canvas.getContext('2d');
        ctx.font = '700 26px ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
        const w = Math.min(ctx.measureText(text).width + 36, 250);
        ctx.fillStyle = 'rgba(14,18,23,0.78)';
        ctx.beginPath();
        ctx.roundRect(128 - w / 2, 12, w, 40, 20);
        ctx.fill();
        ctx.fillStyle = '#f2f5f8';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 128, 33);
        const tex = new THREE.CanvasTexture(canvas);
        tex.colorSpace = THREE.SRGBColorSpace;
        const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: false }));
        sp.scale.set(1.0, 0.25, 1);
        sp.renderOrder = 998;
        return sp;
    }

    /* ---------------- composer ---------------- */

    _initComposer() {
        const { EffectComposer, RenderPass, UnrealBloomPass, OutputPass, SMAAPass, GTAOPass } = addons;
        if (!EffectComposer) {
            this.composer = null;
            this._render = () => this.renderer.render(this.scene, this.camera);
            return;
        }

        const w = this.renderer.domElement.width;
        const h = this.renderer.domElement.height;

        const composer = new EffectComposer(this.renderer);
        composer.addPass(new RenderPass(this.scene, this.camera));

        if (GTAOPass) {
            try {
                const gtao = new GTAOPass(this.scene, this.camera, w, h);
                gtao.output = GTAOPass.OUTPUT.Default;
                gtao.updateGtaoMaterial({
                    radius: 0.32,
                    distanceExponent: 1.6,
                    thickness: 0.9,
                    scale: 1.0,
                    samples: 12,
                    distanceFallOff: 1.0,
                    screenSpaceRadius: false,
                });
                gtao.blendIntensity = 0.95;
                composer.addPass(gtao);
                this.gtao = gtao;
            } catch (err) {
                console.warn('[office3d] GTAO unavailable', err);
            }
        }

        const bloom = new UnrealBloomPass(new THREE.Vector2(w, h), 0.24, 0.62, 0.86);
        composer.addPass(bloom);
        this.bloom = bloom;

        composer.addPass(new OutputPass());

        if (SMAAPass) {
            try {
                const dpr = this.renderer.getPixelRatio();
                composer.addPass(new SMAAPass(w * dpr, h * dpr));
            } catch (err) {
                console.warn('[office3d] SMAA unavailable', err);
            }
        }

        this.composer = composer;
        this._render = () => composer.render();
    }

    /* ---------------- frame ---------------- */

    _loop() {
        const tick = () => {
            if (this._disposed) return;
            this._raf = requestAnimationFrame(tick);

            const dt = Math.min(this.clock.getDelta(), 1 / 20);
            this.time += dt;

            this._updateAgents(dt);
            this.rig.update(dt);
            this._render();
        };
        this._raf = requestAnimationFrame(tick);
    }

    _updateAgents(dt) {
        for (const [name, a] of Object.entries(this.agents)) {
            const c = a.controller;

            // follow the A* waypoint chain
            if (c.path && c.pathIndex < c.path.length && c.pose === 'walk') {
                const wp = c.path[c.pathIndex];
                const dx = wp.x - c.pos.x;
                const dz = wp.z - c.pos.z;
                if (Math.hypot(dx, dz) < 0.16) {
                    c.pathIndex++;
                    if (c.pathIndex >= c.path.length) {
                        c.path = null;
                        c.stopWalking();
                        const final = c.finalPose || 'idle';
                        if (final === 'sleep' && name === 'dewi') {
                            c.setPose('sleep');
                            this._layDewiInBed(a);
                        } else {
                            c.setPose(final);
                        }
                    } else {
                        const next = c.path[c.pathIndex];
                        c.walkTo(next.x, next.z);
                    }
                }
            }

            c.update(dt, this.time);

            // face the desk monitor while working
            if (a.status === 'working' && (c.pose === 'type' || c.pose === 'sit')) {
                c.lookAt(a.group.position.x * 0.2, 1.16, 0.05);
            } else if (a.status === 'break' && c.pose === 'sit') {
                c.lookAt(5.4, 1.1, 1.7);
            } else {
                // idle gaze wanders slowly instead of staring at nothing
                const t = this.time * 0.22 + a.monitor;
                c.lookAt(
                    a.group.position.x + Math.sin(t) * 3.4,
                    1.35 + Math.sin(t * 0.7) * 0.25,
                    a.group.position.z + Math.cos(t * 0.85) * 3.4
                );
            }

            // bubble lifetime + head bob
            if (a.bubble.until && this.time > a.bubble.until) {
                a.bubble.sprite.visible = false;
                a.bubble.until = 0;
            }
            const bob = Math.sin(this.time * 1.1 + a.monitor) * 0.012;
            a.bubble.sprite.position.y = (c.pose === 'sleep' ? 0.9 : 2.28) + bob;
            a.bubble.sprite.visible = a.bubble.sprite.visible && c.pose !== 'walk';
            a.tag.position.y = (c.pose === 'sleep' ? 0.75 : 2.62) + bob;
            a.tag.visible = c.pose !== 'sleep';
        }
    }

    /** Snap the sleeping agent onto the pillow. */
    _layDewiInBed(a) {
        const c = a.controller;
        c.pos.set(BED_SPOT.x, 0, BED_SPOT.z);
        c.facing = BED_SPOT.rotY;
        c.targetFacing = BED_SPOT.rotY;
        a.group.position.set(BED_SPOT.x, 0, BED_SPOT.z);
        a.group.rotation.y = BED_SPOT.rotY;
    }
}

/* --- monitor screen content ---------------------------------------- */
function drawDeskScreen(ctx, w, h, which) {
    ctx.fillStyle = '#0f1620';
    ctx.fillRect(0, 0, w, h);

    // fake window chrome
    ctx.fillStyle = '#182231';
    ctx.fillRect(0, 0, w, 34);
    ctx.fillStyle = '#2a3a4d';
    for (let i = 0; i < 3; i++) ctx.fillRect(14 + i * 22, 12, 12, 10);

    // sidebar
    ctx.fillStyle = '#141d29';
    ctx.fillRect(0, 34, 92, h - 34);
    ctx.fillStyle = '#22303f';
    for (let i = 0; i < 7; i++) ctx.fillRect(14, 58 + i * 26, 64, 9);

    // main rows
    const palettes = [
        ['#1f6feb', '#2ea043', '#d29922', '#8957e5'],
        ['#2ea043', '#1f6feb', '#d29922', '#f85149'],
        ['#8957e5', '#d29922', '#1f6feb', '#2ea043'],
    ][which % 3];

    for (let r = 0; r < 8; r++) {
        const y = 52 + r * 30;
        ctx.fillStyle = r % 2 ? '#131b26' : '#16202c';
        ctx.fillRect(104, y, w - 124, 24);
        ctx.fillStyle = palettes[r % palettes.length];
        ctx.fillRect(112, y + 7, 10, 10);
        ctx.fillStyle = '#3b4c60';
        ctx.fillRect(132, y + 9, 60 + ((r * 37) % 90), 7);
    }
}

/* ==================================================================== *
 *  BOOT — install the global contract Livewire talks to.
 * ==================================================================== */

function boot() {
    if (window.__office3dBooted) {
        // already installed; just refresh the handle in case Livewire replaced
        // the global object between renders
        reinstall();
        return;
    }
    window.__office3dBooted = true;

    const app = new OfficeApp();

    // The Blade template assigns window._threeOfficeApp from a *classic* inline
    // script, which runs while the document is still parsing. This module is
    // deferred, so by the time we get here that object already exists — we keep
    // it (and its shape) and layer our methods on top.
    const legacy = window._threeOfficeApp;
    app.legacy = legacy && typeof legacy.init === 'function' ? legacy : null;
    app._apiTarget = legacy && typeof legacy.init === 'function' ? legacy : {};

    // Livewire seeds these before init(); carry them into the engine.
    if (legacy) {
        app.currentCsStatus = legacy.currentCsStatus;
        app.currentSinggihStatus = legacy.currentSinggihStatus;
        app.currentAnderaStatus = legacy.currentAnderaStatus;
    }

    install(app);
    window.__office3d = app;
}

/** Write the engine API onto the object Livewire/Alpine already holds. */
function install(app) {
    const target = app._apiTarget || (window._threeOfficeApp = {});
    app._apiTarget = target;

    const methods = {
        init: (container, status) => app.init(container, status),
        updateCsPosition: (s) => app.updateCsPosition(s),
        updateSinggihPosition: (s) => app.updateSinggihPosition(s),
        updateAnderaPosition: (s) => app.updateAnderaPosition(s),
        requestAgentStatus: (character, status) => app.requestAgentStatus(character, status),
        updateLiveBubble: (a, t) => app.updateLiveBubble(a, t),
        setView: (v) => app.setView(v),
        zoomIn: () => app.zoomIn(),
        zoomOut: () => app.zoomOut(),
        toggleExpand: () => app.toggleExpand(),
        toggleFullscreen: () => app.toggleExpand(),
        dispose: () => app.dispose(),
        _engine: app,
    };

    for (const [k, fn] of Object.entries(methods)) target[k] = fn;

    // keep the Livewire-mutated status fields mirrored onto the engine, since
    // Alpine assigns them directly on this object
    for (const prop of ['currentCsStatus', 'currentSinggihStatus', 'currentAnderaStatus']) {
        let backing = app[prop];
        Object.defineProperty(target, prop, {
            configurable: true,
            enumerable: true,
            get: () => backing,
            set: (v) => {
                backing = v;
                app[prop] = v;
            },
        });
    }

    for (const [prop, getter] of [
        ['scene', () => app.scene],
        ['camera', () => app.camera],
        ['renderer', () => app.renderer],
        ['composer', () => app.composer],
        ['dewiGroup', () => app.agents?.dewi?.group],
        ['singgihGroup', () => app.agents?.singgih?.group],
        ['anderaGroup', () => app.agents?.andera?.group],
    ]) {
        Object.defineProperty(target, prop, { configurable: true, enumerable: true, get: getter });
    }

    target.ready = true;
    window._threeOfficeApp = target;
}

/** Re-point the global at our engine (used if something overwrote it). */
function reinstall() {
    const app = window.__office3d;
    if (!app) return;
    if (window._threeOfficeApp && window._threeOfficeApp._engine === app) return;
    install(app);
}

if (window.THREE && window.OFFICE_ADDONS) {
    boot();
} else {
    window.addEventListener('three-office-runtime-ready', boot, { once: true });
    // the runtime may already be loaded (script order race)
    if (window.THREE && window.OFFICE_ADDONS) boot();
}

// Safety net: the classic inline script may be re-evaluated by Livewire, which
// would hand us a brand-new global object. Re-install on the events that
// signal a fresh page wiring.
if (typeof document !== 'undefined') {
    document.addEventListener('livewire:init', reinstall);
    window.addEventListener('three-office-ready', reinstall);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(reinstall, 0));
    }
}

export { NavGrid };