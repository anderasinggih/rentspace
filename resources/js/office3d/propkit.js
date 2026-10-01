import * as THREE from 'three';
import { buildHumanoid } from './character.js';
import { buildDeskBench, buildTaskChair, buildSofa, buildCoffeeTable, buildMediaConsole, buildTV, buildKitchenRun, buildFridge, buildIsland, buildBed, buildMug, buildBookshelf } from './props.js';
import { P, SEATS } from './plan.js';
import { clamp } from './lib.js';

/* ================================================================== *
 *  Prop kit
 *
 *  Two jobs:
 *    1. hold the movable objects (mug, controller, phone, pot) and hand
 *       them between characters and the world
 *    2. arbitrate shared resources, so two agents never decide to cook on
 *       the same hob or sit on the same sofa cushion
 *
 *  Movable props are created once and *parked* on a surface when not in
 *  use. Re-parenting the same mesh means a mug keeps its identity, its
 *  coffee level and its wear across the whole session, which is far
 *  cheaper than spawning and disposing geometry.
 * ================================================================== */

export class PropKit {
    constructor(scene, M) {
        this.scene = scene;
        this.M = M;
        this.props = new Map();      // id -> { mesh, home:{x,y,z}, rotY }
        this.claims = new Map();     // resource -> [{ spotId, agentId }]
        this.holders = new Map();     // propId -> agentId
        this._pool = new THREE.Group();
        this._pool.name = 'prop-pool';
        scene.add(this._pool);
    }

    /**
     * Register a movable prop and its resting place.
     * `home` is a world position; when nothing is holding the prop it
     * lives there.
     */
    register(id, mesh, home = { x: 0, y: 0, z: 0, rotY: 0 }) {
        this._pool.add(mesh);
        mesh.position.set(home.x, home.y, home.z);
        mesh.rotation.set(0, home.rotY || 0, 0);
        mesh.userData.propId = id;
        this.props.set(id, { mesh, home });
        return mesh;
    }

    get(id) {
        return this.props.get(id)?.mesh || null;
    }

    isTaken(resource, spot, selfId) {
        const list = this.claims.get(resource);
        if (!list) return false;
        for (const c of list) {
            if (c.agentId === selfId) continue;
            if (!spot || !spot.id || c.spotId === spot.id) return true;
        }
        return false;
    }

    isOccupiedByOthers(resource, selfId) {
        if (!resource) return false;
        const list = this.claims.get(resource);
        if (!list) return false;
        return list.some((c) => c.agentId !== selfId);
    }

    claim(resource, spot, agentId) {
        if (!resource) return;
        const list = this.claims.get(resource) || [];
        list.push({ spotId: spot?.id ?? '*', agentId });
        this.claims.set(resource, list);
    }

    unclaim(resource, agentId) {
        if (!resource) return;
        const list = this.claims.get(resource);
        if (!list) return;
        const next = list.filter((c) => c.agentId !== agentId);
        if (next.length) this.claims.set(resource, next);
        else this.claims.delete(resource);
    }

    /** Take exclusive ownership of a prop so two agents never share it. */
    acquire(propId, agentId, body) {
        if (!propId) return;
        this.release(propId, agentId);
        const current = this.holders.get(propId);
        if (current && current !== agentId) {
            // someone else has it — yank it back to its home and let the
            // previous holder notice via its missing hand
            this.park(propId);
        }
        this.holders.set(propId, agentId);
        void body;
    }

    release(propId, agentId) {
        if (!propId) return;
        if (this.holders.get(propId) === agentId) this.holders.delete(propId);
    }

    /** Park a prop back on its home surface. */
    park(propId) {
        const entry = this.props.get(propId);
        if (!entry) return;
        const { mesh, home } = entry;
        if (mesh.parent !== this._pool) this._pool.add(mesh);
        mesh.position.set(home.x, home.y, home.z);
        mesh.rotation.set(0, home.rotY || 0, 0);
        this.holders.delete(propId);
    }

    /** Attach a prop into one hand of an agent. */
    attach(side, propId, agentId, opts) {
        const entry = this.props.get(propId);
        const body = this.bodies?.get(agentId);
        if (!entry || !body) return null;
        this.acquire(propId, agentId, body);
        return body.attachToHand(side, entry.mesh, opts);
    }
}

/* ------------------------------------------------------------------ *
 *  World dressing
 *
 *  Lays out the permanent furniture.  The positions come from the same
 *  plan the navigation and activities read, so a chair drawn here is the
 *  chair an agent's path avoids.
 * ------------------------------------------------------------------ */

