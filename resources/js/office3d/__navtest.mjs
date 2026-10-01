/* Nav self-test — run with `node resources/js/office3d/__navtest.mjs`.
 * Verifies every activity anchor is reachable from the office floor. */
import { NavGrid } from './nav.js';
import { SEATS, BED_SPOT, STAND_SPOTS } from './plan.js';

const grid = new NavGrid();

/* Every destination the brain can pick, with the side it is entered from. */
const anchors = [
    { name: 'office-centre', x: -1.5, z: 2.5 },

    ...Object.keys(SEATS).map((k) => ({ name: k, ...SEATS[k] })),
    { name: 'bed', ...BED_SPOT },
    ...STAND_SPOTS.map((p, i) => ({ name: `stand${i}`, x: p.x, z: p.z, approach: approachFrom(p.x, p.z) })),

    { name: 'pantry-sink', x: 5.0, z: -5.0 },
    { name: 'pantry-hob', x: 6.2, z: -5.0 },
    { name: 'island', x: 5.4, z: -2.0 },
    { name: 'fridge', x: 3.6, z: -2.4 },
    { name: 'tall-pantry', x: 8.0, z: -4.3 },
    /* bathroom fittings are used from OUTSIDE their footprint */
    { name: 'shower', x: -7.6, z: 1.2, approach: { x: -6.9, z: 1.2 } },
    { name: 'wc', x: -8.0, z: 1.7, approach: { x: -7.6, z: 1.2 } },
    { name: 'vanity', x: -6.8, z: 0.45, approach: { x: -6.5, z: 0.95 } },
    { name: 'bedroom', x: -7.2, z: -2.5 },
    /* NOTE: the wardrobe (plan.js:113) is not listed as a target. Its east
     * face sits 0.42 m from the partition and the bed is 0.42 m from its
     * west face, so there is no standing room in front of it — a plan
     * quirk, not a nav failure. Treat it as scenery. */
    { name: 'hall', x: -5.1, z: 0.0 },
    { name: 'entry', x: -6.6, z: 4.0 },
    { name: 'lounge-tv', x: 3.2, z: 1.7 },
    { name: 'coffee-table', x: 5.35, z: 1.7 },
    { name: 'bookshelf', x: -2.3, z: -5.4 },
    { name: 'server-rack', x: -3.8, z: -5.2 },
];

/* stand a little toward the middle of the flat so the carve has a mouth */
function approachFrom(x, z) {
    return { x: x * 0.75 - 0.4, z: z * 0.75 + 0.4 };
}

let fail = 0;
for (const a of anchors) {
    if (a.approach === undefined && !a.seat) a.approach = approachFrom(a.x, a.z);
    grid.addAnchor(a);
}

const home = grid.reachableFrom(-1.5, 2.5);
console.log(`grid ${grid.cols} x ${grid.rows} (${grid.size} cells), open ${home.size}`);

for (const a of anchors) {
    const i = grid.index(grid.colOf(a.x), grid.rowOf(a.z));
    if (grid.wall[i]) {
        console.log(`  FAIL ${a.name.padEnd(16)} anchor is inside a WALL @ (${a.x}, ${a.z})`);
        fail++;
        continue;
    }
    if (!home.has(i)) {
        console.log(`  FAIL ${a.name.padEnd(16)} disconnected @ (${a.x}, ${a.z})`);
        fail++;
    }
}

/* a seat must be enterable: A* has to be able to end on it */
for (const a of anchors) {
    const path = grid.findPath(-1.5, 2.5, a.x, a.z);
    if (!path) {
        console.log(`  FAIL ${a.name.padEnd(16)} no path from office`);
        fail++;
    }
}

const leg = (n, x, z) => console.log(`\n${n}: ${grid.findPath(-1.5, 2.5, x, z).map((p) => `(${p.x.toFixed(2)},${p.z.toFixed(2)})`).join(' -> ')}`);
leg('office -> pantry counter', 5.0, -5.0);
leg('office -> bathroom', -7.2, 1.1);
leg('office -> bed', BED_SPOT.x, BED_SPOT.z);
leg('office -> sofa seat 2', SEATS.singgihSofa.x, SEATS.singgihSofa.z);

console.log(fail === 0 ? '\nALL ANCHORS REACHABLE' : `\n${fail} FAILURES`);
process.exit(fail === 0 ? 0 : 1);
