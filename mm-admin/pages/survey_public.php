<?php
// Public verification survey - no JWT required
// Accessed via /survey/{64-char-token}
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002' : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
if (strlen($token) !== 64) { http_response_code(404); echo '404 - Not found'; exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm your details</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4">

<div id="app" class="max-w-xl mx-auto">
    <div id="loading" class="text-center text-slate-400 py-20">Loading…</div>
    <div id="error"   class="hidden text-center text-red-500 py-20"></div>
    <div id="content" class="hidden"></div>
</div>

<script>
const TOKEN    = '<?= htmlspecialchars($token, ENT_QUOTES) ?>';
const API_BASE = '<?= API_BASE ?>';
const API_KEY  = '<?= API_KEY ?>';
const ORIGIN   = '<?= ORIGIN ?>';

function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function loadSurvey() {
    try {
        const res  = await fetch(API_BASE + '/wl/public/survey/' + TOKEN, {
            headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN }
        });
        const data = await res.json();
        if (!res.ok) { showError(data.error || 'Survey not found.'); return; }
        render(data);
    } catch (e) {
        showError('Network error: ' + e.message);
    }
}

function showError(msg) {
    document.getElementById('loading').classList.add('hidden');
    const el = document.getElementById('error');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function render(d) {
    document.getElementById('loading').classList.add('hidden');
    const el = document.getElementById('content');
    const co = d.company || {};
    const cust = d.customer || {};

    const logoHtml = co.logo_url
        ? `<img src="${esc(co.logo_url)}" alt="logo" style="max-height:56px;max-width:180px;object-fit:contain">`
        : `<div class="text-xl font-bold text-slate-800">${esc(co.name || '')}</div>`;

    if (d.status === 'completed') {
        el.innerHTML = `
        <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
            <div class="mb-4">${logoHtml}</div>
            <div class="text-5xl mb-3">&#9989;</div>
            <h1 class="text-xl font-semibold text-slate-800 mb-2">Thank you!</h1>
            <p class="text-slate-500">Your details have been confirmed. You're all set.</p>
        </div>`;
        el.classList.remove('hidden');
        return;
    }

    el.innerHTML = `
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-8 border-b border-slate-100">
            <div class="mb-4">${logoHtml}</div>
            <h1 class="text-xl font-semibold text-slate-800">A few quick questions</h1>
            <p class="text-slate-500 text-sm mt-1">${cust.name ? 'Hi ' + esc(cust.name) + ', please' : 'Please'} answer the questions below so we can help you better.</p>
        </div>
        <form id="survey-form" class="p-8 space-y-6">
            ${renderQuestions(d.questions || [])}
            <div id="form-error" class="hidden text-sm text-red-500"></div>
            <button type="submit" id="submit-btn"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition-colors">
                Submit &amp; Confirm
            </button>
        </form>
    </div>`;
    el.classList.remove('hidden');

    document.getElementById('survey-form').addEventListener('submit', submitSurvey);
}

// Build the form fields from the tenant's question set
function renderQuestions(questions) {
    if (!questions.length) {
        return `<p class="text-slate-400 text-sm">No questions configured.</p>`;
    }
    return questions.map(q => {
        const key  = esc(q.key);
        const req  = q.required ? '<span class="text-red-400">*</span>' : '';
        const lbl  = `<label class="block text-sm font-medium text-slate-700 mb-2">${esc(q.label)} ${req}</label>`;

        if (q.type === 'single' && Array.isArray(q.options) && q.options.length) {
            const opts = q.options.map((opt, i) => `
                <label class="flex items-center gap-2.5 px-3 py-2 border border-slate-200 rounded-lg cursor-pointer hover:border-teal-300 transition-colors">
                    <input type="radio" name="${key}" value="${esc(opt)}" ${q.required ? 'required' : ''}
                           class="text-teal-600 focus:ring-teal-400">
                    <span class="text-sm text-slate-600">${esc(opt)}</span>
                </label>`).join('');
            return `<div data-key="${key}">${lbl}<div class="space-y-2">${opts}</div></div>`;
        }

        // Free-text question
        return `<div data-key="${key}">${lbl}
            <textarea name="${key}" rows="3" ${q.required ? 'required' : ''}
                      class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-teal-400"></textarea>
        </div>`;
    }).join('');
}

async function submitSurvey(e) {
    e.preventDefault();
    const form = e.target;
    const btn  = document.getElementById('submit-btn');
    const errEl = document.getElementById('form-error');
    errEl.classList.add('hidden');

    // Collect answers keyed by each question's key
    const responses = {};
    form.querySelectorAll('[data-key]').forEach(wrap => {
        const key = wrap.getAttribute('data-key');
        const field = form.elements[key];
        if (!field) return;
        if (field instanceof RadioNodeList || (field.length && field[0] && field[0].type === 'radio')) {
            const checked = form.querySelector(`input[name="${CSS.escape(key)}"]:checked`);
            responses[key] = checked ? checked.value : '';
        } else {
            responses[key] = (field.value || '').trim();
        }
    });

    btn.disabled = true;
    btn.classList.add('opacity-60');
    btn.textContent = 'Submitting…';

    try {
        const res  = await fetch(API_BASE + '/wl/public/survey/' + TOKEN + '/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-API-Key': API_KEY, 'Origin': ORIGIN },
            body: JSON.stringify({ responses })
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.error || 'Submit failed');
        loadSurvey(); // re-render as completed
    } catch (err) {
        errEl.textContent = err.message;
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.classList.remove('opacity-60');
        btn.textContent = 'Submit & Confirm';
    }
}

loadSurvey();
</script>
</body>
</html>
