<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$layout    = 'app';
$activeNav = 'survey-questions';
?>
<script type="application/json" id="page-meta">
{
    "title": "Survey Questions - Majestic Marquees Admin",
    "description": "Edit the qualification survey sent to new leads"
}
</script>

<div class="space-y-6 max-w-3xl">

    <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Survey Questions</h2>
            <p class="text-sm text-gray-500 mt-1">These questions are emailed to every new lead. Qualification is decided by AI using your context and the submitted answers.</p>
        </div>
        <button type="button" id="add-question-btn"
                class="text-sm font-medium px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition-colors">
            + Add question
        </button>
    </div>

    <div id="loading" class="text-center text-gray-400 py-16">Loading…</div>
    <div id="error"   class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-lg p-4"></div>

    <div id="context-wrap" class="hidden bg-white rounded-xl border border-gray-200 p-5 space-y-2">
        <label for="survey-context" class="block text-sm font-medium text-gray-700">Qualification Context</label>
        <p class="text-xs text-gray-500">This context is sent to AI on every survey submission to decide if the customer/lead is qualified.</p>
        <textarea id="survey-context" rows="5"
                  placeholder="Example: Qualified only if budget is above X and decision maker can commit within Y timeline..."
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-teal-400"></textarea>
    </div>

    <div id="questions" class="hidden space-y-4"></div>

    <div id="footer" class="hidden flex items-center gap-3 pt-2">
        <button type="button" id="save-btn"
                class="text-sm font-medium px-5 py-2.5 rounded-lg bg-teal-600 text-white hover:bg-teal-700 transition-colors">
            Save changes
        </button>
        <span id="save-status" class="text-sm text-gray-500"></span>
    </div>
</div>

