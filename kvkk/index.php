<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// www/site/kvkk.php
require_once dirname(__DIR__) . '/inc/config.php';

// ========== AYARLAR TABLOSUNDAN İLETİŞİM BİLGİLERİNİ ÇEK ==========
$ayarlar_db = [];
try {
    $stmt_ayar = $db->query("SELECT anahtar, deger FROM ayarlar");
    while ($row = $stmt_ayar->fetch(PDO::FETCH_ASSOC)) {
        $ayarlar_db[$row['anahtar']] = $row['deger'];
    }
} catch (Exception $e) {}

$telefon          = $ayarlar_db['telefon'] ?? '+90 533 086 91 67';
$eposta           = $ayarlar_db['email'] ?? 'ibrahimdurandental@gmail.com';
$adres            = $ayarlar_db['adres'] ?? 'Mimar Sinan Mah. Atatürk Bulvarı Riva İş Merkezi No:260 Kat:1, Atakum/Samsun';
$calisma_saatleri = $ayarlar_db['calisma_saatleri'] ?? 'Hafta içi 09:00-18:00';

// ========== MANUEL SEO META TANIMLAMALARI ==========
$lang = $_SESSION['dil'] ?? 'tr';

$kvkk_seo_title_tr = 'KVKK & Aydınlatma Metni | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
$kvkk_seo_description_tr = 'Prof. Dr. İbrahim Duran KVKK aydınlatma metni. 6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında hasta hakları, veri güvenliği ve gizlilik politikası hakkında detaylı bilgi.';
$kvkk_seo_keywords_tr = 'KVKK, aydınlatma metni, kişisel verilerin korunması, 6698 sayılı kanun, hasta hakları, veri güvenliği, gizlilik politikası, diş kliniği KVKK';

$kvkk_seo_title_en = 'KVKK & Privacy Policy | Prof. Dr. İbrahim Duran | Dental Clinic Samsun';
$kvkk_seo_description_en = 'Prof. Dr. İbrahim Duran KVKK privacy policy. Detailed information about patient rights, data security and confidentiality policy within the scope of Law No. 6698 on Protection of Personal Data.';
$kvkk_seo_keywords_en = 'KVKK, privacy policy, personal data protection, Law No. 6698, patient rights, data security, confidentiality policy, dental clinic KVKK';

if ($lang == 'en') {
    $page_title = $kvkk_seo_title_en;
    $seo_description = $kvkk_seo_description_en;
    $site_keywords = $kvkk_seo_keywords_en;
    $dynamic_og_title = $kvkk_seo_title_en;
} else {
    $page_title = $kvkk_seo_title_tr;
    $seo_description = $kvkk_seo_description_tr;
    $site_keywords = $kvkk_seo_keywords_tr;
    $dynamic_og_title = $kvkk_seo_title_tr;
}

$og_image = '/uploads/slide2/kvkk.png';
$mevcut_canonical_link = 'adres gir/kvkk/';
$robots_etiketi_icerigi = '<meta name="robots" content="index, follow">';
$page_slug = 'kvkk';
include dirname(__DIR__) . '/inc/header.php';
include dirname(__DIR__) . '/inc/slide2.php';
?>
<!-- ============================================================ -->
<!-- 🎯 ZENGİN SCHEMA.ORG - WebPage(PrivacyPolicy) + Organization + BreadcrumbList + FAQPage -->
<!-- ============================================================ -->
<?php
$base_url = 'adres gir';
$sayfa_url = $base_url . '/kvkk/';

// OG image absolute URL
$og_image_abs = $og_image;
if (!empty($og_image_abs) && strpos($og_image_abs, 'http') !== 0) {
    $og_image_abs = $base_url . '/' . ltrim($og_image_abs, '/');
}

// Telefon temizle
$tel_temiz = preg_replace('/[^0-9+]/', '', $telefon);
if (!empty($tel_temiz) && substr($tel_temiz, 0, 1) !== '+') {
    $tel_temiz = '+' . ltrim($tel_temiz, '0');
}

// Son güncelleme tarihi
$son_guncelleme = date('c');

$tum_schemalar = [];

