<?php
// www/site/index.php  —  YENİ ANASAYFA (dengeli + renkli başlıklar + UI sözlüğü)
require_once 'inc/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ------------------------------------------------------------------
 * Yardımcılar
 * ------------------------------------------------------------------ */
$dil_en = (isset($_SESSION['dil']) && $_SESSION['dil'] === 'en');

if (!function_exists('ai_metin')) {
    function ai_metin(array $satir, string $kok, string $varsayilan = ''): string {
        global $dil_en;
        if ($dil_en && !empty($satir[$kok . '_en'])) {
            return $satir[$kok . '_en'];
        }
        $tr = !empty($satir[$kok . '_tr']) ? $satir[$kok . '_tr'] : $varsayilan;
        return t_cevir($tr);
    }
}

if (!function_exists('video_coz')) {
    function video_coz(?string $url): ?array {
        $url = trim((string)$url);
        if ($url === '') return null;

        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?(?:.*&)?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return [
                'tip'   => 'youtube',
                'id'    => $m[1],
                'embed' => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0&modestbranding=1',
                'thumb' => 'https://img.youtube.com/vi/' . $m[1] . '/maxresdefault.jpg',
                'thumb_yedek' => 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg',
            ];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return ['tip' => 'vimeo', 'id' => $m[1], 'embed' => 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1', 'thumb' => '', 'thumb_yedek' => ''];
        }
        if (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $url)) {
            return ['tip' => 'dosya', 'src' => $url];
        }
        return null;
    }
}

/* ------------------------------------------------------------------
 * Veriler
 * ------------------------------------------------------------------ */
$kurumsal_data = [];
$stmt = $db->prepare("SELECT ayar_key, ayar_value FROM kurumsal_ayarlari");
$stmt->execute();
while ($row = $stmt->fetch()) {
    $kurumsal_data[$row['ayar_key']] = $row['ayar_value'];
}

$stmt = $db->prepare("SELECT * FROM anasayfa_icerik WHERE id = 1");
$stmt->execute();
$anasayfa = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$tedaviler_metin = [];
if (!empty($anasayfa['tedaviler_metin_json'])) {
    $tedaviler_metin = json_decode($anasayfa['tedaviler_metin_json'], true);
}
if (!is_array($tedaviler_metin)) $tedaviler_metin = [];

$site_ayar = [];
$stmt = $db->prepare("SELECT ayar_key, ayar_value FROM site_ayarlari WHERE ayar_key IN ('header1_telefon','header1_eposta','adres','doktor_aciklama')");
$stmt->execute();
while ($row = $stmt->fetch()) {
    $site_ayar[$row['ayar_key']] = $row['ayar_value'];
}
$telefon_goster = $site_ayar['header1_telefon'] ?? '' ?: '+90 505 223 23 43';
$eposta_goster  = $site_ayar['header1_eposta'] ?? '' ?: 'ibrahimdurandental@gmail.com';
$adres_goster   = $site_ayar['adres'] ?? '' ?: 'Mimar Sinan Mah. Atatürk Bulvarı Riva İş Merkezi No:260 Kat:1 Atakum/Samsun';

$doktor_aciklama = ai_metin($anasayfa, 'doktor_aciklama', '');
if ($doktor_aciklama === '' && !empty($site_ayar['doktor_aciklama'])) {
    $doktor_aciklama = t_cevir($site_ayar['doktor_aciklama']);
}

$video_aktif = !isset($anasayfa['video_aktif']) || (int)$anasayfa['video_aktif'] === 1;
$video = $video_aktif ? video_coz($anasayfa['video_url'] ?? '') : null;

$sss_sorular = $db->query("SELECT * FROM sss WHERE aktif = 1 ORDER BY sira ASC")->fetchAll();
if ($dil_en) {
    foreach ($sss_sorular as &$s) {
        $s['kategori'] = t_cevir($s['kategori']);
        $s['soru']     = t_cevir($s['soru']);
        $s['cevap']    = t_cevir($s['cevap']);
    }
    unset($s);
}
$yarim = (int)ceil(count($sss_sorular) / 2);
$sss_sol = array_slice($sss_sorular, 0, $yarim);
$sss_sag = array_slice($sss_sorular, $yarim);

