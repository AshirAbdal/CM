<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$company = [
    'Entity'   => 'PT Majestic Marquees and Tents',
    'Address'  => 'Jalan Raya Kuta 32, Desa/Kelurahan Kuta, Kec. Kuta, Kab. Badung, Bali 80361, Indonesia',
    'NIB'      => '2312250027113',
    'NPWP'     => '1000000007406291',
    'Director' => 'Vincent Klinkenberg',
    'Email'    => 'hello@majesticmarquees.com',
];

$termsEn = [
    ['title' => 'Definitions', 'body' => '"Agreement" refers to these Terms combined with the quotation and order confirmation. "Equipment" refers to all tents, marquees, flooring, and accessories hired or sold by Majestic. "Client" is the purchasing or hiring party.'],
    ['title' => 'Applicability', 'body' => 'These Terms apply to all offers, agreements, sales, and rentals by Majestic. Any general terms of the Client are explicitly rejected.'],
    ['title' => 'Offers and Quotations', 'body' => 'All offers are obligation-free. Majestic is not bound by manifest errors or obvious typos in pricing. A composite quotation does not obligate Majestic to fulfill a portion of the order for a proportional price.'],
    ['title' => 'Formation of the Agreement', 'body' => 'An Agreement is legally binding once the Client accepts the quotation in writing or makes the first deposit payment.'],
    ['title' => 'Custom Orders (Maatwerk)', 'body' => 'For bespoke or custom-manufactured equipment, once production begins, the order cannot be canceled. The Client is liable for 100% of the agreed price upon cancellation of custom orders.'],
    ['title' => 'Prices and Taxes', 'body' => 'All prices exclude VAT (PPN), local taxes, and permit fees unless explicitly stated. Any unforeseen logistical surcharges will be billed to the Client.'],
    ['title' => 'Payment Terms', 'body' => 'Unless otherwise agreed, a 50% deposit is required to secure the booking and initiate production/preparation. The remaining 50% must be cleared before delivery or installation. Late payments incur a 1% monthly interest rate.'],
    ['title' => 'Delivery Time', 'body' => 'Stated delivery times are indicative. Minor delays do not entitle the Client to terminate the Agreement or claim compensation.'],
    ['title' => 'Delivery and Risk', 'body' => 'Risk transfers to the Client the moment the Equipment arrives at the Site. If the Client fails to accept delivery, Majestic may store the Equipment at the Client\'s risk and expense.'],
    ['title' => 'Transport', 'body' => 'If Majestic arranges transport via third-party logistics at the Client\'s request, the risk of transit damage lies with the Client.'],
    ['title' => 'Site Preparation and Conditions', 'body' => 'The Client must ensure the Site is level, clear of debris, accessible for large vehicles, and structurally sound for tent anchoring.'],
    ['title' => 'Underground Utilities', 'body' => 'The Client is solely responsible for marking all underground pipes, cables, and utilities. Majestic is not liable for damage to hidden utilities during pegging/anchoring.'],
    ['title' => 'Permits and Client Obligations', 'body' => 'The Client must secure and pay for all necessary local permits (e.g., Banjar, police, municipal) required for the event and installation.'],
    ['title' => 'Assembly and Installation', 'body' => 'Majestic\'s crew will install the Equipment according to the agreed floor plan. Once installed, the Client may not move or alter the structural integrity of the Equipment.'],
    ['title' => 'Use and Care of Equipment', 'body' => 'The Equipment must be used for its intended purpose. No open flames, cooking, or modifying of the tents are permitted without Majestic\'s written consent.'],
    ['title' => 'Moisture Control and Maintenance', 'body' => 'Moisture is the primary risk to tent fabric. If Equipment becomes wet, the Client must allow professional drying. Storing or packing wet Equipment by the Client will result in liability for mold/rot damage.'],
    ['title' => 'Hire Period and Return', 'body' => 'The Hire Period is strictly as stated in the Agreement. Late returns caused by the Client will incur additional daily hire rates.'],
    ['title' => 'Retention of Title (For Sales)', 'body' => 'For Equipment sold to the Client, Majestic retains full legal ownership until the invoice is paid in 100% full.'],
    ['title' => 'Inspection and Complaints', 'body' => 'The Client must inspect the Equipment upon handover. Visible defects must be reported in writing within 24 hours. Failure to report voids the right to claim.'],
    ['title' => 'Warranties', 'body' => 'Majestic warrants that the Equipment is fit for purpose at the time of delivery. Fair wear and tear are excluded from any warranty claims.'],
    ['title' => 'Liability', 'body' => 'Majestic\'s total liability for any claim is capped at the invoice value of the specific Agreement. Majestic is never liable for indirect, consequential, or reputational damage.'],
    ['title' => 'Indemnification', 'body' => 'The Client indemnifies Majestic against any third-party claims relating to the event, the Site, or the Client\'s use of the Equipment.'],
    ['title' => 'Force Majeure', 'body' => 'Majestic is not liable for performance failures due to extreme weather (e.g., winds exceeding manufacturer safety limits), natural disasters, pandemics, or government actions.'],
    ['title' => 'Suspension and Termination', 'body' => 'Majestic may immediately suspend services or terminate the Agreement if the Client goes bankrupt, breaches safety rules, or fails to pay.'],
    ['title' => 'Insurance', 'body' => 'The Client is responsible for insuring the Equipment against theft, fire, and vandalism during the Hire Period, as well as holding public liability insurance for their event.'],
    ['title' => 'Repossession and Access', 'body' => 'The Client grants Majestic irrevocable access to the Site to inspect, maintain, or repossess the Equipment if the Agreement is breached.'],
    ['title' => 'Intellectual Property and Data Protection', 'body' => 'Majestic retains copyright on all designs and floor plans. Client data is processed strictly for fulfilling the Agreement in compliance with local privacy laws.'],
    ['title' => 'Governing Law and Jurisdiction', 'body' => 'This Agreement is governed by the laws of the Republic of Indonesia. Disputes will be settled exclusively by the competent District Court (Pengadilan Negeri) in Bali.'],
];

