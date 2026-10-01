import * as THREE from 'three';
import { mountOfficeScene } from './scene.js';

/* ==================================================================== *
 *  Livewire facade
 *
 *  The scene itself lives in the modules (scene, nav, propkit, brain,
 *  activities, character). This file is only the seam between that
 *  simulation and the admin page, and it exists for exactly one reason:
 *  the Blade template has always spoken to `window._threeOfficeApp`, and
 *  Livewire, Alpine and the WebSocket handlers all drive the three agents
 *  through that object. So the facade keeps that exact shape — the status
 *  fields Alpine assigns, the toolbar's setView/zoom/toggleExpand, the
 *  requestAgentStatus funnel and the live chat bubbles — and translates
 *  it into calls the simulation understands.
 *
 *  Nothing here reaches into the simulation's internals beyond what
 *  mountOfficeScene() hands back on purpose.
 * ==================================================================== */

/** Wire payload names -> agent ids. */
const AGENT_ALIASES = {
    dewi: 'dewi', cs: 'dewi', customer: 'dewi', 'customer service': 'dewi',
    singgih: 'singgih', core: 'singgih', dispatcher: 'singgih',
    andera: 'andera', report: 'andera', finance: 'andera',
};

const AGENT_LABELS = {
    dewi: 'Dewi',
    singgih: 'Singgih',
    andera: 'Andera',
};

/** Which agent lives at which status field. */
const STATUS_FIELD = {
    dewi: 'currentCsStatus',
    singgih: 'currentSinggihStatus',
    andera: 'currentAnderaStatus',
};

/**
 * A "break" is not one behaviour, it is whatever that person does when they
 * step away from the desk, so each agent cycles through their own list
 * instead of everyone piling onto the same sofa.
 */
const BREAK_PLAN = {
    dewi: ['sofaRest', 'watchTv', 'drink', 'sofaRest'],
    singgih: ['game', 'drink', 'watchTv', 'sofaRest'],
    andera: ['watchTv', 'eat', 'sofaRest', 'drink'],
};

export class OfficeApp {
    constructor() {
        this.ready = false;
        this.agents = {};
        this.handle = null;
        this._unsubTick = null;
        this._breakTurn = { dewi: 0, singgih: 0, andera: 0 };
    }

    /* ---------------- public API (Livewire contract) ---------------- */

    init(container, initialStatus = 'working') {
        if (!container) return this;

        // The legacy inline engine may already own a render loop on this
        // container. Stop it before mounting ours, or two scenes end up
        // composited on top of each other.
        this._takeoverLegacy(container);

        // Livewire re-runs Alpine init(), which calls init() again. Tear down
        // our own previous renderer first so the container holds one canvas.
        if (this.ready) this.dispose();
        this._disposed = false;

        this.container = container;
        this.container.style.position = 'relative';
        this.container.style.overflow = 'hidden';

        const canvas = document.createElement('canvas');
        canvas.style.cssText = 'display:block;width:100%;height:100%;touch-action:none;';
        this.container.appendChild(canvas);

        this.handle = mountOfficeScene(canvas, { startHour: 8.5 });
        this.scene = this.handle.scene;
        this.camera = this.handle.camera;
        this.renderer = this.handle.renderer;
        this.composer = this.handle.composer;
        this.nav = this.handle.nav;

        this.agents = {};
        for (const a of this.handle.agents) {
            const bubble = makeBubble();
            bubble.sprite.position.set(0, 2.28, 0);
            a.rig.add(bubble.sprite);

            this.agents[a.id] = {
                id: a.id,
                label: AGENT_LABELS[a.id] || a.id,
                group: a.rig,
                controller: a.body,
                brain: a.brain,
                bubble,
                status: null,
            };
        }

        // Speech bubbles ride on the rig, so they only need a heartbeat to
        // expire and to duck while the agent is walking.
        this._unsubTick = this.handle.api.onTick((dt, time) => this._tick(dt, time));

        // Livewire assigns currentCsStatus / currentSinggihStatus /
        // currentAnderaStatus BEFORE calling init(), so honour those instead of
        // blindly stamping every agent with the single initialStatus.
        this.currentCsStatus = this.currentCsStatus || initialStatus || 'working';
        this.currentSinggihStatus = this.currentSinggihStatus || initialStatus || 'working';
        this.currentAnderaStatus = this.currentAnderaStatus || initialStatus || 'working';

        this.applyStatus('dewi', this.currentCsStatus);
        this.applyStatus('singgih', this.currentSinggihStatus);
        this.applyStatus('andera', this.currentAnderaStatus);

        this.ready = true;
        this.handle.api.setView('iso');

        // tell Livewire the scene is alive so it can stop showing a spinner
        window.dispatchEvent(new CustomEvent('three-office-ready'));
        return this;
    }