$gruplar = [
    'sol' => [
        'baslik' => ai_metin($anasayfa, 'sol_sutun_baslik', 'Estetik & Gülüş Tasarımı'),
        'keys'   => ['dis_estetigi', 'dis_agrisi', 'implant', 'dis_eti', 'kanal'],
    ],
    'sag' => [
        'baslik' => ai_metin($anasayfa, 'sag_sutun_baslik', 'Cerrahi & İmplantoloji'),
        'keys'   => ['ortodonti', 'cocuk', 'cene_eklemi', 'beyazlatma', 'samsun'],
    ],
];

$page_title      = ($dil_en && !empty($anasayfa['seo_title_en'])) ? $anasayfa['seo_title_en'] : ($anasayfa['seo_title_tr'] ?? 'Ana Sayfa');
$seo_description = ($dil_en && !empty($anasayfa['seo_description_en'])) ? $anasayfa['seo_description_en'] : ($anasayfa['seo_description_tr'] ?? ($site_aciklama ?? ''));
$site_keywords   = ($dil_en && !empty($anasayfa['seo_keywords_en'])) ? $anasayfa['seo_keywords_en'] : ($anasayfa['seo_keywords_tr'] ?? '');

/* ------------------------------------------------------------------
 * 🔥 SABİT UI SÖZLÜĞÜ (API'ye gitmez, anında döner)
 * ------------------------------------------------------------------ */
$ui = [
    'tr' => [
        'bizimle_iletisim'    => 'Bizimle İletişime Geçin',
        'bizimle_iletisim_alt'=> 'Sorularınız, randevu talepleriniz ve tüm merak ettikleriniz için bize ulaşın.',
        'akademik_kadro'      => 'AKADEMİK KADRO',
        
        'telefon'             => 'Telefon',
        'eposta'              => 'E-posta',
        'adres'               => 'Adres',
        'hemen_iletisim'      => 'Hemen İletişime Geç',
        'yil'                 => 'Yıl',
        'hasta'               => 'Hasta',
        'basari'              => 'Başarı',
        'daha_fazla'          => 'Daha Fazla',
        'daha_az'             => 'Daha Az',
    ],
    'en' => [
        'bizimle_iletisim'    => 'Contact Us',
        'bizimle_iletisim_alt'=> 'Reach out for your questions, appointment requests, and anything you\'re curious about.',
        'akademik_kadro'      => 'ACADEMIC TEAM',
       
        'telefon'             => 'Phone',
        'eposta'              => 'Email',
        'adres'               => 'Address',
        'hemen_iletisim'      => 'Contact Us Now',
        'yil'                 => 'Years',
        'hasta'               => 'Patients',
        'basari'              => 'Success',
        'daha_fazla'          => 'Read More',
        'daha_az'             => 'Read Less',
    ],
];
$U = $ui[$dil_en ? 'en' : 'tr'];

$tedaviler = get_tedaviler();

$hekim_foto = $kurumsal_data['hekim_foto'] ?? (SITE_URL . '/ibrahimduran.webp');
/* ==================================================================
 * 🎯 SAYFAYA ÖZEL SCHEMA'LAR (Anasayfa)
 * ==================================================================
 * header.php'deki MedicalBusiness schema'ya EK olarak eklenir.
 * ================================================================== */
$tum_schemalar = [];
$site_adresi = 'adres gir';

// ---------- OG IMAGE (Absolute URL) ----------
$web_og_image = $anasayfa['seo_og_image'] ?? '';
if (!empty($web_og_image) && strpos($web_og_image, 'http') !== 0) {
    $web_og_image = $site_adresi . '/' . ltrim($web_og_image, '/');
}

// 🔥 Person ve WebSite schema için AYNI resmi kullan (seo_og_image)
$hekim_foto_url = $web_og_image;

// ---------- 1) WebSite (Site kimliği + OG Image) ----------
$website_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => $site_adresi . '/#website',
    'url' => $site_adresi . '/',
    'name' => 'Prof. Dr. İbrahim Duran - Diş Kliniği Samsun',
    'description' => 'Samsun Atakum\'da protetik diş tedavisi, implant, gülüş tasarımı ve estetik diş hekimliği hizmetleri.',
    'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
    'publisher' => [
        '@id' => $site_adresi . '/#medicalbusiness'
    ]
];

