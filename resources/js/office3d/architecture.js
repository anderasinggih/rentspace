import * as THREE from 'three';
import {
    roundedBoxGeometry, roundedRectShape, wallWithOpeningsGeometry, applyBoxUV,
    mesh, setShadowRecursive, Rng, lerp, clamp, TAU, smoothstep, contactShadow,
} from './lib.js';
import { P, WALLS, FLOORS } from './plan.js';
import {
    woodFloor, tileFloor, tileSmall, tileLarge, wallTile, wallPaint, marble,
    concrete, woodVeneer, citySkyline, frostedGlass, texMaterial, retile,
    artworkCanvas, bookSpines, perforated, fabric, brushedMetal, leaf,
} from './textures.js';

/* ------------------------------------------------------------------ *
 *  Shared material library.  Built once, reused everywhere, so the
 *  whole flat fits in a handful of programs.
 * ------------------------------------------------------------------ */

export function createMaterials() {
    const M = {};

    /* --- floors: one material per finish, UV-tiled by real size --- */
    const floorUV = (texKey, texSet, tileMeters, extra = {}) => {
        const t = retile(texSet, 1 / tileMeters);
        return texMaterial(t, { roughness: 1, metalness: 0, ...extra });
    };

    M.floorWood = floorUV('oak', woodFloor({ base: 0x8a5f38, dark: 0x452a17, light: 0xbb8b57, varnish: 0.5, planksPerSide: 4 }), 2.4);
    M.floorWoodDark = floorUV('walnut', woodFloor({ base: 0x5e3d26, dark: 0x2e1b10, light: 0x8b6238, varnish: 0.42, planksPerSide: 4, seed: 33 }), 2.4);
    M.floorTile = floorUV('tile', tileFloor({ base: 0xd6d2cb, gloss: 0.45 }), 2.0);
    M.floorTileSmall = floorUV('tilesmall', tileSmall({ base: 0xd0cbc2 }), 1.2);
    M.floorTileLarge = floorUV('tilelarge', tileLarge({ base: 0xbdb6ab }), 1.8);

    /* --- walls --- */
    const wallTex = wallPaint(0xe9e3d7);
    M.wall = texMaterial(retile(wallTex, 1 / 2.6), { roughness: 1 });
    M.wallWarm = texMaterial(retile(wallPaint(0xd9cbb6), 1 / 2.6), { roughness: 1 });
    M.wallCool = texMaterial(retile(wallPaint(0xd7dbdd), 1 / 2.6), { roughness: 1 });
    M.wallBedroom = texMaterial(retile(wallPaint(0xcfd4d8), 1 / 2.6), { roughness: 1 });

    M.wallTile = texMaterial(retile(wallTile({ base: 0xe8ebec }), 1 / 1.35), {
        roughness: 1,
        metalness: 0.02,
    });

    M.ceiling = texMaterial(retile(wallPaint(0xf6f4f0, { seed: 99 }), 1 / 3.0), { roughness: 0.96 });
    M.concrete = texMaterial(retile(concrete(0xb5b1a9), 1 / 2.0), { roughness: 1 });
    M.marbleCounter = texMaterial(retile(marble(0xe9e7e2, 0x9aa0a8), 1 / 1.4), {
        roughness: 0.16,
        metalness: 0.02,
    });

    /* --- wood furniture --- */
    M.woodWarm = texMaterial(retile(woodVeneer(0x6d472f), 1 / 1.1), { roughness: 0.52 });
    M.woodDark = texMaterial(retile(woodVeneer(0x4a2f1f), 1 / 1.0), { roughness: 0.46 });
    M.woodPale = texMaterial(retile(woodVeneer(0xa98865, { seed: 5 }), 1 / 1.0), { roughness: 0.5 });
    M.woodDoor = texMaterial(retile(woodVeneer(0x6a5340), 1 / 1.5), { roughness: 0.42 });
    M.woodDoorPaint = texMaterial(retile(wallPaint(0xf2f0ea), 1 / 1.5), { roughness: 0.38 });

    /* --- metals --- */
    M.metal = texMaterial(retile(brushedMetal({ tint: 0xc8ccd2 }), 1 / 0.6), {
        roughness: 1,
        metalness: 0.92,
    });
    M.metalDark = texMaterial(retile(brushedMetal({ tint: 0x4a4e55 }), 1 / 0.5), {
        roughness: 1,
        metalness: 0.85,
    });
    M.chrome = new THREE.MeshStandardMaterial({ color: 0xdfe3e8, roughness: 0.09, metalness: 1.0 });
    M.steel = new THREE.MeshStandardMaterial({ color: 0x9aa1a9, roughness: 0.32, metalness: 0.9 });
    M.blackMetal = new THREE.MeshStandardMaterial({ color: 0x1c1f24, roughness: 0.42, metalness: 0.7 });

    /* --- plastics / paint --- */
    M.white = new THREE.MeshStandardMaterial({ color: 0xf4f5f6, roughness: 0.42 });
    M.whiteGloss = new THREE.MeshStandardMaterial({ color: 0xfafbfb, roughness: 0.16 });
    M.cream = new THREE.MeshStandardMaterial({ color: 0xf2ece1, roughness: 0.55 });
    M.charcoal = new THREE.MeshStandardMaterial({ color: 0x23262b, roughness: 0.55 });
    M.blackSoft = new THREE.MeshStandardMaterial({ color: 0x14161a, roughness: 0.8 });

    /* --- fabrics --- */
    M.sofaFabric = texMaterial(retile(fabric(0x8d949c, { weave: 6 }), 1 / 0.35), { roughness: 1 });
    M.bedFabric = texMaterial(retile(fabric(0xe8e2d8, { weave: 7 }), 1 / 0.3), { roughness: 1 });
    M.blanketPink = texMaterial(retile(fabric(0xd98fa8, { weave: 8 }), 1 / 0.32), { roughness: 1 });
    M.curtain = texMaterial(retile(fabric(0xc9b9a4, { weave: 9 }), 1 / 0.28), {
        roughness: 1,
        side: THREE.DoubleSide,
    });

    /* --- books --- */
    M.books = texMaterial(retile(bookSpines(), 1 / 1.0), { roughness: 1 });

    /* --- glass --- */
    M.glass = new THREE.MeshPhysicalMaterial({
        color: 0xdfeaf0,
        roughness: 0.03,
        metalness: 0,
        transparent: true,
        opacity: 0.16,
        transmission: 0,
        side: THREE.DoubleSide,
        depthWrite: false,
    });
    M.glassFrosted = new THREE.MeshStandardMaterial({
        color: 0xe9eff2,
        roughness: 0.62,
        metalness: 0,
        transparent: true,
        opacity: 0.86,
        side: THREE.DoubleSide,
    });

    /* --- greenery --- */
    M.leafDark = texMaterial(retile(leaf({ color: 0x2f6b3a, color2: 0x123f1d }), 1 / 0.25), {
        roughness: 0.72,
        side: THREE.DoubleSide,
    });
    M.leafLight = texMaterial(retile(leaf({ color: 0x4e8c46, color2: 0x245c28, seed: 7 }), 1 / 0.25), {
        roughness: 0.7,
        side: THREE.DoubleSide,
    });
    M.soil = new THREE.MeshStandardMaterial({ color: 0x2f2620, roughness: 1 });

    /* --- misc --- */
    M.grille = texMaterial(retile(perforated({ pitch: 9, holeR: 2.8 }), 1 / 0.35), {
        roughness: 1,
        metalness: 0.3,
    });
    M.matteBlack = new THREE.MeshStandardMaterial({ color: 0x0e1013, roughness: 0.85 });
    M.paperWhite = new THREE.MeshStandardMaterial({ color: 0xf7f6f3, roughness: 0.92 });

    return M;
}

