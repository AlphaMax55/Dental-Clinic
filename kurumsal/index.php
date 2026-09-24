<?php
// www/site/kurumsal.php
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(__DIR__) . '/inc/config.php';

// ========== KURUMSAL SAYFA SEO META (HEADER'DAN ÖNCE) ==========

// 🔥 DEĞİŞKENLERİ GLOBAL YAP! Header.php'ye taşınması için
global $page_title, $seo_description, $site_keywords, $dynamic_og_title, $og_image;

// Dil kontrolü
$lang = $_SESSION['dil'] ?? 'tr';
$dil_en = ($lang === 'en');

// SEO Meta Değerleri - Kurumsal
// Varsayılan değerler, veritabanından gelecekse üzerine yazılacak
$page_title = ($lang === 'en') 
    ? 'Corporate | Prof. Dr. İbrahim Duran | Dental Clinic Samsun' 
    : 'Kurumsal | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';

$seo_description = ($lang === 'en')
    ? 'Corporate information about Prof. Dr. İbrahim Duran Dental Clinic. Our vision, mission, values and treatment approach in Samsun Atakum.'
    : 'Prof. Dr. İbrahim Duran Diş Kliniği kurumsal bilgileri. Vizyonumuz, misyonumuz, değerlerimiz ve Samsun Atakum\'daki tedavi yaklaşımımız.';

$site_keywords = ($lang === 'en')
    ? 'corporate, dental clinic Samsun, Prof. Dr. İbrahim Duran, dental corporate, Samsun dentist'
    : 'kurumsal, diş kliniği Samsun, Prof. Dr. İbrahim Duran, diş hekimi kurumsal, Samsun diş hekimi';

$dynamic_og_title = $page_title;

// OG Image - Kurumsal sayfasına özel
$og_image = '/uploads/kurumsal-og.webp';

// ========== KURUMSAL VERİLERİNİ TEK SEFERDE ÇEK ==========
$stmt = $db->query("SELECT ayar_key, ayar_value FROM kurumsal_ayarlari");
$kurumsal_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);


// ===== VERİTABANINDAN SEO DEĞERLERİNİ ÇEK (Varsa üzerine yaz) =====
if (isset($kurumsal_data['seo_title_tr']) && !empty($kurumsal_data['seo_title_tr'])) {
    $page_title = ($lang === 'en' && !empty($kurumsal_data['seo_title_en'])) 
        ? $kurumsal_data['seo_title_en'] 
        : $kurumsal_data['seo_title_tr'];
}

if (isset($kurumsal_data['seo_description_tr']) && !empty($kurumsal_data['seo_description_tr'])) {
    $seo_description = ($lang === 'en' && !empty($kurumsal_data['seo_description_en'])) 
        ? $kurumsal_data['seo_description_en'] 
        : $kurumsal_data['seo_description_tr'];
}

if (isset($kurumsal_data['seo_keywords_tr']) && !empty($kurumsal_data['seo_keywords_tr'])) {
    $site_keywords = ($lang === 'en' && !empty($kurumsal_data['seo_keywords_en'])) 
        ? $kurumsal_data['seo_keywords_en'] 
        : $kurumsal_data['seo_keywords_tr'];
}

if (isset($kurumsal_data['seo_og_image']) && !empty($kurumsal_data['seo_og_image'])) {
    $og_image = $kurumsal_data['seo_og_image'];
}

// dynamic_og_title
$dynamic_og_title = $page_title;

// ========== TEDAVİ METİNLERİNİ ÇEK (KURUMSAL) ==========
$tedaviler_metin = [];
$tedavi_icon = [];
$tedavi_title = [];
$tedaviler_baslik = 'Tedavilerimiz';
$tedaviler_alt = 'Prof. Dr. İbrahim Duran kliniğinden bir kesit.';

// 1. Önce kurumsal_ayarlari'ndan dene
if (!empty($kurumsal_data['tedaviler_metin'])) {
    $raw_metin = json_decode($kurumsal_data['tedaviler_metin'], true);
    if (is_array($raw_metin) && !empty($raw_metin)) {
        foreach ($raw_metin as $key => $value) {
            if (is_array($value)) {
                $tedaviler_metin[$key] = $value['desc'] ?? '';
                $tedavi_icon[$key] = $value['icon'] ?? '';
                $tedavi_title[$key] = $value['title'] ?? '';
            } elseif (is_string($value)) {
                $tedaviler_metin[$key] = $value;
                $tedavi_icon[$key] = '';
                $tedavi_title[$key] = '';
            }
        }
    }
}

