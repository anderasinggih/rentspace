import * as THREE from 'three';
import {
    roundedBoxGeometry, mesh, setShadowRecursive, contactShadow, Rng, lerp,
    TAU, latheGeometry, pipeGeometry, clamp,
} from './lib.js';
import { P, BODY } from './plan.js';
import { texMaterial, retile, artworkCanvas, fabric, carpet, woodVeneer } from './textures.js';

/* ================================================================== *
 *  Shared builders
 * ================================================================== */

/** Four tapered legs with a small foot pad — the detail that stops
 *  furniture from looking like it is floating or melting into the floor. */
function legs(parent, M, { w, d, h, inset = 0.06, thick = 0.05, mat = null, splay = 0 }) {
    const mat2 = mat || M.blackMetal;
    const geo = new THREE.CylinderGeometry(thick * 0.42, thick * 0.55, h, 10);
    for (const sx of [-1, 1]) {
        for (const sz of [-1, 1]) {
            const leg = new THREE.Mesh(geo, mat2);
            leg.position.set(sx * (w / 2 - inset), h / 2, sz * (d / 2 - inset));
            if (splay) {
                leg.rotation.z = -sx * splay;
                leg.rotation.x = sz * splay;
            }
            leg.castShadow = true;
            leg.receiveShadow = true;
            parent.add(leg);
        }
    }
}

/** Ground contact decal sized to the footprint. */
function groundBlob(parent, w, d, opacity = 0.4) {
    const c = contactShadow(w * 0.62, d * 0.62, opacity);
    parent.add(c);
    return c;
}

/** Thin extruded panel with rounded corners — shelves, doors, backs. */
function panel(parent, mat, w, h, t = 0.02, x = 0, y = 0, z = 0, r = 0.006) {
    const m = new THREE.Mesh(roundedBoxGeometry(w, h, t, r), mat);
    m.position.set(x, y, z);
    m.castShadow = true;
    m.receiveShadow = true;
    parent.add(m);
    return m;
}

/* ================================================================== *
 *  OFFICE — shared bench, chairs, monitors, desk clutter
 * ================================================================== */

export function buildDeskBench(M) {
    const g = new THREE.Group();
    g.name = 'desk-bench';

    const W = 4.6;
    const D = 0.78;
    const H = 0.74;

    // top: two laminated slabs with a shadow gap, so the edge has depth
    const top = new THREE.Mesh(roundedBoxGeometry(W, 0.038, D, 0.008), M.woodWarm);
    top.position.set(0, H - 0.019, 0);
    top.castShadow = true;
    top.receiveShadow = true;
    g.add(top);

    const under = new THREE.Mesh(roundedBoxGeometry(W - 0.06, 0.02, D - 0.06, 0.004), M.woodDark);
    under.position.set(0, H - 0.05, 0);
    under.castShadow = true;
    g.add(under);

    // cable tray slung underneath
    const tray = new THREE.Mesh(roundedBoxGeometry(W - 0.5, 0.06, 0.1, 0.01), M.metalDark);
    tray.position.set(0, H - 0.16, -D / 2 + 0.12);
    tray.castShadow = true;
    g.add(tray);

    // end frames (A-frame steel legs) — reads as real joinery
    for (const sx of [-1, 1]) {
        const frame = new THREE.Group();
        const post = new THREE.Mesh(roundedBoxGeometry(0.05, H - 0.05, 0.05, 0.01), M.blackMetal);
        post.position.set(0, (H - 0.05) / 2, 0);
        post.castShadow = true;
        frame.add(post);
        const foot = new THREE.Mesh(roundedBoxGeometry(0.05, 0.05, D - 0.12, 0.01), M.blackMetal);
        foot.position.set(0, 0.03, 0);
        foot.castShadow = true;
        frame.add(foot);
        const brace = new THREE.Mesh(roundedBoxGeometry(0.04, 0.04, D - 0.3, 0.01), M.blackMetal);
        brace.position.set(0, 0.14, 0);
        brace.castShadow = true;
        frame.add(brace);
        frame.position.set(sx * (W / 2 - 0.14), 0, 0);
        g.add(frame);
    }

    // centre leg with a pedestal drawer unit
    const ped = new THREE.Mesh(roundedBoxGeometry(0.42, H - 0.1, D - 0.16, 0.01), M.white);
    ped.position.set(0.9, (H - 0.1) / 2, 0);
    ped.castShadow = true;
    ped.receiveShadow = true;
    g.add(ped);
    for (let i = 0; i < 3; i++) {
        const drawerFace = new THREE.Mesh(roundedBoxGeometry(0.38, 0.17, 0.012, 0.006), M.white);
        drawerFace.position.set(0.9, 0.16 + i * 0.19, D / 2 - 0.06);
        drawerFace.castShadow = true;
        g.add(drawerFace);
        const pull = new THREE.Mesh(roundedBoxGeometry(0.16, 0.014, 0.014, 0.006), M.metal);
        pull.position.set(0.9, 0.16 + i * 0.19, D / 2 - 0.048);
        pull.castShadow = true;
        g.add(pull);
    }

    // modesty panel at the back so the desk isn't see-through
    const mod = new THREE.Mesh(roundedBoxGeometry(W - 0.36, 0.34, 0.016, 0.006), M.woodWarm);
    mod.position.set(0, H - 0.26, -D / 2 + 0.06);
    mod.castShadow = true;
    mod.receiveShadow = true;
    g.add(mod);

    g.add(groundBlob(g, W, D, 0.5));
    return g;
}

/** Ergonomic task chair: 5-star base, castors, gas lift, mesh back. */
export function buildTaskChair(M, { accent = 0x2f3742 } = {}) {
    const g = new THREE.Group();
    g.name = 'task-chair';

    const seatH = BODY.seatH;

    // seat pan with a waterfall front edge
    const seat = new THREE.Mesh(roundedBoxGeometry(0.47, 0.07, 0.46, 0.05), M.cream);
    seat.position.set(0, seatH, 0);
    seat.castShadow = true;
    seat.receiveShadow = true;
    g.add(seat);

    // cushion insert
    const cushion = new THREE.Mesh(roundedBoxGeometry(0.43, 0.045, 0.42, 0.04), M.sofaFabric);
    cushion.position.set(0, seatH + 0.05, -0.01);
    cushion.castShadow = true;
    g.add(cushion);

    // back rest, tilted, with a lumbar bulge
    const backGroup = new THREE.Group();
    backGroup.position.set(0, seatH + 0.06, -0.22);
    backGroup.rotation.x = -0.14;
    const backFrame = new THREE.Mesh(roundedBoxGeometry(0.44, 0.52, 0.035, 0.016), M.blackMetal);
    backFrame.position.set(0, 0.26, 0);
    backFrame.castShadow = true;
    backGroup.add(backFrame);
    const backMesh = new THREE.Mesh(roundedBoxGeometry(0.40, 0.47, 0.02, 0.012), M.charcoal);
    backMesh.position.set(0, 0.26, 0.02);
    backGroup.add(backMesh);
    const lumbar = new THREE.Mesh(roundedBoxGeometry(0.34, 0.11, 0.05, 0.03), M.sofaFabric);
    lumbar.position.set(0, 0.12, 0.03);
    lumbar.castShadow = true;
    backGroup.add(lumbar);
    g.add(backGroup);

    // armrests
    for (const sx of [-1, 1]) {
        const arm = new THREE.Group();
        const stem = new THREE.Mesh(roundedBoxGeometry(0.03, 0.2, 0.05, 0.012), M.blackMetal);
        stem.position.set(sx * 0.255, seatH + 0.14, -0.02);
        stem.castShadow = true;
        arm.add(stem);
        const pad = new THREE.Mesh(roundedBoxGeometry(0.055, 0.028, 0.24, 0.014), M.blackSoft);
        pad.position.set(sx * 0.255, seatH + 0.25, -0.02);
        pad.castShadow = true;
        arm.add(pad);
        g.add(arm);
    }

    // gas cylinder
    const cyl = new THREE.Mesh(new THREE.CylinderGeometry(0.035, 0.042, seatH - 0.12, 14), M.chrome);
    cyl.position.set(0, (seatH - 0.12) / 2 + 0.06, 0);
    cyl.castShadow = true;
    g.add(cyl);

    // 5-star base + castors
    for (let i = 0; i < 5; i++) {
        const a = (i / 5) * TAU + 0.3;
        const spoke = new THREE.Mesh(roundedBoxGeometry(0.26, 0.032, 0.05, 0.014), M.blackMetal);
        spoke.position.set(Math.cos(a) * 0.14, 0.055, Math.sin(a) * 0.14);
        spoke.rotation.y = -a;
        spoke.castShadow = true;
        g.add(spoke);

        const castorStem = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.012, 0.045, 8), M.metalDark);
        castorStem.position.set(Math.cos(a) * 0.27, 0.035, Math.sin(a) * 0.27);
        g.add(castorStem);

        const wheel = new THREE.Mesh(new THREE.TorusGeometry(0.026, 0.012, 8, 14), M.matteBlack);
        wheel.position.set(Math.cos(a) * 0.27, 0.026, Math.sin(a) * 0.27);
        wheel.rotation.y = -a;
        wheel.castShadow = true;
        g.add(wheel);
    }

    const hub = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.07, 0.05, 14), M.blackMetal);
    hub.position.set(0, 0.07, 0);
    hub.castShadow = true;
    g.add(hub);

    g.add(groundBlob(g, 0.62, 0.62, 0.42));
    return g;
}

/** Monitor with a live canvas screen + stand + a little desk glow. */
export function buildMonitor(M, { w = 0.58, h = 0.34, screenTex = null, tilt = 0.05 } = {}) {
    const g = new THREE.Group();
    g.name = 'monitor';

    const bezel = 0.012;
    const shell = new THREE.Mesh(roundedBoxGeometry(w + bezel * 2, h + bezel * 2, 0.018, 0.006), M.matteBlack);
    shell.position.set(0, h / 2 + 0.02, 0);
    shell.castShadow = true;
    g.add(shell);

    const screenMat = screenTex
        ? new THREE.MeshBasicMaterial({ map: screenTex, toneMapped: false })
        : new THREE.MeshBasicMaterial({ color: 0x0d1420 });
    const screen = new THREE.Mesh(new THREE.PlaneGeometry(w, h), screenMat);
    screen.position.set(0, h / 2 + 0.02, 0.0105);
    g.add(screen);

    // stand
    const neck = new THREE.Mesh(roundedBoxGeometry(0.05, 0.2, 0.026, 0.008), M.metalDark);
    neck.position.set(0, 0.1, -0.02);
    neck.castShadow = true;
    g.add(neck);
    const foot = new THREE.Mesh(roundedBoxGeometry(0.2, 0.014, 0.15, 0.006), M.metalDark);
    foot.position.set(0, 0.008, -0.02);
    foot.castShadow = true;
    g.add(foot);

    // power LED
    const led = new THREE.Mesh(new THREE.CircleGeometry(0.004, 8), new THREE.MeshBasicMaterial({ color: 0x5ef2a8 }));
    led.position.set(w / 2 - 0.02, 0.028, 0.0105);
    g.add(led);

    g.rotation.x = tilt;
    g.userData.screenMat = screenMat;
    return g;
}

