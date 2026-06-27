<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

// Combined search filters (name/email, phone, date range) — applied server-side.
$fq        = trim((string) ($_GET['q']         ?? ''));
$fPhone    = trim((string) ($_GET['phone']     ?? ''));
$fDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$fDateTo   = trim((string) ($_GET['date_to']   ?? ''));
$hasFilters = ($fq !== '' || $fPhone !== '' || $fDateFrom !== '' || $fDateTo !== '');

$qs = http_build_query(array_filter([
    'limit'     => 300,
    'q'         => $fq,
    'phone'     => $fPhone,
    'date_from' => $fDateFrom,
    'date_to'   => $fDateTo,
], static fn($v) => $v !== '' && $v !== null));

// ── Fetch the deal board (one deal per lead submission) ──────────────────────
$ch = curl_init(API_BASE . '/wl/admin/deals?' . $qs);
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

$deals  = $res['deals'] ?? [];
$meta   = $res['meta']  ?? [];
$counts = $meta['stage_counts'] ?? [];

// Pipeline stage definitions, in board order. 'awaiting_info' and 'qualified'
// are communication stages between the initial 'new' enquiry and the offer
// stages. "Disqualified" is a classification (like warm/cold), handled
// separately below.
$STAGES      = ['new', 'awaiting_info', 'qualified', 'offer_1', 'offer_2', 'long_term', 'won'];
$STAGE_SHORT = [
    'new'           => 'New',
    'awaiting_info' => 'Awaiting Info',
    'qualified'     => 'Qualified',
    'offer_1'       => '1st Offer',
    'offer_2'       => '2nd Offer',
    'long_term'     => 'Long Term',
    'won'           => 'Won',
];

$total   = count($deals);
// Independent tallies for the three axes. Source (warm/cold) is a permanent
// fact; qualification (qualified/pending/disqualified) is the verdict. Both are
// counted across ALL deals, regardless of pipeline stage.
$warm = 0; $cold = 0; $qualified = 0; $pend = 0; $disq = 0;
foreach ($deals as $d) {
    if (!empty($d['is_cold_lead'])) { $cold++; } else { $warm++; }
    $q = $d['qualification'] ?? 'pending';
    if ($q === 'qualified')        { $qualified++; }
    elseif ($q === 'disqualified') { $disq++; }
    else                           { $pend++; }
}
$wonCnt   = (int) ($counts['won']  ?? 0);
$deadCnt  = (int) ($counts['dead'] ?? 0);
$offerCnt = (int) ($counts['offer_1'] ?? 0) + (int) ($counts['offer_2'] ?? 0);

$jsApiBase = json_encode(API_BASE);
$jsApiKey  = json_encode(API_KEY);
$jsOrigin  = json_encode(ORIGIN);
$jsJwt     = json_encode($_SESSION['jwt'] ?? '');

$layout    = 'app';
$activeNav = 'lead-management';
?>
<script type="application/json" id="page-meta">
{
    "title": "Lead Management - Majestic Marquees Admin",
    "description": "Track every deal through the sales pipeline"
}
</script>

<!-- Auth config for JS fetch calls -->
<script>
const _apiBase   = <?= $jsApiBase ?>;
const _apiKey    = <?= $jsApiKey ?>;
const _apiOrigin = <?= $jsOrigin ?>;
const _jwt       = <?= $jsJwt ?>;
</script>

