<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$layout    = 'app';
$activeNav = 'ai-settings';
?>
<script type="application/json" id="page-meta">
{
    "title": "AI Settings - Majestic Marquees Admin",
    "description": "Configure the Gemini API key and model used across the panel"
}
</script>

<div class="space-y-6 max-w-3xl">

    <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">AI Settings</h2>
            <p class="text-sm text-gray-500 mt-1">Set the Gemini API key and choose the model used by the invoice assistant and lead qualification. Models are loaded live, so new releases appear here automatically.</p>
        </div>
    </div>

    <div id="loading" class="text-center text-gray-400 py-16">Loading…</div>
    <div id="error"   class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-lg p-4"></div>

    <div id="panel" class="hidden space-y-6">

        <!-- Health status banner -->
        <div id="health-banner" class="hidden rounded-lg p-4 text-sm border"></div>

        <!-- API key -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <label for="api-key" class="block text-sm font-medium text-gray-700">Gemini API key</label>
            <p class="text-xs text-gray-500">Stored securely for this account. Leave blank to keep the current key. The key is never shown again once saved.</p>
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <input id="api-key" type="password" autocomplete="off" spellcheck="false"
                       placeholder="Enter a new key to replace the current one"
                       class="flex-1 min-w-0 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-teal-400">
                <button type="button" id="toggle-key"
                        class="shrink-0 text-sm px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Show</button>
            </div>
            <p id="key-state" class="text-xs text-gray-400"></p>
        </div>

        <!-- Model -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <label for="model" class="block text-sm font-medium text-gray-700">Model</label>
                <button type="button" id="refresh-models"
                        class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Refresh list</button>
            </div>
            <select id="model"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-teal-400">
                <option value="">Loading models…</option>
            </select>
            <p id="model-desc" class="text-xs text-gray-500"></p>
            <div class="flex items-center gap-3 pt-1">
                <button type="button" id="test-btn"
                        class="text-sm font-medium px-4 py-2 rounded-lg border border-teal-600 text-teal-700 hover:bg-teal-50 transition-colors">Test this model</button>
                <span id="test-status" class="text-sm text-gray-500"></span>
            </div>
        </div>

        <!-- Save -->
        <div class="flex items-center gap-3 pt-1">
            <button type="button" id="save-btn"
                    class="text-sm font-medium px-5 py-2.5 rounded-lg bg-teal-600 text-white hover:bg-teal-700 transition-colors">
                Save changes
            </button>
            <span id="save-status" class="text-sm text-gray-500"></span>
        </div>
    </div>
</div>