/** Keyboard + mouse + mousepad, sitting on the desk surface. */
export function buildDeskInput(M) {
    const g = new THREE.Group();
    g.name = 'desk-input';

    const pad = new THREE.Mesh(roundedBoxGeometry(0.42, 0.004, 0.19, 0.01), M.matteBlack);
    pad.position.set(0, 0.002, 0);
    pad.receiveShadow = true;
    g.add(pad);

    const kb = new THREE.Mesh(roundedBoxGeometry(0.34, 0.018, 0.12, 0.005), M.white);
    kb.position.set(0, 0.012, 0);
    kb.castShadow = true;
    kb.receiveShadow = true;
    g.add(kb);

    // key rows
    const keyMat = new THREE.MeshStandardMaterial({ color: 0x2a2e34, roughness: 0.7 });
    for (let r = 0; r < 5; r++) {
        for (let c = 0; c < 14; c++) {
            const k = new THREE.Mesh(new THREE.BoxGeometry(0.019, 0.004, 0.017), keyMat);
            k.position.set(-0.152 + c * 0.0234, 0.0215, -0.045 + r * 0.0225);
            g.add(k);
        }
    }
    const space = new THREE.Mesh(new THREE.BoxGeometry(0.13, 0.004, 0.017), keyMat);
    space.position.set(0, 0.0215, 0.045);
    g.add(space);

    const mouse = new THREE.Mesh(
        latheGeometry([[0, 0], [0.026, 0.004], [0.03, 0.02], [0.026, 0.038], [0.012, 0.048], [0, 0.05]], 18),
        M.white
    );
    mouse.position.set(0.29, 0.004, 0.01);
    mouse.castShadow = true;
    g.add(mouse);

    return g;
}

/** Mug with a handle + coffee surface. */
export function buildMug(M, { color = 0xf3f1ec, r = 0.042, h = 0.095 } = {}) {
    const g = new THREE.Group();
    const mat = new THREE.MeshStandardMaterial({ color, roughness: 0.28 });
    const body = new THREE.Mesh(
        latheGeometry([[0, 0], [r * 0.85, 0], [r * 0.9, 0.008], [r, h * 0.5], [r * 1.02, h], [r * 0.94, h], [r * 0.94, 0.012], [0, 0.012]], 24),
        mat
    );
    body.castShadow = true;
    body.receiveShadow = true;
    g.add(body);

    const coffee = new THREE.Mesh(new THREE.CircleGeometry(r * 0.93, 20), new THREE.MeshStandardMaterial({ color: 0x3a2317, roughness: 0.22 }));
    coffee.rotation.x = -Math.PI / 2;
    coffee.position.y = h - 0.012;
    g.add(coffee);

    const handle = new THREE.Mesh(new THREE.TorusGeometry(r * 0.55, r * 0.15, 8, 18, Math.PI * 1.3), mat);
    handle.position.set(r * 0.95, h * 0.55, 0);
    handle.rotation.set(0, Math.PI / 2, -0.35);
    handle.castShadow = true;
    g.add(handle);

    return g;
}

/** A small stack of loose paper — the detail that sells "someone works here". */
export function buildPaperStack(M, { sheets = 7, w = 0.21, d = 0.29, seed = 3 } = {}) {
    const g = new THREE.Group();
    const rng = new Rng(seed);
    for (let i = 0; i < sheets; i++) {
        const sheet = new THREE.Mesh(
            new THREE.PlaneGeometry(w, d),
            new THREE.MeshStandardMaterial({ color: 0xf6f5f1, roughness: 0.95 })
        );
        sheet.rotation.x = -Math.PI / 2;
        sheet.rotation.z = rng.float(-0.09, 0.09);
        sheet.position.set(rng.float(-0.012, 0.012), 0.001 + i * 0.0006, rng.float(-0.012, 0.012));
        sheet.receiveShadow = true;
        g.add(sheet);
    }
    return g;
}

/** Open laptop. */
export function buildLaptop(M, open = true) {
    const g = new THREE.Group();
    const base = new THREE.Mesh(roundedBoxGeometry(0.32, 0.012, 0.22, 0.006), M.metal);
    base.position.y = 0.006;
    base.castShadow = true;
    base.receiveShadow = true;
    g.add(base);

    const kbPlate = new THREE.Mesh(roundedBoxGeometry(0.28, 0.002, 0.1, 0.003), M.charcoal);
    kbPlate.position.set(0, 0.013, 0.03);
    g.add(kbPlate);
    const trackpad = new THREE.Mesh(roundedBoxGeometry(0.1, 0.002, 0.06, 0.004), M.charcoal);
    trackpad.position.set(0, 0.013, -0.07);
    g.add(trackpad);

    if (open) {
        const lid = new THREE.Group();
        lid.position.set(0, 0.012, -0.108);
        lid.rotation.x = -1.95;
        const shell = new THREE.Mesh(roundedBoxGeometry(0.32, 0.215, 0.007, 0.006), M.metal);
        shell.position.set(0, 0.107, 0);
        shell.castShadow = true;
        lid.add(shell);
        const scr = new THREE.Mesh(
            new THREE.PlaneGeometry(0.295, 0.19),
            new THREE.MeshBasicMaterial({ color: 0x101a2c, toneMapped: false })
        );
        scr.position.set(0, 0.107, 0.0042);
        lid.add(scr);
        g.add(lid);
    }
    return g;
}

/** Adjustable desk lamp — arm, shade, joint. */
export function buildDeskLamp(M, color = 0x2f3742) {
    const g = new THREE.Group();
    const mat = new THREE.MeshStandardMaterial({ color, roughness: 0.4, metalness: 0.3 });

    const base = new THREE.Mesh(latheGeometry([[0, 0], [0.07, 0], [0.075, 0.008], [0.07, 0.018], [0.02, 0.022], [0.018, 0.03], [0, 0.03]], 20), mat);
    base.castShadow = true;
    g.add(base);

    const arm1 = new THREE.Mesh(new THREE.CylinderGeometry(0.008, 0.009, 0.34, 10), mat);
    arm1.position.set(0, 0.19, 0.03);
    arm1.rotation.x = 0.28;
    arm1.castShadow = true;
    g.add(arm1);

    const joint = new THREE.Mesh(new THREE.SphereGeometry(0.016, 12, 10), mat);
    joint.position.set(0, 0.35, 0.075);
    g.add(joint);

    const arm2 = new THREE.Mesh(new THREE.CylinderGeometry(0.007, 0.008, 0.26, 10), mat);
    arm2.position.set(0, 0.42, 0.16);
    arm2.rotation.x = -1.05;
    arm2.castShadow = true;
    g.add(arm2);

    const shade = new THREE.Mesh(
        new THREE.CylinderGeometry(0.045, 0.075, 0.085, 20, 1, true),
        new THREE.MeshStandardMaterial({ color, roughness: 0.4, metalness: 0.3, side: THREE.DoubleSide })
    );
    shade.position.set(0, 0.48, 0.27);
    shade.rotation.x = -0.5;
    shade.castShadow = true;
    g.add(shade);

    const bulb = new THREE.Mesh(new THREE.SphereGeometry(0.03, 12, 10), new THREE.MeshBasicMaterial({ color: 0xffeac6 }));
    bulb.position.set(0, 0.465, 0.285);
    g.add(bulb);

    return g;
}

/* ================================================================== *
 *  LOUNGE — sofa, coffee table, media wall, rug, lamps
 * ================================================================== */

export function buildSofa(M, { seats = 3, w = 2.6, d = 0.95, fabricColor = 0x8d949c } = {}) {
    const g = new THREE.Group();
    g.name = 'sofa';

    const fab = texMaterial(retile(fabric(fabricColor, { weave: 6 }), 1 / 0.35), { roughness: 1 });
    const seatH = 0.42;
    const armW = 0.16;

    // plinth
    const plinth = new THREE.Mesh(roundedBoxGeometry(w - 0.12, 0.1, d - 0.14, 0.02), M.woodDark);
    plinth.position.set(0, 0.09, 0);
    plinth.castShadow = true;
    plinth.receiveShadow = true;
    g.add(plinth);

    // base cushion block
    const base = new THREE.Mesh(roundedBoxGeometry(w, 0.2, d - 0.06, 0.05), fab);
    base.position.set(0, seatH - 0.06, 0.02);
    base.castShadow = true;
    base.receiveShadow = true;
    g.add(base);

    // seat cushions — individually rounded, slightly irregular
    const seatW = (w - armW * 2 - 0.04) / seats;
    const rng = new Rng(17);
    const cushions = [];
    for (let i = 0; i < seats; i++) {
        const c = new THREE.Mesh(roundedBoxGeometry(seatW - 0.02, 0.16, d - 0.22, 0.055), fab);
        c.position.set(-w / 2 + armW + seatW * (i + 0.5), seatH + 0.07, 0.05);
        c.rotation.x = -0.02;
        c.rotation.z = rng.float(-0.012, 0.012);
        c.castShadow = true;
        c.receiveShadow = true;
        g.add(c);
        cushions.push(c);
    }

    // back
    const back = new THREE.Mesh(roundedBoxGeometry(w, 0.52, 0.2, 0.06), fab);
    back.position.set(0, seatH + 0.34, -d / 2 + 0.12);
    back.rotation.x = 0.1;
    back.castShadow = true;
    back.receiveShadow = true;
    g.add(back);

    // back cushions
    for (let i = 0; i < seats; i++) {
        const c = new THREE.Mesh(roundedBoxGeometry(seatW - 0.03, 0.42, 0.16, 0.05), fab);
        c.position.set(-w / 2 + armW + seatW * (i + 0.5), seatH + 0.3, -d / 2 + 0.24);
        c.rotation.x = 0.16;
        c.rotation.z = rng.float(-0.02, 0.02);
        c.castShadow = true;
        g.add(c);
    }

    // arms — rolled
    for (const sx of [-1, 1]) {
        const arm = new THREE.Mesh(
            new THREE.CylinderGeometry(0.115, 0.115, d - 0.08, 20, 1, false, 0, Math.PI),
            fab
        );
        arm.rotation.z = Math.PI / 2;
        arm.rotation.y = Math.PI / 2;
        arm.position.set(sx * (w / 2 - 0.115), seatH + 0.14, 0.02);
        arm.castShadow = true;
        arm.receiveShadow = true;
        g.add(arm);

        const armSide = new THREE.Mesh(roundedBoxGeometry(0.1, 0.34, d - 0.08, 0.04), fab);
        armSide.position.set(sx * (w / 2 - 0.05), seatH - 0.05, 0.02);
        armSide.castShadow = true;
        g.add(armSide);
    }

    // scatter pillows in an accent fabric
    const accentA = texMaterial(retile(fabric(0xc98a72, { weave: 7, seed: 3 }), 1 / 0.3), { roughness: 1 });
    const accentB = texMaterial(retile(fabric(0x5f7f86, { weave: 7, seed: 9 }), 1 / 0.3), { roughness: 1 });
    for (const [px, pz, rot, mat] of [
        [-w / 2 + 0.42, -0.14, 0.34, accentA],
        [w / 2 - 0.44, -0.16, -0.28, accentB],
        [0.1, -0.18, 0.12, accentB],
    ]) {
        const p = new THREE.Mesh(roundedBoxGeometry(0.42, 0.12, 0.42, 0.08), mat);
        p.position.set(px, seatH + 0.2, pz);
        p.rotation.set(0.9, rot, 0.12);
        p.castShadow = true;
        g.add(p);
    }

    // folded throw over one arm
    const throwMesh = new THREE.Mesh(roundedBoxGeometry(0.5, 0.05, d * 0.8, 0.03), accentA);
    throwMesh.position.set(w / 2 - 0.22, seatH + 0.19, 0.02);
    throwMesh.rotation.z = 0.02;
    throwMesh.castShadow = true;
    g.add(throwMesh);

    g.userData.cushions = cushions;
    g.add(groundBlob(g, w, d, 0.45));
    return g;
}

export function buildCoffeeTable(M) {
    const g = new THREE.Group();
    g.name = 'coffee-table';
    const w = 1.15;
    const d = 0.62;
    const h = 0.42;

    const top = new THREE.Mesh(roundedBoxGeometry(w, 0.035, d, 0.012), M.marbleCounter);
    top.position.set(0, h, 0);
    top.castShadow = true;
    top.receiveShadow = true;
    g.add(top);

    // lower shelf in walnut
    const shelf = new THREE.Mesh(roundedBoxGeometry(w - 0.22, 0.022, d - 0.16, 0.008), M.woodDark);
    shelf.position.set(0, h - 0.24, 0);
    shelf.castShadow = true;
    shelf.receiveShadow = true;
    g.add(shelf);

    legs(g, M, { w: w - 0.14, d: d - 0.1, h: h - 0.018, inset: 0.02, thick: 0.028, mat: M.metalDark, splay: 0.03 });

    g.add(groundBlob(g, w, d, 0.4));
    return g;
}

