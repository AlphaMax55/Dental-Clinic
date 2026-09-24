<?php
// ============================================================
// 📍 www/galeri-detay.php - GALERİ DETAY (SLUG DESTEKLİ)
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/inc/config.php';

// ========== OTURUM VE DİL ==========
if(session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$lang = $_SESSION['dil'] ?? 'tr';
$dil_en = ($lang === 'en');

// ========== SLUG ÜRETİCİ ==========
if (!function_exists('galeri_slug_yap')) {
    function galeri_slug_yap($metin) {
        if (empty($metin)) return '';
        $tr = ['ç','ğ','ı','İ','ö','ş','ü','Ç','Ğ','Ö','Ş','Ü','â','î','û'];
        $en = ['c','g','i','i','o','s','u','c','g','o','s','u','a','i','u'];
        $metin = str_replace($tr, $en, $metin);
        $metin = mb_strtolower($metin, 'UTF-8');
        $metin = preg_replace('/[^a-z0-9]+/', '-', $metin);
        $metin = preg_replace('/-+/', '-', $metin);
        $metin = trim($metin, '-');
        return mb_substr($metin, 0, 180);
    }
}

// ========== OTOMATİK ÇEVİRİ ==========
if (!function_exists('galeri_cevir')) {
    function galeri_cevir($metin, $lang = null) {
        if ($lang === null) $lang = $_SESSION['dil'] ?? 'tr';
        if ($lang !== 'en' || empty($metin) || !is_string($metin)) return $metin;
        $metin = trim($metin);
        if ($metin === '' || mb_strlen($metin) < 2) return $metin;

        if (function_exists('t_cevir')) {
            $r = t_cevir($metin);
            if ($r !== $metin && !empty($r)) return $r;
        }

        global $db;
        if (!isset($db)) return $metin;

        $hash_md5 = md5($metin);
        $hash_sha = hash('sha256', $metin);
        try {
            $stmt = $db->prepare("SELECT ingilizce_metin FROM site_cevirileri WHERE metin_hash IN (?, ?) LIMIT 1");
            $stmt->execute([$hash_md5, $hash_sha]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['ingilizce_metin'])) return $row['ingilizce_metin'];
        } catch (Exception $e) {}

        $apiKey = 'AIzaSyDcCc3vch1eAK-eftWETMbNBIfMt9G4YG8';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://translation.googleapis.com/language/translate/v2',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['q'=>$metin,'source'=>'tr','target'=>'en','format'=>'text','key'=>$apiKey]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 6
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp && $code === 200) {
            $data = json_decode($resp, true);
            if (isset($data['data']['translations'][0]['translatedText'])) {
                $ceviri = html_entity_decode($data['data']['translations'][0]['translatedText'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                try {
                    $stmt = $db->prepare("INSERT INTO site_cevirileri (metin_hash, turkce_metin, ingilizce_metin, kayit_tarihi) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE ingilizce_metin = VALUES(ingilizce_metin)");
                    $stmt->execute([$hash_md5, $metin, $ceviri]);
                } catch (Exception $e) {}
                return $ceviri;
            }
        }
        return $metin;
    }
}

// ========== VİDEO ÇÖZ ==========
if (!function_exists('galeri_video_coz')) {
    function galeri_video_coz(?string $url): ?array {
        $url = trim((string)$url);
        if ($url === '') return null;
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?(?:.*&)?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return [
                'tip' => 'youtube', 'id' => $m[1],
                'embed' => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0&vq=hd720',
                'thumb' => 'https://img.youtube.com/vi/' . $m[1] . '/maxresdefault.jpg',
                'thumb_yedek' => 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg',
            ];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return ['tip'=>'vimeo','id'=>$m[1],'embed'=>'https://player.vimeo.com/video/'.$m[1].'?autoplay=1','thumb'=>'','thumb_yedek'=>''];
        }
        if (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $url)) {
            return ['tip'=>'dosya','src'=>$url];
        }
        return null;
    }
}

global $page_title, $seo_description, $site_keywords, $dynamic_og_title, $og_image, $mevcut_canonical_link;

// ========== PARAMETRE ALGILAMA (Hem ID hem slug) ==========
$galeri_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$slug_param = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$kat_slug_param = isset($_GET['k']) ? trim($_GET['k']) : '';

// Pathinfo desteği (temiz URL)
if (empty($slug_param) && !empty($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/galeri/([^/?]+)/([^/?]+)#', $_SERVER['REQUEST_URI'], $m) && !isset($m[3])) {
        $kat_slug_param = $m[1];
        $slug_param = $m[2];
    }
}

$item_raw = null;

