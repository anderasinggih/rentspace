<!DOCTYPE html>
<html lang="id" class="tv-root">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>RentSpace TV</title>
<style>
    :root {
        --pad: clamp(14px, 1.6vw, 34px);
        --fs-xs: clamp(11px, 0.72vw, 20px);
        --fs-sm: clamp(13px, 0.92vw, 26px);
        --fs-md: clamp(17px, 1.25vw, 34px);
        --fs-lg: clamp(24px, 2.1vw, 60px);
        --fs-xl: clamp(38px, 4.6vw, 140px);
        --glass: rgba(9, 11, 20, 0.62);
        --glass-2: rgba(9, 11, 20, 0.42);
        --line: rgba(255, 255, 255, 0.14);
        --ink: #f6f8ff;
        --muted: #9aa4bd;
        --ok: #34d399;
        --warn: #fbbf24;
        --bad: #fb7185;
        --brand: #7c9cff;
    }

    * { box-sizing: border-box; }

    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: #05060b;
        color: var(--ink);
        font-family: 'Helvetica Neue', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        -webkit-font-smoothing: antialiased;
        cursor: default;
    }

    body.idle { cursor: none; }

    /* ---------- LAYER VIDEO ---------- */
    #stage { position: fixed; inset: 0; overflow: hidden; background: #05060b; }

    #playerWrap {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    #playerWrap iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }

    #scrim {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            linear-gradient(to bottom, rgba(5,6,11,0.55) 0%, rgba(5,6,11,0) 22%),
            linear-gradient(to top, rgba(5,6,11,0.6) 0%, rgba(5,6,11,0) 26%);
    }

    /* ---------- HUD KIRI ATAS ---------- */
    #hud {
        position: absolute;
        top: var(--pad);
        left: var(--pad);
        display: flex;
        align-items: center;
        gap: clamp(10px, 1vw, 22px);
        padding: clamp(10px, 0.9vw, 20px) clamp(14px, 1.3vw, 28px);
        background: var(--glass-2);
        border: 1px solid var(--line);
        border-radius: clamp(14px, 1.1vw, 24px);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        z-index: 6;
    }

    #clock {
        font-size: var(--fs-xl);
        font-weight: 800;
        line-height: 0.95;
        letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums;
    }

    #hudMeta { display: flex; flex-direction: column; gap: 4px; }

    #hudDate { font-size: var(--fs-sm); font-weight: 700; color: var(--ink); text-transform: uppercase; letter-spacing: 0.06em; }
    #hudBrand { font-size: var(--fs-xs); font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.18em; }

    #netBadge {
        display: inline-flex;
        align-items: center;
        gap: 0.5em;
        font-size: var(--fs-xs);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        padding: 0.4em 0.75em;
        border-radius: 999px;
        border: 1px solid var(--line);
        background: rgba(255,255,255,0.06);
        color: var(--muted);
    }

    #netBadge .dot { width: 0.7em; height: 0.7em; border-radius: 50%; background: var(--ok); }
    #netBadge.off .dot { background: var(--bad); }
    #netBadge.off { color: var(--bad); border-color: rgba(251,113,133,0.35); }

    /* ---------- PANEL PENYANGGA (ngambang) ---------- */
    #panel {
        position: absolute;
        top: 22vh;
        right: var(--pad);
        width: clamp(300px, 25vw, 620px);
        max-height: 74vh;
        display: flex;
        flex-direction: column;
        background: var(--glass);
        border: 1px solid var(--line);
        border-radius: clamp(16px, 1.3vw, 28px);
        backdrop-filter: blur(18px) saturate(140%);
        -webkit-backdrop-filter: blur(18px) saturate(140%);
        box-shadow: 0 clamp(14px, 1.6vw, 34px) clamp(30px, 3vw, 70px) rgba(0,0,0,0.45);
        z-index: 8;
        overflow: hidden;
    }

    #panel.min .panelBody { display: none; }
    #panel.min #panelMinRow { display: flex; }
    #panelMinRow {
        display: none;
        align-items: center;
        justify-content: space-between;
        padding: var(--fs-sm);
        font-size: var(--fs-xs);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.14em;
        color: var(--muted);
    }

    #panelHead {
        display: flex;
        align-items: center;
        gap: 0.6em;
        padding: clamp(10px, 0.85vw, 18px) clamp(12px, 1vw, 20px);
        border-bottom: 1px solid var(--line);
        background: rgba(255,255,255,0.04);
        cursor: grab;
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
    }

    #panelHead:active { cursor: grabbing; }

    #panelHead .grip { color: var(--muted); font-size: var(--fs-sm); letter-spacing: -2px; }

    #panelTitle {
        flex: 1;
        font-size: var(--fs-xs);
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.16em;
        color: var(--ink);
    }

    .iconBtn {
        width: clamp(26px, 2vw, 42px);
        height: clamp(26px, 2vw, 42px);
        display: grid;
        place-items: center;
        border-radius: 50%;
        border: 1px solid var(--line);
        background: rgba(255,255,255,0.06);
        color: var(--ink);
        font-size: var(--fs-sm);
        line-height: 1;
        cursor: pointer;
        transition: background 0.15s ease, transform 0.1s ease;
    }

    .iconBtn:hover { background: rgba(255,255,255,0.16); }
    .iconBtn:active { transform: scale(0.92); }

    .panelBody {
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: clamp(10px, 0.9vw, 18px);
        display: flex;
        flex-direction: column;
        gap: clamp(9px, 0.8vw, 16px);
    }

    .panelBody::-webkit-scrollbar { width: 6px; }
    .panelBody::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.18); border-radius: 99px; }

    .sectionLabel {
        font-size: var(--fs-xs);
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: 0.6em;
    }

    .sectionLabel::after {
        content: "";
        flex: 1;
        height: 1px;
        background: var(--line);
    }

    .grid { display: grid; gap: clamp(8px, 0.7vw, 14px); }
    .grid.c2 { grid-template-columns: 1fr 1fr; }
    .grid.c3 { grid-template-columns: repeat(3, 1fr); }

    .stat {
        padding: clamp(9px, 0.8vw, 16px);
        border-radius: clamp(12px, 0.9vw, 18px);
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.09);
    }

    .stat .k {
        font-size: var(--fs-xs);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--muted);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5em;
    }

    .stat .v {
        font-size: var(--fs-lg);
        font-weight: 800;
        line-height: 1.05;
        letter-spacing: -0.02em;
        margin-top: 0.15em;
        word-break: break-word;
    }

    .stat .v.sm { font-size: var(--fs-md); }

    .pill {
        font-size: var(--fs-xs);
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 0.25em 0.6em;
        border-radius: 999px;
        white-space: nowrap;
    }

    .pill.ok { color: var(--ok); background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.3); }
    .pill.warn { color: var(--warn); background: rgba(251,191,36,0.12); border: 1px solid rgba(251,191,36,0.3); }
    .pill.bad { color: var(--bad); background: rgba(251,113,133,0.12); border: 1px solid rgba(251,113,133,0.3); }
    .pill.idle { color: var(--muted); background: rgba(255,255,255,0.06); border: 1px solid var(--line); }

    .bar {
        height: clamp(6px, 0.5vw, 12px);
        border-radius: 99px;
        background: rgba(255,255,255,0.1);
        overflow: hidden;
    }

    .bar > i {
        display: block;
        height: 100%;
        width: 0%;
        border-radius: 99px;
        background: linear-gradient(90deg, var(--brand), #a78bfa);
        transition: width 0.6s ease;
    }

    /* ---------- NOW PLAYING ---------- */
    #np {
        display: flex;
        align-items: center;
        gap: clamp(10px, 0.9vw, 18px);
        padding: clamp(10px, 0.9vw, 18px);
        border-radius: clamp(12px, 0.9vw, 18px);
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.09);
    }

    #npArt {
        width: clamp(38px, 3vw, 72px);
        height: clamp(38px, 3vw, 72px);
        flex: none;
        border-radius: clamp(10px, 0.8vw, 16px);
        background: linear-gradient(135deg, #7c9cff, #a78bfa);
        display: grid;
        place-items: center;
        font-size: var(--fs-lg);
    }

    #npText { min-width: 0; flex: 1; }

    #npTitle {
        font-size: var(--fs-md);
        font-weight: 800;
        line-height: 1.15;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    #npSource {
        font-size: var(--fs-xs);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--brand);
        margin-top: 0.35em;
    }

    #npControls { display: flex; gap: 0.45em; flex: none; }

    /* ---------- RESIZE HANDLE ---------- */
    #resizeGrip {
        position: absolute;
        right: 0;
        bottom: 0;
        width: clamp(22px, 1.8vw, 40px);
        height: clamp(22px, 1.8vw, 40px);
        cursor: nwse-resize;
        touch-action: none;
        z-index: 3;
        background:
            linear-gradient(135deg, transparent 0 46%, rgba(255,255,255,0.35) 46% 54%, transparent 54% 66%, rgba(255,255,255,0.35) 66% 74%, transparent 74%);
        border-bottom-right-radius: clamp(16px, 1.3vw, 28px);
    }

    /* ---------- HINT / TOAST ---------- */
    #hint {
        position: absolute;
        left: 50%;
        bottom: 6vh;
        transform: translateX(-50%);
        padding: clamp(10px, 0.9vw, 18px) clamp(18px, 1.6vw, 34px);
        border-radius: 999px;
        background: rgba(9,11,20,0.72);
        border: 1px solid var(--line);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        font-size: var(--fs-sm);
        font-weight: 800;
        letter-spacing: 0.06em;
        z-index: 7;
        opacity: 0;
        transition: opacity 0.4s ease;
        pointer-events: none;
        white-space: nowrap;
    }

    #hint.show { opacity: 1; }

    #toast {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: clamp(12px, 1.1vw, 24px) clamp(20px, 1.8vw, 40px);
        border-radius: clamp(14px, 1.1vw, 24px);
        background: rgba(9,11,20,0.85);
        border: 1px solid var(--line);
        font-size: var(--fs-md);
        font-weight: 800;
        z-index: 9;
        opacity: 0;
        transition: opacity 0.35s ease;
        pointer-events: none;
    }

    #toast.show { opacity: 1; }

    #boot {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        background: #05060b;
        z-index: 20;
        font-size: var(--fs-md);
        font-weight: 800;
        color: var(--muted);
        letter-spacing: 0.2em;
        text-transform: uppercase;
        transition: opacity 0.6s ease;
    }

    #boot.hide { opacity: 0; pointer-events: none; }