/* ------------------------------------------------------------------ *
 *  Shell
 * ------------------------------------------------------------------ */

export function buildShell(scene, M, opts = {}) {
    const root = new THREE.Group();
    root.name = 'shell';

    buildFloors(root, M);
    buildCeiling(root, M);
    buildWalls(root, M);
    buildTrims(root, M);
    buildSkybox(root);

    scene.add(root);
    return root;
}

/* ---------------- floors ---------------- */

function buildFloors(root, M) {
    const matFor = {
        wood: M.floorWood,
        tile: M.floorTile,
        tileSmall: M.floorTileSmall,
        tileLarge: M.floorTileLarge,
    };

    for (const f of FLOORS) {
        const w = f.rect.maxX - f.rect.minX;
        const d = f.rect.maxZ - f.rect.minZ;
        const geo = new THREE.PlaneGeometry(w, d, Math.ceil(w * 2), Math.ceil(d * 2));
        geo.rotateX(-Math.PI / 2);
        // Re-tile UVs by world size so texel density is uniform per room.
        const base = matFor[f.kind];
        const uv = geo.attributes.uv;
        const baseRepeat = base.map ? base.map.repeat.x : 1;
        for (let i = 0; i < uv.count; i++) {
            uv.setXY(i, uv.getX(i) * w * baseRepeat, uv.getY(i) * d * baseRepeat);
        }
        const m = new THREE.Mesh(geo, base);
        m.position.set((f.rect.minX + f.rect.maxX) / 2, 0, (f.rect.minZ + f.rect.maxZ) / 2);
        m.receiveShadow = true;
        m.name = 'floor-' + f.kind;
        root.add(m);
    }
}