// ---------- 1) WebPage (PrivacyPolicy) ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    '@id' => $sayfa_url . '#webpage',
    'url' => $sayfa_url,
    'name' => $page_title,
    'description' => $seo_description,
    'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
    'isPartOf' => ['@id' => $base_url . '/#website'],
    'about' => [
        '@type' => 'Thing',
        'name' => ($lang === 'en') ? 'Privacy Policy and KVKK Disclosure' : 'KVKK Aydınlatma Metni ve Gizlilik Politikası',
        'description' => ($lang === 'en')
            ? 'Personal Data Protection Law (KVKK) disclosure and privacy policy of Prof. Dr. İbrahim Duran Dental Clinic.'
            : 'Prof. Dr. İbrahim Duran Diş Kliniği KVKK aydınlatma metni ve gizlilik politikası.'
    ],
    'publisher' => ['@id' => $base_url . '/#medicalbusiness'],
    'breadcrumb' => ['@id' => $sayfa_url . '#breadcrumb'],
    'primaryImageOfPage' => [
        '@type' => 'ImageObject',
        'url' => $og_image_abs
    ],
    'datePublished' => '2024-01-01T00:00:00+03:00',
    'dateModified' => $son_guncelleme,
    'audience' => [
        '@type' => 'PeopleAudience',
        'geographicArea' => [
            '@type' => 'City',
            'name' => 'Samsun'
        ]
    ]
];

// ---------- 2) Organization (Publisher) ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    '@id' => $base_url . '/#organization',
    'name' => 'Prof. Dr. İbrahim Duran - RivaDent Diş Kliniği',
    'url' => $base_url . '/',
    'logo' => [
        '@type' => 'ImageObject',
        'url' => $base_url . '/uploads/genel/genel_1779669534.png',
        'width' => 512,
        'height' => 512
    ],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => $tel_temiz,
        'contactType' => ($lang === 'en') ? 'Data Protection Contact' : 'Veri Koruma İletişim',
        'email' => $eposta,
        'areaServed' => 'TR',
        'availableLanguage' => ['Turkish', 'English']
    ],
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => 'Mimarsinan, Atatürk Bl. Riva İş merkezi 260/2',
        'addressLocality' => 'Atakum',
        'addressRegion' => 'Samsun',
        'postalCode' => '55200',
        'addressCountry' => 'TR'
    ]
];

// ---------- 3) BreadcrumbList ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    '@id' => $sayfa_url . '#breadcrumb',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => ($lang === 'en') ? 'Home' : 'Anasayfa',
            'item' => $base_url . '/'
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'KVKK',
            'item' => $sayfa_url
        ]
    ]
];

// ---------- 4) FAQPage ----------
$kvkk_sss = ($lang === 'en') ? [
    ['soru' => 'What is KVKK and why is it important?', 'cevap' => 'KVKK (Personal Data Protection Law No. 6698) is a Turkish law that regulates the processing and protection of personal data. It protects the privacy rights of individuals and determines the obligations of data controllers.'],
    ['soru' => 'What are my rights under KVKK?', 'cevap' => 'Under Article 11 of the KVKK, you have the right to learn whether your personal data is processed, request information about it, learn the purpose of processing, know the third parties to whom it is transferred, request correction of incomplete or incorrect data, request deletion or destruction, object to processing, and request compensation for damages.'],
    ['soru' => 'How can I apply to exercise my KVKK rights?', 'cevap' => 'You can submit your applications in writing to Mimar Sinan Mah. Atatürk Bulvarı Riva İş Merkezi No:260 Kat:1, Atakum/Samsun, or via email to ibrahimdurandental@gmail.com. Applications are concluded free of charge within 30 days at the latest, depending on the nature of the request.'],
    ['soru' => 'How long is health data kept?', 'cevap' => 'In accordance with medical legislation and KVKK, health data is kept for the periods stipulated by law (minimum 20 years for medical records). After the retention period, data is anonymized, deleted, or destroyed.'],
    ['soru' => 'How is health data secured?', 'cevap' => 'Health data (special category personal data) is processed and stored using modern security measures such as encryption, access controls, and regular backups, and is only accessible to authorized personnel.']
] : [
    ['soru' => 'KVKK nedir ve neden önemlidir?', 'cevap' => 'KVKK (6698 sayılı Kişisel Verilerin Korunması Kanunu), kişisel verilerin işlenmesini ve korunmasını düzenleyen bir Türk kanunudur. Bireylerin gizlilik haklarını korur ve veri sorumlularının yükümlülüklerini belirler.'],
    ['soru' => 'KVKK kapsamındaki haklarım nelerdir?', 'cevap' => 'KVKK\'nın 11. maddesi uyarınca; kişisel verilerinizin işlenip işlenmediğini öğrenme, işlenmişse buna ilişkin bilgi talep etme, işleme amacını öğrenme, aktarıldığı üçüncü kişileri bilme, eksik veya yanlış işlenmişse düzeltilmesini isteme, silinmesini veya yok edilmesini isteme, itiraz etme ve zararın giderilmesini talep etme haklarına sahipsiniz.'],
    ['soru' => 'KVKK haklarımı kullanmak için nasıl başvurabilirim?', 'cevap' => 'Başvurularınızı yazılı olarak Mimar Sinan Mah. Atatürk Bulvarı Riva İş Merkezi No:260 Kat:1, Atakum/Samsun adresine veya ibrahimdurandental@gmail.com e-posta adresine iletebilirsiniz. Başvurular, talebin niteliğine göre en geç 30 gün içinde ücretsiz olarak sonuçlandırılır.'],
    ['soru' => 'Sağlık verileri ne kadar süre saklanır?', 'cevap' => 'Tıbbi mevzuat ve KVKK uyarınca sağlık verileri, kanunda öngörülen süreler boyunca (tıbbi kayıtlar için minimum 20 yıl) saklanır. Saklama süresi sonunda veriler anonimleştirilir, silinir veya yok edilir.'],
    ['soru' => 'Sağlık verilerinin güvenliği nasıl sağlanır?', 'cevap' => 'Sağlık verileri (özel nitelikli kişisel veri), şifreleme, erişim kontrolleri ve düzenli yedekleme gibi modern güvenlik önlemleri kullanılarak işlenir ve saklanır. Sadece yetkili personel erişebilir.']
];

