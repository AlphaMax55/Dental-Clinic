<?php
// www/tedavi-detay.php

// ========== 1. DEFANSİF SEOMATİK VE GÜVENLİK KORUMASI (EN ÜST KATMAN) ==========
require_once 'inc/config.php';

// ============================================================
// 🔥 KATEGORİ SLUG KONTROLÜ (YENİ)
// Gelen slug bir tedavi DEĞİL de kategori ise, tedaviler/index.php'ye devret.
// (htaccess tüm /tedaviler/xxx/ isteklerini buraya yönlendiriyor)
// ============================================================
if (!empty($_GET['slug'])) {
    $kategori_sluglari = [
        'estetik-dis-hekimligi',
        'cerrahi-implantoloji',
        'protetik-dis-tedavisi',
        'ortodonti-cene',
    ];
    if (in_array(trim($_GET['slug']), $kategori_sluglari, true)) {
        $_GET['k'] = trim($_GET['slug']);
        unset($_GET['slug']);
        require __DIR__ . '/tedaviler/index.php';
        exit;
    }
}

// Slug parametresini al ve temizle
$slug_parametresi = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// 1. Senaryo: Slug parametresi hiç yoksa veya boşsa -> Doğrudan 404 Kodu Ver
if (empty($slug_parametresi)) {
    http_response_code(404);
    header('HTTP/1.1 404 Not Found');
    header('Status: 404 Not Found');
    header('X-Robots-Tag: noindex, nofollow');
    
    $page_title = "Sayfa Bulunamadı";
    include 'inc/header.php';
    echo "<div class='container mx-auto px-4 py-20 text-center'><h1 class='text-4xl font-bold mb-4'>404 - Geçersiz İstek</h1><p class='text-gray-600'>Aradığınız tedavi adresi eksik veya hatalı biçimlendirilmiş.</p><a href='/tedaviler/' class='inline-block mt-6 px-6 py-3 bg-blue-600 text-white rounded-xl'>Tedavilere Dön</a></div>";
    include 'inc/footer.php';
    exit();
}

// 2. Senaryo: Slug var ama veritabanında aktif/silinmemiş karşılığı var mı kontrol et
try {
    $sorgu_doğrulama = $db->prepare("SELECT id FROM tedaviler WHERE slug = :slug AND aktif = 1 AND silindi = 0 LIMIT 1");
    $sorgu_doğrulama->execute([':slug' => $slug_parametresi]);
    $kayit_kontrol = $sorgu_doğrulama->fetch();
    
    if (!$kayit_kontrol) {
        http_response_code(404);
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
        header('X-Robots-Tag: noindex, nofollow');
        
        $page_title = "Sayfa Bulunamadı";
        include 'inc/header.php';
        echo "<div class='container mx-auto px-4 py-20 text-center'><h1 class='text-4xl font-bold mb-4'>404 - Tedavi Bulunamadı</h1><p class='text-gray-600'>Aradığınız tedavi sayfası mevcut değil veya kaldırılmış olabilir.</p><a href='/tedaviler/' class='inline-block mt-6 px-6 py-3 bg-blue-600 text-white rounded-xl'>Tedavilere Dön</a></div>";
        include 'inc/footer.php';
        exit();
    }
} catch (PDOException $hata) {
    http_response_code(404);
    header('HTTP/1.1 404 Not Found');
    header('X-Robots-Tag: noindex, nofollow');
    
    $page_title = "Sistem Hatası";
    include 'inc/header.php';
    echo "<div class='container mx-auto px-4 py-20 text-center'><h1 class='text-4xl font-bold mb-4'>404 - Erişim Hatası</h1><p class='text-gray-600'>Şu anda sistemde teknik bir çalışma yürütülüyor.</p></div>";
    include 'inc/footer.php';
    exit();
}

// ========== 2. OTURUM VE DİL YÖNETİMİ ==========
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Dil kontrolü
$lang = $_SESSION['dil'] ?? 'tr';
$dil_en = ($lang === 'en');

// Veritabanından tedaviyi çek
$stmt = $db->prepare("SELECT * FROM tedaviler WHERE slug = ? AND silindi = 0 AND aktif = 1");
$stmt->execute([$slug_parametresi]);
$tedavi_raw = $stmt->fetch();

// ========== 3. DİL DESTEKLİ ALANLARI ÇÖZ (FONKSİYONLAR) ==========
if (!function_exists('getLangValue')) {
    function getLangValue($json_str, $lang, $default = '') {
        if (empty($json_str)) return $default;
        $data = json_decode($json_str, true);
        if (is_array($data)) {
            return $data[$lang] ?? $data['tr'] ?? $default;
        }
        return $json_str;
    }
}

// Tarih formatlayıcı
if (!function_exists('tarihGoster')) {
    function tarihGoster($tarih, $lang = 'tr') {
        if (empty($tarih) || $tarih === '0000-00-00') return '';
        $ts = strtotime($tarih);
        if (!$ts) return '';
        return date('d.m.Y', $ts);
    }
}

$tedavi = [
    'id' => $tedavi_raw['id'],
    'slug' => $tedavi_raw['slug'],
    'video_url' => $tedavi_raw['video_url'],
    'instagram_url' => $tedavi_raw['instagram_url'] ?? '',
    'sure' => getLangValue($tedavi_raw['sure'], $lang, $tedavi_raw['sure']),
    'kategori_ham' => $tedavi_raw['kategori'],
    'kategori' => getLangValue($tedavi_raw['kategori'], $lang, $tedavi_raw['kategori']),
    'baslik' => getLangValue($tedavi_raw['baslik'], $lang, $tedavi_raw['baslik']),
    'kisa_aciklama' => getLangValue($tedavi_raw['kisa_aciklama'], $lang, $tedavi_raw['kisa_aciklama']),
    'detayli_aciklama' => getLangValue($tedavi_raw['detayli_aciklama'], $lang, $tedavi_raw['detayli_aciklama']),
];

// 🔥 Teknikleri parse et
$teknikler_raw = json_decode($tedavi_raw['teknikler_json'] ?? '', true) ?: [];
$teknikler = [];
foreach ($teknikler_raw as $tek) {
    if (!is_array($tek)) continue;
    $teknikler[] = [
        'baslik' => getLangValue($tek['baslik'] ?? '', $lang, $tek['baslik'] ?? ''),
        'aciklama' => getLangValue($tek['aciklama'] ?? '', $lang, $tek['aciklama'] ?? '')
    ];
}

// ========== 4. SEO META DEĞİŞKENLERİ ==========
$seo_title_tr = $tedavi_raw['seo_title_tr'] ?? '';
$seo_title_en = $tedavi_raw['seo_title_en'] ?? '';
$seo_description_tr = $tedavi_raw['seo_description_tr'] ?? '';
$seo_description_en = $tedavi_raw['seo_description_en'] ?? '';
$seo_keywords_tr = $tedavi_raw['seo_keywords_tr'] ?? '';
$seo_keywords_en = $tedavi_raw['seo_keywords_en'] ?? '';
$seo_canonical = $tedavi_raw['seo_canonical'] ?? '';
$seo_robots = $tedavi_raw['seo_robots'] ?? 'index, follow';

$page_title = ($lang === 'en' && !empty($seo_title_en)) 
    ? $seo_title_en 
    : ($seo_title_tr ?: $tedavi['baslik'] . ' | Prof. Dr. İbrahim Duran');

$seo_description = ($lang === 'en' && !empty($seo_description_en)) 
    ? $seo_description_en 
    : ($seo_description_tr ?: strip_tags($tedavi['kisa_aciklama']));

$site_keywords = ($lang === 'en' && !empty($seo_keywords_en)) 
    ? $seo_keywords_en 
    : ($seo_keywords_tr ?: '');

$dynamic_og_title = $page_title;

// OG image
$og_image = '/uploads/default-og.jpg';
if (!empty($tedavi_raw['id'])) {
    $stmtOg = $db->prepare("SELECT after_resim FROM tedavi_slider WHERE tedavi_id = ? ORDER BY sira ASC LIMIT 1");
    $stmtOg->execute([$tedavi_raw['id']]);
    $ogRow = $stmtOg->fetch();
    if ($ogRow && !empty($ogRow['after_resim'])) {
        $og_image = $ogRow['after_resim'];
    } elseif (!empty($tedavi_raw['sonrasi_resim'])) {
        $og_image = $tedavi_raw['sonrasi_resim'];
    }
}

$robots_etiketi_icerigi = '<meta name="robots" content="' . htmlspecialchars($seo_robots) . '">';

if (!empty($seo_canonical)) {
    $mevcut_canonical_link = $seo_canonical;
} else {
    $mevcut_canonical_link = 'adres gir/tedaviler/' . $tedavi['slug'];
}

