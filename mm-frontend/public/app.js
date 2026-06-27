/* Majestic Marquees - vanilla JS component behaviours.
   Replaces the React components (DynamicImage, Carousel, Accordion,
   Testimonials, configurators, mobile menu) with pure DOM logic.
   window.MM.hydrate(root) is re-run after every SPA navigation. */
(function () {
    'use strict';
    /* ── Backend API config (injected by layout/page.php) ──── */
    var API_BASE = (window.MM_API && window.MM_API.base) || '';
    /* ── Carousel ───────────────────────────────────────────── */
    function arrowButton(dir) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('aria-label', dir < 0 ? 'Previous slide' : 'Next slide');
        var side = dir < 0 ? '-left-2 sm:-left-6' : '-right-2 sm:-right-6';
        btn.className = 'absolute ' + side + ' top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-forest-800/70 hover:text-forest-800 transition-colors';
        var pts = dir < 0 ? '15 6 9 12 15 18' : '9 6 15 12 9 18';
        btn.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="' + pts + '"></polyline></svg>';
        return btn;
    }

    function initCarousel(el) {
        if (el.getAttribute('data-carousel-done')) return;
        el.setAttribute('data-carousel-done', '1');

        var viewport = el.querySelector('[data-carousel-viewport]');
        var track = el.querySelector('[data-carousel-track]');
        if (!viewport || !track) return;
        var slides = Array.prototype.slice.call(track.children);
        if (!slides.length) return;

        var showArrows = el.getAttribute('data-arrows') === '1';
        var showDots = el.getAttribute('data-dots') === '1';
        var autoplayMs = parseInt(el.getAttribute('data-autoplay'), 10) || 0;
        var index = 0;

        function perView() {
            var vw = viewport.clientWidth;
            var sw = slides[0].getBoundingClientRect().width || vw;
            return Math.max(1, Math.round(vw / sw));
        }
        function maxIndex() {
            return Math.max(0, slides.length - perView());
        }
        function apply() {
            if (index > maxIndex()) index = maxIndex();
            if (index < 0) index = 0;
            var offset = slides[index] ? slides[index].offsetLeft : 0;
            track.style.transform = 'translate3d(-' + offset + 'px,0,0)';
            track.style.transition = 'transform 0.4s ease';
            updateDots();
        }

        var dotsWrap = null;
        function buildDots() {
            if (!showDots) return;
            var pages = maxIndex() + 1;
            if (pages <= 1) { if (dotsWrap) dotsWrap.remove(); dotsWrap = null; return; }
            if (!dotsWrap) {
                dotsWrap = document.createElement('div');
                dotsWrap.className = 'flex justify-center gap-2 mt-6';
                dotsWrap.setAttribute('data-carousel-dots', '');
                el.appendChild(dotsWrap);
            }
            dotsWrap.innerHTML = '';
            for (var i = 0; i < pages; i++) {
                (function (i) {
                    var dot = document.createElement('button');
                    dot.type = 'button';
                    dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                    dot.addEventListener('click', function () { index = i; apply(); restartAutoplay(); });
                    dotsWrap.appendChild(dot);
                })(i);
            }
            updateDots();
        }
        function updateDots() {
            if (!dotsWrap) return;
            Array.prototype.forEach.call(dotsWrap.children, function (dot, i) {
                dot.className = 'rounded-full transition ' + (i === index ? 'bg-forest-700 w-6 h-2' : 'bg-forest-700/30 w-2 h-2');
            });
        }

        if (showArrows) {
            var prev = arrowButton(-1);
            var next = arrowButton(1);
            prev.addEventListener('click', function () {
                index = index <= 0 ? maxIndex() : index - 1; apply(); restartAutoplay();
            });
            next.addEventListener('click', function () {
                index = index >= maxIndex() ? 0 : index + 1; apply(); restartAutoplay();
            });
            el.appendChild(prev);
            el.appendChild(next);
        }

        var autoplayTimer = null;
        function stopAutoplay() {
            if (autoplayTimer) { clearInterval(autoplayTimer); autoplayTimer = null; }
        }
        function startAutoplay() {
            stopAutoplay();
            if (autoplayMs <= 0 || maxIndex() <= 0) return;
            autoplayTimer = setInterval(function () {
                index = index >= maxIndex() ? 0 : index + 1;
                apply();
            }, autoplayMs);
        }
        function restartAutoplay() {
            if (autoplayMs > 0) startAutoplay();
        }
        el.addEventListener('mouseenter', stopAutoplay);
        el.addEventListener('mouseleave', startAutoplay);

        buildDots();
        apply();
        startAutoplay();

        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () { buildDots(); apply(); startAutoplay(); }, 150);
        });
    }

    function initCarousels(root) {
        root.querySelectorAll('[data-carousel]').forEach(initCarousel);
    }

    /* ── Accordion ──────────────────────────────────────────── */
    function initAccordions(root) {
        root.querySelectorAll('[data-accordion]:not([data-accordion-done])').forEach(function (acc) {
            acc.setAttribute('data-accordion-done', '1');
            var items = acc.querySelectorAll('[data-accordion-item]');
            items.forEach(function (item, i) {
                var btn = item.querySelector('[data-accordion-trigger]');
                var panel = item.querySelector('[data-accordion-panel]');
                var icon = item.querySelector('[data-accordion-icon]');
                if (!btn || !panel) return;
                function setOpen(open) {
                    panel.classList.toggle('hidden', !open);
                    if (icon) {
                        if (icon.getAttribute('data-icon-type') === 'plus') {
                            icon.textContent = open ? '\u2212' : '+';
                        } else {
                            icon.classList.toggle('rotate-90', open);
                        }
                    }
                }
                setOpen(i === 0);
                btn.addEventListener('click', function () {
                    var isOpen = !panel.classList.contains('hidden');
                    items.forEach(function (other) {
                        if (other === item) return;
                        var p = other.querySelector('[data-accordion-panel]');
                        var ic = other.querySelector('[data-accordion-icon]');
                        if (p) p.classList.add('hidden');
                        if (ic) {
                            if (ic.getAttribute('data-icon-type') === 'plus') ic.textContent = '+';
                            else ic.classList.remove('rotate-90');
                        }
                    });
                    setOpen(!isOpen);
                });
            });
        });
    }

    /* ── Testimonials ───────────────────────────────────────── */
    function starRating(rating) {
        var html = '<div class="flex gap-0.5 mb-5" aria-label="' + rating + ' out of 5 stars">';
        for (var n = 1; n <= 5; n++) {
            html += '<span class="' + (n <= rating ? 'text-amber-500' : 'text-forest-300/40') + '">\u2605</span>';
        }
        return html + '</div>';
    }
    function testimonialCard(t) {
        var avatar;
        if (t.avatar_url) {
            var src = /^https?:/.test(t.avatar_url) ? t.avatar_url : API_BASE.replace(/\/$/, '') + '/' + String(t.avatar_url).replace(/^\//, '');
            avatar = '<img src="' + src + '" alt="' + (t.name || '') + '" class="w-24 h-24 rounded-full object-cover" loading="lazy" onerror="this.style.display=\'none\'">';
        } else {
            avatar = '<div class="w-24 h-24 rounded-full bg-tan-200 flex items-center justify-center text-tan-600 text-2xl font-bold">' + ((t.name || '?').charAt(0).toUpperCase()) + '</div>';
        }
        return '<div class="shrink-0 grow-0 basis-full lg:basis-1/2 px-6">' +
            '<figure class="h-full flex flex-col items-start text-left">' +
            avatar +
            starRating(t.rating || 5) +
            '<blockquote><p class="text-forest-700/80 italic">\u201c' + (t.quote || '') + '\u201d</p></blockquote>' +
            '<figcaption class="mt-6">' +
            '<h4 class="text-forest-800 text-primary-ttl">' + (t.name || '') + '</h4>' +
            '<h5 class="text-body-s italic text-forest-700/70 mt-1">' + (t.role || '') + '</h5>' +
            '</figcaption></figure></div>';
    }
    function initTestimonials(root) {
        root.querySelectorAll('[data-testimonials]:not([data-testimonials-done])').forEach(function (host) {
            host.setAttribute('data-testimonials-done', '1');
            host.innerHTML = '<div class="grid lg:grid-cols-2 gap-8">' +
                '<div class="animate-pulse space-y-4 p-6"><div class="w-24 h-24 rounded-full bg-forest-200/40"></div><div class="h-4 bg-forest-200/40 rounded w-3/4"></div><div class="h-4 bg-forest-200/40 rounded w-full"></div></div>' +
                '<div class="animate-pulse space-y-4 p-6"><div class="w-24 h-24 rounded-full bg-forest-200/40"></div><div class="h-4 bg-forest-200/40 rounded w-3/4"></div><div class="h-4 bg-forest-200/40 rounded w-full"></div></div>' +
                '</div>';
            var page = host.getAttribute('data-page');
            var url = API_BASE + '/wl/public/testimonials' + (page ? '?page=' + encodeURIComponent(page) : '');
            fetch(url, { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    var items = data && Array.isArray(data.testimonials) ? data.testimonials
                              : (Array.isArray(data) ? data : []);
                    if (!items.length) {
                        host.innerHTML = '<p class="text-center text-forest-700/50 italic py-12">No testimonials yet.</p>';
                        return;
                    }
                    var slides = items.map(testimonialCard).join('');
                    host.innerHTML = '<div class="relative" data-carousel data-arrows="0" data-dots="1" data-autoplay="6000">' +
                        '<div class="overflow-hidden" data-carousel-viewport><div class="flex" data-carousel-track>' +
                        slides + '</div></div></div>';
                    initCarousels(host);
                })
                .catch(function () {
                    host.innerHTML = '<p class="text-center text-forest-700/50 italic py-12">No testimonials yet.</p>';
                });
        });
    }

    /* ── Size configurator (Stretch / Sailcloth) ────────────── */
    var SPEC_ROWS = [
        ['Size', 'size'], ['Seated', 'seated'], ['Cocktail Standing', 'cocktail'],
        ['Cinema Style', 'cinema'], ['Surface', 'surface'], ['Coating', 'coating'],
        ['Weight', 'weight'], ['Packed', 'packed'], ['Colours', 'colours']
    ];
    function initConfigurators(root) {
        root.querySelectorAll('[data-configurator]:not([data-config-done])').forEach(function (cfg) {
            cfg.setAttribute('data-config-done', '1');
            var dataEl = cfg.parentNode.querySelector('[data-config-data]') || cfg.nextElementSibling;
            if (!dataEl) return;
            var groups;
            try { groups = JSON.parse(dataEl.textContent); } catch (e) { return; }
            var defaultImages = [];
            try { defaultImages = JSON.parse(cfg.getAttribute('data-default-images') || '[]'); } catch (e) { defaultImages = []; }
            var groupsWrap = cfg.querySelector('[data-config-groups]');
            var variantsWrap = cfg.querySelector('[data-config-variants]');
            var tableBody = cfg.querySelector('[data-config-table]');
            var imagesWrap = cfg.querySelector('[data-config-images]');
            var gi = 0, vi = 0;

            function renderGroups() {
                groupsWrap.innerHTML = '';
                groups.forEach(function (g, i) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = g.label;
                    b.className = 'min-w-[120px] px-6 py-3 text-xs sm:text-sm transition-colors rounded-md ' +
                        (gi === i ? 'bg-[#a06a3f] text-cream-50 shadow-sm' : 'bg-[#f5f0e3] text-forest-800/80 hover:bg-cream-50');
                    b.addEventListener('click', function () { gi = i; vi = 0; renderAll(); });
                    groupsWrap.appendChild(b);
                });
            }
            function renderVariants() {
                variantsWrap.innerHTML = '';
                groups[gi].variants.forEach(function (v, i) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = v.label;
                    b.className = 'min-w-[100px] px-5 py-2.5 text-xs sm:text-sm transition-colors rounded-md ' +
                        (vi === i ? 'bg-[#c79a73] text-cream-50 shadow-sm' : 'bg-[#f5f0e3] text-forest-800/80 hover:bg-cream-50');
                    b.addEventListener('click', function () { vi = i; renderAll(); });
                    variantsWrap.appendChild(b);
                });
            }
            function renderTable() {
                var v = groups[gi].variants[Math.min(vi, groups[gi].variants.length - 1)];
                tableBody.innerHTML = SPEC_ROWS.map(function (row) {
                    return '<tr>' +
                        '<th scope="row" class="text-left font-normal text-forest-800 bg-[#efe5d0] border border-forest-800/40 px-5 sm:px-6 py-4 w-2/5 align-middle">' + row[0] + '</th>' +
                        '<td class="text-forest-800 bg-[#efe5d0] border border-forest-800/40 px-5 sm:px-6 py-4 align-middle">' + (v[row[1]] || '') + '</td>' +
                        '</tr>';
                }).join('');
            }
            function renderImages() {
                var v = groups[gi].variants[Math.min(vi, groups[gi].variants.length - 1)];
                var imgs = (v.images && v.images.length) ? v.images : defaultImages;
                var fallback = defaultImages.length ? defaultImages[0] : '';
                var slides;
                if (imgs.length) {
                    slides = imgs.map(function (src) {
                        var onerr = (fallback && src !== fallback)
                            ? ' onerror="this.onerror=null;this.src=\'' + fallback + '\'"'
                            : '';
                        return '<div class="shrink-0 grow-0 basis-full px-2"><img src="' + src + '" alt="' + v.label + '" class="w-full aspect-[4/3] object-cover" loading="lazy"' + onerr + '></div>';
                    }).join('');
                } else {
                    slides = '';
                    for (var i = 0; i < 4; i++) {
                        slides += '<div class="shrink-0 grow-0 basis-full px-2"><div class="aspect-[4/3] w-full flex items-center justify-center text-forest-800/40 text-xs uppercase tracking-widest">' + v.label + ' View ' + (i + 1) + '</div></div>';
                    }
                }
                imagesWrap.innerHTML = '<div class="relative [&_button]:!text-white/90 [&_button:hover]:!text-white" data-carousel data-arrows="1" data-dots="1">' +
                    '<div class="overflow-hidden" data-carousel-viewport><div class="flex" data-carousel-track>' + slides + '</div></div></div>';
                initCarousels(imagesWrap);
            }
            function renderAll() { renderGroups(); renderVariants(); renderTable(); renderImages(); }
            renderAll();
        });
    }

    /* ── Header: mobile menu + submenu (bound once) ─────────── */
    function initHeader() {
        var nav = document.getElementById('main-nav');
        if (!nav || nav.getAttribute('data-header-done')) return;
        nav.setAttribute('data-header-done', '1');

        var toggle = nav.querySelector('[data-menu-toggle]');
        var menu = nav.querySelector('[data-mobile-menu]');
        if (toggle && menu) {
            toggle.addEventListener('click', function () { menu.classList.toggle('hidden'); });
            menu.querySelectorAll('a.spa-link').forEach(function (a) {
                a.addEventListener('click', function () { menu.classList.add('hidden'); });
            });
        }
        var subToggle = nav.querySelector('[data-submenu-toggle]');
        var sub = nav.querySelector('[data-submenu]');
        if (subToggle && sub) {
            subToggle.addEventListener('click', function () {
                sub.classList.toggle('hidden');
                var svg = subToggle.querySelector('svg');
                if (svg) svg.classList.toggle('rotate-180');
            });
        }
    }

    /* ── Active nav highlighting ────────────────────────────── */
    function updateActiveNav(pathname) {
        document.querySelectorAll('#main-nav .nav-link').forEach(function (a) {
            var active = false;
            if (a.hasAttribute('data-nav-exact')) {
                active = new URL(a.href).pathname === pathname;
            } else if (a.hasAttribute('data-nav-base')) {
                active = pathname.indexOf(a.getAttribute('data-nav-base')) === 0;
            }
            if (active) {
                a.classList.add('text-tan-500');
                a.classList.remove('text-forest-800');
            } else {
                a.classList.remove('text-tan-500');
                a.classList.add('text-forest-800');
            }
        });
    }

    /* ── Google reCAPTCHA (explicit render, SPA-safe) ───────── */
    function renderRecaptcha(root) {
        root = root || document;
        if (!window.grecaptcha || !window.grecaptcha.render) return; // api.js not loaded yet - onload callback will handle it
        var widgets = root.querySelectorAll('.g-recaptcha');
        Array.prototype.forEach.call(widgets, function (el) {
            if (el.getAttribute('data-rendered')) return;
            el.setAttribute('data-rendered', '1');
            try {
                window.grecaptcha.render(el, {
                    sitekey: el.getAttribute('data-sitekey'),
                    theme: el.getAttribute('data-theme') || 'light'
                });
            } catch (e) {
                el.removeAttribute('data-rendered');
            }
        });
    }
    // Called by reCAPTCHA's api.js once it finishes loading.
    window.onloadRecaptchaCallback = function () { renderRecaptcha(document); };

    /* ── AJAX form submission (quote / contact) ─────────────────
       The verified-quote and contact forms post in place instead of
       doing a full-page reload. This keeps the visitor on the same
       section (no jump to the header), preserves the PHP session, and
       avoids the browser re-submitting the POST when the page is later
       reloaded ("session expired"). The server returns the page
       fragment; we swap just the form's section back in. */
    function setSubmitting(form, on) {
        var btn = form.querySelector('button[type="submit"], button:not([type])');
        if (on) {
            form.setAttribute('data-submitting', '1');
            if (btn) { btn.disabled = true; btn.classList.add('opacity-60', 'cursor-not-allowed'); }
        } else {
            form.removeAttribute('data-submitting');
            if (btn) { btn.disabled = false; btn.classList.remove('opacity-60', 'cursor-not-allowed'); }
        }
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        var region = form.closest('[data-ajax-form-region]');
        if (!region || !region.id) return;          // not an AJAX-managed form
        e.preventDefault();
        if (form.getAttribute('data-submitting')) return;
        setSubmitting(form, true);

        var regionId = region.id;
        var url = window.location.pathname + window.location.search;

        fetch(url, {
            method: 'POST',
            headers: { 'X-SPA-Request': 'true' },
            body: new FormData(form),
            credentials: 'same-origin'
        })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var tmp = document.createElement('div');
                tmp.innerHTML = html;
                var fresh = tmp.querySelector('#' + (window.CSS && CSS.escape ? CSS.escape(regionId) : regionId));
                if (!fresh) { window.location.reload(); return; }
                region.replaceWith(fresh);
                if (window.MM && window.MM.hydrate) { window.MM.hydrate(fresh); }
            })
            .catch(function () {
                // Network error - fall back to a normal full-page submit.
                setSubmitting(form, false);
                form.submit();
            });
    });

    /* ── Review submission form ──────────────────── */
    function initReviewForm(root) {
        root.querySelectorAll('[data-review-form]:not([data-review-form-done])').forEach(function (form) {
            form.setAttribute('data-review-form-done', '1');

            var stars   = form.querySelectorAll('[data-star]');
            var ratingI = form.querySelector('[data-review-rating]');
            var statusE = form.querySelector('[data-review-status]');
            var submitB = form.querySelector('[data-review-submit]');
            var current = 0;

            function paint(val) {
                stars.forEach(function (s) {
                    var n = parseInt(s.getAttribute('data-star'), 10);
                    s.classList.toggle('text-amber-500', n <= val);
                    s.classList.toggle('text-forest-300/40', n > val);
                });
            }
            stars.forEach(function (s) {
                var n = parseInt(s.getAttribute('data-star'), 10);
                s.addEventListener('mouseenter', function () { paint(n); });
                s.addEventListener('click', function () {
                    current = n;
                    if (ratingI) { ratingI.value = String(n); }
                    paint(n);
                });
            });
            form.addEventListener('mouseleave', function () { paint(current); });

            /* photo picker (optional) */
            var photoInput   = form.querySelector('[data-review-photo]');
            var photoPreview = form.querySelector('[data-review-photo-preview]');
            var photoIcon    = form.querySelector('[data-review-photo-icon]');
            var photoClear   = form.querySelector('[data-review-photo-clear]');
            var photoData    = '';
            var PHOTO_MAX    = 3 * 1024 * 1024;
            var PHOTO_TYPES  = ['image/jpeg', 'image/png', 'image/webp'];

            function clearPhoto() {
                photoData = '';
                if (photoInput)   { photoInput.value = ''; }
                if (photoPreview) { photoPreview.src = ''; photoPreview.classList.add('hidden'); }
                if (photoIcon)    { photoIcon.classList.remove('hidden'); }
                if (photoClear)   { photoClear.classList.add('hidden'); }
            }
            if (photoClear) { photoClear.addEventListener('click', clearPhoto); }
            if (photoInput) {
                photoInput.addEventListener('change', function () {
                    var file = photoInput.files && photoInput.files[0];
                    if (!file) { clearPhoto(); return; }
                    if (PHOTO_TYPES.indexOf(file.type) === -1) {
                        clearPhoto();
                        setStatus('Please choose a JPG, PNG or WebP image.', false);
                        return;
                    }
                    if (file.size > PHOTO_MAX) {
                        clearPhoto();
                        setStatus('That image is too large (max 3MB).', false);
                        return;
                    }
                    var reader = new FileReader();
                    reader.onload = function () {
                        photoData = String(reader.result || '');
                        if (photoPreview) { photoPreview.src = photoData; photoPreview.classList.remove('hidden'); }
                        if (photoIcon)    { photoIcon.classList.add('hidden'); }
                        if (photoClear)   { photoClear.classList.remove('hidden'); }
                    };
                    reader.onerror = function () { clearPhoto(); setStatus('Could not read that image. Please try another.', false); };
                    reader.readAsDataURL(file);
                });
            }

            function setStatus(msg, ok) {
                if (!statusE) { return; }
                statusE.textContent = msg;
                statusE.className = 'text-body-s mt-3 ' + (ok ? 'text-forest-700' : 'text-red-600');
                statusE.classList.remove('hidden');
            }

            function field(name) {
                var el = form.querySelector('[name="' + name + '"]');
                return el ? String(el.value || '').trim() : '';
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var payload = {
                    name:    field('name'),
                    email:   field('email'),
                    title:   field('title'),
                    company: field('company'),
                    quote:   field('quote'),
                    rating:  current,
                    avatar:  photoData
                };

                if (!payload.name)  { setStatus('Please enter your name.', false); return; }
                if (!payload.email) { setStatus('Please enter a valid email address.', false); return; }
                if (!current)       { setStatus('Please choose a star rating.', false); return; }
                if (payload.quote.length < 10) { setStatus('Please tell us a little more about your experience.', false); return; }

                if (submitB) { submitB.disabled = true; submitB.classList.add('opacity-60'); }
                setStatus('Submitting your review...', true);

                fetch(API_BASE + '/wl/forms/testimonial', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        if (!res.ok || !res.d || !res.d.success) {
                            throw new Error(res.d && res.d.error ? res.d.error : 'Something went wrong. Please try again.');
                        }
                        form.reset();
                        current = 0;
                        paint(0);
                        if (ratingI) { ratingI.value = '0'; }
                        clearPhoto();
                        setStatus(res.d.message || 'Thank you! Your review has been submitted and will appear once approved.', true);
                        if (submitB) { submitB.disabled = false; submitB.classList.remove('opacity-60'); }
                    })
                    .catch(function (err) {
                        setStatus(err.message || 'Something went wrong. Please try again.', false);
                        if (submitB) { submitB.disabled = false; submitB.classList.remove('opacity-60'); }
                    });
            });
        });
    }

    /* ── Review modal open / close ───────────────── */
    var reviewModalGlobalsWired = false;
    function reviewModalEl() { return document.querySelector('[data-review-modal]'); }
    function openReviewModal() {
        var modal = reviewModalEl();
        if (!modal) { return; }
        modal.classList.remove('hidden');
        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');
        var first = modal.querySelector('input, textarea');
        if (first) { setTimeout(function () { first.focus(); }, 80); }
    }
    function closeReviewModal() {
        var modal = reviewModalEl();
        if (!modal) { return; }
        modal.classList.add('hidden');
        document.documentElement.classList.remove('overflow-hidden');
        document.body.classList.remove('overflow-hidden');
    }
    function initReviewToggle(root) {
        root.querySelectorAll('[data-review-open]:not([data-review-open-done])').forEach(function (btn) {
            btn.setAttribute('data-review-open-done', '1');
            btn.addEventListener('click', openReviewModal);
        });
        if (reviewModalGlobalsWired) { return; }
        reviewModalGlobalsWired = true;
        document.addEventListener('click', function (e) {
            if (e.target.closest && e.target.closest('[data-review-close]')) { closeReviewModal(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') { return; }
            var modal = reviewModalEl();
            if (modal && !modal.classList.contains('hidden')) { closeReviewModal(); }
        });
    }

    /* ── Public hydrate ────────────────────────── */
    function hydrate(root) {
        root = root || document;
        initCarousels(root);
        initAccordions(root);
        initTestimonials(root);
        initReviewForm(root);
        initReviewToggle(root);
        initConfigurators(root);
        renderRecaptcha(root);
        if (window.MMConsent) { window.MMConsent.scan(root); }
        updateActiveNav(window.location.pathname);
    }

    window.MM = { hydrate: hydrate, updateActiveNav: updateActiveNav };

    document.addEventListener('DOMContentLoaded', function () {
        initHeader();
        hydrate(document);
    });
}());
