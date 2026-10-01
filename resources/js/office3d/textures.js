import * as THREE from 'three';
import { createFbm2D, createNoise2D, Rng, clamp, lerp, saturate, smoothstep, cssHex, shadeHex, mixHex, TAU } from './lib.js';

/* ------------------------------------------------------------------ *
 *  Procedural PBR texture library.
 *
 *  Every surface in the scene gets albedo + roughness + normal, derived
 *  from one shared height field.  That is what stops flat-shaded boxes
 *  from looking like untextured programmer art: the normal map is what
 *  catches the light and reveals surface, the roughness map is what
 *  separates matte plaster from polished tile.
 * ------------------------------------------------------------------ */

const CACHE = new Map();

function cached(key, factory) {
    if (CACHE.has(key)) return CACHE.get(key);
    const v = factory();
    CACHE.set(key, v);
    return v;
}

/* ------------------------------------------------------------------ *
 *  Canvas helpers
 * ------------------------------------------------------------------ */

function makeCanvas(size, height = size) {
    const c = document.createElement('canvas');
    c.width = size;
    c.height = height;
    return c;
}

function texFromCanvas(canvas, { srgb = true, repeat = 1, aniso = 8 } = {}) {
    const t = new THREE.CanvasTexture(canvas);
    t.wrapS = THREE.RepeatWrapping;
    t.wrapT = THREE.RepeatWrapping;
    t.colorSpace = srgb ? THREE.SRGBColorSpace : THREE.NoColorSpace;
    t.anisotropy = aniso;
    if (repeat !== 1) t.repeat.set(repeat, repeat);
    t.needsUpdate = true;
    return t;
}

/**
 * Sobel-derive a tangent-space normal map from a grayscale height field.
 * `strength` is in height-units-per-texel; 1.0 is a fairly deep relief.
 */
function normalFromHeight(heightCanvas, strength = 2.0) {
    const size = heightCanvas.width;
    const src = heightCanvas.getContext('2d').getImageData(0, 0, size, size).data;
    const out = makeCanvas(size);
    const octx = out.getContext('2d');
    const img = octx.createImageData(size, size);
    const h = (x, y) => src[((((y + size) % size) * size + ((x + size) % size)) << 2)] / 255;

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const tl = h(x - 1, y - 1), t = h(x, y - 1), tr = h(x + 1, y - 1);
            const l = h(x - 1, y), r = h(x + 1, y);
            const bl = h(x - 1, y + 1), b = h(x, y + 1), br = h(x + 1, y + 1);

            const dx = (tr + 2 * r + br) - (tl + 2 * l + bl);
            const dy = (bl + 2 * b + br) - (tl + 2 * t + tr);

            let nx = -dx * strength;
            let ny = -dy * strength;
            const nz = 1.0;
            const len = Math.sqrt(nx * nx + ny * ny + nz * nz);
            nx /= len;
            ny /= len;
            const nzn = nz / len;

            const i = (y * size + x) << 2;
            img.data[i] = (nx * 0.5 + 0.5) * 255;
            img.data[i + 1] = (ny * 0.5 + 0.5) * 255;
            img.data[i + 2] = (nzn * 0.5 + 0.5) * 255;
            img.data[i + 3] = 255;
        }
    }
    octx.putImageData(img, 0, 0);
    return out;
}

/** Write a grayscale value map straight from a per-pixel callback. */
function grayCanvas(size, fn) {
    const c = makeCanvas(size);
    const ctx = c.getContext('2d');
    const img = ctx.createImageData(size, size);
    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const v = clamp(fn(x, y), 0, 1) * 255;
            const i = (y * size + x) << 2;
            img.data[i] = img.data[i + 1] = img.data[i + 2] = v;
            img.data[i + 3] = 255;
        }
    }
    ctx.putImageData(img, 0, 0);
    return c;
}

/** Assemble the standard {map, roughnessMap, normalMap} triple. */
function pack(albedoCanvas, { heightCanvas, roughCanvas, normalStrength = 2.0, repeat = 1, aniso = 8 } = {}) {
    const map = texFromCanvas(albedoCanvas, { repeat, aniso });
    const out = { map };
    if (heightCanvas) {
        out.normalMap = texFromCanvas(normalFromHeight(heightCanvas, normalStrength), { srgb: false, repeat, aniso });
    }
    if (roughCanvas) {
        out.roughnessMap = texFromCanvas(roughCanvas, { srgb: false, repeat, aniso });
    }
    return out;
}

/* ------------------------------------------------------------------ *
 *  WOOD — plank flooring with real grain, knots and micro-bevel
 * ------------------------------------------------------------------ */