// JSON dizilerini çöz
$galeri = json_decode($tedavi_raw['galeri_json'], true) ?: [];
$avantajlar_raw = json_decode($tedavi_raw['avantajlar_json'], true) ?: [];
$sss_raw = json_decode($tedavi_raw['sss_json'], true) ?: [];
$adimlar_raw = json_decode($tedavi_raw['adimlar_json'], true) ?: [];

$avantajlar = [];
foreach($avantajlar_raw as $av) {
    $avantajlar[] = getLangValue($av, $lang, $av);
}

$sss = [];
foreach($sss_raw as $item) {
    $soru_raw = is_array($item['soru']) ? json_encode($item['soru']) : $item['soru'];
    $cevap_raw = is_array($item['cevap']) ? json_encode($item['cevap']) : $item['cevap'];
    
    $sss[] = [
        'soru' => getLangValue($soru_raw, $lang, $item['soru']),
        'cevap' => getLangValue($cevap_raw, $lang, $item['cevap'])
    ];
}

$adimlar = [];
foreach($adimlar_raw as $item) {
    $baslik = is_array($item['baslik']) ? getLangValue(json_encode($item['baslik']), $lang, $item['baslik']) : getLangValue($item['baslik'], $lang, $item['baslik']);
    $aciklama = is_array($item['aciklama']) ? getLangValue(json_encode($item['aciklama']), $lang, $item['aciklama']) : getLangValue($item['aciklama'], $lang, $item['aciklama']);
    $adimlar[] = [
        'no' => $item['no'],
        'baslik' => $baslik,
        'aciklama' => $aciklama
    ];
}

// Tüm tedavileri çek
$stmt = $db->prepare("SELECT * FROM tedaviler WHERE silindi = 0 AND aktif = 1 ORDER BY sira ASC");
$stmt->execute();
$tumTedaviler_raw = $stmt->fetchAll();

$sliderMap = [];
$stmtSliders = $db->query("SELECT tedavi_id, after_resim FROM tedavi_slider ORDER BY tedavi_id, sira ASC");
while ($row = $stmtSliders->fetch()) {
    if (!isset($sliderMap[$row['tedavi_id']])) {
        $sliderMap[$row['tedavi_id']] = $row['after_resim'];
    }
}

$tumTedaviler = [];
foreach($tumTedaviler_raw as $row) {
    $tumTedaviler[] = [
        'id' => $row['id'],
        'slug' => $row['slug'],
        'kategori_ham' => $row['kategori'],
        'kategori' => getLangValue($row['kategori'], $lang, $row['kategori']),
        'baslik' => getLangValue($row['baslik'], $lang, $row['baslik']),
        'kisa_aciklama' => getLangValue($row['kisa_aciklama'], $lang, $row['kisa_aciklama']),
        'image' => $sliderMap[$row['id']] ?? $row['sonrasi_resim'] ?? ''
    ];
}

$digerTedaviler = array_filter($tumTedaviler, function($t) use ($tedavi) {
    return $t['kategori_ham'] == $tedavi['kategori_ham'] && $t['slug'] != $tedavi['slug'];
});

$kategoriKisa = '';
if(strpos($tedavi['kategori_ham'], 'Estetik') !== false) $kategoriKisa = ($lang === 'en') ? 'COSMETIC' : 'ESTETİK';
elseif(strpos($tedavi['kategori_ham'], 'Cerrahi') !== false) $kategoriKisa = ($lang === 'en') ? 'SURGERY' : 'CERRAHİ';
elseif(strpos($tedavi['kategori_ham'], 'Protetik') !== false) $kategoriKisa = ($lang === 'en') ? 'PROSTHETIC' : 'PROTETİK';
elseif(strpos($tedavi['kategori_ham'], 'Ortodonti') !== false) $kategoriKisa = ($lang === 'en') ? 'ORTHODONTICS' : 'ORTODONTİ';
else $kategoriKisa = strtoupper(substr($tedavi['kategori'], 0, 15));

$ozet_sure_etiket = ($lang === 'en') ? 'Duration' : 'Süre';


$telefon = $ayarlar['header1_telefon'] ?? '+90 533 086 91 67';

// ============================================================
// ========== 🔥 SLIDER SETLERİNİ ÇEK (İŞLEM BİLGİLERİ DAHİL) ==========
// ============================================================
$sliderSets = [];
if (!empty($tedavi_raw['id'])) {
    $stmtSlider = $db->prepare("SELECT * FROM tedavi_slider WHERE tedavi_id = ? ORDER BY sira ASC");
    $stmtSlider->execute([$tedavi_raw['id']]);
    $sliderSets = $stmtSlider->fetchAll(PDO::FETCH_ASSOC);
}
// Fallback: eski oncesi_resim/sonrasi_resim
if (empty($sliderSets)) {
    if (!empty($tedavi_raw['oncesi_resim']) || !empty($tedavi_raw['sonrasi_resim'])) {
        $sliderSets[] = [
            'before_resim' => $tedavi_raw['oncesi_resim'] ?? '',
            'after_resim'  => $tedavi_raw['sonrasi_resim'] ?? '',
            'islem'        => '',
            'alt_baslik_json' => '',
            'islem_tarihi' => '',
            'goruntuleme_tarihi' => '',
            'sira'         => 1
        ];
    }
}
$sliderCount = count($sliderSets);
$isSliderActive = ($sliderCount > 1);

$page_slug = 'tedavi-detay';
$tedavi_slug = $slug_parametresi;

/* ==================================================================
 * 🎯 SAYFAYA ÖZEL SCHEMA'LAR (Tedavi Detay)
 * ================================================================== */
$tum_schemalar = [];
$site_adresi = 'adres gir';

// Tedavi URL'si
$tedavi_url = $site_adresi . '/tedaviler/' . $tedavi['slug'];

// Kategori → medicalSpecialty eşleştirmesi
$medical_specialty = 'Dentistry';
switch ($tedavi['kategori_ham']) {
    case 'Estetik Diş Hekimliği':
        $medical_specialty = 'CosmeticDentistry';
        break;
    case 'Cerrahi & İmplantoloji':
        $medical_specialty = 'OralSurgery';
        break;
    case 'Protetik Diş Tedavisi':
        $medical_specialty = 'Prosthodontics';
        break;
    case 'Ortodonti & Çene':
        $medical_specialty = 'Orthodontic';
        break;
}

// Tedavi ana resmi (ilk slider after_resim)
$tedavi_resim = '';
if (!empty($sliderSets[0]['after_resim'])) {
    $tedavi_resim = $sliderSets[0]['after_resim'];
} elseif (!empty($tedavi_raw['sonrasi_resim'])) {
    $tedavi_resim = $tedavi_raw['sonrasi_resim'];
}
if (!empty($tedavi_resim) && strpos($tedavi_resim, 'http') !== 0) {
    $tedavi_resim = $site_adresi . '/' . ltrim($tedavi_resim, '/');
}

// ---------- 1) MedicalProcedure ----------
$procedure_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'MedicalProcedure',
    '@id' => $tedavi_url . '#procedure',
    'name' => $tedavi['baslik'],
    'description' => mb_substr(strip_tags($tedavi['kisa_aciklama']), 0, 300),
    'url' => $tedavi_url,
    'procedureType' => 'https://schema.org/NoninvasiveProcedure',
    'bodyLocation' => $dil_en ? 'Mouth and Teeth' : 'Ağız ve Diş',
    'howPerformed' => !empty($tedavi['detayli_aciklama']) 
        ? mb_substr(strip_tags($tedavi['detayli_aciklama']), 0, 1500) 
        : strip_tags($tedavi['kisa_aciklama']),
    'medicalSpecialty' => $medical_specialty,
    'provider' => [
        '@id' => $site_adresi . '/#medicalbusiness'
    ],
    'areaServed' => [
        '@type' => 'City',
        'name' => 'Samsun'
    ],
    'inLanguage' => $dil_en ? 'en-US' : 'tr-TR'
];

if (!empty($tedavi_resim)) {
    $procedure_schema['image'] = [
        '@type' => 'ImageObject',
        'url' => $tedavi_resim,
        'width' => 800,
        'height' => 600
    ];
}

if (!empty($tedavi['sure'])) {
    $procedure_schema['procedureDuration'] = $tedavi['sure'];
}

$tum_schemalar[] = $procedure_schema;

