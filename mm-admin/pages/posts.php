<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY', 'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
$siteBase = $_is_local ? 'http://localhost:8001' : 'https://blog.majesticmarquees.com';
unset($_is_local);

$layout = 'app';
$activeNav = 'posts';
$canManage = can('posts.manage');
?>
<script type="application/json" id="page-meta">
{
    "title": "Blog - Majestic Marquees Admin",
    "description": "Manage blog posts"
}
</script>

<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Blog Posts</h2>
            <p class="text-sm text-gray-500 mt-1">Create, edit and publish blog content.</p>
        </div>
        <?php if ($canManage): ?>
        <a href="/posts/new" class="text-sm font-medium px-4 py-2 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors">+ New post</a>
        <?php endif; ?>
    </div>

    <div class="flex items-center gap-2 flex-wrap" id="post-filters">
        <button type="button" data-status="all" class="post-filter px-3 py-1.5 rounded-full text-sm font-medium bg-tan-500 text-white">All</button>
        <button type="button" data-status="draft" class="post-filter px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200">Draft</button>
        <button type="button" data-status="published" class="post-filter px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200">Published</button>
        <button type="button" data-status="archived" class="post-filter px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200">Archived</button>
    </div>

    <div id="posts-loading" class="text-center text-gray-400 py-16">Loading...</div>
    <div id="posts-error" class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-lg p-4"></div>
    <div id="posts-empty" class="hidden text-center text-gray-400 py-16">No posts found.</div>

    <div id="posts-wrap" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="text-left font-medium px-4 py-3">Title</th>
                        <th class="text-left font-medium px-4 py-3">Categories</th>
                        <th class="text-left font-medium px-4 py-3">Status</th>
                        <th class="text-left font-medium px-4 py-3">Date</th>
                        <th class="text-left font-medium px-4 py-3">Views</th>
                        <th class="text-right font-medium px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="posts-body" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>

    <div id="posts-pagination" class="hidden flex items-center justify-between">
        <button type="button" id="pg-prev" class="text-sm px-3 py-1.5 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">Previous</button>
        <p id="pg-label" class="text-sm text-gray-500"></p>
        <button type="button" id="pg-next" class="text-sm px-3 py-1.5 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">Next</button>
    </div>
</div>

