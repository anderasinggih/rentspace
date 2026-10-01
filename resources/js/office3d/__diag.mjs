import * as THREE from 'three';
globalThis.THREE = THREE;
const { NavGrid } = await import('./nav.js');
const { allSpots } = await import('./activities.js');

/* exactly the smoke test's setup */
const nav = new NavGrid();
const spots = allSpots();
for (const s of spots) nav.addAnchor(s);

console.log('inWall(5.05,-1.81) =', nav.inWall(5.05, -1.81));
const c = nav.colOf(5.05), r = nav.rowOf(-1.81);
console.log(`col=${c} row=${r} wall=${nav.wall[nav.index(c,r)]} centre=(${nav.xOf(c).toFixed(2)},${nav.zOf(r).toFixed(2)})`);

/* walk the wall band that separates pantry from lounge */
console.log('\n-- wall cells in the pantry/lounge band z=-2.2..-1.0 --');
for (let z = -2.2; z <= -1.0; z += 0.22) {
  let line = `z=${z.toFixed(2)} `;
  for (let x = 2.0; x <= 8.5; x += 0.22) line += nav.inWall(x, z) ? '#' : '.';
  console.log(line);
}
console.log('        x=' + Array.from({length: 30}, (_, i) => (2.0 + i * 0.22).toFixed(1).slice(0, 3).padEnd(3)).join(''));