/* ---------------- ceiling ---------------- */

function buildCeiling(root, M) {
    const w = P.outer.maxX - P.outer.minX;
    const d = P.outer.maxZ - P.outer.minZ;
    const geo = new THREE.PlaneGeometry(w, d, 20, 16);
    geo.rotateX(Math.PI / 2);
    const uv = geo.attributes.uv;
    const r = M.ceiling.map ? M.ceiling.map.repeat.x : 1;
    for (let i = 0; i < uv.count; i++) {
        uv.setXY(i, uv.getX(i) * w * r, uv.getY(i) * d * r);
    }
    const ceil = new THREE.Mesh(geo, M.ceiling);
    ceil.position.set(0, P.ceilH, 0);
    ceil.receiveShadow = true;
    root.add(ceil);

    // shallow coffer above the office so the ceiling isn't a dead plane
    const cofferMat = M.ceiling;
    const coffer = new THREE.Mesh(roundedBoxGeometry(6.2, 0.09, 8.4, 0.02), cofferMat);
    coffer.position.set(-1.2, P.ceilH - 0.045, 0.4);
    coffer.receiveShadow = true;
    coffer.castShadow = true;
    root.add(coffer);

    // recessed LED strip inside the coffer — gives the office real light shape
    const stripMat = new THREE.MeshBasicMaterial({ color: 0xfff2dc });
    for (const z of [-3.6, 4.4]) {
        const strip = new THREE.Mesh(new THREE.BoxGeometry(5.6, 0.012, 0.05), stripMat);
        strip.position.set(-1.2, P.ceilH - 0.1, z);
        root.add(strip);
    }
}

/* ---------------- walls ---------------- */

function buildWalls(root, M) {
    const wallGroup = new THREE.Group();
    wallGroup.name = 'walls';

    for (const w of WALLS) {
        const width = w.to - w.from;
        const len = Math.abs(width);
        const centre = (w.from + w.to) / 2;

        // open/door/window specs -> wall-local openings
        const openings = (w.openings || []).map((o) => ({
            x: o.cx,
            y: o.sill,
            w: o.w,
            h: o.h,
            r: o.kind === 'window' && o.h > 1.0 ? 0.12 : 0,
            kind: o.kind,
        }));

        const geo = wallWithOpeningsGeometry({
            width: len,
            height: w.h,
            thickness: w.t,
            openings,
            uvScale: 1 / 2.6,
        });

        const m = new THREE.Mesh(geo, pickWallMaterial(M, w));
        m.castShadow = true;
        m.receiveShadow = true;

        if (w.axis === 'x') {
            m.position.set(centre, 0, w.at);
            m.rotation.y = 0;
        } else {
            m.position.set(w.at, 0, centre);
            m.rotation.y = Math.PI / 2;
        }
        m.name = 'wall-' + w.axis + '-' + w.at.toFixed(2);
        wallGroup.add(m);

        // interior wall faces get a tile wainscot in wet rooms
        if (w.axis === 'x' && Math.abs(w.at - P.partBathS) < 0.01) {
            const tile = new THREE.Mesh(new THREE.PlaneGeometry(len, 2.1), M.wallTile);
            tile.rotation.x = -Math.PI / 2;
            tile.position.set(centre, 1.05, w.at - w.t / 2 - 0.002);
            tile.rotation.z = Math.PI;
            tile.receiveShadow = true;
            wallGroup.add(tile);
            const tile2 = tile.clone();
            tile2.position.z = w.at + w.t / 2 + 0.002;
            tile2.rotation.z = 0;
            wallGroup.add(tile2);
        }
    }

    root.add(wallGroup);
    return wallGroup;
}

