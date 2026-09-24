<?php
// www/site/sitemap.php
if (!headers_sent()) {
    header('Content-Type: application/xml; charset=utf-8');
    header('X-Robots-Tag: index, follow');
}

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/inc/config.php';

$site_adresi = "adres gir";
$diller = ['tr', 'en'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

// ============================================================
// 1. ANA SAYFA
// ============================================================
foreach ($diller as $dil) {
    echo '<url>' . "\n";
    echo '<loc>' . $site_adresi . ($dil === 'tr' ? '' : '?lang=en') . '</loc>' . "\n";
    echo '<xhtml:link rel="alternate" hreflang="tr" href="' . $site_adresi . '" />' . "\n";
    echo '<xhtml:link rel="alternate" hreflang="en" href="' . $site_adresi . '?lang=en" />' . "\n";
    echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $site_adresi . '" />' . "\n";
    echo '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '<changefreq>daily</changefreq>' . "\n";
    echo '<priority>1.0</priority>' . "\n";
    echo '</url>' . "\n";
}

// ============================================================
// 2. SABİT SAYFALAR
// ============================================================
$sabit_sayfalar = [
    '/tedaviler/' => ['0.9', 'weekly'],
    '/kurumsal/' => ['0.8', 'monthly'],
    '/teknolojiler/' => ['0.7', 'monthly'],
    '/galeri/' => ['0.6', 'monthly'],
    '/blog/' => ['0.8', 'daily'],
    '/iletisim/' => ['0.9', 'monthly'],
    '/kvkk/' => ['0.5', 'monthly'],
];

foreach ($sabit_sayfalar as $konum => $ayarlar) {
    foreach ($diller as $dil) {
        $temiz_konum = rtrim($konum, '/');
        echo '<url>' . "\n";
        echo '<loc>' . $site_adresi . $konum . ($dil === 'tr' ? '' : '?lang=en') . '</loc>' . "\n";
        echo '<xhtml:link rel="alternate" hreflang="tr" href="' . $site_adresi . $konum . '" />' . "\n";
        echo '<xhtml:link rel="alternate" hreflang="en" href="' . $site_adresi . $konum . '?lang=en" />' . "\n";
        echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $site_adresi . $konum . '" />' . "\n";
        echo '<changefreq>' . $ayarlar[1] . '</changefreq>' . "\n";
        echo '<priority>' . $ayarlar[0] . '</priority>' . "\n";
        echo '</url>' . "\n";
    }
}

// ============================================================
// 3. MANUEL TEDAVİLER
// ============================================================
$manuel_tedaviler = [
    'zirkonyum-kaplama',
    'gulus-tasarimi',
    'estetik-kompozit-dolgu',
    'lamine-dis',
    'dis-beyazlatma',
    'pembe-estetik',
    'dis-implant',
    '20-yas-disleri',
    'kemik-grefti',
    'sinus-lifting',
    'cene-cerrahisi',
    'dis-cekimi',
    'porselen-kopru',
    'tam-protez',
    'hassas-tutuculu-protez',
    'implant-ustu-protez',
    'porselen-lamina',
    'inlay-onlay',
    'dis-sikma',
    'cene-eklem-rahatsizliklari',
    'dikissiz-implant',
    'kisa-implant'
];

foreach ($manuel_tedaviler as $slug) {
    foreach ($diller as $dil) {
        echo '<url>' . "\n";
        echo '<loc>' . $site_adresi . '/tedaviler/' . $slug . ($dil === 'tr' ? '' : '?lang=en') . '</loc>' . "\n";
        echo '<xhtml:link rel="alternate" hreflang="tr" href="' . $site_adresi . '/tedaviler/' . $slug . '" />' . "\n";
        echo '<xhtml:link rel="alternate" hreflang="en" href="' . $site_adresi . '/tedaviler/' . $slug . '?lang=en" />' . "\n";
        echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $site_adresi . '/tedaviler/' . $slug . '" />' . "\n";
        echo '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        echo '<changefreq>weekly</changefreq>' . "\n";
        echo '<priority>0.8</priority>' . "\n";
        echo '</url>' . "\n";
    }
}

if (isset($db) && $db) {
    // ============================================================
    // 4. VERİTABANI TEDAVİLERİ
    // ============================================================
    try {
        $sorgu = $db->query("SELECT slug, updated_at FROM tedaviler WHERE silindi = 0 AND aktif = 1 AND slug IS NOT NULL AND slug != '' ORDER BY sira ASC");
        if ($sorgu) {
            while ($row = $sorgu->fetch(PDO::FETCH_ASSOC)) {
                $slug = htmlspecialchars(trim($row['slug']));
                if (in_array($slug, $manuel_tedaviler)) {
                    continue;
                }
                $tarih = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
                
                foreach ($diller as $dil) {
                    echo '<url>' . "\n";
                    echo '<loc>' . $site_adresi . '/tedaviler/' . $slug . ($dil === 'tr' ? '' : '?lang=en') . '</loc>' . "\n";
                    echo '<xhtml:link rel="alternate" hreflang="tr" href="' . $site_adresi . '/tedaviler/' . $slug . '" />' . "\n";
                    echo '<xhtml:link rel="alternate" hreflang="en" href="' . $site_adresi . '/tedaviler/' . $slug . '?lang=en" />' . "\n";
                    echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $site_adresi . '/tedaviler/' . $slug . '" />' . "\n";
                    echo '<lastmod>' . $tarih . '</lastmod>' . "\n";
                    echo '<changefreq>weekly</changefreq>' . "\n";
                    echo '<priority>0.8</priority>' . "\n";
                    echo '</url>' . "\n";
                }
            }
        }
    } catch (Exception $e) {}

    // ============================================================
    // 5. BLOG KATEGORİLERİ
    // ============================================================
    $kategori_slug_map = [];
    try {
        $sorgu_kat = $db->query("SELECT kategori_adi, kategori_slug FROM blog_kategoriler WHERE (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC");
        if ($sorgu_kat) {
            while ($kat = $sorgu_kat->fetch(PDO::FETCH_ASSOC)) {
                $kat_slug = htmlspecialchars(trim($kat['kategori_slug']));
                $kat_adi = trim($kat['kategori_adi']);
                $kategori_slug_map[$kat_adi] = $kat_slug;

                if (!empty($kat_slug)) {
                    foreach ($diller as $dil) {
                        $kat_link = $site_adresi . '/blog/' . $kat_slug;
                        echo '<url>' . "\n";
                        echo '<loc>' . $kat_link . ($dil === 'tr' ? '' : '?lang=en') . '</loc>' . "\n";
                        echo '<xhtml:link rel="alternate" hreflang="tr" href="' . $kat_link . '" />' . "\n";
                        echo '<xhtml:link rel="alternate" hreflang="en" href="' . $kat_link . '?lang=en" />' . "\n";
                        echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $kat_link . '" />' . "\n";
                        echo '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
                        echo '<changefreq>weekly</changefreq>' . "\n";
                        echo '<priority>0.7</priority>' . "\n";
                        echo '</url>' . "\n";
                    }
                }
            }
        }
    } catch (Exception $e) {}

    // ============================================================
    // 6. BLOG YAZILARI
    // ============================================================
    try {
        $sorgu_yazi = $db->query("SELECT baslik, slug, kategori, created_at, updated_at FROM blog_yazilar WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY created_at DESC");
        if ($sorgu_yazi) {
            while ($yazi = $sorgu_yazi->fetch(PDO::FETCH_ASSOC)) {
                $yazi_slug = htmlspecialchars(trim($yazi['slug']));
                $yazi_kat_adi = trim($yazi['kategori']);
                $k_slug = $kategori_slug_map[$yazi_kat_adi] ?? 'genel';
                
                $guncelleme = !empty($yazi['updated_at']) ? $yazi['updated_at'] : $yazi['created_at'];
                $tarih = !empty($guncelleme) ? date('Y-m-d', strtotime($guncelleme)) : date('Y-m-d');

                if (!empty($yazi_slug)) {
                    foreach ($diller as $dil) {
                        $yazi_link = $site_adresi . '/blog/' . $k_slug . '/' . $yazi_slug;
                        echo '<url>' . "\n";
                        echo '<loc>' . $yazi_link . ($dil === 'tr' ? '' : '?lang=en') . '</loc>' . "\n";
                        echo '<xhtml:link rel="alternate" hreflang="tr" href="' . $yazi_link . '" />' . "\n";
                        echo '<xhtml:link rel="alternate" hreflang="en" href="' . $yazi_link . '?lang=en" />' . "\n";
                        echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $yazi_link . '" />' . "\n";
                        echo '<lastmod>' . $tarih . '</lastmod>' . "\n";
                        echo '<changefreq>monthly</changefreq>' . "\n";
                        echo '<priority>0.8</priority>' . "\n";
                        echo '</url>' . "\n";
                    }
                }
            }
        }
    } catch (Exception $e) {}

    // ============================================================
    // 7. DİNAMİK SAYFALAR
    // ============================================================
    try {
        $sorgu = $db->query("SELECT slug, updated_at FROM dinamik_sayfalar WHERE aktif = 1 AND slug IS NOT NULL AND slug != ''");
        if ($sorgu) {
            while ($row = $sorgu->fetch(PDO::FETCH_ASSOC)) {
                $slug = htmlspecialchars(trim($row['slug']));
                $tarih = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
                
                foreach ($diller as $dil) {
                    echo '<url>' . "\n";
                    echo '<loc>' . $site_adresi . '/' . $slug . ($dil === 'tr' ? '' : '?lang=en') . '</loc>' . "\n";
                    echo '<xhtml:link rel="alternate" hreflang="tr" href="' . $site_adresi . '/' . $slug . '" />' . "\n";
                    echo '<xhtml:link rel="alternate" hreflang="en" href="' . $site_adresi . '/' . $slug . '?lang=en" />' . "\n";
                    echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $site_adresi . '/' . $slug . '" />' . "\n";
                    echo '<lastmod>' . $tarih . '</lastmod>' . "\n";
                    echo '<changefreq>monthly</changefreq>' . "\n";
                    echo '<priority>0.6</priority>' . "\n";
                    echo '</url>' . "\n";
                }
            }
        }
    } catch (Exception $e) {}

    // ============================================================
    // 8. GALERİ KATEGORİLERİ (/galeri/kategori-slug/)
    // ============================================================
    $galeri_kategori_slug_map = [];
    try {
        $sorgu_gk = $db->query("SELECT id, kategori_adi, kategori_slug FROM galeri_kategoriler ORDER BY sira ASC");
        if ($sorgu_gk) {
            while ($gk = $sorgu_gk->fetch(PDO::FETCH_ASSOC)) {
                $gk_slug = htmlspecialchars(trim($gk['kategori_slug']));
                $galeri_kategori_slug_map[intval($gk['id'])] = $gk_slug;

                if (!empty($gk_slug)) {
                    $gk_link = $site_adresi . '/galeri/' . $gk_slug . '/';
                    foreach ($diller as $dil) {
                        echo '<url>' . "\n";
                        echo '<loc>' . $gk_link . ($dil === 'tr' ? '' : '?lang=en') . '</loc>' . "\n";
                        echo '<xhtml:link rel="alternate" hreflang="tr" href="' . $gk_link . '" />' . "\n";
                        echo '<xhtml:link rel="alternate" hreflang="en" href="' . $gk_link . '?lang=en" />' . "\n";
                        echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $gk_link . '" />' . "\n";
                        echo '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
                        echo '<changefreq>weekly</changefreq>' . "\n";
                        echo '<priority>0.7</priority>' . "\n";
                        echo '</url>' . "\n";
                    }
                }
            }
        }
    } catch (Exception $e) {}

    // ============================================================
    // 9. GALERİ DETAY SAYFALARI + VIDEO SITEMAP
    // ============================================================
    try {
        $kolon_kontrol = $db->query("SHOW COLUMNS FROM galeri_resimler LIKE 'medya_tipi'")->fetch();
        $medya_tipi_var = (bool)$kolon_kontrol;

        // video_sure kolonu var mı?
        $kolon_sure = $db->query("SHOW COLUMNS FROM galeri_resimler LIKE 'video_sure'")->fetch();
        $sure_var = (bool)$kolon_sure;

        if ($medya_tipi_var) {
            if ($sure_var) {
                $sorgu_gr = $db->query("SELECT id, slug, kategori_id, baslik, aciklama, resim_url, thumbnail_url, video_url, video_sure, medya_tipi, created_at FROM galeri_resimler WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC, created_at DESC");
            } else {
                $sorgu_gr = $db->query("SELECT id, slug, kategori_id, baslik, aciklama, resim_url, thumbnail_url, video_url, NULL AS video_sure, medya_tipi, created_at FROM galeri_resimler WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC, created_at DESC");
            }
        } else {
            $sorgu_gr = $db->query("SELECT id, slug, kategori_id, baslik, aciklama, resim_url, thumbnail_url, NULL AS video_url, NULL AS video_sure, 'resim' AS medya_tipi, created_at FROM galeri_resimler WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC, created_at DESC");
        }

        if ($sorgu_gr) {
            while ($gr = $sorgu_gr->fetch(PDO::FETCH_ASSOC)) {
                $item_slug = htmlspecialchars(trim($gr['slug'] ?? ''));
                $kat_id = intval($gr['kategori_id']);
                $kat_slug = $galeri_kategori_slug_map[$kat_id] ?? '';

                if (empty($item_slug) || empty($kat_slug)) continue;

                $tarih = !empty($gr['created_at']) ? date('Y-m-d', strtotime($gr['created_at'])) : date('Y-m-d');
                $detay_link = $site_adresi . '/galeri/' . $kat_slug . '/' . $item_slug . '/';

                $medya_tipi = $gr['medya_tipi'] ?? 'resim';
                $baslik = htmlspecialchars($gr['baslik'] ?? 'Galeri');
                $aciklama = htmlspecialchars(mb_substr($gr['aciklama'] ?? $gr['baslik'] ?? 'Galeri içeriği', 0, 200));
                $thumbnail = !empty($gr['thumbnail_url']) ? $gr['thumbnail_url'] : ($gr['resim_url'] ?? '');
                if (!empty($thumbnail) && strpos($thumbnail, 'http') !== 0) {
                    $thumbnail = $site_adresi . $thumbnail;
                }
                $video_url = trim($gr['video_url'] ?? '');

                // YouTube video ID çıkart
                $video_id = '';
                if (!empty($video_url) && preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?(?:.*&)?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $video_url, $m)) {
                    $video_id = $m[1];
                }

                // Video süresini DB'den al, saniyeye çevir (fallback: 120 sn)
                $video_duration_sec = 120;
                if ($medya_tipi === 'video' && !empty($gr['video_sure'])) {
                    $sure = $gr['video_sure'];
                    $video_duration_sec = 0;
                    if (preg_match('/(\d+)H/', $sure, $h)) $video_duration_sec += intval($h[1]) * 3600;
                    if (preg_match('/(\d+)M/', $sure, $mi)) $video_duration_sec += intval($mi[1]) * 60;
                    if (preg_match('/(\d+)S/', $sure, $s)) $video_duration_sec += intval($s[1]);
                    if ($video_duration_sec === 0) $video_duration_sec = 120;
                }

                foreach ($diller as $dil) {
                    echo '<url>' . "\n";
                    echo '<loc>' . $detay_link . ($dil === 'tr' ? '' : '?lang=en') . '</loc>' . "\n";
                    echo '<xhtml:link rel="alternate" hreflang="tr" href="' . $detay_link . '" />' . "\n";
                    echo '<xhtml:link rel="alternate" hreflang="en" href="' . $detay_link . '?lang=en" />' . "\n";
                    echo '<xhtml:link rel="alternate" hreflang="x-default" href="' . $detay_link . '" />' . "\n";
                    echo '<lastmod>' . $tarih . '</lastmod>' . "\n";
                    echo '<changefreq>monthly</changefreq>' . "\n";
                    echo '<priority>0.7</priority>' . "\n";

                    // Video bloğu SADECE TR versiyonunda (birincil dil - duplike önleme)
                    if ($dil === 'tr' && $medya_tipi === 'video' && !empty($video_id)) {
                        echo '<video:video>' . "\n";
                        echo '<video:thumbnail_loc>' . htmlspecialchars($thumbnail) . '</video:thumbnail_loc>' . "\n";
                        echo '<video:title>' . $baslik . '</video:title>' . "\n";
                        echo '<video:description>' . $aciklama . '</video:description>' . "\n";
                        echo '<video:player_loc>' . htmlspecialchars('https://www.youtube.com/embed/' . $video_id) . '</video:player_loc>' . "\n";
                        echo '<video:duration>' . $video_duration_sec . '</video:duration>' . "\n";
                        echo '<video:publication_date>' . date('c', strtotime($gr['created_at'])) . '</video:publication_date>' . "\n";
                        echo '<video:family_friendly>yes</video:family_friendly>' . "\n";
                        echo '</video:video>' . "\n";
                    }

                    echo '</url>' . "\n";
                }
            }
        }
    } catch (Exception $e) {}

}

echo '</urlset>';
?>