export function buildMediaConsole(M) {
    const g = new THREE.Group();
    g.name = 'media-console';
    const w = 0.7;
    const d = 1.2;
    const h = 0.48;

    const carcass = new THREE.Mesh(roundedBoxGeometry(w, h - 0.14, d, 0.01), M.woodWarm);
    carcass.position.set(0, 0.14 + (h - 0.14) / 2, 0);
    carcass.castShadow = true;
    carcass.receiveShadow = true;
    g.add(carcass);

    // two drawer fronts with a shadow gap between them
    for (const sz of [-1, 1]) {
        const front = new THREE.Mesh(roundedBoxGeometry(0.014, h - 0.2, d / 2 - 0.04, 0.005), M.woodDark);
        front.position.set(w / 2 + 0.004, 0.14 + (h - 0.14) / 2, sz * (d / 4 + 0.01));
        front.castShadow = true;
        g.add(front);
        const pull = new THREE.Mesh(roundedBoxGeometry(0.012, 0.014, 0.22, 0.005), M.metal);
        pull.position.set(w / 2 + 0.014, 0.14 + (h - 0.14) / 2, sz * (d / 4 + 0.01));
        pull.castShadow = true;
        g.add(pull);
    }

    legs(g, M, { w: w - 0.06, d: d - 0.14, h: 0.15, inset: 0.03, thick: 0.022, mat: M.metalDark, splay: 0.05 });

    g.add(groundBlob(g, w, d, 0.4));
    return g;
}

export function buildTV(M, { w = 1.25, h = 0.72 } = {}) {
    const g = new THREE.Group();
    g.name = 'tv';

    const panel = new THREE.Mesh(roundedBoxGeometry(w, h, 0.035, 0.006), M.matteBlack);
    panel.castShadow = true;
    g.add(panel);

    const bezel = new THREE.Mesh(roundedBoxGeometry(w - 0.008, h - 0.008, 0.038, 0.005), M.charcoal);
    g.add(bezel);

    const screenCanvas = document.createElement('canvas');
    screenCanvas.width = 640;
    screenCanvas.height = 360;
    const screenTex = new THREE.CanvasTexture(screenCanvas);
    screenTex.colorSpace = THREE.SRGBColorSpace;
    const screen = new THREE.Mesh(
        new THREE.PlaneGeometry(w - 0.018, h - 0.018),
        new THREE.MeshBasicMaterial({ map: screenTex, toneMapped: false })
    );
    screen.position.z = 0.0205;
    g.add(screen);

    g.userData.screenCanvas = screenCanvas;
    g.userData.screenTex = screenTex;
    return g;
}

export function buildFloorLamp(M) {
    const g = new THREE.Group();
    g.name = 'floor-lamp';

    const base = new THREE.Mesh(latheGeometry([[0, 0], [0.15, 0], [0.155, 0.012], [0.14, 0.02], [0.02, 0.026], [0.016, 0.05], [0, 0.05]], 24), M.metalDark);
    base.castShadow = true;
    base.receiveShadow = true;
    g.add(base);

    const stem = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.014, 1.5, 12), M.brass || M.metal);
    stem.position.set(0, 0.79, 0);
    stem.castShadow = true;
    g.add(stem);

    const shade = new THREE.Mesh(
        new THREE.CylinderGeometry(0.17, 0.22, 0.26, 28, 1, true),
        new THREE.MeshStandardMaterial({
            color: 0xf0e6d4,
            roughness: 0.85,
            side: THREE.DoubleSide,
            emissive: 0xffdba8,
            emissiveIntensity: 0.32,
        })
    );
    shade.position.set(0, 1.6, 0);
    g.add(shade);

    const bulb = new THREE.Mesh(new THREE.SphereGeometry(0.06, 14, 10), new THREE.MeshBasicMaterial({ color: 0xffe3b4 }));
    bulb.position.set(0, 1.56, 0);
    g.add(bulb);

    g.add(groundBlob(g, 0.4, 0.4, 0.45));
    return g;
}

/* ================================================================== *
 *  PANTRY / KITCHEN
 * ================================================================== */

export function buildKitchenRun(M, { len = 6.2, depth = 0.62, h = 0.9 } = {}) {
    const g = new THREE.Group();
    g.name = 'kitchen-run';

    // carcass
    const carcass = new THREE.Mesh(roundedBoxGeometry(len, h - 0.1, depth, 0.006), M.white);
    carcass.position.set(0, 0.1 + (h - 0.1) / 2, 0);
    carcass.castShadow = true;
    carcass.receiveShadow = true;
    g.add(carcass);

    // plinth recess
    const plinth = new THREE.Mesh(roundedBoxGeometry(len - 0.06, 0.1, depth - 0.09, 0.004), M.matteBlack);
    plinth.position.set(0, 0.05, 0.02);
    plinth.castShadow = true;
    g.add(plinth);

    // worktop with a small overhang + upstand
    const top = new THREE.Mesh(roundedBoxGeometry(len + 0.04, 0.04, depth + 0.03, 0.006), M.marbleCounter);
    top.position.set(0, h - 0.02, -0.012);
    top.castShadow = true;
    top.receiveShadow = true;
    g.add(top);

    const upstand = new THREE.Mesh(roundedBoxGeometry(len + 0.04, 0.1, 0.02, 0.004), M.marbleCounter);
    upstand.position.set(0, h + 0.05, depth / 2 - 0.01);
    upstand.castShadow = true;
    g.add(upstand);

    // door fronts + slim bar pulls
    const nDoors = Math.max(3, Math.round(len / 0.6));
    const dw = len / nDoors;
    for (let i = 0; i < nDoors; i++) {
        const cx = -len / 2 + dw * (i + 0.5);
        const front = new THREE.Mesh(roundedBoxGeometry(dw - 0.012, h - 0.16, 0.018, 0.004), M.whiteGloss);
        front.position.set(cx, 0.1 + (h - 0.1) / 2, depth / 2 + 0.006);
        front.castShadow = true;
        front.receiveShadow = true;
        g.add(front);
        const pull = new THREE.Mesh(roundedBoxGeometry(dw * 0.55, 0.012, 0.016, 0.005), M.metal);
        pull.position.set(cx, h - 0.14, depth / 2 + 0.026);
        pull.castShadow = true;
        g.add(pull);
    }

    return g;
}

export function buildUpperCabinets(M, { len = 4.0, depth = 0.34, h = 0.72, y = 1.5 } = {}) {
    const g = new THREE.Group();
    const body = new THREE.Mesh(roundedBoxGeometry(len, h, depth, 0.006), M.white);
    body.castShadow = true;
    body.receiveShadow = true;
    g.add(body);

    const n = Math.max(2, Math.round(len / 0.55));
    const dw = len / n;
    for (let i = 0; i < n; i++) {
        const front = new THREE.Mesh(roundedBoxGeometry(dw - 0.012, h - 0.014, 0.018, 0.004), M.whiteGloss);
        front.position.set(-len / 2 + dw * (i + 0.5), 0, depth / 2 + 0.006);
        front.castShadow = true;
        g.add(front);
        const pull = new THREE.Mesh(roundedBoxGeometry(0.014, 0.11, 0.016, 0.005), M.metal);
        pull.position.set(-len / 2 + dw * (i + 0.5) + dw / 2 - 0.05, -h / 2 + 0.1, depth / 2 + 0.026);
        g.add(pull);
    }
    // under-cabinet light valance
    const valance = new THREE.Mesh(roundedBoxGeometry(len, 0.03, depth * 0.5, 0.004), M.metal);
    valance.position.set(0, -h / 2 - 0.015, depth * 0.25);
    valance.castShadow = true;
    g.add(valance);

    g.position.y = y;
    return g;
}

export function buildSink(M, { w = 0.56, d = 0.42 } = {}) {
    const g = new THREE.Group();
    g.name = 'sink';

    const bowl = new THREE.Mesh(
        latheGeometry(
            [[0, -0.16], [w * 0.42, -0.16], [w * 0.5, -0.02], [w * 0.52, 0], [w * 0.5, 0.008], [w * 0.4, -0.14], [0, -0.14]],
            4
        ),
        M.chrome
    );
    bowl.rotation.y = Math.PI / 4;
    bowl.castShadow = true;
    g.add(bowl);

    // sink rim
    const rim = new THREE.Mesh(roundedBoxGeometry(w + 0.04, 0.012, d + 0.04, 0.01), M.chrome);
    rim.position.y = 0.006;
    rim.castShadow = true;
    g.add(rim);

    // gooseneck tap
    const tapPts = [
        new THREE.Vector3(0, 0, -d / 2 - 0.06),
        new THREE.Vector3(0, 0.16, -d / 2 - 0.06),
        new THREE.Vector3(0, 0.27, -d / 2 - 0.03),
        new THREE.Vector3(0, 0.28, d / 2 - 0.02),
        new THREE.Vector3(0, 0.22, d / 2 - 0.01),
    ];
    const tap = new THREE.Mesh(pipeGeometry(tapPts, 0.014, 12), M.chrome);
    tap.castShadow = true;
    g.add(tap);

    const base = new THREE.Mesh(new THREE.CylinderGeometry(0.028, 0.034, 0.03, 16), M.chrome);
    base.position.set(0, 0.015, -d / 2 - 0.06);
    base.castShadow = true;
    g.add(base);

    const lever = new THREE.Mesh(roundedBoxGeometry(0.014, 0.014, 0.11, 0.006), M.chrome);
    lever.position.set(0.04, 0.09, -d / 2 - 0.06);
    lever.rotation.z = -0.5;
    lever.castShadow = true;
    g.add(lever);

    return g;
}

export function buildHob(M, { w = 0.58, d = 0.5 } = {}) {
    const g = new THREE.Group();
    const glass = new THREE.Mesh(roundedBoxGeometry(w, 0.012, d, 0.006), new THREE.MeshStandardMaterial({ color: 0x14171c, roughness: 0.12, metalness: 0.3 }));
    glass.position.y = 0.006;
    glass.receiveShadow = true;
    g.add(glass);
    for (const [ox, oz, r] of [[-0.14, -0.12, 0.075], [0.14, -0.12, 0.062], [-0.14, 0.13, 0.062], [0.15, 0.13, 0.085]]) {
        const ring = new THREE.Mesh(new THREE.RingGeometry(r * 0.82, r, 28), new THREE.MeshStandardMaterial({ color: 0x3a4048, roughness: 0.5 }));
        ring.rotation.x = -Math.PI / 2;
        ring.position.set(ox, 0.0125, oz);
        g.add(ring);
    }
    return g;
}

export function buildFridge(M, { w = 0.7, h = 1.85, d = 0.7 } = {}) {
    const g = new THREE.Group();
    g.name = 'fridge';

    const body = new THREE.Mesh(roundedBoxGeometry(w, h, d, 0.018), M.metal);
    body.position.set(0, h / 2, 0);
    body.castShadow = true;
    body.receiveShadow = true;
    g.add(body);

    // door split
    const splitY = h * 0.62;
    const gap = new THREE.Mesh(roundedBoxGeometry(0.004, 0.012, d - 0.02, 0.002), M.matteBlack);
    gap.position.set(w / 2 + 0.001, splitY, 0);
    g.add(gap);

    // recessed vertical handles
    for (const [y, len] of [[splitY + 0.24, 0.34], [splitY - 0.3, 0.5]]) {
        const handle = new THREE.Mesh(roundedBoxGeometry(0.026, len, 0.03, 0.01), M.metalDark);
        handle.position.set(w / 2 + 0.016, y, d / 2 - 0.09);
        handle.castShadow = true;
        g.add(handle);
    }

    // small display + magnets (a little life)
    const disp = new THREE.Mesh(new THREE.PlaneGeometry(0.14, 0.05), new THREE.MeshBasicMaterial({ color: 0x0b1a22, toneMapped: false }));
    disp.position.set(w / 2 + 0.0025, h * 0.86, -0.06);
    disp.rotation.y = Math.PI / 2;
    g.add(disp);
    const dispOn = new THREE.Mesh(new THREE.PlaneGeometry(0.11, 0.03), new THREE.MeshBasicMaterial({ color: 0x5ad1ff, toneMapped: false }));
    dispOn.position.set(w / 2 + 0.004, h * 0.86, -0.06);
    dispOn.rotation.y = Math.PI / 2;
    g.add(dispOn);

    const rng = new Rng(88);
    const magnetCols = [0xe0524a, 0xf0b429, 0x4a9ad4, 0x67b26a, 0xe87ab0];
    for (let i = 0; i < 5; i++) {
        const m = new THREE.Mesh(
            new THREE.CylinderGeometry(0.018, 0.018, 0.006, 14),
            new THREE.MeshStandardMaterial({ color: magnetCols[i], roughness: 0.35 })
        );
        m.rotation.z = Math.PI / 2;
        m.position.set(w / 2 + 0.005, rng.float(0.5, 1.5), rng.float(-0.2, 0.22));
        m.castShadow = true;
        g.add(m);
    }

    g.add(groundBlob(g, w, d, 0.5));
    return g;
}

