<?php
// ============================================================
// 📍 www/site/blog/index.php - BLOG (t_cevir + sabit UI sözlüğü)
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once dirname(__DIR__) . '/inc/config.php';

global $page_title, $seo_description, $site_keywords, $dynamic_og_title, $og_image, $mevcut_canonical_link;

// ============================================================
// 🔥 PARAMETRELERİ AL
// ============================================================
$aktif_kategori = isset($_GET['k']) ? trim($_GET['k']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($aktif_kategori)) {
    $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $segments = explode('/', $request_uri);
    if (isset($segments[0]) && $segments[0] === 'blog' && isset($segments[1]) && $segments[1] !== '' && $segments[1] !== 'index.php') {
        $maybe_slug = $segments[2] ?? '';
        if (!empty($maybe_slug)) {
            $slug = $maybe_slug;
        } else {
            $aktif_kategori = $segments[1];
        }
    }
}

if (!empty($slug)) {
    $stmt_yonlendir = $db->prepare("SELECT * FROM blog_yazilar WHERE slug = ? AND durum = 1 AND (silindi = 0 OR silindi IS NULL) LIMIT 1");
    $stmt_yonlendir->execute([$slug]);
    $yazi_var = $stmt_yonlendir->fetch(PDO::FETCH_ASSOC);
    if ($yazi_var) {
        $stmt_k = $db->prepare("SELECT kategori_slug FROM blog_kategoriler WHERE kategori_adi = ? LIMIT 1");
        $stmt_k->execute([$yazi_var['kategori']]);
        $k_slug = $stmt_k->fetchColumn() ?: 'genel';
        header("Location: /blog/" . $k_slug . "/" . $yazi_var['slug']);
        exit;
    }
}

// ============================================================
// 🔥 SEO AYARLARI
// ============================================================
$seo_ayarlar = [];
try {
    $stmt = $db->query("SELECT anahtar, deger FROM blog_seo_ayarlar");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $seo_ayarlar[$row['anahtar']] = $row['deger'];
    }
} catch (PDOException $e) {}

$lang = $_SESSION['dil'] ?? 'tr';

if ($lang === 'en') {
    $page_title = $seo_ayarlar['seo_title_en'] ?? 'Blog | Prof. Dr. İbrahim Duran | Dental Clinic Samsun';
    $seo_description = $seo_ayarlar['seo_description_en'] ?? 'Expert insights from Prof. Dr. İbrahim Duran on dental health, aesthetic dentistry, implant treatment, smile design, zirconium coating and oral health.';
    $site_keywords = $seo_ayarlar['seo_keywords_en'] ?? 'blog, dental health, implant, smile design, aesthetic dentistry, Samsun dentist';
} else {
    $page_title = $seo_ayarlar['seo_title_tr'] ?? 'Blog | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
    $seo_description = $seo_ayarlar['seo_description_tr'] ?? 'Prof. Dr. İbrahim Duran\'ın kaleminden diş sağlığı, estetik diş hekimliği, implant tedavisi, gülüş tasarımı, zirkonyum kaplama ve ağız sağlığı üzerine uzman görüşleri.';
    $site_keywords = $seo_ayarlar['seo_keywords_tr'] ?? 'blog, diş sağlığı, implant, gülüş tasarımı, estetik diş hekimliği, Samsun diş hekimi';
}

$og_image = $seo_ayarlar['seo_og_image'] ?? '/uploads/blog/blog-og.webp';
$mevcut_canonical_link = $seo_ayarlar['seo_canonical'] ?? 'adres gir/blog/';
$dynamic_og_title = $page_title;

