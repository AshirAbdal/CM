<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

// ── JWT auth guard (matches inventory.php) ─────────────────────────────────
$ch = curl_init(API_BASE . '/wl/admin/products/import');
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
curl_exec($ch);
if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 401) {
    curl_close($ch);
    session_destroy();
    header('Location: /login');
    exit;
}
curl_close($ch);

// Where the close button returns to: back to the originating deal if known.
$CR_id   = (int) ($_GET['CR_id'] ?? 0);
$wantSid = (int) ($_GET['submission_id'] ?? 0);
$backUrl = ($CR_id > 0 && $wantSid > 0)
    ? '/deal?CR_id=' . $CR_id . '&submission_id=' . $wantSid
    : '/lead-management';

$layout    = 'app';
$activeNav = 'lead-management';
?>

<script type="application/json" id="page-meta">
{"title": "Make Offer | Majestic Marquees Admin"}
</script>

<style>
@media (min-width:1280px) {
  .inv-col { height: calc(100vh - 150px); }
}
</style>

<!-- Dialog-style offer builder -->
<div class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm" aria-hidden="true"></div>

<div class="relative z-50 mx-auto w-full max-w-[1850px] px-3 sm:px-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">

        <!-- Dialog header -->
        <div class="flex items-center justify-between gap-3 px-5 sm:px-7 py-4 border-b border-slate-200 bg-white">
            <div>
                <h1 class="text-lg font-bold text-slate-800">Make an Offer</h1>
                <p class="text-xs text-slate-400 mt-0.5">Build an estimate for this deal and send it to the lead.</p>
            </div>
            <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>"
               class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Close
            </a>
        </div>

        <div class="p-5 sm:p-7 bg-slate-50">
            <div class="grid grid-cols-1 xl:grid-cols-[5fr_2fr] gap-8">

            <!-- ══ LEFT: Editable Invoice Preview ══════════════════════════════════ -->
            <div class="inv-col flex flex-col" style="min-height:680px;">
                <div class="mb-4 flex items-start justify-between flex-wrap gap-3">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Invoice Preview</h2>
                        <p class="text-slate-500 mt-1 text-sm">Auto-filled by AI · all fields are editable</p>
                    </div>
                    <div class="flex gap-2" id="invoiceActions" style="display:none!important;">
                        <button onclick="clearInvoice()" class="text-xs border border-slate-200 text-slate-500 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition">Clear</button>
                        <button onclick="copyInvoiceJSON()" class="text-xs border border-slate-200 text-slate-600 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition">Copy JSON</button>
                        <button onclick="printInvoice()" class="text-xs bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">Print / Save PDF</button>
                    </div>
                </div>

                <!-- Restored-draft banner -->
                <div id="invDraftBanner" class="hidden mb-3 flex items-center justify-between gap-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-3 py-2 text-sm">
                    <span>&#8617; <span id="invDraftMsg">Unsaved draft restored.</span></span>
                    <button type="button" onclick="discardDraft()" class="text-xs px-2.5 py-1 rounded-md border border-amber-300 text-amber-700 hover:bg-amber-100 transition whitespace-nowrap">Discard draft</button>
                </div>
                <!-- Autosave status -->
                <div id="invDraftStatus" class="hidden mb-2 text-[11px] text-slate-400"></div>

                <!-- Empty state (hidden - editor always visible) -->
                <div id="invoiceEmpty" class="hidden flex-1 flex flex-col items-center justify-center bg-white rounded-xl border-2 border-dashed border-slate-200 text-slate-400">
                    <p class="text-sm font-medium">No invoice yet</p>
                </div>

                <!-- Editable Invoice (always visible) -->
                <div id="invoiceEditor" class="flex-1 bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
                    <!-- Header toolbar -->
                    <div class="flex items-center justify-between px-3 sm:px-5 py-3 border-b border-slate-100 bg-slate-50 flex-shrink-0 flex-wrap gap-y-2">
                        <span class="text-xs text-slate-400 font-medium uppercase tracking-wide">Estimate</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button onclick="clearInvoice()" class="text-xs border border-slate-200 text-slate-500 px-2.5 py-1 rounded-lg hover:bg-white transition">Clear</button>
                            <button onclick="copyInvoiceJSON()" class="text-xs border border-slate-200 text-slate-600 px-2.5 py-1 rounded-lg hover:bg-white transition">Copy JSON</button>
                            <button onclick="saveEstimate()" id="invSaveBtn" class="text-xs border border-primary text-primary px-3 py-1 rounded-lg hover:bg-blue-50 transition">Save</button>
                            <button onclick="sendEstimateEmail()" id="invSendEmailBtn" class="text-xs bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition">Send Email</button>
                            <button onclick="printInvoice()" class="text-xs bg-primary text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition">Print / PDF</button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-3 sm:p-6 bg-slate-100">
                      <div class="bg-white w-full rounded-lg shadow-sm border border-slate-200 p-4 sm:p-8 lg:p-10">

                        <!-- Document header: logo/company + status (left) · company contact (right) -->
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">
                            <div>
                                <div id="invDocLogo" class="text-[22px] font-bold text-slate-800 leading-tight">Your Company</div>
                                <div id="invDocStatusBadge" class="mt-2"></div>
                            </div>
                            <div id="invDocCompany" class="sm:text-right text-[13px] leading-6 text-slate-500"></div>
                        </div>

                        <div class="border-t-2 border-blue-500 mb-5"></div>

                        <div class="text-right text-[28px] font-bold tracking-wide text-slate-800 mb-6">ESTIMATE</div>

                        <!-- Billed to (editable) · estimate meta (editable) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-8 mb-7">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mb-1">Billed to</p>
                                <input id="invCustomer" type="text" placeholder="Customer name…"
                                       class="w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1 -ml-2 text-[15px] font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <input id="invEmail" type="email" placeholder="client@email.com"
                                       class="w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1 -ml-2 text-[13px] text-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <input id="invAddress" type="text" placeholder="Street, City, Postcode"
                                       class="w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1 -ml-2 text-[13px] text-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div class="text-right text-[13px] space-y-1.5">
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">Estimate No</span>
                                    <input id="invEstimateNo" type="text" placeholder="EST-01"
                                           class="w-28 border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-0.5 text-right font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">Issue Date</span>
                                    <input id="invDate" type="date"
                                           class="w-32 border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-0.5 text-right font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">Expiry Date</span>
                                    <input id="invExpiry" type="date"
                                           class="w-32 border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-0.5 text-right font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">Currency</span>
                                    <select id="invCurrency"
                                            class="border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-0.5 text-right font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        <option>EUR</option><option>USD</option><option>GBP</option><option>AUD</option>
                                    </select>
                                </div>
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">Status</span>
                                    <select id="invStatus" onchange="updateInvStatusBadge()"
                                            class="border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-0.5 text-right font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        <option value="draft">Draft</option>
                                        <option value="sent">Sent</option>
                                        <option value="accepted">Accepted</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tent selector: load all children deterministically -->
                        <div class="relative mb-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Load all items for a tent size</label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input id="invTentSearch" type="text" autocomplete="off"
                                           placeholder="e.g. 4,5x4,5 or Stretch…"
                                           oninput="invTentDebounce()" onkeydown="invTentKey(event)"
                                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary pr-8">
                                    <div id="invTentSpinner" class="hidden absolute right-2 top-2.5">
                                        <svg class="w-4 h-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div id="invTentDropdown" class="hidden absolute z-30 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-52 overflow-y-auto text-sm"></div>
                        </div>

                        <!-- Search & add individual item from catalogue -->
                        <div class="relative mb-3">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Add individual item from catalogue</label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input id="invCatalogueSearch" type="text" autocomplete="off"
                                           placeholder="Search product name, SKU, category…"
                                           oninput="invSearchDebounce()" onkeydown="invSearchKey(event)"
                                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary pr-8">
                                    <div id="invSearchSpinner" class="hidden absolute right-2 top-2.5">
                                        <svg class="w-4 h-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div id="invSearchDropdown" class="hidden absolute z-30 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-52 overflow-y-auto text-sm"></div>
                        </div>

                        <!-- Line items table -->
                        <div class="overflow-x-auto mb-2">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b-2 border-slate-800 text-[10px] text-slate-400 uppercase tracking-wide">
                                        <th class="pb-2 text-left font-semibold">Item Name</th>
                                        <th class="pb-2 text-right font-semibold w-24">Price</th>
                                        <th class="pb-2 text-center font-semibold w-14">QTY</th>
                                        <th class="pb-2 text-center font-semibold w-16">TAX</th>
                                        <th class="pb-2 text-right font-semibold w-24">Subtotal</th>
                                        <th class="pb-2 w-6"></th>
                                    </tr>
                                </thead>
                                <tbody id="invItemsBody"></tbody>
                            </table>
                        </div>
                        <button onclick="invAddRow()" class="text-xs text-primary hover:text-blue-700 font-medium flex items-center gap-1 mb-4">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add blank line
                        </button>

                        <!-- Summary (mirrors the printed estimate) -->
                        <div class="mt-6 flex justify-end">
                            <div class="w-full max-w-xs text-[13px] space-y-1.5">
                                <div class="flex justify-between text-slate-500">
                                    <span>Subtotal</span>
                                    <span id="invSubtotalDisplay">EUR 0.00</span>
                                </div>
                                <div class="flex justify-between items-center text-slate-500">
                                    <span class="flex items-center gap-1">Freight
                                        <span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">non-disc</span>
                                    </span>
                                    <input id="invFreight" type="number" min="0" step="0.01" value="0.00" oninput="invRecalc()"
                                           class="w-24 border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-0.5 text-right focus:outline-none focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div class="flex justify-between text-[15px] font-bold text-slate-800 pt-2 border-t-2 border-slate-800">
                                    <span>Amount Due</span>
                                    <span id="invTotal">EUR 0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mt-6">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mb-1">Notes</p>
                            <textarea id="invNotes" rows="2" placeholder="Optional notes…"
                                      class="w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1 -ml-2 text-[13px] text-slate-500 resize-none focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                        </div>
                      </div>
                    </div>
                </div>
            </div>

            <!-- ══ RIGHT: AI Chat ════════════════════════════════════════════════════ -->
            <div class="inv-col flex flex-col" style="min-height:680px;">
                <div class="mb-4">
                    <h2 class="text-2xl font-bold text-slate-800">AI Invoice Assistant</h2>
                    <p class="text-slate-500 mt-1 text-sm">Describe what you need - AI will build the invoice from your product catalogue.</p>
                </div>

                <div class="flex flex-col flex-1 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-4">
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347a3.75 3.75 0 00-1.097 2.65v.5a2 2 0 01-2 2h-1a2 2 0 01-2-2v-.5a3.75 3.75 0 00-1.097-2.65l-.347-.347z"/></svg>
                            </div>
                            <div class="bg-slate-100 rounded-2xl rounded-tl-sm px-4 py-3 max-w-sm">
                                <p class="text-slate-700 text-sm">Hi! I can help you create invoices from your product catalogue. Try:<br><br>
                                <span class="italic text-slate-500">"Create an invoice for a Stretch 4.5x4.5 tent"</span><br>
                                <span class="italic text-slate-500">"What sizes are available in Sailcloth?"</span><br>
                                <span class="italic text-slate-500">"Invoice for 6x6 tent, remove wooden poles"</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-slate-200 p-3 bg-white flex-shrink-0">
                        <div class="flex gap-2 items-end">
                            <textarea id="chatInput" rows="1"
                                   placeholder="e.g. Create an invoice for Stretch 4.5x4.5…"
                                   class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent resize-none overflow-hidden"
                                   style="min-height:38px;max-height:140px"
                                   oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,140)+'px'"
                                   onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); sendMessage(); }"></textarea>
                            <button id="sendBtn" onclick="sendMessage()"
                                    class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-1.5 text-sm font-medium flex-shrink-0">
                                <svg id="sendIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                <svg id="loadingIcon" class="w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                Send
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5">Powered by Gemini AI · Based on your uploaded product catalogue · <span class="text-slate-300">Shift+Enter for new line</span></p>
                    </div>
                </div>
            </div>

            </div><!-- /grid -->
        </div>
    </div>