export function buildIsland(M, { w = 2.0, d = 1.0, h = 0.92 } = {}) {
    const g = new THREE.Group();
    g.name = 'island';

    const carcass = new THREE.Mesh(roundedBoxGeometry(w - 0.16, h - 0.16, d - 0.16, 0.008), M.woodDark);
    carcass.position.set(0, 0.12 + (h - 0.16) / 2, 0);
    carcass.castShadow = true;
    carcass.receiveShadow = true;
    g.add(carcass);

    // fluted front: a row of half-round staves (this is what makes joinery read)
    const staveGeo = new THREE.CylinderGeometry(0.028, 0.028, h - 0.2, 12, 1, false, 0, Math.PI);
    const n = Math.floor((w - 0.24) / 0.056);
    for (let i = 0; i < n; i++) {
        const s = new THREE.Mesh(staveGeo, M.woodWarm);
        s.rotation.z = Math.PI / 2;
        s.rotation.y = -Math.PI / 2;
        s.position.set(-((n - 1) * 0.056) / 2 + i * 0.056, 0.12 + (h - 0.2) / 2, d / 2 - 0.09);
        s.castShadow = true;
        s.receiveShadow = true;
        g.add(s);
    }

    const top = new THREE.Mesh(roundedBoxGeometry(w, 0.05, d, 0.008), M.marbleCounter);
    top.position.set(0, h - 0.025, 0);
    top.castShadow = true;
    top.receiveShadow = true;
    g.add(top);

    // counter overhang on the seating side
    const lip = new THREE.Mesh(roundedBoxGeometry(w + 0.04, 0.02, 0.12, 0.006), M.marbleCounter);
    lip.position.set(0, h - 0.06, d / 2 + 0.05);
    lip.castShadow = true;
    g.add(lip);

    const plinth = new THREE.Mesh(roundedBoxGeometry(w - 0.3, 0.12, d - 0.3, 0.004), M.matteBlack);
    plinth.position.set(0, 0.06, 0);
    plinth.castShadow = true;
    g.add(plinth);

    g.add(groundBlob(g, w, d, 0.5));
    return g;
}

export function buildStool(M) {
    const g = new THREE.Group();
    const seatH = 0.66;
    const seat = new THREE.Mesh(roundedBoxGeometry(0.34, 0.06, 0.32, 0.06), M.woodWarm);
    seat.position.set(0, seatH, 0);
    seat.castShadow = true;
    seat.receiveShadow = true;
    g.add(seat);

    const pad = new THREE.Mesh(roundedBoxGeometry(0.3, 0.035, 0.28, 0.05), M.sofaFabric);
    pad.position.set(0, seatH + 0.04, 0);
    pad.castShadow = true;
    g.add(pad);

    // splayed metal legs + footring
    for (const sx of [-1, 1]) {
        for (const sz of [-1, 1]) {
            const leg = new THREE.Mesh(new THREE.CylinderGeometry(0.013, 0.016, seatH, 10), M.blackMetal);
            leg.position.set(sx * 0.13, seatH / 2, sz * 0.12);
            leg.rotation.z = -sx * 0.07;
            leg.rotation.x = sz * 0.07;
            leg.castShadow = true;
            g.add(leg);
        }
    }
    const ring = new THREE.Mesh(new THREE.TorusGeometry(0.16, 0.008, 6, 24), M.blackMetal);
    ring.rotation.x = Math.PI / 2;
    ring.position.set(0, 0.22, 0);
    ring.castShadow = true;
    g.add(ring);

    g.add(groundBlob(g, 0.4, 0.4, 0.35));
    return g;
}

/** Kitchen counter clutter: jars, board, kettle, fruit, rack. */
export function buildKitchenClutter(M, { seed = 5 } = {}) {
    const g = new THREE.Group();
    const rng = new Rng(seed);

    // glass storage jars with contents
    const jarMat = new THREE.MeshPhysicalMaterial({
        color: 0xdfe7e4,
        roughness: 0.08,
        transparent: true,
        opacity: 0.35,
        metalness: 0,
    });
    for (let i = 0; i < 3; i++) {
        const r = rng.float(0.05, 0.07);
        const h = rng.float(0.14, 0.22);
        const jar = new THREE.Mesh(
            latheGeometry([[0, 0], [r * 0.92, 0], [r, h * 0.2], [r, h * 0.9], [r * 0.7, h], [r * 0.7, h + 0.02], [0, h + 0.02]], 18),
            jarMat
        );
        jar.position.set(-0.3 + i * 0.16, 0, rng.float(-0.03, 0.03));
        jar.castShadow = true;
        g.add(jar);
        const fill = new THREE.Mesh(
            new THREE.CylinderGeometry(r * 0.9, r * 0.9, h * 0.62, 16),
            new THREE.MeshStandardMaterial({ color: [0xd8b26a, 0xb98b5e, 0xe8e0cf][i], roughness: 0.85 })
        );
        fill.position.set(jar.position.x, h * 0.31, jar.position.z);
        g.add(fill);
        const lid = new THREE.Mesh(new THREE.CylinderGeometry(r * 0.76, r * 0.72, 0.018, 16), M.woodPale);
        lid.position.set(jar.position.x, h + 0.028, jar.position.z);
        lid.castShadow = true;
        g.add(lid);
    }

    // chopping board leaning at the back
    const board = new THREE.Mesh(roundedBoxGeometry(0.3, 0.42, 0.02, 0.02), M.woodPale);
    board.position.set(0.42, 0.21, -0.2);
    board.rotation.x = -0.14;
    board.castShadow = true;
    g.add(board);

    // kettle
    const kettle = new THREE.Group();
    const kb = new THREE.Mesh(
        latheGeometry([[0, 0], [0.085, 0.005], [0.09, 0.05], [0.075, 0.16], [0.05, 0.19], [0.05, 0.2], [0, 0.2]], 20),
        M.whiteGloss
    );
    kb.castShadow = true;
    kettle.add(kb);
    const kspout = new THREE.Mesh(pipeGeometry(
        [new THREE.Vector3(0.06, 0.1, 0), new THREE.Vector3(0.11, 0.12, 0), new THREE.Vector3(0.13, 0.07, 0)], 0.016, 10), M.whiteGloss);
    kettle.add(kspout);
    const khandle = new THREE.Mesh(new THREE.TorusGeometry(0.06, 0.008, 6, 18, Math.PI), M.matteBlack);
    khandle.position.set(-0.03, 0.19, 0);
    khandle.rotation.set(Math.PI / 2, 0, 0.3);
    kettle.add(khandle);
    kettle.position.set(-0.62, 0, 0.02);
    g.add(kettle);

    // fruit bowl
    const bowl = new THREE.Mesh(
        latheGeometry([[0, 0], [0.06, 0.005], [0.1, 0.045], [0.11, 0.07], [0.105, 0.072], [0.095, 0.05], [0.055, 0.012], [0, 0.01]], 24),
        M.whiteGloss
    );
    bowl.position.set(0.72, 0, 0.02);
    bowl.castShadow = true;
    g.add(bowl);
    const fruitCols = [0xe0a12c, 0xc4452f, 0xd8c14a, 0x7ba03c];
    for (let i = 0; i < 5; i++) {
        const f = new THREE.Mesh(
            new THREE.SphereGeometry(rng.float(0.032, 0.042), 14, 12),
            new THREE.MeshStandardMaterial({ color: fruitCols[i % 4], roughness: 0.55 })
        );
        f.scale.y = 0.92;
        f.position.set(0.72 + rng.float(-0.05, 0.05), 0.05 + Math.floor(i / 3) * 0.04, 0.02 + rng.float(-0.05, 0.05));
        f.castShadow = true;
        g.add(f);
    }

    return g;
}

/** Dish rack with plates + mugs, next to the sink. */
export function buildDishRack(M) {
    const g = new THREE.Group();
    const base = new THREE.Mesh(roundedBoxGeometry(0.36, 0.02, 0.28, 0.008), M.metal);
    base.castShadow = true;
    g.add(base);
    for (let i = 0; i < 5; i++) {
        const rail = new THREE.Mesh(new THREE.CylinderGeometry(0.004, 0.004, 0.3, 6), M.metal);
        rail.rotation.x = Math.PI / 2;
        rail.rotation.z = 0.2;
        rail.position.set(-0.14 + i * 0.07, 0.1, 0);
        g.add(rail);
    }
    for (let i = 0; i < 3; i++) {
        const plate = new THREE.Mesh(
            new THREE.CylinderGeometry(0.095, 0.088, 0.012, 20),
            M.whiteGloss
        );
        plate.rotation.x = Math.PI / 2;
        plate.rotation.z = 0.2;
        plate.position.set(-0.07 + i * 0.07, 0.12, 0);
        plate.castShadow = true;
        g.add(plate);
    }
    return g;
}

/* ================================================================== *
 *  BEDROOM
 * ================================================================== */

