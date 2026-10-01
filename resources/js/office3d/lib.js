import * as THREE from 'three';

/* ------------------------------------------------------------------ *
 *  Scalar math
 * ------------------------------------------------------------------ */

export const TAU = Math.PI * 2;
export const clamp = (v, a, b) => (v < a ? a : v > b ? b : v);
export const lerp = (a, b, t) => a + (b - a) * t;
export const invLerp = (a, b, v) => (b === a ? 0 : (v - a) / (b - a));
export const saturate = (v) => clamp(v, 0, 1);

export function smoothstep(edge0, edge1, x) {
    const t = saturate(invLerp(edge0, edge1, x));
    return t * t * (3 - 2 * t);
}

export function smootherstep(edge0, edge1, x) {
    const t = saturate(invLerp(edge0, edge1, x));
    return t * t * t * (t * (t * 6 - 15) + 10);
}

export const easeInOutCubic = (t) =>
    t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
export const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
export const easeInCubic = (t) => t * t * t;
export const easeOutQuint = (t) => 1 - Math.pow(1 - t, 5);
export const easeInOutSine = (t) => -(Math.cos(Math.PI * t) - 1) / 2;

/** Frame-rate independent exponential approach. `rate` = how fast (1/s). */
export function damp(current, target, rate, dt) {
    return lerp(current, target, 1 - Math.exp(-rate * dt));
}

/** Shortest signed delta between two angles, in (-PI, PI]. */
export function angleDelta(from, to) {
    let d = (to - from) % TAU;
    if (d > Math.PI) d -= TAU;
    if (d < -Math.PI) d += TAU;
    return d;
}

/** Angle interpolation that always takes the short way round. */
export function dampAngle(current, target, rate, dt) {
    return current + angleDelta(current, target) * (1 - Math.exp(-rate * dt));
}

/** Critically-damped spring toward a target — used for organic body motion. */
export function spring(current, velocity, target, stiffness, damping, dt) {
    const a = (target - current) * stiffness - velocity * damping;
    const v = velocity + a * dt;
    return [current + v * dt, v];
}

/* ------------------------------------------------------------------ *
 *  Deterministic randomness (seeded — scenes must look identical
 *  across reloads, otherwise everything reads as "random AI noise")
 * ------------------------------------------------------------------ */

