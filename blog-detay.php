<?php
// ============================================================
// 📍 blog-detay.php - BLOG TEKİL YAZI DETAY SAYFASI (DİL DESTEKLİ)
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/inc/config.php';

// ========== 1. OTURUM VE DİL YÖNETİMİ ==========
if(session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$lang = $_SESSION['dil'] ?? 'tr';

// Dil Çeviri Fonksiyonu (site_cevirileri tablosu entegre)
if (!function_exists('t_cevir')) {
    function t_cevir($metin) {
        global $db, $lang;
        if (empty($metin) || $lang === 'tr') return $metin;
        
        $hash = md5(trim($metin));
        try {
            $stmt = $db->prepare("SELECT ingilizce_metin FROM site_cevirileri WHERE metin_hash = ? LIMIT 1");
            $stmt->execute([$hash]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($res && !empty($res['ingilizce_metin'])) {
                return $res['ingilizce_metin'];
            } else {
                $ins = $db->prepare("INSERT IGNORE INTO site_cevirileri (metin_hash, turkce_metin, ingilizce_metin, kayit_tarihi) VALUES (?, ?, ?, NOW())");
                $ins->execute([$hash, $metin, $metin]);
                return $metin;
            }
        } catch (Exception $e) {
            return $metin;
        }
    }
}

global $page_title, $seo_description, $site_keywords, $dynamic_og_title, $og_image, $mevcut_canonical_link;

$kategori_slug = isset($_GET['k']) ? trim($_GET['k']) : '';
$yazi_slug = isset($_GET['slug']) ? trim($_GET['slug']) : (isset($_GET['q']) ? trim($_GET['q']) : '');

if (empty($yazi_slug)) {
    header("Location: /blog");
    exit;
}

// 1. Yazıyı Veritabanından Çek
$stmt = $db->prepare("SELECT * FROM blog_yazilar WHERE slug = ? AND durum = 1 AND (silindi = 0 OR silindi IS NULL) LIMIT 1");
$stmt->execute([$yazi_slug]);
$yazi_raw = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$yazi_raw) {
    header("HTTP/1.0 404 Not Found");
    if (file_exists(__DIR__ . '/404.php')) {
        include __DIR__ . '/404.php';
    } else {
        echo '<div style="text-align:center; padding:100px 20px; font-family:sans-serif;"><h1>404 - Yazı Bulunamadı</h1><p>Aradığınız blog yazısı mevcut değil veya yayından kaldırılmış.</p><a href="/blog">Blog Sayfasına Dön</a></div>';
    }
    exit;
}

// Görüntülenme artır
$db->prepare("UPDATE blog_yazilar SET goruntulenme = goruntulenme + 1 WHERE id = ?")->execute([$yazi_raw['id']]);

// 🔥 DİL KONTROLÜ İLE YAZI ALANLARINI BELİRLE
$yazi = [
    'id' => $yazi_raw['id'],
    'slug' => $yazi_raw['slug'],
    'kategori' => $yazi_raw['kategori'],
    'baslik' => ($lang === 'en' && !empty($yazi_raw['baslik_en'])) ? $yazi_raw['baslik_en'] : $yazi_raw['baslik'],
    'ozet' => ($lang === 'en' && !empty($yazi_raw['ozet_en'])) ? $yazi_raw['ozet_en'] : $yazi_raw['ozet'],
    'icerik' => ($lang === 'en' && !empty($yazi_raw['icerik_en'])) ? $yazi_raw['icerik_en'] : $yazi_raw['icerik'],
    'resim' => $yazi_raw['resim'],
    'yazar' => $yazi_raw['yazar'],
    'goruntulenme' => $yazi_raw['goruntulenme'],
    'created_at' => $yazi_raw['created_at'],
    'seo_title' => $yazi_raw['seo_title'],
    'seo_description' => $yazi_raw['seo_description'],
    'seo_keywords' => $yazi_raw['seo_keywords'],
    'seo_og_image' => $yazi_raw['seo_og_image']
];

// Kategoriler
$kategoriler_db = $db->query("SELECT * FROM blog_kategoriler WHERE (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC")->fetchAll(PDO::FETCH_ASSOC);
$kategori_slug_map = [];
$kategori_isim_map = [];
foreach ($kategoriler_db as $kat) {
    $temiz_slug = trim($kat['kategori_slug']);
    $temiz_adi = trim($kat['kategori_adi']);
    $kategori_slug_map[$temiz_adi] = $temiz_slug;
    $kategori_isim_map[$temiz_slug] = $temiz_adi;
}

$blog_yazilar = $db->query("SELECT * FROM blog_yazilar WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$sss_list = $db->query("SELECT * FROM blog_sss WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC")->fetchAll(PDO::FETCH_ASSOC);

$gercek_kat_slug = $kategori_slug_map[trim($yazi['kategori'])] ?? ($kategori_slug ?: 'genel');

// 4. Dinamik SEO Tanımları
$raw_title = !empty($yazi['seo_title']) ? $yazi['seo_title'] : $yazi['baslik'];
$temiz_baslik = preg_replace('/\s*\|\s*Prof\.?\s*Dr\.?.*$/ui', '', $raw_title);
$temiz_baslik = trim($temiz_baslik);
$page_title = $temiz_baslik . ' | Blog';

$seo_description = !empty($yazi['seo_description']) ? $yazi['seo_description'] : mb_substr(strip_tags($yazi['ozet'] ?? ''), 0, 160);
if (stripos($seo_description, 'blog') === false) {
    $seo_description = mb_substr($seo_description, 0, 145) . ' - Blog';
}
$seo_keywords_ham = !empty($yazi['seo_keywords']) ? $yazi['seo_keywords'] : $yazi['baslik'] . ', diş hekimi, samsun';
if (stripos($seo_keywords_ham, 'blog') === false) {
    $site_keywords = $seo_keywords_ham . ', blog, diş sağlığı makalesi';
} else {
    $site_keywords = $seo_keywords_ham;
}
$secilen_resim = !empty($yazi['seo_og_image']) ? $yazi['seo_og_image'] : (!empty($yazi['resim']) ? $yazi['resim'] : '/uploads/blog/blog-og.webp');
if (!empty($secilen_resim) && !preg_match('/^https?:\/\//i', $secilen_resim)) {
    $og_image = 'adres gir' . (strpos($secilen_resim, '/') === 0 ? '' : '/') . $secilen_resim;
} else {
    $og_image = $secilen_resim;
}

$dynamic_og_title = $page_title;
$mevcut_canonical_link = 'adres gir/blog/' . $gercek_kat_slug . '/' . $yazi['slug'];

// Popüler ve Benzer
$populer_yazilar = $blog_yazilar;
usort($populer_yazilar, function($a, $b) { return $b['goruntulenme'] - $a['goruntulenme']; });
$populer_yazilar = array_slice($populer_yazilar, 0, 4);

$stmt_benzer = $db->prepare("SELECT * FROM blog_yazilar WHERE kategori = ? AND id != ? AND durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY created_at DESC LIMIT 3");
$stmt_benzer->execute([$yazi_raw['kategori'], $yazi['id']]);
$benzer_yazilar = $stmt_benzer->fetchAll(PDO::FETCH_ASSOC);

$okuma_suresi = ceil(str_word_count(strip_tags($yazi['icerik'])) / 200);
$page_slug = 'blog';

// Header form'da kullanılan değişkenler için fallback (tanımsız hata olmasın diye)
$search_query = '';
$total_posts = count($blog_yazilar);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$disable_site_title_suffix = true;
include __DIR__ . '/inc/header.php';
?>
<style>
.kategori-btn:hover { background-color: inherit !important; color: inherit !important; border-color: inherit !important; }
.kategori-btn.bg-blue-600:hover { background-color: #2563eb !important; color: white !important; }
.kategori-btn.bg-slate-50:hover { background-color: #f1f5f9 !important; color: #1e293b !important; border-color: #cbd5e1 !important; }
.kategori-btn { transition: all 0.2s ease-in-out !important; }
</style>

<!-- ============================================================ -->
<!-- 🎯 ZENGİN SCHEMA.ORG - WebPage + BlogPosting + ImageObject + BreadcrumbList + Benzer Yazılar + FAQPage -->
<!-- ============================================================ -->
<?php
$base_url = 'adres gir';
$yazi_url = $base_url . '/blog/' . $gercek_kat_slug . '/' . $yazi['slug'];
$kategori_url = $base_url . '/blog/' . $gercek_kat_slug . '/';

// Görsel absolute URL
$ana_gorsel = $og_image;
if (!empty($ana_gorsel) && strpos($ana_gorsel, 'http') !== 0) {
    $ana_gorsel = $base_url . '/' . ltrim($ana_gorsel, '/');
}

$yazar_adi = $yazi['yazar'] ?: 'Prof. Dr. İbrahim Duran';
$yayin_tarihi = date('c', strtotime($yazi['created_at'] ?? 'now'));

$tum_schemalar = [];

// ---------- 1) MedicalWebPage (Sayfa kimliği) ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'MedicalWebPage',
    '@id' => $yazi_url . '#webpage',
    'url' => $yazi_url,
    'name' => $page_title,
    'description' => $seo_description,
    'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
    'isPartOf' => ['@id' => $base_url . '/#website'],
    'about' => ['@id' => $base_url . '/#medicalbusiness'],
    'publisher' => ['@id' => $base_url . '/#medicalbusiness'],
    'breadcrumb' => ['@id' => $yazi_url . '#breadcrumb'],
    'primaryImageOfPage' => [
        '@type' => 'ImageObject',
        'url' => $ana_gorsel
    ],
    'mainEntity' => ['@id' => $yazi_url . '#article'],
    'audience' => [
        '@type' => 'PeopleAudience',
        'geographicArea' => [
            '@type' => 'City',
            'name' => 'Samsun'
        ]
    ]
];

// ---------- 2) BlogPosting (Ana İçerik) ----------
$blogposting = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    '@id' => $yazi_url . '#article',
    'mainEntityOfPage' => ['@id' => $yazi_url],
    'headline' => mb_substr($yazi['baslik'], 0, 110),
    'name' => $yazi['baslik'],
    'description' => $seo_description,
    'image' => [
        '@type' => 'ImageObject',
        'url' => $ana_gorsel,
        'width' => 1200,
        'height' => 630
    ],
    'datePublished' => $yayin_tarihi,
    'dateModified' => $yayin_tarihi,
    'author' => [
        '@type' => 'Person',
        'name' => $yazar_adi,
        'url' => $base_url . '/kurumsal',
        'image' => $base_url . '/uploads/kurumsal/kurumsal_1779668918_9117.webp',
        'jobTitle' => 'Protetik Diş Tedavisi ve İmplant Uzmanı'
    ],
    'publisher' => ['@id' => $base_url . '/#medicalbusiness'],
    'articleSection' => $yazi['kategori'],
    'keywords' => $site_keywords,
    'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
    'wordCount' => str_word_count(strip_tags($yazi['icerik'])),
    'isAccessibleForFree' => true,
    'commentCount' => 0
];
$tum_schemalar[] = $blogposting;

// ---------- 3) ImageObject (Kapak görseli) ----------
if (!empty($yazi['resim'])) {
    $kapak_url = $yazi['resim'];
    if (strpos($kapak_url, 'http') !== 0) {
        $kapak_url = $base_url . '/' . ltrim($kapak_url, '/');
    }
    $tum_schemalar[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ImageObject',
        '@id' => $yazi_url . '#primaryimage',
        'contentUrl' => $kapak_url,
        'name' => $yazi['baslik'],
        'description' => $seo_description,
        'uploadDate' => $yayin_tarihi,
        'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
        'creator' => [
            '@type' => 'Person',
            'name' => $yazar_adi
        ],
        'creditText' => 'Prof. Dr. İbrahim Duran - RivaDent Diş Kliniği',
        'copyrightNotice' => 'Prof. Dr. İbrahim Duran - RivaDent Diş Kliniği',
        'license' => $base_url . '/kullanim-kosullari/',
        'acquireLicensePage' => $base_url . '/iletisim/'
    ];
}

// ---------- 4) BreadcrumbList ----------
$breadcrumb_items = [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => ($lang === 'en') ? 'Home' : 'Anasayfa',
        'item' => $base_url . '/'
    ],
    [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => 'Blog',
        'item' => $base_url . '/blog/'
    ],
    [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $yazi['kategori'],
        'item' => $kategori_url
    ],
    [
        '@type' => 'ListItem',
        'position' => 4,
        'name' => $yazi['baslik'],
        'item' => $yazi_url
    ]
];
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    '@id' => $yazi_url . '#breadcrumb',
	'name' => 'Breadcrumb',    
    'itemListElement' => $breadcrumb_items
];

