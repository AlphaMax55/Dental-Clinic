<?php
// ============================================================
// 📍 www/site/tedaviler/index.php - TEDAVİLER (LİSTE & FİLTRE)
// ============================================================

require_once dirname(__DIR__) . '/inc/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// URL'den gelen dil değişikliğini yakala
if (isset($_GET['lang'])) {
    $yeni_dil = ($_GET['lang'] === 'en') ? 'en' : 'tr';
    $_SESSION['dil'] = $yeni_dil;
}

$lang = $_SESSION['dil'] ?? 'tr';
$dil_en = ($lang === 'en');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========== TEDAVİLER GENEL SEO AYARLARI ==========
$tedaviler_seo_title_tr = 'Tedaviler | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
$tedaviler_seo_title_en = 'Treatments | Prof. Dr. İbrahim Duran | Dental Clinic Samsun';
$tedaviler_seo_description_tr = 'Prof. Dr. İbrahim Duran, Samsun Atakum\'da Diş Estetiği · Diş Ağrısı · İmplant Tedavisi · Diş Eti Hastalıkları · Kanal Tedavisi · Ortodontik Tedavi · Çocuk Diş Tedavisi · Çene Eklemi Rahatsızlıkları. Tedavi Hizmeti Vermektedir.';
$tedaviler_seo_description_en = 'Prof. Dr. İbrahim Duran offers dental aesthetic, implant treatment, gum diseases, root canal treatment, orthodontic treatment, pediatric dentistry, and jaw joint disorders in Samsun Atakum.';
$tedaviler_seo_keywords_tr = 'Prof. Dr. İbrahim Duran, Samsun diş hekimi, Atakum diş hekimi, Samsun diş kliniği, RivaDent Atakum, Samsun implant tedavisi, Samsun gülüş tasarımı, Samsun zirkonyum kaplama, Samsun lamine diş, Samsun diş beyazlatma, Samsun kanal tedavisi, Samsun ortodonti, Samsun çocuk diş hekimi, Samsun diş eti hastalıkları tedavisi, Samsun çene cerrahisi, Samsun 20 yaş dişi çekimi, Samsun kemik grefti, Samsun sinüs lifting';
$tedaviler_seo_keywords_en = 'Prof. Dr. İbrahim Duran, Samsun dentist, Atakum dentist, Samsun dental clinic, RivaDent Atakum, Samsun implant treatment, Samsun smile design, Samsun zirconium coating, Samsun laminate teeth, Samsun teeth whitening, Samsun root canal treatment, Samsun orthodontics, Samsun pediatric dentist, Samsun gum disease treatment';
$tedaviler_seo_og_image = '/uploads/tedaviler/tedaviler-og.webp';
$tedaviler_seo_canonical = 'adres gir/tedaviler/';

try {
    $tables = $db->query("SHOW TABLES LIKE 'tedaviler_seo_ayarlar'")->fetchAll();
    if (count($tables) > 0) {
        $stmt = $db->query("SELECT anahtar, deger FROM tedaviler_seo_ayarlar");
        while ($row = $stmt->fetch()) {
            if ($row['anahtar'] == 'seo_title_tr') $tedaviler_seo_title_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_title_en') $tedaviler_seo_title_en = $row['deger'];
            if ($row['anahtar'] == 'seo_description_tr') $tedaviler_seo_description_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_description_en') $tedaviler_seo_description_en = $row['deger'];
            if ($row['anahtar'] == 'seo_keywords_tr') $tedaviler_seo_keywords_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_keywords_en') $tedaviler_seo_keywords_en = $row['deger'];
            if ($row['anahtar'] == 'seo_og_image') $tedaviler_seo_og_image = $row['deger'];
            if ($row['anahtar'] == 'seo_canonical') $tedaviler_seo_canonical = $row['deger'];
        }
    }
} catch (Exception $e) {}

if ($lang == 'en') {
    $page_title = $tedaviler_seo_title_en;
    $seo_description = $tedaviler_seo_description_en;
    $site_keywords = $tedaviler_seo_keywords_en;
    $dynamic_og_title = $tedaviler_seo_title_en;
    $og_image = $tedaviler_seo_og_image;
    $mevcut_canonical_link = $tedaviler_seo_canonical . '?lang=en';
} else {
    $page_title = $tedaviler_seo_title_tr;
    $seo_description = $tedaviler_seo_description_tr;
    $site_keywords = $tedaviler_seo_keywords_tr;
    $dynamic_og_title = $tedaviler_seo_title_tr;
    $og_image = $tedaviler_seo_og_image;
    $mevcut_canonical_link = $tedaviler_seo_canonical;
}

$robots_etiketi_icerigi = '<meta name="robots" content="index, follow">';