</div>

<script>
// ── Shared constants ──────────────────────────────────────────────────────────
const API_BASE = '<?= API_BASE ?>';
const JWT      = <?= json_encode($_SESSION['jwt'] ?? '') ?>;
const API_KEY  = '<?= API_KEY ?>';
const ORIGIN   = '<?= ORIGIN ?>';

// ── AI Chatbot ────────────────────────────────────────────────────────────────
let chatHistory    = [];
let currentInvoice = null;

// Persist the AI conversation per deal so it survives closing/reopening the page.
const CHAT_PREFIX = 'mm_invoice_chat_v1:';
function _chatScope() {
    const _q  = new URLSearchParams(window.location.search);
    const sid = parseInt(_q.get('submission_id') || '0', 10);
    return sid > 0 ? String(sid) : 'new';
}
function _chatKey() { return CHAT_PREFIX + _chatScope(); }

function saveChatHistory() {
    try {
        if (chatHistory.length) localStorage.setItem(_chatKey(), JSON.stringify(chatHistory));
        else localStorage.removeItem(_chatKey());
    } catch (_) { /* storage disabled / full — ignore */ }
    scheduleServerDraftSave();
}

function clearChatHistory() {
    chatHistory = [];
    try { localStorage.removeItem(_chatKey()); } catch (_) {}
    // Reset the transcript back to just the welcome bubble.
    const container = document.getElementById('chatMessages');
    if (container) {
        container.querySelectorAll('[data-chat-msg]').forEach(el => el.remove());
    }
}

function restoreChatHistory() {
    let saved = null;
    try { const raw = localStorage.getItem(_chatKey()); saved = raw ? JSON.parse(raw) : null; }
    catch (_) { saved = null; }
    if (!Array.isArray(saved) || !saved.length) return;
    chatHistory = saved;
    saved.forEach(m => appendBubble(m.role === 'user' ? 'user' : 'model', m.text));
}