$termsId = [
    ['title' => 'Definisi', 'body' => '"Perjanjian" merujuk pada Syarat-syarat ini ditambah kuotasi dan konfirmasi pesanan. "Peralatan" adalah semua tenda, lantai, dan aksesoris dari Majestic. "Klien" adalah pihak penyewa atau pembeli.'],
    ['title' => 'Keberlakuan', 'body' => 'Syarat-syarat ini berlaku untuk semua penawaran dan perjanjian. Syarat umum Klien secara tegas ditolak.'],
    ['title' => 'Penawaran dan Kuotasi', 'body' => 'Semua penawaran bersifat tidak mengikat. Majestic tidak terikat oleh kesalahan ketik atau harga yang tidak masuk akal (kesalahan nyata).'],
    ['title' => 'Pembentukan Perjanjian', 'body' => 'Perjanjian sah mengikat setelah Klien menerima kuotasi secara tertulis atau melakukan pembayaran deposit pertama.'],
    ['title' => 'Pesanan Khusus (Maatwerk)', 'body' => 'Untuk peralatan yang diproduksi secara khusus, pesanan tidak dapat dibatalkan setelah produksi dimulai. Klien wajib membayar 100% dari harga yang disepakati jika membatalkan.'],
    ['title' => 'Harga dan Pajak', 'body' => 'Harga belum termasuk PPN, pajak daerah, dan biaya izin kecuali dinyatakan lain.'],
    ['title' => 'Syarat Pembayaran', 'body' => 'Kecuali disepakati lain, deposit 50% diperlukan untuk mengamankan pesanan. Sisa 50% wajib dilunasi sebelum pengiriman atau pemasangan. Keterlambatan dikenakan bunga 1% per bulan.'],
    ['title' => 'Waktu Pengiriman', 'body' => 'Waktu pengiriman bersifat indikatif. Keterlambatan minor tidak memberi hak kepada Klien untuk membatalkan kontrak.'],
    ['title' => 'Pengiriman dan Risiko', 'body' => 'Risiko berpindah ke Klien saat Peralatan tiba di Lokasi. Jika Klien gagal menerima barang, Majestic berhak menyimpannya dengan biaya ditanggung Klien.'],
    ['title' => 'Transportasi', 'body' => 'Jika transportasi diatur melalui pihak ketiga atas permintaan Klien, risiko kerusakan dalam perjalanan berada pada Klien.'],
    ['title' => 'Persiapan Lokasi', 'body' => 'Klien harus memastikan Lokasi rata, bersih, dan aman untuk pemancangan tenda.'],
    ['title' => 'Utilitas Bawah Tanah', 'body' => 'Klien bertanggung jawab menandai pipa dan kabel bawah tanah. Majestic tidak bertanggung jawab atas kerusakan utilitas yang tersembunyi.'],
    ['title' => 'Izin dan Kewajiban Klien', 'body' => 'Klien wajib mengurus dan membayar semua izin lokal (Banjar, polisi) yang diperlukan untuk acara.'],
    ['title' => 'Perakitan dan Pemasangan', 'body' => 'Klien tidak diperbolehkan memindahkan atau mengubah struktur Peralatan setelah dipasang oleh tim Majestic.'],
    ['title' => 'Penggunaan dan Perawatan', 'body' => 'Peralatan harus digunakan sesuai fungsinya. Dilarang menyalakan api atau memasak di dalam tenda tanpa izin tertulis.'],
    ['title' => 'Pemeliharaan dan Kelembapan', 'body' => 'Kelembapan dapat merusak tenda. Jika tenda basah, Klien harus membiarkan tim profesional mengeringkannya. Menyimpan tenda dalam keadaan basah oleh Klien akan membatalkan jaminan/deposit.'],
    ['title' => 'Masa Sewa dan Pengembalian', 'body' => 'Keterlambatan pengembalian yang disebabkan oleh Klien akan dikenakan biaya sewa harian tambahan.'],
    ['title' => 'Hak Kepemilikan (Untuk Penjualan)', 'body' => 'Majestic mempertahankan kepemilikan penuh atas barang yang dijual hingga faktur dibayar 100% lunas.'],
    ['title' => 'Inspeksi dan Keluhan', 'body' => 'Klien wajib memeriksa Peralatan saat serah terima. Cacat yang terlihat wajib dilaporkan secara tertulis dalam 24 jam.'],
    ['title' => 'Garansi', 'body' => 'Majestic menjamin Peralatan layak pakai saat dikirim. Keausan wajar (fair wear and tear) tidak termasuk dalam klaim garansi.'],
    ['title' => 'Tanggung Jawab', 'body' => 'Tanggung jawab maksimal Majestic terbatas pada nilai faktur Perjanjian. Majestic tidak bertanggung jawab atas kerugian tidak langsung atau reputasi.'],
    ['title' => 'Ganti Rugi', 'body' => 'Klien membebaskan Majestic dari tuntutan pihak ketiga yang timbul akibat acara atau penggunaan Peralatan oleh Klien.'],
    ['title' => 'Keadaan Memaksa (Force Majeure)', 'body' => 'Majestic tidak bertanggung jawab atas kegagalan kinerja akibat cuaca ekstrem, bencana alam, atau kebijakan pemerintah.'],
    ['title' => 'Penangguhan dan Penghentian', 'body' => 'Majestic dapat menghentikan layanan jika Klien bangkrut, melanggar aturan keselamatan, atau gagal membayar.'],
    ['title' => 'Asuransi', 'body' => 'Klien bertanggung jawab mengasuransikan Peralatan terhadap pencurian dan kebakaran selama masa sewa.'],
    ['title' => 'Penarikan Kembali dan Akses', 'body' => 'Klien memberikan akses tak terbatas kepada Majestic untuk memasuki Lokasi guna memeriksa atau menarik kembali Peralatan jika terjadi pelanggaran kontrak.'],
    ['title' => 'Kekayaan Intelektual dan Data', 'body' => 'Majestic memegang hak cipta atas desain dan tata letak. Data Klien diproses sesuai hukum privasi setempat.'],
    ['title' => 'Hukum dan Yurisdiksi', 'body' => 'Perjanjian ini diatur oleh hukum Republik Indonesia. Perselisihan akan diselesaikan secara eksklusif di Pengadilan Negeri di Bali.'],
];
?>

