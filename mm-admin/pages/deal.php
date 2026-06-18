<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$CR_id   = (int) ($_GET['CR_id'] ?? 0);
$wantSid = (int) ($_GET['submission_id'] ?? 0);

if ($CR_id <= 0 || $wantSid <= 0) {
    header('Location: /lead-management');
    exit;
}

// ── Fetch the parent lead (carries every deal as a "notification") ───────────
$ch = curl_init(API_BASE . '/wl/admin/lead?CR_id=' . $CR_id);
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

if ($apiStatus === 401) { session_destroy(); header('Location: /login'); exit; }

$lead = $res['data'] ?? null;
if (!$lead) { header('Location: /lead-management'); exit; }

// Locate the specific deal (submission) on this lead.
$deal = null;
foreach (($lead['notifications'] ?? []) as $n) {
    if ((int) ($n['submission_id'] ?? 0) === $wantSid) { $deal = $n; break; }
}
if (!$deal) { header('Location: /customer-info?CR_id=' . $CR_id); exit; }

// ── Pipeline stage metadata ──────────────────────────────────────────────────
$STAGES      = ['new', 'awaiting_info', 'qualified', 'offer_1', 'offer_2', 'won'];
$STAGE_LABEL = [
    'new'           => 'New',
    'awaiting_info' => 'Awaiting Information',
    'qualified'     => 'Qualified',
    'offer_1'       => '1st Offer',
    'offer_2'       => '2nd Offer',
    'won'           => 'Won',
    'dead'          => 'Dead',
];

$leadName  = $lead['name']  ?? 'Unknown';
$leadEmail = $lead['email'] ?? '';
$leadPhone = $lead['phone'] ?? '';
$initial   = strtoupper(substr($leadName, 0, 1));
$isCold    = ($deal['source'] ?? '') === 'apollo-cold';
$stage     = $deal['stage'] ?: 'new';
$isDead    = $stage === 'dead';
$isWon     = $stage === 'won';
$curIdx    = $isDead ? -1 : (int) array_search($stage, $STAGES, true);
if ($curIdx === false) $curIdx = 0;

// Full lead profile (everything we know about this lead)
$leadCountry  = $lead['country']             ?? '';
$leadBusiness = $lead['legal_business_name'] ?? '';
$leadWebsite  = $lead['website_url']         ?? '';
$leadVerified = !empty($lead['is_verified']);
$leadQualif   = !empty($lead['is_qualified']);
$orderCount   = (int) ($lead['order_count']       ?? 0);
$apptCount    = (int) ($lead['appointment_count'] ?? 0);
$dealMessage  = trim((string) ($deal['message'] ?? ''));
$apollo       = $lead['apollo'] ?? null;

$thread = is_array($deal['thread'] ?? null) ? $deal['thread'] : [];
$offers = is_array($deal['offers'] ?? null) ? $deal['offers'] : [];
$survey = $deal['survey'] ?? null;

$layout    = 'app';
$activeNav = 'lead-management';

// Build prefill query for "New offer"
$offerQ = http_build_query([
    'submission_id' => $wantSid,
    'CR_id'         => $CR_id,
    'name'          => $leadName,
    'email'         => $leadEmail,
]);
?>
<script type="application/json" id="page-meta">
{
    "title": "Deal - <?= htmlspecialchars($leadName, ENT_QUOTES, 'UTF-8') ?>",
    "description": "Manage this deal's pipeline, conversation and offers"
}
</script>

<script>
const _apiBase   = <?= json_encode(API_BASE) ?>;
const _apiKey    = <?= json_encode(API_KEY) ?>;
const _apiOrigin = <?= json_encode(ORIGIN) ?>;
const _jwt       = <?= json_encode($_SESSION['jwt'] ?? '') ?>;
const _submissionId = <?= $wantSid ?>;
</script>