    /** Stop and unmount the engine that shipped inline in the Blade file. */
    _takeoverLegacy(container) {
        const legacy = this.legacy;
        this.legacy = null;
        if (legacy) {
            try {
                if (legacy.animationFrameId) cancelAnimationFrame(legacy.animationFrameId);
                legacy.animationFrameId = null;
                for (const id of legacy._rafs || []) cancelAnimationFrame(id);
                legacy.renderer?.dispose?.();
                legacy.composer?.dispose?.();
                legacy.scene?.traverse?.((o) => {
                    if (o.isMesh) {
                        o.geometry?.dispose?.();
                        const mats = Array.isArray(o.material) ? o.material : [o.material];
                        for (const m of mats) m?.dispose?.();
                    }
                });
                legacy.renderer?.domElement?.remove?.();
                window.removeEventListener('resize', legacy.onResize);
            } catch (err) {
                console.warn('[office3d] legacy takeover', err);
            }
        }
        container.innerHTML = '';
    }

    /**
     * The single funnel every WebSocket/Livewire update goes through.
     */
    requestAgentStatus(character, status) {
        const name = String(character || '').toLowerCase();
        const key = AGENT_ALIASES[name];
        if (!key) return;

        const normalised = status === 'idle' ? 'break' : status;
        this[STATUS_FIELD[key]] = normalised;
        this.applyStatus(key, normalised);
    }

    updateCsPosition(status) {
        this.currentCsStatus = status;
        this.applyStatus('dewi', status);
    }

    updateSinggihPosition(status) {
        this.currentSinggihStatus = status;
        this.applyStatus('singgih', status);
    }

    updateAnderaPosition(status) {
        this.currentAnderaStatus = status;
        this.applyStatus('andera', status);
    }

    setView(name) {
        if (!this.handle) return this;
        if (name === 'free') {
            this.handle.api.toggleFreeCam();
        } else {
            this.handle.api.setView(name);
        }
        return this;
    }

    zoomIn() {
        this.handle?.api.zoomIn();
    }

    zoomOut() {
        this.handle?.api.zoomOut();
    }

    toggleExpand() {
        this.toggleFullscreen();
    }

    toggleFullscreen() {
        const el = document.getElementById('three-office-card') || this.container?.parentElement;
        if (!document.fullscreenElement) {
            if (el?.requestFullscreen) el.requestFullscreen();
            else if (el?.webkitRequestFullscreen) el.webkitRequestFullscreen();
        } else if (document.exitFullscreen) {
            document.exitFullscreen();
        }
        // the scene watches the canvas with a ResizeObserver, but give the
        // browser a beat to actually resize the fullscreen element
        setTimeout(() => this.handle?.renderer?.render?.(this.scene, this.camera), 250);
    }

    updateLiveBubble(agent, text) {
        const a = this.agents[agent];
        if (!a || !text) return;
        a.bubble.show(String(text).slice(0, 140), a.label, 7, this.handle?.api.time || 0);

        // talking: turn toward whoever it is speaking to instead of staring
        // at the monitor
        const g = a.group.position;
        const t = (this.handle?.api.time || 0) * 0.6 + (a.id.length * 1.7);
        a.controller.lookAt(g.x + Math.sin(t) * 1.6, 1.42, g.z + Math.cos(t * 0.85) * 1.6);
    }

    dispose() {
        this._disposed = true;
        this._unsubTick?.();
        this._unsubTick = null;

        for (const a of Object.values(this.agents)) {
            a.bubble.sprite.removeFromParent();
            a.bubble.tex.dispose();
            a.bubble.sprite.material.dispose();
        }

        this.handle?.api?.dispose?.();
        this.handle = null;
        this.agents = {};
        this.ready = false;
    }

    /* ---------------- status -> behaviour ---------------- */