<script>
(function () {
    const API_BASE = '<?= API_BASE ?>';
    const API_KEY = '<?= API_KEY ?>';
    const JWT = '<?= e($_SESSION['jwt'] ?? '') ?>';
    const SITE_BASE = '<?= e($siteBase) ?>';
    const CAN_MANAGE = <?= $canManage ? 'true' : 'false' ?>;

    let statusFilter = 'all';
    let page = 1;
    let meta = { current_page: 1, last_page: 1, total: 0 };

    function headers() {
        return {
            'Content-Type': 'application/json',
            'X-API-Key': API_KEY,
            'Authorization': 'Bearer ' + JWT,
        };
    }

    function esc(v) {
        return String(v ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function statusBadge(status) {
        if (status === 'published') return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Published</span>';
        if (status === 'archived') return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Archived</span>';
        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Draft</span>';
    }

    function fmtDate(v) {
        if (!v) return '-';
        const d = new Date(v.replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return v;
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' });
    }

    function render(posts) {
        const loading = document.getElementById('posts-loading');
        const error = document.getElementById('posts-error');
        const empty = document.getElementById('posts-empty');
        const wrap = document.getElementById('posts-wrap');
        const body = document.getElementById('posts-body');
        const pager = document.getElementById('posts-pagination');

        loading.classList.add('hidden');
        error.classList.add('hidden');

        if (!posts.length) {
            wrap.classList.add('hidden');
            pager.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
        }

        empty.classList.add('hidden');
        wrap.classList.remove('hidden');

        body.innerHTML = posts.map(function (p) {
            const thumb = p.featured_image_url
                ? '<img src="' + esc(p.featured_image_url) + '" alt="" class="w-12 h-12 rounded object-cover">'
                : '<div class="w-12 h-12 rounded bg-gray-100"></div>';
            const cats = Array.isArray(p.categories) && p.categories.length
                ? p.categories.map(c => '<span class="inline-flex items-center px-2 py-0.5 rounded bg-cream-100 text-xs text-gray-700">' + esc(c.name) + '</span>').join(' ')
                : '<span class="text-gray-400">-</span>';
            const dateVal = p.published_at || p.created_at;

            let actions = '';
            if (p.status === 'published') {
                actions += '<a href="' + esc(SITE_BASE + '/' + p.slug) + '" target="_blank" rel="noopener" class="text-xs font-medium px-2.5 py-1 rounded-md text-blue-700 hover:bg-blue-50">View</a>';
            }
            if (CAN_MANAGE) {
                actions += '<a href="/posts/' + p.id + '/edit" class="text-xs font-medium px-2.5 py-1 rounded-md text-gray-700 hover:bg-gray-100">Edit</a>';
                actions += '<button type="button" data-act="delete" data-id="' + p.id + '" class="text-xs font-medium px-2.5 py-1 rounded-md text-red-600 hover:bg-red-50">Delete</button>';
            }

            return '<tr>' +
                '<td class="px-4 py-3 align-top">' +
                    '<div class="flex items-center gap-3">' + thumb +
                    '<div>' +
                        '<div class="font-medium text-gray-800">' + esc(p.title) + '</div>' +
                        '<div class="text-xs text-gray-500 mt-1">' + esc((p.read_time || 1) + ' min read') + '</div>' +
                    '</div>' +
                    '</div>' +
                '</td>' +
                '<td class="px-4 py-3 align-top"><div class="flex flex-wrap gap-1">' + cats + '</div></td>' +
                '<td class="px-4 py-3 align-top">' + statusBadge(p.status) + '</td>' +
                '<td class="px-4 py-3 align-top text-gray-600">' + esc(fmtDate(dateVal)) + '</td>' +
                '<td class="px-4 py-3 align-top text-gray-600">' + esc(p.views || 0) + '</td>' +
                '<td class="px-4 py-3 align-top text-right whitespace-nowrap">' + actions + '</td>' +
            '</tr>';
        }).join('');

        const pgLabel = document.getElementById('pg-label');
        const pgPrev = document.getElementById('pg-prev');
        const pgNext = document.getElementById('pg-next');
        pager.classList.remove('hidden');
        pgLabel.textContent = 'Page ' + meta.current_page + ' of ' + meta.last_page + ' (' + meta.total + ' total)';
        pgPrev.disabled = meta.current_page <= 1;
        pgNext.disabled = meta.current_page >= meta.last_page;
        pgPrev.classList.toggle('opacity-50', pgPrev.disabled);
        pgNext.classList.toggle('opacity-50', pgNext.disabled);
    }

    async function load(nextPage) {
        const loading = document.getElementById('posts-loading');
        const error = document.getElementById('posts-error');
        loading.classList.remove('hidden');
        error.classList.add('hidden');

        const q = new URLSearchParams({ page: String(nextPage), per_page: '15' });
        if (statusFilter !== 'all') q.set('status', statusFilter);

        try {
            const res = await fetch(API_BASE + '/wl/admin/blog/posts?' + q.toString(), { headers: headers() });
            if (res.status === 401) {
                window.location.href = '/login';
                return;
            }
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.error || 'Failed to load posts');

            page = nextPage;
            meta = json.meta || { current_page: page, last_page: page, total: 0 };
            render(Array.isArray(json.data) ? json.data : []);
        } catch (err) {
            loading.classList.add('hidden');
            error.textContent = err.message || 'Failed to load posts';
            error.classList.remove('hidden');
        }
    }

    async function removePost(id) {
        if (!CAN_MANAGE) return;
        if (!confirm('Delete this post? This cannot be undone.')) return;

        try {
            const res = await fetch(API_BASE + '/wl/admin/blog/posts/' + id, {
                method: 'DELETE',
                headers: headers(),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json.success) throw new Error(json.error || 'Delete failed');
            await load(page);
        } catch (err) {
            const error = document.getElementById('posts-error');
            error.textContent = err.message || 'Delete failed';
            error.classList.remove('hidden');
        }
    }

    document.getElementById('post-filters').addEventListener('click', function (e) {
        const btn = e.target.closest('.post-filter');
        if (!btn) return;

        statusFilter = btn.getAttribute('data-status') || 'all';
        document.querySelectorAll('.post-filter').forEach(function (el) {
            const active = el === btn;
            el.classList.toggle('bg-tan-500', active);
            el.classList.toggle('text-white', active);
            el.classList.toggle('bg-gray-100', !active);
            el.classList.toggle('text-gray-600', !active);
        });

        load(1);
    });

    document.getElementById('posts-body').addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-act="delete"]');
        if (!btn) return;
        removePost(parseInt(btn.getAttribute('data-id') || '0', 10));
    });

    document.getElementById('pg-prev').addEventListener('click', function () {
        if (page > 1) load(page - 1);
    });
    document.getElementById('pg-next').addEventListener('click', function () {
        if (page < (meta.last_page || 1)) load(page + 1);
    });

    load(1);
})();
</script>
