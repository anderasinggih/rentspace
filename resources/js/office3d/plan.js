/* ------------------------------------------------------------------ *
 *  Floor plan — every dimension in real metres.
 *
 *  Everything else in the engine derives from this file, so proportions
 *  stay consistent (a 2.05 m door is a 2.05 m door everywhere, a chair
 *  seat is 0.45 m, a person is 1.74 m).  Previously every prop was
 *  eyeballed, which is why walls read as 5 m tall next to a 1.1 m person.
 * ------------------------------------------------------------------ */

/* --- global architecture constants --- */
export const P = {
    /* ceiling height: 3.0 m is a normal residential/office slab. */
    ceilH: 3.0,
    corniceH: 0.11,
    baseboardH: 0.11,
    baseboardT: 0.018,

    wallExt: 0.16, // exterior wall thickness
    wallInt: 0.11, // interior partition thickness

    doorH: 2.05, // standard clear opening height
    doorHead: 0.04, // head reveal
    frameW: 0.055, // jamb face width
    frameT: 0.032, // jamb depth

    winSill: 0.9,
    winH: 1.5,

    /* outer shell — wall CENTRELINES, so the clear interior is
       8.92 x 5.92 half-extents. */
    outer: { minX: -9.0, maxX: 9.0, minZ: -6.0, maxZ: 6.0 },

    /* room extents (clear interior faces) */
    bedroom: { minX: -8.92, maxX: -5.86, minZ: -5.92, maxZ: -0.46 },
    bathroom: { minX: -8.92, maxX: -5.86, minZ: -0.46, maxZ: 2.34 },
    hall: { minX: -5.8, maxX: -4.4, minZ: -5.92, maxZ: 2.34 },
    entry: { minX: -8.92, maxX: -4.4, minZ: 2.34, maxZ: 5.92 },
    office: { minX: -4.4, maxX: 2.0, minZ: -5.92, maxZ: 5.92 },
    pantry: { minX: 2.0, maxX: 8.92, minZ: -5.92, maxZ: -1.6 },
    lounge: { minX: 2.0, maxX: 8.92, minZ: -1.6, maxZ: 5.92 },

    /* partition centrelines */
    partBedroom: -5.8, // wall between hall/entry and bedroom+bathroom
    partOffice: -4.4, // wall between hall and office (north segment only)
    partBathN: -0.46, // bedroom / bathroom
    partBathS: 2.34, // bathroom / entry
    partPantry: 2.0, // office | pantry-lounge
    partLounge: -1.6, // pantry | lounge

    /* openings (centre, width) on their partition */
    openBedroomDoor: { z: -2.6, w: 0.85 },
    openBathDoor: { z: 1.1, w: 0.75 },
    openHallToOffice: { z: -3.2, w: 1.1 },
    openOfficeToPantry: { z: -4.4, w: 1.05 },
    openPantryToLounge: { x: 5.4, w: 0.95 },
    openFrontDoor: { x: -6.6, w: 1.0 },
};

/* --- human scale --- */
export const BODY = {
    height: 1.74,
    hipH: 0.94, // hip joint height standing
    kneeH: 0.48,
    ankleH: 0.075,
    shoulderH: 1.45,
    headH: 0.23, // head height
    neckH: 1.55,
    hipW: 0.17,
    shoulderW: 0.42,
    upperArmLen: 0.30,
    foreArmLen: 0.27,
    thighLen: 0.45,
    shankLen: 0.44,
    footLen: 0.26,
    seatH: 0.45, // chair seat surface height
};

/* ------------------------------------------------------------------ *
 *  Prop anchors.  Used by both the builder and the navigation mesh, so
 *  characters can never walk through a table even if a prop moves.
 * ------------------------------------------------------------------ */

