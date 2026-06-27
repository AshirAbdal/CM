<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq_live_b00101f324e00a652f368af1c17a88d26460f273f007d462');
if (!defined('ORIGIN'))   define('ORIGIN',   $_is_local ? 'http://localhost:8002'   : 'https://admin.majesticmarquees.clickdigim.com');
unset($_is_local);

// ── JWT auth guard (matches dashboard.php) ─────────────────────────────────
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

// CSRF token (inline, matches images.php)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Handle CSV import POST ─────────────────────────────────────────────────
$importResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        http_response_code(403);
        exit('Invalid request.');
    }
    unset($_SESSION['csrf_token']);
    $file = $_FILES['csv_file'];

    // Validate upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $importResult = ['success' => false, 'message' => 'Upload failed (error code ' . $file['error'] . ').'];
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $importResult = ['success' => false, 'message' => 'Only .csv files are accepted.'];
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $importResult = ['success' => false, 'message' => 'File exceeds 5 MB limit.'];
    } else {
        // Read CSV into array and send to backend API
        $rows = [];
        if (($fh = fopen($file['tmp_name'], 'r')) !== false) {
            $header = null;
            while (($line = fgetcsv($fh)) !== false) {
                if ($header === null) {
                    $header = array_map('trim', $line);
                    // Strip a UTF-8 BOM that Excel prepends to the first header
                    // cell, otherwise "Tent_Type" would not be recognised.
                    if (isset($header[0])) {
                        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
                    }
                    continue;
                }
                if (count($line) !== count($header)) continue; // skip malformed
                $rows[] = array_combine($header, array_map('trim', $line));
            }
            fclose($fh);
        }

        if (empty($rows)) {
            $importResult = ['success' => false, 'message' => 'CSV file is empty or could not be parsed.'];
        } else {
            // POST rows to backend API
            $payload = json_encode(['rows' => $rows]);

            $ch = curl_init(API_BASE . '/wl/admin/products/import');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload),
                    'X-API-Key: '            . API_KEY,
                    'Origin: '               . ORIGIN,
                    'Authorization: Bearer ' . ($_SESSION['jwt'] ?? ''),
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_TIMEOUT        => 60,
            ]);
            $body    = curl_exec($ch);
            $status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status === 401) {
                session_destroy();
                header('Location: /login');
                exit;
            }

            $decoded = json_decode($body, true);
            $importResult = [
                'success'     => ($status === 200 && !empty($decoded['success'])),
                'message'     => $decoded['message']  ?? 'Unknown response from server.',
                'inserted'    => $decoded['inserted']  ?? 0,
                'skipped'     => $decoded['skipped']   ?? 0,
                'total_rows'  => count($rows),
            ];
        }
    }
}

// Re-issue a CSRF token for the freshly rendered form.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$layout    = 'app';
$activeNav = 'inventory';
?>

<script type="application/json" id="page-meta">
{"title": "Inventory Import | Majestic Marquees Admin"}
</script>

<!-- CSV Import - collapsible strip -->
<div class="mb-6 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <button onclick="toggleCsvPanel()" class="w-full flex items-center justify-between px-5 py-3 text-left hover:bg-slate-50 transition" id="csvToggleBtn">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-sm font-semibold text-slate-700">Product Inventory Import</span>
            <span class="text-xs text-slate-400 hidden sm:inline">- upload CSV to populate the product catalogue</span>
        </div>
        <svg id="csvChevron" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div id="csvPanel" class="hidden border-t border-slate-100">
        <div class="p-5">
            <?php if ($importResult !== null): ?>
            <div class="mb-4 rounded-lg p-3 border text-sm <?= $importResult['success'] ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
                <p class="font-semibold"><?= $importResult['success'] ? '✓ Import successful' : '✗ Import failed' ?></p>
                <p class="mt-1"><?= htmlspecialchars($importResult['message']) ?></p>
                <?php if ($importResult['success']): ?>
                <p class="mt-1">Total: <strong><?= (int)$importResult['total_rows'] ?></strong> · Inserted: <strong><?= (int)$importResult['inserted'] ?></strong> · Skipped: <strong><?= (int)$importResult['skipped'] ?></strong></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                <form id="csvForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <div id="dropZone" class="border-2 border-dashed border-slate-300 rounded-lg p-6 text-center cursor-pointer hover:border-primary hover:bg-blue-50 transition-colors mb-3" onclick="document.getElementById('csvInput').click()">
                        <svg class="mx-auto h-7 w-7 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p class="text-slate-600 text-sm">Click or drag & drop a .csv file</p>
                        <p id="fileName" class="mt-1 text-primary font-semibold text-sm hidden"></p>
                    </div>
                    <input type="file" id="csvInput" name="csv_file" accept=".csv" class="hidden">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Required columns</span>
                        <button type="button" onclick="downloadCsvTemplate()" class="inline-flex items-center gap-1.5 text-primary hover:text-blue-700 text-xs font-semibold transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download blank template
                        </button>
                    </div>
                    <div class="bg-slate-50 rounded p-2 text-xs text-slate-500 font-mono mb-3">Tent_Type, Size_Group, Tent_Size, Seated, Cocktail, Cinema, Coating, Weight, Packed_Size, Colours, Area_m2, Centerpole_Config, Bag_Size, Parts_Bags, Anchor_Bags, Category, Item_Name, discount_percentage, discount_flat, non_discountable, Price, Quantity</div>
                    <button type="submit" id="submitBtn" class="w-full bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>Import CSV</button>
                </form>
                <div id="previewSection" class="hidden">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-slate-700">Preview <span id="previewCount" class="text-slate-400 font-normal text-xs"></span></h3>
                        <p class="text-xs text-slate-400">First 20 rows</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto max-h-48">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200"><tr id="previewHead"></tr></thead>
                                <tbody id="previewBody" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── CSV collapsible ───────────────────────────────────────────────────────────
function toggleCsvPanel() {
    const panel   = document.getElementById('csvPanel');
    const chevron = document.getElementById('csvChevron');
    const hidden  = panel.classList.toggle('hidden');
    chevron.style.transform = hidden ? '' : 'rotate(180deg)';
}

// ── Download a blank CSV template (header row only) ────────────────────────────
// Plain ASCII, no BOM, so it re-imports cleanly through the CSV parser above.
function downloadCsvTemplate() {
    const headers = ['Tent_Type','Size_Group','Tent_Size','Seated','Cocktail','Cinema','Coating','Weight','Packed_Size','Colours','Area_m2','Centerpole_Config','Bag_Size','Parts_Bags','Anchor_Bags','Category','Item_Name','discount_percentage','discount_flat','non_discountable','Price','Quantity'];
    const csv  = headers.join(',') + '\r\n';
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url;
    a.download = 'majestic-inventory-template.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

// ── CSV upload logic ──────────────────────────────────────────────────────────
(function () {
    const input    = document.getElementById('csvInput');
    const dropZone = document.getElementById('dropZone');
    const fileName = document.getElementById('fileName');
    const submitBtn= document.getElementById('submitBtn');
    const preview  = document.getElementById('previewSection');
    const previewCount = document.getElementById('previewCount');

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-primary','bg-blue-50'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-primary','bg-blue-50'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('border-primary','bg-blue-50');
        if (e.dataTransfer.files.length) { const dt = new DataTransfer(); dt.items.add(e.dataTransfer.files[0]); input.files = dt.files; handleFile(e.dataTransfer.files[0]); }
    });
    input.addEventListener('change', () => { if (input.files.length) handleFile(input.files[0]); });

    function handleFile(file) {
        if (!file.name.endsWith('.csv')) { alert('Please select a .csv file.'); return; }
        fileName.textContent = '📄 ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
        fileName.classList.remove('hidden');
        submitBtn.disabled = false;
        const reader = new FileReader();
        reader.onload = e => renderPreview(e.target.result);
        reader.readAsText(file);
    }

    function parseCSV(text) { return text.trim().split(/\r?\n/).map(l => l.split(',').map(s => s.trim())); }

    function renderPreview(text) {
        const rows = parseCSV(text); const headers = rows[0] ?? []; const data = rows.slice(1, 21);
        previewCount.textContent = '(' + (rows.length-1) + ' total rows)';
        const head = document.getElementById('previewHead'); head.innerHTML = '';
        headers.forEach(h => { const th = document.createElement('th'); th.className='px-3 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide whitespace-nowrap'; th.textContent=h; head.appendChild(th); });
        const body = document.getElementById('previewBody'); body.innerHTML = '';
        data.forEach((row,i) => { const tr = document.createElement('tr'); tr.className = i%2===0?'bg-white':'bg-slate-50/50'; row.forEach(cell => { const td = document.createElement('td'); td.className='px-3 py-1.5 text-slate-600 whitespace-nowrap max-w-xs truncate text-xs'; td.textContent=cell; tr.appendChild(td); }); body.appendChild(tr); });
        preview.classList.remove('hidden');
    }
})();

