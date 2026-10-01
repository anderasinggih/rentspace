/* ------------------------------------------------------------------ *
 *  AI-monitor 3D office engine entry point.
 *
 *  Loading order on the page:
 *    1. three-office-runtime.js  (classic-ish Vite entry: window.THREE +
 *       window.OFFICE_ADDONS, then fires three-office-runtime-ready)
 *    2. this module (deferred)     -> picks up the globals and installs the
 *       engine API onto the object the Blade inline script already created.
 *
 *  Because Vite modules are deferred and the Blade engine is a classic
 *  inline script, boot() always runs AFTER window._threeOfficeApp exists,
 *  so the legacy engine can be captured and shut down cleanly.
 * ------------------------------------------------------------------ */
import './app.js';