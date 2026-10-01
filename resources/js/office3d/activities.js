import { SEATS, P } from './plan.js';
import { clamp, lerp, smoothstep } from './lib.js';

/* ================================================================== *
 *  Activities
 *
 *  An activity is a destination plus a way of occupying it. Each one
 *  declares:
 *
 *    spots    where in the flat it happens, and which way to face. Every
 *             spot carries an `approach` point, which is the cell the
 *             character actually walks to — the spot itself is inside a
 *             prop, which is where the hands and the hips end up.
 *    pose     handed to CharacterController.setPose
 *    stand    true for a standing spot, false for a seat
 *    resource an exclusive token ('hob', 'sink', 'fridge'...) so three
 *             agents never decide to cook on one hob at once
 *    when     a multiplier on the clock, so lunch happens at lunch
 *    onEnter / onUpdate / onExit  prop and hand choreography
 *
 *  The `spots` tables are derived from the OBSTACLES footprints in
 *  plan.js rather than typed out by hand, so moving a counter moves the
 *  standing spot with it.
 * ================================================================== */

const T = Math.PI;

/* ------------------------------------------------------------------ *
 *  Footprint lookup
 * ------------------------------------------------------------------ */

const FOOTPRINTS = {
    counter: { x0: 2.4, z0: -5.92, x1: 8.6, z1: -5.28, h: 0.92 },
    island: { x0: 4.4, z0: -3.35, x1: 6.4, z1: -2.35, h: 0.92 },
    fridge: { x0: 2.3, z0: -2.85, x1: 3.0, z1: -2.0, h: 1.85 },
    cabinet: { x0: 8.25, z0: -4.9, x1: 8.92, z1: -3.7, h: 2.2 },
    console: { x0: 2.85, z0: 1.1, x1: 3.55, z1: 2.3, h: 0.5 },
    coffeeTable: { x0: 4.75, z0: 1.35, x1: 5.95, z1: 2.05, h: 0.45 },
    sofa: { x0: 6.95, z0: 0.1, x1: 8.1, z1: 3.5, h: 0.85 },
    shelf: { x0: -3.0, z0: -5.92, x1: -1.6, z1: -5.62, h: 1.95 },
    rack: { x0: -4.35, z0: -5.92, x1: -3.3, z1: -5.55, h: 1.9 },
    entryConsole: { x0: -7.3, z0: 5.4, x1: -5.9, z1: 5.92, h: 0.85 },
    shower: { x0: -8.92, z0: -0.46, x1: -7.5, z1: 0.9, h: 2.1 },
    wc: { x0: -8.92, z0: 1.35, x1: -8.35, z1: 2.05, h: 0.8 },
    vanity: { x0: -7.35, z0: -0.46, x1: -6.25, z1: 0.06, h: 0.9 },
};

const STAND = 0.44; // how far back a person stands from a worktop
const SEAT_REACH = 0.36; // a seated character's root sits this far behind the hips

/** Standing spot in front of a footprint face, facing into it. */
function facing(rect, side, { reach = STAND, handY = 0, handOut = 0 } = {}) {
    const cx = (rect.x0 + rect.x1) / 2;
    const cz = (rect.z0 + rect.z1) / 2;
    let x;
    let z;
    let rotY;
    let nx = 0;
    let nz = 0;
    switch (side) {
        case 'north': // prop is at low Z, you stand at high Z looking down -Z
            x = cx; z = rect.z1 + reach; rotY = T; nx = 0; nz = -1; break;
        case 'south':
            x = cx; z = rect.z0 - reach; rotY = 0; nx = 0; nz = 1; break;
        case 'west':
            x = rect.x0 - reach; z = cz; rotY = T / 2; nx = 1; nz = 0; break;
        case 'east':
            x = rect.x1 + reach; z = cz; rotY = -T / 2; nx = -1; nz = 0; break;
        default:
            x = cx; z = cz; rotY = 0; break;
    }
    const spot = {
        id: `${side}`,
        x,
        z,
        rotY,
        // back off further for the walk, so the character turns to face the
        // prop rather than arriving already sideways
        approach: { x: x + nx * 0.5, z: z + nz * 0.5 },
    };
    if (handY) {
        // a point on the prop itself: the hand goes here, not somewhere
        // guessed in joint space
        spot.hand = {
            x: x - nx * (reach - 0.14),
            y: handY,
            z: z - nz * (reach - 0.14),
        };
    }
    if (handOut) {
        spot.handSpot = { x: x + handOut * -nx, z: z + handOut * -nz };
    }
    return spot;
}

