<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

// Combined search filters (name/email/business, country, date range).
$fq        = trim((string) ($_GET['q']         ?? ''));
$fCountry  = trim((string) ($_GET['country']   ?? ''));
$fDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$fDateTo   = trim((string) ($_GET['date_to']   ?? ''));
$hasFilters = ($fq !== '' || $fCountry !== '' || $fDateFrom !== '' || $fDateTo !== '');

$qs = http_build_query(array_filter([
    'limit'     => 50,
    'q'         => $fq,
    'country'   => $fCountry,
    'date_from' => $fDateFrom,
    'date_to'   => $fDateTo,
], static fn($v) => $v !== '' && $v !== null));

// Fetch leads list from API
$ch = curl_init(API_BASE . '/wl/admin/leads?' . $qs);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'X-API-Key: '            . API_KEY,
        'Origin: '               . ORIGIN,
        'Authorization: Bearer ' . ($_SESSION['jwt'] ?? ''),
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$res       = json_decode(curl_exec($ch), true);
$apiStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($apiStatus === 401) {
    session_destroy();
    header('Location: /login');
    exit;
}

$leads = $res['data'] ?? [];
$meta  = $res['meta'] ?? ['total' => 0];

// Tenant-wide stat counts come from the API (accurate across ALL customers).
$stats = $meta['stats'] ?? [];
$statTotal     = (int) ($stats['total_customers'] ?? ($meta['total'] ?? count($leads)));
$statNew       = (int) ($stats['new']       ?? 0);
$statQualified = (int) ($stats['qualified'] ?? 0);
$statEnriched  = (int) ($stats['enriched']  ?? 0);
$statVerified  = (int) ($stats['verified']  ?? 0);

$jsApiBase = json_encode(API_BASE);
$jsApiKey  = json_encode(API_KEY);
$jsOrigin  = json_encode(ORIGIN);
$jsJwt     = json_encode($_SESSION['jwt'] ?? '');

$layout    = 'app';
$activeNav = 'leads';
?>
<script type="application/json" id="page-meta">
{
    "title": "Leads - Majestic Marquees Admin",
    "description": "Lead submissions for Majestic Marquees"
}
</script>

