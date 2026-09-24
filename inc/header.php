<?php
// www/inc/header.php - %100 SEF URL VE DRAG-DROP ŞEMA UYUMLU VERSİYON

// ========== 0. DİL DEĞİŞİMİ VE TEMİZ URL ==========
if (isset($_GET['lang'])) {
    if ($_GET['lang'] === 'en') {
        $_SESSION['dil'] = 'en';
    }
    elseif ($_GET['lang'] === 'tr') {
        $_SESSION['dil'] = 'tr';
        if (strpos($_SERVER['REQUEST_URI'], 'lang=tr') !== false) {
            $clean_path = strtok($_SERVER['REQUEST_URI'], '?');
            if ($clean_path && !headers_sent()) {
                header('Location: ' . $clean_path, true, 301);
                exit();
            }
        }
    }
}
$lang = $_SESSION['dil'];

// ========== 2. DEVAM EDEN KÜRESEL AYARLAR VE VERİTABANI ==========
global $site_baslik, $site_aciklama, $ai_seo_json, $db;
global $header_logo, $header_unvan, $header_uzmanlik, $header_telefon, $header_whatsapp, $header_lokasyon, $header_adres;
global $header2_navlinks;
global $page_title, $seo_description, $site_keywords, $dynamic_og_title, $og_image;

$site_adresi_ana = "https://www.dribrahimdurandentalclinic.com";

// 🔥 TEK FOTOĞRAF KAYNAĞI — tüm schema ve og:image bunu kullanacak
$hekim_fotografi_url = 'https://www.dribrahimdurandentalclinic.com/uploads/kurumsal/kurumsal_1779668918_9117.webp';

// Ayarları tek seferde veritabanından çek
$stmt_ayarlar = $db->query("SELECT ayar_key, ayar_value FROM site_ayarlari");
$ayarlar = $stmt_ayarlar->fetchAll(PDO::FETCH_KEY_PAIR);

// ========== DİL LİNKİ OLUŞTURUCU (TAM SEF URL DESTEKLİ) ==========
function dil_linki_olustur($hedef_dil) {
    global $site_adresi_ana;
    $request_uri = $_SERVER['REQUEST_URI'];
    
    if (preg_match('#^/tedaviler/([^/?]+)#', $request_uri, $matches)) {
        $slug = $matches[1];
        return $site_adresi_ana . '/tedaviler/' . $slug . '?lang=' . $hedef_dil;
    }
    
    $current_params = $_GET;
    $current_params['lang'] = $hedef_dil;
    unset($current_params['slug']);
    unset($current_params['k']);
    unset($current_params['kat']);
    unset($current_params['kat_slug']);
    
    $path = strtok($request_uri, '?');
    $query_string = http_build_query($current_params);
    
    if (!empty($query_string)) {
        return $site_adresi_ana . $path . '?' . $query_string;
    }
    return $site_adresi_ana . $path;
}

// ========== 3. DEFANSİF CANONICAL VE ROBOTS ZIRHI ==========
$mevcut_sayfa_adi = basename($_SERVER['SCRIPT_NAME']);
$mevcut_slug_degeri = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$gecerli_sayfa_durumu = true;
$robots_etiketi_icerigi = '<meta name="robots" content="index, follow">';

$site_url = "https://www.dribrahimdurandentalclinic.com";

$mevcut_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$canonical_url = rtrim($mevcut_url, '/');

if (isset($_GET['lang']) && $_GET['lang'] === 'en') {
    $canonical_url = $canonical_url;
} else {
    $canonical_url = preg_replace('/[?&]lang=[a-z]{2}/', '', $canonical_url);
    $canonical_url = rtrim($canonical_url, '?&');
}

if ($mevcut_sayfa_adi === 'tedavi-detay.php') {
    if (empty($mevcut_slug_degeri)) {
        $gecerli_sayfa_durumu = false;
        $canonical_url = $site_url;
        $robots_etiketi_icerigi = '<meta name="robots" content="noindex, nofollow">';
    } else {
        try {
            $sorgu_aktiflik = $db->prepare("SELECT id FROM tedaviler WHERE slug = :slug AND aktif = 1 AND silindi = 0 LIMIT 1");
            $sorgu_aktiflik->execute([':slug' => $mevcut_slug_degeri]);
            
            if ($sorgu_aktiflik->rowCount() === 0) {
                $gecerli_sayfa_durumu = false;
                $canonical_url = $site_url;
                $robots_etiketi_icerigi = '<meta name="robots" content="noindex, nofollow">';
            } else {
                $gecerli_sayfa_durumu = true;
                $canonical_url = $site_url . '/tedaviler/' . $mevcut_slug_degeri;
                if (isset($_GET['lang']) && $_GET['lang'] === 'en') {
                    $canonical_url .= '?lang=en';
                }
                $robots_etiketi_icerigi = '<meta name="robots" content="index, follow">';
            }
        } catch (PDOException $hata) {
            $gecerli_sayfa_durumu = false;
            $canonical_url = $site_url;
            $robots_etiketi_icerigi = '<meta name="robots" content="noindex, nofollow">';
        }
    }
}

$mevcut_canonical_link = $canonical_url;

// ========== 4. SEO VE META DEĞİŞKENLERİ MOTORU ==========
$default_title = 'Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
$default_description = 'Prof. Dr. İbrahim Duran kliniğinde implant, gülüş tasarımı ve ortodonti hizmetleri.';
$default_keywords = 'diş kliniği, implant, gülüş tasarımı, ortodonti, Samsun diş hekimi';

$raw_site_baslik = isset($ayarlar['site_baslik']) ? t_cevir($ayarlar['site_baslik']) : $default_title;
$raw_site_aciklama = isset($ayarlar['seo_description']) ? t_cevir($ayarlar['seo_description']) : $default_description;
$raw_site_keywords = isset($ayarlar['site_keywords']) ? t_cevir($ayarlar['site_keywords']) : $default_keywords;

$sayfa_title = $page_title ?? $raw_site_baslik;
$sayfa_description = $seo_description ?? $raw_site_aciklama;
$sayfa_keywords = $site_keywords ?? $raw_site_keywords;
$sayfa_og_title = $dynamic_og_title ?? $sayfa_title;

// ========== 5. BAŞLIK FORMATLAMA ==========
function format_title($title, $lang) {
    if (strpos($title, '{') === 0 || strpos($title, '[') === 0) {
        $json_coz = json_decode($title, true);
        if (is_array($json_coz)) {
            $title = $json_coz[$lang] ?? $json_coz['tr'] ?? $title;
        }
    }
    if (strpos($title, '|') !== false) {
        return $title;
    }
    $clean_title = trim($title);
    if (empty($clean_title)) {
        return 'Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
    }
    return $clean_title . ' | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
}

if (isset($disable_site_title_suffix) && $disable_site_title_suffix === true) {
    $page_title = $sayfa_title;
} else {
    $page_title = format_title($sayfa_title, $lang);
}
$dynamic_og_title = format_title($sayfa_og_title, $lang);

$seo_description = $sayfa_description;
$site_keywords = $sayfa_keywords;

// ========== 6. DİĞER META DEĞİŞKENLERİ ==========
$favicon = $ayarlar['site_favicon'] ?? '/favicon.ico';
$google_analytics_id = $ayarlar['google_analytics'] ?? '';

// ========== 6.5. OG IMAGE - 🔥 TEK FOTOĞRAFA KİLİTLENDİ ==========
$site_url = "https://www.dribrahimdurandentalclinic.com";

// Eski/geçersiz görsel URL'leri (hepsi çöpe)
$gecersiz_gorsel_patternleri = [
    '/ibrahimduran.png',
    'hekim_1788080090',
    'tedavi_1786124315_6944',
    'galeri-og'
];

$og_image_gecerli = false;
if (!empty($og_image) && $og_image !== '/' && $og_image !== '') {
    $gecersiz_mi = false;
    foreach ($gecersiz_gorsel_patternleri as $pattern) {
        if (strpos($og_image, $pattern) !== false) {
            $gecersiz_mi = true;
            break;
        }
    }
    if (!$gecersiz_mi) {
        $og_image_gecerli = true;
        if (strpos($og_image, 'http') !== 0) {
            $og_image = $site_url . '/' . ltrim($og_image, '/');
        }
    }
}

// Sayfa özel görsel vermediyse veya geçersizse → hekim fotoğrafı
if (!$og_image_gecerli) {
    $og_image = $hekim_fotografi_url;
}

// ========== 7. HEADER GÖRSEL VE NAVLINK AYARLARI ==========
$header_logo = $ayarlar['header1_logo'] ?? '';
$header_unvan = isset($ayarlar['header1_unvan']) ? t_cevir($ayarlar['header1_unvan']) : 'Prof. Dr. İbrahim Duran';
$header_uzmanlik = isset($ayarlar['header1_uzmanlik']) ? t_cevir($ayarlar['header1_uzmanlik']) : 'Estetik Diş Hekimi';
$header_lokasyon = isset($ayarlar['header1_lokasyon']) ? t_cevir($ayarlar['header1_lokasyon']) : 'Atakum, Samsun';
$header_telefon = $ayarlar['header1_telefon'] ?? '';
$header_whatsapp = $ayarlar['header1_whatsapp'] ?? '';
$header_adres = $header_adres ?? '';

if (empty($header_logo)) {
    $header_logo = 'https://w7.pngwing.com/pngs/667/263/png-transparent-dentistry-human-tooth-periodontology-health-blue-heart-logo-thumbnail.png';
}

