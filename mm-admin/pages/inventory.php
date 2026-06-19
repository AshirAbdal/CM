<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
if (!defined('API_BASE')) define('API_BASE', $_is_local ? 'http://localhost:8000'   : 'https://apiv1.clickdigim.com');
if (!defined('API_KEY'))  define('API_KEY',  'mq-prod-public-key-001');
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
                    <div class="bg-slate-50 rounded p-2 text-xs text-slate-500 font-mono mb-3">Tent_Type, Size_Group, Tent_Size, Seated, Cocktail, Cinema, Coating, Weight, Packed_Size, Colours, Area_m2, Centerpole_Config, Bag_Size, Parts_Bags, Anchor_Bags, Category, Item_Name, Price, Quantity</div>
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
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
        <div class="flex gap-3">
            <input type="text" id="discountSearch"
                   placeholder="Search by item name or category… e.g. Carabiner, Rigging Part, Size 4,5x4,5"
                   class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                   oninput="debounceSearch()">
            <button onclick="runSearch()" class="bg-primary text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">Search</button>
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
<!-- PRODUCT BROWSER - infinite-scroll CSV-style view of all products      -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<div class="mt-10">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Product Browser</h2>
            <p class="text-slate-500 mt-1 text-sm" id="productTotal">Loading products…</p>
        </div>
    </div>
    <!-- Filter bar -->
    <div class="flex flex-wrap gap-2 mb-3">
        <input type="text" id="productSearchInput" placeholder="Search item, SKU, category, weight…"
               class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-primary"
               oninput="debounceProductSearch()">
        <select id="pbFilterTentType" onchange="pbReset()"
                class="border border-slate-200 rounded-lg px-3 py-2 text-sm flex-1 sm:flex-none min-w-[8rem] focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">All Tent Types</option>
        </select>
        <input type="text" id="pbFilterWeight" placeholder="Weight (e.g. 15kg)"
               class="border border-slate-200 rounded-lg px-3 py-2 text-sm flex-1 sm:flex-none sm:w-36 focus:outline-none focus:ring-2 focus:ring-primary"
               oninput="debounceProductSearch()">
        <input type="text" id="pbFilterSku" placeholder="SKU"
               class="border border-slate-200 rounded-lg px-3 py-2 text-sm flex-1 sm:flex-none sm:w-40 focus:outline-none focus:ring-2 focus:ring-primary"
               oninput="debounceProductSearch()">
        <button onclick="pbClearFilters()" class="text-xs border border-slate-200 text-slate-500 px-3 py-2 rounded-lg hover:bg-slate-50 transition">Clear filters</button>
    </div>

    <!-- Scrollable wrapper - table on desktop, cart-style cards on mobile -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div id="productScrollBox" class="overflow-auto" style="max-height:600px;">
            <table class="w-full text-xs whitespace-nowrap hidden md:table">
                <thead class="bg-slate-50 border-b border-slate-200" style="position:sticky;top:0;z-index:10;">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Tent Type</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Size Group</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Tent Size</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Seated</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Cocktail</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Cinema</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Coating</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Weight</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Packed Size</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Colours</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Area m²</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Centerpole</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Bag</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Parts</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Anchors</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Category</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-slate-500 uppercase tracking-wide">Item Name</th>
                        <th class="px-3 py-2.5 text-right font-semibold text-slate-500 uppercase tracking-wide">Price</th>
                        <th class="px-3 py-2.5 text-right font-semibold text-slate-500 uppercase tracking-wide">Qty</th>
                        <th class="px-3 py-2.5 text-right font-semibold text-slate-500 uppercase tracking-wide">Discount %</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-500 uppercase tracking-wide">Edit</th>
                    </tr>
                </thead>
                <tbody id="productTableBody" class="divide-y divide-slate-100">
                    <tr><td colspan="21" class="px-4 py-8 text-center text-slate-400">Loading…</td></tr>
                </tbody>
            </table>
            <!-- Mobile cart-style card list (same data, shown < md) -->
            <div id="productCardList" class="md:hidden p-3 space-y-3">
                <p class="py-8 text-center text-slate-400 text-sm">Loading…</p>
            </div>
            <!-- Sentinel element – observed to trigger loading the next batch -->
            <div id="productSentinel" class="h-1"></div>
        </div>
        <!-- Loading spinner shown while fetching next batch -->
        <div id="productLoadingBar" class="hidden flex items-center justify-center gap-2 px-4 py-3 border-t border-slate-100 bg-slate-50 text-xs text-slate-400">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            Loading more…
        </div>
    </div>