// ============================================================
// 🔥 SABİT UI METİNLERİ
// ============================================================
$ui = [
    'tr' => [
        'tum_makaleler'      => 'Tüm Makaleler',
        'makale'             => 'makale',
        'secili_kriterler'   => 'Seçilen kriterlere göre yayınlanan içerikler',
        'kategoriler'        => 'KATEGORİLER',
        'tum_yazilar'        => 'TÜM YAZILAR',
        'populer_yazilar'    => 'POPÜLER YAZILAR',
        'bulten_katil'       => 'Bültenimize Katılın',
        'bulten_aciklama'    => 'Yeni makaleler anında e-postanıza gelsin.',
        'eposta_adresiniz'   => 'E-posta adresiniz',
        'abone_ol'           => 'Abone Ol',
        'detaylari_goster'   => 'Detayları Göster',
        'tumunu_goster'      => 'Tümünü Göster',
        'dk_okuma'           => 'dk okuma',
        'klinik_rehberi'     => 'KLİNİK REHBERİ',
        'merak_edilenler'    => 'Merak Edilenler',
        'sss_aciklama'       => 'Tedavi süreçleri, hekim tavsiyeleri ve uygulamalar hakkında sıkça sorulan yanıtlar',
        'onerilenler'        => 'Önerilenler:',
        'arama_placeholder'  => 'Tedavi, yöntem veya konu arayın...',
        'klinik_makale'      => 'Klinik Makale & Bilgi Merkezi',
        'agiz_dis_sagligi'   => 'Ağız ve Diş Sağlığı',
        'rehberi'            => 'Rehberi',
        'header_aciklama'    => 'Tedaviler, hekim tavsiyeleri ve merak ettiğiniz konuları aşağıdan arayabilirsiniz.',
        'eslesen_yok'        => 'Eşleşen Sonuç Bulunamadı',
        'eslesen_yok_desc'   => 'Farklı anahtar kelimelerle arama yapabilir veya filtreyi sıfırlayabilirsiniz.',
        'implant'            => 'İmplant',
        'zirkonyum'          => 'Zirkonyum',
        'gulus_tasarimi'     => 'Gülüş Tasarımı',
        'kanal_tedavisi'     => 'Kanal Tedavisi',
    ],
    'en' => [
        'tum_makaleler'      => 'All Articles',
        'makale'             => 'articles',
        'secili_kriterler'   => 'Content published according to selected criteria',
        'kategoriler'        => 'CATEGORIES',
        'tum_yazilar'        => 'ALL ARTICLES',
        'populer_yazilar'    => 'POPULAR ARTICLES',
        'bulten_katil'       => 'Join Our Newsletter',
        'bulten_aciklama'    => 'Get new articles delivered to your inbox.',
        'eposta_adresiniz'   => 'Your email address',
        'abone_ol'           => 'Subscribe',
        'detaylari_goster'   => 'View Details',
        'tumunu_goster'      => 'Show All',
        'dk_okuma'           => 'min read',
        'klinik_rehberi'     => 'CLINICAL GUIDE',
        'merak_edilenler'    => 'Frequently Asked',
        'sss_aciklama'       => 'Common questions about treatment processes, doctor recommendations and practices',
        'onerilenler'        => 'Suggested:',
        'arama_placeholder'  => 'Search treatment, method or topic...',
        'klinik_makale'      => 'Clinical Article & Information Center',
        'agiz_dis_sagligi'   => 'Oral and Dental Health',
        'rehberi'            => 'Guide',
        'header_aciklama'    => 'Search treatments, doctor advice and topics you are curious about below.',
        'eslesen_yok'        => 'No Matching Results Found',
        'eslesen_yok_desc'   => 'Try different keywords or reset the filter.',
        'implant'            => 'Implant',
        'zirkonyum'          => 'Zirconium',
        'gulus_tasarimi'     => 'Smile Design',
        'kanal_tedavisi'     => 'Root Canal',
    ],
];

$U = $ui[$lang] ?? $ui['tr'];

// ============================================================
// 🔥 VERİLER
// ============================================================
$kategoriler_db = $db->query("SELECT * FROM blog_kategoriler WHERE (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC")->fetchAll(PDO::FETCH_ASSOC);

function createSlugBlog($str) {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $from = ['ç', 'ğ', 'ı', 'i', 'ö', 'ş', 'ü', ' ', '.'];
    $to   = ['c', 'g', 'i', 'i', 'o', 's', 'u', '-', '-'];
    $str = str_replace($from, $to, $str);
    $str = preg_replace('/[^a-z0-9\-]/', '', $str);
    return preg_replace('/-+/', '-', $str);
}

$slug_to_kategori_adi = [];
$kategori_slug_map = [];

foreach ($kategoriler_db as $kat) {
    $temiz_adi = trim($kat['kategori_adi']);
    $temiz_slug = trim($kat['kategori_slug']);
    if (empty($temiz_slug)) {
        $temiz_slug = createSlugBlog($temiz_adi);
    }
    $kategori_slug_map[$temiz_adi] = $temiz_slug;
    $slug_to_kategori_adi[mb_strtolower($temiz_slug, 'UTF-8')] = $temiz_adi;
    $slug_to_kategori_adi[createSlugBlog($temiz_adi)] = $temiz_adi;
    $slug_to_kategori_adi[mb_strtolower($temiz_adi, 'UTF-8')] = $temiz_adi;
}

