<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY', 'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
unset($_is_local);

$layout = 'app';
$activeNav = 'posts';
$postId = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : null;
?>
<script type="application/json" id="page-meta">
{
    "title": "<?= $postId ? 'Edit Post' : 'New Post' ?> - Majestic Marquees Admin",
    "description": "Blog post editor"
}
</script>

<div class="space-y-6" id="editor-app">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <a href="/posts" aria-label="Back to posts" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900"><?= $postId ? 'Edit Post' : 'Create New Post' ?></h2>
                <p class="text-sm text-gray-500 mt-0.5">Write and publish blog content.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button id="btn-preview" type="button" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <span id="btn-preview-label">Preview</span>
            </button>
            <button id="btn-save-draft" type="button" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                Save Draft
            </button>
            <button id="btn-publish" type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-tan-500 text-white text-sm hover:bg-tan-600 transition-colors disabled:opacity-50">Publish</button>
        </div>
    </div>

    <div id="editor-error" class="hidden text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
        <div class="flex items-start justify-between gap-3">
            <span id="editor-error-text"></span>
            <button type="button" id="editor-error-dismiss" aria-label="Dismiss" class="text-red-400 hover:text-red-600 text-lg leading-none">&times;</button>
        </div>
    </div>
    <div id="editor-success" class="hidden text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
        <div class="flex items-start justify-between gap-3">
            <span id="editor-success-text"></span>
            <button type="button" id="editor-success-dismiss" aria-label="Dismiss" class="text-green-400 hover:text-green-600 text-lg leading-none">&times;</button>
        </div>
    </div>

    <div id="editor-grid" class="grid gap-6 grid-cols-1">
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                        <span id="cnt-title" class="text-xs text-gray-400">0/75</span>
                    </div>
                    <input id="f-title" type="text" maxlength="75" placeholder="Enter post title" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-tan-400 focus:border-transparent">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium text-gray-700">Slug
                            <button type="button" id="btn-gen-slug" class="ml-2 text-xs text-tan-600 hover:text-tan-700">Generate from title</button>
                        </label>
                        <span id="cnt-slug" class="text-xs text-gray-400">0/150</span>
                    </div>
                    <input id="f-slug" type="text" maxlength="150" placeholder="post-url-slug" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-tan-400 focus:border-transparent">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium text-gray-700">Subtitle</label>
                        <span id="cnt-subtitle" class="text-xs text-gray-400">0/200</span>
                    </div>
                    <input id="f-subtitle" type="text" maxlength="200" placeholder="Optional supporting headline" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-tan-400 focus:border-transparent">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium text-gray-700">Excerpt</label>
                        <span id="cnt-excerpt" class="text-xs text-gray-400">0/150</span>
                    </div>
                    <textarea id="f-excerpt" rows="2" maxlength="150" placeholder="Brief summary for post listings" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-tan-400 focus:border-transparent"></textarea>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Content <span class="text-red-500">*</span></label>
                <div class="ck-editor-wrapper">
                    <textarea id="content" rows="12" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Featured Image</label>
                    <span class="text-xs text-gray-400">Ideal size: 1200 x 630 px (16:9)</span>
                </div>
                <p id="img-error" class="hidden text-xs text-red-600 font-medium"></p>

                <div id="featured-preview-wrap" class="hidden relative">
                    <img id="featured-preview" src="" alt="" class="w-full h-48 object-cover rounded-lg border border-gray-200">
                    <button id="btn-remove-featured" type="button" aria-label="Remove featured image" class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                    </button>
                </div>

                <label id="featured-dropzone" class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    <span class="mt-2 text-sm text-gray-500">Click to upload featured image</span>
                    <span class="mt-1 text-xs text-red-500 font-medium">Must be exactly 1200 x 630 px</span>
                    <input id="f-featured-file" type="file" accept="image/*" class="hidden">
                </label>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image Alt Text</label>
                    <input id="f-featured-alt" type="text" maxlength="200" placeholder="Image alt text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-tan-400 focus:border-transparent">
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">Category</label>
                        <button type="button" id="btn-toggle-category" class="inline-flex items-center gap-1 text-xs text-tan-600 hover:text-tan-700">
                            <span id="icon-toggle-category">+</span> <span id="label-toggle-category">Add New</span>
                        </button>
                    </div>
                    <div id="cat-add-row" class="hidden gap-2 mb-3">
                        <input id="f-new-category" type="text" placeholder="New category name" class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-tan-400 focus:border-transparent">
                        <button id="btn-add-category" type="button" class="px-3 py-2 rounded-lg bg-tan-500 text-white text-sm hover:bg-tan-600 disabled:opacity-50">Add</button>
                    </div>
                    <div id="categories-list" class="flex flex-wrap gap-2"></div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">Tags <span class="ml-1 text-xs text-gray-400 font-normal">(used as meta keywords)</span></label>
                        <button type="button" id="btn-toggle-tag" class="inline-flex items-center gap-1 text-xs text-tan-600 hover:text-tan-700">
                            <span id="icon-toggle-tag">+</span> <span id="label-toggle-tag">New Tag</span>
                        </button>
                    </div>
                    <div id="tag-add-row" class="hidden gap-2 mb-3">
                        <input id="f-new-tag" type="text" placeholder="Tag name" class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-tan-400 focus:border-transparent">
                        <button id="btn-add-tag" type="button" class="px-3 py-2 rounded-lg bg-tan-500 text-white text-sm hover:bg-tan-600 disabled:opacity-50">Add</button>
                    </div>
                    <div id="tags-list" class="flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>

        <div id="preview-panel" class="hidden bg-white rounded-xl border border-gray-200 shadow-sm p-6 overflow-auto max-h-[calc(100vh-160px)] lg:sticky lg:top-6">
            <div class="border-b border-gray-200 pb-4 mb-6">
                <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Preview</h3>
            </div>
            <article>
                <img id="preview-image" src="" alt="" class="hidden w-full h-64 object-cover rounded-lg mb-6">
                <h1 id="preview-title" class="text-3xl font-bold text-gray-900 mb-4">Untitled Post</h1>
                <div id="preview-content" class="prose prose-lg max-w-none text-gray-700"></div>
            </article>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/vendor/ckeditor5/ckeditor5.css">
