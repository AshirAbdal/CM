<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
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

<!-- Dialog-style offer builder -->
<div class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm" aria-hidden="true"></div>

<div class="relative z-50 mx-auto w-full max-w-6xl px-1 sm:px-2">
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
            <div class="flex flex-col gap-8">

            <!-- ══ Invoice Preview (below) ══════════════════════════════════ -->
            <div class="flex flex-col order-2" style="min-height:600px;">
                <div class="mb-4 flex items-start justify-between flex-wrap gap-3">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Invoice Preview</h2>
                        <p class="text-slate-500 mt-1 text-sm">Auto-filled by AI · all fields are editable</p>
                    </div>
                    <div class="flex gap-2" id="invoiceActions" style="display:none!important;">
                        <button onclick="clearInvoice()" class="text-xs border border-slate-200 text-slate-500 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition">Clear</button>
                        <button onclick="printInvoice()" class="text-xs bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">Print / Save PDF</button>
                    </div>
                </div>

                <!-- Restored-draft banner -->
                <div id="invDraftBanner" class="hidden mb-3 flex items-center justify-between gap-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-3 py-2 text-sm">
                    <span>&#8617; <span id="invDraftMsg">Unsaved draft restored.</span></span>
                    <button type="button" onclick="discardDraft()" class="text-xs px-2.5 py-1 rounded-md border border-amber-300 text-amber-700 hover:bg-amber-100 transition whitespace-nowrap">Discard draft</button>
                </div>

                <!-- Revising-a-prior-offer banner -->
                <div id="invReviseBanner" class="hidden mb-3 flex items-start gap-2 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-lg px-3 py-2 text-sm">
                    <span class="shrink-0">&#128221;</span>
                    <span id="invReviseMsg">Revising a previous offer.</span>
                </div>
                <!-- Autosave status -->
                <div id="invDraftStatus" class="hidden mb-2 text-[11px] text-slate-400"></div>

                <!-- Xero connection notice -->
                <div id="invXeroNotice" class="hidden mb-3 flex items-center justify-between gap-3 bg-sky-50 border border-sky-200 text-sky-800 rounded-lg px-3 py-2 text-sm">
                    <span>Xero is not connected — accepted offers won't be sent to Xero automatically.</span>
                    <a href="/xero" class="text-xs px-2.5 py-1 rounded-md border border-sky-300 text-sky-700 hover:bg-sky-100 transition whitespace-nowrap">Connect Xero</a>
                </div>

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
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">Due Date</span>
                                    <input id="invDueDate" type="date"
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
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">Amounts are</span>
                                    <select id="invLineAmountType" onchange="invRecalc()"
                                            class="border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-0.5 text-right font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        <option value="Exclusive">Tax Exclusive</option>
                                        <option value="Inclusive">Tax Inclusive</option>
                                        <option value="NoTax">No Tax</option>
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
                                    <tr class="border-b-2 border-slate-800 text-xs text-slate-500 uppercase tracking-wide">
                                        <th class="pb-2 text-left font-semibold">Item Name</th>
                                        <th class="pb-2 text-right font-semibold w-28">Price</th>
                                        <th class="pb-2 text-center font-semibold w-20">QTY</th>
                                        <th class="pb-2 text-center font-semibold w-24">TAX</th>
                                        <th class="pb-2 text-right font-semibold w-32">Subtotal</th>
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

            <!-- ══ AI Chat (top) ════════════════════════════════════════════════════ -->
            <div class="flex flex-col order-1" style="height:520px;">
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
                        <!-- Attached transcript / document chip -->
                        <div id="chatAttachment" class="hidden mb-2 flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-sm">
                            <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span id="chatAttachmentName" class="text-slate-700 font-medium truncate"></span>
                            <span id="chatAttachmentSize" class="text-slate-400 text-xs flex-shrink-0"></span>
                            <button type="button" onclick="clearChatAttachment()" title="Remove file" class="ml-auto text-slate-400 hover:text-red-500 transition flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <!-- Voice listening indicator -->
                        <div id="chatVoiceHint" class="hidden mb-2 items-center gap-2 text-xs text-red-500">
                            <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span></span>
                            Recording… speak now, tap the mic again to send.
                        </div>
                        <div class="flex gap-2 items-end">
                            <input type="file" id="chatFileInput" class="hidden"
                                   accept=".txt,.text,.md,.markdown,.vtt,.srt,.csv,.log,.json,.pdf,.wav,.mp3,.m4a,.aac,.aiff,.ogg,.flac,text/plain,application/pdf,text/csv,text/markdown,audio/*"
                                   onchange="onChatFileSelected(event)">
                            <button type="button" id="attachBtn" title="Attach a meeting transcript (.txt, .vtt, .srt, .csv, .md, .pdf)"
                                    onclick="document.getElementById('chatFileInput').click()"
                                    class="flex-shrink-0 w-10 h-[38px] flex items-center justify-center border border-slate-200 rounded-lg text-slate-500 hover:text-primary hover:border-primary transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            </button>
                            <button type="button" id="micBtn" title="Dictate with your voice"
                                    onclick="toggleVoiceInput()"
                                    class="flex-shrink-0 w-10 h-[38px] flex items-center justify-center border border-slate-200 rounded-lg text-slate-500 hover:text-primary hover:border-primary transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-14 0m7 7v4m-4 0h8M12 1a3 3 0 00-3 3v7a3 3 0 006 0V4a3 3 0 00-3-3z"/></svg>
                            </button>
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
                        <p class="text-xs text-slate-400 mt-1.5">Powered by Gemini AI · Attach a meeting transcript or dictate with the mic · <span class="text-slate-300">Shift+Enter for new line</span></p>
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