function pickWallMaterial(M, w) {
    const at = w.at;
    const mid = (w.from + w.to) / 2;
    // bathroom interior surfaces
    if (Math.abs(at - P.partBathN) < 0.01 || Math.abs(at - P.partBathS) < 0.01) {
        return M.wallTile;
    }
    if (Math.abs(at - P.outer.minX) < 0.01 && mid < -0.5 && mid > -6) {
        return mid > 2.3 ? M.wallCool : M.wallBedroom;
    }
    if (Math.abs(at - P.outer.maxX) < 0.01) return M.wallCool;
    if (Math.abs(at - P.outer.minZ) < 0.01) return M.wall;
    if (Math.abs(at - P.outer.maxZ) < 0.01) return M.wallWarm;
    return M.wall;
}

/* ---------------- baseboard + cornice ---------------- */

function buildTrims(root, M) {
    const trimGroup = new THREE.Group();
    trimGroup.name = 'trims';

    const baseMat = M.woodPale;
    const corniceMat = M.white;

    const addBaseRun = (axis, at, from, to, faceSign) => {
        // one run of skirting between two points
        const len = Math.abs(to - from);
        if (len < 0.05) return;
        const geo = roundedBoxGeometry(axis === 'x' ? len : P.baseboardT, P.baseboardH, axis === 'x' ? P.baseboardT : len, 0.006);
        const m = new THREE.Mesh(geo, baseMat);
        m.castShadow = false;
        m.receiveShadow = true;
        const c = (from + to) / 2;
        if (axis === 'x') {
            m.position.set(c, P.baseboardH / 2, at + faceSign * (P.wallExt / 2 + P.baseboardT / 2));
        } else {
            m.position.set(at + faceSign * (P.wallInt / 2 + P.baseboardT / 2), P.baseboardH / 2, c);
        }
        trimGroup.add(m);
    };

    const addCorniceRun = (axis, at, from, to, faceSign, thick) => {
        const len = Math.abs(to - from);
        if (len < 0.05) return;
        const geo = roundedBoxGeometry(
            axis === 'x' ? len : 0.05,
            P.corniceH,
            axis === 'x' ? 0.05 : len,
            0.012
        );
        const m = new THREE.Mesh(geo, corniceMat);
        m.receiveShadow = true;
        const c = (from + to) / 2;
        if (axis === 'x') m.position.set(c, P.ceilH - P.corniceH / 2, at + faceSign * (thick / 2 + 0.025));
        else m.position.set(at + faceSign * (thick / 2 + 0.025), P.ceilH - P.corniceH / 2, c);
        trimGroup.add(m);
    };

    // Walk each wall and emit baseboard/cornice runs, skipping door openings
    // so the skirting doesn't run straight through a doorway.
    for (const w of WALLS) {
        const runs = segmentsExcludingOpenings(w.from, w.to, w.openings || [], w.axis);
        // which faces to treat as "interior" (only skirt inside the building)
        const faces = interiorFaces(w);

        for (const face of faces) {
            const thick = w.t;
            for (const [a, b] of runs) {
                // exterior walls: skirt on both sides only where it is inside
                addBaseRun(w.axis, w.at, a, b, face);
            }
            addCorniceRun(w.axis, w.at, w.from, w.to, face, thick);
        }
    }

    root.add(trimGroup);
    return trimGroup;
}

function interiorFaces(w) {
    // returns array of face signs (-1 or +1) that are inside the apartment
    const eps = 0.001;
    if (Math.abs(w.at - P.outer.minZ) < eps) return [+1];
    if (Math.abs(w.at - P.outer.maxZ) < eps) return [-1];
    if (Math.abs(w.at - P.outer.minX) < eps) return [+1];
    if (Math.abs(w.at - P.outer.maxX) < eps) return [-1];
    // interior partitions: skirt both sides (hall on one, room on other)
    return [-1, +1];
}

