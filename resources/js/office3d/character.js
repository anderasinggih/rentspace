import * as THREE from 'three';
import {
    roundedBoxGeometry, mesh, clamp, lerp, saturate, smoothstep, smootherstep, damp, dampAngle,
    spring, angleDelta, Rng, TAU, contactShadow, easeInOutCubic, easeOutCubic,
    easeInOutSine, setShadowRecursive,
} from './lib.js';
import { BODY } from './plan.js';
import { texMaterial, retile, fabric } from './textures.js';

/* ================================================================== *
 *  Humanoid rig
 *
 *  Real proportions (1.74 m adult), a 14-joint hierarchy, and motion
 *  driven by *distance travelled* rather than a fixed timer.  That last
 *  detail is the whole difference between "walking" and "moonwalking":
 *  the foot is planted in world space and stays there for the whole
 *  stance phase, so there is zero slip.
 * ================================================================== */

const L = {
    thigh: 0.46,
    shank: 0.42,
    foot: 0.26,
    spineLower: 0.19,
    spineUpper: 0.2,
    neck: 0.11,
    head: 0.235,
    upperArm: 0.29,
    foreArm: 0.26,
    shoulderHalf: 0.185,
    hipHalf: 0.095,
};

/* ------------------------------------------------------------------ *
 *  Two-bone IK
 * ------------------------------------------------------------------ */

/**
 * Solve hip + knee for a target expressed in hip-local space
 * (y up, z forward, x right).
 *
 * Euler order 'YZX' means the yaw that rotates the leg's sagittal plane
 * is applied *outside* the pitch, which is exactly the order this
 * decomposition needs: aim the plane at the target, then flex in it.
 */
export function solveLegIK(target, l1, l2) {
    const d = target.length();
    const dMax = l1 + l2 - 0.002;
    const dMin = Math.abs(l1 - l2) + 0.01;
    const dc = clamp(d, dMin, dMax);

    // angle between the thigh and the hip->foot line
    const cosA = (l1 * l1 + dc * dc - l2 * l2) / (2 * l1 * dc);
    const a = Math.acos(clamp(cosA, -1, 1));
    // interior knee angle -> flexion
    const cosK = (l1 * l1 + l2 * l2 - dc * dc) / (2 * l1 * l2);
    const kneeFlex = Math.PI - Math.acos(clamp(cosK, -1, 1));

    // aim the sagittal plane at the target's horizontal projection
    const yaw = Math.atan2(target.x, target.z);
    // angle of the hip->foot line from straight-down, positive = forward
    const planar = Math.hypot(target.z, target.y) || 1e-5;
    const theta = Math.atan2(target.z, -target.y);

    return {
        // rotation.x is negated because positive Rx swings a downward
        // bone *backward*; we want theta positive = forward.
        thighPitch: -(theta + a),
        thighYaw: yaw,
        kneeFlex,
        reachable: d <= l1 + l2,
        planar,
    };
}

/** Same solve, but for arms (elbow bends backward, so the sign flips). */
export function solveArmIK(target, l1, l2) {
    const d = target.length();
    const dc = clamp(d, Math.abs(l1 - l2) + 0.01, l1 + l2 - 0.002);
    const cosA = (l1 * l1 + dc * dc - l2 * l2) / (2 * l1 * dc);
    const a = Math.acos(clamp(cosA, -1, 1));
    const cosK = (l1 * l1 + l2 * l2 - dc * dc) / (2 * l1 * l2);
    const elbowFlex = Math.PI - Math.acos(clamp(cosK, -1, 1));

    const yaw = Math.atan2(target.x, target.z);
    const theta = Math.atan2(target.z, -target.y);
    // elbow points backward => +x rotation on the forearm
    return { upperPitch: -(theta + a), upperYaw: yaw, elbowFlex, reachable: d <= l1 + l2 };
}

/* ------------------------------------------------------------------ *
 *  Soft body part helpers
 * ------------------------------------------------------------------ */

/** A tapered capsule-ish limb segment. Reads far better than a box. */
function limb(len, rTop, rBot, mat, { squashZ = 1, segs = 12 } = {}) {
    const g = new THREE.Group();
    const geo = new THREE.CylinderGeometry(rTop, rBot, len, segs, 3, false);
    geo.translate(0, -len / 2, 0);
    // round the ends off so joints don't show hard caps
    const capTop = new THREE.SphereGeometry(rTop, segs, 8);
    const capBot = new THREE.SphereGeometry(rBot, segs, 8);
    const body = new THREE.Mesh(geo, mat);
    body.castShadow = true;
    body.receiveShadow = true;
    g.add(body);
    const ct = new THREE.Mesh(capTop, mat);
    g.add(ct);
    const cb = new THREE.Mesh(capBot, mat);
    cb.position.y = -len;
    cb.castShadow = true;
    g.add(cb);
    if (squashZ !== 1) g.scale.z = squashZ;
    return g;
}

/** A rounded block that deforms slightly — torso, hips, cushions. */
function softBlock(w, h, d, r, mat) {
    const m = new THREE.Mesh(roundedBoxGeometry(w, h, d, r), mat);
    m.castShadow = true;
    m.receiveShadow = true;
    return m;
}

/* ------------------------------------------------------------------ *
 *  Head
 * ------------------------------------------------------------------ */

function buildHead(skinMat, opts) {
    const g = new THREE.Group();
    g.name = 'head';

    const skullR = 0.093;
    const skull = new THREE.Mesh(new THREE.SphereGeometry(skullR, 24, 20), skinMat);
    skull.scale.set(0.94, 1.1, 1.02);
    skull.castShadow = true;
    skull.receiveShadow = true;
    g.add(skull);

    // jaw / chin so the profile isn't a ball
    const jaw = new THREE.Mesh(new THREE.SphereGeometry(skullR * 0.76, 18, 14), skinMat);
    jaw.position.set(0, -0.062, 0.018);
    jaw.scale.set(0.9, 0.78, 0.95);
    jaw.castShadow = true;
    g.add(jaw);

    // ears
    for (const sx of [-1, 1]) {
        const ear = new THREE.Mesh(new THREE.SphereGeometry(0.021, 10, 8), skinMat);
        ear.scale.set(0.42, 1.15, 0.72);
        ear.position.set(sx * 0.089, -0.006, -0.004);
        ear.castShadow = true;
        g.add(ear);
    }

    // nose
    const nose = new THREE.Mesh(new THREE.ConeGeometry(0.019, 0.045, 10), skinMat);
    nose.rotation.x = Math.PI / 2 + 0.35;
    nose.position.set(0, -0.014, 0.09);
    nose.scale.set(0.8, 1, 1.25);
    nose.castShadow = true;
    g.add(nose);

    /* --- eyes: eyeball + iris + a lid that actually closes --- */
    const eyeWhiteMat = new THREE.MeshStandardMaterial({ color: 0xf6f4f1, roughness: 0.24 });
    const irisMat = new THREE.MeshStandardMaterial({ color: opts.eyeColor || 0x3b2a1c, roughness: 0.2 });
    const pupilMat = new THREE.MeshBasicMaterial({ color: 0x0a0a0c });
    const lidMat = skinMat;

    const eyes = [];
    const lids = [];
    for (const sx of [-1, 1]) {
        const eye = new THREE.Group();
        eye.position.set(sx * 0.036, 0.012, 0.078);

        const ball = new THREE.Mesh(new THREE.SphereGeometry(0.0125, 14, 12), eyeWhiteMat);
        eye.add(ball);
        const iris = new THREE.Mesh(new THREE.SphereGeometry(0.0072, 12, 10), irisMat);
        iris.position.set(0, 0, 0.0095);
        iris.scale.z = 0.6;
        eye.add(iris);
        const pupil = new THREE.Mesh(new THREE.SphereGeometry(0.0034, 10, 8), pupilMat);
        pupil.position.set(0, 0, 0.0128);
        eye.add(pupil);

        // lid = a squashed sphere cap that scales in Y to blink
        const lid = new THREE.Mesh(new THREE.SphereGeometry(0.0148, 14, 12), lidMat);
        lid.scale.set(1, 0.34, 1);
        lid.position.set(0, 0.0035, 0.001);
        lid.castShadow = false;
        eye.add(lid);

        // brow
        const brow = new THREE.Mesh(roundedBoxGeometry(0.032, 0.0055, 0.012, 0.002), opts.browMat);
        brow.position.set(0, 0.031, 0.082);
        brow.rotation.z = -sx * 0.09;
        g.add(brow);
        eye.userData.brow = brow;

        g.add(eye);
        eyes.push(eye);
        lids.push(lid);
    }

    // mouth
    const mouth = new THREE.Mesh(
        new THREE.SphereGeometry(0.021, 14, 10),
        new THREE.MeshStandardMaterial({ color: 0xb9645e, roughness: 0.45 })
    );
    mouth.scale.set(1, 0.3, 0.34);
    mouth.position.set(0, -0.055, 0.083);
    g.add(mouth);
    const lips = new THREE.Mesh(new THREE.TorusGeometry(0.021, 0.0045, 6, 18), new THREE.MeshStandardMaterial({ color: 0xc07068, roughness: 0.5 }));
    lips.rotation.x = 0.1;
    lips.scale.set(1, 0.6, 1);
    lips.position.set(0, -0.055, 0.081);
    g.add(lips);

    /* --- hair: a shaped shell, not a box on top of the head --- */
    const hair = buildHair(opts);
    hair.position.set(0, 0.012, -0.004);
    g.add(hair);

    g.userData = {
        eyes,
        lids,
        mouth,
        lips,
        skull,
        hair,
        browL: eyes[0].userData.brow,
        browR: eyes[1].userData.brow,
        baseLidY: 0.0035,
        baseLidScale: 0.34,
    };
    return g;
}