$header2_navlinks = json_decode($ayarlar['header2_navlinks'] ?? '', true);
if (!is_array($header2_navlinks)) {
    $header2_navlinks = [];
}

$temiz_navlinks = [];
foreach ($header2_navlinks as $item) {
    $name = trim($item['name'] ?? '');
    $link = trim($item['link'] ?? '');
    
    if (empty($name)) continue;
    
    if (empty($link) || $link === '/') {
        $link = SITE_PATH . '/';
    } elseif (strpos($link, 'http') !== 0) {
        $link = SITE_PATH . '/' . ltrim($link, '/');
    }
    $link = str_replace(['//', 'http:/', 'https:/'], ['/', 'http://', 'https://'], $link);
    
    $temiz_navlinks[] = ['name' => t_cevir($name), 'link' => $link];
}
$header2_navlinks = $temiz_navlinks;

$dinamik_sayfalar = $db->query("SELECT baslik, menu_adi, slug FROM dinamik_sayfalar WHERE aktif = 1 ORDER BY sira ASC")->fetchAll();
$mevcut_isimler = array_column($header2_navlinks, 'name');
$sabit_isimler = ['Ana Sayfa', 'Kurumsal', 'Tedavilerimiz', 'Teknolojiler', 'Galeri', 'Blog', 'İletişim'];
$sabit_isimler_en = ['Home', 'Corporate', 'Treatments', 'Technologies', 'Gallery', 'Blog', 'Contact'];

foreach ($dinamik_sayfalar as $ds) {
    $ds_name = t_cevir($ds['menu_adi']);
    if (in_array($ds_name, $sabit_isimler) || in_array($ds_name, $sabit_isimler_en)) {
        continue;
    }
    if (!in_array($ds_name, $mevcut_isimler)) {
        $header2_navlinks[] = [
            'name' => $ds_name, 
            'link' => SITE_PATH . '/' . ltrim($ds['slug'], '/')
        ];
    }
}

$benzersiz_navlinks = [];
foreach ($header2_navlinks as $item) {
    $benzersiz_navlinks[$item['name']] = $item;
}
$header2_navlinks = array_values($benzersiz_navlinks);

$populer_aramalar = json_decode($ayarlar['header2_populer_aramalar'] ?? '', true);
if (!is_array($populer_aramalar) || empty($populer_aramalar)) {
    $populer_aramalar = ['İmplant', 'Zirkonyum', 'Gülüş Tasarımı', 'Fiyatlar', 'İletişim'];
}

// ========== 8. MEGA MENÜ İÇİN TEDAVİLER KATEGORİZASYONU ==========
$mega_menu_kategoriler = [];
$stmt = $db->query("SELECT DISTINCT kategori FROM tedaviler WHERE silindi = 0 AND aktif = 1 ORDER BY kategori");
$kategoriler = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach($kategoriler as $kategori_adi) {
    $kat_title = $kategori_adi;
    $kategori_json = json_decode($kategori_adi, true);
    if (is_array($kategori_json)) {
        $kat_title = $kategori_json[$lang] ?? $kategori_json['tr'] ?? $kategori_adi;
    } else {
        $kat_title = t_cevir($kategori_adi);
    }

    $stmt2 = $db->prepare("SELECT baslik, slug FROM tedaviler WHERE kategori = ? AND silindi = 0 AND aktif = 1 ORDER BY sira ASC");
    $stmt2->execute([$kategori_adi]);
    $tedaviler_list = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($tedaviler_list as &$t_item) {
        $baslik_json = json_decode($t_item['baslik'], true);
        if (is_array($baslik_json)) {
            $ham_baslik = $baslik_json[$lang] ?? $baslik_json['tr'] ?? $t_item['baslik'];
        } else {
            $ham_baslik = $t_item['baslik'];
        }
        $t_item['baslik'] = t_cevir($ham_baslik);
    }
    unset($t_item); 
    
    $mega_menu_kategoriler[] = [
        'title' => $kat_title, 
        'items' => $tedaviler_list
    ];
}

// ========== 9. AI SEO JSON-LD OTOMASYONU ==========
$ai_seo_json = null;
$stmt_ai = $db->prepare("SELECT deger FROM ayarlar WHERE anahtar = 'ai_seo'");
$stmt_ai->execute();
$ai_seo_deger = $stmt_ai->fetchColumn();
if ($ai_seo_deger) {
    $ai_seo_json = json_decode($ai_seo_deger, true);
    
    if (is_array($ai_seo_json)) {
        // 🌐 ÇEVİRİLER
        array_walk_recursive($ai_seo_json, function(&$deger, $anahtar) use ($lang) {
            if (is_string($deger)) {
                if (trim($deger) === 'Çarşamba' || trim($deger) === 'çarşamba') {
                    $deger = ($lang === 'en') ? 'Carsamba' : 'Çarşamba';
                    return;
                }
                if (strtolower(trim($deger)) === 'dentist') {
                    $deger = 'Dentist';
                    return;
                }
                if ($anahtar === '@type' || $anahtar === '@id') {
                    return; 
                }
                if (strpos($deger, 'http') !== 0 && $anahtar !== '@type' && $anahtar !== '@context') {
                    $deger = t_cevir($deger);
                    if ($anahtar === 'name' || $anahtar === 'keywords') {
                        if ($lang === 'en') {
                            $deger = ucwords(str_replace(['ı', 'i̇'], ['I', 'I'], mb_strtolower($deger, 'UTF-8')));
                        } else {
                            $deger = mb_convert_case($deger, MB_CASE_TITLE, "UTF-8");
                        }
                    }
                }
            }
        });
        
        // 🔥 TÜM GÖRSEL URL'LERİNİ TEK FOTOĞRAFA SABİTLE
        array_walk_recursive($ai_seo_json, function(&$deger, $anahtar) use ($hekim_fotografi_url) {
            if (!is_string($deger)) return;
            if (strpos($deger, 'hekim_1788080090') !== false 
                || strpos($deger, 'tedavi_1786124315_6944') !== false 
                || strpos($deger, 'ibrahimduran.png') !== false) {
                $deger = $hekim_fotografi_url;
            }
        });
    }
}

// ========== 10. TELEFON TEMİZLEME ==========
$telefon_clear = preg_replace('/[^0-9]/', '', $header_telefon ?: '905052232343');
$whatsapp_clear = preg_replace('/[^0-9]/', '', $header_whatsapp ?: '905052232343');
if(substr($whatsapp_clear, 0, 1) == '0') $whatsapp_clear = '90' . substr($whatsapp_clear, 1);

// ========== 11. SABİT DEĞERLER ==========
$site_baslik = $raw_site_baslik;
$page_description = $seo_description;
$site_adresi_ana = "https://www.dribrahimdurandentalclinic.com";

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

<?php 
    if (!empty($robots_etiketi_icerigi)) {
        echo '    ' . $robots_etiketi_icerigi . "\n";
    } else {
        echo '    <meta name="robots" content="index, follow">' . "\n";
    }
    
    echo '    <link rel="canonical" href="' . htmlspecialchars($mevcut_canonical_link) . '" />' . "\n";
    
if ($gecerli_sayfa_durumu) {
    $hreflang_base = preg_replace('/[?&]lang=[a-z]{2}/', '', $mevcut_canonical_link);
    $hreflang_base = rtrim($hreflang_base, '?&');
    $turkce_alternatif = $hreflang_base; 
    $ingilizce_alternatif = $hreflang_base . (strpos($hreflang_base, '?') !== false ? '&' : '?') . 'lang=en';
    
    echo '    <link rel="alternate" hreflang="tr" href="' . htmlspecialchars($turkce_alternatif) . '" />' . "\n";
    echo '    <link rel="alternate" hreflang="en" href="' . htmlspecialchars($ingilizce_alternatif) . '" />' . "\n";
    echo '    <link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($hreflang_base) . '" />' . "\n";
}
    ?>
    
    <?php
    $css_file = $_SERVER['DOCUMENT_ROOT'] . '/assets/chunks/b3deedb6e72ce3d2.css';
    if (file_exists($css_file)): ?>
    <link rel="preload" href="/assets/chunks/b3deedb6e72ce3d2.css" as="style">
    <link rel="stylesheet" href="/assets/chunks/b3deedb6e72ce3d2.css">
    <?php endif; ?>
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($site_keywords); ?>">
    <meta name="author" content="Prof. Dr. İbrahim Duran">
    
<link rel="icon" type="image/png" href="<?php echo htmlspecialchars($favicon); ?>?v=<?php echo time(); ?>">
    <meta http-equiv="Content-Language" content="<?php echo $lang; ?>">
<link rel="preconnect" href="/api/">
<link rel="dns-prefetch" href="/api/">
    <?php if($ai_seo_json && is_array($ai_seo_json)): ?>
    <script type="application/ld+json" data-ai-seo="true">
    <?php echo json_encode($ai_seo_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php endif; ?>
    
    <?php echo csrf_meta_tag(); ?>
    
<?php if (!empty($google_analytics_id) && $google_analytics_id !== 'G-XXXXXXXXXX'): ?>
<script>
window.loadAnalytics = function() {
    if (window.__gaLoaded) return;
    window.__gaLoaded = true;
    
    const GA_ID = "<?php echo htmlspecialchars($google_analytics_id); ?>";
    window.dataLayer = window.dataLayer || [];
    function gtag(){ window.dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', GA_ID, {
        send_page_view: true,
        anonymize_ip: true,
        cookie_flags: 'SameSite=None;Secure'
    });
    
    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
    script.crossOrigin = 'anonymous';
    document.head.appendChild(script);
};

