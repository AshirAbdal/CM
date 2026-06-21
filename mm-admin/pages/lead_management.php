<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

// Combined search filters (name/email, country, date range) — applied server-side.
$fq        = trim((string) ($_GET['q']         ?? ''));
$fCountry  = trim((string) ($_GET['country']   ?? ''));
$fDateFrom = trim((string) ($_GET['date_from'] ?? ''));
$fDateTo   = trim((string) ($_GET['date_to']   ?? ''));
$hasFilters = ($fq !== '' || $fCountry !== '' || $fDateFrom !== '' || $fDateTo !== '');

$qs = http_build_query(array_filter([
    'limit'     => 300,
    'q'         => $fq,
    'country'   => $fCountry,
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
// Classification tallies. A warm lead whose survey came back below the bar is
// surfaced as "Disqualified"; one whose answers haven't been scored yet is
// "Pending". Both are split out of the warm count.
$warm    = 0; $cold = 0; $disq = 0; $pend = 0;
foreach ($deals as $d) {
    if (!empty($d['is_cold_lead']))                 { $cold++; }
    elseif (!empty($d['is_disqualified']))          { $disq++; }
    elseif (!empty($d['is_qualification_pending'])) { $pend++; }
    else                                            { $warm++; }
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

    <!-- Combined search: name/email + country + date range (searches ALL deals server-side) -->
    <form method="get" action="/lead-management" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-5">
                <label class="block text-xs font-medium text-gray-500 mb-1">Name or email</label>
                <input type="text" name="q" value="<?= e($fq) ?>" placeholder="e.g. John or john@acme.com"
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
            <a href="/lead-management" class="text-sm text-gray-500 hover:text-gray-800">Clear</a>
            <span class="text-xs text-gray-400">Found <?= count($deals) ?> matching deal<?= count($deals) === 1 ? '' : 's' ?></span>
            <?php endif; ?>
        </div>
    </form>

    <!-- Stat summary -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-8 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4 cursor-pointer hover:border-gray-300 transition-colors" onclick="setFilter('all')">
            <p class="text-xs text-gray-500">Total deals</p>
            <p class="mt-1 text-2xl font-bold text-gray-800"><?= $total ?></p>
        </div>
        <div class="bg-white rounded-xl border border-orange-100 p-4 cursor-pointer hover:border-orange-300 transition-colors" onclick="setFilter('warm')">
            <p class="text-xs text-orange-600 font-medium">&#9733; Warm</p>
            <p class="mt-1 text-2xl font-bold text-orange-600"><?= $warm ?></p>
        </div>
        <div class="bg-white rounded-xl border border-cyan-100 p-4 cursor-pointer hover:border-cyan-300 transition-colors" onclick="setFilter('cold')">
            <p class="text-xs text-cyan-600 font-medium">&#10052; Cold</p>
            <p class="mt-1 text-2xl font-bold text-cyan-600"><?= $cold ?></p>
        </div>
        <div class="bg-white rounded-xl border border-purple-100 p-4 cursor-pointer hover:border-purple-300 transition-colors" onclick="setFilter('disqualified')">
            <p class="text-xs text-purple-600 font-medium">&#9888; Disqualified</p>
            <p class="mt-1 text-2xl font-bold text-purple-600"><?= $disq ?></p>
        </div>
        <div class="bg-white rounded-xl border border-amber-100 p-4 cursor-pointer hover:border-amber-300 transition-colors" onclick="setFilter('pending')">
            <p class="text-xs text-amber-600 font-medium">&#8987; Pending</p>
            <p class="mt-1 text-2xl font-bold text-amber-600"><?= $pend ?></p>
        </div>
        <div class="bg-white rounded-xl border border-blue-100 p-4 cursor-pointer hover:border-blue-300 transition-colors" onclick="setFilter('offer')">
            <p class="text-xs text-blue-600 font-medium">&#128188; In Offer</p>
            <p class="mt-1 text-2xl font-bold text-blue-600"><?= $offerCnt ?></p>
        </div>
        <div class="bg-white rounded-xl border border-green-100 p-4 cursor-pointer hover:border-green-300 transition-colors" onclick="setFilter('won')">
            <p class="text-xs text-green-600 font-medium">&#9989; Won</p>
            <p class="mt-1 text-2xl font-bold text-green-600"><?= $wonCnt ?></p>
        </div>
        <div class="bg-white rounded-xl border border-red-100 p-4 cursor-pointer hover:border-red-300 transition-colors" onclick="setFilter('dead')">
            <p class="text-xs text-red-500 font-medium">&#9760; Dead</p>
            <p class="mt-1 text-2xl font-bold text-red-500"><?= $deadCnt ?></p>
        </div>
    </div>

    <!-- Filter tabs -->
    <div class="flex items-center gap-2 flex-wrap">
        <button id="tab-all"   onclick="setFilter('all')"   class="tab-btn active-tab text-sm px-4 py-1.5 rounded-full border transition-colors">All</button>
        <button id="tab-warm"  onclick="setFilter('warm')"  class="tab-btn text-sm px-4 py-1.5 rounded-full border transition-colors">&#9733; Warm</button>
        <button id="tab-cold"  onclick="setFilter('cold')"  class="tab-btn text-sm px-4 py-1.5 rounded-full border transition-colors">&#10052; Cold</button>
        <button id="tab-disqualified" onclick="setFilter('disqualified')" class="tab-btn text-sm px-4 py-1.5 rounded-full border transition-colors">&#9888; Disqualified</button>
        <button id="tab-pending" onclick="setFilter('pending')" class="tab-btn text-sm px-4 py-1.5 rounded-full border transition-colors">&#8987; Pending</button>
        <span class="w-px h-5 bg-gray-200 mx-1"></span>
        <?php foreach ($STAGES as $st): ?>
        <button id="tab-<?= $st ?>" onclick="setFilter('<?= $st ?>')" class="tab-btn text-sm px-3 py-1.5 rounded-full border transition-colors"><?= htmlspecialchars($STAGE_SHORT[$st]) ?></button>
        <?php endforeach; ?>
        <button id="tab-dead" onclick="setFilter('dead')" class="tab-btn text-sm px-3 py-1.5 rounded-full border transition-colors">Dead</button>
        <span id="row-count" class="ml-auto text-xs text-gray-400"></span>
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
            $isDisq  = !empty($d['is_disqualified']);
            $isPend  = !empty($d['is_qualification_pending']);
            $isNew   = empty($d['is_read']);
            $stage   = $d['stage'] ?: 'new';
            $isDead  = $stage === 'dead';
            $isWon   = $stage === 'won';
            $offers  = (int) ($d['offer_count'] ?? 0);
            $survey  = $d['survey_status'] ?? null;
            $curIdx  = $isDead ? -1 : array_search($stage, $STAGES, true);
            if ($curIdx === false) $curIdx = 0;

            // Classification tag drives both the chip and the filter tabs.
            $classif    = $isCold ? 'cold' : ($isDisq ? 'disqualified' : ($isPend ? 'pending' : 'warm'));
            $filterAttr = implode(' ', ['all', $classif, $stage]);
            $searchKey  = strtolower($name . ' ' . $email . ' ' . $country);
        ?>
        <div class="deal-card bg-white rounded-xl border <?= $isDead ? 'border-red-200' : ($isWon ? 'border-green-200' : 'border-gray-200') ?> p-4 hover:shadow-sm transition cursor-pointer"
             data-filter="<?= htmlspecialchars($filterAttr, ENT_QUOTES, 'UTF-8') ?>"
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
                            <?php elseif ($isDisq): ?>
                            <span class="text-[10px] font-semibold bg-purple-50 text-purple-600 border border-purple-200 px-2 py-0.5 rounded-full">&#9888; Disqualified</span>
                            <?php elseif ($isPend): ?>
                            <span class="text-[10px] font-semibold bg-amber-50 text-amber-600 border border-amber-200 px-2 py-0.5 rounded-full">&#8987; Pending</span>
                            <?php else: ?>
                            <span class="text-[10px] font-semibold bg-orange-50 text-orange-600 border border-orange-200 px-2 py-0.5 rounded-full">&#9733; Warm</span>
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
                    <span class="text-xs px-3 py-1.5 rounded-lg bg-gray-900 text-white">Open</span>
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
                        $dot = $done ? 'bg-green-500 border-green-500' : ($current ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-300');
                        $bar = $i < $curIdx ? 'bg-green-500' : 'bg-gray-200';
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
    .tab-btn { background:#fff; color:#6b7280; border-color:#e5e7eb; }
    .tab-btn:hover { background:#f9fafb; color:#374151; border-color:#d1d5db; }
    .active-tab { background:#1f2937 !important; color:#fff !important; border-color:#1f2937 !important; }
</style>

<script>
let currentFilter = 'all';

function setFilter(filter) {
    currentFilter = filter;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active-tab'));
    const btn = document.getElementById('tab-' + filter);
    if (btn) btn.classList.add('active-tab');
    filterDeals();
}

function filterDeals() {
    const search = (document.getElementById('search-input')?.value ?? '').toLowerCase().trim();
    const cards  = document.querySelectorAll('.deal-card');
    let visible  = 0;

    cards.forEach(card => {
        const tags  = (card.dataset.filter ?? '').split(' ');
        const stage = card.dataset.stage ?? '';
        const srch  = card.dataset.search ?? '';

        let matchF;
        if (currentFilter === 'all')        matchF = true;
        else if (currentFilter === 'offer') matchF = (stage === 'offer_1' || stage === 'offer_2');
        else                                matchF = tags.includes(currentFilter);

        const matchS = !search || srch.includes(search);
        if (matchF && matchS) { card.style.display = ''; visible++; }
        else                  { card.style.display = 'none'; }
    });

    const countEl = document.getElementById('row-count');
    if (countEl) countEl.textContent = visible + ' deal' + (visible !== 1 ? 's' : '');
}

document.addEventListener('DOMContentLoaded', () => filterDeals());
</script>