// Önce slug ile dene
if (!empty($slug_param)) {
    try {
        $stmt = $db->prepare("SELECT * FROM galeri_resimler WHERE slug = ? AND durum = 1 AND (silindi = 0 OR silindi IS NULL) LIMIT 1");
        $stmt->execute([$slug_param]);
        $item_raw = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Slug ile bulunamazsa ID ile dene
if (!$item_raw && $galeri_id > 0) {
    $stmt = $db->prepare("SELECT * FROM galeri_resimler WHERE id = ? AND durum = 1 AND (silindi = 0 OR silindi IS NULL) LIMIT 1");
    $stmt->execute([$galeri_id]);
    $item_raw = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$item_raw) {
    header("HTTP/1.0 404 Not Found");
    if (file_exists(__DIR__ . '/404.php')) {
        include __DIR__ . '/404.php';
    } else {
        echo '<div style="text-align:center; padding:100px 20px; font-family:sans-serif;"><h1>404 - Galeri Kaydı Bulunamadı</h1><p>Aradığınız kayıt mevcut değil veya yayından kaldırılmış.</p><a href="/galeri/">Galeriye Dön</a></div>';
    }
    exit;
}

$galeri_id = intval($item_raw['id']);

// Slug'ı yoksa otomatik üret ve kaydet
if (empty($item_raw['slug'])) {
    $yeni_slug = galeri_slug_yap($item_raw['baslik']);
    try {
        $kontrol = $db->prepare("SELECT COUNT(*) FROM galeri_resimler WHERE slug = ? AND id != ?");
        $kontrol->execute([$yeni_slug, $galeri_id]);
        if ($kontrol->fetchColumn() > 0) $yeni_slug .= '-' . $galeri_id;
        
        $db->prepare("UPDATE galeri_resimler SET slug = ? WHERE id = ?")->execute([$yeni_slug, $galeri_id]);
        $item_raw['slug'] = $yeni_slug;
    } catch (Exception $e) {
        $item_raw['slug'] = $galeri_id;
    }
}

// Görüntülenme artır
try { $db->prepare("UPDATE galeri_resimler SET goruntulenme = COALESCE(goruntulenme,0) + 1 WHERE id = ?")->execute([$galeri_id]); } catch (Exception $e) {}

// ========== DİL DESTEKLİ ALANLAR ==========
$baslik = galeri_cevir($item_raw['baslik'] ?? '', $lang);
$aciklama = galeri_cevir($item_raw['aciklama'] ?? '', $lang);

// ========== MEDYA ==========
$medya_tipi = $item_raw['medya_tipi'] ?? 'resim';
if (!in_array($medya_tipi, ['resim', 'video'])) $medya_tipi = 'resim';
$video_data = ($medya_tipi === 'video') ? galeri_video_coz($item_raw['video_url'] ?? '') : null;

$gorsel_src = !empty($item_raw['thumbnail_url']) ? $item_raw['thumbnail_url'] : ($item_raw['resim_url'] ?? '');
if ($medya_tipi === 'video' && empty($gorsel_src) && !empty($video_data['thumb'])) {
    $gorsel_src = $video_data['thumb'];
}

// ========== KATEGORİ ==========
$kategoriler_db = [];
$kategori_isim_map = [];
$kategori_slug_map = [];
try {
    $kategoriler_db = $db->query("SELECT id, kategori_adi, kategori_slug FROM galeri_kategoriler ORDER BY sira ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($kategoriler_db as $kat) {
        $kategori_isim_map[$kat['id']] = trim($kat['kategori_adi']);
        $slug = trim($kat['kategori_slug']);
        if (empty($slug)) {
            $slug = galeri_slug_yap($kat['kategori_adi']);
            try { $db->prepare("UPDATE galeri_kategoriler SET kategori_slug = ? WHERE id = ?")->execute([$slug, $kat['id']]); } catch (Exception $e) {}
        }
        $kategori_slug_map[$kat['id']] = $slug;
    }
} catch (Exception $e) {}

$kategori_adi_raw = $kategori_isim_map[$item_raw['kategori_id']] ?? '';
$kategori_label = galeri_cevir($kategori_adi_raw, $lang);
$aktif_kategori_slug = $kategori_slug_map[$item_raw['kategori_id']] ?? 'vaka';

// ========== TÜM GALERİLER (Sidebar için) ==========
$tum_galeriler = [];
try {
    $tum_galeriler = $db->query("SELECT id, baslik, slug, aciklama, kategori_id, thumbnail_url, resim_url, medya_tipi, tarih, begeni FROM galeri_resimler WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Kategori sayıları
$kategori_sayilari = [];
foreach ($tum_galeriler as $g) {
    $kid = intval($g['kategori_id']);
    if (!isset($kategori_sayilari[$kid])) $kategori_sayilari[$kid] = 0;
    $kategori_sayilari[$kid]++;
}

// Popüler
$populer_galeriler = $tum_galeriler;
usort($populer_galeriler, function($a, $b) { return ($b['begeni'] ?? 0) - ($a['begeni'] ?? 0); });
$populer_galeriler = array_slice($populer_galeriler, 0, 4);

// Benzer galeriler
$benzerler = [];
try {
    $stmt = $db->prepare("SELECT id, baslik, slug, aciklama, thumbnail_url, resim_url, medya_tipi, tarih FROM galeri_resimler WHERE kategori_id = ? AND id != ? AND durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY RAND() LIMIT 3");
    $stmt->execute([$item_raw['kategori_id'], $galeri_id]);
    $benzerler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// SSS
$sss_list = [];
try {
    $sss_list = $db->query("SELECT * FROM blog_sss WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ========== TEMİZ URL OLUŞTUR ==========
$base_url = 'adres gir';
$canonical_slug = !empty($item_raw['slug']) ? $item_raw['slug'] : $galeri_id;
$temiz_url = '/galeri/' . $aktif_kategori_slug . '/' . $canonical_slug . '/';
$tam_url = $base_url . $temiz_url;

// ========== SEO ==========
$baslik_temiz = preg_replace('/\s*\|\s*Prof\.\s*Dr\.\s*İbrahim\s*Duran\s*$/iu', '', $baslik);
$page_title = $baslik_temiz . ' | Galeri | Prof. Dr. İbrahim Duran';
$seo_description = mb_substr(strip_tags($aciklama), 0, 160);
$site_keywords = 'galeri, ' . mb_strtolower($kategori_label) . ', samsun diş, dr ibrahim duran';

$og_image = $gorsel_src ?: $base_url . '/ibrahimduran.png';
if (!empty($gorsel_src) && strpos($gorsel_src, 'http') !== 0) {
    $og_image = $base_url . '/' . ltrim($gorsel_src, '/');
}
$dynamic_og_title = $page_title;

$mevcut_canonical_link = $tam_url;
if ($dil_en) $mevcut_canonical_link .= '?lang=en';

$robots_etiketi_icerigi = '<meta name="robots" content="index, follow">';

$disable_site_title_suffix = true;
include __DIR__ . '/inc/header.php';
?>

<style>
.kategori-btn:hover { background-color: inherit !important; color: inherit !important; border-color: inherit !important; }
.kategori-btn.bg-blue-600:hover { background-color: #2563eb !important; color: white !important; }
.kategori-btn.bg-slate-50:hover { background-color: #f1f5f9 !important; color: #1e293b !important; border-color: #cbd5e1 !important; }
.kategori-btn { transition: all 0.2s ease-in-out !important; }
</style>

<!-- SCHEMA.ORG - ZENGİN YAPI -->
<?php
// ============================================================
// YouTube / Vimeo video ID çıkart
// ============================================================
$video_embed_url = '';
$youtube_id = '';
$vimeo_id = '';
if ($medya_tipi === 'video' && !empty($item_raw['video_url'])) {
    if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?(?:.*&)?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $item_raw['video_url'], $m)) {
        $youtube_id = $m[1];
        $video_embed_url = 'https://www.youtube.com/embed/' . $youtube_id;
    } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $item_raw['video_url'], $m)) {
        $vimeo_id = $m[1];
        $video_embed_url = 'https://player.vimeo.com/video/' . $vimeo_id;
    }
}

// ============================================================
// Thumbnail absolute URL
// ============================================================
$schema_thumb = $og_image;
if (!empty($schema_thumb) && strpos($schema_thumb, 'http') !== 0) {
    $schema_thumb = $base_url . '/' . ltrim($schema_thumb, '/');
}

// ============================================================
// Upload date — çoklu fallback (created_at → tarih → now)
// Boş string gelirse strtotime("") false döner ve 1970-01-01 üretir; bunu engelliyoruz.
// ============================================================
$upload_raw = $item_raw['created_at'] ?? null;
if (empty($upload_raw)) $upload_raw = $item_raw['tarih'] ?? null;
$upload_ts = !empty($upload_raw) ? strtotime((string)$upload_raw) : false;
if (!$upload_ts) $upload_ts = time();
$schema_upload_date = date('c', $upload_ts);

// ============================================================
// Ana görsel absolute URL
// ============================================================
$main_image_url = $item_raw['resim_url'] ?? $gorsel_src;
if (!empty($main_image_url) && strpos($main_image_url, 'http') !== 0) {
    $main_image_url = $base_url . '/' . ltrim($main_image_url, '/');
}

$tum_schemalar = [];

// ---------- 1) MedicalWebPage ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'MedicalWebPage',
    '@id' => $tam_url . '#webpage',
    'url' => $tam_url,
    'name' => $page_title,
    'description' => $seo_description,
    'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
    'isPartOf' => [
        '@id' => $base_url . '/#website'
    ],
    'about' => [
        '@id' => $base_url . '/#medicalbusiness'
    ],
    'publisher' => [
        '@id' => $base_url . '/#medicalbusiness'
    ],
    'breadcrumb' => [
        '@id' => $tam_url . '#breadcrumb'
    ],
    'primaryImageOfPage' => [
        '@type' => 'ImageObject',
        'url' => $schema_thumb
    ],
    'audience' => [
        '@type' => 'PeopleAudience',
        'geographicArea' => [
            '@type' => 'City',
            'name' => 'Samsun'
        ]
    ]
];

// ---------- 2) Ana İçerik: VideoObject veya ImageObject ----------
if ($medya_tipi === 'video' && !empty($video_embed_url)) {
    $video_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        '@id' => $tam_url . '#video',
        'name' => $baslik,
        'description' => $seo_description,
        'thumbnailUrl' => $schema_thumb,
        'uploadDate' => $schema_upload_date,
        'embedUrl' => $video_embed_url,
        'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
        'isFamilyFriendly' => true,
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Prof. Dr. İbrahim Duran - Diş Kliniği',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $base_url . '/uploads/genel/genel_1779669534.png',
                'width' => 512,
                'height' => 512
            ]
        ]
    ];

    // contentUrl (YouTube / Vimeo)
    if (!empty($youtube_id)) {
        $video_schema['contentUrl'] = 'https://www.youtube.com/watch?v=' . $youtube_id;
    } elseif (!empty($vimeo_id)) {
        $video_schema['contentUrl'] = 'https://vimeo.com/' . $vimeo_id;
    }

    // Süre (varsa)
    if (!empty($item_raw['video_sure'])) {
        $video_schema['duration'] = $item_raw['video_sure'];
    }

    $tum_schemalar[] = $video_schema;

} elseif ($medya_tipi === 'video' && !empty($item_raw['video_url']) && preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $item_raw['video_url'])) {
    // Doğrudan dosya video (mp4/webm/ogg)
    $video_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        '@id' => $tam_url . '#video',
        'name' => $baslik,
        'description' => $seo_description,
        'thumbnailUrl' => $schema_thumb,
        'uploadDate' => $schema_upload_date,
        'contentUrl' => $item_raw['video_url'],
        'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
        'isFamilyFriendly' => true,
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Prof. Dr. İbrahim Duran - Diş Kliniği',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $base_url . '/uploads/genel/genel_1779669534.png',
                'width' => 512,
                'height' => 512
            ]
        ]
    ];
    if (!empty($item_raw['video_sure'])) {
        $video_schema['duration'] = $item_raw['video_sure'];
    }
    $tum_schemalar[] = $video_schema;

} else {
    // Görsel içerik
    $tum_schemalar[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ImageObject',
        '@id' => $tam_url . '#image',
        'name' => $baslik,
        'description' => $seo_description,
        'contentUrl' => $main_image_url,
        'thumbnailUrl' => $schema_thumb,
        'uploadDate' => $schema_upload_date,
        'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
        'creator' => [
            '@id' => $base_url . '/#medicalbusiness'
        ],
        'about' => [
            '@type' => 'MedicalCondition',
            'name' => $kategori_label ?: 'Diş Tedavisi'
        ]
    ];
}