export function buildBed(M, { w = 1.55, len = 2.0 } = {}) {
    const g = new THREE.Group();
    g.name = 'bed';

    // platform frame with a shadow gap and visible feet
    const frame = new THREE.Mesh(roundedBoxGeometry(w + 0.08, 0.28, len + 0.06, 0.02), M.woodDark);
    frame.position.set(0, 0.24, 0);
    frame.castShadow = true;
    frame.receiveShadow = true;
    g.add(frame);

    for (const sx of [-1, 1]) {
        for (const sz of [-1, 1]) {
            const foot = new THREE.Mesh(new THREE.CylinderGeometry(0.022, 0.018, 0.11, 10), M.woodDark);
            foot.position.set(sx * (w / 2 - 0.08), 0.055, sz * (len / 2 - 0.08));
            foot.castShadow = true;
            g.add(foot);
        }
    }

    // upholstered headboard, vertically channelled
    const hbW = w + 0.12;
    const hb = new THREE.Mesh(roundedBoxGeometry(hbW, 1.0, 0.09, 0.03), M.bedFabric);
    hb.position.set(0, 0.66, -len / 2 - 0.03);
    hb.castShadow = true;
    hb.receiveShadow = true;
    g.add(hb);
    const channelCount = Math.floor(hbW / 0.16);
    for (let i = 0; i < channelCount; i++) {
        const ch = new THREE.Mesh(
            new THREE.CylinderGeometry(0.035, 0.035, 0.9, 12, 1, false, 0, Math.PI),
            M.bedFabric
        );
        ch.rotation.z = Math.PI / 2;
        ch.rotation.x = -Math.PI / 2;
        ch.position.set(-(channelCount - 1) * 0.08 + i * 0.16, 0.66, -len / 2 + 0.02);
        ch.castShadow = true;
        g.add(ch);
    }

    // mattress with a rounded, slightly compressed top
    const mat = new THREE.Mesh(roundedBoxGeometry(w, 0.26, len, 0.07), M.whiteGloss);
    mat.position.set(0, 0.51, 0);
    mat.castShadow = true;
    mat.receiveShadow = true;
    g.add(mat);

    // quilted top sheet / mattress protector band
    const sheet = new THREE.Mesh(roundedBoxGeometry(w + 0.015, 0.06, len - 0.05, 0.05), M.bedFabric);
    sheet.position.set(0, 0.62, 0);
    sheet.castShadow = true;
    g.add(sheet);

    // duvet — slightly rumpled so it isn't a perfect box
    const rng = new Rng(41);
    const duvet = new THREE.Mesh(roundedBoxGeometry(w + 0.06, 0.13, len * 0.66, 0.055), M.bedFabric);
    duvet.position.set(0.01, 0.69, len * 0.15);
    duvet.rotation.y = rng.float(-0.01, 0.01);
    duvet.castShadow = true;
    duvet.receiveShadow = true;
    g.add(duvet);

    // folded throw at the foot
    const throwM = new THREE.Mesh(roundedBoxGeometry(w + 0.08, 0.07, 0.42, 0.03), M.blanketPink);
    throwM.position.set(0, 0.72, len * 0.36);
    throwM.castShadow = true;
    g.add(throwM);

    // pillows — two, slightly compressed, one leaning
    const pillowGeo = roundedBoxGeometry(0.62, 0.14, 0.4, 0.09);
    const p1 = new THREE.Mesh(pillowGeo, M.whiteGloss);
    p1.position.set(-0.32, 0.72, -len / 2 + 0.28);
    p1.rotation.set(-0.12, 0.06, 0.02);
    p1.castShadow = true;
    g.add(p1);
    const p2 = new THREE.Mesh(pillowGeo, M.whiteGloss);
    p2.position.set(0.34, 0.71, -len / 2 + 0.3);
    p2.rotation.set(-0.16, -0.05, -0.03);
    p2.castShadow = true;
    g.add(p2);

    // a couple of small cushions
    const accent = texMaterial(retile(fabric(0x7e9a86, { weave: 8, seed: 4 }), 1 / 0.3), { roughness: 1 });
    const c1 = new THREE.Mesh(roundedBoxGeometry(0.36, 0.11, 0.36, 0.07), accent);
    c1.position.set(0.02, 0.76, -len / 2 + 0.44);
    c1.rotation.set(-0.5, 0.2, 0.05);
    c1.castShadow = true;
    g.add(c1);

    g.add(groundBlob(g, w + 0.1, len + 0.1, 0.5));
    return g;
}

export function buildNightstand(M, { w = 0.44, d = 0.4, h = 0.52 } = {}) {
    const g = new THREE.Group();
    const carcass = new THREE.Mesh(roundedBoxGeometry(w, h - 0.12, d, 0.01), M.woodWarm);
    carcass.position.set(0, 0.12 + (h - 0.12) / 2, 0);
    carcass.castShadow = true;
    carcass.receiveShadow = true;
    g.add(carcass);

    const front = new THREE.Mesh(roundedBoxGeometry(w - 0.03, (h - 0.2), 0.016, 0.005), M.woodDark);
    front.position.set(0, 0.12 + (h - 0.12) / 2, d / 2 + 0.006);
    front.castShadow = true;
    g.add(front);
    const pull = new THREE.Mesh(roundedBoxGeometry(0.1, 0.014, 0.014, 0.006), M.brass || M.metal);
    pull.position.set(0, 0.12 + (h - 0.12) / 2, d / 2 + 0.022);
    g.add(pull);

    legs(g, M, { w: w - 0.06, d: d - 0.05, h: 0.12, inset: 0.02, thick: 0.018, mat: M.metalDark, splay: 0.08 });

    g.add(groundBlob(g, w, d, 0.35));
    return g;
}

/** Bedside table lamp with a fabric drum shade. */
export function buildTableLamp(M, { color = 0xe8ddc8 } = {}) {
    const g = new THREE.Group();
    const ceramic = new THREE.MeshStandardMaterial({ color: 0xd9c7ae, roughness: 0.32 });

    const base = new THREE.Mesh(
        latheGeometry([[0, 0], [0.07, 0.002], [0.075, 0.02], [0.06, 0.1], [0.045, 0.2], [0.028, 0.24], [0.022, 0.26], [0, 0.26]], 22),
        ceramic
    );
    base.castShadow = true;
    base.receiveShadow = true;
    g.add(base);

    const shade = new THREE.Mesh(
        new THREE.CylinderGeometry(0.11, 0.135, 0.17, 26, 1, true),
        new THREE.MeshStandardMaterial({
            color,
            roughness: 0.9,
            side: THREE.DoubleSide,
            emissive: 0xffdca8,
            emissiveIntensity: 0.3,
        })
    );
    shade.position.set(0, 0.33, 0);
    g.add(shade);

    const bulb = new THREE.Mesh(new THREE.SphereGeometry(0.035, 12, 10), new THREE.MeshBasicMaterial({ color: 0xffe0b0 }));
    bulb.position.set(0, 0.31, 0);
    g.add(bulb);

    return g;
}

export function buildWardrobe(M, { w = 1.8, d = 0.62, h = 2.3 } = {}) {
    const g = new THREE.Group();
    g.name = 'wardrobe';

    const carcass = new THREE.Mesh(roundedBoxGeometry(w, h, d, 0.008), M.woodWarm);
    carcass.position.set(0, h / 2, 0);
    carcass.castShadow = true;
    carcass.receiveShadow = true;
    g.add(carcass);

    const n = 3;
    const dw = w / n;
    for (let i = 0; i < n; i++) {
        const front = new THREE.Mesh(roundedBoxGeometry(dw - 0.01, h - 0.06, 0.02, 0.005), M.woodPale);
        front.position.set(-w / 2 + dw * (i + 0.5), h / 2, d / 2 + 0.008);
        front.castShadow = true;
        front.receiveShadow = true;
        g.add(front);
        const pull = new THREE.Mesh(roundedBoxGeometry(0.016, 0.3, 0.018, 0.007), M.metal);
        pull.position.set(-w / 2 + dw * (i + 0.5) + (i === 1 ? 0 : dw / 2 - 0.07), h * 0.55, d / 2 + 0.03);
        pull.castShadow = true;
        g.add(pull);
    }
    // cornice cap
    const cap = new THREE.Mesh(roundedBoxGeometry(w + 0.04, 0.05, d + 0.04, 0.008), M.woodDark);
    cap.position.set(0, h + 0.02, 0);
    cap.castShadow = true;
    g.add(cap);

    g.add(groundBlob(g, w, d, 0.55));
    return g;
}

/* ================================================================== *
 *  BATHROOM — shower, WC, vanity, towels
 * ================================================================== */

export function buildShower(M, { w = 1.42, d = 1.36, h = 2.1 } = {}) {
    const g = new THREE.Group();
    g.name = 'shower';

    // tray with a fall towards the drain + a slim kerb
    const tray = new THREE.Mesh(roundedBoxGeometry(w, 0.07, d, 0.01), M.whiteGloss);
    tray.position.set(0, 0.035, 0);
    tray.receiveShadow = true;
    g.add(tray);

    const inner = new THREE.Mesh(roundedBoxGeometry(w - 0.1, 0.02, d - 0.1, 0.008), M.white);
    inner.position.set(0, 0.062, 0);
    g.add(inner);

    const drain = new THREE.Mesh(new THREE.CylinderGeometry(0.045, 0.045, 0.006, 18), M.chrome);
    drain.position.set(w / 2 - 0.18, 0.073, d / 2 - 0.18);
    g.add(drain);

    // glass: one fixed panel + one return, with slim profiles
    const glassMat = M.glass;
    const fixed = new THREE.Mesh(new THREE.PlaneGeometry(w - 0.02, h - 0.1), glassMat);
    fixed.position.set(0, h / 2 + 0.05, d / 2);
    g.add(fixed);

    const profile = new THREE.Mesh(roundedBoxGeometry(w, 0.035, 0.035, 0.01), M.metal);
    profile.position.set(0, h - 0.02, d / 2);
    profile.castShadow = true;
    g.add(profile);

    const wallProfile = new THREE.Mesh(roundedBoxGeometry(0.035, h - 0.1, 0.035, 0.01), M.metal);
    wallProfile.position.set(-w / 2 + 0.017, h / 2 + 0.05, d / 2);
    wallProfile.castShadow = true;
    g.add(wallProfile);

    const returnPanel = new THREE.Mesh(new THREE.PlaneGeometry(d - 0.06, h - 0.1), glassMat);
    returnPanel.position.set(w / 2 - 0.005, h / 2 + 0.05, 0.02);
    returnPanel.rotation.y = Math.PI / 2;
    g.add(returnPanel);

    // shower column, riser, head, mixer
    const col = new THREE.Mesh(new THREE.CylinderGeometry(0.014, 0.014, 1.5, 12), M.chrome);
    col.position.set(-w / 2 + 0.1, 0.9, -d / 2 + 0.09);
    col.castShadow = true;
    g.add(col);

    const head = new THREE.Mesh(
        latheGeometry([[0, 0], [0.11, 0.01], [0.115, 0.02], [0.11, 0.028], [0, 0.03]], 22),
        M.chrome
    );
    head.position.set(-w / 2 + 0.32, 2.02, -d / 2 + 0.09);
    head.rotation.z = 0.2;
    head.castShadow = true;
    g.add(head);

    const arm = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.012, 0.24, 10), M.chrome);
    arm.position.set(-w / 2 + 0.2, 2.05, -d / 2 + 0.09);
    arm.rotation.z = Math.PI / 2;
    arm.castShadow = true;
    g.add(arm);

    const mixer = new THREE.Mesh(roundedBoxGeometry(0.2, 0.07, 0.07, 0.02), M.chrome);
    mixer.position.set(-w / 2 + 0.1, 1.05, -d / 2 + 0.06);
    mixer.castShadow = true;
    g.add(mixer);

    // hand shower on a slider
    const hand = new THREE.Mesh(
        latheGeometry([[0, 0], [0.03, 0.005], [0.032, 0.14], [0.024, 0.17], [0, 0.175]], 16),
        M.chrome
    );
    hand.position.set(-w / 2 + 0.17, 1.32, -d / 2 + 0.07);
    hand.rotation.z = -0.3;
    hand.castShadow = true;
    g.add(hand);

    // shampoo bottles on a small shelf
    const shelf = new THREE.Mesh(roundedBoxGeometry(0.3, 0.02, 0.14, 0.006), M.whiteGloss);
    shelf.position.set(-w / 2 + 0.22, 1.25, -d / 2 + 0.08);
    shelf.castShadow = true;
    g.add(shelf);
    const bottleCols = [0x5ec2a8, 0xe8a15c, 0x9aa8e0];
    for (let i = 0; i < 3; i++) {
        const b = new THREE.Mesh(
            latheGeometry([[0, 0], [0.03, 0.002], [0.032, 0.12], [0.02, 0.15], [0.012, 0.17], [0, 0.172]], 14),
            new THREE.MeshStandardMaterial({ color: bottleCols[i], roughness: 0.3 })
        );
        b.position.set(-w / 2 + 0.12 + i * 0.09, 1.26, -d / 2 + 0.08);
        b.castShadow = true;
        g.add(b);
    }

    return g;
}

