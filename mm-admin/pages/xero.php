<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$jsApiBase = json_encode(API_BASE);
$jsApiKey  = json_encode(API_KEY);
$jsOrigin  = json_encode(ORIGIN);
$jsJwt     = json_encode($_SESSION['jwt'] ?? '');

// Result banner after the OAuth round-trip (?xero=connected | error)
$xeroResult = $_GET['xero']   ?? '';
$xeroReason = $_GET['reason'] ?? '';

$layout    = 'app';
$activeNav = 'xero';
?>
<script type="application/json" id="page-meta">
{
    "title": "Xero Integration - Majestic Marquees Admin",
    "description": "Connect your Xero account to send final invoices automatically"
}
</script>

<script>
const _apiBase   = <?= $jsApiBase ?>;
const _apiKey    = <?= $jsApiKey ?>;
const _apiOrigin = <?= $jsOrigin ?>;
const _jwt       = <?= $jsJwt ?>;
</script>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- Heading -->
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Xero Integration</h1>
        <p class="mt-1 text-sm text-gray-500">
            Connect your Xero organisation so accepted offers are pushed to Xero as
            final invoices and emailed to your customer automatically.
        </p>
    </div>

    <?php if ($xeroResult === 'connected'): ?>
    <div class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
        Xero connected successfully.
    </div>
    <?php elseif ($xeroResult === 'error'): ?>
    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
        Could not connect Xero<?= $xeroReason ? ' (' . e($xeroReason) . ')' : '' ?>. Please try again.
    </div>
    <?php endif; ?>

    <!-- Status card -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-[#13B5EA]/10 flex items-center justify-center">
                    <span class="text-[#13B5EA] font-bold text-lg">X</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Xero account</p>
                    <p id="xero-status-line" class="text-sm text-gray-500">Checking connection…</p>
                </div>
            </div>
            <span id="xero-status-pill"
                  class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                …
            </span>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button id="xero-connect-btn" onclick="xeroConnect()"
                    class="hidden inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#13B5EA] text-white text-sm font-medium hover:bg-[#0f9fcc] transition-colors disabled:opacity-60">
                Connect Xero
            </button>
            <button id="xero-reconnect-btn" onclick="xeroConnect()"
                    class="hidden inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                Reconnect
            </button>
            <button id="xero-disconnect-btn" onclick="xeroDisconnect()"
                    class="hidden inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50 transition-colors">
                Disconnect
            </button>
        </div>
    </div>

    <!-- How it works -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <p class="text-sm font-semibold text-gray-900">How it works</p>
        <ol class="mt-3 space-y-2 text-sm text-gray-600 list-decimal list-inside">
            <li>Connect your Xero organisation using the button above.</li>
            <li>Build an offer in <a href="/lead-management" class="text-blue-600 hover:underline">Lead Management</a> and send it to the customer.</li>
            <li>When the customer <strong>accepts</strong> the offer, the deal moves to <strong>Won</strong>.</li>
            <li>The offer is pushed to Xero as a final invoice and Xero emails it to the customer.</li>
        </ol>
    </div>

    <!-- Setup note -->
    <div class="bg-blue-50 rounded-2xl border border-blue-100 p-6">
        <p class="text-sm font-semibold text-blue-900">One-time app setup</p>
        <p class="mt-2 text-sm text-blue-800">
            Register the app in the
            <a href="https://developer.xero.com/" target="_blank" rel="noopener noreferrer"
               class="font-medium underline hover:text-blue-900">Xero developer portal</a>,
            then add the following redirect URI to your app
            (your app → Configuration):
        </p>
        <code id="xero-redirect-uri" class="mt-2 block bg-white border border-blue-200 rounded-lg px-3 py-2 text-xs text-blue-900 break-all"></code>
    </div>

    <!-- Pay Now (custom payment URL) -->
    <div id="xero-paynow-card" class="hidden bg-white rounded-2xl border border-gray-200 shadow-sm">
        <button type="button" id="xero-paynow-toggle" onclick="togglePayNow()" aria-expanded="false"
                class="w-full flex items-center justify-between gap-3 p-6 text-left">
            <span class="text-sm font-semibold text-gray-900">Pay Now button <span class="text-gray-400 font-normal">(optional)</span></span>
            <svg id="xero-paynow-chevron" class="w-5 h-5 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div id="xero-paynow-body" class="hidden px-6 pb-6">
            <p class="text-sm text-gray-600">
                Only needed if you don't already collect payment through Xero (your own bank
                account, PayPal or Stripe). This adds a <strong>Pay Now</strong> button to your
                Xero online invoices that sends the customer to a PayPal / card checkout, then
                marks the invoice paid automatically.
            </p>

            <p class="mt-4 text-sm font-medium text-gray-900">Your custom payment URL</p>
            <div class="mt-1 flex items-stretch gap-2">
                <code id="xero-pay-url" class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-800 break-all"></code>
                <button type="button" onclick="copyPayUrl()"
                        class="shrink-0 px-3 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50">
                    <span id="xero-pay-copy-label">Copy</span>
                </button>
            </div>

            <ol class="mt-4 space-y-2 text-sm text-gray-600 list-decimal list-inside">
                <li>In Xero, go to <strong>Settings → Payment services</strong>.</li>
                <li>Click <strong>Add Payment Service → Custom Payment URL</strong>.</li>
                <li>Paste the URL above and give it a name (e.g. “Pay online”).</li>
                <li>Open <strong>Settings → Invoice settings → your branding theme → Edit</strong> and tick the custom payment service so the <strong>Pay Now</strong> button appears on invoices.</li>
            </ol>

            <p class="mt-4 text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                Recording payments needs an extra Xero permission. If you connected Xero before
                this feature was added, click <strong>Reconnect</strong> once to grant it.
            </p>
        </div>
    </div>