// 2. Eğer boşsa anasayfa_icerik'ten dene
if (empty($tedaviler_metin)) {
    try {
        $anasayfa = $db->query("SELECT * FROM anasayfa_icerik WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
        if ($anasayfa) {
            if (!empty($anasayfa['tedaviler_metin_json'])) {
                $raw_metin = json_decode($anasayfa['tedaviler_metin_json'], true);
                if (is_array($raw_metin) && !empty($raw_metin)) {
                    foreach ($raw_metin as $key => $value) {
                        if (is_array($value)) {
                            $tedaviler_metin[$key] = $value['desc'] ?? '';
                            $tedavi_icon[$key] = $value['icon'] ?? '';
                            $tedavi_title[$key] = $value['title'] ?? '';
                        } elseif (is_string($value)) {
                            $tedaviler_metin[$key] = $value;
                            $tedavi_icon[$key] = '';
                            $tedavi_title[$key] = '';
                        }
                    }
                }
            }
            // Başlık ve alt başlık
            if (!empty($anasayfa['tedaviler_baslik_tr'])) {
                $tedaviler_baslik = $anasayfa['tedaviler_baslik_tr'];
            }
            if (!empty($anasayfa['tedaviler_alt_baslik_tr'])) {
                $tedaviler_alt = $anasayfa['tedaviler_alt_baslik_tr'];
            }
        }
    } catch (Exception $e) {}
}

// 3. Varsayılan değerler
$tedaviKeys = ['dis_estetigi', 'dis_agrisi', 'implant', 'dis_eti', 'kanal', 'ortodonti', 'cocuk', 'cene_eklemi', 'beyazlatma', 'samsun'];
foreach ($tedaviKeys as $key) {
    if (!isset($tedaviler_metin[$key])) $tedaviler_metin[$key] = '';
    if (!isset($tedavi_icon[$key])) $tedavi_icon[$key] = '';
    if (!isset($tedavi_title[$key])) $tedavi_title[$key] = '';
}

// ========== YÖNTEMLER, DEĞERLER, KARTLAR ==========
$yontemler_liste = !empty($kurumsal_data['yontemler_liste']) ? json_decode($kurumsal_data['yontemler_liste'], true) : [
    ['title' => t_cevir('Hasta Odaklılık'), 'icon' => 'HeartPulse'],
    ['title' => t_cevir('Etik Değerler'), 'icon' => 'ShieldCheck'],
    ['title' => t_cevir('Mükemmeliyet'), 'icon' => 'Zap']
];

$degerler_liste = !empty($kurumsal_data['degerler_liste']) ? json_decode($kurumsal_data['degerler_liste'], true) : [
    ['title' => t_cevir('Hasta Odaklılık'), 'desc' => t_cevir('Her hastamızı özel ve öncelikli görürüz.'), 'icon' => 'HeartPulse'],
    ['title' => t_cevir('Etik Değerler'), 'desc' => t_cevir('Dürüstlük, şeffaflık ve güvenilirlik.'), 'icon' => 'ShieldCheck'],
    ['title' => t_cevir('Mükemmeliyet'), 'desc' => t_cevir('En yüksek kalitede hizmet.'), 'icon' => 'Award']
];

$tedavi_kartlari = !empty($kurumsal_data['tedavi_kartlari']) ? json_decode($kurumsal_data['tedavi_kartlari'], true) : [
    ['id' => 'implant', 'title' => t_cevir('İmplant Tedavisi'), 'desc' => t_cevir('Eksik dişlerin kalıcı restorasyonu.'), 'image' => 'https://images.unsplash.com/photo-1606811841689-23dfddce3e95?q=80&w=800'],
    ['id' => 'zirkonyum', 'title' => t_cevir('Zirkonyum Kaplama'), 'desc' => t_cevir('Estetik ve dayanıklı kaplamalar.'), 'image' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?q=80&w=800'],
    ['id' => 'gulus-tasarimi', 'title' => t_cevir('Gülüş Tasarımı'), 'desc' => t_cevir('Dijital simülasyon destekli gülüşler.'), 'image' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?q=80&w=800']
];

// ========== UZMANLIK GRUPLARI ==========
$uzmanlik_gruplari = [];
$stmt = $db->query("SELECT * FROM uzmanlik_gruplari WHERE durum = 1 ORDER BY grup_sira ASC, id ASC");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['baslik'] = t_cevir($row['baslik']);
    $maddeler = json_decode($row['maddeler'], true);
    $row['maddeler'] = is_array($maddeler) ? array_map('t_cevir', $maddeler) : [];
    $uzmanlik_gruplari[] = $row;
}

$active_tab = isset($_GET['tab']) ? intval($_GET['tab']) : 0;
$page_slug = 'kurumsal';

/* ==================================================================
 * 🎯 SAYFAYA ÖZEL SCHEMA'LAR (Kurumsal)
 * ==================================================================
 * header.php'deki MedicalBusiness schema'ya EK olarak eklenir.
 * ================================================================== */
$tum_schemalar = [];
$site_adresi = 'adres gir';

// Hekim fotoğrafını tam URL'ye çevir
$hekim_foto_ham = $kurumsal_data['hekim_foto'] ?? '/ibrahimduran.webp';
$hekim_foto_url = $hekim_foto_ham;
if (!empty($hekim_foto_url) && strpos($hekim_foto_url, 'http') !== 0) {
    $hekim_foto_url = $site_adresi . '/' . ltrim($hekim_foto_url, '/');
}

// Hekim ismi
$hekim_isim = trim(($kurumsal_data['hekim_unvan'] ?? 'Prof. Dr.') . ' ' . ($kurumsal_data['hekim_isim'] ?? 'İbrahim DURAN'));
if (empty($hekim_isim) || $hekim_isim === 'Prof. Dr.') {
    $hekim_isim = 'Prof. Dr. İbrahim Duran';
}

// ---------- 1) AboutPage (Kurumsal sayfa kimliği) ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
    '@id' => $site_adresi . '/kurumsal/#aboutpage',
    'url' => $site_adresi . '/kurumsal/',
    'name' => $page_title,
    'description' => $seo_description,
    'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
    'isPartOf' => [
        '@id' => $site_adresi . '/#website'
    ],
    'about' => [
        '@id' => $site_adresi . '/#medicalbusiness'
    ],
    'mainEntity' => [
        '@id' => $site_adresi . '/#hekim'
    ]
];

// ---------- 2) Person (Hekim detaylı kimlik) ----------
if (!empty($hekim_foto_url)) {
    $tum_schemalar[] = [
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
            'width' => 800,
            'height' => 800,
            'caption' => $hekim_isim . ' - Protetik Diş Tedavisi ve İmplant Uzmanı'
        ],
        'jobTitle' => 'Protetik Diş Tedavisi ve İmplant Uzmanı',
        'description' => !empty($seo_description)
            ? mb_substr(strip_tags($seo_description), 0, 300)
            : $hekim_isim . ' - Samsun Atakum\'da protetik diş tedavisi ve implant uzmanı.',
        'worksFor' => [
            '@id' => $site_adresi . '/#medicalbusiness'
        ],
        'url' => $site_adresi . '/kurumsal/',
        'alumniOf' => [
            '@type' => 'EducationalOrganization',
            'name' => 'Gazi Üniversitesi Diş Hekimliği Fakültesi'
        ],
        'hasCredential' => [
            [
                '@type' => 'EducationalOccupationalCredential',
                'name' => 'Doktora',
                'dateCreated' => '2011'
            ],
            [
                '@type' => 'EducationalOccupationalCredential',
                'name' => 'Doçentlik',
                'dateCreated' => '2017'
            ],
            [
                '@type' => 'EducationalOccupationalCredential',
                'name' => 'Profesörlük',
                'dateCreated' => '2025'
            ]
        ],
        'knowsAbout' => [
            'Protetik Diş Tedavisi',
            'İmplant Tedavisi',
            'İmplant Üstü Protez',
            'Dikişsiz İmplant',
            'Dijital Gülüş Tasarımı',
            'Zirkonyum Kaplama',
            'Porselen Lamina (Veneer)',
            'Lamine Diş',
            'Tam Protez',
            'Hassas Tutuculu Protez',
            'İnlay ve Onlay Dolgular',
            'Diş Sıkma (Bruksizm) Tedavisi',
            'Çene Eklem Rahatsızlıkları',
            'Estetik Diş Hekimliği',
            'Gülüş Estetiği',
            'İmplant Cerrahisi',
            'Sinus Lifting'
        ],
        'sameAs' => [
            'https://instagram.com/dribrahimduran',
            'https://linkedin.com/in/ibrahim-duran'
        ],
        'inLanguage' => $dil_en ? 'en-US' : 'tr-TR'
    ];
}