    /**
     * Translate a page status into an activity, and let the brain decide how to
     * get there. Re-issuing the same status would restart the activity
     * mid-walk, so identical repeats are ignored.
     */
    applyStatus(name, status) {
        const a = this.agents[name];
        const brain = a?.brain;
        if (!a || !brain) return;
        if (a.status === status) return;
        a.status = status;

        if (status === 'sleeping') {
            brain.interrupt('nap');
            a.bubble.sprite.visible = false;
            return;
        }

        if (status === 'break') {
            const plan = BREAK_PLAN[name] || ['sofaRest'];
            const pick = plan[this._breakTurn[name]++ % plan.length];
            if (brain.interrupt(pick)) return;
            // that activity had nowhere to go for this agent
            if (brain.interrupt('sofaRest')) return;
            brain.settle();
            return;
        }

        brain.goWork();
    }

    /* ---------------- per-frame glue ---------------- */

    _tick() {
        const time = this.handle?.api.time || 0;
        for (const a of Object.values(this.agents)) {
            const b = a.bubble;
            if (!b.sprite.visible) continue;
            if (b.until && time > b.until) {
                b.sprite.visible = false;
                b.until = 0;
            }
            const pose = a.controller.pose;
            b.sprite.position.y = pose === 'sleep' ? 0.95 : 2.28;
            // walking with a bubble in the way reads as a glitch
            b.sprite.visible = b.sprite.visible && pose !== 'walk';
        }
    }
}

/* ------------------------------------------------------------------ *
 *  Speech bubble — a CSS-free canvas sprite so it survives in-scene.
 * ------------------------------------------------------------------ */
function makeBubble() {
    const canvas = document.createElement('canvas');
    canvas.width = 512;
    canvas.height = 256;
    const tex = new THREE.CanvasTexture(canvas);
    tex.colorSpace = THREE.SRGBColorSpace;
    const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: false });
    const sprite = new THREE.Sprite(mat);
    sprite.scale.set(0.9, 0.45, 1);
    sprite.renderOrder = 999;
    sprite.visible = false;

    const state = { text: '', until: 0, canvas, tex, sprite };

    state.draw = (text, who) => {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, 512, 256);

        // bubble body
        const pad = 18;
        ctx.font = '600 27px ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
        const maxW = 512 - pad * 2 - 46;
        const words = String(text).split(/\s+/);
        const lines = [];
        let line = '';
        for (const word of words) {
            const test = line ? line + ' ' + word : word;
            if (ctx.measureText(test).width > maxW && line) {
                lines.push(line);
                line = word;
            } else {
                line = test;
            }
        }
        if (line) lines.push(line);
        const shown = lines.slice(0, 4);
        if (lines.length > 4) shown[3] = shown[3].replace(/\s*\S*$/, '…');

        const lineH = 36;
        const bodyH = shown.length * lineH + 26;
        const bodyW = Math.min(
            maxW + pad * 2,
            Math.max(180, ...shown.map((l) => ctx.measureText(l).width)) + pad * 2
        );
        const x0 = (512 - bodyW) / 2;
        const y0 = 26;

        ctx.fillStyle = 'rgba(255,255,255,0.97)';
        ctx.strokeStyle = 'rgba(24,28,34,0.22)';
        ctx.lineWidth = 2;
        const r = 18;
        ctx.beginPath();
        ctx.moveTo(x0 + r, y0);
        ctx.arcTo(x0 + bodyW, y0, x0 + bodyW, y0 + bodyH, r);
        ctx.arcTo(x0 + bodyW, y0 + bodyH, x0, y0 + bodyH, r);
        ctx.arcTo(x0, y0 + bodyH, x0, y0, r);
        ctx.arcTo(x0, y0, x0 + bodyW, y0, r);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        // tail
        ctx.beginPath();
        ctx.moveTo(256 - 15, y0 + bodyH - 1);
        ctx.lineTo(256 + 4, y0 + bodyH + 24);
        ctx.lineTo(256 + 16, y0 + bodyH - 1);
        ctx.closePath();
        ctx.fillStyle = 'rgba(255,255,255,0.97)';
        ctx.fill();

        ctx.fillStyle = '#1a1f26';
        ctx.textBaseline = 'top';
        shown.forEach((l, i) => ctx.fillText(l, x0 + pad, y0 + 13 + i * lineH));

        // speaker chip
        if (who) {
            ctx.font = '700 18px ui-sans-serif, system-ui, sans-serif';
            const lw = ctx.measureText(who).width;
            ctx.fillStyle = 'rgba(26,31,38,0.9)';
            ctx.beginPath();
            ctx.roundRect(18, 4, lw + 22, 30, 15);
            ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.textBaseline = 'middle';
            ctx.fillText(who, 29, 20);
        }

        tex.needsUpdate = true;
    };

    state.show = (text, who, seconds = 7, now = 0) => {
        state.text = text;
        state.until = now + seconds;
        state.draw(text, who);
        sprite.visible = true;
    };

    return state;
}