/**
 * Break [from,to] into sub-runs that avoid opening footprints, so trims
 * stop at door jambs and window sills.
 */
function segmentsExcludingOpenings(from, to, openings, axis) {
    const sorted = [...openings]
        .map((o) => ({ a: o.cx - o.w / 2, b: o.cx + o.w / 2 }))
        .sort((p, q) => p.a - q.a);
    const runs = [];
    let cursor = from;
    for (const o of sorted) {
        if (o.b <= cursor || o.a >= to) continue;
        if (o.a > cursor) runs.push([cursor, o.a]);
        cursor = Math.max(cursor, o.b);
    }
    if (cursor < to) runs.push([cursor, to]);
    return runs;
}

/* ---------------- skybox / exterior ---------------- */

function buildSkybox(root) {
    const tex = citySkyline();
    const mat = new THREE.MeshBasicMaterial({ map: tex, side: THREE.BackSide, fog: false, toneMapped: true });
    const sky = new THREE.Mesh(new THREE.CylinderGeometry(34, 34, 26, 48, 1, true), mat);
    sky.position.y = 4;
    root.add(sky);

    // ground plane far below to avoid a void when looking out
    const groundMat = new THREE.MeshBasicMaterial({ color: 0x8e877c, fog: false });
    const ground = new THREE.Mesh(new THREE.CircleGeometry(34, 40), groundMat);
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -6;
    root.add(ground);
}

/* ------------------------------------------------------------------ *
 *  DOORS — the real deal.
 *
 *  Leaf on a hinge pivot, moulded panels, architrave casing both sides,
 *  lever handle + rose, three hinges, threshold strip.  The bathroom
 *  door used to be missing entirely, which is why the bathroom looked
 *  like an open box; it now has a proper inward-opening door.
 * ------------------------------------------------------------------ */