export function mulberry32(seed) {
    let a = seed >>> 0;
    return function () {
        a |= 0;
        a = (a + 0x6d2b79f5) | 0;
        let t = Math.imul(a ^ (a >>> 15), 1 | a);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** Small helper object wrapping a seeded generator with useful ranges. */
export class Rng {
    constructor(seed = 1337) {
        this.next = mulberry32(seed);
    }
    float(min = 0, max = 1) {
        return min + this.next() * (max - min);
    }
    int(min, max) {
        return Math.floor(this.float(min, max + 1));
    }
    bool(chance = 0.5) {
        return this.next() < chance;
    }
    pick(arr) {
        return arr[Math.floor(this.next() * arr.length) % arr.length];
    }
    sign() {
        return this.next() < 0.5 ? -1 : 1;
    }
    /** Roughly gaussian via central limit — nicer than flat noise. */
    gauss(mean = 0, sd = 1) {
        let s = 0;
        for (let i = 0; i < 4; i++) s += this.next();
        return mean + ((s - 2) / 0.8165) * sd;
    }
}

/* ------------------------------------------------------------------ *
 *  Value noise / fbm  (used by every texture generator)
 * ------------------------------------------------------------------ */

const NOISE_TABLE_SIZE = 256;
const NOISE_MASK = NOISE_TABLE_SIZE - 1;

function buildNoiseTable(seed) {
    const rand = mulberry32(seed);
    const table = new Float32Array(NOISE_TABLE_SIZE * NOISE_TABLE_SIZE);
    for (let i = 0; i < table.length; i++) table[i] = rand();
    return table;
}

export function createNoise2D(seed = 7) {
    const table = buildNoiseTable(seed);
    const at = (x, y) => table[((y & NOISE_MASK) << 8) | (x & NOISE_MASK)];

    return function noise(x, y) {
        const xi = Math.floor(x);
        const yi = Math.floor(y);
        const xf = x - xi;
        const yf = y - yi;
        const u = xf * xf * (3 - 2 * xf);
        const v = yf * yf * (3 - 2 * yf);
        const a = at(xi, yi);
        const b = at(xi + 1, yi);
        const c = at(xi, yi + 1);
        const d = at(xi + 1, yi + 1);
        return lerp(lerp(a, b, u), lerp(c, d, u), v);
    };
}

export function createFbm2D(seed = 7) {
    const noise = createNoise2D(seed);
    return function fbm(x, y, octaves = 4, lacunarity = 2, gain = 0.5) {
        let amp = 1;
        let freq = 1;
        let sum = 0;
        let norm = 0;
        for (let i = 0; i < octaves; i++) {
            sum += amp * noise(x * freq, y * freq);
            norm += amp;
            amp *= gain;
            freq *= lacunarity;
        }
        return sum / norm;
    };
}

/* ------------------------------------------------------------------ *
 *  Colour helpers
 * ------------------------------------------------------------------ */

export function hexToRgb(hex) {
    return { r: (hex >> 16) & 255, g: (hex >> 8) & 255, b: hex & 255 };
}

export function mixHex(a, b, t) {
    const ca = hexToRgb(a);
    const cb = hexToRgb(b);
    const r = Math.round(lerp(ca.r, cb.r, t));
    const g = Math.round(lerp(ca.g, cb.g, t));
    const bl = Math.round(lerp(ca.b, cb.b, t));
    return (r << 16) | (g << 8) | bl;
}

export function shadeHex(hex, amount) {
    const c = hexToRgb(hex);
    const f = (v) =>
        Math.round(clamp(amount > 0 ? lerp(v, 255, amount) : lerp(v, 0, -amount), 0, 255));
    return (f(c.r) << 16) | (f(c.g) << 8) | f(c.b);
}

export function cssHex(hex) {
    return '#' + hex.toString(16).padStart(6, '0');
}

export function rgbaString(hex, alpha = 1) {
    const c = hexToRgb(hex);
    return `rgba(${c.r},${c.g},${c.b},${alpha})`;
}

/* ------------------------------------------------------------------ *
 *  Geometry: rounded box
 *
 *  Replaces BoxGeometry everywhere. Real furniture has a 2-4mm radius
 *  on every edge; without it, everything catches light as a hard black
 *  line and instantly reads as "blockout / programmer art".
 * ------------------------------------------------------------------ */

/**
 * Rounded, bevelled box centred on the origin.
 * @param {number} w width  (x)
 * @param {number} h height (y)
 * @param {number} d depth  (z)
 * @param {number} r corner radius (clamped so it can never self-intersect)
 * @param {object} [opts] { curveSegments, bevelSegments, bevel }
 */
export function roundedBoxGeometry(w, h, d, r = 0.02, opts = {}) {
    const radius = Math.min(r, w / 2 - 1e-4, d / 2 - 1e-4);
    const bevel = opts.bevel !== undefined ? opts.bevel : Math.min(radius * 0.8, 0.012);
    const depth = Math.max(h - bevel * 2, 1e-4);

    const shape = roundedRectShape(w - bevel * 2, d - bevel * 2, Math.max(radius - bevel, 1e-4));
    const geo = new THREE.ExtrudeGeometry(shape, {
        depth,
        bevelEnabled: bevel > 1e-5,
        bevelThickness: bevel,
        bevelSize: bevel,
        bevelOffset: 0,
        bevelSegments: opts.bevelSegments ?? 3,
        curveSegments: opts.curveSegments ?? 4,
        steps: 1,
    });

    // Extrusion runs along +Z; stand it up along +Y and centre it.
    geo.rotateX(-Math.PI / 2);
    geo.translate(0, depth / 2 + bevel, 0);
    geo.center();
    geo.computeVertexNormals();
    return geo;
}

/** Rounded rectangle Shape centred on the origin, in the XY plane. */
export function roundedRectShape(width, height, radius) {
    const r = Math.max(Math.min(radius, width / 2, height / 2), 1e-5);
    const w = width / 2;
    const h = height / 2;
    const shape = new THREE.Shape();
    shape.moveTo(-w + r, -h);
    shape.lineTo(w - r, -h);
    shape.quadraticCurveTo(w, -h, w, -h + r);
    shape.lineTo(w, h - r);
    shape.quadraticCurveTo(w, h, w - r, h);
    shape.lineTo(-w + r, h);
    shape.quadraticCurveTo(-w, h, -w, h - r);
    shape.lineTo(-w, -h + r);
    shape.quadraticCurveTo(-w, -h, -w + r, -h);
    return shape;
}

/* ------------------------------------------------------------------ *
 *  Geometry: box-projected UVs
 *
 *  Three's default UVs on extruded walls / merged props are garbage and
 *  give wildly inconsistent texel density, which reads as "the texture
 *  is stretched". Reprojecting per-triangle from the dominant normal
 *  axis makes one material tile correctly across every surface.
 * ------------------------------------------------------------------ */

export function applyBoxUV(geometry, scale = 1, offset = new THREE.Vector2(0, 0)) {
    const pos = geometry.attributes.position;
    const nor = geometry.attributes.normal;
    if (!pos || !nor) return geometry;

    const uv = new Float32Array(pos.count * 2);
    const n = new THREE.Vector3();

    for (let i = 0; i < pos.count; i++) {
        const x = pos.getX(i);
        const y = pos.getY(i);
        const z = pos.getZ(i);
        n.set(nor.getX(i), nor.getY(i), nor.getZ(i));
        const ax = Math.abs(n.x);
        const ay = Math.abs(n.y);
        const az = Math.abs(n.z);

        let u;
        let v;
        if (ay >= ax && ay >= az) {
            // horizontal face — project on XZ
            u = x;
            v = z;
        } else if (ax >= az) {
            // faces +/-X — project on ZY
            u = z;
            v = y;
        } else {
            // faces +/-Z — project on XY
            u = x;
            v = y;
        }

        uv[i * 2] = u * scale + offset.x;
        uv[i * 2 + 1] = v * scale + offset.y;
    }

    geometry.setAttribute('uv', new THREE.BufferAttribute(uv, 2));
    geometry.setAttribute('uv1', new THREE.BufferAttribute(uv, 2));
    return geometry;
}

/** Scale existing UVs so `texelsPerMeter` stays constant. */
export function scaleUV(geometry, su, sv = su) {
    const uv = geometry.attributes.uv;
    if (!uv) return geometry;
    for (let i = 0; i < uv.count; i++) {
        uv.setXY(i, uv.getX(i) * su, uv.getY(i) * sv);
    }
    uv.needsUpdate = true;
    return geometry;
}

/* ------------------------------------------------------------------ *
 *  Geometry: walls with real openings
 *
 *  Instead of a solid box with a "door-looking decal" glued on the front,
 *  we extrude a rectangle whose Shape has actual holes punched in it.
 *  The reveal (the thickness of wall you see through the opening) is
 *  then genuine geometry, so the door frame has something to sit in.
 * ------------------------------------------------------------------ */

/**
 * @param {object} spec
 *   width, height  – wall extents (local X, local Y)
 *   thickness      – extrusion depth (local Z)
 *   openings       – [{ x, y, w, h, kind }] in wall-local coords
 *                    x = centre, y = sill height (bottom of the hole)
 */
export function wallWithOpeningsGeometry(spec) {
    const { width, height, thickness, openings = [], uvScale = 1 } = spec;
    const shape = new THREE.Shape();
    shape.moveTo(-width / 2, 0);
    shape.lineTo(width / 2, 0);
    shape.lineTo(width / 2, height);
    shape.lineTo(-width / 2, height);
    shape.lineTo(-width / 2, 0);

    for (const o of openings) {
        const hole = new THREE.Path();
        const hw = o.w / 2;
        const r = o.r || 0;
        const x0 = o.x - hw;
        const x1 = o.x + hw;
        const y0 = o.y;
        const y1 = o.y + o.h;

        if (r > 0 && o.kind === 'roundTop') {
            // arched opening (doors with a fanlight, bathroom vents…)
            hole.moveTo(x0, y0);
            hole.lineTo(x0, y1 - r);
            hole.absarc(o.x, y1 - r, r, Math.PI, 0, true);
            hole.lineTo(x1, y0);
            hole.lineTo(x0, y0);
        } else {
            hole.moveTo(x0, y0);
            hole.lineTo(x0, y1);
            hole.lineTo(x1, y1);
            hole.lineTo(x1, y0);
            hole.lineTo(x0, y0);
        }
        shape.holes.push(hole);
    }

    const geo = new THREE.ExtrudeGeometry(shape, {
        depth: thickness,
        bevelEnabled: false,
        curveSegments: 10,
        steps: 1,
    });
    geo.translate(0, 0, -thickness / 2);
    applyBoxUV(geo, uvScale);
    geo.computeVertexNormals();
    return geo;
}

/* ------------------------------------------------------------------ *
 *  Geometry: tube / pipe with proper caps (handrails, lamp stems)
 * ------------------------------------------------------------------ */

export function pipeGeometry(points, radius, radialSegments = 10) {
    const curve = new THREE.CatmullRomCurve3(points, false, 'catmullrom', 0.2);
    return new THREE.TubeGeometry(curve, Math.max(8, points.length * 6), radius, radialSegments, false);
}

/* ------------------------------------------------------------------ *
 *  Geometry: lathe from a profile (vases, lamp shades, pots)
 * ------------------------------------------------------------------ */

export function latheGeometry(profile, segments = 32) {
    const pts = profile.map((p) => new THREE.Vector2(p[0], p[1]));
    const geo = new THREE.LatheGeometry(pts, segments);
    geo.computeVertexNormals();
    return geo;
}

/* ------------------------------------------------------------------ *
 *  Scene helpers
 * ------------------------------------------------------------------ */

export function mesh(geometry, material, { cast = true, receive = true, name = '' } = {}) {
    const m = new THREE.Mesh(geometry, material);
    m.castShadow = cast;
    m.receiveShadow = receive;
    if (name) m.name = name;
    return m;
}

export function place(obj, x, y, z, rx = 0, ry = 0, rz = 0) {
    obj.position.set(x, y, z);
    obj.rotation.set(rx, ry, rz);
    return obj;
}

/** Recursively enable shadows on a subtree. */
export function setShadowRecursive(root, cast, receive) {
    root.traverse((o) => {
        if (o.isMesh) {
            o.castShadow = cast;
            o.receiveShadow = receive;
        }
    });
    return root;
}

/** Merge a list of meshes that share one material to cut draw calls. */
export function mergeMeshes(meshes) {
    const geos = [];
    let totalVerts = 0;
    for (const m of meshes) {
        const g = m.geometry.clone();
        m.updateMatrix();
        g.applyMatrix4(m.matrix);
        if (!g.attributes.uv) {
            const count = g.attributes.position.count;
            g.setAttribute('uv', new THREE.BufferAttribute(new Float32Array(count * 2), 2));
        }
        geos.push(g);
        totalVerts += g.attributes.position.count;
    }
    if (!geos.length) return null;

    let offset = 0;
    const positions = new Float32Array(totalVerts * 3);
    const normals = new Float32Array(totalVerts * 3);
    const uvs = new Float32Array(totalVerts * 2);
    const indices = [];

    for (const g of geos) {
        const p = g.attributes.position;
        const nAttr = g.attributes.normal;
        const uvAttr = g.attributes.uv;
        positions.set(p.array.subarray(0, p.count * 3), offset * 3);
        if (nAttr) normals.set(nAttr.array.subarray(0, p.count * 3), offset * 3);
        if (uvAttr) uvs.set(uvAttr.array.subarray(0, p.count * 2), offset * 2);
        if (g.index) {
            const idx = g.index.array;
            for (let i = 0; i < idx.length; i++) indices.push(idx[i] + offset);
        } else {
            for (let i = 0; i < p.count; i++) indices.push(i + offset);
        }
        offset += p.count;
        g.dispose();
    }

    const merged = new THREE.BufferGeometry();
    merged.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    merged.setAttribute('normal', new THREE.BufferAttribute(normals, 3));
    merged.setAttribute('uv', new THREE.BufferAttribute(uvs, 2));
    merged.setIndex(indices);
    merged.computeBoundingSphere();
    return merged;
}

/* ------------------------------------------------------------------ *
 *  Soft radial gradient sprite — cheap contact shadows, light pools,
 *  glows. Real-time, cheap, and (unlike a shadow map) never flickers.
 * ------------------------------------------------------------------ */

let _radialCache = null;

export function radialAlphaTexture(size = 128, power = 2.2, innerStop = 0.0) {
    if (!_radialCache) _radialCache = new Map();
    const key = `${size}:${power}:${innerStop}`;
    if (_radialCache.has(key)) return _radialCache.get(key);

    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const img = ctx.createImageData(size, size);
    const half = size / 2;

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const dx = (x + 0.5 - half) / half;
            const dy = (y + 0.5 - half) / half;
            const d = Math.sqrt(dx * dx + dy * dy);
            let a = 1 - saturate(d);
            a = Math.pow(a, power);
            if (innerStop > 0 && d < innerStop) a = 1;
            const i = (y * size + x) * 4;
            img.data[i] = 255;
            img.data[i + 1] = 255;
            img.data[i + 2] = 255;
            img.data[i + 3] = Math.round(a * 255);
        }
    }
    ctx.putImageData(img, 0, 0);

    const tex = new THREE.CanvasTexture(canvas);
    tex.colorSpace = THREE.SRGBColorSpace;
    _radialCache.set(key, tex);
    return tex;
}