function buildHair(opts) {
    const mat = new THREE.MeshStandardMaterial({
        color: opts.hairColor || 0x2b1a12,
        roughness: 0.62,
        metalness: 0.02,
    });
    const g = new THREE.Group();

    // scalp cap: sphere clipped to the upper/back of the skull
    const capGeo = new THREE.SphereGeometry(0.099, 26, 20, 0, TAU, 0, Math.PI * 0.62);
    const cap = new THREE.Mesh(capGeo, mat);
    cap.scale.set(0.99, 1.12, 1.05);
    cap.rotation.x = -0.16;
    cap.castShadow = true;
    cap.receiveShadow = true;
    g.add(cap);

    // volume on top
    const topGeo = new THREE.SphereGeometry(0.098, 24, 16, 0, TAU, 0, Math.PI * 0.42);
    const top = new THREE.Mesh(topGeo, mat);
    top.position.y = 0.018;
    top.scale.set(1.03, 0.7, 1.02);
    top.castShadow = true;
    g.add(top);

    if (opts.longHair) {
        // back mass falling to the shoulders
        const backGeo = new THREE.SphereGeometry(0.104, 24, 20);
        const back = new THREE.Mesh(backGeo, mat);
        back.position.set(0, -0.1, -0.036);
        back.scale.set(1.0, 1.5, 0.78);
        back.castShadow = true;
        back.receiveShadow = true;
        g.add(back);

        // side falls, with a slight inward curl at the ends
        for (const sx of [-1, 1]) {
            const side = new THREE.Mesh(backGeo, mat);
            side.position.set(sx * 0.072, -0.088, -0.012);
            side.scale.set(0.52, 1.36, 0.66);
            side.rotation.z = sx * 0.09;
            side.castShadow = true;
            g.add(side);
        }
        // a couple of loose strands for silhouette interest
        const strandMat = mat;
        for (const sx of [-1, 1]) {
            const pts = [
                new THREE.Vector3(sx * 0.085, -0.06, 0.03),
                new THREE.Vector3(sx * 0.1, -0.16, 0.045),
                new THREE.Vector3(sx * 0.088, -0.25, 0.015),
                new THREE.Vector3(sx * 0.07, -0.3, -0.03),
            ];
            const strand = new THREE.Mesh(
                new THREE.TubeGeometry(new THREE.CatmullRomCurve3(pts), 12, 0.011, 6, false),
                strandMat
            );
            strand.castShadow = true;
            g.add(strand);
        }
    } else {
        // short sides — a cropped fade wrapping the back of the skull
        for (const sx of [-1, 1]) {
            const side = new THREE.Mesh(
                new THREE.SphereGeometry(0.101, 18, 14, Math.PI * 0.34, Math.PI * 0.32, 0, Math.PI * 0.62),
                mat
            );
            side.position.set(0, -0.004, 0);
            side.scale.set(0.97, 1.06, 1.0);
            side.castShadow = true;
            g.add(side);
            void sx;
        }
        // nape
        const nape = new THREE.Mesh(new THREE.SphereGeometry(0.096, 20, 14), mat);
        nape.position.set(0, -0.036, -0.03);
        nape.scale.set(0.96, 0.62, 0.8);
        nape.castShadow = true;
        g.add(nape);
    }

    if (opts.bangs) {
        // fringe: a few overlapping flattened spheres
        const fringeMat = mat;
        for (let i = 0; i < 5; i++) {
            const t = i / 4;
            const b = new THREE.Mesh(new THREE.SphereGeometry(0.032, 12, 10), fringeMat);
            b.position.set(-0.062 + t * 0.124, 0.05, 0.072 - Math.abs(t - 0.5) * 0.03);
            b.scale.set(1.15, 0.62, 0.7);
            b.rotation.z = -0.3 + t * 0.6;
            b.castShadow = true;
            g.add(b);
        }
    }

    return g;
}

/* ------------------------------------------------------------------ *
 *  Rig assembly
 * ------------------------------------------------------------------ */