<div class="space-y-6 max-w-5xl">

    <!-- Back -->
    <a href="/lead-management" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        Back to Lead Management
    </a>

    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0">
                    <span class="text-lg text-gray-500 font-semibold"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($leadName, ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if ($isCold): ?>
                        <span class="text-[10px] font-semibold bg-cyan-50 text-cyan-600 border border-cyan-200 px-2 py-0.5 rounded-full">&#10052; Cold</span>
                        <?php else: ?>
                        <span class="text-[10px] font-semibold bg-orange-50 text-orange-600 border border-orange-200 px-2 py-0.5 rounded-full">&#9733; Warm</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-gray-400 mt-0.5"><?= htmlspecialchars(trim($leadEmail . ($leadPhone ? ' · ' . $leadPhone : '')), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-gray-400 mt-0.5">Deal #<?= $wantSid ?> · <?= htmlspecialchars($deal['source'] ?? '', ENT_QUOTES, 'UTF-8') ?> · created <?= htmlspecialchars(substr((string)($deal['created_at'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <a href="/customer-info?CR_id=<?= $CR_id ?>" class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700">View customer</a>
        </div>

        <!-- Progress bar -->
        <div class="mt-6" id="progress-wrap">
            <?php if ($isDead): ?>
            <div class="flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                <span>&#9760;</span><span class="font-medium">Dead deal</span>
                <?php if (!empty($deal['dead_reason'])): ?><span class="text-red-400">— <?= htmlspecialchars($deal['dead_reason'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            </div>
            <?php else: ?>
            <div class="flex items-center">
                <?php foreach ($STAGES as $i => $st):
                    $dot = $i < $curIdx ? 'bg-green-500 border-green-500' : ($i === $curIdx ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-300');
                    $bar = $i < $curIdx ? 'bg-green-500' : 'bg-gray-200';
                ?>
                    <?php if ($i > 0): ?><div class="flex-1 h-0.5 <?= $bar ?>"></div><?php endif; ?>
                    <div class="w-4 h-4 rounded-full border-2 <?= $dot ?> shrink-0"></div>
                <?php endforeach; ?>
            </div>
            <div class="flex items-center justify-between mt-1.5">
                <?php foreach ($STAGES as $i => $st): ?>
                <span class="text-[11px] <?= $i === $curIdx ? 'text-blue-600 font-semibold' : 'text-gray-400' ?> text-center" style="flex:1 1 0"><?= htmlspecialchars($STAGE_LABEL[$st]) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stage controls -->
        <div class="mt-5 pt-4 border-t border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Move stage</p>
            <div class="flex items-center gap-2 flex-wrap">
                <?php
                $first3 = ['new', 'awaiting_info', 'qualified'];
                foreach ($STAGES as $st):
                    $isCurrent = $st === $stage;
                    $auto      = in_array($st, $first3, true);
                ?>
                <button onclick="moveStage('<?= $st ?>')"
                        <?= $isCurrent ? 'disabled' : '' ?>
                        title="<?= $auto ? 'Set automatically from the survey reply' : 'Manual stage' ?>"
                        class="text-xs px-3 py-1.5 rounded-lg border transition <?= $isCurrent ? 'bg-blue-600 text-white border-blue-600 cursor-default' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300 hover:text-blue-600' ?>">
                    <?= htmlspecialchars($STAGE_LABEL[$st]) ?><?= $auto ? ' &#9881;' : '' ?>
                </button>
                <?php endforeach; ?>
                <span class="w-px h-5 bg-gray-200 mx-1"></span>
                <button onclick="markDead()" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-500 bg-white hover:bg-red-50 transition">&#9760; Mark Dead</button>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">&#9881; The first three stages are set automatically when the lead replies to the survey. The rest are manual.</p>
        </div>
    </div>

    <!-- ── Full lead details ──────────────────────────────────────────────── -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">Lead details</h3>
            <div class="flex items-center gap-2">
                <?php if ($leadVerified): ?>
                <span class="text-[11px] font-medium bg-teal-50 text-teal-700 border border-teal-200 px-2 py-0.5 rounded-full">&#9745; Verified</span>
                <?php else: ?>
                <span class="text-[11px] font-medium bg-gray-50 text-gray-400 border border-gray-200 px-2 py-0.5 rounded-full">Unverified</span>
                <?php endif; ?>
                <?php if ($leadQualif): ?>
                <span class="text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded-full">&#9873; Qualified</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact + business grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Name</p>
                <p class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars($leadName, ENT_QUOTES, 'UTF-8') ?: '—' ?></p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Email</p>
                <p class="text-sm mt-0.5">
                    <?php if ($leadEmail): ?>
                    <a href="mailto:<?= htmlspecialchars($leadEmail, ENT_QUOTES, 'UTF-8') ?>" class="text-blue-600 hover:underline break-all"><?= htmlspecialchars($leadEmail, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php else: ?><span class="text-gray-300">—</span><?php endif; ?>
                </p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Phone</p>
                <p class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars($leadPhone, ENT_QUOTES, 'UTF-8') ?: '—' ?></p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Country</p>
                <p class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars($leadCountry, ENT_QUOTES, 'UTF-8') ?: '—' ?></p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Business</p>
                <p class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars($leadBusiness, ENT_QUOTES, 'UTF-8') ?: '—' ?></p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Website</p>
                <p class="text-sm mt-0.5">
                    <?php if ($leadWebsite): ?>
                    <a href="<?= htmlspecialchars($leadWebsite, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline break-all"><?= htmlspecialchars($leadWebsite, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php else: ?><span class="text-gray-300">—</span><?php endif; ?>
                </p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Source</p>
                <p class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars($deal['source'] ?? '', ENT_QUOTES, 'UTF-8') ?: '—' ?></p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Enquiry date</p>
                <p class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars(substr((string)($deal['created_at'] ?? ''), 0, 16), ENT_QUOTES, 'UTF-8') ?: '—' ?></p>
            </div>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Activity</p>
                <p class="text-sm text-gray-700 mt-0.5"><?= $orderCount ?> order<?= $orderCount === 1 ? '' : 's' ?> · <?= $apptCount ?> appointment<?= $apptCount === 1 ? '' : 's' ?></p>
            </div>
        </div>

        <!-- Enquiry message -->
        <?php if ($dealMessage !== ''): ?>
        <div class="mt-5 pt-4 border-t border-gray-100">
            <p class="text-[11px] uppercase tracking-wide text-gray-400 mb-1">Enquiry message</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap bg-gray-50 border border-gray-100 rounded-lg px-3 py-2.5"><?= htmlspecialchars($dealMessage, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php endif; ?>

        <!-- Apollo enrichment (collapsible) -->
        <?php if ($apollo): ?>
        <div class="mt-5 pt-4 border-t border-gray-100">
            <button type="button" class="w-full flex items-center justify-between text-left"
                    onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.apollo-chev').classList.toggle('rotate-180');">
                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">&#10003; Apollo.io enrichment</span>
                <svg class="apollo-chev w-4 h-4 text-gray-300 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="hidden mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                <?php
                $addr = $apollo['formatted_address']
                    ?? implode(', ', array_filter([$apollo['city'] ?? null, $apollo['state'] ?? null, $apollo['country'] ?? null]));
                $apolloFields = [
                    'Title'        => $apollo['title']            ?? null,
                    'Seniority'    => $apollo['seniority']        ?? null,
                    'Company'      => $apollo['current_company']  ?? ($apollo['organization'] ?? null),
                    'Headline'     => $apollo['headline']         ?? null,
                    'Location'     => $addr ?: null,
                    'Time zone'    => $apollo['time_zone']        ?? null,
                    'Email status' => $apollo['email_status']     ?? null,
                    'LinkedIn'     => $apollo['linkedin_url']     ?? null,
                ];
                foreach ($apolloFields as $label => $val):
                    if (empty($val)) continue;
                    $isUrl = is_string($val) && str_starts_with($val, 'http');
                ?>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($isUrl): ?>
                    <a href="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="text-sm text-blue-600 hover:underline break-all"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php else: ?>
                    <p class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Conversation -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-800">Conversation</h3>
            <p class="text-xs text-gray-400 mb-4">This thread belongs only to this deal. Sending emails the lead; logging a reply records what they said back.</p>

            <div id="thread" class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
                <?php if (empty($thread)): ?>
                <p id="thread-empty" class="text-sm text-gray-400 text-center py-6">No messages yet. Start the conversation below.</p>
                <?php else: foreach ($thread as $m):
                    $out = ($m['direction'] ?? 'out') === 'out';
                ?>
                <div class="flex <?= $out ? 'justify-end' : 'justify-start' ?>">
                    <div class="max-w-[80%] rounded-xl px-3.5 py-2.5 <?= $out ? 'bg-blue-50 border border-blue-100' : 'bg-gray-50 border border-gray-100' ?>">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[11px] font-semibold <?= $out ? 'text-blue-600' : 'text-gray-500' ?>"><?= $out ? 'You → Lead' : 'Lead' ?></span>
                            <span class="text-[10px] text-gray-400"><?= htmlspecialchars((string)($m['at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <?php if (!empty($m['subject'])): ?>
                        <p class="text-xs font-medium text-gray-700 mb-0.5"><?= htmlspecialchars($m['subject'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars((string)($m['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Composer -->
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
                <input id="msg-subject" type="text" placeholder="Subject (optional)"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                <textarea id="msg-body" rows="3" placeholder="Write a message…"
                          class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300"></textarea>
                <div class="flex items-center gap-2 flex-wrap">
                    <button onclick="sendMessage('out')" id="btn-send"
                            class="text-sm px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">&#9993; Send email to lead</button>
                    <button onclick="sendMessage('in')" id="btn-log"
                            class="text-sm px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Log a reply from lead</button>
                </div>
            </div>
        </div>

        <!-- Sidebar: offers + survey -->
        <div class="space-y-6">

            <!-- Offers -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800">Offers</h3>
                    <a href="/inventory?<?= htmlspecialchars($offerQ, ENT_QUOTES, 'UTF-8') ?>"
                       class="text-xs px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">+ New offer</a>
                </div>
                <?php if (empty($offers)): ?>
                <p class="text-sm text-gray-400">No offers yet. Create an AI invoice to send the first offer.</p>
                <?php else: ?>
                <?php
                $stColor = ['draft'=>'bg-gray-100 text-gray-500','sent'=>'bg-blue-50 text-blue-600','accepted'=>'bg-green-50 text-green-700','rejected'=>'bg-red-50 text-red-600','expired'=>'bg-amber-50 text-amber-600'];
                foreach ($offers as $o):
                    $cls = $stColor[$o['status']] ?? 'bg-gray-100 text-gray-500';
                ?>
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($o['estimate_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($o['currency'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($o['total'] ?? 0), 2) ?></p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full <?= $cls ?> capitalize"><?= htmlspecialchars($o['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($o['token'])): ?>
                        <a href="/estimate/<?= htmlspecialchars($o['token'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-xs text-blue-600 hover:underline">View</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Survey responses -->
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-800 mb-3">Survey responses</h3>
                <?php if (!$survey || ($survey['status'] ?? '') !== 'completed'): ?>
                <p class="text-sm text-gray-400"><?= $survey && ($survey['status'] ?? '') === 'pending' ? 'Survey sent — awaiting the lead’s reply.' : 'No survey completed for this deal.' ?></p>
                <?php else:
                    $questions = $survey['questions'] ?? [];
                    $answers   = $survey['answers']   ?? [];
                ?>
                <div class="space-y-2.5">
                    <?php if (is_array($questions) && $questions): foreach ($questions as $q):
                        $key = $q['key'] ?? '';
                        $ans = is_array($answers) ? ($answers[$key] ?? '') : '';
                        if (is_array($ans)) $ans = implode(', ', $ans);
                    ?>
                    <div>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($q['label'] ?? $key, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-sm text-gray-700"><?= htmlspecialchars((string)$ans, ENT_QUOTES, 'UTF-8') ?: '<span class="text-gray-300">—</span>' ?></p>
                    </div>
                    <?php endforeach; elseif (is_array($answers)): foreach ($answers as $k => $v):
                        if (is_array($v)) $v = implode(', ', $v);
                    ?>
                    <div>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-sm text-gray-700"><?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function _headers() {
    return { 'Content-Type':'application/json', 'X-API-Key':_apiKey, 'Origin':_apiOrigin, 'Authorization':'Bearer '+_jwt };
}

async function moveStage(stage, reason) {
    try {
        const res = await fetch(_apiBase + '/wl/admin/lead/stage', {
            method: 'POST', headers: _headers(),
            body: JSON.stringify({ submission_id: _submissionId, stage, reason: reason || '' }),
        });
        if (res.status === 401) { window.location = '/login'; return; }
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) { alert('Could not move stage: ' + (data.error || 'Unknown error')); return; }
        location.reload();
    } catch (e) { alert('Network error: ' + e.message); }
}

function markDead() {
    const reason = prompt('Mark this deal as Dead.\nReason (optional):', 'No reply to offer');
    if (reason === null) return;
    moveStage('dead', reason);
}

async function sendMessage(direction) {
    const subject = document.getElementById('msg-subject').value.trim();
    const body    = document.getElementById('msg-body').value.trim();
    if (!body) { alert('Please write a message first.'); return; }

    const btn = document.getElementById(direction === 'out' ? 'btn-send' : 'btn-log');
    const orig = btn.textContent;
    btn.disabled = true; btn.textContent = direction === 'out' ? 'Sending…' : 'Saving…';

    try {
        const res = await fetch(_apiBase + '/wl/admin/lead/message', {
            method: 'POST', headers: _headers(),
            body: JSON.stringify({ submission_id: _submissionId, direction, subject, body }),
        });
        if (res.status === 401) { window.location = '/login'; return; }
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) { alert('Failed: ' + (data.error || 'Unknown error')); return; }
        appendMessage(data.message);
        document.getElementById('msg-subject').value = '';
        document.getElementById('msg-body').value = '';
    } catch (e) {
        alert('Network error: ' + e.message);
    } finally {
        btn.disabled = false; btn.textContent = orig;
    }
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function appendMessage(m) {
    const wrap = document.getElementById('thread');
    const empty = document.getElementById('thread-empty');
    if (empty) empty.remove();
    const out = (m.direction || 'out') === 'out';
    const div = document.createElement('div');
    div.className = 'flex ' + (out ? 'justify-end' : 'justify-start');
    div.innerHTML =
        '<div class="max-w-[80%] rounded-xl px-3.5 py-2.5 ' + (out ? 'bg-blue-50 border border-blue-100' : 'bg-gray-50 border border-gray-100') + '">' +
            '<div class="flex items-center gap-2 mb-1">' +
                '<span class="text-[11px] font-semibold ' + (out ? 'text-blue-600' : 'text-gray-500') + '">' + (out ? 'You → Lead' : 'Lead') + '</span>' +
                '<span class="text-[10px] text-gray-400">' + escHtml(m.at) + '</span>' +
            '</div>' +
            (m.subject ? '<p class="text-xs font-medium text-gray-700 mb-0.5">' + escHtml(m.subject) + '</p>' : '') +
            '<p class="text-sm text-gray-700 whitespace-pre-wrap">' + escHtml(m.body) + '</p>' +
        '</div>';
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;
}
</script>