// ---------- 3) BreadcrumbList (Anasayfa > Kurumsal) ----------
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
            'name' => $dil_en ? 'Corporate' : 'Kurumsal',
            'item' => $site_adresi . '/kurumsal/'
        ]
    ]
];

// ---------- 4) Organization (Kurum detaylı - MedicalBusiness ile @id bağlantılı) ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'MedicalClinic',
    '@id' => $site_adresi . '/#medicalbusiness',
    'name' => 'Prof. Dr. İbrahim Duran - Protetik Diş Tedavisi ve İmplant Uzmanı',
    'alternateName' => 'RivaDent Diş Kliniği',
    'url' => $site_adresi . '/',
    'logo' => [
        '@type' => 'ImageObject',
        'url' => $site_adresi . '/uploads/genel/genel_1779669534.png',
        'width' => 512,
        'height' => 512
    ],
    'image' => $hekim_foto_url,
    'description' => 'Samsun Atakum\'da protetik diş tedavisi, implant, gülüş tasarımı ve estetik diş hekimliği hizmetleri sunan diş kliniği.',
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => 'Mimarsinan, Atatürk Bl. Riva İş merkezi 260/2',
        'addressLocality' => 'Atakum',
        'addressRegion' => 'Samsun',
        'postalCode' => '55200',
        'addressCountry' => 'TR'
    ],
    'telephone' => '+905330869167',
    'email' => 'ibrahimdurandental@gmail.com',
    'priceRange' => '$$',
    'founder' => [
        '@id' => $site_adresi . '/#hekim'
    ],
    'medicalSpecialty' => [
        'Prosthodontics',
        'CosmeticDentistry',
        'OralSurgery'
    ],
    'sameAs' => [
        'https://instagram.com/dribrahimduran',
        'https://www.google.com/maps/place/Prof+Dr+İbrahim+Duran/@41.3364323,36.2721827,816m/data=!3m2!1e3!4b1!4m6!3m5!1s0x40887912c588fbf9:0x187c8dea4fd80630!8m2!3d41.3364323!4d36.2747576!16s%2Fg%2F11mlcslr2w?entry'
    ],
    'inLanguage' => $dil_en ? 'en-US' : 'tr-TR'
];

// ---------- Schema'ları değişkende topla (Pretty Print ile) ----------
$sayfa_schemalari = '';
foreach ($tum_schemalar as $schema) {
    $sayfa_schemalari .= '<script type="application/ld+json">' . "\n"
                       . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                       . "\n" . '</script>' . "\n";
}

include dirname(__DIR__) . '/inc/header.php';
include dirname(__DIR__) . '/inc/slide2.php';
?>

