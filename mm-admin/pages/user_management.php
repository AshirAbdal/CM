<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$layout    = 'app';
$activeNav = 'user-management';

// Each tab is gated by its own permission. The page route only requires one of
// the two, so a user may legitimately see a single tab.
$canUsers = can('users.manage');
$canRoles = can('roles.manage');

// Default tab = first one the admin may use.
$defaultTab = $canUsers ? 'users' : 'roles';

$tabActive = 'border-tan-500 text-tan-600';
$tabIdle   = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
?>
<script type="application/json" id="page-meta">
{
    "title": "Users & Roles - Majestic Marquees Admin",
    "description": "Manage admin users, roles and permissions"
}
</script>

<div class="space-y-6">

    <div>
        <h2 class="text-xl font-semibold text-gray-800">Users &amp; Roles</h2>
        <p class="text-sm text-gray-500 mt-1">Manage who can sign in and what each role is allowed to do.</p>
    </div>

    <!-- Tab bar -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-6" id="um-tabs">
            <?php if ($canUsers): ?>
            <button type="button" data-tab="users"
                    class="um-tab whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors <?= $defaultTab === 'users' ? $tabActive : $tabIdle ?>">
                Users
            </button>
            <?php endif; ?>
            <?php if ($canRoles): ?>
            <button type="button" data-tab="roles"
                    class="um-tab whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors <?= $defaultTab === 'roles' ? $tabActive : $tabIdle ?>">
                Roles &amp; Permissions
            </button>
            <?php endif; ?>
        </nav>
    </div>

    <?php if ($canUsers): ?>
    <!-- Users panel -->
    <section id="panel-users" class="um-panel space-y-4 <?= $defaultTab === 'users' ? '' : 'hidden' ?>">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <p class="text-sm text-gray-500">Create accounts and assign each person a role.</p>
            <button type="button" id="add-user-btn"
                    class="text-sm font-medium px-4 py-2 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors">
                + Add user
            </button>
        </div>

        <div id="u-loading" class="text-center text-gray-400 py-16">Loading…</div>
        <div id="u-error"   class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-lg p-4"></div>

        <div id="u-table-wrap" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left font-medium px-4 py-3">Name</th>
                            <th class="text-left font-medium px-4 py-3">Email</th>
                            <th class="text-left font-medium px-4 py-3">Role</th>
                            <th class="text-left font-medium px-4 py-3">Status</th>
                            <th class="text-left font-medium px-4 py-3">Last login</th>
                            <th class="text-right font-medium px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-body" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($canRoles): ?>
    <!-- Roles panel -->
    <section id="panel-roles" class="um-panel space-y-4 <?= $defaultTab === 'roles' ? '' : 'hidden' ?>">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <p class="text-sm text-gray-500">Create roles and tick the pages and capabilities each one can use.</p>
            <button type="button" id="add-role-btn"
                    class="text-sm font-medium px-4 py-2 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors">
                + Add role
            </button>
        </div>

        <div id="r-loading" class="text-center text-gray-400 py-16">Loading…</div>
        <div id="r-error"   class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-lg p-4"></div>

        <div id="roles-list" class="hidden grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
    </section>
    <?php endif; ?>
</div>

<?php if ($canUsers): ?>
<!-- Modal: Create / Edit user -->
<div id="user-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" data-close-modal></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
        <h3 id="user-modal-title" class="text-lg font-semibold text-gray-800">Add user</h3>

        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="f-name" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            </div>
            <div id="f-email-wrap">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="f-email" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            </div>
            <div id="f-invite-note" class="text-sm text-gray-600 bg-tan-50 border border-tan-200 rounded-lg px-3 py-2">
                We will email this person a secure link to set their own password and activate their account.
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select id="f-role" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-tan-400"></select>
            </div>
            <div id="f-active-wrap" class="hidden">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" id="f-active" class="rounded border-gray-300 text-tan-500 focus:ring-tan-400">
                    Account is active
                </label>
            </div>
        </div>

        <div id="user-modal-error" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2"></div>

        <div class="flex items-center justify-end gap-3 pt-1">
            <button type="button" data-close-modal class="text-sm px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
            <button type="button" id="user-save" class="text-sm font-medium px-5 py-2 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors disabled:opacity-50">Save</button>
        </div>
    </div>