// ---------- 5) ItemList → Benzer Yazılar ----------
if (!empty($benzer_yazilar)) {
    $benzer_items = [];
    foreach ($benzer_yazilar as $idx => $b) {
        $b_k_slug = $kategori_slug_map[trim($b['kategori'])] ?? $gercek_kat_slug;
        $b_url = $base_url . '/blog/' . $b_k_slug . '/' . $b['slug'];
        $b_baslik = ($lang === 'en' && !empty($b['baslik_en'])) ? $b['baslik_en'] : $b['baslik'];
        $b_ozet = ($lang === 'en' && !empty($b['ozet_en'])) ? $b['ozet_en'] : ($b['ozet'] ?? '');
        $b_img = $b['resim'] ?? '';
        if (!empty($b_img) && strpos($b_img, 'http') !== 0) {
            $b_img = $base_url . '/' . ltrim($b_img, '/');
        }

        $b_item = [
            '@type' => 'BlogPosting',
            '@id' => $b_url . '#article',
            'mainEntityOfPage' => ['@id' => $b_url],
            'headline' => mb_substr($b_baslik, 0, 110),
            'name' => $b_baslik,
            'description' => mb_substr(strip_tags($b_ozet), 0, 300),
            'url' => $b_url,
            'datePublished' => date('c', strtotime($b['created_at'] ?? 'now')),
            'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
            'author' => [
                '@type' => 'Person',
                'url' => $base_url . '/',
                'name' => $b['yazar'] ?: 'Prof. Dr. İbrahim Duran'
            ],
            'publisher' => ['@id' => $base_url . '/#medicalbusiness']
        ];
        if (!empty($b_img)) {
            $b_item['image'] = [
                '@type' => 'ImageObject',
                'url' => $b_img,
                'width' => 1200,
                'height' => 630
            ];
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
        '@id' => $yazi_url . '#related',
        'name' => ($lang === 'en') ? 'Related Articles' : 'Benzer Yazılar',
        'numberOfItems' => count($benzer_items),
        'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
        'itemListElement' => $benzer_items
    ];
}

// ---------- 6) FAQPage (SSS bölümü) ----------
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
            '@id' => $yazi_url . '#faq',
            'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
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

<!-- Blog Header & Arama Bölümü -->
<section style="background: linear-gradient(180deg, #020617 0%, #0f172a 100%); color: #ffffff; padding: 42px 20px 55px 20px; position: relative; overflow: visible; border-bottom: 1px solid #1e293b;">
    <div style="max-width: 850px; margin: 0 auto; text-align: center; position: relative; z-index: 10;">
        
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 16px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 9999px; color: #60a5fa; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 14px;">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: #38bdf8;"></span>
            <?php echo t_cevir('Klinik Makale & Bilgi Merkezi'); ?>
        </div>
        
        <h1 style="font-size: clamp(26px, 3.5vw, 38px); font-weight: 900; color: #ffffff; margin: 0 0 10px 0; line-height: 1.2;">
            <?php echo t_cevir('Ağız ve Diş Sağlığı'); ?> <span style="color: #38bdf8;"><?php echo t_cevir('Rehberi'); ?></span>
        </h1>
        
        <p style="color: #94a3b8; font-size: 15px; max-width: 520px; margin: 0 auto 24px auto; line-height: 1.5;">
            <?php echo t_cevir('Tedaviler, hekim tavsiyeleri ve merak ettiğiniz konuları aşağıdan arayabilirsiniz.'); ?>
        </p>

        <div style="position: relative; max-width: 620px; margin: 0 auto; text-align: left;">
            <div style="display: flex; align-items: center; background: #1e293b; border: 2px solid #334155; border-radius: 15px; padding: 7px 14px; box-shadow: 0 12px 35px -5px rgba(0,0,0,0.5);">
                
                <div style="color: #38bdf8; display: flex; align-items: center; padding-left: 4px; padding-right: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                
                <input type="text" id="blogSearchInput" placeholder="<?php echo t_cevir('Tedavi, yöntem veya konu arayın...'); ?>" value="" style="width: 100%; background: transparent; border: none; outline: none; color: #ffffff; font-size: 16px; font-weight: 500; padding: 7px 0;" autocomplete="off">
                
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div id="aramaSpinner" class="hidden" style="width: 18px; height: 18px; border: 2px solid #38bdf8; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    
                    <a href="/blog/" id="aramaTemizle" class="hidden" style="width: 26px; height: 26px; border-radius: 7px; background: #334155; border: none; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </a>
                    
                    <span id="aramaSonucSayaci" style="background: #2563eb; color: #ffffff; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 7px;"><?php echo $total_posts; ?></span>
                </div>
            </div>

            <div style="margin-top: 12px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; justify-content: center;">
                <span style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;"><?php echo t_cevir('Önerilenler:'); ?></span>
                <a href="/blog/?q=implant" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; text-decoration: none;"><?php echo t_cevir('🦷 İmplant'); ?></a>
                <a href="/blog/?q=zirkonyum" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; text-decoration: none;"><?php echo t_cevir('✨ Zirkonyum'); ?></a>
                <a href="/blog/?q=g%C3%BCl%C3%BC%C5%9F+tasar%C4%B1m%C4%B1" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; text-decoration: none;"><?php echo t_cevir('😁 Gülüş Tasarımı'); ?></a>
                <a href="/blog/?q=kanal+tedavisi" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; text-decoration: none;"><?php echo t_cevir('🩺 Kanal Tedavisi'); ?></a>
            </div>
        </div>

    </div>
</section>

<!-- MAIN CONTENT - SOL SIDEBAR + SAĞ DETAY İÇERİK -->
<div class="w-full mx-auto py-10" style="padding-left: clamp(20px, 4vw, 50px); padding-right: clamp(20px, 4vw, 50px); max-width: 1750px;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- SOL SIDEBAR: KATEGORİLER + POPÜLER + BÜLTEN -->
        <aside class="hidden lg:block lg:col-span-3 space-y-6">
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
                    <?php $tumu_count = count($blog_yazilar); ?>
                    <a href="/blog" class="kategori-btn w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100">
                        <span class="flex items-center gap-2.5 min-w-0">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0 text-blue-600">
                                <?php echo getBlogIcon('FileText', 15); ?>
                            </span>
                            <span class="truncate"><?php echo t_cevir('TÜM YAZILAR'); ?></span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 bg-white text-slate-700 border border-slate-200">
                            <?php echo $tumu_count; ?>
                        </span>
                    </a>

                    <?php foreach($kategoriler_db as $kat): 
                        $kat_adi = trim($kat['kategori_adi']);
                        $kat_slug = trim($kat['kategori_slug']);
                        $is_active = (mb_strtolower(trim($yazi['kategori']), 'UTF-8') === mb_strtolower($kat_adi, 'UTF-8'));
                        
                        $kat_sayac = 0;
                        foreach($blog_yazilar as $item) {
                            if(mb_strtolower(trim($item['kategori']), 'UTF-8') === mb_strtolower($kat_adi, 'UTF-8')) $kat_sayac++;
                        }
                    ?>
                    <a href="/blog/<?php echo htmlspecialchars($kat_slug); ?>/" class="kategori-btn w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border <?php echo $is_active ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/25 ring-2 ring-blue-600/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'; ?>">
                        <span class="flex items-center gap-2.5 min-w-0">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0 <?php echo $is_active ? 'text-white' : 'text-blue-600'; ?>">
                                <?php echo getBlogIcon($kat['ikon'] ?? 'FileText', 15); ?>
                            </span>
                            <span class="truncate"><?php echo mb_strtoupper(t_cevir($kat_adi), 'UTF-8'); ?></span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 <?php echo $is_active ? 'bg-white/20 text-white' : 'bg-white text-slate-700 border border-slate-200'; ?>">
                            <?php echo $kat_sayac; ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- POPÜLER YAZILAR -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-xl border border-slate-200/80">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue-600"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                    <?php echo t_cevir('POPÜLER YAZILAR'); ?>
                </h3>
                <div class="space-y-3">
                    <?php foreach($populer_yazilar as $index => $post): 
                        $pop_k_slug = $kategori_slug_map[trim($post['kategori'])] ?? 'genel';
                        $pop_link = '/blog/' . htmlspecialchars($pop_k_slug) . '/' . htmlspecialchars($post['slug']);
                        $pop_baslik = ($lang === 'en' && !empty($post['baslik_en'])) ? $post['baslik_en'] : $post['baslik'];
                    ?>
                    <div class="flex items-center gap-3 cursor-pointer group p-2.5 rounded-2xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-md shrink-0">
                            <?php echo $index+1; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 group-hover:text-blue-600 line-clamp-2 transition-colors leading-snug">
                                <a href="<?php echo $pop_link; ?>"><?php echo htmlspecialchars(t_cevir($pop_baslik)); ?></a>
                            </h4>
                            <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-1">
                                <span class="flex items-center gap-1">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <?php echo number_format($post['goruntulenme']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- BÜLTEN -->
            <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-blue-800 rounded-3xl p-5 sm:p-6 shadow-xl text-white relative overflow-hidden">
                <div class="w-10 h-10 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <h3 class="font-extrabold text-base mb-1"><?php echo t_cevir('Bültenimize Katılın'); ?></h3>
                <p class="text-blue-100 text-xs mb-4 leading-relaxed"><?php echo t_cevir('Yeni makaleler anında e-postanıza gelsin.'); ?></p>
                <form action="/api/blog-abone.php" method="POST" class="space-y-2.5">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="email" name="email" required placeholder="<?php echo t_cevir('E-posta adresiniz'); ?>" class="w-full px-3.5 py-2.5 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder:text-white/60 text-xs focus:outline-none focus:ring-2 focus:ring-white/40 transition-all">
                    <button type="submit" class="w-full py-2.5 bg-white text-blue-700 font-bold text-xs rounded-xl hover:bg-blue-50 transition-colors shadow-md"><?php echo t_cevir('Abone Ol'); ?></button>
                </form>
            </div>
        </aside>

        <!-- SAĞ ANA ALAN: TEKİL YAZI DETAY -->
        <section class="lg:col-span-9 space-y-8">
            
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-xs md:text-sm text-slate-500 font-medium">
                <a href="/blog" class="hover:text-blue-600 transition"><?php echo t_cevir('Blog'); ?></a>
                <span>/</span>
                <a href="/blog/<?php echo htmlspecialchars($gercek_kat_slug); ?>/" class="hover:text-blue-600 font-semibold text-blue-600 transition">
                    <?php echo htmlspecialchars(t_cevir($yazi['kategori'])); ?>
                </a>
                <span>/</span>
                <span class="text-slate-800 truncate"><?php echo htmlspecialchars(t_cevir($yazi['baslik'])); ?></span>
            </nav>

            <article class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200 shadow-sm space-y-8">
                
                <!-- Başlık ve Meta Alanı -->
                <header class="space-y-4">
                    <a href="/blog/<?php echo htmlspecialchars($gercek_kat_slug); ?>/" class="mr-10 inline-flex items-center gap-2 px-6 py-3 bg-white hover:bg-slate-100 text-slate-700 text-sm font-bold rounded-xl border border-slate-200 transition-all">
                        ← <?php echo htmlspecialchars(t_cevir($yazi['kategori'])); ?> <?php echo t_cevir('Yazıları'); ?>
                    </a>

                    <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black text-slate-900 leading-tight tracking-tight">
                        <?php echo htmlspecialchars(t_cevir($yazi['baslik'])); ?>
                    </h1>
                    
                    <div class="flex flex-wrap items-center gap-4 text-xs md:text-sm text-slate-500 border-b border-slate-100 pb-6">
                        <span class="font-bold text-slate-800 flex items-center gap-1.5">
                            👨‍⚕️ <?php echo htmlspecialchars($yazi['yazar'] ?: 'Prof. Dr. İbrahim Duran'); ?>
                        </span>
                        <span>•</span>
                        <span>📅 <?php echo date('d.m.Y', strtotime($yazi['created_at'])); ?></span>
                        <span>•</span>
                        <span class="text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-md font-bold">⏱️ <?php echo $okuma_suresi; ?> <?php echo t_cevir('dk okuma'); ?></span>
                        <span>•</span>
                        <span>👁️ <?php echo number_format($yazi['goruntulenme']); ?> <?php echo t_cevir('görüntülenme'); ?></span>
                    </div>
                </header>

                <!-- Kapak Görseli -->
                <?php if(!empty($yazi['resim'])): ?>
                    <div class="rounded-3xl overflow-hidden shadow-md bg-slate-100 border border-slate-200 w-full object-contain h-[600px] md:h-[520px] lg:h-[640px] flex items-center justify-center">
                        <img src="<?php echo htmlspecialchars($yazi['resim']); ?>" alt="<?php echo htmlspecialchars(t_cevir($yazi['baslik'])); ?>" class="w-full h-full object-contain object-center">
                    </div>
                <?php endif; ?>

                <!-- Özet / Vurgu Kutusu -->
                <?php if(!empty($yazi['ozet'])): ?>
                    <div class="bg-blue-50/80 border-l-4 border-blue-600 p-6 rounded-r-2xl text-slate-800 font-medium text-base md:text-lg leading-relaxed shadow-xs">
                        <?php echo nl2br(htmlspecialchars(t_cevir($yazi['ozet']))); ?>
                    </div>
                <?php endif; ?>

                <!-- Makale İçeriği -->
                <div class="text-slate-800 leading-relaxed text-base md:text-lg space-y-6">
                    <?php 
                    echo t_cevir($yazi['icerik']); 
                    ?>
                </div>

                <!-- Aksiyon ve İletişim Kutusu -->
                <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50 p-6 rounded-2xl border">
                    <a href="/blog/<?php echo htmlspecialchars($gercek_kat_slug); ?>/" class="inline-flex items-center gap-2 px-6 py-3 bg-white hover:bg-slate-100 text-slate-700 text-sm font-bold rounded-xl border border-slate-200 transition-all">
                        ← <?php echo htmlspecialchars(t_cevir($yazi['kategori'])); ?> <?php echo t_cevir('Yazıları'); ?>
                    </a>
                    <a href="/iletisim/" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-extrabold rounded-xl transition-all shadow-md shadow-blue-500/25">
                        📅 <?php echo t_cevir('Hemen İletişime Geç'); ?>
                    </a>
                </div>

            </article>

<!-- İlgili Diğer Yazılar -->
<?php if(count($benzer_yazilar) > 0): ?>
    <div class="pt-10 border-t border-slate-200 mt-10">
        <h3 class="text-2xl font-black text-slate-900 mb-6">
            <?php echo t_cevir('Benzer Yazılar'); ?>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach($benzer_yazilar as $benzer):
                $benzer_k_slug = $kategori_slug_map[trim($benzer['kategori'])] ?? $gercek_kat_slug;
                $benzer_link = '/blog/' . htmlspecialchars($benzer_k_slug) . '/' . htmlspecialchars($benzer['slug']);
                $benzer_baslik = ($lang === 'en' && !empty($benzer['baslik_en'])) ? $benzer['baslik_en'] : $benzer['baslik'];
                $benzer_ozet = ($lang === 'en' && !empty($benzer['ozet_en'])) ? $benzer['ozet_en'] : $benzer['ozet'];
            ?>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col group">
                    <?php if(!empty($benzer['resim'])): ?>
                        <a href="<?php echo $benzer_link; ?>" class="block h-48 overflow-hidden bg-slate-100">
                            <img src="<?php echo htmlspecialchars($benzer['resim']); ?>" 
                                 alt="<?php echo htmlspecialchars(t_cevir($benzer_baslik)); ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </a>
                    <?php else: ?>
                        <div class="h-48 bg-gradient-to-br from-blue-50 to-slate-100 flex items-center justify-center text-slate-300">
                            <svg class="w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <path d="M21 15l-5-5L5 21"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="p-5 flex-1 flex flex-col">
                        <span class="text-[11px] font-extrabold text-blue-600 uppercase tracking-wider block mb-1.5">
                            <?php echo htmlspecialchars(t_cevir($benzer['kategori'])); ?>
                        </span>
                        <h4 class="font-bold text-slate-900 text-base line-clamp-2 group-hover:text-blue-600 transition leading-snug mb-2">
                            <a href="<?php echo $benzer_link; ?>">
                                <?php echo htmlspecialchars(t_cevir($benzer_baslik)); ?>
                            </a>
                        </h4>
                        <?php if(!empty($benzer_ozet)): ?>
                            <p class="text-sm text-slate-500 line-clamp-2 flex-1">
                                <?php echo htmlspecialchars(mb_substr(t_cevir($benzer_ozet), 0, 100)) . '...'; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="px-5 pb-5 pt-0">
                        <a href="<?php echo $benzer_link; ?>" 
                           class="text-xs font-extrabold text-blue-600 hover:text-blue-800 inline-flex items-center gap-1 transition">
                            <?php echo t_cevir('Oku'); ?> →
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

<!-- SIK SORULAN SORULAR -->
<section class="bg-gradient-to-b from-slate-50 via-blue-50/30 to-slate-100/70 py-20 border-t border-slate-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-100/80 border border-blue-200 rounded-full text-blue-700 text-xs font-black uppercase tracking-wider mb-3 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                KLİNİK REHBERİ
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight"><?php echo t_cevir('Merak Edilenler'); ?></h2>
            <p class="text-slate-500 text-sm md:text-base mt-2 max-w-xl mx-auto"><?php echo t_cevir('Tedavi süreçleri, hekim tavsiyeleri ve uygulamalar hakkında sıkça sorulan yanıtlar'); ?></p>
        </div>

        <?php 
        $tekil_sss = [];
        $gorulen_sorular = [];
        foreach($sss_list as $item) {
            $soru_anahtar = mb_strtolower(trim($item['soru']), 'UTF-8');
            if(!in_array($soru_anahtar, $gorulen_sorular)) {
                $gorulen_sorular[] = $soru_anahtar;
                $tekil_sss[] = $item;
            }
        }

        $sol_sutun = [];
        $sag_sutun = [];
        foreach($tekil_sss as $index => $item) {
            if($index % 2 == 0) {
                $sol_sutun[] = $item;
            } else {
                $sag_sutun[] = $item;
            }
        }
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div class="space-y-4">
                <?php foreach($sol_sutun as $sss): ?>
                <div class="faq-item bg-white rounded-2xl border border-slate-200 shadow-sm transition-all duration-300 hover:shadow-md hover:border-blue-300 overflow-hidden">
                    <button type="button" class="faq-question w-full flex items-center justify-between p-5 md:p-6 text-left select-none outline-none focus:outline-none cursor-pointer" data-id="<?php echo $sss['id']; ?>">
                        <span class="faq-baslik font-extrabold text-slate-800 text-base md:text-lg transition-colors leading-snug pr-4">
                            <?php echo htmlspecialchars(t_cevir($sss['soru'])); ?>
                        </span>
                        <span class="faq-ikon-kutu w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 transition-all shrink-0">
                            <svg class="faq-ok w-4 h-4 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    
                    <div class="faq-answer hidden px-5 md:px-6 pb-6 pt-0">
                        <div class="p-5 bg-gradient-to-br from-blue-50/90 via-indigo-50/50 to-white rounded-2xl border border-blue-100 border-l-4 border-l-blue-600 text-slate-700 text-sm md:text-base leading-relaxed tracking-normal shadow-xs">
                            <?php echo nl2br(htmlspecialchars(t_cevir($sss['cevap']))); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="space-y-4">
                <?php foreach($sag_sutun as $sss): ?>
                <div class="faq-item bg-white rounded-2xl border border-slate-200 shadow-sm transition-all duration-300 hover:shadow-md hover:border-blue-300 overflow-hidden">
                    <button type="button" class="faq-question w-full flex items-center justify-between p-5 md:p-6 text-left select-none outline-none focus:outline-none cursor-pointer" data-id="<?php echo $sss['id']; ?>">
                        <span class="faq-baslik font-extrabold text-slate-800 text-base md:text-lg transition-colors leading-snug pr-4">
                            <?php echo htmlspecialchars(t_cevir($sss['soru'])); ?>
                        </span>
                        <span class="faq-ikon-kutu w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 transition-all shrink-0">
                            <svg class="faq-ok w-4 h-4 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    
                    <div class="faq-answer hidden px-5 md:px-6 pb-6 pt-0">
                        <div class="p-5 bg-gradient-to-br from-blue-50/90 via-indigo-50/50 to-white rounded-2xl border border-blue-100 border-l-4 border-l-blue-600 text-slate-700 text-sm md:text-base leading-relaxed tracking-normal shadow-xs">
                            <?php echo nl2br(htmlspecialchars(t_cevir($sss['cevap']))); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>

</main>

<script>
// FAQ Akordeon
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
            iconBox.classList.add('bg-blue-600', 'text-white', 'shadow-md', 'shadow-blue-500/20');
            baslik.classList.add('text-blue-600');
            parent.classList.add('border-blue-300', 'shadow-lg');
        } else {
            answer.classList.add('hidden');
            arrow.style.transform = 'rotate(0deg)';
            iconBox.classList.remove('bg-blue-600', 'text-white', 'shadow-md', 'shadow-blue-500/20');
            iconBox.classList.add('bg-slate-100', 'text-slate-500');
            baslik.classList.remove('text-blue-600');
            parent.classList.remove('border-blue-300', 'shadow-lg');
        }
    });
});
</script>

<?php include __DIR__ . '/inc/footer.php'; ?>

<?php
function getBlogIcon($name, $size = 16) {
    if (empty($name)) $name = 'FileText';
    $icons = [
        'Sparkles' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><circle cx="4" cy="20" r="2"/></svg>',
        'Syringe' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3c-.4.4-1 .6-1.6.6H5a2 2 0 0 1-2-2v-2.1c0-.6.2-1.2.6-1.6L15 5"/><path d="M10 5 8 7"/><path d="M14 9l-2 2"/><path d="M3 21h18"/></svg>',
        'Activity' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9-4-18-3 9H2"/></svg>',
        'Shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
        'Smile' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>',
        'FileText' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>'
    ];
    return isset($icons[$name]) ? $icons[$name] : $icons['FileText'];
}
?>