async function sendMessage() {
    const input   = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const sendIcon= document.getElementById('sendIcon');
    const loadIcon= document.getElementById('loadingIcon');
    const message = input.value.trim();
    if (!message) return;

    appendBubble('user', message);
    input.value = '';
    input.style.height = '38px';
    sendBtn.disabled = true;
    sendIcon.classList.add('hidden');
    loadIcon.classList.remove('hidden');

    try {
        const res = await fetch(API_BASE + '/wl/admin/products/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT },
            body: JSON.stringify({ message, history: chatHistory }),
        });
        const data = await res.json();
        if (!res.ok) { appendBubble('model', '⚠️ ' + (data.error || 'Something went wrong.')); return; }
        chatHistory.push({ role: 'user', text: message });
        chatHistory.push({ role: 'model', text: data.reply });
        saveChatHistory();

        // Client-side fallback: if backend didn't parse the invoice, try here
        let invoice = data.invoice;
        if (!invoice && data.reply) {
            const jsonMatch = data.reply.match(/```json\s*([\s\S]*?)\s*```/) ||
                              data.reply.match(/```\s*(\{[\s\S]*?"items"[\s\S]*?\})\s*```/) ||
                              data.reply.match(/```(?:json)?\s*(\{[\s\S]+)/);  // unclosed fence
            if (jsonMatch) {
                let fragment = jsonMatch[1].replace(/```[\s\S]*$/, ''); // remove trailing ```
                let parsed = null;
                try { parsed = JSON.parse(fragment); } catch (_) {}
                if (!parsed) {
                    // Try to close truncated JSON
                    for (const suffix of [']}', '"]}', '"\n]}}']) {
                        try { parsed = JSON.parse(fragment + suffix); if (parsed) break; } catch (_) {}
                    }
                }
                if (parsed && Array.isArray(parsed.items)) invoice = parsed;
            }
        }

        // Strip ALL code fences (open or closed, json-tagged or not) from the display text
        const cleanReply = data.reply
            .replace(/```(?:json)?[\s\S]*?```/g, '')   // closed fences
            .replace(/```(?:json)?[\s\S]*/g, '')        // unclosed/truncated fences
            .trim();
        appendBubble('model', cleanReply || (invoice ? 'Invoice generated! See the Invoice Preview on the left.' : data.reply));

        if (invoice) {
            currentInvoice = invoice;
            loadInvoiceIntoEditor(invoice);
        }
    } catch (err) {
        appendBubble('model', '⚠️ Network error: ' + err.message);
    } finally {
        sendBtn.disabled = false;
        sendIcon.classList.remove('hidden');
        loadIcon.classList.add('hidden');
    }
}

function appendBubble(role, text) {
    const container = document.getElementById('chatMessages');
    const isUser = role === 'user';
    const wrapper = document.createElement('div');
    wrapper.className = 'flex gap-3' + (isUser ? ' justify-end' : '');
    wrapper.setAttribute('data-chat-msg', '1');
    if (!isUser) {
        wrapper.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.347a3.75 3.75 0 00-1.097 2.65v.5a2 2 0 01-2 2h-1a2 2 0 01-2-2v-.5a3.75 3.75 0 00-1.097-2.65l-.347-.347z"/></svg>
            </div>
            <div class="bg-slate-100 rounded-2xl rounded-tl-sm px-4 py-3 max-w-sm text-sm text-slate-700 whitespace-pre-wrap">${escHtml(text)}</div>`;
    } else {
        wrapper.innerHTML = `<div class="bg-primary text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-sm text-sm whitespace-pre-wrap">${escHtml(text)}</div>`;
    }
    container.appendChild(wrapper);
    container.scrollTop = container.scrollHeight;
}

// ── Invoice Editor ────────────────────────────────────────────────────────────
let invCompany   = null;  // loaded once from /wl/admin/company
let invCurrentId = null;  // est_id if this estimate is saved
let invSubmissionId = null;  // deal (lead submission) this offer belongs to, if any

async function invLoadCompany() {
    if (invCompany) { invRenderDocHeader(); return invCompany; }
    try {
        const res  = await fetch(API_BASE + '/wl/admin/company', {
            headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT }
        });
        if (res.ok) invCompany = await res.json();
    } catch (_) {}
    invRenderDocHeader();
    return invCompany;
}

// Render the document header (logo / company name + contact block) from company info
function invRenderDocHeader() {
    const co = invCompany || {};
    const logoEl = document.getElementById('invDocLogo');
    if (logoEl) {
        logoEl.innerHTML = co.logo_url
            ? `<img src="${escHtml(co.logo_url)}" alt="logo" style="max-height:56px;max-width:180px;object-fit:contain">`
            : escHtml(co.name || 'Your Company');
    }
    const coEl = document.getElementById('invDocCompany');
    if (coEl) {
        coEl.innerHTML =
            (co.name    ? `<div class="font-semibold text-[15px] text-slate-800">${escHtml(co.name)}</div>` : '') +
            (co.phone   ? `<div>${escHtml(co.phone)}</div>` : '') +
            (co.address ? `<div>${escHtml(co.address).replace(/,/g,'<br>')}</div>` : '') +
            (co.website ? `<div>${escHtml(co.website)}</div>` : '');
    }
}

// Live status badge (matches the printed estimate colours)
const INV_STATUS_COLORS = { draft:'#94a3b8', sent:'#3b82f6', accepted:'#16a34a', rejected:'#dc2626', expired:'#f59e0b' };
function updateInvStatusBadge() {
    const sel   = document.getElementById('invStatus');
    const badge = document.getElementById('invDocStatusBadge');
    if (!sel || !badge) return;
    const s = sel.value || 'draft';
    const c = INV_STATUS_COLORS[s] || '#94a3b8';
    badge.innerHTML = `<span style="border:1px solid ${c};color:${c};padding:2px 12px;border-radius:20px;font-size:12px;text-transform:capitalize">${s}</span>`;
}

// Reveal the per-row discount controls (kept in the DOM so totals always compute)
function invToggleDisc(btn) {
    const wrap = btn.closest('td').querySelector('.inv-disc-wrap');
    if (!wrap) return;
    wrap.classList.remove('hidden');
    btn.classList.add('hidden');
    wrap.querySelector('.inv-disc')?.focus();
}

function invShowEditor() {
    document.getElementById('invoiceEmpty').classList.add('hidden');
    document.getElementById('invoiceEditor').classList.remove('hidden');
    invLoadCompany();        // pre-fetch + render company header
    updateInvStatusBadge();  // sync status badge with current select
}

function loadInvoiceIntoEditor(inv) {
    invShowEditor();
    document.getElementById('invCustomer').value    = inv.customer_name    || '';
    document.getElementById('invEmail').value       = inv.customer_email   || '';
    document.getElementById('invAddress').value     = inv.customer_address || '';
    document.getElementById('invEstimateNo').value  = inv.estimate_no      || '';
    document.getElementById('invDate').value        = inv.invoice_date || inv.issue_date || new Date().toISOString().slice(0,10);
    document.getElementById('invExpiry').value      = inv.expiry_date      || '';
    document.getElementById('invNotes').value       = inv.notes            || '';
    document.getElementById('invFreight').value     = (inv.freight ?? 0).toFixed(2);
    const cur = document.getElementById('invCurrency');
    [...cur.options].forEach(o => o.selected = o.value === (inv.currency || 'EUR'));
    if (inv.status) document.getElementById('invStatus').value = inv.status;
    updateInvStatusBadge();
    const tbody = document.getElementById('invItemsBody');
    tbody.innerHTML = '';
    (inv.items || []).forEach(item => invAppendRow(item.name, item.qty, item.unit_price, item.discount_percentage ?? 0, item.discount_flat ?? 0, item.tax_pct ?? 0));
    invRecalc();
}