// ---------- 2) FAQPage ----------
if (!empty($sss)) {
    $faq_items = [];
    $gorulen = [];
    foreach ($sss as $s) {
        $soru = strip_tags(trim($s['soru'] ?? ''));
        $cevap = strip_tags(trim($s['cevap'] ?? ''));
        if (empty($soru) || empty($cevap)) continue;
        
        $anahtar = mb_strtolower($soru, 'UTF-8');
        if (in_array($anahtar, $gorulen)) continue;
        $gorulen[] = $anahtar;
        
        $faq_items[] = [
            '@type' => 'Question',
            'name' => $soru,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $cevap
            ]
        ];
    }
    
    if (!empty($faq_items)) {
        $tum_schemalar[] = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faq_items
        ];
    }
}

// ---------- 3) HowTo (Tedavi Adımları) ----------
if (!empty($adimlar)) {
    $howto_steps = [];
    foreach ($adimlar as $adim) {
        $step_name = strip_tags(trim($adim['baslik'] ?? ''));
        $step_text = strip_tags(trim($adim['aciklama'] ?? ''));
        if (empty($step_name)) continue;
        
        $howto_steps[] = [
            '@type' => 'HowToStep',
            'position' => intval($adim['no']),
            'name' => $step_name,
            'text' => $step_text
        ];
    }
    
    if (!empty($howto_steps)) {
        $howto_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $tedavi['baslik'] . ' - ' . ($dil_en ? 'Treatment Steps' : 'Tedavi Adımları'),
            'description' => mb_substr(strip_tags($tedavi['kisa_aciklama']), 0, 300),
            'step' => $howto_steps
        ];
        
        if (!empty($tedavi_resim)) {
            $howto_schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $tedavi_resim
            ];
        }
        
        $tum_schemalar[] = $howto_schema;
    }
}

// ---------- 4) BreadcrumbList ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => $dil_en ? 'Home' : 'Anasayfa',
            'item' => $site_adresi . '/'
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $dil_en ? 'Treatments' : 'Tedaviler',
            'item' => $site_adresi . '/tedaviler/'
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $tedavi['baslik'],
            'item' => $tedavi_url
        ]
    ]
];

// ---------- Schema'ları Pretty Print ile topla ----------
$sayfa_schemalari = '';
foreach ($tum_schemalar as $schema) {
    $sayfa_schemalari .= '<script type="application/ld+json">' . "\n"
                       . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                       . "\n" . '</script>' . "\n";
}

include 'inc/header.php';
?>

<style>
.expand-content { transition: all 0.3s ease; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.faq-answer { transition: all 0.3s ease; }
</style>

<main class="min-h-screen bg-white w-full font-sans selection:bg-cyan-100 selection:text-cyan-900">

<!-- HERO SECTION -->
<section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 text-white overflow-hidden">
    <div class="relative max-w-[1780px] mx-auto px-4 lg:px-8 py-8 lg:py-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            
            <!-- SOL TARAF - METİN -->
            <div>
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="px-4 py-1.5 bg-yellow-300 text-blue-900 rounded-full text-xs font-black"><?php echo htmlspecialchars(t_cevir($tedavi['kategori'])); ?></span>
                    <?php if($tedavi['sure']): ?>
                    <span class="flex items-center gap-1.5 text-blue-200 text-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?php echo htmlspecialchars(t_cevir($tedavi['sure'])); ?>
                    </span>
                    <?php endif; ?>
                </div>
<h1 class="text-5xl lg:text-4xl font-black tracking-tighter leading-[1.1] mb-3"><?php echo htmlspecialchars(t_cevir($tedavi['baslik'])); ?></h1>

<?php 
// 🔥 İlk slider'ın kendi alt başlığı (JSON destekli, TR/EN). Boşsa gösterilmez.
$ilk_slide_alt = '';
if (!empty($sliderSets[0])) {
    $ilk_slide_alt = getLangValue($sliderSets[0]['alt_baslik_json'] ?? '', $lang, '');
}
$ilk_slide_alt = trim($ilk_slide_alt);
?>
<h2 id="heroAltBaslik" 
    class="text-lg lg:text-xl font-medium text-yellow-300 tracking-tight leading-snug mb-6 italic transition-all duration-500 <?php echo empty($ilk_slide_alt) ? 'hidden opacity-0' : 'opacity-100'; ?>">
    ✦ <span id="heroAltBaslikText"><?php echo htmlspecialchars($ilk_slide_alt); ?></span>
</h2>

                <p class="text-xl text-blue-200 leading-relaxed mb-8 max-w-2xl border-l-4 border-yellow-300 pl-6 py-2"><?php echo htmlspecialchars(t_cevir($tedavi['kisa_aciklama'])); ?></p>
                
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                        <svg class="w-6 h-6 mx-auto mb-2 text-yellow-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <div class="text-xs font-medium text-blue-200"><?php echo t_cevir($ozet_sure_etiket); ?></div>
                        <div class="text-sm font-bold"><?php echo htmlspecialchars(t_cevir($tedavi['sure'] ?: ($lang === 'en' ? 'Custom' : 'Kişiye özel'))); ?></div>
                    </div>

                </div>

                <div class="flex flex-wrap gap-4">
                    <a href="/iletisim/" class="flex items-center gap-2 px-8 py-4 bg-yellow-300 text-blue-900 font-bold rounded-xl hover:bg-yellow-400 transition-all shadow-lg">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?php echo t_cevir('İletişime Geç'); ?>
                    </a>
                    <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $telefon); ?>" class="flex items-center justify-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm border border-white/30 text-white font-bold rounded-xl hover:bg-white/20 transition-all">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <?php echo htmlspecialchars($telefon); ?>
                    </a>
                </div>
            </div>

<!-- SAĞ TARAF - SLIDER -->
            <div class="relative w-full overflow-hidden rounded-2xl">
                <div class="flex transition-transform duration-700 ease-in-out w-full" id="sliderTrack" style="transform: translateX(0%);">
                    <?php foreach($sliderSets as $index => $set): 
                        $beforeImage = !empty($set['before_resim']) ? $set['before_resim'] : '';
                        $afterImage  = !empty($set['after_resim']) ? $set['after_resim'] : '';
                        $islemAdi = trim($set['islem'] ?? '');
                        $islemTarihi = trim($set['islem_tarihi'] ?? '');
                        $goruntulemeTarihi = trim($set['goruntuleme_tarihi'] ?? '');
                        $hasIslemInfo = ($islemAdi !== '' || $islemTarihi !== '' || $goruntulemeTarihi !== '');
                        
                        // 🔥 Slider'ın kendi alt başlığı (JSON destekli, TR/EN). Boşsa gösterme.
                        $slideAlt = trim(getLangValue($set['alt_baslik_json'] ?? '', $lang, ''));
                    ?>
                    <div class="w-full min-w-full flex-shrink-0 flex flex-col px-2 box-border" 
                         data-alt-baslik="<?php echo htmlspecialchars($slideAlt); ?>">
                        
                        <div class="flex items-center justify-center gap-4">
                            
                            <div class="flex-1 flex flex-col items-center min-w-0">
<div class="w-full aspect-[4/3] relative rounded-[2rem] overflow-hidden bg-transparent transition-all duration-500 hover:rotate-[15deg] hover:scale-110 cursor-pointer z-10">
    <?php if(!empty($beforeImage)): ?>
    <img src="<?php echo htmlspecialchars($beforeImage); ?>" 
         alt="<?php echo htmlspecialchars(t_cevir($tedavi['baslik'])) . ' - ' . t_cevir('Klinik Kayıt'); ?>" 
         class="absolute inset-0 w-full h-full object-contain block">
    <?php endif; ?>