$blog_yazilar = $db->query("SELECT * FROM blog_yazilar WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$sss_list = $db->query("SELECT * FROM blog_sss WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC")->fetchAll(PDO::FETCH_ASSOC);

$aktif_kategori_adi = 'tumu';
$aktif_kategori_slug = 'tumu';

if ($aktif_kategori !== '' && $aktif_kategori !== 'tumu') {
    $aktif_clean = mb_strtolower(trim($aktif_kategori), 'UTF-8');
    $aktif_slug_target = createSlugBlog($aktif_clean);

    if (isset($slug_to_kategori_adi[$aktif_clean])) {
        $aktif_kategori_adi = $slug_to_kategori_adi[$aktif_clean];
        $aktif_kategori_slug = $aktif_clean;
    } elseif (isset($slug_to_kategori_adi[$aktif_slug_target])) {
        $aktif_kategori_adi = $slug_to_kategori_adi[$aktif_slug_target];
        $aktif_kategori_slug = $aktif_slug_target;
    } else {
        foreach ($kategoriler_db as $kat) {
            $k_adi = trim($kat['kategori_adi']);
            $k_slug = trim($kat['kategori_slug']);
            if (createSlugBlog($k_adi) === $aktif_slug_target || createSlugBlog($k_slug) === $aktif_slug_target || mb_strtolower($k_adi, 'UTF-8') === $aktif_clean) {
                $aktif_kategori_adi = $k_adi;
                $aktif_kategori_slug = $k_slug ?: $aktif_slug_target;
                break;
            }
        }
    }
}

$filtrelenmis_yazilar = [];
foreach ($blog_yazilar as $yazi) {
    $yazi_kat = trim($yazi['kategori']);
    $kategori_uygun = false;
    if ($aktif_kategori_adi === 'tumu') {
        $kategori_uygun = true;
    } else {
        $yazi_slug = createSlugBlog($yazi_kat);
        $hedef_slug = createSlugBlog($aktif_kategori_adi);
        $gelen_slug = createSlugBlog($aktif_kategori);
        if ($yazi_slug === $hedef_slug || $yazi_slug === $gelen_slug || mb_strtolower($yazi_kat, 'UTF-8') === mb_strtolower($aktif_kategori_adi, 'UTF-8') || strpos($yazi_slug, $gelen_slug) !== false || strpos($gelen_slug, $yazi_slug) !== false) {
            $kategori_uygun = true;
        }
    }

    $arama_uygun = empty($search_query) || 
                   stripos($yazi['baslik'], $search_query) !== false || 
                   stripos($yazi['ozet'], $search_query) !== false;
    
    if ($kategori_uygun && $arama_uygun) {
        $filtrelenmis_yazilar[] = $yazi;
    }
}

$total_posts = count($filtrelenmis_yazilar);

$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
if ($sayfa < 1) $sayfa = 1;
$limit = 9;
$toplam_sayfa = ceil($total_posts / $limit);
$baslangic = ($sayfa - 1) * $limit;
$gosterilecek_yazilar = array_slice($filtrelenmis_yazilar, $baslangic, $limit);

$populer_yazilar = $blog_yazilar;
usort($populer_yazilar, function($a, $b) {
    return $b['goruntulenme'] - $a['goruntulenme'];
});
$populer_yazilar = array_slice($populer_yazilar, 0, 4);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// 🔥 SÜTUN RENDER FONKSİYONU
// ============================================================
function renderBlogSutun($yazilar_dizisi, $kategoriler_db) {
    global $U;
    if(empty($yazilar_dizisi)) return '';
    ob_start();
    foreach($yazilar_dizisi as $post): 
        $okuma_suresi = ceil(str_word_count(strip_tags($post['icerik'])) / 200);
        $k_kat = trim($post['kategori']);
        
        $k_slug = createSlugBlog($k_kat);
        foreach($kategoriler_db as $kat) {
            if(trim($kat['kategori_adi']) === $k_kat) {
                $k_slug = trim($kat['kategori_slug']);
                break;
            }
        }
        if ($k_slug === 'protetik-dis-tedavisi-gnatoloji') $k_slug = 'cene-eklemi';
        $post_url = '/blog/' . htmlspecialchars($k_slug) . '/' . htmlspecialchars($post['slug']);
    ?>
    <article class="group bg-white rounded-3xl border-2 border-slate-200 overflow-hidden transition-all duration-300 flex flex-col mb-8 hover:border-blue-500 hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-1">

        <!-- GÖRSEL ALANI -->
        <div class="relative h-64 md:h-72 overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200 shrink-0">

            <?php if(!empty($post['resim'])): ?>
                <img src="<?php echo htmlspecialchars($post['resim']); ?>"
                     alt="<?php echo htmlspecialchars(t_cevir($post['baslik'])); ?>"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                     loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/30 via-transparent to-transparent pointer-events-none"></div>
            <?php else: ?>
                <div class="w-full h-full bg-gradient-to-tr from-slate-800 to-blue-900 flex items-center justify-center text-white/30">
                    <svg class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <path d="M21 15l-5-5L5 21"/>
                    </svg>
                </div>
            <?php endif; ?>

            <!-- KATEGORİ ROZETİ -->
            <div class="absolute top-4 left-4 z-20 max-w-[calc(100%-6rem)]">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/95 backdrop-blur-md text-blue-700 text-[11px] font-black uppercase tracking-wider rounded-xl shadow-lg border border-white/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                    <span class="truncate"><?php echo htmlspecialchars(t_cevir($post['kategori'])); ?></span>
                </span>
            </div>

            <!-- GÖRÜNTÜLENME -->
            <div class="absolute top-4 right-4 z-20">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-900/80 backdrop-blur-md text-white text-[11px] font-bold rounded-lg shadow-md">
                    <svg class="w-3.5 h-3.5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <?php echo number_format($post['goruntulenme']); ?>
                </span>
            </div>
        </div>

        <!-- İÇERİK ALANI -->
        <div class="p-6 md:p-7 flex-1 flex flex-col justify-between gap-5">

            <div class="space-y-4">
                <!-- Tarih + Okuma Süresi -->
                <div class="flex items-center gap-2.5 text-xs font-bold">
                    <span class="flex items-center gap-1.5 text-slate-500">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <?php echo date('d.m.Y', strtotime($post['created_at'])); ?>
                    </span>
                    <span class="text-slate-300">•</span>
                    <span class="flex items-center gap-1.5 text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-100">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?php echo $okuma_suresi; ?> <?php echo $U['dk_okuma']; ?>
                    </span>
                </div>

                <!-- BAŞLIK -->
                <h3 class="text-xl md:text-2xl font-black text-slate-900 group-hover:text-blue-700 transition-colors leading-tight line-clamp-2">
                    <a href="<?php echo $post_url; ?>" class="block">
                        <?php echo htmlspecialchars(t_cevir($post['baslik'])); ?>
                    </a>
                </h3>

                <!-- ÖZET -->
                <p class="text-slate-600 text-sm md:text-[15px] line-clamp-3 leading-relaxed">
                    <?php echo htmlspecialchars(t_cevir($post['ozet'])); ?>
                </p>
            </div>

            <!-- ALT KISIM -->
            <div class="pt-5 border-t-2 border-dashed border-slate-100 flex items-center justify-between gap-3">

                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 text-white font-black flex items-center justify-center text-base shadow-lg shadow-blue-500/25 shrink-0 border-2 border-white">
                        <?php echo mb_substr($post['yazar'] ?: 'P', 0, 1); ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-extrabold text-slate-900 truncate leading-tight">
                            <?php echo htmlspecialchars($post['yazar'] ?: 'Prof. Dr. İbrahim Duran'); ?>
                        </h4>
                        <p class="text-xs text-slate-400 truncate font-medium mt-0.5">
                            <?php echo htmlspecialchars(t_cevir($post['yazar_unvan'] ?? 'Diş Hekimi')); ?>
                        </p>
                    </div>
                </div>

                <a href="<?php echo $post_url; ?>"
                   class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-extrabold text-xs md:text-sm rounded-xl transition-all shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-105 whitespace-nowrap">
                    <span><?php echo $U['detaylari_goster']; ?></span>
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            </div>
        </div>
    </article>
    <?php endforeach;
    return ob_get_clean();
}

$blog_sol_sutun = [];
$blog_sag_sutun = [];
foreach($gosterilecek_yazilar as $index => $post_item) {
    if($index % 2 == 0) {
        $blog_sol_sutun[] = $post_item;
    } else {
        $blog_sag_sutun[] = $post_item;
    }
}
// ============================================================
// 🔥 SEO OVERRIDE — Kategori ve Arama Farkındalıklı
// Bu blok, yukarıdaki default SEO değerlerini ezer.
// Kategori/arama varsa dinamik başlık + açıklama üretir.
// ============================================================
$base_seo_url = 'adres gir';

if (!empty($search_query)) {

    // ---------- ARAMA SAYFASI ----------
    if ($lang === 'en') {
        $page_title      = '"' . $search_query . '" — Search Results | Blog | Prof. Dr. İbrahim Duran';
        $seo_description = 'Search results for "' . $search_query . '" — ' . $total_posts . ' articles found in Prof. Dr. İbrahim Duran\'s dental health blog.';
        $site_keywords   = mb_strtolower($search_query) . ', dental blog, search, Samsun dentist';
    } else {
        $page_title      = '"' . $search_query . '" — Arama Sonuçları | Blog | Prof. Dr. İbrahim Duran';
        $seo_description = '"' . $search_query . '" için ' . $total_posts . ' makale bulundu. Prof. Dr. İbrahim Duran\'ın diş sağlığı blogunda arama sonuçları.';
        $site_keywords   = mb_strtolower($search_query) . ', diş blogu, arama, Samsun diş hekimi';
    }
    // Arama sonuçları indexlenmesin (thin/duplicate content), canonical /blog/'a
    $mevcut_canonical_link  = $base_seo_url . '/blog/';
    $robots_etiketi_icerigi = '<meta name="robots" content="noindex, follow">';

} elseif ($aktif_kategori_adi !== 'tumu' && !empty($aktif_kategori_adi)) {

    // ---------- KATEGORİ SAYFASI ----------
    $kat_label       = function_exists('t_cevir') ? t_cevir($aktif_kategori_adi) : $aktif_kategori_adi;
    $kat_label_clean = trim(strip_tags($kat_label));

    // Bu kategorideki yazı sayısı (kategori başlığından sonra hesaplandı, hazır)
    $kat_yazi_sayisi = 0;
    foreach ($blog_yazilar as $y) {
        if (trim($y['kategori']) === trim($aktif_kategori_adi)) $kat_yazi_sayisi++;
    }

    if ($lang === 'en') {
        $page_title      = $kat_label_clean . ' Articles (' . $kat_yazi_sayisi . ') | Blog | Prof. Dr. İbrahim Duran';
        $seo_description = $kat_yazi_sayisi . ' expert articles on ' . $kat_label_clean . ' by Prof. Dr. İbrahim Duran. Treatment processes, clinical applications and professional insights.';
        $site_keywords   = mb_strtolower($kat_label_clean) . ', ' . ($seo_ayarlar['seo_keywords_en'] ?? 'dental health, dentist, Samsun');
    } else {
        $page_title      = $kat_label_clean . ' Yazıları (' . $kat_yazi_sayisi . ') | Blog | Prof. Dr. İbrahim Duran';
        $seo_description = 'Prof. Dr. İbrahim Duran\'ın ' . $kat_label_clean . ' kategorisindeki ' . $kat_yazi_sayisi . ' uzman makalesi. Tedavi süreçleri, klinik uygulamalar ve hekim tavsiyeleri.';
        $site_keywords   = mb_strtolower($kat_label_clean) . ', ' . ($seo_ayarlar['seo_keywords_tr'] ?? 'diş sağlığı, diş hekimi, Samsun');
    }
    $mevcut_canonical_link = $base_seo_url . '/blog/' . $aktif_kategori_slug . '/';
}

// Her durumda OG title sayfa title'ı ile aynı olsun
$dynamic_og_title = $page_title;
$page_slug = 'blog';
include dirname(__DIR__) . '/inc/header.php';
?>

<style>
.kategori-btn:hover { background-color: inherit !important; color: inherit !important; border-color: inherit !important; }
.kategori-btn.bg-blue-600:hover { background-color: #2563eb !important; color: white !important; }
.kategori-btn.bg-slate-50:hover { background-color: #f1f5f9 !important; color: #1e293b !important; border-color: #cbd5e1 !important; }
.kategori-btn { transition: all 0.2s ease-in-out !important; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<!-- ============================================================ -->
<!-- 🎯 ZENGİN SCHEMA.ORG - CollectionPage + 36 BlogPosting + 9 Kategori + Breadcrumb + FAQPage -->
<!-- ============================================================ -->
<?php
$base_url = 'adres gir';

// Aktif kategori için canonical blog URL'si
$blog_url = $base_url . '/blog/';
if ($aktif_kategori_slug !== 'tumu' && !empty($aktif_kategori_slug)) {
    $blog_url = $base_url . '/blog/' . $aktif_kategori_slug . '/';
}

$tum_schemalar = [];

// ---------- 1) CollectionPage (Sayfa kimliği) ----------
$collection_name = ($aktif_kategori_adi === 'tumu')
    ? ($lang === 'en' ? 'Dental Health Blog' : 'Ağız ve Diş Sağlığı Blogu')
    : 'Blog - ' . $aktif_kategori_adi;

$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    '@id' => $blog_url . '#collectionpage',
    'url' => $blog_url,
    'name' => $collection_name,
    'description' => $seo_description,
    'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
    'isPartOf' => ['@id' => $base_url . '/#website'],
    'about' => ['@id' => $base_url . '/#medicalbusiness'],
    'publisher' => ['@id' => $base_url . '/#medicalbusiness'],
    'primaryImageOfPage' => [
        '@type' => 'ImageObject',
        'url' => (strpos($og_image, 'http') === 0 ? $og_image : $base_url . $og_image)
    ]
];

// ---------- 2) ItemList → TÜM BlogPosting'ler (36 yazı - sayfalamadan bağımsız) ----------
if (!empty($blog_yazilar)) {
    $blog_items = [];
    foreach ($blog_yazilar as $idx => $yazi_item) {
        $k_kat = trim($yazi_item['kategori']);
        $k_slug = createSlugBlog($k_kat);
        foreach ($kategoriler_db as $kat) {
            if (trim($kat['kategori_adi']) === $k_kat) {
                $k_slug = trim($kat['kategori_slug']);
                break;
            }
        }
        if ($k_slug === 'protetik-dis-tedavisi-gnatoloji') $k_slug = 'cene-eklemi';

        $yazi_url = $base_url . '/blog/' . $k_slug . '/' . $yazi_item['slug'];
        $yazi_img = $yazi_item['resim'] ?? '';
        if (!empty($yazi_img) && strpos($yazi_img, 'http') !== 0) {
            $yazi_img = $base_url . '/' . ltrim($yazi_img, '/');
        }

        $yazi_baslik = function_exists('t_cevir') ? t_cevir($yazi_item['baslik']) : $yazi_item['baslik'];
        $yazi_ozet = function_exists('t_cevir') ? t_cevir($yazi_item['ozet'] ?? '') : ($yazi_item['ozet'] ?? '');

        $blog_item = [
            '@type' => 'BlogPosting',
            '@id' => $yazi_url . '#article',
            'mainEntityOfPage' => ['@id' => $yazi_url],
            'headline' => mb_substr($yazi_baslik, 0, 110),
            'name' => $yazi_baslik,
            'description' => mb_substr(strip_tags($yazi_ozet), 0, 300),
            'url' => $yazi_url,
            'datePublished' => date('c', strtotime($yazi_item['created_at'] ?? 'now')),
            'dateModified' => date('c', strtotime($yazi_item['created_at'] ?? 'now')),
            'inLanguage' => ($lang === 'en') ? 'en-US' : 'tr-TR',
            'author' => [
                '@type' => 'Person',
                'name' => $yazi_item['yazar'] ?: 'Prof. Dr. İbrahim Duran',
                'url' => $base_url . '/hakkimizda'
            ],
            'publisher' => ['@id' => $base_url . '/#medicalbusiness'],
            'articleSection' => $k_kat,
            'isAccessibleForFree' => true,
        ];
        if (!empty($yazi_img)) {
            $blog_item['image'] = [
                '@type' => 'ImageObject',
                'url' => $yazi_img,
                'width' => 1200,
                'height' => 630
            ];
        }

        $blog_items[] = [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'item' => $blog_item
        ];
    }

    $tum_schemalar[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        '@id' => $blog_url . '#articlelist',
        'name' => ($lang === 'en') ? 'All Blog Articles' : 'Tüm Blog Yazıları',
        'numberOfItems' => count($blog_items),
        'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
        'itemListElement' => $blog_items
    ];
}

// ---------- 3) ItemList → 9 Kategori (Blog Category Listesi) ----------
if (!empty($kategoriler_db)) {
    $kategori_items = [];
    foreach ($kategoriler_db as $idx => $kat) {
        $kat_adi = trim($kat['kategori_adi']);
        $kat_slug = trim($kat['kategori_slug']);
        if (empty($kat_slug)) $kat_slug = createSlugBlog($kat_adi);

        // Kategori sayısını hesapla
        $kat_sayac = 0;
        foreach ($blog_yazilar as $yazi) {
            $y_kat = trim($yazi['kategori']);
            $y_slug = createSlugBlog($y_kat);
            $target_slug1 = createSlugBlog($kat_adi);
            $target_slug2 = createSlugBlog($kat_slug);
            if ($y_slug === $target_slug1 || $y_slug === $target_slug2 || strpos($y_slug, $target_slug1) !== false || ($target_slug2 === 'cene-eklemi' && strpos($y_slug, 'protetik') !== false)) {
                $kat_sayac++;
            }
        }

        $kat_label = function_exists('t_cevir') ? t_cevir($kat_adi) : $kat_adi;

        $kategori_items[] = [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'item' => [
                '@type' => 'DefinedTerm',
                '@id' => $base_url . '/blog/' . $kat_slug . '/#category',
                'name' => $kat_label,
                'termCode' => $kat_slug,
                'url' => $base_url . '/blog/' . $kat_slug . '/',
                'description' => $kat_sayac . ' ' . ($lang === 'en' ? 'articles' : 'makale'),
                'inDefinedTermSet' => [
                    '@type' => 'DefinedTermSet',
                    '@id' => $base_url . '/blog/#categoryset',
                    'name' => ($lang === 'en') ? 'Blog Categories' : 'Blog Kategorileri'
                ]
            ]
        ];
    }

    $tum_schemalar[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        '@id' => $base_url . '/blog/#categorylist',
        'name' => ($lang === 'en') ? 'Blog Categories' : 'Blog Kategorileri',
        'numberOfItems' => count($kategori_items),
        'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
        'itemListElement' => $kategori_items
    ];
}

// ---------- 4) BreadcrumbList ----------
$breadcrumb = [
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
    ]
];
if ($aktif_kategori_slug !== 'tumu' && !empty($aktif_kategori_adi)) {
    $breadcrumb[] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $aktif_kategori_adi,
        'item' => $base_url . '/blog/' . $aktif_kategori_slug . '/'
    ];
}
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    '@id' => $blog_url . '#breadcrumb',
    'itemListElement' => $breadcrumb
];