// ========== PARAMETRELER ==========
$aktif_kategori = isset($_GET['k']) ? trim($_GET['k']) : 'tumu';
if ($aktif_kategori === 'index.php' || empty($aktif_kategori)) {
    $aktif_kategori = 'tumu';
}
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// ========== VERİTABANI VERİLERİ ==========
// Sorgu: tedavi_slider'dan sira=1 olan after_resim'i çek
$stmt = $db->prepare("
    SELECT t.*, ts.after_resim AS slider_resim
    FROM tedaviler t
    LEFT JOIN tedavi_slider ts ON t.id = ts.tedavi_id AND ts.sira = 1
    WHERE t.aktif = 1 AND t.silindi = 0
    ORDER BY t.sira ASC, t.id DESC
");
$stmt->execute();
$ham_tedaviler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tedaviler = [];
foreach($ham_tedaviler as $t) {
    // Resim: önce slider'dan after_resim, yoksa sonrasi_resim
    $resim = !empty($t['slider_resim']) ? $t['slider_resim'] : ($t['sonrasi_resim'] ?? '');

    $baslik_json = json_decode($t['baslik'] ?? '', true);
    if (is_array($baslik_json)) {
        if (!empty($baslik_json[$lang])) {
            $aktif_baslik = $baslik_json[$lang];
        } else {
            $yedek_baslik = $baslik_json['tr'] ?? ($t['baslik'] ?? '');
            $aktif_baslik = ($lang === 'en' && function_exists('t_cevir')) ? t_cevir($yedek_baslik) : $yedek_baslik;
        }
    } else {
        $aktif_baslik = ($lang === 'en' && function_exists('t_cevir')) ? t_cevir($t['baslik'] ?? '') : ($t['baslik'] ?? '');
    }

    $kisa_json = json_decode($t['kisa_aciklama'] ?? '', true);
    if (is_array($kisa_json)) {
        if (!empty($kisa_json[$lang])) {
            $aktif_kisa_aciklama = $kisa_json[$lang];
        } else {
            $yedek_kisa = $kisa_json['tr'] ?? ($t['kisa_aciklama'] ?? '');
            $aktif_kisa_aciklama = ($lang === 'en' && function_exists('t_cevir')) ? t_cevir($yedek_kisa) : $yedek_kisa;
        }
    } else {
        $aktif_kisa_aciklama = ($lang === 'en' && function_exists('t_cevir')) ? t_cevir($t['kisa_aciklama'] ?? '') : ($t['kisa_aciklama'] ?? '');
    }

    $tedaviler[] = [
        'id' => $t['id'],
        'slug' => $t['slug'],
        'sonrasi_resim' => $resim,
        'kategori_ham' => trim($t['kategori'] ?? ''),
        'baslik' => $aktif_baslik,
        'kisa_aciklama' => $aktif_kisa_aciklama,
        'icerik' => $t['icerik'] ?? $aktif_kisa_aciklama,
        'goruntulenme' => $t['goruntulenme'] ?? 1250,
        'created_at' => $t['created_at'] ?? date('Y-m-d H:i:s')
    ];
}

// Kategoriler
$kategoriListesi = [
    'Estetik Diş Hekimliği' => ['ikon' => 'Sparkles', 'tr' => 'Estetik Diş Hekimliği', 'en' => 'Cosmetic Dentistry'],
    'Cerrahi & İmplantoloji' => ['ikon' => 'Syringe', 'tr' => 'Cerrahi & İmplantoloji', 'en' => 'Surgery & Implantology'],
    'Protetik Diş Tedavisi' => ['ikon' => 'Shield', 'tr' => 'Protetik Diş Tedavisi', 'en' => 'Prosthetic Dentistry'],
    'Ortodonti & Çene' => ['ikon' => 'Smile', 'tr' => 'Ortodonti & Çene', 'en' => 'Orthodontics & Jaw']
];

$kategoriSayilari = ['tumu' => count($tedaviler)];
foreach ($kategoriListesi as $katKey => $val) {
    $kategoriSayilari[$katKey] = 0;
}

foreach ($tedaviler as $t) {
    $kat = $t['kategori_ham'];
    if (isset($kategoriSayilari[$kat])) {
        $kategoriSayilari[$kat]++;
    }
}

// Aktif kategori kontrolü
$aktif_kategori_adi = 'tumu';
if ($aktif_kategori !== 'tumu') {
    foreach ($kategoriListesi as $kKey => $kVal) {
        if (mb_strtolower($kKey, 'UTF-8') === mb_strtolower($aktif_kategori, 'UTF-8') || mb_strtolower($kVal['tr'], 'UTF-8') === mb_strtolower($aktif_kategori, 'UTF-8') || mb_strtolower($kVal['en'], 'UTF-8') === mb_strtolower($aktif_kategori, 'UTF-8')) {
            $aktif_kategori_adi = $kKey;
            break;
        }
    }
}

// Filtreleme
$filtrelenmis_tedaviler = [];
foreach ($tedaviler as $tedavi) {
    $kategori_uygun = ($aktif_kategori_adi === 'tumu') || (mb_strtolower($tedavi['kategori_ham'], 'UTF-8') === mb_strtolower($aktif_kategori_adi, 'UTF-8'));
    $arama_uygun = empty($search_query) || 
                   stripos($tedavi['baslik'], $search_query) !== false || 
                   stripos($tedavi['kisa_aciklama'], $search_query) !== false;

    if ($kategori_uygun && $arama_uygun) {
        $filtrelenmis_tedaviler[] = $tedavi;
    }
}

$total_posts = count($filtrelenmis_tedaviler);

// Sayfalama
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
if ($sayfa < 1) $sayfa = 1;
$limit = 6;
$toplam_sayfa = ceil($total_posts / $limit);
$baslangic = ($sayfa - 1) * $limit;
$gosterilecek_tedaviler = array_slice($filtrelenmis_tedaviler, $baslangic, $limit);

// Popüler Tedaviler
$populerTedaviler = array_slice($tedaviler, 0, 4);

// SSS Listesi
$sss_list = [];
try {
    $sss_sorgu = $db->query("SELECT * FROM blog_sss WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC");
    if ($sss_sorgu) {
        $sss_list = $sss_sorgu->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(Exception $e) {}

// CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sütun Render Fonksiyonu
function renderTedaviSutun($tedaviler_dizisi, $lang = 'tr') {
    if (empty($tedaviler_dizisi)) return '';
    ob_start();
    foreach ($tedaviler_dizisi as $tedavi): 
        $detay_url = '/tedaviler/' . htmlspecialchars($tedavi['slug']);
    ?>
    <article class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col group mb-8">
        <div class="relative h-64 md:h-72 overflow-hidden bg-slate-100 shrink-0">
            <?php if(!empty($tedavi['sonrasi_resim'])): ?>
                <img src="<?php echo htmlspecialchars($tedavi['sonrasi_resim']); ?>" alt="<?php echo htmlspecialchars($tedavi['baslik']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            <?php else: ?>
                <div class="w-full h-full bg-gradient-to-tr from-slate-900 to-blue-900 flex items-center justify-center text-white/30">
                    <svg class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </div>
            <?php endif; ?>
            
            <div style="position: absolute; top: 14px; left: 14px; z-index: 20; max-width: calc(100% - 28px); line-height: 1;">
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #ffffff; color: #1d4ed8; font-size: 11px; font-weight: 800; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.25); text-transform: uppercase; letter-spacing: 0.03em; border: 1px solid rgba(226, 232, 240, 0.9); line-height: 1.2;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #2563eb; flex-shrink: 0; display: inline-block;"></span>
                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block;"><?php echo htmlspecialchars($tedavi['kategori_ham']); ?></span>
                </span>
            </div>

            <div class="absolute bottom-4 right-4">
                <span class="px-3 py-1 bg-slate-900/80 backdrop-blur-md text-white text-xs font-semibold rounded-lg flex items-center gap-1.5 shadow-md">
                    <svg class="w-3.5 h-3.5 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                    %98 <?php echo ($lang === 'en') ? 'Satisfaction' : 'Memnuniyet'; ?>
                </span>
            </div>
        </div>

        <div class="p-7 md:p-8 flex-1 flex flex-col justify-between space-y-6">
            <div>
                <div class="flex items-center gap-3 text-xs md:text-sm font-bold text-slate-400 mb-3">
                    <span class="flex items-center gap-1.5 text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-lg">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?php echo ($lang === 'en') ? 'Personalized Treatment' : 'Kişiye Özel Tedavi'; ?>
                    </span>
                </div>

                <h3 class="text-xl md:text-2xl font-extrabold text-slate-900 group-hover:text-blue-600 transition-colors mb-3 line-clamp-2 leading-snug">
                    <a href="<?php echo $detay_url; ?>">
                        <?php echo htmlspecialchars($tedavi['baslik']); ?>
                    </a>
                </h3>

                <p class="text-slate-600 text-sm md:text-base line-clamp-3 leading-relaxed">
                    <?php echo htmlspecialchars($tedavi['kisa_aciklama']); ?>
                </p>
            </div>

            <div class="pt-6 border-t border-slate-100 flex flex-nowrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 border border-blue-200 text-blue-700 font-black flex items-center justify-center text-sm shadow-sm shrink-0">
                        P
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-extrabold text-slate-900 truncate">Prof. Dr. İbrahim Duran</h4>
                        <p class="text-xs text-slate-400 truncate font-medium"><?php echo ($lang === 'en') ? 'Dental Specialist' : 'Diş Hekimi & Uzman'; ?></p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="toggleKartDetay(this)" 
                            class="pl-8 pr-8 kart-detay-btn inline-flex items-center gap-1.5 px-4 py-2.5 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-600 font-bold text-xs md:text-sm rounded-xl transition-all whitespace-nowrap cursor-pointer">
                        <span><?php echo ($lang === 'en') ? 'Summary' : 'Özet'; ?></span>
                        <svg class="kart-ok w-3.5 h-3.5 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <a href="<?php echo $detay_url; ?>" 
                       class="pl-8 pr-8 inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs md:text-sm rounded-xl transition-all shadow-md shadow-blue-500/25 whitespace-nowrap">
                        <span><?php echo ($lang === 'en') ? 'Examine' : 'İncele'; ?></span>
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="kart-detay-icerik hidden border-t border-slate-100 bg-slate-50/75 p-6 md:p-8 space-y-6">
            <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center gap-2 text-blue-700 font-bold text-xs uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <?php echo ($lang === 'en') ? 'Treatment Information' : 'Tedavi ve Klinik Özeti'; ?>
                </div>
                <div class="text-slate-700 leading-relaxed text-sm md:text-base whitespace-pre-line">
                    <?php echo htmlspecialchars(strip_tags($tedavi['kisa_aciklama'])); ?>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                <span class="text-xs text-slate-400 font-medium"><?php echo ($lang === 'en') ? 'Contact us for a detailed consultation.' : 'Kişiye özel tedavi planı için hemen İletişime Geçın.'; ?></span>
                <a href="/iletisim/" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs md:text-sm rounded-xl shadow-md transition-all">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span><?php echo ($lang === 'en') ? 'Book Appointment' : 'İletişime Geç'; ?></span>
                </a>
            </div>
        </div>
    </article>
    <?php endforeach;
    return ob_get_clean();
}

$tedavi_sol_sutun = [];
$tedavi_sag_sutun = [];
foreach($gosterilecek_tedaviler as $index => $post_item) {
    if($index % 2 == 0) {
        $tedavi_sol_sutun[] = $post_item;
    } else {
        $tedavi_sag_sutun[] = $post_item;
    }
}

// ========== AJAX KONTROLÜ ==========
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

if ($is_ajax) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
        <div>
            <h2 class="text-2xl md:text-3xl font-black text-slate-900" id="baslikAlan">
                <?php echo ($aktif_kategori_adi == 'tumu') ? (($lang === 'en') ? '🦷 All Treatments' : '🦷 Tüm Tedaviler') : '📂 ' . htmlspecialchars($aktif_kategori_adi); ?>
                <span class="text-blue-600 text-sm ml-2 font-bold">(<?php echo $total_posts; ?> <?php echo ($lang === 'en') ? 'treatments' : 'tedavi'; ?>)</span>
            </h2>
            <p class="text-xs md:text-sm text-slate-500 mt-1"><?php echo ($lang === 'en') ? 'Treatments matching your criteria' : 'Seçilen kriterlere göre sunulan klinikler ve tedaviler'; ?></p>
        </div>
        <?php if($aktif_kategori_adi != 'tumu'): ?>
        <a href="javascript:void(0)" data-kategori="tumu" class="kategori-btn inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-3.5 py-2 rounded-xl transition-colors">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            <?php echo ($lang === 'en') ? 'Show All' : 'Tümünü Göster'; ?>
        </a>
        <?php endif; ?>
    </div>

    <div id="yazilarAlan" class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
        <?php if (count($gosterilecek_tedaviler) > 0): ?>
            <div class="flex flex-col">
                <?php echo renderTedaviSutun($tedavi_sol_sutun, $lang); ?>
            </div>
            <div class="flex flex-col">
                <?php echo renderTedaviSutun($tedavi_sag_sutun, $lang); ?>
            </div>
        <?php else: ?>
            <div class="col-span-full py-20 text-center bg-white border-2 border-dashed border-slate-200 rounded-3xl">
                <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-blue-500">
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1"><?php echo ($lang === 'en') ? 'No Treatments Found' : 'Eşleşen Tedavi Bulunamadı'; ?></h3>
                <p class="text-sm text-slate-500"><?php echo ($lang === 'en') ? 'You can search with different keywords or reset the filter.' : 'Farklı anahtar kelimelerle arama yapabilir veya filtreyi sıfırlayabilirsiniz.'; ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div id="sayfalamaAlan">
        <?php if($toplam_sayfa > 1): ?>
        <div class="pt-8 flex justify-center">
            <nav class="inline-flex items-center gap-2 bg-white p-2 rounded-2xl border border-slate-200 shadow-sm">
                <?php if($sayfa > 1): ?>
                <a href="javascript:void(0)" data-sayfa="<?php echo $sayfa - 1; ?>" class="sayfalama-btn p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition-all">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
                <?php endif; ?>

                <?php for($i = max(1, $sayfa - 2); $i <= min($toplam_sayfa, $sayfa + 2); $i++): ?>
                <a href="javascript:void(0)" data-sayfa="<?php echo $i; ?>" class="sayfalama-btn w-10 h-10 flex items-center justify-center rounded-xl text-sm font-bold transition-all <?php echo $i == $sayfa ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-600 hover:bg-slate-100'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <?php if($sayfa < $toplam_sayfa): ?>
                <a href="javascript:void(0)" data-sayfa="<?php echo $sayfa + 1; ?>" class="sayfalama-btn p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition-all">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    <?php
    exit;
}

$page_slug = 'tedaviler';

/* ==================================================================
 * 🎯 SAYFAYA ÖZEL SCHEMA'LAR (Tedaviler Ana Sayfa)
 * ==================================================================
 * Bu bir "koleksiyon" sayfasıdır - 4 schema eklenir:
 * 1. CollectionPage (sayfa kimliği)
 * 2. ItemList (tedavi listesi - her biri Service)
 * 3. FAQPage (SSS bölümü)
 * 4. BreadcrumbList (navigasyon)
 * ================================================================== */
$tum_schemalar = [];
$site_adresi = 'adres gir';

// ---------- 1) CollectionPage (Sayfa kimliği) ----------
$tum_schemalar[] = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    '@id' => $site_adresi . '/tedaviler/#collectionpage',
    'url' => $site_adresi . '/tedaviler/',
    'name' => $page_title,
    'description' => $seo_description,
    'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
    'isPartOf' => [
        '@id' => $site_adresi . '/#website'
    ],
    'about' => [
        '@id' => $site_adresi . '/#medicalbusiness'
    ],
    'publisher' => [
        '@id' => $site_adresi . '/#medicalbusiness'
    ]
];

// ---------- 2) ItemList (Tedaviler listesi - her biri Service) ----------
if (!empty($tedaviler)) {
    $item_list = [];
    foreach ($tedaviler as $idx => $tedavi) {
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
        
        // Resim URL'sini tam yola çevir
        $tedavi_resim = $tedavi['sonrasi_resim'] ?? '';
        if (!empty($tedavi_resim) && strpos($tedavi_resim, 'http') !== 0) {
            $tedavi_resim = $site_adresi . '/' . ltrim($tedavi_resim, '/');
        }
        
        $service_item = [
            '@type' => 'Service',
            '@id' => $tedavi_url . '#service',
            'name' => $tedavi['baslik'],
            'description' => mb_substr(strip_tags($tedavi['kisa_aciklama']), 0, 300),
            'url' => $tedavi_url,
            'serviceType' => $tedavi['kategori_ham'],
            'medicalSpecialty' => $medical_specialty,
            'provider' => [
                '@id' => $site_adresi . '/#medicalbusiness'
            ],
            'areaServed' => [
                '@type' => 'City',
                'name' => 'Samsun'
            ]
        ];
        
        // Resim varsa ImageObject olarak ekle
        if (!empty($tedavi_resim)) {
            $service_item['image'] = [
                '@type' => 'ImageObject',
                'url' => $tedavi_resim,
                'width' => 800,
                'height' => 600
            ];
        }
        
        $item_list[] = [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'item' => $service_item
        ];
    }
    
    $tum_schemalar[] = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $dil_en ? 'Dental Treatments' : 'Diş Tedavileri',
        'numberOfItems' => count($item_list),
        'itemListElement' => $item_list
    ];
}
// ---------- 3) FAQPage (SSS bölümü) ----------
if (!empty($sss_list)) {
    $faq_items = [];
    $gorulen = [];
    foreach ($sss_list as $s) {
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
        ]
    ]
];