</div>

<!-- Modal: Reset password -->
<div id="reset-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" data-close-modal></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">Reset password</h3>
        <p class="text-sm text-gray-500">Set a new password for <span id="reset-name" class="font-medium text-gray-700"></span>.</p>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">New password</label>
            <input type="password" id="reset-password" minlength="8" autocomplete="new-password" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
            <ul id="reset-pw-reqs" class="mt-2 space-y-1 text-xs text-gray-500">
                <li data-req="len"     class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> At least 8 characters</li>
                <li data-req="upper"   class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> An uppercase letter (A-Z)</li>
                <li data-req="lower"   class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A lowercase letter (a-z)</li>
                <li data-req="num"     class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A number (0-9)</li>
                <li data-req="special" class="flex items-center gap-1.5"><span class="pw-dot">&#9675;</span> A special character (e.g. !?@#$)</li>
            </ul>
        </div>
        <div id="reset-modal-error" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2"></div>
        <div class="flex items-center justify-end gap-3 pt-1">
            <button type="button" data-close-modal class="text-sm px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
            <button type="button" id="reset-save" class="text-sm font-medium px-5 py-2 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors disabled:opacity-50">Reset</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canRoles): ?>
<!-- Modal: Create / Edit role -->
<div id="role-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" data-close-modal></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 space-y-4">
        <h3 id="role-modal-title" class="text-lg font-semibold text-gray-800">Add role</h3>

        <div id="role-system-note" class="hidden text-sm text-tan-700 bg-tan-50 border border-tan-200 rounded-lg px-3 py-2">
            This is a system role with full access. It cannot be edited or deleted.
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Role name</label>
            <input type="text" id="r-name" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="r-desc" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-tan-400">
        </div>

        <div>
            <p class="block text-sm font-medium text-gray-700 mb-2">Permissions</p>
            <div id="r-matrix" class="border border-gray-200 rounded-lg p-3 space-y-3"></div>
        </div>

        <div id="role-modal-error" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2"></div>

        <div class="flex items-center justify-end gap-3 pt-1">
            <button type="button" data-close-modal class="text-sm px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
            <button type="button" id="role-save" class="text-sm font-medium px-5 py-2 rounded-lg bg-tan-500 text-white hover:bg-tan-600 transition-colors disabled:opacity-50">Save</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Tab switching (Users / Roles & Permissions)
(function () {
    const tabs   = Array.from(document.querySelectorAll('.um-tab'));
    const panels = {
        users: document.getElementById('panel-users'),
        roles: document.getElementById('panel-roles'),
    };
    const ACTIVE = ['border-tan-500', 'text-tan-600'];
    const IDLE   = ['border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'];

    function activate(name) {
        tabs.forEach(function (t) {
            const on = t.dataset.tab === name;
            t.classList.remove.apply(t.classList, ACTIVE.concat(IDLE));
            t.classList.add.apply(t.classList, on ? ACTIVE : IDLE);
        });
        Object.keys(panels).forEach(function (k) {
            if (panels[k]) panels[k].classList.toggle('hidden', k !== name);
        });
        try { history.replaceState(null, '', '?tab=' + name); } catch (e) {}
    }

    tabs.forEach(function (t) { t.addEventListener('click', function () { activate(t.dataset.tab); }); });

    const want  = new URLSearchParams(location.search).get('tab');
    const first = tabs.length ? tabs[0].dataset.tab : 'users';
    activate((want && panels[want]) ? want : first);
})();
</script>