export function buildHumanoid(M, opts = {}) {
    const {
        skinColor = 0xf0c9a4,
        shirtColor = 0xf472b6,
        pantsColor = 0x33415a,
        shoeColor = 0xf5f5f5,
        hairColor = 0x2b1a12,
        longHair = false,
        bangs = true,
        eyeColor = 0x3b2a1c,
        scale = 1,
        seed = 1,
    } = opts;

    const rng = new Rng(seed);

    const skinMat = new THREE.MeshStandardMaterial({ color: skinColor, roughness: 0.62, metalness: 0 });
    const shirtMat = texMaterial(retile(fabric(shirtColor, { weave: 9, seed }), 1 / 0.22), { roughness: 0.94 });
    const shirtMatPlain = new THREE.MeshStandardMaterial({ color: shirtColor, roughness: 0.88 });
    const pantsMat = texMaterial(retile(fabric(pantsColor, { weave: 10, seed: seed + 2 }), 1 / 0.2), { roughness: 0.95 });
    const shoeMat = new THREE.MeshStandardMaterial({ color: shoeColor, roughness: 0.45 });
    const soleMat = new THREE.MeshStandardMaterial({ color: 0xe6e2da, roughness: 0.85 });
    const hairMatColor = hairColor;

    /* --- root --- */
    const root = new THREE.Group();
    root.name = 'humanoid-root';
    root.scale.setScalar(scale);

    // The whole body pivots around the pelvis; `body` carries the world
    // transform so we can bob / lean without touching the pelvis angles.
    const body = new THREE.Group();
    body.name = 'body';
    root.add(body);

    /* --- pelvis --- */
    const pelvis = new THREE.Group();
    pelvis.name = 'pelvis';
    body.add(pelvis);

    const hips = softBlock(L.hipHalf * 2.06, 0.2, 0.19, 0.07, pantsMat);
    pelvis.add(hips);
    // waistband
    const waist = new THREE.Mesh(
        new THREE.CylinderGeometry(0.115, 0.108, 0.05, 18),
        new THREE.MeshStandardMaterial({ color: shadeDownColor(pantsColor, 0.18), roughness: 0.8 })
    );
    waist.position.y = 0.115;
    waist.scale.z = 0.72;
    waist.castShadow = true;
    pelvis.add(waist);
    // belt
    const belt = new THREE.Mesh(new THREE.TorusGeometry(0.117, 0.011, 6, 22), new THREE.MeshStandardMaterial({ color: 0x2b2622, roughness: 0.5 }));
    belt.rotation.x = Math.PI / 2;
    belt.scale.z = 0.72;
    belt.position.y = 0.13;
    belt.castShadow = true;
    pelvis.add(belt);
    const buckle = new THREE.Mesh(roundedBoxGeometry(0.03, 0.024, 0.012, 0.004), new THREE.MeshStandardMaterial({ color: 0xc8b070, roughness: 0.28, metalness: 0.85 }));
    buckle.position.set(0, 0.13, 0.088);
    pelvis.add(buckle);

    /* --- spine --- */
    const spine = new THREE.Group();
    spine.name = 'spine';
    spine.position.y = 0.13;
    pelvis.add(spine);

    const lumbar = softBlock(0.19, L.spineLower + 0.03, 0.15, 0.055, shirtMatPlain);
    lumbar.position.y = L.spineLower / 2;
    spine.add(lumbar);

    const chest = new THREE.Group();
    chest.name = 'chest';
    chest.position.y = L.spineLower;
    spine.add(chest);

    const ribs = softBlock(0.235, L.spineUpper + 0.02, 0.16, 0.06, shirtMatPlain);
    ribs.position.y = L.spineUpper / 2;
    ribs.castShadow = true;
    chest.add(ribs);

    // shoulder yoke — a little wider than the ribs, so shoulders read square
    const yoke = softBlock(0.365, 0.075, 0.16, 0.05, shirtMatPlain);
    yoke.position.y = L.spineUpper - 0.02;
    yoke.castShadow = true;
    chest.add(yoke);

    // collar
    const collar = new THREE.Mesh(
        new THREE.TorusGeometry(0.052, 0.014, 8, 20),
        new THREE.MeshStandardMaterial({ color: shadeDownColor(shirtColor, 0.12), roughness: 0.85 })
    );
    collar.rotation.x = Math.PI / 2;
    collar.position.y = L.spineUpper - 0.005;
    collar.castShadow = true;
    chest.add(collar);

    // chest volume for the female silhouette (optional)
    if (opts.bust) {
        for (const sx of [-1, 1]) {
            const b = new THREE.Mesh(new THREE.SphereGeometry(0.058, 16, 12), shirtMatPlain);
            b.position.set(sx * 0.052, L.spineUpper * 0.44, 0.062);
            b.scale.set(1.0, 0.95, 0.72);
            b.castShadow = true;
            chest.add(b);
        }
    }

    /* --- neck + head --- */
    const neck = new THREE.Group();
    neck.name = 'neck';
    neck.position.y = L.spineUpper + 0.012;
    chest.add(neck);

    const neckMesh = limb(L.neck + 0.03, 0.038, 0.042, skinMat, { segs: 12 });
    neckMesh.position.y = L.neck + 0.03;
    neck.add(neckMesh);

    const head = new THREE.Group();
    head.name = 'head';
    head.position.y = L.neck + 0.045;
    neck.add(head);

    const headMesh = buildHead(
        skinMat,
        { hairColor: hairMatColor, longHair, bangs, eyeColor, browMat: new THREE.MeshStandardMaterial({ color: shadeDownColor(hairColor, 0.1), roughness: 0.7 }) }
    );
    head.add(headMesh);

    /* --- arms --- */
    const arms = {};
    for (const side of ['L', 'R']) {
        const sx = side === 'L' ? -1 : 1;
        const shoulder = new THREE.Group();
        shoulder.name = 'shoulder-' + side;
        shoulder.position.set(sx * L.shoulderHalf, L.spineUpper - 0.035, 0);
        chest.add(shoulder);

        const deltoid = new THREE.Mesh(new THREE.SphereGeometry(0.048, 14, 12), shirtMatPlain);
        deltoid.castShadow = true;
        shoulder.add(deltoid);

        const upper = new THREE.Group();
        upper.name = 'upperarm-' + side;
        upper.rotation.order = 'YZX';
        shoulder.add(upper);

        const upperMesh = limb(L.upperArm, 0.044, 0.036, shirtMatPlain);
        upperMesh.scale.z = 0.95;
        upper.add(upperMesh);
        // short sleeve cuff
        const cuff = new THREE.Mesh(new THREE.CylinderGeometry(0.043, 0.041, 0.03, 14), shirtMatPlain);
        cuff.position.y = -L.upperArm + 0.015;
        cuff.castShadow = true;
        upper.add(cuff);

        const fore = new THREE.Group();
        fore.name = 'forearm-' + side;
        fore.position.y = -L.upperArm;
        fore.rotation.order = 'YZX';
        upper.add(fore);

        const foreMesh = limb(L.foreArm, 0.034, 0.027, skinMat);
        foreMesh.scale.z = 0.94;
        fore.add(foreMesh);
        const wrist = new THREE.Mesh(new THREE.SphereGeometry(0.028, 12, 10), skinMat);
        wrist.position.y = -L.foreArm;
        fore.add(wrist);

        const hand = new THREE.Group();
        hand.name = 'hand-' + side;
        hand.position.y = -L.foreArm;
        fore.add(hand);

        const palm = softBlock(0.048, 0.088, 0.026, 0.012, skinMat);
        palm.position.y = -0.042;
        hand.add(palm);
        // fingers as one tapered block plus a thumb — enough at this scale
        const fingers = softBlock(0.044, 0.062, 0.022, 0.01, skinMat);
        fingers.position.set(0, -0.105, 0.002);
        fingers.scale.x = 0.95;
        hand.add(fingers);
        const thumb = limb(0.05, 0.012, 0.01, skinMat, { segs: 8 });
        thumb.position.set(sx * -0.02, -0.045, 0.012);
        thumb.rotation.z = sx * 0.9;
        hand.add(thumb);

        // The grip is faked by curling the whole finger block about the
        // knuckle line and swinging the thumb in. At the scale this is
        // viewed a real finger rig would buy nothing, and this keeps the
        // prop-reading hand readable when it wraps a mug or a controller.
        const knuckle = new THREE.Group();
        knuckle.position.set(0, -0.076, 0);
        hand.add(knuckle);
        hand.remove(fingers);
        knuckle.add(fingers);
        fingers.position.set(0, -0.029, 0.002);

        arms[side] = { shoulder, upper, fore, hand, knuckle, thumb, sx };
    }

    /* --- legs --- */
    const legs = {};
    for (const side of ['L', 'R']) {
        const sx = side === 'L' ? -1 : 1;
        const hip = new THREE.Group();
        hip.name = 'hip-' + side;
        hip.position.set(sx * L.hipHalf, -0.055, 0);
        hip.rotation.order = 'YZX';
        pelvis.add(hip);

        const thighMesh = limb(L.thigh, 0.078, 0.058, pantsMat);
        thighMesh.scale.z = 0.96;
        hip.add(thighMesh);

        const knee = new THREE.Group();
        knee.name = 'knee-' + side;
        knee.position.y = -L.thigh;
        knee.rotation.order = 'YZX';
        hip.add(knee);

        const kneeCap = new THREE.Mesh(new THREE.SphereGeometry(0.056, 12, 10), pantsMat);
        kneeCap.scale.z = 0.95;
        knee.add(kneeCap);

        const shinMesh = limb(L.shank, 0.056, 0.036, pantsMat);
        shinMesh.scale.z = 0.94;
        knee.add(shinMesh);
        // trouser hem
        const hem = new THREE.Mesh(new THREE.CylinderGeometry(0.042, 0.038, 0.03, 12), pantsMat);
        hem.position.y = -L.shank + 0.015;
        knee.add(hem);

        const ankle = new THREE.Group();
        ankle.name = 'ankle-' + side;
        ankle.position.y = -L.shank;
        ankle.rotation.order = 'YZX';
        knee.add(ankle);

        // shoe: sole + upper + heel counter
        const shoe = new THREE.Group();
        shoe.name = 'shoe';
        ankle.add(shoe);

        const sole = softBlock(0.088, 0.026, L.foot, 0.012, soleMat);
        sole.position.set(0, -0.014, 0.028);
        shoe.add(sole);

        const upper2 = softBlock(0.082, 0.058, 0.2, 0.024, shoeMat);
        upper2.position.set(0, 0.026, 0.006);
        shoe.add(upper2);

        const toe = new THREE.Mesh(new THREE.SphereGeometry(0.042, 14, 12), shoeMat);
        toe.position.set(0, 0.014, 0.078);
        toe.scale.set(1.0, 0.72, 0.8);
        toe.castShadow = true;
        shoe.add(toe);

        const heel = new THREE.Mesh(new THREE.SphereGeometry(0.043, 14, 12), shoeMat);
        heel.position.set(0, 0.03, -0.062);
        heel.scale.set(0.95, 0.95, 0.75);
        heel.castShadow = true;
        shoe.add(heel);

        const collarCuff = new THREE.Mesh(new THREE.CylinderGeometry(0.044, 0.04, 0.05, 12), shoeMat);
        collarCuff.position.set(0, 0.058, -0.048);
        collarCuff.castShadow = true;
        shoe.add(collarCuff);

        legs[side] = { hip, knee, ankle, shoe, sx };
    }

    /* --- contact shadow (follows the body, not each foot) --- */
    const blob = contactShadow(0.42, 0.42, 0.5, 0.006);
    root.add(blob);

    root.userData = {
        body, pelvis, spine, chest, neck, head, headMesh,
        arms, legs, blob,
        skinMat, shirtMat, pantsMat, shoeMat,
    };
    return root;
}

function shadeDownColor(hex, amount = 0.1) {
    const f = (v) => Math.round(clamp(v * (1 - amount), 0, 255));
    return (f((hex >> 16) & 255) << 16) | (f((hex >> 8) & 255) << 8) | f(hex & 255);
}

/* ================================================================== *
 *  Controller — poses, gait, idle life
 * ================================================================== */

export class CharacterController {
    constructor(root, opts = {}) {
        this.root = root;
        this.rng = new Rng(opts.seed || 7);
        const ud = root.userData;

        this.pelvis = ud.pelvis;
        this.spine = ud.spine;
        this.chest = ud.chest;
        this.neck = ud.neck;
        this.head = ud.head;
        this.headMesh = ud.headMesh;
        this.arms = ud.arms;
        this.legs = ud.legs;
        this.blob = ud.blob;

        /* --- world transform --- */
        this.pos = new THREE.Vector3(0, 0, 0); // ground contact point (feet)
        this.facing = 0;
        this.targetFacing = 0;
        this.speed = 0;
        this.targetSpeed = 0;
        // NOTE: the rig is solved entirely in root-local units. Any non-1
        // root.scale is applied by three.js on top, so the solver must NOT
        // multiply its lengths by it (that would scale twice).

        /* --- gait --- */
        this.strideLength = 0.72;
        this.stepDistance = 0; // distance since last footfall
        this.stepSide = 'L';
        this.walkBlend = 0; // 0 idle … 1 walking
        this.speedSmoothed = 0;

        this.feet = {
            L: this._makeFootState('L'),
            R: this._makeFootState('R'),
        };

        /* --- idle noise state (all organic, none periodic-looking) --- */
        this.idleSeed = this.rng.float(0, 100);
        this.breathPhase = this.rng.float(0, TAU);
        this.swayPhase = this.rng.float(0, TAU);
        this.gazeTarget = new THREE.Vector3(0, 1.5, 2);
        this.gaze = new THREE.Vector3(0, 1.5, 2);
        this.headYaw = 0;
        this.headPitch = 0;
        this.headYawVel = 0;
        this.headPitchVel = 0;

        /* --- blink --- */
        this.blink = { t: this.rng.float(0.5, 3), phase: 'open', queue: 0 };

        /* --- pose --- */
        this.pose = 'idle'; // see POSES below
        this.poseBlend = 0;
        this.actionT = 0;
        this.actionTimer = this.rng.float(4, 9);

        /* --- arm / hand scratch --- */
        this._poseState = {
            tL: new THREE.Vector3(),
            tR: new THREE.Vector3(),
            out: 0.12,
            gripL: 0,
            gripR: 0,
            wristL: 0,
            wristR: 0,
        };
        this._outTarget = 0.12;

        /* --- world-space hand overrides, set per frame by activities --- */
        this.reach = { L: null, R: null };
        this.held = { L: null, R: null };

        /* --- smoothed joint angles (springs) --- */
        this.j = {
            spinePitch: 0, spineYaw: 0, spineRoll: 0,
            chestPitch: 0, chestYaw: 0, chestRoll: 0,
            neckPitch: 0, neckYaw: 0, neckRoll: 0,
            pelvisY: 0, pelvisRoll: 0, pelvisYaw: 0,
        };
        this.jv = { ...this.j };

        this._tmp = new THREE.Vector3();
        this._tmp2 = new THREE.Vector3();
        this._q = new THREE.Quaternion();
        this._inverse = new THREE.Matrix4();

        // smoothed upper-body yaw, read by the pelvis counter-rotation.
        // must start defined or the very first frame produces NaN.
        this._chestYaw = 0;

        // set whenever the feet must be re-planted against the ground
        this._needsStanceReset = true;

        this._placeFeetUnderBody(0);
        this._needsStanceReset = false;
    }