// ── Draft autosave (localStorage) ─────────────────────────────────────────────
// Protects in-progress work: the editor is saved to the browser as the admin
// types and restored when they come back, even before clicking "Save".
// Drafts are scoped per deal (submission_id) so different deals never collide.
const DRAFT_PREFIX = 'mm_invoice_draft_v1:';
let _draftTimer    = null;
let _suppressDraft = false;

function _draftScope() {
    const _q  = new URLSearchParams(window.location.search);
    const sid = parseInt(_q.get('submission_id') || '0', 10);
    return sid > 0 ? String(sid) : 'new';
}
function _draftKey() { return DRAFT_PREFIX + _draftScope(); }

function draftHasContent(inv) {
    if (!inv) return false;
    const hasItems = Array.isArray(inv.items) && inv.items.some(i => (i.name || '').trim() !== '' || Number(i.unit_price) > 0);
    const hasParty = (inv.customer_name || '').trim() !== '' || (inv.customer_email || '').trim() !== '' || (inv.customer_address || '').trim() !== '';
    const hasNotes = (inv.notes || '').trim() !== '';
    return hasItems || hasParty || hasNotes;
}

function saveDraftNow() {
    if (_suppressDraft) return;
    try {
        const inv = buildInvoiceFromEditor();
        if (!draftHasContent(inv)) { localStorage.removeItem(_draftKey()); setDraftStatus(''); return; }
        localStorage.setItem(_draftKey(), JSON.stringify({ savedAt: new Date().toISOString(), invoice: inv }));
        setDraftStatus('\u2713 Draft saved ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
    } catch (_) { /* storage disabled / full — fail silently */ }
}

function scheduleDraftSave() {
    clearTimeout(_draftTimer);
    _draftTimer = setTimeout(saveDraftNow, 600);
    scheduleServerDraftSave();
}

function loadDraft() {
    try { const raw = localStorage.getItem(_draftKey()); return raw ? JSON.parse(raw) : null; }
    catch (_) { return null; }
}

function clearDraft() {
    try { localStorage.removeItem(_draftKey()); } catch (_) {}
    deleteOfferDraftServer();
    const b = document.getElementById('invDraftBanner');
    if (b) b.classList.add('hidden');
    setDraftStatus('');
}

function discardDraft() {
    if (!confirm('Discard the restored draft and start a blank invoice?')) return;
    clearDraft();
    clearInvoice();
}

function setDraftStatus(text) {
    const el = document.getElementById('invDraftStatus');
    if (!el) return;
    if (!text) { el.classList.add('hidden'); el.textContent = ''; return; }
    el.textContent = text;
    el.classList.remove('hidden');
}

function _draftRelTime(iso) {
    const t = Date.parse(iso);
    if (isNaN(t)) return '';
    const mins = Math.round((Date.now() - t) / 60000);
    if (mins < 1)  return 'just now';
    if (mins < 60) return mins + ' min ago';
    const hrs = Math.round(mins / 60);
    if (hrs < 24)  return hrs + ' hr ago';
    const days = Math.round(hrs / 24);
    return days + ' day' + (days === 1 ? '' : 's') + ' ago';
}

function maybeRestoreDraft() {
    const d = loadDraft();
    if (!d || !draftHasContent(d.invoice)) return false;
    loadInvoiceIntoEditor(d.invoice);
    if (d.invoice.submission_id) invSubmissionId = d.invoice.submission_id;
    const msg = document.getElementById('invDraftMsg');
    if (msg) msg.textContent = 'Unsaved draft restored (saved ' + _draftRelTime(d.savedAt) + ').';
    const banner = document.getElementById('invDraftBanner');
    if (banner) banner.classList.remove('hidden');
    return true;
}

// ── Server-side draft (per deal) ──────────────────────────────────────────────
// When the page is opened for a real deal (?submission_id), the invoice draft AND
// the AI chat are mirrored to the database so the work follows the admin across
// devices/browsers. localStorage stays the fallback (offline / save failure) and
// is the only store when there is no deal yet (the "new" scope).
let _serverDraftTimer = null;

function _hasServerScope() {
    const _q = new URLSearchParams(window.location.search);
    return parseInt(_q.get('submission_id') || '0', 10) > 0;
}
function _serverScopeId() {
    const _q = new URLSearchParams(window.location.search);
    return parseInt(_q.get('submission_id') || '0', 10);
}

async function saveOfferDraftServer() {
    if (_suppressDraft || !_hasServerScope()) return;
    const inv = buildInvoiceFromEditor();
    if (!draftHasContent(inv) && !chatHistory.length) return;
    try {
        const res = await fetch(API_BASE + '/wl/admin/lead/offer-draft', {
            method: 'PUT',
            headers: { 'Content-Type':'application/json', 'X-API-Key':API_KEY, 'Origin':ORIGIN, 'Authorization':'Bearer '+JWT },
            body: JSON.stringify({ submission_id: _serverScopeId(), invoice: inv, chat: chatHistory }),
        });
        if (res.ok) {
            setDraftStatus('\u2713 Draft saved ' + new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' }));
        }
    } catch (_) { /* keep the localStorage copy as the fallback */ }
}

function scheduleServerDraftSave() {
    if (!_hasServerScope()) return;
    clearTimeout(_serverDraftTimer);
    _serverDraftTimer = setTimeout(saveOfferDraftServer, 800);
}

async function deleteOfferDraftServer() {
    if (!_hasServerScope()) return;
    try {
        await fetch(API_BASE + '/wl/admin/lead/offer-draft?submission_id=' + _serverScopeId(), {
            method: 'DELETE',
            headers: { 'X-API-Key':API_KEY, 'Origin':ORIGIN, 'Authorization':'Bearer '+JWT },
        });
    } catch (_) {}
}

async function restoreOfferDraftServer() {
    if (!_hasServerScope()) return false;
    try {
        const res = await fetch(API_BASE + '/wl/admin/lead/offer-draft?submission_id=' + _serverScopeId(), {
            headers: { 'X-API-Key':API_KEY, 'Origin':ORIGIN, 'Authorization':'Bearer '+JWT },
        });
        if (!res.ok) return false;
        const data  = await res.json();
        const draft = data.draft;
        if (!draft) return false;
        let restored = false;
        if (draft.invoice && draftHasContent(draft.invoice)) {
            loadInvoiceIntoEditor(draft.invoice);
            if (draft.invoice.submission_id) invSubmissionId = draft.invoice.submission_id;
            const banner = document.getElementById('invDraftBanner');
            if (banner) banner.classList.remove('hidden');
            const msg = document.getElementById('invDraftMsg');
            if (msg) msg.textContent = 'Unsaved draft restored (saved ' + _draftRelTime(draft.saved_at) + ').';
            restored = true;
        }
        if (Array.isArray(draft.chat) && draft.chat.length) {
            chatHistory = draft.chat;
            draft.chat.forEach(m => appendBubble(m.role === 'user' ? 'user' : 'model', m.text));
            restored = true;
        }
        return restored;
    } catch (_) { return false; }
}

// Autosave on any edit inside the invoice editor; flush before leaving the page.
['input', 'change'].forEach(function (evt) {
    document.addEventListener(evt, function (e) {
        const t = e.target;
        if (t && t.closest && t.closest('#invoiceEditor')) scheduleDraftSave();
    });
});
window.addEventListener('beforeunload', saveDraftNow);

// Also expose a blank-editor start path
document.addEventListener('DOMContentLoaded', async () => {
    document.getElementById('invDate').value = new Date().toISOString().slice(0,10);
    // Set default expiry 15 days out
    const exp = new Date(); exp.setDate(exp.getDate() + 15);
    document.getElementById('invExpiry').value = exp.toISOString().slice(0,10);
    // Auto-assign estimate number
    document.getElementById('invEstimateNo').value = 'EST-' + Date.now().toString().slice(-4);
    invShowEditor();

    // When opened from a deal (Lead Management → New offer), link this offer to
    // that deal and prefill the customer details.
    const _q = new URLSearchParams(window.location.search);
    const _sid = parseInt(_q.get('submission_id') || '0', 10);
    if (_sid > 0) {
        invSubmissionId = _sid;
        if (_q.get('name'))    document.getElementById('invCustomer').value = _q.get('name');
        if (_q.get('email'))   document.getElementById('invEmail').value    = _q.get('email');
        if (_q.get('address')) document.getElementById('invAddress').value  = _q.get('address');
        const banner = document.getElementById('invDealBanner');
        if (banner) { banner.classList.remove('hidden'); }
    }

    // Restore any in-progress work for this deal. For a real deal we prefer the
    // server copy (follows the admin across devices) and fall back to the local
    // browser copy if there's nothing saved server-side or the request fails.
    // The "new" scope (no deal yet) is browser-only.
    let _restored = false;
    if (_hasServerScope()) {
        _restored = await restoreOfferDraftServer();
    }
    if (!_restored) {
        maybeRestoreDraft();
        restoreChatHistory();
    }

    // The lead's contact details are the authoritative "Billed to". A restored
    // draft may not carry them (or saved them empty), so backfill any contact
    // field that ended up blank from the deal's URL params.
    if (_sid > 0) {
        const _fillIfEmpty = (id, val) => {
            const el = document.getElementById(id);
            if (el && !el.value.trim() && val) el.value = val;
        };
        _fillIfEmpty('invCustomer', _q.get('name'));
        _fillIfEmpty('invEmail',    _q.get('email'));
        _fillIfEmpty('invAddress',  _q.get('address'));
    }
});

function invAppendRow(name = '', qty = 1, price = 0, disc = 0, flat = 0, tax = 0, nonDisc = false) {
    const tbody = document.getElementById('invItemsBody');
    const tr = document.createElement('tr');
    tr.className = 'border-b border-slate-100 group align-top';
    const hasDisc   = (Number(disc) > 0 || Number(flat) > 0);
    const lockBadge = nonDisc
        ? `<span class="text-[10px] bg-amber-100 text-amber-700 px-1 rounded ml-1" title="Non-discountable">🔒</span>`
        : '';
    const discToggle = nonDisc
        ? ''
        : `<button type="button" onclick="invToggleDisc(this)" class="inv-disc-toggle text-[11px] text-slate-400 hover:text-blue-600 mt-0.5 ${hasDisc ? 'hidden' : ''}">+ discount</button>`;
    tr.innerHTML = `
        <td class="py-2 pr-3">
            <div class="flex items-center">
                <input type="text" value="${escHtml(name)}" oninput="invRecalc()"
                       class="inv-name w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Item name…">
                ${lockBadge}
            </div>
            <div class="inv-disc-wrap ${hasDisc ? '' : 'hidden'} flex items-end gap-3 mt-1.5 pl-2 bg-slate-50 border border-slate-200 rounded-md px-2 py-1.5 w-fit">
                <span class="text-[10px] uppercase tracking-wide text-slate-400 self-center mr-1">Discount</span>
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Percent (%)</label>
                    <div class="relative">
                        <input type="number" value="${Number(disc).toFixed(1)}" min="0" max="100" step="0.1" oninput="invRecalc()"
                               ${nonDisc ? 'disabled title="Non-discountable"' : ''}
                               class="inv-disc w-20 border border-slate-200 rounded px-1.5 py-0.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-500 pr-5 ${nonDisc ? 'opacity-40 cursor-not-allowed' : ''}" placeholder="0">
                        <span class="absolute right-1.5 top-1 text-slate-400 text-[10px] pointer-events-none">%</span>
                    </div>
                </div>
                <span class="text-[11px] text-slate-300 self-center pb-1">+</span>
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Flat (<span class="inv-disc-cur">EUR</span>)</label>
                    <div class="relative">
                        <input type="number" value="${Number(flat).toFixed(2)}" min="0" step="0.01" oninput="invRecalc()"
                               ${nonDisc ? 'disabled title="Non-discountable"' : ''}
                               class="inv-flat w-24 border border-slate-200 rounded px-1.5 py-0.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-500 ${nonDisc ? 'opacity-40 cursor-not-allowed' : ''}" placeholder="0.00">
                    </div>
                </div>
            </div>
            ${discToggle}
        </td>
        <td class="py-2 pr-3 w-24">
            <input type="number" value="${Number(price).toFixed(2)}" min="0" step="0.01" oninput="invRecalc()"
                   class="inv-price w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1 text-sm text-right focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td class="py-2 pr-3 w-14">
            <input type="number" value="${qty}" min="0" oninput="invRecalc()"
                   class="inv-qty w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1 text-sm text-center focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td class="py-2 pr-3 w-16">
            <div class="relative">
                <input type="number" value="${Number(tax).toFixed(1)}" min="0" max="100" step="0.1" oninput="invRecalc()"
                       class="inv-tax w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1 text-sm text-center focus:outline-none focus:ring-1 focus:ring-blue-500 pr-5">
                <span class="absolute right-1.5 top-1.5 text-slate-400 text-xs pointer-events-none">%</span>
            </div>
        </td>
        <td class="py-2 w-24 text-right text-sm text-slate-800 inv-sub font-medium whitespace-nowrap pt-2.5"></td>
        <td class="py-2 w-6 text-center">
            <button onclick="this.closest('tr').remove(); invRecalc(); scheduleDraftSave();"
                    class="text-slate-300 hover:text-red-400 opacity-0 group-hover:opacity-100 transition pt-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </td>`;
    tbody.appendChild(tr);
    invRecalc();
    scheduleDraftSave();
    return tr;
}

function invAddRow() { invAppendRow(); }

function invRecalc() {
    const cur     = document.getElementById('invCurrency').value;
    document.querySelectorAll('.inv-disc-cur').forEach(el => el.textContent = cur);
    let   subtotal = 0;
    document.querySelectorAll('#invItemsBody tr').forEach(tr => {
        const qty   = parseFloat(tr.querySelector('.inv-qty')?.value)   || 0;
        const price = parseFloat(tr.querySelector('.inv-price')?.value) || 0;
        const disc  = parseFloat(tr.querySelector('.inv-disc')?.value)  || 0;
        const flat  = parseFloat(tr.querySelector('.inv-flat')?.value)  || 0;
        const tax   = parseFloat(tr.querySelector('.inv-tax')?.value)   || 0;
        const base  = Math.max(0, (qty * price * (1 - disc / 100)) - flat);
        const sub   = base * (1 + tax / 100);
        subtotal   += sub;
        const subCell = tr.querySelector('.inv-sub');
        if (subCell) {
            subCell.textContent = cur + ' ' + sub.toFixed(2);
            subCell.style.color = (disc > 0 || flat > 0) ? '#16a34a' : '';
        }
    });
    const freight = parseFloat(document.getElementById('invFreight')?.value) || 0;
    const total   = subtotal + freight;
    const subtotalEl = document.getElementById('invSubtotalDisplay');
    if (subtotalEl) subtotalEl.textContent = cur + ' ' + subtotal.toFixed(2);
    document.getElementById('invTotal').textContent = cur + ' ' + total.toFixed(2);
}

document.addEventListener('change', e => {
    if (e.target.id === 'invCurrency' || e.target.id === 'invFreight') invRecalc();
});

function clearInvoice() {
    currentInvoice = null;
    invCurrentId   = null;
    document.getElementById('invCustomer').value    = '';
    document.getElementById('invEmail').value       = '';
    document.getElementById('invAddress').value     = '';
    document.getElementById('invEstimateNo').value  = 'EST-' + Date.now().toString().slice(-4);
    document.getElementById('invDate').value        = new Date().toISOString().slice(0,10);
    const exp = new Date(); exp.setDate(exp.getDate() + 15);
    document.getElementById('invExpiry').value      = exp.toISOString().slice(0,10);
    document.getElementById('invNotes').value       = '';
    document.getElementById('invFreight').value     = '0.00';
    document.getElementById('invItemsBody').innerHTML = '';
    document.getElementById('invStatus').value      = 'draft';
    updateInvStatusBadge();
    invRecalc();
    clearDraft();
    clearChatHistory();
}

// ── Catalogue search for invoice ──────────────────────────────────────────────
let invSearchTimer = null;
let invSelectedIdx = -1;
let invSearchResults = [];

function invSearchDebounce() {
    clearTimeout(invSearchTimer);
    invSearchTimer = setTimeout(invDoSearch, 280);
}

async function invDoSearch() {
    const q = document.getElementById('invCatalogueSearch').value.trim();
    const dd = document.getElementById('invSearchDropdown');
    const spin = document.getElementById('invSearchSpinner');
    if (q.length < 2) { dd.classList.add('hidden'); return; }

    spin.classList.remove('hidden');
    try {
        const res = await fetch(API_BASE + '/wl/admin/products/search?q=' + encodeURIComponent(q) + '&limit=20', {
            headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT }
        });
        const data = await res.json();
        invSearchResults = data.results || [];
        invSelectedIdx   = -1;
        renderInvDropdown();
    } catch (_) { dd.classList.add('hidden'); }
    finally { spin.classList.add('hidden'); }
}

function renderInvDropdown() {
    const dd = document.getElementById('invSearchDropdown');
    if (!invSearchResults.length) { dd.innerHTML = '<p class="px-4 py-3 text-xs text-slate-400">No products found.</p>'; dd.classList.remove('hidden'); return; }
    dd.innerHTML = invSearchResults.map((r, i) => {
        if (r.is_leaf === false) {
            // Structural node (tent type / size group / tent size / category) → expandable
            return `
        <div class="inv-dd-item flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-blue-50 transition"
             data-idx="${i}" onclick="invPickResult(${i})">
            <div class="flex items-center gap-2 min-w-0">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                <div class="min-w-0">
                    <span class="text-sm font-medium text-slate-800">${escHtml(r.item_name)}</span>
                    <span class="text-xs text-slate-400 ml-2">${escHtml(r.breadcrumb)}</span>
                </div>
            </div>
            <span class="text-xs font-medium text-amber-600 ml-3 whitespace-nowrap">Load all items</span>
        </div>`;
        }
        // Leaf item → adds a single line
        return `
        <div class="inv-dd-item flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-blue-50 transition"
             data-idx="${i}" onclick="invPickResult(${i})">
            <div class="min-w-0">
                <span class="text-sm font-medium text-slate-800">${escHtml(r.item_name)}</span>
                <span class="text-xs text-slate-400 ml-2">${escHtml(r.breadcrumb)}</span>
            </div>
            <span class="text-xs font-semibold text-primary ml-3 whitespace-nowrap">${r.unit_price > 0 ? 'EUR '+Number(r.unit_price).toFixed(2) : '-'}</span>
        </div>`;
    }).join('');
    dd.classList.remove('hidden');
}

function invPickResult(idx) {
    const r = invSearchResults[idx];
    if (!r) return;
    document.getElementById('invCatalogueSearch').value = '';
    document.getElementById('invSearchDropdown').classList.add('hidden');
    invSearchResults = [];

    // Structural node → load every leaf item beneath it (group / tent size / category)
    if (r.is_leaf === false) {
        invLoadGroupItems(r.p_id, r.item_name);
        return;
    }
    // Leaf item → add a single line
    invShowEditor();
    invAppendRow(r.item_name, 1, r.unit_price ?? 0, r.discount_percentage ?? 0, r.discount_flat ?? 0, 0, !!r.non_discountable);
}

// Load all leaf items under a structural node (shared with the tent loader path)
async function invLoadGroupItems(p_id, label) {
    try {
        const res  = await fetch(API_BASE + '/wl/admin/products/' + p_id + '/items', {
            headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT }
        });
        const data = await res.json();
        if (!res.ok || !data.items?.length) {
            alert('No items found under "' + label + '".');
            return;
        }
        invShowEditor();
        data.items.forEach(item =>
            invAppendRow(item.item_name, 1, item.unit_price, item.discount_percentage, item.discount_flat, 0, !!item.non_discountable)
        );
    } catch (e) {
        alert('Could not load items: ' + e.message);
    }
}