<!-- KURUMSAL MASTER PANEL -->
<section id="kurumsal-panel" class="bg-[#f0f8ff] py-12 lg:py-20 px-4 lg:px-10">
    <div class="max-w-[1600px] mx-auto">
        <div class="grid lg:grid-cols-12 gap-6 items-stretch">
            
            <div class="lg:col-span-3 space-y-3">
                <?php 
                $tabs = [
                    ['id' => 0, 'title' => t_cevir('Hakkımızda'), 'icon' => 'Building2', 'sub' => t_cevir('Kurumsal Vizyon')],
                    ['id' => 1, 'title' => t_cevir('Vizyonumuz'), 'icon' => 'GraduationCap', 'sub' => t_cevir('Gelecek Hedefi')],
                    ['id' => 2, 'title' => t_cevir('Misyonumuz'), 'icon' => 'GraduationCap', 'sub' => t_cevir('Hizmet Amacı')],
                    ['id' => 3, 'title' => t_cevir('Uzmanlık Alanları'), 'icon' => 'Award', 'sub' => t_cevir('Bilimsel Odak')],
                    ['id' => 5, 'title' => t_cevir('Değerlerimiz'), 'icon' => 'HeartPulse', 'sub' => t_cevir('Temel İlkeler')]
                ];
                foreach($tabs as $tab): 
                    $is_active = ($active_tab == $tab['id']);
                ?>
                <a href="?tab=<?php echo $tab['id']; ?>#kurumsal-panel" class="block">
                    <div class="w-full flex items-center gap-4 p-5 rounded-[2rem] transition-all duration-500 group border <?php echo $is_active ? 'bg-blue-600 border-blue-600 shadow-xl shadow-blue-500/20 -translate-y-1' : 'bg-white border-slate-100 hover:border-blue-200'; ?>">
                        <div class="p-3 rounded-2xl transition-colors <?php echo $is_active ? 'bg-white/20 text-white' : 'bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white'; ?>">
                            <?php echo getLucideIcon($tab['icon'], 20); ?>
                        </div>
                        <div class="text-left">
                            <h4 class="font-black text-xs uppercase tracking-tight <?php echo $is_active ? 'text-white' : 'text-slate-900'; ?>"><?php echo htmlspecialchars($tab['title']); ?></h4>
                            <p class="text-[9px] font-bold uppercase tracking-widest mt-0.5 <?php echo $is_active ? 'text-blue-200' : 'text-slate-800'; ?>"><?php echo htmlspecialchars($tab['sub']); ?></p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="lg:col-span-6 bg-white rounded-[3.5rem] p-8 lg:p-14 shadow-2xl border border-blue-50 relative overflow-hidden flex flex-col justify-center min-h-[700px]">
                
                <?php if($active_tab == 0): ?>
                <div class="space-y-8">
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-600 text-white rounded-full font-black text-[9px] tracking-widest uppercase">
                            <?php echo getLucideIcon('GraduationCap', 12); ?> <?php echo htmlspecialchars(t_cevir($kurumsal_data['hakkimizda_badge'] ?? 'HEKİM ÖZGEÇMİŞİ')); ?>
                        </div>
                        <?php 
                        $hakkimizda_baslik = $kurumsal_data['hakkimizda_baslik'] ?? 'Bilimsel Temelli Estetik Mükemmellik';
                        $baslik_parti = explode(' ', t_cevir($hakkimizda_baslik));
                        $ilk_kelime = array_shift($baslik_parti);
                        $kalan_kisim = implode(' ', $baslik_parti);
                        ?>
                        <h3 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter italic leading-tight uppercase">
                            <?php echo htmlspecialchars($ilk_kelime); ?> <br/><span class="text-blue-600 not-italic"><?php echo htmlspecialchars($kalan_kisim); ?></span>
                        </h3>
                        <div class="w-20 h-1.5 bg-blue-600 rounded-full"></div>
                    </div>
                    <div class="space-y-6 text-slate-800 font-medium leading-relaxed text-base lg:text-lg">
                        <div><?php echo t_cevir($kurumsal_data['hakkimizda_yazi'] ?? ''); ?></div>
                        <p class="font-bold text-slate-900 italic border-l-4 border-blue-600 pl-6 bg-slate-50 py-4 rounded-r-2xl">
                            <?php echo htmlspecialchars(t_cevir($kurumsal_data['hakkimizda_quote'] ?? '')); ?>
                        </p>
                        <p class="text-sm"><?php echo htmlspecialchars(t_cevir($kurumsal_data['hakkimizda_alt'] ?? '')); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($active_tab == 1): ?>
                <div class="space-y-8">
                    <div class="space-y-4">
                        <?php 
                        $vizyon_baslik = t_cevir($kurumsal_data['vizyon_baslik'] ?? 'Global Standartlarda Hizmet');
                        $baslik_parti = explode(' ', $vizyon_baslik);
                        $ilk_kelime = array_shift($baslik_parti);
                        $kalan_kisim = implode(' ', $baslik_parti);
                        ?>
                        <h3 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter uppercase leading-[0.9]">
                            <?php echo htmlspecialchars($ilk_kelime); ?> <br/><span class="text-blue-600 italic"><?php echo htmlspecialchars($kalan_kisim); ?></span>
                        </h3>
                        <div class="w-20 h-1.5 bg-blue-600 rounded-full"></div>
                    </div>
                    <div class="space-y-6 text-slate-800 font-medium leading-relaxed text-base lg:text-lg">
                        <p><?php echo t_cevir($kurumsal_data['vizyon_yazi'] ?? ''); ?></p>
                        <div class="bg-slate-50 p-8 rounded-[3rem] border border-slate-100 space-y-4 shadow-sm">
                            <h4 class="text-blue-600 font-black uppercase text-xs tracking-[0.2em] mb-2"><?php echo htmlspecialchars(t_cevir($kurumsal_data['vizyon_badge'] ?? 'VİZYONUMUZ')); ?></h4>
                            <p class="text-slate-700 font-bold leading-relaxed"><?php echo htmlspecialchars(t_cevir($kurumsal_data['vizyon_kutu'] ?? '')); ?></p>
                        </div>
                        <p class="font-medium border-l-4 border-blue-600 pl-6 italic text-slate-500"><?php echo htmlspecialchars(t_cevir($kurumsal_data['vizyon_quote'] ?? '')); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($active_tab == 2): ?>
                <div class="space-y-8">
                    <div class="space-y-4">
                        <h4 class="text-blue-600 font-black uppercase text-xs tracking-[0.3em]"><?php echo htmlspecialchars(t_cevir($kurumsal_data['misyon_badge'] ?? 'MİSYONUMUZ')); ?></h4>
                        <?php 
                        $misyon_baslik = t_cevir($kurumsal_data['misyon_baslik'] ?? 'Yüksek Kalite Maksimum Memnuniyet');
                        $baslik_parti = explode(' ', $misyon_baslik);
                        $ilk_kelime = array_shift($baslik_parti);
                        $kalan_kisim = implode(' ', $baslik_parti);
                        ?>
                        <h3 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter leading-tight uppercase">
                            <?php echo htmlspecialchars($ilk_kelime); ?> <br/><span class="text-blue-600 italic"><?php echo htmlspecialchars($kalan_kisim); ?></span>
                        </h3>
                        <div class="w-20 h-1.5 bg-blue-600 rounded-full"></div>
                    </div>
                    <div class="space-y-6 text-slate-800 font-medium leading-relaxed text-base lg:text-lg">
                        <p><?php echo t_cevir($kurumsal_data['misyon_yazi'] ?? ''); ?></p>
                        <div class="bg-blue-600 text-white p-8 lg:p-12 rounded-[3.5rem] shadow-2xl shadow-blue-500/20 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 p-8 opacity-10">
                                <?php echo getLucideIcon('Sparkles', 120); ?>
                            </div>
                            <p class="relative z-10 text-xl lg:text-2xl font-black italic leading-tight tracking-tight"><?php echo htmlspecialchars(t_cevir($kurumsal_data['misyon_quote'] ?? '')); ?></p>
                        </div>
                        <p class="font-bold text-slate-900 border-l-4 border-blue-600 pl-6"><?php echo t_cevir($kurumsal_data['misyon_alt'] ?? ''); ?></p>
                    </div>
                </div>
                <?php endif; ?>
				
				<?php if($active_tab == 3): ?>