document.addEventListener('DOMContentLoaded', function() {
    const consent = document.cookie.match(/cerez_onay_v1=([^;]+)/);
    if (consent && consent[1] === 'accepted') {
        window.loadAnalytics();
    }
});
</script>
<?php endif; ?>

    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Sen:wght@400;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sen:wght@400;700&display=swap"></noscript>

    <!-- ========== 🔥 OG META ETİKETLERİ - TEK FOTOĞRAF ========== -->
    <meta property="og:title" content="<?php echo htmlspecialchars($dynamic_og_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($og_image); ?>">
    <meta property="og:image:type" content="image/webp">
    <meta property="og:image:width" content="800">
    <meta property="og:image:height" content="800">
    <meta property="og:image:alt" content="Prof. Dr. İbrahim Duran - Protetik Diş Tedavisi ve İmplant Uzmanı">
    <link rel="image_src" href="<?php echo htmlspecialchars($og_image); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($mevcut_canonical_link); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Prof. Dr. İbrahim Duran">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($dynamic_og_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">
	
	    <?php 
    if (!empty($sayfa_schemalari)) {
        echo "\n    " . $sayfa_schemalari;
    }
    ?>
    <!-- ========== OG META ETİKETLERİ SONU ========== -->
</head>
<style>
/* ========== ÇEREZ ONAY BANNER ========== */
#cookieConsent {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    max-width: 480px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #e2e8f0;
    border-radius: 20px;
    padding: 22px 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(6, 182, 212, 0.15);
    z-index: 99998;
    font-family: 'Sen', -apple-system, sans-serif;
    transform: translateY(150%);
    opacity: 0;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease;
    pointer-events: none;
}
#cookieConsent.show {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
}
#cookieConsent .cc-icon {
    width: 44px;
    height: 44px;
    background: rgba(6, 182, 212, 0.12);
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    color: #06b6d4;
}
#cookieConsent .cc-title {
    font-size: 15px;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
#cookieConsent .cc-text {
    font-size: 12.5px;
    line-height: 1.6;
    color: #94a3b8;
    margin-bottom: 18px;
}
#cookieConsent .cc-text a {
    color: #06b6d4;
    text-decoration: underline;
    font-weight: 600;
}
#cookieConsent .cc-text a:hover {
    color: #22d3ee;
}
#cookieConsent .cc-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
#cookieConsent .cc-btn {
    padding: 11px 20px;
    border-radius: 12px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    letter-spacing: 0.3px;
    font-family: inherit;
    flex: 1;
    min-width: 120px;
}
#cookieConsent .cc-btn-accept {
    background: linear-gradient(95deg, #06b6d4, #0891b2);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(6, 182, 212, 0.3);
}
#cookieConsent .cc-btn-accept:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(6, 182, 212, 0.45);
}
#cookieConsent .cc-btn-reject {
    background: rgba(255, 255, 255, 0.06);
    color: #cbd5e1;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
#cookieConsent .cc-btn-reject:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
}
#cookieConsent .cc-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
#cookieConsent .cc-close:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}
@media (max-width: 480px) {
    #cookieConsent {
        bottom: 12px;
        left: 12px;
        right: 12px;
        padding: 18px 18px;
        border-radius: 16px;
    }
    #cookieConsent .cc-btn { min-width: auto; }
    #cookieConsent .cc-actions { flex-direction: column; }
}

