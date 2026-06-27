<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$layout    = 'app';
$activeNav = 'reviews';

// reviews.view lets a user read the queue; reviews.manage unlocks the
// approve / edit / delete / add actions. The route already required one of
// the two, so a view-only admin still sees the list.
$canManage = can('reviews.manage');
?>
<script type="application/json" id="page-meta">
{
    "title": "Reviews - Majestic Marquees Admin",
    "description": "Approve, edit and manage customer reviews"
}
</script>

<div class="space-y-6">

    <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Reviews</h2>
            <p class="text-sm text-gray-500 mt-1">Approve customer reviews to publish them on the website, or add your own.</p>
        </div>
        <?php if ($canManage): ?>
        <button type="button" id="add-review-btn"
                class="text-sm font-medium px-4 py-2 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors">
            + Add review
        </button>
        <?php endif; ?>
    </div>

    <!-- Filter bar -->
    <div class="flex items-center gap-2" id="rv-filters">
        <button type="button" data-filter="all"      class="rv-filter px-3 py-1.5 rounded-full text-sm font-medium bg-tan-500 text-white">All <span class="rv-count" data-count="all">0</span></button>
        <button type="button" data-filter="pending"  class="rv-filter px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200">Pending <span class="rv-count" data-count="pending">0</span></button>
        <button type="button" data-filter="approved" class="rv-filter px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200">Approved <span class="rv-count" data-count="approved">0</span></button>
    </div>

    <div id="rv-loading" class="text-center text-gray-400 py-16">Loading&hellip;</div>
    <div id="rv-error"   class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-lg p-4"></div>
    <div id="rv-empty"   class="hidden text-center text-gray-400 py-16">No reviews to show.</div>

    <div id="rv-table-wrap" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="text-left font-medium px-4 py-3">Reviewer</th>
                        <th class="text-left font-medium px-4 py-3">Rating</th>
                        <th class="text-left font-medium px-4 py-3">Review</th>
                        <th class="text-left font-medium px-4 py-3">Status</th>
                        <th class="text-right font-medium px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="rv-body" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Modal: Create / Edit review -->
<div id="rv-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" data-close-modal></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <h3 id="rv-modal-title" class="text-lg font-semibold text-gray-800">Add review</h3>

        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="f-name" maxlength="255" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="f-email" maxlength="255" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title / Role</label>
                <input type="text" id="f-title" maxlength="255" placeholder="e.g. Wedding Coordinator" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                <input type="text" id="f-company" maxlength="255" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <input type="text" id="f-country" maxlength="100" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Display order</label>
                <input type="number" id="f-order" min="0" value="999" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
            <div class="flex gap-1 text-2xl text-gray-300 select-none" id="f-stars">
                <button type="button" data-star="1" class="leading-none hover:scale-110 transition-transform">&#9733;</button>
                <button type="button" data-star="2" class="leading-none hover:scale-110 transition-transform">&#9733;</button>
                <button type="button" data-star="3" class="leading-none hover:scale-110 transition-transform">&#9733;</button>
                <button type="button" data-star="4" class="leading-none hover:scale-110 transition-transform">&#9733;</button>
                <button type="button" data-star="5" class="leading-none hover:scale-110 transition-transform">&#9733;</button>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Avatar URL</label>
            <input type="url" id="f-avatar" maxlength="500" placeholder="https://... (optional)" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Review</label>
            <textarea id="f-quote" rows="4" maxlength="2000" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400"></textarea>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" id="f-active" checked class="rounded border-gray-300 text-tan-500 focus:ring-tan-400">
            Published (visible on the website)
        </label>

        <div id="rv-modal-error" class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-lg p-3"></div>

        <div class="flex justify-end gap-3 pt-2">
            <button type="button" data-close-modal class="text-sm font-medium px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
            <button type="button" id="rv-save" class="text-sm font-medium px-4 py-2 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors">Save</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Confirm delete -->