function invSearchKey(e) {
    const dd = document.getElementById('invSearchDropdown');
    if (dd.classList.contains('hidden')) return;
    const items = dd.querySelectorAll('.inv-dd-item');
    if (e.key === 'ArrowDown') { e.preventDefault(); invSelectedIdx = Math.min(invSelectedIdx + 1, items.length - 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); invSelectedIdx = Math.max(invSelectedIdx - 1, 0); }
    else if (e.key === 'Enter' && invSelectedIdx >= 0) { e.preventDefault(); invPickResult(invSelectedIdx); return; }
    else if (e.key === 'Escape') { dd.classList.add('hidden'); return; }
    items.forEach((el, i) => el.classList.toggle('bg-blue-50', i === invSelectedIdx));
}

// Close dropdowns on outside click
document.addEventListener('click', e => {
    if (!e.target.closest('#invCatalogueSearch') && !e.target.closest('#invSearchDropdown'))
        document.getElementById('invSearchDropdown')?.classList.add('hidden');
    if (!e.target.closest('#invTentSearch') && !e.target.closest('#invTentDropdown'))
        document.getElementById('invTentDropdown')?.classList.add('hidden');
});

// ── Tent size selector ────────────────────────────────────────────────
let invTentTimer   = null;
let invTentResults = [];
let invTentIdx     = -1;

