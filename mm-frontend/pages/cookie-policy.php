<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$cookieEn = [
    ['title' => 'Introduction', 'body' => 'PT Majestic Marquees and Tents utilizes cookies on https://www.majesticmarquees.com. This policy complies with global privacy regulations (GDPR, CCPA/CPRA, UU PDP, Australian Privacy Act).'],
    ['title' => 'What Are Cookies?', 'body' => 'Cookies are small data files placed on your device to make websites work, improve efficiency, and provide reporting information (Session, Persistent, First-Party, and Third-Party cookies).'],
    ['title' => 'Types of Cookies We Use', 'list' => [
        ['label' => 'Strictly Necessary:', 'text' => 'Essential for secure site function (no consent required).'],
        ['label' => 'Performance / Analytics:', 'text' => 'Analyzes visitor interactions via Google Analytics (opt-in required in EU/UK).'],
        ['label' => 'Functional:', 'text' => 'Remembers preferences (opt-in required).'],
        ['label' => 'Targeting / Advertising:', 'text' => 'Used by Meta (Facebook) Pixel for relevant ads (opt-out required under CCPA).'],
    ]],
    ['title' => 'Your Consent and Management', 'body' => 'You can accept, reject, or customize cookies via our Cookie Consent Banner on your first visit. You can withdraw consent or opt-out anytime by clicking "Manage Cookie Preferences" in our website footer or adjusting your browser settings. California residents may use the Global Privacy Control (GPC) signal to opt-out of the "sharing" of data for targeted ads.'],
    ['title' => 'Contact Us', 'body' => 'Email: hello@majesticmarquees.com | Address: Jalan Raya Kuta 32, Bali 80361, Indonesia.'],
];

$cookieId = [
    ['title' => 'Pendahuluan', 'body' => 'PT Majestic Marquees and Tents menggunakan cookie pada situs web https://www.majesticmarquees.com. Kebijakan ini mematuhi peraturan privasi global (GDPR, CCPA/CPRA, UU PDP Indonesia, dan Undang-Undang Privasi Australia).'],
    ['title' => 'Apa Itu Cookie?', 'body' => 'Cookie adalah file data kecil yang ditempatkan pada perangkat Anda agar situs web dapat berfungsi, meningkatkan efisiensi, dan memberikan informasi analitik (terdiri dari Cookie Sesi, Persisten, Pihak Pertama, dan Pihak Ketiga).'],
    ['title' => 'Jenis Cookie yang Kami Gunakan', 'list' => [
        ['label' => 'Sangat Diperlukan (Strictly Necessary):', 'text' => 'Penting untuk fungsi situs yang aman (tidak memerlukan persetujuan).'],
        ['label' => 'Performa / Analitik:', 'text' => 'Menganalisis interaksi pengunjung melalui Google Analytics (memerlukan persetujuan keikutsertaan/opt-in di UE/Inggris Raya).'],
        ['label' => 'Fungsional:', 'text' => 'Mengingat preferensi dan pengaturan Anda (memerlukan persetujuan).'],
        ['label' => 'Penargetan / Periklanan:', 'text' => 'Digunakan oleh Meta (Facebook) Pixel untuk menayangkan iklan yang relevan (hak menolak/opt-out berdasarkan CCPA).'],
    ]],
    ['title' => 'Persetujuan dan Pengelolaan Anda', 'body' => 'Anda dapat menerima, menolak, atau menyesuaikan cookie melalui Spanduk Persetujuan Cookie kami pada kunjungan pertama Anda. Anda dapat menarik persetujuan atau menyisih (opt-out) kapan saja dengan mengklik "Kelola Preferensi Cookie" di bagian bawah situs web kami atau menyesuaikan pengaturan browser Anda. Penduduk California dapat menggunakan sinyal Kontrol Privasi Global (GPC) untuk menolak "pembagian" data untuk iklan yang ditargetkan.'],
    ['title' => 'Hubungi Kami', 'body' => 'Email: hello@majesticmarquees.com | Alamat: Jalan Raya Kuta 32, Kuta, Badung, Bali 80361, Indonesia.'],
];
?>

<section class="section bg-[#f5f1e8]">
    <div class="container-x">
        <article class="max-w-4xl mx-auto">
            <header class="text-forest-800">
                <h1 class="font-sans text-xl sm:text-2xl font-bold uppercase leading-snug">Cookie Policy / Kebijakan Cookie</h1>
                <p class="mt-2 font-bold">Last Updated: 26 February 2026</p>
            </header>

            <div class="mt-10">
                <?php render_legal_bilingual($cookieEn, $cookieId, 'Part A: English Version', 'Part B: Bahasa Indonesia Version'); ?>
            </div>
        </article>
    </div>
</section>
