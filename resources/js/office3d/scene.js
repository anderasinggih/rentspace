import * as THREE from 'three';
import { EffectComposer } from 'three/examples/jsm/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/examples/jsm/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/examples/jsm/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/examples/jsm/postprocessing/OutputPass.js';
import { SMAAPass } from 'three/examples/jsm/postprocessing/SMAAPass.js';

import { createMaterials, buildShell } from './architecture.js';
import { buildHumanoid, CharacterController } from './character.js';
import { NavGrid } from './nav.js';
import { PropKit, dressWorld, AGENT_LOOKS } from './propkit.js';
import { AgentBrain } from './brain.js';
import { allSpots } from './activities.js';
import { clamp, damp, Rng } from './lib.js';

/* ================================================================== *
 *  Scene bootstrap
 *
 *  Composes the refactored modules into the one thing the admin page
 *  needs: a live apartment with three people who decide what to do next.
 *
 *  The whole file is behind `mountOfficeScene()` and leaves no globals
 *  behind, so the inline scene in the Blade view stays usable as a
 *  fallback while this one is dialled in.
 * ================================================================== */

const AGENT_SEED = { dewi: 101, singgih: 202, andera: 303 };

/* ------------------------------------------------------------------ *
 *  Camera views
 * ------------------------------------------------------------------ */

const VIEWS = {
    overview: { pos: [7.2, 6.4, 12.0], look: [-0.4, 0.9, 0.4], fov: 42 },
    office: { pos: [2.6, 2.5, 5.6], look: [-2.2, 1.0, 0.2], fov: 46 },
    lounge: { pos: [1.4, 2.2, 5.2], look: [6.6, 0.9, 1.8], fov: 48 },
    pantry: { pos: [3.0, 2.4, 0.6], look: [6.0, 1.0, -4.6], fov: 48 },
    bedroom: { pos: [-3.4, 2.2, 1.4], look: [-7.4, 0.9, -3.4], fov: 50 },
    bathroom: { pos: [-4.4, 1.9, 3.0], look: [-7.4, 1.1, 1.0], fov: 50 },
};