function invTentDebounce() {
    clearTimeout(invTentTimer);
    invTentTimer = setTimeout(invDoTentSearch, 300);
}

async function invDoTentSearch() {
    const q    = document.getElementById('invTentSearch').value.trim();
    const dd   = document.getElementById('invTentDropdown');
    const spin = document.getElementById('invTentSpinner');
    if (q.length < 2) { dd.classList.add('hidden'); return; }
    spin.classList.remove('hidden');
    try {
        const res  = await fetch(API_BASE + '/wl/admin/products/search?q=' + encodeURIComponent(q) + '&limit=50', {
            headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT }
        });
        const data = await res.json();
        // Build unique tent-size entries from results using breadcrumb
        const seen = new Map();
        (data.results || []).forEach(r => {
            const parts    = r.breadcrumb.split(' › ');
            const sizeIdx  = parts.findIndex(p => /^Size\s/i.test(p.trim()));
            if (sizeIdx < 0) return;
            const key      = parts.slice(0, sizeIdx + 1).join(' › ');
            const sizeLabel= parts[sizeIdx].trim();
            if (!seen.has(key)) {
                seen.set(key, { label: key, sizeLabel, p_id: r.ref_id });
            }
        });
        invTentResults = [...seen.values()];
        invTentIdx     = -1;
        if (!invTentResults.length) {
            dd.innerHTML = '<p class="px-4 py-3 text-xs text-slate-400">No tent sizes found. Try e.g. "4,5x4,5".</p>';
        } else {
            dd.innerHTML = invTentResults.map((r, i) => `
                <div class="inv-tent-item flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-blue-50"
                     data-idx="${i}" onclick="invLoadTent(${i})">
                    <span class="text-sm text-slate-800">${escHtml(r.label)}</span>
                </div>`).join('');
        }
        dd.classList.remove('hidden');
    } catch (_) { dd.classList.add('hidden'); }
    finally { spin.classList.add('hidden'); }
}

