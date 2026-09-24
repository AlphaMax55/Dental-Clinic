<?php
// www/site/admin/modules/ayarlar/index.php - %100 ENTEGRE AI SEO VE SEF URL SÜRÜCÜSÜ

require_once dirname(__DIR__, 2) . '/includes/config.php';

// www/site/admin/modules/ayarlar/index.php - DOSYANIN EN BAŞINA EKLE (require_once'dan hemen sonra)

if (isset($_GET['islem']) && $_GET['islem'] === 'resim_yukle') {
    header('Content-Type: application/json');
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/genel/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    if (isset($_FILES['resim'])) {
        $file = $_FILES['resim'];
        $img = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        
        if ($img) {
            $w = imagesx($img);
            $h = imagesy($img);
            $new_h = intval(300 * ($h / $w));
            
            // True color tuval oluştur ve şeffaflık motorunu mühürle
            $new_img = imagecreatetruecolor(300, $new_h);
            imagealphablending($new_img, false);
            imagesavealpha($new_img, true);
            
            // Arka plana sıfır renk (tamamen şeffaf) bas
            $transparent = imagecolorallocatealpha($new_img, 0, 0, 0, 127);
            imagefilledrectangle($new_img, 0, 0, 300, $new_h, $transparent);
            
            imagecopyresampled($new_img, $img, 0, 0, 0, 0, 300, $new_h, $w, $h);
            
            $filename = 'genel_' . time() . '.png';
            $destination = $upload_dir . $filename;
            
            // PNG kaydederken alpha kanalının bozulmasını engelle
            imagesavealpha($new_img, true);
            if (imagepng($new_img, $destination, 9)) {
                imagedestroy($img);
                imagedestroy($new_img);
                echo json_encode(['success' => true, 'url' => '/uploads/genel/' . $filename]);
            } else {
                echo json_encode(['success' => false, 'message' => 'PNG kaydetme hatası']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Resim formatı geçersiz']);
        }
    }
    exit;
}
// ========== E-POSTA AYARLARINI KAYDET ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eposta_ayarlar'])) {
    $posted_token = $_POST['csrf_token'] ?? '';
    
    if (!isset($_SESSION['csrf_token']) || $posted_token !== $_SESSION['csrf_token']) {
        $_SESSION['mesaj'] = 'Güvenlik hatası!';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=ayarlar');
        exit;
    }
    
    // 🔥 TÜM E-POSTA ALANLARI (1. ve 2. SMTP dahil)
    $eposta_fields = [
        'bildirim_eposta', 'bildirim_eposta_2',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
        'smtp_host_2', 'smtp_port_2', 'smtp_username_2', 'smtp_password_2', 'smtp_encryption_2'
    ];
    
    try {
        foreach ($eposta_fields as $key) {
            $value = isset($_POST[$key]) ? trim($_POST[$key]) : '';
            $stmt = $db->prepare("UPDATE site_ayarlari SET ayar_value = ? WHERE ayar_key = ?");
            $stmt->execute([$value, $key]);
        }
        $_SESSION['mesaj'] = '✅ E-posta ayarları kaydedildi!';
        $_SESSION['mesaj_tip'] = 'success';
    } catch (Exception $e) {
        $_SESSION['mesaj'] = 'Hata: ' . $e->getMessage();
        $_SESSION['mesaj_tip'] = 'error';
    }
    
    header('Location: ?modul=ayarlar&tab=eposta');
    exit;
}
// Logo yükleme için de ayrı endpoint (isteğe bağlı aynı olabilir)
if (isset($_GET['islem']) && $_GET['islem'] === 'logo_resim_yukle') {
    header('Content-Type: application/json');
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/ayarlar/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    if (isset($_FILES['resim'])) {
        $file = $_FILES['resim'];
        $info = getimagesize($file['tmp_name']);
        if ($info) {
            $mime = $info['mime'];
            switch ($mime) {
                case 'image/jpeg': $img = imagecreatefromjpeg($file['tmp_name']); break;
                case 'image/png':  $img = imagecreatefrompng($file['tmp_name']); break;
                case 'image/webp': $img = imagecreatefromwebp($file['tmp_name']); break;
                default: echo json_encode(['success' => false, 'message' => 'Format geçersiz']); exit;
            }
            $filename = 'logo_' . time() . '.webp';
            if (imagewebp($img, $upload_dir . $filename, 90)) {
                imagedestroy($img);
                echo json_encode(['success' => true, 'url' => '/uploads/ayarlar/' . $filename]);
            } else {
                echo json_encode(['success' => false, 'message' => 'WebP hatası']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Resim okunamadı']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dosya bulunamadı']);
    }
    exit;
}
if (isset($_GET['islem']) && $_GET['islem'] === 'resim_yukle') {
    header('Content-Type: application/json');
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/genel/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    if (isset($_FILES['resim'])) {
        $file = $_FILES['resim'];
        $img = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        
        if ($img) {
            imagealphablending($img, true);
            imagesavealpha($img, true);
            
            $new_img = imagescale($img, 300, -1);
            $filename = 'genel_' . time() . '.png';
            $destination = $upload_dir . $filename;
            
            if (imagepng($new_img, $destination, 9)) {
                imagedestroy($img);
                imagedestroy($new_img);
                echo json_encode(['success' => true, 'url' => '/uploads/genel/' . $filename]);
            } else {
                echo json_encode(['success' => false, 'message' => 'PNG kaydetme hatası']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Resim formatı geçersiz']);
        }
    }
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Dinamik sayfaları otomatik kontrol et
$dinamik_sayfalar = $db->query("SELECT baslik, slug FROM dinamik_sayfalar WHERE aktif = 1 ORDER BY sira ASC")->fetchAll();

$header2_navlinks_value = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header2_navlinks'");
$stmt->execute();
$header2_navlinks_value = $stmt->fetchColumn();
$nav_links = json_decode($header2_navlinks_value, true);
if (!is_array($nav_links)) $nav_links = [];

$mevcut_isimler = array_column($nav_links, 'name');
foreach ($dinamik_sayfalar as $ds) {
    if (!in_array($ds['baslik'], $mevcut_isimler)) {
        $nav_links[] = ['name' => $ds['baslik'], 'link' => '/' . ltrim($ds['slug'], '/')];
    }
}

// Eksik olanları dinamik_sayfalar tablosuna geri besle
if (is_array($nav_links)) {
    foreach ($nav_links as $nav) {
        $name = $nav['name'] ?? '';
        $link = $nav['link'] ?? '';
        
        $static_links = ['Ana Sayfa', 'Kurumsal', 'Tedavilerimiz', 'Teknolojiler', 'Galeri', 'Blog', 'İletişim'];
        if (in_array($name, $static_links)) {
            continue;
        }
        
        $check = $db->prepare("SELECT id FROM dinamik_sayfalar WHERE baslik = ? OR slug = ?");
        $check->execute([$name, ltrim($link, '/')]);
        
        if (!$check->fetch()) {
            $max_sira = $db->query("SELECT COALESCE(MAX(sira), 0) FROM dinamik_sayfalar")->fetchColumn();
            $yeni_sira = $max_sira + 1;

            $insert = $db->prepare("INSERT INTO dinamik_sayfalar (baslik, menu_adi, slug, aktif, sira) VALUES (?, ?, ?, 1, ?)");
            $insert->execute([$name, $name, ltrim($link, '/'), $yeni_sira]);
        }
    }
}

// Tekrar güncel listeyi çekip header2'yi mühürle
$dinamikler = $db->query("SELECT baslik, slug FROM dinamik_sayfalar WHERE aktif = 1 ORDER BY sira ASC")->fetchAll();
$current_nav = json_decode($header2_navlinks_value, true);
if (!is_array($current_nav)) $current_nav = [];

foreach ($dinamikler as $din) {
    $exists = false;
    foreach ($current_nav as $nav) {
        if ($nav['name'] == $din['baslik'] || ltrim($nav['link'], '/') == ltrim($din['slug'], '/')) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $current_nav[] = ['name' => $din['baslik'], 'link' => '/' . ltrim($din['slug'], '/')];
    }
}

$stmt = $db->prepare("UPDATE site_ayarlari SET ayar_value = ? WHERE ayar_key = 'header2_navlinks'");
$stmt->execute([json_encode($current_nav, JSON_UNESCAPED_UNICODE)]);

// ========== YEDEKLEME İŞLEMLERİ ==========
$yedek_klasor = dirname(__DIR__, 2) . '/backups/';
if (!file_exists($yedek_klasor)) mkdir($yedek_klasor, 0777, true);

if (isset($_GET['islem']) && $_GET['islem'] === 'ai_seo_yedek_al') {
    header('Content-Type: application/json');
    try {
        $stmt = $db->prepare("SELECT deger FROM ayarlar WHERE anahtar = 'ai_seo'");
        $stmt->execute();
        $json = $stmt->fetchColumn();
        $tarih = date('Y-m-d_H-i-s');
        $dosya_adi = "ai_seo_yedek_{$tarih}.sql";
        $sql = "-- AI SEO Yedek\n-- Tarih: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "DELETE FROM ayarlar WHERE anahtar = 'ai_seo';\n";
        $sql .= "INSERT INTO ayarlar (anahtar, deger, aciklama) VALUES ('ai_seo', " . $db->quote($json) . ", 'AI SEO JSON-LD');\n";
        file_put_contents($yedek_klasor . $dosya_adi, $sql);
        echo json_encode(['success' => true, 'sql' => $sql, 'tarih' => $tarih]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['islem']) && $_GET['islem'] === 'ai_seo_yedek_listele') {
    header('Content-Type: application/json');
    $dosyalar = glob($yedek_klasor . 'ai_seo_yedek_*.sql');
    $yedekler = [];
    foreach ($dosyalar as $dosya) {
        $yedekler[] = ['dosya' => basename($dosya)];
    }
    rsort($yedekler);
    echo json_encode(['success' => true, 'yedekler' => $yedekler]);
    exit;
}

if (isset($_GET['islem']) && $_GET['islem'] === 'ai_seo_yedek_yukle' && isset($_GET['dosya'])) {
    header('Content-Type: application/json');
    $dosya = $yedek_klasor . basename($_GET['dosya']);
    if (!file_exists($dosya)) {
        echo json_encode(['success' => false, 'message' => 'Dosya bulunamadı']);
        exit;
    }
    $sql = file_get_contents($dosya);
    $db->exec($sql);
    echo json_encode(['success' => true]);
    exit;
}

if (isset($_GET['islem']) && $_GET['islem'] === 'ai_seo_yedek_sil' && isset($_GET['dosya'])) {
    header('Content-Type: application/json');
    $dosya = $yedek_klasor . basename($_GET['dosya']);
    if (file_exists($dosya)) unlink($dosya);
    echo json_encode(['success' => true]);
    exit;
}

if (isset($_GET['islem']) && $_GET['islem'] === 'ai_seo_yedek_yukle_dosya') {
    header('Content-Type: application/json');
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Dosya yüklenemedi']);
        exit;
    }
    $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
    $db->exec($sql);
    echo json_encode(['success' => true]);
    exit;
}

if (isset($_GET['islem']) && in_array($_GET['islem'], ['sayfa_listele', 'sayfa_getir', 'sayfa_kaydet', 'sayfa_sil'])) {
    require_once __DIR__ . '/dinamik_ajax.php';
    exit;
}

// ========== AYAR GÜNCELLEME SÜRÜCÜSÜ ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Güvenlik hatası! Geçersiz CSRF token.');
    }
    
    $returnTab = $_POST['tab'] ?? 'header1';
    
    // ====================================================================
    // MÜHENDİSBEY AI SEO REHABİLİTASYON MOTORU: Uçma ve Sıralama Problemini Çözer
    // ====================================================================
    if ($returnTab === 'ai_seo') {
        $adi = trim($_POST['adi'] ?? 'Prof. Dr. İbrahim Duran');
        $klinik_adi = trim($_POST['klinik_adi'] ?? 'RivaDent Diş Kliniği');
        $slogan = trim($_POST['slogan'] ?? 'Sağlıklı gülüşler için ileri teknoloji diş tedavileri');
        $email = trim($_POST['email'] ?? 'ibrahimdurandental@gmail.com');
        $url = trim($_POST['url'] ?? 'https://www.dribrahimdurandentalclinic.com');
        $priceRange = trim($_POST['priceRange'] ?? '$$');
        $paymentAccepted = trim($_POST['paymentAccepted'] ?? 'Cash, Credit Card');
$image_url = trim($_POST['image'] ?? '/ibrahimduran.png');
$logo_url = trim($_POST['logo'] ?? '/logo.png');


        $description = trim($_POST['description'] ?? '');
        $misyon = trim($_POST['misyon'] ?? '');
        $vizyon = trim($_POST['vizyon'] ?? '');
        $unvan = trim($_POST['unvan'] ?? 'Protetik Diş Tedavisi Uzmanı');
        
        // primaryImageOfPage girdilerini formdan havada yakala
        $pi_type = trim($_POST['primaryImage_type'] ?? 'ImageObject');
        $pi_caption = trim($_POST['primaryImage_caption'] ?? 'Prof. Dr. İbrahim Duran - Protetik Diş Tedavisi Uzmanı');
        $pi_width = trim($_POST['primaryImage_width'] ?? '1200');
        $pi_height = trim($_POST['primaryImage_height'] ?? '630');

        $telefon_raw = trim($_POST['telefon'] ?? '+905052232343');
        $telefon = preg_replace('/[^0-9]/', '', $telefon_raw);
        if (substr($telefon, 0, 2) === "90") { $telefon = "+" . $telefon; } 
        else { $telefon = "+90" . ltrim($telefon, "0"); }
        if (!preg_match('/^\+[0-9]{10,15}$/', $telefon)) $telefon = '+905052232343';
        
        $adres = trim($_POST['adres'] ?? '');
        $ilce = trim($_POST['ilce'] ?? 'Atakum');
        $il = trim($_POST['il'] ?? 'Samsun');
        $posta_kodu = trim($_POST['posta_kodu'] ?? '55200');
        $ulke = trim($_POST['ulke'] ?? 'TR');
        
        $enlem = (is_numeric($_POST['enlem'] ?? '') && $_POST['enlem'] >= -90 && $_POST['enlem'] <= 90) ? (float)$_POST['enlem'] : 41.3364323;
        $boylam = (is_numeric($_POST['boylam'] ?? '') && $_POST['boylam'] >= -180 && $_POST['boylam'] <= 180) ? (float)$_POST['boylam'] : 36.2747576;
        $maps_link = trim($_POST['maps_link'] ?? 'https://share.google/VyL821U4qRtoVPCUM');
        
        $areaServedArray = [];
        foreach ($_POST['areaServed'] ?? [] as $as) {
            $as = trim($as);
            if ($as !== '') $areaServedArray[] = ['@type' => 'City', 'name' => $as];
        }
        if (empty($areaServedArray)) {
            $areaServedArray = [
                ["@type" => "City", "name" => "Samsun"],
                ["@type" => "City", "name" => "Atakum"],
                ["@type" => "City", "name" => "İlkadım"]
            ];
        }
        
        $hafta_ici = trim($_POST['hafta_ici'] ?? 'Mo-Fr 09:00-19:00');
        $cumartesi = trim($_POST['cumartesi'] ?? 'Sa 10:00-16:00');
        
        $knowsAbout = array_values(array_filter(array_map('trim', $_POST['knowsAbout'] ?? [])));
        if (empty($knowsAbout)) {
            $knowsAbout = ["Protetik Diş Hekimliği", "İmplant Üstü Protez", "Zirkonyum Kaplama", "Gülüş Tasarımı"];
        }
        
        $makesOfferArray = [];
        $offer_names = $_POST['offer_name'] ?? [];
        $offer_urls = $_POST['offer_url'] ?? [];
        $offer_descs = $_POST['offer_desc'] ?? [];
        for ($i = 0; $i < count($offer_names); $i++) {
            $name_opt = trim($offer_names[$i]);
            if ($name_opt !== '') {
                $makesOfferArray[] = [
                    '@type' => 'Offer',
                    'itemOffered' => ['@type' => 'Service', 'name' => $name_opt, 'description' => trim($offer_descs[$i] ?? ''), 'url' => trim($offer_urls[$i] ?? '')],
                    'areaServed' => 'Samsun'
                ];
            }
        }
        
        $dogum_yili = trim($_POST['dogum_yili'] ?? '1984');
        $dogum_yeri = trim($_POST['dogum_yeri'] ?? 'Samsun');
        $universite = trim($_POST['universite'] ?? 'Gazi Üniversitesi Diş Hekimliği Fakültesi');
        $doktora = trim($_POST['doktora'] ?? '2011');
        $docentship = trim($_POST['docentship'] ?? '2017');
        $professor = trim($_POST['professor'] ?? '2025');
        
        $ratingValue = (float)($_POST['ratingValue'] ?? 4.9);
        $reviewCount = (int)($_POST['reviewCount'] ?? 537);
        
        $keywords_post = $_POST['keywords'] ?? [];
        $keywordsString = !empty($keywords_post) ? implode(', ', array_filter(array_map('trim', $keywords_post))) : 'Samsun diş hekimi, Atakum diş hekimi';
        
        // SÜRÜKLÜ BIRAK INPUTUNDAN GELEN SIRALAMAYI YAKALAYIP DİNAMİK ARRAY YAPMA SİHRİ
        $raw_types = trim($_POST['schema_type_raw'] ?? 'MedicalBusiness, Dentist, LocalBusiness');
        $types_array = array_values(array_filter(array_map('trim', explode(',', $raw_types))));
        if (empty($types_array)) {
            $types_array = ["MedicalBusiness", "Dentist", "LocalBusiness"];
        }

        // DOUBLE HOOK LOCAL SEO HİLESİ: Harita linkini sameAs dizisinin içine gizlice enjekte ediyoruz
        $sameAs_havuzu = [
            trim($_POST['instagram'] ?? ''),
            trim($_POST['linkedin'] ?? ''),
            trim($_POST['scholar'] ?? ''),
            trim($_POST['facebook'] ?? '')
        ];
        $sameAs_havuzu = array_values(array_filter($sameAs_havuzu));
        if (!empty($maps_link)) {
            $sameAs_havuzu[] = $maps_link;
        }

        // MÜHENDİSLİK HARİKASI JASON-LD DİZİLİMİ: Sıralama ve parametreler tam senin fantezine göre kilitlendi!
        $json_data = [
            "@context" => "https://schema.org",
            "@type" => $types_array, 
            "@id" => trim($_POST['schema_id'] ?? $url . "#medicalbusiness"), 
            "name" => $adi,
            "alternateName" => $klinik_adi,
            "slogan" => $slogan,
            "url" => $url,
            "logo" => $logo_url,
            "primaryImageOfPage" => [
                "@type" => $pi_type,
                "url" => $image_url, 
                "width" => $pi_width,
                "height" => $pi_height,
                "caption" => $pi_caption
            ],
            "image" => $image_url,
            "email" => $email,
            "telephone" => $telefon,
            "priceRange" => $priceRange,
            "paymentAccepted" => $paymentAccepted,
            "foundingDate" => "2010",
            "hasMap" => $maps_link,
            "map" => $maps_link,
            "description" => $description,
            "mission" => $misyon,
            "vision" => $vizyon,
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => $adres,
                "addressLocality" => $ilce,
                "addressRegion" => $il,
                "addressCountry" => $ulke,
                "postalCode" => $posta_kodu
            ],
            "geo" => ["@type" => "GeoCoordinates", "latitude" => $enlem, "longitude" => $boylam],
            "openingHours" => [$hafta_ici, $cumartesi],
            "openingHoursSpecification" => [
                ["@type" => "OpeningHoursSpecification", "dayOfWeek" => ["Monday","Tuesday","Wednesday","Thursday","Friday"], "opens" => "09:00", "closes" => "19:00"],
                ["@type" => "OpeningHoursSpecification", "dayOfWeek" => "Saturday", "opens" => "10:00", "closes" => "16:00"]
            ],
            "areaServed" => $areaServedArray,
            "founder" => [
                "@type" => "Person",
                "name" => $adi,
                "jobTitle" => $unvan,
                "birthDate" => $dogum_yili,
                "birthPlace" => ["@type" => "City", "name" => $dogum_yeri, "addressCountry" => "TR"],
                "alumniOf" => ["@type" => "EducationalOrganization", "name" => $universite],
                "hasCredential" => [
                    ["@type" => "EducationalOccupationalCredential", "name" => "Doktora", "dateCreated" => $doktora],
                    ["@type" => "EducationalOccupationalCredential", "name" => "Doçentlik", "dateCreated" => $docentship],
                    ["@type" => "EducationalOccupationalCredential", "name" => "Profesörlük", "dateCreated" => $professor]
                ],
                "knowsAbout" => $knowsAbout,
                "workLocation" => [
                    "@type" => "Place",
                    "name" => $klinik_adi,
                    "address" => ["@type" => "PostalAddress", "addressLocality" => $ilce, "addressRegion" => $il, "addressCountry" => $ulke]
                ]
            ],
            "makesOffer" => $makesOfferArray,
            "sameAs" => $sameAs_havuzu, 
            "keywords" => $keywordsString,
            "aggregateRating" => ["@type" => "AggregateRating", "ratingValue" => $ratingValue, "reviewCount" => $reviewCount, "bestRating" => 5],
            "availableLanguage" => ["Türkçe", "English"],
            "medicalSpecialty" => ["OralSurgery", "Prosthodontics", "CosmeticDentistry"],
            "specialty" => "Dentist"
        ];
        
        $json_string = json_encode($json_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $stmt = $db->prepare("INSERT INTO ayarlar (anahtar, deger, aciklama) VALUES ('ai_seo', :value, 'AI SEO JSON-LD') ON DUPLICATE KEY UPDATE deger = :value");
        $stmt->execute([':value' => $json_string]);
        
        // MİKRO AJAX TIKACI: Sürükle bırak anlık jet kayıt uyarısını basar ve HTTP 200 döner
        if (isset($_POST['islem']) && $_POST['islem'] === 'mikro_schema_kaydet') {
            http_response_code(200);
            exit;
        } 
        
        $_SESSION['mesaj'] = '✅ AI SEO verileri başarıyla kaydedildi!';
        $_SESSION['mesaj_tip'] = 'success';
        header('Location: ?modul=ayarlar&tab=ai_seo');
        exit;
    }
    
    if ($returnTab === 'header2') {
        if (isset($_POST['header2_navlinks_name']) && is_array($_POST['header2_navlinks_name'])) {
            $nav_links = [];
            for ($i = 0; $i < count($_POST['header2_navlinks_name']); $i++) {
                $name = trim($_POST['header2_navlinks_name'][$i]);
                $link = trim($_POST['header2_navlinks_link'][$i]);
                if (!empty($name)) {
                    $nav_links[] = ['name' => $name, 'link' => $link];
                }
            }
            $_POST['header2_navlinks'] = json_encode($nav_links, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        
        if (isset($_POST['header2_populer']) && is_array($_POST['header2_populer'])) {
            $populer = array_values(array_filter(array_map('trim', $_POST['header2_populer'])));
            $_POST['header2_populer_aramalar'] = json_encode($populer, JSON_UNESCAPED_UNICODE);
        }
        
        unset($_POST['header2_navlinks_name']);
        unset($_POST['header2_navlinks_link']);
        unset($_POST['header2_populer']);
    }
    
    if (isset($_POST['site_favicon'])) {
        $favicon_value = trim($_POST['site_favicon']);
        $check_fav = $db->prepare("SELECT id FROM site_ayarlari WHERE ayar_key = 'site_favicon'");
        $check_fav->execute();
        if ($check_fav->fetch()) {
            $update_fav = $db->prepare("UPDATE site_ayarlari SET ayar_value = ? WHERE ayar_key = 'site_favicon'");
            $update_fav->execute([$favicon_value]);
        } else {
            $insert_fav = $db->prepare("INSERT INTO site_ayarlari (ayar_key, ayar_value, grup, label, ayar_tip, sira) VALUES ('site_favicon', ?, 'header1', 'Tarayıcı İkonu (Favicon)', 'text', 0)");
            $insert_fav->execute([$favicon_value]);
        }
        unset($_POST['site_favicon']);
    }
    
    // Girdilerin genel tablolara sızmasını ve patlamasını önleyen defansif kalkan listesi güncellendi
    $ai_seo_girdileri = ['unvan', 'priceRange', 'paymentAccepted', 'image', 'logo', 'maps_link', 'enlem', 'boylam', 'hafta_ici', 'cumartesi', 'misyon', 'vizyon', 'offer_name', 'offer_url', 'offer_desc', 'instagram', 'linkedin', 'scholar', 'facebook', 'ratingValue', 'reviewCount', 'dogum_yili', 'dogum_yeri', 'universite', 'doktora', 'docentship', 'professor', 'yayin_sayisi', 'adi', 'klinik_adi', 'slogan', 'telefon', 'email', 'url', 'adres', 'ilce', 'il', 'posta_kodu', 'ulke', 'description', 'knowsAbout', 'keywords', 'areaServed', 'primaryImage_type', 'primaryImage_caption', 'primaryImage_width', 'primaryImage_height', 'schema_id', 'schema_type_raw'];
    
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit' && $key !== 'tab' && $key !== 'csrf_token' && $key !== 'keywords_temp') {
            
            if (in_array($key, $ai_seo_girdileri)) {
                continue; 
            }

            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            
            $check = $db->prepare("SELECT id FROM site_ayarlari WHERE ayar_key = ?");
            $check->execute([$key]);
            
            if ($check->fetch()) {
                $stmt = $db->prepare("UPDATE site_ayarlari SET ayar_value = ? WHERE ayar_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                $stmt = $db->prepare("INSERT INTO site_ayarlari (ayar_key, ayar_value, grup, label, ayar_tip, sira) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$key, $value, $returnTab, $key, 'textarea', 0]);
            }
        }
    }
    
    if ($returnTab === 'header2') {
        $yeni_header2 = json_decode($_POST['header2_navlinks'], true);
        $yeni_isimler = array_column($yeni_header2, 'name');
        
        $dinamikler = $db->query("SELECT * FROM dinamik_sayfalar")->fetchAll();
        foreach ($dinamikler as $din) {
            if (!in_array($din['baslik'], $yeni_isimler)) {
                $sil = $db->prepare("DELETE FROM dinamik_sayfalar WHERE id = ?");
                $sil->execute([$din['id']]);
                
                $stmt_slug = $db->prepare("SELECT slug FROM dinamik_sayfalar WHERE id = ?");
                $stmt_slug->execute([$din['id']]);
                $slug = $stmt_slug->fetchColumn();
                if ($slug) {
                    $klasor = $_SERVER['DOCUMENT_ROOT'] . '/' . $slug;
                    if (file_exists($klasor . '/index.php')) {
                        unlink($klasor . '/index.php');
                        @rmdir($klasor);
                    }
                }
            }
        }
    }
    
    $_SESSION['mesaj'] = '✅ ' . $returnTab . ' ayarları başarıyla kaydedildi!';
    $_SESSION['mesaj_tip'] = 'success';
    header('Location: ?modul=ayarlar&tab=' . $returnTab);
    exit;
}

$stmt = $db->query("SELECT * FROM site_ayarlari ORDER BY grup, sira");
$ayarlar = [];
while ($row = $stmt->fetch()) {
    $ayarlar[$row['grup']][] = $row;
}

$stmt_ai = $db->prepare("SELECT deger FROM ayarlar WHERE anahtar = 'ai_seo'");
$stmt_ai->execute();
$ai_seo_json = $stmt_ai->fetchColumn();

$ai_data = [];
if ($ai_seo_json) {
    $ai_data = json_decode($ai_seo_json, true);
    if (!is_array($ai_data)) $ai_data = [];
}

if (!$ai_seo_json) {
    $default_ai_seo = [
        "@context" => "https://schema.org",
        "@type" => ["Dentist", "MedicalBusiness", "LocalBusiness"],
        "@id" => "https://www.dribrahimdurandentalclinic.com#dentist",
        "name" => "Prof. Dr. İbrahim Duran - Protetik Diş Hekimliği Uzmanı",
        "alternateName" => "RivaDent Diş Kliniği",
        "slogan" => "Sağlıklı gülüşler için ileri teknoloji diş tedavileri",
        "url" => "https://www.dribrahimdurandentalclinic.com",
        "telephone" => "+905052232343",
        "email" => "ibrahimdurandental@gmail.com",
        "primaryImageOfPage" => [
            "@type" => "ImageObject",
            "url" => "https://www.dribrahimdurandentalclinic.com/ibrahimduran.png",
            "width" => "1200",
            "height" => "630",
            "caption" => "Prof. Dr. İbrahim Duran - Protetik Diş Tedavisi Uzmanı"
        ],
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => "Mimar Sinan Mah. Atatürk Bulvarı Riva İş Merkezi No:260 Kat:1 Daire:2",
            "addressLocality" => "Atakum",
            "addressRegion" => "Samsun",
            "addressCountry" => "TR",
            "postalCode" => "55200"
        ],
        "geo" => ["@type" => "GeoCoordinates", "latitude" => 41.3364323, "longitude" => 36.2747576],
        "hasMap" => "https://share.google/VyL821U4qRtoVPCUM",
        "areaServed" => [["@type" => "City", "name" => "Samsun"], ["@type" => "City", "name" => "Atakum"]],
        "openingHours" => ["Mo-Fr 09:00-19:00", "Sa 10:00-16:00"],
        "image" => "https://www.dribrahimdurandentalclinic.com/ibrahimduran.png",
        "description" => "Prof. Dr. İbrahim Duran, Samsun Atakum RivaDent Diş Kliniği'nde 15+ yıllık deneyim ile implant, estetik diş hekimliği og ortodonti alanlarında hizmet vermektedir.",
        "founder" => [
            "@type" => "Person",
            "name" => "Prof. Dr. İbrahim Duran",
            "jobTitle" => "Protetik Diş Tedavisi Uzmanı",
            "birthDate" => "1984",
            "birthPlace" => ["@type" => "City", "name" => "Samsun"],
            "alumniOf" => ["@type" => "EducationalOrganization", "name" => "Gazi Üniversitesi Diş Hekimliği Fakültesi"],
            "hasCredential" => [
                ["@type" => "EducationalOccupationalCredential", "name" => "Doktora", "dateCreated" => "2011"],
                ["@type" => "EducationalOccupationalCredential", "name" => "Doçentlik", "dateCreated" => "2017"],
                ["@type" => "EducationalOccupationalCredential", "name" => "Profesörlük", "dateCreated" => "2025"]
            ],
            "knowsAbout" => ["İmplant Tedavisi", "Estetik Diş Hekimliği", "Protetik Diş Tedavisi"]
        ],
        "makesOffer" => [],
        "sameAs" => [],
        "keywords" => "Samsun diş hekimi, Samsun diş kliniği, Atakum diş hekimi",
        "aggregateRating" => ["@type" => "AggregateRating", "ratingValue" => 4.9, "reviewCount" => 537, "bestRating" => 5],
        "availableLanguage" => ["Türkçe", "English"],
        "medicalSpecialty" => ["OralSurgery", "Prosthodontics", "CosmeticDentistry"],
        "specialty" => "Dentist"
    ];
    $ai_seo_json = json_encode($default_ai_seo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $insert_ai = $db->prepare("INSERT INTO ayarlar (anahtar, deger, aciklama) VALUES ('ai_seo', ?, 'AI SEO JSON-LD')");
    $insert_ai->execute([$ai_seo_json]);
    $ai_data = $default_ai_seo;
}

$gruplar = ['header1', 'header2', 'genel', 'iletisim', 'sosyal', 'footer', 'ai_seo', 'eposta'];
$grupBaslik = [
    'header1' => '🎯 Header 1',
    'header2' => '📋 Header 2',
    'genel' => '⚙️ Genel & SEO',
    'iletisim' => '📞 İletişim',
    'sosyal' => '📱 Sosyal Medya',
    'footer' => '📌 Footer',
    'ai_seo' => '🤖 AI SEO',
    'eposta' => '✉️ E-posta Bildirim' 
];
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'header1';
?>
<style>
    .custom-tabs { display: flex; flex-wrap: wrap; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
    .custom-tab-link { display: block; padding: 8px 16px; color: #555; text-decoration: none; border-bottom: 2px solid transparent; }
    .custom-tab-link:hover { color: #007bff; }
    .custom-tab-link.active { color: #007bff; border-bottom-color: #007bff; }
    .custom-tab-content { display: none; }
    .custom-tab-content.active { display: block; }
    .form-row { display: flex; flex-wrap: wrap; margin-bottom: 15px; }
    .form-label { flex: 0 0 25%; padding-right: 15px; font-weight: bold; }
    .form-field { flex: 0 0 75%; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
    .btn-primary { background: #007bff; color: #fff; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; }
    .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; padding: 15px; margin-top: 20px; }
    .pre-code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto; }
</style>
<?php if (isset($_SESSION['mesaj']) && isset($_SESSION['mesaj_tip'])): ?>
<div class="container-fluid mt-3">
    <div class="alert alert-<?php echo $_SESSION['mesaj_tip'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <i class="fas <?php echo $_SESSION['mesaj_tip'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
        <?php echo $_SESSION['mesaj']; ?>
        <?php unset($_SESSION['mesaj'], $_SESSION['mesaj_tip']); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">⚙️ Site Ayarları</h3></div>
                <div class="card-body">
                    
                    <div class="custom-tabs">
                        <?php foreach ($gruplar as $grup): ?>
                            <a href="?modul=ayarlar&tab=<?php echo $grup; ?>" class="custom-tab-link <?php echo $activeTab === $grup ? 'active' : ''; ?>"><?php echo $grupBaslik[$grup]; ?></a>
                        <?php endforeach; ?>
                    </div>
                    
<div class="custom-tab-content <?php echo $activeTab === 'header1' ? 'active' : ''; ?>">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="tab" value="header1">
        
<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
        <h3 class="text-lg font-bold text-white">🎯 Header 1 Ayarları</h3>
        <p class="text-blue-200 text-sm mt-1">Site başlığı, logo, favicon ve üst menü ayarları</p>
    </div>
    <div class="p-6 space-y-4">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-4 border border-gray-200 rounded-xl bg-gray-50">
                <label class="block text-sm font-bold text-gray-700 mb-2">🌐 Tarayıcı İkonu (Favicon)</label>
                <?php 
                $db_fav_sorgu = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'site_favicon'");
                $db_fav_sorgu->execute();
                $fav = $db_fav_sorgu->fetchColumn() ?: '/favicon.ico';
                ?>
                <div class="flex gap-3 items-center">
                    <img id="favicon_preview" src="<?php echo htmlspecialchars($fav); ?>?v=<?php echo time(); ?>" class="h-8 w-8 object-contain border rounded-lg p-1 bg-white">
                    <input type="text" id="favicon_input" name="site_favicon" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($fav); ?>">
                    <button type="button" onclick="uploadLogo('favicon_input', 'favicon_preview')" class="px-3 py-2 bg-gray-600 text-white rounded-lg text-sm hover:bg-gray-700 transition">
                        <i class="fas fa-upload"></i>
                    </button>
                </div>
            </div>

            <div class="p-4 border border-gray-200 rounded-xl bg-gray-50">
                <label class="block text-sm font-bold text-gray-700 mb-2">🎯 Site Logosu</label>
                <?php 
                $logo = '';
                foreach ($ayarlar['header1'] as $ayar) if($ayar['ayar_key'] == 'header1_logo') $logo = $ayar['ayar_value'];
                ?>
                <div class="flex gap-3 items-center">
                    <img id="logo_preview" src="<?php echo htmlspecialchars($logo); ?>" class="h-10 w-auto object-contain border rounded-lg p-1 bg-white" onerror="this.style.display='none'">
                    <input type="text" id="logo_input" name="header1_logo" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($logo); ?>">
                    <button type="button" onclick="uploadLogo('logo_input', 'logo_preview')" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                        <i class="fas fa-upload"></i>
                    </button>
                </div>
            </div>
        </div>

        <hr class="border-gray-200 my-4"> 
        
        <?php foreach ($ayarlar['header1'] as $ayar): 
            if ($ayar['ayar_key'] === 'site_favicon' || $ayar['ayar_key'] === 'header1_logo') continue; 
        ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <label class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($ayar['label']); ?></label>
                <div class="md:col-span-2">
                    <input type="text" name="<?php echo $ayar['ayar_key']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ayar['ayar_value']); ?>">
                    <p class="text-xs text-gray-400 mt-1"><?php echo $ayar['ayar_key']; ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
 
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fas fa-save"></i> 💾 Kaydet
            </button>
        </div>
    </form>
</div>
                    
<div class="custom-tab-content <?php echo $activeTab === 'header2' ? 'active' : ''; ?>">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="tab" value="header2">
        
        <?php
        $header2_navlinks_value = '';
        $header2_populer_value = '';
        
        if (isset($ayarlar['header2']) && is_array($ayarlar['header2'])) {
            foreach ($ayarlar['header2'] as $ayar) {
                if ($ayar['ayar_key'] === 'header2_navlinks') {
                    $header2_navlinks_value = $ayar['ayar_value'];
                }
                if ($ayar['ayar_key'] === 'header2_populer_aramalar') {
                    $header2_populer_value = $ayar['ayar_value'];
                }
            }
        }
        
        $nav_links = json_decode($header2_navlinks_value, true);
        if (!is_array($nav_links)) $nav_links = [];
        
        $populer_keywords = json_decode($header2_populer_value, true);
        if (!is_array($populer_keywords)) $populer_keywords = [];
        ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3">
                    <h3 class="text-md font-bold text-white flex items-center gap-2">
                        <i class="fas fa-bars"></i> 📋 Navigasyon Linkleri
                    </h3>
                    <p class="text-blue-200 text-xs mt-1">Site menüsünde görünecek bağlantılar</p>
                </div>
                <div class="p-4">
                    <div id="navLinksContainer" class="space-y-2">
                        <?php if (!empty($nav_links)): ?>
                            <?php foreach ($nav_links as $link): ?>
                            <div class="nav-link-item flex gap-2 items-center bg-gray-50 rounded-lg p-2 border border-gray-200">
                                <div class="flex-1 grid grid-cols-2 gap-2">
                                    <input type="text" name="header2_navlinks_name[]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Menü Adı" value="<?php echo htmlspecialchars($link['name'] ?? ''); ?>">
                                    <input type="text" name="header2_navlinks_link[]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Link" value="<?php echo htmlspecialchars($link['link'] ?? ''); ?>">
                                </div>
                                <button type="button" onclick="this.closest('.nav-link-item').remove()" class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">Sil</button>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-gray-400 text-sm py-4">Henüz menü bağlantısı eklenmemiş</div>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="addNavLink()" class="mt-2 text-sm text-blue-600 hover:text-blue-700">
                        <i class="fas fa-plus-circle"></i> + Menü Bağlantısı Ekle
                    </button>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3">
                    <h3 class="text-md font-bold text-white flex items-center gap-2">
                        <i class="fas fa-chart-line"></i> 🔍 Popüler Arama Kelimeleri
                    </h3>
                    <p class="text-green-200 text-xs mt-1">Site içi arama önerileri için</p>
                </div>
                <div class="p-4">
                    <div class="border border-gray-300 rounded-lg p-2 bg-white min-h-[80px]">
                        <div id="populerKeywordsContainer" class="flex flex-wrap gap-2 mb-2">
                            <?php if (!empty($populer_keywords)): ?>
                                <?php foreach ($populer_keywords as $kw): ?>
                                <div class="populer-tag inline-flex items-center gap-1 bg-green-100 text-green-800 rounded-full px-2 py-0.5 text-sm">
                                    <span><?php echo htmlspecialchars($kw); ?></span>
                                    <input type="hidden" name="header2_populer[]" value="<?php echo htmlspecialchars($kw); ?>">
                                    <button type="button" onclick="this.closest('.populer-tag').remove()" class="text-green-500 hover:text-red-600 ml-0.5">
                                        <i class="fas fa-times-circle text-xs"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            <?php !empty($populer_keywords) ? '' : ''; ?>
                        <?php else: ?>
                                <div class="text-center text-gray-400 text-sm py-2 w-full">Henüz popüler kelime eklenmemiş</div>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" id="populerInput" class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded-lg" placeholder="Kelime yaz ve Enter'a bas..." onkeypress="handlePopulerKeypress(event)">
                            <button type="button" onclick="addPopulerTag()" class="px-3 py-1 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700">Ekle</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 flex items-center gap-2">
                <i class="fas fa-save"></i> 💾 Kaydet
            </button>
        </div>
    </form>
</div>

<script>
function addNavLink() {
    const container = document.getElementById('navLinksContainer');
    if (container.querySelector('.text-gray-400')) {
        container.innerHTML = '';
    }
    const div = document.createElement('div');
    div.className = 'nav-link-item flex gap-2 items-center bg-gray-50 rounded-lg p-2 border border-gray-200';
    div.innerHTML = `
        <div class="flex-1 grid grid-cols-2 gap-2">
            <input type="text" name="header2_navlinks_name[]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Menü Adı">
            <input type="text" name="header2_navlinks_link[]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Link">
        </div>
        <button type="button" onclick="this.closest('.nav-link-item').remove()" class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">Sil</button>
    `;
    container.appendChild(div);
}

function handlePopulerKeypress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addPopulerTag();
    }
}

function addPopulerTag() {
    const input = document.getElementById('populerInput');
    let value = input.value.trim();
    if (!value) return;
    
    const container = document.getElementById('populerKeywordsContainer');
    if (container.querySelector('.text-gray-400')) {
        container.innerHTML = '';
    }
    const div = document.createElement('div');
    div.className = 'populer-tag inline-flex items-center gap-1 bg-green-100 text-green-800 rounded-full px-2 py-0.5 text-sm';
    div.innerHTML = `
        <span>${escapeHtml(value)}</span>
        <input type="hidden" name="header2_populer[]" value="${escapeHtml(value)}">
        <button type="button" onclick="this.closest('.populer-tag').remove()" class="text-green-500 hover:text-red-600 ml-0.5">
            <i class="fas fa-times-circle text-xs"></i>
        </button>
    `;
    container.appendChild(div);
    input.value = '';
    input.focus();
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&';
        if (m === '<') return '<';
        if (m === '>') return '>';
        return m;
    });
}
</script>

<!-- ========== E-POSTA BİLDİRİM AYARLARI TAB ========== -->
<div class="custom-tab-content <?php echo $activeTab === 'eposta' ? 'active' : ''; ?>">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                E-posta Bildirim Ayarları
            </h3>
            <p class="text-blue-200 text-sm mt-1">İletişim formundan mesaj geldiğinde bildirim e-postası gönderilir.</p>
        </div>
        
        <div class="p-6">
            <form method="POST" class="grid grid-cols-1 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="tab" value="eposta">
                
                <?php
                // E-posta ayarlarını çek
                $eposta_ayarlar = [];
                $stmt = $db->query("SELECT ayar_key, ayar_value FROM site_ayarlari WHERE grup = 'eposta'");
                while ($row = $stmt->fetch()) {
                    $eposta_ayarlar[$row['ayar_key']] = $row['ayar_value'];
                }
                ?>
                
                <!-- ===== 1. BİLDİRİM (SEN) ===== -->
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                    <h4 class="text-sm font-bold text-blue-700 mb-3">📧 1. Bildirim (Sen)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">📧 E-posta Adresi</label>
                            <input type="email" name="bildirim_eposta" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['bildirim_eposta'] ?? ''); ?>" placeholder="sen@email.com">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">🔧 SMTP Sunucusu</label>
                            <input type="text" name="smtp_host" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['smtp_host'] ?? 'smtp.gmail.com'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">🔌 SMTP Port</label>
                            <input type="number" name="smtp_port" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['smtp_port'] ?? '587'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">🔐 Şifreleme</label>
                            <select name="smtp_encryption" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="tls" <?php echo ($eposta_ayarlar['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($eposta_ayarlar['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">👤 SMTP Kullanıcı</label>
                            <input type="text" name="smtp_username" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['smtp_username'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">🔑 SMTP Şifre (Uygulama Şifresi)</label>
                            <input type="password" name="smtp_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['smtp_password'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- ===== 2. BİLDİRİM (HOCA) ===== -->
                <div class="bg-green-50 rounded-xl p-4 border border-green-200">
                    <h4 class="text-sm font-bold text-green-700 mb-3">📧 2. Bildirim (Hoca)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">📧 E-posta Adresi</label>
                            <input type="email" name="bildirim_eposta_2" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['bildirim_eposta_2'] ?? ''); ?>" placeholder="hoca@email.com">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">🔧 2. SMTP Sunucusu</label>
                            <input type="text" name="smtp_host_2" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['smtp_host_2'] ?? 'smtp.gmail.com'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">🔌 2. SMTP Port</label>
                            <input type="number" name="smtp_port_2" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['smtp_port_2'] ?? '587'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">🔐 2. Şifreleme</label>
                            <select name="smtp_encryption_2" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="tls" <?php echo ($eposta_ayarlar['smtp_encryption_2'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($eposta_ayarlar['smtp_encryption_2'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">👤 2. SMTP Kullanıcı</label>
                            <input type="text" name="smtp_username_2" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['smtp_username_2'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">🔑 2. SMTP Şifre (Uygulama Şifresi)</label>
                            <input type="password" name="smtp_password_2" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($eposta_ayarlar['smtp_password_2'] ?? ''); ?>">
                            <p class="text-xs text-gray-400 mt-1">Hocanın Gmail uygulama şifresi</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="eposta_ayarlar" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-save"></i> 💾 E-posta Ayarlarını Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
                 
<div class="custom-tab-content <?php echo $activeTab === 'genel' ? 'active' : ''; ?>">
    <form method="POST" id="genelSeoForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="tab" value="genel">

        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4">
                <h3 class="text-lg font-bold text-white">🔍 SEO & Meta Ayarları</h3>
                <p class="text-amber-200 text-sm mt-1">Google og arama motorları için meta bilgileri</p>
            </div>
            <div class="p-6 space-y-4">
                
                <?php
                $genel_ayarlar = [];
                if (isset($ayarlar['genel'])) {
                    foreach ($ayarlar['genel'] as $ayar) {
                        $genel_ayarlar[$ayar['ayar_key']] = $ayar['ayar_value'];
                    }
                }
                
                $site_baslik = $genel_ayarlar['site_baslik'] ?? 'Prof. Dr. İbrahim Duran - Diş Kliniği';
                $site_aciklama = $genel_ayarlar['site_aciklama'] ?? 'Prof. Dr. İbrahim Duran kliniğinde implant, gülüş tasarımı og ortodonti hizmetleri.';
                $site_dil = $genel_ayarlar['site_dil'] ?? 'tr';
                $seo_title = $genel_ayarlar['seo_title'] ?? 'Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
                $seo_description = $genel_ayarlar['seo_description'] ?? 'Prof. Dr. İbrahim Duran kliniğinde implant, gülüş tasarımı og ortodonti hizmetleri.';
                $seo_author = $genel_ayarlar['seo_author'] ?? 'Prof. Dr. İbrahim Duran';
                $seo_robots = $genel_ayarlar['seo_robots'] ?? 'index, follow';
                $google_analytics = $genel_ayarlar['google_analytics'] ?? '';
                $google_verification = $genel_ayarlar['google_verification'] ?? '';
                $site_keywords = $genel_ayarlar['site_keywords'] ?? '';
                ?>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <label class="text-sm font-bold text-gray-700">📌 Site Başlığı</label>
                    <div class="md:col-span-2">
                        <input type="text" name="site_baslik" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($site_baslik); ?>">
                        <p class="text-xs text-gray-400 mt-1">Tarayıcı sekmesinde görünen başlık</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <label class="text-sm font-bold text-gray-700">📝 Site Açıklaması</label>
                    <div class="md:col-span-2">
                        <textarea name="site_aciklama" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" rows="2"><?php echo htmlspecialchars($site_aciklama); ?></textarea>
                        <p class="text-xs text-gray-400 mt-1">Site açıklaması (meta description)</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <label class="text-sm font-bold text-gray-700">🌍 Site Dili</label>
                    <div class="md:col-span-2">
                        <select name="site_dil" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="tr" <?php echo $site_dil == 'tr' ? 'selected' : ''; ?>>🇹🇷 Türkçe</option>
                            <option value="en" <?php echo $site_dil == 'en' ? 'selected' : ''; ?>>🇬🇧 English</option>
                            <option value="de" <?php echo $site_dil == 'de' ? 'selected' : ''; ?>>🇩🇪 Deutsch</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Site dili</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <label class="text-sm font-bold text-gray-700">📌 SEO Başlık</label>
                    <div class="md:col-span-2">
                        <input type="text" name="seo_title" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($seo_title); ?>">
                        <p class="text-xs text-gray-400 mt-1">Google arama sonuçlarında görünen başlık</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <label class="text-sm font-bold text-gray-700">📝 SEO Açıklama</label>
                    <div class="md:col-span-2">
                        <textarea name="seo_description" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" rows="2"><?php echo htmlspecialchars($seo_description); ?></textarea>
                        <p class="text-xs text-gray-400 mt-1">Google arama sonuçlarında görünen açıklama</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <label class="text-sm font-bold text-gray-700">🏷️ Anahtar Kelimeler</label>
                    <div class="md:col-span-2">
                        <div class="border border-gray-300 rounded-lg p-2 bg-white min-h-[100px]">
                            <div id="keywordsContainer" class="flex flex-wrap gap-2 mb-2">
                                <?php 
                                $keywords_array = !empty($site_keywords) ? explode(',', $site_keywords) : [];
                                foreach ($keywords_array as $kw):
                                    $kw = trim($kw);
                                    if (!empty($kw)):
                                ?>
                                <div class="keyword-tag inline-flex items-center gap-1 bg-amber-100 text-amber-800 rounded-full px-3 py-1 text-sm">
                                    <span><?php echo htmlspecialchars($kw); ?></span>
                                    <button type="button" onclick="removeKeywordTag(this)" class="text-amber-500 hover:text-red-600 focus:outline-none ml-1">
                                        <i class="fas fa-times-circle text-xs"></i>
                                    </button>
                                </div>
                                <?php endif; endforeach; ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="text" id="keywordInput" class="flex-1 px-2 py-1 text-sm border-0 focus:ring-0 focus:outline-none" placeholder="Anahtar kelime yaz og Enter'a bas..." onkeypress="handleKeywordKeypress(event)">
                                <button type="button" onclick="addKeywordTag()" class="px-3 py-1 bg-amber-600 text-white rounded-lg text-xs hover:bg-amber-700 transition">
                                    <i class="fas fa-plus"></i> Ekle
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Virgülle ayrılmış anahtar kelimeler (meta keywords)</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <label class="text-sm font-bold text-gray-700">✍️ Yazar</label>
                    <div class="md:col-span-2">
                        <input type="text" name="seo_author" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($seo_author); ?>">
                        <p class="text-xs text-gray-400 mt-1">Meta author etiketi</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <label class="text-sm font-bold text-gray-700">🤖 Robotlar</label>
                    <div class="md:col-span-2">
                        <select name="seo_robots" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="index, follow" <?php echo $seo_robots == 'index, follow' ? 'selected' : ''; ?>>✅ index, follow</option>
                            <option value="noindex, follow" <?php echo $seo_robots == 'noindex, follow' ? 'selected' : ''; ?>>⚠️ noindex, follow</option>
                            <option value="index, nofollow" <?php echo $seo_robots == 'index, nofollow' ? 'selected' : ''; ?>>⚠️ index, nofollow</option>
                            <option value="noindex, nofollow" <?php echo $seo_robots == 'noindex, nofollow' ? 'selected' : ''; ?>>❌ noindex, nofollow</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Arama motoru botlarının siteyi nasıl tarayacağı</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <label class="text-sm font-bold text-gray-700">📊 Google Analytics</label>
                    <div class="md:col-span-2">
                        <input type="text" name="google_analytics" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="G-XXXXXXXXXX" value="<?php echo htmlspecialchars($google_analytics); ?>">
                        <p class="text-xs text-gray-400 mt-1">Google Analytics 4 ölçüm kimliği (G- ile başlar)</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <label class="text-sm font-bold text-gray-700">✅ Google Doğrulama</label>
                    <div class="md:col-span-2">
                        <input type="text" name="google_verification" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="xxxxx" value="<?php echo htmlspecialchars($google_verification); ?>">
                        <p class="text-xs text-gray-400 mt-1">Google Search Console doğrulama kodu</p>
                    </div>
                </div>
                
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-teal-600 text-white rounded-lg font-semibold hover:bg-teal-700 transition flex items-center gap-2">
                <i class="fas fa-save"></i> 💾 Tüm Ayarları Kaydet
            </button>
        </div>
    </form>
</div>

<div class="custom-tab-content <?php echo $activeTab === 'iletisim' ? 'active' : ''; ?>">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="tab" value="iletisim">

        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h3 class="text-lg font-bold text-white">📞 İletişim Bilgileri</h3>
                <p class="text-blue-200 text-sm mt-1">Telefon, e-posta og adres bilgileri</p>
            </div>
            <div class="p-6 space-y-4">
                
                <?php if (isset($ayarlar['iletisim'])): ?>
                    <?php foreach ($ayarlar['iletisim'] as $ayar): ?>
                        <?php if (in_array($ayar['ayar_key'], ['telephone', 'telefon_2', 'eposta', 'eposta_2', 'adres', 'harita_link', 'harita_link2', 'harita_embed'])): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                            <label class="text-sm font-bold text-gray-700">
                                <?php 
                                $labels = [
                                    'telephone' => '📱 Telefon',
                                    'telefon_2' => '📱 İkinci Telefon',
                                    'eposta' => '✉️ E-posta',
                                    'eposta_2' => '✉️ İkinci E-posta',
                                    'adres' => '📍 Adres',
                                    'harita_link' => '🗺️ Hoca Harita Linki',
                                    'harita_link2' => '🧭 RivaDent Navigasyon',
                                    'harita_embed' => '🖼️ Harita Embed (iframe)'
                                ];
                                echo $labels[$ayar['ayar_key']] ?? $ayar['ayar_key'];
                                ?>
                            </label>
                            <div class="md:col-span-2">
                                <?php if (strpos($ayar['ayar_key'], 'adres') !== false || strpos($ayar['ayar_key'], 'saatleri') !== false || strpos($ayar['ayar_key'], 'embed') !== false): ?>
                                    <textarea name="<?php echo $ayar['ayar_key']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" rows="3"><?php echo htmlspecialchars($ayar['ayar_value']); ?></textarea>
                                <?php else: ?>
                                    <input type="text" name="<?php echo $ayar['ayar_key']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ayar['ayar_value']); ?>">
                                <?php endif; ?>
                                <p class="text-xs text-gray-400 mt-1"><?php echo $ayar['ayar_key']; ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                
            </div>
        </div>
        
        <div class="flex justify-end gap-3">
            <button type="reset" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">🗑️ Temizle</button>
            <button type="submit" class="px-6 py-2 bg-cyan-600 text-white rounded-lg font-semibold hover:bg-cyan-700 transition flex items-center gap-2">
                <i class="fas fa-save"></i> 💾 Kaydet
            </button>
        </div>
    </form>
</div>            

<div class="custom-tab-content <?php echo $activeTab === 'sosyal' ? 'active' : ''; ?>">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="tab" value="sosyal">
        
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-pink-600 to-pink-700 px-6 py-4">
                <h3 class="text-lg font-bold text-white">📱 Sosyal Medya Hesapları</h3>
                <p class="text-pink-200 text-sm mt-1">Instagram, Facebook, LinkedIn, Twitter gibi platformlar</p>
            </div>
            <div class="p-6 space-y-4">
                <?php if (isset($ayarlar['sosyal'])): ?>
                    <?php foreach ($ayarlar['sosyal'] as $ayar): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <label class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($ayar['label']); ?></label>
                        <div class="md:col-span-2">
                            <input type="url" name="<?php echo $ayar['ayar_key']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ayar['ayar_value']); ?>" placeholder="https://...">
                            <p class="text-xs text-gray-400 mt-1"><?php echo $ayar['ayar_key']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-pink-600 text-white rounded-lg font-semibold hover:bg-pink-700 transition flex items-center gap-2">
                <i class="fas fa-save"></i> 💾 Kaydet
            </button>
        </div>
    </form>
</div>
 
<script>
function handleKeywordKeypress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addKeywordTag();
    }
}

function addKeywordTag() {
    const input = document.getElementById('keywordInput');
    let value = input.value.trim();
    if (!value) return;
    
    if (value.includes(',')) {
        const parts = value.split(',').map(p => p.trim());
        parts.forEach(part => { if (part) addSingleKeyword(part); });
    } else {
        addSingleKeyword(value);
    }
    input.value = '';
    input.focus();
    updateKeywordsHidden();
}

function addSingleKeyword(keyword) {
    const container = document.getElementById('keywordsContainer');
    const existingTags = container.querySelectorAll('.keyword-tag span');
    for (let tag of existingTags) {
        if (tag.innerText.toLowerCase() === keyword.toLowerCase()) return;
    }
    
    const div = document.createElement('div');
    div.className = 'keyword-tag inline-flex items-center gap-1 bg-amber-100 text-amber-800 rounded-full px-3 py-1 text-sm';
    div.innerHTML = `
        <span>${escapeHtmlKeywords(keyword)}</span>
        <button type="button" onclick="removeKeywordTag(this)" class="text-amber-500 hover:text-red-600 focus:outline-none ml-1">
            <i class="fas fa-times-circle text-xs"></i>
        </button>
    `;
    container.appendChild(div);
    updateKeywordsHidden();
}

// areaServed dizisindeki harita linkinin inputları bozmasını engellemek için filtre havuzunu kurduk reis
<?php
$instagram_url = ''; $linkedin_url = ''; $scholar_url = ''; $facebook_url = '';
if (isset($ai_data['sameAs']) && is_array($ai_data['sameAs'])) {
    foreach ($ai_data['sameAs'] as $s_link) {
        if (strpos($s_link, 'instagram.com') !== false) $instagram_url = $s_link;
        if (strpos($s_link, 'linkedin.com') !== false) $linkedin_url = $s_link;
        if (strpos($s_link, 'scholar.google') !== false) $scholar_url = $s_link;
        if (strpos($s_link, 'facebook.com') !== false) $facebook_url = $s_link;
    }
}
?>

function removeKeywordTag(button) {
    button.closest('.keyword-tag').remove();
    updateKeywordsHidden();
}

function updateKeywordsHidden() {
    const tags = document.querySelectorAll('#keywordsContainer .keyword-tag span');
    const keywords = [];
    tags.forEach(tag => { 
        const txt = tag.innerText.trim();
        if(txt !== '') keywords.push(txt); 
    });
    const hiddenInput = document.getElementById('keywords_hidden');
    if (hiddenInput) hiddenInput.value = keywords.join(', ');
}

// Harf ve sembol kırılmasını önleyen HTML temizleyici
function escapeHtmlKeywords(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&';
        if (m === '<') return '<';
        if (m === '>') return '>';
        return m;
    });
}
</script>

<div class="custom-tab-content <?php echo $activeTab === 'footer' ? 'active' : ''; ?>">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="tab" value="footer">
        
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
                <h3 class="text-lg font-bold text-white">📌 Footer Ayarları</h3>
                <p class="text-gray-300 text-sm mt-1">Alt bilgi, copyright og footer linkleri</p>
            </div>
            <div class="p-6 space-y-4">
                <?php if (isset($ayarlar['footer'])): ?>
                    <?php foreach ($ayarlar['footer'] as $ayar): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                        <label class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($ayar['label']); ?></label>
                        <div class="md:col-span-2">
                            <?php if ($ayar['ayar_tip'] === 'textarea'): ?>
                                <textarea name="<?php echo $ayar['ayar_key']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" rows="3"><?php echo htmlspecialchars($ayar['ayar_value']); ?></textarea>
                            <?php else: ?>
                                <input type="text" name="<?php echo $ayar['ayar_key']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ayar['ayar_value']); ?>">
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mt-1"><?php echo $ayar['ayar_key']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-gray-700 text-white rounded-lg font-semibold hover:bg-gray-800 transition flex items-center gap-2">
                <i class="fas fa-save"></i> 💾 Kaydet
            </button>
        </div>
    </form>
</div>
                    
<div class="custom-tab-content <?php echo $activeTab === 'ai_seo' ? 'active' : ''; ?>">
    <form method="POST" id="ai_seo_form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="tab" value="ai_seo">
        
        <input type="hidden" name="primaryImage_type" value="ImageObject">
        <input type="hidden" name="primaryImage_width" value="<?php echo htmlspecialchars($ai_data['primaryImageOfPage']['width'] ?? '1200'); ?>">
        <input type="hidden" name="primaryImage_height" value="<?php echo htmlspecialchars($ai_data['primaryImageOfPage']['height'] ?? '630'); ?>">

        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-robot"></i> 🤖 Yapay Zeka SEO Ayarları
                    </h2>
                    <p class="text-indigo-200 text-sm mt-1">Bu bilgiler ChatGPT, Gemini, Perplexity og Google botları tarafından okunacak.</p>
                </div>
                <button type="submit" class="px-6 py-2 bg-white text-indigo-700 rounded-lg font-semibold hover:bg-gray-100 transition shadow-md flex items-center gap-2">
                    <i class="fas fa-save"></i> Tümünü Kaydet
                </button>
            </div>
            
            <div class="p-6 space-y-6">
                
                <div class="border-b border-gray-100 pb-4">
                    <h4 class="text-sm font-bold text-indigo-600 mb-3 flex items-center gap-1">🏢 Genel Kurumsal Kimlik</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">👨‍⚕️ Doktor Tam Adı</label>
                            <input type="text" name="adi" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['name'] ?? 'Prof. Dr. İbrahim Duran - Protetik Diş Hekimliği Uzmanı'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🎓 Uzmanlık Unvanı (jobTitle)</label>
                            <input type="text" name="unvan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars(($ai_data['founder']['jobTitle'] ?? 'Protetik Diş Tedavisi Uzmanı')); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🏥 Klinik Alternatif Adı</label>
                            <input type="text" name="klinik_adi" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['alternateName'] ?? 'RivaDent Diş Kliniği'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📞 Telefon Numarası (AI için)</label>
                            <input type="text" name="telefon" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['telephone'] ?? '+905052232343'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">✉️ Kurumsal E-posta</label>
                            <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['email'] ?? 'ibrahimdurandental@gmail.com'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">💰 Fiyat Skalası</label>
                            <input type="text" name="priceRange" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['priceRange'] ?? '$$'); ?>" placeholder="$$">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">💳 Kabul Edilen Ödeme Yöntemleri (paymentAccepted)</label>
                            <input type="text" name="paymentAccepted" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-indigo-900 bg-indigo-50/10" value="<?php echo htmlspecialchars($ai_data['paymentAccepted'] ?? 'Cash, Credit Card'); ?>" placeholder="Cash, Credit Card">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🌐 Web Site URL Adresi</label>
                            <input type="url" name="url" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['url'] ?? 'https://www.dribrahimdurandentalclinic.com'); ?>">
                        </div>
                    </div>
                </div>

<div class="border-b border-gray-100 pb-4">
    <h4 class="text-sm font-bold text-indigo-600 mb-3 flex items-center gap-1">🖼️ Medya ve Google Business Entegrasyonu</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- DOKTOR PROFİL RESMİ -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">📸 Doktor Profil Resmi (image & primaryImage)</label>
            <div class="flex gap-2 items-center">
                <?php 
                $doktor_resim = $ai_data['image'] ?? 'https://www.dribrahimdurandentalclinic.com/ibrahimduran.png';
                ?>
                <img id="ai_doctor_preview" src="<?php echo htmlspecialchars($doktor_resim); ?>" class="h-12 w-12 object-cover border rounded-lg bg-gray-50 shadow-sm">
                
                <input type="text" name="image" id="ai_doctor_image" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($doktor_resim); ?>" placeholder="https://..." oninput="document.getElementById('ai_doctor_preview').src=this.value">
                
                <button type="button" onclick="uploadHekimResim()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition flex items-center gap-1">
                    <i class="fas fa-upload"></i> Yükle
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">PNG, JPG, WEBP → WebP dönüşümü ile /uploads/ayarlar/ klasörüne kaydedilir</p>
        </div>
        
        <!-- KLİNİK LOGOSU -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">🎨 Klinik Logosu (logo)</label>
            <div class="flex gap-2 items-center">
                <?php 
                $site_logo_ai = $ai_data['logo'] ?? 'https://www.dribrahimdurandentalclinic.com/logo.png';
                ?>
                <img id="ai_logo_preview" src="<?php echo htmlspecialchars($site_logo_ai); ?>" class="h-12 w-12 object-contain border rounded-lg bg-gray-50 p-1 shadow-sm">
                
                <input type="text" name="logo" id="ai_logo_image" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($site_logo_ai); ?>" placeholder="https://..." oninput="document.getElementById('ai_logo_preview').src=this.value">
                
                <button type="button" onclick="uploadLogoResim()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-1">
                    <i class="fas fa-upload"></i> Yükle
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">PNG, JPG, WEBP → WebP dönüşümü ile /uploads/ayarlar/ klasörüne kaydedilir</p>
        </div>

        <!-- primaryImageOfPage.caption -->
        <div class="md:col-span-2 bg-gray-50/70 p-4 rounded-xl border border-gray-200">
            <label class="block text-sm font-semibold text-gray-700 mb-1">🖼️ Birincil Sayfa Görsel Başlığı (primaryImageOfPage.caption)</label>
            <input type="text" name="primaryImage_caption" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" value="<?php echo htmlspecialchars($ai_data['primaryImageOfPage']['caption'] ?? 'Prof. Dr. İbrahim Duran - Protetik Diş Tedavisi Uzmanı'); ?>">
        </div>
        
        <!-- HARİTA LİNKİ -->
        <div class="md:col-span-2 bg-indigo-50/40 p-4 rounded-xl border border-indigo-100/70">
            <label class="block text-sm font-bold text-indigo-950 mb-1">🗺️ Şahsi Google Business Harita Linki (hasMap / CID)</label>
            <div class="flex gap-2">
                <input type="url" name="maps_link" id="maps_link_input" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-indigo-900 bg-white" placeholder="https://share.google/VyL821U4qRtoVPCUM" value="<?php echo htmlspecialchars($ai_data['hasMap'] ?? 'https://share.google/VyL821U4qRtoVPCUM'); ?>">
                <?php if (!empty($ai_data['hasMap'])): ?>
                    <a href="<?php echo htmlspecialchars($ai_data['hasMap']); ?>" target="_blank" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition flex items-center justify-center gap-1 font-semibold shadow-sm">
                        <i class="fas fa-map-marked-alt"></i> Test Et
                    </a>
                <?php endif; ?>
            </div>
            <p class="text-xs text-indigo-500 mt-1 font-medium">🎯 Hile Kuruldu: Bu şahsi paylaşım kancası, web sitesi ile harita kaydını siber alemde yapışık ikiz ilan eder reis!</p>
        </div>

        <!-- Şema ID ve Type Drag-Drop (eski haliyle devam) -->
        <div class="md:col-span-2 bg-indigo-50/40 p-5 rounded-2xl border border-indigo-100 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-6 w-full">
            <!-- ... bu kısım aynı kalacak ... -->
        </div>

    </div>
</div>

                <div class="border-b border-gray-100 pb-4">
                    <h4 class="text-sm font-bold text-indigo-600 mb-3 flex items-center gap-1">📍 Harita Konum ve Resmi Koordinatlar</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🏠 Açık Adres</label>
                            <input type="text" name="adres" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['address']['streetAddress'] ?? 'Mimar Sinan Mah. Atatürk Bulvarı Riva İş Merkezi No:260 Kat:1 Daire:2'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🏙️ İlçe</label>
                            <input type="text" name="ilce" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['address']['addressLocality'] ?? 'Atakum'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🏛️ İl</label>
                            <input type="text" name="il" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['address']['addressRegion'] ?? 'Samsun'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🗺️ Posta Kodu</label>
                            <input type="text" name="posta_kodu" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['address']['postalCode'] ?? '55200'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🌍 Ülke Kodu</label>
                            <input type="text" name="ulke" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['address']['addressCountry'] ?? 'TR'); ?>">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="block text-sm font-bold text-red-700 mb-1">📐 GPS Gerçek Enlem (Latitude)</label>
                            <input type="text" name="enlem" class="w-full px-3 py-2 border border-red-300 rounded-lg text-sm font-mono bg-red-50/10 text-red-900" value="<?php echo htmlspecialchars($ai_data['geo']['latitude'] ?? '41.3364323'); ?>">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="block text-sm font-bold text-red-700 mb-1">📏 GPS Gerçek Boylam (Longitude)</label>
                            <input type="text" name="boylam" class="w-full px-3 py-2 border border-red-300 rounded-lg text-sm font-mono bg-red-50/10 text-red-900" value="<?php echo htmlspecialchars($ai_data['geo']['longitude'] ?? '36.2747576'); ?>">
                        </div>
                    </div>
                </div>

                <div class="border-b border-gray-100 pb-4">
                    <h4 class="text-sm font-bold text-indigo-600 mb-3 flex items-center gap-1">🕐 Zamanlama ve Klinik Tanıtımı</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🕐 Hafta İçi Çalışma Saatleri</label>
                            <input type="text" name="hafta_ici" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['openingHours'][0] ?? 'Mo-Fr 09:00-19:00'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📅 Cumartesi Çalışma Saatleri</label>
                            <input type="text" name="cumartesi" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['openingHours'][1] ?? 'Sa 10:00-16:00'); ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📝 Yapay Zeka Tanıtım Özeti (description)</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="AI'ların okuyacağı zengin özet metni..."><?php echo htmlspecialchars($ai_data['description'] ?? 'Prof. Dr. İbrahim Duran, Protetik Diş Hekimliği Uzmanı olarak Samsun ve Atakum\'da lüks kliniğinde hizmet vermektedir...'); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="border-b border-gray-100 pb-4 bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                    <h4 class="text-sm font-bold text-indigo-700 mb-3 flex items-center gap-1">🎓 Akademik Özgeçmiş ve Bilimsel Güç</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🎂 Doğum Yılı</label>
                            <input type="text" name="dogum_yili" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['founder']['birthDate'] ?? '1984'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📍 Doğum Yeri</label>
                            <input type="text" name="dogum_yeri" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['founder']['birthPlace']['name'] ?? 'Samsun'); ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🎓 Mezun Olunan Üniversite</label>
                            <input type="text" name="universite" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['founder']['alumniOf']['name'] ?? 'Gazi Üniversitesi Diş Hekimliği Fakültesi'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📜 Doktora Yılı</label>
                            <input type="text" name="doktora" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['founder']['hasCredential'][0]['dateCreated'] ?? '2011'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📜 Doçentlik Yılı</label>
                            <input type="text" name="docentship" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['founder']['hasCredential'][1]['dateCreated'] ?? '2017'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📜 Profesörlük Yılı</label>
                            <input type="text" name="professor" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['founder']['hasCredential'][2]['dateCreated'] ?? '2025'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📚 Bilimsel Yayın Sayısı</label>
                            <input type="text" name="yayin_sayisi" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="30+" placeholder="30+">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">🔬 Uzmanlık Alanları Sinyalleri</label>
                    <div id="knowsAboutContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mb-2">
                        <?php 
                        $knowsAbout = $ai_data['founder']['knowsAbout'] ?? [];
                        foreach ($knowsAbout as $ka):
                        ?>
                        <div class="knowsAbout-item flex gap-2">
                            <input type="text" name="knowsAbout[]" class="knowsAbout-input flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ka); ?>" autocomplete="off">
                            <button type="button" onclick="this.parentElement.remove()" class="bg-red-500 text-white px-3 py-1 rounded-lg text-xs hover:bg-red-600">Sil</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex gap-2 items-center">
                        <input type="text" id="newKnowsAbout" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Yeni uzmanlık alanı yaz og ekle..." autocomplete="off">
                        <button type="button" onclick="addKnowsAboutFromInput()" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600">+ Ekle</button>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">🦷 Tedaviler (AI'ların önereceği doğrudan hizmetler)</label>
                    <p class="text-xs text-green-600 mb-2">⭐ ÖNEMLİ: "Samsun implant" diye aratana AI seni önersin! Her tedavini ayrı ayrı mühürle.</p>
                    <div id="offersContainer" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <?php 
                        $offers = $ai_data['makesOffer'] ?? [];
                        foreach ($offers as $offer):
                            $item = $offer['itemOffered'] ?? [];
                        ?>
                        <div class="offer-item bg-gray-50 rounded-xl p-3 border border-gray-200">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1 grid grid-cols-2 gap-2">
                                    <input type="text" name="offer_name[]" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold" placeholder="Tedavi Adı (örn: İmplant Tedavisi)" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>">
                                    <input type="text" name="offer_url[]" class="px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Tedavi URL'si (örn: /implant)" value="<?php echo htmlspecialchars($item['url'] ?? ''); ?>">
                                </div>
                                <button type="button" onclick="this.closest('.offer-item').remove()" class="bg-red-500 text-white px-3 py-1 rounded-lg text-xs hover:bg-red-600 ml-2">Sil</button>
                            </div>
                            <textarea name="offer_desc[]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Kısa açıklama (AI'nın anlayacağı şekilde)"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addOfferField()" class="mt-2 text-sm text-indigo-600 hover:text-indigo-700"><i class="fas fa-plus-circle"></i> + Tedavi Ekle</button>
                </div>
                
                <div class="border-b border-gray-100 pb-4">
                    <h4 class="text-sm font-bold text-indigo-600 mb-3 flex items-center gap-1">🔗 Sosyal Medya Otorite Linkleri (sameAs)</h4>
                    <?php
                    $instagram_url = ''; $linkedin_url = ''; $scholar_url = ''; $facebook_url = '';
                    if (isset($ai_data['sameAs']) && is_array($ai_data['sameAs'])) {
                        foreach ($ai_data['sameAs'] as $s_link) {
                            if (strpos($s_link, 'instagram.com') !== false) $instagram_url = $s_link;
                            if (strpos($s_link, 'linkedin.com') !== false) $linkedin_url = $s_link;
                            if (strpos($s_link, 'scholar.google') !== false) $scholar_url = $s_link;
                            if (strpos($s_link, 'facebook.com') !== false) $facebook_url = $s_link;
                        }
                    }
                    ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📸 Instagram URL</label>
                            <input type="url" name="instagram" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($instagram_url); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🔗 LinkedIn URL</label>
                            <input type="url" name="linkedin" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($linkedin_url); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">🎓 Google Scholar URL</label>
                            <input type="url" name="scholar" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($scholar_url); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">📘 Facebook URL</label>
                            <input type="url" name="facebook" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($facebook_url); ?>">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">⭐ Otorite Değerlendirme Puanı (AggregateRating)</label>
                    <div class="grid grid-cols-2 gap-3 max-w-md">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Skor (Puan)</label>
                            <input type="text" name="ratingValue" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['aggregateRating']['ratingValue'] ?? '4.9'); ?>">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Hasta Yorum Sayısı</label>
                            <input type="text" name="reviewCount" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['aggregateRating']['reviewCount'] ?? '537'); ?>">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">🏷️ Yapay Zeka Anahtar Kelimeleri</label>
                        <div class="border border-gray-300 rounded-lg p-2 bg-white min-h-[100px] focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                            <div id="aiKeywordsContainer" class="flex flex-wrap gap-2 mb-2">
                                <?php 
                                $kw_string = $ai_data['keywords'] ?? '';
                                $keywords = !empty($kw_string) ? explode(',', $kw_string) : [];
                                foreach ($keywords as $kw):
                                    $kw = trim($kw);
                                    if (!empty($kw)):
                                ?>
                                <div class="keyword-tag inline-flex items-center gap-1 bg-indigo-100 text-indigo-800 rounded-full px-3 py-1 text-sm">
                                    <span><?php echo htmlspecialchars($kw); ?></span>
                                    <input type="hidden" name="keywords[]" value="<?php echo htmlspecialchars($kw); ?>">
                                    <button type="button" onclick="this.closest('.keyword-tag').remove()" class="text-indigo-500 hover:text-red-600 focus:outline-none ml-1">
                                        <i class="fas fa-times-circle text-xs"></i>
                                    </button>
                                </div>
                                <?php endif; endforeach; ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="text" id="aiKeywordInput" class="flex-1 px-2 py-1 text-sm border-0 focus:ring-0 focus:outline-none" placeholder="Yaz og ekle..." onkeypress="handleAiKeywordKeypress(event)">
                                <button type="button" onclick="addAiKeywordTag()" class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-xs hover:bg-indigo-700 transition">💾 Ekle</button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">📍 Hizmet Verilen Geniş Bölgeler (areaServed)</label>
                        <div class="border border-gray-300 rounded-lg p-2 bg-white min-h-[100px] focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500">
                            <div id="areaServedContainer" class="flex flex-wrap gap-2 mb-2">
                                <?php 
                                $areaServed = $ai_data['areaServed'] ?? [];
                                foreach ($areaServed as $as):
                                    $as_name = is_array($as) ? ($as['name'] ?? '') : $as;
                                    if (!empty($as_name)):
                                ?>
                                <div class="area-tag inline-flex items-center gap-1 bg-purple-100 text-purple-800 rounded-full px-3 py-1 text-sm">
                                    <span><?php echo htmlspecialchars($as_name); ?></span>
                                    <input type="hidden" name="areaServed[]" value="<?php echo htmlspecialchars($as_name); ?>">
                                    <button type="button" onclick="this.closest('.area-tag').remove()" class="text-purple-500 hover:text-red-600 focus:outline-none ml-1">
                                        <i class="fas fa-times-circle text-xs"></i>
                                    </button>
                                </div>
                                <?php endif; endforeach; ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="text" id="areaInput" class="flex-1 px-2 py-1 text-sm border-0 focus:ring-0 focus:outline-none" placeholder="Bölge (Bafra, Çarşamba vb.)" onkeypress="handleAreaKeypress(event)">
                                <button type="button" onclick="addAreaTag()" class="px-3 py-1 bg-purple-600 text-white rounded-lg text-xs hover:bg-purple-700 transition">💾 Ekle</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">🎯 Kurumsal Misyon</label>
                        <textarea name="misyon" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><?php echo htmlspecialchars($ai_data['mission'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">👁️ Kurumsal Vizyon</label>
                        <textarea name="vizyon" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><?php echo htmlspecialchars($ai_data['vision'] ?? ''); ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">💬 AI Slogan Kancası</label>
                        <input type="text" name="slogan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($ai_data['slogan'] ?? 'Sağlıklı gülüşler için ileri teknoloji diş tedavileri'); ?>">
                    </div>
                </div>
                
            </div>
        </div>
    </form>

    <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-5 border border-blue-200">
        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fab fa-chatgpt text-green-600"></i> 🤖 AI'lar SENİ NASIL ÖNERECEK?
        </h3>
        
        <div class="bg-white rounded-xl p-4 mb-4 border border-gray-200 shadow-sm">
            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-200">
                <i class="fas fa-search text-blue-600"></i>
                <span class="font-bold text-gray-700 text-sm">Kullanıcılar şunları aradığında AI SENİ ÖNERECEK (Tıkla ve Promptu Kopyala):</span>
            </div>
            
            <div class="space-y-3">
                <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-green-500 cursor-pointer hover:bg-green-50/50 transition" onclick="copyAndRedirect('Samsun Atakum\'da implant ve estetik diş hekimliği alanında en iyi uzman profesör kimdir?', 'https://chatgpt.com')">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-copy text-green-600 text-sm"></i>
                        <span class="font-mono text-sm font-semibold text-gray-700">"Samsun'da iyi diş hekimi"</span>
                        <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                        <span class="text-xs text-green-600 font-bold">📋 TIKLA & CHATGPT'DE DENE</span>
                    </div>
                    <div class="bg-green-50 rounded p-2 text-xs text-gray-600">
                        🤖 AI cevabı: "Samsun Atakum'da <strong>Prof. Dr. İbrahim Duran</strong> protetik diş tedavisi, implant ve estetik diş hekimliği alanında öne çıkan bir uzmandır..."
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-green-500 cursor-pointer hover:bg-green-50/50 transition" onclick="copyAndRedirect('Atakum\'da güvenilir bir diş kliniği tavsiyesi arıyorum, RivaDent nasıl?', 'https://gemini.google.com')">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-copy text-green-600 text-sm"></i>
                        <span class="font-mono text-sm font-semibold text-gray-700">"Atakum diş kliniği tavsiye"</span>
                        <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                        <span class="text-xs text-blue-600 font-bold">📋 TIKLA & GEMINI'DE DENE</span>
                    </div>
                    <div class="bg-green-50 rounded p-2 text-xs text-gray-600">
                        🤖 AI cevabı: "Atakum'da <strong>RivaDent Diş Kliniği</strong>, Prof. Dr. İbrahim Duran yönetiminde 4.9 yüksek puanla hizmet vermektedir..."
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-green-500 cursor-pointer hover:bg-green-50/50 transition" onclick="copyAndRedirect('Samsun ve Atakum bölgesinde implant üstü protez ve zirkonyum kaplama yapan en iyi protetik diş tedavisi uzmanı kim?', 'https://www.perplexity.ai')">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-copy text-green-600 text-sm"></i>
                        <span class="font-mono text-sm font-semibold text-gray-700">"Samsun implant tedavisi"</span>
                        <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                        <span class="text-xs text-purple-600 font-bold">📋 TIKLA & PERPLEXITY'DE DENE</span>
                    </div>
                    <div class="bg-green-50 rounded p-2 text-xs text-gray-600">
                        🤖 AI cevabı: "Samsun'da implant ve protetik diş tedavisi için 30'dan fazla uluslararası yayını olan <strong>Prof. Dr. İbrahim Duran</strong> referans isimdir..."
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-4 mb-4 border border-gray-200">
            <p class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                <i class="fas fa-chart-line text-green-600"></i> AI'lar SENİ neden önerecek?
            </p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                <div class="bg-green-50 p-2 rounded text-center font-bold text-green-800">⭐ 4.9 Puan<br><span class="text-gray-500 font-normal">(537 yorum)</span></div>
                <div class="bg-green-50 p-2 rounded text-center font-bold text-green-800">🔬 Protetik Uzmanı<br><span class="text-gray-500 font-normal">30+ Uluslararası Yayın</span></div>
                <div class="bg-green-50 p-2 rounded text-center font-bold text-green-800">🎓 Profesörlük<br><span class="text-gray-500 font-normal">Gazi Üni. Mezunu</span></div>
                <div class="bg-green-50 p-2 rounded text-center font-bold text-green-800">📍 Samsun/Atakum<br><span class="text-gray-500 font-normal">Riva İş Merkezi</span></div>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="copyAndRedirect('Samsun Atakum\'da implant ve estetik diş hekimliği alanında en iyi uzman profesör kimdir?', 'https://chatgpt.com')" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700 transition font-semibold shadow-sm">
                <i class="fab fa-chatgpt"></i> ChatGPT'de Dene
            </button>
            <button type="button" onclick="copyAndRedirect('Atakum\'da güvenilir bir diş kliniği tavsiyesi arıyorum, RivaDent nasıl?', 'https://gemini.google.com')" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition font-semibold shadow-sm">
                <i class="fas fa-robot"></i> Gemini'de Dene
            </button>
            <button type="button" onclick="copyAndRedirect('Samsun ve Atakum bölgesinde implant üstü protez ve zirkonyum kaplama yapan en iyi protetik diş tedavisi uzmanı kim?', 'https://www.perplexity.ai')" class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg text-xs hover:bg-purple-700 transition font-semibold shadow-sm">
                <i class="fas fa-search"></i> Perplexity'de Dene
            </button>
            <button type="button" onclick="window.open('https://search.google.com/test/rich-results?url='+encodeURIComponent('<?php echo htmlspecialchars($ai_data['url'] ?? ''); ?>'), '_blank')" class="flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg text-xs hover:bg-gray-700 transition font-semibold shadow-sm">
                <i class="fab fa-google"></i> Google Zengin Sonuç Testi
            </button>
        </div>
        
        <div class="flex flex-wrap gap-2">
        <details class="mt-3">
            <summary class="text-xs text-gray-500 cursor-pointer select-none hover:text-gray-700">📋 JSON-LD Çıktısı (Teknik Akım)</summary>
            <pre class="mt-2 bg-gray-900 text-green-400 p-3 rounded-lg overflow-x-auto text-[10px] max-h-48 font-mono"><?php echo htmlspecialchars(json_encode($ai_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
        </details>
    </div>

    <script>
    // Profil resminden gelen URL'yi anlık olarak primaryImage gizli alanına bağlayan senkronizasyon motoru
    function syncPrimaryImageUrl(val) {
        const hiddenImgInput = document.querySelector('input[name="primaryImage_url"]');
        if(hiddenImgInput) {
            hiddenImgInput.value = val;
        }
    }
    </script>

    <div class="mt-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-5 border border-gray-300">
        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-database text-blue-600"></i> 💾 Yedekleme & Geri Yükleme
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl p-4 border border-gray-200">
                <button type="button" onclick="aiSeoYedekAl()" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-download"></i> Yedek Al (SQL)
                </button>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200">
                <input type="file" id="backupFile" accept=".sql" class="hidden">
                <button type="button" onclick="document.getElementById('backupFile').click()" class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-upload"></i> Dosya Seç ve Yükle
                </button>
            </div>
        </div>
    </div>
</div>            
             </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const liste = document.getElementById("drag_drop_schema_list");
    let dragItem = null;

    if (liste) {
        liste.addEventListener("dragstart", function(e) {
            if (e.target.classList.contains("schema-drag-item")) {
                dragItem = e.target;
                e.target.classList.add("opacity-50");
            }
        });

        liste.addEventListener("dragend", function(e) {
            if (dragItem) {
                dragItem.classList.remove("opacity-50");
                dragItem = null;
                schemaSiralamasiniGuncelle();
            }
        });

        liste.addEventListener("dragover", function(e) {
            e.preventDefault();
            const overItem = e.target.closest(".schema-drag-item");
            if (overItem && overItem !== dragItem) {
                const bounding = overItem.getBoundingClientRect();
                const offset = e.clientX - bounding.left;
                if (offset > bounding.width / 2) {
                    overItem.after(dragItem);
                } else {
                    overItem.before(dragItem);
                }
            }
        });
    }

    schemaSiralamasiniGuncelle(false);
});

function schemaSiralamasiniGuncelle(idGuncelle = true) {
    const items = document.querySelectorAll("#drag_drop_schema_list .schema-drag-item");
    let tipler = [];
    items.forEach(item => {
        tipler.push(item.getAttribute("data-value"));
    });
    
    const hiddenTypeInput = document.getElementById("schema_type_hidden");
    if (hiddenTypeInput) {
        hiddenTypeInput.value = tipler.join(", ");
    }

    if (idGuncelle && tipler.length > 0) {
        const idInput = document.getElementById("schema_id_input");
        if (idInput) {
            let mevcutUrl = idInput.value.split("#")[0];
            let yeniKanca = tipler[0].toLowerCase();
            idInput.value = mevcutUrl + "#" + yeniKanca;
        }
    }
}

function anlikSchemaKaydet() {
    schemaSiralamasiniGuncelle(false);
    
    const schemaId = document.getElementById("schema_id_input").value;
    const schemaTypeRaw = document.getElementById("schema_type_hidden").value;
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    const formData = new FormData();
    formData.append("tab", "ai_seo");
    formData.append("islem", "mikro_schema_kaydet");
    formData.append("csrf_token", csrfToken);
    formData.append("schema_id", schemaId);
    formData.append("schema_type_raw", schemaTypeRaw);

    const anaForm = document.getElementById("ai_seo_form");
    if(anaForm) {
        const inputs = anaForm.querySelectorAll("input[type='text'], input[type='hidden'], input[type='email'], input[type='url'], textarea");
        inputs.forEach(input => {
            if(input.name !== "schema_id" && input.name !== "schema_type_raw" && input.name !== "") {
                formData.append(input.name, input.value);
            }
        });
        
        const knowsAbouts = anaForm.querySelectorAll('input[name="knowsAbout[]"]');
        knowsAbouts.forEach(input => formData.append("knowsAbout[]", input.value));
        
        const areas = anaForm.querySelectorAll('input[name="areaServed[]"]');
        areas.forEach(input => formData.append("areaServed[]", input.value));
    }

    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(response => {
        if(response.ok) {
            alert("⚡ Jet Hızıyla Kaydedildi Reis!\n\nSıralama og @id kancası anında kilitlendi.");
            location.reload(); 
        } else {
            alert("❌ Bir şeyler ters gitti mühendis bey.");
        }
    })
    .catch(error => console.error("Hata:", error));
}
</script>

<script>
function addOfferField() {
    const container = document.getElementById('offersContainer');
    const div = document.createElement('div');
    div.className = 'offer-item bg-gray-50 rounded-xl p-3 border border-gray-200';
    div.innerHTML = `
        <div class="flex justify-between items-start mb-2">
            <div class="flex-1 grid grid-cols-2 gap-2">
                <input type="text" name="offer_name[]" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold" placeholder="Tedavi Adı (örn: İmplant Tedavisi)">
                <input type="text" name="offer_url[]" class="px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Tedavi URL'si (örn: /implant)">
            </div>
            <button type="button" onclick="this.closest('.offer-item').remove()" class="bg-red-500 text-white px-3 py-1 rounded-lg text-xs hover:bg-red-600 ml-2">Sil</button>
        </div>
        <textarea name="offer_desc[]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Kısa açıklama (AI'nın anlayacağı şekilde)"></textarea>
    `;
    container.appendChild(div);
}

function handleAiKeywordKeypress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addAiKeywordTag();
    }
}

