<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$privacyEn = [
    ['title' => 'Introduction', 'body' => 'PT Majestic Marquees and Tents ("we," "us," or "our") respects your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit https://www.majesticmarquees.com (the "Website"), use our services, or interact with our CRM (GoHighLevel). This policy complies with the EU General Data Protection Regulation (GDPR), the California Privacy Rights Act (CPRA), and the Indonesian Personal Data Protection Law (UU PDP).'],
    ['title' => 'Information We Collect', 'body' => 'We may collect the following personal data:', 'list' => [
        ['label' => 'Contact Information:', 'text' => 'Name, email address, phone number, and billing/delivery address.'],
        ['label' => 'Event Details:', 'text' => 'Venue locations, event dates, and specific requirements for marquees/tents.'],
        ['label' => 'Technical Data:', 'text' => 'IP address, browser type, device information, and browsing activity (collected via cookies).'],
        ['label' => 'Communications:', 'text' => 'Records of correspondence when you contact us via email, phone, or web forms.'],
    ]],
    ['title' => 'How We Use Your Information', 'body' => 'We use your data based on legitimate business purposes, contract fulfillment, and your consent:', 'list' => [
        'To provide and manage our marquee and tent hire services.',
        'To process payments and send invoices.',
        'To manage leads and customer communications via our CRM (GoHighLevel).',
        'To deliver targeted advertising (Meta/Facebook Pixel) and analyze website traffic (Google Analytics), subject to your cookie consent.',
        'To comply with legal and tax obligations in Indonesia.',
    ]],
    ['title' => 'Data Sharing and Disclosure', 'body' => 'We do not sell your personal data. We only share information with third parties necessary to operate our business:', 'list' => [
        ['label' => 'Service Providers:', 'text' => 'Logistics/transport partners, CRM platforms (GoHighLevel), payment gateways, and IT support.'],
        ['label' => 'Legal Authorities:', 'text' => 'When required by Indonesian law or court order.'],
    ]],
    ['title' => 'Your Global Privacy Rights', 'body' => 'Depending on your location, you have specific rights regarding your personal data:', 'list' => [
        ['label' => 'GDPR (EU/UK) & UU PDP (Indonesia):', 'text' => 'You have the right to access, rectify, delete (right to be forgotten), restrict processing, and port your data. You may withdraw consent at any time.'],
        ['label' => 'CCPA/CPRA (California):', 'text' => 'You have the Right to Know what data we collect, the Right to Delete, the Right to Correct, and the Right to Opt-Out of the "Sale" or "Sharing" of personal information (e.g., opting out of third-party targeted advertising cookies). We do not discriminate against users who exercise these rights.'],
    ]],
    ['title' => 'Data Retention and Security', 'body' => 'We implement industry-standard administrative and technical security measures to protect your data. We retain personal information only for as long as necessary to fulfill the purposes outlined in this policy or to comply with legal tax retention requirements (typically up to 7 years in Indonesia).'],
    ['title' => 'Contact Us', 'body' => 'To exercise your rights or ask questions about this policy, please contact our Data Protection Officer:', 'list' => [
        ['label' => 'Director:', 'text' => 'Vincent Klinkenberg'],
        ['label' => 'Email:', 'text' => 'hello@majesticmarquees.com'],
        ['label' => 'Address:', 'text' => 'Jalan Raya Kuta 32, Kuta, Badung, Bali 80361, Indonesia'],
    ]],
];