/* ------------------------------------------------------------------ *
 *  Boot / global install
 * ------------------------------------------------------------------ */

function boot() {
    if (typeof window === 'undefined') return;
    if (window.__office3dBooted) return;
    window.__office3dBooted = true;

    const app = new OfficeApp();

    // The Blade template assigns window._threeOfficeApp from a *classic* inline
    // script, which runs while the document is still parsing. This module is
    // deferred, so by the time we get here that object already exists — we keep
    // it (and its shape) and layer our methods on top.
    const legacy = window._threeOfficeApp;
    app.legacy = legacy && typeof legacy.init === 'function' ? legacy : null;
    app._apiTarget = legacy && typeof legacy.init === 'function' ? legacy : {};

    // Livewire seeds these before init(); carry them into the engine.
    if (legacy) {
        app.currentCsStatus = legacy.currentCsStatus;
        app.currentSinggihStatus = legacy.currentSinggihStatus;
        app.currentAnderaStatus = legacy.currentAnderaStatus;
    }

    install(app);
    window.__office3d = app;
}

/** Write the engine API onto the object Livewire/Alpine already holds. */
function install(app) {
    const target = app._apiTarget || (window._threeOfficeApp = {});
    app._apiTarget = target;

    const methods = {
        init: (container, status) => app.init(container, status),
        updateCsPosition: (s) => app.updateCsPosition(s),
        updateSinggihPosition: (s) => app.updateSinggihPosition(s),
        updateAnderaPosition: (s) => app.updateAnderaPosition(s),
        requestAgentStatus: (character, status) => app.requestAgentStatus(character, status),
        updateLiveBubble: (a, t) => app.updateLiveBubble(a, t),
        setView: (v) => app.setView(v),
        zoomIn: () => app.zoomIn(),
        zoomOut: () => app.zoomOut(),
        toggleExpand: () => app.toggleExpand(),
        toggleFullscreen: () => app.toggleFullscreen(),
        dispose: () => app.dispose(),
        _engine: app,
    };

    for (const [k, fn] of Object.entries(methods)) target[k] = fn;

    // keep the Livewire-mutated status fields mirrored onto the engine, since
    // Alpine assigns them directly on this object
    for (const prop of ['currentCsStatus', 'currentSinggihStatus', 'currentAnderaStatus']) {
        let backing = app[prop];
        Object.defineProperty(target, prop, {
            configurable: true,
            enumerable: true,
            get: () => backing,
            set: (v) => {
                backing = v;
                app[prop] = v;
            },
        });
    }

    for (const [prop, getter] of [
        ['scene', () => app.scene],
        ['camera', () => app.camera],
        ['renderer', () => app.renderer],
        ['composer', () => app.composer],
        ['dewiGroup', () => app.agents?.dewi?.group],
        ['singgihGroup', () => app.agents?.singgih?.group],
        ['anderaGroup', () => app.agents?.andera?.group],
    ]) {
        Object.defineProperty(target, prop, { configurable: true, enumerable: true, get: getter });
    }

    target.ready = true;
    window._threeOfficeApp = target;
}

/** Re-point the global at our engine (used if something overwrote it). */
function reinstall() {
    const app = window.__office3d;
    if (!app) return;
    if (window._threeOfficeApp && window._threeOfficeApp._engine === app) return;
    install(app);
}

if (window.THREE && window.OFFICE_ADDONS) {
    boot();
} else {
    window.addEventListener('three-office-runtime-ready', boot, { once: true });
    // the runtime may already be loaded (script order race)
    if (window.THREE && window.OFFICE_ADDONS) boot();
}

// Safety net: the classic inline script may be re-evaluated by Livewire, which
// would hand us a brand-new global object. Re-install on the events that
// signal a fresh page wiring.
if (typeof document !== 'undefined') {
    document.addEventListener('livewire:init', reinstall);
    window.addEventListener('three-office-ready', reinstall);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(reinstall, 0));
    }
}

export default OfficeApp;