    _makeFootState(side) {
        const s = side === 'L' ? -1 : 1;
        return {
            side,
            s,
            planted: new THREE.Vector3(),
            target: new THREE.Vector3(),
            swinging: false,
            swingT: 0,
            swingFrom: new THREE.Vector3(),
            swingTo: new THREE.Vector3(),
            lift: 0.045,
            roll: 0,
            contact: 1, // 1 = fully planted
            y: 0,
            lastPlantPos: new THREE.Vector3(),
        };
    }

    /* -------------------- external API -------------------- */

    setPosition(x, z, facing = this.facing) {
        this.pos.set(x, 0, z);
        this.facing = facing;
        this.targetFacing = facing;
        this._placeFeetUnderBody(0);
        this._needsStanceReset = true;
        this.walkTarget = null;
    }

    lookAt(x, y, z) {
        this.gazeTarget.set(x, y, z);
    }

    setPose(pose) {
        if (this.pose === pose) return;
        const leavingWalk = this.pose === 'walk';
        this.pose = pose;
        this.actionT = 0;
        // coming out of a walk, snap the stance back under the body so the
        // character doesn't stand on a stretched-out foot
        if (leavingWalk || pose === 'sleep' || CharacterController.SEATED.has(pose)) {
            this._needsStanceReset = true;
        }
        if (pose !== 'walk') this.walkTarget = null;
        if (pose === 'sleep') {
            // snap into the bed pose so nothing lerps through the floor
            this._applySleepPose();
        }
    }

    /** Request a walk; the controller handles the stepping itself. */
    walkTo(x, z) {
        this.walkTarget = { x, z };
        this.targetSpeed = 1.15;
        const dx = x - this.pos.x;
        const dz = z - this.pos.z;
        if (dx * dx + dz * dz > 0.04) this.targetFacing = Math.atan2(dx, dz);
        // A walk request implies the walk state. Without this the controller
        // would stay in idle and the idle branch would re-place the feet every
        // frame, which reads as a slow foot slide.
        if (this.pose !== 'walk') {
            this.pose = 'walk';
            this.actionT = 0;
            this.actionTimer = this.rng.float(4, 9);
            // coming out of sleep/seated, the feet must be recovered to a
            // standing stance before the first step is measured
            this._needsStanceReset = true;
        }
    }

    stopWalking() {
        this.walkTarget = null;
        this.targetSpeed = 0;
    }

    get isWalking() {
        return this.speedSmoothed > 0.12;
    }

    /* -------------------- hands -------------------- */

    /**
     * Parent a prop into a hand so it travels with the arm. `offset` and
     * `rotation` are in hand-local space, where -Y runs down the fingers.
     * This is how a mug, a controller or a phone becomes something the
     * character is actually holding rather than something floating nearby.
     */
    attachToHand(side, object, { offset = [0, -0.07, 0.02], rotation = [0, 0, 0] } = {}) {
        this.detachFromHand(side, object);
        const arm = this.arms[side];
        if (!arm) return null;
        object.position.set(offset[0], offset[1], offset[2]);
        object.rotation.set(rotation[0], rotation[1], rotation[2]);
        arm.hand.add(object);
        this.held[side] = object;
        object.userData.heldBy = this;
        return object;
    }

    detachFromHand(side, object) {
        const arm = this.arms[side];
        if (!arm) return;
        const target = object || this.held[side];
        if (!target) return;
        if (target.parent) target.parent.remove(target);
        this.held[side] = null;
        target.userData.heldBy = null;
        void arm;
    }

    detachAll() {
        this.detachFromHand('L');
        this.detachFromHand('R');
    }

    /**
     * Aim one hand at a point in world space for one frame. The arm solve
     * converts it into shoulder-local space, so activities can name a real
     * object ("the fridge handle") instead of guessing joint angles.
     * Pass null to hand control back to the pose.
     */
    setReach(side, worldVec) {
        this.reach[side] = worldVec;
    }

    clearReach() {
        this.reach.L = null;
        this.reach.R = null;
    }

    /* -------------------- seating -------------------- */

    /**
     * Sit at a seat. The character keeps its world position but the seated
     * pelvis height and a forward root offset put the hips on the cushion;
     * `yOffset` walks the root the last stretch so a character can step in
     * from the approach point instead of teleporting onto the sofa.
     */
    sitAt(x, z, rotY) {
        this.pos.set(x, 0, z);
        this.facing = rotY;
        this.targetFacing = rotY;
        this._needsStanceReset = true;
        this.walkTarget = null;
    }

    /* -------------------- main update -------------------- */

    /** Poses that keep the hips on a seat rather than on the floor. */
    static SEATED = new Set([
        'sit', 'type', 'read', 'talk', 'watch', 'game',
    ]);

    /** Poses the body is upright for but the hands are doing a task. */
    static STANDING_TASK = new Set([
        'stir', 'pour', 'wash', 'phone', 'stretch', 'lean', 'think', 'hold', 'sip',
    ]);

    update(dt, time) {
        dt = Math.min(dt, 1 / 30);
        this.actionT += dt;

        switch (this.pose) {
            case 'sleep':
                this._updateSleep(dt, time);
                break;
            case 'walk':
                this._updateWalk(dt, time);
                break;
            default:
                if (CharacterController.SEATED.has(this.pose)) this._updateSeated(dt, time);
                else this._updateIdle(dt, time);
                break;
        }

        this._updateBlink(dt);
        this._updateBlob();
    }

    /* -------------------- locomotion -------------------- */

    _updateWalk(dt, time) {
        // Recover a clean standing stance on the first walking frame.
        if (this._needsStanceReset) {
            this._placeFeetUnderBody(0);
            this._needsStanceReset = false;
            this.stepDistance = 0;
        }

        const speedTarget = this.walkTarget ? this.targetSpeed : 0;
        this.speed = damp(this.speed, speedTarget, 6.5, dt);

        // arrived?
        if (this.walkTarget) {
            const dx = this.walkTarget.x - this.pos.x;
            const dz = this.walkTarget.z - this.pos.z;
            const dist = Math.hypot(dx, dz);
            if (dist < 0.12 || this.speed < 0.05) {
                this.walkTarget = null;
                this.targetSpeed = 0;
            } else {
                this.targetFacing = Math.atan2(dx, dz);
            }
        }

        // ease out the last few centimetres so it doesn't stop dead
        let speed = this.speed;
        if (this.walkTarget) {
            const dist = Math.hypot(this.walkTarget.x - this.pos.x, this.walkTarget.z - this.pos.z);
            speed *= smoothstep(0.05, 0.55, dist);
        }
        this.speedSmoothed = damp(this.speedSmoothed, speed, 8, dt);

        // ---- advance along the facing direction ----
        const dirX = Math.sin(this.facing);
        const dirZ = Math.cos(this.facing);
        this.pos.x += dirX * this.speedSmoothed * dt;
        this.pos.z += dirZ * this.speedSmoothed * dt;

        // turn before moving much, so the body doesn't crab sideways
        this.facing = dampAngle(this.facing, this.targetFacing, 7.5, dt);
        this.walkBlend = damp(this.walkBlend, saturate(this.speedSmoothed / 0.5), 6, dt);

        // ---- step events driven by DISTANCE, not time -> no foot slide ----
        this.stepDistance += this.speedSmoothed * dt;
        const halfStride = this.strideLength * 0.5;
        if (this.stepDistance >= halfStride) {
            this.stepDistance -= halfStride;
            this._beginSwing(this.stepSide === 'L' ? 'R' : 'L');
            this.stepSide = this.stepSide === 'L' ? 'R' : 'L';
        }

        this._advanceSwing(dt);
        this._solveBody(dt, time, true);
    }

