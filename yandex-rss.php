<?php
header('Content-Type: application/rss+xml; charset=utf-8');
require_once 'inc/config.php';

$site_adresi = "adres gir";
$lang = isset($_GET['lang']) && $_GET['lang'] == 'en' ? 'en' : 'tr';

// Dil bazlı başlık ve açıklama
if ($lang == 'en') {
    $site_title = 'Prof. Dr. İbrahim Duran - Dental Clinic';
    $site_desc = 'Dental treatments with Prof. Dr. İbrahim Duran in Samsun Atakum';
} else {
    $site_title = 'Prof. Dr. İbrahim Duran - Diş Kliniği';
    $site_desc = 'Samsun Atakum\'da Prof. Dr. İbrahim Duran ile diş tedavileri';
}

$rss = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:yandex="http://news.yandex.ru" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
    <title>' . $site_title . '</title>
    <link>' . $site_adresi . '/' . ($lang == 'en' ? '?lang=en' : '') . '</link>
    <description>' . $site_desc . '</description>
    <language>' . ($lang == 'en' ? 'en' : 'tr') . '</language>';

// ============================================================
// 1. STATİK SAYFALAR
// ============================================================
$statik = [
    '' => 'Ana Sayfa',
    'tedaviler' => 'Tedaviler',
    'kurumsal' => 'Kurumsal',
    'teknolojiler' => 'Teknolojiler',
    'galeri' => 'Galeri',
    'blog' => 'Blog',
    'iletisim' => 'İletişim',
    'kvkk' => 'KVKK'
];

foreach ($statik as $slug => $title) {
    $url = $site_adresi . '/' . $slug . ($lang == 'en' ? '?lang=en' : '?lang=tr');
    $rss .= '
    <item>
        <title>' . htmlspecialchars(t_cevir($title)) . '</title>
        <link>' . $url . '</link>
        <description>' . htmlspecialchars(t_cevir($title)) . ' - Prof. Dr. İbrahim Duran</description>
        <pubDate>' . date('D, d M Y H:i:s O') . '</pubDate>
        <guid>' . $url . '</guid>
    </item>';
}

// ============================================================
// 2. TEDAVİLER (t_cevir() ile)
// ============================================================
$sorgu = $db->query("SELECT slug, baslik, kisa_aciklama, updated_at FROM tedaviler WHERE silindi = 0 AND aktif = 1 ORDER BY updated_at DESC");
if ($sorgu) {
    while ($row = $sorgu->fetch()) {
        $title = htmlspecialchars(t_cevir($row['baslik']));
        $desc = htmlspecialchars(strip_tags(t_cevir($row['kisa_aciklama'])));
        
        $url = $site_adresi . '/tedaviler/' . $row['slug'] . ($lang == 'en' ? '?lang=en' : '?lang=tr');
        $date = date('D, d M Y H:i:s O', strtotime($row['updated_at']));
        
        $rss .= '
    <item>
        <title>' . $title . '</title>
        <link>' . $url . '</link>
        <description>' . $desc . '</description>
        <pubDate>' . $date . '</pubDate>
        <guid>' . $url . '</guid>
    </item>';
    }
}

// ============================================================
// 3. BLOG YAZILARI (t_cevir() ile)
// ============================================================
$sorgu = $db->query("SELECT slug, baslik, updated_at FROM blog_yazilar WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY updated_at DESC");
if ($sorgu) {
    while ($row = $sorgu->fetch()) {
        $title = htmlspecialchars(t_cevir($row['baslik']));
        
        $url = $site_adresi . '/blog/' . $row['slug'] . ($lang == 'en' ? '?lang=en' : '?lang=tr');
        $date = date('D, d M Y H:i:s O', strtotime($row['updated_at']));
        
        $rss .= '
    <item>
        <title>' . $title . '</title>
        <link>' . $url . '</link>
        <description>' . $title . ' - Blog</description>
        <pubDate>' . $date . '</pubDate>
        <guid>' . $url . '</guid>
    </item>';
    }
}

$rss .= '
</channel>
</rss>';

echo $rss;
?>