function invTentKey(e) {
    const dd    = document.getElementById('invTentDropdown');
    if (dd.classList.contains('hidden')) return;
    const items = dd.querySelectorAll('.inv-tent-item');
    if      (e.key === 'ArrowDown') { e.preventDefault(); invTentIdx = Math.min(invTentIdx + 1, items.length - 1); }
    else if (e.key === 'ArrowUp')   { e.preventDefault(); invTentIdx = Math.max(invTentIdx - 1, 0); }
    else if (e.key === 'Enter' && invTentIdx >= 0) { e.preventDefault(); invLoadTent(invTentIdx); return; }
    else if (e.key === 'Escape')    { dd.classList.add('hidden'); return; }
    items.forEach((el, i) => el.classList.toggle('bg-blue-50', i === invTentIdx));
}

async function invLoadTent(idx) {
    const r = invTentResults[idx];
    if (!r) return;
    document.getElementById('invTentDropdown').classList.add('hidden');
    document.getElementById('invTentSearch').value = '';

    // r.p_id is the ref_id of a leaf → that is the category node.
    // The tent-size node is one level above the category node.
    // We need to call GET /wl/admin/products/{tentSizeId}/items.
    // Since r.p_id = category's ref_id = tent-size p_id, call directly.
    const res = await fetch(API_BASE + '/wl/admin/products/' + r.p_id + '/items', {
        headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT }
    });
    const data = await res.json();
    if (!res.ok || !data.items?.length) {
        alert('No items found under that tent size. The tent-size node ID may not match. Try searching for a specific item name instead.');
        return;
    }
    invShowEditor();
    data.items.forEach(item =>
        invAppendRow(item.item_name, 1, item.unit_price, item.discount_percentage, item.discount_flat, 0, !!item.non_discountable)
    );
}

// ── Invoice build / print ─────────────────────────────────────────────────────
function copyInvoiceJSON() {
    navigator.clipboard.writeText(JSON.stringify(buildInvoiceFromEditor(), null, 2))
        .then(() => alert('Invoice JSON copied to clipboard.'));
}

function buildInvoiceFromEditor() {
    const items = [];
    document.querySelectorAll('#invItemsBody tr').forEach(tr => {
        const name  = tr.querySelector('.inv-name')?.value  || '';
        const qty   = parseFloat(tr.querySelector('.inv-qty')?.value)   || 0;
        const price = parseFloat(tr.querySelector('.inv-price')?.value) || 0;
        const disc  = parseFloat(tr.querySelector('.inv-disc')?.value)  || 0;
        const flat  = parseFloat(tr.querySelector('.inv-flat')?.value)  || 0;
        const tax   = parseFloat(tr.querySelector('.inv-tax')?.value)   || 0;
        const base  = Math.max(0, (qty * price * (1 - disc / 100)) - flat);
        const sub   = base * (1 + tax / 100);
        items.push({ name, qty, unit_price: price, discount_percentage: disc, discount_flat: flat, tax_pct: tax, subtotal: sub });
    });
    const freight  = parseFloat(document.getElementById('invFreight').value) || 0;
    const subtotal = items.reduce((s, i) => s + i.subtotal, 0);
    return {
        submission_id:    invSubmissionId || undefined,
        customer_name:    document.getElementById('invCustomer').value,
        customer_email:   document.getElementById('invEmail').value,
        customer_address: document.getElementById('invAddress').value,
        estimate_no:      document.getElementById('invEstimateNo').value,
        issue_date:       document.getElementById('invDate').value,
        expiry_date:      document.getElementById('invExpiry').value,
        currency:         document.getElementById('invCurrency').value,
        status:           document.getElementById('invStatus').value,
        notes:            document.getElementById('invNotes').value,
        items, freight, subtotal,
        total: subtotal + freight,
    };
}

async function sendEstimateEmail() {
    // Must be saved first
    if (!invCurrentId) {
        const go = confirm('This estimate has not been saved yet.\n\nSave it now and then send the email?');
        if (!go) return;
        await saveEstimate();
        if (!invCurrentId) return; // save failed
    }

    const email = document.getElementById('invEmail').value.trim();
    if (!email) {
        alert('Please enter the customer email address before sending.');
        document.getElementById('invEmail').focus();
        return;
    }

    if (!confirm('Send this estimate by email to:\n' + email + '\n\nThis will also set the status to "Sent". Continue?')) return;

    const btn = document.getElementById('invSendEmailBtn');
    btn.disabled = true; btn.textContent = 'Sending…';

    try {
        const res = await fetch(API_BASE + '/wl/admin/estimates/' + invCurrentId + '/send', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-API-Key':API_KEY, 'Origin':ORIGIN, 'Authorization':'Bearer '+JWT },
            body: JSON.stringify({ base_url: window.location.origin }),
        });
        const rawText = await res.text();
        let data;
        try { data = JSON.parse(rawText); } catch (_) {
            alert('Unexpected server response:\n' + rawText.slice(0, 300));
            return;
        }
        if (!res.ok || !data.success) {
            alert('Send failed: ' + (data.error || 'Unknown error'));
            return;
        }
        // Update status in UI
        document.getElementById('invStatus').value = 'sent';
        // The offer is sent — discard the local draft and don't let the unload
        // handler resurrect it during the redirect below.
        _suppressDraft = true;
        clearDraft();
        clearChatHistory();
        // If this offer belongs to a deal, return to that deal so the sent offer
        // shows in the conversation thread; otherwise just confirm.
        if (invSubmissionId) {
            const _q2 = new URLSearchParams(window.location.search);
            const _cr = parseInt(_q2.get('CR_id') || '0', 10);
            alert('\u2713 Offer sent to ' + data.sent_to + '. Returning to the deal\u2026');
            window.location = '/deal?CR_id=' + _cr + '&submission_id=' + invSubmissionId;
            return;
        }
        alert('\u2713 Email sent to ' + data.sent_to);
    } catch (e) {
        alert('Network error: ' + e.message);
    } finally {
        btn.disabled = false; btn.textContent = 'Send Email';
    }
}

