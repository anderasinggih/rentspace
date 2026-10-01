import * as THREE from 'three';
import { clamp, damp, dampAngle, angleDelta, Rng, lerp, smoothstep, easeInOutCubic } from './lib.js';
import { CharacterController } from './character.js';
import { ACTIVITIES, scoreActivity, TV_POINT } from './activities.js';

/* ================================================================== *
 *  Agent brain
 *
 *  One of these per character.  It owns the character's *intent* — where
 *  it is going, what it is doing there, which prop it is holding — and
 *  leaves the actual body motion to CharacterController.
 *
 *  The loop is deliberately three states rather than one:
 *
 *      travel    follow an A* path, steering around the other two
 *      perform   be at the spot, hold the pose, play the prop loop
 *      decide    a gap of pure idling, so arriving is never the instant
 *                the next decision fires
 *
 *  Decisions are utility-scored, not random-picked.  A score is the
 *  activity's appetite (which personality and which hour of the day set)
 *  times a travel discount times a fatigue factor for the current chat
 *  load, zeroed when a shared resource is occupied.  That combination
 *  is what produces a flat that looks inhabited: the diligent one works
 *  while the restless one is already in the kitchen.
 * ================================================================== */

/** Personality — per-agent multipliers on activity classes. */
const PERSONALITIES = {
    dewi: {
        seed: 11,
        work: 1.5, phone: 0.5, game: 0.25, drink: 1.1, idleStand: 0.5,
        nap: 0.7, stretch: 0.9, watchTv: 0.7, cook: 1.0, washUp: 1.1,
    },
    singgih: {
        seed: 23,
        work: 0.9, phone: 1.3, game: 1.9, drink: 0.8, idleStand: 1.3,
        nap: 1.2, stretch: 1.0, watchTv: 1.3, cook: 0.7, washUp: 0.6,
    },
    andera: {
        seed: 37,
        work: 1.1, phone: 1.1, game: 1.3, drink: 1.2, idleStand: 1.4,
        nap: 1.0, stretch: 1.3, watchTv: 1.0, cook: 1.2, washUp: 1.0,
    },
};

const CLASS_OF = {
    work: 'work',
    paperwork: 'work',
    server: 'work',
    shelf: 'work',
    cook: 'cook',
    brew: 'cook',
    eat: 'cook',
    snack: 'cook',
    tidy: 'cook',
    washUp: 'cook',
    game: 'game',
    watchTv: 'watchTv',
    nap: 'nap',
    sofaRest: 'idleStand',
    idleStand: 'idleStand',
    phone: 'phone',
    stretch: 'stretch',
    drink: 'drink',
    waterPlant: 'idleStand',
    shower: 'idleStand',
    washroom: 'idleStand',
    washFace: 'idleStand',
};

export class AgentBrain {
    /**
     * @param {CharacterController} body
     * @param {object} opts  { id, name, appearance, propKit, nav, palette }
     */
    constructor(body, opts = {}) {
        this.id = opts.id || 'agent';
        this.name = opts.name || this.id;
        this.body = body;
        this.nav = opts.nav;
        this.props = opts.propKit || null;
        this.rng = new Rng(PERSONALITIES[this.id]?.seed ?? 7);

        const p = PERSONALITIES[this.id] || {};
        this.tasteMap = p;

        this.state = 'decide';
        this.stateT = 0;
        this.hour = 9;

        /* --- decision state --- */
        this.activity = null;
        this.spot = null;
        this.path = null;
        this.pathIdx = 0;
        this.duration = 0;
        this.lastActivity = null;
        this.recent = [];
        this.cooldowns = new Map();

        /* --- what the person is physically doing, for the UI --- */
        this.status = 'idle';
        this.statusLabel = 'idle';

        /* --- crowd --- */
        this.vel = new THREE.Vector2();
        this.avoid = new THREE.Vector2();
        this.busy = 0;
        this.claimedSpot = null;
        this.claimedResource = null;

        /* --- per-frame prop bookkeeping --- */
        this.phase = 0;
        this._handPhase = [];
        this._lastPose = null;
        this._tmp3 = new THREE.Vector3();
        this._gaze = new THREE.Vector3(0, 1.4, 2);
    }