    _beginSwing(side) {
        const foot = this.feet[side];
        const stance = this.feet[side === 'L' ? 'R' : 'L'];

        // Where should this foot land? A little ahead of the body,
        // offset to its own side. Lateral offset uses the hip half-width
        // plus a small stance width — wide enough to look balanced.
        const dirX = Math.sin(this.facing);
        const dirZ = Math.cos(this.facing);
        const sideX = Math.cos(this.facing); // right vector
        const sideZ = -Math.sin(this.facing);

        const lead = this.speedSmoothed > 0.2 ? halfStride_(this) : 0.12;
        const lateral = 0.105 + (side === 'L' ? -1 : 1) * 0.0;

        const targetX = this.pos.x + dirX * lead + sideX * 0.0;
        const targetZ = this.pos.z + dirZ * lead + sideZ * 0.0;

        foot.swingFrom.copy(foot.planted);
        foot.swingTo.set(
            targetX + sideX * (side === 'L' ? -0.105 : 0.105),
            0,
            targetZ + sideZ * (side === 'L' ? -0.105 : 0.105)
        );

        // if the stance foot has barely moved (character standing still and
        // a step was forced), just keep the foot where it is
        if (foot.swingTo.distanceTo(foot.swingFrom) < 0.05) {
            foot.swingTo.copy(foot.planted);
        }

        foot.swinging = true;
        foot.swingT = 0;
        foot.lift = 0.035 + this.speedSmoothed * 0.045;
        foot.roll = 0;
        void stance;
        void lateral;
    }

    _advanceSwing(dt) {
        // Swing duration follows stride length so the foot covers the same
        // ground distance in the same time it takes to walk it.
        const swingTime = clamp(halfStride_(this) / Math.max(this.speedSmoothed, 0.25), 0.16, 0.5);

        for (const side of ['L', 'R']) {
            const foot = this.feet[side];
            if (!foot.swinging) continue;
            foot.swingT += dt / swingTime;
            if (foot.swingT >= 1) {
                foot.swingT = 1;
                foot.swinging = false;
                foot.contact = 1;
                foot.planted.copy(foot.swingTo);
                // heel strike: toe is up slightly, then rolls flat
                foot.roll = 0.16;
            }
        }
    }

    _placeFeetUnderBody(phaseOffset) {
        const dirX = Math.sin(this.facing);
        const dirZ = Math.cos(this.facing);
        const sideX = Math.cos(this.facing);
        const sideZ = -Math.sin(this.facing);
        for (const [side, foot] of Object.entries(this.feet)) {
            const s = foot.s;
            const ahead = (side === 'L' ? phaseOffset : -phaseOffset) * 0.18;
            foot.planted.set(
                this.pos.x + dirX * ahead + sideX * s * 0.105,
                0,
                this.pos.z + dirZ * ahead + sideZ * s * 0.105
            );
            foot.target.copy(foot.planted);
            foot.swinging = false;
            foot.swingT = 1;
            foot.contact = 1;
        }
    }

    /* -------------------- standing / idle -------------------- */

    _updateIdle(dt, time) {
        this.speedSmoothed = damp(this.speedSmoothed, 0, 8, dt);
        this.walkBlend = damp(this.walkBlend, 0, 6, dt);
        this.facing = dampAngle(this.facing, this.targetFacing, 4.5, dt);

        // Occasionally the character decides to do something, then returns
        // to idle. This is what removes the "perfect loop" feel — there is
        // no loop, just a sequence of decisions.
        this.actionTimer -= dt;
        if (this.actionTimer <= 0 && this.pose === 'idle') {
            this.actionTimer = this.rng.float(4.5, 11);
            this.action = this.rng.pick(['shift', 'glance', 'stretch', 'handOnHip', 'none', 'none']);
            this.actionT = 0;
        }

        // Feet are only re-planted when the stance actually changes. Standing
        // still must NOT nudge the soles, or the contact reads as a slide.
        if (this._needsStanceReset) {
            this._placeFeetUnderBody(0);
            this._needsStanceReset = false;
        }

        this._solveBody(dt, time, false);
    }

    /* -------------------- seated poses -------------------- */

    _updateSeated(dt, time) {
        this.speedSmoothed = damp(this.speedSmoothed, 0, 8, dt);
        this.walkBlend = damp(this.walkBlend, 0, 6, dt);
        this.facing = dampAngle(this.facing, this.targetFacing, 6, dt);

        // Seated feet are LOCKED to the chair position: they never slide, and
        // they are only snapped once when the seated pose is entered.
        if (this._needsStanceReset || this.poseBlend < 0.5) {
            const dirX = Math.sin(this.facing);
            const dirZ = Math.cos(this.facing);
            const sideX = Math.cos(this.facing);
            const sideZ = -Math.sin(this.facing);
            for (const foot of Object.values(this.feet)) {
                foot.planted.set(
                    this.pos.x + dirX * 0.36 + sideX * foot.s * 0.1,
                    0,
                    this.pos.z + dirZ * 0.36 + sideZ * foot.s * 0.1
                );
                foot.target.copy(foot.planted);
                foot.swinging = false;
                foot.swingT = 1;
                foot.contact = 1;
                foot.lift = 0;
            }
            this._needsStanceReset = false;
        }

        this._solveBody(dt, time, false);
    }

    /* -------------------- sleeping -------------------- */

    _applySleepPose() {
        const ud = this.root.userData;
        // lying on the side is done by rotating the BODY, so the rig stays
        // in a normal standing configuration and the animation is simple
        ud.body.rotation.x = -Math.PI / 2;
        ud.body.position.y = 0;
        this.pelvis.rotation.set(0, 0, 0.12);
        this.spine.rotation.set(0.1, 0, 0);
        this.chest.rotation.set(0.06, 0, 0);
        this.neck.rotation.set(0.14, 0.12, 0);

        for (const side of ['L', 'R']) {
            const leg = this.legs[side];
            leg.hip.rotation.set(-1.15, 0, side === 'L' ? -0.18 : 0.12);
            leg.knee.rotation.set(1.5, 0, 0);
            leg.ankle.rotation.set(-0.2, 0, 0);
            const arm = this.arms[side];
            arm.upper.rotation.set(-0.85, 0, side === 'L' ? -0.5 : 0.42);
            arm.fore.rotation.set(1.15, 0, 0);
        }
    }

    _updateSleep(dt, time) {
        const ud = this.root.userData;

        // breathing: slow, deep, and never perfectly periodic
        const b = Math.sin(time * 0.72 + this.idleSeed) * 0.5 + 0.5;
        const b2 = Math.sin(time * 0.31 + this.idleSeed * 1.7) * 0.5 + 0.5;
        ud.chest.scale.set(1 + b * 0.018, 1 + b * 0.012, 1 + b * 0.03);

        this.pelvis.rotation.z = 0.12 + Math.sin(time * 0.24) * 0.01;
        this.chest.rotation.x = 0.06 + b * 0.03;
        this.neck.rotation.x = 0.14 - b2 * 0.05 + b * 0.02;
        this.neck.rotation.y = 0.12 + Math.sin(time * 0.17 + 1.1) * 0.05;

        // tiny settling shifts
        const shift = Math.sin(time * 0.19 + 2.2) * 0.02;
        this.arms.L.upper.rotation.z = -0.5 + shift;
        this.arms.R.upper.rotation.z = 0.42 - shift;
        this.arms.L.upper.rotation.x = -0.85 + b * 0.04;

        this._updateBlink(dt);
    }

    /* ================================================================== *
     *  Core solver — places the pelvis, runs leg IK, then layers the
     *  upper body on top. This is shared by every pose.
     * ================================================================== */