<div class="space-y-6">

    <!-- Page heading -->
    <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Lead Management</h2>
            <p class="text-sm text-gray-500 mt-1">Each enquiry is its own deal with an isolated conversation and offer history.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative">
                <input id="search-input" type="text" placeholder="Quick filter loaded…"
                       oninput="filterDeals()"
                       class="text-sm border border-gray-200 rounded-lg px-3 py-2 pl-8 w-56 focus:outline-none focus:ring-2 focus:ring-blue-300">
                <svg class="absolute left-2.5 top-2.5 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Combined search: name/email + phone + date range (searches ALL deals server-side) -->
    <div id="adv-search-wrap">
        <button type="button" id="adv-search-toggle" onclick="toggleAdvSearch()"
                aria-expanded="<?= $hasFilters ? 'true' : 'false' ?>" aria-controls="adv-search-panel"
                class="inline-flex items-center gap-2 text-sm bg-white border border-gray-200 rounded-lg px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8z"/></svg>
            Advanced Search
            <?php if ($hasFilters): ?>
            <span class="text-[10px] font-semibold bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">Active</span>
            <?php endif; ?>
            <svg id="adv-search-chevron" class="w-4 h-4 text-gray-400 transition-transform <?= $hasFilters ? 'rotate-180' : '' ?>" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/>
            </svg>
        </button>

        <form method="get" action="/lead-management" id="adv-search-panel"
              class="mt-2 w-full bg-white rounded-xl border border-gray-200 shadow-sm p-4 <?= $hasFilters ? '' : 'hidden' ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <div class="lg:col-span-5">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Name or email</label>
                    <input type="text" name="q" value="<?= e($fq) ?>" placeholder="e.g. John or john@acme.com"
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div class="lg:col-span-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Phone number</label>
                    <input type="tel" name="phone" value="<?= e($fPhone) ?>" placeholder="e.g. 07123 456789"
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
                <a href="/lead-management" class="text-sm text-gray-500 hover:text-gray-800">Clear</a>
                <span class="text-xs text-gray-400">Found <?= count($deals) ?> matching deal<?= count($deals) === 1 ? '' : 's' ?></span>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Stat summary: grouped by the three axes (type | qualification | pipeline) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-9 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4 cursor-pointer hover:border-gray-300 transition-colors" onclick="resetFilters()">
            <p class="text-xs text-gray-500">Total deals</p>
            <p class="mt-1 text-2xl font-bold text-gray-800"><?= $total ?></p>
        </div>
        <div class="bg-white rounded-xl border border-orange-100 p-4 cursor-pointer hover:border-orange-300 transition-colors" onclick="pickType('warm')">
            <p class="text-xs text-orange-600 font-medium">&#9733; Warm</p>
            <p class="mt-1 text-2xl font-bold text-orange-600"><?= $warm ?></p>
        </div>
        <div class="bg-white rounded-xl border border-cyan-100 p-4 cursor-pointer hover:border-cyan-300 transition-colors" onclick="pickType('cold')">
            <p class="text-xs text-cyan-600 font-medium">&#10052; Cold</p>
            <p class="mt-1 text-2xl font-bold text-cyan-600"><?= $cold ?></p>
        </div>
        <div class="bg-white rounded-xl border border-emerald-100 p-4 cursor-pointer hover:border-emerald-300 transition-colors" onclick="pickQual('qualified')">
            <p class="text-xs text-emerald-600 font-medium">&#10003; Qualified</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600"><?= $qualified ?></p>
        </div>
        <div class="bg-white rounded-xl border border-amber-100 p-4 cursor-pointer hover:border-amber-300 transition-colors" onclick="pickQual('pending')">
            <p class="text-xs text-amber-600 font-medium">&#8987; Pending</p>
            <p class="mt-1 text-2xl font-bold text-amber-600"><?= $pend ?></p>
        </div>
        <div class="bg-white rounded-xl border border-purple-100 p-4 cursor-pointer hover:border-purple-300 transition-colors" onclick="pickQual('disqualified')">
            <p class="text-xs text-purple-600 font-medium">&#9888; Disqualified</p>
            <p class="mt-1 text-2xl font-bold text-purple-600"><?= $disq ?></p>
        </div>
        <div class="bg-white rounded-xl border border-blue-100 p-4 cursor-pointer hover:border-blue-300 transition-colors" onclick="pickStage('offer')">
            <p class="text-xs text-blue-600 font-medium">&#128188; In Offer</p>
            <p class="mt-1 text-2xl font-bold text-blue-600"><?= $offerCnt ?></p>
        </div>
        <div class="bg-white rounded-xl border border-green-100 p-4 cursor-pointer hover:border-green-300 transition-colors" onclick="pickStage('won')">
            <p class="text-xs text-green-600 font-medium">&#9989; Won</p>
            <p class="mt-1 text-2xl font-bold text-green-600"><?= $wonCnt ?></p>
        </div>
        <div class="bg-white rounded-xl border border-red-100 p-4 cursor-pointer hover:border-red-300 transition-colors" onclick="pickStage('dead')">
            <p class="text-xs text-red-500 font-medium">&#9760; Dead</p>
            <p class="mt-1 text-2xl font-bold text-red-500"><?= $deadCnt ?></p>
        </div>
    </div>

    <!-- Three independent filter axes (CRM standard) on ONE compact row of
         dropdowns: lead type (source) + qualification (verdict) + pipeline
         stage. A deal must match ALL THREE (plus the text search) to show.
         Qualification defaults to "Active", which HIDES pending/disqualified
         leads still sitting in the New stage (low-priority triage); pick another
         option (or a stat card) to surface them. -->
    <div class="flex items-end gap-3 flex-wrap">
        <label class="flex flex-col gap-1">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Lead type</span>
            <select id="filter-type" onchange="setType(this.value)" class="filter-select text-sm px-3 py-2 rounded-lg border min-w-[140px]">
                <option value="all">All types</option>
                <option value="warm">&#9733; Warm</option>
                <option value="cold">&#10052; Cold</option>
            </select>
        </label>
        <label class="flex flex-col gap-1">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Qualification</span>
            <select id="filter-qual" onchange="setQual(this.value)" class="filter-select text-sm px-3 py-2 rounded-lg border min-w-[210px]">
                <option value="active">Active (hide unqualified)</option>
                <option value="all">All qualifications</option>
                <option value="qualified">&#10003; Qualified</option>
                <option value="pending">&#8987; Pending</option>
                <option value="disqualified">&#9888; Disqualified</option>
            </select>
        </label>
        <label class="flex flex-col gap-1">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Pipeline</span>
            <select id="filter-stage" onchange="setStage(this.value)" class="filter-select text-sm px-3 py-2 rounded-lg border min-w-[150px]">
                <option value="all">All stages</option>
                <?php foreach ($STAGES as $st): ?>
                <option value="<?= $st ?>"><?= htmlspecialchars($STAGE_SHORT[$st]) ?></option>
                <?php endforeach; ?>
                <option value="dead">Dead</option>
            </select>
        </label>
        <span id="row-count" class="ml-auto text-xs text-gray-400 pb-2"></span>
    </div>

    <!-- Deal cards -->
    <?php if (empty($deals)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-10 text-center text-gray-400">
        <?php if ($hasFilters): ?>
        No deals match your search. <a href="/lead-management" class="text-blue-600 hover:underline">Clear filters</a>
        <?php else: ?>
        No deals yet.
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div id="deal-list" class="space-y-3">
        <?php foreach ($deals as $d):
            $sid     = (int) $d['submission_id'];
            $crId    = (int) $d['CR_id'];
            $name    = $d['lead_name'] ?: 'Unknown';
            $email   = $d['lead_email'] ?: '';
            $phone   = $d['lead_phone'] ?: '';
            $country = $d['lead_country'] ?? '';
            $initial = strtoupper(substr($name, 0, 1));
            $isCold  = !empty($d['is_cold_lead']);
            $type    = $isCold ? 'cold' : 'warm';
            $qual    = $d['qualification'] ?? 'pending';
            $isNew   = empty($d['is_read']);
            $stage   = $d['stage'] ?: 'new';
            $isDead  = $stage === 'dead';
            $isWon   = $stage === 'won';
            $offers  = (int) ($d['offer_count'] ?? 0);
            $survey  = $d['survey_status'] ?? null;
            $curIdx  = $isDead ? -1 : array_search($stage, $STAGES, true);
            if ($curIdx === false) $curIdx = 0;

            $searchKey = strtolower($name . ' ' . $email . ' ' . $country);
        ?>
        <div class="deal-card bg-white rounded-xl border <?= $isDead ? 'border-red-200' : ($isWon ? 'border-green-200' : 'border-gray-200') ?> p-4 hover:shadow-sm transition cursor-pointer"
             data-type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
             data-qual="<?= htmlspecialchars($qual, ENT_QUOTES, 'UTF-8') ?>"
             data-stage="<?= htmlspecialchars($stage, ENT_QUOTES, 'UTF-8') ?>"
             data-search="<?= htmlspecialchars($searchKey, ENT_QUOTES, 'UTF-8') ?>"
             onclick="window.location='/deal?CR_id=<?= $crId ?>&submission_id=<?= $sid ?>'">

            <!-- Top row -->
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0">
                        <span class="text-sm text-gray-500 font-semibold"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-gray-800 truncate max-w-[200px]"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($isCold): ?>
                            <span class="text-[10px] font-semibold bg-cyan-50 text-cyan-600 border border-cyan-200 px-2 py-0.5 rounded-full">&#10052; Cold</span>
                            <?php else: ?>
                            <span class="text-[10px] font-semibold bg-orange-50 text-orange-600 border border-orange-200 px-2 py-0.5 rounded-full">&#9733; Warm</span>
                            <?php endif; ?>
                            <?php if ($qual === 'qualified'): ?>
                            <span class="text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded-full">&#10003; Qualified</span>
                            <?php elseif ($qual === 'disqualified'): ?>
                            <span class="text-[10px] font-semibold bg-purple-50 text-purple-600 border border-purple-200 px-2 py-0.5 rounded-full">&#9888; Disqualified</span>
                            <?php else: ?>
                            <span class="text-[10px] font-semibold bg-amber-50 text-amber-600 border border-amber-200 px-2 py-0.5 rounded-full">&#8987; Pending</span>
                            <?php endif; ?>
                            <?php if ($isNew): ?>
                            <span class="text-[9px] font-bold bg-blue-500 text-white px-1.5 py-0.5 rounded-full leading-none">NEW</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-400 truncate max-w-[240px]">
                            <?= htmlspecialchars(trim($email . ($phone ? ' · ' . $phone : '')), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <?php if ($offers > 0): ?>
                    <span class="text-[11px] font-medium bg-indigo-50 text-indigo-600 border border-indigo-200 px-2 py-0.5 rounded-full"><?= $offers ?> offer<?= $offers > 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                    <?php if ($survey === 'completed'): ?>
                    <span class="text-[11px] font-medium bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full">&#9745; Survey</span>
                    <?php elseif ($survey === 'pending'): ?>
                    <span class="text-[11px] font-medium bg-amber-50 text-amber-600 border border-amber-200 px-2 py-0.5 rounded-full">&#9993; Sent</span>
                    <?php endif; ?>
                    <span class="text-xs px-3 py-1.5 rounded-lg bg-tan-500 text-white">Open</span>
                </div>
            </div>

            <!-- Progress bar -->
            <?php if ($isDead): ?>
            <div class="mt-4 flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                <span>&#9760;</span>
                <span class="font-medium">Dead deal</span>
                <?php if (!empty($d['dead_reason'])): ?>
                <span class="text-red-400">— <?= htmlspecialchars($d['dead_reason'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="mt-4">
                <div class="flex items-center">
                    <?php foreach ($STAGES as $i => $st):
                        $done    = $i < $curIdx;
                        $current = $i === $curIdx;
                        $dot = $done ? 'bg-tan-500 border-tan-500' : ($current ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-300');
                        $bar = $i < $curIdx ? 'bg-tan-500' : 'bg-gray-200';
                    ?>
                        <?php if ($i > 0): ?><div class="flex-1 h-0.5 <?= $bar ?>"></div><?php endif; ?>
                        <div class="w-3.5 h-3.5 rounded-full border-2 <?= $dot ?> shrink-0"></div>
                    <?php endforeach; ?>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <?php foreach ($STAGES as $i => $st):
                        $current = $i === $curIdx;
                    ?>
                    <span class="text-[10px] <?= $current ? 'text-blue-600 font-semibold' : 'text-gray-400' ?> text-center" style="flex:1 1 0"><?= htmlspecialchars($STAGE_SHORT[$st]) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
    .filter-select { background:#fff; color:#374151; border-color:#e5e7eb; }
    .filter-select:hover { border-color:#d1d5db; }
    .filter-select:focus { outline:none; border-color:#a57b5b; box-shadow:0 0 0 2px rgba(165,123,91,0.2); }
</style>

<script>
// Three independent filter axes (CRM standard), each a dropdown:
//   - lead type     (curType):  all | warm | cold          (source, permanent)
//   - qualification (curQual):  active | all | qualified | pending | disqualified
//   - pipeline      (curStage): all | <stage> | offer | dead
// A deal shows only when it matches ALL THREE axes (plus the text search).
// Qualification 'active' (the DEFAULT) hides pending/disqualified leads that are
// still in the 'new' stage - low-priority triage the admin chose to ignore. Such
// a lead reappears the moment it qualifies or is moved out of 'new', and can
// always be surfaced by choosing All / Pending / Disqualified.
let curType  = 'all';
let curQual  = 'active';
let curStage = 'all';

// Reflect the current axis state back onto the dropdown controls.
function syncFilterControls() {
    const ft = document.getElementById('filter-type');
    const fq = document.getElementById('filter-qual');
    const fs = document.getElementById('filter-stage');
    if (ft) ft.value = curType;
    if (fq) fq.value = curQual;
    if (fs) fs.value = curStage;
}

function setType(t)  { curType  = t; syncFilterControls(); filterDeals(); }
function setQual(q)  { curQual  = q; syncFilterControls(); filterDeals(); }
function setStage(s) { curStage = s; syncFilterControls(); filterDeals(); }

// Stat-card shortcuts: focus one axis and widen the others so the card shows
// exactly what its number counts. Qualification opens to 'all' (not 'active')
// so e.g. the Warm card reveals every warm lead, including pending ones.
function pickType(t)  { curQual = 'all'; curStage = 'all'; curType  = t; syncFilterControls(); filterDeals(); }
function pickQual(q)  { curType = 'all'; curStage = 'all'; curQual  = q; syncFilterControls(); filterDeals(); }
function pickStage(s) { curType = 'all'; curQual = 'all'; curStage = s; syncFilterControls(); filterDeals(); }
function resetFilters() { curType = 'all'; curQual = 'active'; curStage = 'all'; syncFilterControls(); filterDeals(); }

function filterDeals() {
    const search = (document.getElementById('search-input')?.value ?? '').toLowerCase().trim();
    const cards  = document.querySelectorAll('.deal-card');
    let visible  = 0;
    let hidden   = 0;

    cards.forEach(card => {
        const type  = card.dataset.type  ?? '';
        const qual  = card.dataset.qual  ?? '';
        const stage = card.dataset.stage ?? '';
        const srch  = card.dataset.search ?? '';

        const matchType = curType === 'all' || type === curType;

        // 'active' = hide pending/disqualified leads that are still in 'new'.
        const unqualifiedInNew = (qual === 'pending' || qual === 'disqualified') && stage === 'new';
        let matchQual;
        if (curQual === 'all')         matchQual = true;
        else if (curQual === 'active') matchQual = !unqualifiedInNew;
        else                           matchQual = (qual === curQual);

        let matchStage;
        if (curStage === 'all')        matchStage = true;
        else if (curStage === 'offer') matchStage = (stage === 'offer_1' || stage === 'offer_2');
        else                           matchStage = (stage === curStage);

        const matchS = !search || srch.includes(search);

        if (matchType && matchQual && matchStage && matchS) { card.style.display = ''; visible++; }
        else {
            card.style.display = 'none';
            // Count leads suppressed ONLY by the default 'active' lens, so we can
            // hint the admin that low-priority leads are tucked away.
            if (curQual === 'active' && unqualifiedInNew && matchType && matchStage && matchS) { hidden++; }
        }
    });

    const countEl = document.getElementById('row-count');
    if (countEl) {
        let txt = visible + ' deal' + (visible !== 1 ? 's' : '');
        if (hidden > 0) txt += ' \u00b7 ' + hidden + ' unqualified hidden';
        countEl.textContent = txt;
    }
}

document.addEventListener('DOMContentLoaded', () => { syncFilterControls(); filterDeals(); });

// ── Advanced-search dropdown ────────────────────────────────────
function toggleAdvSearch() {
    const panel = document.getElementById('adv-search-panel');
    const chev  = document.getElementById('adv-search-chevron');
    const btn   = document.getElementById('adv-search-toggle');
    if (!panel) return;
    panel.classList.toggle('hidden');
    const isOpen = !panel.classList.contains('hidden');
    if (chev) chev.classList.toggle('rotate-180', isOpen);
    if (btn)  btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}
</script>