$faq_items = [];
foreach ($kvkk_sss as $s) {
    $faq_items[] = [
        '@type' => 'Question',
        'name' => $s['soru'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $s['cevap']
        ]
    ];
}

$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    '@id' => $sayfa_url . '#faq',
    'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
    'mainEntity' => $faq_items
];

// ---------- ÇIKTI ----------
foreach ($tum_schemalar as $schema) {
    echo '<script type="application/ld+json">' . "\n"
       . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
       . "\n" . '</script>' . "\n\n";
}
?>
<main class="min-h-screen bg-gradient-to-b from-white via-blue-50/20 to-white">

    <section class="py-16 lg:py-20">
        <div class="max-w-[1200px] mx-auto px-6 lg:px-12">
            
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 lg:p-10 mb-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-200 rounded-xl flex items-center justify-center text-blue-600">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo t_cevir('Veri Sorumlusu'); ?></h2>
                </div>
                <div class="prose prose-blue max-w-none text-gray-600 leading-relaxed space-y-4">
                    <p><?php echo t_cevir('Kişisel verileriniz,'); ?> <strong><?php echo t_cevir('6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”)'); ?></strong> <?php echo t_cevir('kapsamında,'); ?> <strong><?php echo t_cevir('veri sorumlusu'); ?></strong> <?php echo t_cevir('olarak Prof. Dr. İbrahim Duran tarafından aşağıda açıklanan kapsamda işlenecektir.'); ?></p>
                    <div class="bg-gray-50 rounded-2xl p-6 mt-4">
                        <p class="mb-2"><strong class="text-gray-800">👤 <?php echo t_cevir('Veri Sorumlusu:'); ?></strong> Prof. Dr. İbrahim Duran</p>
                        <p class="mb-2"><strong class="text-gray-800">🏥 <?php echo t_cevir('Klinik Adı:'); ?></strong> RivaDent Diş Kliniği</p>
                        <p class="mb-2"><strong class="text-gray-800">📍 <?php echo t_cevir('Adres:'); ?></strong> <?php echo htmlspecialchars($adres); ?></p>
                        <p class="mb-2"><strong class="text-gray-800">📧 <?php echo t_cevir('E-posta:'); ?></strong> <?php echo htmlspecialchars($eposta); ?></p>
                        <p><strong class="text-gray-800">📞 <?php echo t_cevir('Telefon:'); ?></strong> <?php echo htmlspecialchars($telefon); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 lg:p-10 mb-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-200 rounded-xl flex items-center justify-center text-blue-600">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="18" rx="2" ry="2"/>
                            <line x1="8" y1="9" x2="16" y2="9"/>
                            <line x1="8" y1="13" x2="12" y2="13"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo t_cevir('İşlenen Kişisel Veriler'); ?></h2>
                </div>
                <div class="prose prose-blue max-w-none text-gray-600 leading-relaxed">
                    <p><?php echo t_cevir('Randevu oluşturma, tedavi planlaması, hasta kaydı oluşturma ve klinik hizmetlerimizden faydalanmanız sırasında aşağıdaki kişisel verileriniz işlenebilmektedir:'); ?></p>
                    <ul class="grid md:grid-cols-2 gap-3 mt-4">
                        <li class="flex items-center gap-2"><span class="w-2 h-2 bg-blue-500 rounded-full"></span> <?php echo t_cevir('Kimlik bilgileri (Ad, soyad, TCKN)'); ?></li>
                        <li class="flex items-center gap-2"><span class="w-2 h-2 bg-blue-500 rounded-full"></span> <?php echo t_cevir('İletişim bilgileri (Telefon, e-posta, adres)'); ?></li>
                        <li class="flex items-center gap-2"><span class="w-2 h-2 bg-blue-500 rounded-full"></span> <?php echo t_cevir('Sağlık verileri (Tıbbi geçmiş, teşhis, tedavi bilgileri)'); ?></li>
                        <li class="flex items-center gap-2"><span class="w-2 h-2 bg-blue-500 rounded-full"></span> <?php echo t_cevir('Görüntü ve ses kayıtları (Röntgen, fotoğraf, muayene kayıtları)'); ?></li>
                        <li class="flex items-center gap-2"><span class="w-2 h-2 bg-blue-500 rounded-full"></span> <?php echo t_cevir('İşlem güvenliği bilgileri (IP adresi, ziyaret kayıtları)'); ?></li>
                        <li class="flex items-center gap-2"><span class="w-2 h-2 bg-blue-500 rounded-full"></span> <?php echo t_cevir('Finansal bilgiler (Fatura, ödeme kayıtları)'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 lg:p-10 mb-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-200 rounded-xl flex items-center justify-center text-blue-600">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo t_cevir('Veri İşleme Amaçları'); ?></h2>
                </div>
                <div class="prose prose-blue max-w-none text-gray-600 leading-relaxed">
                    <p><?php echo t_cevir('Kişisel verileriniz aşağıdaki amaçlarla işlenmektedir:'); ?></p>
                    <div class="grid md:grid-cols-2 gap-4 mt-4">
                        <div class="bg-gray-50 rounded-xl p-4"><span class="font-bold text-blue-600 block mb-2">🎯 <?php echo t_cevir('Randevu ve Hasta Yönetimi'); ?></span><p class="text-sm text-gray-600"><?php echo t_cevir('Randevu oluşturma, hatırlatma ve takip işlemleri'); ?></p></div>
                        <div class="bg-gray-50 rounded-xl p-4"><span class="font-bold text-blue-600 block mb-2">🏥 <?php echo t_cevir('Tedavi Hizmetleri'); ?></span><p class="text-sm text-gray-600"><?php echo t_cevir('Teşhis, tedavi planlaması ve klinik hizmet sunumu'); ?></p></div>
                        <div class="bg-gray-50 rounded-xl p-4"><span class="font-bold text-blue-600 block mb-2">📋 <?php echo t_cevir('Bilgilendirme ve İletişim'); ?></span><p class="text-sm text-gray-600"><?php echo t_cevir('Tedavi süreci hakkında bilgilendirme, kampanya duyuruları'); ?></p></div>
                        <div class="bg-gray-50 rounded-xl p-4"><span class="font-bold text-blue-600 block mb-2">⚖️ <?php echo t_cevir('Yasal Yükümlülükler'); ?></span><p class="text-sm text-gray-600"><?php echo t_cevir('Mevzuat gereği saklama ve raporlama işlemleri'); ?></p></div>
                        <div class="bg-gray-50 rounded-xl p-4"><span class="font-bold text-blue-600 block mb-2">🔬 <?php echo t_cevir('Bilimsel Araştırmalar'); ?></span><p class="text-sm text-gray-600"><?php echo t_cevir('Anonimleştirilmiş verilerle akademik çalışmalar'); ?></p></div>
                        <div class="bg-gray-50 rounded-xl p-4"><span class="font-bold text-blue-600 block mb-2">🛡️ <?php echo t_cevir('İşlem Güvenliği'); ?></span><p class="text-sm text-gray-600"><?php echo t_cevir('Klinik kayıtlarının güvenliğinin sağlanması'); ?></p></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 lg:p-10 mb-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-200 rounded-xl flex items-center justify-center text-blue-600">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo t_cevir('Kişisel Verilerin Aktarımı'); ?></h2>
                </div>
                <div class="prose prose-blue max-w-none text-gray-600 leading-relaxed">
                    <p><?php echo t_cevir('Kişisel verileriniz, aşağıdaki amaçlarla üçüncü taraflara aktarılabilmektedir:'); ?></p>
                    <ul class="space-y-2 mt-4">
                        <li class="flex items-start gap-3"><span class="text-blue-600 font-bold">•</span> <span><strong><?php echo t_cevir('Laboratuvarlar:'); ?></strong> <?php echo t_cevir('Dental protez, implant ve aparey üretimi için iş birliği yapılan laboratuvarlar'); ?></span></li>
                        <li class="flex items-start gap-3"><span class="text-blue-600 font-bold">•</span> <span><strong><?php echo t_cevir('SGK ve Resmi Kurumlar:'); ?></strong> <?php echo t_cevir('Sağlık hizmetlerinin faturalandırılması ve yasal yükümlülüklerin yerine getirilmesi'); ?></span></li>
                        <li class="flex items-start gap-3"><span class="text-blue-600 font-bold">•</span> <span><strong><?php echo t_cevir('Bilişim Hizmet Sağlayıcıları:'); ?></strong> <?php echo t_cevir('Randevu ve hasta yönetim sistemlerinin sağlıklı çalışması için'); ?></span></li>
                        <li class="flex items-start gap-3"><span class="text-blue-600 font-bold">•</span> <span><strong><?php echo t_cevir('Hukuki Danışmanlar:'); ?></strong> <?php echo t_cevir('Hakların kullanılması ve hukuki süreçler için'); ?></span></li>
                    </ul>
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mt-6">
                        <p class="text-sm text-amber-800 flex items-start gap-2"><span>⚠️</span> <strong><?php echo t_cevir('Sağlık verileriniz (özel nitelikli kişisel veri) KVKK\'nın 6. maddesi gereği, sadece sizden alınan açık rıza ile veya kanunun öngördüğü istisnai durullarda işlenmekte ve aktarılmaktadır.'); ?></strong></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 lg:p-10 mb-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-200 rounded-xl flex items-center justify-center text-blue-600">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo t_cevir('Veri Toplama Yöntemi ve Hukuki Sebep'); ?></h2>
                </div>
                <div class="prose prose-blue max-w-none text-gray-600 leading-relaxed">
                    <p><?php echo t_cevir('Kişisel verileriniz, sözlü, yazılı veya elektronik ortamda aşağıdaki yöntemlerle toplanmaktadır:'); ?></p>
                    <ul class="space-y-2 mt-4">
                        <li class="flex items-start gap-3"><span class="text-blue-600 font-bold">•</span> <span><?php echo t_cevir('Randevu formları ve hasta kayıt formları'); ?></span></li>
                        <li class="flex items-start gap-3"><span class="text-blue-600 font-bold">•</span> <span><?php echo t_cevir('Telefon, e-posta ve WhatsApp iletişimleri'); ?></span></li>
                        <li class="flex items-start gap-3"><span class="text-blue-600 font-bold">•</span> <span><?php echo t_cevir('Web sitesi üzerinden yapılan randevu ve iletişim formları'); ?></span></li>
                        <li class="flex items-start gap-3"><span class="text-blue-600 font-bold">•</span> <span><?php echo t_cevir('Klinik içi muayene ve tedavi sırasında alınan tıbbi kayıtlar'); ?></span></li>
                    </ul>
                    <p class="mt-4"><?php echo t_cevir('Kişisel verileriniz, KVKK\'nın 5. ve 6. maddelerinde belirtilen hukuki sebeplere dayanarak işlenmektedir.'); ?></p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-3xl shadow-xl p-8 lg:p-10 mb-10 text-white">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-white"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                    <h2 class="text-2xl font-bold text-white"><?php echo t_cevir('KVKK Kapsamındaki Haklarınız'); ?></h2>
                </div>
                <p class="text-blue-200 mb-6"><?php echo t_cevir('6698 sayılı Kanun\'un 11. maddesi uyarınca aşağıdaki haklara sahipsiniz:'); ?></p>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-3"><span class="text-cyan-300 text-xl">✓</span> <span><?php echo t_cevir('Kişisel verilerinizin işlenip işlenmediğini öğrenme'); ?></span></div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-3"><span class="text-cyan-300 text-xl">✓</span> <span><?php echo t_cevir('İşlenmişse buna ilişkin bilgi talep etme'); ?></span></div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-3"><span class="text-cyan-300 text-xl">✓</span> <span><?php echo t_cevir('İşleme amacını ve amaca uygun kullanılıp kullanılmadığını öğrenme'); ?></span></div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-3"><span class="text-cyan-300 text-xl">✓</span> <span><?php echo t_cevir('Yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme'); ?></span></div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-3"><span class="text-cyan-300 text-xl">✓</span> <span><?php echo t_cevir('Eksik veya yanlış işlenmişse düzeltilmesini isteme'); ?></span></div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-3"><span class="text-cyan-300 text-xl">✓</span> <span><?php echo t_cevir('KVKK’nın 7. maddesi gereği silinmesini veya yok edilmesini isteme'); ?></span></div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-3"><span class="text-cyan-300 text-xl">✓</span> <span><?php echo t_cevir('İtiraz etme'); ?></span></div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-3"><span class="text-cyan-300 text-xl">✓</span> <span><?php echo t_cevir('Kanuna aykırı işlenmesi halinde zararın giderilmesini talep etme'); ?></span></div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 lg:p-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-200 rounded-xl flex items-center justify-center text-blue-600"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo t_cevir('Başvuru ve İletişim'); ?></h2>
                </div>
                <div class="prose prose-blue max-w-none text-gray-600 leading-relaxed">
                    <p><?php echo t_cevir('Haklarınızı kullanmak veya kişisel verilerinizle ilgili her türlü soru, talep ve şikayetiniz için bize aşağıdaki yöntemlerle ulaşabilirsiniz:'); ?></p>
                    <div class="grid md:grid-cols-2 gap-4 mt-6">
                        <div class="bg-gray-50 rounded-xl p-5"><div class="flex items-center gap-3 mb-3"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><span class="font-semibold text-gray-800"><?php echo t_cevir('E-posta'); ?></span></div><a href="mailto:<?php echo htmlspecialchars($eposta); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($eposta); ?></a></div>
                        <div class="bg-gray-50 rounded-xl p-5"><div class="flex items-center gap-3 mb-3"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg><span class="font-semibold text-gray-800"><?php echo t_cevir('Telefon'); ?></span></div>
						<a href="tel:<?php echo preg_replace('/[^0-9]/', '', $telefon); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($telefon); ?></a></div>
                        <div class="bg-gray-50 rounded-xl p-5 md:col-span-2"><div class="flex items-center gap-3 mb-3"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="3"/></svg><span class="font-semibold text-gray-800"><?php echo t_cevir('Adres'); ?></span></div><p class="text-gray-600"><?php echo htmlspecialchars($adres); ?></p></div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-5 mt-4"><p class="text-sm text-gray-500 flex items-start gap-2"><span>📌</span><?php echo t_cevir('KVKK kapsamında başvurularınız, talebin niteliğine göre en geç 30 gün içinde ücretsiz olarak sonuçlandırılacaktır.'); ?></p></div>
                </div>
            </div>

            <div class="text-center text-gray-400 text-sm mt-10">
                <?php echo t_cevir('Son Güncelleme Tarihi:'); ?> <?php echo date('d.m.Y'); ?>
            </div>
        </div>
    </section>

    <section class="py-16 bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 text-white">
        <div class="relative max-w-4xl mx-auto px-6 text-center">
            <div class="w-20 h-20 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mx-auto mb-6"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            <h2 class="text-3xl lg:text-4xl font-bold mb-4"><?php echo t_cevir('Sorularınız mı var?'); ?></h2>
            <p class="text-xl text-blue-200 mb-8"><?php echo t_cevir('KVKK ve kişisel verilerinizin korunması hakkında detaylı bilgi almak için bizimle iletişime geçin.'); ?></p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/site/iletisim/" class="flex items-center justify-center gap-3 px-8 py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-blue-50 transition-all shadow-lg text-lg"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg><?php echo t_cevir('İletişime Geç'); ?></a>
                <a href="/site/#" class="flex items-center justify-center gap-3 px-8 py-4 bg-transparent border-2 border-white/30 text-white font-bold rounded-xl hover:bg-white/10 transition-all text-lg"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><?php echo t_cevir('Randevu Al'); ?></a>
            </div>
        </div>
    </section>

</main>

<?php include dirname(__DIR__) . '/inc/footer.php'; ?>