<div class="space-y-8">
    <div class="space-y-4">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-600 text-white rounded-full font-black text-[9px] tracking-widest uppercase">
            <?php echo getLucideIcon('Award', 12); ?> <?php echo t_cevir("UZMANLIK ALANLARI"); ?>
        </div>
        <h3 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter italic leading-tight uppercase">
            <?php echo t_cevir("Modern"); ?> <br/><span class="text-blue-600 not-italic"><?php echo t_cevir("Protokoller"); ?></span>
        </h3>
        <div class="w-20 h-1.5 bg-blue-600 rounded-full"></div>
    </div>
    
    <?php if(!empty($uzmanlik_gruplari)): ?>
        <div class="space-y-8">
            <?php foreach($uzmanlik_gruplari as $grup): ?>
            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 shadow-sm">
                <h4 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 bg-blue-600 rounded-full inline-block"></span>
                    <?php echo htmlspecialchars($grup['baslik']); ?>
                </h4>
                <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <?php foreach($grup['maddeler'] as $madde): ?>
                    <li class="flex items-center gap-3 text-slate-700 font-medium text-sm">
                        <span class="w-1.5 h-1.5 bg-blue-400 rounded-full inline-block"></span>
                        <?php echo htmlspecialchars($madde); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 p-8 rounded-3xl text-center">
            <p class="font-medium"><?php echo t_cevir("Henüz uzmanlık alanı eklenmemiş. Paneli ziyaret ederek ekleyebilirsiniz."); ?></p>
            <a href="/admin/modules/kurumsal/?tab=uzmanlik" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-full text-sm font-bold hover:bg-blue-700 transition-all">
                <?php echo t_cevir("Panele Git"); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
            </div>

<div class="lg:col-span-3 space-y-6 flex flex-col justify-between">
    <!-- flex-grow kaldırıldı, h-[500px] ile boyu taş gibi sabitlendi -->
    <div class="relative h-[500px] w-full rounded-[3.5rem] overflow-hidden border-[12px] border-white shadow-2xl group">
        <img src="<?php echo htmlspecialchars($kurumsal_data['hekim_foto'] ?? '/ibrahimduran.webp'); ?>" alt="Prof. Dr. İbrahim DURAN" class="w-full h-full object-cover object-top">
        <div class="absolute bottom-8 left-8 right-8 bg-white/10 backdrop-blur-md p-4 rounded-3xl border border-white/25">
            <p class="text-white font-black text-[10px] uppercase tracking-widest mb-1 opacity-80"><?php echo t_cevir("Hekim Özgeçmişi"); ?></p>
            <h4 class="text-white font-black text-xl uppercase leading-tight tracking-tighter"><?php echo htmlspecialchars($kurumsal_data['hekim_unvan'] ?? 'Prof. Dr.'); ?> <br/><?php echo htmlspecialchars($kurumsal_data['hekim_isim'] ?? 'İbrahim DURAN'); ?></h4>
        </div>
    </div>