</div>
                                <div class="mt-6 w-full p-4 relative overflow-hidden rounded-2xl border border-white/15 bg-gradient-to-br from-blue-900/90 via-slate-800/90 to-blue-900/90 backdrop-blur-xl group">
                                    <div class="relative z-10 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-yellow-400/20 to-yellow-500/10 border border-yellow-400/20 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-sm sm:text-base font-extrabold text-white block truncate tracking-tight group-hover:text-yellow-300 transition-colors">
                                                    <?php echo t_cevir('Vaka Başlangıcı'); ?> 
                                                </span>
                                            </div>
                                        </div>
                                        <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

               
                            <div class="flex-1 flex flex-col items-center min-w-0">
                                <div class="w-full aspect-[4/3] relative rounded-[2rem] overflow-hidden bg-transparent transition-all duration-500 hover:rotate-[15deg] hover:scale-110 cursor-pointer z-10">
                                    <?php if(!empty($afterImage)): ?>
                                    <img src="<?php echo htmlspecialchars($afterImage); ?>" 
                                         alt="<?php echo htmlspecialchars(t_cevir($tedavi['baslik'])) . ' - ' . t_cevir('Restorasyon'); ?>"  
                                         class="absolute inset-0 w-full h-full object-contain block">
                                    <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-blue-800/50">
                                        <div class="text-center text-white/60">
                                            <svg class="w-14 h-14 mx-auto mb-2 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                            <span class="text-sm font-medium"><?php echo t_cevir('Sonra'); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
								
                                <div class="mt-6 w-full p-4 relative overflow-hidden rounded-2xl border border-white/15 bg-gradient-to-br from-blue-900/90 via-slate-800/90 to-blue-900/90 backdrop-blur-xl group">
                                    <div class="relative z-10 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-yellow-400/20 to-yellow-500/10 border border-yellow-400/20 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-sm sm:text-base font-extrabold text-white block truncate tracking-tight group-hover:text-yellow-300 transition-colors">
                                                    <?php echo t_cevir('Restorasyon'); ?> 
                                                </span>
                                            </div>
                                        </div>
                                        <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                        </div>										
                                    </div>									
                                </div>								
                            </div>

					  </div>

                        <?php if($hasIslemInfo): ?>
                        <div class="mt-1 flex flex-row flex-wrap justify-center items-center gap-x-6 gap-y-1 text-[11px] sm:text-xs">
                            
                            <?php if($islemAdi !== ''): ?>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-blue-300 uppercase tracking-wider"><?php echo t_cevir('İşlem'); ?>:</span>
                                <span class="font-bold text-white"><?php echo htmlspecialchars($islemAdi); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if($islemTarihi !== ''): ?>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-blue-300 uppercase tracking-wider"><?php echo t_cevir('İşlem Tarihi'); ?>:</span>
                                <span class="font-bold text-white"><?php echo htmlspecialchars(tarihGoster($islemTarihi)); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if($goruntulemeTarihi !== ''): ?>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-blue-300 uppercase tracking-wider"><?php echo t_cevir('Görüntüleme Tarihi'); ?>:</span>
                                <span class="font-bold text-white"><?php echo htmlspecialchars(tarihGoster($goruntulemeTarihi)); ?></span>
                            </div>
                            <?php endif; ?>

                        </div>
                        <?php endif; ?>

                        <div class="mt-1 w-full flex justify-center">
                            <p class="text-[12px] sm:text-[13px] text-amber-50/90 leading-relaxed text-center px-4 max-w-6xl">
                                <?php echo t_cevir('Her tedavide sonuçlar kişiye, ağız yapısına ve klinik koşullara göre değişebilir. Detaylı bilgi için hekiminizle değerlendirme yapmanız önerilir.'); ?>
                            </p>
                        </div>
          
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if($isSliderActive): ?>
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10 bg-black/30 backdrop-blur-sm px-3 py-2 rounded-full">
                    <?php for($i = 0; $i < $sliderCount; $i++): ?>
                    <button class="slider-dot w-2.5 h-2.5 rounded-full bg-white/50 transition-all duration-300 <?php echo $i === 0 ? 'bg-white w-8' : ''; ?>" data-index="<?php echo $i; ?>"></button>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
   
   </div>
    </div>
</section>
 
 
 <!-- MAIN CONTENT -->
    <section class="py-16 lg:py-10">
        <div class="max-w-[1600px] mx-auto px-6 lg:px-12">
            
<!-- TAB MENÜSÜ -->
            <div class="flex flex-wrap gap-2 mb-8 border-b border-gray-200 pb-4">
                <button onclick="switchTab('aciklama')" id="tab-aciklama-btn" class="tab-btn px-6 py-3 rounded-xl font-semibold text-sm transition-all bg-blue-600 text-white shadow-lg">
                    📄 <?php echo t_cevir('Tedavi Açıklaması'); ?>
                </button>
                
                <?php if(count($adimlar) > 0): ?>
                <button onclick="switchTab('adimlar')" id="tab-adimlar-btn" class="tab-btn px-6 py-3 rounded-xl font-semibold text-sm transition-all bg-gray-100 text-gray-700 hover:bg-gray-200">
                    📋 <?php echo t_cevir('Tedavi Adımları'); ?>
                </button>
                <?php endif; ?>
                
                <?php if(count($sss) > 0): ?>
                <button onclick="switchTab('sss')" id="tab-sss-btn" class="tab-btn px-6 py-3 rounded-xl font-semibold text-sm transition-all bg-gray-100 text-gray-700 hover:bg-gray-200">
                    ❓ <?php echo t_cevir('Sık Sorulan Sorular'); ?>
                </button>
                <?php endif; ?>
                
                <button onclick="switchTab('sorular')" id="tab-sorular-btn" class="tab-btn px-6 py-3 rounded-xl font-semibold text-sm transition-all bg-gray-100 text-gray-700 hover:bg-gray-200">
                    💬 <?php echo t_cevir('Hasta Soruları'); ?>
                </button>
            </div>

            <div class="grid lg:grid-cols-12 gap-8 lg:gap-12">
                
<!-- SIDEBAR - KATEGORİLER -->
                <div class="lg:col-span-3">
                    <div class="sticky top-24">
                        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white shadow-md">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                                    </div>
                                    <h3 class="text-sm font-black text-gray-900"><?php echo t_cevir('TEDAVİ KATEGORİLERİ'); ?></h3>
                                </div>
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full"><?php echo count($tumTedaviler); ?> <?php echo t_cevir('tedavi'); ?></span>
                            </div>
                            
                            <div class="space-y-4" id="kategoriListesi">
                                <?php 
                                $kategorilerData = [
                                    ['id' => 'Estetik Diş Hekimliği', 'icon' => '✨', 'color' => 'from-pink-500 to-rose-500', 'bgLight' => 'bg-pink-50', 'textColor' => 'text-pink-600'],
                                    ['id' => 'Cerrahi & İmplantoloji', 'icon' => '💉', 'color' => 'from-blue-500 to-cyan-500', 'bgLight' => 'bg-blue-50', 'textColor' => 'text-blue-600'],
                                    ['id' => 'Protetik Diş Tedavisi', 'icon' => '🛡️', 'color' => 'from-purple-500 to-indigo-500', 'bgLight' => 'bg-purple-50', 'textColor' => 'text-purple-600'],
                                    ['id' => 'Ortodonti & Çene', 'icon' => '📐', 'color' => 'from-green-500 to-emerald-500', 'bgLight' => 'bg-green-50', 'textColor' => 'text-green-600']
                                ];
                                foreach($kategorilerData as $index => $kategori): 
                                    $kategoridekiTedaviler = array_filter($tumTedaviler, function($t) use ($kategori) { return $t['kategori_ham'] == $kategori['id']; });
                                    $isCurrentCategory = $tedavi['kategori_ham'] == $kategori['id'];
                                ?>
                                <div class="kategori-item relative" data-kategori-id="<?php echo $kategori['id']; ?>">
                                    <button onclick="toggleCategory(<?php echo $index; ?>)" class="kategori-baslik w-full flex items-center justify-between p-1.5 rounded-xl transition-all duration-200 group relative z-10 <?php echo $isCurrentCategory ? 'bg-blue-50/80 ring-1 ring-blue-200' : 'hover:bg-gray-50'; ?>">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-gradient-to-br <?php echo $kategori['color']; ?> flex items-center justify-center text-white shadow-sm group-hover:scale-110 transition-transform <?php echo $isCurrentCategory ? 'ring-2 ring-offset-2 ring-blue-400' : ''; ?>">
                                                <span class="text-xs"><?php echo $kategori['icon']; ?></span>
                                            </div>
                                            <h4 class="font-bold text-xs uppercase tracking-wider <?php echo $kategori['textColor']; ?>"><?php echo htmlspecialchars(t_cevir($kategori['id'])); ?></h4>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?php echo $kategori['bgLight']; ?> <?php echo $kategori['textColor']; ?>"><?php echo count($kategoridekiTedaviler); ?></span>
                                            <svg class="kategori-arrow w-3.5 h-3.5 text-gray-400 transition-transform duration-300 <?php echo $isCurrentCategory ? 'text-blue-500' : ''; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                        </div>
                                    </button>
                                    
                                    <?php if($isCurrentCategory): ?>
                                    <div class="flex justify-start mt-2 kategori-badge">
                                        <div class="text-[10px] font-medium text-blue-600 bg-blue-200 px-2.5 py-1 rounded-full shadow-sm flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                                            <span><?php echo t_cevir('Şu anda buradasınız'); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="kategori-tedaviler overflow-hidden transition-all duration-300 ease-in-out" style="max-height: <?php echo ($isCurrentCategory ? '500' : '0'); ?>px; opacity: <?php echo ($isCurrentCategory ? '1' : '0'); ?>;">
                                        <div class="space-y-1 ml-2 border-l-2 border-gray-100 pl-3 mt-3">
                                            <?php 
                                            $tedaviIndex = 0;
                                            foreach($kategoridekiTedaviler as $t): 
                                                $isActive = $t['slug'] == $tedavi['slug'];
                                                $tedaviIndex++;
                                            ?>
                                            <a href="/tedaviler/<?php echo $t['slug']; ?>" class="tedavi-item group relative flex items-center gap-2 text-sm py-2.5 px-3 rounded-xl transition-all duration-200 <?php echo $isActive ? 'bg-gradient-to-r ' . $kategori['color'] . ' text-white font-medium shadow-md' : $kategori['bgLight'] . '/30 text-gray-600 hover:' . $kategori['bgLight'] . ' hover:text-gray-900 hover:translate-x-1'; ?>">
                                                <?php if($isActive): ?><div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-white rounded-full shadow-sm"></div><?php endif; ?>
                                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold transition-all duration-200 shrink-0 <?php echo $isActive ? 'bg-white/20 text-white' : $kategori['bgLight'] . ' ' . $kategori['textColor'] . ' group-hover:scale-110'; ?>"><?php echo $tedaviIndex; ?></span>
                                                <span class="flex-1 line-clamp-1 text-sm truncate"><?php echo htmlspecialchars(t_cevir($t['baslik'])); ?></span>
                                                <svg class="tedavi-arrow w-3.5 h-3.5 transition-all duration-200 shrink-0 <?php echo $isActive ? 'text-white opacity-100' : 'text-gray-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-1'; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if($index < count($kategorilerData) - 1): ?>
                                    <div class="border-t border-gray-100 my-3 kategori-ayrac"></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <div class="bg-blue-50 rounded-xl p-3 mb-3">
                                    <div class="flex items-center gap-2 text-xs text-blue-700">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        <span><?php echo t_cevir('Bulunduğunuz kategori:'); ?> <strong class="font-bold"><?php echo htmlspecialchars(t_cevir($tedavi['kategori'])); ?></strong></span>
                                    </div>
                                </div>
                                <a href="/tedaviler/" class="flex items-center justify-between group">
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors"><?php echo t_cevir('Tüm Tedavileri Gör'); ?></span>
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white shadow-md group-hover:scale-110 transition-all duration-300">
                                        <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- CENTER - DETAYLI İÇERİK -->
               

             <div class="lg:col-span-6">
                    