<script>
(function () {
    const API_BASE = '<?= API_BASE ?>';
    const API_KEY  = '<?= API_KEY ?>';
    const JWT      = '<?= e($_SESSION['jwt'] ?? '') ?>';

    let settings = { configured: false, api_key_masked: '', model: '', default_model: 'gemini-2.5-flash' };
    let models   = [];

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

    const $ = (id) => document.getElementById(id);

    function showError(msg) {
        $('loading').classList.add('hidden');
        const el = $('error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function setBanner(state, msg) {
        // state: 'ok' | 'bad' | 'checking' | 'hidden'
        const el = $('health-banner');
        if (state === 'hidden') { el.classList.add('hidden'); return; }
        el.classList.remove('hidden');
        const styles = {
            ok:       'bg-green-50 border-green-200 text-green-700',
            bad:      'bg-red-50 border-red-200 text-red-700',
            checking: 'bg-gray-50 border-gray-200 text-gray-600',
        };
        el.className = 'rounded-lg p-4 text-sm border ' + (styles[state] || styles.checking);
        el.innerHTML = msg;
    }

    function renderModelDesc() {
        const m = models.find(x => x.id === $('model').value);
        if (!m) { $('model-desc').textContent = ''; return; }
        const limits = [];
        if (m.input_token_limit)  limits.push('input ' + m.input_token_limit.toLocaleString());
        if (m.output_token_limit) limits.push('output ' + m.output_token_limit.toLocaleString());
        $('model-desc').textContent = (m.description || m.name || m.id) + (limits.length ? '  •  ' + limits.join(' / ') + ' tokens' : '');
    }

    function renderModelOptions() {
        const sel = $('model');
        const chosen = settings.model || settings.default_model;
        if (!models.length) {
            // Listing failed - still let the operator keep/save the current model.
            sel.innerHTML = '<option value="' + esc(chosen) + '">' + esc(chosen) + ' (current)</option>';
            sel.value = chosen;
            renderModelDesc();
            return;
        }
        sel.innerHTML = models.map(m =>
            '<option value="' + esc(m.id) + '">' + esc(m.id) + (m.id === settings.default_model ? '  (default)' : '') + '</option>'
        ).join('');
        // Keep current selection even if it is not in the live list.
        if (!models.some(m => m.id === chosen)) {
            sel.insertAdjacentHTML('afterbegin', '<option value="' + esc(chosen) + '">' + esc(chosen) + ' (current)</option>');
        }
        sel.value = chosen;
        renderModelDesc();
    }

    function renderKeyState() {
        $('key-state').textContent = settings.configured
            ? 'Current key: ' + settings.api_key_masked
            : 'No account key set - falling back to the server default key.';
    }

    async function loadSettings() {
        const res  = await fetch(API_BASE + '/wl/admin/gemini/settings', { headers: headers() });
        if (res.status === 401) { window.location.href = '/login'; return; }
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.error || 'Failed to load settings');
        settings = {
            configured:     !!data.configured,
            api_key_masked: data.api_key_masked || '',
            model:          data.model || '',
            default_model:  data.default_model || 'gemini-2.5-flash',
        };
    }

    async function loadModels() {
        try {
            const res  = await fetch(API_BASE + '/wl/admin/gemini/models', { headers: headers() });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json();
            if (!res.ok || !data.success) {
                models = [];
                $('model-desc').textContent = 'Could not load the live model list: ' + (data.error || 'unknown error');
            } else {
                models = Array.isArray(data.models) ? data.models : [];
            }
        } catch (e) {
            models = [];
            $('model-desc').textContent = 'Could not load the live model list: ' + e.message;
        }
        renderModelOptions();
    }

    async function testModel(model) {
        const res  = await fetch(API_BASE + '/wl/admin/gemini/test', {
            method:  'POST',
            headers: headers(),
            body:    JSON.stringify({ model }),
        });
        if (res.status === 401) { window.location.href = '/login'; return null; }
        return res.json();
    }

    async function runHealthCheck(model) {
        setBanner('checking', 'Checking <strong>' + esc(model) + '</strong>…');
        try {
            const data = await testModel(model);
            if (!data) return;
            if (data.ok) {
                setBanner('ok', '<strong>' + esc(model) + '</strong> is working (' + data.latency_ms + ' ms).');
            } else {
                setBanner('bad', 'This model is not working right now: <strong>' + esc(model) + '</strong>. ' + esc(data.error || ('HTTP ' + data.http_status)));
            }
        } catch (e) {
            setBanner('bad', 'This model is not working right now. ' + esc(e.message));
        }
    }

    function wire() {
        $('toggle-key').addEventListener('click', () => {
            const inp = $('api-key');
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            $('toggle-key').textContent = show ? 'Hide' : 'Show';
        });

        $('model').addEventListener('change', () => {
            renderModelDesc();
            setBanner('hidden', '');
            $('test-status').textContent = '';
        });

        $('refresh-models').addEventListener('click', async () => {
            $('refresh-models').disabled = true;
            $('refresh-models').textContent = 'Refreshing…';
            await loadModels();
            $('refresh-models').disabled = false;
            $('refresh-models').textContent = 'Refresh list';
        });

        $('test-btn').addEventListener('click', async () => {
            const model = $('model').value;
            if (!model) return;
            $('test-btn').disabled = true;
            $('test-status').textContent = 'Testing…';
            $('test-status').className = 'text-sm text-gray-500';
            const data = await testModel(model);
            $('test-btn').disabled = false;
            if (!data) return;
            if (data.ok) {
                $('test-status').textContent = 'Working (' + data.latency_ms + ' ms)';
                $('test-status').className = 'text-sm text-green-600';
                setBanner('ok', '<strong>' + esc(model) + '</strong> is working (' + data.latency_ms + ' ms).');
            } else {
                $('test-status').textContent = 'Not working';
                $('test-status').className = 'text-sm text-red-600';
                setBanner('bad', 'This model is not working right now: <strong>' + esc(model) + '</strong>. ' + esc(data.error || ('HTTP ' + data.http_status)));
            }
        });

        $('save-btn').addEventListener('click', async () => {
            const model  = $('model').value;
            const keyRaw = $('api-key').value;
            if (!model) { return; }
            const body = { model };
            // Only send api_key when the operator typed one (blank keeps current).
            if (keyRaw.trim() !== '') { body.api_key = keyRaw.trim(); }

            $('save-btn').disabled = true;
            $('save-status').textContent = 'Saving…';
            $('save-status').className = 'text-sm text-gray-500';
            try {
                const res  = await fetch(API_BASE + '/wl/admin/gemini/settings', {
                    method:  'PUT',
                    headers: headers(),
                    body:    JSON.stringify(body),
                });
                if (res.status === 401) { window.location.href = '/login'; return; }
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.error || 'Failed to save');
                settings.configured     = !!data.configured;
                settings.api_key_masked = data.api_key_masked || '';
                settings.model          = data.model || model;
                $('api-key').value = '';
                renderKeyState();
                $('save-status').textContent = 'Saved';
                $('save-status').className = 'text-sm text-green-600';
                // Re-check health for the newly saved model/key.
                runHealthCheck(settings.model);
            } catch (e) {
                $('save-status').textContent = e.message;
                $('save-status').className = 'text-sm text-red-600';
            } finally {
                $('save-btn').disabled = false;
            }
        });
    }

    async function init() {
        try {
            await loadSettings();
            $('loading').classList.add('hidden');
            $('panel').classList.remove('hidden');
            renderKeyState();
            wire();
            await loadModels();
            // Auto health check for the active model on load (curl in the background).
            runHealthCheck(settings.model || settings.default_model);
        } catch (e) {
            showError(e.message);
        }
    }

    init();
})();
</script>