// ── Shared constants ──────────────────────────────────────────────────────────
const API_BASE = '<?= API_BASE ?>';
const JWT      = <?= json_encode($_SESSION['jwt'] ?? '') ?>;
const API_KEY  = '<?= API_KEY ?>';
const ORIGIN   = '<?= ORIGIN ?>';

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<!-- ══ LEFT: CSV Import ══════════════════════════════════════════════════ -->
<div>
<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- DISCOUNT MANAGER - full-width section below the two-column grid      -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<div class="mt-10">
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-slate-800">Inventory Discount Manager</h2>
        <p class="text-slate-500 mt-1 text-sm">Search for a product or category, then apply a discount. The cascade rule preserves custom overrides.</p>
    </div>

    <!-- Search bar -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5 mb-6">
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <input type="text" id="discountSearch"
                   placeholder="Search by item name or category… e.g. Carabiner, Rigging Part"
                   class="flex-1 min-w-0 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                   oninput="debounceSearch()">
            <button onclick="runSearch()" class="w-full sm:w-auto shrink-0 bg-primary text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition whitespace-nowrap">Search</button>
        </div>
        <p id="searchStatus" class="text-xs text-slate-400 mt-2 hidden"></p>
    </div>

    <!-- Apply-to-all bulk bar (shown after search) -->
    <div id="bulkBar" class="hidden bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 mb-4 flex items-center gap-3 flex-wrap">
        <span class="text-sm font-medium text-blue-800" id="bulkLabel">Apply same discount to all results:</span>
        <input type="number" min="0" max="100" step="0.01" id="bulkDiscountInput"
               value="0"
               class="w-24 border border-blue-300 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-primary">
        <span class="text-sm text-blue-700">%</span>
        <button onclick="applyAllDiscounts()"
                id="bulkApplyBtn"
                class="bg-primary text-white px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
            Apply to All
        </button>
        <div id="bulkProgress" class="hidden text-xs text-blue-600 ml-auto"></div>
    </div>

    <!-- Results table -->
    <div id="discountResults" class="hidden bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto mb-6">
        <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Item</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Path</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase w-28">Current %</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase w-36">New Discount %</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase w-24">Apply</th>
                </tr>
            </thead>
            <tbody id="discountTableBody" class="divide-y divide-slate-100"></tbody>
        </table>
        <div id="noResults" class="hidden py-10 text-center text-slate-400 text-sm">No products matched your search.</div>
    </div>

    <!-- Feedback banner -->
    <div id="discountFeedback" class="hidden rounded-lg p-4 border text-sm mb-4"></div>
</div>

<script>
// ── Discount Manager ──────────────────────────────────────────────────────────
let searchTimer = null;

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, 400);
}

async function runSearch() {
    const q = document.getElementById('discountSearch').value.trim();
    if (q.length < 2) return;

    const status = document.getElementById('searchStatus');
    status.textContent = 'Searching…';
    status.classList.remove('hidden');

    try {
        const res = await fetch(API_BASE + '/wl/admin/products/search?q=' + encodeURIComponent(q) + '&limit=100', {
            headers: {
                'X-API-Key': API_KEY,
                'Origin': ORIGIN,
                'Authorization': 'Bearer ' + JWT,
            }
        });
        const data = await res.json();

        if (!res.ok) { status.textContent = '⚠ ' + (data.error || 'Search failed'); return; }

        status.textContent = data.total + ' result(s) found.';
        renderDiscountTable(data.results);
    } catch (e) {
        status.textContent = '⚠ Network error: ' + e.message;
    }
}

// Store current result set for bulk apply
let currentSearchResults = [];

function renderDiscountTable(rows) {
    currentSearchResults = rows;
    const wrapper = document.getElementById('discountResults');
    const tbody   = document.getElementById('discountTableBody');
    const noRes   = document.getElementById('noResults');
    const bulkBar = document.getElementById('bulkBar');

    tbody.innerHTML = '';
    wrapper.classList.remove('hidden');

    if (!rows.length) {
        noRes.classList.remove('hidden');
        bulkBar.classList.add('hidden');
        return;
    }
    noRes.classList.add('hidden');
    bulkBar.classList.remove('hidden');
    document.getElementById('bulkLabel').textContent =
        'Apply same discount to all ' + rows.length + ' result(s):';

    rows.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/50 transition';
        tr.innerHTML = `
            <td class="px-4 py-3 font-medium text-slate-800">${escHtml(row.item_name)}</td>
            <td class="px-4 py-3 text-slate-500 text-xs">${escHtml(row.breadcrumb)}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold ${row.discount_percentage > 0 ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}">
                    ${row.discount_percentage}%
                </span>
            </td>
            <td class="px-4 py-3 text-center">
                <input type="number" min="0" max="100" step="0.01"
                       id="disc_${row.p_id}"
                       value="${row.discount_percentage}"
                       class="w-24 border border-slate-200 rounded px-2 py-1 text-sm text-center focus:outline-none focus:ring-2 focus:ring-primary">
            </td>
            <td class="px-4 py-3 text-center">
                <button onclick="applyDiscount(${row.p_id}, '${escHtml(row.item_name)}')"
                        class="bg-primary text-white text-xs px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">
                    Apply
                </button>
            </td>`;
        tbody.appendChild(tr);
    });
}

async function applyDiscount(p_id, name) {
    const input = document.getElementById('disc_' + p_id);
    const val   = parseFloat(input.value);
    if (isNaN(val) || val < 0 || val > 100) {
        showFeedback(false, 'Invalid discount value for "' + name + '". Must be 0–100.');
        return;
    }

    const btn = input.closest('tr').querySelector('button');
    btn.disabled = true;
    btn.textContent = '…';

    try {
        const res = await fetch(API_BASE + '/wl/admin/products/' + p_id + '/discount', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': API_KEY,
                'Origin': ORIGIN,
                'Authorization': 'Bearer ' + JWT,
            },
            body: JSON.stringify({ discount_percentage: val }),
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
            showFeedback(false, data.error || 'Failed to apply discount.');
        } else {
            showFeedback(true,
                `✓ "${escHtml(name)}" → ${data.new_discount}% applied to ${data.nodes_updated} node(s). ` +
                `(was ${data.old_discount}%)`
            );
            // Refresh search to show updated values
            runSearch();
        }
    } catch (e) {
        showFeedback(false, 'Network error: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Apply';
    }
}

async function applyAllDiscounts() {
    const val = parseFloat(document.getElementById('bulkDiscountInput').value);
    if (isNaN(val) || val < 0 || val > 100) {
        showFeedback(false, 'Invalid discount value. Must be 0–100.');
        return;
    }

    const btn      = document.getElementById('bulkApplyBtn');
    const progress = document.getElementById('bulkProgress');
    btn.disabled   = true;
    btn.textContent = 'Applying…';
    progress.classList.remove('hidden');

    let done = 0;
    let failed = 0;
    const total = currentSearchResults.length;

    for (const row of currentSearchResults) {
        progress.textContent = done + ' / ' + total + ' done…';
        try {
            const res = await fetch(API_BASE + '/wl/admin/products/' + row.p_id + '/discount', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': API_KEY,
                    'Origin': ORIGIN,
                    'Authorization': 'Bearer ' + JWT,
                },
                body: JSON.stringify({ discount_percentage: val }),
            });
            const data = await res.json();
            if (res.ok && data.success) done++; else failed++;
        } catch (_) { failed++; }
    }

    progress.classList.add('hidden');
    btn.disabled    = false;
    btn.textContent = 'Apply to All';

    const msg = '✓ Applied ' + val + '% to ' + done + ' item(s).' + (failed ? ' ⚠ ' + failed + ' failed.' : '');
    showFeedback(done > 0, msg);
    runSearch(); // refresh table to show new values
}