    _solveBody(dt, time, walking) {
        const ud = this.root.userData;
        const j = this.j;
        const jv = this.jv;

        /* ---------- 1. foot world positions ---------- */
        const dirX = Math.sin(this.facing);
        const dirZ = Math.cos(this.facing);
        const sideX = Math.cos(this.facing);
        const sideZ = -Math.sin(this.facing);

        for (const foot of Object.values(this.feet)) {
            if (foot.swinging) {
                const t = easeInOutSine(foot.swingT);
                foot.planted.lerpVectors(foot.swingFrom, foot.swingTo, t);
                // vertical arc — high at mid-swing, and slightly asymmetric
                // (fast lift, softer landing) like a real step
                const arc = Math.sin(foot.swingT * Math.PI);
                foot.planted.y = arc * foot.lift * (1 + Math.sin(foot.swingT * Math.PI) * 0.15);
                foot.contact = 0;
            } else {
                // STANCE: the planted position is authoritative and does
                // not move at all. Zero slide, by construction.
                foot.planted.y = 0;
                foot.contact = 1;
            }
            // heel-strike roll, easing to flat over ~0.14 s
            foot.roll = damp(foot.roll, 0, 9, dt);
        }

        const footL = this.feet.L.planted;
        const footR = this.feet.R.planted;

        /* ---------- 2. pelvis placement ---------- */
        // mid-point of the feet, biased forward so the body leads the feet
        // slightly (that bias is what stops a "moonwalker" look)
        let midX = (footL.x + footR.x) * 0.5 + dirX * 0.02;
        let midZ = (footL.z + footR.z) * 0.5 + dirZ * 0.02;

        // lateral sway: hips swing toward the *stance* leg, twice per stride
        const stanceBias = footL.contact > footR.contact ? -1 : 1;
        const swayAmt = this.walkBlend * 0.022;
        midX += sideX * stanceBias * swayAmt;
        midZ += sideZ * stanceBias * swayAmt;

        // vertical bob: lowest at mid-stance, highest at push-off
        let bobTarget = 0;
        if (walking) {
            const phase = (this.stepDistance / Math.max(halfStride_(this), 1e-3)) * Math.PI;
            bobTarget = -Math.abs(Math.cos(phase)) * 0.022 * this.walkBlend;
        } else {
            bobTarget = 0;
        }
        // idle "settle" — a very slow vertical drift so a standing person
        // isn't perfectly still
        const idleBob = (1 - this.walkBlend) * Math.sin(time * 0.42 + this.idleSeed) * 0.006;

        const standingHipH = BODY.hipH;
        const seatedHipH = BODY.seatH + 0.055;

        let pelvisY = lerp(seatedHipH, standingHipH, this._standAmount());
        pelvisY += bobTarget + idleBob;

        // Pelvis roll: torso leans away from the swing leg
        const pelvisRollTarget =
            -stanceBias * 0.055 * this.walkBlend +
            (1 - this.walkBlend) * Math.sin(time * 0.23 + this.idleSeed) * 0.02;

        [j.pelvisY, jv.pelvisY] = spring(j.pelvisY, jv.pelvisY, pelvisY, 220, 26, dt);
        [j.pelvisRoll, jv.pelvisRoll] = spring(j.pelvisRoll, jv.pelvisRoll, pelvisRollTarget, 130, 21, dt);

        // pelvis yaw counter-rotates against the chest
        const pelvisYawTarget = -this._chestYaw * 0.4 + (walking ? 0 : Math.sin(time * 0.19) * 0.03);
        [j.pelvisYaw, jv.pelvisYaw] = spring(j.pelvisYaw, jv.pelvisYaw, pelvisYawTarget, 90, 17, dt);

        /* ---------- 3. pelvis transform (root-local) ---------- */
        // The root carries the world placement and the facing; the pelvis is
        // a child of it and only ever holds the height + pelvis rotations.
        // Solving everything in root-local space keeps the leg IK from
        // accumulating an extra frame of offset.
        this.root.position.set(0, 0, 0);
        this.root.rotation.set(0, this.facing, 0);
        this.pelvis.position.set(0, j.pelvisY, 0);
        this.pelvis.rotation.set(0, j.pelvisYaw, j.pelvisRoll);

        /* ---------- 4. clamp pelvis height so the legs stay reachable --- */
        this._clampPelvisHeight(footL, footR);

        /* ---------- 5. leg IK ---------- */
        this.root.updateMatrixWorld(true);
        for (const side of ['L', 'R']) {
            const leg = this.legs[side];
            const foot = this.feet[side];

            // target in the hip's local space (root scale is handled by
            // worldToLocal, so every offset below is in rig units)
            const local = this._tmp.copy(foot.planted);
            leg.hip.worldToLocal(local);
            // the ankle joint should sit above the sole, not on it
            local.y += 0.075;
            local.z -= 0.012; // ankle sits behind the toe

            const sol = solveLegIK(local, L.thigh, L.shank);
            leg.hip.rotation.set(sol.thighPitch, sol.thighYaw, 0);
            leg.knee.rotation.set(sol.kneeFlex, 0, 0);

            // ankle: keep the sole flat, with heel-strike roll
            const dorsiflex = foot.swinging
                ? -0.42 * Math.sin(foot.swingT * Math.PI) + 0.1
                : foot.roll;
            leg.ankle.rotation.set(
                -(sol.thighPitch + sol.kneeFlex) + dorsiflex,
                0,
                0
            );
        }

        /* ---------- 6. spine / chest ---------- */
        this._updateUpperBody(dt, time, walking, stanceBias);

        /* ---------- 7. root placement ---------- */
        // The root lands exactly on the ground contact point we derived from
        // the planted feet, so the character can never drift away from them.
        // The pelvis keeps its clamped local height from step 4.
        ud.body.position.set(0, 0, 0);
        ud.body.rotation.set(0, 0, 0);
        this.root.position.set(midX, 0, midZ);
        this.root.rotation.set(0, this.facing, 0);
    }

    /** How much "standing" (vs seated) the current pose is. */
    _standAmount() {
        if (CharacterController.SEATED.has(this.pose)) return 0;
        if (this.pose === 'talk') return 0.12;
        return 1;
    }

    _clampPelvisHeight(footL, footR) {
        // Lower the pelvis until both ankles are within reach of their hip.
        // Everything here is root-local: the root is already at the origin
        // with only a yaw, so world -> local is a single rotation.
        const usable = (L.thigh + L.shank) * 0.985;

        let limit = Infinity;
        for (const [side, foot] of [['L', footL], ['R', footR]]) {
            // world -> root-local (inverse yaw about Y)
            const c = Math.cos(-this.facing);
            const s = Math.sin(-this.facing);
            const lx = foot.x * c + foot.z * s;
            const lz = -foot.x * s + foot.z * c;

            const hipX = (side === 'L' ? -1 : 1) * L.hipHalf;
            const horiz = Math.hypot(lx - hipX, lz);

            if (horiz > usable) {
                // ankle is genuinely out of reach horizontally — nothing the
                // pelvis height can fix, so leave the springs alone
                return;
            }
            const dyMax = Math.sqrt(Math.max(usable * usable - horiz * horiz, 0));
            limit = Math.min(limit, dyMax);
        }

        const maxY = Math.max(limit, 0.22);
        if (this.pelvis.position.y > maxY) this.pelvis.position.y = maxY;
    }

    _updateUpperBody(dt, time, walking, stanceBias) {
        const j = this.j;
        const jv = this.jv;

        const t = time;
        const noise = (a, b) => Math.sin(t * a + this.idleSeed * b);

        /* ---------- breathing ---------- */
        // rate rises a little with exertion; amplitude too
        const breathRate = lerp(0.62, 1.5, saturate(this.speedSmoothed));
        const breath = Math.sin(t * breathRate * TAU * 0.28 + this.idleSeed) * 0.5 + 0.5;
        const breathDepth = lerp(0.011, 0.02, saturate(this.speedSmoothed));

        const chest = this.root.userData.chest;
        chest.scale.set(1 + breath * 0.012, 1 + breath * 0.008, 1 + breath * breathDepth);

        /* ---------- posture targets ---------- */
        let spinePitch = 0.03;
        let chestPitch = 0.0;
        let chestYaw = 0;
        let neckPitch = 0;

        if (walking) {
            // slight forward lean that increases with speed, plus a small
            // vertical oscillation driven by the step
            const phase = (this.stepDistance / Math.max(halfStride_(this), 1e-3)) * TAU;
            spinePitch = 0.055 + saturate(this.speedSmoothed / 1.4) * 0.055 + Math.sin(phase) * 0.012;
            chestYaw = Math.sin(phase) * 0.075 * this.walkBlend;
            chestPitch = Math.cos(phase) * 0.02 * this.walkBlend;
        } else {
            spinePitch = 0.035 + noise(0.19, 1.3) * 0.02;
            chestYaw = noise(0.13, 2.1) * 0.05;
            chestPitch = noise(0.17, 3.3) * 0.015;
        }

        // pose overrides
        const act = this.action || 'none';
        const at = this.actionT;
        if (!walking && this.pose === 'idle') {
            if (act === 'stretch' && at < 2.2) {
                const s = Math.sin(saturate(at / 2.2) * Math.PI);
                spinePitch -= s * 0.09;
                this.arms.L.upper.rotation.z = lerp(0, -1.1, s);
                this.arms.R.upper.rotation.z = lerp(0, 1.1, s);
                this.arms.L.upper.rotation.x = lerp(0, -0.5, s);
                this.arms.R.upper.rotation.x = lerp(0, -0.5, s);
            } else if (act === 'handOnHip' && at < 3.4) {
                const s = smoothstep(0, 0.5, at) * (1 - smoothstep(2.6, 3.4, at));
                this.arms.R.upper.rotation.z = lerp(0, 0.75, s);
                this.arms.R.fore.rotation.x = lerp(0, -1.5, s);
                spinePitch += s * 0.05;
            }
        }

        switch (this.pose) {
            case 'type':
                spinePitch = 0.19;
                chestPitch = 0.06;
                neckPitch = -0.2;
                break;
            case 'read':
                spinePitch = 0.16;
                neckPitch = -0.26;
                break;
            case 'talk':
                spinePitch = 0.06;
                chestPitch = -0.03;
                neckPitch = -0.04 + Math.sin(t * 2.4) * 0.02;
                break;
            case 'sit':
                spinePitch = 0.05;
                neckPitch = 0.02;
                break;
        }

        [j.spinePitch, jv.spinePitch] = spring(j.spinePitch, jv.spinePitch, spinePitch, 120, 20, dt);
        [j.chestPitch, jv.chestPitch] = spring(j.chestPitch, jv.chestPitch, chestPitch, 120, 20, dt);
        [j.chestYaw, jv.chestYaw] = spring(j.chestYaw, jv.chestYaw, chestYaw, 90, 16, dt);
        this._chestYaw = j.chestYaw;

        // counter-rotate the shoulders against the hips (walking only)
        if (walking) this.pelvis.rotation.y = j.pelvisYaw - j.chestYaw * 0.55;

        this.spine.rotation.set(j.spinePitch, j.chestYaw * 0.35, 0);
        this.chest.rotation.set(j.chestPitch, j.chestYaw * 0.65, 0);

        /* ---------- head: gaze tracking with springs ---------- */
        this._updateHead(dt, time, neckPitch);

        /* ---------- arms ---------- */
        this._updateArms(dt, time, walking, stanceBias);
    }