<style>
    .ck-editor-wrapper .ck-editor__editable_inline { min-height: 400px; }
    .ck-editor-wrapper .ck.ck-editor__main > .ck-editor__editable { min-height: 400px; }
    /* Tailwind preflight resets ul/ol to list-style:none, so restore them inside the editor */
    .ck.ck-content ul, .ck.ck-content ol { padding-left: 2em; margin: 0.75em 0; }
    .ck.ck-content ul { list-style-type: disc; }
    .ck.ck-content ol { list-style-type: decimal; }
    .ck.ck-content li { margin: 0.25em 0; }
    .ck.ck-content h1 { font-size: 2.25rem; font-weight: 800; line-height: 1.2; margin: 0.75em 0; }
    .ck.ck-content h2 { font-size: 1.75rem; font-weight: 700; line-height: 1.3; margin: 0.75em 0; }
    .ck.ck-content h3 { font-size: 1.375rem; font-weight: 600; line-height: 1.4; margin: 0.75em 0; }
    .ck.ck-content h4 { font-size: 1.125rem; font-weight: 600; line-height: 1.5; margin: 0.75em 0; }
    .ck.ck-content h5 { font-size: 1rem; font-weight: 600; line-height: 1.5; margin: 0.75em 0; }
</style>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js"></script>
<script src="/vendor/ckeditor5/ckeditor5.umd.js"></script>
<script>
(function () {
    const API_BASE = '<?= API_BASE ?>';
    const API_KEY = '<?= API_KEY ?>';
    const JWT = '<?= e($_SESSION['jwt'] ?? '') ?>';
    const POST_ID = <?= $postId ? (int) $postId : 'null' ?>;

    const state = {
        editor: null,
        featuredPath: null,
        featuredUrl: null,
        categories: [],
        tags: [],
        selectedCategoryIds: new Set(),
        selectedTagIds: new Set(),
    };

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

    function showError(msg) {
        document.getElementById('editor-error-text').textContent = msg;
        document.getElementById('editor-error').classList.remove('hidden');
    }

    function clearError() {
        document.getElementById('editor-error').classList.add('hidden');
        document.getElementById('editor-error-text').textContent = '';
    }

    function showSuccess(msg) {
        document.getElementById('editor-success-text').textContent = msg;
        document.getElementById('editor-success').classList.remove('hidden');
    }

    function clearSuccess() {
        document.getElementById('editor-success').classList.add('hidden');
        document.getElementById('editor-success-text').textContent = '';
    }

    function showImgError(msg) {
        const el = document.getElementById('img-error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function clearImgError() {
        const el = document.getElementById('img-error');
        el.classList.add('hidden');
        el.textContent = '';
    }

    function slugify(v) {
        return String(v || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 150);
    }

    class MmUpload {
        constructor(loader) {
            this.loader = loader;
        }
        async upload() {
            const file = await this.loader.file;
            const fd = new FormData();
            fd.append('image', file);
            const r = await fetch(API_BASE + '/wl/admin/blog/upload-image', {
                method: 'POST',
                headers: {
                    'X-API-Key': API_KEY,
                    'Authorization': 'Bearer ' + JWT,
                },
                body: fd,
            });
            const j = await r.json();
            if (!r.ok || !j.success) {
                throw new Error(j.error || 'Image upload failed');
            }
            return { default: j.url };
        }
        abort() {}
    }

    function MmBlogImageUploadPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = function (loader) {
            return new MmUpload(loader);
        };
    }

    async function initEditor() {
        const {
            ClassicEditor, Autoformat, AutoLink, BlockQuote, Bold, Code, CodeBlock,
            Essentials, FileRepository, FontBackgroundColor, FontColor, FontFamily,
            FontSize, Heading, HorizontalLine, Image, ImageCaption, ImageResize,
            ImageStyle, ImageToolbar, ImageUpload, Italic, Link, LinkImage, List,
            ListProperties, MediaEmbed, Paragraph, PasteFromOffice, SelectAll,
            Strikethrough, Table, TableToolbar, Alignment, Underline, Undo
        } = window.CKEDITOR;

        state.editor = await ClassicEditor.create(document.querySelector('#content'), {
            licenseKey: 'GPL',
            plugins: [
                Autoformat, AutoLink, BlockQuote, Bold, Code, CodeBlock, Essentials,
                FileRepository, FontBackgroundColor, FontColor, FontFamily, FontSize,
                Heading, HorizontalLine, Image, ImageCaption, ImageResize, ImageStyle,
                ImageToolbar, ImageUpload, Italic, Link, LinkImage, List, ListProperties,
                MediaEmbed, Paragraph, PasteFromOffice, SelectAll, Strikethrough, Table,
                TableToolbar, Alignment, Underline, Undo, MmBlogImageUploadPlugin
            ],
            toolbar: {
                items: [
                    'undo', 'redo', '|',
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                    'alignment', '|',
                    'bulletedList', 'numberedList', '|',
                    'link', 'uploadImage', 'insertTable', 'mediaEmbed', '|',
                    'blockQuote', 'codeBlock', 'horizontalLine'
                ],
                shouldNotGroupWhenFull: true,
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                    { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                ],
            },
            image: {
                toolbar: [
                    'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|',
                    'toggleImageCaption', 'imageTextAlternative'
                ],
            },
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
            },
            list: {
                properties: { styles: true, startIndex: true, reversed: true },
            },
            link: {
                defaultProtocol: 'https://',
                decorators: {
                    openInNewTab: {
                        mode: 'manual',
                        label: 'Open in a new tab',
                        attributes: { target: '_blank', rel: 'noopener noreferrer' },
                    },
                },
            },
            placeholder: 'Start writing your blog post...',
        });
    }

    function renderTaxonomy(listEl, items, selectedSet) {
        if (!items.length) {
            const what = listEl.id === 'tags-list' ? 'tags' : 'categories';
            listEl.innerHTML = '<span class="text-sm text-gray-500">No ' + what + ' yet. Use the button above to create one.</span>';
            return;
        }
        listEl.innerHTML = items.map(function (item) {
            const selected = selectedSet.has(item.id);
            const base = selected ? 'bg-tan-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200';
            const del = selected ? 'text-cream-100 hover:text-white' : 'text-gray-400 hover:text-red-600';
            return '<span class="inline-flex items-center rounded-full text-sm font-medium transition-colors ' + base + '">' +
                '<button type="button" data-action="toggle" data-id="' + item.id + '" class="pl-3 pr-2 py-1">' + esc(item.name) + '</button>' +
                '<button type="button" data-action="delete" data-id="' + item.id + '" data-name="' + esc(item.name) + '" title="Delete" class="pr-2 py-1 rounded-r-full text-base leading-none ' + del + '">&times;</button>' +
            '</span>';
        }).join('');
    }

    function wireTaxonomyEvents() {
        document.getElementById('categories-list').addEventListener('click', function (e) {
            handleTaxonomyClick(e, 'category');
        });
        document.getElementById('tags-list').addEventListener('click', function (e) {
            handleTaxonomyClick(e, 'tag');
        });
    }

    function handleTaxonomyClick(e, kind) {
        const btn = e.target.closest('button[data-action][data-id]');
        if (!btn) return;
        const id = parseInt(btn.getAttribute('data-id') || '0', 10);
        if (!id) return;
        const selectedSet = kind === 'category' ? state.selectedCategoryIds : state.selectedTagIds;
        const listEl = document.getElementById(kind === 'category' ? 'categories-list' : 'tags-list');
        const items = kind === 'category' ? state.categories : state.tags;

        if (btn.getAttribute('data-action') === 'toggle') {
            if (selectedSet.has(id)) selectedSet.delete(id);
            else selectedSet.add(id);
            renderTaxonomy(listEl, items, selectedSet);
        } else {
            deleteTaxonomy(kind, id, btn.getAttribute('data-name') || 'this item');
        }
    }

    async function deleteTaxonomy(kind, id, name) {
        const label = kind === 'category' ? 'category' : 'tag';
        if (!window.confirm('Delete ' + label + ' "' + name + '"? This cannot be undone.')) return;
        try {
            clearError();
            const path = kind === 'category' ? '/wl/admin/blog/categories/' : '/wl/admin/blog/tags/';
            const r = await fetch(API_BASE + path + id, { method: 'DELETE', headers: headers() });
            const j = await r.json().catch(function () { return {}; });
            if (!r.ok || j.success === false) {
                throw new Error(j.error || ('Failed to delete ' + label));
            }
            (kind === 'category' ? state.selectedCategoryIds : state.selectedTagIds).delete(id);
            await loadTaxonomies();
        } catch (err) {
            showError(err.message || ('Failed to delete ' + label + '. It may be in use by other posts.'));
        }
    }

    async function loadTaxonomies() {
        const [catsRes, tagsRes] = await Promise.all([
            fetch(API_BASE + '/wl/admin/blog/categories', { headers: headers() }),
            fetch(API_BASE + '/wl/admin/blog/tags', { headers: headers() }),
        ]);

        const catsJson = await catsRes.json();
        const tagsJson = await tagsRes.json();

        if (!catsRes.ok || !catsJson.success) throw new Error(catsJson.error || 'Failed to load categories');
        if (!tagsRes.ok || !tagsJson.success) throw new Error(tagsJson.error || 'Failed to load tags');

        state.categories = Array.isArray(catsJson.data) ? catsJson.data.map(function (c) {
            return { id: parseInt(c.cat_id || c.id, 10), name: c.name, slug: c.slug };
        }) : [];

        state.tags = Array.isArray(tagsJson.data) ? tagsJson.data.map(function (t) {
            return { id: parseInt(t.tag_id || t.id, 10), name: t.name, slug: t.slug };
        }) : [];

        renderTaxonomy(document.getElementById('categories-list'), state.categories, state.selectedCategoryIds);
        renderTaxonomy(document.getElementById('tags-list'), state.tags, state.selectedTagIds);
    }

    function setFeaturedPreview(url) {
        const wrap = document.getElementById('featured-preview-wrap');
        const img = document.getElementById('featured-preview');
        const dropzone = document.getElementById('featured-dropzone');
        if (!url) {
            wrap.classList.add('hidden');
            dropzone.classList.remove('hidden');
            img.src = '';
        } else {
            img.src = url;
            wrap.classList.remove('hidden');
            dropzone.classList.add('hidden');
        }
        updatePreview();
    }

    async function uploadFeatured(file) {
        const fd = new FormData();
        fd.append('image', file);

        const r = await fetch(API_BASE + '/wl/admin/blog/upload-image', {
            method: 'POST',
            headers: {
                'X-API-Key': API_KEY,
                'Authorization': 'Bearer ' + JWT,
            },
            body: fd,
        });

        const j = await r.json();
        if (!r.ok || !j.success) throw new Error(j.error || 'Featured image upload failed');

        state.featuredPath = j.path || null;
        state.featuredUrl = j.url || null;
        setFeaturedPreview(state.featuredUrl);
    }

    function fillForm(post) {
        document.getElementById('f-title').value = post.title || '';
        document.getElementById('f-slug').value = post.slug || '';
        document.getElementById('f-subtitle').value = post.subtitle || '';
        document.getElementById('f-excerpt').value = post.excerpt || '';
        document.getElementById('f-featured-alt').value = post.featured_image_alt || '';

        state.featuredUrl = post.featured_image_url || null;
        if (post.featured_image_url) {
            try {
                const u = new URL(post.featured_image_url);
                state.featuredPath = u.pathname.replace(/^\//, '');
            } catch (_e) {
                state.featuredPath = post.featured_image_url;
            }
        } else {
            state.featuredPath = null;
        }

        setFeaturedPreview(state.featuredUrl);

        state.selectedCategoryIds = new Set((post.categories || []).map(c => parseInt(c.id, 10)).filter(Boolean));
        state.selectedTagIds = new Set((post.tags || []).map(t => parseInt(t.id, 10)).filter(Boolean));

        if (state.editor) {
            state.editor.setData(post.content || '');
        }

        renderTaxonomy(document.getElementById('categories-list'), state.categories, state.selectedCategoryIds);
        renderTaxonomy(document.getElementById('tags-list'), state.tags, state.selectedTagIds);
    }

    async function loadPost() {
        if (!POST_ID) return;
        const r = await fetch(API_BASE + '/wl/admin/blog/posts/' + POST_ID, { headers: headers() });
        const j = await r.json();
        if (!r.ok || !j.success || !j.data) throw new Error(j.error || 'Failed to load post');
        fillForm(j.data);
    }

    async function createCategory(name) {
        const r = await fetch(API_BASE + '/wl/admin/blog/categories', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ name: name, slug: slugify(name) }),
        });
        const j = await r.json();
        if (!r.ok || !j.success) throw new Error(j.error || 'Failed to create category');
        await loadTaxonomies();
        const id = parseInt((j.data && j.data.id) || '0', 10);
        if (id) state.selectedCategoryIds.add(id);
        renderTaxonomy(document.getElementById('categories-list'), state.categories, state.selectedCategoryIds);
    }

    async function createTag(name) {
        const r = await fetch(API_BASE + '/wl/admin/blog/tags', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ name: name, slug: slugify(name) }),
        });
        const j = await r.json();
        if (!r.ok || !j.success) throw new Error(j.error || 'Failed to create tag');
        await loadTaxonomies();
        const id = parseInt((j.data && j.data.id) || '0', 10);
        if (id) state.selectedTagIds.add(id);
        renderTaxonomy(document.getElementById('tags-list'), state.tags, state.selectedTagIds);
    }

    async function save(status) {
        clearError();
        clearSuccess();
        const title = document.getElementById('f-title').value.trim();
        if (!title) {
            showError('Title is required.');
            return;
        }
        const content = state.editor ? state.editor.getData().trim() : '';
        if (status === 'published' && !content) {
            showError('Content is required before publishing.');
            return;
        }

        const body = {
            title: title,
            slug: document.getElementById('f-slug').value.trim(),
            subtitle: document.getElementById('f-subtitle').value.trim(),
            excerpt: document.getElementById('f-excerpt').value.trim(),
            content: content,
            featured_image: state.featuredPath,
            featured_image_alt: document.getElementById('f-featured-alt').value.trim(),
            category_ids: Array.from(state.selectedCategoryIds),
            tag_ids: Array.from(state.selectedTagIds),
            meta_title: title,
            meta_description: document.getElementById('f-excerpt').value.trim(),
            status: status,
        };

        const url = POST_ID
            ? API_BASE + '/wl/admin/blog/posts/' + POST_ID
            : API_BASE + '/wl/admin/blog/posts';

        setSaving(true);
        try {
            const r = await fetch(url, {
                method: POST_ID ? 'PUT' : 'POST',
                headers: headers(),
                body: JSON.stringify(body),
            });
            const j = await r.json();
            if (!r.ok || !j.success) {
                throw new Error(j.error || 'Save failed');
            }
            if (POST_ID) {
                showSuccess('Post updated successfully.');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                const newId = j.data && j.data.id ? j.data.id : null;
                try { sessionStorage.setItem('mm_post_flash', 'Post created successfully.'); } catch (e) {}
                window.location.href = newId ? '/posts/' + newId + '/edit' : '/posts';
            }
        } finally {
            setSaving(false);
        }
    }

    function setSaving(on) {
        ['btn-save-draft', 'btn-publish', 'btn-preview'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.disabled = on;
        });
        const pub = document.getElementById('btn-publish');
        if (pub) pub.textContent = on ? 'Saving...' : 'Publish';
    }

    function showFlash() {
        try {
            const msg = sessionStorage.getItem('mm_post_flash');
            if (msg) {
                showSuccess(msg);
                sessionStorage.removeItem('mm_post_flash');
            }
        } catch (e) {}
    }

    function previewOpen() {
        return !document.getElementById('preview-panel').classList.contains('hidden');
    }

    function updatePreview() {
        const titleEl = document.getElementById('preview-title');
        const imgEl = document.getElementById('preview-image');
        const contentEl = document.getElementById('preview-content');
        if (!titleEl || !contentEl) return;
        const title = document.getElementById('f-title').value.trim();
        titleEl.textContent = title || 'Untitled Post';
        if (state.featuredUrl) {
            imgEl.src = state.featuredUrl;
            imgEl.alt = document.getElementById('f-featured-alt').value.trim() || title;
            imgEl.classList.remove('hidden');
        } else {
            imgEl.src = '';
            imgEl.classList.add('hidden');
        }
        const raw = state.editor ? state.editor.getData() : '';
        const fallback = '<p class="text-gray-400">Start writing to see the preview...</p>';
        contentEl.innerHTML = (window.DOMPurify ? DOMPurify.sanitize(raw, { ADD_ATTR: ['target', 'style'] }) : raw) || fallback;
    }

    function togglePreview() {
        const grid = document.getElementById('editor-grid');
        const panel = document.getElementById('preview-panel');
        const label = document.getElementById('btn-preview-label');
        if (previewOpen()) {
            panel.classList.add('hidden');
            grid.classList.remove('lg:grid-cols-2');
            label.textContent = 'Preview';
        } else {
            panel.classList.remove('hidden');
            grid.classList.add('lg:grid-cols-2');
            label.textContent = 'Hide Preview';
            updatePreview();
        }
    }

    function bindCounter(inputId, counterId, max) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        if (!input || !counter) return;
        const update = function () {
            const len = input.value.length;
            counter.textContent = len + '/' + max;
            counter.classList.toggle('text-red-500', len >= max);
            counter.classList.toggle('text-gray-400', len < max);
        };
        input.addEventListener('input', update);
        update();
    }

    function wireCounters() {
        bindCounter('f-title', 'cnt-title', 75);
        bindCounter('f-slug', 'cnt-slug', 150);
        bindCounter('f-subtitle', 'cnt-subtitle', 200);
        bindCounter('f-excerpt', 'cnt-excerpt', 150);
    }

    function validateImageFile(file) {
        return new Promise(function (resolve, reject) {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = function () {
                URL.revokeObjectURL(url);
                if (img.naturalWidth !== 1200 || img.naturalHeight !== 630) {
                    reject(new Error('Image must be exactly 1200 x 630 px. Yours is ' + img.naturalWidth + ' x ' + img.naturalHeight + ' px.'));
                } else {
                    resolve(true);
                }
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('Could not read the selected image.'));
            };
            img.src = url;
        });
    }

    async function addCategoryFromInput() {
        const el = document.getElementById('f-new-category');
        const name = el.value.trim();
        if (!name) return;
        try {
            clearError();
            await createCategory(name);
            el.value = '';
            closeAddRow('category');
        } catch (err) {
            showError(err.message || 'Failed to add category');
        }
    }

    async function addTagFromInput() {
        const el = document.getElementById('f-new-tag');
        const name = el.value.trim();
        if (!name) return;
        try {
            clearError();
            await createTag(name);
            el.value = '';
            closeAddRow('tag');
        } catch (err) {
            showError(err.message || 'Failed to add tag');
        }
    }

    function toggleAddRow(kind) {
        const row = document.getElementById(kind === 'category' ? 'cat-add-row' : 'tag-add-row');
        const icon = document.getElementById(kind === 'category' ? 'icon-toggle-category' : 'icon-toggle-tag');
        const lbl = document.getElementById(kind === 'category' ? 'label-toggle-category' : 'label-toggle-tag');
        const input = document.getElementById(kind === 'category' ? 'f-new-category' : 'f-new-tag');
        const nowHidden = row.classList.toggle('hidden');
        row.classList.toggle('flex', !nowHidden);
        icon.textContent = nowHidden ? '+' : '\u00d7';
        lbl.textContent = nowHidden ? (kind === 'category' ? 'Add New' : 'New Tag') : 'Cancel';
        if (!nowHidden) input.focus();
    }

    function closeAddRow(kind) {
        const row = document.getElementById(kind === 'category' ? 'cat-add-row' : 'tag-add-row');
        row.classList.add('hidden');
        row.classList.remove('flex');
        document.getElementById(kind === 'category' ? 'icon-toggle-category' : 'icon-toggle-tag').textContent = '+';
        document.getElementById(kind === 'category' ? 'label-toggle-category' : 'label-toggle-tag').textContent = kind === 'category' ? 'Add New' : 'New Tag';
    }

    function bindUi() {
        document.getElementById('btn-gen-slug').addEventListener('click', function () {
            const slugEl = document.getElementById('f-slug');
            slugEl.value = slugify(document.getElementById('f-title').value);
            slugEl.dispatchEvent(new Event('input'));
        });

        document.getElementById('f-title').addEventListener('blur', function () {
            const slugEl = document.getElementById('f-slug');
            if (!slugEl.value.trim()) {
                slugEl.value = slugify(document.getElementById('f-title').value);
                slugEl.dispatchEvent(new Event('input'));
            }
        });

        document.getElementById('f-featured-file').addEventListener('change', async function (e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            clearImgError();
            try {
                await validateImageFile(file);
            } catch (verr) {
                showImgError(verr.message);
                e.target.value = '';
                return;
            }
            try {
                clearError();
                await uploadFeatured(file);
            } catch (err) {
                showError(err.message || 'Image upload failed');
            }
        });

        document.getElementById('btn-remove-featured').addEventListener('click', function () {
            state.featuredPath = null;
            state.featuredUrl = null;
            document.getElementById('f-featured-file').value = '';
            clearImgError();
            setFeaturedPreview(null);
        });

        document.getElementById('btn-toggle-category').addEventListener('click', function () { toggleAddRow('category'); });
        document.getElementById('btn-add-category').addEventListener('click', addCategoryFromInput);
        document.getElementById('f-new-category').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addCategoryFromInput(); }
        });

        document.getElementById('btn-toggle-tag').addEventListener('click', function () { toggleAddRow('tag'); });
        document.getElementById('btn-add-tag').addEventListener('click', addTagFromInput);
        document.getElementById('f-new-tag').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addTagFromInput(); }
        });

        document.getElementById('btn-save-draft').addEventListener('click', async function () {
            try {
                await save('draft');
            } catch (err) {
                showError(err.message || 'Save failed');
            }
        });

        document.getElementById('btn-publish').addEventListener('click', async function () {
            try {
                await save('published');
            } catch (err) {
                showError(err.message || 'Publish failed');
            }
        });

        document.getElementById('btn-preview').addEventListener('click', togglePreview);
        document.getElementById('editor-error-dismiss').addEventListener('click', clearError);
        document.getElementById('editor-success-dismiss').addEventListener('click', clearSuccess);
        document.getElementById('f-title').addEventListener('input', function () { if (previewOpen()) updatePreview(); });
        document.getElementById('f-featured-alt').addEventListener('input', function () { if (previewOpen()) updatePreview(); });
    }

    async function init() {
        try {
            bindUi();
            wireTaxonomyEvents();
            showFlash();
            await initEditor();
            if (state.editor) {
                state.editor.model.document.on('change:data', function () {
                    if (previewOpen()) updatePreview();
                });
            }
            await loadTaxonomies();
            await loadPost();
            wireCounters();
            updatePreview();
        } catch (err) {
            showError(err.message || 'Failed to initialize editor');
        }
    }

    init();
})();
</script>