function showFeedback(success, msg) {
    const el = document.getElementById('discountFeedback');
    el.className = 'rounded-lg p-4 border text-sm mb-4 ' +
        (success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800');
    el.textContent = msg;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 6000);
}
</script>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- PRODUCT BROWSER - grouped tent tree (Tent Type > Size Group > Tent Size > Category > Items) -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<div class="mt-10">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Product Browser</h2>
            <p class="text-slate-500 mt-1 text-sm" id="productTotal">Loading products...</p>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button onclick="pbOpenImport()" class="inline-flex items-center justify-center gap-1.5 border border-slate-200 text-slate-600 text-sm font-medium px-3 sm:px-4 py-2 rounded-lg hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                <span class="hidden sm:inline">Import JSON</span>
            </button>
            <button onclick="pbOpenAddProduct()" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 border border-slate-200 text-slate-600 text-sm font-medium px-3 sm:px-4 py-2 rounded-lg hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span class="sm:inline">Add Product</span>
            </button>
            <button onclick="pbOpenAdd()" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-primary text-white text-sm font-medium px-3 sm:px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span class="sm:inline">Add Tent</span>
            </button>
        </div>
    </div>
    <!-- Filter bar -->
    <div class="flex flex-wrap items-center gap-2 mb-3">
        <input type="text" id="productSearchInput" placeholder="Search anything: tent, size, spec, item, SKU, price..."
               class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full sm:w-72 min-w-0 focus:outline-none focus:ring-2 focus:ring-primary"
               oninput="debounceProductSearch()">
        <select id="pbFilterTentType" onchange="pbRenderTree()"
                class="border border-slate-200 rounded-lg px-3 py-2 text-sm flex-1 sm:flex-none min-w-[8rem] focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">All Tent Types</option>
        </select>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button onclick="pbExpandAll(true)" class="flex-1 sm:flex-none text-xs border border-slate-200 text-slate-500 px-3 py-2 rounded-lg hover:bg-slate-50 transition whitespace-nowrap">Expand all</button>
            <button onclick="pbExpandAll(false)" class="flex-1 sm:flex-none text-xs border border-slate-200 text-slate-500 px-3 py-2 rounded-lg hover:bg-slate-50 transition whitespace-nowrap">Collapse all</button>
            <button onclick="pbClearFilters()" class="flex-1 sm:flex-none text-xs border border-slate-200 text-slate-500 px-3 py-2 rounded-lg hover:bg-slate-50 transition">Clear</button>
        </div>
    </div>

    <!-- Cart view - one card per tent, mirrors the JSON object (specs + categories + item lines) -->
    <div id="pbTree" class="space-y-4 overflow-auto pr-1" style="max-height:760px;">
        <p class="py-10 text-center text-slate-400 text-sm">Loading...</p>
    </div>
</div>

<script>
// ── Product Browser - infinite scroll ────────────────────────────────────────
let   PB_TENTS = [];          // full dataset from /wl/admin/products/tree
const pbOpen   = new Set();   // _tentsize_pid of expanded tents
let   pbTimer  = null;

// DB-side spec key  ->  display label (only non-empty specs are shown)
const PB_SPEC_KEYS = [
    ['Seated', 'Seated'], ['Cocktail', 'Cocktail'], ['Cinema', 'Cinema'], ['Coating', 'Coating'],
    ['Weight', 'Weight'], ['Packed Size', 'Packed Size'], ['Colours', 'Colours'], ['Area_m2', 'Area m2'],
    ['Centerpole_Config', 'Centerpole'], ['Bag_Size', 'Bag'], ['Parts_Bags', 'Parts'], ['Anchor_Bags', 'Anchors'],
];

document.addEventListener('DOMContentLoaded', () => {
    pbLoadTree();
});

function debounceProductSearch() {
    clearTimeout(pbTimer);
    pbTimer = setTimeout(pbRenderTree, 250);
}

function pbClearFilters() {
    document.getElementById('productSearchInput').value = '';
    document.getElementById('pbFilterTentType').value   = '';
    pbRenderTree();
}

async function pbLoadTree() {
    const tree = document.getElementById('pbTree');
    tree.innerHTML = '<p class="py-10 text-center text-slate-400 text-sm">Loading...</p>';
    try {
        const res  = await fetch(API_BASE + '/wl/admin/products/tree', {
            headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT }
        });
        const data = await res.json();
        if (!res.ok) {
            tree.innerHTML = '<p class="py-10 text-center text-red-400 text-sm">' + escHtml(data.error || 'Error loading products') + '</p>';
            return;
        }
        PB_TENTS = data.tents || [];
        pbPopulateTentTypes();
        pbRenderTree();
    } catch (e) {
        tree.innerHTML = '<p class="py-10 text-center text-red-400 text-sm">Network error: ' + escHtml(e.message) + '</p>';
    }
}

function pbPopulateTentTypes() {
    const sel = document.getElementById('pbFilterTentType');
    const cur = sel.value;
    const types = [...new Set(PB_TENTS.map(t => t['Tent Type']).filter(Boolean))].sort();
    sel.innerHTML = '<option value="">All Tent Types</option>';
    types.forEach(t => {
        const o = document.createElement('option');
        o.value = t; o.textContent = t;
        sel.appendChild(o);
    });
    sel.value = cur;
}

function pbTentItemCount(tent) {
    return (tent.Categories || []).reduce((n, c) => n + (c.Items ? c.Items.length : 0), 0);
}