    _updateHead(dt, time, neckPitchBase) {
        const j = this.j;
        const jv = this.jv;

        // where is the gaze point relative to the head?
        const headWorld = this._tmp2;
        this.head.getWorldPosition(headWorld);
        const dx = this.gaze.x - headWorld.x;
        const dz = this.gaze.z - headWorld.z;
        const dy = this.gaze.y - headWorld.y;

        // convert into the root's yaw frame
        const rel = Math.atan2(dx, dz) - this.facing;
        const horiz = Math.hypot(dx, dz);
        const pitch = Math.atan2(dy, Math.max(horiz, 0.05));

        // clamp to a believable neck range, then damp
        const yawTarget = clamp(shortestAngle(rel), -0.85, 0.85);
        const pitchTarget = clamp(-pitch, -0.5, 0.42) + neckPitchBase;

        [j.neckYaw, jv.neckYaw] = spring(j.neckYaw, jv.neckYaw, yawTarget * 0.45, 90, 17, dt);
        [j.neckPitch, jv.neckPitch] = spring(j.neckPitch, jv.neckPitch, pitchTarget * 0.5, 90, 17, dt);

        this.neck.rotation.set(j.neckPitch, j.neckYaw, 0);

        // the head gets the remainder, plus micro-noise so it is never
        // perfectly locked onto the target
        const micro = Math.sin(time * 0.83 + this.idleSeed * 2.3) * 0.022;
        const microY = Math.sin(time * 0.61 + this.idleSeed * 1.1) * 0.03;
        this.head.rotation.set(
            pitchTarget * 0.5 + micro * 0.4,
            yawTarget * 0.55 + microY,
            Math.sin(time * 0.37 + this.idleSeed) * 0.02
        );

        // eye saccades: eyes move faster than the head, which is what makes
        // gaze feel alive rather than glued
        const eyeYaw = clamp(shortestAngle(rel) - yawTarget, -0.22, 0.22);
        const eyePitch = clamp(-pitch - pitchTarget, -0.16, 0.16);
        const eyes = this.headMesh.userData.eyes;
        for (const e of eyes) {
            e.rotation.y = damp(e.rotation.y, eyeYaw, 22, dt);
            e.rotation.x = damp(e.rotation.x, eyePitch, 22, dt);
        }

        // brows react a little
        const brows = [this.headMesh.userData.browL, this.headMesh.userData.browR];
        const browLift = this.pose === 'talk' ? Math.sin(time * 1.9) * 0.12 : 0;
        for (const b of brows) {
            if (b) b.position.y = damp(b.position.y, 0.031 + browLift, 10, dt);
        }
    }

    /* ---------------------------------------------------------------- *
     *  Arms
     *
     *  Poses are authored as a *reach target in shoulder-local space*
     *  (x right, y up, z forward, metres from the shoulder joint) rather
     *  than as raw joint Euler angles.  Two reasons:
     *
     *    - a positive rotation.x on this rig swings the arm backwards, so
     *      hand-authored pitch numbers are a silent sign error waiting to
     *      happen. Deriving the angles from a point cannot get it wrong.
     *    - a target is how you actually think about a task. "Hand on the
     *      fridge handle at (x, 1.4, 0.5)" is expressible; "pitch 0.9,
     *      yaw -0.2, elbow 1.1" is not.
     *
     *  `out` is a small abduction applied after the solve, purely to keep
     *  elbows off the ribs when precision does not matter (typing, holding
     *  a controller). Poses that touch a specific object leave it at zero.
     * ---------------------------------------------------------------- */

    /** Reach target in shoulder-local space: x right, y up, z forward. */
    _reach(side, x, y, z) {
        const a = this.arms[side];
        if (!a._r) a._r = new THREE.Vector3();
        return a._r.set(x, y, z);
    }

    _poseArms(dt, time, walking, out) {
        void dt;
        void out;
        const s = this._poseState;
        s.out = 0.12;
        s.gripL = 0.06;
        s.gripR = 0.06;
        s.wristL = 0;
        s.wristR = 0;
        s.tL.copy(this._reach('L', 0.035, -0.5, 0.05));
        s.tR.copy(this._reach('R', -0.035, -0.5, 0.05));

        if (walking) {
            // swing opposite to the leg on the same side; the amplitude is
            // small because the target is measured from the shoulder, not
            // invented per frame
            const armSwing = 0.16 * this.walkBlend;
            const lp = this.feet.L.swinging ? Math.sin(this.feet.L.swingT * Math.PI) : 0;
            const rp = this.feet.R.swinging ? Math.sin(this.feet.R.swingT * Math.PI) : 0;
            s.tL.set(0.05, -0.5, 0.05 + armSwing * (rp > 0 ? 1 : -1) + lp * armSwing * 0.2);
            s.tR.set(-0.05, -0.5, 0.05 + armSwing * (lp > 0 ? 1 : -1) + rp * armSwing * 0.2);
            s.out = 0.1;
            s.gripL = 0.22;
            s.gripR = 0.22;
        }

        const n = (a, b) => Math.sin(time * a + this.idleSeed * b);

        switch (this.pose) {
            /* ---- seated at the desk ---- */
            case 'type': {
                // hands down and forward onto the desk, fingers tapping in
                // alternation; wrists stay neutral so the taps read as
                // fingers rather than as a bouncing hand
                const tapL = Math.max(0, n(9, 0.4));
                const tapR = Math.max(0, n(9, 0.4 + 0.3));
                s.tL.set(0.2, -0.42, 0.4 - tapL * 0.012);
                s.tR.set(-0.2, -0.42, 0.4 - tapR * 0.012);
                s.out = 0.3;
                s.gripL = 0.2 + tapL * 0.25;
                s.gripR = 0.2 + tapR * 0.25;
                s.wristL = -0.12;
                s.wristR = -0.12;
                break;
            }

            /* ---- a sheet of paper held up in both hands ---- */
            case 'read': {
                const lift = n(0.6, 0.5) * 0.015;
                s.tL.set(0.14, -0.2 + lift, 0.36);
                s.tR.set(-0.14, -0.2 + lift, 0.36);
                s.out = 0.24;
                s.gripL = 0.4;
                s.gripR = 0.4;
                s.wristL = -0.5;
                s.wristR = -0.5;
                break;
            }

            /* ---- one hand leads, the other rests ---- */
            case 'talk': {
                const lead = n(1.05, 1.0);
                const lead2 = n(1.05, 1.7);
                s.tL.set(0.2 + Math.abs(lead) * 0.08, -0.3 + lead * 0.1, 0.3 + lead2 * 0.08);
                s.tR.set(-0.16, -0.46, 0.1);
                s.out = 0.26 + Math.abs(lead) * 0.06;
                s.gripL = 0.2 + Math.abs(lead2) * 0.3;
                break;
            }

            /* ---- sitting on the sofa, hands on the thighs ---- */
            case 'sit':
            case 'watch': {
                const breathe = n(0.5, 0.3) * 0.008;
                s.tL.set(0.17, -0.16 + breathe, 0.24);
                s.tR.set(-0.17, -0.16 - breathe, 0.24);
                s.out = 0.2;
                s.gripL = 0.1;
                s.gripR = 0.1;
                s.wristL = 0.25;
                s.wristR = 0.25;
                break;
            }

            /* ---- a controller, two hands, thumbs working ---- */
            case 'game': {
                // a real pad session: the hands sit low and close, the
                // thumbs do the moving, and the shoulders creep forward
                const pressL = Math.max(0, n(7.5, 0.9));
                const pressR = Math.max(0, n(6.1, 2.2));
                const sway = n(0.8, 0.4) * 0.012;
                s.tL.set(0.115, -0.34, 0.3 + sway);
                s.tR.set(-0.115, -0.34, 0.3 - sway);
                s.out = 0.2;
                s.gripL = 0.62 + pressL * 0.2;
                s.gripR = 0.62 + pressR * 0.2;
                s.wristL = -0.34 - pressL * 0.16;
                s.wristR = -0.34 - pressR * 0.16;
                break;
            }

            /* ---- holding a mug with both hands ---- */
            case 'hold': {
                const sway = n(0.7, 0.2) * 0.006;
                s.tL.set(0.075, -0.34, 0.26 + sway);
                s.tR.set(-0.075, -0.34, 0.26 - sway);
                s.out = 0.16;
                s.gripL = 0.66;
                s.gripR = 0.66;
                s.wristL = -0.2;
                s.wristR = -0.2;
                break;
            }

            /* ---- mug at the mouth ---- */
            case 'sip': {
                const raise = this.actionT < 1.1 ? smootherstep(0, 1.1, this.actionT) : 1;
                const settle = this.actionT < 1.1 ? 0 : Math.sin((this.actionT - 1.1) * 2.2) * 0.02;
                s.tL.set(0.07, -0.34 + raise * 0.26, 0.24 + raise * 0.08);
                s.tR.set(-0.07, -0.34 + raise * 0.26, 0.24 + raise * 0.08);
                s.out = 0.14;
                s.gripL = 0.66;
                s.gripR = 0.66;
                s.wristL = -0.2 - raise * 0.1;
                s.wristR = -0.2 - raise * 0.1;
                void settle;
                break;
            }

            /* ---- stirring a pot on the hob ---- */
            case 'stir': {
                // circular wrist path; the elbow stays put and the hand does
                // the work, which is what stirring actually looks like
                const a = time * 3.1 + this.idleSeed;
                const r = 0.055;
                s.tR.set(
                    -0.22 + Math.cos(a) * r,
                    -0.3 + Math.sin(a * 1.3) * 0.012,
                    0.34 + Math.sin(a) * r
                );
                s.tL.set(0.2, -0.4, 0.24);
                s.out = 0.18;
                s.gripL = 0.5;
                s.gripR = 0.55;
                s.wristR = 0.4;
                break;
            }

            /* ---- kettle tipping into a mug ---- */
            case 'pour': {
                const tip = smootherstep(0.3, 1.2, this.actionT) * (1 - smootherstep(2.2, 3.2, this.actionT));
                s.tR.set(-0.24, -0.26, 0.32);
                s.tL.set(0.16, -0.4, 0.22);
                s.out = 0.2;
                s.gripL = 0.5;
                s.gripR = 0.6;
                s.wristR = -0.9 * tip;
                break;
            }

            /* ---- both hands under the tap ---- */
            case 'wash': {
                const scrub = Math.sin(time * 4.2 + this.idleSeed) * 0.02;
                s.tL.set(0.09, -0.34, 0.34 + scrub);
                s.tR.set(-0.09, -0.34, 0.34 - scrub);
                s.out = 0.14;
                s.gripL = 0.3;
                s.gripR = 0.3;
                s.wristL = 0.3;
                s.wristR = 0.3;
                break;
            }

            /* ---- phone in one hand, head down ---- */
            case 'phone': {
                // the scroll is a thumb flex, so it belongs in the grip
                // rather than in a wrist rotation the viewer will not see
                const scroll = Math.max(0, n(1.8, 0.2));
                s.tR.set(-0.13, -0.34, 0.26);
                s.tL.set(0.1, -0.48, 0.06);
                s.out = 0.12;
                s.gripL = 0.5;
                s.gripR = 0.4 + scroll * 0.3;
                s.wristR = -1.15;
                break;
            }

            /* ---- arms overhead ---- */
            case 'stretch': {
                // a full stretch has a shape: reach, hold, release. Driving
                // it off actionT means the pose is self-timing, so the
                // brain only has to hold it long enough.
                const cycle = 4.6;
                const p = (this.actionT % cycle) / cycle;
                const up = Math.pow(Math.sin(p * Math.PI), 1.3);
                s.tL.set(0.15, -0.5 + up * 0.7, 0.05 - up * 0.05);
                s.tR.set(-0.15, -0.5 + up * 0.7, 0.05 - up * 0.05);
                s.out = 0.12 - up * 0.02;
                s.gripL = 0.08;
                s.gripR = 0.08;
                s.wristL = -up * 0.2;
                s.wristR = -up * 0.2;
                break;
            }

            /* ---- weight on one hip, one hand at the side ---- */
            case 'lean': {
                const shift = n(0.4, 0.8) * 0.01;
                s.tL.set(0.14, -0.5 + shift, 0.04);
                s.tR.set(-0.14, -0.5 - shift, 0.04);
                s.out = 0.11;
                break;
            }

            /* ---- arms folded ---- */
            case 'think': {
                const t = n(0.5, 1.1) * 0.008;
                s.tL.set(0.13, -0.3 + t, 0.3);
                s.tR.set(-0.13, -0.3 - t, 0.3);
                s.out = 0.34;
                s.gripL = 0.35;
                s.gripR = 0.35;
                s.wristL = -0.7;
                s.wristR = -0.7;
                break;
            }
        }

        // A world-space reach overrides the authored target. This is what
        // lets an activity say "put the hand on that fridge handle" without
        // knowing anything about the rig. It has to be applied after the
        // pose switch so a precision reach always wins.
        let reached = false;
        for (const side of ['L', 'R']) {
            const w = this.reach[side];
            if (!w) continue;
            const arm = this.arms[side];
            const local = this._tmp.copy(w);
            arm.shoulder.worldToLocal(local);
            (side === 'L' ? s.tL : s.tR).copy(local);
            // a hand on an object is a closed hand, whatever the pose says
            if (side === 'L') s.gripL = Math.max(s.gripL, 0.5);
            else s.gripR = Math.max(s.gripR, 0.5);
            s.out = Math.min(s.out, 0.12);
            reached = true;
        }
        if (reached) s.out = Math.min(s.out, 0.1);

        this._outTarget = s.out;
        return s;
    }