<section class="section bg-[#f5f1e8]">
    <div class="container-x">
        <article class="max-w-4xl mx-auto">
            <header class="text-forest-800">
                <h1 class="font-sans text-xl sm:text-2xl font-bold uppercase leading-snug">Terms and Conditions of Hire, Sales, and Services</h1>
                <p class="mt-2 font-bold"><span class="uppercase">PT Majestic Marquees and Tents</span> Effective Date: 26 February 2026</p>
            </header>

            <section class="mt-6 text-forest-800" aria-label="Company details">
                <p class="font-bold">COMPANY DETAILS:</p>
                <ul class="mt-2 list-disc pl-5 sm:pl-6 space-y-1 text-body">
                    <li><strong class="font-semibold">Entity:</strong> <?= e($company['Entity']) ?></li>
                    <li><strong class="font-semibold">Address:</strong> <?= e($company['Address']) ?></li>
                    <li><strong class="font-semibold">NIB:</strong> <?= e($company['NIB']) ?> | <strong class="font-semibold">NPWP:</strong> <?= e($company['NPWP']) ?></li>
                    <li><strong class="font-semibold">Director:</strong> <?= e($company['Director']) ?></li>
                    <li><strong class="font-semibold">Email:</strong> <?= e($company['Email']) ?></li>
                </ul>
            </section>

            <div class="mt-10">
                <?php render_legal_bilingual($termsEn, $termsId); ?>
            </div>
        </article>
    </div>
</section>