// ── Full inventory search (token AND-match across every field) ───────────────
function pbActiveQuery() {
    return (document.getElementById('productSearchInput').value || '').trim().toLowerCase();
}
function pbTokens(q) {
    return (q || '').split(/\s+/).filter(Boolean);
}
// Identity + specs haystack: everything about the tent except its item rows.
function pbTentIdentityHay(tent) {
    return [tent['Tent Type'], tent['Size Group'], tent['Tent_Size'], tent._sku,
            ...PB_SPEC_KEYS.map(([k]) => tent[k])]
        .filter(v => v !== null && v !== undefined && v !== '').join(' ').toLowerCase();
}
// Single-item haystack: name, category, sku, price, qty, discounts, flags.
function pbItemHay(cat, it) {
    return [cat.Category, it.Item_Name, it._sku, it.Price, it.Quantity,
            it.discount_percentage ? it.discount_percentage + '%' : '',
            it.discount_flat ? it.discount_flat : '',
            it.non_discountable ? 'non-discountable no-disc' : '']
        .filter(v => v !== null && v !== undefined && v !== '').join(' ').toLowerCase();
}
// An item is visible under the active search when identity + item together
// satisfy every token. So "stretch carabiner" (stretch in identity) narrows to
// carabiner rows, while "stretch" alone (identity matches all) shows every item.
function pbItemVisible(tent, cat, it, tokens) {
    if (!tokens.length) return true;
    const hay = pbTentIdentityHay(tent) + ' ' + pbItemHay(cat, it);
    return tokens.every(t => hay.includes(t));
}
// Categories with items filtered by the active search (empties dropped when searching).
function pbVisibleCategories(tent, tokens) {
    const cats = (tent.Categories || []).map(cat => ({
        Category: cat.Category,
        Items: (cat.Items || []).filter(it => pbItemVisible(tent, cat, it, tokens)),
    }));
    return tokens.length ? cats.filter(c => c.Items.length) : cats;
}
function pbVisibleItemCount(tent, tokens) {
    return pbVisibleCategories(tent, tokens).reduce((n, c) => n + c.Items.length, 0);
}
function pbTentMatches(tent, tokens) {
    if (!tokens.length) return true;
    if (tokens.every(t => pbTentIdentityHay(tent).includes(t))) return true; // identity matches all tokens
    return pbVisibleItemCount(tent, tokens) > 0;
}
// Wrap matched tokens in a highlight mark (input is escaped first).
function pbHighlight(text, tokens) {
    let safe = escHtml(text);
    if (!tokens || !tokens.length) return safe;
    tokens.forEach(t => {
        if (!t) return;
        const re = new RegExp('(' + t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        safe = safe.replace(re, '<mark class="bg-yellow-200 text-slate-900 rounded px-0.5">$1</mark>');
    });
    return safe;
}

function pbFilteredTents() {
    const tokens = pbTokens(pbActiveQuery());
    const tt = document.getElementById('pbFilterTentType').value.trim();
    return PB_TENTS.filter(t => (!tt || t['Tent Type'] === tt) && pbTentMatches(t, tokens));
}

function pbExpandAll(open) {
    pbOpen.clear();
    if (open) pbFilteredTents().forEach(t => pbOpen.add(t._tentsize_pid));
    pbRenderTree();
}

function pbRenderTree() {
    const tree  = document.getElementById('pbTree');
    const tokens = pbTokens(pbActiveQuery());
    const tents = pbFilteredTents();
    const totalTents = PB_TENTS.length;
    const totalItems = PB_TENTS.reduce((n, t) => n + pbTentItemCount(t), 0);

    let label = totalTents + ' tent' + (totalTents === 1 ? '' : 's') +
                ' - ' + totalItems + ' item' + (totalItems === 1 ? '' : 's');
    if (tokens.length) {
        const shownItems = tents.reduce((n, t) => n + pbVisibleItemCount(t, tokens), 0);
        label += ' - ' + tents.length + ' tent' + (tents.length === 1 ? '' : 's') +
                 ', ' + shownItems + ' item' + (shownItems === 1 ? '' : 's') + ' match';
    } else if (tents.length !== totalTents) {
        label += ' - ' + tents.length + ' shown';
    }
    document.getElementById('productTotal').textContent = label;

    if (!tents.length) {
        tree.innerHTML = '<p class="py-10 text-center text-slate-400 text-sm">No tents match your filters.</p>';
        return;
    }
    tree.innerHTML = '';
    const frag = document.createDocumentFragment();
    tents.forEach(tent => frag.appendChild(pbBuildTentCard(tent)));
    tree.appendChild(frag);
}

// Cart money helpers - line total = unit price * quantity
function pbMoney(n) {
    const v = (typeof n === 'number' && isFinite(n)) ? n : 0;
    return '\u20ac' + v.toFixed(2);
}
function pbItemLineTotal(it) {
    const price = (typeof it.Price === 'number') ? it.Price : 0;
    const qty   = (typeof it.Quantity === 'number') ? it.Quantity : 0;
    return price * qty;
}
function pbCategorySubtotal(cat) {
    return (cat.Items || []).reduce((s, it) => s + pbItemLineTotal(it), 0);
}
function pbTentSubtotal(tent) {
    return (tent.Categories || []).reduce((s, c) => s + pbCategorySubtotal(c), 0);
}

function pbBuildTentCard(tent) {
    const wrap   = document.createElement('div');
    wrap.className = 'bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden';
    const tokens    = pbTokens(pbActiveQuery());
    const visCats   = pbVisibleCategories(tent, tokens);
    const itemCount = visCats.reduce((n, c) => n + c.Items.length, 0);
    const catCount  = visCats.length;
    const subtotal  = visCats.reduce((s, c) => s + pbCategorySubtotal(c), 0);
    const isOpen    = tokens.length ? true : pbOpen.has(tent._tentsize_pid);

    const header = document.createElement('div');
    header.className = 'flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50 transition select-none';
    header.onclick = (ev) => {
        if (ev.target.closest('[data-stop]')) return;
        if (pbOpen.has(tent._tentsize_pid)) pbOpen.delete(tent._tentsize_pid);
        else pbOpen.add(tent._tentsize_pid);
        pbRenderTree();
    };

    const titleBits = [];
    if (tent['Tent Type'])  titleBits.push('<span class="text-sm font-bold text-slate-800">' + escHtml(tent['Tent Type']) + '</span>');
    if (tent['Tent_Size'])  titleBits.push('<span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-700">' + escHtml(tent['Tent_Size']) + '</span>');
    if (tent['Size Group']) titleBits.push('<span class="text-xs text-slate-400">' + escHtml(tent['Size Group']) + '</span>');

    header.innerHTML =
        '<svg class="w-4 h-4 shrink-0 text-slate-400 transition-transform ' + (isOpen ? 'rotate-90' : '') + '" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>' +
        '<svg class="w-5 h-5 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>' +
        '<div class="flex flex-wrap items-center gap-2 min-w-0">' + titleBits.join('') + '</div>' +
        '<span class="text-[11px] text-slate-400 ml-1 whitespace-nowrap hidden sm:inline">' + catCount + ' cat - ' + itemCount + ' item' + (itemCount === 1 ? '' : 's') + '</span>' +
        '<div class="ml-auto flex items-center gap-2" data-stop>' +
            '<span class="text-xs font-semibold text-slate-700 bg-slate-100 rounded-full px-2.5 py-1 whitespace-nowrap">' + pbMoney(subtotal) + '</span>' +
            '<button data-stop onclick="pbExportTent(' + tent._tentsize_pid + ')" title="Export JSON" class="text-slate-400 hover:text-primary transition p-1.5 rounded hover:bg-slate-100">' +
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>' +
            '</button>' +
            '<button data-stop onclick="pbAddItemTo(' + tent._tentsize_pid + ')" title="Add items" class="text-slate-400 hover:text-primary transition p-1.5 rounded hover:bg-slate-100">' +
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>' +
            '</button>' +
        '</div>';
    wrap.appendChild(header);

    if (isOpen) wrap.appendChild(pbBuildTentBody(tent));
    return wrap;
}

function pbBuildTentBody(tent) {
    const body = document.createElement('div');
    body.className = 'px-4 pb-4 pt-2 bg-slate-50/40 border-t border-slate-100';
    const tokens = pbTokens(pbActiveQuery());

    // Specifications panel (matched terms highlighted)
    const specPairs = PB_SPEC_KEYS
        .map(([k, label]) => [label, tent[k]])
        .filter(([, v]) => v !== null && v !== undefined && String(v).trim() !== '');
    if (specPairs.length) {
        const specWrap = document.createElement('div');
        specWrap.className = 'mb-3';
        specWrap.innerHTML =
            '<p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Specifications</p>' +
            '<div class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-1 text-xs bg-white rounded-lg border border-slate-100 p-3">' +
            specPairs.map(([label, v]) =>
                '<div class="flex justify-between gap-2"><span class="text-slate-400">' + escHtml(label) + '</span><span class="text-slate-700 text-right truncate">' + pbHighlight(v, tokens) + '</span></div>'
            ).join('') +
            '</div>';
        body.appendChild(specWrap);
    }

    // Cart sections - one per category, items as line rows (filtered by search)
    const visCats = pbVisibleCategories(tent, tokens);
    visCats.forEach(cat => {
        const items = cat.Items;
        const catWrap = document.createElement('div');
        catWrap.className = 'mb-3';
        const rows = items.map(it => pbItemRowHtml(tent, cat, it, tokens)).join('');
        catWrap.innerHTML =
            '<div class="flex items-center justify-between gap-2 mb-1">' +
                '<div class="flex items-center gap-2">' +
                    '<span class="text-[11px] font-semibold text-blue-600 uppercase tracking-wide">' + pbHighlight(cat.Category || 'Uncategorised', tokens) + '</span>' +
                    '<span class="text-[11px] text-slate-400">' + items.length + ' item' + (items.length === 1 ? '' : 's') + '</span>' +
                '</div>' +
                '<span class="text-[11px] font-medium text-slate-500">' + pbMoney(pbCategorySubtotal(cat)) + '</span>' +
            '</div>' +
            '<div class="bg-white rounded-lg border border-slate-100 overflow-hidden">' +
                '<div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-slate-50 text-[10px] font-semibold text-slate-400 uppercase tracking-wide">' +
                    '<span class="flex-1">Item</span>' +
                    '<span class="w-12 text-right">Qty</span>' +
                    '<span class="w-20 text-right">Unit</span>' +
                    '<span class="w-24 text-right">Line total</span>' +
                    '<span class="w-14"></span>' +
                '</div>' +
                '<div class="divide-y divide-slate-50">' +
                    (rows || '<p class="px-3 py-2 text-xs text-slate-300">No items.</p>') +
                '</div>' +
            '</div>';
        body.appendChild(catWrap);
    });

    // Grand total footer (reflects the currently visible items)
    const visItems    = visCats.reduce((n, c) => n + c.Items.length, 0);
    const visSubtotal = visCats.reduce((s, c) => s + pbCategorySubtotal(c), 0);
    const foot = document.createElement('div');
    foot.className = 'flex items-center justify-between border-t border-slate-200 pt-2 mt-1';
    foot.innerHTML =
        '<span class="text-xs text-slate-400">' + visCats.length + ' categor' + (visCats.length === 1 ? 'y' : 'ies') + ' - ' + visItems + ' item' + (visItems === 1 ? '' : 's') + (tokens.length ? ' matched' : '') + '</span>' +
        '<span class="text-sm font-bold text-slate-800">Total ' + pbMoney(visSubtotal) + '</span>';
    body.appendChild(foot);

    return body;
}

function pbItemRowHtml(tent, cat, it, tokens) {
    tokens = tokens || pbTokens(pbActiveQuery());
    const unit    = (typeof it.Price === 'number' && it.Price > 0) ? pbMoney(it.Price) : '-';
    const line    = pbItemLineTotal(it);
    const lineTxt = line > 0 ? pbMoney(line) : '-';
    const disc  = it.discount_percentage > 0 ? '<span class="text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">' + it.discount_percentage + '%</span>' : '';
    const flat  = it.discount_flat > 0 ? '<span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full">\u20ac' + it.discount_flat + '</span>' : '';
    const nd    = it.non_discountable ? '<span class="text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded-full">no disc</span>' : '';
    const ctx = {
        p_id: it._p_id,
        item_name: it.Item_Name,
        unit_price: it.Price,
        quantity: it.Quantity,
        discount_percentage: it.discount_percentage,
        tent_type: tent['Tent Type'],
        size_group: tent['Size Group'],
        tent_size: tent['Tent_Size'],
        category: cat.Category,
    };
    const ctxAttr = JSON.stringify(ctx).replace(/"/g, '&quot;');
    return '<div class="flex items-start gap-2 px-3 py-2 text-xs hover:bg-slate-50/60">' +
        '<div class="flex-1 min-w-0 flex flex-wrap items-center gap-1.5">' +
            '<span class="font-medium text-slate-700 break-words">' + pbHighlight(it.Item_Name, tokens) + '</span>' +
            disc + flat + nd +
        '</div>' +
        '<span class="w-12 text-right text-slate-500">' + it.Quantity + '</span>' +
        '<span class="hidden sm:block w-20 text-right text-slate-500">' + unit + '</span>' +
        '<span class="w-16 sm:w-24 text-right font-semibold text-slate-800">' + lineTxt + '</span>' +
        '<button onclick="pbViewItem(' + (it._p_id || 0) + ')" title="View" class="w-6 text-slate-400 hover:text-primary transition flex items-center justify-center">' +
            '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>' +
        '</button>' +
        '<button onclick="pbOpenEdit(' + ctxAttr + ')" title="Edit" class="w-6 text-slate-400 hover:text-primary transition flex items-center justify-center">' +
            '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>' +
        '</button>' +
        '<button onclick="pbDeleteItem(' + (it._p_id || 0) + ', \'' + encodeURIComponent(it.Item_Name || '') + '\')" title="Delete" class="w-6 text-slate-400 hover:text-red-600 transition flex items-center justify-center">' +
            '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
        '</button>' +
    '</div>';
}

// Strip the internal _-prefixed keys before export so the JSON matches the canonical shape
function pbStripInternal(obj) {
    if (Array.isArray(obj)) return obj.map(pbStripInternal);
    if (obj && typeof obj === 'object') {
        const out = {};
        Object.keys(obj).forEach(k => { if (!k.startsWith('_')) out[k] = pbStripInternal(obj[k]); });
        return out;
    }
    return obj;
}

function pbExportTent(pid) {
    const tent = PB_TENTS.find(t => t._tentsize_pid === pid);
    if (!tent) return;
    const json = JSON.stringify(pbStripInternal(tent), null, 2);
    const safe = ((tent['Tent Type'] || 'tent') + '-' + (tent['Tent_Size'] || '')).replace(/[^a-z0-9]+/gi, '-').toLowerCase().replace(/^-+|-+$/g, '') || 'tent';
    const blob = new Blob([json], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = safe + '.json';
    document.body.appendChild(a); a.click(); a.remove();
    URL.revokeObjectURL(a.href);
    if (navigator.clipboard) navigator.clipboard.writeText(json).catch(() => {});
}

// ── Product Edit Modal ────────────────────────────────────────────────────────
let pbEditId = null;

function pbOpenEdit(r) {
    pbEditId = r.p_id;
    document.getElementById('pbEditName').value     = r.item_name;
    document.getElementById('pbEditPrice').value    = r.unit_price;
    document.getElementById('pbEditQty').value      = r.quantity;
    document.getElementById('pbEditDiscount').value = r.discount_percentage;
    document.getElementById('pbEditContext').textContent =
        [r.tent_type, r.size_group, r.tent_size, r.category].filter(Boolean).join(' › ');
    document.getElementById('pbEditError').classList.add('hidden');
    document.getElementById('pbEditModal').classList.remove('hidden');
}

function pbCloseEdit() {
    document.getElementById('pbEditModal').classList.add('hidden');
    pbEditId = null;
}

async function pbSaveEdit() {
    if (!pbEditId) return;
    const name     = document.getElementById('pbEditName').value.trim();
    const price    = parseFloat(document.getElementById('pbEditPrice').value);
    const qty      = parseInt(document.getElementById('pbEditQty').value);
    const discount = parseFloat(document.getElementById('pbEditDiscount').value);
    const errEl    = document.getElementById('pbEditError');
    const saveBtn  = document.getElementById('pbEditSaveBtn');

    if (!name)                              { errEl.textContent = 'Item name cannot be empty.'; errEl.classList.remove('hidden'); return; }
    if (isNaN(price) || price < 0)         { errEl.textContent = 'Price must be 0 or more.';   errEl.classList.remove('hidden'); return; }
    if (isNaN(qty)   || qty < 0)           { errEl.textContent = 'Quantity must be 0 or more.'; errEl.classList.remove('hidden'); return; }
    if (isNaN(discount) || discount < 0 || discount > 100) { errEl.textContent = 'Discount must be 0–100.'; errEl.classList.remove('hidden'); return; }

    saveBtn.disabled    = true;
    saveBtn.textContent = 'Saving…';
    errEl.classList.add('hidden');

    try {
        const res = await fetch(API_BASE + '/wl/admin/products/' + pbEditId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key':    API_KEY,
                'Origin':       ORIGIN,
                'Authorization': 'Bearer ' + JWT,
            },
            body: JSON.stringify({ item_name: name, unit_price: price, quantity: qty, discount_percentage: discount }),
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            errEl.textContent = data.error || 'Save failed.';
            errEl.classList.remove('hidden');
            return;
        }
        pbCloseEdit();
        pbLoadTree(); // refresh the tree
    } catch (e) {
        errEl.textContent = 'Network error: ' + e.message;
        errEl.classList.remove('hidden');
    } finally {
        saveBtn.disabled    = false;
        saveBtn.textContent = 'Save';
    }
}

// ── Delete a single item ──────────────────────────────────────────────────────
// Leaf items delete straight away. If the node turns out to hold sub-items the
// backend answers 409; we then confirm a cascade delete and retry with
// ?cascade=1 so the whole group is removed in one transaction.
async function pbDeleteItem(pid, encName) {
    pid = parseInt(pid);
    if (!pid) return;
    const name = decodeURIComponent(encName || '');
    if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;

    async function doDelete(cascade) {
        const url = API_BASE + '/wl/admin/products/' + pid + (cascade ? '?cascade=1' : '');
        const res = await fetch(url, {
            method: 'DELETE',
            headers: {
                'X-API-Key':     API_KEY,
                'Origin':        ORIGIN,
                'Authorization': 'Bearer ' + JWT,
            },
        });
        let data = {};
        try { data = await res.json(); } catch (e) {}
        return { res, data };
    }

    try {
        let { res, data } = await doDelete(false);

        if (res.status === 409 && data.has_children) {
            const n = data.descendant_count || 0;
            if (!confirm('This contains ' + n + ' sub-item' + (n === 1 ? '' : 's') + '. Delete the whole group?')) return;
            ({ res, data } = await doDelete(true));
        }

        if (!res.ok || !data.success) {
            alert(data.error || 'Delete failed.');
            return;
        }
        pbLoadTree(); // refresh the tree
    } catch (e) {
        alert('Network error: ' + e.message);
    }
}

// ── Add Single Product ────────────────────────────────────────────────────────
// The Add form and the JSON paste-import both POST the nested tent structure to
// /wl/admin/products/import-tree (idempotent: existing nodes are reused, only new
// items insert). Spec field id (pbAdd_<id>)  ->  canonical JSON key.
const PB_SPEC_FIELDS = [
    ['Seated', 'Seated'], ['Cocktail', 'Cocktail'], ['Cinema', 'Cinema'], ['Coating', 'Coating'],
    ['Weight', 'Weight'], ['Packed_Size', 'Packed Size'], ['Colours', 'Colours'], ['Area_m2', 'Area_m2'],
    ['Centerpole_Config', 'Centerpole_Config'], ['Bag_Size', 'Bag_Size'], ['Parts_Bags', 'Parts_Bags'], ['Anchor_Bags', 'Anchor_Bags'],
];

function pbOpenAdd(prefillTent) {
    ['Tent_Type', 'Size_Group', 'Tent_Size', 'sku'].forEach(f => {
        const el = document.getElementById('pbAdd_' + f); if (el) el.value = '';
    });
    PB_SPEC_FIELDS.forEach(([fieldId]) => {
        const el = document.getElementById('pbAdd_' + fieldId); if (el) el.value = '';
    });
    document.getElementById('pbAddCategories').innerHTML = '';
    document.getElementById('pbAddError').classList.add('hidden');

    // category-name suggestions from the existing catalogue
    const dl = document.getElementById('pbCatSuggestions');
    if (dl) {
        const cats = [...new Set(PB_TENTS.flatMap(t => (t.Categories || []).map(c => c.Category)).filter(Boolean))].sort();
        dl.innerHTML = cats.map(c => '<option value="' + escHtml(c) + '"></option>').join('');
    }

    if (prefillTent) {
        document.getElementById('pbAddTitle').textContent =
            'Add items to ' + (prefillTent['Tent Type'] || 'tent') + ' ' + (prefillTent['Tent_Size'] || '');
        document.getElementById('pbAdd_Tent_Type').value  = prefillTent['Tent Type'] || '';
        document.getElementById('pbAdd_Size_Group').value = prefillTent['Size Group'] || '';
        document.getElementById('pbAdd_Tent_Size').value  = prefillTent['Tent_Size'] || '';
        document.getElementById('pbAdd_sku').value        = prefillTent._sku || '';
        PB_SPEC_FIELDS.forEach(([fieldId, jsonKey]) => {
            const el = document.getElementById('pbAdd_' + fieldId);
            if (el && prefillTent[jsonKey] != null) el.value = prefillTent[jsonKey];
        });
    } else {
        document.getElementById('pbAddTitle').textContent = 'Add Tent';
    }

    pbAddCategoryRow();
    document.getElementById('pbAddModal').classList.remove('hidden');
}

function pbAddItemTo(pid) {
    const tent = PB_TENTS.find(t => t._tentsize_pid === pid);
    pbOpenAdd(tent || undefined);
}

function pbCloseAdd() {
    document.getElementById('pbAddModal').classList.add('hidden');
}

function pbAddCategoryRow(prefillName) {
    const host = document.getElementById('pbAddCategories');
    const cat  = document.createElement('div');
    cat.className = 'pb-cat border border-slate-200 rounded-lg p-3 bg-white';
    cat.innerHTML =
        '<div class="flex items-center gap-2 mb-2">' +
            '<input class="pb-cat-name flex-1 border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Category name (e.g. Rigging Part)" list="pbCatSuggestions" value="' + escHtml(prefillName || '') + '">' +
            '<button type="button" title="Remove category" class="pb-cat-del text-slate-300 hover:text-red-500 transition p-1.5">' +
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' +
            '</button>' +
        '</div>' +
        '<div class="pb-items space-y-2"></div>' +
        '<button type="button" class="pb-item-add mt-2 text-xs text-primary font-medium hover:underline">+ Add item</button>';
    cat.querySelector('.pb-cat-del').onclick  = () => cat.remove();
    cat.querySelector('.pb-item-add').onclick = () => pbAddItemRow(cat.querySelector('.pb-items'));
    host.appendChild(cat);
    pbAddItemRow(cat.querySelector('.pb-items'));
    return cat;
}

function pbAddItemRow(itemsHost) {
    const row = document.createElement('div');
    row.className = 'pb-item grid grid-cols-12 gap-2 items-center';
    row.innerHTML =
        '<input class="pb-item-name col-span-12 sm:col-span-4 border border-slate-200 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Item name">' +
        '<input class="pb-item-price col-span-3 sm:col-span-2 border border-slate-200 rounded px-2 py-1.5 text-xs text-right focus:outline-none focus:ring-2 focus:ring-primary" type="number" min="0" step="0.01" placeholder="Price">' +
        '<input class="pb-item-qty col-span-3 sm:col-span-2 border border-slate-200 rounded px-2 py-1.5 text-xs text-right focus:outline-none focus:ring-2 focus:ring-primary" type="number" min="0" step="1" placeholder="Qty" value="1">' +
        '<input class="pb-item-disc col-span-2 sm:col-span-1 border border-slate-200 rounded px-2 py-1.5 text-xs text-right focus:outline-none focus:ring-2 focus:ring-primary" type="number" min="0" max="100" step="0.01" placeholder="%">' +
        '<input class="pb-item-flat col-span-2 sm:col-span-1 border border-slate-200 rounded px-2 py-1.5 text-xs text-right focus:outline-none focus:ring-2 focus:ring-primary" type="number" min="0" step="0.01" placeholder="Flat">' +
        '<label class="col-span-1 flex items-center justify-center" title="Non-discountable"><input type="checkbox" class="pb-item-nd w-4 h-4"></label>' +
        '<button type="button" class="pb-item-del col-span-1 text-slate-300 hover:text-red-500 transition flex items-center justify-center" title="Remove item">' +
            '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' +
        '</button>';
    row.querySelector('.pb-item-del').onclick = () => row.remove();
    itemsHost.appendChild(row);
    return row;
}

async function pbSaveAdd() {
    const errEl   = document.getElementById('pbAddError');
    const saveBtn = document.getElementById('pbAddSaveBtn');
    const val = id => (document.getElementById(id).value || '').trim();

    const tent = {
        'Tent Type':  val('pbAdd_Tent_Type'),
        'Size Group': val('pbAdd_Size_Group'),
        'Tent_Size':  val('pbAdd_Tent_Size'),
    };
    const sku = val('pbAdd_sku');
    if (sku) tent.sku = sku;
    PB_SPEC_FIELDS.forEach(([fieldId, jsonKey]) => {
        const v = val('pbAdd_' + fieldId);
        if (v) tent[jsonKey] = v;
    });

    const cats = [];
    document.querySelectorAll('#pbAddCategories .pb-cat').forEach(catEl => {
        const cname = (catEl.querySelector('.pb-cat-name').value || '').trim();
        const items = [];
        catEl.querySelectorAll('.pb-item').forEach(itEl => {
            const name = (itEl.querySelector('.pb-item-name').value || '').trim();
            if (!name) return;
            const price = parseFloat(itEl.querySelector('.pb-item-price').value);
            const qty   = parseInt(itEl.querySelector('.pb-item-qty').value, 10);
            const disc  = parseFloat(itEl.querySelector('.pb-item-disc').value);
            const flat  = parseFloat(itEl.querySelector('.pb-item-flat').value);
            const nd    = itEl.querySelector('.pb-item-nd').checked;
            items.push({
                Item_Name: name,
                Price: isNaN(price) ? 0 : price,
                Quantity: isNaN(qty) ? 1 : qty,
                discount_percentage: isNaN(disc) ? 0 : disc,
                discount_flat: isNaN(flat) ? 0 : flat,
                non_discountable: !!nd,
            });
        });
        if (cname || items.length) cats.push({ Category: cname, Items: items });
    });
    tent.Categories = cats;

    if (!tent['Tent Type'] && !tent['Tent_Size']) {
        errEl.textContent = 'Provide at least a Tent Type or Tent Size.';
        errEl.classList.remove('hidden'); return;
    }
    const totalItems = cats.reduce((n, c) => n + c.Items.length, 0);
    if (totalItems === 0) {
        errEl.textContent = 'Add at least one item with a name.';
        errEl.classList.remove('hidden'); return;
    }

    saveBtn.disabled = true; saveBtn.textContent = 'Saving...';
    errEl.classList.add('hidden');
    try {
        const res = await fetch(API_BASE + '/wl/admin/products/import-tree', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-API-Key':     API_KEY,
                'Origin':        ORIGIN,
                'Authorization': 'Bearer ' + JWT,
            },
            body: JSON.stringify({ tents: [tent] }),
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            errEl.textContent = data.error || data.message || 'Failed to save.';
            errEl.classList.remove('hidden'); return;
        }
        pbCloseAdd();
        await pbLoadTree();
    } catch (e) {
        errEl.textContent = 'Network error: ' + e.message;
        errEl.classList.remove('hidden');
    } finally {
        saveBtn.disabled = false; saveBtn.textContent = 'Save';
    }
}

// JSON paste import (single tent object, an array, or { "tents": [...] })
function pbOpenImport() {
    document.getElementById('pbImportText').value = '';
    document.getElementById('pbImportError').classList.add('hidden');
    document.getElementById('pbImportMsg').classList.add('hidden');
    document.getElementById('pbImportModal').classList.remove('hidden');
}

function pbCloseImport() {
    document.getElementById('pbImportModal').classList.add('hidden');
}

async function pbRunImport() {
    const errEl = document.getElementById('pbImportError');
    const msgEl = document.getElementById('pbImportMsg');
    const btn   = document.getElementById('pbImportBtn');
    const raw   = document.getElementById('pbImportText').value.trim();
    if (!raw) { errEl.textContent = 'Paste JSON first.'; errEl.classList.remove('hidden'); return; }
    let parsed;
    try { parsed = JSON.parse(raw); }
    catch (e) { errEl.textContent = 'Invalid JSON: ' + e.message; errEl.classList.remove('hidden'); return; }

    btn.disabled = true; btn.textContent = 'Importing...';
    errEl.classList.add('hidden'); msgEl.classList.add('hidden');
    try {
        const res = await fetch(API_BASE + '/wl/admin/products/import-tree', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-API-Key':     API_KEY,
                'Origin':        ORIGIN,
                'Authorization': 'Bearer ' + JWT,
            },
            body: JSON.stringify(parsed),
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            errEl.textContent = data.error || data.message || 'Import failed.';
            errEl.classList.remove('hidden'); return;
        }
        msgEl.textContent = data.message || 'Imported.';
        msgEl.classList.remove('hidden');
        await pbLoadTree();
    } catch (e) {
        errEl.textContent = 'Network error: ' + e.message;
        errEl.classList.remove('hidden');
    } finally {
        btn.disabled = false; btn.textContent = 'Import';
    }
}