function addAiKeywordTag() {
    const input = document.getElementById('aiKeywordInput');
    let value = input.value.trim();
    if (!value) return;
    
    if (value.includes(',')) {
        const parts = value.split(',').map(p => p.trim());
        parts.forEach(part => { if (part) addSingleAiKeyword(part); });
    } else {
        addSingleAiKeyword(value);
    }
    input.value = '';
    input.focus();
}

function addSingleAiKeyword(keyword) {
    const container = document.getElementById('aiKeywordsContainer');
    const existingTags = container.querySelectorAll('.keyword-tag span');
    for (let tag of existingTags) {
        if (tag.innerText.toLowerCase() === keyword.toLowerCase()) return;
    }
    
    const div = document.createElement('div');
    div.className = 'keyword-tag inline-flex items-center gap-1 bg-indigo-100 text-indigo-800 rounded-full px-3 py-1 text-sm';
    div.innerHTML = `
        <span>${escapeHtml(keyword)}</span>
        <input type="hidden" name="keywords[]" value="${escapeHtml(keyword)}">
        <button type="button" onclick="this.closest('.keyword-tag').remove()" class="text-indigo-500 hover:text-red-600 focus:outline-none ml-1">
            <i class="fas fa-times-circle text-xs"></i>
        </button>
    `;
    container.appendChild(div);
}