// ── Xero organisation metadata (accounts / tax rates / currencies) ────────────
// Populated from the connected Xero org so each invoice line carries a valid
// account code + tax type and nothing is left blank when pushed to Xero.
let xeroMeta = { connected:false, accounts:[], tax_rates:[], currencies:[], default_account_code:'200' };

async function loadXeroMeta() {
    try {
        const res = await fetch(API_BASE + '/wl/admin/xero/meta', {
            headers: { 'X-API-Key':API_KEY, 'Origin':ORIGIN, 'Authorization':'Bearer '+JWT },
        });
        if (res.ok) {
            const j = await res.json();
            xeroMeta.connected  = !!j.connected;
            xeroMeta.accounts   = j.accounts   || [];
            xeroMeta.tax_rates  = j.tax_rates  || [];
            xeroMeta.currencies = j.currencies || [];
            if (j.default_account_code) xeroMeta.default_account_code = j.default_account_code;
        }
    } catch (_) { /* offline / not connected — the editor still works */ }
    applyXeroMetaToEditor();
}

function xeroAccountOptionsHtml(selected) {
    const accts = xeroMeta.accounts || [];
    if (!accts.length) return '<option value="">Default</option>';
    let chosen = String(selected || '');
    if (!chosen) {
        const def = String(xeroMeta.default_account_code || '');
        chosen = accts.some(a => String(a.code) === def) ? def : String(accts[0].code);
    }
    return accts.map(a => {
        const sel = (String(a.code) === chosen) ? ' selected' : '';
        return `<option value="${escHtml(a.code)}"${sel}>${escHtml(a.code)} · ${escHtml(a.name)}</option>`;
    }).join('');
}

function xeroTaxOptionsHtml(selected) {
    const sel0 = selected ? '' : ' selected';
    let html = `<option value="" data-rate=""${sel0}>Account default</option>`;
    (xeroMeta.tax_rates || []).forEach(t => {
        const sel = (String(t.type) === String(selected)) ? ' selected' : '';
        html += `<option value="${escHtml(t.type)}" data-rate="${t.rate}"${sel}>${escHtml(t.name)} (${t.rate}%)</option>`;
    });
    return html;
}

// Reflect the loaded Xero metadata into the currency picker, the per-line
// Account/Tax selectors, and the "not connected" notice.
function applyXeroMetaToEditor() {
    if (xeroMeta.connected && xeroMeta.currencies.length) {
        const sel  = document.getElementById('invCurrency');
        const prev = sel.value;
        sel.innerHTML = xeroMeta.currencies
            .map(c => `<option value="${escHtml(c.code)}">${escHtml(c.code)}</option>`).join('');
        if ([...sel.options].some(o => o.value === prev)) sel.value = prev;
    }
    const notice = document.getElementById('invXeroNotice');
    if (notice) notice.classList.toggle('hidden', !!xeroMeta.connected);

    document.querySelectorAll('#invItemsBody tr').forEach(tr => {
        const wrap = tr.querySelector('.inv-xero-wrap');
        if (!wrap) return;
        wrap.classList.toggle('hidden', !xeroMeta.connected);
        const acc  = tr.querySelector('.inv-account');
        const taxT = tr.querySelector('.inv-taxtype');
        if (acc)  acc.innerHTML  = xeroAccountOptionsHtml(acc.value);
        if (taxT) taxT.innerHTML = xeroTaxOptionsHtml(taxT.value);
    });
    invRecalc();
}

// Tax mode (header) decides whether a line's tax % is added on top (Exclusive),
// already included (Inclusive) or ignored (NoTax) — keeps the preview in step
// with how Xero will compute the amount due.
function invTaxMultiplier(taxPct) {
    const mode = document.getElementById('invLineAmountType')?.value || 'Exclusive';
    return mode === 'Exclusive' ? (1 + (taxPct / 100)) : 1;
}