</div>

<script>
(function () {
    const H = {
        'X-API-Key':     _apiKey,
        'Authorization': 'Bearer ' + _jwt,
    };

    const statusLine = document.getElementById('xero-status-line');
    const statusPill = document.getElementById('xero-status-pill');
    const connectBtn = document.getElementById('xero-connect-btn');
    const reconnBtn  = document.getElementById('xero-reconnect-btn');
    const disconnBtn = document.getElementById('xero-disconnect-btn');
    const payCard    = document.getElementById('xero-paynow-card');
    const payUrlEl   = document.getElementById('xero-pay-url');

    // Show the redirect URI for this environment.
    document.getElementById('xero-redirect-uri').textContent =
        _apiBase + '/wl/admin/xero/callback';

    function setPill(text, cls) {
        statusPill.textContent = text;
        statusPill.className =
            'shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ' + cls;
    }

    function loadStatus() {
        fetch(_apiBase + '/wl/admin/xero/status', { headers: H })
            .then(r => r.json())
            .then(j => {
                if (j.connected) {
                    statusLine.textContent = j.org_name
                        ? ('Connected to ' + j.org_name)
                        : 'Connected';
                    setPill('Connected', 'bg-green-100 text-green-700');
                    connectBtn.classList.add('hidden');
                    reconnBtn.classList.remove('hidden');
                    disconnBtn.classList.remove('hidden');
                    if (j.pay_url) {
                        payUrlEl.textContent = j.pay_url;
                        payCard.classList.remove('hidden');
                    } else {
                        payCard.classList.add('hidden');
                    }
                } else {
                    statusLine.textContent = 'Not connected yet.';
                    setPill('Not connected', 'bg-gray-100 text-gray-500');
                    connectBtn.classList.remove('hidden');
                    reconnBtn.classList.add('hidden');
                    disconnBtn.classList.add('hidden');
                    payCard.classList.add('hidden');
                }
            })
            .catch(() => {
                statusLine.textContent = 'Could not load Xero status.';
                setPill('Error', 'bg-red-100 text-red-700');
            });
    }

    window.xeroConnect = function () {
        connectBtn.disabled = true;
        reconnBtn.disabled  = true;
        fetch(_apiBase + '/wl/admin/xero/connect', { headers: H })
            .then(r => r.json())
            .then(j => {
                if (j.authorize_url) {
                    window.location.href = j.authorize_url;
                } else {
                    alert(j.error || 'Could not start the Xero connection.');
                    connectBtn.disabled = false;
                    reconnBtn.disabled  = false;
                }
            })
            .catch(() => {
                alert('Network error starting the Xero connection.');
                connectBtn.disabled = false;
                reconnBtn.disabled  = false;
            });
    };

    window.copyPayUrl = function () {
        const txt = payUrlEl.textContent || '';
        const label = document.getElementById('xero-pay-copy-label');
        const done = function () { if (label) { label.textContent = 'Copied'; setTimeout(function () { label.textContent = 'Copy'; }, 1500); } };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(txt).then(done, function () {});
        }
    };

    window.togglePayNow = function () {
        const body = document.getElementById('xero-paynow-body');
        const chev = document.getElementById('xero-paynow-chevron');
        const btn  = document.getElementById('xero-paynow-toggle');
        if (!body) return;
        const open = body.classList.toggle('hidden') === false;
        if (chev) chev.classList.toggle('rotate-180', open);
        if (btn)  btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    window.xeroDisconnect = function () {
        if (!confirm('Disconnect Xero? Accepted offers will no longer be sent to Xero until you reconnect.')) {
            return;
        }
        fetch(_apiBase + '/wl/admin/xero/disconnect', { method: 'POST', headers: H })
            .then(r => r.json())
            .then(() => loadStatus())
            .catch(() => alert('Could not disconnect Xero.'));
    };

    loadStatus();

    // Clean the ?xero=… params out of the URL after showing the banner.
    if (window.history.replaceState && location.search.indexOf('xero=') !== -1) {
        window.history.replaceState({}, '', '/xero');
    }
})();
</script>