    /** Blend the authored target into the arm chain, then solve. */
    _updateArms(dt, time, walking, stanceBias) {
        void stanceBias;
        const s = this._poseArms(dt, time, walking);
        const out = this._outTarget;

        for (const side of ['L', 'R']) {
            const arm = this.arms[side];
            const sx = arm.sx;
            const target = side === 'L' ? s.tL : s.tR;
            const gripTarget = side === 'L' ? s.gripL : s.gripR;
            const wristTarget = side === 'L' ? s.wristL : s.wristR;

            const sol = solveArmIK(target, L.upperArm, L.foreArm);

            if (arm._p === undefined) {
                arm._p = sol.upperPitch;
                arm._y = sol.upperYaw;
                arm._e = sol.elbowFlex;
                arm._o = out;
                arm._pv = 0;
                arm._yv = 0;
                arm._ev = 0;
                arm._ov = 0;
                arm._w = wristTarget;
                arm._wv = 0;
                arm._g = gripTarget;
                arm._gv = 0;
            }

            // the springs are what make a pose change look like a body
            // moving rather than a model being swapped
            [arm._p, arm._pv] = spring(arm._p, arm._pv, sol.upperPitch, 95, 18, dt);
            [arm._y, arm._yv] = spring(arm._y, arm._yv, sol.upperYaw, 95, 18, dt);
            [arm._e, arm._ev] = spring(arm._e, arm._ev, sol.elbowFlex, 82, 17, dt);
            [arm._o, arm._ov] = spring(arm._o, arm._ov, out, 70, 15, dt);
            [arm._w, arm._wv] = spring(arm._w, arm._wv, wristTarget, 110, 19, dt);
            [arm._g, arm._gv] = spring(arm._g, arm._gv, gripTarget, 70, 14, dt);

            arm.upper.rotation.set(arm._p, arm._y, sx * arm._o);
            arm.fore.rotation.set(arm._e, 0, 0);
            arm.hand.rotation.set(arm._w, 0, 0);

            // grip: curl the knuckle line and swing the thumb across it
            const curl = arm._g;
            arm.knuckle.rotation.x = -curl * 1.15;
            arm.knuckle.scale.y = 1 - curl * 0.1;
            arm.thumb.rotation.z = sx * (0.9 - curl * 0.72);
            arm.thumb.rotation.x = -curl * 0.3;
        }
    }

    /* -------------------- blinking -------------------- */

    _updateBlink(dt) {
        const ud = this.root.userData;
        const hm = this.headMesh?.userData;
        if (!hm) return;

        const b = this.blink;
        b.t += dt;

        const duration =
            b.phase === 'closing' ? 0.055 :
            b.phase === 'closed' ? 0.045 + (b.dbl ? 0.05 : 0) :
            b.phase === 'opening' ? 0.09 : 0;

        if (b.t >= duration) {
            b.t = 0;
            if (b.phase === 'open') {
                b.phase = 'closing';
            } else if (b.phase === 'closing') {
                b.phase = 'closed';
            } else if (b.phase === 'closed') {
                b.phase = 'opening';
            } else {
                b.phase = 'open';
                // schedule the next blink; occasionally do a double blink
                b.dbl = Math.random() < 0.22;
                b.next = (b.dbl ? 0.14 : 0) + lerp(1.6, 5.4, Math.random());
            }
        }
        if (b.phase === 'open' && b.next !== undefined) {
            b.t -= dt; // don't consume the inter-blink interval
            b.waiting = (b.waiting || 0) + dt;
            if (b.waiting >= b.next) {
                b.waiting = 0;
                b.phase = 'closing';
                b.t = 0;
            }
        }

        // eyelid closure curve: fast down, slower up
        let openness = 1;
        if (b.phase === 'closing') openness = 1 - b.t / 0.055;
        else if (b.phase === 'closed') openness = 0;
        else if (b.phase === 'opening') openness = b.t / 0.09;

        const sy = lerp(hm.baseLidScale, 1.5, saturate(1 - openness));
        for (const lid of hm.lids) lid.scale.y = sy;
    }

    /* -------------------- shadow -------------------- */

    _updateBlob() {
        if (!this.blob) return;
        // fade the fake contact shadow out when lying down
        const lying = this.pose === 'sleep';
        this.blob.material.opacity = damp(
            this.blob.material.opacity,
            lying ? 0.22 : 0.5,
            4,
            0.016
        );
        this.blob.position.set(this.root.position.x, 0.006, this.root.position.z);
        this.blob.updateMatrix();
    }

    /* -------------------- helpers -------------------- */

    get worldFeetAverage() {
        return new THREE.Vector3(
            (this.feet.L.planted.x + this.feet.R.planted.x) / 2,
            0,
            (this.feet.L.planted.z + this.feet.R.planted.z) / 2
        );
    }
}

/* ------------------------------------------------------------------ *
 *  small helpers
 * ------------------------------------------------------------------ */

function halfStride_(c) {
    return c.strideLength * 0.5;
}

function shortestAngle(a) {
    let d = a % TAU;
    if (d > Math.PI) d -= TAU;
    if (d < -Math.PI) d += TAU;
    return d;
}