// When a Xero tax type is picked, mirror its rate into the line's tax % so the
// preview total matches the invoice Xero will generate.
function invTaxTypeChange(sel) {
    const tr = sel.closest('tr');
    if (!tr) return;
    const opt  = sel.options[sel.selectedIndex];
    const rate = opt ? parseFloat(opt.getAttribute('data-rate')) : NaN;
    const taxInput = tr.querySelector('.inv-tax');
    if (taxInput && !isNaN(rate)) taxInput.value = Number(rate).toFixed(1);
    invRecalc();
    scheduleDraftSave();
}

// ── AI Chatbot ────────────────────────────────────────────────────────────────
let chatHistory    = [];
let currentInvoice = null;

// Attached meeting transcript / document for the next AI message, plus voice state.
let attachedFile   = null;  // { name, mime_type, data(base64), size }
let mediaRecorder  = null;  // MediaRecorder instance while recording
let mediaStream    = null;  // active getUserMedia stream (so we can stop tracks)
let recChunks      = [];    // recorded audio Blob parts
let recording      = false; // currently capturing audio
let autoSend       = false; // send the message as soon as the clip is ready
let voiceSupported = false; // MediaRecorder + getUserMedia + AudioContext present

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

// ── Transcript / document upload ────────────────────────────────────────
const CHAT_FILE_MAX = 8 * 1024 * 1024; // 8 MB
const CHAT_FILE_EXT = ['txt','text','md','markdown','vtt','srt','csv','log','json','pdf','wav','mp3','mpga','m4a','aac','aiff','aif','ogg','oga','flac'];

function formatBytes(n) {
    if (n < 1024) return n + ' B';
    if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
    return (n / 1048576).toFixed(1) + ' MB';
}

function onChatFileSelected(event) {
    const file = event.target.files && event.target.files[0];
    event.target.value = '';                       // allow re-picking the same file later
    if (!file) return;
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if (!CHAT_FILE_EXT.includes(ext)) {
        appendBubble('model', '⚠️ Unsupported file type. Upload a transcript (.txt, .vtt, .srt, .csv, .md, .json), an audio clip (.wav, .mp3, .m4a, .ogg), or a PDF.');
        return;
    }
    if (file.size > CHAT_FILE_MAX) {
        appendBubble('model', '⚠️ That file is too large (max 8 MB).');
        return;
    }
    const reader = new FileReader();
    reader.onload = () => {
        const base64 = String(reader.result).split(',')[1] || '';  // strip data: prefix
        attachedFile = {
            name: file.name,
            mime_type: file.type || (ext === 'pdf' ? 'application/pdf' : 'text/plain'),
            data: base64,
            size: file.size,
        };
        document.getElementById('chatAttachmentName').textContent = file.name;
        document.getElementById('chatAttachmentSize').textContent = formatBytes(file.size);
        document.getElementById('chatAttachment').classList.remove('hidden');
    };
    reader.onerror = () => appendBubble('model', '⚠️ Could not read that file. Please try again.');
    reader.readAsDataURL(file);
}

function clearChatAttachment() {
    attachedFile = null;
    const chip = document.getElementById('chatAttachment');
    if (chip) chip.classList.add('hidden');
    const inp = document.getElementById('chatFileInput');
    if (inp) inp.value = '';
}

// ── Voice capture (MediaRecorder -> 16kHz mono WAV -> Gemini audio) ───────────────────────────────────
function setupVoiceCapture() {
    voiceSupported = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia
        && window.MediaRecorder && (window.AudioContext || window.webkitAudioContext));
    const micBtn = document.getElementById('micBtn');
    if (!voiceSupported && micBtn) {
        micBtn.classList.add('opacity-50');
        micBtn.title = 'Voice recording is not available in this browser';
    }
}

async function toggleVoiceInput() {
    if (!voiceSupported) {
        appendBubble('model', '⚠️ Voice recording is not available in this browser. Please type your request or attach a transcript instead.');
        return;
    }
    if (recording) { stopRecording(true); return; }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        startRecording(stream);
    } catch (err) {
        const name = (err && err.name) ? err.name : '';
        if (name === 'NotAllowedError' || name === 'SecurityError') {
            appendBubble('model', '⚠️ Microphone access is blocked. Allow mic permission for this site in your browser, then tap the mic again.');
        } else if (name === 'NotFoundError') {
            appendBubble('model', '⚠️ No microphone was found. Plug one in or check your system settings, then try again.');
        } else {
            appendBubble('model', '⚠️ Could not start recording. Please try again.');
        }
    }
}