function handleAreaKeypress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addAreaTag();
    }
}

// areaServed için dinamik etiket ekleme motoru
function addAreaTag() {
    const input = document.getElementById('areaInput');
    let value = input.value.trim();
    if (!value) return;
    
    if (value.includes(',')) {
        const parts = value.split(',').map(p => p.trim());
        parts.forEach(part => { if (part) addSingleArea(part); });
    } else {
        addSingleArea(value);
    }
    input.value = '';
    input.focus();
}

function addSingleArea(area) {
    const container = document.getElementById('areaServedContainer');
    const existingTags = container.querySelectorAll('.area-tag span');
    for (let tag of existingTags) {
        if (tag.innerText.toLowerCase() === area.toLowerCase()) return;
    }
    
    const div = document.createElement('div');
    div.className = 'area-tag inline-flex items-center gap-1 bg-purple-100 text-purple-800 rounded-full px-3 py-1 text-sm';
    div.innerHTML = `
        <span>${escapeHtml(area)}</span>
        <input type="hidden" name="areaServed[]" value="${escapeHtml(area)}">
        <button type="button" onclick="this.closest('.area-tag').remove()" class="text-purple-500 hover:text-red-600 focus:outline-none ml-1">
            <i class="fas fa-times-circle text-xs"></i>
        </button>
    `;
    container.appendChild(div);
}

