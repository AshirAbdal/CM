<?php
// Public estimate view - no JWT required
// Accessed via /estimate/{64-char-token}
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000' : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002' : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

$token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
if (strlen($token) !== 64) { http_response_code(404); echo '404 - Not found'; exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4">

<div id="app" class="max-w-3xl mx-auto">
    <div id="loading" class="text-center text-slate-400 py-20">Loading estimate…</div>
    <div id="error"   class="hidden text-center text-red-500 py-20"></div>
    <div id="content" class="hidden"></div>
</div>

<script>
const TOKEN   = '<?= htmlspecialchars($token, ENT_QUOTES) ?>';
const API_BASE= '<?= API_BASE ?>';
const API_KEY = '<?= API_KEY ?>';
const ORIGIN  = '<?= ORIGIN ?>';

function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function loadEstimate() {
    try {
        const res  = await fetch(API_BASE + '/wl/public/estimates/' + TOKEN, {
            headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN }
        });
        const data = await res.json();
        if (!res.ok) { showError(data.error || 'Estimate not found.'); return; }
        renderEstimate(data);
    } catch (e) {
        showError('Network error: ' + e.message);
    }
}

function showError(msg) {
    document.getElementById('loading').classList.add('hidden');
    const el = document.getElementById('error');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function renderEstimate(d) {
    document.getElementById('loading').classList.add('hidden');
    const el  = document.getElementById('content');
    const cur = esc(d.currency);
    const co  = d.company || {};

    const statusColors = {
        draft:'#94a3b8', sent:'#3b82f6', accepted:'#16a34a',
        rejected:'#dc2626', expired:'#f59e0b'
    };
    const sc = statusColors[d.status] || '#94a3b8';
    const statusBadge = `<span style="border:1.5px solid ${sc};color:${sc};padding:3px 14px;border-radius:20px;font-size:12px;font-weight:500;text-transform:capitalize">${esc(d.status)}</span>`;

    const logoHtml = co.logo_url
        ? `<img src="${esc(co.logo_url)}" alt="logo" style="max-height:64px;max-width:200px;object-fit:contain">`
        : `<div style="font-size:22px;font-weight:700;color:#1e293b">${esc(co.name||'')}</div>`;

    const itemRows = (d.items || []).map(i => {
        const taxCell = i.tax_pct > 0 ? `${i.tax_pct}%` : '-';
        return `<tr class="border-b border-slate-100">
            <td class="py-2.5 pr-4 text-slate-700 text-sm">${esc(i.name)}</td>
            <td class="py-2.5 pr-4 text-slate-600 text-sm text-right">${cur}&nbsp;${Number(i.unit_price).toFixed(2)}</td>
            <td class="py-2.5 pr-4 text-slate-600 text-sm text-center">${i.qty}</td>
            <td class="py-2.5 pr-4 text-slate-500 text-sm text-center">${taxCell}</td>
            <td class="py-2.5 text-slate-800 text-sm text-right font-medium">${cur}&nbsp;${Number(i.subtotal).toFixed(2)}</td>
        </tr>`;
    }).join('');

    const canRespond = d.status === 'sent';

    el.innerHTML = `
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="p-8 border-b border-slate-100">
            <div class="flex items-start justify-between mb-6">
                <div>${logoHtml}<div class="mt-2">${statusBadge}</div></div>
                <div class="text-right text-slate-500 text-sm leading-6">
                    <div class="text-xl font-bold text-slate-800 tracking-wide mb-1">ESTIMATE</div>
                    ${co.name ? `<div class="font-semibold text-slate-700">${esc(co.name)}</div>` : ''}
                    ${co.phone ? `<div>${esc(co.phone)}</div>` : ''}
                    ${co.address ? `<div>${esc(co.address)}</div>` : ''}
                    ${co.website ? `<div>${esc(co.website)}</div>` : ''}
                </div>
            </div>
            <div class="border-t-2 border-blue-500 mb-6"></div>
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Billed to</p>
                    <p class="font-semibold text-slate-800">${esc(d.customer_name||'-')}</p>
                    ${d.customer_email ? `<p class="text-blue-600 text-sm">${esc(d.customer_email)}</p>` : ''}
                    ${d.customer_address ? `<p class="text-slate-500 text-sm">${esc(d.customer_address)}</p>` : ''}
                </div>
                <div class="text-right">
                    <div class="grid grid-cols-2 gap-x-4 text-sm">
                        <span class="text-slate-400 text-right">Estimate No</span><span class="font-medium text-slate-700">${esc(d.estimate_no)}</span>
                        <span class="text-slate-400 text-right">Issue Date</span><span class="font-medium text-slate-700">${esc(d.issue_date||'')}</span>
                        ${d.expiry_date ? `<span class="text-slate-400 text-right">Expiry Date</span><span class="font-medium text-slate-700">${esc(d.expiry_date)}</span>` : ''}
                    </div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="p-8">
            <table class="w-full">
                <thead><tr class="border-b-2 border-slate-200 text-xs text-slate-400 uppercase tracking-wide">
                    <th class="pb-2 text-left font-semibold">Item Name</th>
                    <th class="pb-2 text-right font-semibold">Price</th>
                    <th class="pb-2 text-center font-semibold">QTY</th>
                    <th class="pb-2 text-center font-semibold">TAX</th>
                    <th class="pb-2 text-right font-semibold">Subtotal</th>
                </tr></thead>
                <tbody>${itemRows}</tbody>
            </table>

            <div class="mt-5 border-t border-slate-200 pt-4 text-sm">
                <div class="flex justify-end gap-8 text-slate-500 mb-1">
                    <span>Subtotal</span><span>${cur}&nbsp;${Number(d.subtotal).toFixed(2)}</span>
                </div>
                ${d.freight > 0 ? `<div class="flex justify-end gap-8 text-slate-500 mb-1"><span>Freight</span><span>${cur}&nbsp;${Number(d.freight).toFixed(2)}</span></div>` : ''}
                <div class="flex justify-end gap-8 font-bold text-slate-800 text-base pt-2 border-t border-slate-200 mt-1">
                    <span>Amount Due (${cur==='€'?'EUR':cur==='$'?'USD':cur})</span><span>${cur}&nbsp;${Number(d.total).toFixed(2)}</span>
                </div>
            </div>
            ${d.notes ? `<p class="mt-5 text-xs text-slate-400">${esc(d.notes)}</p>` : ''}
        </div>

        <!-- Accept / Reject / Request Changes buttons -->
        ${canRespond ? `
        <div id="actionBar" class="no-print px-8 pb-8">
            <div class="flex gap-4 justify-center mb-4">
                <button onclick="respond('accept')" class="flex-1 max-w-xs bg-green-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-green-700 transition">
                    ✓ Accept Estimate
                </button>
                <button onclick="respond('reject')" class="flex-1 max-w-xs border-2 border-red-400 text-red-600 py-3 rounded-xl font-semibold text-sm hover:bg-red-50 transition">
                    ✗ Decline
                </button>
            </div>
            <div class="border-t border-slate-100 pt-4">
                <button onclick="toggleChangesBox()" class="text-sm text-slate-500 hover:text-slate-700 underline underline-offset-2">
                    Request changes instead
                </button>
                <div id="changesBox" class="hidden mt-3">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Describe the changes you need</label>
                    <textarea id="changesMsg" rows="3"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"
                        placeholder="e.g. Please remove the wooden poles and add 2 extra carabiners…"></textarea>
                    <button onclick="submitChanges()"
                        class="mt-2 bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">
                        Send Request
                    </button>
                </div>
            </div>
        </div>` : `
        <div class="no-print px-8 pb-8 text-center text-sm">
            ${d.status === 'accepted' ? '<span class="text-green-600 font-medium">✓ You accepted this estimate.</span>'
              : d.status === 'rejected' ? '<span class="text-red-500 font-medium">✗ You declined this estimate.</span>'
              : d.status === 'expired'  ? '<span class="text-amber-500 font-medium">⚠ This estimate has expired.</span>'
              : ''}
            ${d.change_request ? `<div class="mt-3 bg-blue-50 border border-blue-200 rounded-xl p-3 text-left text-sm text-slate-600"><p class="text-xs font-semibold text-blue-600 mb-1">Your change request (sent ${esc(d.change_requested_at||'')})</p><p>${esc(d.change_request)}</p></div>` : ''}
        </div>`}
    </div>
    <div class="no-print mt-4 text-center">
        <button onclick="window.print()" class="text-xs text-slate-400 hover:text-slate-600">Print / Save as PDF</button>
    </div>`;
    el.classList.remove('hidden');

    window._estimateData = d;
}

async function respond(action) {
    const bar = document.getElementById('actionBar');
    if (bar) bar.innerHTML = '<p class="text-slate-400 text-sm text-center">Processing…</p>';
    try {
        const res  = await fetch(API_BASE + '/wl/public/estimates/' + TOKEN + '/respond', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-API-Key': API_KEY, 'Origin': ORIGIN },
            body: JSON.stringify({ action }),
        });
        const data = await res.json();
        if (!res.ok || !data.success) { alert(data.error || 'Something went wrong.'); loadEstimate(); return; }
        loadEstimate();
    } catch (e) { alert('Network error: ' + e.message); }
}

function toggleChangesBox() {
    const box = document.getElementById('changesBox');
    box.classList.toggle('hidden');
    if (!box.classList.contains('hidden')) {
        document.getElementById('changesMsg').focus();
    }
}

async function submitChanges() {
    const msg = document.getElementById('changesMsg').value.trim();
    if (!msg) { alert('Please describe the changes you need.'); return; }
    const btn = document.querySelector('#changesBox button');
    btn.disabled = true; btn.textContent = 'Sending…';
    try {
        const res = await fetch(API_BASE + '/wl/public/estimates/' + TOKEN + '/respond', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-API-Key': API_KEY, 'Origin': ORIGIN },
            body: JSON.stringify({ action: 'changes', message: msg }),
        });
        const data = await res.json();
        if (!res.ok || !data.success) { alert(data.error || 'Failed to send.'); btn.disabled = false; btn.textContent = 'Send Request'; return; }
        loadEstimate(); // reload to show confirmation
    } catch (e) { alert('Network error: ' + e.message); btn.disabled = false; btn.textContent = 'Send Request'; }
}

loadEstimate();
</script>
</body>
</html>