</div>
        </div>
    </div>
</section>
<!-- ========== TEDAVİLER BÖLÜMÜ (DİNAMİK - VERİTABANINDAN) ========== -->
<section class="bg-gradient-to-b from-white to-slate-50 py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
        
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-200 rounded-full text-blue-700 font-black text-[10px] tracking-widest uppercase mb-4">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/>
                </svg>
                <?php echo t_cevir("TEDAVİLERİMİZ"); ?>
            </div>
            <h2 class="text-3xl lg:text-4xl font-bold text-slate-800">
                <?php echo t_cevir($tedaviler_baslik ?? 'Prof. Dr. İbrahim Duran'); ?> 
                <span class="text-blue-600"><?php echo t_cevir($tedaviler_alt ?? 'kliniğinden bir kesit.'); ?></span>
            </h2>
            <p class="text-slate-500 mt-2"><?php echo t_cevir("Bilimsel temelli, estetik odaklı tedavi yaklaşımlarımız"); ?></p>
        </div>
        
        <div class="grid md:grid-cols-2 gap-8">
            
            <?php 
            // ============================================================
            // SOL GRUP - Estetik & Gülüş Tasarımı
            // ============================================================
            $sol_grup = [
                'goster_1' => ['key' => 'dis_estetigi', 'icon' => $tedavi_icon['dis_estetigi'] ?? '🦷', 'title' => t_cevir($tedavi_title['dis_estetigi'] ?? 'Diş Estetiği')],
                'goster_2' => ['key' => 'dis_agrisi', 'icon' => $tedavi_icon['dis_agrisi'] ?? '🦷', 'title' => t_cevir($tedavi_title['dis_agrisi'] ?? 'Diş Ağrısı')],
                'gizli_1' => ['key' => 'implant', 'icon' => $tedavi_icon['implant'] ?? '💉', 'title' => t_cevir($tedavi_title['implant'] ?? 'İmplant Tedavisi')],
                'gizli_2' => ['key' => 'dis_eti', 'icon' => $tedavi_icon['dis_eti'] ?? '🩸', 'title' => t_cevir($tedavi_title['dis_eti'] ?? 'Diş Eti Hastalıkları')],
                'gizli_3' => ['key' => 'kanal', 'icon' => $tedavi_icon['kanal'] ?? '🔬', 'title' => t_cevir($tedavi_title['kanal'] ?? 'Kanal Tedavisi')]
            ];
            
            // ============================================================
            // SAĞ GRUP - Cerrahi & İmplantoloji
            // ============================================================
            $sag_grup = [
                'goster_1' => ['key' => 'ortodonti', 'icon' => $tedavi_icon['ortodonti'] ?? '😬', 'title' => t_cevir($tedavi_title['ortodonti'] ?? 'Ortodontik Tedavi')],
                'goster_2' => ['key' => 'cocuk', 'icon' => $tedavi_icon['cocuk'] ?? '👶', 'title' => t_cevir($tedavi_title['cocuk'] ?? 'Çocuk Diş Tedavisi')],
                'gizli_1' => ['key' => 'cene_eklemi', 'icon' => $tedavi_icon['cene_eklemi'] ?? '🦴', 'title' => t_cevir($tedavi_title['cene_eklemi'] ?? 'Çene Eklemi Rahatsızlıkları')],
                'gizli_2' => ['key' => 'beyazlatma', 'icon' => $tedavi_icon['beyazlatma'] ?? '⭐', 'title' => t_cevir($tedavi_title['beyazlatma'] ?? 'Diş Beyazlatma')],
                'gizli_3' => ['key' => 'samsun', 'icon' => $tedavi_icon['samsun'] ?? '📍', 'title' => t_cevir($tedavi_title['samsun'] ?? "Samsun'da Diş Hekimi")]
            ];
            ?>
            
<!-- SOL KOLON - Estetik & Gülüş Tasarımı -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
        <h3 class="text-white font-bold text-xl"><?php echo t_cevir("✨ Estetik & Gülüş Tasarımı, Fonksiyonel Tedavi"); ?></h3>
    </div>
    <div class="p-6">
        <?php 
        // TÜM SOL TEDAVİLER (goster + gizli hepsi)
        $sol_tum = ['goster_1', 'goster_2', 'gizli_1', 'gizli_2', 'gizli_3'];
        foreach($sol_tum as $key): 
            $item = $sol_grup[$key];
            $content = isset($tedaviler_metin[$item['key']]) ? $tedaviler_metin[$item['key']] : '';
        ?>
        <div class="mb-4">
            <h4 class="font-bold text-gray-800 text-lg mb-2"><?php echo $item['icon']; ?> <?php echo $item['title']; ?></h4>
            <p class="text-gray-600 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars(t_cevir($content))); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- SAĞ KOLON - Cerrahi & İmplantoloji -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
    <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4">
        <h3 class="text-white font-bold text-xl"><?php echo t_cevir("💉 Cerrahi & İmplantoloji"); ?></h3>
    </div>
    <div class="p-6">
        <?php 
        // TÜM SAĞ TEDAVİLER (goster + gizli hepsi)
        $sag_tum = ['goster_1', 'goster_2', 'gizli_1', 'gizli_2', 'gizli_3'];
        foreach($sag_tum as $key): 
            $item = $sag_grup[$key];
            $content = isset($tedaviler_metin[$item['key']]) ? $tedaviler_metin[$item['key']] : '';
        ?>
        <div class="mb-4">
            <h4 class="font-bold text-gray-800 text-lg mb-2"><?php echo $item['icon']; ?> <?php echo $item['title']; ?></h4>
            <p class="text-gray-600 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars(t_cevir($content))); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

	   </div>
    </div>