/** A seat, expressed the way the rig wants it: root 0.36 m behind the hips. */
function seat(key, seatDef) {
    const back = SEAT_REACH;
    const px = seatDef.x - Math.sin(seatDef.rotY) * back;
    const pz = seatDef.z - Math.cos(seatDef.rotY) * back;
    return {
        id: key,
        x: seatDef.x,
        z: seatDef.z,
        rotY: seatDef.rotY,
        root: { x: px, z: pz },
        approach: {
            x: seatDef.x - Math.sin(seatDef.rotY) * (back + 0.85),
            z: seatDef.z - Math.cos(seatDef.rotY) * (back + 0.85),
        },
        seat: true,
    };
}

/* ------------------------------------------------------------------ *
 *  Where things are
 * ------------------------------------------------------------------ */

export const SPOTS = {
    /* ---- kitchen ---- */
    sink: facing({ ...FOOTPRINTS.counter, z0: -5.92, z1: -5.28 }, 'north', { reach: 0.46 }),
    hob: facing(FOOTPRINTS.counter, 'north', { reach: 0.46 }),
    counterBrew: facing(FOOTPRINTS.counter, 'north', { reach: 0.46 }),
    islandSouth: facing(FOOTPRINTS.island, 'north', { reach: 0.44 }),
    islandNorth: facing(FOOTPRINTS.island, 'south', { reach: 0.44 }),
    fridge: {
        ...facing(FOOTPRINTS.fridge, 'east', { reach: 0.46 }),
        hand: { x: 3.02, y: 1.24, z: -2.4 }, // the handle
    },
    cabinet: facing(FOOTPRINTS.cabinet, 'west', { reach: 0.46 }),

    /* ---- lounge ---- */
    sofa: [seat('sofa0', SEATS.dewiSofa), seat('sofa1', SEATS.singgihSofa), seat('sofa2', SEATS.anderaSofa)],
    tv: facing(FOOTPRINTS.console, 'east', { reach: 0.55 }),
    coffeeTable: facing(FOOTPRINTS.coffeeTable, 'south', { reach: 0.5 }),

    /* ---- office ---- */
    desk: [seat('dewiDesk', SEATS.dewiDesk), seat('singgihDesk', SEATS.singgihDesk), seat('anderaDesk', SEATS.anderaDesk)],
    shelf: facing(FOOTPRINTS.shelf, 'north', { reach: 0.5 }),
    rack: facing(FOOTPRINTS.rack, 'north', { reach: 0.5 }),
    entryConsole: facing(FOOTPRINTS.entryConsole, 'south', { reach: 0.5 }),

    /* ---- bathroom + bedroom ---- */
    shower: {
        ...facing(FOOTPRINTS.shower, 'south', { reach: 0.4 }),
        hidden: true,
    },
    wc: { ...facing(FOOTPRINTS.wc, 'east', { reach: 0.5 }), hidden: true },
    vanity: facing(FOOTPRINTS.vanity, 'north', { reach: 0.44 }),
    bed: seat('bed', { x: -7.75, z: -4.55, rotY: T / 2 }),

    /* ---- anywhere ---- */
    lounge: [
        { id: 'l0', x: 4.3, z: 3.6, rotY: -0.5, approach: { x: 4.0, z: 2.6 } },
        { id: 'l1', x: 6.4, z: -0.6, rotY: 0.3, approach: { x: 5.6, z: 0.2 } },
        { id: 'l2', x: 3.6, z: 4.2, rotY: 0.8, approach: { x: 3.0, z: 3.4 } },
    ],
    office: [
        { id: 'o0', x: 0.4, z: 2.2, rotY: 0.2, approach: { x: 0.0, z: 1.4 } },
        { id: 'o1', x: -2.2, z: 3.4, rotY: -0.4, approach: { x: -2.4, z: 2.4 } },
    ],
    entry: [
        { id: 'e0', x: -6.6, z: 4.2, rotY: 0.1, approach: { x: -6.6, z: 3.4 } },
    ],
    plants: [
        { id: 'p0', x: 1.5, z: -5.0, rotY: 0.1, approach: { x: 1.3, z: -4.2 } },
        { id: 'p1', x: 1.1, z: 4.9, rotY: 0.1, approach: { x: 0.9, z: 4.1 } },
        { id: 'p2', x: -4.0, z: 4.5, rotY: 0.6, approach: { x: -3.6, z: 3.8 } },
        { id: 'p3', x: -8.3, z: 4.3, rotY: -0.4, approach: { x: -7.7, z: 4.1 } },
    ],
};