// ── Show Single Item ──────────────────────────────────────────────────────────
let pbViewCurrent = null;
function pbViewItem(pid) {
    if (!pid) return;
    pbViewCurrent = null;
    const body = document.getElementById('pbViewBody');
    const err  = document.getElementById('pbViewError');
    err.classList.add('hidden');
    body.innerHTML = '<p class="py-8 text-center text-slate-400 text-sm">Loading...</p>';
    document.getElementById('pbViewModal').classList.remove('hidden');

    fetch(API_BASE + '/wl/admin/products/' + pid, {
        headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT },
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (!ok || !d.product) {
            body.innerHTML = '';
            err.textContent = d.error || 'Could not load item.';
            err.classList.remove('hidden');
            return;
        }
        const p = d.product;
        pbViewCurrent = p;
        const badges =
            (p.discount_percentage > 0 ? '<span class="text-[11px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full">' + p.discount_percentage + '% off</span>' : '') +
            (p.discount_flat > 0 ? '<span class="text-[11px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full ml-1">\u20ac' + p.discount_flat + ' off</span>' : '') +
            (p.non_discountable ? '<span class="text-[11px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full ml-1">non-discountable</span>' : '');
        const row = (label, value) =>
            '<div class="flex justify-between gap-3 py-1.5 border-b border-slate-50 last:border-0"><span class="text-slate-400">' + escHtml(label) + '</span><span class="text-slate-700 text-right font-medium">' + value + '</span></div>';
        body.innerHTML =
            '<div class="mb-3">' +
                '<p class="text-[11px] text-slate-400 mb-1">' + (p.location ? escHtml(p.location) : '') + '</p>' +
                '<h4 class="text-lg font-bold text-slate-800 break-words">' + escHtml(p.item_name) + '</h4>' +
                (badges ? '<div class="mt-1.5 flex flex-wrap items-center">' + badges + '</div>' : '') +
            '</div>' +
            '<div class="text-sm bg-slate-50 rounded-lg border border-slate-100 px-3 py-2">' +
                row('SKU', p.sku ? escHtml(p.sku) : '<span class="text-slate-300">-</span>') +
                row('Unit price', pbMoney(p.unit_price)) +
                row('Quantity', p.quantity) +
                row('Line total', pbMoney(p.line_total)) +
                row('Net unit price', '<span class="text-slate-900">' + pbMoney(p.net_unit_price) + '</span>') +
                row('Net line total', '<span class="text-emerald-700 font-bold">' + pbMoney(p.net_line_total) + '</span>') +
            '</div>' +
            '<p class="text-[11px] text-slate-400 mt-3">' +
                'Item #' + p.p_id + (p.updated_at ? ' &middot; updated ' + escHtml(String(p.updated_at)) : '') +
            '</p>' +
            '<div class="mt-4 flex justify-end gap-2">' +
                '<button onclick="pbViewDelete()" class="px-4 py-2 rounded-lg text-sm font-medium border border-red-200 text-red-600 hover:bg-red-50 transition">Delete</button>' +
                '<button onclick="pbViewEdit()" class="px-4 py-2 rounded-lg text-sm font-medium bg-primary text-white hover:bg-blue-700 transition">Edit this item</button>' +
            '</div>';
    })
    .catch(e => {
        body.innerHTML = '';
        err.textContent = 'Network error: ' + e.message;
        err.classList.remove('hidden');
    });
}