[aria-hidden="true"] a, 
[aria-hidden="true"] button {
    pointer-events: none; 
    visibility: hidden;   
}
.mega-menu-container {
    position: fixed;
    left: 0;
    width: 100%;
    background: rgb(17 31 63 / 98%);;
    backdrop-filter: blur(24px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
    z-index: 999;
    transition: all 0.25s ease;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-15px);
}
.mega-menu-container.open {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
.mega-menu-inner {
    max-width: 1600px;
    margin: 0 auto;
    padding: 0 40px;
}
.mega-menu-layout {
    display: flex;
    gap: 48px;
    padding: 48px 0;
    min-height: 480px;
}
.mega-menu-categories {
    width: 375px;
    flex-shrink: 0;
}
.mega-menu-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.mega-menu-badge {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 3px;
    color: #64748b;
    text-transform: uppercase;
}
.mega-menu-count {
    font-size: 11px;
    padding: 4px 10px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 30px;
    color: #94a3b8;
    font-weight: 600;
}
.mega-menu-cat-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.mega-cat-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 16px 20px;
    background: transparent;
    border: none;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.25s ease;
    color: #94a3b8;
}
.mega-cat-btn:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #ffffff;
    transform: translateX(6px);
}
.mega-cat-btn.active {
    background: linear-gradient(90deg, rgba(6, 182, 212, 0.12) 0%, transparent 100%);
    border-left: 3px solid #06b6d4;
    color: #06b6d4;
}
.mega-cat-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
}
.mega-cat-icon svg {
    width: 20px;
    height: 20px;
}
.mega-cat-name {
    flex: 1;
    text-align: left;
    font-size: 15px;
    font-weight: 600;
    margin-left: 16px;
}
.mega-cat-arrow svg {
    width: 16px;
    height: 16px;
    opacity: 0;
    transition: all 0.25s ease;
}
.mega-cat-btn:hover .mega-cat-arrow svg {
    opacity: 1;
    transform: translateX(5px);
}
.mega-cat-btn.active .mega-cat-arrow svg {
    opacity: 1;
    color: #06b6d4;
}
.mega-menu-tedaviler {
    flex: 1;
}
.mega-menu-tedavi-header {
    display: flex;
    align-items: baseline;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.mega-badge-cyan {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 3px;
    color: #06b6d4;
    text-transform: uppercase;
    background: rgba(6, 182, 212, 0.1);
    padding: 4px 12px;
    border-radius: 30px;
}
.mega-menu-active-title {
    font-size: 13px;
    font-weight: 500;
    color: #cbd5e1;
}
.mega-menu-tedavi-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    max-height: 380px;
    overflow-y: auto;
    padding-right: 8px;
}
.mega-tedavi-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 14px;
    text-decoration: none;
    transition: all 0.25s ease;
    color: #cbd5e1;
    border: 1px solid rgba(255, 255, 255, 0.05);
}
.mega-tedavi-item:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(6, 182, 212, 0.3);
    transform: translateX(6px);
}
.mega-tedavi-dot {
    width: 8px;
    height: 8px;
    background: #475569;
    border-radius: 50%;
    transition: all 0.25s ease;
}
.mega-tedavi-item:hover .mega-tedavi-dot {
    background: #06b6d4;
    transform: scale(1.6);
    box-shadow: 0 0 8px rgba(6, 182, 212, 0.5);
}
.mega-tedavi-name {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: -0.2px;
}
.mega-tedavi-arrow {
    width: 16px;
    height: 16px;
    opacity: 0;
    transition: all 0.25s ease;
    color: #06b6d4;
}
.mega-tedavi-item:hover .mega-tedavi-arrow {
    opacity: 1;
    transform: translateX(5px);
}
.mega-menu-tedavi-list::-webkit-scrollbar {
    width: 4px;
}
.mega-menu-tedavi-list::-webkit-scrollbar-track {
    background: #1e293b;
    border-radius: 10px;
}
.mega-cat-btn.active .mega-cat-arrow svg {
    opacity: 1; 
    color: #06b6d4;
}
.mega-menu-tedavi-list::-webkit-scrollbar-thumb {
    background: #06b6d4;
    border-radius: 10px;
}
.mega-menu-cta {
    width: 320px;
    flex-shrink: 0;
}
.mega-cta-card {
    position: relative;
    background: linear-gradient(145deg, #0f172a 0%, #0a0f1a 100%);
    border-radius: 28px;
    padding: 32px;
    overflow: hidden;
    border: 1px solid rgba(6, 182, 212, 0.2);
    transition: all 0.35s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.mega-cta-card:hover {
    transform: translateY(-6px);
    border-color: rgba(6, 182, 212, 0.6);
    box-shadow: 0 25px 45px -12px rgba(6, 182, 212, 0.25);
}
.mega-cta-glow {
    position: absolute;
    top: -20%;
    right: -20%;
    width: 180px;
    height: 180px;
    background: radial-gradient(circle, rgba(6, 182, 212, 0.25) 0%, transparent 70%);
    border-radius: 50%;
    transition: all 0.5s ease;
}
.mega-cta-card:hover .mega-cta-glow {
    transform: scale(1.4);
    opacity: 1.2;
}
.mega-cta-icon {
    width: 56px;
    height: 56px;
    background: rgba(6, 182, 212, 0.12);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    border: 1px solid rgba(6, 182, 212, 0.3);
}
.mega-cta-icon svg {
    width: 28px;
    height: 28px;
    color: #06b6d4;
}
.mega-cta-title {
    font-size: 20px;
    font-weight: 800;
    color: white;
    margin-bottom: 16px;
    line-height: 1.35;
}
.mega-cta-text {
    font-size: 13px;
    color: #94a3b8;
    line-height: 1.6;
    margin-bottom: 28px;
}
.mega-cta-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 24px;
    background: linear-gradient(95deg, #06b6d4, #0891b2);
    border: none;
    border-radius: 40px;
    color: white;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    text-decoration: none;
    transition: all 0.3s ease;
    width: fit-content;
}
.mega-cta-btn svg {
    width: 16px;
    height: 16px;
}
.mega-cta-btn:hover {
    transform: scale(1.03);
    gap: 14px;
    box-shadow: 0 10px 25px -8px rgba(6, 182, 212, 0.6);
}
@media (max-width: 1300px) {
    .mega-menu-cta { width: 280px; }
    .mega-menu-categories { width: 280px; }
    .mega-menu-tedavi-list { grid-template-columns: 1fr; }
}
@media (max-width: 1100px) {
    .mega-menu-cta { display: none; }
    .mega-menu-tedavi-list { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .mega-menu-layout { flex-direction: column; gap: 24px; }
    .mega-menu-categories { width: 100%; }
    .mega-menu-tedavi-list { grid-template-columns: 1fr; }
}
.lcp-container {
    min-height: 400px; 
    display: block;
    width: 100%;
}
</style>
<body class="bg-white text-gray-900 m-0 p-0 overflow-x-hidden">
<!-- ========== ÇEREZ ONAY BANNER ========== -->
<div id="cookieConsent" role="dialog" aria-labelledby="cc-title" aria-describedby="cc-text">
<button type="button" class="cc-close" aria-label="Kapat ve reddet" title="Kapat (reddet)" onclick="cerezReddet()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="cc-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <circle cx="8" cy="10" r="1" fill="currentColor"/>
            <circle cx="15" cy="9" r="1" fill="currentColor"/>
            <circle cx="10" cy="15" r="1" fill="currentColor"/>
            <circle cx="16" cy="14" r="1" fill="currentColor"/>
        </svg>
    </div>
    <div id="cc-title" class="cc-title">
        <?php echo t_cevir('Çerez Kullanımı'); ?>
    </div>
    <div id="cc-text" class="cc-text">
        <?php echo t_cevir('Sitemizde deneyiminizi iyileştirmek, site trafiğini analiz etmek ve içerikleri kişiselleştirmek amacıyla çerezler kullanıyoruz. Detaylı bilgi için'); ?>
        <a href="<?php echo SITE_PATH; ?>/kvkk/"><?php echo t_cevir('Çerez Politikamızı'); ?></a>
        <?php echo t_cevir('inceleyebilirsiniz.'); ?>
    </div>
    <div class="cc-actions">
        <button type="button" class="cc-btn cc-btn-accept" onclick="cerezKabul()">
            <?php echo t_cevir('Tümünü Kabul Et'); ?>
        </button>
        <button type="button" class="cc-btn cc-btn-reject" onclick="cerezReddet()">
            <?php echo t_cevir('Tümünü Reddet'); ?>
        </button>
    </div>
</div>
<!-- ========== ÇEREZ ONAY BANNER SONU ========== -->

<!-- ========== SEO TARİH - GİZLİ ========== -->
<div style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;" aria-hidden="true">
    <span itemprop="datePublished"><?php echo date('Y-m-d'); ?></span>
    <span itemprop="dateModified"><?php echo date('Y-m-d'); ?></span>
    <span itemprop="author" itemscope itemtype="https://schema.org/Person">
        <span itemprop="name">Prof. Dr. İbrahim Duran</span>
    </span>
    <meta itemprop="publisher" content="Prof. Dr. İbrahim Duran Diş Kliniği">
    <time datetime="<?php echo date('Y-m-d'); ?>"><?php echo date('d.m.Y'); ?></time>
    <span class="seo-date"><?php echo date('F d, Y'); ?></span>
</div>
<!-- ========== SEO TARİH SONU ========== -->

<header class="w-full relative z-[100]">
    <div class="w-full bg-white relative z-[60] border-b border-slate-50">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12 h-20 lg:h-24 flex items-center justify-between">
            <a class="flex items-center gap-2 sm:gap-3 lg:gap-4 group no-underline min-w-0" href="<?php echo SITE_PATH; ?>/">
<div class="relative w-10 h-10 sm:w-12 sm:h-12 lg:w-16 lg:h-16 flex-shrink-0">
    <div class="absolute inset-0 bg-cyan-50 rounded-full scale-90 group-hover:scale-100 transition-transform duration-300"></div>
    <img src="<?php echo htmlspecialchars($header_logo); ?>" 
         alt="Duran Dental Logo" 
         class="relative w-full h-full object-contain p-1 drop-shadow-sm"
         width="64" 
         height="64"
         style="aspect-ratio: 1/1;">
</div>
                <div class="flex flex-col justify-center min-w-0">
                    <span class="text-[8px] lg:text-[10px] font-bold text-cyan-600 tracking-[0.2em] uppercase leading-none mb-1 truncate"><?php echo htmlspecialchars($header_uzmanlik); ?></span>
                    <div class="flex items-baseline leading-none whitespace-nowrap overflow-hidden">
                        <span class="text-[13px] sm:text-base lg:text-2xl font-black text-slate-800 tracking-tight group-hover:text-cyan-600 transition-colors"><?php echo htmlspecialchars($header_unvan); ?></span>
                    </div>
                </div>
            </a>
            <div class="hidden lg:flex items-center gap-6 xl:gap-8">
                <div class="hidden xl:flex items-center gap-2 opacity-60 hover:opacity-100 transition-opacity">
                    <div class="bg-slate-50 p-2 rounded-full text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    </div>
                    <a href="<?php echo SITE_PATH; ?>/iletisim/#harita" class="flex flex-col group/location hover:opacity-80 transition-opacity">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400"><?php echo t_cevir('Lokasyon'); ?></span>
                        <span class="text-xs font-bold text-slate-700 group-hover/location:text-blue-600 transition-colors"><?php echo htmlspecialchars($header_lokasyon); ?></span>
                    </a>
                </div>
                <div class="hidden xl:block h-8 w-[1px] bg-slate-200"></div>
                <div class="flex items-center gap-6 pl-2">
                    <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $header_telefon ?: '905052232343'); ?>" class="flex items-center gap-3 group/phone">
                        <div class="relative flex-shrink-0">
                            <div class="absolute inset-0 bg-blue-400 rounded-full blur opacity-20 animate-pulse"></div>
                            <div class="relative bg-[#007aff] w-10 h-10 rounded-full flex items-center justify-center text-white shadow-md group-hover/phone:scale-105 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone fill-white"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path></svg>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5"><?php echo t_cevir('İletişim Hattı'); ?></p>
                            <p class="text-base font-black text-slate-800 tracking-tight whitespace-nowrap"><?php echo htmlspecialchars($header_telefon ?: '+90 505 223 23 43'); ?></p>
                        </div>
                    </a>
                    <a href="https://wa.me/<?php echo $whatsapp_clear; ?>" target="_blank" class="flex items-center gap-3 group/wa">
                        <div class="w-10 h-10 bg-[#25D366] rounded-full flex items-center justify-center text-white shadow-md shadow-green-100 group-hover/wa:scale-105 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="white" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle text-white"><path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"></path></svg>
                        </div>
                        <div class="flex flex-col">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5"><?php echo t_cevir('Hızlı Destek'); ?></p>
                            <p class="text-base font-black text-slate-800 tracking-tight whitespace-nowrap">WhatsApp</p>
                        </div>
                    </a>
                </div>
                <div class="h-8 w-[1px] bg-slate-200"></div>
<div class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-xl border border-slate-100">
    <a href="<?php echo dil_linki_olustur('tr'); ?>" class="px-2.5 py-1.5 rounded-lg text-xs font-black transition-all no-underline <?php echo ($lang === 'tr') ? 'bg-white text-cyan-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'; ?>">TR</a>
    <div class="w-[1px] h-3 bg-slate-200"></div>
    <a href="<?php echo dil_linki_olustur('en'); ?>" class="px-2.5 py-1.5 rounded-lg text-xs font-black transition-all no-underline <?php echo ($lang === 'en') ? 'bg-white text-cyan-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'; ?>">EN</a>
</div>
            </div>
            <div class="flex lg:hidden items-center gap-1.5 sm:gap-2 z-[70] flex-shrink-0 ml-2">
                <a class="flex items-center justify-center gap-1.5 px-3 py-2 bg-[#007aff] hover:bg-[#0062cc] text-white rounded-xl active:scale-95 transition-all shadow-[0_4px_12px_rgba(0,122,255,0.3)]" href="<?php echo SITE_PATH; ?>/iletisim/">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg>
                    <span class="text-[10px] sm:text-[11px] font-black tracking-wide whitespace-nowrap"><?php echo t_cevir('İLETİŞİM'); ?></span>
                </a>
<button 
    class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" 
    id="mobileMenuBtn"
    aria-label="Mobil Menüyü Aç"
    aria-expanded="false">
    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path>
    </svg>
</button>
            </div>
        </div>
    </div>
    
    <div class="fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300 lg:hidden opacity-0 invisible pointer-events-none" id="mobileOverlay"></div>
    <div class="fixed top-0 right-0 h-full w-[85%] max-w-[340px] z-[110] bg-[#1b202c] shadow-[-10px_0_30px_rgba(0,0,0,0.5)] flex flex-col transform transition-all duration-400 ease-[cubic-bezier(0.4,0,0.2,1)] lg:hidden translate-x-[120%] opacity-0" id="mobileMenuPanel">
        <div class="p-6 pb-4 flex items-center justify-between border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-[#262c3a] p-1 flex items-center justify-center border border-white/5">
                    <img src="<?php echo htmlspecialchars($header_logo); ?>" class="w-full h-full object-contain drop-shadow-md" alt="Logo">
                </div>
                <div class="flex flex-col">
                    <span class="text-white font-bold text-lg tracking-tight leading-none mb-1.5"><?php echo htmlspecialchars($header_unvan); ?></span>
                    <span class="text-[#00e1ff] text-[10px] font-black tracking-widest uppercase leading-none"><?php echo t_cevir('KLİNİK MENÜ'); ?></span>
                </div>
            </div>
            <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#262c3a] text-slate-400 hover:text-white hover:bg-red-500/20 border border-white/5 transition-all" id="closeMobileMenu" aria-label="Mobil Menüyü Aç">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </button>
        </div>
        <div class="px-6 py-5 border-b border-white/5">
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search text-slate-500"><path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle></svg>
                </div>
                <input type="text" placeholder="<?php echo t_cevir('Tedavi veya bilgi ara...'); ?>" class="w-full bg-[#262c3a] text-white text-[13px] font-medium rounded-xl pl-11 pr-4 py-3.5 outline-none border border-transparent focus:border-[#00e1ff]/40 focus:bg-[#2d3446] transition-all duration-300 placeholder-slate-500 shadow-inner">
            </div>
        </div>
        <nav class="flex-1 overflow-y-auto">
            <a class="flex items-center justify-between px-6 py-4 transition-all group border-b border-white/5 last:border-none border-l-4 border-l-transparent text-slate-300 hover:bg-[#262c3a] hover:text-white" href="<?php echo SITE_PATH; ?>/">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-cyan-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-7H9v7H5a2 2 0 0 1-2-2z"/></svg>
                    <span class="text-[15px] font-bold tracking-wide"><?php echo t_cevir('Anasayfa'); ?></span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
            <a class="flex items-center justify-between px-6 py-4 transition-all group border-b border-white/5 last:border-none border-l-4 border-l-transparent text-slate-300 hover:bg-[#262c3a] hover:text-white" href="<?php echo SITE_PATH; ?>/kurumsal/">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-cyan-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg>
                    <span class="text-[15px] font-bold tracking-wide"><?php echo t_cevir('Kurumsal'); ?></span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
            <div class="mobile-dropdown border-b border-white/5">
                <button class="dropdown-trigger flex items-center justify-between w-full px-6 py-4 transition-all group text-slate-300 hover:bg-[#262c3a] hover:text-cyan-400">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-cyan-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                        <span class="text-[15px] font-bold tracking-wide"><?php echo t_cevir('Tedavilerimiz'); ?></span>
                    </div>
                    <svg class="dropdown-arrow w-4 h-4 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="submenu hidden bg-[#1f2533] overflow-hidden transition-all duration-300 ease-in-out">
                    <?php foreach($mega_menu_kategoriler as $kategori): ?>
                    <div class="border-b border-white/5 last:border-none">
                        <div class="px-6 py-3 text-[11px] font-black text-cyan-400 uppercase tracking-wider bg-[#1a1f2a] flex items-center gap-2">
                            <span>📌</span> <?php echo htmlspecialchars($kategori['title']); ?>
                        </div>
                        <?php foreach($kategori['items'] as $item): ?>
                        <a href="<?php echo SITE_PATH; ?>/tedaviler/<?php echo $item['slug']; ?>" class="flex items-center justify-between px-6 py-3 pl-10 transition-all group text-slate-400 hover:text-cyan-400 hover:bg-[#262c3a] text-sm">
                            <span><?php echo htmlspecialchars($item['baslik']); ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <a class="flex items-center justify-between px-6 py-4 transition-all group border-b border-white/5 last:border-none border-l-4 border-l-transparent text-slate-300 hover:bg-[#262c3a] hover:text-white" href="<?php echo SITE_PATH; ?>/teknolojiler/">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-cyan-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span class="text-[15px] font-bold tracking-wide"><?php echo t_cevir('Teknolojiler'); ?></span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
            <a class="flex items-center justify-between px-6 py-4 transition-all group border-b border-white/5 last:border-none border-l-4 border-l-transparent text-slate-300 hover:bg-[#262c3a] hover:text-white" href="<?php echo SITE_PATH; ?>/galeri/">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-cyan-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2"/><circle cx="8.5" cy="8.5" r="2.5"/><path d="M21 15l-5-5-6 6-3-3-4 4"/></svg>
                    <span class="text-[15px] font-bold tracking-wide"><?php echo t_cevir('Galeri'); ?></span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
            <a class="flex items-center justify-between px-6 py-4 transition-all group border-b border-white/5 last:border-none border-l-4 border-l-transparent text-slate-300 hover:bg-[#262c3a] hover:text-white" href="<?php echo SITE_PATH; ?>/blog/">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-cyan-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><line x1="8" y1="9" x2="16" y2="9"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="12" y2="17"/></svg>
                    <span class="text-[15px] font-bold tracking-wide"><?php echo t_cevir('Blog'); ?></span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
            <a class="flex items-center justify-between px-6 py-4 transition-all group border-b border-white/5 last:border-none border-l-4 border-l-transparent text-slate-300 hover:bg-[#262c3a] hover:text-white" href="<?php echo SITE_PATH; ?>/iletisim/">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-cyan-400 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span class="text-[15px] font-bold tracking-wide"><?php echo t_cevir('İletişim'); ?></span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </nav>
        <div class="p-6 space-y-3 mt-auto border-t border-white/5 bg-[#171b26]">
            <div class="grid grid-cols-2 gap-3">
                <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $header_telefon ?: ''); ?>" class="flex flex-col items-center justify-center py-4 rounded-xl bg-[#262c3a] hover:bg-[#2d3446] active:scale-95 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone text-[#00e1ff] mb-2"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path></svg>
                    <span class="text-[11px] font-black text-slate-300 tracking-widest uppercase"><?php echo t_cevir('ARA'); ?></span>
                </a>
                <a href="https://wa.me/<?php echo $whatsapp_clear; ?>" target="_blank" class="flex flex-col items-center justify-center py-4 rounded-xl bg-[#1a332a] hover:bg-[#224538] active:scale-95 transition-all border border-[#25d366]/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle text-[#25d366] mb-2"><path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"></path></svg>
                    <span class="text-[11px] font-black text-[#25d366] tracking-widest uppercase">WHATSAPP</span>
                </a>
            </div>
            <a class="w-full flex items-center justify-center gap-2 py-4 bg-[#007aff] hover:bg-[#0062cc] text-white rounded-xl active:scale-[0.98] transition-all shadow-[0_0_20px_rgba(0,122,255,0.3)]" href="<?php echo SITE_PATH; ?>/iletisim/">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg>
                <span class="text-[14px] font-black tracking-wide"><?php echo t_cevir('İletişime Geç'); ?></span>
            </a>
        </div>
    </div>

<div class="hidden lg:block bg-slate-700 text-white shadow-xl sticky top-0 z-50 border-t border-slate-800 relative" id="desktopNav">
    <div class="max-w-[1600px] mx-auto px-6 h-[70px] flex items-center justify-between">
<ul class="flex items-center gap-6 xl:gap-8 text-sm font-bold tracking-wider h-full relative">
    <?php foreach($header2_navlinks as $item): ?>
        <?php 
        $ham_link = $item['link'];
        $nav_link = (empty($ham_link) || $ham_link == '/') ? SITE_PATH . '/' : (strpos($ham_link, 'http') === 0 ? $ham_link : SITE_PATH . '/' . ltrim($ham_link, '/'));
        $nav_link = str_replace(['//', ':/'], ['/', '://'], $nav_link);

        $is_mega_menu = (mb_stripos($item['name'], 'Tedaviler') !== false || mb_stripos($item['name'], 'Treatments') !== false);
        ?>
        
        <?php if($is_mega_menu): ?>
            <li class="h-full flex items-center group mega-menu-trigger" id="megaMenuTrigger">
                <div class="relative flex items-center h-full">
                    <a href="<?php echo SITE_PATH; ?>/tedaviler/" class="relative py-2 transition-all duration-300 group-hover:text-cyan-400 text-slate-300 flex items-center gap-1">
                        <?php echo htmlspecialchars($item['name']); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="transition-transform duration-300 mega-menu-arrow"><path d="m6 9 6 6 6-6"/></svg>
                        <span class="absolute bottom-0 left-0 h-[2px] bg-cyan-500 transition-all w-0 group-hover:w-full"></span>
                    </a>
                </div>
            </li>
        <?php else: ?>
            <li class="h-full flex items-center">
                <a href="<?php echo $nav_link; ?>" class="relative py-2 transition-all duration-300 group text-slate-300 hover:text-cyan-400">
                    <?php echo htmlspecialchars(ucfirst($item['name'])); ?>
                    <span class="absolute bottom-0 left-0 h-[2px] bg-cyan-500 transition-all w-0 group-hover:w-full"></span>
                </a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
        <div class="flex items-center gap-3 xl:gap-4">
<div class="relative group flex items-center" id="searchContainer">
                <div class="absolute left-3.5 flex items-center pointer-events-none z-20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search transition-colors duration-300 text-slate-400"><path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle></svg>
                </div>
                <input type="text" id="searchInput" placeholder="<?php echo t_cevir('Sitede Ara...'); ?>" autocomplete="off" class="relative z-10 bg-white/5 border border-white/10 text-white text-xs font-semibold rounded-full py-2.5 outline-none transition-all duration-300 ease-out placeholder-slate-400/70 shadow-inner pl-10 pr-4 w-32 focus:w-64">
                
                <div id="searchResults" class="absolute top-[120%] right-0 w-[400px] bg-[#1b202c]/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_20px_40px_rgba(0,0,0,0.6)] transition-all duration-300 origin-top-right overflow-hidden z-50 opacity-0 invisible scale-95">
                    <div class="p-4 max-h-[400px] overflow-y-auto">
                        <div id="searchResultsContent"></div>
                    </div>
                </div>
            </div>
            
<a class="px-8 xl:px-10 py-3 rounded-full text-white font-bold text-xs bg-gradient-to-r from-cyan-600 to-blue-600 hover:scale-105 transition-all shadow-[0_0_15px_rgba(0,182,255,0.3)] shrink-0 inline-flex items-center justify-center min-w-[140px]" href="<?php echo SITE_PATH; ?>/iletisim/">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar mr-2">
        <path d="M8 2v4"></path>
        <path d="M16 2v4"></path>
        <rect width="18" height="18" x="3" y="4" rx="2"></rect>
        <path d="M3 10h18"></path>
    </svg>
    <span><?php echo t_cevir('İLETİŞİM'); ?></span>
</a>
        </div>
    </div>
</div>

<div id="megaMenuPanel" class="mega-menu-container">
    <div class="mega-menu-inner">
        <div class="mega-menu-layout">
            
            <div class="mega-menu-categories">
                <div class="mega-menu-header">
<span class="mega-menu-badge">📋 <?php echo t_cevir('KATEGORİLER'); ?></span>
<span class="mega-menu-count"><?php echo count($mega_menu_kategoriler); ?> <?php echo t_cevir('kategori'); ?></span>
                </div>
                <div class="mega-menu-cat-list" id="megaMenuCategories">
                    <?php foreach($mega_menu_kategoriler as $index => $kategori): ?>
                    <button data-cat-index="<?php echo $index; ?>" class="mega-cat-btn <?php echo $index === 0 ? 'active' : ''; ?>">
                        <div class="mega-cat-icon">
                            <?php if($index == 0): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/></svg>
                            <?php elseif($index == 1): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                            <?php elseif($index == 2): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
                            <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                            <?php endif; ?>
                        </div>
                        <span class="mega-cat-name"><?php echo htmlspecialchars($kategori['title']); ?></span>
                        <div class="mega-cat-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
<div class="mega-menu-tedaviler">
    <div class="mega-menu-header">
        <span class="mega-menu-badge mega-badge-cyan">🎯 <?php echo t_cevir('TEDAVİLER'); ?></span>
        <span class="mega-menu-active-title" id="megaMenuActiveTitle"><?php echo htmlspecialchars($mega_menu_kategoriler[0]['title'] ?? ''); ?></span>
    </div>
    <div class="mega-menu-tedavi-list" id="megaMenuItems">
        <?php foreach($mega_menu_kategoriler[0]['items'] as $item): ?>
        <a href="<?php echo SITE_PATH; ?>/tedaviler/<?php echo $item['slug']; ?>" class="mega-tedavi-item">
            <span class="mega-tedavi-dot"></span>
            <span class="mega-tedavi-name"><?php echo htmlspecialchars(t_cevir($item['baslik'])); ?></span>
            <svg class="mega-tedavi-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endforeach; ?>
    </div>
</div>
            
<div class="mega-menu-cta">
    <div class="mega-cta-card">
        <div class="mega-cta-glow"></div>
        <div class="mega-cta-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="mega-cta-title" style="font-size:20px;font-weight:800;color:white;margin-bottom:16px;line-height:1.35;">
            <?php echo t_cevir('Gülüşünüzü Ertelemeyin'); ?>
        </div>
        <p class="mega-cta-text"><?php echo t_cevir('Ücretsiz ilk muayene ve dijital analiz için şimdi iletişime geçin.'); ?></p>
        <a href="<?php echo SITE_PATH; ?>/iletisim/" class="mega-cta-btn">
            <?php echo t_cevir('Hemen Başla'); ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="5" y1="12" x2="19" y2="12"/><line x1="12" y1="5" x2="19" y2="12"/></svg>
        </a>
    </div>
</div>
        </div>
    </div>
</div>
</header>
<script>
// ========== MOBILE MENU ==========
document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
    document.getElementById('mobileOverlay')?.classList.remove('opacity-0', 'invisible');
    document.getElementById('mobileMenuPanel')?.classList.remove('translate-x-[120%]', 'opacity-0');
    document.getElementById('mobileMenuPanel')?.classList.add('translate-x-0', 'opacity-100');
});
document.getElementById('closeMobileMenu')?.addEventListener('click', function() {
    document.getElementById('mobileOverlay')?.classList.add('opacity-0', 'invisible');
    document.getElementById('mobileMenuPanel')?.classList.remove('translate-x-0', 'opacity-100');
    document.getElementById('mobileMenuPanel')?.classList.add('translate-x-[120%]', 'opacity-0');
});
document.getElementById('mobileOverlay')?.addEventListener('click', function() {
    document.getElementById('closeMobileMenu')?.click();
});
document.getElementById('closeMobileMenu')?.addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('mobileOverlay')?.classList.add('opacity-0', 'invisible');
    document.getElementById('mobileMenuPanel')?.classList.remove('translate-x-0', 'opacity-100');
    document.getElementById('mobileMenuPanel')?.classList.add('translate-x-[120%]', 'opacity-0');
});