export function mountOfficeScene(canvas, opts = {}) {
    const renderer = new THREE.WebGLRenderer({
        canvas,
        antialias: true,
        powerPreference: 'high-performance',
    });
    renderer.setPixelRatio(Math.min(devicePixelRatio || 1, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.02;
    renderer.outputColorSpace = THREE.SRGBColorSpace;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, 1, 0.05, 120);

    /* ---------------- world ---------------- */

    const M = createMaterials();
    buildShell(scene, M);

    const nav = new NavGrid();
    const kit = new PropKit(scene, M);
    dressWorld(scene, M, kit);

    // every spot the brain can target is a nav destination, and every
    // approach point has to be open floor or A* cannot reach it
    for (const spot of allSpots()) nav.addAnchor(spot);
    // the built-in seats were registered by the grid's own constructor,
    // but their approach hints are duplicated here so the paths in and out
    // of the sofa use the same mouth
    for (const key of ['sofa0', 'sofa1', 'sofa2', 'dewiDesk', 'singgihDesk', 'anderaDesk', 'bed']) {
        const s = allSpots().find((x) => x.id === key);
        if (s) nav.addAnchor({ x: s.x, z: s.z, approach: s.approach });
    }

    /* ---------------- lighting ---------------- */

    const hemi = new THREE.HemisphereLight(0xdfe9f5, 0x5b5044, 0.55);
    scene.add(hemi);

    const sun = new THREE.DirectionalLight(0xfff0d8, 1.35);
    sun.position.set(6.5, 9.5, 7.5);
    sun.castShadow = true;
    sun.shadow.mapSize.set(2048, 2048);
    sun.shadow.camera.near = 1;
    sun.shadow.camera.far = 34;
    sun.shadow.camera.left = -13;
    sun.shadow.camera.right = 13;
    sun.shadow.camera.top = 11;
    sun.shadow.camera.bottom = -11;
    sun.shadow.bias = -0.0006;
    sun.shadow.normalBias = 0.022;
    scene.add(sun);
    scene.add(sun.target);

    // a warm pool over the desk so the office reads as the place of work
    const deskLamp = new THREE.PointLight(0xffd9a0, 14, 7, 2);
    deskLamp.position.set(-2.2, 2.3, 0.4);
    scene.add(deskLamp);

    // cool fill in the kitchen, so the two halves of the flat differ
    const kitchenFill = new THREE.PointLight(0xeaf2ff, 12, 8, 2);
    kitchenFill.position.set(5.4, 2.6, -3.6);
    scene.add(kitchenFill);

    // the TV is a light source, which is what makes a lounge read as a
    // lounge at night
    const tvGlow = new THREE.PointLight(0x7fa8d8, 0, 5.5, 2);
    tvGlow.position.set(3.7, 1.1, 1.7);
    scene.add(tvGlow);

    /* ---------------- agents ---------------- */

    const agents = [];
    const kitBodies = new Map();

    const SPAWNS = [
        { id: 'dewi', x: -2.6, z: 2.2, rotY: Math.PI },
        { id: 'singgih', x: 6.4, z: 3.2, rotY: -Math.PI / 2 },
        { id: 'andera', x: 4.0, z: 4.4, rotY: 0.4 },
    ];

    for (const spawn of SPAWNS) {
        const look = AGENT_LOOKS[spawn.id] || {};
        const rig = buildHumanoid(M, { ...look, seed: AGENT_SEED[spawn.id] });
        scene.add(rig);

        const body = new CharacterController(rig, { seed: AGENT_SEED[spawn.id] });
        body.setPosition(spawn.x, spawn.z, spawn.rotY);

        const brain = new AgentBrain(body, {
            id: spawn.id,
            name: spawn.id,
            nav,
            propKit: kit,
        });
        kitBodies.set(spawn.id, body);

        agents.push({ id: spawn.id, rig, body, brain, status: 'idle' });
    }
    kit.bodies = kitBodies;

    /* ---------------- post processing ---------------- */

    const composer = new EffectComposer(renderer);
    composer.addPass(new RenderPass(scene, camera));
    const bloom = new UnrealBloomPass(new THREE.Vector2(1, 1), 0.28, 0.7, 0.86);
    composer.addPass(bloom);
    composer.addPass(new SMAAPass());
    composer.addPass(new OutputPass());

    const usePost = opts.usePostProcessing !== false;

    /* ---------------- camera rig ---------------- */

    const view = { ...VIEWS.overview };
    const camPos = new THREE.Vector3(...view.pos);
    const camLook = new THREE.Vector3(...view.look);
    const desiredPos = camPos.clone();
    const desiredLook = camLook.clone();
    let camFov = view.fov;

    // free-cam, toggled at runtime
    const free = {
        on: false,
        yaw: 0,
        pitch: -0.3,
        dist: 8,
        target: new THREE.Vector3(-0.4, 0.9, 0.4),
    };

    function setView(name) {
        const v = VIEWS[name];
        if (!v) return;
        desiredPos.set(...v.pos);
        desiredLook.set(...v.look);
        camFov = v.fov;
        free.on = false;
    }

    /* ---------------- interaction ---------------- */

    const pointer = { down: false, x: 0, y: 0 };
    canvas.addEventListener('pointerdown', (e) => {
        if (!free.on) return;
        pointer.down = true;
        pointer.x = e.clientX;
        pointer.y = e.clientY;
        canvas.setPointerCapture?.(e.pointerId);
    });
    canvas.addEventListener('pointerup', (e) => {
        pointer.down = false;
        canvas.releasePointerCapture?.(e.pointerId);
    });
    canvas.addEventListener('pointermove', (e) => {
        if (!pointer.down) return;
        free.yaw -= (e.clientX - pointer.x) * 0.0045;
        free.pitch = clamp(free.pitch - (e.clientY - pointer.y) * 0.0035, -1.3, 0.5);
        pointer.x = e.clientX;
        pointer.y = e.clientY;
    });
    canvas.addEventListener(
        'wheel',
        (e) => {
            if (!free.on) return;
            e.preventDefault();
            free.dist = clamp(free.dist * (1 + Math.sign(e.deltaY) * 0.1), 1.6, 26);
        },
        { passive: false }
    );

    function toggleFreeCam() {
        free.on = !free.on;
        if (free.on) {
            // start the orbit from wherever the camera currently is, so the
            // toggle never cuts
            free.target.copy(camLook);
            const off = new THREE.Vector3().subVectors(camPos, camLook);
            free.dist = off.length();
            free.yaw = Math.atan2(off.x, off.z);
            free.pitch = Math.asin(clamp(off.y / (free.dist || 1), -1, 1));
        }
        return free.on;
    }

    /* ---------------- resize ---------------- */

    function resize() {
        const w = canvas.clientWidth || canvas.width || 1;
        const h = canvas.clientHeight || canvas.height || 1;
        renderer.setSize(w, h, false);
        composer.setSize(w, h);
        bloom.setSize(w, h);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    }
    resize();
    const ro = new ResizeObserver(resize);
    ro.observe(canvas);

    /* ---------------- live chat bridge ---------------- */

    // Lets the Laravel side push an agent back to work when a real chat
    // lands, without this module knowing anything about Echo or Pusher.
    const api = {
        setView,
        toggleFreeCam,
        get freeCam() { return free.on; },
        focusAgent(id) {
            const a = agents.find((x) => x.id === id);
            if (!a) return;
            free.target.set(a.body.pos.x, 1.0, a.body.pos.z);
            const off = new THREE.Vector3(2.6, 1.9, 2.6);
            desiredPos.copy(free.target).add(off);
            desiredLook.copy(free.target);
        },
        /** A chat arrived: pull this agent to the desk. */
        onChat(id) {
            agents.find((x) => x.id === id)?.brain.goWork();
        },
        /** The queue drained: send them home to their own spot. */
        onIdle(id) {
            agents.find((x) => x.id === id)?.brain.settle();
        },
        /** Force a specific behaviour, e.g. 'game' or 'cook'. */
        command(id, activityId) {
            agents.find((x) => x.id === id)?.brain.interrupt(activityId);
        },
        agents: () => agents.map((a) => ({
            id: a.id,
            status: a.brain.status,
            label: a.brain.statusLabel,
            activity: a.brain.activity?.id || null,
        })),
        dispose() {
            ro.disconnect();
            renderer.dispose();
        },
    };
    if (typeof window !== 'undefined') window._office3d = api;

    /* ---------------- loop ---------------- */

    const clock = new THREE.Clock();
    let raf = 0;
    let elapsed = 0;
    let running = true;

    function frame() {
        if (!running) return;
        raf = requestAnimationFrame(frame);
        const dt = Math.min(clock.getDelta(), 0.1);
        elapsed += dt;

        // A simulated clock, so the flat has a routine: lunch at lunch,
        // games in the evening, sleep in the small hours. Real wall time
        // would make the scene static for a whole afternoon.
        const hour = (elapsed / 60 + (opts.startHour ?? 8.5)) % 24;

        // the TV comes on when someone is watching, and its glow follows
        const watching = agents.some((a) => a.brain.activity?.id === 'watchTv');
        tvGlow.intensity = damp(tvGlow.intensity, watching ? 9 : 0, 1.5, dt);
        // and the room dims a touch at night
        const night = Math.max(0, Math.cos(((hour - 22) / 24) * Math.PI * 2));
        const target = night * 0.75;
        sun.intensity = damp(sun.intensity, 1.35 - target, 0.6, dt);
        hemi.intensity = damp(hemi.intensity, 0.55 - target * 0.3, 0.6, dt);
        deskLamp.intensity = damp(deskLamp.intensity, 9 + target * 14, 0.6, dt);

        // brains decide, bodies move
        for (const a of agents) {
            a.brain.hour = hour;
            a.brain.update(dt, elapsed, agents, chatLoadFor(a.id));
            a.body.update(dt, elapsed);
        }

        updateCamera(dt);
        sun.target.position.set(0, 0, 0);

        if (usePost) composer.render();
        else renderer.render(scene, camera);
    }

    // Chat load per agent, settable from outside; 0 = nothing waiting.
    const loads = { dewi: 0.35, singgih: 0.15, andera: 0.25 };
    function chatLoadFor(id) {
        return loads[id] ?? 0;
    }
    api.setChatLoad = (id, v) => { loads[id] = clamp(v, 0, 1); };

    function updateCamera(dt) {
        if (free.on) {
            const cy = Math.cos(free.pitch);
            desiredPos.set(
                free.target.x + Math.sin(free.yaw) * cy * free.dist,
                free.target.y - Math.sin(free.pitch) * free.dist,
                free.target.z + Math.cos(free.yaw) * cy * free.dist
            );
            desiredLook.copy(free.target);
            camFov = 50;
        }
        camPos.x = damp(camPos.x, desiredPos.x, 5, dt);
        camPos.y = damp(camPos.y, desiredPos.y, 5, dt);
        camPos.z = damp(camPos.z, desiredPos.z, 5, dt);
        camLook.x = damp(camLook.x, desiredLook.x, 6, dt);
        camLook.y = damp(camLook.y, desiredLook.y, 6, dt);
        camLook.z = damp(camLook.z, desiredLook.z, 6, dt);
        camera.position.copy(camPos);
        camera.lookAt(camLook);
        if (Math.abs(camera.fov - camFov) > 0.01) {
            camera.fov = damp(camera.fov, camFov, 6, dt);
            camera.updateProjectionMatrix();
        }
    }

    frame();

    return {
        api,
        stop() {
            running = false;
            cancelAnimationFrame(raf);
        },
        start() {
            if (running) return;
            running = true;
            clock.getDelta();
            frame();
        },
    };
}
