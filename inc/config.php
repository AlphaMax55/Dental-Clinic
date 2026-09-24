<?php
// www/site/inc/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');
require_once dirname(__DIR__, 1) . '/admin/includes/config.php';
// Çevirmen motorunu herkesten önce projeye dahil ediyoruz ki fonksiyonlar çalışsın reis!

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hata raporlama (canlıda kapat, localde aç)
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Kullanıcı URL'den dil değiştirdi mi? (?lang=en veya ?lang=tr)
if (isset($_GET['lang'])) {
    $gider_dil = trim($_GET['lang']);
    if (in_array($gider_dil, ['tr', 'en'])) {
        $_SESSION['dil'] = $gider_dil;
    }
}

// Varsayılan dil Türkçe
if (!isset($_SESSION['dil'])) {
    $_SESSION['dil'] = 'tr';
}

require_once __DIR__ . '/cevirmen.php';

// ========== SITE PATH VE URL SABİTLERİ ==========
$site_path = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ? '/site' : '';

if (!defined('SITE_PATH')) define('SITE_PATH', $site_path);
if (!defined('SITE_URL')) define('SITE_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $site_path);
if (!defined('BASE_URL')) define('BASE_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
if (!defined('ADMIN_URL')) define('ADMIN_URL', BASE_URL . '/admin');

// ========== SITE AYARLARI ==========
$site_baslik = isset($ayarlar['site_baslik']) ? t_cevir($ayarlar['site_baslik']) : 'Prof. Dr. İbrahim Duran - Diş Kliniği';
$site_aciklama = isset($ayarlar['site_aciklama']) ? t_cevir($ayarlar['site_aciklama']) : 'Samsun Atakum\'da implant, estetik diş hekimliği ve gülüş tasarımı alanlarında hizmet veren Prof. Dr. İbrahim Duran.';

// AI SEO JSON-LD
$ai_seo_json = null;
if (isset($ayarlar['ai_seo']) && !empty($ayarlar['ai_seo'])) {
    $ai_seo_json = json_decode($ayarlar['ai_seo'], true);
}

// ========== FOOTER AYARLARI ==========
$footer_aciklama = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'footer_aciklama'");
$stmt->execute();
$footer_aciklama = t_cevir($stmt->fetchColumn());

$footer_copyright = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'footer_copyright'");
$stmt->execute();
$footer_copyright = t_cevir($stmt->fetchColumn());

// İletişim bilgileri
$adres = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'adres'");
$stmt->execute();
$adres = t_cevir($stmt->fetchColumn());

$telefon = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'telefon'");
$stmt->execute();
$telefon = $stmt->fetchColumn();

$eposta = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'eposta'");
$stmt->execute();
$eposta = $stmt->fetchColumn();

// ========== SOSYAL MEDYA ==========
$instagram = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'instagram'");
$stmt->execute();
$instagram = $stmt->fetchColumn();

$facebook = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'facebook'");
$stmt->execute();
$facebook = $stmt->fetchColumn();

$youtube = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'youtube'");
$stmt->execute();
$youtube = $stmt->fetchColumn();

$linkedin = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'linkedin'");
$stmt->execute();
$linkedin = $stmt->fetchColumn();

// ========== VERİTABANI FONKSİYONLARI ==========
function get_tedaviler($limit = null) {
    global $db;
    $sql = "SELECT * FROM tedaviler WHERE silindi = 0 AND aktif = 1 ORDER BY sira ASC";
    if ($limit) $sql .= " LIMIT " . intval($limit);
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $sonuc = $stmt->fetchAll();
    
    if (isset($_SESSION['dil']) && $_SESSION['dil'] === 'en') {
        foreach ($sonuc as &$tedavi) {
            if (isset($tedavi['baslik'])) $tedavi['baslik'] = t_cevir($tedavi['baslik']);
            if (isset($tedavi['icerik'])) $tedavi['icerik'] = t_cevir($tedavi['icerik']);
            if (isset($tedavi['kategori'])) $tedavi['kategori'] = t_cevir($tedavi['kategori']);
            if (isset($tedavi['kisa_aciklama'])) $tedavi['kisa_aciklama'] = t_cevir($tedavi['kisa_aciklama']);
        }
    }
    return $sonuc;
}

if(!function_exists('get_yorumlar')) {
    function get_yorumlar() { return []; }
}

if(!function_exists('get_yorum_ortalama')) {
    function get_yorum_ortalama() { return ['ortalama' => 0, 'toplam' => 0]; }
}

// ========== HEADER AYARLARI ==========
$header_logo = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_logo'");
$stmt->execute();
$header_logo = $stmt->fetchColumn();
if (empty($header_logo)) {
    $header_logo = SITE_URL . '/assets/img/logo.png';
}

$header_unvan = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_unvan'");
$stmt->execute();
$header_unvan = t_cevir($stmt->fetchColumn());

$header_uzmanlik = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_uzmanlik'");
$stmt->execute();
$header_uzmanlik = t_cevir($stmt->fetchColumn());

$header_telefon = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'telefon'");
$stmt->execute();
$header_telefon = $stmt->fetchColumn();

$header_whatsapp = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'whatsapp'");
$stmt->execute();
$header_whatsapp = $stmt->fetchColumn();

$header_lokasyon = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_lokasyon'");
$stmt->execute();
$header_lokasyon = t_cevir($stmt->fetchColumn());

$header_adres = $adres;

// ========== HEADER2 NAVIGASYON ==========
$header2_navlinks = [];
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header2_navlinks'");
$stmt->execute();
$navlinks_json = $stmt->fetchColumn();
if ($navlinks_json) {
    $header2_navlinks = json_decode($navlinks_json, true);
}

// Linkleri düzenle (DOĞRU VERSİYON)
if (is_array($header2_navlinks)) {
    foreach ($header2_navlinks as &$item) {
        if (isset($item['link'])) {
            $link = $item['link'];
            
            if (empty($link) || $link === '/') {
                $item['link'] = SITE_PATH . '/';
            }
            elseif (strpos($link, 'http://') === 0 || strpos($link, 'https://') === 0) {
                $item['link'] = $link;
            }
            else {
                $item['link'] = SITE_PATH . '/' . ltrim($link, '/');
            }
            
            $item['link'] = str_replace('//', '/', $item['link']);
            $item['link'] = str_replace('http:/', 'http://', $item['link']);
            $item['link'] = str_replace('https:/', 'https://', $item['link']);
        }
    }
    
    $seen_links = [];
    $unique_links = [];
    foreach ($header2_navlinks as $item) {
        if (!isset($seen_links[$item['name']])) {
            $seen_links[$item['name']] = true;
            $unique_links[] = $item;
        }
    }
    $header2_navlinks = $unique_links;
}

// ========== PROFİL AYARLARI ==========
$profil_ad = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_unvan'");
$stmt->execute();
$profil_ad = t_cevir($stmt->fetchColumn());

$profil_unvan = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_uzmanlik'");
$stmt->execute();
$profil_unvan = t_cevir($stmt->fetchColumn());

$profil_foto = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'doktor_foto'");
$stmt->execute();
$profil_foto = $stmt->fetchColumn();
if (empty($profil_foto)) {
    $profil_foto = SITE_URL . '/assets/img/profil.jpg';
}

$profil_yil_deneyim = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'doktor_deneyim_yil'");
$stmt->execute();
$profil_yil_deneyim = t_cevir($stmt->fetchColumn());

$profil_hasta_sayisi = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'doktor_hasta_sayisi'");
$stmt->execute();
$profil_hasta_sayisi = t_cevir($stmt->fetchColumn());

$profil_tedavi_sayisi = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'doktor_tedavi_sayisi'");
$stmt->execute();
$profil_tedavi_sayisi = t_cevir($stmt->fetchColumn());

$profil_basari_orani = '';
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'doktor_basari_orani'");
$stmt->execute();
$profil_basari_orani = t_cevir($stmt->fetchColumn());

$profil_lokasyon = $header_lokasyon;

// Vizyon maddeleri
$profil_vizyon = [];
$stmt = $db->prepare("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'doktor_vizyon'");
$stmt->execute();
$vizyon_json = $stmt->fetchColumn();
if ($vizyon_json) {
    $profil_vizyon = json_decode($vizyon_json, true);
    if (isset($_SESSION['dil']) && $_SESSION['dil'] === 'en') {
        foreach ($profil_vizyon as &$v_item) {
            if (isset($v_item['text'])) $v_item['text'] = t_cevir($v_item['text']);
        }
    }
}

// ========== CSRF TOKEN FONKSİYONLARI ==========
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token']) || !isset($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

function csrf_meta_tag() {
    return '<meta name="csrf-token" content="' . generate_csrf_token() . '">';
}

// ... config.php dosyanın en altına ekle ...

function getLucideIcon($name, $size = 20) {
    $icons = [
        'Building2' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12h4"/><path d="M6 16h4"/><path d="M6 20h4"/><path d="M14 6h4"/><path d="M14 10h4"/><path d="M14 14h4"/><path d="M14 18h4"/></svg>',
        'GraduationCap' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/></svg>',
        'Award' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/></svg>',
        'Microscope' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 1 0 0-14h-1"/><path d="M9 14h2"/><path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z"/><path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3"/></svg>',
        'HeartPulse' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/></svg>',
        'ShieldCheck' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
        'Zap' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>',
        'Sparkles' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><circle cx="4" cy="20" r="2"/></svg>',
        'ChevronLeft' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>',
        'ChevronRight' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>',
        'ArrowRight' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
        'BadgeCheck' => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>'
    ];
    return isset($icons[$name]) ? $icons[$name] : $icons['Sparkles'];
}


?>