</section>


<section class="bg-white py-16 lg:py-5 overflow-hidden border-t border-slate-50">
    <div class="max-w-6xl mx-auto space-y-16 mb-[-100px]">
        <div class="flex flex-col md:flex-row justify-between items-end gap-6 px-4">
            <div class="space-y-4 pl-2">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-600/10 text-blue-700 font-black text-[10px] tracking-widest uppercase">
                    <?php echo getLucideIcon('Sparkles', 14); ?> <span><?php echo t_cevir("Tedavi Kartları"); ?></span>
                </div>
                <h2 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter leading-tight max-w-xl">
                    <?php echo t_cevir("Gülüşünüze değer katıyor,"); ?> <br/><span class="text-blue-600 italic"><?php echo t_cevir("özgüveninizi artırıyoruz!"); ?></span>
                </h2>
            </div>
            <div class="flex gap-4 pr-2">
                <button id="slideLeftBtn" class="p-4 rounded-full border border-slate-100 bg-white shadow-lg hover:bg-blue-600 hover:text-white transition-all"><?php echo getLucideIcon('ChevronLeft', 24); ?></button>
                <button id="slideRightBtn" class="p-4 rounded-full border border-slate-100 bg-white shadow-lg hover:bg-blue-600 hover:text-white transition-all"><?php echo getLucideIcon('ChevronRight', 24); ?></button>
            </div>
        </div>
        <div class="relative w-full overflow-hidden">
            <div id="treatmentSlider" class="flex gap-10 overflow-x-auto snap-x snap-mandatory scroll-smooth px-4 pb-32 pt-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                <style>#treatmentSlider::-webkit-scrollbar { display: none; }</style>
                <?php foreach($tedavi_kartlari as $item): ?>
                <div class="flex-none w-full pl-5 md:w-[calc(33.333%-1.67rem)] snap-start group flex flex-col space-y-10">
                    <div class="relative aspect-[3/4] rounded-[4.5rem] overflow-hidden shadow-[0_45px_90px_-20px_rgba(0,0,0,0.22)] border-[16px] border-white ring-1 ring-slate-100 group-hover:shadow-blue-200/50 transition-all duration-700">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars(t_cevir($item['title'])); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000">
                    </div>
                    <div class="space-y-4 px-6">
                        <h4 class="text-2xl font-black text-slate-900 tracking-tight group-hover:text-blue-600 transition-colors uppercase leading-tight"><?php echo t_cevir($item['title']); ?></h4>
                        <p class="text-slate-500 text-sm font-medium leading-relaxed line-clamp-3 pl-1"><?php echo t_cevir($item['desc']); ?></p>
                        <a href="<?php echo SITE_PATH; ?>/tedavi-detay.php?slug=<?php echo $item['id']; ?>" class="inline-flex items-center gap-2 text-blue-600 font-black text-[10px] uppercase tracking-[0.25em] border-b-2 border-blue-600/10 hover:border-blue-600 transition-all pb-1 pl-1">
                            <?php echo t_cevir("Detaylı Bilgi"); ?> <?php echo getLucideIcon('ArrowRight', 14); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<script>