$privacyId = [
    ['title' => 'Pendahuluan', 'body' => 'PT Majestic Marquees and Tents ("kami") menghormati privasi Anda. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, mengungkapkan, dan melindungi informasi Anda saat Anda mengunjungi https://www.majesticmarquees.com ("Situs Web"), menggunakan layanan kami, atau berinteraksi dengan CRM kami. Kebijakan ini mematuhi Peraturan Perlindungan Data Umum UE (GDPR), Undang-Undang Hak Privasi California (CPRA), dan Undang-Undang Pelindungan Data Pribadi (UU PDP) Indonesia.'],
    ['title' => 'Informasi yang Kami Kumpulkan', 'body' => 'Kami dapat mengumpulkan data pribadi berikut:', 'list' => [
        ['label' => 'Informasi Kontak:', 'text' => 'Nama, alamat email, nomor telepon, dan alamat penagihan/pengiriman.'],
        ['label' => 'Detail Acara:', 'text' => 'Lokasi acara, tanggal acara, dan kebutuhan spesifik untuk tenda.'],
        ['label' => 'Data Teknis:', 'text' => 'Alamat IP, jenis browser, informasi perangkat, dan aktivitas penelusuran (dikumpulkan melalui cookie).'],
        ['label' => 'Komunikasi:', 'text' => 'Catatan korespondensi saat Anda menghubungi kami melalui email, telepon, atau formulir web.'],
    ]],
    ['title' => 'Bagaimana Kami Menggunakan Informasi Anda', 'body' => 'Kami menggunakan data Anda berdasarkan tujuan bisnis yang sah, pelaksanaan kontrak, dan persetujuan Anda:', 'list' => [
        'Untuk menyediakan dan mengelola layanan penyewaan tenda kami.',
        'Untuk memproses pembayaran dan mengirimkan faktur.',
        'Untuk mengelola prospek (leads) dan komunikasi pelanggan melalui CRM kami (GoHighLevel).',
        'Untuk menyampaikan iklan yang ditargetkan (Meta Pixel) dan menganalisis lalu lintas web (Google Analytics), tunduk pada persetujuan cookie Anda.',
        'Untuk mematuhi kewajiban hukum dan pajak di Indonesia.',
    ]],
    ['title' => 'Pembagian dan Pengungkapan Data', 'body' => 'Kami tidak menjual data pribadi Anda. Kami hanya membagikan informasi dengan pihak ketiga yang diperlukan untuk menjalankan bisnis kami:', 'list' => [
        ['label' => 'Penyedia Layanan:', 'text' => 'Mitra logistik/transportasi, platform CRM, gateway pembayaran, dan dukungan TI.'],
        ['label' => 'Otoritas Hukum:', 'text' => 'Jika diwajibkan oleh hukum Indonesia atau perintah pengadilan.'],
    ]],
    ['title' => 'Hak Privasi Global Anda', 'body' => 'Bergantung pada lokasi Anda, Anda memiliki hak spesifik terkait data pribadi Anda:', 'list' => [
        ['label' => 'GDPR (UE/Inggris) & UU PDP (Indonesia):', 'text' => 'Anda berhak untuk mengakses, memperbaiki, menghapus (hak untuk dilupakan), membatasi pemrosesan, dan memindahkan data Anda. Anda dapat menarik persetujuan kapan saja.'],
        ['label' => 'CCPA/CPRA (California):', 'text' => 'Anda memiliki Hak untuk Mengetahui, Hak untuk Menghapus, Hak untuk Memperbaiki, dan Hak untuk Menolak "Penjualan" atau "Pembagian" informasi pribadi (misalnya, menolak cookie iklan pihak ketiga).'],
    ]],
    ['title' => 'Penyimpanan dan Keamanan Data', 'body' => 'Kami menerapkan langkah-langkah keamanan untuk melindungi data Anda. Kami menyimpan informasi pribadi hanya selama diperlukan untuk memenuhi tujuan dalam kebijakan ini atau untuk mematuhi kewajiban penyimpanan pajak hukum (biasanya hingga 7 tahun di Indonesia).'],
    ['title' => 'Hubungi Kami', 'body' => 'Untuk melaksanakan hak Anda atau jika ada pertanyaan, silakan hubungi kami di:', 'list' => [
        ['label' => 'Direktur:', 'text' => 'Vincent Klinkenberg'],
        ['label' => 'Email:', 'text' => 'hello@majesticmarquees.com'],
        ['label' => 'Alamat:', 'text' => 'Jalan Raya Kuta 32, Kuta, Badung, Bali 80361, Indonesia'],
    ]],
];
?>

<section class="section bg-[#f5f1e8]">
    <div class="container-x">
        <article class="max-w-4xl mx-auto">
            <header class="text-forest-800">
                <h1 class="font-sans text-xl sm:text-2xl font-bold uppercase leading-snug">Privacy Policy / Kebijakan Privasi</h1>
                <p class="mt-2 font-bold"><span class="uppercase">PT Majestic Marquees and Tents</span> Last Updated: 26 February 2026</p>
            </header>

            <div class="mt-10">
                <?php render_legal_bilingual($privacyEn, $privacyId, 'Part A: English Version', 'Part B: Bahasa Indonesia Version'); ?>
            </div>
        </article>
    </div>
</section>