export function buildDoor(M, spec) {
    const {
        id, axis, at, cx, w, h, hingeSide = 1, swing = -1,
        style = 'panel',             // 'panel' | 'flush' | 'glass'
        color = 0xf2f0ea,
        open = false,
        label = '',
    } = spec;

    const isZ = axis === 'z';
    const wallT = spec.wallT || P.wallInt;

    /* --- frame-of-reference unit vectors -------------------------------
     * isZ : wall normal lies along X, the opening spans Z
     * else: wall normal lies along Z, the opening spans X
     * ---------------------------------------------------------------- */
    const runX = isZ ? 0 : 1;   // +1 if the opening runs along X
    const runZ = isZ ? 1 : 0;
    const nrmX = isZ ? 1 : 0;
    const nrmZ = isZ ? 0 : 1;

    // centre of the opening, in world space
    const openCx = isZ ? at : cx;
    const openCz = isZ ? cx : at;

    // hinge sits at one end of the opening
    const hingeX = openCx + runX * hingeSide * (w / 2);
    const hingeZ = openCz + runZ * hingeSide * (w / 2);

    const group = new THREE.Group();
    group.name = 'door-' + (id || 'door');

    const linerMat = M.woodDoorPaint;
    const frameDepth = wallT + 0.006;
    const frameW = P.frameW;

    /** Box whose long dimension follows `run`, thickness follows `normal`. */
    const frameBox = (runLen, height, runThick, mat) => {
        const geo = new THREE.BoxGeometry(
            runX ? runThick : runLen,
            height,
            runZ ? runThick : runLen
        );
        const m = new THREE.Mesh(geo, mat);
        m.castShadow = true;
        m.receiveShadow = true;
        return m;
    };
    const put = (m, runOff, nrmOff, y) => {
        m.position.set(
            openCx + runX * runOff + nrmX * nrmOff,
            y,
            openCz + runZ * runOff + nrmZ * nrmOff
        );
        group.add(m);
        return m;
    };

    /* ---- reveal lining: two jambs + head, so you never see raw plaster -- */
    for (const side of [-1, +1]) {
        const off = side * (w / 2 + frameW / 2);
        put(frameBox(frameW, h, frameDepth, linerMat), off, 0, h / 2);
    }
    put(frameBox(w + frameW * 2, frameW, frameDepth, linerMat), 0, 0, h + frameW / 2);

    /* ---- architrave / casing, proud of both wall faces ---- */
    const casingMat = M.woodDoorPaint;
    const casingW = 0.062;
    const casingT = 0.014;
    for (const face of [-1, +1]) {
        const nOff = face * (wallT / 2 + casingT / 2);
        for (const side of [-1, +1]) {
            const off = (side * (w + casingW)) / 2;
            put(frameBox(casingW, h + casingW, casingT, casingMat), off, nOff, (h + casingW) / 2);
        }
        put(frameBox(w + casingW * 2, casingW, casingT, casingMat), 0, nOff, h + casingW / 2);
    }

    /* ---- threshold strip ---- */
    const th = new THREE.Mesh(
        new THREE.BoxGeometry(runX ? wallT : w, 0.014, runZ ? wallT : w),
        M.woodPale
    );
    th.position.set(openCx, 0.007, openCz);
    th.receiveShadow = true;
    group.add(th);

    /* ---- the leaf, on its hinge pivot ---- */
    const pivot = new THREE.Group();
    pivot.name = 'door-pivot-' + (id || 'door');
    pivot.position.set(hingeX, 0, hingeZ);
    group.add(pivot);

    const leafW = w - 0.01;
    const leafH = h - 0.012;
    const leafT = 0.042;

    // geometry is authored along +X starting at the hinge (x = 0)
    const leafGroup = new THREE.Group();

    if (style === 'glass') {
        const frameMat = M.metalDark;
        const railT = 0.062;
        const addRail = (x, y, rw, rh) => {
            const m = new THREE.Mesh(roundedBoxGeometry(rw, rh, leafT, 0.008), frameMat);
            m.position.set(x, y, 0);
            m.castShadow = true;
            m.receiveShadow = true;
            leafGroup.add(m);
        };
        addRail(leafW / 2, railT / 2, leafW, railT);
        addRail(leafW / 2, leafH - railT / 2, leafW, railT);
        addRail(railT / 2, leafH / 2, railT, leafH);
        addRail(leafW - railT / 2, leafH / 2, railT, leafH);
        addRail(leafW / 2, leafH * 0.4, leafW, 0.05);

        const glass = new THREE.Mesh(
            new THREE.PlaneGeometry(leafW - railT * 2, leafH - railT * 2),
            M.glassFrosted
        );
        glass.position.set(leafW / 2, leafH / 2, 0);
        leafGroup.add(glass);
    } else {
        const slabMat = new THREE.MeshStandardMaterial({ color, roughness: 0.4, metalness: 0 });
        const slab = new THREE.Mesh(roundedBoxGeometry(leafW, leafH, leafT, 0.006), slabMat);
        slab.position.set(leafW / 2, leafH / 2, 0);
        slab.castShadow = true;
        slab.receiveShadow = true;
        leafGroup.add(slab);

        if (style === 'panel') {
            /* Recessed shaker panels, built as slightly thinner plates that
             * sit inside the slab so the stiles/rails read as proud. */
            const panelMat = new THREE.MeshStandardMaterial({
                color: shadeDown(color),
                roughness: 0.48,
            });
            const inset = leafT - 0.016;
            const stile = 0.115;
            const mkPanel = (x0, x1, y0, y1) => {
                const m = new THREE.Mesh(
                    roundedBoxGeometry(x1 - x0, y1 - y0, inset, 0.012),
                    panelMat
                );
                m.position.set((x0 + x1) / 2, (y0 + y1) / 2, 0);
                m.receiveShadow = true;
                leafGroup.add(m);
            };
            mkPanel(stile, leafW - stile, stile, leafH * 0.44);
            mkPanel(stile, leafW - stile, leafH * 0.54, leafH - stile);
        }
    }

    /* ---- lever handle + rose, both sides ---- */
    const roseGeo = new THREE.CylinderGeometry(0.028, 0.028, 0.012, 20);
    const leverGeo = roundedBoxGeometry(0.019, 0.019, 0.115, 0.008);
    for (const side of [-1, +1]) {
        const rose = new THREE.Mesh(roseGeo, M.chrome);
        rose.rotation.x = Math.PI / 2;
        rose.position.set(leafW - 0.075, 1.04, side * (leafT / 2 + 0.006));
        rose.castShadow = true;
        leafGroup.add(rose);

        const spindle = new THREE.Mesh(new THREE.CylinderGeometry(0.009, 0.009, 0.024, 10), M.chrome);
        spindle.rotation.x = Math.PI / 2;
        spindle.position.set(leafW - 0.075, 1.04, side * (leafT / 2 + 0.018));
        leafGroup.add(spindle);

        const lever = new THREE.Mesh(leverGeo, M.chrome);
        lever.position.set(leafW - 0.075 - 0.048, 1.04, side * (leafT / 2 + 0.036));
        lever.castShadow = true;
        leafGroup.add(lever);
    }

    /* ---- three hinges on the pivot edge ---- */
    for (const hy of [0.24, leafH / 2, leafH - 0.24]) {
        const hinge = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.012, 0.095, 10), M.steel);
        hinge.position.set(0.008, hy, 0);
        leafGroup.add(hinge);
    }

    pivot.add(leafGroup);

    /* ---- swing angles, derived from geometry instead of guessed signs ---
     * dir(theta) for a +X-authored leaf is (cos θ, 0, -sin θ).
     * ------------------------------------------------------------------ */
    const angleOf = (dx, dz) => Math.atan2(-dz, dx);
    const closedAngle = angleOf(-hingeSide * runX, -hingeSide * runZ);
    let openAngle = angleOf(swing * nrmX, swing * nrmZ);
    // pick the representative exactly 90° away from closed
    while (openAngle - closedAngle > Math.PI / 2 + 1e-6) openAngle -= Math.PI;
    while (openAngle - closedAngle < -Math.PI / 2 - 1e-6) openAngle += Math.PI;

    pivot.rotation.y = open ? openAngle : closedAngle;

    group.userData = {
        isDoor: true,
        id: id || 'door',
        pivot,
        closedAngle,
        openAngle,
        targetAngle: open ? openAngle : closedAngle,
        label,
        /** world-space hinge position, for agents to stand clear of */
        hinge: new THREE.Vector3(hingeX, 0, hingeZ),
    };

    return group;
}

