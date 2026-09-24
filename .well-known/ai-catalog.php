<?php
// ============================================================
// .well-known/ai-catalog.json - DİNAMİK (DB'den çeker)
// URL: https://www.dribrahimdurandentalclinic.com/.well-known/ai-catalog.json
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../inc/config.php';

$site_adresi = "https://www.dribrahimdurandentalclinic.com";
$lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'tr';
$dil_param = $lang === 'en' ? '?lang=en' : '';

$entries = [];

// 1. llms.txt
$entries[] = [
    'identifier' => 'urn:ai:dribrahimdurandentalclinic.com:knowledge:llms-txt',
    'displayName' => 'Prof. Dr. İbrahim Duran - Bilgi Dosyası',
    'type' => 'text/plain',
    'url' => $site_adresi . '/llms.txt',
    'description' => 'AI ajanları için site hakkında yapılandırılmış bilgi içeren llms.txt dosyası.',
];

// 2. OpenAPI
$entries[] = [
    'identifier' => 'urn:ai:dribrahimdurandentalclinic.com:api:openapi',
    'displayName' => 'Prof. Dr. İbrahim Duran - API Tanımı',
    'type' => 'application/vnd.oai.openapi+json',
    'url' => $site_adresi . '/.well-known/openapi.yaml',
    'description' => 'Kliniğin web servisleri için OpenAPI tanımı.',
];

// 3. ai-plugin.json
$entries[] = [
    'identifier' => 'urn:ai:dribrahimdurandentalclinic.com:manifest:ai-plugin',
    'displayName' => 'AI Plugin Manifest',
    'type' => 'application/json',
    'url' => $site_adresi . '/.well-known/ai-plugin.json',
    'description' => 'ChatGPT ve diğer AI platformları için plugin manifest dosyası.',
];

// 4. Ana sayfa
$entries[] = [
    'identifier' => 'urn:ai:dribrahimdurandentalclinic.com:page:home',
    'displayName' => $lang === 'en' ? 'Home Page' : 'Ana Sayfa',
    'type' => 'text/html',
    'url' => $site_adresi . '/' . $dil_param,
    'description' => 'Kliniğin ana sayfası',
];

// 5. Sabit sayfalar
$sabit_sayfalar = [
    ['/tedaviler/', 'Tedaviler', 'Treatments'],
    ['/kurumsal/', 'Kurumsal', 'About Us'],
    ['/teknolojiler/', 'Teknolojiler', 'Technologies'],
    ['/galeri/', 'Galeri', 'Gallery'],
    ['/blog/', 'Blog', 'Blog'],
    ['/iletisim/', 'İletişim', 'Contact'],
    ['/kvkk/', 'KVKK', 'Privacy Policy'],
];
foreach ($sabit_sayfalar as $s) {
    $entries[] = [
        'identifier' => 'urn:ai:dribrahimdurandentalclinic.com:page:' . trim($s[0], '/'),
        'displayName' => $lang === 'en' ? $s[2] : $s[1],
        'type' => 'text/html',
        'url' => $site_adresi . $s[0] . $dil_param,
        'description' => ($lang === 'en' ? $s[2] : $s[1]) . ' - Prof. Dr. İbrahim Duran Diş Kliniği',
    ];
}

// 6. Tüm tedavileri DB'den ekle
try {
    $stmt = $db->query("SELECT slug, baslik, kategori FROM tedaviler WHERE silindi = 0 AND aktif = 1 AND slug IS NOT NULL AND slug != '' ORDER BY kategori ASC, sira ASC");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $baslik_raw = trim($row['baslik']);
            $baslik = $baslik_raw;
            $j = json_decode($baslik_raw, true);
            if (is_array($j)) $baslik = $j[$lang] ?? ($j['tr'] ?? $baslik_raw);
            $baslik = html_entity_decode($baslik, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $entries[] = [
                'identifier' => 'urn:ai:dribrahimdurandentalclinic.com:treatment:' . $row['slug'],
                'displayName' => $baslik,
                'type' => 'text/html',
                'url' => $site_adresi . '/tedaviler/' . $row['slug'] . $dil_param,
                'description' => $baslik . ' - ' . ($row['kategori'] ?? 'Tedavi') . ' hakkında detaylı bilgi.',
                'category' => $row['kategori'] ?? '',
            ];
        }
    }
} catch (Exception $e) {}

// 7. Tüm blog yazılarını DB'den ekle
try {
    $stmt = $db->query("SELECT slug, baslik, kategori FROM blog_yazilar WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY created_at DESC");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $kat_slug = 'genel';
            try {
                $ks = $db->prepare("SELECT kategori_slug FROM blog_kategoriler WHERE kategori_adi = ? LIMIT 1");
                $ks->execute([$row['kategori']]);
                $kat_slug = $ks->fetchColumn() ?: 'genel';
            } catch (Exception $e) {}
            $entries[] = [
                'identifier' => 'urn:ai:dribrahimdurandentalclinic.com:blog:' . $row['slug'],
                'displayName' => html_entity_decode(trim($row['baslik']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'type' => 'text/html',
                'url' => $site_adresi . '/blog/' . $kat_slug . '/' . $row['slug'] . $dil_param,
                'description' => 'Blog yazısı: ' . trim($row['baslik']),
                'category' => $row['kategori'] ?? '',
            ];
        }
    }
} catch (Exception $e) {}

// 8. Galeri kategorilerini ekle
try {
    $stmt = $db->query("SELECT kategori_adi, kategori_slug FROM galeri_kategoriler ORDER BY sira ASC");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entries[] = [
                'identifier' => 'urn:ai:dribrahimdurandentalclinic.com:gallery:' . $row['kategori_slug'],
                'displayName' => trim($row['kategori_adi']) . ' Galerisi',
                'type' => 'text/html',
                'url' => $site_adresi . '/galeri/' . $row['kategori_slug'] . '/' . $dil_param,
                'description' => 'Klinik galeri: ' . trim($row['kategori_adi']),
            ];
        }
    }
} catch (Exception $e) {}

// 9. llms.txt dosyasını da entries'e ekle
$entries[] = [
    'identifier' => 'urn:ai:dribrahimdurandentalclinic.com:knowledge:about',
    'displayName' => 'Klinik Hakkında Detaylı Bilgi',
    'type' => 'text/plain',
    'url' => $site_adresi . '/llms.txt' . $dil_param,
    'description' => 'Klinik, hekim, tedaviler ve iletişim bilgilerini içeren yapılandırılmış bilgi dosyası.',
];

$catalog = [
    'specVersion' => '1.0',
    'host' => [
        'displayName' => 'Prof. Dr. İbrahim Duran - Diş Kliniği',
        'identifier' => 'dribrahimdurandentalclinic.com',
        'url' => $site_adresi,
    ],
    'lastUpdate' => date('c'),
    'totalEntries' => count($entries),
    'entries' => $entries,
];

echo json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);