// OG image varsa WebSite'a primaryImageOfPage olarak ekle
if (!empty($web_og_image)) {
    $website_schema['primaryImageOfPage'] = [
        '@type' => 'ImageObject',
        'url' => $web_og_image,
        'width' => 1200,
        'height' => 630,
        'caption' => 'Prof. Dr. İbrahim Duran - Samsun Atakum Diş Kliniği'
    ];
}

$tum_schemalar[] = $website_schema;

// ---------- 2) Person (Hekim kimliği - Fotoğraflı) ----------
if (!empty($hekim_foto_url)) {
    $hekim_isim = trim(($profil_unvan ?? 'Prof. Dr.') . ' ' . ($profil_ad ?? 'İbrahim Duran'));

    // Sosyal medya linkleri (site_ayarlari tablosundan)
    $hekim_sameas = [];
    $sosyal_sorgu = $db->query("SELECT ayar_key, ayar_value FROM site_ayarlari WHERE grup = 'sosyal'");
    if ($sosyal_sorgu) {
        $sosyal_ayarlar = $sosyal_sorgu->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach (['instagram', 'facebook', 'youtube', 'linkedin', 'twitter'] as $platform) {
            if (!empty($sosyal_ayarlar[$platform]) && $sosyal_ayarlar[$platform] !== '#') {
                $hekim_sameas[] = $sosyal_ayarlar[$platform];
            }
        }
    }

    $person_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        '@id' => $site_adresi . '/#hekim',
        'name' => $hekim_isim,
        'givenName' => 'İbrahim',
        'familyName' => 'Duran',
        'honorificPrefix' => 'Prof. Dr.',
        'image' => [
            '@type' => 'ImageObject',
            'url' => $hekim_foto_url,
            'width' => 1200,
            'height' => 630,
            'caption' => $hekim_isim . ' - Protetik Diş Tedavisi ve İmplant Uzmanı'
        ],
        'jobTitle' => 'Protetik Diş Tedavisi ve İmplant Uzmanı',
        'description' => !empty($doktor_aciklama)
            ? mb_substr(strip_tags($doktor_aciklama), 0, 300)
            : 'Prof. Dr. İbrahim Duran, Samsun Atakum\'da protetik diş tedavisi ve implant uzmanı.',
        'worksFor' => [
            '@id' => $site_adresi . '/#medicalbusiness'
        ],
        'url' => $site_adresi . '/',
        'alumniOf' => [
            '@type' => 'EducationalOrganization',
            'name' => 'Diş Hekimliği Fakültesi'
        ],
        'knowsAbout' => [
            'Protetik Diş Tedavisi',
            'Dental İmplantoloji',
            'Gülüş Tasarımı',
            'Estetik Diş Hekimliği',
            'Zirkonyum Kaplama',
            'Çene Eklemi Tedavisi'
        ],
        'inLanguage' => $dil_en ? 'en-US' : 'tr-TR'
    ];

    // Sosyal linkler varsa ekle
    if (!empty($hekim_sameas)) {
        $person_schema['sameAs'] = $hekim_sameas;
    }

    $tum_schemalar[] = $person_schema;
}

// ---------- 3) VideoObject (Anasayfa tanıtım videosu) ----------
if (!empty($video) && isset($video['tip'], $video['id']) && in_array($video['tip'], ['youtube', 'vimeo'])) {
    $video_thumb = !empty($video['thumb']) ? $video['thumb'] : ($video['thumb_yedek'] ?? '');

    $video_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        '@id' => $site_adresi . '/#video',
        'name' => 'Prof. Dr. İbrahim Duran - Klinik Tanıtım Videosu',
        'description' => !empty($doktor_aciklama)
            ? mb_substr(strip_tags($doktor_aciklama), 0, 300)
            : 'Prof. Dr. İbrahim Duran diş kliniği tanıtım videosu.',
        'thumbnailUrl' => $video_thumb,
        'uploadDate' => date('c', strtotime($anasayfa['guncelleme_tarihi'] ?? $anasayfa['created_at'] ?? 'now')),
        'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
        'isFamilyFriendly' => true,
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Prof. Dr. İbrahim Duran - Diş Kliniği',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $site_adresi . '/uploads/genel/genel_1779669534.png',
                'width' => 512,
                'height' => 512
            ]
        ]
    ];

    if ($video['tip'] === 'youtube') {
        $video_schema['embedUrl'] = 'https://www.youtube-nocookie.com/embed/' . $video['id'];
        $video_schema['contentUrl'] = 'https://www.youtube.com/watch?v=' . $video['id'];
    } elseif ($video['tip'] === 'vimeo') {
        $video_schema['embedUrl'] = 'https://player.vimeo.com/video/' . $video['id'];
        $video_schema['contentUrl'] = 'https://vimeo.com/' . $video['id'];
    }

    $tum_schemalar[] = $video_schema;
}