/** Slightly darken a colour for recessed panel faces. */
function shadeDown(hex, amount = 0.06) {
    const r = Math.round(((hex >> 16) & 255) * (1 - amount));
    const g = Math.round(((hex >> 8) & 255) * (1 - amount));
    const b = Math.round((hex & 255) * (1 - amount));
    return (r << 16) | (g << 8) | b;
}

/* ------------------------------------------------------------------ *
 *  WINDOWS
 * ------------------------------------------------------------------ */

export function buildWindow(M, spec) {
    const { axis, at, cx, w, h, sill = 0.9, frosted = false, wallT = P.wallExt } = spec;
    const isZ = axis === 'z';
    const group = new THREE.Group();
    group.name = 'window';

    const centerX = isZ ? at : cx;
    const centerZ = isZ ? cx : at;

    const glassMat = frosted ? M.glassFrosted : M.glass;
    const frameMat = M.whiteGloss;

    // outer frame
    const fw = 0.05;
    const addBar = (ox, oy, bw, bh, mat, depth) => {
        const geo = new THREE.BoxGeometry(
            isZ ? depth : bw, bh, isZ ? bw : depth
        );
        const m = new THREE.Mesh(geo, mat);
        m.position.set(
            centerX + (isZ ? 0 : ox),
            sill + h / 2 + oy,
            centerZ + (isZ ? ox : 0)
        );
        m.castShadow = true;
        m.receiveShadow = true;
        group.add(m);
        return m;
    };

    const depth = wallT * 0.5;
    // frame bars (sash)
    addBar(0, h / 2 - fw / 2, w, fw, frameMat, depth); // top
    addBar(0, -h / 2 + fw / 2, w, fw, frameMat, depth); // bottom
    addBar(-w / 2 + fw / 2, 0, fw, h, frameMat, depth); // left
    addBar(w / 2 - fw / 2, 0, fw, h, frameMat, depth); // right
    // mullion + transom -> 2x2 panes for big windows
    const cols = w > 1.8 ? 3 : 2;
    const rows = h > 1.3 ? 2 : 1;
    for (let i = 1; i < cols; i++) {
        addBar(-w / 2 + (w * i) / cols, 0, 0.032, h - fw * 2, frameMat, depth * 0.85);
    }
    for (let j = 1; j < rows; j++) {
        addBar(0, -h / 2 + (h * j) / rows, w - fw * 2, 0.032, frameMat, depth * 0.85);
    }

    // glass panes
    for (let i = 0; i < cols; i++) {
        for (let j = 0; j < rows; j++) {
            const pw = w / cols - 0.04;
            const ph = h / rows - 0.04;
            const px = -w / 2 + (w * (i + 0.5)) / cols;
            const py = -h / 2 + (h * (j + 0.5)) / rows;
            const geo = new THREE.PlaneGeometry(pw, ph);
            const m = new THREE.Mesh(geo, glassMat);
            m.position.set(
                centerX + (isZ ? 0 : px),
                sill + h / 2 + py,
                centerZ + (isZ ? px : 0)
            );
            if (isZ) m.rotation.y = Math.PI / 2;
            group.add(m);
        }
    }

    // interior sill board (projects into the room)
    const sillGeo = roundedBoxGeometry(isZ ? 0.14 : w + 0.1, 0.03, isZ ? w + 0.1 : 0.14, 0.008);
    const sillMesh = new THREE.Mesh(sillGeo, M.whiteGloss);
    sillMesh.position.set(
        centerX + (isZ ? (spec.inward || 1) * (wallT / 2 + 0.06) : 0),
        sill - 0.015,
        centerZ + (isZ ? 0 : (spec.inward || 1) * (wallT / 2 + 0.06))
    );
    sillMesh.castShadow = true;
    sillMesh.receiveShadow = true;
    group.add(sillMesh);

    // soft light spill on the floor beneath the window
    return group;
}