    /* ---------------- decision context ---------------- */

    taste(activityId) {
        const cls = CLASS_OF[activityId] || 'idleStand';
        return this.tasteMap[cls] ?? 1;
    }

    cooldownOf(id) {
        return this.cooldowns.get(id) || 0;
    }

    isTaken(resource, spot) {
        return this.props ? this.props.isTaken(resource, spot, this.id) : false;
    }

    /* ---------------- the loop ---------------- */

    update(dt, time, others, chatLoad) {
        dt = Math.min(dt, 1 / 20);
        this.stateT += dt;
        this.busy = damp(this.busy, chatLoad, 2, dt);

        for (const [k, v] of this.cooldowns) {
            if (v <= 0) this.cooldowns.delete(k);
            else this.cooldowns.set(k, v - dt);
        }

        switch (this.state) {
            case 'travel':
                this._travel(dt, time, others);
                break;
            case 'perform':
                this._perform(dt, time);
                break;
            default:
                this._decideIdle(dt, time);
                break;
        }
    }

    /** Sit still for a beat, then pick something. */
    _decideIdle(dt, time) {
        this.status = 'idle';
        this.body.setPose(this.rng.pick(['idle', 'idle', 'think', 'lean', 'phone']));

        if (this.stateT < 1.6 + this.rng.float(0, 2.4)) return;

        const pick = this._choose();
        if (!pick) {
            // nothing scored: back off and try again shortly rather than
            // hammering the scorer every frame
            this.stateT = 0;
            return;
        }
        this._begin(pick.activity, pick.spot);
    }

    _choose() {
        let best = null;
        let bestScore = 0;
        for (const activity of ACTIVITIES) {
            if (activity.exclusive && this.props?.isOccupiedByOthers(activity.resource, this.id)) continue;
            for (const spot of activity.spots) {
                const s = scoreActivity(activity, spot, {
                    hour: this.hour,
                    x: this.body.pos.x,
                    z: this.body.pos.z,
                    busy: this.busy,
                    lastActivity: this.lastActivity,
                    recent: this.recent,
                    cooldownOf: (id) => this.cooldownOf(id),
                    taste: (id) => this.taste(id),
                    isTaken: (r, sp) => this.isTaken(r, sp),
                });
                // a little noise so two identical agents do not lock onto
                // the same choice purely because their scores are equal
                const jitter = 1 + this.rng.float(-0.12, 0.12);
                if (s * jitter > bestScore) {
                    bestScore = s * jitter;
                    best = { activity, spot };
                }
            }
        }
        return best;
    }

    _begin(activity, spot) {
        this.activity = activity;
        this.spot = spot;
        this.state = 'travel';
        this.stateT = 0;
        this.phase = 0;
        this.claimedSpot = spot;
        this.claimedResource = activity.resource || null;
        this.statusLabel = activity.label;

        // Reserve the resource immediately, before anyone else can decide.
        this.props?.claim(activity.resource, spot, this.id);

        const from = { x: this.body.pos.x, z: this.body.pos.z };
        const goal = spot.approach || spot;
        this.path = this.nav?.findPath(from.x, from.z, goal.x, goal.z) || [goal];
        this.pathIdx = 1;

        // face the direction of travel while walking
        this.body.setPose('walk');
    }

    _arrive() {
        const { activity, spot } = this;
        this.state = 'perform';
        this.stateT = 0;
        this.phase = 0;
        this.duration = this.rng.float(activity.minDuration[0], activity.minDuration[1]);

        // turn to face the spot, then settle into the pose
        this.body.targetFacing = spot.rotY;
        this.body.stopWalking();
        this.body.clearReach();
        this.body.setPose(activity.pose);

        this._handPhase = (activity.handPhase || []).slice();

        if (activity.prop && this.props) this.props.acquire(activity.prop, this.id, this.body);
        activity.onEnter?.(this._ctx());
    }

    /* ---------------- travel ---------------- */

