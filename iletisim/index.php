<?php
// www/site/iletisim/index.php
require_once dirname(__DIR__) . '/inc/config.php';

// Hata raporlamayı AÇ (geçici - çözüm sonrası kapatabilirsin)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== AJAX FORM GÖNDERİMİ ==========
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    // CSRF TOKEN KONTROLÜ
    $posted_token = $_POST['csrf_token'] ?? '';
    if(!isset($_SESSION['csrf_token']) || $posted_token !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Güvenlik hatası! Sayfayı yenileyin.']);
        exit;
    }
    
    $ad_soyad = trim($_POST['name'] ?? '');
    $telefon = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $konu = trim($_POST['subject'] ?? '');
    $mesaj = trim($_POST['message'] ?? '');
    
    // EĞER KONU "diger" İSE, TEXTBOX'TAKİ DEĞERİ KULLAN
    if ($konu === 'diger') {
        $diger_konu = trim($_POST['diger_konu'] ?? '');
        $konu = !empty($diger_konu) ? 'Diğer: ' . $diger_konu : 'Diğer (Konu belirtilmemiş)';
    }
    
    // Boş alan kontrolü
    if(empty($ad_soyad) || empty($telefon) || empty($email) || empty($mesaj)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
        exit;
    }
    
    // E-posta doğrulama
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta adresi girin.']);
        exit;
    }
    
    try {
        // Tablo var mı kontrol et, yoksa oluştur
        $tableCheck = $db->query("SHOW TABLES LIKE 'iletisim_mesajlari'")->fetchAll();
        if(count($tableCheck) == 0) {
            $db->exec("CREATE TABLE IF NOT EXISTS `iletisim_mesajlari` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ad_soyad` varchar(100) NOT NULL,
                `telefon` varchar(20) NOT NULL,
                `email` varchar(100) NOT NULL,
                `konu` varchar(255) DEFAULT NULL,
                `mesaj` text NOT NULL,
                `durum` enum('okunmadi','okundu','cevaplandi') DEFAULT 'okunmadi',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `silinme_tarihi` datetime DEFAULT NULL,
                `silindi` tinyint(1) DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        
        $stmt = $db->prepare("INSERT INTO iletisim_mesajlari (ad_soyad, telefon, email, konu, mesaj, durum, created_at, silindi) VALUES (?, ?, ?, ?, ?, 'okunmadi', NOW(), 0)");
        
        if($stmt->execute([$ad_soyad, $telefon, $email, $konu, $mesaj])) {
            
            // ============================================================
            // 🔥 E-POSTA GÖNDERİMİ - HEM SANA HEM HOCANA (FARKLI SMTP)
            // ============================================================
            require_once dirname(__DIR__) . '/inc/email-templates.php';
            
            $emailData = [
                'ad_soyad' => $ad_soyad,
                'telefon' => $telefon,
                'email' => $email,
                'konu' => $konu,
                'mesaj' => $mesaj
            ];
            
            $template = getContactNotificationEmail($emailData);
            
            // 1. BİLDİRİM E-POSTASI (SEN - KENDİ SMTP'N İLE)
            $bildirim_eposta = $db->query("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'bildirim_eposta'")->fetchColumn();
            
            // 2. BİLDİRİM E-POSTASI (HOCA - KENDİ SMTP'Sİ İLE)
            $bildirim_eposta_2 = $db->query("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'bildirim_eposta_2'")->fetchColumn();
            
            $mail_sent = false;
            $mail_error = '';
            $mail_sent_2 = false;
            $mail_error_2 = '';
            
            // 🔥 1. MAİLİ GÖNDER (SEN - SENİN SMTP'N)
            if (!empty($bildirim_eposta)) {
                $mail_result = sendMailWithSMTP(
                    $bildirim_eposta,
                    $template['subject'],
                    $template['body'],
                    $email,
                    $ad_soyad,
                    false  // false = 1. SMTP (senin)
                );
                
                if ($mail_result === true) {
                    $mail_sent = true;
                } else {
                    $mail_error = is_string($mail_result) ? $mail_result : 'E-posta gönderilemedi.';
                    error_log("E-posta gönderim hatası (1): " . $mail_error);
                }
            }
            
            // 🔥 2. MAİLİ GÖNDER (HOCA - HOCANIN SMTP'Sİ)
            if (!empty($bildirim_eposta_2)) {
                $mail_result_2 = sendMailWithSMTP(
                    $bildirim_eposta_2,
                    $template['subject'],
                    $template['body'],
                    $email,
                    $ad_soyad,
                    true  // true = 2. SMTP (hocanın)
                );
                
                if ($mail_result_2 === true) {
                    $mail_sent_2 = true;
                } else {
                    $mail_error_2 = is_string($mail_result_2) ? $mail_result_2 : 'E-posta gönderilemedi.';
                    error_log("E-posta gönderim hatası (2): " . $mail_error_2);
                }
            }
            
            // ============================================================
            // 🔥 WHATSAPP BİLDİRİM LİNKİ (HOCANIN WHATSAPP'INA)
            // ============================================================
            $whatsapp_link = '';
            $hoca_whatsapp = $db->query("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_whatsapp'")->fetchColumn();

            if (!empty($hoca_whatsapp)) {
                $whatsapp_num = preg_replace('/[^0-9]/', '', $hoca_whatsapp);
                if (substr($whatsapp_num, 0, 2) !== '90') {
                    $whatsapp_num = '90' . ltrim($whatsapp_num, '0');
                }
                
                // Mesaj içeriği (URL encode)
                $whatsapp_mesaj = "📩 *Yeni İletişim Mesajı!*%0A%0A";
                $whatsapp_mesaj .= "👤 *Ad Soyad:* " . urlencode($ad_soyad) . "%0A";
                $whatsapp_mesaj .= "📞 *Telefon:* " . urlencode($telefon) . "%0A";
                $whatsapp_mesaj .= "✉️ *E-posta:* " . urlencode($email) . "%0A";
                $whatsapp_mesaj .= "📌 *Konu:* " . urlencode($konu) . "%0A";
                $whatsapp_mesaj .= "💬 *Mesaj:* " . urlencode($mesaj) . "%0A%0A";
                $whatsapp_mesaj .= "🔗 " . urlencode($_SERVER['HTTP_HOST'] . "/admin/modules/iletisim/");
                
                // WhatsApp linki
                $whatsapp_link = "https://wa.me/" . $whatsapp_num . "?text=" . $whatsapp_mesaj;
            }
            
            // ============================================================
            // 🔥 CEVAP: Mesaj gönderildi, tüm durumları belirt
            // ============================================================
            $response = [
                'success' => true,
                'message' => 'Mesajınız başarıyla iletildi. En kısa sürede dönüş yapacağız.',
                'mail_sent' => $mail_sent,
                'mail_error' => $mail_error,
                'mail_sent_2' => $mail_sent_2,
                'mail_error_2' => $mail_error_2,
                'whatsapp_link' => $whatsapp_link
            ];
            
            echo json_encode($response);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Veritabanına kayıt sırasında hata oluştu.']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
    exit;
}

// ========== İLETİŞİM SEO AYARLARINI ÇEK ==========
$iletisim_seo_title_tr = 'İletişim | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
$iletisim_seo_title_en = 'Contact | Prof. Dr. İbrahim Duran | Dental Clinic Samsun';
$iletisim_seo_description_tr = "Prof. Dr. İbrahim Duran ile iletişime geçin. Samsun Atakum diş kliniğimizde implant, gülüş tasarımı, zirkonyum kaplama ve estetik diş tedavileri için randevu alın. Telefon ve WhatsApp ile 7/24 iletişim.";
$iletisim_seo_description_en = "Contact Prof. Dr. İbrahim Duran. Make an appointment for implant, smile design, zirconium coating and aesthetic dental treatments at our dental clinic in Samsun Atakum. 7/24 communication via phone and WhatsApp.";
$iletisim_seo_keywords_tr = 'iletişim, randevu, diş kliniği, Samsun diş hekimi, implant, gülüş tasarımı, zirkonyum kaplama, estetik diş hekimliği';
$iletisim_seo_keywords_en = 'contact, appointment, dental clinic, Samsun dentist, implant, smile design, zirconium coating, aesthetic dentistry';
$iletisim_seo_og_image = 'adres gir/uploads/slide2/iletisim.jpg';
$iletisim_seo_canonical = 'adres gir/iletisim/';

// Veritabanından SEO ayarlarını çek
try {
    $tables = $db->query("SHOW TABLES LIKE 'iletisim_seo_ayarlar'")->fetchAll();
    if (count($tables) > 0) {
        $stmt = $db->query("SELECT anahtar, deger FROM iletisim_seo_ayarlar");
        while ($row = $stmt->fetch()) {
            if ($row['anahtar'] == 'seo_title_tr') $iletisim_seo_title_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_title_en') $iletisim_seo_title_en = $row['deger'];
            if ($row['anahtar'] == 'seo_description_tr') $iletisim_seo_description_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_description_en') $iletisim_seo_description_en = $row['deger'];
            if ($row['anahtar'] == 'seo_keywords_tr') $iletisim_seo_keywords_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_keywords_en') $iletisim_seo_keywords_en = $row['deger'];
            if ($row['anahtar'] == 'seo_og_image') $iletisim_seo_og_image = $row['deger'];
            if ($row['anahtar'] == 'seo_canonical') $iletisim_seo_canonical = $row['deger'];
        }
    }
} catch (Exception $e) {
    // Tablo yoksa sessizce devam et
}

// ========== SEO META DEĞİŞKENLERİNİ ATA ==========
$lang = $_SESSION['dil'] ?? 'tr';

if ($lang == 'en') {
    $page_title = $iletisim_seo_title_en;
    $seo_description = $iletisim_seo_description_en;
    $site_keywords = $iletisim_seo_keywords_en;
    $dynamic_og_title = $iletisim_seo_title_en;
} else {
    $page_title = $iletisim_seo_title_tr;
    $seo_description = $iletisim_seo_description_tr;
    $site_keywords = $iletisim_seo_keywords_tr;
    $dynamic_og_title = $iletisim_seo_title_tr;
}

$og_image = $iletisim_seo_og_image;
$mevcut_canonical_link = $iletisim_seo_canonical;
$robots_etiketi_icerigi = '<meta name="robots" content="index, follow">';

// ========== CSRF Token oluştur ==========
if(!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========== İLETİŞİM BİLGİLERİNİ site_ayarlari TABLOSUNDAN ÇEK ==========
$iletisim = [
    'adres' => '',
    'telefon' => '',
    'eposta' => '',
    'calisma_saatleri' => '',
    'harita_link' => '',
    'harita_link2' => '',
    'harita_embed' => '',
    'whatsapp' => ''
];

$stmt = $db->prepare("SELECT ayar_key, ayar_value FROM site_ayarlari WHERE grup = 'iletisim'");
$stmt->execute();
$ayarlar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

foreach($ayarlar as $key => $value) {
    switch($key) {
        case 'adres': $iletisim['adres'] = t_cevir($value); break;
        case 'eposta': $iletisim['eposta'] = $value; break;
        case 'calisma_saatleri': $iletisim['calisma_saatleri'] = t_cevir($value); break;
        case 'harita_link': $iletisim['harita_link'] = $value; break;
        case 'whatsapp': $iletisim['whatsapp'] = $value; break;
        case 'harita_link2': $iletisim['harita_link2'] = $value; break;
        case 'harita_embed': $iletisim['harita_embed'] = $value; break;
    }
}

// 🔥 TELEFONU HEADER1_TELEFON'DAN ÇEK
$header1_telefon = $db->query("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_telefon'")->fetchColumn();
$iletisim['telefon'] = $header1_telefon ?: '+90 505 223 23 43';
if(empty($iletisim['whatsapp'])) {
    $stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_whatsapp'");
    $stmt->execute();
    $whatsapp = $stmt->fetchColumn();
    if($whatsapp) $iletisim['whatsapp'] = $whatsapp;
}

// 🔥 Google Reviews API endpoint'i (backend proxy)
$google_reviews_api_url = '/api/google-reviews.php';

$page_slug = 'iletisim';
include dirname(__DIR__) . '/inc/header.php';
include dirname(__DIR__) . '/inc/slide2.php';
?>
<!-- ============================================================ -->
<!-- 🎯 ZENGİN SCHEMA.ORG - ContactPage + Dentist + Breadcrumb + Organization + WebSite -->
<!-- ============================================================ -->
<?php
$base_url = 'adres gir';
$sayfa_url = $base_url . '/iletisim/';

// Telefon temizle (+90 533 086 91 67 → +905330869167)
$tel_temiz = preg_replace('/[^0-9+]/', '', $iletisim['telefon'] ?? '');
if (!empty($tel_temiz) && substr($tel_temiz, 0, 1) !== '+') {
    $tel_temiz = '+' . ltrim($tel_temiz, '0');
}

// WhatsApp temizle
$wa_temiz = preg_replace('/[^0-9]/', '', $iletisim['whatsapp'] ?? '');
if (!empty($wa_temiz) && substr($wa_temiz, 0, 2) !== '90') {
    $wa_temiz = '90' . ltrim($wa_temiz, '0');
}

// Adres ayrıştırma (virgülle bölmeye çalış)
$adres_parcalari = array_map('trim', explode(',', $iletisim['adres'] ?? ''));

// Çalışma saatleri
$calisma_saatleri_raw = trim($iletisim['calisma_saatleri'] ?? '');
$calisma_satirlari = array_filter(array_map('trim', explode("\n", $calisma_saatleri_raw)));

// Og image absolute
$og_image_abs = $og_image;
if (!empty($og_image_abs) && strpos($og_image_abs, 'http') !== 0) {
    $og_image_abs = $base_url . '/' . ltrim($og_image_abs, '/');
}

// Sosyal medya linkleri
$sosyal_linkler_schema = [];
if (!empty($sosyal_linkler['instagram']) && $sosyal_linkler['instagram'] !== '#') $sosyal_linkler_schema[] = $sosyal_linkler['instagram'];
if (!empty($sosyal_linkler['facebook']) && $sosyal_linkler['facebook'] !== '#') $sosyal_linkler_schema[] = $sosyal_linkler['facebook'];
if (!empty($sosyal_linkler['youtube']) && $sosyal_linkler['youtube'] !== '#') $sosyal_linkler_schema[] = $sosyal_linkler['youtube'];
if (!empty($sosyal_linkler['linkedin']) && $sosyal_linkler['linkedin'] !== '#') $sosyal_linkler_schema[] = $sosyal_linkler['linkedin'];

$tum_schemalar = [];

// ---------- 1) ContactPage (Sayfa kimliği) ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'ContactPage',
    '@id' => $sayfa_url . '#contactpage',
    'url' => $sayfa_url,
    'name' => $page_title,
    'description' => $seo_description,
    'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
    'isPartOf' => ['@id' => $base_url . '/#website'],
    'about' => ['@id' => $base_url . '/#medicalbusiness'],
    'publisher' => ['@id' => $base_url . '/#medicalbusiness'],
    'breadcrumb' => ['@id' => $sayfa_url . '#breadcrumb'],
    'primaryImageOfPage' => [
        '@type' => 'ImageObject',
        'url' => $og_image_abs
    ]
];

// ---------- 2) Dentist / MedicalBusiness (Ana İşletme Bilgisi) ----------
$dentist = [
    '@context' => 'https://schema.org',
    '@type' => ['Dentist', 'MedicalBusiness', 'LocalBusiness'],
    '@id' => $base_url . '/#medicalbusiness',
    'name' => 'Prof. Dr. İbrahim Duran - RivaDent Diş Kliniği',
    'alternateName' => 'RivaDent Ağız ve Diş Sağlığı Polikliniği',
    'url' => $base_url . '/',
    'description' => $seo_description,
    'image' => $og_image_abs,
    'logo' => [
        '@type' => 'ImageObject',
        'url' => $base_url . '/uploads/genel/genel_1779669534.png',
        'width' => 512,
        'height' => 512
    ],
    'medicalSpecialty' => ['Dentistry', 'CosmeticDentistry', 'OralSurgery', 'Orthodontic', 'Prosthodontics'],
    'priceRange' => '₺₺',
    'currenciesAccepted' => 'TRY',
    'paymentAccepted' => 'Nakit, Kredi Kartı, Havale/EFT',
    'areaServed' => [
        '@type' => 'City',
        'name' => 'Samsun'
    ],
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $adres_parcalari[0] ?? ($iletisim['adres'] ?? ''),
        'addressLocality' => 'Atakum',
        'addressRegion' => 'Samsun',
        'postalCode' => '55200',
        'addressCountry' => 'TR'
    ],
    'geo' => [
        '@type' => 'GeoCoordinates',
        'latitude' => 41.3257,
        'longitude' => 36.2263
    ],
    'hasMap' => $iletisim['harita_link'] ?: ($base_url . '/iletisim/#harita')
];

if (!empty($tel_temiz)) {
    $dentist['telephone'] = $tel_temiz;
}
if (!empty($iletisim['eposta'])) {
    $dentist['email'] = $iletisim['eposta'];
}
if (!empty($sosyal_linkler_schema)) {
    $dentist['sameAs'] = $sosyal_linkler_schema;
}

// Açılış saatleri (varsayılan iş saatleri)
$dentist['openingHoursSpecification'] = [
    [
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        'opens' => '09:00',
        'closes' => '19:00'
    ],
    [
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => 'Saturday',
        'opens' => '09:00',
        'closes' => '17:00'
    ]
];

$tum_schemalar[] = $dentist;

// ---------- 3) Organization (Publisher referansı) ----------
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
    'contactPoint' => array_values(array_filter([
        !empty($tel_temiz) ? [
            '@type' => 'ContactPoint',
            'telephone' => $tel_temiz,
            'contactType' => 'customer service',
            'areaServed' => 'TR',
            'availableLanguage' => ['Turkish', 'English']
        ] : null,
        !empty($wa_temiz) ? [
            '@type' => 'ContactPoint',
            'telephone' => '+' . $wa_temiz,
            'contactType' => 'WhatsApp',
            'areaServed' => 'TR',
            'availableLanguage' => ['Turkish', 'English']
        ] : null
    ]))
];

// ---------- 4) WebSite (Sayfa kimliği - arama kutusu YOK) ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => $base_url . '/#website',
    'url' => $base_url . '/',
    'name' => 'Prof. Dr. İbrahim Duran - Diş Kliniği Samsun',
    'description' => 'Samsun Atakum\'da implant, gülüş tasarımı, zirkonyum kaplama ve estetik diş hekimliği hizmetleri.',
    'publisher' => ['@id' => $base_url . '/#medicalbusiness'],
    'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR'
];

