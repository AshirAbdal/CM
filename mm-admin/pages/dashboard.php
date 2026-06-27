<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

// Server-side fetch of the live dashboard so the first paint already has real
// numbers; the JS below then polls the same endpoint to keep it live.
$ch = curl_init(API_BASE . '/wl/admin/dashboard');
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
$raw       = curl_exec($ch);
$apiStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($apiStatus === 401) {
    session_destroy();
    header('Location: /login');
    exit;
}

$dash = json_decode($raw, true) ?: ['success' => false];

$jsApiBase = json_encode(API_BASE);
$jsApiKey  = json_encode(API_KEY);
$jsJwt     = json_encode($_SESSION['jwt'] ?? '');
$jsDash    = json_encode($dash, JSON_UNESCAPED_SLASHES);

$layout    = 'app';
$activeNav = 'dashboard';
?>
<script type="application/json" id="page-meta">
{
    "title": "Dashboard - Majestic Marquees Admin",
    "description": "Live sales, pipeline and lead performance"
}
</script>

<div class="space-y-6" id="dash-root">

    <!-- Heading + live indicator -->
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
            <p class="text-sm text-gray-500 mt-1">Live sales, pipeline and lead performance.</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-500 bg-white border border-gray-200 rounded-full px-3 py-1.5">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
            </span>
            <span>Live</span>
            <span class="text-gray-300">&middot;</span>
            <span>updated <span id="dash-updated">--:--:--</span></span>
        </div>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total leads</p>
            <p class="mt-2 text-2xl font-bold text-gray-800" data-kpi="total_leads">0</p>
            <p class="text-xs text-gray-400 mt-1"><span data-kpi="new_this_month">0</span> new this month</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Active deals</p>
            <p class="mt-2 text-2xl font-bold text-gray-800" data-kpi="active_deals">0</p>
            <p class="text-xs text-gray-400 mt-1">in the pipeline</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Qualified</p>
            <p class="mt-2 text-2xl font-bold text-gray-800" data-kpi="qualified">0</p>
            <p class="text-xs text-gray-400 mt-1">leads qualified</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Won</p>
            <p class="mt-2 text-2xl font-bold text-forest-600" data-kpi="won">0</p>
            <p class="text-xs text-gray-400 mt-1"><span data-kpi="lead_win_rate">0</span>% lead win rate</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Offer win rate</p>
            <p class="mt-2 text-2xl font-bold text-gray-800"><span data-kpi="offer_win_rate">0</span>%</p>
            <p class="text-xs text-gray-400 mt-1"><span data-kpi="estimates_accepted">0</span> of <span data-kpi="estimates_issued">0</span> offers</p>
        </div>
        <div class="bg-tan-50 rounded-xl border border-tan-200 p-4">
            <p class="text-xs font-medium text-tan-700 uppercase tracking-wide">Revenue (won)</p>
            <div class="mt-2 text-2xl font-bold text-tan-800 leading-tight" id="kpi-revenue">&mdash;</div>
            <p class="text-xs text-tan-600 mt-1">accepted offers</p>
        </div>
    </div>

    <!-- Charts: row 1 -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 xl:col-span-2">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Leads over time</h3>
            <div class="h-64"><canvas id="chart-leads"></canvas></div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Offers by status</h3>
            <div class="h-64"><canvas id="chart-estimates"></canvas></div>
        </div>
    </div>

    <!-- Charts: row 2 -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 xl:col-span-2">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Sales pipeline</h3>
            <div class="h-64"><canvas id="chart-pipeline"></canvas></div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Offers per month</h3>
            <div class="h-64"><canvas id="chart-offers"></canvas></div>
        </div>
    </div>

    <!-- Recent won deals -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Recent won deals</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase tracking-wide">
                        <th class="px-5 py-3 font-medium">Customer</th>
                        <th class="px-5 py-3 font-medium">Offer</th>
                        <th class="px-5 py-3 font-medium text-right">Amount</th>
                        <th class="px-5 py-3 font-medium text-right">Date</th>
                    </tr>
                </thead>
                <tbody id="recent-won-body" class="divide-y divide-gray-100">
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const API_BASE = <?= $jsApiBase ?>;
    const API_KEY  = <?= $jsApiKey ?>;
    const JWT      = <?= $jsJwt ?>;
    let data       = <?= $jsDash ?>;

    const PALETTE = {
        tan:     '#a57b5b',
        tanSoft: 'rgba(165,123,91,0.15)',
        forest:  '#586b4f',
        amber:   '#d9a441',
        slate:   '#94a3b8',
        red:     '#c2603f',
        green:   '#5a8f5a',
    };

    const CUR = { GBP: '\u00a3', USD: '$', EUR: '\u20ac', AUD: '$', CAD: '$', NZD: '$' };
    function money(amount, currency) {
        const sym = CUR[currency] || (currency ? currency + ' ' : '');
        return sym + Number(amount || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
    }
    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    const charts = {};

    function buildCharts() {
        Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
        Chart.defaults.color = '#6b7280';
        const noLegend = { plugins: { legend: { display: false } } };
        const intYAxis = { y: { beginAtZero: true, ticks: { precision: 0 } } };

        // Leads over time - area line
        charts.leads = new Chart(document.getElementById('chart-leads'), {
            type: 'line',
            data: { labels: [], datasets: [{
                data: [], borderColor: PALETTE.tan, backgroundColor: PALETTE.tanSoft,
                fill: true, tension: 0.35, borderWidth: 2,
                pointBackgroundColor: PALETTE.tan, pointRadius: 3,
            }] },
            options: { responsive: true, maintainAspectRatio: false, ...noLegend, scales: intYAxis },
        });

        // Offers by status - doughnut
        charts.estimates = new Chart(document.getElementById('chart-estimates'), {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [],
                backgroundColor: [PALETTE.slate, PALETTE.amber, PALETTE.green, PALETTE.red, '#cbd5e1'],
                borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } } },
        });

        // Sales pipeline - horizontal bar
        charts.pipeline = new Chart(document.getElementById('chart-pipeline'), {
            type: 'bar',
            data: { labels: [], datasets: [{ data: [], backgroundColor: PALETTE.forest, borderRadius: 4 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, ...noLegend,
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
        });

        // Offers per month - bar
        charts.offers = new Chart(document.getElementById('chart-offers'), {
            type: 'bar',
            data: { labels: [], datasets: [{ data: [], backgroundColor: PALETTE.tan, borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, ...noLegend, scales: intYAxis },
        });
    }

    function render() {
        if (!data || !data.success) return;
        const k = data.kpis || {};

        // KPI numbers
        document.querySelectorAll('[data-kpi]').forEach(el => {
            const key = el.getAttribute('data-kpi');
            if (key in k) el.textContent = k[key];
        });

        // Revenue (won), grouped by currency - never summed across currencies
        const rev = data.revenue_by_currency || [];
        const revEl = document.getElementById('kpi-revenue');
        revEl.innerHTML = rev.length
            ? rev.map(r => '<div>' + esc(money(r.total, r.currency)) + '</div>').join('')
            : money(0, data.currency);

        // Charts
        const leads = data.leads_by_month || [];
        charts.leads.data.labels = leads.map(p => p.label);
        charts.leads.data.datasets[0].data = leads.map(p => p.value);
        charts.leads.update();

        const est = data.estimates_by_status || [];
        charts.estimates.data.labels = est.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1));
        charts.estimates.data.datasets[0].data = est.map(s => s.count);
        charts.estimates.update();

        const pipe = (data.pipeline || []).filter(s => s.stage !== 'dead' || s.count > 0);
        charts.pipeline.data.labels = pipe.map(s => s.label);
        charts.pipeline.data.datasets[0].data = pipe.map(s => s.count);
        charts.pipeline.update();

        const offers = data.offers_by_month || [];
        charts.offers.data.labels = offers.map(p => p.label);
        charts.offers.data.datasets[0].data = offers.map(p => p.value);
        charts.offers.update();

        // Recent won table
        const won = data.recent_won || [];
        const body = document.getElementById('recent-won-body');
        body.innerHTML = won.length
            ? won.map(w =>
                '<tr class="hover:bg-gray-50">'
                + '<td class="px-5 py-3 font-medium text-gray-800">' + esc(w.name) + '</td>'
                + '<td class="px-5 py-3 text-gray-500">' + (w.estimate_no ? esc(w.estimate_no) : '&mdash;') + '</td>'
                + '<td class="px-5 py-3 text-right font-semibold text-forest-600">' + esc(money(w.amount, w.currency)) + '</td>'
                + '<td class="px-5 py-3 text-right text-gray-500">' + esc((w.date || '').substring(0, 10)) + '</td>'
                + '</tr>').join('')
            : '<tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">No won deals yet.</td></tr>';

        // Updated timestamp
        document.getElementById('dash-updated').textContent = new Date().toLocaleTimeString();
    }

    async function refresh() {
        try {
            const res = await fetch(API_BASE + '/wl/admin/dashboard', {
                headers: { 'X-API-Key': API_KEY, 'Authorization': 'Bearer ' + JWT },
            });
            if (res.status === 401) { window.location.href = '/login'; return; }
            const json = await res.json();
            if (json && json.success) { data = json; render(); }
        } catch (e) { /* keep last good data on transient errors */ }
    }

    buildCharts();
    render();
    // Live polling every 30s, plus an immediate refresh when the tab regains focus.
    setInterval(refresh, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
</script>