</style>
</head>
<body>
<div id="stage">
    <div id="playerWrap"><div id="player"></div></div>
    <div id="scrim"></div>

    <div id="hud">
        <div id="clock">--:--</div>
        <div id="hudMeta">
            <div id="hudDate">—</div>
            <div id="hudBrand">RentSpace AI Monitoring</div>
        </div>
        <div id="netBadge"><span class="dot"></span><span id="netText">sinkron</span></div>
    </div>

    <aside id="panel">
        <div id="panelHead">
            <span class="grip">⠿</span>
            <span id="panelTitle">AI Monitoring</span>
            <button class="iconBtn" id="btnRefresh" title="Refresh (R)">⟳</button>
            <button class="iconBtn" id="btnMin" title="Minimize">–</button>
            <button class="iconBtn" id="btnHide" title="Sembunyikan (H)">✕</button>
        </div>

        <div id="panelMinRow">
            <span>AI Monitoring</span>
            <button class="iconBtn" id="btnRestore" title="Perbesar">□</button>
        </div>

        <div class="panelBody">
            <div>
                <div class="sectionLabel">AI Engine</div>
                <div class="grid c2" style="margin-top:.6em">
                    <div class="stat">
                        <div class="k">Model <span class="pill idle" id="aiKey">—</span></div>
                        <div class="v sm" id="aiModel">—</div>
                    </div>
                    <div class="stat">
                        <div class="k">Gateway <span class="pill idle" id="aiGateway">—</span></div>
                        <div class="v sm" id="aiGatewayText">—</div>
                    </div>
                </div>
            </div>

            <div>
                <div class="sectionLabel">Aktivitas</div>
                <div class="grid c3" style="margin-top:.6em">
                    <div class="stat">
                        <div class="k">Sesi</div>
                        <div class="v" id="aiSessions">0</div>
                    </div>
                    <div class="stat">
                        <div class="k">Pesan</div>
                        <div class="v" id="aiMessages">0</div>
                    </div>
                    <div class="stat">
                        <div class="k">Token</div>
                        <div class="v" id="aiTokens">0</div>
                    </div>
                </div>
            </div>

            <div>
                <div class="sectionLabel">Agent</div>
                <div class="grid c3" style="margin-top:.6em">
                    <div class="stat">
                        <div class="k">CS <span class="pill idle" id="csDot">—</span></div>
                        <div class="v sm" id="csText">—</div>
                    </div>
                    <div class="stat">
                        <div class="k">Report <span class="pill idle" id="rpDot">—</span></div>
                        <div class="v sm" id="rpText">—</div>
                    </div>
                    <div class="stat">
                        <div class="k">Core <span class="pill idle" id="coreDot">—</span></div>
                        <div class="v sm" id="coreText">—</div>
                    </div>
                </div>
            </div>

            <div>
                <div class="sectionLabel">Unit</div>
                <div class="grid c2" style="margin-top:.6em">
                    <div class="stat">
                        <div class="k">Terisi / Aktif <span class="pill ok" id="occPill">—</span></div>
                        <div class="v" id="unitsOcc">0 / 0</div>
                        <div class="bar" style="margin-top:.5em"><i id="occBar"></i></div>
                    </div>
                    <div class="stat">
                        <div class="k">Tersedia</div>
                        <div class="v" id="unitsFree">0</div>
                    </div>
                </div>
            </div>

            <div>
                <div class="sectionLabel">Transaksi</div>
                <div class="grid c2" style="margin-top:.6em">
                    <div class="stat">
                        <div class="k">Hari Ini</div>
                        <div class="v" id="trxToday">0</div>
                        <div class="v sm" style="color:var(--ok)" id="trxTodayRevenue">Rp0</div>
                    </div>
                    <div class="stat">
                        <div class="k">Bulan Ini</div>
                        <div class="v sm" id="trxMonth">Rp0</div>
                        <div class="v sm" style="color:var(--muted)" id="trxPending">0 pending</div>
                    </div>
                </div>
            </div>

            <div>
                <div class="sectionLabel">Now Playing</div>
                <div id="np" style="margin-top:.6em">
                    <div id="npArt">♪</div>
                    <div id="npText">
                        <div id="npTitle">Menyiapkan playlist…</div>
                        <div id="npSource">—</div>
                    </div>
                    <div id="npControls">
                        <button class="iconBtn" id="btnMute" title="Bisukan (M)">🔊</button>
                        <button class="iconBtn" id="btnNext" title="Lanjut (N)">⏭</button>
                        <button class="iconBtn" id="btnFs" title="Layar Penuh (F)">⛶</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="resizeGrip"></div>
    </aside>

    <div id="hint">Klik layar untuk mengaktifkan suara</div>
    <div id="toast"></div>
    <div id="boot">Memuat…</div>
