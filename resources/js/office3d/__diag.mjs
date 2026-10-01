import * as THREE from 'three';
globalThis.THREE = THREE;
const { NavGrid } = await import('./nav.js');
const { allSpots } = await import('./activities.js');
const { P } = await import('./plan.js');

const nav = new NavGrid();
for (const s of allSpots()) nav.addAnchor(s);
for (const key of ['sofa0','sofa1','sofa2','dewiDesk','singgihDesk','anderaDesk','bed']) {
  const s = allSpots().find((x) => x.id === key);
  if (s) nav.addAnchor({ x: s.x, z: s.z, approach: s.approach });
}

console.log('P.outer', JSON.stringify(P.outer));
console.log('\n-- spots inside a wall cell --');
for (const s of allSpots()) {
  const goal = s.approach || s;
  if (nav.inWall(s.x, s.z)) console.log(`  SPOT     ${s.id.padEnd(14)} (${s.x}, ${s.z})`);
  if (nav.inWall(goal.x, goal.z)) console.log(`  APPROACH ${s.id.padEnd(14)} (${goal.x}, ${goal.z})`);
}
console.log('\n-- the stuck cell (5.05,-1.81) --');
const c = nav.colOf(5.05), r = nav.rowOf(-1.81), i = nav.index(c, r);
console.log(`  col=${c} row=${r} idx=${i} wall=${nav.wall[i]} furn=${nav.furn[i]} blocked=${nav.blocked[i]} terminal=${nav.terminal[i]}`);
console.log(`  centre = (${nav.xOf(c).toFixed(2)}, ${nav.zOf(r).toFixed(2)})`);
console.log('\n-- neighbourhood (#=wall X=blocked T=terminal .=free) --');
let out = '';
for (let rr = r - 6; rr <= r + 6; rr++) {
  let line = '';
  for (let cc = c - 16; cc <= c + 16; cc++) {
    const idx = nav.index(cc, rr);
    if (!nav.inBounds(cc, rr)) { line += ' '; continue; }
    line += nav.wall[idx] ? '#' : nav.blocked[idx] ? (nav.terminal[idx] ? 'T' : 'X') : '.';
  }
  out += line + '\n';
}
console.log(out);
console.log('-- spots within 2.5m of the stuck point --');
for (const s of allSpots()) {
  const d = Math.hypot(s.x - 5.05, s.z + 1.81);
  if (d < 2.5) console.log(`  ${s.id.padEnd(14)} (${s.x}, ${s.z}) d=${d.toFixed(2)} approach=${s.approach ? `(${s.approach.x}, ${s.approach.z})` : 'none'}`);
}