// ---------- 5) FAQPage (SSS bölümü) ----------
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
            '@id' => $blog_url . '#faq',
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

<!-- Blog Header & Arama -->
<section style="background: linear-gradient(180deg, #020617 0%, #0f172a 100%); color: #ffffff; padding: 30px 20px 30px 20px; position: relative; overflow: visible; border-bottom: 1px solid #1e293b;">
    <div style="max-width: 850px; margin: 0 auto; text-align: center; position: relative; z-index: 10;">
        
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 16px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 9999px; color: #60a5fa; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 14px;">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: #38bdf8;"></span>
            <?php echo $U['klinik_makale']; ?>
        </div>
        
        <h1 style="font-size: clamp(26px, 3.5vw, 38px); font-weight: 900; color: #ffffff; margin: 0 0 10px 0; line-height: 1.2;">
            <?php echo $U['agiz_dis_sagligi']; ?> <span style="color: #38bdf8;"><?php echo $U['rehberi']; ?></span>
        </h1>
        
        <p style="color: #94a3b8; font-size: 15px; max-width: 520px; margin: 0 auto 24px auto; line-height: 1.5;">
            <?php echo $U['header_aciklama']; ?>
        </p>

        <form action="/blog/" method="GET" style="position: relative; max-width: 620px; margin: 0 auto; text-align: left;">
            <div style="display: flex; align-items: center; background: #1e293b; border: 2px solid #334155; border-radius: 15px; padding: 7px 14px; box-shadow: 0 12px 35px -5px rgba(0,0,0,0.5);">
                
                <div style="color: #38bdf8; display: flex; align-items: center; padding-left: 4px; padding-right: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                
                <input type="text" id="blogSearchInput" name="q" placeholder="<?php echo $U['arama_placeholder']; ?>" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 100%; background: transparent; border: none; outline: none; color: #ffffff; font-size: 16px; font-weight: 500; padding: 7px 0;" autocomplete="off">
                
                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="/blog/" id="aramaTemizle" class="<?php echo empty($search_query) ? 'hidden' : ''; ?>" style="width: 26px; height: 26px; border-radius: 7px; background: #334155; border: none; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </a>
                    
                    <span id="aramaSonucSayaci" style="background: #2563eb; color: #ffffff; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 7px;"><?php echo $total_posts; ?></span>
                </div>
            </div>

            <div style="margin-top: 12px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; justify-content: center;">
                <span style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;"><?php echo $U['onerilenler']; ?></span>
                <a href="/blog/?q=implant" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; text-decoration: none;">🦷 <?php echo $U['implant']; ?></a>
                <a href="/blog/?q=zirkonyum" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; text-decoration: none;">✨ <?php echo $U['zirkonyum']; ?></a>
                <a href="/blog/?q=g%C3%BCl%C3%BC%C5%9F+tasar%C4%B1m%C4%B1" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; text-decoration: none;">😁 <?php echo $U['gulus_tasarimi']; ?></a>
                <a href="/blog/?q=kanal+tedavisi" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; text-decoration: none;">🩺 <?php echo $U['kanal_tedavisi']; ?></a>
            </div>
        </form>

    </div>