// ---------- 3) BreadcrumbList ----------
$breadcrumb_items = [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => $dil_en ? 'Home' : 'Anasayfa',
        'item' => $base_url . '/'
    ],
    [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $dil_en ? 'Gallery' : 'Galeri',
        'item' => $base_url . '/galeri/'
    ]
];
if (!empty($kategori_label)) {
    $breadcrumb_items[] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $kategori_label,
        'item' => $base_url . '/galeri/' . $aktif_kategori_slug . '/'
    ];
    $breadcrumb_items[] = [
        '@type' => 'ListItem',
        'position' => 4,
        'name' => $baslik,
        'item' => $tam_url
    ];
} else {
    $breadcrumb_items[] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $baslik,
        'item' => $tam_url
    ];
}
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    '@id' => $tam_url . '#breadcrumb',
    'itemListElement' => $breadcrumb_items
];

// ---------- 4) Benzer Vakalar ItemList ----------
// ⚠️ ItemList içinde VideoObject/ImageObject tanımlamak Google'ın zengin sonuç
// doğrulayıcısını tetikliyor. Item'lar eksik alanla kalınca "uploadDate eksik"
// gibi KRİTİK hatalar üretiyor. Bu yüzden nötr CreativeWork kullanıyoruz —
// Google detayları hedef sayfadan zaten çekecek.
if (!empty($benzerler)) {
    $benzer_items = [];
    foreach ($benzerler as $idx => $b) {
        $b_baslik = galeri_cevir($b['baslik'] ?? '', $lang);
        $b_slug = !empty($b['slug']) ? $b['slug'] : $b['id'];
        $b_kat_slug = $kategori_slug_map[$item_raw['kategori_id']] ?? 'vaka';
        $b_url = $base_url . '/galeri/' . $b_kat_slug . '/' . $b_slug . '/';
        $b_img = !empty($b['thumbnail_url']) ? $b['thumbnail_url'] : ($b['resim_url'] ?? '');
        if (!empty($b_img) && strpos($b_img, 'http') !== 0) {
            $b_img = $base_url . '/' . ltrim($b_img, '/');
        }

        $b_item = [
            '@type' => 'CreativeWork',
            'name' => $b_baslik,
            'url' => $b_url,
            'description' => mb_substr(strip_tags(galeri_cevir($b['aciklama'] ?? '', $lang)), 0, 200)
        ];
        if (!empty($b_img)) {
            $b_item['image'] = $b_img;
        }

        $benzer_items[] = [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'item' => $b_item
        ];
    }
    $tum_schemalar[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        '@id' => $tam_url . '#similar',
        'name' => $dil_en ? 'Similar Cases' : 'Benzer Vakalar',
        'numberOfItems' => count($benzer_items),
        'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
        'itemListElement' => $benzer_items
    ];
}