<!-- AÇIKLAMA TAB'İ -->
                    <div id="tab-aciklama" class="tab-content">
                        <?php if(count($avantajlar) > 0): ?>
                        <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-200 mb-8">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars(t_cevir($tedavi['baslik'])); ?> <span class="text-blue-600"><?php echo t_cevir('Avantajları'); ?></span></h2>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach($avantajlar as $av): ?>
                                <div class="group flex items-center gap-4 p-4 bg-gradient-to-br from-blue-50 to-white rounded-xl border border-blue-200">
                                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white group-hover:scale-110 transition-all">
                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars(t_cevir($av)); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-200 mb-8">
                            <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-6">
                                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900"><?php echo t_cevir('Tedavi'); ?> <span class="text-red-500"><?php echo t_cevir('Detayları'); ?></span></h2>
                            </div>
                            <div class="text-gray-600 prose max-w-none">
                                <?php echo t_cevir($tedavi['detayli_aciklama']); ?>
                            </div>
                        </div>

<!-- 🔥 KULLANILAN İLERİ TEKNİKLER -->
<?php if(count($teknikler) > 0): ?>
<div class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 rounded-2xl p-8 shadow-lg border border-indigo-200 mb-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900"><?php echo t_cevir('Kullanılan'); ?> <span class="text-indigo-600"><?php echo t_cevir('İleri Teknikler'); ?></span></h2>
            <p class="text-xs text-gray-500 mt-1"><?php echo t_cevir('Bu tedavide uyguladığımız özel yöntem ve teknolojiler'); ?></p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach($teknikler as $tek): ?>
        <div class="group bg-white rounded-xl p-5 border-2 border-indigo-100 hover:border-indigo-300 hover:shadow-lg transition-all duration-300">
            <div class="flex items-start gap-3 mb-2">
                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-xs font-black flex-shrink-0 group-hover:scale-110 transition-transform">
                    ✓
                </div>
                <h3 class="font-bold text-gray-900 text-base leading-snug group-hover:text-indigo-600 transition-colors">
                    <?php echo htmlspecialchars($tek['baslik']); ?>
                </h3>
            </div>
            <?php if(!empty($tek['aciklama'])): ?>
            <p class="text-sm text-gray-600 leading-relaxed pl-11">
                <?php echo htmlspecialchars($tek['aciklama']); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

                        <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-8 shadow-xl text-white">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/30">
                                    <svg class="w-6 h-6 text-yellow-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
                                </div>
                                <h3 class="text-2xl font-bold text-white"><?php echo t_cevir('Tedavi Özeti'); ?></h3>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                                    <div class="flex items-center gap-3 mb-3">
                                        <svg class="w-4 h-4 text-yellow-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <span class="text-xs font-medium text-blue-200"><?php echo t_cevir($ozet_sure_etiket); ?></span>
                                    </div>
                                    <div class="text-xl font-bold text-white"><?php echo htmlspecialchars(t_cevir($tedavi['sure'] ?: 'Kişiye özel')); ?></div>
                                </div>

                            </div>
                        </div>
                    </div>
 <!-- ADIMLAR TAB'İ -->
                    <?php if(count($adimlar) > 0): ?>
                    <div id="tab-adimlar" class="tab-content hidden">
                        <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-200">
                            <div class="flex items-center gap-3 mb-8 pb-4 border-b border-gray-100">
                                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-3xl font-bold text-gray-900"><?php echo t_cevir('Tedavi'); ?> <span class="text-blue-600"><?php echo t_cevir('Süreci'); ?></span></h2>
                                    <p class="text-sm text-gray-500 mt-1"><?php echo t_cevir('Adım adım tedavi planlaması'); ?></p>
                                </div>
                            </div>
                            
                            <div class="relative">
                                <div class="absolute left-[1.75rem] top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-200 via-blue-400 to-blue-200 hidden lg:block rounded-full"></div>
                                <div class="space-y-8">
                                    <?php foreach($adimlar as $index => $adim): ?>
                                    <div class="relative group">
                                        <div class="flex flex-col lg:flex-row gap-6">
                                            <div class="flex-shrink-0 lg:w-36">
                                                <div class="flex items-center gap-3 lg:flex-col lg:items-start">
                                                    <div class="relative">
                                                        <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-lg group-hover:scale-110 group-hover:shadow-xl transition-all duration-300 z-10 relative">
                                                            <?php echo htmlspecialchars($adim['no']); ?>
                                                        </div>
                                                        <div class="absolute -right-2 -top-2 w-5 h-5 bg-green-500 rounded-full border-2 border-white opacity-0 group-hover:opacity-100 transition-all duration-300 shadow-lg shadow-green-500/50 z-20"></div>
                                                        <div class="absolute -right-2 -top-2 w-5 h-5 bg-green-400 rounded-full opacity-0 group-hover:opacity-60 group-hover:animate-ping transition-all duration-500 z-10"></div>
                                                        <div class="absolute -inset-1 bg-blue-400 rounded-2xl opacity-0 group-hover:opacity-30 transition-opacity duration-300 blur-sm"></div>
                                                    </div>
                                                    <div class="lg:text-center lg:mt-2">
                                                        <span class="text-xs font-bold text-blue-600 uppercase tracking-wider bg-blue-50 px-2 py-1 rounded-full"><?php echo t_cevir('Adım'); ?> <?php echo htmlspecialchars($adim['no']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-6 border border-gray-200 group-hover:border-blue-300 group-hover:shadow-xl transition-all duration-300">
                                                    <div class="flex items-center gap-3 mb-3">
                                                        <div class="w-10 h-10 bg-blue-200 rounded-xl flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                                                            <?php if($index == 0): ?>
                                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                                            <?php elseif($index == 1): ?>
                                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                            <?php elseif($index == 2): ?>
                                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                                            <?php else: ?>
                                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                                                            <?php endif; ?>
                                                        </div>
                                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars(t_cevir($adim['baslik'])); ?></h3>
                                                    </div>
                                                    <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars(t_cevir($adim['aciklama']))); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if($index < count($adimlar) - 1): ?>
                                        <div class="hidden lg:flex absolute left-[1.75rem] -bottom-6 transform -translate-x-1/2 flex-col items-center">
                                            <div class="w-0.5 h-8 bg-gradient-to-b from-blue-400 to-blue-200"></div>
                                            <svg class="w-4 h-4 text-blue-400 mt-1 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