function pbCloseView() {
    document.getElementById('pbViewModal').classList.add('hidden');
}

function pbViewDelete() {
    const p = pbViewCurrent;
    if (!p) return;
    pbCloseView();
    pbDeleteItem(p.p_id, encodeURIComponent(p.item_name || ''));
}

function pbViewEdit() {
    const p = pbViewCurrent;
    if (!p) return;
    pbCloseView();
    pbOpenEdit({
        p_id: p.p_id,
        item_name: p.item_name,
        unit_price: p.unit_price,
        quantity: p.quantity,
        discount_percentage: p.discount_percentage,
        tent_type: '',
        size_group: '',
        tent_size: '',
        category: p.location || '',
    });
}

// ── Add Single Product ────────────────────────────────────────────────────────
// Inserts ONE leaf item under an existing category (POST /wl/admin/products).
function pbOpenAddProduct() {
    const err = document.getElementById('pbAddProductError');
    err.classList.add('hidden');
    ['pbAP_Name', 'pbAP_Price', 'pbAP_Disc', 'pbAP_Flat'].forEach(id => { document.getElementById(id).value = ''; });
    document.getElementById('pbAP_Qty').value = '1';
    document.getElementById('pbAP_Nd').checked = false;

    // Tent dropdown (sorted, same order as the browser)
    const tentSel = document.getElementById('pbAP_Tent');
    tentSel.innerHTML = '<option value="">General (no tent)</option>' +
        PB_TENTS.map(t => {
            const label = [t['Tent Type'], t['Tent_Size'], t['Size Group']].filter(Boolean).join(' - ');
            return '<option value="' + t._tentsize_pid + '">' + escHtml(label || ('Tent #' + t._tentsize_pid)) + '</option>';
        }).join('');
    pbAPTentChange();
    document.getElementById('pbAddProductModal').classList.remove('hidden');
}

