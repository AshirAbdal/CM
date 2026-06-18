<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

// Validate CR_id from URL
$CR_id = (int) ($_GET['CR_id'] ?? 0);
if ($CR_id <= 0) {
    header('Location: /customer-info-details');
    exit;
}

// Fetch full customer detail from API
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

if ($apiStatus === 401) {
    session_destroy();
    header('Location: /customer-info-details');
    exit;
}
if ($apiStatus === 404 || empty($res['success'])) {
    header('Location: /customer-info-details');
    exit;
}

$lead   = $res['data'];
$apollo = $lead['apollo'] ?? null;

// Extract current company from employment_history if not in summary
$currentCompany = null;
if ($apollo && !empty($apollo['employment_history'])) {
    foreach ($apollo['employment_history'] as $job) {
        if (!empty($job['current'])) {
            $currentCompany = $job['organization_name'] ?? null;
            break;
        }
    }
}

$enrichedAt = $lead['apollo_enriched_at'] ?? null;

$layout    = 'app';
$activeNav = 'leads';
?>
<script type="application/json" id="page-meta">
{
    "title": "<?= e($lead['name'] ?? 'Lead') ?> - Majestic Marquees Admin",
    "description": "Lead detail for <?= e($lead['email'] ?? '') ?>"
}
</script>