// ========== OPTİMİZE EDİLMİŞ TOGGLE ve SLIDER ==========
(function() {
    'use strict';
    
    // ========== TOGGLE FONKSİYONLARI (Tek fonksiyon ile) ==========
    function createToggleHandler(elementId, btnId, isReverse = false) {
        return function() {
            const gizli = document.getElementById(elementId);
            const btn = document.getElementById(btnId);
            
            if (!gizli || !btn) return;
            
            const isHidden = gizli.classList.contains('hidden');
            const icon = `<svg class="w-4 h-4 transition-transform duration-200 ${isHidden ? 'rotate-180' : ''}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="${isHidden ? '18 15 12 9 6 15' : '6 9 12 15 18 9'}"/>
            </svg>`;
            
            if (isHidden) {
                gizli.classList.remove('hidden');
                btn.innerHTML = `Daha Az ${icon}`;
            } else {
                gizli.classList.add('hidden');
                btn.innerHTML = `Daha Fazla ${icon}`;
            }
        };
    }
    
    // Toggle'ları başlat
    window.toggleSol = createToggleHandler('solGizli', 'solBtn');
    window.toggleSag = createToggleHandler('sagGizli', 'sagBtn');
    
    // ========== OPTİMİZE EDİLMİŞ SLIDER ==========
    function initSlider() {
        const slider = document.getElementById('treatmentSlider');
        const leftBtn = document.getElementById('slideLeftBtn');
        const rightBtn = document.getElementById('slideRightBtn');
        
        if (!slider || !leftBtn || !rightBtn) return;
        
        // Debounce için timer
        let isScrolling = false;
        let scrollTimeout = null;
        
        // Scroll miktarını hesapla (responsive)
        function getScrollAmount() {
            // Mobilde daha az kaydır
            if (window.innerWidth < 768) {
                return slider.clientWidth * 0.8;
            }
            return slider.clientWidth;
        }
        
        // Kaydırma işlemi
        function scrollSlider(direction) {
            if (isScrolling) return;
            isScrolling = true;
            
            const amount = getScrollAmount();
            const scrollAmount = direction === 'left' ? -amount : amount;
            
            slider.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
            
            // Scroll bittiğinde flag'i sıfırla
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                isScrolling = false;
            }, 500);
        }
        
        // Event listener'lar
        leftBtn.addEventListener('click', (e) => {
            e.preventDefault();
            scrollSlider('left');
        });
        
        rightBtn.addEventListener('click', (e) => {
            e.preventDefault();
            scrollSlider('right');
        });
        
        // Klavye desteği (opsiyonel)
        slider.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                scrollSlider('left');
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                scrollSlider('right');
            }
        });
        
        // Touch cihazlar için optimizasyon
        let touchStartX = 0;
        let touchEndX = 0;
        
        slider.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        slider.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const swipeDistance = touchEndX - touchStartX;
            
            if (Math.abs(swipeDistance) > 50) { // 50px eşik değeri
                if (swipeDistance > 0) {
                    scrollSlider('left');
                } else {
                    scrollSlider('right');
                }
            }
        });
        
        // Buton görünürlüğünü yönet (scroll durumuna göre)
        function updateButtonsVisibility() {
            const scrollLeft = slider.scrollLeft;
            const maxScrollLeft = slider.scrollWidth - slider.clientWidth;
            
            // Sol buton: Başta değilse göster
            if (scrollLeft <= 10) {
                leftBtn.classList.add('opacity-50', 'cursor-not-allowed');
                leftBtn.disabled = true;
            } else {
                leftBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                leftBtn.disabled = false;
            }
            
            // Sağ buton: Sonda değilse göster
            if (scrollLeft >= maxScrollLeft - 10) {
                rightBtn.classList.add('opacity-50', 'cursor-not-allowed');
                rightBtn.disabled = true;
            } else {
                rightBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                rightBtn.disabled = false;
            }
        }
        
        // Scroll event'inde butonları güncelle
        slider.addEventListener('scroll', () => {
            requestAnimationFrame(updateButtonsVisibility);
        });
        
        // Pencere boyutu değiştiğinde butonları güncelle
        window.addEventListener('resize', () => {
            updateButtonsVisibility();
        });
        
        // İlk yüklemede butonları güncelle
        setTimeout(updateButtonsVisibility, 100);
        
        // Auto-slide (opsiyonel)
        let autoSlideInterval = null;
        let isAutoSliding = false;
        
        function startAutoSlide() {
            if (autoSlideInterval) clearInterval(autoSlideInterval);
            autoSlideInterval = setInterval(() => {
                if (!isAutoSliding && !isScrolling) {
                    const scrollLeft = slider.scrollLeft;
                    const maxScrollLeft = slider.scrollWidth - slider.clientWidth;
                    
                    if (scrollLeft >= maxScrollLeft - 10) {
                        // Sona geldiysek başa dön
                        slider.scrollTo({ left: 0, behavior: 'smooth' });
                    } else {
                        scrollSlider('right');
                    }
                }
            }, 5000);
        }
        
        function stopAutoSlide() {
            if (autoSlideInterval) {
                clearInterval(autoSlideInterval);
                autoSlideInterval = null;
            }
        }
        
        // Mouse slider üzerindeyken auto-slide durdursun
        slider.addEventListener('mouseenter', () => {
            isAutoSliding = true;
            stopAutoSlide();
        });
        
        slider.addEventListener('mouseleave', () => {
            isAutoSliding = false;
            startAutoSlide();
        });
        
        // Mobilde touch ile kaydırma sırasında auto-slide durdursun
        slider.addEventListener('touchstart', () => {
            stopAutoSlide();
        });
        
        slider.addEventListener('touchend', () => {
            setTimeout(() => {
                if (!isAutoSliding) startAutoSlide();
            }, 3000);
        });
        
        // Auto-slide'ı başlat (isteğe bağlı - kapatmak için yorum satırı yapın)
        // startAutoSlide();
    }
    
    // ========== SLIDER BUTON STİLLERİ ==========
    function addSliderStyles() {
        if (!document.querySelector('#slider-styles')) {
            const style = document.createElement('style');
            style.id = 'slider-styles';
            style.textContent = `
                #treatmentSlider {
                    scroll-behavior: smooth;
                    scrollbar-width: thin;
                    -webkit-overflow-scrolling: touch;
                }
                #treatmentSlider::-webkit-scrollbar {
                    height: 4px;
                }
                #treatmentSlider::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 10px;
                }
                #treatmentSlider::-webkit-scrollbar-thumb {
                    background: #888;
                    border-radius: 10px;
                }
                #treatmentSlider::-webkit-scrollbar-thumb:hover {
                    background: #555;
                }
                .slide-btn:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // ========== BAŞLAT ==========
    function init() {
        initSlider();
        addSliderStyles();
    }
    
    // DOM yüklendiğinde başlat
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>


<?php include dirname(__DIR__) . '/inc/footer.php'; ?>