</div>

<script>
// ── Product Browser - infinite scroll ────────────────────────────────────────
const PB_LIMIT   = 50;
let   pbPage      = 1;
let   pbTotal     = 0;
let   pbLoading   = false;
let   pbExhausted = false;
let   pbQuery     = '';
let   pbTimer     = null;
let   pbLastKey   = '';
let   pbObserver  = null;

document.addEventListener('DOMContentLoaded', () => {
    pbInitObserver();
    pbPopulateTentTypes();
    pbReset();
});

function debounceProductSearch() {
    clearTimeout(pbTimer);
    pbTimer = setTimeout(() => pbReset(), 350);
}

function pbClearFilters() {
    document.getElementById('productSearchInput').value = '';
    document.getElementById('pbFilterTentType').value   = '';
    document.getElementById('pbFilterWeight').value     = '';
    document.getElementById('pbFilterSku').value        = '';
    pbReset();
}

async function pbPopulateTentTypes() {
    try {
        const res  = await fetch(API_BASE + '/wl/admin/products?page=1&limit=500', {
            headers: { 'X-API-Key': API_KEY, 'Origin': ORIGIN, 'Authorization': 'Bearer ' + JWT }
        });
        const data = await res.json();
        const types = [...new Set((data.rows || []).map(r => r.tent_type).filter(Boolean))].sort();
        const sel   = document.getElementById('pbFilterTentType');
        types.forEach(t => {
            const o = document.createElement('option');
            o.value = t; o.textContent = t;
            sel.appendChild(o);
        });
    } catch (_) {}
}

function pbReset() {
    pbPage      = 1;
    pbTotal     = 0;
    pbLoading   = false;
    pbExhausted = false;
    pbLastKey   = '';
    pbQuery     = document.getElementById('productSearchInput').value.trim();

    const tbody = document.getElementById('productTableBody');
    tbody.innerHTML = '<tr><td colspan="21" class="px-4 py-8 text-center text-slate-400">Loading…</td></tr>';
    document.getElementById('productCardList').innerHTML = '<p class="py-8 text-center text-slate-400 text-sm">Loading…</p>';
    document.getElementById('productTotal').textContent = 'Loading products…';

    pbFetch();
}

function pbBuildUrl() {
    const q         = document.getElementById('productSearchInput').value.trim();
    const tentType  = document.getElementById('pbFilterTentType').value.trim();
    const weight    = document.getElementById('pbFilterWeight').value.trim();
    const sku       = document.getElementById('pbFilterSku').value.trim();
    // Combine all into one q param - backend searches item_name, sku, weight, tent_type
    const parts = [q, tentType, weight, sku].filter(Boolean);
    const combined = parts.join(' ');
    return API_BASE + '/wl/admin/products?page=' + pbPage + '&limit=' + PB_LIMIT
         + (combined ? '&q=' + encodeURIComponent(combined) : '');
}