export function buildVanity(M, { w = 1.1, d = 0.52, h = 0.85 } = {}) {
    const g = new THREE.Group();
    g.name = 'vanity';

    const counter = new THREE.Mesh(roundedBoxGeometry(w + 0.03, 0.05, d + 0.02, 0.008), M.marbleCounter);
    counter.position.set(0, h, 0);
    counter.castShadow = true;
    counter.receiveShadow = true;
    g.add(counter);

    const unit = new THREE.Mesh(roundedBoxGeometry(w, h - 0.34, d - 0.06, 0.008), M.woodWarm);
    unit.position.set(0, 0.16 + (h - 0.34) / 2, 0.01);
    unit.castShadow = true;
    unit.receiveShadow = true;
    g.add(unit);

    for (const sx of [-1, 1]) {
        const drawer = new THREE.Mesh(roundedBoxGeometry(w / 2 - 0.02, (h - 0.36) / 2 - 0.01, 0.016, 0.005), M.woodPale);
        drawer.position.set(sx * (w / 4), 0.16 + (h - 0.34) / 4 + 0.09, d / 2 - 0.01);
        drawer.castShadow = true;
        g.add(drawer);
        const pull = new THREE.Mesh(roundedBoxGeometry(0.09, 0.012, 0.014, 0.005), M.metal);
        pull.position.set(sx * (w / 4), 0.16 + (h - 0.34) / 4 + 0.09, d / 2 + 0.006);
        g.add(pull);
    }

    // vessel basin
    const basin = new THREE.Mesh(
        latheGeometry([[0, 0], [0.15, 0.005], [0.175, 0.05], [0.18, 0.1], [0.172, 0.104], [0.165, 0.055], [0.14, 0.014], [0, 0.012]], 30),
        M.whiteGloss
    );
    basin.position.set(0, h + 0.025, 0.01);
    basin.castShadow = true;
    basin.receiveShadow = true;
    g.add(basin);

    const tap = new THREE.Mesh(
        pipeGeometry([
            new THREE.Vector3(0, h + 0.02, -d / 2 + 0.1),
            new THREE.Vector3(0, h + 0.2, -d / 2 + 0.1),
            new THREE.Vector3(0, h + 0.24, -d / 2 + 0.16),
            new THREE.Vector3(0, h + 0.2, -d / 2 + 0.2),
        ], 0.016, 12),
        M.chrome
    );
    tap.castShadow = true;
    g.add(tap);

    g.add(groundBlob(g, w, d, 0.4));
    return g;
}

export function buildToilet(M) {
    const g = new THREE.Group();
    g.name = 'toilet';

    const cistern = new THREE.Mesh(roundedBoxGeometry(0.38, 0.42, 0.19, 0.03), M.whiteGloss);
    cistern.position.set(0, 0.55, -0.28);
    cistern.castShadow = true;
    cistern.receiveShadow = true;
    g.add(cistern);

    const lid = new THREE.Mesh(roundedBoxGeometry(0.39, 0.03, 0.2, 0.012), M.whiteGloss);
    lid.position.set(0, 0.775, -0.28);
    lid.castShadow = true;
    g.add(lid);

    const flushBtn = new THREE.Mesh(roundedBoxGeometry(0.09, 0.008, 0.05, 0.004), M.chrome);
    flushBtn.position.set(0, 0.79, -0.28);
    g.add(flushBtn);

    // pedestal + bowl (tapered, not a box)
    const bowl = new THREE.Mesh(
        latheGeometry([[0, 0], [0.16, 0.02], [0.19, 0.16], [0.2, 0.36], [0.2, 0.4], [0.17, 0.4], [0.165, 0.16], [0, 0.14]], 26),
        M.whiteGloss
    );
    bowl.scale.z = 1.42;
    bowl.position.set(0, 0, 0.05);
    bowl.castShadow = true;
    bowl.receiveShadow = true;
    g.add(bowl);

    const seat = new THREE.Mesh(
        new THREE.TorusGeometry(0.16, 0.028, 8, 26),
        M.whiteGloss
    );
    seat.rotation.x = Math.PI / 2;
    seat.scale.z = 1.38;
    seat.position.set(0, 0.405, 0.05);
    seat.castShadow = true;
    g.add(seat);

    const lidClosed = new THREE.Mesh(roundedBoxGeometry(0.35, 0.02, 0.44, 0.07), M.whiteGloss);
    lidClosed.position.set(0, 0.415, 0.05);
    lidClosed.castShadow = true;
    g.add(lidClosed);

    g.add(groundBlob(g, 0.5, 0.7, 0.45));
    return g;
}

/** Wall towel rail with two towels. */
export function buildTowelRail(M, { w = 0.6 } = {}) {
    const g = new THREE.Group();
    const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.012, w, 12), M.chrome);
    bar.rotation.z = Math.PI / 2;
    bar.castShadow = true;
    g.add(bar);
    for (const sx of [-1, 1]) {
        const brk = new THREE.Mesh(new THREE.CylinderGeometry(0.014, 0.014, 0.07, 10), M.chrome);
        brk.rotation.x = Math.PI / 2;
        brk.position.set(sx * (w / 2 - 0.02), 0, -0.035);
        g.add(brk);
    }
    const cols = [0xe4ece9, 0xd8c8b4];
    for (let i = 0; i < 2; i++) {
        const towel = new THREE.Mesh(
            roundedBoxGeometry(w * 0.36, 0.44, 0.045, 0.02),
            texMaterial(retile(fabric(cols[i], { weave: 8, seed: 12 + i }), 1 / 0.3), { roughness: 1 })
        );
        towel.position.set((i - 0.5) * w * 0.42, -0.23, 0.0);
        towel.castShadow = true;
        g.add(towel);
    }
    return g;
}

/* ================================================================== *
 *  PLANTS
 * ================================================================== */

export function buildFiddleLeafPlant(M, { h = 1.5, seed = 1 } = {}) {
    const g = new THREE.Group();
    g.name = 'plant';

    const pot = new THREE.Mesh(
        latheGeometry([[0, 0], [0.16, 0], [0.19, 0.06], [0.21, 0.3], [0.225, 0.34], [0.215, 0.345], [0.2, 0.3], [0.18, 0.06], [0, 0.05]], 26),
        new THREE.MeshStandardMaterial({ color: 0xc9c0b2, roughness: 0.55 })
    );
    pot.castShadow = true;
    pot.receiveShadow = true;
    g.add(pot);

    const soil = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.19, 0.03, 22), M.soil);
    soil.position.y = 0.33;
    g.add(soil);

    const rng = new Rng(seed);
    const stems = 5;
    for (let s = 0; s < stems; s++) {
        const baseAngle = (s / stems) * TAU + rng.float(-0.4, 0.4);
        const lean = rng.float(0.05, 0.2);
        const stemH = h * rng.float(0.6, 1.0);

        const pts = [];
        for (let i = 0; i <= 5; i++) {
            const t = i / 5;
            pts.push(new THREE.Vector3(
                Math.cos(baseAngle) * lean * t * t * stemH,
                0.34 + stemH * t,
                Math.sin(baseAngle) * lean * t * t * stemH
            ));
        }
        const stem = new THREE.Mesh(pipeGeometry(pts, 0.011, 8), M.woodDark);
        stem.castShadow = true;
        g.add(stem);

        const leaves = 4 + Math.floor(rng.float(0, 3));
        for (let i = 0; i < leaves; i++) {
            const t = 0.42 + (i / leaves) * 0.56;
            const lp = new THREE.Vector3(
                Math.cos(baseAngle) * lean * t * t * stemH,
                0.34 + stemH * t,
                Math.sin(baseAngle) * lean * t * t * stemH
            );
            const leafShape = new THREE.Shape();
            leafShape.moveTo(0, 0);
            leafShape.bezierCurveTo(0.09, 0.06, 0.11, 0.2, 0, 0.3);
            leafShape.bezierCurveTo(-0.11, 0.2, -0.09, 0.06, 0, 0);
            const geo = new THREE.ShapeGeometry(leafShape, 8);
            // curl the blade so it isn't a flat card
            const pos = geo.attributes.position;
            for (let v = 0; v < pos.count; v++) {
                const px = pos.getX(v);
                const py = pos.getY(v);
                pos.setZ(v, -Math.pow(px / 0.11, 2) * 0.035 - Math.pow(py / 0.3, 2) * 0.02);
            }
            geo.computeVertexNormals();
            applyBoxUVSafe(geo, 1 / 0.3);

            const leaf = new THREE.Mesh(geo, rng.bool() ? M.leafDark : M.leafLight);
            leaf.position.copy(lp);
            leaf.rotation.set(
                rng.float(-0.7, -0.25),
                baseAngle + rng.float(-1.1, 1.1),
                rng.float(-0.5, 0.5)
            );
            leaf.scale.setScalar(rng.float(0.85, 1.25));
            leaf.castShadow = true;
            g.add(leaf);

            // petiole
            const pet = new THREE.Mesh(new THREE.CylinderGeometry(0.004, 0.004, 0.07, 6), M.woodDark);
            pet.position.copy(lp);
            pet.rotation.copy(leaf.rotation);
            pet.translateZ(0.035);
            g.add(pet);
        }
    }

    g.add(groundBlob(g, 0.5, 0.5, 0.5));
    return g;
}

export function buildSnakePlant(M, { h = 1.0, seed = 3 } = {}) {
    const g = new THREE.Group();
    const pot = new THREE.Mesh(
        latheGeometry([[0, 0], [0.13, 0], [0.15, 0.05], [0.16, 0.26], [0.172, 0.29], [0.164, 0.295], [0.152, 0.26], [0.14, 0.05], [0, 0.045]], 22),
        new THREE.MeshStandardMaterial({ color: 0x8a8378, roughness: 0.6 })
    );
    pot.castShadow = true;
    pot.receiveShadow = true;
    g.add(pot);

    const soil = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.14, 0.02, 20), M.soil);
    soil.position.y = 0.28;
    g.add(soil);

    const rng = new Rng(seed);
    for (let i = 0; i < 11; i++) {
        const a = rng.float(0, TAU);
        const r = rng.float(0, 0.07);
        const hh = h * rng.float(0.55, 1.0);
        const blade = new THREE.Mesh(
            roundedBoxGeometry(0.055, hh, 0.012, 0.006),
            rng.bool() ? M.leafDark : M.leafLight
        );
        blade.position.set(Math.cos(a) * r, 0.29 + hh / 2, Math.sin(a) * r);
        blade.rotation.set(rng.float(-0.16, 0.16), a, rng.float(-0.14, 0.14));
        blade.castShadow = true;
        g.add(blade);
    }
    g.add(groundBlob(g, 0.4, 0.4, 0.45));
    return g;
}

export function buildSmallPlant(M, { seed = 9 } = {}) {
    const g = new THREE.Group();
    const pot = new THREE.Mesh(
        latheGeometry([[0, 0], [0.07, 0], [0.082, 0.04], [0.088, 0.13], [0.094, 0.145], [0.088, 0.148], [0.08, 0.13], [0.07, 0.04], [0, 0.035]], 20),
        new THREE.MeshStandardMaterial({ color: 0xbfae97, roughness: 0.6 })
    );
    pot.castShadow = true;
    pot.receiveShadow = true;
    g.add(pot);
    const soil = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.075, 0.015, 16), M.soil);
    soil.position.y = 0.135;
    g.add(soil);

    const rng = new Rng(seed);
    for (let i = 0; i < 14; i++) {
        const a = rng.float(0, TAU);
        const r = rng.float(0, 0.06);
        const leaf = new THREE.Mesh(
            new THREE.CircleGeometry(rng.float(0.035, 0.055), 10),
            rng.bool() ? M.leafLight : M.leafDark
        );
        leaf.position.set(Math.cos(a) * r, 0.16 + rng.float(0.02, 0.11), Math.sin(a) * r);
        leaf.rotation.set(rng.float(-1.2, -0.3), a, rng.float(-0.4, 0.4));
        leaf.castShadow = true;
        g.add(leaf);
    }
    g.add(groundBlob(g, 0.22, 0.22, 0.35));
    return g;
}

function applyBoxUVSafe(geo, scale) {
    const pos = geo.attributes.position;
    const uv = new Float32Array(pos.count * 2);
    for (let i = 0; i < pos.count; i++) {
        uv[i * 2] = pos.getX(i) * scale;
        uv[i * 2 + 1] = pos.getY(i) * scale;
    }
    geo.setAttribute('uv', new THREE.BufferAttribute(uv, 2));
    return geo;
}

/* ================================================================== *
 *  WALL-MOUNTED / DECOR
 * ================================================================== */