export function dressWorld(scene, M, kit, { agents = [] } = {}) {
    const add = (obj, x, y, z, ry = 0) => {
        obj.position.set(x, y, z);
        obj.rotation.y = ry;
        scene.add(obj);
        return obj;
    };

    /* ---- office ---- */
    add(buildDeskBench(M), -2.2, 0, 0);
    for (const key of Object.keys(SEATS)) {
        const s = SEATS[key];
        const fwd = new THREE.Vector3(Math.sin(s.rotY), 0, Math.cos(s.rotY));
        add(buildTaskChair(M), s.x + fwd.x * 0.36, 0, s.z + fwd.z * 0.36, s.rotY);
    }
    add(buildBookshelf(M), -2.3, 0, -5.77);

    /* ---- lounge ---- */
    add(buildSofa(M, { seats: 3, w: 3.0, d: 0.95 }), 7.5, 0, 1.8, -Math.PI / 2);
    add(buildCoffeeTable(M), 5.35, 0, 1.7);
    add(buildMediaConsole(M), 3.2, 0, 1.7, Math.PI / 2);
    add(buildTV(M), 3.32, 1.02, 1.7, Math.PI / 2);

    /* ---- kitchen ---- */
    add(buildKitchenRun(M, { len: 6.2, depth: 0.62, h: 0.9 }), 5.5, 0, -5.6);
    add(buildIsland(M, { w: 2.0, d: 1.0, h: 0.92 }), 5.4, 0, -2.85);
    add(buildFridge(M), 2.65, 0, -2.42, Math.PI / 2);

    /* ---- bedroom ---- */
    add(buildBed(M), -7.75, 0, -4.7, Math.PI / 2);

    /* ---- movable props, parked on real surfaces ---- */
    // mug on the kitchen counter, beside the kettle
    kit.register('mug', buildMug(M, { color: 0xf3f1ec }), { x: 4.55, y: 0.9, z: -5.5, rotY: 0.3 });
    // controller on the coffee table
    kit.register('controller', buildController(M), { x: 5.35, y: 0.45, z: 1.6, rotY: -0.4 });
    // phone on the entry console
    kit.register('phone', buildPhone(M), { x: -6.6, y: 0.85, z: 5.5, rotY: 0.2 });

    return scene;
}

/* ------------------------------------------------------------------ *
 *  Small props that don't warrant a full builder
 * ------------------------------------------------------------------ */

function buildController(M) {
    const g = new THREE.Group();
    const body = new THREE.Mesh(
        new THREE.BoxGeometry(0.15, 0.035, 0.1),
        new THREE.MeshStandardMaterial({ color: 0x1c1f24, roughness: 0.6 })
    );
    body.castShadow = true;
    g.add(body);
    // two grips
    for (const sx of [-1, 1]) {
        const grip = new THREE.Mesh(
            new THREE.CapsuleGeometry(0.024, 0.07, 4, 10),
            new THREE.MeshStandardMaterial({ color: 0x14161a, roughness: 0.7 })
        );
        grip.rotation.z = Math.PI / 2;
        grip.rotation.y = sx * 0.28;
        grip.position.set(sx * 0.085, 0.002, 0.03);
        grip.castShadow = true;
        g.add(grip);
    }
    // buttons
    for (const [bx, bz] of [[-0.05, -0.02], [-0.03, -0.02], [-0.04, 0.0], [-0.06, 0.0]]) {
        const b = new THREE.Mesh(
            new THREE.CylinderGeometry(0.006, 0.006, 0.004, 8),
            new THREE.MeshStandardMaterial({ color: 0x4a515a, roughness: 0.5 })
        );
        b.position.set(bx, 0.019, bz);
        g.add(b);
    }
    void M;
    return g;
}

function buildPhone(M) {
    const g = new THREE.Group();
    const body = new THREE.Mesh(
        new THREE.BoxGeometry(0.072, 0.008, 0.148),
        new THREE.MeshStandardMaterial({ color: 0x0d0f12, roughness: 0.35, metalness: 0.4 })
    );
    body.castShadow = true;
    g.add(body);
    const screen = new THREE.Mesh(
        new THREE.PlaneGeometry(0.064, 0.136),
        new THREE.MeshStandardMaterial({ color: 0x5b7fa8, emissive: 0x1a2836, roughness: 0.2 })
    );
    screen.rotation.x = -Math.PI / 2;
    screen.position.y = 0.0045;
    g.add(screen);
    void M;
    return g;
}

/* ------------------------------------------------------------------ *
 *  Agents
 * ------------------------------------------------------------------ */

export const AGENT_LOOKS = {
    dewi: {
        skinColor: 0xf0c9a4, shirtColor: 0x3f6fb5, pantsColor: 0x2c3446,
        shoeColor: 0xf2f2ef, hairColor: 0x1d1310, longHair: true, bangs: true,
    },
    singgih: {
        skinColor: 0xd9a877, shirtColor: 0x2f8f6f, pantsColor: 0x37414f,
        shoeColor: 0x33383f, hairColor: 0x141414, longHair: false, bangs: false,
    },
    andera: {
        skinColor: 0xe8bd97, shirtColor: 0xc2553f, pantsColor: 0x4a4238,
        shoeColor: 0xe8e2d8, hairColor: 0x2b1a12, longHair: true, bangs: true,
    },
};

export function buildAgent(kit, id, look, seed) {
    const root = buildHumanoid(kit.M, { ...look, seed });
    kit.scene.add(root);
    return root;
}