// ---------- 5) FAQPage ----------
if (!empty($sss_list)) {
    $faq_items = [];
    $gorulen = [];
    foreach ($sss_list as $s) {
        $soru = function_exists('t_cevir') ? t_cevir($s['soru']) : $s['soru'];
        $cevap = function_exists('t_cevir') ? t_cevir($s['cevap']) : $s['cevap'];
        $anahtar = mb_strtolower(trim($soru), 'UTF-8');
        if (in_array($anahtar, $gorulen)) continue;
        $gorulen[] = $anahtar;
        if (empty($soru) || empty($cevap)) continue;

        $faq_items[] = [
            '@type' => 'Question',
            'name' => strip_tags($soru),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => strip_tags($cevap)
            ]
        ];
    }
    if (!empty($faq_items)) {
        $tum_schemalar[] = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => $tam_url . '#faq',
            'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
            'mainEntity' => $faq_items
        ];
    }
}

// ---------- ÇIKTI: Pretty Print + Alt Alta ----------
foreach ($tum_schemalar as $schema) {
    echo '<script type="application/ld+json">' . "\n"
       . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
       . "\n" . '</script>' . "\n\n";
}
?>
<main class="min-h-screen bg-white w-full font-sans selection:bg-cyan-100 selection:text-cyan-900">