<!-- Auth config for JS fetch calls (admin's own session data) -->
<script>
const _apiBase   = <?= $jsApiBase ?>;
const _apiKey    = <?= $jsApiKey ?>;
const _apiOrigin = <?= $jsOrigin ?>;
const _jwt       = <?= $jsJwt ?>;
</script>

<div class="space-y-8">

    <!-- Page heading -->
    <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Customer Information</h2>
            <p class="text-sm text-gray-500 mt-1">All enquiries submitted through website forms.</p>
        </div>
        <button id="sync-btn" onclick="syncColdLeads()"
                class="inline-flex items-center gap-2 text-sm bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
            <span id="sync-icon">&#10227;</span> Sync Cold Leads
        </button>
    </div>

    <!-- Combined search: name/email/business + country + date range (server-side) -->
    <form method="get" action="/customer-info-details" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-5">
                <label class="block text-xs font-medium text-gray-500 mb-1">Name, email or business</label>
                <input type="text" name="q" value="<?= e($fq) ?>" placeholder="e.g. John, john@acme.com, Acme Ltd"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div class="lg:col-span-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Country</label>
                <input type="text" name="country" value="<?= e($fCountry) ?>" placeholder="e.g. United Kingdom"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="date_from" value="<?= e($fDateFrom) ?>"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="date_to" value="<?= e($fDateTo) ?>"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
        </div>
        <div class="flex items-center gap-3 mt-3">
            <button type="submit" class="inline-flex items-center gap-2 text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Search
            </button>
            <?php if ($hasFilters): ?>
            <a href="/customer-info-details" class="text-sm text-gray-500 hover:text-gray-800">Clear</a>
            <span class="text-xs text-gray-400">Showing <?= count($leads) ?> of <?= (int) ($meta['total'] ?? count($leads)) ?> matching</span>
            <?php endif; ?>
        </div>
    </form>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border <?= $statNew > 0 ? 'border-blue-300' : 'border-gray-200' ?> p-5">
            <p class="text-sm <?= $statNew > 0 ? 'text-blue-600 font-medium' : 'text-gray-500' ?>">New</p>
            <p class="mt-1 text-3xl font-bold <?= $statNew > 0 ? 'text-blue-600' : 'text-gray-800' ?>"><?= $statNew ?></p>
            <p class="text-xs text-gray-400 mt-1">unread submissions</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Total Customers</p>
            <p class="mt-1 text-3xl font-bold text-gray-800"><?= $statTotal ?></p>
            <p class="text-xs text-gray-400 mt-1">all time</p>
        </div>
        <div class="bg-white rounded-xl border border-purple-100 p-5">
            <p class="text-sm text-purple-600 font-medium">&#9873; Qualified</p>
            <p id="stat-qualified" class="mt-1 text-3xl font-bold text-purple-600"><?= $statQualified ?></p>
            <p class="text-xs text-gray-400 mt-1">marked by admin</p>
        </div>
        <div class="bg-white rounded-xl border border-green-100 p-5">
            <p class="text-sm text-green-600 font-medium">&#10003; Apollo Enriched</p>
            <p class="mt-1 text-3xl font-bold text-green-600"><?= $statEnriched ?></p>
            <p class="text-xs text-gray-400 mt-1">professional data</p>
        </div>
        <div class="bg-white rounded-xl border border-teal-100 p-5">
            <p class="text-sm text-teal-600 font-medium">&#9745; Verified</p>
            <p id="stat-verified" class="mt-1 text-3xl font-bold text-teal-600"><?= $statVerified ?></p>
            <p class="text-xs text-gray-400 mt-1">marked by admin</p>
        </div>
    </div>

    <!-- Customer cards -->
    <?php if (empty($leads)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-10 text-center text-gray-400">
        <?php if ($hasFilters): ?>
        No customers match your search. <a href="/customer-info-details" class="text-blue-600 hover:underline">Clear filters</a>
        <?php else: ?>
        No leads yet.
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($leads as $row):
            $apollo = $row['apollo'] ?? null;

            // Apollo company (summary already extracts current_company)
            $company  = $apollo['current_company'] ?? null;
            $title    = $apollo['title'] ?? null;
            $location = trim(implode(', ', array_filter([$apollo['city'] ?? null, $apollo['state'] ?? null]))) ?: null;
        ?>
        <div onclick="window.location='/customer-info?CR_id=<?= (int)$row['CR_id'] ?>'"
             class="group bg-white rounded-xl border border-gray-200 p-5 flex flex-col
                    hover:border-blue-300 hover:shadow-md transition-all cursor-pointer">

            <!-- Header: avatar + identity -->
            <div class="flex items-start gap-3">
                <?php if ($apollo && !empty($apollo['photo_url'])): ?>
                <img src="<?= e($apollo['photo_url']) ?>" alt=""
                     class="w-12 h-12 rounded-full object-cover border border-gray-200 shrink-0">
                <?php else: ?>
                <div class="w-12 h-12 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0">
                    <span class="text-lg text-gray-400 font-semibold"><?= e(strtoupper(substr($row['name'] ?? '?', 0, 1))) ?></span>
                </div>
                <?php endif; ?>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <p class="font-semibold text-gray-800 truncate"><?= e($row['name'] ?? '-') ?></p>
                        <?php if (!empty($row['has_unread'])): ?>
                        <span class="text-[10px] font-bold bg-blue-500 text-white px-1.5 py-0.5 rounded-full">New</span>
                        <?php endif; ?>
                        <?php if (!empty($row['is_cold_lead'])): ?>
                        <span class="text-[10px] font-semibold bg-cyan-50 text-cyan-600 border border-cyan-200 px-1.5 py-0.5 rounded-full">&#10052; Cold</span>
                        <?php endif; ?>
                        <?php if (!empty($row['is_warm_lead'])): ?>
                        <span class="text-[10px] font-semibold bg-orange-50 text-orange-600 border border-orange-200 px-1.5 py-0.5 rounded-full">&#9733; Warm</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-blue-600 truncate mt-0.5"><?= e($row['email'] ?? '') ?></p>
                    <?php if (!empty($row['phone'])): ?>
                    <p class="text-xs text-gray-400 mt-0.5"><?= e($row['phone']) ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($apollo): ?>
                <span class="text-[10px] font-medium bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full shrink-0">&#10003; Apollo</span>
                <?php endif; ?>
            </div>

            <!-- Apollo key details -->
            <?php if ($title || $company || $location || ($apollo['seniority'] ?? null)): ?>
            <div class="mt-3 space-y-1">
                <?php if ($title || $company): ?>
                <p class="text-xs text-gray-600 truncate">
                    <?php if ($title): ?><span class="font-medium text-gray-700"><?= e($title) ?></span><?php endif; ?>
                    <?php if ($title && $company): ?> · <?php endif; ?>
                    <?php if ($company): ?><?= e($company) ?><?php endif; ?>
                </p>
                <?php endif; ?>
                <div class="flex items-center gap-2 flex-wrap">
                    <?php if (!empty($apollo['seniority'])): ?>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-600"><?= e($apollo['seniority']) ?></span>
                    <?php endif; ?>
                    <?php if ($location): ?>
                    <span class="text-[11px] text-gray-400">&#128205; <?= e($location) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Status labels (driven by the survey flow; managed on the detail page) -->
            <?php $surveyStatus = $row['survey_status'] ?? null; ?>
            <div class="flex items-center gap-2 flex-wrap mt-3">
                <?php if (!empty($row['is_verified'])): ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 border border-teal-200">&#9745; Verified</span>
                <?php else: ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-gray-50 text-gray-400 border border-gray-200">&#9744; Unverified</span>
                <?php endif; ?>

                <?php if (!empty($row['is_qualified'])): ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200">&#9873; Qualified</span>
                <?php else: ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-gray-50 text-gray-400 border border-gray-200">&#9872; Not qualified</span>
                <?php endif; ?>

                <?php if ($surveyStatus === 'completed'): ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-green-50 text-green-700 border border-green-200">&#9993; Survey completed</span>
                <?php elseif ($surveyStatus === 'pending'): ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600 border border-amber-200">&#9993; Survey sent</span>
                <?php else: ?>
                <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-gray-50 text-gray-400 border border-gray-200">&#9993; No survey</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- slide-over removed -->
<div id="lead-panel" style="display:none"
     class="fixed top-0 right-0 h-full w-full max-w-xl bg-white z-50 shadow-2xl
            transform translate-x-full transition-transform duration-300
            flex flex-col overflow-hidden">

    <!-- Panel header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white shrink-0">
        <h3 id="panel-heading" class="text-base font-semibold text-gray-800">Lead Detail</h3>
        <button onclick="closeLead()"
                class="text-gray-400 hover:text-gray-700 text-2xl leading-none transition-colors"
                aria-label="Close">&times;</button>
    </div>

    <!-- Loading state -->
    <div id="panel-loading" class="flex-1 flex items-center justify-center text-gray-400 hidden">
        <svg class="animate-spin h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        Loading…
    </div>

    <!-- Panel body -->
    <div id="panel-content" class="flex-1 overflow-y-auto px-6 py-6 space-y-6 hidden">

        <!-- ── Form data - matches screenshot layout ────────────── -->
        <div class="space-y-4">
            <!-- Name + email line (exactly as in the screenshot) -->
            <p class="text-sm text-gray-700">
                Name :&nbsp;<strong id="pd-name" class="text-gray-900"></strong>,
                &nbsp;email :&nbsp;<span id="pd-email" class="text-blue-600"></span>
            </p>

            <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Phone</p>
                    <p id="pd-phone" class="text-gray-700 mt-1">-</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Country</p>
                    <p id="pd-country" class="text-gray-700 mt-1">-</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Business Name</p>
                    <p id="pd-biz" class="text-gray-700 mt-1">-</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Website</p>
                    <p id="pd-website" class="text-gray-700 mt-1">-</p>
                </div>
            </div>
        </div>

        <!-- ── Apollo.io data box - matches the bordered box in the screenshot ── -->
        <div id="apollo-box"
             class="border border-gray-200 rounded-lg p-5 min-h-[120px]">
            <!-- populated by renderPanel() -->
        </div>

        <!-- ── Activity counts ──────────────────────────────────── -->
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Orders</p>
                <p id="pd-orders" class="text-2xl font-bold text-gray-800 mt-1">0</p>
            </div>
            <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Appointments</p>
                <p id="pd-appts" class="text-2xl font-bold text-gray-800 mt-1">0</p>
            </div>
        </div>

        <!-- ── Recent activity (notifications - from full detail only) ── -->
        <div id="notif-section" class="hidden">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Recent Activity</p>
            <ul id="notif-list" class="space-y-2 text-sm"></ul>
        </div>

    </div>
</div>

<script>
// Seniority → Tailwind badge colour class
const _senBadge = {
    entry:    'bg-gray-100 text-gray-600',
    mid:      'bg-blue-100 text-blue-700',
    senior:   'bg-green-100 text-green-700',
    manager:  'bg-purple-100 text-purple-700',
    director: 'bg-orange-100 text-orange-700',
    vp:       'bg-red-100 text-red-700',
    c_suite:  'bg-amber-100 text-amber-700',
};

function openLead(event, row) {
    const lrId   = row.dataset.lrId;
    const parsed = JSON.parse(row.dataset.row);

    document.getElementById('lead-overlay').classList.remove('hidden');
    document.getElementById('lead-panel').classList.remove('translate-x-full');
    document.getElementById('panel-content').classList.add('hidden');
    document.getElementById('panel-loading').classList.remove('hidden');

    fetch(`${_apiBase}/wl/admin/lead?CR_id=${encodeURIComponent(lrId)}`, {
        headers: {
            'X-API-Key':     _apiKey,
            'Authorization': 'Bearer ' + _jwt,
        }
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(json => {
        if (json.success) {
            renderPanel(json.data);
        } else {
            console.error('[leads] API error:', json);
            renderPanel(parsed);
        }
    })
    .catch(err => {
        console.error('[leads] fetch failed:', err);
        // Show error state in panel instead of silently falling back
        document.getElementById('panel-loading').classList.add('hidden');
        document.getElementById('panel-content').classList.remove('hidden');
        document.getElementById('apollo-box').innerHTML =
            `<p class="text-xs text-red-500">Could not load full detail: ${err.message}.<br>
             Check browser console. Backend must be running on ${_apiBase}.</p>`;
        document.getElementById('apollo-box').className = 'border border-red-200 rounded-lg p-5 bg-red-50';
        // Still render form fields from the cached row data
        renderFormFields(parsed);
    });
}

function renderFormFields(data) {
    document.getElementById('panel-heading').textContent = data.name || 'Lead Detail';
    document.getElementById('pd-name').textContent       = data.name    || '-';
    document.getElementById('pd-email').textContent      = data.email   || '-';
    document.getElementById('pd-phone').textContent      = data.phone   || '-';
    document.getElementById('pd-country').textContent    = data.country || '-';
    document.getElementById('pd-biz').textContent        = data.legal_business_name || '-';

    const wEl = document.getElementById('pd-website');
    if (data.website_url) {
        wEl.innerHTML = `<a href="${_esc(data.website_url)}" target="_blank" rel="noopener noreferrer"
            class="text-blue-600 hover:underline text-xs">${_esc(data.website_url)}</a>`;
    } else {
        wEl.textContent = '-';
    }

    document.getElementById('pd-orders').textContent = data.order_count       ?? 0;
    document.getElementById('pd-appts').textContent  = data.appointment_count ?? 0;
}

function renderPanel(data) {
    console.log('[leads] renderPanel received:', data);
    console.log('[leads] apollo object:', data.apollo);
    renderFormFields(data);

    // ── Apollo box ────────────────────────────────────────────
    const box    = document.getElementById('apollo-box');
    const apollo = data.apollo;

    if (apollo && typeof apollo === 'object') {
        let currentCompany = apollo.current_company || null;
        if (!currentCompany && Array.isArray(apollo.employment_history)) {
            const current = apollo.employment_history.find(j => j.current);
            if (current) currentCompany = current.organization_name || null;
        }
        const enrichedAt = apollo.enriched_at || data.apollo_enriched_at || null;
        const sCol = _senBadge[apollo.seniority] || 'bg-gray-100 text-gray-600';

        let html = `<p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Apollo.io Data</p>`;

        // ── Profile header (photo + name + headline) ─────────
        html += `<div class="flex items-start gap-3 mb-4">`;
        if (apollo.photo_url) {
            html += `<img src="${_esc(apollo.photo_url)}" alt="" class="w-10 h-10 rounded-full object-cover shrink-0 border border-gray-200">`;
        }
        html += `<div>
            <p class="font-semibold text-gray-800">${_esc(apollo.name || '')}</p>
            ${apollo.headline ? `<p class="text-xs text-gray-400 mt-0.5">${_esc(apollo.headline)}</p>` : ''}
        </div></div>`;

        // ── Section: Professional ─────────────────────────────
        html += _section('Professional');

        if (apollo.title)    html += _row('Title',
            `<span class="font-medium text-gray-800">${_esc(apollo.title)}</span>`);

        if (apollo.seniority) html += _row('Seniority',
            `<span class="text-xs px-2 py-0.5 rounded-full font-medium ${sCol}">${_esc(apollo.seniority)}</span>`);

        if (currentCompany)  html += _row('Company',
            `<span class="text-gray-700">${_esc(currentCompany)}</span>`);

        const depts = (apollo.departments || []).concat(apollo.subdepartments || []);
        if (depts.length)    html += _row('Departments',
            `<span class="text-gray-600">${depts.map(d => _esc(d)).join(', ')}</span>`);

        const funcs = (apollo.functions || []);
        if (funcs.length)    html += _row('Functions',
            `<span class="text-gray-600">${funcs.map(f => _esc(f)).join(', ')}</span>`);

        // ── Section: Location ─────────────────────────────────
        html += _section('Location');

        const loc = apollo.formatted_address
            || [apollo.city, apollo.state, apollo.country].filter(Boolean).join(', ');
        if (loc)             html += _row('Address',
            `<span class="text-gray-700">${_esc(loc)}</span>`);

        if (apollo.time_zone) html += _row('Timezone',
            `<span class="text-gray-600">${_esc(apollo.time_zone)}</span>`);

        // ── Section: Contact & Social ─────────────────────────
        html += _section('Contact & Social');

        if (apollo.email_status) {
            const eCol = apollo.email_status === 'verified'
                ? 'text-green-600 font-medium' : 'text-gray-500';
            html += _row('Email status',
                `<span class="${eCol}">${_esc(apollo.email_status)}</span>`);
        }

        if (Array.isArray(apollo.personal_emails) && apollo.personal_emails.length) {
            html += _row('Personal emails',
                apollo.personal_emails.map(e =>
                    `<span class="block text-gray-600 text-xs">${_esc(e)}</span>`
                ).join(''));
        }

        const socials = [
            { key: 'linkedin_url', label: 'LinkedIn' },
            { key: 'twitter_url',  label: 'Twitter'  },
            { key: 'github_url',   label: 'GitHub'   },
            { key: 'facebook_url', label: 'Facebook' },
        ];
        socials.forEach(({ key, label }) => {
            if (apollo[key]) html += _row(label,
                `<a href="${_esc(apollo[key])}" target="_blank" rel="noopener noreferrer"
                   class="text-blue-600 hover:underline text-xs break-all">${_esc(apollo[key])}</a>`);
        });

        // ── Section: Employment History ───────────────────────
        if (Array.isArray(apollo.employment_history) && apollo.employment_history.length) {
            html += _section('Employment History');
            apollo.employment_history.forEach(job => {
                const badge = job.current
                    ? `<span class="text-xs bg-green-50 text-green-700 border border-green-200 px-1.5 py-0.5 rounded-full ml-1">Current</span>`
                    : '';
                const dates = [job.start_date, job.end_date].filter(Boolean).join(' → ');
                html += `<div class="pl-1 border-l-2 border-gray-100 ml-1 mb-2">
                    <p class="text-sm font-medium text-gray-800">${_esc(job.title || '')}${badge}</p>
                    <p class="text-xs text-gray-500">${_esc(job.organization_name || '')}</p>
                    ${dates ? `<p class="text-xs text-gray-400 mt-0.5">${_esc(dates)}</p>` : ''}
                </div>`;
            });
        }

        if (enrichedAt) html += `
            <div class="pt-3 mt-2 border-t border-gray-100 flex items-center gap-3">
                <span class="text-xs text-gray-400 w-28 shrink-0">Enriched at</span>
                <span class="text-xs text-gray-400">${_esc(enrichedAt)}</span>
            </div>`;

        box.innerHTML  = html;
        box.className  = 'border border-gray-200 rounded-lg p-5';
    } else {
        box.innerHTML  = `
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Apollo.io Data</p>
            <p class="text-sm text-gray-400 italic mt-3">
                No enrichment data yet. Apollo.io is queried automatically when this lead submits a form.
            </p>`;
        box.className  = 'border border-dashed border-gray-200 rounded-lg p-5 bg-gray-50';
    }

    // ── Notifications (only present in full detail response) ───
    const notifs   = data.notifications;
    const nSection = document.getElementById('notif-section');
    const nList    = document.getElementById('notif-list');
    if (notifs && notifs.length > 0) {
        nSection.classList.remove('hidden');
        nList.innerHTML = notifs.map(n => `
            <li class="flex items-start gap-3">
                <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 ${n.is_read ? 'bg-gray-200' : 'bg-blue-500'}"></span>
                <div>
                    <span class="text-gray-700">${_esc(n.message)}</span>
                    <span class="text-xs text-gray-400 ml-2">${_esc((n.created_at || '').substring(0, 10))}</span>
                </div>
            </li>`).join('');
    } else {
        nSection.classList.add('hidden');
    }

    document.getElementById('panel-loading').classList.add('hidden');
    document.getElementById('panel-content').classList.remove('hidden');
}

function closeLead() {
    document.getElementById('lead-overlay').classList.add('hidden');
    document.getElementById('lead-panel').classList.add('translate-x-full');
}

// Build a label : value row for the Apollo box
function _row(label, valueHtml) {
    return `<div class="flex items-start gap-3 text-sm mb-2">
        <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">${label}</span>
        <div class="flex-1 min-w-0">${valueHtml}</div>
    </div>`;
}

// Section heading divider
function _section(title) {
    return `<p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-4 mb-2 pt-3 border-t border-gray-100">${title}</p>`;
}

// HTML-escape for safe JS → DOM output
function _esc(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLead(); });

function syncColdLeads() {
    const btn  = document.getElementById('sync-btn');
    const icon = document.getElementById('sync-icon');
    if (!btn) return;
    btn.disabled  = true;
    btn.classList.add('opacity-60');
    icon.textContent = '⟳';

    fetch(`${_apiBase}/wl/admin/apollo/sync-cold-leads`, {
        method: 'POST',
        headers: {
            'Content-Type':  'application/json',
            'X-API-Key':     _apiKey,
            'Authorization': 'Bearer ' + _jwt,
        },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            alert('Sync complete - ' + json.synced + ' cold lead(s) synced from Apollo.io. Refreshing...');
            location.reload();
        } else {
            alert('Sync failed: ' + (json.error || 'Unknown error'));
        }
    })
    .catch(err => alert('Request failed: ' + err.message))
    .finally(() => {
        btn.disabled = false;
        btn.classList.remove('opacity-60');
        icon.textContent = '↻';
    });
}
</script>