    _travel(dt, time, others) {
        this.status = 'walking';
        const body = this.body;

        // advance along the path
        const path = this.path;
        let guard = 0;
        while (this.pathIdx < path.length && guard++ < 8) {
            const wp = path[this.pathIdx];
            const d = Math.hypot(wp.x - body.pos.x, wp.z - body.pos.z);
            if (d < 0.22) {
                this.pathIdx++;
            } else break;
        }

        if (this.pathIdx >= path.length) {
            this._arrive();
            return;
        }

        // steering: toward the waypoint, plus a shove away from anyone close
        const wp = path[this.pathIdx];
        let dx = wp.x - body.pos.x;
        let dz = wp.z - body.pos.z;
        const len = Math.hypot(dx, dz) || 1;
        dx /= len;
        dz /= len;

        this.avoid.set(0, 0);
        for (const o of others) {
            if (o === this) continue;
            const ox = body.pos.x - o.body.pos.x;
            const oz = body.pos.z - o.body.pos.z;
            const dist = Math.hypot(ox, oz);
            if (dist < 0.95 && dist > 1e-4) {
                const push = (0.95 - dist) / 0.95;
                this.avoid.x += (ox / dist) * push;
                this.avoid.y += (oz / dist) * push;
            }
        }

        const targetVX = dx * 1.05 + this.avoid.x * 0.85;
        const targetVZ = dz * 1.05 + this.avoid.y * 0.85;
        this.vel.x = damp(this.vel.x, targetVX, 5, dt);
        this.vel.y = damp(this.vel.y, targetVZ, 5, dt);

        // hand steering to the controller as a velocity + heading
        body.targetSpeed = Math.min(1.15, Math.hypot(this.vel.x, this.vel.y));
        body.setPose('walk');
        // face the velocity, not the waypoint, so avoidance reads as a
        // sidestep rather than as a pivot
        if (Math.hypot(this.vel.x, this.vel.y) > 0.1) {
            body.targetFacing = Math.atan2(this.vel.x, this.vel.y);
        }

        this._glanceAround(time);
    }

    /** While walking, drift the gaze toward whatever is interesting nearby. */
    _glanceAround(time) {
        const seed = this.rng ? 0 : 0;
        void seed;
        const t = time * 0.4 + this.id.length;
        const gx = this.body.pos.x + Math.sin(t * 0.7) * 2.0;
        const gz = this.body.pos.z + Math.cos(t * 0.5) * 2.0;
        this._gaze.set(gx, 1.3, gz);
        this.body.lookAt(this._gaze.x, this._gaze.y, this._gaze.z);
    }

    /* ---------------- perform ---------------- */

    _perform(dt, time) {
        const { activity, spot } = this;
        this.status = activity.id;
        const body = this.body;

        // slide onto the seat / into the spot
        if (spot.seat) {
            const rx = spot.root ? spot.root.x : spot.x;
            const rz = spot.root ? spot.root.z : spot.z;
            body.pos.x = damp(body.pos.x, rx, 6, dt);
            body.pos.z = damp(body.pos.z, rz, 6, dt);
            body.targetFacing = spot.rotY;
        } else {
            body.pos.x = damp(body.pos.x, spot.x, 8, dt);
            body.pos.z = damp(body.pos.z, spot.z, 8, dt);
            body.targetFacing = spot.rotY;
        }
        body.targetSpeed = 0;

        this.phase = clamp(this.stateT / Math.max(this.duration, 0.001), 0, 1);

        // loop: a list of (window, pose) so an activity is a small
        // performance, not a single frozen pose
        if (activity.loop) {
            for (const seg of activity.loop) {
                if (this.phase >= seg.at && this.phase < seg.to) {
                    body.setPose(seg.pose);
                    break;
                }
            }
        } else {
            body.setPose(activity.pose);
        }

        // hand choreography: reach for a real point in the world
        this._applyHandPhase();

        // where to look
        this._gazeFor(activity, spot, time);
        activity.onUpdate?.(this._ctx(), this.phase, time);

        if (this.stateT >= this.duration) this._finish();
    }