function drawPlankFloor(opts) {
    const {
        size = 1024,
        planksPerSide = 4, // 4x4 planks per texture tile
        base = 0x8a5a34,
        dark = 0x4a2c17,
        light = 0xb9834f,
        seed = 11,
        varnish = 0.55, // 0 matte .. 1 glossy
        groutDark = 0x2a1a10,
    } = opts;

    const rng = new Rng(seed);
    const fbm = createFbm2D(seed);
    const fine = createNoise2D(seed + 91);
    const canvas = makeCanvas(size);
    const ctx = canvas.getContext('2d');
    const heightC = makeCanvas(size);
    const hctx = heightC.getContext('2d');
    const roughC = makeCanvas(size);
    const rctx = roughC.getContext('2d');

    ctx.fillStyle = cssHex(base);
    ctx.fillRect(0, 0, size, size);

    const cell = size / planksPerSide;
    // staggered plank lengths
    const rowOffset = [];

    const img = ctx.getImageData(0, 0, size, size);
    const hImg = hctx.createImageData(size, size);
    const rImg = rctx.createImageData(size, size);

    for (let row = 0; row < planksPerSide; row++) {
        // random plank break positions along this row
        const breaks = [0];
        let x = 0;
        while (x < size) {
            x += cell * rng.float(1.1, 2.2);
            breaks.push(Math.min(x, size));
        }
        rowOffset.push(breaks);
    }

    for (let py = 0; py < size; py++) {
        const row = Math.floor(py / cell);
        const rowLocal = (py % cell) / cell;
        const breaks = rowOffset[row];
        const plankShift = rngGaussFor(row, seed) * 0.35;

        for (let px = 0; px < size; px++) {
            // find which plank along X this texel belongs to
            let seg = 0;
            while (seg < breaks.length - 2 && px >= breaks[seg + 1]) seg++;
            const a = breaks[seg];
            const b = breaks[seg + 1];
            const u = (px - a) / Math.max(1, b - a);
            const plankId = row * 97 + seg;

            // ---- grain ----
            // grain runs along the plank (X). Stretch noise hard on Y.
            const gx = px * 0.012 + plankId * 13.7 + plankShift * 40;
            const gy = py * 0.32 + plankId * 5.1;
            let grain = fbm(gx, gy, 5, 2.1, 0.55);
            // ring-like banding
            grain += 0.34 * Math.sin((py / cell) * 34 + fbm(gx * 0.4, gy * 0.06, 3) * 9);
            // fine pores
            const pore = fine(px * 0.9, py * 1.7) * 0.16;
            grain = saturate(grain * 0.85 + pore);

            // occasional knot
            let knot = 0;
            const kseed = (plankId * 2654435761) >>> 0;
            const kx = (kseed % 1000) / 1000;
            const ky = ((kseed >> 10) % 1000) / 1000;
            if (kx > 0.62) {
                const d = Math.hypot((u - kx) * 1.0, (rowLocal - ky) * 1.0);
                knot = smoothstep(0.055, 0.004, d);
            }

            // per-plank tonal variation so the floor isn't a repeating stamp
            const pv = ((Math.sin(plankId * 12.9898) * 43758.5453) % 1 + 1) % 1;
            const plankShiftTone = (pv - 0.5) * 0.26;

            let col = mixHex(dark, light, saturate(grain + plankShiftTone));
            col = mixHex(col, shadeHex(dark, -0.35), knot * 0.85);

            // ---- micro bevel between planks ----
            const edgeX = Math.min(u, 1 - u) * (b - a);
            const edgeY = Math.min(rowLocal, 1 - rowLocal) * cell;
            const edge = Math.min(edgeX, edgeY);
            const bevel = smoothstep(0.0, 2.4, edge); // 2.4 px dark groove
            col = mixHex(col, groutDark, (1 - bevel) * 0.85);

            // subtle satin sheen variation along the plank
            const sheen = 0.5 + 0.5 * Math.sin(u * 7 + pv * 6.28);

            const i = (py * size + px) << 2;
            img.data[i] = (col >> 16) & 255;
            img.data[i + 1] = (col >> 8) & 255;
            img.data[i + 2] = col & 255;
            img.data[i + 3] = 255;

            // height: grain relief + deep grooves at plank edges
            const hgt = saturate(0.55 + grain * 0.28 - knot * 0.3) * bevel;
            const hv = hgt * 255;
            hImg.data[i] = hImg.data[i + 1] = hImg.data[i + 2] = hv;
            hImg.data[i + 3] = 255;

            // roughness: varnish flat, grooves rough, knots slightly rougher
            const rv = saturate(1 - varnish + (1 - bevel) * 0.45 + knot * 0.22 - sheen * 0.06);
            const rV = rv * 255;
            rImg.data[i] = rImg.data[i + 1] = rImg.data[i + 2] = rV;
            rImg.data[i + 3] = 255;
        }
    }

    ctx.putImageData(img, 0, 0);
    hctx.putImageData(hImg, 0, 0);
    rctx.putImageData(rImg, 0, 0);

    return pack(canvas, {
        heightCanvas: heightC,
        roughCanvas: roughC,
        normalStrength: 2.4,
        ...opts.texOpts,
    });
}

function rngGaussFor(i, seed) {
    const s = Math.sin((i + seed) * 127.1) * 43758.5453;
    return (s - Math.floor(s)) - 0.5;
}

export function woodFloor(opts = {}) {
    return cached('wood:' + JSON.stringify(opts), () => drawPlankFloor(opts));
}