</section>

<div class="w-full mx-auto py-10" style="padding-left: clamp(20px, 4vw, 50px); padding-right: clamp(20px, 4vw, 50px); max-width: 1750px;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- SOL SIDEBAR -->
        <aside class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-xl border border-slate-200/80">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        <?php echo $U['kategoriler']; ?>
                    </h3>
                    <span class="text-[11px] font-bold text-slate-400 bg-slate-100 px-2.5 py-0.5 rounded-md">
                        <?php echo count($kategoriler_db) + 1; ?>
                    </span>
                </div>

                <div class="space-y-2">
                    <?php
                    $tumu_count = count($blog_yazilar);
                    $is_active_tumu = ($aktif_kategori_slug === 'tumu' || $aktif_kategori_adi === 'tumu');
                    ?>
                    <a href="/blog/" class="kategori-link w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border <?php echo $is_active_tumu ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/25 ring-2 ring-blue-600/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'; ?>">
                        <span class="flex items-center gap-2.5 min-w-0">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0 <?php echo $is_active_tumu ? 'text-white' : 'text-blue-600'; ?>">
                                <?php echo getBlogIcon('FileText', 15); ?>
                            </span>
                            <span class="truncate"><?php echo $U['tum_yazilar']; ?></span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 <?php echo $is_active_tumu ? 'bg-white/20 text-white' : 'bg-white text-slate-700 border border-slate-200'; ?>">
                            <?php echo $tumu_count; ?>
                        </span>
                    </a>

                    <?php foreach($kategoriler_db as $kat): 
                        $kat_adi = trim($kat['kategori_adi']);
                        $kat_slug = trim($kat['kategori_slug']);
                        $is_active = ($aktif_kategori_slug === $kat_slug || mb_strtolower($aktif_kategori_adi, 'UTF-8') === mb_strtolower($kat_adi, 'UTF-8'));
                        
                        $kat_sayac = 0;
                        foreach($blog_yazilar as $yazi) {
                            $y_kat = trim($yazi['kategori']);
                            $y_slug = createSlugBlog($y_kat);
                            $target_slug1 = createSlugBlog($kat_adi);
                            $target_slug2 = createSlugBlog($kat_slug);
                            if($y_slug === $target_slug1 || $y_slug === $target_slug2 || strpos($y_slug, $target_slug1) !== false || strpos($target_slug1, $y_slug) !== false || ($target_slug2 === 'cene-eklemi' && strpos($y_slug, 'protetik') !== false)) {
                                $kat_sayac++;
                            }
                        }
                    ?>
                    <a href="/blog/<?php echo htmlspecialchars($kat_slug); ?>" class="kategori-link w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border <?php echo $is_active ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/25 ring-2 ring-blue-600/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'; ?>">
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
                    <?php echo $U['populer_yazilar']; ?>
                </h3>
                <div class="space-y-3">
                    <?php foreach($populer_yazilar as $index => $post): 
                        $p_kat = trim($post['kategori']);
                        $pop_k_slug = createSlugBlog($p_kat);
                        foreach($kategoriler_db as $kat) {
                            if(trim($kat['kategori_adi']) === $p_kat) {
                                $pop_k_slug = trim($kat['kategori_slug']);
                                break;
                            }
                        }
                        if ($pop_k_slug === 'protetik-dis-tedavisi-gnatoloji') $pop_k_slug = 'cene-eklemi';
                    ?>
                    <div class="flex items-center gap-3 cursor-pointer group p-2.5 rounded-2xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-md shrink-0">
                            <?php echo $index+1; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 group-hover:text-blue-600 line-clamp-2 transition-colors leading-snug">
                                <a href="/blog/<?php echo htmlspecialchars($pop_k_slug); ?>/<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars(t_cevir($post['baslik'])); ?></a>
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
                <h3 class="font-extrabold text-base mb-1"><?php echo $U['bulten_katil']; ?></h3>
                <p class="text-blue-100 text-xs mb-4 leading-relaxed"><?php echo $U['bulten_aciklama']; ?></p>
                <form action="/api/blog-abone.php" method="POST" class="space-y-2.5">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="email" name="email" required placeholder="<?php echo $U['eposta_adresiniz']; ?>" class="w-full px-3.5 py-2.5 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder:text-white/60 text-xs focus:outline-none focus:ring-2 focus:ring-white/40 transition-all">
                    <button type="submit" class="w-full py-2.5 bg-white text-blue-700 font-bold text-xs rounded-xl hover:bg-blue-50 transition-colors shadow-md"><?php echo $U['abone_ol']; ?></button>
                </form>
            </div>
        </aside>

        <!-- SAĞ İÇERİK ALANI -->
        <section class="lg:col-span-9 space-y-8">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                <div>
                    <h2 class="text-2xl md:text-3xl font-black text-slate-900">
                        <?php if ($aktif_kategori_adi == 'tumu'): ?>
                            📝 <?php echo $U['tum_makaleler']; ?>
                        <?php else: ?>
                            📂 <?php echo htmlspecialchars(t_cevir($aktif_kategori_adi)); ?>
                        <?php endif; ?>
                        <span class="text-blue-600 text-sm ml-2 font-bold">(<?php echo $total_posts; ?> <?php echo $U['makale']; ?>)</span>
                    </h2>
                    <p class="text-xs md:text-sm text-slate-500 mt-1"><?php echo $U['secili_kriterler']; ?></p>
                </div>
                <?php if($aktif_kategori_adi != 'tumu'): ?>
                <a href="/blog/" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-3.5 py-2 rounded-xl transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                    <?php echo $U['tumunu_goster']; ?>
                </a>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                <?php if(count($gosterilecek_yazilar) > 0): ?>
                    <div class="flex flex-col">
                        <?php echo renderBlogSutun($blog_sol_sutun, $kategoriler_db); ?>
                    </div>
                    <div class="flex flex-col">
                        <?php echo renderBlogSutun($blog_sag_sutun, $kategoriler_db); ?>
                    </div>
                <?php else: ?>
                    <div class="col-span-full py-20 text-center bg-white border-2 border-dashed border-slate-200 rounded-3xl">
                        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-blue-500">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-1"><?php echo $U['eslesen_yok']; ?></h3>
                        <p class="text-sm text-slate-500"><?php echo $U['eslesen_yok_desc']; ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SAYFALAMA -->
            <?php if($toplam_sayfa > 1): ?>
            <div class="pt-8 flex justify-center">
                <nav class="inline-flex items-center gap-2 bg-white p-2 rounded-2xl border border-slate-200 shadow-sm">
                    <?php 
                    $base_pagination_url = ($aktif_kategori_slug !== 'tumu') ? '/blog/' . $aktif_kategori_slug . '/' : '/blog/';
                    if(!empty($search_query)) {
                        $base_pagination_url = '/blog/?q=' . urlencode($search_query) . '&';
                    }
                    ?>
                    
                    <?php if($sayfa > 1): ?>
                    <a href="<?php echo $base_pagination_url . ($aktif_kategori_slug !== 'tumu' && empty($search_query) ? '?sayfa=' . ($sayfa - 1) : 'sayfa=' . ($sayfa - 1)); ?>" class="sayfa-link p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition-all">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                    <?php endif; ?>

                    <?php for($i = max(1, $sayfa - 2); $i <= min($toplam_sayfa, $sayfa + 2); $i++): 
                        $page_link = ($aktif_kategori_slug !== 'tumu' && empty($search_query)) ? '/blog/' . $aktif_kategori_slug . ($i > 1 ? '?sayfa=' . $i : '') : '/blog/?' . (!empty($search_query) ? 'q=' . urlencode($search_query) . '&' : '') . ($i > 1 ? 'sayfa=' . $i : '');
                    ?>
                    <a href="<?php echo $page_link; ?>" class="sayfa-link w-10 h-10 flex items-center justify-center rounded-xl text-sm font-bold transition-all <?php echo $i == $sayfa ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-600 hover:bg-slate-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if($sayfa < $toplam_sayfa): 
                        $next_link = ($aktif_kategori_slug !== 'tumu' && empty($search_query)) ? '/blog/' . $aktif_kategori_slug . '?sayfa=' . ($sayfa + 1) : '/blog/?' . (!empty($search_query) ? 'q=' . urlencode($search_query) . '&' : '') . 'sayfa=' . ($sayfa + 1);
                    ?>
                    <a href="<?php echo $next_link; ?>" class="sayfa-link p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition-all">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                    <?php endif; ?>
                </nav>
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
                <?php echo $U['klinik_rehberi']; ?>
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight"><?php echo $U['merak_edilenler']; ?></h2>
            <p class="text-slate-500 text-sm md:text-base mt-2 max-w-xl mx-auto"><?php echo $U['sss_aciklama']; ?></p>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const savedScrollY = sessionStorage.getItem('blogScrollY');
    if (savedScrollY !== null) {
        window.scrollTo(0, parseInt(savedScrollY, 10));
        sessionStorage.removeItem('blogScrollY');
    }

    const kayitTetikleyicileri = document.querySelectorAll('.kategori-link, .sayfa-link, #blogSearchForm');
    kayitTetikleyicileri.forEach(el => {
        el.addEventListener('click', function() {
            sessionStorage.setItem('blogScrollY', window.scrollY);
        });
    });

    const aramaFormu = document.getElementById('blogSearchForm');
    if (aramaFormu) {
        aramaFormu.addEventListener('submit', function() {
            sessionStorage.setItem('blogScrollY', window.scrollY);
        });
    }
});

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

<?php include dirname(__DIR__) . '/inc/footer.php'; ?>

<?php
function getBlogIcon($name, $size = 16) {
    if (empty($name)) $name = 'FileText';
    $icons = [
        'Sparkles' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><circle cx="4" cy="20" r="2"/></svg>',
        'Syringe' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3c-.4.4-1 .6-1.6.6H5a2 2 0 0 1-2-2v-2.1c0-.6.2-1.2.6-1.6L15 5"/><path d="M10 5 8 7"/><path d="M14 9l-2 2"/><path d="M3 21h18"/></svg>',
        'Activity' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9-4-18-3 9H2"/></svg>',
        'Shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>',
        'Smile' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="15" x2="15.01" y1="9" y2="9"/><line x1="9" x2="9.01" y1="9" y2="9"/></svg>',
        'FileText' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>'
    ];
    return isset($icons[$name]) ? $icons[$name] : $icons['FileText'];
}
?>