<div id="rv-confirm" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" data-close-confirm></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">Delete review</h3>
        <p class="text-sm text-gray-500">This permanently removes the review. This cannot be undone.</p>
        <div class="flex justify-end gap-3">
            <button type="button" data-close-confirm class="text-sm font-medium px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
            <button type="button" id="rv-confirm-delete" class="text-sm font-medium px-4 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors">Delete</button>
        </div>
    </div>
</div>

<script>
(function () {
    const API_BASE   = '<?= API_BASE ?>';
    const API_KEY    = '<?= API_KEY ?>';
    const JWT        = '<?= e($_SESSION['jwt'] ?? '') ?>';
    const CAN_MANAGE = <?= $canManage ? 'true' : 'false' ?>;

    let reviews    = [];
    let filter     = 'all';
    let editingId  = null;   // null = create mode
    let deleteId   = null;
    let modalStars = 0;

    function headers() {
        return {
            'Content-Type':  'application/json',
            'X-API-Key':     API_KEY,
            'Authorization': 'Bearer ' + JWT,
        };
    }

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Uploaded photos are stored as a relative path (uploads/..) - resolve them
    // against the API origin; full URLs (e.g. seeded avatars) pass through.
    function mediaUrl(u) {
        u = String(u || '');
        if (!u) { return ''; }
        return /^https?:/i.test(u) ? u : API_BASE.replace(/\/$/, '') + '/' + u.replace(/^\//, '');
    }

    function showError(msg) {
        document.getElementById('rv-loading').classList.add('hidden');
        const el = document.getElementById('rv-error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function starsHtml(rating) {
        let h = '<span class="text-amber-500 whitespace-nowrap" aria-label="' + rating + ' out of 5">';
        for (let n = 1; n <= 5; n++) {
            h += '<span class="' + (n <= rating ? 'text-amber-500' : 'text-gray-300') + '">\u2605</span>';
        }
        return h + '</span>';
    }

    function roleLine(r) {
        const parts = [];
        if (r.title)   parts.push(r.title);
        if (r.company) parts.push(r.company);
        return parts.join(', ');
    }

    async function load() {
        try {
            const res = await fetch(API_BASE + '/wl/admin/testimonials', { headers: headers() });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Failed to load reviews');
            reviews = Array.isArray(data.testimonials) ? data.testimonials : [];
            render();
        } catch (e) {
            showError(e.message);
        }
    }

    function updateCounts() {
        const approved = reviews.filter(r => r.is_active).length;
        const pending  = reviews.length - approved;
        document.querySelector('[data-count="all"]').textContent      = reviews.length;
        document.querySelector('[data-count="approved"]').textContent = approved;
        document.querySelector('[data-count="pending"]').textContent  = pending;
    }

    function render() {
        document.getElementById('rv-loading').classList.add('hidden');
        document.getElementById('rv-error').classList.add('hidden');
        updateCounts();

        let rows = reviews;
        if (filter === 'pending')  rows = reviews.filter(r => !r.is_active);
        if (filter === 'approved') rows = reviews.filter(r => r.is_active);

        const wrap  = document.getElementById('rv-table-wrap');
        const empty = document.getElementById('rv-empty');
        const body  = document.getElementById('rv-body');

        if (!rows.length) {
            wrap.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');
        wrap.classList.remove('hidden');

        body.innerHTML = rows.map(function (r) {
            const role   = roleLine(r);
            const avatar = r.avatar_url
                ? '<img src="' + esc(mediaUrl(r.avatar_url)) + '" alt="" class="w-9 h-9 rounded-full object-cover" onerror="this.style.display=\'none\'">'
                : '<div class="w-9 h-9 rounded-full bg-tan-100 text-tan-600 flex items-center justify-center text-sm font-semibold">' + esc((r.name || '?').charAt(0).toUpperCase()) + '</div>';

            const badge = r.is_active
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Approved</span>'
                : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>';

            let actions = '';
            if (CAN_MANAGE) {
                actions += r.is_active
                    ? '<button type="button" data-act="unapprove" data-id="' + r.id + '" class="text-xs font-medium px-2.5 py-1 rounded-md text-amber-700 hover:bg-amber-50">Unapprove</button>'
                    : '<button type="button" data-act="approve" data-id="' + r.id + '" class="text-xs font-medium px-2.5 py-1 rounded-md text-green-700 hover:bg-green-50">Approve</button>';
                actions += '<button type="button" data-act="edit" data-id="' + r.id + '" class="text-xs font-medium px-2.5 py-1 rounded-md text-gray-600 hover:bg-gray-100">Edit</button>';
                actions += '<button type="button" data-act="delete" data-id="' + r.id + '" class="text-xs font-medium px-2.5 py-1 rounded-md text-red-600 hover:bg-red-50">Delete</button>';
            } else {
                actions = '<span class="text-xs text-gray-400">View only</span>';
            }

            return '<tr>' +
                '<td class="px-4 py-3 align-top">' +
                    '<div class="flex items-center gap-3">' + avatar +
                    '<div><div class="font-medium text-gray-800">' + esc(r.name) + '</div>' +
                    (role ? '<div class="text-xs text-gray-500">' + esc(role) + '</div>' : '') +
                    (r.email ? '<div class="text-xs text-gray-400">' + esc(r.email) + '</div>' : '') +
                    '</div></div>' +
                '</td>' +
                '<td class="px-4 py-3 align-top">' + starsHtml(r.rating) + '</td>' +
                '<td class="px-4 py-3 align-top max-w-md"><p class="text-gray-600 line-clamp-3">' + esc(r.quote) + '</p></td>' +
                '<td class="px-4 py-3 align-top">' + badge + '</td>' +
                '<td class="px-4 py-3 align-top text-right whitespace-nowrap">' + actions + '</td>' +
            '</tr>';
        }).join('');
    }

    // ---- Filters ----
    document.getElementById('rv-filters').addEventListener('click', function (e) {
        const btn = e.target.closest('.rv-filter');
        if (!btn) return;
        filter = btn.getAttribute('data-filter');
        document.querySelectorAll('.rv-filter').forEach(function (b) {
            const on = b === btn;
            b.classList.toggle('bg-tan-500', on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('bg-gray-100', !on);
            b.classList.toggle('text-gray-600', !on);
        });
        render();
    });

    // ---- Row actions ----
    document.getElementById('rv-body').addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-act]');
        if (!btn) return;
        const id  = parseInt(btn.getAttribute('data-id'), 10);
        const act = btn.getAttribute('data-act');
        if (act === 'approve')   return setActive(id, true);
        if (act === 'unapprove') return setActive(id, false);
        if (act === 'edit')      return openEdit(id);
        if (act === 'delete')    return openDelete(id);
    });

    async function setActive(id, approve) {
        try {
            const res = await fetch(API_BASE + '/wl/admin/testimonials/' + id + '/' + (approve ? 'approve' : 'unapprove'), {
                method: 'POST', headers: headers(),
            });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Action failed');
            const row = reviews.find(r => r.id === id);
            if (row) row.is_active = approve;
            render();
        } catch (e) {
            showError(e.message);
        }
    }

    <?php if ($canManage): ?>
    // ---- Modal helpers ----
    const modal = document.getElementById('rv-modal');

    function paintStars(val) {
        document.querySelectorAll('#f-stars button').forEach(function (b) {
            const n = parseInt(b.getAttribute('data-star'), 10);
            b.classList.toggle('text-amber-500', n <= val);
            b.classList.toggle('text-gray-300', n > val);
        });
    }
    document.querySelectorAll('#f-stars button').forEach(function (b) {
        const n = parseInt(b.getAttribute('data-star'), 10);
        b.addEventListener('mouseenter', function () { paintStars(n); });
        b.addEventListener('click', function () { modalStars = n; paintStars(n); });
    });
    document.getElementById('f-stars').addEventListener('mouseleave', function () { paintStars(modalStars); });

    function setField(id, v) { document.getElementById(id).value = v == null ? '' : v; }

    function openModal() {
        document.getElementById('rv-modal-error').classList.add('hidden');
        modal.classList.remove('hidden');
    }
    function closeModal() { modal.classList.add('hidden'); }

    function openCreate() {
        editingId = null;
        modalStars = 5;
        document.getElementById('rv-modal-title').textContent = 'Add review';
        ['f-name','f-email','f-title','f-company','f-country','f-avatar','f-quote'].forEach(id => setField(id, ''));
        setField('f-order', 999);
        document.getElementById('f-active').checked = true;
        document.getElementById('f-email').disabled = false;
        paintStars(5);
        openModal();
    }

    function openEdit(id) {
        const r = reviews.find(x => x.id === id);
        if (!r) return;
        editingId = id;
        modalStars = r.rating || 5;
        document.getElementById('rv-modal-title').textContent = 'Edit review';
        setField('f-name', r.name);
        setField('f-email', r.email);
        setField('f-title', r.title);
        setField('f-company', r.company);
        setField('f-country', r.country);
        setField('f-avatar', r.avatar_url);
        setField('f-quote', r.quote);
        setField('f-order', r.display_order);
        document.getElementById('f-active').checked = !!r.is_active;
        // Email identifies the customer record; editing it is not supported here.
        document.getElementById('f-email').disabled = true;
        paintStars(modalStars);
        openModal();
    }

    const addBtn = document.getElementById('add-review-btn');
    if (addBtn) addBtn.addEventListener('click', openCreate);

    modal.querySelectorAll('[data-close-modal]').forEach(el => el.addEventListener('click', closeModal));

    document.getElementById('rv-save').addEventListener('click', async function () {
        const errEl = document.getElementById('rv-modal-error');
        errEl.classList.add('hidden');

        const name  = document.getElementById('f-name').value.trim();
        const email = document.getElementById('f-email').value.trim();
        const quote = document.getElementById('f-quote').value.trim();
        if (!name)              { errEl.textContent = 'Name is required.'; errEl.classList.remove('hidden'); return; }
        if (!editingId && !email) { errEl.textContent = 'Email is required.'; errEl.classList.remove('hidden'); return; }
        if (!modalStars)        { errEl.textContent = 'Please choose a rating.'; errEl.classList.remove('hidden'); return; }
        if (quote.length < 1)   { errEl.textContent = 'Review text is required.'; errEl.classList.remove('hidden'); return; }

        const payload = {
            name:          name,
            title:         document.getElementById('f-title').value.trim(),
            company:       document.getElementById('f-company').value.trim(),
            country:       document.getElementById('f-country').value.trim(),
            rating:        modalStars,
            quote:         quote,
            avatar_url:    document.getElementById('f-avatar').value.trim(),
            display_order: parseInt(document.getElementById('f-order').value, 10) || 999,
            is_active:     document.getElementById('f-active').checked,
        };
        if (!editingId) payload.email = email;

        const url    = editingId === null ? API_BASE + '/wl/admin/testimonials' : API_BASE + '/wl/admin/testimonials/' + editingId;
        const method = editingId === null ? 'POST' : 'PUT';

        try {
            const res = await fetch(url, { method: method, headers: headers(), body: JSON.stringify(payload) });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Save failed');
            closeModal();
            await load();
        } catch (e) {
            errEl.textContent = e.message;
            errEl.classList.remove('hidden');
        }
    });
    <?php endif; ?>

    // ---- Delete ----
    const confirmEl = document.getElementById('rv-confirm');
    function openDelete(id) { deleteId = id; confirmEl.classList.remove('hidden'); }
    function closeConfirm()  { deleteId = null; confirmEl.classList.add('hidden'); }
    confirmEl.querySelectorAll('[data-close-confirm]').forEach(el => el.addEventListener('click', closeConfirm));

    document.getElementById('rv-confirm-delete').addEventListener('click', async function () {
        if (!deleteId) return;
        try {
            const res = await fetch(API_BASE + '/wl/admin/testimonials/' + deleteId, { method: 'DELETE', headers: headers() });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Delete failed');
            reviews = reviews.filter(r => r.id !== deleteId);
            closeConfirm();
            render();
        } catch (e) {
            closeConfirm();
            showError(e.message);
        }
    });

    load();
})();
</script>