/* ------------------------------------------------------------------ *
 *  PAINTED PLASTER WALL — orange-peel roller texture
 * ------------------------------------------------------------------ */

export function wallPaint(color = 0xe8e2d8, opts = {}) {
    const key = 'wallpaint:' + color + JSON.stringify(opts);
    return cached(key, () => {
        const size = 512;
        const fbm = createFbm2D(4242);
        const peel = createNoise2D(919);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        const img = ctx.createImageData(size, size);
        const base = { r: (color >> 16) & 255, g: (color >> 8) & 255, b: color & 255 };

        const heightC = grayCanvas(size, (x, y) => {
            // orange peel: high-frequency blobby noise
            const a = peel(x * 0.16, y * 0.16);
            const b = fbm(x * 0.03, y * 0.03, 3);
            return 0.45 + a * 0.32 + b * 0.18;
        });
        const roughC = grayCanvas(size, (x, y) => 0.86 + peel(x * 0.05, y * 0.05) * 0.1);

        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                const n = fbm(x * 0.012, y * 0.012, 4) - 0.5;
                const p = peel(x * 0.16, y * 0.16) - 0.5;
                const k = 1 + n * 0.09 + p * 0.045;
                const i = (y * size + x) << 2;
                img.data[i] = clamp(base.r * k, 0, 255);
                img.data[i + 1] = clamp(base.g * k, 0, 255);
                img.data[i + 2] = clamp(base.b * k, 0, 255);
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        return pack(canvas, {
            heightCanvas: heightC,
            roughCanvas: roughC,
            normalStrength: 0.55,
            ...opts.texOpts,
        });
    });
}

/* ------------------------------------------------------------------ *
 *  CERAMIC TILE FLOOR / WALL
 * ------------------------------------------------------------------ */

function drawTile(opts) {
    const {
        size = 1024,
        tilesX = 4,
        tilesY = 4,
        base = 0xd8d3cb,
        grout = 0x9c968c,
        groutWidth = 3.0,
        seed = 5,
        veining = 0.0,
        cloudiness = 0.05,
        gloss = 0.28,
        offsetRow = false,
    } = opts;

    const fbm = createFbm2D(seed);
    const speck = createNoise2D(seed + 3);
    const canvas = makeCanvas(size);
    const ctx = canvas.getContext('2d');
    const img = ctx.createImageData(size, size);

    const cellX = size / tilesX;
    const cellY = size / tilesY;

    /**
     * Single source of truth for "where am I on the tile grid", so the
     * albedo / height / roughness passes can never disagree about grout.
     */
    const sample = (x, y) => {
        const row = Math.floor(y / cellY);
        const shift = offsetRow ? (row % 2) * cellX * 0.5 : 0;
        const wx = x + shift;
        const lx = wx % cellX;
        const ly = y % cellY;
        const gx = Math.min(lx, cellX - lx);
        const gy = Math.min(ly, cellY - ly);
        const gap = Math.min(gx, gy);
        const colIdx = Math.floor(wx / cellX) + row * 31;
        // cheap deterministic 0..1 per tile, for batch tone variation
        const v = ((Math.sin(colIdx * 45.233) * 9117.13) % 1 + 1) % 1;
        return { gap, inTile: smoothstep(0, groutWidth, gap), colIdx, v };
    };

    const heightC = grayCanvas(size, (x, y) => {
        const s = sample(x, y);
        return s.inTile * 0.9 + fbm(x * 0.05, y * 0.05, 3) * 0.06;
    });

    const roughC = grayCanvas(size, (x, y) => {
        const s = sample(x, y);
        const r = 1 - gloss;
        return lerp(0.92, r + fbm(x * 0.02, y * 0.02, 2) * 0.05, s.inTile);
    });

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const { inTile, v } = sample(x, y);

            // marble veining: ridged fbm
            let vein = 0;
            if (veining > 0) {
                const r1 = Math.abs(fbm(x * 0.006, y * 0.006, 5) - 0.5) * 2;
                vein = smoothstep(0.5, 0.0, r1) * veining;
            }
            const cloud = (fbm(x * 0.01, y * 0.01, 3) - 0.5) * cloudiness;
            const sp = (speck(x * 1.4, y * 1.4) - 0.5) * 0.03;

            let col = mixHex(base, shadeHex(base, 0.5), cloud + sp);
            col = mixHex(col, shadeHex(base, -0.45), vein);
            // per-tile batch variation
            col = mixHex(col, shadeHex(col, 0.5), (v - 0.5) * 0.12);
            // grout
            col = mixHex(grout, col, inTile);

            const i = (y * size + x) << 2;
            img.data[i] = (col >> 16) & 255;
            img.data[i + 1] = (col >> 8) & 255;
            img.data[i + 2] = col & 255;
            img.data[i + 3] = 255;
        }
    }
    ctx.putImageData(img, 0, 0);
    return pack(canvas, {
        heightCanvas,
        roughCanvas: roughC,
        normalStrength: 1.5,
        ...opts.texOpts,
    });
}

