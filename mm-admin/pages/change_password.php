<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$layout    = 'app';
$activeNav = 'change-password';
?>
<script type="application/json" id="page-meta">
{
    "title": "Change Password - Majestic Marquees Admin",
    "description": "Update your account password"
}
</script>

<div class="space-y-6 max-w-lg">

    <div>
        <h2 class="text-xl font-semibold text-gray-800">Change Password</h2>
        <p class="text-sm text-gray-500 mt-1">Choose a strong password of at least 8 characters with an uppercase letter, a lowercase letter, a number, and a special character. You will stay signed in on this device.</p>
    </div>

    <form id="pw-form" class="bg-white rounded-xl border border-gray-200 p-5 space-y-4" autocomplete="off">
        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
            <input type="password" id="current_password" name="current_password" required
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
        </div>
        <div>
            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New password</label>
            <input type="password" id="new_password" name="new_password" required minlength="8"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            <ul id="pw-reqs" class="mt-2 space-y-1 text-xs text-gray-500">
                <li data-req="len"     class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> At least 8 characters</li>
                <li data-req="upper"   class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> An uppercase letter (A-Z)</li>
                <li data-req="lower"   class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A lowercase letter (a-z)</li>
                <li data-req="num"     class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A number (0-9)</li>
                <li data-req="special" class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A special character (e.g. !?@#$)</li>
            </ul>
        </div>
        <div>
            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
        </div>

        <div id="pw-error" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2"></div>
        <div id="pw-ok"    class="hidden text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2"></div>

        <div class="flex items-center gap-3 pt-1">
            <button type="submit" id="pw-save"
                    class="text-sm font-medium px-5 py-2.5 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors disabled:opacity-50">
                Update password
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const API_BASE = '<?= API_BASE ?>';
    const API_KEY  = '<?= API_KEY ?>';
    const JWT      = '<?= e($_SESSION['jwt'] ?? '') ?>';

    const form    = document.getElementById('pw-form');
    const errBox  = document.getElementById('pw-error');
    const okBox   = document.getElementById('pw-ok');
    const saveBtn = document.getElementById('pw-save');
    const newPw   = document.getElementById('new_password');
    const reqs    = document.getElementById('pw-reqs');

    function pwChecks(v) {
        return {
            len:     v.length >= 8,
            upper:   /[A-Z]/.test(v),
            lower:   /[a-z]/.test(v),
            num:     /[0-9]/.test(v),
            special: /[^A-Za-z0-9]/.test(v),
        };
    }
    function pwAllOk(v) {
        const c = pwChecks(v);
        return c.len && c.upper && c.lower && c.num && c.special;
    }
    function renderReqs() {
        const c = pwChecks(newPw.value);
        reqs.querySelectorAll('li[data-req]').forEach(function (li) {
            const ok  = c[li.getAttribute('data-req')];
            const dot = li.querySelector('.pw-dot');
            li.classList.toggle('text-green-600', ok);
            li.classList.toggle('text-gray-500', !ok);
            dot.innerHTML = ok ? '&#10003;' : '&#9675;';
        });
    }
    newPw.addEventListener('input', renderReqs);
    renderReqs();

    function headers() {
        return {
            'Content-Type':  'application/json',
            'X-API-Key':     API_KEY,
            'Authorization': 'Bearer ' + JWT,
        };
    }

    function show(box, msg) {
        box.textContent = msg;
        box.classList.remove('hidden');
    }
    function hide(box) { box.classList.add('hidden'); }

    form.addEventListener('submit', async function (ev) {
        ev.preventDefault();
        hide(errBox); hide(okBox);

        const current = document.getElementById('current_password').value;
        const next    = document.getElementById('new_password').value;
        const confirm = document.getElementById('confirm_password').value;

        if (!pwAllOk(next)) { show(errBox, 'Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.'); return; }
        if (next !== confirm) { show(errBox, 'New password and confirmation do not match.'); return; }
        if (next === current) { show(errBox, 'New password must be different from your current password.'); return; }

        saveBtn.disabled = true;
        try {
            const res = await fetch(API_BASE + '/wl/admin/me/password', {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify({ current_password: current, new_password: next }),
            });
            const data = await res.json().catch(() => ({}));
            // A true session expiry returns the fixed 'Unauthorized' string;
            // a wrong current password also returns 401 but with its own message.
            if (res.status === 401 && data.error === 'Unauthorized') { window.location.href = '/login'; return; }
            if (!res.ok || !data.success) throw new Error(data.error || 'Could not update password');
            form.reset();
            show(okBox, 'Your password has been updated.');
        } catch (e) {
            show(errBox, e.message);
        } finally {
            saveBtn.disabled = false;
        }
    });
})();
</script>