function startRecording(stream) {
    mediaStream = stream;
    recChunks   = [];
    try {
        mediaRecorder = new MediaRecorder(stream);
    } catch (_) {
        mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
    }
    mediaRecorder.ondataavailable = (e) => { if (e.data && e.data.size) recChunks.push(e.data); };
    mediaRecorder.onstop = handleRecordingStop;
    mediaRecorder.start();
    recording = true;
    setVoiceUI(true);
}

function stopRecording(doSend) {
    autoSend  = !!doSend;
    recording = false;
    setVoiceUI(false);
    try { if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop(); } catch (_) {}
    if (mediaStream) { mediaStream.getTracks().forEach(t => t.stop()); mediaStream = null; }
}

function setVoiceUI(on) {
    const micBtn = document.getElementById('micBtn');
    const hint   = document.getElementById('chatVoiceHint');
    if (micBtn) {
        micBtn.classList.toggle('text-red-500', on);
        micBtn.classList.toggle('border-red-300', on);
        micBtn.classList.toggle('bg-red-50', on);
        micBtn.classList.toggle('text-slate-500', !on);
    }
    if (hint) { hint.classList.toggle('hidden', !on); hint.classList.toggle('flex', on); }
}

async function handleRecordingStop() {
    const send = autoSend; autoSend = false;
    if (!recChunks.length) return;
    const blob = new Blob(recChunks, { type: (mediaRecorder && mediaRecorder.mimeType) || 'audio/webm' });
    recChunks = [];
    let wavB64;
    try {
        wavB64 = await blobToWavBase64(blob);
    } catch (_) {
        appendBubble('model', '⚠️ Could not process the recording. Please try again.');
        return;
    }
    const bytes = Math.floor(wavB64.length * 3 / 4);
    if (bytes > CHAT_FILE_MAX) {
        appendBubble('model', '⚠️ That recording is too long (max about 4 minutes). Please record a shorter message.');
        return;
    }
    attachedFile = { name: 'voice-message.wav', mime_type: 'audio/wav', data: wavB64, size: bytes };
    showAttachmentChip('Voice message', bytes);
    if (send) sendMessage();
}

function showAttachmentChip(label, bytes) {
    document.getElementById('chatAttachmentName').textContent = label;
    document.getElementById('chatAttachmentSize').textContent = formatBytes(bytes);
    document.getElementById('chatAttachment').classList.remove('hidden');
}

// Decode the recorded clip, downmix to mono and resample to 16kHz, then encode
// a standard 16-bit PCM WAV - the format Gemini transcribes most reliably.
async function blobToWavBase64(blob) {
    const arrayBuf = await blob.arrayBuffer();
    const AC  = window.AudioContext || window.webkitAudioContext;
    const ac  = new AC();
    const decoded = await decodeAudio(ac, arrayBuf);
    try { ac.close(); } catch (_) {}
    const targetRate = 16000;
    const OAC = window.OfflineAudioContext || window.webkitOfflineAudioContext;
    const frames = Math.max(1, Math.ceil(decoded.duration * targetRate));
    const off = new OAC(1, frames, targetRate);
    const src = off.createBufferSource();
    src.buffer = decoded;
    src.connect(off.destination);
    src.start(0);
    const rendered = await off.startRendering();
    const wav = encodeWav(rendered.getChannelData(0), targetRate);
    return arrayBufferToBase64(wav);
}

function decodeAudio(ctx, arrayBuf) {
    return new Promise((resolve, reject) => {
        const p = ctx.decodeAudioData(arrayBuf, resolve, reject);
        if (p && typeof p.then === 'function') p.then(resolve, reject);
    });
}