export function tileFloor(opts = {}) {
    return cached('tile:' + JSON.stringify(opts), () =>
        drawTile({ size: 1024, tilesX: 6, tilesY: 6, base: 0xd6d2cb, gloss: 0.42, cloudiness: 0.08, ...opts }));
}

export function tileSmall(opts = {}) {
    return cached('tilesmall:' + JSON.stringify(opts), () =>
        drawTile({ size: 1024, tilesX: 10, tilesY: 10, base: 0xcfcabf, grout: 0x8f8a80, groutWidth: 2.2, gloss: 0.5, ...opts }));
}

export function tileLarge(opts = {}) {
    return cached('tilelarge:' + JSON.stringify(opts), () =>
        drawTile({ size: 1024, tilesX: 3, tilesY: 3, base: 0xbdb6ab, grout: 0x8a837a, groutWidth: 3.4, gloss: 0.35, veining: 0.55, ...opts }));
}

export function wallTile(opts = {}) {
    return cached('walltile:' + JSON.stringify(opts), () =>
        drawTile({ size: 1024, tilesX: 8, tilesY: 4, base: 0xe6e9ea, grout: 0xb9bfc2, groutWidth: 2.6, gloss: 0.62, offsetRow: true, ...opts }));
}

/* ------------------------------------------------------------------ *
 *  MARBLE (countertops, side table)
 * ------------------------------------------------------------------ */