function pbAPTentChange() {
    const pid     = parseInt(document.getElementById('pbAP_Tent').value, 10);
    const catSel  = document.getElementById('pbAP_Cat');
    const tent    = PB_TENTS.find(t => t._tentsize_pid === pid);
    const cats    = tent ? (tent.Categories || []) : [];
    // No tent selected: the item goes into the default "General" category.
    if (!tent) {
        catSel.disabled = true;
        catSel.innerHTML = '<option value="">General</option>';
        return;
    }
    catSel.disabled = !cats.length;
    catSel.innerHTML = cats.length
        ? cats.map(c => '<option value="' + c._category_pid + '">' + escHtml(c.Category || 'Uncategorised') + '</option>').join('')
        : '<option value="">This tent has no categories</option>';
}

function pbCloseAddProduct() {
    document.getElementById('pbAddProductModal').classList.add('hidden');
}

async function pbSaveAddProduct() {
    const err     = document.getElementById('pbAddProductError');
    const saveBtn = document.getElementById('pbAddProductSaveBtn');
    const refId   = parseInt(document.getElementById('pbAP_Cat').value, 10);
    const name    = (document.getElementById('pbAP_Name').value || '').trim();
    const price   = parseFloat(document.getElementById('pbAP_Price').value);
    const qty     = parseInt(document.getElementById('pbAP_Qty').value, 10);
    const disc    = parseFloat(document.getElementById('pbAP_Disc').value);
    const flat    = parseFloat(document.getElementById('pbAP_Flat').value);
    const nd      = document.getElementById('pbAP_Nd').checked;

    const fail = msg => { err.textContent = msg; err.classList.remove('hidden'); };
    if (!name)                               return fail('Item name cannot be empty.');
    if (!isNaN(price) && price < 0)          return fail('Price must be 0 or more.');
    if (!isNaN(qty) && qty < 0)              return fail('Quantity must be 0 or more.');
    if (!isNaN(disc) && (disc < 0 || disc > 100)) return fail('Discount % must be 0-100.');
    if (!isNaN(flat) && flat < 0)            return fail('Flat discount must be 0 or more.');

    saveBtn.disabled = true; saveBtn.textContent = 'Saving...';
    err.classList.add('hidden');
    try {
        const res = await fetch(API_BASE + '/wl/admin/products', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-API-Key':     API_KEY,
                'Origin':        ORIGIN,
                'Authorization': 'Bearer ' + JWT,
            },
            body: JSON.stringify({
                ref_id: isNaN(refId) || refId <= 0 ? 0 : refId,
                item_name: name,
                unit_price: isNaN(price) ? 0 : price,
                quantity: isNaN(qty) ? 1 : qty,
                discount_percentage: isNaN(disc) ? 0 : disc,
                discount_flat: isNaN(flat) ? 0 : flat,
                non_discountable: nd ? 1 : 0,
            }),
        });
        const data = await res.json();
        if (!res.ok || !data.success) { fail(data.error || 'Save failed.'); return; }
        pbCloseAddProduct();
        await pbLoadTree();
    } catch (e) {
        fail('Network error: ' + e.message);
    } finally {
        saveBtn.disabled = false; saveBtn.textContent = 'Add product';
    }
}
</script>