</div>

<script>
(function () {
    'use strict';

    var TOKEN = @json(request()->route('token'));
    var REFRESH = @json($refreshSeconds);
    var BASE = '/tv/' + encodeURIComponent(TOKEN);

    var el = function (id) { return document.getElementById(id); };

    var state = {
        player: null,
        ready: false,
        tracks: [],
        index: -1,
        lastVideoId: null,
        muted: true,
        serverOffset: 0,
        consecutiveErrors: 0,
        idleTimer: null
    };

    /* ---------------- FORMAT ---------------- */
    var nf = new Intl.NumberFormat('id-ID');
    var cf = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });

    function num(v) { return nf.format(Number(v || 0)); }
    function rp(v) { return cf.format(Number(v || 0)); }

    function toast(msg) {
        var t = el('toast');
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(function () { t.classList.remove('show'); }, 1600);
    }

    function setPill(node, stateName, text) {
        var map = {
            running: 'ok', working: 'ok', ok: 'ok',
            idle: 'warn', break: 'idle', sleeping: 'idle',
            offline: 'bad', error: 'bad', pending: 'warn'
        };
        node.className = 'pill ' + (map[stateName] || 'idle');
        node.textContent = text || stateName;
    }

    function agentLabel(status) {
        if (status === 'working') return 'kerja';
        if (status === 'sleeping') return 'tidur';
        return 'istirahat';
    }

    /* ---------------- VIDEO FIT (cover 16:9 -> layar TV) ---------------- */
    var vw = 1280, vh = 720;

    function readViewport() {
        vw = window.innerWidth;
        vh = window.innerHeight;
    }

    function fitPlayer() {
        var frame = document.querySelector('#playerWrap iframe') || el('player');
        if (!frame) return;

        var h = Math.max(vh, vw * 9 / 16);
        var w = h * 16 / 9;

        frame.style.width = w + 'px';
        frame.style.height = h + 'px';
        frame.style.left = Math.round((vw - w) / 2) + 'px';
        frame.style.top = Math.round((vh - h) / 2) + 'px';
    }

    function layout() {
        readViewport();
        fitPlayer();
    }

    window.addEventListener('resize', layout);
    window.addEventListener('orientationchange', function () { setTimeout(layout, 250); });

    /* ---------------- YOUTUBE ---------------- */
    function onYouTubeIframeAPIReady() {
        state.player = new YT.Player('player', {
            height: '100%',
            width: '100%',
            videoId: '',
            playerVars: {
                autoplay: 1,
                controls: 0,
                disablekb: 1,
                fs: 0,
                modestbranding: 1,
                rel: 0,
                playsinline: 1,
                iv_load_policy: 3,
                origin: window.location.origin
            },
            events: {
                onReady: function () {
                    state.ready = true;
                    state.player.mute();
                    state.muted = true;
                    syncMuteIcon();
                    layout();
                    loadTracks(true);
                    setTimeout(function () { el('boot').classList.add('hide'); }, 5000);
                },
                onStateChange: onPlayerStateChange,
                onError: function () { skipBroken(); }
            }
        });
    }

    window.onYouTubeIframeAPIReady = onYouTubeIframeAPIReady;

    function onPlayerStateChange(e) {
        if (e.data === YT.PlayerState.ENDED) {
            playNext();
        }

        if (e.data === YT.PlayerState.PLAYING) {
            el('boot').classList.add('hide');
            state.consecutiveErrors = 0;
        }
    }

    function skipBroken() {
        state.consecutiveErrors++;

        if (state.consecutiveErrors > 6 || state.tracks.length <= 1) {
            toast('Playlist bermasalah');
            return;
        }

        playNext();
    }

    function shuffle(arr) {
        for (var i = arr.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var t = arr[i]; arr[i] = arr[j]; arr[j] = t;
        }
        return arr;
    }

    function loadTracks(firstRun) {
        fetch(BASE + '/tracks', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.server_time) applyServerTime(data.server_time);

                state.tracks = (data && data.tracks) ? data.tracks : [];

                if (!state.tracks.length) {
                    el('npTitle').textContent = 'Playlist belum tersedia';
                    el('npSource').textContent = 'cek koneksi server';
                    return;
                }

                state.tracks = shuffle(state.tracks);
                state.index = -1;

                if (firstRun) playNext();
            })
            .catch(function () {
                el('npTitle').textContent = 'Gagal memuat playlist';
            });
    }

    function playNext() {
        if (!state.ready || !state.tracks.length) return;

        state.index = (state.index + 1) % state.tracks.length;

        var guard = 0;
        var track = state.tracks[state.index];

        while (track.video_id === state.lastVideoId && state.tracks.length > 1 && guard < state.tracks.length) {
            state.index = (state.index + 1) % state.tracks.length;
            track = state.tracks[state.index];
            guard++;
        }

        state.lastVideoId = track.video_id;

        el('npTitle').textContent = track.title;
        el('npSource').textContent = track.source;

        try {
            state.player.loadVideoById({
                videoId: track.video_id,
                startSeconds: 0
            });
        } catch (e) {
            toast('Player belum siap');
        }
    }

    /* ---------------- STATS ---------------- */
    function applyServerTime(iso) {
        var diff = Date.now() - new Date(iso).getTime();
        if (isFinite(diff)) state.serverOffset = diff;
    }

    function loadStats() {
        fetch(BASE + '/stats', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d || !d.ai) throw new Error('bad payload');
                applyServerTime(d.server_time);
                paintStats(d);
                setNet(true);
            })
            .catch(function () { setNet(false); });
    }

    function setNet(online) {
        var b = el('netBadge');
        b.classList.toggle('off', !online);
        el('netText').textContent = online ? 'sinkron' : 'offline';
    }

    function paintStats(d) {
        var ai = d.ai, u = d.units, r = d.rentals;

        el('aiModel').textContent = ai.model || '—';
        setPill(el('aiKey'), ai.key_ok ? 'running' : 'error', ai.key_ok ? 'api ok' : 'no api');

        setPill(el('aiGateway'), ai.gateway, ai.gateway);
        el('aiGatewayText').textContent = ai.gateway === 'running' ? 'nyambung' : 'gantung';
        el('aiGatewayText').style.color = ai.gateway === 'running' ? 'var(--ok)' : 'var(--bad)';

        el('aiSessions').textContent = num(ai.sessions);
        el('aiMessages').textContent = num(ai.messages);
        el('aiTokens').textContent = num(ai.tokens);

        setPill(el('csDot'), ai.cs, ai.cs === 'working' ? 'on' : 'off');
        el('csText').textContent = agentLabel(ai.cs);
        setPill(el('rpDot'), ai.report, ai.report === 'working' ? 'on' : 'off');
        el('rpText').textContent = agentLabel(ai.report);
        setPill(el('coreDot'), ai.core, ai.core === 'working' ? 'on' : 'off');
        el('coreText').textContent = agentLabel(ai.core);

        el('unitsOcc').textContent = num(u.rented) + ' / ' + num(u.active);
        el('unitsFree').textContent = num(u.available);
        var pct = u.active > 0 ? Math.round((u.rented / u.active) * 100) : 0;
        el('occBar').style.width = Math.min(100, pct) + '%';
        setPill(el('occPill'), pct >= 80 ? 'ok' : (pct >= 40 ? 'warn' : 'idle'), pct + '%');

        el('trxToday').textContent = num(r.today_count) + ' trx';
        el('trxTodayRevenue').textContent = rp(r.today_revenue);
        el('trxMonth').textContent = rp(r.month_revenue);
        el('trxPending').textContent = num(r.pending_count) + ' pending · ' + num(r.active_count) + ' aktif';
    }

    /* ---------------- CLOCK ---------------- */
    var DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    function tickClock() {
        var now = new Date(Date.now() - state.serverOffset);
        var hh = now.getHours();
        var mm = now.getMinutes();
        el('clock').textContent = (hh < 10 ? '0' + hh : hh) + ':' + (mm < 10 ? '0' + mm : mm) + ':' + pad(now.getSeconds());

        el('hudDate').textContent = DAYS[now.getDay()] + ', ' + now.getDate() + ' ' + MONTHS[now.getMonth()] + ' ' + now.getFullYear();
    }

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    /* ---------------- PANEL: DRAG + RESIZE + MIN ---------------- */
    var panel = el('panel');
    var head = el('panelHead');
    var grip = el('resizeGrip');
    var STORE_KEY = 'rentspace.tv.panel';

    function loadPanelState() {
        try {
            var saved = JSON.parse(localStorage.getItem(STORE_KEY) || 'null');
            if (!saved) return;
            if (typeof saved.x === 'number' && typeof saved.y === 'number') {
                panel.style.left = clampPx(saved.x, 0, Math.max(0, vw - panel.offsetWidth));
                panel.style.top = clampPx(saved.y, 0, Math.max(0, vh - 60));
                panel.style.right = 'auto';
            }
            if (typeof saved.w === 'number') panel.style.width = saved.w + 'px';
            if (saved.min) panel.classList.add('min');
            if (saved.hidden) panel.style.display = 'none';
        } catch (e) { /* ignore */ }
    }

    function savePanelState() {
        try {
            localStorage.setItem(STORE_KEY, JSON.stringify({
                x: parseFloat(panel.style.left),
                y: parseFloat(panel.style.top),
                w: parseFloat(panel.style.width),
                min: panel.classList.contains('min'),
                hidden: panel.style.display === 'none'
            }));
        } catch (e) { /* ignore */ }
    }

    function clampPx(v, min, max) {
        if (!isFinite(v)) return min;
        return Math.max(min, Math.min(max, v));
    }

    function dragBy(e) {
        var startX = e.clientX, startY = e.clientY;
        var rect = panel.getBoundingClientRect();
        var offX = startX - rect.left, offY = startY - rect.top;

        function move(ev) {
            var x = clampPx(ev.clientX - offX, 8, vw - panel.offsetWidth - 8);
            var y = clampPx(ev.clientY - offY, 8, vh - 56);
            panel.style.left = x + 'px';
            panel.style.top = y + 'px';
            panel.style.right = 'auto';
        }

        function up() {
            document.removeEventListener('pointermove', move);
            document.removeEventListener('pointerup', up);
            savePanelState();
        }

        document.addEventListener('pointermove', move);
        document.addEventListener('pointerup', up);
    }

    head.addEventListener('pointerdown', function (e) {
        if (e.target.closest('.iconBtn')) return;
        dragBy(e);
    });

    grip.addEventListener('pointerdown', function (e) {
        e.preventDefault();
        var startX = e.clientX, startY = e.clientY;
        var w0 = panel.offsetWidth, h0 = panel.offsetHeight;
        var rect = panel.getBoundingClientRect();

        function move(ev) {
            var w = clampPx(w0 + (ev.clientX - startX), 260, vw - rect.left - 12);
            var h = clampPx(h0 + (ev.clientY - startY), 180, vh - rect.top - 12);
            panel.style.width = w + 'px';
            panel.style.height = h + 'px';
            panel.style.maxHeight = 'none';
        }

        function up() {
            document.removeEventListener('pointermove', move);
            document.removeEventListener('pointerup', up);
            panel.style.height = 'auto';
            savePanelState();
        }

        document.addEventListener('pointermove', move);
        document.addEventListener('pointerup', up);
    });

    el('btnMin').addEventListener('click', function () {
        panel.classList.toggle('min');
        savePanelState();
    });

    el('btnRestore').addEventListener('click', function () {
        panel.classList.remove('min');
        savePanelState();
    });

    el('btnHide').addEventListener('click', function () {
        panel.style.display = panel.style.display === 'none' ? '' : 'none';
        savePanelState();
        toast(panel.style.display === 'none' ? 'Panel disembunyikan (H)' : 'Panel tampil');
    });

    /* ---------------- CONTROLS ---------------- */
    function toggleMute() {
        if (!state.ready) return;
        state.muted = !state.muted;

        if (state.muted) state.player.mute();
        else state.player.unMute();

        syncMuteIcon();
    }

    function syncMuteIcon() {
        el('btnMute').textContent = state.muted ? '🔇' : '🔊';
    }

    function tryUnmute() {
        if (!state.ready) return;
        state.player.unMute();
        state.muted = false;
        syncMuteIcon();
    }

    el('btnMute').addEventListener('click', function (e) { e.stopPropagation(); toggleMute(); });
    el('btnNext').addEventListener('click', function (e) { e.stopPropagation(); playNext(); });
    el('btnRefresh').addEventListener('click', function (e) {
        e.stopPropagation();
        loadStats();
        loadTracks(false);
        toast('Data diperbarui');
    });
    el('btnFs').addEventListener('click', function (e) { e.stopPropagation(); toggleFullscreen(); });

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            (document.documentElement.requestFullscreen || function () {}).call(document.documentElement);
        } else if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }

    document.addEventListener('click', function () {
        tryUnmute();
        el('hint').classList.remove('show');
    });

    document.addEventListener('fullscreenchange', function () {
        setTimeout(function () { layout(); }, 120);
    });

    document.addEventListener('keydown', function (e) {
        var k = e.key.toLowerCase();

        if (k === 'f') { toggleFullscreen(); }
        else if (k === 'h') { panel.style.display = panel.style.display === 'none' ? '' : 'none'; savePanelState(); }
        else if (k === 'n') { playNext(); }
        else if (k === 'm') { toggleMute(); }
        else if (k === 'r') { loadStats(); loadTracks(false); }
        else if (k === ' ') {
            e.preventDefault();
            if (state.ready) {
                if (state.player.getPlayerState() === YT.PlayerState.PLAYING) state.player.pauseVideo();
                else state.player.playVideo();
            }
        }
    });

    /* ---------------- IDLE CURSOR ---------------- */
    function wake() {
        document.body.classList.remove('idle');
        clearTimeout(state.idleTimer);
        state.idleTimer = setTimeout(function () { document.body.classList.add('idle'); }, 3000);
    }

    document.addEventListener('mousemove', wake);
    document.addEventListener('touchstart', wake, { passive: true });

    /* ---------------- SELF HEAL ---------------- */
    setInterval(function () {
        if (state.ready && state.player.getPlayerState() === YT.PlayerState.UNSTARTED) {
            el('boot').classList.remove('hide');
            loadTracks(true);
        }
    }, 60000);

    window.addEventListener('offline', function () { setNet(false); });
    window.addEventListener('online', function () { loadStats(); loadTracks(false); });

    /* ---------------- BOOT ---------------- */
    layout();
    tickClock();
    setInterval(tickClock, 1000);
    loadStats();
    setInterval(loadStats, REFRESH * 1000);
    setInterval(function () { loadTracks(false); }, 30 * 60 * 1000);
    loadPanelState();
    wake();
    el('hint').classList.add('show');

    var tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    document.head.appendChild(tag);
})();
</script>
</body>
</html>