    _applyHandPhase() {
        const body = this.body;
        const { activity } = this;
        if (!this._handPhase.length) return;
        for (const step of this._handPhase) {
            const inWindow = this.phase >= step.at && this.phase < step.to;
            if (inWindow && step.side && step.at3) {
                // ease the world point on so the arm sweeps rather than snaps
                const local = this._tmp3.set(step.at3.x, step.at3.y, step.at3.z);
                body.setReach(step.side, local);
            } else if (!inWindow && step.side) {
                body.setReach(step.side, null);
            }
        }
        void activity;
    }

    _gazeFor(activity, spot, time) {
        const body = this.body;
        if (activity.gaze) {
            this._gaze.set(activity.gaze.x, activity.gaze.y, activity.gaze.z);
        } else if (this._handPhase.length) {
            // look at whatever the hand is touching
            const step = this._handPhase.find((s) => this.phase >= s.at && this.phase < s.to && s.at3);
            if (step) this._gaze.set(step.at3.x, step.at3.y, step.at3.z);
            else this._gaze.set(spot.x, 1.1, spot.z);
        } else if (spot.hand) {
            this._gaze.set(spot.hand.x, spot.hand.y, spot.hand.z);
        } else if (activity.pose === 'read' || activity.pose === 'phone') {
            // look down at your own hands
            const fwd = new THREE.Vector3(Math.sin(spot.rotY), 0, Math.cos(spot.rotY));
            this._gaze.set(
                body.pos.x + fwd.x * 0.35,
                0.95,
                body.pos.z + fwd.z * 0.35
            );
        } else {
            // otherwise look around the room slowly
            const t = time * 0.3 + this.rng.float(0, 6);
            this._gaze.set(
                body.pos.x + Math.sin(t) * 3,
                1.2 + Math.sin(t * 0.7) * 0.3,
                body.pos.z + Math.cos(t * 0.8) * 3
            );
        }
        body.lookAt(this._gaze.x, this._gaze.y, this._gaze.z);
    }

    _finish() {
        const { activity } = this;
        activity.onExit?.(this._ctx());
        this.props?.release(activity.prop, this.id);
        this.props?.unclaim(activity.resource, this.id);
        this.body.clearReach();
        this.body.detachAll();

        this.recent.unshift(activity.id);
        if (this.recent.length > 3) this.recent.pop();
        this.lastActivity = activity.id;
        this.cooldowns.set(activity.id, activity.cooldown || 60);

        // chain into a follow-up activity (e.g. brew -> drink)
        const next = activity.then;
        this.activity = null;
        this.spot = null;
        this.claimedSpot = null;
        this.claimedResource = null;
        this.state = 'decide';
        this.stateT = 0;

        if (next) {
            const chained = ACTIVITIES.find((a) => a.id === next);
            if (chained) {
                // bias the follow-up so it actually gets chosen next
                this.recent = this.recent.filter((id) => id !== next);
                this.cooldowns.delete(next);
                this._forceNext = chained.id;
            }
        }
    }

    /* ---------------- prop / activity context ---------------- */

    /** The object handed to activity hooks. */
    _ctx() {
        if (!this._ctxObj) {
            const self = this;
            this._ctxObj = {
                get body() { return self.body; },
                attach: (side, propId, opts) => self.props?.attach(side, propId, self.id, opts),
                attachBoth: (propId, opts) => {
                    self.props?.attach('L', propId, self.id, opts);
                    self.props?.attach('R', propId, self.id, opts);
                },
                detachAll: () => self.body.detachAll(),
                park: (propId) => self.props?.park(propId, self.id),
            };
        }
        return this._ctxObj;
    }

    /** Force a specific activity next (used by live-chat events). */
    interrupt(activityId) {
        const a = ACTIVITIES.find((x) => x.id === activityId);
        if (!a || !a.spots || !a.spots.length) return false;
        this._finish();
        const spot = a.spots[this.rng.int(0, a.spots.length - 1)];
        this._begin(a, spot);
        return true;
    }

    /** Drive an agent to the desk right now (chat arrived). */
    goWork() {
        return this.interrupt('work');
    }

    /** Called when a chat is resolved: back to the desk. */
    settle() {
        if (this.state === 'travel' && this.activity?.id === 'work') return;
        if (this.state === 'perform' && this.activity?.id === 'work') return;
        this.goWork();
    }
}

export { PERSONALITIES, TV_POINT };