let _blobCache = null;

/** Elliptical soft blob used as a fake contact/ambient-occlusion decal. */
export function blobShadowTexture(size = 128) {
    if (_blobCache) return _blobCache;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const half = size / 2;

    const g = ctx.createRadialGradient(half, half, 0, half, half, half);
    g.addColorStop(0.0, 'rgba(0,0,0,0.62)');
    g.addColorStop(0.35, 'rgba(0,0,0,0.40)');
    g.addColorStop(0.66, 'rgba(0,0,0,0.14)');
    g.addColorStop(1.0, 'rgba(0,0,0,0)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, size, size);

    _blobCache = new THREE.CanvasTexture(canvas);
    _blobCache.colorSpace = THREE.SRGBColorSpace;
    return _blobCache;
}

/**
 * A ground-projected soft shadow decal. Sized to the object's footprint,
 * darkens toward the contact point. Paired with real shadow maps it reads
 * as contact darkening rather than a floating grey disc.
 */
export function contactShadow(radiusX, radiusZ, opacity = 0.55, y = 0.004) {
    const geo = new THREE.PlaneGeometry(radiusX * 2, radiusZ * 2);
    geo.rotateX(-Math.PI / 2);
    const mat = new THREE.MeshBasicMaterial({
        map: blobShadowTexture(),
        transparent: true,
        opacity,
        depthWrite: false,
        color: 0x000000,
        blending: THREE.NormalBlending,
    });
    const m = new THREE.Mesh(geo, mat);
    m.position.y = y;
    m.renderOrder = 2;
    m.matrixAutoUpdate = false;
    return m;
}

/** Vertical soft gradient — light shafts under windows, wall wash. */
export function gradientPlane(w, h, topColor = '#ffffff', bottomColor = '#000000', opacity = 0.2) {
    const canvas = document.createElement('canvas');
    canvas.width = 4;
    canvas.height = 128;
    const ctx = canvas.getContext('2d');
    const g = ctx.createLinearGradient(0, 0, 0, 128);
    g.addColorStop(0, topColor);
    g.addColorStop(1, bottomColor);
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, 4, 128);
    const tex = new THREE.CanvasTexture(canvas);
    tex.colorSpace = THREE.SRGBColorSpace;
    const mat = new THREE.MeshBasicMaterial({
        map: tex,
        transparent: true,
        opacity,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        side: THREE.DoubleSide,
    });
    return new THREE.Mesh(new THREE.PlaneGeometry(w, h), mat);
}

/* ------------------------------------------------------------------ *
 *  Material factory with sane defaults
 * ------------------------------------------------------------------ */

export function pbr(params = {}) {
    const m = new THREE.MeshStandardMaterial({
        color: 0xffffff,
        roughness: 0.7,
        metalness: 0.0,
        ...params,
    });
    if (params.map && params.map.colorSpace === undefined) {
        params.map.colorSpace = THREE.SRGBColorSpace;
    }
    return m;
}

/* ------------------------------------------------------------------ *
 *  Misc
 * ------------------------------------------------------------------ */

/** Format metres as a tidy label for debug overlays. */
export function fmtLen(m) {
    return `${Math.round(m * 100) / 100} m`;
}

/** Rolling average, used to smooth noisy per-frame values. */
export class RollingMean {
    constructor(size = 8) {
        this.buf = new Float32Array(size);
        this.i = 0;
        this.n = 0;
    }
    push(v) {
        this.buf[this.i] = v;
        this.i = (this.i + 1) % this.buf.length;
        this.n = Math.min(this.n + 1, this.buf.length);
        return this.mean();
    }
    mean() {
        let s = 0;
        for (let k = 0; k < this.n; k++) s += this.buf[k];
        return this.n ? s / this.n : 0;
    }
}