<script>
(function () {
    const API_BASE = '<?= API_BASE ?>';
    const API_KEY  = '<?= API_KEY ?>';
    const JWT      = '<?= e($_SESSION['jwt'] ?? '') ?>';

    let questions = [];
    let contextText = '';

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

    function slugify(s) {
        return String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 40);
    }

    async function load() {
        try {
            const res  = await fetch(API_BASE + '/wl/admin/survey/questions', { headers: headers() });
            const data = await res.json();
            if (res.status === 401) { window.location.href = '/login'; return; }
            if (!res.ok || !data.success) throw new Error(data.error || 'Failed to load questions');
            questions = Array.isArray(data.questions) ? data.questions : [];
            contextText = String(data.context || '');
            render();
        } catch (e) {
            showError(e.message);
        }
    }

    function showError(msg) {
        document.getElementById('loading').classList.add('hidden');
        const el = document.getElementById('error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function render() {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('error').classList.add('hidden');
        const wrap = document.getElementById('questions');
        document.getElementById('footer').classList.remove('hidden');
        document.getElementById('context-wrap').classList.remove('hidden');
        document.getElementById('survey-context').value = contextText;

        if (!questions.length) {
            wrap.innerHTML = '<p class="text-sm text-gray-400 italic py-8 text-center">No questions yet. Click “Add question” to start.</p>';
            wrap.classList.remove('hidden');
            return;
        }

        wrap.innerHTML = questions.map((q, i) => card(q, i)).join('');
        wrap.classList.remove('hidden');
    }

    function syncContext() {
        contextText = document.getElementById('survey-context').value.trim();
    }

    function card(q, i) {
        const isSingle = (q.type || 'single') === 'single';
        const opts = (q.options || []).map((opt, oi) => `
            <div class="flex items-center gap-2">
                <input type="text" value="${esc(opt)}" data-q="${i}" data-opt="${oi}"
                       class="opt-input flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-teal-400">
                <button type="button" data-remove-opt="${i}:${oi}"
                        class="text-gray-300 hover:text-red-500 text-lg leading-none px-1">&times;</button>
            </div>`).join('');

        return `
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider pt-1">Question ${i + 1}</span>
                <button type="button" data-remove-q="${i}"
                        class="text-xs text-gray-400 hover:text-red-500 transition-colors">Remove</button>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Question text</label>
                <input type="text" value="${esc(q.label)}" data-q="${i}" data-field="label"
                       class="q-input w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-teal-400">
            </div>

            <div class="flex items-center gap-5 flex-wrap">
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <span class="text-xs text-gray-500">Type</span>
                    <select data-q="${i}" data-field="type"
                            class="q-input border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-teal-400">
                        <option value="single" ${isSingle ? 'selected' : ''}>Multiple choice</option>
                        <option value="text"   ${!isSingle ? 'selected' : ''}>Free text</option>
                    </select>
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" data-q="${i}" data-field="required" ${q.required ? 'checked' : ''}
                           class="q-check text-teal-600 focus:ring-teal-400 rounded">
                    Required
                </label>
            </div>

            <div class="${isSingle ? '' : 'hidden'}" data-opts-wrap="${i}">
                <label class="block text-xs text-gray-500 mb-2">Options</label>
                <div class="space-y-2">${opts}</div>
                <button type="button" data-add-opt="${i}"
                        class="mt-2 text-xs font-medium text-teal-600 hover:text-teal-700">+ Add option</button>
            </div>
        </div>`;
    }

    // Pull current DOM values back into the questions array
    function sync() {
        document.querySelectorAll('.q-input').forEach(el => {
            const i = +el.dataset.q, f = el.dataset.field;
            questions[i][f] = el.value;
        });
        document.querySelectorAll('.q-check').forEach(el => {
            questions[+el.dataset.q][el.dataset.field] = el.checked;
        });
        document.querySelectorAll('.opt-input').forEach(el => {
            const i = +el.dataset.q, oi = +el.dataset.opt;
            if (!questions[i].options) questions[i].options = [];
            questions[i].options[oi] = el.value;
        });
    }

    document.addEventListener('click', function (e) {
        const t = e.target;

        if (t.id === 'add-question-btn') {
            sync();
            questions.push({ key: '', label: '', type: 'single', required: true, options: ['', ''] });
            render();
        } else if (t.dataset.removeQ !== undefined) {
            sync();
            questions.splice(+t.dataset.removeQ, 1);
            render();
        } else if (t.dataset.addOpt !== undefined) {
            sync();
            const i = +t.dataset.addOpt;
            if (!questions[i].options) questions[i].options = [];
            questions[i].options.push('');
            render();
        } else if (t.dataset.removeOpt !== undefined) {
            sync();
            const [i, oi] = t.dataset.removeOpt.split(':').map(Number);
            questions[i].options.splice(oi, 1);
            render();
        } else if (t.id === 'save-btn') {
            save();
        }
    });

    // Toggle option visibility when type changes
    document.addEventListener('change', function (e) {
        if (e.target.dataset.field === 'type') {
            sync();
            render();
        }
    });

    async function save() {
        sync();
        syncContext();
        // Derive a stable key from the label when missing
        questions.forEach(q => { if (!q.key) q.key = slugify(q.label); });

        if (!contextText) return setStatus('Qualification context is required.', true);

        // Client-side validation mirrors the backend
        const keys = new Set();
        for (let i = 0; i < questions.length; i++) {
            const q = questions[i];
            if (!q.label.trim()) return setStatus('Question ' + (i + 1) + ' needs text.', true);
            if (!q.key) return setStatus('Question ' + (i + 1) + ' needs a valid label.', true);
            if (keys.has(q.key)) return setStatus('Duplicate question key “' + q.key + '”.', true);
            keys.add(q.key);
            if (q.type === 'single') {
                q.options = (q.options || []).map(o => o.trim()).filter(Boolean);
                if (!q.options.length) return setStatus('Question ' + (i + 1) + ' needs at least one option.', true);
            } else {
                q.options = [];
            }
        }

        const btn = document.getElementById('save-btn');
        btn.disabled = true;
        btn.classList.add('opacity-60');
        setStatus('Saving…', false);

        try {
            const res  = await fetch(API_BASE + '/wl/admin/survey/questions', {
                method: 'PUT',
                headers: headers(),
                body: JSON.stringify({ context: contextText, questions })
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Save failed');
            questions = Array.isArray(data.questions) ? data.questions : questions;
            contextText = String(data.context || contextText);
            render();
            setStatus('Saved ✓', false);
        } catch (e) {
            setStatus(e.message, true);
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-60');
        }
    }

    function setStatus(msg, isError) {
        const el = document.getElementById('save-status');
        el.textContent = msg;
        el.className = 'text-sm ' + (isError ? 'text-red-500' : 'text-gray-500');
    }

    load();
})();
</script>