<!-- Edit Modal -->
<div id="pbEditModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" onclick="if(event.target===this)pbCloseEdit()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-800">Edit Product</h3>
            <button onclick="pbCloseEdit()" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <p id="pbEditContext" class="text-xs text-slate-400 mb-4 truncate"></p>

        <div id="pbEditError" class="hidden mb-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"></div>

        <div class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Item Name</label>
                <input type="text" id="pbEditName" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Price (€)</label>
                    <input type="number" id="pbEditPrice" min="0" step="0.01" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Quantity</label>
                    <input type="number" id="pbEditQty" min="0" step="1" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Discount %</label>
                    <input type="number" id="pbEditDiscount" min="0" max="100" step="0.01" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <button onclick="pbCloseEdit()" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 transition">Cancel</button>
            <button id="pbEditSaveBtn" onclick="pbSaveEdit()" class="px-5 py-2 rounded-lg text-sm font-medium bg-primary text-white hover:bg-blue-700 transition">Save</button>
        </div>
    </div>
</div>

<!-- Add Tent / Add Items Modal (nested structure) -->
<div id="pbAddModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" onclick="if(event.target===this)pbCloseAdd()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 id="pbAddTitle" class="text-lg font-bold text-slate-800">Add Tent</h3>
            <button onclick="pbCloseAdd()" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="overflow-y-auto px-6 py-5 space-y-5">
            <div id="pbAddError" class="hidden rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"></div>

            <!-- Tent identity -->
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Tent</p>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tent Type</label>
                        <input type="text" id="pbAdd_Tent_Type" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Size Group</label>
                        <input type="text" id="pbAdd_Size_Group" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tent Size</label>
                        <input type="text" id="pbAdd_Tent_Size" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">SKU <span class="text-slate-400 font-normal">(optional)</span></label>
                        <input type="text" id="pbAdd_sku" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>

            <!-- Specifications -->
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Specifications <span class="text-slate-400 font-normal normal-case">(optional)</span></p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Seated</label>
                        <input type="text" id="pbAdd_Seated" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Cocktail</label>
                        <input type="text" id="pbAdd_Cocktail" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Cinema</label>
                        <input type="text" id="pbAdd_Cinema" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Coating</label>
                        <input type="text" id="pbAdd_Coating" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Weight</label>
                        <input type="text" id="pbAdd_Weight" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Packed Size</label>
                        <input type="text" id="pbAdd_Packed_Size" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Colours</label>
                        <input type="text" id="pbAdd_Colours" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Area m2</label>
                        <input type="text" id="pbAdd_Area_m2" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Centerpole</label>
                        <input type="text" id="pbAdd_Centerpole_Config" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Bag Size</label>
                        <input type="text" id="pbAdd_Bag_Size" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Parts Bags</label>
                        <input type="text" id="pbAdd_Parts_Bags" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Anchor Bags</label>
                        <input type="text" id="pbAdd_Anchor_Bags" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>

            <!-- Categories + items -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Categories &amp; Items</p>
                    <button type="button" onclick="pbAddCategoryRow()" class="text-xs text-primary font-medium hover:underline">+ Add category</button>
                </div>
                <div id="pbAddCategories" class="space-y-3"></div>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100">
            <button onclick="pbCloseAdd()" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 transition">Cancel</button>
            <button id="pbAddSaveBtn" onclick="pbSaveAdd()" class="px-5 py-2 rounded-lg text-sm font-medium bg-primary text-white hover:bg-blue-700 transition">Save</button>
        </div>
    </div>
</div>

<!-- Import JSON Modal -->
<div id="pbImportModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" onclick="if(event.target===this)pbCloseImport()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Import JSON</h3>
            <button onclick="pbCloseImport()" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto px-6 py-5 space-y-3">
            <p class="text-xs text-slate-500">Paste a single tent object, an array of tents, or <code class="bg-slate-100 px-1 rounded">{ "tents": [ ... ] }</code>. Existing nodes are reused; only new items are inserted.</p>
            <div id="pbImportError" class="hidden rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"></div>
            <div id="pbImportMsg" class="hidden rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"></div>
            <textarea id="pbImportText" rows="14" spellcheck="false" placeholder='{ "Tent Type": "Stretch", "Tent_Size": "4,5x4,5", "Categories": [ ... ] }'
                      class="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100">
            <button onclick="pbCloseImport()" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 transition">Cancel</button>
            <button id="pbImportBtn" onclick="pbRunImport()" class="px-5 py-2 rounded-lg text-sm font-medium bg-primary text-white hover:bg-blue-700 transition">Import</button>
        </div>
    </div>
</div>

<!-- Show Single Item Modal -->
<div id="pbViewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" onclick="if(event.target===this)pbCloseView()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 max-h-[85vh] overflow-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Item details</h3>
            <button onclick="pbCloseView()" class="text-slate-300 hover:text-slate-500 transition p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-4">
            <div id="pbViewError" class="hidden mb-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"></div>
            <div id="pbViewBody"></div>
        </div>
    </div>
</div>

<!-- Add Single Product Modal -->
<div id="pbAddProductModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" onclick="if(event.target===this)pbCloseAddProduct()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[85vh] overflow-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Add a single product</h3>
            <button onclick="pbCloseAddProduct()" class="text-slate-300 hover:text-slate-500 transition p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-4 space-y-3">
            <div id="pbAddProductError" class="hidden rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"></div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Tent <span class="text-slate-300">(optional)</span></label>
                    <select id="pbAP_Tent" onchange="pbAPTentChange()" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"></select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Category <span class="text-slate-300">(optional)</span></label>
                    <select id="pbAP_Cat" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"></select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Item name</label>
                <input type="text" id="pbAP_Name" placeholder="e.g. Carabiner 10mm" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Price</label>
                    <input type="number" id="pbAP_Price" min="0" step="0.01" placeholder="0.00" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Qty</label>
                    <input type="number" id="pbAP_Qty" min="0" step="1" value="1" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Disc %</label>
                    <input type="number" id="pbAP_Disc" min="0" max="100" step="0.01" placeholder="0" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Flat &euro;</label>
                    <input type="number" id="pbAP_Flat" min="0" step="0.01" placeholder="0.00" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" id="pbAP_Nd" class="w-4 h-4"> Non-discountable
            </label>
        </div>
        <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100">
            <button onclick="pbCloseAddProduct()" class="px-4 py-2 rounded-lg text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Cancel</button>
            <button id="pbAddProductSaveBtn" onclick="pbSaveAddProduct()" class="px-5 py-2 rounded-lg text-sm font-medium bg-primary text-white hover:bg-blue-700 transition">Add product</button>
        </div>
    </div>
</div>

<!-- Category-name autocomplete suggestions (populated from the catalogue) -->
<datalist id="pbCatSuggestions"></datalist>

