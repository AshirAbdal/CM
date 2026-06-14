(function () {
    'use strict';

    // ── Read page-meta JSON block and update <head> ───────────────
    function updateMeta(container) {
    var metaEl = container.querySelector('#page-meta');
    if (!metaEl) return;

    var meta;
    try { meta = JSON.parse(metaEl.textContent); }
    catch (e) { return; }

    // Update <title>
    if (meta.title) {
        document.title = meta.title;
    }

    // Update <meta name="..."> tags
    if (meta.name) {
        Object.keys(meta.name).forEach(function (key) {
            var el = document.querySelector('meta[name="' + key + '"]');
            if (!el) {
                el = document.createElement('meta');
                el.setAttribute('name', key);
                document.head.appendChild(el);
            }
            el.setAttribute('content', meta.name[key]);
        });
    }

    // Update <meta property="..."> tags (og:, twitter:, etc.)
    if (meta.property) {
        Object.keys(meta.property).forEach(function (key) {
            var el = document.querySelector('meta[property="' + key + '"]');
            if (!el) {
                el = document.createElement('meta');
                el.setAttribute('property', key);
                document.head.appendChild(el);
            }
            el.setAttribute('content', meta.property[key]);
        });
    }

    // Update schema.org structured data
    if (meta.schema) {
        var existingSchema = document.querySelector('script[type="application/ld+json"]');
        if (existingSchema) {
            existingSchema.remove();
        }
        var schemaEl = document.createElement('script');
        schemaEl.setAttribute('type', 'application/ld+json');
        schemaEl.textContent = JSON.stringify(meta.schema);
        document.head.appendChild(schemaEl);
    }
}

    // ── Update active nav (delegated to app.js / window.MM) ───────
    function updateActiveNav(pathname) {
        if (window.MM && window.MM.updateActiveNav) {
            window.MM.updateActiveNav(pathname);
        }
    }

    // ── Smooth-scroll to an in-page anchor ────────────────────────
    function scrollToHash(hash) {
        if (!hash) { window.scrollTo(0, 0); return; }
        var target = document.getElementById(hash.replace(/^#/, ''));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            window.scrollTo(0, 0);
        }
    }

    // ── Fetch a page fragment and inject into #content ────────────
    function navigate(url, pushToHistory) {
        var hash = new URL(url, window.location.origin).hash;
        fetch(url, { headers: { 'X-SPA-Request': 'true' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var content = document.getElementById('content');
                content.innerHTML = html;

                // Parse fragment in a temp div to extract page-meta
                var tmp = document.createElement('div');
                tmp.innerHTML = html;
                updateMeta(tmp);

                var pathname = new URL(url, window.location.origin).pathname;
                updateActiveNav(pathname);

                if (pushToHistory !== false) {
                    window.history.pushState({ url: url }, '', url);
                }

                // Re-run component behaviours on the freshly injected content
                if (window.MM && window.MM.hydrate) { window.MM.hydrate(content); }

                scrollToHash(hash);
            })
            .catch(function () {
                // Network error — fall back to full page load
                window.location.href = url;
            });
    }

    // ── Intercept clicks on spa-link anchors ──────────────────────
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a.spa-link');
        if (!link) return;
        if (link.hostname !== window.location.hostname) return;

        e.preventDefault();

        var targetPath = new URL(link.href, window.location.origin).pathname;
        var currentPath = window.location.pathname;
        var hash = new URL(link.href, window.location.origin).hash;

        // Same page with a hash → just scroll, don't refetch
        if (targetPath === currentPath && hash) {
            window.history.pushState({ url: link.href }, '', link.href);
            scrollToHash(hash);
            return;
        }
        if (link.href === window.location.href) return;

        navigate(link.href);
    });

    // ── Handle browser back / forward ─────────────────────────────
    window.addEventListener('popstate', function () {
        navigate(window.location.href, false);
    });

    // ── On initial SSR load: set active nav + read initial meta ───
    updateActiveNav(window.location.pathname);
    var initialContent = document.getElementById('content');
    if (initialContent) { updateMeta(initialContent); }

}());