// ---------- 5) BreadcrumbList ----------
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
            'name' => ($lang === 'en') ? 'Contact' : 'İletişim',
            'item' => $sayfa_url
        ]
    ]
];

// ---------- ÇIKTI: Pretty Print + Alt Alta ----------
foreach ($tum_schemalar as $schema) {
    echo '<script type="application/ld+json">' . "\n"
       . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
       . "\n" . '</script>' . "\n\n";
}
?>
<section class="relative w-full py-20 lg:py-28 bg-gradient-to-b from-white to-slate-50/30 overflow-hidden">
    <div class="mx-auto px-6 lg:px-12 relative z-10">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-stretch">
            
            <!-- SOL TARAF  -->
            <div class="lg:col-span-4 bg-[#020617] rounded-[2.5rem] p-8 lg:p-12 shadow-2xl border border-blue-500/20 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 right-0 w-96 h-96 bg-blue-600/10 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="relative z-10 space-y-8">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-500/10 border border-blue-500/20 rounded-full mb-6">
                            <div class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></div>
                            <span class="text-blue-400 font-bold text-[10px] tracking-widest uppercase"><?php echo t_cevir('Dijital Bağlantı'); ?></span>
                        </div>
                        <h2 class="text-3xl lg:text-4xl font-black text-white tracking-tighter uppercase italic leading-none mb-3">
                            <?php echo t_cevir('AKADEMİK'); ?> 
                            <span class="text-blue-500 not-italic uppercase"><?php echo t_cevir('MEDYA'); ?></span> 
                            <?php echo t_cevir('AKIŞI'); ?>
                        </h2>
                        <p class="text-slate-300 text-sm lg:text-base leading-relaxed">
                            <?php echo t_cevir("Sosyal medya ve akademik platformlardaki güncel paylaşımlarımızı takip edin."); ?>
                        </p>
                    </div>

                    <?php 
                    $sosyal_linkler = [];
                    $stmt = $db->prepare("SELECT ayar_key, ayar_value FROM site_ayarlari WHERE grup = 'sosyal'");
                    $stmt->execute();
                    $sosyal_ayarlar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    
                    $sosyal_linkler['instagram'] = $sosyal_ayarlar['instagram'] ?? '#';
                    $sosyal_linkler['facebook'] = $sosyal_ayarlar['facebook'] ?? '#';
                    $sosyal_linkler['youtube'] = $sosyal_ayarlar['youtube'] ?? '#';
                    $sosyal_linkler['linkedin'] = $sosyal_ayarlar['linkedin'] ?? '#';
                    ?>

                    <!-- Alt alta şık sosyal medya kartları -->
                    <div class="space-y-4">
                        <!-- Instagram -->
                        <a href="<?php echo htmlspecialchars($sosyal_linkler['instagram']); ?>" target="_blank" rel="noopener noreferrer" class="group relative bg-slate-900/80 backdrop-blur-md p-5 border border-slate-800 hover:border-pink-500/50 transition-all duration-300 rounded-2xl flex items-center justify-between shadow-lg">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-600/20 to-pink-600/20 flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-white uppercase tracking-wider">Instagram</h4>
                                    <p class="text-xs font-medium text-slate-400">@dr.ibrahimduran</p>
                                </div>
                            </div>
                            <span class="text-xs text-blue-400 font-bold group-hover:translate-x-1 transition-transform">→</span>
                        </a>

                        <!-- Facebook -->
                        <a href="<?php echo htmlspecialchars($sosyal_linkler['facebook']); ?>" target="_blank" rel="noopener noreferrer" class="group relative bg-slate-900/80 backdrop-blur-md p-5 border border-slate-800 hover:border-blue-500/50 transition-all duration-300 rounded-2xl flex items-center justify-between shadow-lg">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-600/20 to-blue-400/20 flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-white uppercase tracking-wider">Facebook</h4>
                                    <p class="text-xs font-medium text-slate-400">@ibrahimduran</p>
                                </div>
                            </div>
                            <span class="text-xs text-blue-400 font-bold group-hover:translate-x-1 transition-transform">→</span>
                        </a>

                        <!-- YouTube -->
                        <a href="<?php echo htmlspecialchars($sosyal_linkler['youtube']); ?>" target="_blank" rel="noopener noreferrer" class="group relative bg-slate-900/80 backdrop-blur-md p-5 border border-slate-800 hover:border-red-500/50 transition-all duration-300 rounded-2xl flex items-center justify-between shadow-lg">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-600/20 to-red-400/20 flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"></path><path d="m10 15 5-3-5-3z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-white uppercase tracking-wider">YouTube</h4>
                                    <p class="text-xs font-medium text-slate-400"><?php echo t_cevir('Akademik Diş'); ?></p>
                                </div>
                            </div>
                            <span class="text-xs text-blue-400 font-bold group-hover:translate-x-1 transition-transform">→</span>
                        </a>

                        <!-- LinkedIn -->
                        <a href="<?php echo htmlspecialchars($sosyal_linkler['linkedin']); ?>" target="_blank" rel="noopener noreferrer" class="group relative bg-slate-900/80 backdrop-blur-md p-5 border border-slate-800 hover:border-blue-400/50 transition-all duration-300 rounded-2xl flex items-center justify-between shadow-lg">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-800/20 to-blue-600/20 flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect width="4" height="12" x="2" y="9"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-white uppercase tracking-wider">LinkedIn</h4>
                                    <p class="text-xs font-medium text-slate-400">prof-dr-duran</p>
                                </div>
                            </div>
                            <span class="text-xs text-blue-400 font-bold group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-800 text-center">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.4em]"><?php echo t_cevir('SAMSUN AKADEMİK HUB'); ?></p>
                </div>
            </div>
            <!-- ORTA TARAF -->
            <div class="lg:col-span-4 bg-white rounded-[2.5rem] p-8 lg:p-12 shadow-2xl border-2 border-slate-100 flex flex-col justify-between">
                <div class="space-y-8">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-full mb-6">
                            <div class="w-1.5 h-1.5 bg-blue-600 rounded-full"></div>
                            <span class="text-blue-700 font-bold text-[10px] tracking-wider uppercase"><?php echo t_cevir('Akademik Erişim'); ?></span>
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-bold text-slate-900 mb-4"><?php echo t_cevir('Bize'); ?> <span class="text-blue-600"><?php echo t_cevir('Ulaşın'); ?></span></h1>
                        <p class="text-slate-800 text-base leading-relaxed"><?php echo t_cevir("Prof. Dr. İbrahim Duran önderliğinde Samsun'da akademik ağız ve diş sağlığı hizmeti."); ?></p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex gap-4 p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 shadow-sm">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1"><?php echo t_cevir('Adres'); ?></div>
                                <div class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($iletisim['adres']); ?></div>
                                <a href="#harita" class="inline-block text-xs font-semibold text-blue-600 mt-1 hover:underline"><?php echo t_cevir('Yol Tarifi →'); ?></a>
                            </div>
                        </div>

                        <div class="flex gap-4 p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 shadow-sm">
                            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center text-green-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-bold text-green-600 uppercase tracking-wider mb-1"><?php echo t_cevir('Telefon'); ?></div>
                                <div class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($iletisim['telefon']); ?></div>
                                <div class="text-xs text-gray-500 mt-1"><?php echo t_cevir('7/24 Randevu Hattı'); ?></div>
                            </div>
                        </div>

                        <div class="flex gap-4 p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 shadow-sm">
                            <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-bold text-purple-600 uppercase tracking-wider mb-1"><?php echo t_cevir('E-posta'); ?></div>
                                <div class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($iletisim['eposta']); ?></div>
                                <div class="text-xs text-gray-500 mt-1"><?php echo t_cevir('Bilgi ve Randevu'); ?></div>
                            </div>
                        </div>

                        <div class="flex gap-4 p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 shadow-sm">
                            <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-bold text-amber-600 uppercase tracking-wider mb-1"><?php echo t_cevir('Çalışma Saatleri'); ?></div>
                                <div class="text-sm font-medium text-gray-700"><?php echo nl2br(htmlspecialchars($iletisim['calisma_saatleri'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <?php if($iletisim['whatsapp']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $iletisim['whatsapp']); ?>" target="_blank" class="flex items-center justify-center gap-2 w-full py-4 bg-[#25D366] text-white font-bold text-sm uppercase tracking-wide rounded-2xl hover:bg-[#20b859] transition shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        <?php echo t_cevir("WhatsApp'tan Yaz"); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SAĞ TARAF -->
            <div class="lg:col-span-4 bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 overflow-hidden flex flex-col justify-between">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <h3 class="text-2xl font-black text-white uppercase flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        <?php echo t_cevir('İLETİŞİM FORMU'); ?>
                    </h3>
                    <p class="text-blue-200 text-sm mt-2"><?php echo t_cevir('Size en kısa sürede dönüş yapacağız.'); ?></p>
                </div>

                <div class="p-8 lg:p-10 flex-1 flex flex-col justify-center">
                    <form id="iletisimForm" method="POST" action="" class="grid gap-6">
                        <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="grid md:grid-cols-2 gap-5">
                            <div class="group">
                                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 mb-1 block"><?php echo t_cevir('ADINIZ SOYADINIZ'); ?> <span class="text-red-500">*</span></label>
                                <input type="text" name="name" required class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none transition-all" placeholder="<?php echo t_cevir('Ahmet Yılmaz'); ?>">
                            </div>
                            <div class="group">
                                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 mb-1 block"><?php echo t_cevir('TELEFON NUMARANIZ'); ?> <span class="text-red-500">*</span></label>
                                <input type="tel" name="phone" required class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none transition-all" placeholder="0 5__ ___ __ __">
                            </div>
                        </div>

                        <div class="group">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 mb-1 block"><?php echo t_cevir('E-POSTA ADRESİNİZ'); ?> <span class="text-red-500">*</span></label>
                            <input type="email" name="email" required class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none transition-all" placeholder="ornek@email.com">
                        </div>

                        <div class="group">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 mb-1 block"><?php echo t_cevir('KONU SEÇİNİZ'); ?> <span class="text-red-500">*</span></label>
                            <select id="konuSelect" name="subject" required class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none appearance-none cursor-pointer">
                                <option value=""><?php echo t_cevir('LÜTFEN BİR KONU SEÇİNİZ'); ?></option>
                                <option value="randevu">📅 <?php echo t_cevir('RANDEVU TALEBİ'); ?></option>
                                <option value="tedavi">🦷 <?php echo t_cevir('TEDAVİ TALEBİ'); ?></option>
                                <option value="bilgi">💡 <?php echo t_cevir('TEDAVİLER HAKKINDA BİLGİ'); ?></option>
                                <option value="diger">📝 <?php echo t_cevir('DİĞER'); ?></option>
                            </select>
                        </div>

                        <!-- 🔥 "DİĞER" SEÇİNCE AÇILAN TEXTBOX -->
                        <div id="digerKonuContainer" class="hidden group mt-1">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 mb-1 block">
                                <?php echo t_cevir('KONUNUZ'); ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="digerKonuInput" name="diger_konu" class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none transition-all" placeholder="<?php echo t_cevir('Lütfen konunuzu yazın...'); ?>" disabled>
                        </div>

                        <div class="group">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 mb-1 block"><?php echo t_cevir('MESAJINIZ'); ?> <span class="text-red-500">*</span></label>
                            <textarea name="message" rows="4" required class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-semibold text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none transition-all resize-none" placeholder="<?php echo t_cevir('Mesajınızı buraya yazabilirsiniz...'); ?>"></textarea>
                        </div>

                        <div id="formMessage" class="hidden mb-4 p-4 rounded-xl text-sm font-semibold flex items-center gap-3 shadow-md"></div>

                        <button type="submit" style="background:blue" class="w-full py-5 text-white font-bold text-sm uppercase tracking-wide rounded-2xl transition-all duration-300 flex items-center justify-center gap-3 shadow-lg shadow-blue-600/30 bg-blue-600 hover:bg-blue-700">
                            <span><?php echo t_cevir('MESAJI GÖNDER'); ?></span>
                        </button>

                        <p class="text-[10px] text-slate-500 text-center mt-2"><?php echo t_cevir('Kişisel verileriniz KVKK kapsamında korunmaktadır.'); ?></p>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>


<section id="harita" class="w-full relative h-[600px] bg-slate-100 overflow-hidden">
    <iframe 
        src="<?php echo htmlspecialchars($iletisim['harita_embed'] ?: 'about:blank'); ?>" 
        class="absolute inset-0 w-full h-full border-0" 
        allowfullscreen="" 
        loading="lazy">
    </iframe>
    
<div class="absolute top-3 right-6 lg:right-12 z-20 w-full max-w-xl lg:max-w-2xl">
    <div class="bg-[#0a0f1c] rounded-3xl p-2 lg:p-10 shadow-3xl border border-blue-500/30 backdrop-blur-md">
        
        <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 bg-blue-500/20 rounded-2xl flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                    <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
            <span class="text-blue-300 font-black text-xs uppercase tracking-widest"><?php echo t_cevir('Konum Bilgisi'); ?></span>
        </div>
        
        <h3 class="text-2xl lg:text-3xl font-black text-white mb-4"><?php echo t_cevir('Kliniğimiz'); ?></h3>
        
        <p class="text-blue-200 font-medium leading-relaxed mb-6 text-base lg:text-lg">
            <?php echo htmlspecialchars($iletisim['adres']); ?>
        </p>
        
        <a href="<?php echo htmlspecialchars($iletisim['harita_link'] ?: '#'); ?>" 
           target="_blank" 
           class="flex items-center justify-center gap-3 w-full bg-gradient-to-r from-yellow-500 to-yellow-600 border-2 border-white/30 hover:border-white/60 text-white font-bold text-base px-6 py-4 rounded-2xl transition-all shadow-xl mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            <span>🗺️ <?php echo t_cevir('Prof. Dr. İbrahim Duran - Yol Tarifi ⭐ Yorum Yap'); ?></span>
        </a>

        <div class="mt-4 pt-4 border-t border-blue-500/20 flex justify-between text-xs lg:text-sm text-blue-200 font-medium">
            <span class="flex items-center gap-1.5">⏱️ <?php echo t_cevir('7/24 Hizmet'); ?></span>
            <span class="flex items-center gap-1.5">👨‍⚕️ <?php echo t_cevir('Uzman Hekim'); ?></span>
            <span class="flex items-center gap-1.5">🏥 <?php echo t_cevir('Modern Klinik'); ?></span>
        </div>
        
    </div>
</div>


</section>
<!-- ========================================== -->
<!-- 🔥 KOYU TEMA - GOOGLE YORUMLARI SECTION -->
<!-- ========================================== -->
<section class="relative w-full bg-[#020617] py-10 px-6 lg:px-12 overflow-hidden border-t border-blue-500/20 mb-5">
    <!-- Arka plan parıltı efektleri -->
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-1/2 left-10 w-96 h-96 bg-blue-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-indigo-600/10 rounded-full blur-[120px]"></div>
    </div>

    <div class="max-w-[1400px] mx-auto relative z-10">
        <!-- Başlık Alanı -->
        <div class="flex flex-col md:flex-row items-start md:items-end justify-between mb-16 gap-6">
            <div>
                <div class="inline-flex items-center gap-2.5 px-4 py-1.5 bg-blue-500/10 border border-blue-500/20 rounded-full mb-4">
                    <span class="w-2 h-2 rounded-full bg-yellow-400 animate-ping"></span>
                    <span class="text-blue-400 font-bold text-xs tracking-widest uppercase">⭐ Google İşletme Profili</span>
                </div>
                <h2 class="text-3xl lg:text-5xl font-black text-white uppercase tracking-tight">
                    Hastalarımızın <span class="text-blue-500">Google Yorumları</span>
                </h2>
                <p id="reviewCountHeader" class="text-slate-400 text-sm mt-2">Gerçek hastalarımızın kliniğimiz hakkındaki değerlendirmeleri</p>
            </div>
            
            <a href="<?php echo htmlspecialchars($iletisim['harita_link'] ?: '#'); ?>" target="_blank" class="pl-10 pr-10 px-7 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white font-bold text-xs uppercase tracking-wider rounded-2xl transition-all shadow-xl hover:shadow-blue-500/20 flex items-center gap-3 shrink-0">
                <span>Google'da Tüm Yorumları Gör & Yorum Yap</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            </a>
        </div>

        <!-- Kartların Listeleneceği Grid Alanı -->
        <div id="googleReviewsGrid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Yükleniyor Durumu -->
            <div class="bg-slate-900/80 border border-slate-800 p-12 rounded-3xl text-center text-slate-400 col-span-full shadow-2xl">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent mb-4"></div>
                <p class="text-sm font-medium">Google Yorumları yükleniyor...</p>
            </div>
        </div>

        <!-- Alt Not -->
        <div class="mt-12 text-center border-t border-slate-800/80 pt-6">
            <p class="text-[11px] text-slate-500 uppercase tracking-[0.2em]">
                Yorumlar doğrudan Google Haritalar altyapısı üzerinden çekilmekte olup Google kullanıcılarına aittir.
            </p>
        </div>
    </div>
</section>

<!-- Google Yorumları - Tam Türkçe Çeviri -->
<script>
(function() {
    'use strict';

    const gridContainer = document.getElementById('googleReviewsGrid');
    const reviewCountHeader = document.getElementById('reviewCountHeader');

    if (!gridContainer) return;

    // ================================
    // YÜKLENİYOR
    // ================================
    gridContainer.innerHTML = `
        <div class="bg-slate-900/80 border border-slate-800 p-12 rounded-3xl text-center text-slate-400 col-span-full shadow-2xl">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent mb-4"></div>
            <p class="text-sm font-medium">Google Yorumları yükleniyor...</p>
        </div>
    `;

    // ================================
    // GOOGLE REVIEWS API
    // ================================
    fetch('/api/google-reviews-js.php', {
        method: 'GET',
        cache: 'no-store'
    })
    .then(function(res) {
        if (!res.ok) {
            throw new Error('HTTP ' + res.status);
        }
        return res.json();
    })
    .then(function(data) {
        console.log('Google Reviews API:', data);

        if (data.error || data.status === 'REQUEST_DENIED' || data.status === 'INVALID_REQUEST') {
            const errorMessage = data.error_message || data.error || 'Google API bağlantısı başarısız.';
            gridContainer.innerHTML = `
                <div class="bg-slate-900/80 border border-slate-800 p-8 rounded-3xl text-center text-red-400 col-span-full shadow-xl">
                    <p class="text-sm">⚠️ Google Yorumları yüklenemedi.</p>
                    <p class="text-xs text-slate-500 mt-2">${escapeHtml(errorMessage)}</p>
                </div>
            `;
            return;
        }

        const result = data.result;
        if (!result) {
            gridContainer.innerHTML = `
                <div class="bg-slate-900/80 border border-slate-800 p-8 rounded-3xl text-center text-slate-400 col-span-full shadow-xl">
                    <p class="text-sm">Henüz Google yorumu bulunmuyor.</p>
                </div>
            `;
            return;
        }

        const reviews = Array.isArray(result.reviews) ? result.reviews : [];
        if (reviews.length === 0) {
            gridContainer.innerHTML = `
                <div class="bg-slate-900/80 border border-slate-800 p-8 rounded-3xl text-center text-slate-400 col-span-full shadow-xl">
                    <p class="text-sm">Henüz Google yorumu bulunmuyor.</p>
                </div>
            `;
            return;
        }

        const totalReviews = Number(result.user_ratings_total || reviews.length);
        const rating = Number(result.rating || 5);
        if (reviewCountHeader) {
            reviewCountHeader.textContent = `⭐ ${rating.toFixed(1)} Puan ve ${totalReviews} Gerçek Google Değerlendirmesi`;
        }

        const preparedReviews = reviews.map(function(review) {
            return {
                author_name: review.author_name || 'İsimsiz Hasta',
                author_url: review.author_url || '',
                profile_photo_url: review.profile_photo_url || 'https://www.gravatar.com/avatar/?d=mp',
                rating: Number(review.rating || 5),
                relative_time_description: review.relative_time_description || '',
                text: review.text || '',
                original_text: review.original_text || review.text || '',
                translated: review.translated === true
            };
        });

        renderReviews(preparedReviews);
    })
    .catch(function(error) {
        console.error('Google Reviews bağlantı hatası:', error);
        gridContainer.innerHTML = `
            <div class="bg-slate-900/80 border border-slate-800 p-8 rounded-3xl text-center text-red-400 col-span-full shadow-xl">
                <p class="text-sm">⚠️ Yorumlar yüklenirken bağlantı hatası oluştu.</p>
                <p class="text-xs text-slate-500 mt-2">Lütfen daha sonra tekrar deneyiniz.</p>
            </div>
        `;
    });

    // ============================================
    // YORUMLARI RENDER ET
    // ============================================
    function renderReviews(reviews) {
        let html = '';

        reviews.forEach(function(review, index) {
            const author = review.author_name || 'İsimsiz Hasta';
            const text = review.text || '';
            const time = review.relative_time_description || '';
            const reviewRating = Number(review.rating || 5);
            const photo = review.profile_photo_url || 'https://www.gravatar.com/avatar/?d=mp';

            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= reviewRating) {
                    starsHtml += `
                        <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                    `;
                } else {
                    starsHtml += `
                        <svg class="w-4 h-4 text-slate-700 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                    `;
                }
            }

            const isLong = text.length > 150;
            const shortText = isLong ? text.substring(0, 150) + '...' : text;
            const fullText = text;

            // 🔥 KART HTML – Grid yapısı korunur, h-auto ile yükseklik içeriğe göre
            html += `
                <div class="bg-slate-900/90 border border-slate-800 p-8 rounded-3xl shadow-2xl flex flex-col hover:border-blue-500/50 transition-all duration-300 h-auto" data-card-index="${index}">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-1">${starsHtml}</div>
                            <span class="text-xs text-blue-400 font-medium bg-blue-500/10 px-3 py-1 rounded-full">${escapeHtml(time)}</span>
                        </div>

                        <p class="text-slate-300 text-base leading-relaxed mb-4 font-normal italic">
                            "<span id="text-${index}" data-short="${escapeHtml(shortText)}" data-full="${escapeHtml(fullText)}">${escapeHtml(shortText)}</span>"
                        </p>

                        ${isLong ? `<button type="button" onclick="yorumToggle(${index})" id="btn-${index}" data-expanded="false" class="text-xs text-blue-400 font-bold hover:underline mb-4 inline-block">Devamını Oku ↓</button>` : ''}

                        ${review.translated ? `
                            <div class="mt-3 pt-4 pb-4 border-t border-slate-700/50">
                                <p class="text-base font-semibold text-emerald-400 gap-2 justify-end">
                                    <span class="text-lg">🌐</span>
                                    Google tarafından çevirildi
                                </p>
                            </div>
                        ` : ''}
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-slate-800 mt-auto">
                        <img src="${escapeAttribute(photo)}" alt="${escapeAttribute(author)}" class="w-14 h-14 rounded-full object-cover border-2 border-blue-500/40 shadow-inner" loading="lazy" referrerpolicy="no-referrer">
                        <div>
                            <h4 class="font-bold text-white text-base tracking-wide">${escapeHtml(author)}</h4>
                            <span class="text-xs text-blue-400 font-semibold uppercase tracking-wider">Google Haritalar Hastası</span>
                        </div>
                    </div>
                </div>
            `;
        });

        // 🔥 Grid yapısını koru, items-start ile üstten hizala
        gridContainer.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 items-start';
        gridContainer.innerHTML = html;
    }

    // ============================================
    // HTML ESCAPE
    // ============================================
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(str).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function escapeAttribute(str) {
        return escapeHtml(str).replace(/`/g, '&#096;');
    }

    // ============================================
    // DEVAMINI OKU – SADECE TIKLANAN KART AÇILIR
    // ============================================
    window.yorumToggle = function(index) {
        const span = document.getElementById(`text-${index}`);
        const btn = document.getElementById(`btn-${index}`);
        if (!span || !btn) return;

        // 🔥 Önce diğer tüm kartları kapat
        document.querySelectorAll('[id^="text-"]').forEach(function(el) {
            if (el.id !== `text-${index}`) {
                const idx = el.id.replace('text-', '');
                const otherBtn = document.getElementById(`btn-${idx}`);
                if (otherBtn && otherBtn.getAttribute('data-expanded') === 'true') {
                    el.textContent = el.getAttribute('data-short');
                    otherBtn.textContent = 'Devamını Oku ↓';
                    otherBtn.setAttribute('data-expanded', 'false');
                }
            }
        });

        // 🔥 Şimdi tıklanan kartı toggle et
        const isExpanded = btn.getAttribute('data-expanded') === 'true';
        if (isExpanded) {
            span.textContent = span.getAttribute('data-short');
            btn.textContent = 'Devamını Oku ↓';
            btn.setAttribute('data-expanded', 'false');
        } else {
            span.textContent = span.getAttribute('data-full');
            btn.textContent = 'Küçült ↑';
            btn.setAttribute('data-expanded', 'true');
        }
    };

})();
</script>