function encodeWav(samples, sampleRate) {
    const buffer = new ArrayBuffer(44 + samples.length * 2);
    const view   = new DataView(buffer);
    const writeStr = (off, str) => { for (let i = 0; i < str.length; i++) view.setUint8(off + i, str.charCodeAt(i)); };
    writeStr(0, 'RIFF');
    view.setUint32(4, 36 + samples.length * 2, true);
    writeStr(8, 'WAVE');
    writeStr(12, 'fmt ');
    view.setUint32(16, 16, true);             // PCM chunk size
    view.setUint16(20, 1, true);              // audio format = PCM
    view.setUint16(22, 1, true);              // mono
    view.setUint32(24, sampleRate, true);
    view.setUint32(28, sampleRate * 2, true); // byte rate
    view.setUint16(32, 2, true);              // block align
    view.setUint16(34, 16, true);             // bits per sample
    writeStr(36, 'data');
    view.setUint32(40, samples.length * 2, true);
    let off = 44;
    for (let i = 0; i < samples.length; i++, off += 2) {
        const s = Math.max(-1, Math.min(1, samples[i]));
        view.setInt16(off, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
    }
    return buffer;
}

function arrayBufferToBase64(buf) {
    const bytes = new Uint8Array(buf);
    let binary = '';
    const chunk = 0x8000;
    for (let i = 0; i < bytes.length; i += chunk) {
        binary += String.fromCharCode.apply(null, bytes.subarray(i, i + chunk));
    }
    return btoa(binary);
}

async function sendMessage() {
    const input   = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const sendIcon= document.getElementById('sendIcon');
    const loadIcon= document.getElementById('loadingIcon');
    const message = input.value.trim();
    const fileToSend = attachedFile;             // capture the attached transcript (if any)
    if (!message && !fileToSend) return;
    if (recording) { stopRecording(false); }

    // Show the user's turn (with the attached filename, if any)
    const fileLabel  = fileToSend ? ((fileToSend.mime_type || '').indexOf('audio/') === 0 ? '🎤 Voice message' : '📎 ' + fileToSend.name) : '';
    const bubbleText = (message || '') + (fileToSend ? (message ? '\n' : '') + fileLabel : '');
    appendBubble('user', bubbleText);
    input.value = '';
    input.style.height = '38px';
    clearChatAttachment();                       // chip clears as soon as it's sent
    sendBtn.disabled = true;
    sendIcon.classList.add('hidden');
    loadIcon.classList.remove('hidden');

    // Client-side safety cap so a stuck request surfaces a clear timeout message
    // instead of spinning forever.
    const _ctrl = new AbortController();
    const _timeoutId = setTimeout(() => _ctrl.abort(), 90000);
    try {
        const payload = { message, history: chatHistory };
        // Send the invoice the admin is currently working on so the AI edits it
        // (adds/removes/changes items) instead of rebuilding from scratch and
        // wiping the existing line items.
        const _curInv = buildInvoiceFromEditor();
        if (_curInv && Array.isArray(_curInv.items) && _curInv.items.length) {
            payload.current_invoice = _curInv;
        }
        if (fileToSend) payload.file = { name: fileToSend.name, mime_type: fileToSend.mime_type, data: fileToSend.data };
        const res = await fetch(API_BASE + '/wl/admin/products/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT },
            body: JSON.stringify(payload),
            signal: _ctrl.signal,
        });
        let data = {};
        try { data = await res.json(); } catch (_) { data = {}; }
        if (!res.ok) { appendBubble('model', '⚠️ ' + (data.error || chatErrorForStatus(res.status))); return; }
        chatHistory.push({ role: 'user', text: bubbleText });
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
            // The AI invoice usually only describes line items, so it omits the
            // "Billed to" details. Carry over whatever the admin already entered.
            const curName  = document.getElementById('invCustomer').value;
            const curEmail = document.getElementById('invEmail').value;
            const curAddr  = document.getElementById('invAddress').value;
            if (invSubmissionId && invSubmissionId > 0) {
                // Opened from a deal: the lead is authoritative. The AI must never
                // overwrite the "Billed to" name/email/address.
                if (curName)  invoice.customer_name    = curName;
                if (curEmail) invoice.customer_email   = curEmail;
                if (curAddr)  invoice.customer_address = curAddr;
            } else {
                // No deal: keep the admin's entry and drop any placeholder name
                // the model may have invented.
                if (!invoice.customer_name || isPlaceholderName(invoice.customer_name))
                                               invoice.customer_name    = curName;
                if (!invoice.customer_email)   invoice.customer_email   = curEmail;
                if (!invoice.customer_address) invoice.customer_address = curAddr;
            }
            // The AI only returns name/qty/price per line. For items that were
            // already on the invoice, carry over the richer fields it does not
            // echo back (per-line discounts and the Xero account / tax codes) so
            // an edit does not silently reset them.
            const _norm = s => (s || '').trim().toLowerCase().replace(/\s+/g, ' ');
            const _prevByName = {};
            (_curInv.items || []).forEach(it => {
                const k = _norm(it.name);
                if (k) _prevByName[k] = it;
            });
            (invoice.items || []).forEach(it => {
                const prev = _prevByName[_norm(it.name)];
                if (!prev) return;
                if (it.discount_percentage == null) it.discount_percentage = prev.discount_percentage;
                if (it.discount_flat == null)       it.discount_flat       = prev.discount_flat;
                if (it.tax_pct == null)             it.tax_pct             = prev.tax_pct;
                if (!it.account_code)               it.account_code        = prev.account_code;
                if (!it.tax_type)                   it.tax_type            = prev.tax_type;
                if (it.non_discountable == null)    it.non_discountable    = prev.non_discountable;
            });
            currentInvoice = invoice;
            loadInvoiceIntoEditor(invoice);
        }
    } catch (err) {
        if (err && err.name === 'AbortError') {
            appendBubble('model', '⚠️ The assistant took too long to respond. Please try again, or ask for a smaller invoice.');
        } else {
            appendBubble('model', '⚠️ Could not reach the assistant. Please check your connection and try again.');
        }
    } finally {
        clearTimeout(_timeoutId);
        sendBtn.disabled = false;
        sendIcon.classList.remove('hidden');
        loadIcon.classList.add('hidden');
    }
}