export function buildBookshelf(M, { w = 1.4, h = 1.95, d = 0.3 } = {}) {
    const g = new THREE.Group();
    g.name = 'bookshelf';

    const sideGeo = roundedBoxGeometry(0.03, h, d, 0.005);
    for (const sx of [-1, 1]) {
        const s = new THREE.Mesh(sideGeo, M.woodWarm);
        s.position.set(sx * (w / 2 - 0.015), h / 2, 0);
        s.castShadow = true;
        s.receiveShadow = true;
        g.add(s);
    }
    const backGeo = roundedBoxGeometry(w - 0.06, h - 0.06, 0.014, 0.004);
    const back = new THREE.Mesh(backGeo, M.books);
    back.position.set(0, h / 2, -d / 2 + 0.007);
    back.receiveShadow = true;
    g.add(back);

    const shelves = Math.max(3, Math.round(h / 0.36));
    for (let i = 0; i <= shelves; i++) {
        const y = (h / shelves) * i + 0.02;
        const sh = new THREE.Mesh(roundedBoxGeometry(w - 0.06, 0.024, d - 0.02, 0.005), M.woodWarm);
        sh.position.set(0, y, 0);
        sh.castShadow = true;
        sh.receiveShadow = true;
        g.add(sh);
    }

    // fill some shelves with books + objects
    const rng = new Rng(21);
    for (let i = 0; i < shelves; i++) {
        const yBase = (h / shelves) * i + 0.032;
        const shelfH = h / shelves - 0.05;
        if (rng.bool(0.25)) continue; // one empty shelf reads as real

        let x = -w / 2 + 0.07;
        while (x < w / 2 - 0.12) {
            if (rng.bool(0.16)) {
                // a small object: box, vase or stack
                const kind = rng.int(0, 2);
                if (kind === 0) {
                    const box = new THREE.Mesh(
                        roundedBoxGeometry(rng.float(0.12, 0.2), shelfH * rng.float(0.5, 0.8), rng.float(0.14, 0.2), 0.012),
                        new THREE.MeshStandardMaterial({
                            color: [0x8a9a8f, 0xa8907a, 0x77838f][rng.int(0, 2)],
                            roughness: 0.7,
                        })
                    );
                    box.position.set(x + 0.08, yBase + shelfH * 0.3, -0.02);
                    box.castShadow = true;
                    g.add(box);
                    x += 0.22;
                } else if (kind === 1) {
                    const vase = new THREE.Mesh(
                        latheGeometry([[0, 0], [0.05, 0.005], [0.06, 0.08], [0.04, 0.16], [0.035, 0.2], [0.04, 0.21], [0, 0.21]], 18),
                        new THREE.MeshStandardMaterial({ color: 0xc4b49a, roughness: 0.5 })
                    );
                    vase.position.set(x + 0.06, yBase, 0);
                    vase.castShadow = true;
                    g.add(vase);
                    x += 0.16;
                } else {
                    const stack = new THREE.Mesh(
                        roundedBoxGeometry(0.16, 0.05, 0.2, 0.006),
                        new THREE.MeshStandardMaterial({ color: 0x6a5f52, roughness: 0.8 })
                    );
                    stack.position.set(x + 0.08, yBase + 0.026, -0.01);
                    stack.rotation.y = rng.float(-0.15, 0.15);
                    stack.castShadow = true;
                    g.add(stack);
                    x += 0.19;
                }
                continue;
            }
            // a row of books
            const runLen = rng.float(0.18, 0.42);
            const bw = runLen;
            const bh = shelfH * rng.float(0.72, 0.95);
            const run = new THREE.Mesh(
                roundedBoxGeometry(bw, bh, d - 0.09, 0.006),
                new THREE.MeshStandardMaterial({
                    color: [0x8c3b3b, 0x2f4858, 0x3d6b4a, 0xa8763e, 0x5a3d6b, 0xb0a08a][rng.int(0, 5)],
                    roughness: 0.78,
                })
            );
            run.position.set(x + bw / 2, yBase + bh / 2, -0.03);
            run.rotation.z = rng.bool(0.08) ? rng.float(-0.05, 0.05) : 0;
            run.castShadow = true;
            run.receiveShadow = true;
            g.add(run);
            x += bw + 0.012;
        }
    }

    g.add(groundBlob(g, w, d, 0.5));
    return g;
}

export function buildServerRack(M, { w = 0.9, h = 1.85, d = 0.45 } = {}) {
    const g = new THREE.Group();
    g.name = 'server-rack';

    const frameMat = M.blackMetal;
    for (const sx of [-1, 1]) {
        const post = new THREE.Mesh(roundedBoxGeometry(0.04, h, 0.04, 0.008), frameMat);
        post.position.set(sx * (w / 2 - 0.02), h / 2, -d / 2 + 0.02);
        post.castShadow = true;
        g.add(post);
    }
    const side = new THREE.Mesh(roundedBoxGeometry(0.014, h, d, 0.004), frameMat);
    for (const sx of [-1, 1]) {
        const s = side.clone();
        s.position.set(sx * (w / 2 - 0.007), h / 2, 0);
        s.castShadow = true;
        s.receiveShadow = true;
        g.add(s);
    }
    const top = new THREE.Mesh(roundedBoxGeometry(w, 0.03, d, 0.006), frameMat);
    top.position.set(0, h - 0.015, 0);
    top.castShadow = true;
    g.add(top);

    // 1U blanks + servers, with breathing LED strips
    const rng = new Rng(31);
    const uH = 0.044;
    const units = Math.floor((h - 0.12) / uH);
    for (let i = 0; i < units; i++) {
        const y = 0.08 + i * uH;
        const kind = rng.next();
        const isServer = kind > 0.35;
        const face = new THREE.Mesh(
            roundedBoxGeometry(w - 0.06, uH - 0.006, 0.02, 0.004),
            isServer ? M.metalDark : new THREE.MeshStandardMaterial({ color: 0x191c21, roughness: 0.6 })
        );
        face.position.set(0, y + uH / 2, d / 2 - 0.014);
        face.castShadow = true;
        g.add(face);

        if (isServer) {
            // vent grille
            const vent = new THREE.Mesh(roundedBoxGeometry(w * 0.42, uH - 0.014, 0.008, 0.003), M.grille);
            vent.position.set(-w * 0.12, y + uH / 2, d / 2 - 0.002);
            g.add(vent);

            const ledCount = 3 + Math.floor(rng.float(0, 4));
            const ledGroup = [];
            for (let k = 0; k < ledCount; k++) {
                const led = new THREE.Mesh(
                    new THREE.CircleGeometry(0.005, 8),
                    new THREE.MeshBasicMaterial({ color: 0x2fe08a, toneMapped: false })
                );
                led.position.set(w * 0.14 + k * 0.028, y + uH / 2, d / 2 - 0.003);
                g.add(led);
                ledGroup.push(led);
            }
            if (rng.bool(0.3)) {
                const amber = new THREE.Mesh(
                    new THREE.CircleGeometry(0.005, 8),
                    new THREE.MeshBasicMaterial({ color: 0xffb020, toneMapped: false })
                );
                amber.position.set(w * 0.32, y + uH / 2, d / 2 - 0.003);
                g.add(amber);
            }
            // stash the LEDs so app.js can animate activity
            (g.userData.leds || (g.userData.leds = [])).push(...ledGroup);
        }
    }

    // cable bundle at the back
    for (let i = 0; i < 4; i++) {
        const cable = new THREE.Mesh(
            pipeGeometry([
                new THREE.Vector3(-w / 2 + 0.1 + i * 0.08, 0.4, -d / 2 - 0.02),
                new THREE.Vector3(-w / 2 + 0.14 + i * 0.08, 0.1, -d / 2 - 0.05),
                new THREE.Vector3(-w / 2 + 0.2 + i * 0.08, 0.02, -d / 2 - 0.08),
            ], 0.008, 6),
            M.blackSoft
        );
        g.add(cable);
    }

    g.add(groundBlob(g, w, d, 0.5));
    return g;
}

export function buildACUnit(M, { w = 0.9, h = 0.3, d = 0.21 } = {}) {
    const g = new THREE.Group();
    g.name = 'ac-unit';

    const body = new THREE.Mesh(
        latheGeometry(
            [[0, 0], [0.001, 0], [0.001, 0], [0.09, 0.0], [0.12, 0.06], [0.13, 0.2], [0.13, 0.26], [0.115, 0.28], [0.001, 0.29], [0, 0.29]],
            4
        ),
        M.whiteGloss
    );
    body.scale.set(w / 0.13, h / 0.29, d / 0.13);
    body.rotation.y = Math.PI / 4;
    body.castShadow = true;
    body.receiveShadow = true;
    g.add(body);

    // outlet louvre
    const louvre = new THREE.Mesh(roundedBoxGeometry(w * 0.82, 0.03, 0.05, 0.008), M.white);
    louvre.position.set(0, -h / 2 + 0.03, d / 2 - 0.02);
    louvre.rotation.x = 0.4;
    louvre.castShadow = true;
    g.add(louvre);

    // display + status LED
    const disp = new THREE.Mesh(
        roundedBoxGeometry(0.13, 0.04, 0.006, 0.003),
        new THREE.MeshBasicMaterial({ color: 0x123040, toneMapped: false })
    );
    disp.position.set(w * 0.28, -0.01, d / 2 + 0.002);
    g.add(disp);
    const dispOn = new THREE.Mesh(
        new THREE.PlaneGeometry(0.1, 0.028),
        new THREE.MeshBasicMaterial({ color: 0x35e08a, toneMapped: false })
    );
    dispOn.position.set(w * 0.28, -0.01, d / 2 + 0.006);
    g.add(dispOn);
    const led = new THREE.Mesh(
        new THREE.CircleGeometry(0.006, 10),
        new THREE.MeshBasicMaterial({ color: 0x66ff9c, toneMapped: false })
    );
    led.position.set(w * 0.28 + 0.085, -0.01, d / 2 + 0.004);
    g.add(led);

    g.userData.screenCanvas = null;
    return g;
}

export function buildWallClock(M, { r = 0.16 } = {}) {
    const g = new THREE.Group();
    const rim = new THREE.Mesh(new THREE.TorusGeometry(r, 0.016, 10, 40), M.blackMetal);
    rim.castShadow = true;
    g.add(rim);

    const canvas = document.createElement('canvas');
    canvas.width = 256;
    canvas.height = 256;
    const tex = new THREE.CanvasTexture(canvas);
    tex.colorSpace = THREE.SRGBColorSpace;
    const face = new THREE.Mesh(
        new THREE.CircleGeometry(r - 0.012, 44),
        new THREE.MeshBasicMaterial({ map: tex, toneMapped: false })
    );
    face.position.z = 0.001;
    g.add(face);

    // glass dome
    const dome = new THREE.Mesh(
        new THREE.SphereGeometry(r + 0.004, 24, 12, 0, Math.PI * 2, 0, Math.PI / 2.4),
        new THREE.MeshPhysicalMaterial({ color: 0xffffff, roughness: 0.04, transparent: true, opacity: 0.14 })
    );
    dome.rotation.x = Math.PI / 2;
    g.add(dome);

    g.userData.canvas = canvas;
    g.userData.texture = tex;
    return g;
}