// ========== MEGA MENÜ - PROFESYONEL ==========
const megaMenuTrigger = document.getElementById('megaMenuTrigger');
const megaMenuPanel = document.getElementById('megaMenuPanel');
let megaMenuTimeout;

function openMegaMenu() {
    if(megaMenuTimeout) clearTimeout(megaMenuTimeout);
    if(megaMenuPanel) {
        megaMenuPanel.classList.add('open');
    }
    const arrow = document.querySelector('#megaMenuTrigger .mega-menu-arrow');
    if(arrow) arrow.style.transform = 'rotate(90deg)';
}

function closeMegaMenu() {
    megaMenuTimeout = setTimeout(() => {
        if(megaMenuPanel) {
            megaMenuPanel.classList.remove('open');
        }
        const arrow = document.querySelector('#megaMenuTrigger .mega-menu-arrow');
        if(arrow) arrow.style.transform = 'rotate(0deg)';
    }, 150);
}

if(megaMenuTrigger) {
    megaMenuTrigger.addEventListener('mouseenter', openMegaMenu);
    megaMenuTrigger.addEventListener('mouseleave', closeMegaMenu);
}
if(megaMenuPanel) {
    megaMenuPanel.addEventListener('mouseenter', openMegaMenu);
    megaMenuPanel.addEventListener('mouseleave', closeMegaMenu);
}