<div class="space-y-8 max-w-5xl">

    <!-- Breadcrumb + back -->
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="/customer-info-details" class="hover:text-gray-800 transition-colors">&larr; Back to Customers</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700 font-medium"><?= e($lead['name'] ?? $lead['email']) ?></span>
    </div>

    <!-- Page heading -->
    <div class="flex items-start justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
            <?php if ($apollo && !empty($apollo['photo_url'])): ?>
            <img src="<?= e($apollo['photo_url']) ?>"
                 alt=""
                 class="w-14 h-14 rounded-full object-cover border border-gray-200 shrink-0">
            <?php else: ?>
            <div class="w-14 h-14 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0">
                <span class="text-xl text-gray-400 font-semibold"><?= e(strtoupper(substr($lead['name'] ?? '?', 0, 1))) ?></span>
            </div>
            <?php endif; ?>
            <div>
                <h2 class="text-xl font-semibold text-gray-800"><?= e($lead['name'] ?? '-') ?></h2>
                <p class="text-sm text-blue-600 mt-0.5"><?= e($lead['email'] ?? '') ?></p>
                <?php if ($apollo && !empty($apollo['headline'])): ?>
                <p class="text-xs text-gray-400 mt-1"><?= e($apollo['headline']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($apollo): ?>
        <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-green-50 text-green-700 border border-green-200 px-3 py-1.5 rounded-full">
            &#10003; Apollo Enriched
            <?php if ($enrichedAt): ?>
            <span class="text-green-500 font-normal">· <?= e(substr($enrichedAt, 0, 10)) ?></span>
            <?php endif; ?>
        </span>
        <?php else: ?>
        <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-gray-100 text-gray-400 border border-gray-200 px-3 py-1.5 rounded-full">
            &#8857; Not Enriched
        </span>
        <?php endif; ?>
    </div>

    <!-- ── Lead qualification (customer-level summary) ───────── -->
    <?php
    // Customer-level flags are DERIVED (MAX across this customer's submissions):
    // once any enquiry is verified/qualified, the customer stays so — a new
    // form submission never downgrades it. Per-enquiry state lives in each
    // lead row below.
    $isVerified  = !empty($lead['is_verified']);
    $isQualified = !empty($lead['is_qualified']);
    $subs        = $lead['notifications'] ?? [];
    $pendingCnt  = 0;
    $completedCnt = 0;
    foreach ($subs as $s) {
        $st = $s['survey']['status'] ?? null;
        if ($st === 'completed') $completedCnt++;
        elseif ($st === 'pending') $pendingCnt++;
    }
    ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Lead Qualification</p>

        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-sm font-medium px-3 py-1.5 rounded-lg border
                         <?= $isVerified ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-gray-50 text-gray-400 border-gray-200' ?>">
                <?= $isVerified ? '&#9745;' : '&#9744;' ?> Verified
            </span>
            <span class="text-sm font-medium px-3 py-1.5 rounded-lg border
                         <?= $isQualified ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-gray-50 text-gray-400 border-gray-200' ?>">
                <?= $isQualified ? '&#9873;' : '&#9872;' ?> Qualified
            </span>

            <?php if ($completedCnt > 0): ?>
            <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-green-50 text-green-700 border border-green-200"><?= (int)$completedCnt ?> survey<?= $completedCnt > 1 ? 's' : '' ?> completed</span>
            <?php endif; ?>
            <?php if ($pendingCnt > 0): ?>
            <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600 border border-amber-200"><?= (int)$pendingCnt ?> awaiting reply</span>
            <?php endif; ?>
            <?php if ($completedCnt === 0 && $pendingCnt === 0): ?>
            <span class="text-xs font-medium px-2.5 py-1 rounded-lg bg-gray-50 text-gray-400 border border-gray-200">No survey sent</span>
            <?php endif; ?>
        </div>

        <p class="text-xs text-gray-400 mt-3">
            Status is kept across all enquiries — verifying or qualifying any single enquiry below marks this customer for good. A new form submission never resets it.
        </p>
    </div>

    <!-- ── Apollo.io data (collapsible) ──────────────────────── -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <button type="button"
                onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.apollo-chev').classList.toggle('rotate-180');"
                class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 transition">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Apollo.io Data</span>
            <svg class="apollo-chev w-4 h-4 text-gray-300 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="hidden border-t border-gray-100 p-6">

            <?php if ($apollo): ?>

            <?php
            // ── Professional ────────────────────────────────────
            $seniorityBadge = [
                'entry'    => 'bg-gray-100 text-gray-600',
                'mid'      => 'bg-blue-100 text-blue-700',
                'senior'   => 'bg-green-100 text-green-700',
                'manager'  => 'bg-purple-100 text-purple-700',
                'director' => 'bg-orange-100 text-orange-700',
                'vp'       => 'bg-red-100 text-red-700',
                'c_suite'  => 'bg-amber-100 text-amber-700',
            ];
            $senCol = $seniorityBadge[$apollo['seniority'] ?? ''] ?? 'bg-gray-100 text-gray-600';
            ?>

            <!-- Professional -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Professional</p>
            <div class="space-y-2 mb-5">
                <?php if (!empty($apollo['title'])): ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Title</span>
                    <span class="text-sm font-medium text-gray-800"><?= e($apollo['title']) ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($apollo['seniority'])): ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Seniority</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= e($senCol) ?>"><?= e($apollo['seniority']) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($currentCompany): ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Company</span>
                    <span class="text-sm text-gray-700"><?= e($currentCompany) ?></span>
                </div>
                <?php endif; ?>

                <?php
                $depts = array_merge($apollo['departments'] ?? [], $apollo['subdepartments'] ?? []);
                if ($depts):
                ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Departments</span>
                    <span class="text-sm text-gray-600"><?= e(implode(', ', $depts)) ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($apollo['functions'])): ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Functions</span>
                    <span class="text-sm text-gray-600"><?= e(implode(', ', $apollo['functions'])) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Location -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 pt-4 border-t border-gray-100">Location</p>
            <div class="space-y-2 mb-5">
                <?php
                $addr = $apollo['formatted_address']
                    ?? implode(', ', array_filter([$apollo['city'] ?? null, $apollo['state'] ?? null, $apollo['country'] ?? null]));
                if ($addr):
                ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Address</span>
                    <span class="text-sm text-gray-700"><?= e($addr) ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($apollo['time_zone'])): ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Timezone</span>
                    <span class="text-sm text-gray-600"><?= e($apollo['time_zone']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Contact & Social -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 pt-4 border-t border-gray-100">Contact & Social</p>
            <div class="space-y-2 mb-5">
                <?php if (!empty($apollo['email_status'])): ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Email status</span>
                    <span class="text-sm <?= $apollo['email_status'] === 'verified' ? 'text-green-600 font-medium' : 'text-gray-500' ?>">
                        <?= e($apollo['email_status']) ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if (!empty($apollo['personal_emails'])): ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Personal emails</span>
                    <div>
                        <?php foreach ($apollo['personal_emails'] as $pe): ?>
                        <p class="text-sm text-gray-600"><?= e($pe) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                $socials = [
                    'LinkedIn'  => $apollo['linkedin_url']  ?? null,
                    'Twitter'   => $apollo['twitter_url']   ?? null,
                    'GitHub'    => $apollo['github_url']    ?? null,
                    'Facebook'  => $apollo['facebook_url']  ?? null,
                ];
                foreach ($socials as $label => $url):
                    if (!$url) continue;
                ?>
                <div class="flex items-start gap-4">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5"><?= e($label) ?></span>
                    <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"
                       class="text-sm text-blue-600 hover:underline break-all"><?= e($url) ?></a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Employment History -->
            <?php if (!empty($apollo['employment_history'])): ?>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 pt-4 border-t border-gray-100">Employment History</p>
            <div class="space-y-3">
                <?php foreach ($apollo['employment_history'] as $job): ?>
                <div class="pl-3 border-l-2 border-gray-100">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-medium text-gray-800"><?= e($job['title'] ?? '') ?></p>
                        <?php if (!empty($job['current'])): ?>
                        <span class="text-xs bg-green-50 text-green-700 border border-green-200 px-1.5 py-0.5 rounded-full">Current</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5"><?= e($job['organization_name'] ?? '') ?></p>
                    <?php
                    $dates = array_filter([$job['start_date'] ?? null, $job['end_date'] ?? null]);
                    if ($dates):
                    ?>
                    <p class="text-xs text-gray-400 mt-0.5"><?= e(implode(' → ', $dates)) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($job['description'])): ?>
                    <p class="text-xs text-gray-400 mt-1 italic"><?= e($job['description']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <p class="text-sm text-gray-400 italic">
                No enrichment data yet. Apollo.io is queried automatically when this lead submits a form.
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Activity stats row ────────────────────────────────── -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Orders</p>
            <p class="text-2xl font-bold text-gray-800 mt-1"><?= (int)($lead['order_count'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Appointments</p>
            <p class="text-2xl font-bold text-gray-800 mt-1"><?= (int)($lead['appointment_count'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Lead since</p>
            <p class="text-sm font-medium text-gray-700 mt-1"><?= e(substr($lead['created_at'] ?? '', 0, 10)) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Last updated</p>
            <p class="text-sm font-medium text-gray-700 mt-1"><?= e(substr($lead['updated_at'] ?? '', 0, 10)) ?></p>
        </div>
    </div>

    <!-- ── All Leads (submissions) ───────────────────────────── -->
    <?php if (!empty($lead['notifications'])): ?>
    <?php
    // Lead-qualification pipeline stages (from the CRM pipeline reference).
    // Dummy progress for now — real per-lead stage wiring is configured later.
    $qualStages  = ['New', 'Awaiting Information', 'Qualified', '1st offer', '2nd offer', 'Long Term', 'Won'];
    $qualCurrent = 0; // index of the current stage (placeholder)
    ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">All Leads</p>
        <div class="space-y-3">
            <?php foreach ($lead['notifications'] as $n):
                $nVerified  = !empty($n['is_verified']);
                $nQualified = !empty($n['is_qualified']);
                $nSurvey    = $n['survey'] ?? null;
                $nState     = $nSurvey['status'] ?? null;
                $sid        = (int) $n['submission_id'];
            ?>
            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <button type="button"
                        onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.lead-chev').classList.toggle('rotate-180');"
                        class="w-full flex items-start gap-3 text-left px-4 py-3 hover:bg-gray-50 transition">
                    <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 <?= $n['is_read'] ? 'bg-gray-200' : 'bg-blue-500' ?>"></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm text-gray-700"><?= e($n['message']) ?></p>
                            <span class="text-xs bg-gray-100 text-gray-500 border border-gray-200 px-1.5 py-0.5 rounded font-mono"><?= e($n['type']) ?></span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap mt-1">
                            <p class="text-xs text-gray-400"><?= e(substr($n['created_at'] ?? '', 0, 16)) ?></p>
                            <?php if ($nVerified): ?>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 border border-teal-200">&#9745; Verified</span>
                            <?php endif; ?>
                            <?php if ($nQualified): ?>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">&#9873; Qualified</span>
                            <?php endif; ?>
                            <?php if ($nState === 'completed'): ?>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-green-50 text-green-700 border border-green-200">Survey done</span>
                            <?php elseif ($nState === 'pending'): ?>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-amber-50 text-amber-600 border border-amber-200">Awaiting reply</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <svg class="lead-chev w-4 h-4 text-gray-300 shrink-0 transition-transform mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <!-- Lead detail view (expandable) -->
                <div class="hidden border-t border-gray-100 px-4 py-5 bg-gray-50/60">

                    <!-- Per-enquiry verification controls -->
                    <div class="flex items-center gap-3 flex-wrap mb-5">
                        <button type="button"
                                onclick="toggleFlag(this, <?= $sid ?>, 'verified')"
                                data-on="<?= $nVerified ? '1' : '0' ?>"
                                class="text-xs font-medium px-3 py-1.5 rounded-lg border transition-colors
                                       <?= $nVerified ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-white text-gray-500 border-gray-200 hover:border-teal-200' ?>">
                            <span class="flag-icon"><?= $nVerified ? '&#9745;' : '&#9744;' ?></span> Verified
                        </button>
                        <button type="button"
                                onclick="toggleFlag(this, <?= $sid ?>, 'qualified')"
                                data-on="<?= $nQualified ? '1' : '0' ?>"
                                class="text-xs font-medium px-3 py-1.5 rounded-lg border transition-colors
                                       <?= $nQualified ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-white text-gray-500 border-gray-200 hover:border-purple-200' ?>">
                            <span class="flag-icon"><?= $nQualified ? '&#9873;' : '&#9872;' ?></span> Qualified
                        </button>
                        <button type="button"
                                onclick="resendSurvey(this, <?= $sid ?>)"
                                class="text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:border-teal-300 hover:text-teal-700 transition-colors">
                            &#9993; <span class="rs-label"><?= $nState ? 'Resend survey' : 'Send survey' ?></span>
                        </button>
                    </div>

                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-4">Lead Qualification</p>
                    <div class="overflow-x-auto pb-1">
                        <div class="flex items-start min-w-max">
                            <?php foreach ($qualStages as $si => $stageName):
                                $done      = $si <  $qualCurrent;
                                $isCurrent = $si === $qualCurrent;
                            ?>
                            <div class="flex items-start">
                                <div class="flex flex-col items-center w-20">
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold border-2
                                                <?= $isCurrent ? 'bg-blue-500 border-blue-500 text-white' : ($done ? 'bg-blue-100 border-blue-300 text-blue-600' : 'bg-white border-gray-200 text-gray-300') ?>">
                                        <?= $done ? '&#10003;' : ($si + 1) ?>
                                    </div>
                                    <span class="mt-1.5 text-[10px] text-center leading-tight <?= $isCurrent ? 'text-blue-600 font-semibold' : ($done ? 'text-blue-500' : 'text-gray-400') ?>"><?= e($stageName) ?></span>
                                </div>
                                <?php if ($si < count($qualStages) - 1): ?>
                                <div class="w-8 h-0.5 mt-3.5 <?= $done ? 'bg-blue-300' : 'bg-gray-200' ?>"></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Survey Responses (this enquiry only) -->
                    <div class="mt-5 pt-4 border-t border-gray-200">
                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-3">Survey Responses</p>
                        <?php if ($nSurvey && !empty($nSurvey['answers'])):
                            $answers   = $nSurvey['answers'];
                            $questions = $nSurvey['questions'] ?? null;
                            $rows = [];
                            if (is_array($questions)) {
                                foreach ($questions as $q) {
                                    $val = $answers[$q['key']] ?? '';
                                    if ($val === '' || $val === null) continue;
                                    $rows[] = [$q['label'], is_array($val) ? implode(', ', $val) : $val];
                                }
                            } else {
                                foreach ($answers as $k => $val) {
                                    if ($val === '' || $val === null) continue;
                                    $rows[] = [$k, is_array($val) ? implode(', ', $val) : $val];
                                }
                            }
                        ?>
                            <?php if (!empty($rows)): ?>
                            <div class="space-y-3">
                                <?php foreach ($rows as [$qLabel, $qVal]): ?>
                                <div class="flex items-start gap-4">
                                    <span class="text-xs text-gray-400 w-40 shrink-0 pt-0.5"><?= e($qLabel) ?></span>
                                    <span class="text-sm text-gray-700"><?= e($qVal) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-sm text-gray-400">No responses yet.</p>
                            <?php endif; ?>
                        <?php elseif ($nState === 'pending'): ?>
                            <p class="text-sm text-gray-400">Survey sent &middot; awaiting reply.</p>
                        <?php else: ?>
                            <p class="text-sm text-gray-400">No survey submitted for this enquiry.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
(function () {
    const API_BASE = '<?= API_BASE ?>';
    const API_KEY  = '<?= API_KEY ?>';
    const JWT      = '<?= e($_SESSION['jwt'] ?? '') ?>';

    function headers() {
        return {
            'Content-Type':  'application/json',
            'X-API-Key':     API_KEY,
            'Authorization': 'Bearer ' + JWT,
        };
    }

    // Manual override of the per-enquiry verified / qualified flags
    window.toggleFlag = function (btn, submissionId, field) {
        const next = btn.dataset.on === '1' ? 0 : 1;
        btn.disabled = true;
        btn.classList.add('opacity-60');

        fetch(API_BASE + '/wl/admin/customer/flag', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ submission_id: submissionId, field: field, value: next })
        })
        .then(r => r.json())
        .then(json => {
            if (!json.success) throw new Error(json.error || 'Update failed');
            applyFlagState(btn, field, next);
        })
        .catch(err => alert('Could not update ' + field + ': ' + err.message))
        .finally(() => {
            btn.disabled = false;
            btn.classList.remove('opacity-60');
        });
    };

    function applyFlagState(btn, field, on) {
        btn.dataset.on = on ? '1' : '0';
        const icon = btn.querySelector('.flag-icon');
        const onColor  = field === 'qualified'
            ? 'bg-purple-50 text-purple-700 border-purple-200'
            : 'bg-teal-50 text-teal-700 border-teal-200';
        const offHover = field === 'qualified' ? 'hover:border-purple-200' : 'hover:border-teal-200';
        const offColor = 'bg-white text-gray-500 border-gray-200 ' + offHover;
        btn.className = 'text-sm font-medium px-3 py-1.5 rounded-lg border transition-colors '
            + (on ? onColor : offColor);
        if (icon) {
            if (field === 'qualified') icon.innerHTML = on ? '&#9873;' : '&#9872;';
            else                       icon.innerHTML = on ? '&#9745;' : '&#9744;';
        }
    }

    // Send (or resend) the qualification survey email for one enquiry
    window.resendSurvey = function (btn, submissionId) {
        const label = btn.querySelector('.rs-label');
        const original = label.textContent;
        btn.disabled = true;
        btn.classList.add('opacity-60');
        label.textContent = 'Sending…';

        fetch(API_BASE + '/wl/admin/customer/survey/send', {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ submission_id: submissionId })
        })
        .then(r => r.json())
        .then(json => {
            if (!json.success) throw new Error(json.error || 'Send failed');
            label.textContent = 'Sent ✓';
            setTimeout(() => { label.textContent = 'Resend survey'; }, 4000);
        })
        .catch(err => {
            alert('Could not send survey: ' + err.message);
            label.textContent = original;
        })
        .finally(() => {
            btn.disabled = false;
            btn.classList.remove('opacity-60');
        });
    };
})();
</script>
