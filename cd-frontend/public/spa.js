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

    // ── Highlight the active nav link ─────────────────────────────
    function updateActiveNav(pathname) {
        document.querySelectorAll('#main-nav .spa-link').forEach(function (a) {
            var href = a.getAttribute('href');
            var isActive = href === pathname || (href === '/' && pathname === '');
            if (isActive) {
                a.style.fontWeight = 'bold';
                a.style.textDecoration = 'underline';
            } else {
                a.style.fontWeight = '';
                a.style.textDecoration = 'none';
            }
        });
    }

    // ── Fetch a page fragment and inject into #content ────────────
    function navigate(url, pushToHistory) {
        fetch(url, { headers: { 'X-SPA-Request': 'true' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                document.getElementById('content').innerHTML = html;

                // Parse fragment in a temp div to extract page-meta
                var tmp = document.createElement('div');
                tmp.innerHTML = html;
                updateMeta(tmp);

                var pathname = new URL(url, window.location.origin).pathname;
                updateActiveNav(pathname);

                if (pushToHistory !== false) {
                    window.history.pushState({ url: url }, '', url);
                }

                window.scrollTo(0, 0);
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