// Friendly fallback message for an HTTP error status when the backend did not
// return its own { error } text.
function chatErrorForStatus(status) {
    switch (status) {
        case 401:
        case 403: return 'Your session has expired. Please sign in again.';
        case 413: return 'That attachment is too large. Please use a file under 8 MB.';
        case 415: return 'That file type is not supported. Attach a transcript (.txt, .vtt, .srt, .csv, .md, .json), a voice recording, or a PDF.';
        case 422: return 'The assistant could not process that request. Please rephrase and try again.';
        case 429: return 'The assistant is busy right now (usage limit reached). Please wait a moment and try again.';
        case 500: return 'Something went wrong on the server. Please try again.';
        case 503: return 'The assistant is temporarily unavailable. Please try again in a few seconds.';
        case 504: return 'The assistant took too long to respond. Please try again, or ask for a smaller invoice.';
        default:  return 'Something went wrong. Please try again.';
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
            <div class="bg-slate-100 rounded-2xl rounded-tl-sm px-4 py-3 max-w-sm text-sm text-slate-700 whitespace-pre-wrap">${escHtml(mdToPlain(text))}</div>`;
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
    document.getElementById('invDueDate').value     = inv.due_date         || '';
    document.getElementById('invNotes').value       = inv.notes            || '';
    document.getElementById('invFreight').value     = (inv.freight ?? 0).toFixed(2);
    const cur = document.getElementById('invCurrency');
    [...cur.options].forEach(o => o.selected = o.value === (inv.currency || 'EUR'));
    const _lat = document.getElementById('invLineAmountType');
    if (_lat && inv.line_amount_type) _lat.value = inv.line_amount_type;
    if (inv.status) document.getElementById('invStatus').value = inv.status;
    updateInvStatusBadge();
    const tbody = document.getElementById('invItemsBody');
    tbody.innerHTML = '';
    (inv.items || []).forEach(item => invAppendRow(item.name, item.qty, item.unit_price, item.discount_percentage ?? 0, item.discount_flat ?? 0, item.tax_pct ?? 0, item.non_discountable ?? false, item.account_code ?? '', item.tax_type ?? ''));
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

// ── Revise a previously saved/sent offer ──────────────────────────────────────
// When the page is opened with ?est_id=, load that estimate's content back into
// the editor so the admin can adjust it (e.g. after the lead asked for a change)
// and send a revised offer. The original offer is NOT modified: we deliberately
// leave invCurrentId null so saving creates a new offer version, regenerate the
// estimate number, reset the status to draft and refresh the dates. The prior
// offer stays on record and the pipeline advances on the next send.
async function reviseFromEstimate(estId) {
    try {
        const res = await fetch(API_BASE + '/wl/admin/estimates/' + estId, {
            headers: { 'X-API-Key':API_KEY, 'Origin':ORIGIN, 'Authorization':'Bearer '+JWT },
        });
        if (!res.ok) return false;
        const e = await res.json();
        if (!e || !Array.isArray(e.items)) return false;

        loadInvoiceIntoEditor({
            customer_name:    e.customer_name,
            customer_email:   e.customer_email,
            customer_address: e.customer_address,
            estimate_no:      '',
            currency:         e.currency,
            line_amount_type: e.line_amount_type,
            notes:            e.notes,
            freight:          e.freight,
            items:            e.items,
            status:           'draft',
        });

        // Fresh document identity + dates for the revised offer.
        document.getElementById('invEstimateNo').value = 'EST-' + Date.now().toString().slice(-4);
        document.getElementById('invDate').value = new Date().toISOString().slice(0,10);
        syncExpiryFromIssue();
        const due = new Date(); due.setDate(due.getDate() + 14);
        document.getElementById('invDueDate').value = due.toISOString().slice(0,10);
        invCurrentId = null; // save -> POST a brand-new offer version

        const banner = document.getElementById('invReviseBanner');
        const msg    = document.getElementById('invReviseMsg');
        if (msg) {
            const priorNo = (e.estimate_no || '').trim();
            let html = 'Revising ' + (priorNo ? '<strong>' + escHtml(priorNo) + '</strong>' : 'a previous offer')
                     + '. Saving or sending creates a new offer version; the original stays on record.';
            const cr = (e.change_request || '').trim();
            if (cr) {
                html += '<br><span class="text-[12px] text-indigo-600">Lead\u2019s change request: '
                      + escHtml(cr) + '</span>';
            }
            msg.innerHTML = html;
        }
        if (banner) banner.classList.remove('hidden');
        return true;
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
    setupVoiceCapture();
    document.getElementById('invDate').value = new Date().toISOString().slice(0,10);
    // Expiry is 5 working days after the issue date (skips weekends); it also
    // recomputes whenever the admin changes the issue date.
    syncExpiryFromIssue();
    document.getElementById('invDate').addEventListener('change', syncExpiryFromIssue);
    // Default due date 14 days out (Xero invoices require a due date)
    const _due = new Date(); _due.setDate(_due.getDate() + 14);
    document.getElementById('invDueDate').value = _due.toISOString().slice(0,10);
    // Auto-assign estimate number
    document.getElementById('invEstimateNo').value = 'EST-' + Date.now().toString().slice(-4);
    invShowEditor();

    // Load the connected Xero org's accounts / tax rates / currencies so the
    // per-line Account + Tax selectors are populated before any rows render.
    await loadXeroMeta();

    // When opened from a deal (Lead Management → New offer), link this offer to
    // that deal and prefill the customer details.
    const _q = new URLSearchParams(window.location.search);
    const _sid = parseInt(_q.get('submission_id') || '0', 10);
    if (_sid > 0) {
        invSubmissionId = _sid;
        // Opened from a deal: prefill the "Billed to" details from the lead
        // (name/email, plus address from Apollo.io when present). These remain
        // editable so the admin can adjust them for this offer.
        const _prefillFromDeal = (id, val) => {
            const el = document.getElementById(id);
            if (el && val) el.value = val;
        };
        _prefillFromDeal('invCustomer', _q.get('name'));
        _prefillFromDeal('invEmail',    _q.get('email'));
        _prefillFromDeal('invAddress',  _q.get('address'));
        const banner = document.getElementById('invDealBanner');
        if (banner) { banner.classList.remove('hidden'); }
    }

    // Restore any in-progress work for this deal. For a real deal we prefer the
    // server copy (follows the admin across devices) and fall back to the local
    // browser copy if there's nothing saved server-side or the request fails.
    // The "new" scope (no deal yet) is browser-only.
    //
    // Exception: when opened with ?est_id= the admin explicitly chose to revise a
    // specific prior offer, so that wins over any stale autosaved draft.
    let _restored = false;
    const _estId = parseInt(_q.get('est_id') || '0', 10);
    if (_estId > 0) {
        _restored = await reviseFromEstimate(_estId);
    }
    if (!_restored && _hasServerScope()) {
        _restored = await restoreOfferDraftServer();
    }
    if (!_restored) {
        maybeRestoreDraft();
        restoreChatHistory();
    }

    // The lead's contact details prefill the "Billed to". After a draft restore,
    // only backfill fields that ended up empty so the admin's own edits (saved in
    // the draft) are preserved.
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

function invAppendRow(name = '', qty = 1, price = 0, disc = 0, flat = 0, tax = 0, nonDisc = false, accountCode = '', taxType = '') {
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
            <div class="inv-xero-wrap ${xeroMeta.connected ? '' : 'hidden'} flex flex-wrap items-end gap-3 mt-1.5 pl-2">
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Account (Xero)</label>
                    <select onchange="scheduleDraftSave()" class="inv-account border border-slate-200 rounded px-1.5 py-0.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500 max-w-[220px]">${xeroAccountOptionsHtml(accountCode)}</select>
                </div>
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Tax (Xero)</label>
                    <select onchange="invTaxTypeChange(this)" class="inv-taxtype border border-slate-200 rounded px-1.5 py-0.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500 max-w-[220px]">${xeroTaxOptionsHtml(taxType)}</select>
                </div>
            </div>
        </td>
        <td class="py-2 pr-3 w-28">
            <input type="number" value="${Number(price).toFixed(2)}" min="0" step="0.01" oninput="invRecalc()"
                   class="inv-price w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1.5 text-[15px] text-right focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td class="py-2 pr-3 w-20">
            <input type="number" value="${qty}" min="0" oninput="invRecalc()"
                   class="inv-qty w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1.5 text-[15px] text-center focus:outline-none focus:ring-1 focus:ring-blue-500">
        </td>
        <td class="py-2 pr-3 w-24">
            <div class="relative">
                <input type="number" value="${Number(tax).toFixed(1)}" min="0" max="100" step="0.1" oninput="invRecalc()"
                       class="inv-tax w-full border border-transparent hover:border-slate-200 focus:border-blue-500 rounded px-2 py-1.5 text-[15px] text-center focus:outline-none focus:ring-1 focus:ring-blue-500 pr-6">
                <span class="absolute right-2 top-2 text-slate-400 text-xs pointer-events-none">%</span>
            </div>
        </td>
        <td class="py-2 w-32 text-right text-[15px] text-slate-800 inv-sub font-semibold whitespace-nowrap pt-2.5"></td>
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
        const sub   = base * invTaxMultiplier(tax);
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
    syncExpiryFromIssue();
    const due = new Date(); due.setDate(due.getDate() + 14);
    document.getElementById('invDueDate').value     = due.toISOString().slice(0,10);
    document.getElementById('invLineAmountType').value = 'Exclusive';
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
        const sub   = base * invTaxMultiplier(tax);
        const account_code = tr.querySelector('.inv-account')?.value || '';
        const tax_type     = tr.querySelector('.inv-taxtype')?.value || '';
        items.push({ name, qty, unit_price: price, discount_percentage: disc, discount_flat: flat, tax_pct: tax, account_code, tax_type, subtotal: sub });
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
        due_date:         document.getElementById('invDueDate').value,
        currency:         document.getElementById('invCurrency').value,
        line_amount_type: document.getElementById('invLineAmountType').value,
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
        const discPct  = Number(i.discount_percentage) || 0;
        const discFlat = Number(i.discount_flat) || 0;
        const hasDisc  = discPct > 0 || discFlat > 0;
        const discCell = hasDisc
            ? [discPct > 0 ? `${discPct}%` : null, discFlat > 0 ? `${cur}${discFlat.toFixed(2)}` : null].filter(Boolean).join(' + ')
            : '-';
        return `<tr>
            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0">${escHtml(i.name)}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;text-align:right">${cur}${i.unit_price.toFixed(2)}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;text-align:center">${i.qty}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;text-align:center${hasDisc ? ';color:#16a34a' : ''}">${discCell}</td>
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
        <th class="center">Discount</th>
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
            let msg = 'Estimate updated ✓';
            if (data.xero) {
                if (data.xero.ok && data.xero.invoice_number) {
                    msg += '\n\nXero invoice ' + data.xero.invoice_number + ' created and emailed.';
                } else if (data.xero.reason === 'already_invoiced') {
                    msg += '\n\nXero invoice ' + (data.xero.invoice_number || '') + ' already exists for this offer.';
                } else if (data.xero.reason === 'not_connected') {
                    msg += '\n\nNote: Xero is not connected, so no invoice was created.';
                } else if (!data.xero.ok) {
                    msg += '\n\nXero invoice could NOT be created: ' + (data.xero.message || data.xero.reason || 'unknown error');
                }
            }
            alert(msg);
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

// Add N working days (Mon-Fri, skipping weekends) to a Date and return a new
// Date. Used for the estimate expiry, which is 5 working days from the issue date.
function addWorkingDays(fromDate, n) {
    const d = new Date(fromDate.getTime());
    let added = 0;
    while (added < n) {
        d.setDate(d.getDate() + 1);
        const day = d.getDay();           // 0 = Sun, 6 = Sat
        if (day !== 0 && day !== 6) added++;
    }
    return d;
}

// Set the Expiry Date to 5 working days after the current Issue Date value.
// Parses the date input as a local date so there is no timezone off-by-one.
function syncExpiryFromIssue() {
    const issueEl = document.getElementById('invDate');
    const expEl   = document.getElementById('invExpiry');
    if (!issueEl || !expEl) return;
    const parts = (issueEl.value || '').split('-');
    const base  = parts.length === 3
        ? new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
        : new Date();
    const exp = addWorkingDays(base, 5);
    const y = exp.getFullYear();
    const m = String(exp.getMonth() + 1).padStart(2, '0');
    const dd = String(exp.getDate()).padStart(2, '0');
    expEl.value = `${y}-${m}-${dd}`;
}

// Strip markdown so AI replies render cleanly as plain text (no stray * # `).
function mdToPlain(str) {
    return String(str ?? '')
        .replace(/(\*\*|__)(.+?)\1/g, '$2')      // **bold** / __bold__
        .replace(/^[ \t]*[*+\-][ \t]+/gm, '- ')   // bullet markers -> "- "
        .replace(/^[ \t]*#{1,6}[ \t]*/gm, '')     // markdown headings
        .replace(/`/g, '')                        // backticks
        .replace(/\*+/g, '')                      // any stray asterisks
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

// Detect placeholder/junk customer names the model sometimes invents.
function isPlaceholderName(name) {
    const n = String(name ?? '').trim().toLowerCase();
    if (!n) return false;
    return ['voice message customer','voice message','voice customer','customer',
            'customer name','the customer','valued customer','unknown',
            'unknown customer','n/a','na','not specified','not provided',
            'not available'].includes(n);
}
</script>
