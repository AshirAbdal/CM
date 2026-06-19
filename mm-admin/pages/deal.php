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
$STAGES      = ['new', 'awaiting_info', 'qualified', 'offer_1', 'offer_2', 'long_term', 'won'];
$STAGE_LABEL = [
    'new'           => 'New',
    'awaiting_info' => 'Awaiting Information',
    'qualified'     => 'Qualified',
    'offer_1'       => '1st Offer',
    'offer_2'       => '2nd Offer',
    'long_term'     => 'Long Term',
    'won'           => 'Won',
    'dead'          => 'Dead',
];

// Which tools each stage exposes (conversation / survey / offers).
$STAGE_TOOLS = [
    'new'           => ['conversation'],
    'awaiting_info' => ['conversation', 'survey'],
    'qualified'     => ['conversation', 'survey'],
    'offer_1'       => ['conversation'],
    'offer_2'       => ['conversation'],
    'long_term'     => ['conversation'],
    'won'           => ['conversation'],
    'dead'          => ['conversation'],
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
    </div>

    <!-- ── Full lead details ──────────────────────────────────────────────── -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <button type="button" id="lead-details-toggle"
                onclick="document.getElementById('lead-details-body').classList.toggle('hidden'); this.querySelector('.ld-chev').classList.toggle('rotate-180');"
                class="w-full flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <h3 class="font-semibold text-gray-800">Lead details</h3>
                <?php if ($leadVerified): ?>
                <span class="text-[11px] font-medium bg-teal-50 text-teal-700 border border-teal-200 px-2 py-0.5 rounded-full">&#9745; Verified</span>
                <?php else: ?>
                <span class="text-[11px] font-medium bg-gray-50 text-gray-400 border border-gray-200 px-2 py-0.5 rounded-full">Unverified</span>
                <?php endif; ?>
                <?php if ($leadQualif): ?>
                <span class="text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded-full">&#9873; Qualified</span>
                <?php endif; ?>
            </div>
            <svg class="ld-chev w-5 h-5 text-gray-300 transition-transform shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div id="lead-details-body" class="hidden mt-4">
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
        </div><!-- /lead-details-body -->
    </div>

    <!-- ── Stage workspace: pick a stage to reveal its tools ───────────────── -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between gap-3 flex-wrap mb-4">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pipeline stage</p>
                <p class="text-[11px] text-gray-400 mt-0.5">Click a segment to preview its tools. &#9881; = set automatically when the lead replies to the survey. Sending an offer advances the stage on its own.</p>
            </div>
            <button onclick="markDead()" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-500 bg-white hover:bg-red-50 transition">&#9760; Mark Dead</button>
        </div>

        <?php if ($isDead): ?>
        <div class="flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2 mb-3">
            <span>&#9760;</span><span class="font-medium">Dead deal</span>
            <?php if (!empty($deal['dead_reason'])): ?><span class="text-red-400">&mdash; <?= htmlspecialchars($deal['dead_reason'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Stage bar (progress + selector in one) -->
        <div class="flex w-full select-none">
            <?php
            $first3 = ['new', 'awaiting_info', 'qualified'];
            foreach ($STAGES as $i => $st):
                $auto = in_array($st, $first3, true);
                if ($i < $curIdx)        { $seg = 'bg-green-500 text-white border-green-500'; }
                elseif ($i === $curIdx)  { $seg = 'bg-blue-600 text-white border-blue-600'; }
                else                     { $seg = 'bg-white text-gray-500 border-gray-200'; }
            ?>
            <button type="button" data-stageseg="<?= $st ?>" onclick="selectStage('<?= $st ?>')"
                    title="<?= htmlspecialchars($STAGE_LABEL[$st]) ?><?= $st === $stage ? ' (current stage)' : '' ?>"
                    class="stage-seg relative flex-1 min-w-0 px-2 py-2.5 text-[11px] font-semibold text-center border first:rounded-l-lg last:rounded-r-lg -ml-px first:ml-0 transition hover:brightness-95 <?= $seg ?>">
                <span class="block truncate"><?= htmlspecialchars($STAGE_LABEL[$st]) ?><?= $auto ? ' &#9881;' : '' ?></span>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Selected-stage action bar -->
        <div class="mt-4 flex items-center justify-between gap-3 flex-wrap bg-gray-50 border border-gray-100 rounded-lg px-4 py-2.5">
            <p class="text-sm text-gray-600">
                Viewing <span id="sel-stage-label" class="font-semibold text-gray-800">—</span>
                <span id="sel-stage-current" class="text-[11px] text-green-600 ml-1 hidden">· current stage</span>
            </p>
            <button id="sel-stage-move" onclick="moveSelected()" class="hidden text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">Move deal to this stage</button>
        </div>

        <!-- ── Tools (shown according to the selected stage) ── -->
        <div class="mt-5 space-y-5">

            <!-- Conversation -->
            <div data-tool="conversation" class="deal-tool border border-gray-100 rounded-lg p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-800">&#9993; Conversation</h3>
                        <p class="text-xs text-gray-400 mb-4">Everything for this deal &mdash; emails you send, offers, and the lead&rsquo;s replies pulled from your mailbox.</p>
                    </div>
                    <button onclick="syncReplies()" id="btn-sync" class="shrink-0 text-xs px-3 py-1.5 rounded-lg border border-blue-200 text-blue-600 bg-white hover:bg-blue-50 transition">&#8635; Sync replies</button>
                </div>

                <div id="thread" class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
                    <?php if (empty($thread)): ?>
                    <p id="thread-empty" class="text-sm text-gray-400 text-center py-6">No messages yet. Start the conversation below.</p>
                    <?php else: foreach ($thread as $m):
                        $out     = ($m['direction'] ?? 'out') === 'out';
                        $isOffer = ($m['type'] ?? '') === 'offer';
                    ?>
                    <div class="flex <?= $out ? 'justify-end' : 'justify-start' ?>">
                        <div class="max-w-[80%] rounded-xl px-3.5 py-2.5 <?= $isOffer ? 'bg-indigo-50 border border-indigo-100' : ($out ? 'bg-blue-50 border border-blue-100' : 'bg-gray-50 border border-gray-100') ?>">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[11px] font-semibold <?= $isOffer ? 'text-indigo-600' : ($out ? 'text-blue-600' : 'text-gray-500') ?>"><?= $isOffer ? '&#128221; Offer &rarr; Lead' : ($out ? 'You &rarr; Lead' : 'Lead') ?></span>
                                <span class="text-[10px] text-gray-400"><?= htmlspecialchars((string)($m['at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php if (!empty($m['subject'])): ?>
                            <p class="text-xs font-medium text-gray-700 mb-0.5"><?= htmlspecialchars($m['subject'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars((string)($m['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($isOffer && !empty($m['token'])): ?>
                            <a href="/estimate/<?= htmlspecialchars($m['token'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="inline-block mt-1.5 text-xs font-medium text-indigo-600 hover:underline">View estimate &rarr;</a>
                            <?php endif; ?>
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
                        <a href="/make-offer?<?= htmlspecialchars($offerQ, ENT_QUOTES, 'UTF-8') ?>"
                           class="text-sm px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">&#128221; Make offer</a>
                        <button onclick="sendMessage('in')" id="btn-log"
                                class="text-sm px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Log a reply manually</button>
                    </div>
                </div>
            </div>

            <!-- Survey responses -->
            <div data-tool="survey" class="deal-tool border border-gray-100 rounded-lg p-4">
                <button type="button"
                        onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.survey-chev').classList.toggle('rotate-180');"
                        class="w-full flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-gray-800">&#9745; Survey responses</h3>
                    <svg class="survey-chev w-5 h-5 text-gray-300 transition-transform shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="hidden mt-3">
                <?php if (!$survey || ($survey['status'] ?? '') !== 'completed'): ?>
                <p class="text-sm text-gray-400"><?= $survey && ($survey['status'] ?? '') === 'pending' ? 'Survey sent — awaiting the lead’s reply.' : 'No survey completed for this deal.' ?></p>
                <?php else:
                    $questions = $survey['questions'] ?? [];
                    $answers   = $survey['answers']   ?? [];
                ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                    <?php if (is_array($questions) && $questions): foreach ($questions as $q):
                        $key = $q['key'] ?? '';
                        $ans = is_array($answers) ? ($answers[$key] ?? '') : '';
                        if (is_array($ans)) $ans = implode(', ', $ans);
                    ?>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-gray-400"><?= htmlspecialchars($q['label'] ?? $key, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars((string)$ans, ENT_QUOTES, 'UTF-8') ?: '<span class="text-gray-300">—</span>' ?></p>
                    </div>
                    <?php endforeach; elseif (is_array($answers)): foreach ($answers as $k => $v):
                        if (is_array($v)) $v = implode(', ', $v);
                    ?>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-gray-400"><?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-sm text-gray-700 mt-0.5"><?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function _headers() {
    return { 'Content-Type':'application/json', 'X-API-Key':_apiKey, 'Origin':_apiOrigin, 'Authorization':'Bearer '+_jwt };
}

const _stageTools   = <?= json_encode($STAGE_TOOLS, JSON_UNESCAPED_SLASHES) ?>;
const _stageLabels  = <?= json_encode($STAGE_LABEL, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const _currentStage = <?= json_encode($stage) ?>;
let _selectedStage  = _currentStage;

function selectStage(stage) {
    _selectedStage = stage;

    // Mark the selected segment without disturbing its progress colour.
    document.querySelectorAll('.stage-seg').forEach(function (b) {
        const active = b.dataset.stageseg === stage;
        b.classList.toggle('ring-2', active);
        b.classList.toggle('ring-inset', active);
        b.classList.toggle('ring-blue-800', active);
        b.classList.toggle('z-10', active);
    });

    // Reveal only the tools that belong to this stage
    const tools = _stageTools[stage] || ['conversation'];
    document.querySelectorAll('.deal-tool').forEach(function (t) {
        t.classList.toggle('hidden', tools.indexOf(t.dataset.tool) === -1);
    });

    // Action bar
    document.getElementById('sel-stage-label').textContent = _stageLabels[stage] || stage;
    const isCurrent = stage === _currentStage;
    document.getElementById('sel-stage-current').classList.toggle('hidden', !isCurrent);
    const moveBtn = document.getElementById('sel-stage-move');
    if (isCurrent || stage === 'dead') {
        moveBtn.classList.add('hidden');
    } else {
        moveBtn.classList.remove('hidden');
        moveBtn.textContent = 'Move deal to ' + (_stageLabels[stage] || stage);
    }
}

function moveSelected() { moveStage(_selectedStage); }

// Auto-pull customer replies: once on load, then quietly every 60s while the
// deal page is open (paused when the tab is hidden). The ↻ button still allows
// a manual refresh with feedback.
let _syncing = false;
let _syncTimer = null;

function startReplyAutoSync() {
    if (_syncTimer) return;
    _syncTimer = setInterval(function () {
        if (!document.hidden) { syncReplies(true); }
    }, 60000);
}

document.addEventListener('DOMContentLoaded', function () {
    selectStage(_currentStage);
    syncReplies(true);        // initial silent pull
    startReplyAutoSync();     // keep it fresh
});

document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { syncReplies(true); } // catch up when tab refocuses
});

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

async function syncReplies(silent) {
    if (_syncing) return;
    _syncing = true;
    const btn = document.getElementById('btn-sync');
    const orig = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = '\u27F3 Syncing\u2026'; }
    try {
        const res = await fetch(_apiBase + '/wl/admin/lead/sync-replies', {
            method: 'POST', headers: _headers(),
            body: JSON.stringify({ submission_id: _submissionId }),
        });
        if (res.status === 401) { if (!silent) window.location = '/login'; return; }
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            if (!silent) alert('Sync failed: ' + (data.error || 'Unknown error'));
            return;
        }
        if (Array.isArray(data.thread)) { renderThread(data.thread); }
        if (!silent) {
            alert(data.added > 0
                ? '\u2713 ' + data.added + ' new repl' + (data.added === 1 ? 'y' : 'ies') + ' added to the conversation.'
                : 'No new replies found in the mailbox.');
        }
    } catch (e) {
        if (!silent) alert('Network error: ' + e.message);
    } finally {
        _syncing = false;
        if (btn) { btn.disabled = false; btn.textContent = orig; }
    }
}

function bubbleHtml(m) {
    const out     = (m.direction || 'out') === 'out';
    const isOffer = (m.type || '') === 'offer';
    const wrapCls = 'flex ' + (out ? 'justify-end' : 'justify-start');
    const boxCls  = isOffer ? 'bg-indigo-50 border border-indigo-100'
                  : (out ? 'bg-blue-50 border border-blue-100' : 'bg-gray-50 border border-gray-100');
    const nameCls = isOffer ? 'text-indigo-600' : (out ? 'text-blue-600' : 'text-gray-500');
    const name    = isOffer ? '\uD83D\uDCDD Offer \u2192 Lead' : (out ? 'You \u2192 Lead' : 'Lead');
    const offerLink = (isOffer && m.token)
        ? '<a href="/estimate/' + escHtml(m.token) + '" target="_blank" class="inline-block mt-1.5 text-xs font-medium text-indigo-600 hover:underline">View estimate \u2192</a>'
        : '';
    return '<div class="' + wrapCls + '">' +
        '<div class="max-w-[80%] rounded-xl px-3.5 py-2.5 ' + boxCls + '">' +
            '<div class="flex items-center gap-2 mb-1">' +
                '<span class="text-[11px] font-semibold ' + nameCls + '">' + name + '</span>' +
                '<span class="text-[10px] text-gray-400">' + escHtml(m.at) + '</span>' +
            '</div>' +
            (m.subject ? '<p class="text-xs font-medium text-gray-700 mb-0.5">' + escHtml(m.subject) + '</p>' : '') +
            '<p class="text-sm text-gray-700 whitespace-pre-wrap">' + escHtml(m.body) + '</p>' +
            offerLink +
        '</div>' +
    '</div>';
}

function appendMessage(m) {
    const wrap = document.getElementById('thread');
    const empty = document.getElementById('thread-empty');
    if (empty) empty.remove();
    wrap.insertAdjacentHTML('beforeend', bubbleHtml(m));
    wrap.scrollTop = wrap.scrollHeight;
}

function renderThread(thread) {
    const wrap = document.getElementById('thread');
    if (!Array.isArray(thread) || !thread.length) {
        wrap.innerHTML = '<p id="thread-empty" class="text-sm text-gray-400 text-center py-6">No messages yet. Start the conversation below.</p>';
        return;
    }
    wrap.innerHTML = thread.map(bubbleHtml).join('');
    wrap.scrollTop = wrap.scrollHeight;
}
</script>