/** axis-aligned footprint obstacles {x0,z0,x1,z1} inflated by radius. */
export const OBSTACLES = [
    /* office desk run (4.6 x 0.78) + monitor overhang */
    { x0: -4.55, z0: -0.45, x1: 0.05, z1: 0.45, h: 0.75 },
    /* desk chairs — keep agents out of the chair volume */
    { x0: -3.05, z0: 0.5, x1: -2.15, z1: 1.5, h: 0.5 },
    { x0: -1.65, z0: 0.5, x1: -0.75, z1: 1.5, h: 0.5 },
    { x0: -1.15, z0: -1.5, x1: -0.25, z1: -0.5, h: 0.5 },

    /* lounge sofa (backs onto +X wall) */
    { x0: 6.95, z0: 0.1, x1: 8.1, z1: 3.5, h: 0.85 },
    /* media console + TV wall (low X side of lounge) */
    { x0: 2.85, z0: 1.1, x1: 3.55, z1: 2.3, h: 0.5 },
    /* coffee table */
    { x0: 4.75, z0: 1.35, x1: 5.95, z1: 2.05, h: 0.45 },
    /* lounge side table + floor lamp */
    { x0: 6.5, z0: 3.7, x1: 7.1, z1: 4.3, h: 0.6 },
    { x0: 7.4, z0: 4.5, x1: 8.1, z1: 5.2, h: 1.7 },

    /* pantry counter run (along Z = -5.92) */
    { x0: 2.4, z0: -5.92, x1: 8.6, z1: -5.28, h: 0.92 },
    /* island */
    { x0: 4.4, z0: -3.35, x1: 6.4, z1: -2.35, h: 0.92 },
    /* fridge */
    { x0: 2.3, z0: -2.85, x1: 3.0, z1: -2.0, h: 1.85 },
    /* tall pantry cabinet */
    { x0: 8.25, z0: -4.9, x1: 8.92, z1: -3.7, h: 2.2 },

    /* bedroom bed + wardrobe + vanity */
    { x0: -8.525, z0: -5.7, x1: -6.975, z1: -3.7, h: 0.55 },
    { x0: -6.56, z0: -5.5, x1: -5.86, z1: -3.7, h: 2.35 },
    { x0: -6.75, z0: -2.4, x1: -6.2, z1: -0.9, h: 0.78 },
    { x0: -8.92, z0: -5.7, x1: -8.5, z1: -4.95, h: 0.5 },

    /* bathroom fixtures */
    { x0: -8.92, z0: -0.46, x1: -7.5, z1: 0.9, h: 2.1 }, // shower
    { x0: -8.92, z0: 1.35, x1: -8.35, z1: 2.05, h: 0.8 }, // wc
    { x0: -7.35, z0: -0.46, x1: -6.25, z1: 0.06, h: 0.9 }, // vanity

    /* entry vestibule */
    { x0: -7.3, z0: 5.4, x1: -5.9, z1: 5.92, h: 0.85 }, // console
    { x0: -8.6, z0: 3.9, x1: -8.1, z1: 4.7, h: 1.5 }, // plant

    /* office wall furniture */
    { x0: -4.35, z0: -5.92, x1: -3.3, z1: -5.55, h: 1.9 }, // server rack
    { x0: -3.0, z0: -5.92, x1: -1.6, z1: -5.62, h: 1.95 }, // bookshelf
    { x0: 1.15, z0: -5.92, x1: 1.7, z1: -5.45, h: 1.5 }, // plant
    { x0: 0.75, z0: 4.6, x1: 1.35, z1: 5.3, h: 1.6 }, // plant
    { x0: -4.35, z0: 4.2, x1: -3.7, z1: 4.9, h: 1.4 }, // plant
];

/* ------------------------------------------------------------------ *
 *  Wall segments.  Each is a real extruded rectangle; openings are
 *  punched through it so the reveal has thickness.
 *    axis: 'x' → runs along X (normal is ±Z)
 *    axis: 'z' → runs along Z (normal is ±X)
 * ------------------------------------------------------------------ */