<!-- HEADER & ARAMA -->
<section style="background: linear-gradient(180deg, #020617 0%, #0f172a 100%); color: #ffffff; padding: 42px 20px 55px 20px; position: relative; overflow: visible; border-bottom: 1px solid #1e293b;">
    <div style="max-width: 850px; margin: 0 auto; text-align: center; position: relative; z-index: 10;">

        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 16px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 9999px; color: #60a5fa; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 14px;">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: #38bdf8;"></span>
            <?php echo t_cevir('Klinik Vakalar & Galeri'); ?>
        </div>

        <h1 style="font-size: clamp(26px, 3.5vw, 38px); font-weight: 900; color: #ffffff; margin: 0 0 10px 0; line-height: 1.2;">
            <?php echo t_cevir('Gülüş Galerisi'); ?> <span style="color: #38bdf8;"><?php echo t_cevir('& Tedavi Sonuçları'); ?></span>
        </h1>

        <p style="color: #94a3b8; font-size: 15px; max-width: 520px; margin: 0 auto 24px auto; line-height: 1.5;">
            <?php echo t_cevir('Öncesi sonrası, klinik vakalar ve tedavi videolarını aşağıdan inceleyebilirsiniz.'); ?>
        </p>

        <div style="position: relative; max-width: 620px; margin: 0 auto; text-align: left;">
            <form action="/galeri/" method="GET" style="display: flex; align-items: center; background: #1e293b; border: 2px solid #334155; border-radius: 15px; padding: 7px 14px; box-shadow: 0 12px 35px -5px rgba(0,0,0,0.5);">
                <div style="color: #38bdf8; display: flex; align-items: center; padding-left: 4px; padding-right: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <input type="text" name="q" placeholder="<?php echo t_cevir('Vaka, tedavi veya yöntem arayın...'); ?>" style="width: 100%; background: transparent; border: none; outline: none; color: #ffffff; font-size: 16px; font-weight: 500; padding: 7px 0;" autocomplete="off">
                <button type="submit" style="background: #2563eb; color: #fff; border: none; border-radius: 8px; padding: 7px 16px; font-weight: 700; font-size: 12px; cursor: pointer;">
                    <?php echo t_cevir('Ara'); ?>
                </button>
            </form>
        </div>

    </div>
</section>