/* The TV, as a point characters can look at. */
export const TV_POINT = { x: 3.2, y: 1.15, z: 1.7 };

/* ------------------------------------------------------------------ *
 *  The library
 * ------------------------------------------------------------------ */

const HOUR = (h) => h;

/**
 * `when` returns a multiplier for the current hour. Writing it as a sum
 * of smoothsteps keeps the transition soft — an agent drifts into a
 * different routine over a few minutes rather than flipping at 12:00:00.
 */
function gauss(h, center, width) {
    const d = (h - center) / width;
    return Math.exp(-d * d);
}

export const ACTIVITIES = [
    /* ================= work ================= */
    {
        id: 'work',
        label: 'kerja',
        thought: 'menjawab chat',
        pose: 'type',
        spots: SPOTS.desk,
        weight: 1,
        cooldown: 20,
        // the office is the default destination, so it has to lose to
        // anything interesting when the queue is empty
        when: () => 1,
        minDuration: [90, 260],
    },
    {
        id: 'paperwork',
        label: 'berkas',
        thought: 'baca berkas',
        pose: 'read',
        spots: SPOTS.entryConsole,
        weight: 0.4,
        cooldown: 150,
        when: (h) => 0.4 + gauss(h, 10, 2.4) * 0.6 + gauss(h, 16, 2.2) * 0.5,
        minDuration: [50, 110],
    },
    {
        id: 'server',
        label: 'server',
        thought: 'cek server',
        pose: 'lean',
        spots: SPOTS.rack,
        weight: 0.3,
        cooldown: 260,
        when: () => 1,
        minDuration: [40, 90],
    },
    {
        id: 'shelf',
        label: 'rak buku',
        thought: 'cari referensi',
        pose: 'read',
        spots: SPOTS.shelf,
        weight: 0.28,
        cooldown: 200,
        when: (h) => 0.3 + gauss(h, 14, 3) * 0.7,
        minDuration: [40, 80],
    },

    /* ================= kitchen ================= */
    {
        id: 'cook',
        label: 'masak',
        thought: 'masak',
        pose: 'stir',
        spots: [SPOTS.hob],
        resource: 'hob',
        weight: 0.75,
        cooldown: 200,
        when: (h) =>
            0.12 + gauss(h, 7.5, 1.1) * 1.0 + gauss(h, 12.5, 1.2) * 0.95 + gauss(h, 18.5, 1.4) * 1.0,
        minDuration: [80, 170],
        prop: 'pot',
        onEnter(ctx) {
            ctx.attachBoth('mug', { offset: [0, -0.07, 0.03] });
        },
        onExit(ctx) {
            ctx.detachAll();
            ctx.park('mug');
        },
    },
    {
        id: 'brew',
        label: 'kopi',
        thought: 'buat kopi',
        pose: 'pour',
        spots: [SPOTS.counterBrew],
        resource: 'kettle',
        weight: 0.62,
        cooldown: 120,
        when: (h) =>
            0.3 + gauss(h, 7, 1.6) * 0.9 + gauss(h, 13, 1.8) * 0.7 + gauss(h, 21, 2) * 0.5,
        minDuration: [35, 70],
        onEnter(ctx) {
            ctx.attach('R', 'kettle', { offset: [0, -0.05, 0.06] });
        },
        onExit(ctx) {
            ctx.detachAll();
            ctx.park('kettle');
        },
        // and then actually carry the mug somewhere to drink it
        then: 'drink',
    },
    {
        id: 'washUp',
        label: 'beres',
        thought: 'Cuci piring',
        pose: 'wash',
        spots: [SPOTS.sink],
        resource: 'sink',
        weight: 0.5,
        cooldown: 180,
        when: (h) => 0.35 + gauss(h, 9, 1.6) * 0.8 + gauss(h, 13.5, 1.6) * 0.9 + gauss(h, 20, 1.8) * 0.9,
        minDuration: [45, 95],
    },
    {
        id: 'snack',
        label: 'ngemil',
        thought: 'buka kulkas',
        pose: 'lean',
        spots: [SPOTS.fridge],
        resource: 'fridge',
        weight: 0.45,
        cooldown: 90,
        when: (h) => 0.2 + gauss(h, 10, 2) * 0.7 + gauss(h, 15.5, 2.4) * 0.9 + gauss(h, 22, 2) * 0.6,
        minDuration: [20, 45],
        // a real hand on the handle, then a lean
        handPhase: [
            { at: 0.0, to: 0.35, side: 'R', at3: SPOTS.fridge.hand },
            { at: 0.35, to: 1.0, side: null, at3: null },
        ],
        then: 'eat',
    },
    {
        id: 'eat',
        label: 'makan',
        thought: 'makan',
        pose: 'watch',
        spots: [SPOTS.islandSouth, SPOTS.islandNorth],
        resource: 'table',
        weight: 0.5,
        cooldown: 130,
        when: (h) => gauss(h, 8, 1.2) * 1.0 + gauss(h, 13, 1.3) * 1.0 + gauss(h, 19, 1.5) * 0.95,
        minDuration: [45, 80],
        prop: 'bowl',
        seated: false,
    },
    {
        id: 'drink',
        label: 'minum',
        thought: 'minum kopi',
        pose: 'sip',
        spots: [SPOTS.lounge, SPOTS.office],
        weight: 0.6,
        cooldown: 70,
        when: () => 1,
        minDuration: [25, 50],
        prop: 'mug',
        onExit(ctx) {
            ctx.detachAll();
            ctx.park('mug');
        },
        // sip, look around, sip again
        loop: [
            { at: 0.0, to: 0.28, pose: 'hold' },
            { at: 0.28, to: 0.5, pose: 'sip' },
            { at: 0.5, to: 0.78, pose: 'hold' },
            { at: 0.78, to: 1.0, pose: 'sip' },
        ],
    },
    {
        id: 'tidy',
        label: 'beres-beres',
        thought: 'rapikan',
        pose: 'lean',
        spots: [SPOTS.islandSouth, SPOTS.counterBrew],
        weight: 0.3,
        cooldown: 220,
        when: (h) => 0.3 + gauss(h, 11, 2.4) * 0.7,
        minDuration: [50, 100],
    },

    /* ================= leisure ================= */
    {
        id: 'game',
        label: 'main game',
        thought: 'main game',
        pose: 'game',
        spots: SPOTS.sofa,
        weight: 0.85,
        cooldown: 150,
        when: (h) => 0.1 + gauss(h, 20.5, 2.2) * 1.0 + gauss(h, 15, 2.2) * 0.5 + gauss(h, 11, 1.6) * 0.3,
        minDuration: [70, 200],
        prop: 'controller',
        onEnter(ctx) {
            ctx.attachBoth('controller', { offset: [0, -0.06, 0.05] });
        },
        onExit(ctx) {
            ctx.detachAll();
            ctx.park('controller');
        },
        loop: [
            { at: 0.0, to: 0.85, pose: 'game' },
            { at: 0.85, to: 0.93, pose: 'think' },   // a pause between rounds
            { at: 0.93, to: 1.0, pose: 'game' },
        ],
    },
    {
        id: 'watchTv',
        label: 'nonton',
        thought: 'nonton tv',
        pose: 'watch',
        spots: SPOTS.sofa,
        gaze: TV_POINT,
        weight: 0.6,
        cooldown: 120,
        when: (h) => 0.15 + gauss(h, 19.5, 2.6) * 0.9 + gauss(h, 13.5, 1.8) * 0.5,
        minDuration: [60, 150],
    },
    {
        id: 'nap',
        label: 'tidur',
        thought: 'tidur',
        pose: 'sleep',
        spots: [SPOTS.bed],
        weight: 0.9,
        cooldown: 300,
        exclusive: true,
        when: (h) =>
            gauss(h, 2.5, 1.6) * 1.0 + gauss(h, 13.5, 1.0) * 0.6 + gauss(h, 23, 1.8) * 0.95,
        minDuration: [180, 420],
    },
    {
        id: 'sofaRest',
        label: 'santai',
        thought: 'santai',
        pose: 'sit',
        spots: SPOTS.sofa,
        weight: 0.45,
        cooldown: 60,
        when: () => 1,
        minDuration: [40, 110],
    },
    {
        id: 'phone',
        label: 'scroll HP',
        thought: 'scrolling',
        pose: 'phone',
        spots: [...SPOTS.lounge, ...SPOTS.office, ...SPOTS.entry],
        weight: 0.5,
        cooldown: 45,
        when: (h) => 0.35 + gauss(h, 8, 1.6) * 0.6 + gauss(h, 12.5, 1.4) * 0.5 + gauss(h, 22, 2) * 0.8,
        minDuration: [25, 90],
    },
    {
        id: 'idleStand',
        label: 'diam',
        thought: 'melamun',
        pose: 'idle',
        spots: [...SPOTS.lounge, ...SPOTS.office, ...SPOTS.entry, ...SPOTS.plants],
        weight: 0.3,
        cooldown: 20,
        when: () => 1,
        minDuration: [12, 40],
    },
    {
        id: 'stretch',
        label: 'regang',
        thought: 'regang badan',
        pose: 'stretch',
        spots: [...SPOTS.office, ...SPOTS.lounge],
        weight: 0.4,
        cooldown: 75,
        when: (h) => 0.3 + gauss(h, 10, 2) * 0.5 + gauss(h, 15, 2) * 0.6 + gauss(h, 21, 2) * 0.6,
        minDuration: [12, 22],
    },
    {
        id: 'waterPlant',
        label: 'rawat tanaman',
        thought: 'rawat tanaman',
        pose: 'lean',
        spots: SPOTS.plants,
        weight: 0.25,
        cooldown: 280,
        when: (h) => 0.2 + gauss(h, 9, 2.2) * 0.6 + gauss(h, 17, 2.4) * 0.6,
        minDuration: [40, 80],
    },

    /* ================= washroom ================= */
    {
        id: 'shower',
        label: 'mandi',
        thought: 'mandi',
        pose: 'idle',
        spots: [SPOTS.shower],
        resource: 'shower',
        weight: 0.9,
        cooldown: 400,
        exclusive: true,
        hidden: true,
        when: (h) => gauss(h, 6.5, 1.4) * 1.0 + gauss(h, 21.5, 1.6) * 0.9,
        minDuration: [150, 300],
    },
    {
        id: 'washroom',
        label: 'ke toilet',
        thought: 'ke toilet',
        pose: 'idle',
        spots: [SPOTS.wc],
        resource: 'wc',
        weight: 0.8,
        cooldown: 90,
        exclusive: true,
        hidden: true,
        when: () => 0.3,
        minDuration: [40, 90],
    },
    {
        id: 'washFace',
        label: 'basuh muka',
        thought: 'basuh muka',
        pose: 'wash',
        spots: [SPOTS.vanity],
        resource: 'vanity',
        weight: 0.5,
        cooldown: 110,
        when: (h) => 0.3 + gauss(h, 6.5, 1.4) * 0.8 + gauss(h, 22, 1.8) * 0.7,
        minDuration: [30, 60],
    },
];

