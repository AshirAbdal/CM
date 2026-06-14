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
    "title": "<?= e($lead['name'] ?? 'Lead') ?> — Majestic Marquees Admin",
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
                <h2 class="text-xl font-semibold text-gray-800"><?= e($lead['name'] ?? '—') ?></h2>
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

    <!-- ── Two-column layout ─────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- LEFT: Form data -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Form Data</p>

            <?php
            $fields = [
                'Email'         => $lead['email']                ?? null,
                'Phone'         => $lead['phone']                ?? null,
                'Country'       => $lead['country']              ?? null,
                'Business Name' => $lead['legal_business_name']  ?? null,
                'Website'       => $lead['website_url']          ?? null,
                'Added'         => $lead['created_at']           ?? null,
            ];
            foreach ($fields as $label => $value):
                if ($value === null || $value === '') continue;
            ?>
            <div class="flex items-start gap-4">
                <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5 uppercase tracking-wide font-medium"><?= e($label) ?></span>
                <?php if ($label === 'Website'): ?>
                <a href="<?= e($value) ?>" target="_blank" rel="noopener noreferrer"
                   class="text-sm text-blue-600 hover:underline break-all"><?= e($value) ?></a>
                <?php else: ?>
                <span class="text-sm text-gray-700 break-all"><?= e($value) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- RIGHT: Apollo.io data -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-5">Apollo.io Data</p>

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

    <!-- ── Recent Activity (notifications) ───────────────────── -->
    <?php if (!empty($lead['notifications'])): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Recent Activity</p>
        <ul class="space-y-3">
            <?php foreach ($lead['notifications'] as $n): ?>
            <li class="flex items-start gap-3">
                <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 <?= $n['is_read'] ? 'bg-gray-200' : 'bg-blue-500' ?>"></span>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm text-gray-700"><?= e($n['message']) ?></p>
                        <span class="text-xs bg-gray-100 text-gray-500 border border-gray-200 px-1.5 py-0.5 rounded font-mono"><?= e($n['type']) ?></span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5"><?= e(substr($n['created_at'] ?? '', 0, 16)) ?></p>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

</div>