<!-- SSS TAB'İ -->
                    <?php if(count($sss) > 0): ?>
                    <div id="tab-sss" class="tab-content hidden">
                        <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-200">
                            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900"><?php echo t_cevir('Sık Sorulan'); ?> <span class="text-blue-600"><?php echo t_cevir('Sorular'); ?></span></h2>
                                    <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars(t_cevir($tedavi['baslik'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <?php foreach($sss as $index => $item): ?>
                                <div class="border rounded-2xl overflow-hidden transition-all duration-300 hover:border-blue-300">
                                    <button onclick="toggleSSS(<?php echo $index; ?>)" class="w-full flex items-center justify-between p-5 text-left bg-white hover:bg-blue-50/30 transition-all group">
                                        <div class="flex items-start gap-4 flex-1">
                                            <div class="w-8 h-8 rounded-lg bg-blue-200 text-blue-600 flex items-center justify-center font-bold text-sm transition-all duration-200 group-hover:bg-blue-600 group-hover:text-white">
                                                <?php echo $index + 1; ?>
                                            </div>
                                            <h3 class="font-bold text-lg text-gray-900 group-hover:text-blue-600 transition-colors pr-8"><?php echo htmlspecialchars(t_cevir($item['soru'])); ?></h3>
                                        </div>
                                        <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 group-hover:bg-blue-200 group-hover:text-blue-600 flex items-center justify-center transition-all duration-300">
                                            <svg class="w-4 h-4 transition-transform duration-300 sss-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                        </div>
                                    </button>
                                    <div id="sss-answer-<?php echo $index; ?>" class="overflow-hidden transition-all duration-500 ease-in-out max-h-0 opacity-0">
                                        <div class="p-6 pt-0 border-t border-gray-200">
                                            <div class="flex gap-4 pt-6">
                                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-3 h-3 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                                </div>
                                                <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars(t_cevir($item['cevap']))); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- SORULAR TAB'İ -->
<div id="tab-sorular" class="tab-content hidden">
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                            
                            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
                                <h3 class="text-white font-bold text-xl flex items-center gap-2">
                                    📝 <?php echo t_cevir('Soru Sorun'); ?>
                                </h3>
                                <p class="text-blue-200 text-sm mt-1"><?php echo htmlspecialchars(t_cevir($tedavi['baslik'])); ?> <?php echo t_cevir('tedavisi hakkında sorularınızı uzman ekibimize iletebilirsiniz.'); ?></p>
                            </div>
                            
                            <div class="p-6 bg-white">
                                <form id="soruForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="ust_id" value="0">
                                    <input type="hidden" name="tedavi" value="<?php echo htmlspecialchars($tedavi['kategori_ham']); ?>">
                                    <input type="hidden" name="puan" value="5">
                                    
                                    <div class="grid md:grid-cols-2 gap-5 mb-5">
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">👤 <?php echo t_cevir('Adınız Soyadınız *'); ?></label>
                                            <input type="text" name="ad_soyad" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 !text-black text-gray-900">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">📧 <?php echo t_cevir('E-posta *'); ?></label>
                                            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 !text-black text-gray-900">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-6" style="padding-top:10px">
                                        <label class="block text-sm font-bold text-gray-700 mb-2 mt-5">❓ <?php echo t_cevir('Sorunuz *'); ?></label>
                                        <textarea 
                                            name="yorum" 
                                            rows="4" 
                                            required 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-white text-gray-900 !text-black" 
                                            placeholder="<?php echo htmlspecialchars(t_cevir($tedavi['baslik'])) . ' ' . t_cevir('tedavisi hakkında merak ettiğiniz her şeyi sorabilirsiniz...'); ?>"
                                        ></textarea>
                                    </div>
                                    
                                    <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition shadow-md font-bold flex items-center justify-center gap-2 text-lg">
                                        ✨ <?php echo t_cevir('Soruyu Gönder'); ?>
                                    </button>
                                    
                                    <p class="text-xs text-gray-400 text-center mt-3 flex items-center justify-center gap-1">
                                        🔒 <?php echo t_cevir('Sorularınız onaylandıktan sonra yayınlanır ve cevaplanır.'); ?>
                                    </p>
                                </form>
                            </div>
                            
                            <div class="p-6 bg-gray-50 border-t border-gray-200">
                                <div class="flex items-center justify-center gap-3 mb-6">
                                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center shadow-md text-white font-bold text-lg">
                                        📋
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800"><?php echo t_cevir('Hasta Soruları ve Cevapları'); ?></h3>
                                </div>
                                
                                <div id="yorumlarListesi" class="space-y-6">
                                    <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-200">
                                        <div class="animate-pulse">
                                            <div class="w-16 h-16 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl">
                                                💬
                                            </div>
                                            <div class="h-4 bg-gray-200 rounded w-48 mx-auto mb-3"></div>
                                            <div class="h-3 bg-gray-100 rounded w-64 mx-auto"></div>
                                        </div>
                                        <p class="text-gray-400 mt-4"><?php echo t_cevir('Sorular yükleniyor...'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
               </div>

<!-- RIGHT SIDEBAR -->
                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl p-6 shadow-xl text-white">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Prof. Dr. İbrahim Duran</h3>
                                <p class="text-sm text-blue-300"><?php echo t_cevir('Ağız, Diş ve Çene Cerrahisi'); ?></p>
                            </div>
                        </div>
                        <a href="/kurumsal/" class="w-full py-2 bg-white/10 border border-white/20 rounded-xl text-sm font-medium hover:bg-white/20 text-center block transition-all"><?php echo t_cevir('Özgeçmişi İncele'); ?></a>
                    </div>

                    <?php if(count($digerTedaviler) > 0): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                        <h3 class="text-sm font-black text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                            <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
                            <?php echo t_cevir('DİĞER'); ?> <?php echo htmlspecialchars(t_cevir($kategoriKisa)); ?> <?php echo t_cevir('TEDAVİLERİ'); ?>
                        </h3>
                        <div class="space-y-3">
<?php foreach(array_slice($digerTedaviler, 0, 5) as $dt): ?>
<a href="/tedaviler/<?php echo $dt['slug']; ?>" class="flex items-center gap-3 group hover:bg-gray-50 p-2 rounded-xl transition-all duration-200">
    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
    </div>
    <div class="flex-1">
        <h4 class="font-medium text-gray-900 group-hover:text-blue-600 text-sm transition-colors line-clamp-2"><?php echo htmlspecialchars(t_cevir($dt['baslik'])); ?></h4>
        <p class="text-xs text-gray-500 line-clamp-1"><?php echo htmlspecialchars(t_cevir(mb_substr($dt['kisa_aciklama'], 0, 50))); ?></p>
    </div>
    <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-600 transition-all group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
</a>
<?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
<?php if(!empty($tedavi['instagram_url'])): ?>
<div class="bg-gradient-to-br from-pink-500 via-purple-500 to-orange-400 rounded-2xl p-6 shadow-xl text-white">
    <div class="flex items-center gap-3 mb-3">
        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="2" width="20" height="20" rx="5"/>
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
        </svg>
        <h3 class="font-bold text-lg">Instagram</h3>
    </div>
    <p class="text-white/90 text-sm mb-4"><?php echo t_cevir('Bu tedavinin gerçek vaka sonucunu Instagram\'da görebilirsiniz.'); ?></p>
    <a href="<?php echo htmlspecialchars($tedavi['instagram_url']); ?>" target="_blank" rel="noopener"
       class="w-full py-3 bg-white text-pink-600 font-bold rounded-xl text-sm hover:bg-pink-50 transition-all flex items-center justify-center gap-2">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
            <polyline points="15 3 21 3 21 9"/>
            <line x1="10" y1="14" x2="21" y2="3"/>
        </svg>
        <?php echo t_cevir('Instagram\'da Gör'); ?>
    </a>
</div>
<?php endif; ?>
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-6 border border-amber-200">
                        <div class="flex items-center gap-3 mb-3">
                            <svg class="w-8 h-8 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <h3 class="font-bold text-gray-900"><?php echo t_cevir('Tedavi Broşürü'); ?></h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-3"><?php echo t_cevir('Detaylı tedavi bilgilerini PDF olarak indirin.'); ?></p>
                        <button class="w-full py-2 bg-amber-600 text-white rounded-xl text-sm font-medium hover:bg-amber-700 transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <?php echo t_cevir('PDF İndir'); ?>
                        </button>
                    </div>
                </div>
           </div>
        </div>
    </section>

<!-- İLGİNİZİ ÇEKEBİLECEK TEDAVİLER -->
    <?php if(count($digerTedaviler) > 0): ?>
    <section class="py-20 bg-gradient-to-b from-white to-gray-50 border-t border-gray-200">
        <div class="max-w-[1600px] mx-auto px-6 lg:px-12">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo t_cevir('İlginizi Çekebilecek Tedaviler'); ?></h2>
                <p class="text-gray-600"><?php echo t_cevir('Aynı kategorideki diğer tedavi seçeneklerimizi keşfedin.'); ?></p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
<?php foreach(array_slice($digerTedaviler, 0, 4) as $t): ?>
<a href="/tedaviler/<?php echo $t['slug']; ?>" class="group bg-white rounded-2xl border border-gray-200 shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all overflow-hidden">
    <div class="relative h-40 overflow-hidden">
        <img src="<?php echo htmlspecialchars($t['image'] ?: 'https://placehold.co/400x300/667eea/white?text=Tedavi'); ?>" 
             alt="<?php echo htmlspecialchars(t_cevir($t['baslik'])); ?>" 
             class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
    </div>
    <div class="p-6">
        <h3 class="font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors line-clamp-2"><?php echo htmlspecialchars(t_cevir($t['baslik'])); ?></h3>
        <p class="text-sm text-gray-600 line-clamp-2"><?php echo htmlspecialchars(t_cevir($t['kisa_aciklama'])); ?></p>
    </div>
</a>
<?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- RANDEVU CTA -->
    <section class="py-20 bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 text-white">
        <div class="relative max-w-4xl mx-auto px-6 lg:px-12 text-center">
            <div class="w-20 h-20 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mx-auto mb-6 border border-white/30">
                <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <h2 class="text-4xl lg:text-5xl font-bold mb-4"><?php echo ($lang === 'en') ? 'Make an Appointment for' : ''; ?> <?php echo htmlspecialchars($tedavi['baslik']); ?></h2>
            <p class="text-xl text-blue-200 mb-8 max-w-2xl mx-auto"><?php echo ($lang === 'en') ? 'Create an appointment now to create your personalized treatment plan. First examination and digital analysis are free!' : 'Size özel tedavi planını oluşturmak için hemen randevu oluşturun. İlk muayene ve dijital analiz ücretsiz!'; ?></p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/#" class="flex items-center justify-center gap-3 px-8 py-4 bg-yellow-300 text-blue-900 font-bold rounded-xl hover:bg-yellow-400 transition-all shadow-lg text-lg">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php echo ($lang === 'en') ? 'Make Appointment Now' : 'Hemen İletişime Geç'; ?>
                </a>
                <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $telefon); ?>" class="flex items-center justify-center gap-3 px-8 py-4 bg-transparent border-2 border-white/30 text-white font-bold rounded-xl hover:bg-white/10 transition-all text-lg">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <?php echo htmlspecialchars($telefon); ?>
                </a>
            </div>
        </div>
    </section>
</main>
<style>
.kategori-tedaviler {
    transform: scaleY(1);
    transform-origin: top;
    transition: transform 0.3s ease-out;
    will-change: transform;
}

.kategori-tedaviler.hidden {
    display: none;
    transform: scaleY(0);
}

</style>
<div id="imageModal" class="fixed inset-0 z-[200] bg-black/90 hidden items-center justify-center p-4" onclick="closeModal()">
    <button onclick="closeModal()" class="absolute top-6 right-6 text-white/70 hover:text-white transition-all">
        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <img id="modalImage" src="" alt="" class="max-w-full max-h-[90vh] object-contain rounded-2xl">
</div>

<script>
// ========== SLIDER KONTROLÜ ==========
document.addEventListener('DOMContentLoaded', function() {
    const track = document.getElementById('sliderTrack');
    if (!track) return;
    
    const slides = track.querySelectorAll('.min-w-full');
    const totalSlides = slides.length;
    if (totalSlides <= 1) return;
    
    const dots = document.querySelectorAll('.slider-dot');
    let currentIndex = 0;
    let slideInterval = null;
    const intervalTime = 5000;
    
    function goToSlide(index) {
        if (index < 0) index = totalSlides - 1;
        if (index >= totalSlides) index = 0;
        currentIndex = index;
        const offset = -index * 100;
        track.style.transform = 'translateX(' + offset + '%)';
        
        // 🔥 ALT BAŞLIK GÜNCELLE (slider ile birlikte dönsün)
        const altEl = document.getElementById('heroAltBaslik');
        const altTextEl = document.getElementById('heroAltBaslikText');
        if (altEl && altTextEl) {
            const currentSlide = slides[index];
            const newAlt = (currentSlide.getAttribute('data-alt-baslik') || '').trim();
            
            // Önce fade-out
            altEl.classList.remove('opacity-100');
            altEl.classList.add('opacity-0');
            
            setTimeout(() => {
                if (newAlt) {
                    // Alt başlık var → göster
                    altTextEl.textContent = newAlt;
                    altEl.classList.remove('hidden');
                    // force reflow (animasyon tetiklensin)
                    void altEl.offsetHeight;
                    altEl.classList.remove('opacity-0');
                    altEl.classList.add('opacity-100');
                } else {
                    // 🔥 Alt başlık yok → tamamen gizle (yer kaplamasın)
                    altEl.classList.add('hidden');
                    altEl.classList.remove('opacity-100');
                }
            }, 250);
        }
        
        dots.forEach((dot, i) => {
            if (i === index) {
                dot.className = 'slider-dot w-8 h-2.5 rounded-full bg-white transition-all duration-300';
            } else {
                dot.className = 'slider-dot w-2.5 h-2.5 rounded-full bg-white/50 transition-all duration-300';
            }
        });
    }
    
    function nextSlide() {
        goToSlide(currentIndex + 1);
    }
    
    function startSlider() {
        if (slideInterval) clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, intervalTime);
    }
    
    function stopSlider() {
        if (slideInterval) {
            clearInterval(slideInterval);
            slideInterval = null;
        }
    }
    
    dots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
            stopSlider();
            goToSlide(index);
            startSlider();
        });
    });
    
    const sliderContainer = track.closest('.relative');
    if (sliderContainer) {
        sliderContainer.addEventListener('mouseenter', stopSlider);
        sliderContainer.addEventListener('mouseleave', startSlider);
    }
    
    startSlider();
    
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopSlider();
        } else {
            startSlider();
        }
    });
});

