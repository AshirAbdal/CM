<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$layout    = 'app';
$activeNav = 'smtp-settings';
?>
<script type="application/json" id="page-meta">
{
    "title": "Email / SMTP Settings - Majestic Marquees Admin",
    "description": "Configure the outgoing email (SMTP) account used to send all mail"
}
</script>

<div class="space-y-6 max-w-3xl">

    <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Email / SMTP Settings</h2>
            <p class="text-sm text-gray-500 mt-1">Set the outgoing mail account used for verification codes, invitations, password resets, offers and lead conversations. Use the Test button to confirm the credentials work before saving.</p>
        </div>
    </div>

    <div id="loading" class="text-center text-gray-400 py-16">Loading…</div>
    <div id="error"   class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-lg p-4"></div>

    <div id="panel" class="hidden space-y-6">

        <!-- Health status banner -->
        <div id="health-banner" class="hidden rounded-lg p-4 text-sm border"></div>

        <!-- Server -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Mail server</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="host" class="block text-sm font-medium text-gray-700 mb-1">SMTP host</label>
                    <input id="host" type="text" autocomplete="off" spellcheck="false" placeholder="e.g. smtp.yourdomain.com"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-teal-400">
                </div>
                <div>
                    <label for="port" class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                    <input id="port" type="number" min="1" max="65535" placeholder="465"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-teal-400">
                </div>
                <div>
                    <label for="encryption" class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                    <select id="encryption"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-teal-400">
                        <option value="ssl">SSL (usually port 465)</option>
                        <option value="tls">TLS / STARTTLS (usually port 587)</option>
                        <option value="none">None (not recommended)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Credentials -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Login</h3>
            <div>
                <label for="user" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input id="user" type="text" autocomplete="off" spellcheck="false" placeholder="e.g. you@yourdomain.com"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-teal-400">
            </div>
            <div>
                <label for="pass" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <input id="pass" type="password" autocomplete="new-password" spellcheck="false"
                           placeholder="Enter the mailbox password"
                           class="flex-1 min-w-0 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-teal-400">
                    <button type="button" id="toggle-pass"
                            class="shrink-0 text-sm px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Show</button>
                </div>
                <p id="pass-state" class="text-xs text-gray-400 mt-1"></p>
            </div>
        </div>

        <!-- Identity -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Sender identity</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="from_name" class="block text-sm font-medium text-gray-700 mb-1">From name</label>
                    <input id="from_name" type="text" placeholder="e.g. Majestic Marquees"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-teal-400">
                </div>
                <div>
                    <label for="from_email" class="block text-sm font-medium text-gray-700 mb-1">From email</label>
                    <input id="from_email" type="email" autocomplete="off" spellcheck="false" placeholder="Defaults to the username"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-teal-400">
                </div>
            </div>
        </div>

        <!-- Test -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-sm font-semibold text-gray-700">Test the connection</h3>
            <p class="text-xs text-gray-500">Verify the settings above without saving. Enter a recipient to also send a real test email; leave it blank to only check the connection and login.</p>
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <input id="test-to" type="email" autocomplete="off" spellcheck="false" placeholder="Send a test email to (optional)"
                       class="flex-1 min-w-0 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-teal-400">
                <button type="button" id="test-btn"
                        class="shrink-0 text-sm font-medium px-4 py-2 rounded-lg border border-teal-600 text-teal-700 hover:bg-teal-50 transition-colors">Test</button>
            </div>
            <span id="test-status" class="text-sm text-gray-500"></span>
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

    let settings = { configured: false, pass_set: false, pass_masked: '' };

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

    function renderPassState() {
        $('pass-state').textContent = settings.pass_set
            ? 'A password is saved (' + settings.pass_masked + '). Leave blank to keep it, or type a new one to replace it.'
            : 'No password saved yet.';
    }

    function formCfg() {
        const cfg = {
            host:       $('host').value.trim(),
            port:       parseInt($('port').value, 10) || 0,
            encryption: $('encryption').value,
            user:       $('user').value.trim(),
            from_name:  $('from_name').value.trim(),
            from_email: $('from_email').value.trim(),
        };
        // Only send a password when the operator typed one (blank keeps current).
        const pass = $('pass').value;
        if (pass.trim() !== '') { cfg.pass = pass; }
        return cfg;
    }

    async function loadSettings() {
        const res  = await fetch(API_BASE + '/wl/admin/smtp/settings', { headers: headers() });
        if (res.status === 401) { window.location.href = '/login'; return; }
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.error || 'Failed to load settings');
        settings = data;
        $('host').value       = data.host || '';
        $('port').value       = data.port || 465;
        $('encryption').value = data.encryption || 'ssl';
        $('user').value       = data.user || '';
        $('from_name').value  = data.from_name || '';
        $('from_email').value = data.from_email || '';
        $('pass').value       = '';
    }

    async function runTest() {
        const cfg = formCfg();
        if (!cfg.host || !cfg.user) {
            setBanner('bad', 'Enter at least a host and username before testing.');
            return;
        }
        const to = $('test-to').value.trim();
        if (to !== '') { cfg.to = to; }

        $('test-btn').disabled = true;
        $('test-status').textContent = 'Testing…';
        $('test-status').className = 'text-sm text-gray-500';
        setBanner('checking', 'Contacting <strong>' + esc(cfg.host) + '</strong>…');
        try {
            const res  = await fetch(API_BASE + '/wl/admin/smtp/test', {
                method:  'POST',
                headers: headers(),
                body:    JSON.stringify(cfg),
            });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Test failed');
            if (data.ok) {
                const detail = data.mode === 'send'
                    ? 'Test email sent to <strong>' + esc(data.sent_to) + '</strong> (' + data.latency_ms + ' ms).'
                    : 'Connection and login succeeded (' + data.latency_ms + ' ms).';
                setBanner('ok', detail);
                $('test-status').textContent = 'Working';
                $('test-status').className = 'text-sm text-green-600';
            } else {
                setBanner('bad', 'The settings are not working: ' + esc(data.error || 'unknown error'));
                $('test-status').textContent = 'Not working';
                $('test-status').className = 'text-sm text-red-600';
            }
        } catch (e) {
            setBanner('bad', 'Test failed: ' + esc(e.message));
            $('test-status').textContent = 'Not working';
            $('test-status').className = 'text-sm text-red-600';
        } finally {
            $('test-btn').disabled = false;
        }
    }

    async function save() {
        const cfg = formCfg();
        if (!cfg.host)              { $('save-status').textContent = 'Host is required.'; $('save-status').className = 'text-sm text-red-600'; return; }
        if (!cfg.user)              { $('save-status').textContent = 'Username is required.'; $('save-status').className = 'text-sm text-red-600'; return; }
        if (!cfg.port)              { $('save-status').textContent = 'A valid port is required.'; $('save-status').className = 'text-sm text-red-600'; return; }
        if (!settings.pass_set && !cfg.pass) {
            $('save-status').textContent = 'A password is required.';
            $('save-status').className = 'text-sm text-red-600';
            return;
        }

        $('save-btn').disabled = true;
        $('save-status').textContent = 'Saving…';
        $('save-status').className = 'text-sm text-gray-500';
        try {
            const res  = await fetch(API_BASE + '/wl/admin/smtp/settings', {
                method:  'PUT',
                headers: headers(),
                body:    JSON.stringify(cfg),
            });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Failed to save');
            settings = data;
            $('pass').value = '';
            renderPassState();
            $('save-status').textContent = 'Saved';
            $('save-status').className = 'text-sm text-green-600';
        } catch (e) {
            $('save-status').textContent = e.message;
            $('save-status').className = 'text-sm text-red-600';
        } finally {
            $('save-btn').disabled = false;
        }
    }

    function wire() {
        $('toggle-pass').addEventListener('click', () => {
            const inp = $('pass');
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            $('toggle-pass').textContent = show ? 'Hide' : 'Show';
        });
        // A sensible default port when switching encryption (only if empty/standard).
        $('encryption').addEventListener('change', () => {
            const p = parseInt($('port').value, 10);
            if (!p || p === 465 || p === 587 || p === 25) {
                $('port').value = $('encryption').value === 'tls' ? 587 : ($('encryption').value === 'none' ? 25 : 465);
            }
        });
        $('test-btn').addEventListener('click', runTest);
        $('save-btn').addEventListener('click', save);
    }

    async function init() {
        try {
            await loadSettings();
            $('loading').classList.add('hidden');
            $('panel').classList.remove('hidden');
            renderPassState();
            wire();
        } catch (e) {
            showError(e.message);
        }
    }

    init();
})();
</script>