<!-- MAIN CONTENT -->
<div class="w-full mx-auto py-10" style="padding-left: clamp(20px, 4vw, 50px); padding-right: clamp(20px, 4vw, 50px); max-width: 1750px;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- SOL SIDEBAR -->
        <aside class="hidden lg:block lg:col-span-3 space-y-6">

            <!-- KATEGORİLER -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-xl border border-slate-200/80">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        <?php echo t_cevir('KATEGORİLER'); ?>
                    </h3>
                    <span class="text-[11px] font-bold text-slate-400 bg-slate-100 px-2.5 py-0.5 rounded-md">
                        <?php echo count($kategoriler_db) + 1; ?>
                    </span>
                </div>

                <div class="space-y-2">
                    <a href="/galeri/" class="kategori-btn w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100">
                        <span class="flex items-center gap-2.5 min-w-0">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0 text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            </span>
                            <span class="truncate"><?php echo t_cevir('TÜM VAKALAR'); ?></span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 bg-white text-slate-700 border border-slate-200">
                            <?php echo count($tum_galeriler); ?>
                        </span>
                    </a>

                    <?php foreach($kategoriler_db as $kat):
                        $kat_adi = trim($kat['kategori_adi']);
                        $kat_slug = $kategori_slug_map[$kat['id']] ?? 'vaka';
                        $kat_label = galeri_cevir($kat_adi, $lang);
                        $is_active = (intval($item_raw['kategori_id']) === intval($kat['id']));
                        $kat_sayac = $kategori_sayilari[$kat['id']] ?? 0;
                    ?>
                    <a href="/galeri/<?php echo htmlspecialchars($kat_slug); ?>/" class="kategori-btn w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border <?php echo $is_active ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/25 ring-2 ring-blue-600/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'; ?>">
                        <span class="flex items-center gap-2.5 min-w-0">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0 <?php echo $is_active ? 'text-white' : 'text-blue-600'; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                            </span>
                            <span class="truncate"><?php echo mb_strtoupper(t_cevir($kat_label), 'UTF-8'); ?></span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 <?php echo $is_active ? 'bg-white/20 text-white' : 'bg-white text-slate-700 border border-slate-200'; ?>">
                            <?php echo $kat_sayac; ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- POPÜLER GALERİLER -->
            <?php if(!empty($populer_galeriler)): ?>
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-xl border border-slate-200/80">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue-600"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                    <?php echo t_cevir('POPÜLER VAKALAR'); ?>
                </h3>
                <div class="space-y-3">
                    <?php foreach($populer_galeriler as $index => $post):
                        $pop_baslik = galeri_cevir($post['baslik'] ?? '', $lang);
                        $pop_slug = !empty($post['slug']) ? $post['slug'] : $post['id'];
                        $pop_kat_slug = $kategori_slug_map[$post['kategori_id']] ?? 'vaka';
                        $pop_link = '/galeri/' . $pop_kat_slug . '/' . $pop_slug . '/';
                    ?>
                    <a href="<?php echo $pop_link; ?>" class="flex items-center gap-3 group p-2.5 rounded-2xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-md shrink-0">
                            <?php echo $index+1; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 group-hover:text-blue-600 line-clamp-2 transition-colors leading-snug">
                                <?php echo htmlspecialchars($pop_baslik); ?>
                            </h4>
                            <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-1">
                                <span class="flex items-center gap-1">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                    <?php echo number_format($post['begeni'] ?? 0); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- BÜLTEN -->
            <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-blue-800 rounded-3xl p-5 sm:p-6 shadow-xl text-white relative overflow-hidden">
                <div class="w-10 h-10 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <h3 class="font-extrabold text-base mb-1"><?php echo t_cevir('Bültenimize Katılın'); ?></h3>
                <p class="text-blue-100 text-xs mb-4 leading-relaxed"><?php echo t_cevir('Yeni vakalar anında e-postanıza gelsin.'); ?></p>
                <form action="/api/blog-abone.php" method="POST" class="space-y-2.5">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="email" name="email" required placeholder="<?php echo t_cevir('E-posta adresiniz'); ?>" class="w-full px-3.5 py-2.5 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder:text-white/60 text-xs focus:outline-none focus:ring-2 focus:ring-white/40 transition-all">
                    <button type="submit" class="w-full py-2.5 bg-white text-blue-700 font-bold text-xs rounded-xl hover:bg-blue-50 transition-colors shadow-md"><?php echo t_cevir('Abone Ol'); ?></button>
                </form>
            </div>
        </aside>

        <!-- SAĞ ANA ALAN -->
        <section class="lg:col-span-9 space-y-8">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-xs md:text-sm text-slate-500 font-medium flex-wrap">
                <a href="/galeri/" class="hover:text-blue-600 transition"><?php echo t_cevir('Galeri'); ?></a>
                <?php if(!empty($kategori_label)): ?>
                <span>/</span>
                <a href="/galeri/<?php echo htmlspecialchars($aktif_kategori_slug); ?>/" class="hover:text-blue-600 font-semibold text-blue-600 transition">
                    <?php echo htmlspecialchars($kategori_label); ?>
                </a>
                <?php endif; ?>
                <span>/</span>
                <span class="text-slate-800 truncate max-w-[300px]"><?php echo htmlspecialchars($baslik); ?></span>
            </nav>

            <!-- ANA KART -->
            <article class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200 shadow-sm space-y-8">

                <!-- Başlık + Meta -->
                <header class="space-y-4">
                    <a href="/galeri/<?php echo htmlspecialchars($aktif_kategori_slug); ?>/" class="mr-10 inline-flex items-center gap-2 px-6 py-3 bg-white hover:bg-slate-100 text-slate-700 text-sm font-bold rounded-xl border border-slate-200 transition-all">
                        ← <?php echo htmlspecialchars($kategori_label); ?> <?php echo t_cevir('Vakaları'); ?>
                    </a>

                    <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black text-slate-900 leading-tight tracking-tight">
                        <?php echo htmlspecialchars($baslik); ?>
                    </h1>

                    <div class="flex flex-wrap items-center gap-4 text-xs md:text-sm text-slate-500 border-b border-slate-100 pb-6">
                        <span class="font-bold text-slate-800 flex items-center gap-1.5">
                            🦷 <?php echo htmlspecialchars($kategori_label ?: 'Vaka'); ?>
                        </span>
                        <?php if(!empty($item_raw['tarih'])): ?>
                        <span>•</span>
                        <span>📅 <?php echo htmlspecialchars($item_raw['tarih']); ?></span>
                        <?php endif; ?>
                        <span>•</span>
                        <span class="text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-md font-bold">
                            <?php echo $medya_tipi === 'video' ? '🎬 Video' : '📷 Fotoğraf'; ?>
                        </span>
                        <span>•</span>
                        <span>❤️ <?php echo number_format($item_raw['begeni'] ?? 0); ?> <?php echo t_cevir('beğeni'); ?></span>
                    </div>
                </header>

                <!-- MEDYA -->
                <div style="width: 100%; border-radius: 1.5rem; overflow: hidden; background: #000; border: 1px solid #e2e8f0; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.2);">
                    <?php if($medya_tipi === 'video' && $video_data): ?>
                        <?php if(($video_data['tip'] ?? '') === 'dosya' && !empty($video_data['src'])): ?>
                            <video controls playsinline preload="metadata"
                                   style="display:block; width: 100%; aspect-ratio: 16 / 9; background: #000; object-fit: contain;"
                                   poster="<?php echo htmlspecialchars($gorsel_src); ?>">
                                <source src="<?php echo htmlspecialchars($video_data['src']); ?>" type="video/mp4">
                            </video>
                        <?php else: ?>
                            <div style="position: relative; width: 100%; aspect-ratio: 16 / 9; background: #000;">
                                <iframe src="<?php echo htmlspecialchars($video_data['embed']); ?>"
                                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen loading="lazy"></iframe>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($item_raw['resim_url'] ?: $gorsel_src); ?>"
                             alt="<?php echo htmlspecialchars($baslik); ?>"
                             style="display:block; width: 100%; height: auto; max-height: 85vh; object-fit: contain; background: #000;">
                    <?php endif; ?>
                </div>

                <!-- AÇIKLAMA -->
                <?php if(!empty($aciklama)): ?>
                    <div class="bg-blue-50/80 border-l-4 border-blue-600 p-6 rounded-r-2xl text-slate-800 font-medium text-base md:text-lg leading-relaxed shadow-xs">
                        <?php echo nl2br(htmlspecialchars($aciklama)); ?>
                    </div>
                <?php endif; ?>

                <!-- İLETİŞİM / GERİ DÖN -->
                <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50 p-6 rounded-2xl border">
                    <a href="/galeri/" class="inline-flex items-center gap-2 px-6 py-3 bg-white hover:bg-slate-100 text-slate-700 text-sm font-bold rounded-xl border border-slate-200 transition-all">
                        ← <?php echo t_cevir('Galeriye Dön'); ?>
                    </a>
                    <a href="/iletisim/" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-extrabold rounded-xl transition-all shadow-md shadow-blue-500/25">
                        📅 <?php echo t_cevir('Hemen İletişime Geç'); ?>
                    </a>
                </div>
            </article>

            <!-- BENZER GALERİLER -->
            <?php if(count($benzerler) > 0): ?>
            <div class="pt-10 border-t border-slate-200 mt-10">
                <h3 class="text-2xl font-black text-slate-900 mb-6"><?php echo t_cevir('Benzer Vakalar'); ?></h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($benzerler as $benzer):
                        $benzer_baslik = galeri_cevir($benzer['baslik'] ?? '', $lang);
                        $benzer_aciklama = galeri_cevir($benzer['aciklama'] ?? '', $lang);
                        $benzer_img = !empty($benzer['thumbnail_url']) ? $benzer['thumbnail_url'] : $benzer['resim_url'];
                        $b_is_video = ($benzer['medya_tipi'] === 'video');
                        $b_slug = !empty($benzer['slug']) ? $benzer['slug'] : $benzer['id'];
                        $b_kat_slug = $kategori_slug_map[$item_raw['kategori_id']] ?? 'vaka';
                        $b_link = '/galeri/' . $b_kat_slug . '/' . $b_slug . '/';
                    ?>
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col group">
                        <a href="<?php echo $b_link; ?>" class="block h-48 overflow-hidden bg-slate-100 relative">
                            <?php if(!empty($benzer_img)): ?>
                                <img src="<?php echo htmlspecialchars($benzer_img); ?>"
                                     alt="<?php echo htmlspecialchars($benzer_baslik); ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-900 to-slate-700 text-white/40">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                </div>
                            <?php endif; ?>
                            <?php if($b_is_video): ?>
                            <span class="absolute top-2 left-2 inline-flex items-center gap-1 px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-wider bg-red-600 text-white shadow-md">
                                🎬 Video
                            </span>
                            <?php endif; ?>
                        </a>
                        <div class="p-5 flex-1 flex flex-col">
                            <h4 class="font-bold text-slate-900 text-base line-clamp-2 group-hover:text-blue-600 transition leading-snug mb-2">
                                <a href="<?php echo $b_link; ?>">
                                    <?php echo htmlspecialchars($benzer_baslik); ?>
                                </a>
                            </h4>
                            <?php if(!empty($benzer_aciklama)): ?>
                                <p class="text-sm text-slate-500 line-clamp-2 flex-1">
                                    <?php echo htmlspecialchars(mb_substr($benzer_aciklama, 0, 100)) . '...'; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="px-5 pb-5 pt-0">
                            <a href="<?php echo $b_link; ?>"
                               class="text-xs font-extrabold text-blue-600 hover:text-blue-800 inline-flex items-center gap-1 transition">
                                <?php echo t_cevir('İncele'); ?> →
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </section>
    </div>