// ========== DİĞER FONKSİYONLAR (ORİJİNAL) ==========
(function() {
    'use strict';
    
    const elementCache = new Map();
    
    function getCachedElement(id, selector = null) {
        if (elementCache.has(id)) {
            return elementCache.get(id);
        }
        const element = selector ? document.querySelector(selector) : document.getElementById(id);
        if (element) elementCache.set(id, element);
        return element;
    }
    
    function setHTMLSafe(element, html) {
        if (!element) return;
        element.innerHTML = '';
        const fragment = document.createDocumentFragment();
        const temp = document.createElement('div');
        temp.innerHTML = html;
        while (temp.firstChild) {
            fragment.appendChild(temp.firstChild);
        }
        element.appendChild(fragment);
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, m => m === '&' ? '&amp;' : m === '<' ? '&lt;' : '&gt;');
    }
    
    (function() {
        const tokenInput = document.getElementById('csrf_token');
        console.log("%c════════════════════════════════════════", "color: #3b82f6");
        console.log("%c🔐 CSRF TOKEN DURUMU", "color: #10b981; font-weight: bold");
        console.log("%c════════════════════════════════════════", "color: #3b82f6");
        if(tokenInput && tokenInput.value) {
            console.log("%c✅ TOKEN AKTİF", "color: #22c55e");
        } else {
            console.log("%c❌ TOKEN BULUNAMADI!", "color: #ef4444");
        }
    })();
    
    window.switchTab = function(tabName) {
        const tabContents = document.querySelectorAll('.tab-content');
        const tabBtns = document.querySelectorAll('.tab-btn');
        
        tabContents.forEach(tab => tab.classList.add('hidden'));
        
        const selectedTab = document.getElementById('tab-' + tabName);
        if(selectedTab) selectedTab.classList.remove('hidden');
        
        tabBtns.forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'shadow-lg');
            btn.classList.add('bg-gray-100', 'text-gray-700');
        });
        
        const activeBtn = document.getElementById('tab-' + tabName + '-btn');
        if(activeBtn) {
            activeBtn.classList.remove('bg-gray-100', 'text-gray-700');
            activeBtn.classList.add('bg-blue-600', 'text-white', 'shadow-lg');
        }
        
        requestAnimationFrame(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        });
    };
    
    window.toggleCategory = function(index) {
        const kategoriItems = document.querySelectorAll('.kategori-item');
        if(!kategoriItems[index]) return;
        
        const clickedItem = kategoriItems[index];
        const clickedTedaviler = clickedItem.querySelector('.kategori-tedaviler');
        const clickedArrow = clickedItem.querySelector('.kategori-arrow');
        const isCurrentlyOpen = clickedTedaviler && !clickedTedaviler.classList.contains('hidden');
        
        kategoriItems.forEach((item, i) => {
            const tedaviler = item.querySelector('.kategori-tedaviler');
            const arrow = item.querySelector('.kategori-arrow');
            if(tedaviler) {
                if(i !== index) {
                    tedaviler.classList.add('hidden');
                    tedaviler.style.maxHeight = '0px';
                    tedaviler.style.opacity = '0';
                    if(arrow) arrow.style.transform = 'rotate(0deg)';
                }
            }
        });
        
        if(clickedTedaviler) {
            if(isCurrentlyOpen) {
                clickedTedaviler.classList.add('hidden');
                clickedTedaviler.style.maxHeight = '0px';
                clickedTedaviler.style.opacity = '0';
                if(clickedArrow) clickedArrow.style.transform = 'rotate(0deg)';
            } else {
                clickedTedaviler.classList.remove('hidden');
                requestAnimationFrame(() => {
                    const height = clickedTedaviler.scrollHeight;
                    clickedTedaviler.style.maxHeight = height + 'px';
                    clickedTedaviler.style.opacity = '1';
                    if(clickedArrow) clickedArrow.style.transform = 'rotate(180deg)';
                });
            }
        }
    };
    
    window.openModal = function(imgSrc) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        if(modal && modalImg) {
            modalImg.src = imgSrc;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.closeModal = function() {
        const modal = document.getElementById('imageModal');
        if(modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    };
    
    window.toggleSSS = function(index) {
        const answer = document.getElementById('sss-answer-' + index);
        const arrows = document.querySelectorAll('.sss-arrow');
        const arrow = arrows[index];
        
        if(answer) {
            requestAnimationFrame(() => {
                if(answer.style.maxHeight && answer.style.maxHeight !== '0px') {
                    answer.style.maxHeight = '0px';
                    answer.style.opacity = '0';
                    if(arrow) arrow.style.transform = 'rotate(0deg)';
                } else {
                    void answer.offsetHeight;
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                    answer.style.opacity = '1';
                    if(arrow) arrow.style.transform = 'rotate(180deg)';
                }
            });
        }
    };
    
    window.yorumlariListele = function() {
        const kategori = '<?php echo $tedavi['kategori_ham']; ?>';
        const lang = '<?php echo $lang; ?>';
        const container = document.getElementById('yorumlarListesi');
        
        if (!container) return;
        
        console.log("🔍 Aranan kategori:", kategori);
        
        fetch('/api/yorum-listele.php')
            .then(res => res.json())
            .then(data => {
                console.log("📦 Gelen veri:", data);
                
                if(!data.success || !data.data.length) {
                    const emptyMsg = lang === 'en' ? 'No questions yet.' : 'Henüz soru yok.';
                    setHTMLSafe(container, `<div class="text-center py-12 text-gray-400">${emptyMsg}</div>`);
                    return;
                }
                
                const filtrelenenler = data.data.filter(s => s.ust_id == 0 && s.tedavi == kategori);
                
                console.log("✅ Filtrelenen sorular:", filtrelenenler);
                
                if(filtrelenenler.length === 0) {
                    const noQuestionMsg = lang === 'en' 
                        ? 'No questions asked yet' 
                        : 'Henüz soru sorulmamış';
                    const askMsg = lang === 'en' 
                        ? `Be the first to ask a question in the "${kategori}" category!`
                        : `"${kategori}" kategorisinde ilk soruyu siz sorun!`;
                    
                    setHTMLSafe(container, `
                        <div class="text-center py-12 bg-white rounded-xl border">
                            <div class="text-3xl mb-3">💬</div>
                            <h4 class="font-bold text-gray-800 text-lg mb-1">${noQuestionMsg}</h4>
                            <p class="text-gray-500">${askMsg}</p>
                        </div>
                    `);
                    return;
                }
                
                let html = '';
                for (const soru of filtrelenenler) {
                    html += `
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition p-5 mb-5">
                            <div class="mb-3">
                                <span class="inline-block px-3 py-1 bg-blue-200 text-blue-700 text-xs font-bold rounded-full">📌 ${escapeHtml(soru.tedavi)}</span>
                            </div>
                            
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-md shadow-md">
                                    ${soru.ad_soyad ? escapeHtml(soru.ad_soyad.charAt(0).toUpperCase()) : '?'}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center flex-wrap gap-2 mb-1">
                                        <span class="font-bold text-gray-800 text-base">${escapeHtml(soru.ad_soyad)}</span>
                                        <span class="text-gray-400 text-xs">${escapeHtml(soru.tarih)}</span>
                                    </div>
                                    <p class="text-gray-700 text-base leading-relaxed">${escapeHtml(soru.yorum)}</p>
                                </div>
                            </div>
                    `;
                    
                    const cevaplar = data.data.filter(c => c.ust_id == soru.id);
                    if(cevaplar.length > 0) {
                        html += `<div class="ml-12 pl-4 border-l-2 border-blue-200 space-y-3 mt-4">`;
                        for (const cevap of cevaplar) {
                            const isAdmin = cevap.ad_soyad && cevap.ad_soyad.includes('Prof');
                            html += `
                                <div class="${isAdmin ? 'bg-blue-50' : 'bg-gray-50'} rounded-lg p-3">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-6 h-6 rounded-full ${isAdmin ? 'bg-blue-600' : 'bg-gray-400'} flex items-center justify-center text-white text-[10px] font-bold">
                                            ${cevap.ad_soyad ? escapeHtml(cevap.ad_soyad.charAt(0).toUpperCase()) : '?'}
                                        </div>
                                        <span class="font-bold text-sm ${isAdmin ? 'text-blue-800' : 'text-gray-700'}">${escapeHtml(cevap.ad_soyad)}</span>
                                        <span class="text-gray-400 text-xs">${escapeHtml(cevap.tarih)}</span>
                                    </div>
                                    <p class="text-gray-700 text-sm">${escapeHtml(cevap.yorum)}</p>
                                </div>
                            `;
                        }
                        html += `</div>`;
                    }
                    
                    html += `</div>`;
                }
                
                setHTMLSafe(container, html);
            })
            .catch(err => {
                console.error("Hata:", err);
                const errorMsg = lang === 'en' ? 'Error loading questions' : 'Yüklenirken hata oluştu';
                setHTMLSafe(container, `<div class="text-center py-12 text-red-500">${errorMsg}</div>`);
            });
    };
    
    const soruForm = document.getElementById('soruForm');
    if(soruForm) {
        soruForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            console.log('Gönderilen veri:', data);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            const lang = '<?php echo $lang; ?>';
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ ' + (lang === 'en' ? 'Sending...' : 'Gönderiliyor...');
            
            try {
                const response = await fetch('/api/yorum-gonder.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if(result.success) {
                    const successMsg = lang === 'en' 
                        ? 'Your question has been sent successfully! It will be published after approval.'
                        : 'Sorunuz başarıyla gönderildi! Onaylandıktan sonra yayınlanacaktır.';
                    alert('✅ ' + successMsg);
                    this.reset();
                    window.yorumlariListesi();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch(error) {
                console.error('Hata:', error);
                const errorMsg = lang === 'en' ? 'An error occurred. Please try again.' : 'Bir hata oluştu. Lütfen tekrar deneyin.';
                alert('❌ ' + errorMsg);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if(tabParam && ['aciklama', 'adimlar', 'sss', 'sorular'].includes(tabParam)) {
            window.switchTab(tabParam);
        }
        
        requestAnimationFrame(() => {
            const aktifKategori = '<?php echo $tedavi['kategori_ham']; ?>';
            const kategoriItems = document.querySelectorAll('.kategori-item');
            
            kategoriItems.forEach((item) => {
                if(item.getAttribute('data-kategori-id') === aktifKategori) {
                    const tedavilerDiv = item.querySelector('.kategori-tedaviler');
                    const arrow = item.querySelector('.kategori-arrow');
                    if(tedavilerDiv) {
                        tedavilerDiv.style.maxHeight = tedavilerDiv.scrollHeight + 'px';
                        tedavilerDiv.style.opacity = '1';
                        if(arrow) arrow.style.transform = 'rotate(180deg)';
                    }
                }
            });
        });
        
        window.yorumlariListesi();
    });
    
    window.__cleanup = function() {
        elementCache.clear();
    };
})();
</script>

<?php include 'inc/footer.php'; ?>