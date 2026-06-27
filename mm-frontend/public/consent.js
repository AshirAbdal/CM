/* Majestic Marquees - GDPR cookie consent engine (vanilla JS, ES5).
 *
 * Responsibilities:
 *   - Store the visitor's consent choices in a first-party cookie (mm_consent).
 *   - Show the consent banner (Layer 1) until a choice is made; open the
 *     preferences panel (Layer 2) on request.
 *   - Gate non-essential third parties so NOTHING loads before consent:
 *       functional -> Google Fonts
 *       location   -> Google Maps embed (contact page)
 *       analytics  -> Google Analytics 4   (loads only when an ID is set)
 *       marketing  -> Meta (Facebook) Pixel (loads only when an ID is set)
 *   - Expose window.MMConsent for the rest of the site + future trackers.
 *   - Google Consent Mode v2: default-deny, update on consent.
 *
 * The banner markup is rendered server-side (lib/consent.php -> render_cookie_consent()).
 * Tracker IDs come from window.MM_TRACKERS (empty by default - set them later
 * and the loader activates automatically once the category is granted).
 */
(function () {
    'use strict';

    /* ── Config ─────────────────────────────────────────────── */
    var COOKIE_NAME = 'mm_consent';
    var COOKIE_DAYS = 365;                       // 12 months, then re-ask
    var CFG     = window.MM_CONSENT || {};
    var VERSION = CFG.version || '1';            // bump in PHP to force re-consent
    var TRACKERS = window.MM_TRACKERS || {};     // { ga4: 'G-XXXX', metaPixel: '123' }

    // Categories. `necessary` is always on and cannot be turned off.
    var CATEGORIES = ['necessary', 'functional', 'location', 'analytics', 'marketing'];

    /* ── Google Consent Mode v2: deny everything until the
          visitor decides (correct default for GA4 / Google Ads). ─ */
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = window.gtag || gtag;
    gtag('consent', 'default', {
        ad_storage: 'denied',
        analytics_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        functionality_storage: 'granted',
        security_storage: 'granted',
        wait_for_update: 500
    });

    /* ── State ──────────────────────────────────────────────── */
    var state = null;                 // { necessary:1, functional:0, ... } or null = undecided
    var grantedCallbacks = {};        // cat -> [fn]

    /* ── Cookie helpers ─────────────────────────────────────── */
    function writeCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + days * 864e5);
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = name + '=' + encodeURIComponent(value) +
            '; Expires=' + d.toUTCString() + '; Path=/; SameSite=Lax' + secure;
    }
    function readCookie(name) {
        var parts = document.cookie ? document.cookie.split('; ') : [];
        for (var i = 0; i < parts.length; i++) {
            var p = parts[i].split('=');
            if (p[0] === name) { return decodeURIComponent(p.slice(1).join('=')); }
        }
        return null;
    }

    function loadState() {
        var raw = readCookie(COOKIE_NAME);
        if (!raw) { return null; }
        try {
            var obj = JSON.parse(raw);
            if (!obj || obj.v !== VERSION || !obj.cats) { return null; } // version changed -> re-ask
            var s = { necessary: 1 };
            for (var i = 0; i < CATEGORIES.length; i++) {
                var c = CATEGORIES[i];
                s[c] = c === 'necessary' ? 1 : (obj.cats[c] ? 1 : 0);
            }
            return s;
        } catch (e) { return null; }
    }

    function persist(s) {
        var cats = {};
        for (var i = 0; i < CATEGORIES.length; i++) { cats[CATEGORIES[i]] = s[CATEGORIES[i]] ? 1 : 0; }
        var payload = { v: VERSION, ts: new Date().toISOString(), cats: cats };
        writeCookie(COOKIE_NAME, JSON.stringify(payload), COOKIE_DAYS);
        logConsent(payload);
    }

    /* ── Proof-of-consent log (best-effort, never blocks UX) ── */
    function logConsent(payload) {
        try {
            var body = JSON.stringify(payload);
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/api/consent-log', new Blob([body], { type: 'application/json' }));
            } else {
                fetch('/api/consent-log', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: body,
                    keepalive: true
                }).catch(function () {});
            }
        } catch (e) { /* ignore */ }
    }

    /* ── Apply consent: activate granted categories ─────────── */
    function applyConsentMode(s) {
        gtag('consent', 'update', {
            analytics_storage: s.analytics ? 'granted' : 'denied',
            ad_storage: s.marketing ? 'granted' : 'denied',
            ad_user_data: s.marketing ? 'granted' : 'denied',
            ad_personalization: s.marketing ? 'granted' : 'denied'
        });
    }

    var loaded = {}; // guard so each tracker loads once
    function loadGA4(id) {
        if (!id || loaded.ga4) { return; }
        loaded.ga4 = true;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
        document.head.appendChild(s);
        gtag('js', new Date());
        gtag('config', id, { anonymize_ip: true });
    }
    function loadMetaPixel(id) {
        if (!id || loaded.pixel) { return; }
        loaded.pixel = true;
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
            t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
        }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
        window.fbq('init', id);
        window.fbq('track', 'PageView');
    }
    function loadFonts() {
        if (loaded.fonts || !CFG.fonts) { return; }
        loaded.fonts = true;
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = CFG.fonts;
        document.head.appendChild(l);
    }

    function fireGranted(cat) {
        var list = grantedCallbacks[cat];
        if (!list) { return; }
        for (var i = 0; i < list.length; i++) { try { list[i](); } catch (e) {} }
        grantedCallbacks[cat] = [];
    }

    function apply(s) {
        state = s;
        applyConsentMode(s);
        if (s.functional) { loadFonts(); }
        if (s.analytics)  { loadGA4(TRACKERS.ga4 || ''); }
        if (s.marketing)  { loadMetaPixel(TRACKERS.metaPixel || ''); }
        for (var i = 0; i < CATEGORIES.length; i++) {
            if (s[CATEGORIES[i]]) { fireGranted(CATEGORIES[i]); }
        }
        scan(document);
    }

    /* ── Declarative gating ─────────────────────────────────────
       <script type="text/plain" data-consent="analytics" data-src="...">
       [data-consent-placeholder="location"] with data-src on a child iframe. */
    function activateScripts(root, cat) {
        var nodes = root.querySelectorAll('script[type="text/plain"][data-consent="' + cat + '"]:not([data-consent-done])');
        Array.prototype.forEach.call(nodes, function (old) {
            old.setAttribute('data-consent-done', '1');
            var s = document.createElement('script');
            if (old.getAttribute('data-src')) { s.src = old.getAttribute('data-src'); }
            else { s.text = old.textContent || ''; }
            if (old.getAttribute('data-type')) { s.type = old.getAttribute('data-type'); }
            document.head.appendChild(s);
        });
    }
    function activatePlaceholders(root, cat) {
        var ph = root.querySelectorAll('[data-consent-placeholder="' + cat + '"]:not([data-consent-loaded])');
        Array.prototype.forEach.call(ph, function (el) {
            el.setAttribute('data-consent-loaded', '1');
            var src = el.getAttribute('data-src');
            var title = el.getAttribute('data-title') || '';
            if (src) {
                var iframe = document.createElement('iframe');
                iframe.src = src;
                iframe.className = el.getAttribute('data-iframe-class') || 'w-full h-full';
                iframe.style.border = '0';
                iframe.loading = 'lazy';
                iframe.referrerPolicy = 'no-referrer-when-downgrade';
                if (title) { iframe.title = title; }
                iframe.setAttribute('allowfullscreen', '');
                el.innerHTML = '';
                el.appendChild(iframe);
            } else {
                el.style.display = '';
            }
        });
    }
    function scan(root) {
        root = root || document;
        if (!state) { return; }
        for (var i = 0; i < CATEGORIES.length; i++) {
            var c = CATEGORIES[i];
            if (state[c]) { activateScripts(root, c); activatePlaceholders(root, c); }
        }
    }

    /* ── Banner / preferences UI wiring ─────────────────────── */
    function $(sel, root) { return (root || document).querySelector(sel); }

    function banner()  { return $('[data-consent-banner]'); }
    function panel()   { return $('[data-consent-panel]'); }

    function showBanner() { var b = banner(); if (b) { b.classList.remove('hidden'); } }
    function hideBanner() { var b = banner(); if (b) { b.classList.add('hidden'); } }

    function openPanel() {
        var p = panel();
        if (!p) { return; }
        // Reflect current (or default) choices into the toggles
        var cur = state || { necessary: 1 };
        var toggles = p.querySelectorAll('input[type="checkbox"][data-cat]');
        Array.prototype.forEach.call(toggles, function (t) {
            var c = t.getAttribute('data-cat');
            t.checked = c === 'necessary' ? true : !!cur[c];
            if (c === 'necessary') { t.disabled = true; }
        });
        p.classList.remove('hidden');
        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');
        var first = p.querySelector('button, input:not([disabled])');
        if (first) { setTimeout(function () { first.focus(); }, 60); }
    }
    function closePanel() {
        var p = panel();
        if (p) { p.classList.add('hidden'); }
        document.documentElement.classList.remove('overflow-hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function fromToggles() {
        var s = { necessary: 1 };
        var p = panel();
        var toggles = p ? p.querySelectorAll('input[type="checkbox"][data-cat]') : [];
        Array.prototype.forEach.call(toggles, function (t) {
            var c = t.getAttribute('data-cat');
            if (c !== 'necessary') { s[c] = t.checked ? 1 : 0; }
        });
        for (var i = 0; i < CATEGORIES.length; i++) { if (!(CATEGORIES[i] in s)) { s[CATEGORIES[i]] = 0; } }
        return s;
    }

    function setAll(val) {
        var s = { necessary: 1 };
        for (var i = 0; i < CATEGORIES.length; i++) { s[CATEGORIES[i]] = CATEGORIES[i] === 'necessary' ? 1 : (val ? 1 : 0); }
        return s;
    }

    function acceptAll() { var s = setAll(true);  persist(s); apply(s); hideBanner(); closePanel(); }
    function rejectAll() { var s = setAll(false); persist(s); apply(s); hideBanner(); closePanel(); }
    function saveChoices() { var s = fromToggles(); persist(s); apply(s); hideBanner(); closePanel(); }

    function wire() {
        document.addEventListener('click', function (e) {
            var t = e.target;
            if (!t || !t.closest) { return; }
            if (t.closest('[data-consent-accept]'))    { e.preventDefault(); acceptAll(); return; }
            if (t.closest('[data-consent-reject]'))    { e.preventDefault(); rejectAll(); return; }
            if (t.closest('[data-consent-save]'))      { e.preventDefault(); saveChoices(); return; }
            if (t.closest('[data-consent-customize]')) { e.preventDefault(); openPanel(); return; }
            if (t.closest('[data-consent-open]'))      { e.preventDefault(); openPanel(); return; }
            if (t.closest('[data-consent-close]'))     { e.preventDefault(); closePanel(); return; }
            // "Load map" style button inside a gated placeholder: grant that category.
            var loadBtn = t.closest('[data-consent-grant]');
            if (loadBtn) {
                e.preventDefault();
                var cat = loadBtn.getAttribute('data-consent-grant');
                var s = state ? cloneState(state) : setAll(false);
                s[cat] = 1;
                persist(s); apply(s);
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var p = panel();
                if (p && !p.classList.contains('hidden')) { closePanel(); }
            }
        });
    }
    function cloneState(s) { var o = {}; for (var k in s) { if (s.hasOwnProperty(k)) { o[k] = s[k]; } } return o; }

    /* ── Public API ─────────────────────────────────────────── */
    window.MMConsent = {
        has: function (cat) { return !!(state && state[cat]); },
        get: function () { return state ? cloneState(state) : null; },
        onGranted: function (cat, cb) {
            if (state && state[cat]) { try { cb(); } catch (e) {} return; }
            (grantedCallbacks[cat] = grantedCallbacks[cat] || []).push(cb);
        },
        open: openPanel,
        scan: scan,
        acceptAll: acceptAll,
        rejectAll: rejectAll
    };

    /* ── Init ───────────────────────────────────────────────── */
    function init() {
        wire();
        var saved = loadState();
        if (saved) {
            apply(saved);          // returning visitor - apply silently
            hideBanner();
        } else {
            showBanner();          // first visit / consent expired - ask
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