export const WALLS = [
    /* ---- exterior shell ---- */
    {
        axis: 'x',
        at: P.outer.minZ,
        from: P.outer.minX - P.wallExt,
        to: P.outer.maxX + P.wallExt,
        t: P.wallExt,
        h: P.ceilH,
        openings: [
            // office window (2.4 x 1.5)
            { cx: -2.3, w: 2.4, sill: 0.9, h: 1.5, kind: 'window' },
        ],
    },
    {
        axis: 'x',
        at: P.outer.maxZ,
        from: P.outer.minX - P.wallExt,
        to: P.outer.maxX + P.wallExt,
        t: P.wallExt,
        h: P.ceilH,
        openings: [{ cx: P.openFrontDoor.x, w: P.openFrontDoor.w, sill: 0, h: P.doorH + P.doorHead, kind: 'door' }],
    },
    {
        axis: 'z',
        at: P.outer.minX,
        from: P.outer.minZ - P.wallExt,
        to: P.outer.maxZ + P.wallExt,
        t: P.wallExt,
        h: P.ceilH,
        openings: [
            { cx: -3.6, w: 1.1, sill: P.winSill, h: 1.35, kind: 'window' }, // bedroom
            { cx: 1.05, w: 0.5, sill: 1.85, h: 0.42, kind: 'window', frosted: true }, // bathroom
        ],
    },
    {
        axis: 'z',
        at: P.outer.maxX,
        from: P.outer.minZ - P.wallExt,
        to: P.outer.maxZ + P.wallExt,
        t: P.wallExt,
        h: P.ceilH,
        openings: [
            { cx: -3.9, w: 1.7, sill: 1.15, h: 0.95, kind: 'window' }, // pantry
            { cx: 1.8, w: 2.6, sill: 0.85, h: 1.7, kind: 'window' }, // lounge
        ],
    },

    /* ---- bedroom / bathroom enclosure ---- */
    {
        axis: 'z',
        at: P.partBedroom,
        from: P.outer.minZ,
        to: P.partBathS,
        t: P.wallInt,
        h: P.ceilH,
        openings: [
            { cx: P.openBedroomDoor.z, w: P.openBedroomDoor.w, sill: 0, h: P.doorH + P.doorHead, kind: 'door', id: 'bedroom' },
            { cx: P.openBathDoor.z, w: P.openBathDoor.w, sill: 0, h: P.doorH + P.doorHead, kind: 'door', id: 'bathroom' },
        ],
    },
    { axis: 'x', at: P.partBathN, from: P.outer.minX, to: P.partBedroom, t: P.wallInt, h: P.ceilH, openings: [] },
    { axis: 'x', at: P.partBathS, from: P.outer.minX, to: P.partBedroom, t: P.wallInt, h: P.ceilH, openings: [] },

    /* ---- hall | office ---- */
    {
        axis: 'z',
        at: P.partOffice,
        from: P.outer.minZ,
        to: 0.0,
        t: P.wallInt,
        h: P.ceilH,
        openings: [{ cx: P.openHallToOffice.z, w: P.openHallToOffice.w, sill: 0, h: P.doorH + 0.02, kind: 'cased' }],
    },

    /* ---- office | pantry (cased opening, no leaf) ---- */
    {
        axis: 'z',
        at: P.partPantry,
        from: P.outer.minZ,
        to: P.partLounge,
        t: P.wallInt,
        h: P.ceilH,
        openings: [{ cx: P.openOfficeToPantry.z, w: P.openOfficeToPantry.w, sill: 0, h: P.doorH + 0.02, kind: 'cased' }],
    },
    /* ---- pantry | lounge (glazed door) ---- */
    {
        axis: 'x',
        at: P.partLounge,
        from: P.partPantry,
        to: P.outer.maxX,
        t: P.wallInt,
        h: P.ceilH,
        openings: [{ cx: P.openPantryToLounge.x, w: P.openPantryToLounge.w, sill: 0, h: P.doorH + P.doorHead, kind: 'glassdoor', id: 'pantry' }],
    },
];

/* ------------------------------------------------------------------ *
 *  Seating / standing anchors
 * ------------------------------------------------------------------ */

export const SEATS = {
    dewiDesk: { x: -2.6, y: BODY.seatH, z: 0.95, rotY: Math.PI, desk: true },
    singgihDesk: { x: -1.2, y: BODY.seatH, z: -0.95, rotY: 0, desk: true },
    anderaDesk: { x: -0.55, y: BODY.seatH, z: 0.95, rotY: Math.PI, desk: true },

    dewiSofa: { x: 7.45, y: BODY.seatH + 0.08, z: 0.55, rotY: -Math.PI / 2 },
    singgihSofa: { x: 7.45, y: BODY.seatH + 0.08, z: 1.8, rotY: -Math.PI / 2 },
    anderaSofa: { x: 7.45, y: BODY.seatH + 0.08, z: 3.0, rotY: -Math.PI / 2 },
};

export const BED_SPOT = { x: -7.75, y: 0.56, z: -4.55, rotY: Math.PI / 2 };

/* ------------------------------------------------------------------ *
 *  Where characters stand while they "think" / read papers.
 * ------------------------------------------------------------------ */

export const STAND_SPOTS = [
    { x: 1.1, z: 3.4, rotY: Math.PI * 0.15 },
    { x: -3.4, z: 3.9, rotY: Math.PI * 0.9 },
    { x: 4.2, z: -3.9, rotY: Math.PI * 0.5 },
    { x: -6.7, z: 3.9, rotY: Math.PI * 0.8 },
    { x: 6.0, z: 4.6, rotY: Math.PI * 1.2 },
];

/* ------------------------------------------------------------------ *
 *  Navigation
 * ------------------------------------------------------------------ */