<script>
// Users tab
(function () {
    if (!document.getElementById('panel-users')) return;

    const API_BASE = '<?= API_BASE ?>';
    const API_KEY  = '<?= API_KEY ?>';
    const JWT      = '<?= e($_SESSION['jwt'] ?? '') ?>';

    let users = [];
    let roles = [];
    let editingId = null;   // null = create mode
    let resetId   = null;

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

    function showError(msg) {
        document.getElementById('u-loading').classList.add('hidden');
        const el = document.getElementById('u-error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function fmtDate(s) {
        if (!s) return '<span class="text-gray-400">Never</span>';
        return esc(String(s).substring(0, 16).replace('T', ' '));
    }

    async function loadAll() {
        try {
            const [uRes, rRes] = await Promise.all([
                fetch(API_BASE + '/wl/admin/users', { headers: headers() }),
                fetch(API_BASE + '/wl/admin/roles', { headers: headers() }),
            ]);
            if (uRes.status === 401 || rRes.status === 401) { window.location.href = '/login'; return; }
            const uData = await uRes.json();
            const rData = await rRes.json();
            if (!uRes.ok || !uData.success) throw new Error(uData.error || 'Failed to load users');
            if (!rRes.ok || !rData.success) throw new Error(rData.error || 'Failed to load roles');
            users = Array.isArray(uData.users) ? uData.users : [];
            roles = Array.isArray(rData.roles) ? rData.roles : [];
            render();
        } catch (e) {
            showError(e.message);
        }
    }

    function roleOptions(selectedId) {
        return roles.map(r =>
            '<option value="' + r.id + '"' + (r.id === selectedId ? ' selected' : '') + '>' + esc(r.name) + '</option>'
        ).join('');
    }

    function render() {
        document.getElementById('u-loading').classList.add('hidden');
        document.getElementById('u-error').classList.add('hidden');
        document.getElementById('u-table-wrap').classList.remove('hidden');

        const body = document.getElementById('users-body');
        if (users.length === 0) {
            body.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No users yet.</td></tr>';
            return;
        }
        body.innerHTML = users.map(u => {
            const statusBadge = !u.email_verified
                ? '<span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-amber-50 text-amber-700 border border-amber-200">Invited</span>'
                : (u.is_active
                    ? '<span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700 border border-green-200">Active</span>'
                    : '<span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500 border border-gray-200">Inactive</span>');
            const selfBadge = u.is_self
                ? ' <span class="ml-1 text-[10px] uppercase tracking-wide text-tan-600">You</span>'
                : '';
            const resendBtn = !u.email_verified
                ? '<button type="button" data-resend="' + u.id + '" class="text-xs font-medium text-tan-600 hover:text-tan-800 px-2 py-1">Resend invite</button>'
                : '';
            const removeBtn = !u.is_self
                ? '<button type="button" data-remove="' + u.id + '" class="text-xs font-medium text-red-500 hover:text-red-700 px-2 py-1">Remove</button>'
                : '';
            return '<tr>'
                + '<td class="px-4 py-3 text-gray-800">' + esc(u.name) + selfBadge + '</td>'
                + '<td class="px-4 py-3 text-gray-600">' + esc(u.email) + '</td>'
                + '<td class="px-4 py-3 text-gray-600">' + esc(u.role_name || u.role) + '</td>'
                + '<td class="px-4 py-3">' + statusBadge + '</td>'
                + '<td class="px-4 py-3 text-gray-500">' + fmtDate(u.last_login_at) + '</td>'
                + '<td class="px-4 py-3 text-right whitespace-nowrap">'
                +   '<button type="button" data-edit="' + u.id + '" class="text-xs font-medium text-tan-600 hover:text-tan-800 px-2 py-1">Edit</button>'
                +   resendBtn
                +   '<button type="button" data-reset="' + u.id + '" class="text-xs font-medium text-gray-500 hover:text-gray-800 px-2 py-1">Reset password</button>'
                +   removeBtn
                + '</td>'
                + '</tr>';
        }).join('');

        body.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => openEdit(parseInt(b.dataset.edit, 10))));
        body.querySelectorAll('[data-reset]').forEach(b => b.addEventListener('click', () => openReset(parseInt(b.dataset.reset, 10))));
        body.querySelectorAll('[data-resend]').forEach(b => b.addEventListener('click', () => resendInvite(parseInt(b.dataset.resend, 10), b)));
        body.querySelectorAll('[data-remove]').forEach(b => b.addEventListener('click', () => removeUser(parseInt(b.dataset.remove, 10))));
    }

    // Create / Edit modal
    const userModal = document.getElementById('user-modal');
    const userErr   = document.getElementById('user-modal-error');

    function openCreate() {
        editingId = null;
        document.getElementById('user-modal-title').textContent = 'Add user';
        document.getElementById('f-name').value = '';
        document.getElementById('f-email').value = '';
        document.getElementById('f-email-wrap').classList.remove('hidden');
        document.getElementById('f-invite-note').classList.remove('hidden');
        document.getElementById('f-active-wrap').classList.add('hidden');
        document.getElementById('f-role').innerHTML = roleOptions(null);
        userErr.classList.add('hidden');
        userModal.classList.remove('hidden');
    }

    function openEdit(id) {
        const u = users.find(x => x.id === id);
        if (!u) return;
        editingId = id;
        document.getElementById('user-modal-title').textContent = 'Edit user';
        document.getElementById('f-name').value = u.name || '';
        document.getElementById('f-email-wrap').classList.add('hidden');
        document.getElementById('f-invite-note').classList.add('hidden');
        document.getElementById('f-role').innerHTML = roleOptions(u.role_id);
        const activeWrap = document.getElementById('f-active-wrap');
        const activeBox  = document.getElementById('f-active');
        activeBox.checked = !!u.is_active;
        // Cannot deactivate your own account.
        activeWrap.classList.toggle('hidden', !!u.is_self);
        userErr.classList.add('hidden');
        userModal.classList.remove('hidden');
    }

    async function saveUser() {
        userErr.classList.add('hidden');
        const name   = document.getElementById('f-name').value.trim();
        const roleId = parseInt(document.getElementById('f-role').value, 10);
        const btn    = document.getElementById('user-save');

        if (!name) { showModalErr(userErr, 'Name is required.'); return; }

        let url, method, payload;
        if (editingId === null) {
            const email = document.getElementById('f-email').value.trim();
            if (!email) { showModalErr(userErr, 'Email is required.'); return; }
            url = API_BASE + '/wl/admin/users';
            method = 'POST';
            payload = { name, email, role_id: roleId, base_url: window.location.origin };
        } else {
            url = API_BASE + '/wl/admin/users/' + editingId;
            method = 'PUT';
            payload = { name, role_id: roleId };
            if (!document.getElementById('f-active-wrap').classList.contains('hidden')) {
                payload.is_active = document.getElementById('f-active').checked;
            }
        }

        btn.disabled = true;
        try {
            const res = await fetch(url, { method, headers: headers(), body: JSON.stringify(payload) });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.error || 'Could not save user');
            closeModals();
            await loadAll();
            if (editingId === null && data.invited === false) {
                window.alert(data.message || 'User created, but the invitation email could not be sent. Use "Resend invite" to try again.');
            }
        } catch (e) {
            showModalErr(userErr, e.message);
        } finally {
            btn.disabled = false;
        }
    }

    // Reset password modal
    const resetModal = document.getElementById('reset-modal');
    const resetErr   = document.getElementById('reset-modal-error');
    const resetPwReqs = document.getElementById('reset-pw-reqs');

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
    function renderResetReqs() {
        const c = pwChecks(document.getElementById('reset-password').value);
        resetPwReqs.querySelectorAll('li[data-req]').forEach(function (li) {
            const ok  = c[li.getAttribute('data-req')];
            const dot = li.querySelector('.pw-dot');
            li.classList.toggle('text-green-600', ok);
            li.classList.toggle('text-gray-500', !ok);
            dot.innerHTML = ok ? '&#10003;' : '&#9675;';
        });
    }
    document.getElementById('reset-password').addEventListener('input', renderResetReqs);

    function openReset(id) {
        const u = users.find(x => x.id === id);
        if (!u) return;
        resetId = id;
        document.getElementById('reset-name').textContent = u.name;
        document.getElementById('reset-password').value = '';
        renderResetReqs();
        resetErr.classList.add('hidden');
        resetModal.classList.remove('hidden');
    }

    async function saveReset() {
        resetErr.classList.add('hidden');
        const pass = document.getElementById('reset-password').value;
        const btn  = document.getElementById('reset-save');
        if (!pwAllOk(pass)) { showModalErr(resetErr, 'Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.'); return; }
        btn.disabled = true;
        try {
            const res = await fetch(API_BASE + '/wl/admin/users/' + resetId + '/password', {
                method: 'POST', headers: headers(), body: JSON.stringify({ new_password: pass }),
            });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.error || 'Could not reset password');
            closeModals();
        } catch (e) {
            showModalErr(resetErr, e.message);
        } finally {
            btn.disabled = false;
        }
    }

    function showModalErr(box, msg) { box.textContent = msg; box.classList.remove('hidden'); }
    function closeModals() {
        userModal.classList.add('hidden');
        resetModal.classList.add('hidden');
    }

    async function resendInvite(id, btn) {
        const u = users.find(x => x.id === id);
        if (!u) return;
        if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }
        try {
            const res = await fetch(API_BASE + '/wl/admin/users/' + id + '/invite', {
                method: 'POST', headers: headers(), body: JSON.stringify({ base_url: window.location.origin }),
            });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.error || 'Could not send invite');
            if (btn) btn.textContent = 'Invite sent';
        } catch (e) {
            if (btn) { btn.disabled = false; btn.textContent = 'Resend invite'; }
            window.alert(e.message);
        }
    }

    async function removeUser(id) {
        const u = users.find(x => x.id === id);
        if (!u) return;
        if (!window.confirm('Remove ' + u.name + '? This permanently deletes their account and cannot be undone.')) return;
        try {
            const res = await fetch(API_BASE + '/wl/admin/users/' + id, { method: 'DELETE', headers: headers() });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.error || 'Could not remove user');
            await loadAll();
        } catch (e) {
            window.alert(e.message);
        }
    }

    // Wiring
    document.getElementById('add-user-btn').addEventListener('click', openCreate);
    document.getElementById('user-save').addEventListener('click', saveUser);
    document.getElementById('reset-save').addEventListener('click', saveReset);
    document.querySelectorAll('#user-modal [data-close-modal], #reset-modal [data-close-modal]')
        .forEach(el => el.addEventListener('click', closeModals));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModals(); });

    loadAll();
})();
</script>