export function drawClockFace(canvas, hours, minutes, seconds) {
    const ctx = canvas.getContext('2d');
    const S = canvas.width;
    const c = S / 2;
    ctx.clearRect(0, 0, S, S);

    ctx.fillStyle = '#f7f5f0';
    ctx.beginPath();
    ctx.arc(c, c, c - 2, 0, TAU);
    ctx.fill();

    ctx.strokeStyle = '#1c1f24';
    ctx.lineWidth = 4;
    ctx.beginPath();
    ctx.arc(c, c, c - 6, 0, TAU);
    ctx.stroke();

    for (let i = 0; i < 60; i++) {
        const a = (i / 60) * TAU - Math.PI / 2;
        const major = i % 5 === 0;
        const r1 = c - (major ? 26 : 16);
        const r2 = c - 12;
        ctx.strokeStyle = major ? '#1c1f24' : '#9aa0a8';
        ctx.lineWidth = major ? 4 : 2;
        ctx.beginPath();
        ctx.moveTo(c + Math.cos(a) * r1, c + Math.sin(a) * r1);
        ctx.lineTo(c + Math.cos(a) * r2, c + Math.sin(a) * r2);
        ctx.stroke();
    }

    const hand = (angle, len, width, color, tail = 0.18) => {
        ctx.save();
        ctx.translate(c, c);
        ctx.rotate(angle);
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.moveTo(-width, width * tail);
        ctx.lineTo(-width * 0.7, -len + width);
        ctx.lineTo(0, -len);
        ctx.lineTo(width * 0.7, -len + width);
        ctx.lineTo(width, width * tail);
        ctx.closePath();
        ctx.fill();
        ctx.restore();
    };

    const hA = ((hours % 12) + minutes / 60) / 12 * TAU - Math.PI / 2;
    const mA = (minutes / 60) * TAU - Math.PI / 2;
    const sA = (seconds / 60) * TAU - Math.PI / 2;

    hand(hA, c * 0.5, 8, '#1c1f24');
    hand(mA, c * 0.72, 6, '#1c1f24');
    hand(sA, c * 0.78, 2.5, '#d94f4f', 0.3);

    ctx.fillStyle = '#1c1f24';
    ctx.beginPath();
    ctx.arc(c, c, 6, 0, TAU);
    ctx.fill();
}

/** Framed artwork on the wall — mat board, frame, glass, image. */
export function buildFrame(M, { w = 0.5, h = 0.65, draw, frameColor = 0x1c1f24, frameW = 0.03, seed = 1 } = {}) {
    const g = new THREE.Group();

    const back = new THREE.Mesh(roundedBoxGeometry(w, h, 0.022, 0.004), new THREE.MeshStandardMaterial({ color: frameColor, roughness: 0.45 }));
    back.castShadow = true;
    back.receiveShadow = true;
    g.add(back);

    const matBoard = new THREE.Mesh(
        new THREE.PlaneGeometry(w - frameW * 2, h - frameW * 2),
        new THREE.MeshStandardMaterial({ color: 0xf6f4ef, roughness: 0.92 })
    );
    matBoard.position.z = 0.012;
    g.add(matBoard);

    const tex = artworkCanvas(draw, 512, Math.round((512 * (h - frameW * 2)) / (w - frameW * 2)));
    const art = new THREE.Mesh(
        new THREE.PlaneGeometry(w - frameW * 2 - 0.07, h - frameW * 2 - 0.07),
        new THREE.MeshBasicMaterial({ map: tex, toneMapped: false })
    );
    art.position.z = 0.0135;
    g.add(art);

    return g;
}

export function drawPoster(ctx, w, h, { title, sub, bg, fg, accent }) {
    const g = ctx.createLinearGradient(0, 0, 0, h);
    g.addColorStop(0, bg[0]);
    g.addColorStop(1, bg[1]);
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, w, h);

    // graphic block
    ctx.fillStyle = accent;
    ctx.globalAlpha = 0.9;
    ctx.beginPath();
    ctx.arc(w * 0.72, h * 0.3, w * 0.26, 0, TAU);
    ctx.fill();
    ctx.globalAlpha = 0.35;
    ctx.beginPath();
    ctx.arc(w * 0.3, h * 0.72, w * 0.3, 0, TAU);
    ctx.fill();
    ctx.globalAlpha = 1;

    ctx.fillStyle = fg;
    ctx.textAlign = 'center';
    ctx.font = `bold ${Math.round(w * 0.13)}px "Helvetica Neue", Arial, sans-serif`;
    ctx.fillText(title, w / 2, h * 0.52);
    ctx.font = `${Math.round(w * 0.055)}px "Helvetica Neue", Arial, sans-serif`;
    ctx.globalAlpha = 0.8;
    ctx.fillText(sub, w / 2, h * 0.62);
    ctx.globalAlpha = 1;
    ctx.fillRect(w * 0.3, h * 0.68, w * 0.4, 3);
}

/* ================================================================== *
 *  ENTRY
 * ================================================================== */

export function buildConsoleTable(M, { w = 1.4, d = 0.42, h = 0.8 } = {}) {
    const g = new THREE.Group();
    const top = new THREE.Mesh(roundedBoxGeometry(w, 0.035, d, 0.008), M.woodWarm);
    top.position.set(0, h, 0);
    top.castShadow = true;
    top.receiveShadow = true;
    g.add(top);

    const shelf = new THREE.Mesh(roundedBoxGeometry(w - 0.16, 0.022, d - 0.1, 0.006), M.woodDark);
    shelf.position.set(0, 0.24, 0);
    shelf.castShadow = true;
    shelf.receiveShadow = true;
    g.add(shelf);

    legs(g, M, { w: w - 0.1, d: d - 0.06, h: h - 0.017, inset: 0.02, thick: 0.026, mat: M.metalDark, splay: 0.04 });

    g.add(groundBlob(g, w, d, 0.4));
    return g;
}

export function buildCoatRack(M, { w = 1.0, h = 1.75 } = {}) {
    const g = new THREE.Group();
    const rail = new THREE.Mesh(new THREE.CylinderGeometry(0.016, 0.016, w, 14), M.woodWarm);
    rail.rotation.z = Math.PI / 2;
    rail.position.set(0, h - 0.06, 0);
    rail.castShadow = true;
    g.add(rail);

    const base = new THREE.Mesh(roundedBoxGeometry(w, 0.05, 0.3, 0.012), M.woodWarm);
    base.position.set(0, 0.03, 0);
    base.castShadow = true;
    base.receiveShadow = true;
    g.add(base);

    for (const sx of [-1, 1]) {
        const post = new THREE.Mesh(new THREE.CylinderGeometry(0.018, 0.022, h - 0.08, 12), M.woodWarm);
        post.position.set(sx * (w / 2 - 0.05), h / 2, 0);
        post.castShadow = true;
        g.add(post);
    }

    // hooks
    const hookMat = M.blackMetal;
    const coatCols = [0x6c7f8c, 0x8a6f5c, 0x4f5d52];
    for (let i = 0; i < 5; i++) {
        const x = -w / 2 + 0.12 + i * ((w - 0.24) / 4);
        const hook = new THREE.Mesh(
            pipeGeometry([
                new THREE.Vector3(x, h - 0.06, 0),
                new THREE.Vector3(x, h - 0.02, 0.03),
                new THREE.Vector3(x, h - 0.08, 0.06),
            ], 0.006, 6),
            hookMat
        );
        hook.castShadow = true;
        g.add(hook);

        if (i % 2 === 0) {
            // a coat / tote bag hanging
            const bag = new THREE.Mesh(
                roundedBoxGeometry(0.26, 0.5, 0.1, 0.05),
                new THREE.MeshStandardMaterial({
                    color: coatCols[(i / 2) % coatCols.length],
                    roughness: 0.85,
                })
            );
            bag.position.set(x, h - 0.38, 0.05);
            bag.rotation.y = 0.1;
            bag.castShadow = true;
            g.add(bag);
        }
    }

    g.add(groundBlob(g, w, 0.4, 0.45));
    return g;
}

export function buildShoeRack(M, { w = 0.9, d = 0.32, h = 0.5 } = {}) {
    const g = new THREE.Group();
    const top = new THREE.Mesh(roundedBoxGeometry(w, 0.03, d, 0.006), M.woodPale);
    top.position.set(0, h, 0);
    top.castShadow = true;
    top.receiveShadow = true;
    g.add(top);

    const board2 = new THREE.Mesh(roundedBoxGeometry(w, 0.026, d - 0.02, 0.006), M.woodPale);
    board2.position.set(0, h * 0.42, 0);
    board2.castShadow = true;
    g.add(board2);

    legs(g, M, { w: w - 0.06, d: d - 0.04, h, inset: 0.02, thick: 0.022, mat: M.metalDark, splay: 0.06 });

    // shoes
    const shoeCols = [0x2b2f36, 0x8a6a4a, 0xe8e4dc, 0x3d5568, 0x6a4a3a];
    const rng = new Rng(55);
    for (let level = 0; level < 2; level++) {
        const y = level === 0 ? h + 0.016 : h * 0.42 + 0.014;
        for (let i = 0; i < 3; i++) {
            const shoe = new THREE.Group();
            const sole = new THREE.Mesh(
                roundedBoxGeometry(0.1, 0.028, 0.26, 0.02),
                new THREE.MeshStandardMaterial({ color: 0xf0ece4, roughness: 0.8 })
            );
            shoe.add(sole);
            const upper = new THREE.Mesh(
                roundedBoxGeometry(0.095, 0.075, 0.14, 0.035),
                new THREE.MeshStandardMaterial({ color: shoeCols[rng.int(0, 4)], roughness: 0.72 })
            );
            upper.position.set(0, 0.05, -0.045);
            upper.castShadow = true;
            shoe.add(upper);
            shoe.position.set(-w / 2 + 0.2 + i * 0.24, y, rng.float(-0.02, 0.02));
            shoe.rotation.y = rng.float(-0.2, 0.2);
            shoe.castShadow = true;
            g.add(shoe);
        }
    }
    g.add(groundBlob(g, w, d, 0.4));
    return g;
}

/* ================================================================== *
 *  Rugs
 * ================================================================== */

export function buildRug(M, { w = 3.2, d = 2.4, color = 0x9a8c7a, seed = 2, thickness = 0.014 } = {}) {
    const tex = carpet(color);
    const mat = texMaterial(retile(tex, w / 1.2, d / 1.2), { roughness: 1 });
    const geo = roundedBoxGeometry(w, thickness, d, thickness * 0.45);
    const m = new THREE.Mesh(geo, mat);
    m.position.y = thickness / 2 + 0.001;
    m.receiveShadow = true;
    m.castShadow = false;

    // flat-ish border strip so the edge reads as a bound rug
    const borderMat = texMaterial(retile(carpet(shadeHexInt(color, -0.25)), w / 1.2, d / 1.2), { roughness: 1 });
    const border = new THREE.Mesh(
        roundedBoxGeometry(w - 0.16, thickness + 0.001, d - 0.16, thickness * 0.45),
        borderMat
    );
    border.position.y = thickness / 2 + 0.0022;
    border.receiveShadow = true;

    const g = new THREE.Group();
    g.add(m);
    g.add(border);
    return g;
}

function shadeHexInt(hex, amt) {
    const f = (v) => Math.round(amt > 0 ? lerp(v, 255, amt) : lerp(v, 0, -amt));
    return (f((hex >> 16) & 255) << 16) | (f((hex >> 8) & 255) << 8) | f(hex & 255);
}

/* ================================================================== *
 *  Curtains
 * ================================================================== */

export function buildCurtains(M, { w = 2.6, h = 1.9, side = 1 } = {}) {
    const g = new THREE.Group();
    const rod = new THREE.Mesh(new THREE.CylinderGeometry(0.014, 0.014, w + 0.5, 12), M.blackMetal);
    rod.rotation.z = Math.PI / 2;
    rod.castShadow = true;
    g.add(rod);
    for (const sx of [-1, 1]) {
        const finial = new THREE.Mesh(new THREE.SphereGeometry(0.026, 12, 10), M.blackMetal);
        finial.position.set(sx * (w / 2 + 0.24), 0, 0);
        finial.castShadow = true;
        g.add(finial);
    }

    // two panels, gathered — modelled as vertical folds so they catch light
    const panelW = w * 0.32;
    for (const sx of [-1, 1]) {
        const folds = 7;
        for (let i = 0; i < folds; i++) {
            const t = i / (folds - 1);
            const fold = new THREE.Mesh(
                new THREE.CylinderGeometry(0.055, 0.06, h, 8, 1, false, 0, Math.PI),
                M.curtain
            );
            fold.rotation.y = Math.PI / 2;
            fold.position.set(
                sx * (w / 2 - 0.12) - sx * t * panelW,
                -h / 2,
                0.02
            );
            fold.castShadow = true;
            fold.receiveShadow = true;
            g.add(fold);
        }
    }
    return g;
}

export { legs, groundBlob, panel };