async function printInvoice() {
    const inv = buildInvoiceFromEditor();
    const cur = escHtml(inv.currency);
    const co  = invCompany || await invLoadCompany() || {};

    const statusColors = {
        draft: '#94a3b8', sent: '#3b82f6', accepted: '#16a34a',
        rejected: '#dc2626', expired: '#f59e0b'
    };
    const statusBadge = inv.status
        ? `<span style="border:1px solid ${statusColors[inv.status]||'#94a3b8'};color:${statusColors[inv.status]||'#94a3b8'};padding:2px 10px;border-radius:20px;font-size:12px;text-transform:capitalize">${escHtml(inv.status)}</span>`
        : '';

    const logoHtml = co.logo_url
        ? `<img src="${escHtml(co.logo_url)}" alt="logo" style="max-height:60px;max-width:180px;object-fit:contain">`
        : `<div style="font-size:22px;font-weight:700;color:#1e293b">${escHtml(co.name||'')}</div>`;

    const rows = inv.items.map(i => {
        const taxCell = i.tax_pct > 0 ? `${i.tax_pct}%` : '-';
        return `<tr>
            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0">${escHtml(i.name)}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;text-align:right">${cur}${i.unit_price.toFixed(2)}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;text-align:center">${i.qty}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;text-align:center">${taxCell}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;text-align:right">${cur}${i.subtotal.toFixed(2)}</td>
        </tr>`;
    }).join('');

    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html><html><head>
    <title>Estimate ${escHtml(inv.estimate_no||'')} - ${escHtml(inv.customer_name||'')}</title>
    <style>
      *{box-sizing:border-box;margin:0;padding:0}
      body{font-family:Arial,sans-serif;color:#334155;background:#fff;padding:50px;max-width:780px;margin:auto}
      .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px}
      .company-right{text-align:right;font-size:13px;line-height:1.7;color:#64748b}
      .company-right strong{font-size:16px;color:#1e293b;display:block}
      .divider{border:none;border-top:2px solid #3b82f6;margin:0 0 24px}
      .meta{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
      .billed-to{font-size:13px;line-height:1.7}
      .billed-to .label{font-size:10px;text-transform:uppercase;color:#94a3b8;letter-spacing:.5px;margin-bottom:2px}
      .billed-to .name{font-weight:600;color:#1e293b}
      .billed-to a{color:#3b82f6;text-decoration:none}
      .est-meta{font-size:13px;line-height:1.7;text-align:right}
      .est-meta .label{font-size:10px;text-transform:uppercase;color:#94a3b8;letter-spacing:.5px}
      .est-meta .value{color:#1e293b;font-weight:500}
      table{width:100%;border-collapse:collapse;font-size:13px}
      thead tr{border-bottom:2px solid #1e293b}
      th{padding:8px 10px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8}
      th.right{text-align:right}th.center{text-align:center}
      .summary{margin-top:20px;text-align:right;font-size:13px}
      .summary-row{display:flex;justify-content:flex-end;gap:40px;padding:3px 0;color:#64748b}
      .summary-row.total{font-size:16px;font-weight:700;color:#1e293b;border-top:2px solid #1e293b;padding-top:8px;margin-top:4px}
      .notes{margin-top:24px;font-size:12px;color:#94a3b8}
    </style>
    </head><body>
    <div class="header">
      <div>${logoHtml}<div style="margin-top:6px">${statusBadge}</div></div>
      <div class="company-right">
        <strong>${escHtml(co.name||'')}</strong>
        ${co.phone ? escHtml(co.phone)+'<br>' : ''}
        ${co.address ? escHtml(co.address).replace(/,/g,'<br>') : ''}
        ${co.website ? escHtml(co.website) : ''}
      </div>
    </div>
    <hr class="divider">

    <div style="text-align:right;font-size:28px;font-weight:700;letter-spacing:1px;color:#1e293b;margin-bottom:20px">ESTIMATE</div>

    <div class="meta">
      <div class="billed-to">
        <div class="label">Billed to</div>
        <div class="name">${escHtml(inv.customer_name||'-')}</div>
        ${inv.customer_email ? `<a href="mailto:${escHtml(inv.customer_email)}">${escHtml(inv.customer_email)}</a><br>` : ''}
        ${inv.customer_address ? escHtml(inv.customer_address) : ''}
      </div>
      <div class="est-meta">
        <div><span class="label">Estimate No</span><br><span class="value">${escHtml(inv.estimate_no||'-')}</span></div>
        <div style="margin-top:8px"><span class="label">Issue Date</span><br><span class="value">${escHtml(inv.issue_date||'-')}</span></div>
        ${inv.expiry_date ? `<div style="margin-top:8px"><span class="label">Expiry Date</span><br><span class="value">${escHtml(inv.expiry_date)}</span></div>` : ''}
      </div>
    </div>

    <table>
      <thead><tr>
        <th>Item Name</th>
        <th class="right">Price</th>
        <th class="center">QTY</th>
        <th class="center">TAX</th>
        <th class="right">Subtotal</th>
      </tr></thead>
      <tbody>${rows}</tbody>
    </table>

    <div class="summary">
      <div class="summary-row"><span>Subtotal</span><span>${cur}${inv.subtotal.toFixed(2)}</span></div>
      ${inv.freight > 0 ? `<div class="summary-row"><span>Freight</span><span>${cur}${inv.freight.toFixed(2)}</span></div>` : ''}
      <div class="summary-row total"><span>Amount Due (${cur.replace('€','EUR').replace('$','USD')})</span><span>${cur}${inv.total.toFixed(2)}</span></div>
    </div>
    ${inv.notes ? `<div class="notes">${escHtml(inv.notes)}</div>` : ''}
    </body></html>`);
    win.document.close();
    win.print();
}

async function saveEstimate() {
    const inv = buildInvoiceFromEditor();
    const btn = document.getElementById('invSaveBtn');
    btn.disabled = true; btn.textContent = 'Saving…';

    try {
        const method = invCurrentId ? 'PUT' : 'POST';
        const saveUrl = API_BASE + '/wl/admin/estimates' + (invCurrentId ? '/' + invCurrentId : '');
        const res = await fetch(saveUrl, {
            method,
            headers: { 'Content-Type':'application/json', 'X-API-Key':API_KEY, 'Origin':ORIGIN, 'Authorization':'Bearer '+JWT },
            body: JSON.stringify(inv),
        });

        // Read body as text first to avoid JSON parse crash
        const rawText = await res.text();
        let data;
        try { data = JSON.parse(rawText); }
        catch (_) {
            alert('Server returned unexpected response (HTTP ' + res.status + '):\n' + rawText.slice(0, 300));
            return;
        }

        if (!res.ok || !data.success) { alert('Save failed: ' + (data.error || 'Unknown error')); return; }

        // Safely persisted in the DB now — drop the local draft.
        clearDraft();

        if (!invCurrentId) {
            invCurrentId = data.est_id;
            const shareUrl = window.location.origin + '/estimate/' + data.token;
            document.getElementById('invEstimateNo').value = data.estimate_no;
            if (confirm('Estimate saved! ✓\n\nClient link (copy to share with customer):\n' + shareUrl + '\n\nCopy to clipboard?')) {
                navigator.clipboard.writeText(shareUrl).catch(() => {});
            }
        } else {
            alert('Estimate updated ✓');
        }
    } catch (e) {
        alert('Network error: ' + e.message);
    } finally {
        btn.disabled = false; btn.textContent = 'Save';
    }
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