async function pbFetch() {
    if (pbLoading || pbExhausted) return;
    pbLoading = true;
    document.getElementById('productLoadingBar').classList.remove('hidden');

    try {
        const url = pbBuildUrl();
        const res = await fetch(url, {
            headers: {
                'X-API-Key':     API_KEY,
                'Origin':        ORIGIN,
                'Authorization': 'Bearer ' + JWT,
            }
        });
        const data = await res.json();
        const tbody = document.getElementById('productTableBody');
        const cardList = document.getElementById('productCardList');

        if (!res.ok) {
            tbody.innerHTML = '<tr><td colspan="21" class="px-4 py-8 text-center text-red-400">' + escHtml(data.error || 'Error') + '</td></tr>';
            cardList.innerHTML = '<p class="py-8 text-center text-red-400 text-sm">' + escHtml(data.error || 'Error') + '</p>';
            pbExhausted = true;
            return;
        }

        pbTotal = data.total;

        if (pbPage === 1) { tbody.innerHTML = ''; cardList.innerHTML = ''; }

        if (!data.rows.length && pbPage === 1) {
            tbody.innerHTML = '<tr><td colspan="21" class="px-4 py-8 text-center text-slate-400">No products found.</td></tr>';
            cardList.innerHTML = '<p class="py-8 text-center text-slate-400 text-sm">No products found.</p>';
            pbExhausted = true;
        } else {
            pbAppendRows(data.rows, tbody, cardList);
            pbPage++;
            if (pbPage > data.pages) pbExhausted = true;
        }

        const loaded = Math.min(pbPage > 1 ? (pbPage - 1) * PB_LIMIT : data.rows.length, pbTotal);
        document.getElementById('productTotal').textContent =
            'Showing ' + Math.min(loaded, pbTotal) + ' of ' + pbTotal + ' items'
            + (pbQuery ? ' matching "' + escHtml(pbQuery) + '"' : '');

    } catch (e) {
        document.getElementById('productTableBody').innerHTML =
            '<tr><td colspan="21" class="px-4 py-8 text-center text-red-400">Network error: ' + escHtml(e.message) + '</td></tr>';
        document.getElementById('productCardList').innerHTML =
            '<p class="py-8 text-center text-red-400 text-sm">Network error: ' + escHtml(e.message) + '</p>';
        pbExhausted = true;
    } finally {
        pbLoading = false;
        document.getElementById('productLoadingBar').classList.add('hidden');
    }
}

function pbAppendRows(rows, tbody, cardList) {
    const frag     = document.createDocumentFragment();
    const cardFrag = document.createDocumentFragment();
    rows.forEach(r => {
        const key   = r.tent_type + '||' + r.size_group + '||' + r.tent_size;
        const isNew = key !== pbLastKey && pbLastKey !== '';
        pbLastKey   = key;

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-blue-50/30 transition' + (isNew ? ' border-t-2 border-slate-300' : '');

        const disc  = r.discount_percentage > 0
            ? `<span class="inline-block px-1.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">${r.discount_percentage}%</span>`
            : `<span class="text-slate-300">-</span>`;
        const price = r.unit_price > 0
            ? `€${r.unit_price.toFixed(2)}`
            : `<span class="text-slate-300">-</span>`;

        tr.innerHTML = `
            <td class="px-3 py-1.5 text-slate-700 font-medium">${escHtml(r.tent_type) || '<span class="text-slate-300">-</span>'}</td>
            <td class="px-3 py-1.5 text-slate-600">${escHtml(r.size_group) || '<span class="text-slate-300">-</span>'}</td>
            <td class="px-3 py-1.5 text-slate-700 font-semibold">${escHtml(r.tent_size) || '<span class="text-slate-300">-</span>'}</td>
            <td class="px-3 py-1.5 text-slate-500">${escHtml(r.seated) || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${escHtml(r.cocktail) || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${escHtml(r.cinema) || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${escHtml(r.coating) || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${escHtml(r.weight) || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${escHtml(r.packed_size) || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500 max-w-[120px] truncate" title="${escHtml(r.colours)}">${escHtml(r.colours) || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${r.area_m2 || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${escHtml(r.centerpole_config) || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${escHtml(r.bag_size) || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${r.parts_bags || '-'}</td>
            <td class="px-3 py-1.5 text-slate-500">${r.anchor_bags || '-'}</td>
            <td class="px-3 py-1.5 text-blue-600 font-medium">${escHtml(r.category)}</td>
            <td class="px-3 py-1.5 text-slate-800 font-semibold">${escHtml(r.item_name)}</td>
            <td class="px-3 py-1.5 text-right text-slate-700">${price}</td>
            <td class="px-3 py-1.5 text-right text-slate-700 font-semibold">${r.quantity}</td>
            <td class="px-3 py-1.5 text-right">${disc}</td>
            <td class="px-3 py-1.5 text-center">
                <button onclick="pbOpenEdit(${JSON.stringify(r).replace(/"/g,'&quot;')})"
                        class="text-slate-400 hover:text-primary transition" title="Edit">
                    <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                </button>
            </td>`;
        frag.appendChild(tr);

        if (cardList) cardFrag.appendChild(pbBuildCard(r));
    });
    tbody.appendChild(frag);
    if (cardList) cardList.appendChild(cardFrag);
}