function addKnowsAboutFromInput() {
    const input = document.getElementById('newKnowsAbout');
    let value = input.value.trim();
    if (!value) return;
    
    const container = document.getElementById('knowsAboutContainer');
    const div = document.createElement('div');
    div.className = 'knowsAbout-item flex gap-2';
    div.innerHTML = `<input type="text" name="knowsAbout[]" class="knowsAbout-input flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm" value="${value.replace(/[&<>]/g, function(m){return m=='&'?'&':m=='<'?'<':'>';})}" autocomplete="off">
                     <button type="button" onclick="this.parentElement.remove()" class="bg-red-500 text-white px-3 py-1 rounded-lg text-xs hover:bg-red-600">Sil</button>`;
    container.appendChild(div);
    input.value = '';
}
</script>
<script>
let currentSayfaId = null;

function openSayfaModal(id = null) {
    currentSayfaId = id;
    if (id) {
        fetch('/site/admin/modules/ayarlar/dinamik_ajax.php?islem=sayfa_getir&id=' + id)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('sayfaBaslik').value = data.data.baslik;
                    document.getElementById('sayfaMenuAdi').value = data.data.menu_adi;
                    document.getElementById('sayfaAktif').checked = data.data.aktif == 1;
                }
            });
    } else {
        document.getElementById('sayfaForm').reset();
        document.getElementById('sayfaAktif').checked = true;
    }
    document.getElementById('sayfaModal').style.display = 'flex';
}