export const NAV = {
    minX: P.outer.minX + 0.1,
    maxX: P.outer.maxX - 0.1,
    minZ: P.outer.minZ + 0.1,
    maxZ: P.outer.maxZ - 0.1,
    cell: 0.22,
    agentRadius: 0.32,
};

/** Rects that are structurally unwalkable (walls, fixed partitions). */
export const BLOCKERS = [
    // exterior shell (with the front-door opening left open)
    { x0: P.outer.minX - 0.3, z0: P.outer.minZ - 0.3, x1: P.outer.maxX + 0.3, z1: P.outer.minZ + 0.02 },
    { x0: P.outer.minX - 0.3, z0: P.outer.maxZ - 0.02, x1: P.openFrontDoor.x - 0.55, z1: P.outer.maxZ + 0.3 },
    { x0: P.openFrontDoor.x + 0.55, z0: P.outer.maxZ - 0.02, x1: P.outer.maxX + 0.3, z1: P.outer.maxZ + 0.3 },
    { x0: P.outer.minX - 0.3, z0: P.outer.minZ - 0.3, x1: P.outer.minX + 0.02, z1: P.outer.maxZ + 0.3 },
    { x0: P.outer.maxX - 0.02, z0: P.outer.minZ - 0.3, x1: P.outer.maxX + 0.3, z1: P.outer.maxZ + 0.3 },

    // bedroom / bathroom wall (with both door gaps left open)
    {
        x0: P.partBedroom - 0.07,
        z0: P.outer.minZ,
        x1: P.partBedroom + 0.07,
        z1: P.openBedroomDoor.z - P.openBedroomDoor.w / 2 - 0.05,
    },
    {
        x0: P.partBedroom - 0.07,
        z0: P.openBedroomDoor.z + P.openBedroomDoor.w / 2 + 0.05,
        x1: P.partBedroom + 0.07,
        z1: P.openBathDoor.z - P.openBathDoor.w / 2 - 0.05,
    },
    {
        x0: P.partBedroom - 0.07,
        z0: P.openBathDoor.z + P.openBathDoor.w / 2 + 0.05,
        x1: P.partBedroom + 0.07,
        z1: P.partBathS,
    },
    // bedroom | bathroom, bathroom | entry
    { x0: P.outer.minX, z0: P.partBathN - 0.07, x1: P.partBedroom + 0.07, z1: P.partBathN + 0.07 },
    { x0: P.outer.minX, z0: P.partBathS - 0.07, x1: P.partBedroom + 0.07, z1: P.partBathS + 0.07 },

    // hall | office (cased opening kept open)
    { x0: P.partOffice - 0.07, z0: P.outer.minZ, x1: P.partOffice + 0.07, z1: P.openHallToOffice.z - P.openHallToOffice.w / 2 - 0.05 },
    { x0: P.partOffice - 0.07, z0: P.openHallToOffice.z + P.openHallToOffice.w / 2 + 0.05, x1: P.partOffice + 0.07, z1: 0.05 },

    // office | pantry (cased opening kept open)
    { x0: P.partPantry - 0.07, z0: P.outer.minZ, x1: P.partPantry + 0.07, z1: P.openOfficeToPantry.z - P.openOfficeToPantry.w / 2 - 0.05 },
    { x0: P.partPantry - 0.07, z0: P.openOfficeToPantry.z + P.openOfficeToPantry.w / 2 + 0.05, x1: P.partPantry + 0.07, z1: P.partLounge },

    // pantry | lounge (glazed door kept open — the leaf swings, so treat
    // the gap as open and just make sure nobody stands in the arc)
    { x0: P.partPantry, z0: P.partLounge - 0.07, x1: P.openPantryToLounge.x - P.openPantryToLounge.w / 2 - 0.05, z1: P.partLounge + 0.07 },
    { x0: P.openPantryToLounge.x + P.openPantryToLounge.w / 2 + 0.05, z0: P.partLounge - 0.07, x1: P.outer.maxX, z1: P.partLounge + 0.07 },
];

/** Floor finish per room — drives which texture is used. */
export const FLOORS = [
    { rect: P.bedroom, kind: 'wood', tone: 0x6b4a30 },
    { rect: P.bathroom, kind: 'tile', tone: 0xbfc6cc },
    { rect: P.hall, kind: 'wood', tone: 0x6b4a30 },
    { rect: P.entry, kind: 'tileLarge', tone: 0xa8a29a },
    { rect: P.office, kind: 'wood', tone: 0x7a5638 },
    { rect: P.pantry, kind: 'tileSmall', tone: 0xd6d2cb },
    { rect: P.lounge, kind: 'wood', tone: 0x7a5638 },
];