// Build a single cart-style product card for the mobile view
function pbBuildCard(r) {
    const card = document.createElement('div');
    card.className = 'border border-slate-200 rounded-xl bg-white p-4 shadow-sm';

    const discBadge = r.discount_percentage > 0
        ? `<span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold bg-green-100 text-green-700">${r.discount_percentage}% off</span>`
        : '';
    const priceTxt = r.unit_price > 0 ? `€${r.unit_price.toFixed(2)}` : '-';
    const context  = [r.tent_type, r.size_group, r.tent_size].filter(Boolean).map(escHtml).join(' › ');

    // All spec fields - only render the ones that have a value
    const specs = [
        ['Seated', r.seated], ['Cocktail', r.cocktail], ['Cinema', r.cinema],
        ['Coating', r.coating], ['Weight', r.weight], ['Packed Size', r.packed_size],
        ['Colours', r.colours], ['Area m²', r.area_m2], ['Centerpole', r.centerpole_config],
        ['Bag', r.bag_size], ['Parts', r.parts_bags], ['Anchors', r.anchor_bags],
    ];
    const specRows = specs
        .filter(([, v]) => v !== null && v !== undefined && v !== '' && v !== 0)
        .map(([label, v]) => `
            <div class="flex justify-between gap-2">
                <span class="text-slate-400">${label}</span>
                <span class="text-slate-700 text-right truncate">${escHtml(v)}</span>
            </div>`).join('');

    card.innerHTML = `
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                ${r.category ? `<p class="text-[11px] text-blue-600 font-medium uppercase tracking-wide truncate">${escHtml(r.category)}</p>` : ''}
                <h3 class="text-sm font-semibold text-slate-800 break-words">${escHtml(r.item_name)}</h3>
                ${context ? `<p class="text-xs text-slate-400 mt-0.5">${context}</p>` : ''}
            </div>
            <button onclick="pbOpenEdit(${JSON.stringify(r).replace(/"/g,'&quot;')})"
                    class="flex-shrink-0 text-slate-400 hover:text-primary transition p-1" title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
            </button>
        </div>
        <div class="flex flex-wrap items-center gap-2 mt-3">
            <span class="text-lg font-bold text-slate-800">${priceTxt}</span>
            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-medium">Qty: ${r.quantity}</span>
            ${discBadge}
        </div>
        ${specRows ? `<div class="grid grid-cols-2 gap-x-4 gap-y-1 mt-3 pt-3 border-t border-slate-100 text-xs">${specRows}</div>` : ''}`;
    return card;
}

function pbInitObserver() {
    const sentinel = document.getElementById('productSentinel');
    const scrollBox = document.getElementById('productScrollBox');

    pbObserver = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) pbFetch();
    }, {
        root:       scrollBox,   // observe within the scroll container
        rootMargin: '0px',
        threshold:  0
    });
    pbObserver.observe(sentinel);
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
        pbReset(); // refresh the table
    } catch (e) {
        errEl.textContent = 'Network error: ' + e.message;
        errEl.classList.remove('hidden');
    } finally {
        saveBtn.disabled    = false;
        saveBtn.textContent = 'Save';
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