function closeSayfaModal() {
    document.getElementById('sayfaModal').style.display = 'none';
}

function saveSayfa() {
    const baslik = document.getElementById('sayfaBaslik').value.trim();
    const menu_adi = document.getElementById('sayfaMenuAdi').value.trim();
    
    if (!baslik || !menu_adi) {
        alert('Başlık og menü adı zorunludur!');
        return;
    }
    
    const data = {
        id: currentSayfaId,
        baslik: baslik,
        menu_adi: menu_adi,
        aktif: document.getElementById('sayfaAktif').checked ? 1 : 0,
        csrf_token: document.querySelector('input[name="csrf_token"]')?.value || '<?php echo $_SESSION['csrf_token']; ?>'
    };
    
    fetch('/site/admin/modules/ayarlar/dinamik_ajax.php?islem=sayfa_kaydet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ Hata: ' + data.message);
        }
    })
    .catch(err => {
        alert('❌ Bağlantı hatası: ' + err);
    });
}

function sayfalariListele() {
    fetch('/site/admin/modules/ayarlar/dinamik_ajax.php?islem=sayfa_listele')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('dinamikSayfalarListesi');
            if (!container) return;
            
            if (data.success && data.data && data.data.length > 0) {
                let html = '';
                data.data.forEach(sayfa => {
                    html += `
                        <div class="border border-gray-200 rounded-xl p-4 bg-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold">${sayfa.baslik}</h4>
                                    <div class="text-xs text-gray-400">/${sayfa.slug}/</div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="openSayfaModal(${sayfa.id})" class="text-blue-600">✏️</button>
                                    <button onclick="sayfaSil(${sayfa.id})" class="text-red-600">🗑️</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                container.appendChild = html;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-center text-gray-400 py-8">Henüz sayfa yok</div>';
            }
        })
        .catch(err => {
            document.getElementById('dinamikSayfalarListesi').innerHTML = '<div class="text-center text-red-500 py-8">Yüklenirken hata oluştu</div>';
        });
}

function sayfaSil(id) {
    if (!confirm('Bu sayfayı silmek istediğinize emin misiniz?\nSayfa hem menüden hem de veritabanından kalıcı olarak silinecektir.')) return;
    
    fetch('/site/admin/modules/ayarlar/dinamik_ajax.php?islem=sayfa_sil&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ Sayfa silindi!');
                location.reload();
            } else {
                alert('❌ Hata: ' + data.message);
            }
        });
}

if (document.getElementById('dinamikSayfalarListesi')) {
    sayfalariListele();
}
</script>

<div id="sayfaModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 20px; width: 90%; max-width: 500px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px 20px; border-radius: 20px 20px 0 0; color: white;">
            <div style="display: flex; justify-content: space-between;">
                <h4 style="margin: 0;"><i class="fas fa-plus-circle"></i> Yeni Sayfa</h4>
                <button onclick="closeSayfaModal()" style="background: none; border: none; color: white; font-size: 20px;">✖</button>
            </div>
        </div>
        <div style="padding: 20px;">
            <div style="margin-bottom: 15px;">
                <label>Sayfa Başlığı *</label>
                <input type="text" id="sayfaBaslik" class="form-control" placeholder="Örn: Hizmetlerimiz" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Menü Adı *</label>
                <input type="text" id="sayfaMenuAdi" class="form-control" placeholder="Örn: Hizmetlerimiz" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                <small class="text-gray-400">Header2 menüsünde görünecek isim</small>
            </div>
            <div style="margin-bottom: 15px;">
                <label>
                    <input type="checkbox" id="sayfaAktif" checked> Aktif
                </label>
            </div>
        </div>
        <div style="padding: 16px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
            <button onclick="closeSayfaModal()" style="background: #e2e8f0; border: none; padding: 8px 20px; border-radius: 8px;">İptal</button>
            <button onclick="saveSayfa()" style="background: #3b82f6; border: none; padding: 8px 20px; border-radius: 8px; color: white; margin-left: 10px;">Kaydet</button>
        </div>
    </div>
</div>

<script>
function uploadLogo(inputId, imgId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) { console.log("Dosya seçilmedi!"); return; }
        
        console.log("Dosya seçildi:", file.name);
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
        
        fetch('/admin/modules/ayarlar/index.php?islem=resim_yukle', { 
            method: 'POST', 
            body: formData 
        })
        .then(response => {
            console.log("Sunucudan yanıt geldi, durum:", response.status);
            return response.text(); 
        })
        .then(text => {
            console.log("Sunucudan gelen ham veri:", text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    document.getElementById(inputId).value = data.url;
                    document.getElementById(imgId).src = data.url;
                    alert('✅ Yüklendi!');
                } else {
                    alert('❌ Hata: ' + data.message);
                }
            } catch (e) {
                alert('❌ Sunucu hatası! Konsolu kontrol et (F12).');
            }
        })
        .catch(err => {
            console.error("Fetch hatası:", err);
            alert('❌ Bağlantı koptu!');
        });
    };
    input.click();
}

function uploadHekimResim() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
        
        const apiUrl = '/admin/modules/ayarlar/index.php?islem=hekim_resim_yukle';
        
        fetch(apiUrl, { 
            method: 'POST', 
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            console.log('📥 API Yanıtı:', data);
            if (data.success) {
                // 🔥 RELATIVE URL'Yİ DOĞRUDAN KULLAN (absolute yapma)
                // Çünkü header.php zaten absolute yapıyor!
                const relativeUrl = data.url; // /uploads/ayarlar/hekim_xxx.webp
                document.getElementById('ai_doctor_image').value = relativeUrl;
                document.getElementById('ai_doctor_preview').src = relativeUrl;
                
                // ✅ Zaten relative URL, header.php absolute yapacak!
                alert('✅ Doktor resmi yüklendi!\n' + relativeUrl);
            } else {
                alert('❌ Hata: ' + data.message);
            }
        })
        .catch(err => {
            console.error('❌ Hata:', err);
            alert('❌ Bağlantı hatası: ' + err.message);
        });
    };
    input.click();
}
// DOKTOR RESMİ YÜKLEME - DÜZELTİLDİ
function uploadHekimResim() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
        
        const apiUrl = '/admin/modules/ayarlar/index.php?islem=hekim_resim_yukle';
        
        fetch(apiUrl, { 
            method: 'POST', 
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 🔥 RELATIVE URL - header.php absolute yapacak
                const relativeUrl = data.url;
                document.getElementById('ai_doctor_image').value = relativeUrl;
                document.getElementById('ai_doctor_preview').src = relativeUrl;
                alert('✅ Doktor resmi yüklendi!\n' + relativeUrl);
            } else {
                alert('❌ Hata: ' + data.message);
            }
        })
        .catch(err => {
            console.error('❌ Hata:', err);
            alert('❌ Bağlantı hatası: ' + err.message);
        });
    };
    input.click();
}

// LOGO RESMİ YÜKLEME - DÜZELTİLDİ
function uploadLogoResim() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
        
        const apiUrl = '/admin/modules/ayarlar/index.php?islem=logo_resim_yukle';
        
        fetch(apiUrl, { 
            method: 'POST', 
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 🔥 RELATIVE URL - header.php absolute yapacak
                const relativeUrl = data.url;
                document.getElementById('ai_logo_image').value = relativeUrl;
                document.getElementById('ai_logo_preview').src = relativeUrl;
                alert('✅ Logo yüklendi!\n' + relativeUrl);
            } else {
                alert('❌ Hata: ' + data.message);
            }
        })
        .catch(err => {
            console.error('❌ Hata:', err);
            alert('❌ Bağlantı hatası: ' + err.message);
        });
    };
    input.click();
}

// ========== İLETİŞİM FORMU (HATA GÖSTERİMLİ) ==========
const iletisimForm = document.getElementById('iletisimForm');
if (iletisimForm) {
    iletisimForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        const messageDiv = document.getElementById('formMessage');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ GÖNDERİLİYOR...';
        
        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // ✅ Başarılı
                messageDiv.className = 'mb-4 p-4 rounded-xl text-sm font-semibold flex items-center gap-3 shadow-md bg-green-500 text-white';
                messageDiv.innerHTML = '✅ ' + data.message;
                messageDiv.classList.remove('hidden');
                
                // E-posta durumunu göster
                if (data.mail_sent === false && data.mail_error) {
                    console.warn('📧 E-posta gönderilemedi:', data.mail_error);
                    // 🔥 Hata mesajını F12 konsolunda göster
                    console.error('❌ E-posta Hatası:', data.mail_error);
                }
                
                iletisimForm.reset();
                setTimeout(() => {
                    location.reload();
                }, 3000);
            } else {
                // ❌ Hata
                messageDiv.className = 'mb-4 p-4 rounded-xl text-sm font-semibold flex items-center gap-3 shadow-md bg-red-500 text-white';
                messageDiv.innerHTML = '❌ ' + data.message;
                messageDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('❌ Fetch Hatası:', error);
            messageDiv.className = 'mb-4 p-4 rounded-xl text-sm font-semibold flex items-center gap-3 shadow-md bg-red-500 text-white';
            messageDiv.innerHTML = '❌ Bağlantı hatası! Lütfen tekrar deneyin.';
            messageDiv.classList.remove('hidden');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
}
</script>