<script>
// Roles & Permissions tab
(function () {
    if (!document.getElementById('panel-roles')) return;

    const API_BASE = '<?= API_BASE ?>';
    const API_KEY  = '<?= API_KEY ?>';
    const JWT      = '<?= e($_SESSION['jwt'] ?? '') ?>';

    let roles      = [];
    let catalog    = {};   // { group: { key: label } }
    let assignable = [];   // permission keys the current admin may grant
    let editingId  = null; // null = create

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

    function showError(msg) {
        document.getElementById('r-loading').classList.add('hidden');
        const el = document.getElementById('r-error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    async function loadAll() {
        try {
            const [rRes, cRes] = await Promise.all([
                fetch(API_BASE + '/wl/admin/roles', { headers: headers() }),
                fetch(API_BASE + '/wl/admin/roles/catalog', { headers: headers() }),
            ]);
            if (rRes.status === 401 || cRes.status === 401) { window.location.href = '/login'; return; }
            const rData = await rRes.json();
            const cData = await cRes.json();
            if (!rRes.ok || !rData.success) throw new Error(rData.error || 'Failed to load roles');
            if (!cRes.ok || !cData.success) throw new Error(cData.error || 'Failed to load catalog');
            roles      = Array.isArray(rData.roles) ? rData.roles : [];
            catalog    = cData.catalog || {};
            assignable = Array.isArray(cData.assignable) ? cData.assignable : [];
            render();
        } catch (e) {
            showError(e.message);
        }
    }

    function permLabel(key) {
        for (const group of Object.values(catalog)) {
            if (group[key]) return group[key];
        }
        return key;
    }

    function render() {
        document.getElementById('r-loading').classList.add('hidden');
        document.getElementById('r-error').classList.add('hidden');
        const list = document.getElementById('roles-list');
        list.classList.remove('hidden');

        list.innerHTML = roles.map(r => {
            const sysBadge = r.is_system
                ? '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] bg-tan-100 text-tan-700 border border-tan-200">System</span>'
                : '';
            const summary = r.is_full_access
                ? '<span class="text-gray-500">Full access to everything</span>'
                : (r.permissions.length
                    ? r.permissions.map(k => '<span class="inline-block bg-gray-100 text-gray-600 rounded px-1.5 py-0.5 text-[11px] mr-1 mb-1">' + esc(permLabel(k)) + '</span>').join('')
                    : '<span class="text-gray-400 italic">No permissions</span>');
            const actions = r.is_system
                ? '<button type="button" data-view="' + r.id + '" class="text-xs font-medium text-gray-500 hover:text-gray-800 px-2 py-1">View</button>'
                : '<button type="button" data-edit="' + r.id + '" class="text-xs font-medium text-tan-600 hover:text-tan-800 px-2 py-1">Edit</button>'
                  + '<button type="button" data-del="' + r.id + '" class="text-xs font-medium text-red-500 hover:text-red-700 px-2 py-1">Delete</button>';

            return '<div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col">'
                + '<div class="flex items-start justify-between gap-2">'
                +   '<div><h3 class="text-sm font-semibold text-gray-800">' + esc(r.name) + '</h3>'
                +   (r.description ? '<p class="text-xs text-gray-500 mt-0.5">' + esc(r.description) + '</p>' : '')
                +   '</div>' + sysBadge
                + '</div>'
                + '<div class="mt-3 flex-1 text-xs leading-relaxed">' + summary + '</div>'
                + '<div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">'
                +   '<span class="text-[11px] text-gray-400">' + r.user_count + ' user' + (r.user_count === 1 ? '' : 's') + '</span>'
                +   '<div class="whitespace-nowrap">' + actions + '</div>'
                + '</div>'
                + '</div>';
        }).join('');

        list.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => openEdit(parseInt(b.dataset.edit, 10), false)));
        list.querySelectorAll('[data-view]').forEach(b => b.addEventListener('click', () => openEdit(parseInt(b.dataset.view, 10), true)));
        list.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => deleteRole(parseInt(b.dataset.del, 10))));
    }

    // Matrix
    function renderMatrix(selectedKeys, readOnly) {
        return Object.entries(catalog).map(([group, perms]) => {
            const items = Object.entries(perms).map(([key, label]) => {
                const allowed = assignable.includes(key);
                const checked = selectedKeys.includes(key);
                const disabled = readOnly || !allowed;
                const note = (!allowed && !readOnly) ? ' <span class="text-[10px] text-gray-400">(not available to you)</span>' : '';
                return '<label class="flex items-start gap-2 py-0.5 ' + (disabled ? 'opacity-60' : '') + '">'
                    + '<input type="checkbox" value="' + esc(key) + '" ' + (checked ? 'checked' : '') + ' ' + (disabled ? 'disabled' : '')
                    + ' class="perm-box mt-0.5 rounded border-gray-300 text-tan-500 focus:ring-tan-400">'
                    + '<span class="text-sm text-gray-700">' + esc(label) + note + '</span>'
                    + '</label>';
            }).join('');
            return '<div><p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">' + esc(group) + '</p>'
                + '<div class="space-y-0.5 pl-1">' + items + '</div></div>';
        }).join('');
    }

    function collectPerms() {
        return Array.from(document.querySelectorAll('#r-matrix .perm-box:checked')).map(b => b.value);
    }

    // Create / Edit modal
    const roleModal = document.getElementById('role-modal');
    const roleErr   = document.getElementById('role-modal-error');
    const sysNote   = document.getElementById('role-system-note');
    const saveBtn   = document.getElementById('role-save');

    function openCreate() {
        editingId = null;
        document.getElementById('role-modal-title').textContent = 'Add role';
        document.getElementById('r-name').value = '';
        document.getElementById('r-desc').value = '';
        document.getElementById('r-name').disabled = false;
        document.getElementById('r-desc').disabled = false;
        document.getElementById('r-matrix').innerHTML = renderMatrix([], false);
        sysNote.classList.add('hidden');
        saveBtn.classList.remove('hidden');
        roleErr.classList.add('hidden');
        roleModal.classList.remove('hidden');
    }

    function openEdit(id, readOnly) {
        const r = roles.find(x => x.id === id);
        if (!r) return;
        editingId = id;
        const ro = readOnly || r.is_system;
        document.getElementById('role-modal-title').textContent = ro ? 'Role details' : 'Edit role';
        document.getElementById('r-name').value = r.name || '';
        document.getElementById('r-desc').value = r.description || '';
        document.getElementById('r-name').disabled = ro;
        document.getElementById('r-desc').disabled = ro;
        document.getElementById('r-matrix').innerHTML = renderMatrix(r.permissions || [], ro);
        sysNote.classList.toggle('hidden', !r.is_system);
        saveBtn.classList.toggle('hidden', ro);
        roleErr.classList.add('hidden');
        roleModal.classList.remove('hidden');
    }

    async function saveRole() {
        roleErr.classList.add('hidden');
        const name = document.getElementById('r-name').value.trim();
        const desc = document.getElementById('r-desc').value.trim();
        const perms = collectPerms();
        if (!name) { showModalErr(roleErr, 'Role name is required.'); return; }

        const url    = editingId === null ? API_BASE + '/wl/admin/roles' : API_BASE + '/wl/admin/roles/' + editingId;
        const method = editingId === null ? 'POST' : 'PUT';

        saveBtn.disabled = true;
        try {
            const res = await fetch(url, { method, headers: headers(), body: JSON.stringify({ name, description: desc, permissions: perms }) });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.error || 'Could not save role');
            closeModals();
            await loadAll();
        } catch (e) {
            showModalErr(roleErr, e.message);
        } finally {
            saveBtn.disabled = false;
        }
    }

    async function deleteRole(id) {
        const r = roles.find(x => x.id === id);
        if (!r) return;
        if (!window.confirm('Delete the role "' + r.name + '"? This cannot be undone.')) return;
        try {
            const res = await fetch(API_BASE + '/wl/admin/roles/' + id, { method: 'DELETE', headers: headers() });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.error || 'Could not delete role');
            await loadAll();
        } catch (e) {
            window.alert(e.message);
        }
    }

    function showModalErr(box, msg) { box.textContent = msg; box.classList.remove('hidden'); }
    function closeModals() { roleModal.classList.add('hidden'); }

    // Wiring
    document.getElementById('add-role-btn').addEventListener('click', openCreate);
    saveBtn.addEventListener('click', saveRole);
    document.querySelectorAll('#role-modal [data-close-modal]').forEach(el => el.addEventListener('click', closeModals));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModals(); });

    loadAll();
})();
</script>