</div>

<!-- SSS -->
<?php if(!empty($sss_list)): ?>
<section class="bg-gradient-to-b from-slate-50 via-blue-50/30 to-slate-100/70 py-20 border-t border-slate-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-100/80 border border-blue-200 rounded-full text-blue-700 text-xs font-black uppercase tracking-wider mb-3 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                KLİNİK REHBERİ
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight"><?php echo t_cevir('Merak Edilenler'); ?></h2>
            <p class="text-slate-500 text-sm md:text-base mt-2 max-w-xl mx-auto"><?php echo t_cevir('Tedavi süreçleri ve uygulamalar hakkında sıkça sorulan yanıtlar'); ?></p>
        </div>

        <?php
        $tekil_sss = [];
        $gorulen_sorular = [];
        foreach($sss_list as $s) {
            $soru_anahtar = mb_strtolower(trim($s['soru']), 'UTF-8');
            if(!in_array($soru_anahtar, $gorulen_sorular)) {
                $gorulen_sorular[] = $soru_anahtar;
                $tekil_sss[] = $s;
            }
        }
        $sol = []; $sag = [];
        foreach($tekil_sss as $i => $s) {
            if($i % 2 == 0) $sol[] = $s; else $sag[] = $s;
        }
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div class="space-y-4">
                <?php foreach($sol as $sss): ?>
                <div class="faq-item bg-white rounded-2xl border border-slate-200 shadow-sm transition-all duration-300 hover:shadow-md hover:border-blue-300 overflow-hidden">
                    <button type="button" class="faq-question w-full flex items-center justify-between p-5 md:p-6 text-left cursor-pointer">
                        <span class="faq-baslik font-extrabold text-slate-800 text-base md:text-lg transition-colors leading-snug pr-4">
                            <?php echo htmlspecialchars(t_cevir($sss['soru'])); ?>
                        </span>
                        <span class="faq-ikon-kutu w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 transition-all shrink-0">
                            <svg class="faq-ok w-4 h-4 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer hidden px-5 md:px-6 pb-6 pt-0">
                        <div class="p-5 bg-gradient-to-br from-blue-50/90 via-indigo-50/50 to-white rounded-2xl border border-blue-100 border-l-4 border-l-blue-600 text-slate-700 text-sm md:text-base leading-relaxed">
                            <?php echo nl2br(htmlspecialchars(t_cevir($sss['cevap']))); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="space-y-4">
                <?php foreach($sag as $sss): ?>
                <div class="faq-item bg-white rounded-2xl border border-slate-200 shadow-sm transition-all duration-300 hover:shadow-md hover:border-blue-300 overflow-hidden">
                    <button type="button" class="faq-question w-full flex items-center justify-between p-5 md:p-6 text-left cursor-pointer">
                        <span class="faq-baslik font-extrabold text-slate-800 text-base md:text-lg transition-colors leading-snug pr-4">
                            <?php echo htmlspecialchars(t_cevir($sss['soru'])); ?>
                        </span>
                        <span class="faq-ikon-kutu w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 transition-all shrink-0">
                            <svg class="faq-ok w-4 h-4 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer hidden px-5 md:px-6 pb-6 pt-0">
                        <div class="p-5 bg-gradient-to-br from-blue-50/90 via-indigo-50/50 to-white rounded-2xl border border-blue-100 border-l-4 border-l-blue-600 text-slate-700 text-sm md:text-base leading-relaxed">
                            <?php echo nl2br(htmlspecialchars(t_cevir($sss['cevap']))); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

</main>

<script>
document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const parent = this.closest('.faq-item');
        const answer = parent.querySelector('.faq-answer');
        const arrow = this.querySelector('.faq-ok');
        const iconBox = this.querySelector('.faq-ikon-kutu');
        const baslik = this.querySelector('.faq-baslik');
        const isHidden = answer.classList.contains('hidden');
        if(isHidden) {
            answer.classList.remove('hidden');
            arrow.style.transform = 'rotate(180deg)';
            iconBox.classList.remove('bg-slate-100', 'text-slate-500');
            iconBox.classList.add('bg-blue-600', 'text-white', 'shadow-md');
            baslik.classList.add('text-blue-600');
            parent.classList.add('border-blue-300', 'shadow-lg');
        } else {
            answer.classList.add('hidden');
            arrow.style.transform = 'rotate(0deg)';
            iconBox.classList.remove('bg-blue-600', 'text-white', 'shadow-md');
            iconBox.classList.add('bg-slate-100', 'text-slate-500');
            baslik.classList.remove('text-blue-600');
            parent.classList.remove('border-blue-300', 'shadow-lg');
        }
    });
});
</script>

<?php include __DIR__ . '/inc/footer.php'; ?>