export function marble(color = 0xeceae5, veinColor = 0x8b8f96, opts = {}) {
    return cached('marble:' + color + veinColor + JSON.stringify(opts), () => {
        const size = 512;
        const fbm = createFbm2D(77);
        const warp = createFbm2D(78);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        const img = ctx.createImageData(size, size);

        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                const wu = x + (warp(x * 0.004, y * 0.004, 3) - 0.5) * 90;
                const wv = y + (warp(x * 0.004 + 5, y * 0.004 + 5, 3) - 0.5) * 90;
                let v = Math.abs(fbm(wu * 0.006, wv * 0.02, 5) - 0.5) * 2;
                const vein = smoothstep(0.42, 0.0, v);
                const hair = smoothstep(0.16, 0.0, v) * 0.5;
                const cloud = (fbm(x * 0.008, y * 0.008, 3) - 0.5) * 0.07;
                let col = mixHex(color, shadeHex(color, -0.05), cloud);
                col = mixHex(col, veinColor, saturate(vein * 0.8 + hair));
                const i = (y * size + x) << 2;
                img.data[i] = (col >> 16) & 255;
                img.data[i + 1] = (col >> 8) & 255;
                img.data[i + 2] = col & 255;
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        const heightC = grayCanvas(size, (x, y) => {
            const wu = x + (warp(x * 0.004, y * 0.004, 3) - 0.5) * 90;
            let v = Math.abs(fbm(wu * 0.006, y * 0.02, 5) - 0.5) * 2;
            return 0.7 - smoothstep(0.42, 0.0, v) * 0.25;
        });
        const roughC = grayCanvas(size, (x, y) => {
            const wu = x + (warp(x * 0.004, y * 0.004, 3) - 0.5) * 90;
            let v = Math.abs(fbm(wu * 0.006, y * 0.02, 5) - 0.5) * 2;
            return lerp(0.12, 0.26, smoothstep(0.42, 0.0, v));
        });
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 0.5, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  FABRIC / UPHOLSTERY — woven weave for sofa, chair pads, bedding
 * ------------------------------------------------------------------ */

export function fabric(color = 0x9aa3ad, opts = {}) {
    return cached('fabric:' + color + JSON.stringify(opts), () => {
        const size = 512;
        const fuzz = createNoise2D(303);
        const fbm = createFbm2D(304);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        const img = ctx.createImageData(size, size);
        const base = { r: (color >> 16) & 255, g: (color >> 8) & 255, b: color & 255 };
        const weave = opts.weave || 5; // px per thread pair

        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                const wu = (x % weave) / weave;
                const wv = (y % weave) / weave;
                // over/under weave: whichever thread is on top gets the lift
                const cellX = Math.floor(x / weave);
                const cellY = Math.floor(y / weave);
                const warpOnTop = (cellX + cellY) % 2 === 0;
                const t = warpOnTop ? Math.sin(wv * Math.PI) : Math.sin(wu * Math.PI);
                const f = fuzz(x * 1.3, y * 1.3) * 0.2;
                const k = 1 + (t - 0.5) * 0.26 + f - 0.1;
                const i = (y * size + x) << 2;
                img.data[i] = clamp(base.r * k, 0, 255);
                img.data[i + 1] = clamp(base.g * k, 0, 255);
                img.data[i + 2] = clamp(base.b * k, 0, 255);
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        const heightC = grayCanvas(size, (x, y) => {
            const wu = (x % weave) / weave;
            const wv = (y % weave) / weave;
            const cellX = Math.floor(x / weave);
            const cellY = Math.floor(y / weave);
            const warpOnTop = (cellX + cellY) % 2 === 0;
            return 0.5 + (warpOnTop ? wv : wu) * 0.45;
        });
        const roughC = grayCanvas(size, (x, y) => 0.9 + fbm(x * 0.02, y * 0.02, 2) * 0.08);
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 1.5, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  CARPET / RUG — fibre noise
 * ------------------------------------------------------------------ */

export function carpet(color = 0x7d6a58, opts = {}) {
    return cached('carpet:' + color + JSON.stringify(opts), () => {
        const size = 512;
        const fibre = createNoise2D(505);
        const patch = createFbm2D(506);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        const img = ctx.createImageData(size, size);
        const base = { r: (color >> 16) & 255, g: (color >> 8) & 255, b: color & 255 };

        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                const f = fibre(x * 2.4, y * 2.4);
                const p = patch(x * 0.01, y * 0.01, 3);
                const k = 1 + (f - 0.5) * 0.34 + (p - 0.5) * 0.16;
                const i = (y * size + x) << 2;
                img.data[i] = clamp(base.r * k, 0, 255);
                img.data[i + 1] = clamp(base.g * k, 0, 255);
                img.data[i + 2] = clamp(base.b * k, 0, 255);
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        const heightC = grayCanvas(size, (x, y) => fibre(x * 2.4, y * 2.4));
        const roughC = grayCanvas(size, () => 0.97);
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 1.1, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  METAL
 * ------------------------------------------------------------------ */

export function brushedMetal(opts = {}) {
    return cached('metal:' + JSON.stringify(opts), () => {
        const size = 512;
        const streak = createNoise2D(707);
        const fine = createNoise2D(708);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        const img = ctx.createImageData(size, size);
        const tint = opts.tint || 0xd8dade;
        const base = { r: (tint >> 16) & 255, g: (tint >> 8) & 255, b: tint & 255 };

        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                // long streaks along X
                const s = streak(x * 0.02, y * 3.2) * 0.6 + fine(x * 0.9, y * 6.0) * 0.4;
                const k = 1 + (s - 0.5) * 0.1;
                const i = (y * size + x) << 2;
                img.data[i] = clamp(base.r * k, 0, 255);
                img.data[i + 1] = clamp(base.g * k, 0, 255);
                img.data[i + 2] = clamp(base.b * k, 0, 255);
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        const heightC = grayCanvas(size, (x, y) => streak(x * 0.02, y * 3.2) * 0.7 + fine(x * 0.9, y * 6.0) * 0.3);
        const roughC = grayCanvas(size, (x, y) => 0.24 + streak(x * 0.02, y * 3.2) * 0.18);
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 0.4, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  CONCRETE / micro-cement (ceiling, columns, decorative walls)
 * ------------------------------------------------------------------ */

export function concrete(color = 0xb8b4ac, opts = {}) {
    return cached('concrete:' + color + JSON.stringify(opts), () => {
        const size = 512;
        const fbm = createFbm2D(909);
        const pit = createNoise2D(910);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        const img = ctx.createImageData(size, size);
        const base = { r: (color >> 16) & 255, g: (color >> 8) & 255, b: color & 255 };
        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                const n = fbm(x * 0.008, y * 0.008, 5) - 0.5;
                const p = pit(x * 1.6, y * 1.6) > 0.93 ? -0.16 : 0;
                const k = 1 + n * 0.16 + p;
                const i = (y * size + x) << 2;
                img.data[i] = clamp(base.r * k, 0, 255);
                img.data[i + 1] = clamp(base.g * k, 0, 255);
                img.data[i + 2] = clamp(base.b * k, 0, 255);
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        const heightC = grayCanvas(size, (x, y) => {
            const p = pit(x * 1.6, y * 1.6) > 0.93 ? 0.2 : 0.5;
            return p + fbm(x * 0.05, y * 0.05, 3) * 0.3;
        });
        const roughC = grayCanvas(size, (x, y) => 0.82 + fbm(x * 0.02, y * 0.02, 2) * 0.12);
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 0.8, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  WOOD VENEER (furniture panels, door leaves)
 * ------------------------------------------------------------------ */

export function woodVeneer(color = 0x6b4630, opts = {}) {
    return cached('veneer:' + color + JSON.stringify(opts), () => {
        const size = 512;
        const fbm = createFbm2D(1111);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        const img = ctx.createImageData(size, size);
        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                const g = fbm(x * 0.006, y * 0.22, 5, 2.2, 0.55);
                const band = Math.sin(y * 0.28 + fbm(x * 0.01, y * 0.01, 2) * 7) * 0.5 + 0.5;
                const k = 0.72 + g * 0.36 + band * 0.16;
                const col = mixHex(shadeHex(color, -0.4), shadeHex(color, 0.3), saturate(k));
                const i = (y * size + x) << 2;
                img.data[i] = (col >> 16) & 255;
                img.data[i + 1] = (col >> 8) & 255;
                img.data[i + 2] = col & 255;
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        const heightC = grayCanvas(size, (x, y) => fbm(x * 0.02, y * 0.3, 4));
        const roughC = grayCanvas(size, (x, y) => 0.44 + fbm(x * 0.01, y * 0.05, 3) * 0.16);
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 0.7, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  LEATHER (sofa cushions, chair pads)
 * ------------------------------------------------------------------ */

export function leather(color = 0x8b5e3c, opts = {}) {
    return cached('leather:' + color + JSON.stringify(opts), () => {
        const size = 512;
        const cell = createNoise2D(1212);
        const fbm = createFbm2D(1213);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        const img = ctx.createImageData(size, size);
        const base = { r: (color >> 16) & 255, g: (color >> 8) & 255, b: color & 255 };
        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                // Voronoi-ish grain: cellular pattern from ridged noise
                const g1 = cell(x * 0.18, y * 0.18);
                const g2 = cell(x * 0.42 + 11, y * 0.42 + 7);
                const grain = 0.6 + g1 * 0.25 + g2 * 0.15;
                const k = 1 + (grain - 0.7) * 0.4;
                const i = (y * size + x) << 2;
                img.data[i] = clamp(base.r * k, 0, 255);
                img.data[i + 1] = clamp(base.g * k, 0, 255);
                img.data[i + 2] = clamp(base.b * k, 0, 255);
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        const heightC = grayCanvas(size, (x, y) => 0.4 + cell(x * 0.18, y * 0.18) * 0.5);
        const roughC = grayCanvas(size, (x, y) => 0.5 + fbm(x * 0.03, y * 0.03, 3) * 0.2);
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 1.3, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  PERFORATED GRILLE (AC, speaker, PC mesh)
 * ------------------------------------------------------------------ */

export function perforated(opts = {}) {
    return cached('perf:' + JSON.stringify(opts), () => {
        const size = 256;
        const pitch = opts.pitch || 10;
        const holeR = opts.holeR || 3.2;
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#d6d6d6';
        ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = '#3a3a3a';
        for (let y = 0; y < size + pitch; y += pitch) {
            for (let x = 0; x < size + pitch; x += pitch) {
                ctx.beginPath();
                ctx.arc(x, y, holeR, 0, TAU);
                ctx.fill();
            }
        }
        const heightC = grayCanvas(size, (x, y) => {
            const mx = x % pitch;
            const my = y % pitch;
            const d = Math.hypot(mx, my);
            return smoothstep(holeR - 0.8, holeR + 0.8, d);
        });
        const roughC = grayCanvas(size, (x, y) => {
            const mx = x % pitch;
            const my = y % pitch;
            const d = Math.hypot(mx, my);
            return lerp(0.95, 0.45, smoothstep(holeR - 0.8, holeR + 0.8, d));
        });
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 2.0, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  BOOK SPINES (bookshelf backdrop)
 * ------------------------------------------------------------------ */

export function bookSpines(opts = {}) {
    return cached('books:' + JSON.stringify(opts), () => {
        const size = 512;
        const rng = new Rng(4242);
        const fbm = createFbm2D(4243);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#241a14';
        ctx.fillRect(0, 0, size, size);

        const heightC = makeCanvas(size);
        const hctx = heightC.getContext('2d');
        hctx.fillStyle = '#000';
        hctx.fillRect(0, 0, size, size);

        const roughC = makeCanvas(size);
        const rctx = roughC.getContext('2d');
        rctx.fillStyle = '#c0c0c0';
        rctx.fillRect(0, 0, size, size);

        const palette = [0x8c3b3b, 0x2f4858, 0x3d6b4a, 0xa8763e, 0x5a3d6b, 0xb0a08a, 0x2b2b30, 0x7a5230, 0x1f4b5c];
        let x = 0;
        while (x < size) {
            const w = rng.int(8, 22);
            const h = Math.round(size * rng.float(0.72, 0.97));
            const y = size - h;
            const col = palette[rng.int(0, palette.length - 1)];
            const tilt = rng.bool(0.08) ? rng.float(-0.06, 0.06) : 0;

            ctx.save();
            ctx.translate(x + w / 2, size);
            ctx.rotate(tilt);
            ctx.fillStyle = cssHex(col);
            ctx.fillRect(-w / 2, -h, w, h);
            // top edge highlight + bottom shadow for roundness
            const g = ctx.createLinearGradient(-w / 2, 0, w / 2, 0);
            g.addColorStop(0, 'rgba(0,0,0,0.4)');
            g.addColorStop(0.35, 'rgba(255,255,255,0.12)');
            g.addColorStop(1, 'rgba(0,0,0,0.45)');
            ctx.fillStyle = g;
            ctx.fillRect(-w / 2, -h, w, h);
            // spine bands / title block
            ctx.fillStyle = 'rgba(240,225,190,0.55)';
            if (rng.bool(0.7)) ctx.fillRect(-w / 2 + 2, -h * 0.82, w - 4, 2.5);
            if (rng.bool(0.6)) ctx.fillRect(-w / 2 + 2, -h * 0.74, w - 4, 1.5);
            if (rng.bool(0.5)) ctx.fillRect(-w / 2 + 2, -h * 0.3, w - 4, 2.0);
            ctx.restore();

            hctx.fillStyle = '#c8c8c8';
            hctx.fillRect(x + 0.6, y, Math.max(1, w - 1.2), h);

            const rv = Math.round(rng.float(150, 205));
            rctx.fillStyle = `rgb(${rv},${rv},${rv})`;
            rctx.fillRect(x, y, w, h);

            x += w + (rng.bool(0.3) ? rng.float(0.5, 2) : 0);
        }
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 1.6, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  MONSTERA / RUBBER-PLANT LEAF
 * ------------------------------------------------------------------ */

export function leaf(opts = {}) {
    return cached('leaf:' + JSON.stringify(opts), () => {
        const size = 256;
        const fbm = createFbm2D(1313);
        const vein = createNoise2D(1314);
        const canvas = makeCanvas(size);
        const ctx = canvas.getContext('2d');
        const img = ctx.createImageData(size, size);
        const c1 = opts.color || 0x2f6b3a;
        const c2 = opts.color2 || 0x14421f;

        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                // midrib runs down the centre
                const cx = size / 2;
                const dx = (x - cx) / (size * 0.5);
                const rib = smoothstep(0.055, 0.0, Math.abs(dx));
                // lateral veins angle out from the midrib
                const lat = Math.abs(Math.sin((y * 0.09 + Math.abs(dx) * 5.2)));
                const latMask = smoothstep(0.86, 1.0, lat) * (1 - smoothstep(0.2, 0.95, Math.abs(dx)));
                const mott = fbm(x * 0.02, y * 0.02, 4);
                const edgeDark = smoothstep(0.55, 1.0, Math.abs(dx));

                let col = mixHex(c1, c2, mott * 0.7 + edgeDark * 0.4);
                col = mixHex(col, shadeHex(c1, 0.35), saturate(rib * 0.8 + latMask * 0.35));
                col = mixHex(col, shadeHex(c2, -0.2), smoothstep(0.7, 1.0, fbm(x * 0.05, y * 0.05, 2)));

                const i = (y * size + x) << 2;
                img.data[i] = (col >> 16) & 255;
                img.data[i + 1] = (col >> 8) & 255;
                img.data[i + 2] = col & 255;
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        const heightC = grayCanvas(size, (x, y) => {
            const dx = Math.abs((x - size / 2) / (size * 0.5));
            const rib = smoothstep(0.06, 0.0, dx);
            const lat = smoothstep(0.86, 1.0, Math.abs(Math.sin((y * 0.09 + dx * 5.2)))) * (1 - smoothstep(0.2, 0.95, dx));
            return 0.4 + rib * 0.4 + lat * 0.2;
        });
        const roughC = grayCanvas(size, (x, y) => 0.42 + fbm(x * 0.03, y * 0.03, 3) * 0.22);
        return pack(canvas, { heightCanvas: heightC, roughCanvas: roughC, normalStrength: 1.2, ...opts.texOpts });
    });
}

/* ------------------------------------------------------------------ *
 *  CITY SKYLINE — what you see through the windows
 * ------------------------------------------------------------------ */

export function citySkyline(opts = {}) {
    return cached('skyline:' + JSON.stringify(opts), () => {
        const w = 1024;
        const h = 512;
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        const rng = new Rng(opts.seed || 777);

        // sky gradient — late afternoon
        const sky = ctx.createLinearGradient(0, 0, 0, h);
        sky.addColorStop(0.0, '#2f5f92');
        sky.addColorStop(0.35, '#6e9ec6');
        sky.addColorStop(0.62, '#c9bda6');
        sky.addColorStop(0.82, '#e8c79a');
        sky.addColorStop(1.0, '#f3d9b4');
        ctx.fillStyle = sky;
        ctx.fillRect(0, 0, w, h);

        // soft clouds
        for (let i = 0; i < 26; i++) {
            const cx = rng.float(0, w);
            const cy = rng.float(0, h * 0.45);
            const r = rng.float(30, 130);
            const g = ctx.createRadialGradient(cx, cy, 0, cx, cy, r);
            const alpha = rng.float(0.05, 0.22);
            g.addColorStop(0, `rgba(255,246,232,${alpha})`);
            g.addColorStop(1, 'rgba(255,246,232,0)');
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(cx, cy, r, 0, TAU);
            ctx.fill();
        }

        // three depth layers of buildings
        const layers = [
            { base: h * 0.86, minH: 40, maxH: 130, color: '#8fa6bd', win: 0.0, alpha: 0.55 },
            { base: h * 0.93, minH: 70, maxH: 230, color: '#5f7691', win: 0.12, alpha: 0.85 },
            { base: h * 1.02, minH: 110, maxH: 330, color: '#3a4c63', win: 0.3, alpha: 1.0 },
        ];

        for (const L of layers) {
            let x = -40;
            while (x < w + 40) {
                const bw = rng.float(38, 110);
                const bh = rng.float(L.minH, L.maxH);
                const by = L.base - bh;
                ctx.globalAlpha = L.alpha;
                ctx.fillStyle = L.color;
                ctx.fillRect(x, by, bw, bh + 20);

                // roof detail
                if (rng.bool(0.3)) ctx.fillRect(x + bw * 0.35, by - rng.float(8, 26), bw * 0.12, 26);
                if (rng.bool(0.2)) ctx.fillRect(x + bw * 0.6, by - rng.float(14, 40), 3, 40);

                // windows
                if (L.win > 0) {
                    const cols = Math.max(1, Math.floor(bw / 13));
                    const rows = Math.max(1, Math.floor(bh / 17));
                    for (let c = 0; c < cols; c++) {
                        for (let r2 = 0; r2 < rows; r2++) {
                            if (!rng.bool(L.win + 0.25)) continue;
                            const wx = x + 4 + c * 13;
                            const wy = by + 6 + r2 * 17;
                            if (wx + 6 > x + bw - 3) continue;
                            ctx.fillStyle = rng.bool(0.25) ? 'rgba(255,226,160,0.95)' : 'rgba(190,210,230,0.35)';
                            ctx.fillRect(wx, wy, 6, 8);
                        }
                    }
                }
                ctx.globalAlpha = 1;
                x += bw + rng.float(4, 16);
            }
            // atmospheric haze between layers
            const haze = ctx.createLinearGradient(0, L.base - 300, 0, L.base);
            haze.addColorStop(0, 'rgba(214,196,178,0)');
            haze.addColorStop(1, 'rgba(214,196,178,0.5)');
            ctx.fillStyle = haze;
            ctx.fillRect(0, L.base - 300, w, 300);
        }

        // ground haze
        const gz = ctx.createLinearGradient(0, h * 0.9, 0, h);
        gz.addColorStop(0, 'rgba(226,208,186,0)');
        gz.addColorStop(1, 'rgba(226,208,186,0.85)');
        ctx.fillStyle = gz;
        ctx.fillRect(0, h * 0.9, w, h * 0.1);

        const t = new THREE.CanvasTexture(canvas);
        t.colorSpace = THREE.SRGBColorSpace;
        t.wrapS = THREE.ClampToEdgeWrapping;
        t.wrapT = THREE.ClampToEdgeWrapping;
        t.needsUpdate = true;
        return t;
    });
}

/* ------------------------------------------------------------------ *
 *  FROSTED GLASS
 * ------------------------------------------------------------------ */

export function frostedGlass(opts = {}) {
    return cached('frost:' + JSON.stringify(opts), () => {
        const size = 256;
        const n = createNoise2D(1515);
        const c = makeCanvas(size);
        const ctx = c.getContext('2d');
        const img = ctx.createImageData(size, size);
        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                const v = 235 + n(x * 0.5, y * 0.5) * 20;
                const i = (y * size + x) << 2;
                img.data[i] = img.data[i + 1] = img.data[i + 2] = v;
                img.data[i + 3] = 255;
            }
        }
        ctx.putImageData(img, 0, 0);
        const heightC = grayCanvas(size, (x, y) => n(x * 0.5, y * 0.5));
        const roughC = grayCanvas(size, (x, y) => 0.6 + n(x * 0.2, y * 0.2) * 0.3);
        return pack(c, {
            heightCanvas: heightC,
            roughCanvas: roughC,
            normalStrength: 0.7,
            ...opts.texOpts,
        });
    });
}

/* ------------------------------------------------------------------ *
 *  Generic normal / bump for small utility surfaces
 * ------------------------------------------------------------------ */

export function noiseNormal(opts = {}) {
    return cached('nrm:' + JSON.stringify(opts), () => {
        const size = 256;
        const fbm = createFbm2D(opts.seed || 1);
        const h = grayCanvas(size, (x, y) => fbm(x * 0.08, y * 0.08, 4));
        return texFromCanvas(normalFromHeight(h, opts.strength || 1.4), { srgb: false });
    });
}

/* ------------------------------------------------------------------ *
 *  Canvas-drawn artwork / posters / signage
 * ------------------------------------------------------------------ */

export function artworkCanvas(draw, w = 512, h = 640) {
    const c = document.createElement('canvas');
    c.width = w;
    c.height = h;
    const ctx = c.getContext('2d');
    draw(ctx, w, h);
    const t = new THREE.CanvasTexture(c);
    t.colorSpace = THREE.SRGBColorSpace;
    t.needsUpdate = true;
    return t;
}

/* ------------------------------------------------------------------ *
 *  Convenience: build a MeshStandardMaterial straight from a generator
 * ------------------------------------------------------------------ */

export function texMaterial(texSet, extra = {}) {
    return new THREE.MeshStandardMaterial({
        map: texSet?.map || null,
        normalMap: texSet?.normalMap || null,
        roughnessMap: texSet?.roughnessMap || null,
        roughness: 1.0,
        metalness: 0.0,
        ...extra,
    });
}

/** Clone a texture set with independent repeat (textures are shared). */
export function retile(texSet, ru, rv = ru) {
    const out = {};
    for (const k of ['map', 'normalMap', 'roughnessMap']) {
        if (!texSet || !texSet[k]) continue;
        const t = texSet[k].clone();
        t.needsUpdate = true;
        t.wrapS = THREE.RepeatWrapping;
        t.wrapT = THREE.RepeatWrapping;
        t.repeat.set(ru, rv);
        out[k] = t;
    }
    return out;
}

export { texFromCanvas, normalFromHeight, grayCanvas, makeCanvas, pack };