/* ------------------------------------------------------------------ *
 *  Ceiling fixtures / pendants (geometry only; lights live in app)
 * ------------------------------------------------------------------ */

export function buildCeilingLamp(M, { x, z, y = P.ceilH, drop = 0.5, shade = 0.22, cord = true } = {}) {
    const g = new THREE.Group();
    g.position.set(x, y, z);

    const rose = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.06, 0.03, 20), M.white);
    rose.position.y = -0.015;
    g.add(rose);

    if (cord) {
        const c = new THREE.Mesh(new THREE.CylinderGeometry(0.004, 0.004, drop, 6), M.blackSoft);
        c.position.y = -drop / 2 - 0.02;
        g.add(c);
    }

    const shadeGeo = new THREE.ConeGeometry(shade, shade * 0.9, 24, 1, true);
    const shadeMat = new THREE.MeshStandardMaterial({
        color: 0xf3ece0,
        roughness: 0.7,
        side: THREE.DoubleSide,
        emissive: 0xffe6bd,
        emissiveIntensity: 0.22,
    });
    const cone = new THREE.Mesh(shadeGeo, shadeMat);
    cone.position.y = -drop - shade * 0.4;
    cone.castShadow = false;
    g.add(cone);

    // bulb
    const bulb = new THREE.Mesh(
        new THREE.SphereGeometry(shade * 0.2, 16, 12),
        new THREE.MeshBasicMaterial({ color: 0xfff1d8 })
    );
    bulb.position.y = -drop - shade * 0.5;
    g.add(bulb);

    return g;
}

export function buildCeilingSpotRow(M, { x, z, count = 4, spacing = 0.9, y = P.ceilH } = {}) {
    const g = new THREE.Group();
    for (let i = 0; i < count; i++) {
        const px = x + (i - (count - 1) / 2) * spacing;
        const housing = new THREE.Mesh(new THREE.CylinderGeometry(0.055, 0.055, 0.04, 20), M.white);
        housing.position.set(px, y - 0.02, z);
        g.add(housing);
        const lens = new THREE.Mesh(new THREE.CircleGeometry(0.045, 20), new THREE.MeshBasicMaterial({ color: 0xfff4e2 }));
        lens.rotation.x = Math.PI / 2;
        lens.position.set(px, y - 0.042, z);
        g.add(lens);
    }
    return g;
}

export { segmentsExcludingOpenings, interiorFaces };