// ========== KATEGORİ HOVER İLE İÇERİK DEĞİŞTİRME ==========
const catButtons = document.querySelectorAll('.mega-cat-btn');
const megaMenuItems = document.getElementById('megaMenuItems');
const megaMenuActiveTitle = document.getElementById('megaMenuActiveTitle');
const megaMenuData = <?php echo json_encode($mega_menu_kategoriler, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>;

function updateCategoryContent(catIndex) {
    const data = megaMenuData[catIndex];
    if (!data) return;
    
    if (megaMenuActiveTitle) megaMenuActiveTitle.textContent = data.title; 
    
    if (megaMenuItems) {
        const fragment = document.createDocumentFragment();
        
        if (data.items && data.items.length > 0) {
            data.items.forEach(item => {
                const a = document.createElement('a');
                a.href = "<?php echo SITE_PATH; ?>/tedaviler/" + item.slug;
                a.className = "mega-tedavi-item";
                
                a.innerHTML = `
                    <span class="mega-tedavi-dot"></span>
                    <span class="mega-tedavi-name">${escapeHtml(item.baslik)}</span>
                    <svg class="mega-tedavi-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="9 18 15 12 9 6"/></svg>
                `;
                fragment.appendChild(a);
            });
            megaMenuItems.innerHTML = '';
            megaMenuItems.appendChild(fragment);
        } else {
            megaMenuItems.innerHTML = `<div style="padding: 20px; text-align: center; color: #64748b;">Henüz tedavi bulunmuyor.</div>`;
        }
    }
}

catButtons.forEach(btn => {
    btn.addEventListener('mouseenter', function() {
        const catIndex = this.getAttribute('data-cat-index');
        catButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        updateCategoryContent(catIndex);
    });
});
document.addEventListener('DOMContentLoaded', function() {
    if(catButtons.length > 0) {
        catButtons[0].classList.add('active');
        updateCategoryContent(0); 
    }
});

function escapeHtml(str) {
    if(!str) return ''; 
    return String(str).replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

// ========== GLOBAL DICTIONARY ==========
const dinamikCeviriler = {
    'Lamine Diş (Yaprak Porselen)': 'Laminated Teeth (Leaf Porcelain)',
    'Diş Beyazlatma (Bleaching)': 'Teeth Whitening (Bleaching)',
    'Pembe Estetik (Diş Eti Estetiği)': 'Pink Aesthetics (Gum Aesthetics)',
    'Diş İmplantı': 'Dental Implant',
    '20 Yaş Dişleri': 'Wisdom Teeth (20th Year Teeth)',
    'Diş Çekimi': 'Tooth Extraction',
    'Diş Teli': 'Dental Braces',
    'Zirkonyum Kaplama': 'Zirconium Coating',
    'Estetik Kompozit Dolgu': 'Aesthetic Composite Filling',
    'Gülüş Tasarımı': 'Smile Design (Hollywood Smile)',
    'Ağız ve Çene Cerrahisi': 'Oral and Maxillofacial Surgery',
    'Kanal Tedavisi': 'Root Canal Treatment',
    'Diş Eti Tedavisi': 'Gum Treatment (Periodontoloji)',
    'İmplant Üstü Protez': 'Implant-Supported Denture',
    'Dikişsiz implant': 'Seamless Implant',
    'Kısa implant': 'Short Implant'
};

function turkceKucukHarf(str) {
    if(!str) return '';
    return str.replace(/İ/g, "i").replace(/I/g, "ı").replace(/Ş/g, "ş").replace(/Ç/g, "ç").replace(/Ğ/g, "ğ").replace(/Ü/g, "ü").replace(/Ö/g, "ö").toLowerCase().trim();
}

const kucukCeviriler = {};
const kucukTersCeviriler = {};
const orijinalTrSozluk = {};

for (const [key, value] of Object.entries(dinamikCeviriler)) {
    const trKey = turkceKucukHarf(key);
    const enVal = value;
    kucukCeviriler[trKey] = enVal;
    kucukTersCeviriler[turkceKucukHarf(enVal)] = key;
    orijinalTrSozluk[trKey] = key; 
}

// ========== SEARCH INPUT ==========
let searchTimeout;
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');

if (searchInput) {
    searchInput.classList.add('origin-right');
}

const populerAramalarData = <?php echo json_encode($populer_aramalar, JSON_UNESCAPED_UNICODE); ?>;
const sitePathString = '<?php echo SITE_PATH; ?>';

function restoreDefaultSearch() {
    const resultsContent = document.getElementById('searchResultsContent');
    if(!resultsContent) return;

    const aktifDil = '<?php echo $_SESSION['dil'] ?? 'tr'; ?>';
    const isEn = (aktifDil === 'en');

    const txtSonArananlar = isEn ? 'RECENT SEARCHES' : 'Son Arananlar';
    const txtHizliErisim = isEn ? 'QUICK ACCESS' : 'Hızlı Erişim';
    const txtKlinikAdres = isEn ? 'Clinic Address & Contact' : 'Klinik Adres & İletişim';
    const txtGecmisBos = isEn ? 'No recent searches yet.' : 'Henüz arama geçmişiniz yok.';

    let sonArananlar = [];
    try {
        sonArananlar = JSON.parse(localStorage.getItem('arama_gecmisi')) || [];
    } catch(e) {
        sonArananlar = [];
    }

    let gecmisHtml = '';
    if (sonArananlar.length > 0) {
        gecmisHtml = '<div class="grid grid-cols-2 gap-2">';
        sonArananlar.forEach(item => {
            const arananKucuk = turkceKucukHarf(item.baslik);
            let anahtarTurkce = item.baslik;
            if (kucukTersCeviriler[arananKucuk]) {
                anahtarTurkce = kucukTersCeviriler[arananKucuk];
            } else if (orijinalTrSozluk[arananKucuk]) {
                anahtarTurkce = orijinalTrSozluk[arananKucuk];
            }

            let gosterilecekMetin = anahtarTurkce;
            const kontrolKey = turkceKucukHarf(anahtarTurkce);
            if (isEn && kucukCeviriler[kontrolKey]) {
                gosterilecekMetin = kucukCeviriler[kontrolKey];
            }

            const temizBaslik = escapeHtml(gosterilecekMetin);
            const temizLink = escapeHtml(item.link);
            const güvenliBaslik = temizBaslik.replace(/'/g, "\\'");
            const güvenliLink = temizLink.replace(/'/g, "\\'");
            const güvenliAnahtar = escapeHtml(anahtarTurkce).replace(/'/g, "\\'");
            
            gecmisHtml += `
                <a href="${temizLink}" onclick="aramaKaydet('${güvenliAnahtar}', '${güvenliLink}')" class="flex items-center justify-between pl-3 pr-1.5 py-1.5 bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white rounded-xl transition-all border border-white/5 group/item text-xs font-medium min-w-0">
                    <span class="truncate mr-1">${temizBaslik}</span>
                    <button onclick="aramaSil(event, '${güvenliAnahtar}')" class="w-5 h-5 rounded-lg flex items-center justify-center text-slate-500 hover:text-red-400 hover:bg-white/10 transition-all cursor-pointer flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </a>`;
        });
        gecmisHtml += '</div>';
    } else {
        gecmisHtml = `<div class="text-xs text-slate-500 p-2 italic">${txtGecmisBos}</div>`;
    }

    resultsContent.innerHTML = `
        <h4 class="text-[10px] font-black tracking-widest text-slate-500 uppercase mb-3">${txtSonArananlar}</h4>
        <div class="mb-5">${gecmisHtml}</div>
        
        <h4 class="text-[10px] font-black tracking-widest text-slate-500 uppercase mb-3">${txtHizliErisim}</h4>
        <ul class="space-y-1">
            <li class="group/link">
                <a href="${sitePathString}/iletisim/" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-white/5 text-sm font-semibold text-slate-300 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin text-slate-500 group-hover/link:text-cyan-400 transition-colors">
                        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    ${txtKlinikAdres}
                </a>
            </li>
        </ul>
    `;
}

function aramaKaydet(baslik, link) {
    if (!baslik || !link) return;
    
    let gecmis = [];
    try {
        gecmis = JSON.parse(localStorage.getItem('arama_gecmisi')) || [];
    } catch(e) {
        gecmis = [];
    }

    const baslikKucuk = turkceKucukHarf(baslik);
    let temizBaslik = baslik;
    if (kucukTersCeviriler[baslikKucuk]) {
        temizBaslik = kucukTersCeviriler[baslikKucuk];
    } else if (orijinalTrSozluk[baslikKucuk]) {
        temizBaslik = orijinalTrSozluk[baslikKucuk];
    }

    gecmis = gecmis.filter(item => {
        const itemKucuk = turkceKucukHarf(item.baslik);
        return itemKucuk !== turkceKucukHarf(temizBaslik);
    });
    
    gecmis.unshift({ baslik: temizBaslik, link: link });

    if (gecmis.length > 6) {
        gecmis = gecmis.slice(0, 6);
    }

    localStorage.setItem('arama_gecmisi', JSON.stringify(gecmis));
}

function aramaSil(event, baslik) {
    event.preventDefault();
    event.stopPropagation();
    
    let gecmis = [];
    try {
        gecmis = JSON.parse(localStorage.getItem('arama_gecmisi')) || [];
    } catch(e) {
        gecmis = [];
    }
    
    const baslikKucuk = turkceKucukHarf(baslik);
    let temizBaslik = baslik;
    if (kucukTersCeviriler[baslikKucuk]) {
        temizBaslik = kucukTersCeviriler[baslikKucuk];
    } else if (orijinalTrSozluk[baslikKucuk]) {
        temizBaslik = orijinalTrSozluk[baslikKucuk];
    }
    
    gecmis = gecmis.filter(item => {
        const itemKucuk = turkceKucukHarf(item.baslik);
        return itemKucuk !== turkceKucukHarf(temizBaslik);
    });
    localStorage.setItem('arama_gecmisi', JSON.stringify(gecmis));
    
    restoreDefaultSearch();
}

window.searchPopuler = function(value) {
    if(searchInput) {
        searchInput.value = value;
        performSearch2(value);
    }
}

function performSearch2(query) {
    if(!searchResults) return;
    if(query.length < 2) {
        restoreDefaultSearch();
        searchResults.classList.remove('opacity-0', 'invisible', 'scale-95');
        searchResults.classList.add('opacity-100', 'visible', 'scale-100');
        return;
    }

    const aktifDil = '<?php echo $_SESSION['dil'] ?? 'tr'; ?>';
    const isEn = (aktifDil === 'en');

    let sorgulanacakKelime = query;
    if (isEn) {
        const kucukHarfQuery = turkceKucukHarf(query);
        if (kucukHarfQuery.includes('tooth') || kucukHarfQuery.includes('teeth') || kucukHarfQuery.includes('dental')) {
            sorgulanacakKelime = 'diş';
        } else if (kucukHarfQuery.includes('implant')) {
            sorgulanacakKelime = 'implant';
        } else if (kucukHarfQuery.includes('zirconium') || kucukHarfQuery.includes('coating')) {
            sorgulanacakKelime = 'zirkonyum';
        } else if (kucukHarfQuery.includes('smile') || kucukHarfQuery.includes('hollywood')) {
            sorgulanacakKelime = 'gülüş';
        } else if (kucukHarfQuery.includes('filling')) {
            sorgulanacakKelime = 'dolgu';
        } else if (kucukHarfQuery.includes('bleaching') || kucukHarfQuery.includes('whitening')) {
            sorgulanacakKelime = 'beyazlatma';
        } else if (kucukHarfQuery.includes('gum') || kucukHarfQuery.includes('pink')) {
            sorgulanacakKelime = 'pembe';
        } else if (kucukHarfQuery.includes('surgery')) {
            sorgulanacakKelime = 'cerrahi';
        } else if (kucukHarfQuery.includes('root') || kucukHarfQuery.includes('canal')) {
            sorgulanacakKelime = 'kanal';
        } else if (kucukHarfQuery.includes('brace')) {
            sorgulanacakKelime = 'tel';
        }
    }
    
    fetch(`${sitePathString}/api/arama.php?q=${encodeURIComponent(sorgulanacakKelime)}`)
        .then(res => {
            if (!res.ok) throw new Error('Network response error');
            return res.json();
        })
        .then(data => {
            const resultsContent = document.getElementById('searchResultsContent');

            const txtAramaSonuclari = isEn ? 'SEARCH RESULTS' : 'Arama Sonuçları';
            const txtTipTedavi = isEn ? 'Treatment' : 'Tedavi';
            const txtTipSayfa = isEn ? 'Page' : 'Sayfa';
            const txtSonucBulunamadi = isEn ? 'No results found for' : 'için sonuç bulunamadı';

            if(data.success && data.data && data.data.length > 0) {
                let html = `<h4 class="text-[10px] font-black tracking-widest text-cyan-400 uppercase mb-3">${txtAramaSonuclari} (${data.data.length})</h4><div class="space-y-1">`;
                
                data.data.forEach(result => {
                    let link = result.tip === 'tedavi' ? `${sitePathString}/tedaviler/${result.slug}` : `${sitePathString}/${result.slug}.php`;
                    let tipText = result.tip === 'tedavi' ? txtTipTedavi : txtTipSayfa;
                    
                    let gosterilecekBaslik = result.baslik;
                    try {
                        if (typeof result.baslik === 'string' && (result.baslik.startsWith('{') || result.baslik.startsWith('['))) {
                            const baslikJson = JSON.parse(result.baslik);
                            gosterilecekBaslik = baslikJson[aktifDil] || baslikJson['tr'] || result.baslik;
                        }
                    } catch (e) {
                        gosterilecekBaslik = result.baslik;
                    }

                    const hamTurkceBaslik = gosterilecekBaslik;
                    const baslikKontrolKey = turkceKucukHarf(gosterilecekBaslik);

                    if (isEn && kucukCeviriler[baslikKontrolKey]) {
                        gosterilecekBaslik = kucukCeviriler[baslikKontrolKey];
                    }

                    const temizBaslik = escapeHtml(gosterilecekBaslik).replace(/'/g, "\\'");
                    const temizLink = link.replace(/'/g, "\\'");
                    const güvenliHamBaslik = escapeHtml(hamTurkceBaslik).replace(/'/g, "\\'");

                    html += `<a href="${link}" onclick="aramaKaydet('${güvenliHamBaslik}', '${temizLink}')" class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div><span class="text-sm font-semibold text-slate-200 group-hover:text-cyan-400">${escapeHtml(gosterilecekBaslik)}</span><span class="text-[10px] text-slate-500 ml-2 uppercase">${tipText}</span></div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right text-slate-500 group-hover:text-cyan-400"><path d="m9 18 6-6-6-6"></path></svg>
                    </a>`;
                });
                html += `</div>`;
                resultsContent.innerHTML = html;
            } else {
                if(isEn) {
                    resultsContent.innerHTML = `<div class="text-center py-8 text-slate-400 text-sm">${txtSonucBulunamadi} "${escapeHtml(query)}"</div>`;
                } else {
                    resultsContent.innerHTML = `<div class="text-center py-8 text-slate-400 text-sm">"${escapeHtml(query)}" ${txtSonucBulunamadi}</div>`;
                }
            }
            searchResults.classList.remove('opacity-0', 'invisible', 'scale-95');
            searchResults.classList.add('opacity-100', 'visible', 'scale-100');
        })
        .catch(() => {
            const resultsContent = document.getElementById('searchResultsContent');
            const aktifDil = '<?php echo $_SESSION['dil'] ?? 'tr'; ?>';
            const txtHata = (aktifDil === 'en') ? 'Search error' : 'Arama hatası';
            if (resultsContent) {
                resultsContent.innerHTML = `<div class="text-center py-8 text-slate-400 text-sm">${txtHata}</div>`;
            }
            searchResults.classList.remove('opacity-0', 'invisible', 'scale-95');
            searchResults.classList.add('opacity-100', 'visible', 'scale-100');
        });
}

if(searchInput) {
    searchInput.addEventListener('click', function(e) {
        e.stopPropagation();
        this.classList.remove('w-32');
        this.classList.add('w-64');
        searchResults.classList.remove('opacity-0', 'invisible', 'scale-95');
        searchResults.classList.add('opacity-100', 'visible', 'scale-100');
    });

    searchInput.addEventListener('focus', function() {
        this.classList.remove('w-32');
        this.classList.add('w-64');
        if(this.value.length >= 2) { performSearch2(this.value); } 
        else {
            restoreDefaultSearch();
            searchResults.classList.remove('opacity-0', 'invisible', 'scale-95');
            searchResults.classList.add('opacity-100', 'visible', 'scale-100');
        }
    });

    searchInput.addEventListener('input', function() {
        if(searchTimeout) clearTimeout(searchTimeout);
        const val = this.value;
        if(val.length < 2) {
            restoreDefaultSearch();
            searchResults.classList.remove('opacity-0', 'invisible', 'scale-95');
            searchResults.classList.add('opacity-100', 'visible', 'scale-100');
        } else {
            searchTimeout = setTimeout(() => performSearch2(val), 300);
        }
    });

    searchInput.addEventListener('blur', function() {
        setTimeout(() => {
            if(this.value.length === 0) {
                searchResults.classList.add('opacity-0', 'invisible', 'scale-95');
                searchResults.classList.remove('opacity-100', 'visible', 'scale-100');
                this.classList.remove('w-64');
                this.classList.add('w-32');
            }
        }, 200);
    });
}

document.addEventListener('click', function(e) {
    if(searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.add('opacity-0', 'invisible', 'scale-95');
        searchResults.classList.remove('opacity-100', 'visible', 'scale-100');
        searchInput.classList.remove('w-64');
        searchInput.classList.add('w-32');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.mobile-dropdown');
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        if(trigger) {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                const submenu = dropdown.querySelector('.submenu');
                if(submenu) {
                    submenu.classList.toggle('hidden');
                    submenu.classList.toggle('block');
                }
            });
        }
    });
});

// ========== MOBİL ARAMA DESTEĞİ ==========
(function() {
    'use strict';

    const mobileSearch = document.getElementById('mobileSearchInput');
    const searchResults = document.getElementById('searchResults');
    const resultsContent = document.getElementById('searchResultsContent');

    if (!mobileSearch || !searchResults) return;

    let mobileSearchTimeout;

    mobileSearch.addEventListener('focus', function() {
        if (this.value.length >= 2) {
            performSearch2(this.value);
        } else {
            restoreDefaultSearch();
        }
        searchResults.classList.remove('opacity-0', 'invisible', 'scale-95');
        searchResults.classList.add('opacity-100', 'visible', 'scale-100');
    });

    mobileSearch.addEventListener('input', function() {
        clearTimeout(mobileSearchTimeout);
        const val = this.value;
        if (val.length < 2) {
            restoreDefaultSearch();
            searchResults.classList.remove('opacity-0', 'invisible', 'scale-95');
            searchResults.classList.add('opacity-100', 'visible', 'scale-100');
        } else {
            mobileSearchTimeout = setTimeout(() => performSearch2(val), 300);
        }
    });

    mobileSearch.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = this.value.trim();
            if (query.length >= 2) {
                performSearch2(query);
            } else {
                if (resultsContent) {
                    resultsContent.innerHTML = `<div class="text-center py-4 text-slate-400 text-sm">Lütfen en az 2 karakter girin.</div>`;
                }
                searchResults.classList.remove('opacity-0', 'invisible', 'scale-95');
                searchResults.classList.add('opacity-100', 'visible', 'scale-100');
            }
        }
    });

    mobileSearch.addEventListener('blur', function() {
        setTimeout(() => {
            if (this.value.length === 0) {
                searchResults.classList.add('opacity-0', 'invisible', 'scale-95');
                searchResults.classList.remove('opacity-100', 'visible', 'scale-100');
            }
        }, 300);
    });

})();

// ========== ÇEREZ ONAY YÖNETİMİ ==========
(function() {
    'use strict';
    
    const CONSENT_KEY = 'cerez_onay_v1';
    const CONSENT_EXPIRY_DAYS = 365;
    
    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = 'expires=' + d.toUTCString();
        document.cookie = name + '=' + value + ';' + expires + ';path=/;SameSite=Lax';
    }
    
    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    function showBanner() {
        const banner = document.getElementById('cookieConsent');
        if (banner) {
            setTimeout(() => banner.classList.add('show'), 800);
        }
    }
    
    function hideBanner() {
        const banner = document.getElementById('cookieConsent');
        if (banner) {
            banner.classList.remove('show');
            setTimeout(() => banner.remove(), 600);
        }
    }
    
window.cerezKabul = function() {
    setCookie(CONSENT_KEY, 'accepted', CONSENT_EXPIRY_DAYS);
    
    if (typeof loadAnalytics === 'function') {
        try { loadAnalytics(); } catch(e) { console.warn('GA yüklenemedi:', e); }
    }
    if (typeof loadYandexMetrika === 'function') {
        try { loadYandexMetrika(); } catch(e) { console.warn('Yandex yüklenemedi:', e); }
    }
    
    hideBanner();
};
    
    window.cerezReddet = function() {
        setCookie(CONSENT_KEY, 'rejected', CONSENT_EXPIRY_DAYS);
        hideBanner();
    };
window.cerezTercihleriSifirla = function() {
    document.cookie = CONSENT_KEY + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    
    const banner = document.getElementById('cookieConsent');
    if (banner) {
        banner.classList.add('show');
    } else {
        location.reload();
    }
};
    document.addEventListener('DOMContentLoaded', function() {
        const consent = getCookie(CONSENT_KEY);
        if (!consent) {
            showBanner();
        }
    });
})();

</script>

  <script defer>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[aria-hidden="true"] a, [aria-hidden="true"] button')
                .forEach(el => el.setAttribute('tabindex', '-1'));
        });
    </script>
	

<!-- Yandex.Metrika counter - SADECE çerez onayı verildiyse yükle -->
<script type="text/javascript">
window.loadYandexMetrika = function() {
    if (window.__ymLoaded) return;
    window.__ymLoaded = true;
    
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=111371087', 'ym');

    ym(111371087, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
};

document.addEventListener('DOMContentLoaded', function() {
    const consent = document.cookie.match(/cerez_onay_v1=([^;]+)/);
    if (consent && consent[1] === 'accepted') {
        window.loadYandexMetrika();
    }
});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/111371087" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->