export const ACTIVITY_BY_ID = Object.fromEntries(ACTIVITIES.map((a) => [a.id, a]));

/* ------------------------------------------------------------------ *
 *  Helpers used by the brain
 * ------------------------------------------------------------------ */

/** Every spot in the library, flattened, for nav registration. */
export function allSpots() {
    const out = [];
    for (const a of ACTIVITIES) {
        for (const s of a.spots || []) {
            out.push({ activity: a.id, ...s, approach: s.approach || s });
        }
    }
    return out;
}

/**
 * How many of a character who is about to pick an activity. Distance is a
 * real cost: crossing the flat to boil a kettle is a bigger decision than
 * crossing to the sofa, and without this term every agent converges on
 * whatever scores highest in absolute terms.
 */
export function travelCost(fromX, fromZ, spot) {
    const d = Math.hypot(spot.approach.x - fromX, spot.approach.z - fromZ);
    // 12 m across the flat is already a long errand
    return 1 / (1 + d / 12);
}

/**
 * Utility for one activity at one spot. Kept separate from the brain so
 * the scoring can be inspected and tuned on its own.
 */
export function scoreActivity(activity, spot, ctx) {
    let s = (activity.weight || 0.5) * (activity.when ? activity.when(ctx.hour) : 1);
    if (activity.hidden) s *= 0.6;
    s *= travelCost(ctx.x, ctx.z, spot);

    // an agent with a full inbox does not wander off
    if (activity.id !== 'work') s *= lerp(1, 0.25, clamp(ctx.busy, 0, 1));
    else s *= lerp(0.25, 1.6, clamp(ctx.busy, 0, 1));

    // never repeat the last thing, and stay off whatever just happened
    if (activity.id === ctx.lastActivity) s *= 0.12;
    for (const id of ctx.recent || []) s *= id === activity.id ? 0.45 : 1;
    if (ctx.cooldownOf(activity.id) > 0) s = 0;

    // personality
    s *= ctx.taste(activity.id);

    // shared resources
    if (activity.resource && ctx.isTaken(activity.resource, spot)) s = 0;

    return s;
}

/* Smooth 0->1 over a window, used for hour shaping. */
export { smoothstep, HOUR };