// ---------- 4) FAQPage (Anasayfa SSS bölümü) ----------
if (!empty($sss_sorular)) {
    $faq_items = [];
    $gorulen = [];
    foreach ($sss_sorular as $s) {
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
            '@id' => $site_adresi . '/#faq',
            'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
            'mainEntity' => $faq_items
        ];
    }
}

// ---------- 5) BreadcrumbList (Anasayfa - tek item) ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    '@id' => $site_adresi . '/#breadcrumb',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => $dil_en ? 'Home' : 'Anasayfa',
            'item' => $site_adresi . '/'
        ]
    ]
];

// ---------- Schema'ları değişkende topla (Pretty Print + Alt Alta) ----------
$sayfa_schemalari = '';
foreach ($tum_schemalar as $schema) {
    $sayfa_schemalari .= '<script type="application/ld+json">' . "\n"
                       . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                       . "\n" . '</script>' . "\n\n";
}

include 'inc/header.php';
include 'inc/slide.php';
?>

<style>
/* =========================================================
   Anasayfa CSS
   ========================================================= */

/* --- Video --- */
.ah-video { position: relative; aspect-ratio: 16 / 9; border-radius: 1.75rem; overflow: hidden; background: #0f172a; box-shadow: 0 30px 60px -25px rgba(15,23,42,.55); }
.ah-video iframe, .ah-video video, .ah-video .ah-poster { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
.ah-poster { display: block; padding: 0; cursor: pointer; background-size: cover; background-position: center; background-color: #0f2a4a; }
.ah-poster::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,23,42,.05) 30%, rgba(15,23,42,.65)); }
.ah-play { position: absolute; z-index: 2; left: 50%; top: 50%; transform: translate(-50%,-50%); width: 88px; height: 88px; border-radius: 9999px; background: #fff; color: #1d4ed8; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(0,0,0,.35); transition: transform .2s ease; }
.ah-poster:hover .ah-play, .ah-poster:focus-visible .ah-play { transform: translate(-50%,-50%) scale(1.08); }
.ah-poster:focus-visible { outline: 3px solid #60a5fa; outline-offset: -6px; }

/* --- Bölüm başlıkları (renkli gradient) --- */
.ah-baslik-grad {
    background: linear-gradient(90deg, #1d4ed8 0%, #0891b2 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    color: transparent;
}

/* --- Doktor kartı (video ile AYNI yükseklik) --- */
.ah-doktor {
    border-radius: 1.75rem;
    background: linear-gradient(135deg, #0f172a 0%, #111c34 60%, #0b2545 100%);
    color: #fff;
    position: relative;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.ah-doktor::before { content: ""; position: absolute; top: -80px; right: -80px; width: 260px; height: 260px; background: rgba(59,130,246,.25); filter: blur(70px); border-radius: 9999px; pointer-events: none; }
.ah-doktor::after  { content: ""; position: absolute; bottom: -80px; left: -80px; width: 220px; height: 220px; background: rgba(34,211,238,.18); filter: blur(70px); border-radius: 9999px; pointer-events: none; }
.ah-doktor > * { position: relative; z-index: 1; }

.ah-doktor-desc {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ah-doktor-vizyon {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .55rem;
    list-style: none;
    padding: 0;
    margin: 0;
}
.ah-doktor-vizyon li {
    display: flex;
    align-items: center;
    gap: .55rem;
    padding: .65rem .8rem;
    border-radius: .75rem;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.1);
    font-size: .85rem;
    line-height: 1.2;
    font-weight: 600;
    color: rgba(255,255,255,.92);
    overflow: hidden;
    min-width: 0;
}
.ah-doktor-vizyon .ah-vz-ico {
    flex-shrink: 0;
    width: 1.4rem;
    height: 1.4rem;
    border-radius: .4rem;
    background: rgba(96,165,250,.25);
    color: #93c5fd;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ah-doktor-vizyon .ah-vz-txt {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.ah-doktor-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .7rem;
}
.ah-stat {
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: .9rem;
    padding: .9rem .5rem;
    text-align: center;
    transition: background .2s ease;
}
.ah-stat:hover { background: rgba(255,255,255,.08); }
.ah-stat .ah-stat-num { font-size: 1.6rem; line-height: 1.1; font-weight: 800; color: #fff; letter-spacing: -.02em; }
.ah-stat .ah-stat-label { font-size: .78rem; color: rgba(255,255,255,.6); margin-top: .25rem; font-weight: 500; }

@media (max-width: 640px) {
    .ah-doktor-vizyon { grid-template-columns: 1fr; }
    .ah-doktor-vizyon .ah-vz-txt { white-space: normal; }
}

/* --- Sekmeler / tedavi kartları / SSS --- */
.ah-tab[aria-selected="true"] { background: #1d4ed8; color: #fff; box-shadow: 0 8px 20px -8px rgba(29,78,216,.7); }
.ah-panel[hidden] { display: none; }

.ah-tedavi .ah-desc { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.ah-tedavi.acik .ah-desc { -webkit-line-clamp: unset; display: block; }
.ah-tedavi .ah-ok { transition: transform .25s ease; }
.ah-tedavi.acik .ah-ok { transform: rotate(180deg); }

.ah-sss summary { list-style: none; cursor: pointer; }
.ah-sss summary::-webkit-details-marker { display: none; }
.ah-sss[open] .ah-sss-ok { transform: rotate(180deg); }
.ah-sss .ah-sss-ok { transition: transform .25s ease; }
.ah-sss[open] { border-color: #93c5fd; background: #f8fbff; }

@media (prefers-reduced-motion: reduce) { .ah-play, .ah-tedavi .ah-ok, .ah-sss .ah-sss-ok { transition: none; } }
</style>

<main class="bg-slate-50">

<!-- =====================================================
     1) VİDEO (SOL) + DOKTOR KARTI (SAĞ)
     ===================================================== -->
<section class="max-w-[1600px] mx-auto px-5">

    <div class="max-w-8xl mb-12 lg:mb-16 text-center">
        <h2 class="text-3xl lg:text-5xl font-extrabold leading-tight tracking-tight ah-baslik-grad">
            <?php echo ai_metin($anasayfa, 'video_baslik', 'Kliniğimizi Tanıyın'); ?>
        </h2>
        <p class="mt-4 text-lg lg:text-xl text-slate-600 leading-relaxed">
            <?php echo ai_metin($anasayfa, 'video_alt_baslik', ''); ?>
        </p>
    </div>

    <div class="grid lg:grid-cols-12 gap-6 lg:gap-8 items-stretch">

        <!-- SOL: Video (6/12) -->
        <div class="lg:col-span-6">
            <?php if ($video): ?>
                <div class="ah-video" id="ahVideo">
                    <?php if ($video['tip'] === 'dosya'): ?>
                        <video controls preload="metadata" playsinline>
                            <source src="<?php echo htmlspecialchars($video['src']); ?>">
                        </video>
                    <?php else: ?>
                        <button type="button" class="ah-poster" id="ahPoster"
                                data-embed="<?php echo htmlspecialchars($video['embed']); ?>"
                                <?php if (!empty($video['thumb'])): ?>
                                style="background-image:url('<?php echo htmlspecialchars($video['thumb']); ?>')"
                                data-yedek="<?php echo htmlspecialchars($video['thumb_yedek']); ?>"
                                <?php endif; ?>
                                aria-label="<?php echo htmlspecialchars(t_cevir('Videoyu oynat')); ?>">
                            <span class="ah-play">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72a1 1 0 0 0 1.53.85l10.9-6.86a1 1 0 0 0 0-1.7L9.53 4.29A1 1 0 0 0 8 5.14z"/></svg>
                            </span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="ah-video">
                    <img src="<?php echo htmlspecialchars($hekim_foto); ?>" alt="<?php echo htmlspecialchars($profil_ad ?? ''); ?>" class="w-full h-full object-cover object-top" loading="lazy" decoding="async">
                </div>
            <?php endif; ?>
        </div>

<!-- SAĞ: Doktor kartı (6/12) -->
    <aside class="lg:col-span-6">
        <div class="ah-doktor p-6 lg:p-8 h-full">

            <!-- 2 SÜTUN: FOTO (SOL) + İÇERİK (SAĞ) -->
            <div class="flex flex-col lg:flex-row gap-6 h-full">

                <!-- SOL: FOTO - TAM BOY -->
                <div class="relative shrink-0 lg:w-48 xl:w-56 w-full">
                    <img src="<?php echo htmlspecialchars($hekim_foto); ?>"
                         alt="<?php echo htmlspecialchars(($profil_unvan ?? '') . ' ' . ($profil_ad ?? '')); ?>"
                         class="w-full rounded-2xl object-cover object-top border-2 border-white/20 shadow-lg" style="height: 360px;"
                         loading="lazy" decoding="async">
                </div>

                <!-- SAĞ: DİĞER HER ŞEY -->
                <div class="flex-1 flex flex-col justify-between min-w-0">

                    <div>
                        <!-- Ünvan -->
                        <p class="text-blue-300 text-sm font-bold uppercase tracking-wider">
                            <?php echo ai_metin($anasayfa, 'akademik_vizyon_baslik', 'Akademik Vizyonumuz'); ?>
                        </p>
                        <h3 class="mt-2 text-xl lg:text-2xl xl:text-3xl font-extrabold leading-tight text-white break-words">
                            <?php echo htmlspecialchars($profil_unvan ?? ''); ?>
                        </h3>
                        <h4 class="mt-1 text-lg lg:text-xl xl:text-2xl font-bold leading-tight text-blue-200 break-words">
                            <?php echo htmlspecialchars($profil_ad ?? ''); ?>
                        </h4>

                        <!-- Açıklama -->
                        <?php if ($doktor_aciklama): ?>
                        <p class="mt-5 text-base text-white/80 leading-relaxed">
                            <?php echo htmlspecialchars($doktor_aciklama); ?>
                        </p>
                        <?php endif; ?>

                        <!-- Vizyon maddeleri -->
                        <?php if (!empty($profil_vizyon) && is_array($profil_vizyon)): ?>
                        <ul class="ah-doktor-vizyon mt-4" style="margin-top:14px">
                            <?php foreach ($profil_vizyon as $vizyon): ?>
                            <li>
                                <span class="ah-vz-ico" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span class="ah-vz-txt" style="font-size:15px;"><?php echo htmlspecialchars($vizyon['text'] ?? ''); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>

                    <!-- CTA BUTONLARI -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="/iletisim" class="flex-1 min-w-[140px] text-center px-4 py-3 rounded-xl bg-blue-600 text-white font-bold text-base hover:bg-blue-700 transition-colors">
                            <?php echo ai_metin($anasayfa, 'Hemen İletişime Geç', 'Hemen İletişime Geç'); ?>
                        </a>
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $telefon_goster); ?>"
                           class="flex-1 min-w-[140px] text-center px-4 py-3 rounded-xl border border-white/20 text-white font-bold text-base hover:bg-white/10 transition-colors whitespace-nowrap">
                            <?php echo htmlspecialchars($telefon_goster); ?>
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </aside>

   </div>

</section>

<!-- =====================================================
     2) TEDAVİLER
     ===================================================== -->
<section class="max-w-[1600px] mx-auto px-5 lg:px-8 mt-28 lg:mt-36">
    <div class="text-center mb-12">

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-5 mt-10">
            <h2 class="text-3xl lg:text-5xl font-extrabold tracking-tight ah-baslik-grad whitespace-nowrap">
                <?php echo t_cevir(ai_metin($anasayfa, 'tedaviler_baslik', 'Tedavilerimiz')); ?>
            </h2>
            <p class="text-base lg:text-lg text-slate-500">
                <?php echo ai_metin($anasayfa, 'tedaviler_alt_baslik', ''); ?>
            </p>
        </div>

        <div role="tablist" class="inline-flex mt-8 p-1.5 rounded-2xl bg-white border border-slate-200 shadow-sm">
            <?php $i = 0; foreach ($gruplar as $gid => $g): ?>
            <button type="button" role="tab" id="tab-<?php echo $gid; ?>" aria-controls="panel-<?php echo $gid; ?>"
                    aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                    class="ah-tab px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
                <?php echo htmlspecialchars($g['baslik']); ?>
            </button>
            <?php $i++; endforeach; ?>
        </div>
    </div>

    <?php $i = 0; foreach ($gruplar as $gid => $g): ?>
    <div role="tabpanel" id="panel-<?php echo $gid; ?>" aria-labelledby="tab-<?php echo $gid; ?>" class="ah-panel grid sm:grid-cols-2 xl:grid-cols-3 gap-6" <?php echo $i === 0 ? '' : 'hidden'; ?>>
        <?php foreach ($g['keys'] as $key):
            $item = $tedaviler_metin[$key] ?? null;
            if (!$item) continue;
            $ikon  = $item['icon'] ?? '🦷';
            $baslik = $item['title'] ?? $key;
            $aciklama = $item['desc'] ?? '';
        ?>
        <article class="ah-tedavi bg-white rounded-2xl border border-slate-200 p-6 flex flex-col">
            <div class="flex items-start gap-4">
                <span class="shrink-0 w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-2xl" aria-hidden="true"><?php echo htmlspecialchars($ikon); ?></span>
                <h3 class="text-lg font-bold text-slate-900 leading-snug pt-1"><?php echo htmlspecialchars(t_cevir($baslik)); ?></h3>
            </div>
            <p class="ah-desc mt-4 text-sm text-slate-600 leading-relaxed"><?php echo nl2br(htmlspecialchars(t_cevir($aciklama))); ?></p>
            <button type="button" class="ah-toggle mt-4 self-start inline-flex items-center gap-1 text-sm font-semibold text-blue-700 hover:text-blue-900" aria-expanded="false">
                <span class="ah-toggle-text"><?php echo $U['daha_fazla']; ?></span>
                <svg class="ah-ok w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
        </article>
        <?php endforeach; ?>
    </div>
    <?php $i++; endforeach; ?>
</section>

<!-- =====================================================
     İLETİŞİM BAŞLIĞI (mavi kutunun dışında, üstünde)
     ===================================================== -->
<div class="max-w-[1600px] mx-auto px-5 lg:px-8 mt-28 lg:mt-36">
    <div class="text-center mb-10 mt-10">
        <h2 class="text-3xl lg:text-5xl font-extrabold tracking-tight ah-baslik-grad">
            <?php echo $U['bizimle_iletisim']; ?>
        </h2>
        <p class="mt-4 text-lg text-slate-500">
            <?php echo $U['bizimle_iletisim_alt']; ?>
        </p>
    </div>
</div>

<!-- KOYU MAVİ BÖLÜM -->
<section class="bg-white">
    <div class="max-w-full mx-auto px-6 lg:px-8">
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-3xl p-12 text-white overflow-hidden relative">
            <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/20 rounded-full blur-[100px]"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-purple-500/20 rounded-full blur-[100px]"></div>
            <div class="relative z-10 grid lg:grid-cols-2 gap-12 items-center">
                
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white border border-white/20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
                        <span class="text-xs font-semibold"><?php echo $U['akademik_kadro']; ?></span>
                    </div>
                    <h2 class="text-4xl lg:text-5xl font-bold leading-tight">
                        <?php 
                        $ad_parca = explode(' ', $profil_ad ?? 'Prof. Dr. İbrahim Duran');
                        $unvan_parca = explode(' ', $profil_unvan ?? 'Prof. Dr.');
                        ?>
                        <?php echo htmlspecialchars($unvan_parca[0] ?? 'Prof.'); ?> <br/>
                        <span class="text-blue-400"><?php echo htmlspecialchars($profil_ad ?? 'Dr. İbrahim Duran'); ?></span>
                    </h2>
                    <p class="text-gray-300 text-lg leading-relaxed">
                        <?php echo htmlspecialchars($doktor_aciklama ?: ''); ?>
                    </p>
                    <div class="flex items-center gap-4 pt-4">

                        <div class="w-px h-10 bg-white/20"></div>

                    </div>
                </div>

                <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-8 border border-white/10">
                    <h3 class="text-2xl font-bold mb-6"><?php echo $U['hemen_iletisim']; ?></h3>
                    <div class="space-y-4">
                        
                        <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $telefon_goster); ?>" class="flex items-center gap-4 p-4 bg-white/10 rounded-xl hover:bg-white/20 transition-colors group">
                            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path></svg>
                            </div>
                            <div>
                                <div class="text-sm text-gray-400"><?php echo $U['telefon']; ?></div>
                                <div class="font-semibold text-lg"><?php echo htmlspecialchars($telefon_goster); ?></div>
                            </div>
                        </a>
                        
                        <a href="mailto:<?php echo htmlspecialchars($eposta_goster); ?>" class="flex items-center gap-4 p-4 bg-white/10 rounded-xl hover:bg-white/20 transition-colors group">
                            <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path><rect x="2" y="4" width="20" height="16" rx="2"></rect></svg>
                            </div>
                            <div>
                                <div class="text-sm text-gray-400"><?php echo $U['eposta']; ?></div>
                                <div class="font-semibold text-lg"><?php echo htmlspecialchars($eposta_goster); ?></div>
                            </div>
                        </a>
                        
                        <div class="flex items-center gap-4 p-4 bg-white/10 rounded-xl">
                            <div class="w-12 h-12 bg-emerald-600 rounded-xl flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </div>
                            <div>
                                <div class="text-sm text-gray-400"><?php echo $U['adres']; ?></div>
                                <div class="font-semibold"><?php echo htmlspecialchars($adres_goster); ?></div>
                            </div>
                        </div>
                        
                        <a class="w-full mt-4 py-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2 no-underline" href="/iletisim">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg>
                            <?php echo $U['hemen_iletisim']; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =====================================================
     3) SSS
     ===================================================== -->
<?php if (!empty($sss_sorular)): ?>
<section class="max-w-[1600px] mx-auto px-5 lg:px-8 mt-28 lg:mt-36">
    <div class="max-w-3xl mx-auto text-center mb-8 mt-10">
        <h2 class="text-3xl lg:text-5xl font-extrabold tracking-tight ah-baslik-grad"><?php echo ai_metin($anasayfa, 'sss_baslik', 'Sıkça Sorulan Sorular'); ?></h2>
        <p class="mt-4 text-lg text-slate-600"><?php echo ai_metin($anasayfa, 'sss_alt_baslik', ''); ?></p>
    </div>

    <div class="grid lg:grid-cols-2 gap-x-6 gap-y-3 items-start text-left">
        <?php foreach ([$sss_sol, $sss_sag] as $kolon): ?>
        <div class="space-y-3">
            <?php foreach ($kolon as $item): ?>
            <details class="ah-sss group bg-white border border-slate-200 rounded-2xl">
                <summary class="flex items-start justify-between gap-4 p-5 rounded-2xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
                    <span>
                        <span class="block text-xs font-semibold text-blue-700 mb-1"><?php echo htmlspecialchars($item['kategori']); ?></span>
                        <span class="block font-bold text-slate-900"><?php echo htmlspecialchars($item['soru']); ?></span>
                    </span>
                    <svg class="ah-sss-ok w-5 h-5 mt-1 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($item['cevap'])); ?></div>
            </details>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
</main>

<script>
(function () {
    'use strict';

    var poster = document.getElementById('ahPoster');
    if (poster) {
        var yedek = poster.getAttribute('data-yedek');
        if (yedek) {
            var test = new Image();
            test.onload = function () { if (test.width <= 120) poster.style.backgroundImage = "url('" + yedek + "')"; };
            test.onerror = function () { poster.style.backgroundImage = "url('" + yedek + "')"; };
            test.src = poster.style.backgroundImage.replace(/^url\(["']?|["']?\)$/g, '');
        }
        poster.addEventListener('click', function () {
            var f = document.createElement('iframe');
            f.src = poster.getAttribute('data-embed');
            f.allow = 'accelerometer; autoplay; encrypted-media; picture-in-picture; fullscreen';
            f.allowFullscreen = true;
            f.title = 'Video';
            poster.replaceWith(f);
        });
    }

    var tabs = document.querySelectorAll('.ah-tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                var aktif = (t === tab);
                t.setAttribute('aria-selected', aktif ? 'true' : 'false');
                document.getElementById(t.getAttribute('aria-controls')).hidden = !aktif;
            });
        });
    });

    var metinAc = <?php echo json_encode($U['daha_fazla']); ?>;
    var metinKapat = <?php echo json_encode($U['daha_az']); ?>;
    document.querySelectorAll('.ah-tedavi .ah-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var kart = btn.closest('.ah-tedavi');
            var acik = kart.classList.toggle('acik');
            btn.setAttribute('aria-expanded', acik ? 'true' : 'false');
            btn.querySelector('.ah-toggle-text').textContent = acik ? metinKapat : metinAc;
        });
    });
})();
</script>

<?php include 'inc/footer.php'; ?>