// ---------- Schema'ları değişkende topla (Pretty Print ile) ----------
$sayfa_schemalari = '';
foreach ($tum_schemalar as $schema) {
    $sayfa_schemalari .= '<script type="application/ld+json">' . "\n"
                       . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                       . "\n" . '</script>' . "\n";
}

include dirname(__DIR__) . '/inc/header.php';
?>

<style>
.kategori-btn:hover { background-color: inherit !important; color: inherit !important; border-color: inherit !important; }
.kategori-btn.bg-blue-600:hover { background-color: #2563eb !important; color: white !important; }
.kategori-btn.bg-slate-50:hover { background-color: #f1f5f9 !important; color: #1e293b !important; border-color: #cbd5e1 !important; }
.kategori-btn { transition: all 0.2s ease-in-out !important; }
</style>

<main class="min-h-screen bg-white w-full font-sans selection:bg-cyan-100 selection:text-cyan-900">

<!-- Tedaviler Header & Arama Bölümü -->
<section style="background: linear-gradient(180deg, #020617 0%, #0f172a 100%); color: #ffffff; padding: 30px 20px 30px 20px; position: relative; overflow: visible; border-bottom: 1px solid #1e293b;">
    <div style="max-width: 850px; margin: 0 auto; text-align: center; position: relative; z-index: 10;">
        
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 16px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 9999px; color: #60a5fa; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 14px;">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: #38bdf8;"></span>
            <?php echo ($lang === 'en') ? 'Clinical Treatments & Solutions' : 'Klinik Tedaviler & Çözüm Merkezi'; ?>
        </div>
        
        <h1 style="font-size: clamp(26px, 3.5vw, 38px); font-weight: 900; color: #ffffff; margin: 0 0 10px 0; line-height: 1.2;">
            <?php if($lang === 'en'): ?>
                Dental Treatment & <span style="color: #38bdf8;">Aesthetics Guide</span>
            <?php else: ?>
                Ağız ve Diş Sağlığı <span style="color: #38bdf8;">Tedavileri</span>
            <?php endif; ?>
        </h1>
        
        <p style="color: #94a3b8; font-size: 15px; max-width: 520px; margin: 0 auto 24px auto; line-height: 1.5;">
            <?php echo ($lang === 'en') ? 'Search and explore treatments, expert methodologies, and dental solutions below.' : 'İmplant, gülüş tasarımı, zirkonyum ve uzmanlık gerektiren tüm tedavilerimizi arayabilirsiniz.'; ?>
        </p>

        <div style="position: relative; max-width: 620px; margin: 0 auto; text-align: left;">
            <div style="display: flex; align-items: center; background: #1e293b; border: 2px solid #334155; border-radius: 15px; padding: 7px 14px; box-shadow: 0 12px 35px -5px rgba(0,0,0,0.5);">
                
                <div style="color: #38bdf8; display: flex; align-items: center; padding-left: 4px; padding-right: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                
                <input type="text" id="tedaviSearchInput" placeholder="<?php echo ($lang === 'en') ? 'Search treatment, method or topic...' : 'Tedavi, yöntem veya konu arayın...'; ?>" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 100%; background: transparent; border: none; outline: none; color: #ffffff; font-size: 16px; font-weight: 500; padding: 7px 0;" autocomplete="off">
                
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div id="aramaSpinner" class="hidden" style="width: 18px; height: 18px; border: 2px solid #38bdf8; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    
                    <button id="aramaTemizle" class="<?php echo empty($search_query) ? 'hidden' : ''; ?>" type="button" style="width: 26px; height: 26px; border-radius: 7px; background: #334155; border: none; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                    
                    <span id="aramaSonucSayaci" style="background: #2563eb; color: #ffffff; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 7px;"><?php echo $total_posts; ?></span>
                </div>
            </div>

            <div style="margin-top: 12px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; justify-content: center;">
                <span style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;"><?php echo ($lang === 'en') ? 'Suggested:' : 'Önerilenler:'; ?></span>
                <button type="button" data-arama="implant" class="populer-arama" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; cursor: pointer;">🦷 <?php echo ($lang === 'en') ? 'Implant' : 'İmplant'; ?></button>
                <button type="button" data-arama="zirkonyum" class="populer-arama" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; cursor: pointer;">✨ <?php echo ($lang === 'en') ? 'Zirconium' : 'Zirkonyum'; ?></button>
                <button type="button" data-arama="gülüş tasarımı" class="populer-arama" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; cursor: pointer;">😁 <?php echo ($lang === 'en') ? 'Smile Design' : 'Gülüş Tasarımı'; ?></button>
                <button type="button" data-arama="lamine" class="populer-arama" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; font-weight: 600; cursor: pointer;">💎 <?php echo ($lang === 'en') ? 'Laminate' : 'Lamine'; ?></button>
            </div>
        </div>

    </div>
</section>
<div class="w-full mx-auto py-10" style="padding-left: clamp(20px, 4vw, 50px); padding-right: clamp(20px, 4vw, 50px); max-width: 1750px;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- SOL SIDEBAR: KATEGORİLER & POPÜLER & BÜLTEN -->
        <aside class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-xl border border-slate-200/80">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        <?php echo ($lang === 'en') ? 'CATEGORIES' : 'KATEGORİLER'; ?>
                    </h3>
                    <span class="text-[11px] font-bold text-slate-400 bg-slate-100 px-2.5 py-0.5 rounded-md">
                        <?php echo count($kategoriListesi) + 1; ?>
                    </span>
                </div>

                <div class="space-y-2">
                    <?php
                    $tumu_count = count($tedaviler);
                    $is_active_tumu = ($aktif_kategori_adi === 'tumu');
                    ?>
                    <a href="javascript:void(0)" data-kategori="tumu" class="kategori-btn w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border <?php echo $is_active_tumu ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/25 ring-2 ring-blue-600/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'; ?>">
                        <span class="flex items-center gap-2.5 min-w-0">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0 <?php echo $is_active_tumu ? 'text-white' : 'text-blue-600'; ?>">
                                <?php echo getTedaviIcon('FileText', 15); ?>
                            </span>
                            <span class="truncate"><?php echo ($lang === 'en') ? 'ALL TREATMENTS' : 'TÜM TEDAVİLER'; ?></span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 <?php echo $is_active_tumu ? 'bg-white/20 text-white' : 'bg-white text-slate-700 border border-slate-200'; ?>">
                            <?php echo $tumu_count; ?>
                        </span>
                    </a>

                    <?php foreach($kategoriListesi as $katKey => $katItem): 
                        $kat_adi_goster = ($lang === 'en') ? $katItem['en'] : $katItem['tr'];
                        $is_active = (mb_strtolower($aktif_kategori_adi, 'UTF-8') === mb_strtolower($katKey, 'UTF-8'));
                        $kat_sayac = $kategoriSayilari[$katKey] ?? 0;
                    ?>
                    <a href="javascript:void(0)" data-kategori="<?php echo htmlspecialchars($katKey); ?>" class="kategori-btn w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border <?php echo $is_active ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/25 ring-2 ring-blue-600/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'; ?>">
                        <span class="flex items-center gap-2.5 min-w-0">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0 <?php echo $is_active ? 'text-white' : 'text-blue-600'; ?>">
                                <?php echo getTedaviIcon($katItem['ikon'], 15); ?>
                            </span>
                            <span class="truncate"><?php echo mb_strtoupper($kat_adi_goster, 'UTF-8'); ?></span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 <?php echo $is_active ? 'bg-white/20 text-white' : 'bg-white text-slate-700 border border-slate-200'; ?>">
                            <?php echo $kat_sayac; ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- POPÜLER TEDAVİLER -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-xl border border-slate-200/80">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue-600"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                    <?php echo ($lang === 'en') ? 'POPULAR TREATMENTS' : 'POPÜLER TEDAVİLER'; ?>
                </h3>
                <div class="space-y-3">
                    <?php foreach($populerTedaviler as $index => $tedavi): ?>
                    <div class="flex items-center gap-3 cursor-pointer group p-2.5 rounded-2xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-md shrink-0">
                            <?php echo $index+1; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 group-hover:text-blue-600 line-clamp-2 transition-colors leading-snug">
                                <a href="/tedaviler/<?php echo htmlspecialchars($tedavi['slug']); ?>"><?php echo htmlspecialchars($tedavi['baslik']); ?></a>
                            </h4>
                            <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-1">
                                <span class="flex items-center gap-1">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <?php echo number_format($tedavi['goruntulenme']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- BÜLTEN & İLETİŞİM -->
            <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-blue-800 rounded-3xl p-5 sm:p-6 shadow-xl text-white relative overflow-hidden">
                <div class="w-10 h-10 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <h3 class="font-extrabold text-base mb-1"><?php echo ($lang === 'en') ? 'Stay Informed' : 'Tedavi Rehberi & Bülten'; ?></h3>
                <p class="text-blue-100 text-xs mb-4 leading-relaxed"><?php echo ($lang === 'en') ? 'Get expert dental health tips directly to your email.' : 'En yeni tedavi yöntemleri ve sağlık tavsiyeleri e-postanıza gelsin.'; ?></p>
                <form action="/api/blog-abone.php" method="POST" class="space-y-2.5">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="email" name="email" required placeholder="<?php echo ($lang === 'en') ? 'Your email address' : 'E-posta adresiniz'; ?>" class="w-full px-3.5 py-2.5 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder:text-white/60 text-xs focus:outline-none focus:ring-2 focus:ring-white/40 transition-all">
                    <button type="submit" class="w-full py-2.5 bg-white text-blue-700 font-bold text-xs rounded-xl hover:bg-blue-50 transition-colors shadow-md"><?php echo ($lang === 'en') ? 'Subscribe' : 'Abone Ol'; ?></button>
                </form>
            </div>
        </aside>

        <!-- SAĞ İÇERİK ALANI -->
        <section class="lg:col-span-9 space-y-8">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                <div>
                    <h2 class="text-2xl md:text-3xl font-black text-slate-900" id="baslikAlan">
                        <?php echo ($aktif_kategori_adi == 'tumu') ? (($lang === 'en') ? '🦷 All Treatments' : '🦷 Tüm Tedaviler') : '📂 ' . htmlspecialchars($aktif_kategori_adi); ?>
                        <span class="text-blue-600 text-sm ml-2 font-bold">(<?php echo $total_posts; ?> <?php echo ($lang === 'en') ? 'treatments' : 'tedavi'; ?>)</span>
                    </h2>
                    <p class="text-xs md:text-sm text-slate-500 mt-1"><?php echo ($lang === 'en') ? 'Treatments matching your criteria' : 'Seçilen kriterlere göre sunulan klinikler ve tedaviler'; ?></p>
                </div>
                <?php if($aktif_kategori_adi != 'tumu'): ?>
                <a href="javascript:void(0)" data-kategori="tumu" class="kategori-btn inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-3.5 py-2 rounded-xl transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                    <?php echo ($lang === 'en') ? 'Show All' : 'Tümünü Göster'; ?>
                </a>
                <?php endif; ?>
            </div>

            <div id="yazilarAlan" class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                <?php if(count($gosterilecek_tedaviler) > 0): ?>
                    <div class="flex flex-col">
                        <?php echo renderTedaviSutun($tedavi_sol_sutun, $lang); ?>
                    </div>
                    <div class="flex flex-col">
                        <?php echo renderTedaviSutun($tedavi_sag_sutun, $lang); ?>
                    </div>
                <?php else: ?>
                    <div class="col-span-full py-20 text-center bg-white border-2 border-dashed border-slate-200 rounded-3xl">
                        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-blue-500">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-1"><?php echo ($lang === 'en') ? 'No Treatments Found' : 'Eşleşen Tedavi Bulunamadı'; ?></h3>
                        <p class="text-sm text-slate-500"><?php echo ($lang === 'en') ? 'You can search with different keywords or reset the filter.' : 'Farklı anahtar kelimelerle arama yapabilir veya filtreyi sıfırlayabilirsiniz.'; ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="sayfalamaAlan">
                <?php if($toplam_sayfa > 1): ?>
                <div class="pt-8 flex justify-center">
                    <nav class="inline-flex items-center gap-2 bg-white p-2 rounded-2xl border border-slate-200 shadow-sm">
                        <?php if($sayfa > 1): ?>
                        <a href="javascript:void(0)" data-sayfa="<?php echo $sayfa - 1; ?>" class="sayfalama-btn p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition-all">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php for($i = max(1, $sayfa - 2); $i <= min($toplam_sayfa, $sayfa + 2); $i++): ?>
                        <a href="javascript:void(0)" data-sayfa="<?php echo $i; ?>" class="sayfalama-btn w-10 h-10 flex items-center justify-center rounded-xl text-sm font-bold transition-all <?php echo $i == $sayfa ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-600 hover:bg-slate-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if($sayfa < $toplam_sayfa): ?>
                        <a href="javascript:void(0)" data-sayfa="<?php echo $sayfa + 1; ?>" class="sayfalama-btn p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition-all">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>

<!-- SIK SORULAN SORULAR -->
<?php if(!empty($sss_list)): ?>
<section class="bg-gradient-to-b from-slate-50 via-blue-50/30 to-slate-100/70 py-20 border-t border-slate-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-100/80 border border-blue-200 rounded-full text-blue-700 text-xs font-black uppercase tracking-wider mb-3 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                <?php echo ($lang === 'en') ? 'TREATMENT GUIDE' : 'TEDAVİ REHBERİ'; ?>
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight"><?php echo ($lang === 'en') ? 'Frequently Asked Questions' : 'Merak Edilenler'; ?></h2>
            <p class="text-slate-500 text-sm md:text-base mt-2 max-w-xl mx-auto"><?php echo ($lang === 'en') ? 'Common answers about treatment procedures and specialist advice' : 'Tedavi süreçleri, hekim tavsiyeleri ve uygulamalar hakkında sıkça sorulan yanıtlar'; ?></p>
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
                            <?php echo htmlspecialchars($sss['soru']); ?>
                        </span>
                        <span class="faq-ikon-kutu w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 transition-all shrink-0">
                            <svg class="faq-ok w-4 h-4 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    
                    <div class="faq-answer hidden px-5 md:px-6 pb-6 pt-0">
                        <div class="p-5 bg-gradient-to-br from-blue-50/90 via-indigo-50/50 to-white rounded-2xl border border-blue-100 border-l-4 border-l-blue-600 text-slate-700 text-sm md:text-base leading-relaxed tracking-normal shadow-xs">
                            <?php echo nl2br(htmlspecialchars($sss['cevap'])); ?>
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
                            <?php echo htmlspecialchars($sss['soru']); ?>
                        </span>
                        <span class="faq-ikon-kutu w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 transition-all shrink-0">
                            <svg class="faq-ok w-4 h-4 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    
                    <div class="faq-answer hidden px-5 md:px-6 pb-6 pt-0">
                        <div class="p-5 bg-gradient-to-br from-blue-50/90 via-indigo-50/50 to-white rounded-2xl border border-blue-100 border-l-4 border-l-blue-600 text-slate-700 text-sm md:text-base leading-relaxed tracking-normal shadow-xs">
                            <?php echo nl2br(htmlspecialchars($sss['cevap'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>
<?php endif; ?>

<script>
function toggleKartDetay(btn) {
    const article = btn.closest('article');
    if(!article) return;

    const icerik = article.querySelector('.kart-detay-icerik');
    const ok = btn.querySelector('.kart-ok');
    const yazi = btn.querySelector('span');

    if(icerik) {
        const isHidden = icerik.classList.contains('hidden');
        if(isHidden) {
            icerik.classList.remove('hidden');
            if(ok) ok.style.transform = 'rotate(180deg)';
            if(yazi) yazi.textContent = '<?php echo ($lang === "en") ? "Collapse" : "Daralt"; ?>';
            btn.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
            btn.classList.remove('bg-slate-100', 'text-slate-700', 'hover:bg-blue-50');
            article.classList.add('ring-2', 'ring-blue-500/30', 'shadow-xl');
        } else {
            icerik.classList.add('hidden');
            if(ok) ok.style.transform = 'rotate(0deg)';
            if(yazi) yazi.textContent = '<?php echo ($lang === "en") ? "Summary" : "Özet"; ?>';
            btn.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
            btn.classList.add('bg-slate-100', 'text-slate-700', 'hover:bg-blue-50');
            article.classList.remove('ring-2', 'ring-blue-500/30', 'shadow-xl');
        }
    }
}

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

(function() {
    'use strict';

    const searchInput = document.getElementById('tedaviSearchInput');
    const sonucSayaci = document.getElementById('aramaSonucSayaci');
    const spinner = document.getElementById('aramaSpinner');
    const temizleBtn = document.getElementById('aramaTemizle');
    let searchTimeout;

    let seciliKategori = '<?php echo $aktif_kategori_adi ?: "tumu"; ?>';

    function guncelleSidebarUI(kategori) {
        seciliKategori = kategori;
        document.querySelectorAll('.kategori-btn').forEach(b => {
            const btnKat = b.getAttribute('data-kategori');
            if(!btnKat) return;

            const isActive = (btnKat === kategori);
            b.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-md', 'shadow-blue-500/25', 'ring-2', 'ring-blue-600/20', 'bg-slate-50', 'text-slate-700', 'border-slate-200');

            if(isActive) {
                b.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-md', 'shadow-blue-500/25', 'ring-2', 'ring-blue-600/20');
            } else {
                b.classList.add('bg-slate-50', 'text-slate-700', 'border-slate-200');
            }

            const iconSpan = b.querySelector('.min-w-0 > span:first-child');
            if(iconSpan) {
                iconSpan.classList.toggle('text-white', isActive);
                iconSpan.classList.toggle('text-blue-600', !isActive);
            }

            const countSpan = b.querySelector(':scope > span:last-child');
            if(countSpan) {
                countSpan.classList.toggle('bg-white/20', isActive);
                countSpan.classList.toggle('text-white', isActive);
                countSpan.classList.toggle('bg-white', !isActive);
                countSpan.classList.toggle('text-slate-700', !isActive);
            }
        });
    }

    function veriYukle(kategori, arama, sayfa = 1) {
        if(spinner) spinner.classList.remove('hidden');
        if(sonucSayaci) {
            sonucSayaci.textContent = '...';
            sonucSayaci.classList.add('animate-pulse');
        }

        const params = new URLSearchParams();
        if(kategori && kategori !== 'tumu') params.set('k', kategori);
        if(arama) params.set('q', arama);
        if(sayfa > 1) params.set('sayfa', sayfa);

        const newUrl = '/tedaviler/' + (params.toString() ? '?' + params.toString() : '');
        window.history.pushState({k: kategori, q: arama, sayfa: sayfa}, '', newUrl);

        const ajaxUrl = '/tedaviler/?ajax=1&' + params.toString();

        fetch(ajaxUrl)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const yazilarAlan = doc.querySelector('#yazilarAlan');
                const baslikAlan = doc.querySelector('#baslikAlan');
                const sayfalamaAlan = doc.querySelector('#sayfalamaAlan');

                const currentYazilar = document.getElementById('yazilarAlan');
                const currentBaslik = document.getElementById('baslikAlan');
                const currentSayfalama = document.getElementById('sayfalamaAlan');

                if(currentYazilar && yazilarAlan) currentYazilar.innerHTML = yazilarAlan.innerHTML;
                if(currentBaslik && baslikAlan) currentBaslik.innerHTML = baslikAlan.innerHTML;
                if(currentSayfalama && sayfalamaAlan) currentSayfalama.innerHTML = sayfalamaAlan.innerHTML;

                const sonucYazisi = currentBaslik ? currentBaslik.innerText : '';
                const sayi = sonucYazisi.match(/\d+/);
                if(sonucSayaci) {
                    sonucSayaci.textContent = sayi ? sayi[0] : '0';
                    sonucSayaci.classList.remove('animate-pulse');
                }

                if(sayfa > 1) {
                    window.scrollTo({ top: currentBaslik ? currentBaslik.offsetTop - 120 : 300, behavior: 'smooth' });
                }
            })
            .catch(() => {
                if(sonucSayaci) {
                    sonucSayaci.textContent = '!';
                    sonucSayaci.classList.remove('animate-pulse');
                }
            })
            .finally(() => {
                if(spinner) spinner.classList.add('hidden');
            });
    }

    if(searchInput) {
        if(temizleBtn) {
            temizleBtn.classList.toggle('hidden', searchInput.value.length === 0);
        }

        searchInput.addEventListener('input', function() {
            if(searchTimeout) clearTimeout(searchTimeout);
            if(temizleBtn) temizleBtn.classList.toggle('hidden', this.value.length === 0);
            
            searchTimeout = setTimeout(() => veriYukle(seciliKategori, this.value.trim(), 1), 300);
        });

        searchInput.addEventListener('keydown', function(e) {
            if(e.key === 'Enter') {
                e.preventDefault();
                veriYukle(seciliKategori, searchInput.value.trim(), 1);
            }
        });
    }

    if(temizleBtn) {
        temizleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if(searchInput) {
                searchInput.value = '';
                searchInput.focus();
                this.classList.add('hidden');
                veriYukle(seciliKategori, '', 1);
            }
        });
    }

    document.querySelectorAll('.populer-arama').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const query = this.getAttribute('data-arama');
            if(searchInput) {
                searchInput.value = query;
                if(temizleBtn) temizleBtn.classList.remove('hidden');
                veriYukle(seciliKategori, query, 1);
            }
        });
    });

    document.addEventListener('click', function(e) {
        const katBtn = e.target.closest('.kategori-btn');
        if(katBtn) {
            e.preventDefault();
            const kategori = katBtn.getAttribute('data-kategori') || 'tumu';
            const arama = searchInput ? searchInput.value.trim() : '';
            guncelleSidebarUI(kategori);
            veriYukle(kategori, arama, 1);
            return;
        }

        const sayfaBtn = e.target.closest('.sayfalama-btn');
        if(sayfaBtn) {
            e.preventDefault();
            const hedefSayfa = parseInt(sayfaBtn.getAttribute('data-sayfa'), 10);
            if(hedefSayfa) {
                const arama = searchInput ? searchInput.value.trim() : '';
                veriYukle(seciliKategori, arama, hedefSayfa);
            }
        }
    });

    window.addEventListener('popstate', function(e) {
        const urlParams = new URLSearchParams(window.location.search);
        const kategori = urlParams.get('k') || 'tumu';
        const arama = urlParams.get('q') || '';
        const sayfa = parseInt(urlParams.get('sayfa'), 10) || 1;

        if(searchInput) searchInput.value = arama;
        if(temizleBtn) temizleBtn.classList.toggle('hidden', arama.length === 0);

        guncelleSidebarUI(kategori);
        
        const params = new URLSearchParams();
        if(kategori && kategori !== 'tumu') params.set('k', kategori);
        if(arama) params.set('q', arama);
        if(sayfa > 1) params.set('sayfa', sayfa);

        fetch('/tedaviler/?ajax=1&' + params.toString())
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const currentYazilar = document.getElementById('yazilarAlan');
                const currentBaslik = document.getElementById('baslikAlan');
                const currentSayfalama = document.getElementById('sayfalamaAlan');

                if(currentYazilar && doc.querySelector('#yazilarAlan')) currentYazilar.innerHTML = doc.querySelector('#yazilarAlan').innerHTML;
                if(currentBaslik && doc.querySelector('#baslikAlan')) currentBaslik.innerHTML = doc.querySelector('#baslikAlan').innerHTML;
                if(currentSayfalama && doc.querySelector('#sayfalamaAlan')) currentSayfalama.innerHTML = doc.querySelector('#sayfalamaAlan').innerHTML;
            });
    });
})();
</script>

<?php include dirname(__DIR__) . '/inc/footer.php'; ?>

<?php
function getTedaviIcon($name, $size = 16) {
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