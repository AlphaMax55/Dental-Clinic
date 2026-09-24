<?php
// ============================================================
// 📍 www/site/galeri.php - GALERİ (FULL FIX v3)
// 🔥 Title dinamik | Kategori çalışır | Tab sidebar günceller
// ============================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../inc/config.php';

// ========== SİTE ADRESİ ==========
if (!isset($site_adresi) || empty($site_adresi)) {
    $site_adresi = 'adres gir';
}
$site_adresi = rtrim($site_adresi, '/');

// ========== ÇEVİRİ ==========
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
        return $metin;
    }
}

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_GET['lang'])) $_SESSION['dil'] = ($_GET['lang'] === 'en') ? 'en' : 'tr';
$lang = $_SESSION['dil'] ?? 'tr';
$dil_en = ($lang === 'en');

// ========== UI ==========
$ui = [
    'tr' => [
        'klinik_vakalar' => 'Klinik Vakalar & Sonuçları','gulus_galerisi' => 'Gülüş Galerisi &','tedavi_sonuclari' => 'Tedavi Sonuçları',
        'header_aciklama' => 'Kliniğimizde gerçekleştirdiğimiz tedavilerin fotoğraflarını ve bilgilendirici videolarını inceleyebilirsiniz.',
        'arama_placeholder' => 'Vaka, tedavi veya yöntem arayın...','onerilenler' => 'Önerilenler:',
        'implant' => 'İmplant','zirkonyum' => 'Zirkonyum','gulus_tasarimi' => 'Gülüş Tasarımı','lamine' => 'Lamine',
        'tab_fotograf' => 'Fotoğraflar','tab_video' => 'Videolar',
        'tab_foto_aciklama' => 'Klinik fotoğraf arşivi','tab_video_aciklama' => 'Bilgilendirici video içerikleri',
        'kategoriler' => 'KATEGORİLER','tum_vakalar' => 'TÜMÜ','populer_vakalar' => 'POPÜLER VAKALAR',
        'bulten_baslik' => 'Gülüş Rehberi & Bülten','bulten_aciklama' => 'Yeni vaka sonuçları ve estetik gülüş tavsiyeleri e-postanıza gelsin.',
        'eposta_adresiniz' => 'E-posta adresiniz','abone_ol' => 'Abone Ol',
        'adet_resim' => 'görsel','adet_video' => 'video',
        'alt_aciklama' => 'Seçilen kriterlere göre kliniğimizde tamamlanan vaka sonuçları',
        'klinik_sonuc' => 'Klinik Sonuç','incele' => 'İncele','izle' => 'İzle',
        'eslesen_yok' => 'Eşleşen Sonuç Bulunamadı','eslesen_yok_desc' => 'Farklı anahtar kelimelerle arama yapabilir veya filtreyi sıfırlayabilirsiniz.',
        'tedavi_rehberi' => 'TEDAVİ REHBERİ','merak_edilenler' => 'Merak Edilenler',
        'sss_aciklama' => 'Tedavi süreçleri ve uygulamalar hakkında sıkça sorulan yanıtlar',
        'begeni' => 'Beğeni','video_bolumu' => 'Videolar','foto_bolumu' => 'Fotoğraflar','kategori' => 'Kategori',
        'foto_bilgi' => 'Kliniğimizin fiziki yapısı, teknik donanımı ve ekip çalışmaları hakkında görsel arşiv.',
        'video_bilgi' => 'Tedaviler hakkında bilgilendirici video içerikleri. Tanı ve tedavi için hekiminize başvurunuz.',
    ],
    'en' => [
        'klinik_vakalar' => 'Clinical Cases & Results','gulus_galerisi' => 'Smile Gallery &','tedavi_sonuclari' => 'Case Results',
        'header_aciklama' => 'Explore photos and informational videos of treatments performed at our clinic.',
        'arama_placeholder' => 'Search case, treatment or method...','onerilenler' => 'Suggested:',
        'implant' => 'Implant','zirkonyum' => 'Zirconium','gulus_tasarimi' => 'Smile Design','lamine' => 'Laminate',
        'tab_fotograf' => 'Photos','tab_video' => 'Videos',
        'tab_foto_aciklama' => 'Clinical photo archive','tab_video_aciklama' => 'Informational video content',
        'kategoriler' => 'CATEGORIES','tum_vakalar' => 'ALL','populer_vakalar' => 'POPULAR CASES',
        'bulten_baslik' => 'Smile Newsletter','bulten_aciklama' => 'Get dental aesthetic advice and new case results.',
        'eposta_adresiniz' => 'Your email address','abone_ol' => 'Subscribe',
        'adet_resim' => 'images','adet_video' => 'videos',
        'alt_aciklama' => 'Before-after treatment results matching your criteria',
        'klinik_sonuc' => 'Clinical Result','incele' => 'Examine','izle' => 'Watch',
        'eslesen_yok' => 'No Results Found','eslesen_yok_desc' => 'You can search with different keywords or reset the filter.',
        'tedavi_rehberi' => 'TREATMENT GUIDE','merak_edilenler' => 'Frequently Asked Questions',
        'sss_aciklama' => 'Common answers about treatment procedures and practices',
        'begeni' => 'Likes','video_bolumu' => 'Videos','foto_bolumu' => 'Photos','kategori' => 'Category',
        'foto_bilgi' => 'Visual archive of our clinic\'s physical structure, technical equipment and team work.',
        'video_bilgi' => 'Informational video content about treatments. Consult your physician for diagnosis and treatment.',
    ],
];
$U = $ui[$dil_en ? 'en' : 'tr'];

// ========== VİDEO ÇÖZÜMLEYİCİ ==========
if (!function_exists('galeri_video_coz')) {
    function galeri_video_coz(?string $url): ?array {
        $url = trim((string)$url);
        if ($url === '') return null;
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?(?:.*&)?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return ['tip'=>'youtube','id'=>$m[1],'embed'=>'https://www.youtube-nocookie.com/embed/'.$m[1].'?autoplay=1&rel=0&mute=1','watch'=>'https://www.youtube.com/watch?v='.$m[1],'thumb'=>'https://img.youtube.com/vi/'.$m[1].'/maxresdefault.jpg','thumb_yedek'=>'https://img.youtube.com/vi/'.$m[1].'/hqdefault.jpg'];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return ['tip'=>'vimeo','id'=>$m[1],'embed'=>'https://player.vimeo.com/video/'.$m[1].'?autoplay=1&muted=1','watch'=>'https://vimeo.com/'.$m[1],'thumb'=>'','thumb_yedek'=>''];
        }
        if (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $url)) {
            return ['tip'=>'dosya','src'=>$url];
        }
        return null;
    }
}

// ========== PARAMETRELER ==========
$aktif_kategori_id = isset($_GET['kat']) ? intval($_GET['kat']) : 0;
$aktif_kategori_slug = isset($_GET['kat_slug']) ? trim($_GET['kat_slug']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// 🔥 URL'den slug parse
if (empty($aktif_kategori_slug)) {
    $req_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $segments = explode('/', $req_path);
    if (isset($segments[0]) && $segments[0] === 'galeri' && isset($segments[1]) && $segments[1] !== '') {
        $slug_candidate = $segments[1];
        if (!in_array($slug_candidate, ['video','resim','index.php'], true)) {
            $aktif_kategori_slug = $slug_candidate;
        }
    }
}

$aktif_tip = isset($_GET['tip']) && in_array($_GET['tip'], ['resim','video']) ? $_GET['tip'] : 'video';

// ========== VERİLER ==========
$kolon_medya = $db->query("SHOW COLUMNS FROM galeri_kategoriler LIKE 'medya_tipi'")->fetch();
$kategori_medya_tipi_var = (bool)$kolon_medya;

if ($kategori_medya_tipi_var) {
    $stmt = $db->prepare("SELECT id, kategori_adi, kategori_slug, ikon, medya_tipi, sira FROM galeri_kategoriler ORDER BY sira ASC");
} else {
    $stmt = $db->prepare("SELECT id, kategori_adi, kategori_slug, ikon, 'hepsi' AS medya_tipi, sira FROM galeri_kategoriler ORDER BY sira ASC");
}
$stmt->execute();
$kategoriler_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($aktif_kategori_id === 0 && !empty($aktif_kategori_slug)) {
    foreach ($kategoriler_db as $kat) {
        if (trim($kat['kategori_slug']) === $aktif_kategori_slug) {
            $aktif_kategori_id = intval($kat['id']);
            break;
        }
    }
}

$kolon_kontrol = $db->query("SHOW COLUMNS FROM galeri_resimler LIKE 'medya_tipi'")->fetch();
$medya_tipi_var = (bool)$kolon_kontrol;

if ($medya_tipi_var) {
    $stmt = $db->prepare("SELECT * FROM galeri_resimler WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC, created_at DESC");
} else {
    $stmt = $db->prepare("SELECT *, 'resim' AS medya_tipi, NULL AS video_url FROM galeri_resimler WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC, created_at DESC");
}
$stmt->execute();
$ham_resimler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resimler = [];
$kategori_tip_sayilari = ['resim' => [], 'video' => []];
$tip_sayilari = ['resim' => 0, 'video' => 0];

foreach($ham_resimler as $r) {
    $baslik_cevrilmis = galeri_cevir($r['baslik'] ?? '', $lang);
    $aciklama_cevrilmis = galeri_cevir($r['aciklama'] ?? '', $lang);
    $kat_id = intval($r['kategori_id']);

    $medya_tipi = in_array($r['medya_tipi'] ?? 'resim', ['resim','video']) ? ($r['medya_tipi'] ?? 'resim') : 'resim';
    $video_url = $r['video_url'] ?? '';
    $video_data = ($medya_tipi === 'video') ? galeri_video_coz($video_url) : null;

    $thumb = $r['thumbnail_url'] ?? '';
    if ($medya_tipi === 'video' && empty($thumb) && !empty($video_data['thumb'])) {
        $thumb = $video_data['thumb'];
    }

    if(!isset($kategori_tip_sayilari[$medya_tipi][$kat_id])) $kategori_tip_sayilari[$medya_tipi][$kat_id] = 0;
    $kategori_tip_sayilari[$medya_tipi][$kat_id]++;
    $tip_sayilari[$medya_tipi]++;

    $resimler[] = [
        'id' => $r['id'],'slug' => $r['slug'] ?? '','kategori_id' => $kat_id,
        'baslik' => $baslik_cevrilmis,'aciklama' => $aciklama_cevrilmis,
        'resim_url' => $r['resim_url'] ?? '','thumbnail_url' => $thumb,
        'tarih' => $r['tarih'] ?? date('Y'),'begeni' => intval($r['begeni'] ?? 0),
        'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s'),
        'medya_tipi' => $medya_tipi,'video_url' => $video_url,'video_sure' => $r['video_sure'] ?? null,
        'video_tip' => $video_data['tip'] ?? null,'video_embed' => $video_data['embed'] ?? null,
        'video_watch' => $video_data['watch'] ?? null,'video_src' => $video_data['src'] ?? null,
        'video_thumb_yedek' => $video_data['thumb_yedek'] ?? '',
    ];
}

$kategori_listesi = [];
$kategori_map = [];
foreach($kategoriler_db as $kat) {
    $kat_label = galeri_cevir($kat['kategori_adi'], $lang);
    $kategori_map[$kat['id']] = $kat_label;
    
    $count_resim = $kategori_tip_sayilari['resim'][$kat['id']] ?? 0;
    $count_video = $kategori_tip_sayilari['video'][$kat['id']] ?? 0;
    $aktif_count = ($aktif_tip === 'video') ? $count_video : $count_resim;
    
    // 🔥 Sadece o tabda içeriği olan kategoriler gelsin
    if ($aktif_count === 0) continue;
    
    $kategori_listesi[] = [
        'id' => intval($kat['id']),'slug' => $kat['kategori_slug'],'label' => $kat_label,
        'icon' => $kat['ikon'] ?? 'Camera','medya_tipi' => $kat['medya_tipi'] ?? 'hepsi',
        'count' => $aktif_count,'count_resim' => $count_resim,'count_video' => $count_video,
    ];
}
$kategori_slug_map = [];
foreach($kategoriler_db as $kat) {
    $kategori_slug_map[intval($kat['id'])] = $kat['kategori_slug'];
}
$aktif_kategori_adi = ($aktif_kategori_id === 0) ? $U['tum_vakalar'] : ($kategori_map[$aktif_kategori_id] ?? ($dil_en ? 'Gallery' : 'Galeri'));

// ========== FİLTRELEME ==========
$filtrelenmis_hepsi = [];
$filtrelenmis_video = [];
$filtrelenmis_resim = [];

foreach($resimler as $r) {
    if ($r['medya_tipi'] !== $aktif_tip) continue;
    
    $kategori_uygun = ($aktif_kategori_id === 0) || (intval($r['kategori_id']) === intval($aktif_kategori_id));
    $arama_uygun = empty($search_query) ||
                    stripos($r['baslik'], $search_query) !== false ||
                    stripos($r['aciklama'], $search_query) !== false;

    if(!$kategori_uygun || !$arama_uygun) continue;

    if($r['medya_tipi'] === 'video') $filtrelenmis_video[] = $r;
    else $filtrelenmis_resim[] = $r;
    $filtrelenmis_hepsi[] = $r;
}

$total_video = count($filtrelenmis_video);
$total_resim = count($filtrelenmis_resim);

// ========== SAYFALAMA ==========
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
if ($sayfa < 1) $sayfa = 1;

if ($aktif_tip === 'video') {
    $limit = 9;
    $total_posts = $total_video;
    $toplam_sayfa = ceil($total_posts / $limit);
    $baslangic = ($sayfa - 1) * $limit;
    $gosterilecek = array_slice($filtrelenmis_video, $baslangic, $limit);
} else {
    $limit = 12;
    $total_posts = $total_resim;
    $toplam_sayfa = ceil($total_posts / $limit);
    $baslangic = ($sayfa - 1) * $limit;
    $gosterilecek = array_slice($filtrelenmis_resim, $baslangic, $limit);
}

$populerVakalar = array_values(array_filter($resimler, fn($x) => $x['medya_tipi'] === $aktif_tip));
usort($populerVakalar, function($a, $b) { return $b['begeni'] - $a['begeni']; });
$populerVakalar = array_slice($populerVakalar, 0, 4);

$sss_list = [];
try {
    $sss_sorgu = $db->query("SELECT * FROM blog_sss WHERE durum = 1 AND (silindi = 0 OR silindi IS NULL) ORDER BY sira ASC");
    if ($sss_sorgu) $sss_list = $sss_sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ============================================================
// 🔥 SEO OVERRIDE — AJAX'tan ÖNCE (KRİTİK!)
// ============================================================
$kat_slug_url = ($aktif_kategori_id > 0) ? ($kategori_slug_map[$aktif_kategori_id] ?? '') : '';
$kat_path = $kat_slug_url ? ($kat_slug_url . '/') : '';

if (!empty($search_query)) {
    if ($dil_en) {
        $page_title = '"' . $search_query . '" — Search | Gallery | Prof. Dr. İbrahim Duran';
        $seo_description = 'Search results for "' . $search_query . '" — ' . $total_posts . ' results found.';
    } else {
        $page_title = '"' . $search_query . '" — Arama | Galeri | Prof. Dr. İbrahim Duran';
        $seo_description = '"' . $search_query . '" için ' . $total_posts . ' sonuç bulundu.';
    }
    $robots_etiketi_icerigi = '<meta name="robots" content="noindex, follow">';
    $mevcut_canonical_link = $site_adresi . '/galeri/' . ($dil_en ? '?lang=en' : '');
} elseif ($aktif_kategori_id > 0 && !empty($aktif_kategori_adi) && $aktif_kategori_adi !== $U['tum_vakalar']) {
    $kat_clean = trim(strip_tags($aktif_kategori_adi));
    if ($dil_en) {
        $page_title = $kat_clean . ' ' . ($aktif_tip === 'video' ? 'Videos' : 'Photos') . ' (' . $total_posts . ') | Gallery | Prof. Dr. İbrahim Duran';
        $seo_description = $total_posts . ' ' . ($aktif_tip === 'video' ? 'videos' : 'photos') . ' in ' . $kat_clean . ' category.';
    } else {
        $page_title = $kat_clean . ' ' . ($aktif_tip === 'video' ? 'Videoları' : 'Fotoğrafları') . ' (' . $total_posts . ') | Galeri | Prof. Dr. İbrahim Duran';
        $seo_description = $kat_clean . ' kategorisinde ' . $total_posts . ' ' . ($aktif_tip === 'video' ? 'video' : 'fotoğraf') . '.';
    }
    $mevcut_canonical_link = $site_adresi . '/galeri/' . $kat_path . ($dil_en ? '?lang=en' : '');
} else {
    if ($dil_en) {
        $page_title = ($aktif_tip === 'video' ? 'Dental Treatment Videos' : 'Dental Treatment Photos') . ' (' . $total_posts . ') | Gallery | Prof. Dr. İbrahim Duran';
        $seo_description = 'Informational dental treatment content by Prof. Dr. İbrahim Duran.';
    } else {
        $page_title = ($aktif_tip === 'video' ? 'Diş Tedavi Videoları' : 'Diş Tedavi Fotoğrafları') . ' (' . $total_posts . ') | Galeri | Prof. Dr. İbrahim Duran';
        $seo_description = 'Prof. Dr. İbrahim Duran kliniğinden diş tedavi içerikleri.';
    }
    $mevcut_canonical_link = $site_adresi . '/galeri/' . ($dil_en ? '?lang=en' : '');
}

$dynamic_og_title = $page_title;
$og_image = '/uploads/galeri/galeri-og.webp';
$site_keywords = 'galeri, video, Samsun diş hekimi, gülüş tasarımı, implant';
$seo_og_image = $og_image;
$robots_etiketi_icerigi = $robots_etiketi_icerigi ?? '<meta name="robots" content="index, follow">';

// ============================================================
// YARDIMCI RENDER FONKSİYONLARI
// ============================================================
function renderVideoKart($item, $kategori_map, $lang, $U, $kategori_slug_map) {
    if (empty($item)) return '';
    $dil_en = ($lang === 'en');
    $kat_adi = $kategori_map[$item['kategori_id']] ?? ($dil_en ? 'Case' : 'Vaka');
    $img_src = !empty($item['thumbnail_url']) ? $item['thumbnail_url'] : $item['resim_url'];
    $item_json = htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8");
    $kat_slug = $kategori_slug_map[$item['kategori_id']] ?? 'vaka';
    $item_slug = !empty($item['slug']) ? $item['slug'] : $item['id'];
    $detay_link = '/galeri/' . $kat_slug . '/' . $item_slug . '/';
    $data_tip = htmlspecialchars($item['video_tip'] ?? '', ENT_QUOTES, 'UTF-8');
    $data_src = htmlspecialchars($item['video_src'] ?? '', ENT_QUOTES, 'UTF-8');
    $data_embed = htmlspecialchars($item['video_embed'] ?? '', ENT_QUOTES, 'UTF-8');

    ob_start(); ?>
    <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-300 group">
        <div>
            <div class="kart-media-alani" data-id="<?php echo $item['id']; ?>" data-video-tip="<?php echo $data_tip; ?>" data-video-src="<?php echo $data_src; ?>" data-video-embed="<?php echo $data_embed; ?>" style="position: relative; background: #0f172a; min-height: 320px; overflow: hidden;">
                <div class="kart-video-thumb" onclick='openLightbox(<?php echo $item_json; ?>)' style="position: absolute; inset: 0; cursor: pointer; display: block;">
                    <?php if(!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($item['baslik']); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="position: absolute; inset: 0; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.3);">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </div>
                    <?php endif; ?>
                    <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.5), transparent 40%);"></div>
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none;">
                        <span class="group-hover:scale-110 transition-transform" style="width: 64px; height: 64px; border-radius: 50%; background: #dc2626; display: flex; align-items: center; justify-content: center; box-shadow: 0 20px 40px rgba(220,38,38,0.4);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff" style="margin-left: 3px;"><path d="M8 5.14v13.72a1 1 0 0 0 1.53.85l10.9-6.86a1 1 0 0 0 0-1.7L9.53 4.29A1 1 0 0 0 8 5.14z"/></svg>
                        </span>
                    </div>
                    <div style="position: absolute; top: 12px; left: 12px; z-index: 10;">
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 8px; font-size: 10px; font-weight: 900; text-transform: uppercase; background: #dc2626; color: #fff;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            VIDEO
                        </span>
                    </div>
                    <div style="position: absolute; bottom: 12px; right: 12px;">
                        <span style="padding: 4px 8px; background: rgba(15,23,42,0.8); color: #fff; font-size: 10px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                            <svg width="12" height="12" style="color: #f87171;" viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                            <?php echo number_format($item['begeni']); ?>
                        </span>
                    </div>
                </div>
                <div class="kart-video-player hidden" style="position: absolute; inset: 0; z-index: 5; pointer-events: none;"></div>
            </div>

            <div style="min-height: 200px; display: flex; flex-direction: column; justify-content: space-between; background: linear-gradient(to bottom right, #f8fafc, #ffffff); position: relative;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(to right, #ef4444, #dc2626, #e11d48);"></div>
                <div style="padding: 18px 18px 14px 18px; flex: 1; display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?php echo htmlspecialchars($item['tarih'] ?: date('Y')); ?>
                    </div>
                    <h3 style="font-size: 15px; font-weight: 900; color: #0f172a; line-height: 1.4; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?php echo htmlspecialchars($item['baslik']); ?>
                    </h3>
                    <div style="display: flex; align-items: center; gap: 10px; padding: 8px 10px; background: #fff; border: 1px solid #f1f5f9; border-radius: 10px;">
                        <span style="width: 26px; height: 26px; border-radius: 7px; background: linear-gradient(to bottom right, #dc2626, #e11d48); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </span>
                        <div style="min-width: 0; flex: 1;">
                            <div style="font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; line-height: 1; margin-bottom: 3px;"><?php echo $U['kategori'] ?? 'Kategori'; ?></div>
                            <div style="font-size: 12px; font-weight: 800; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($kat_adi); ?></div>
                        </div>
                    </div>
                </div>
                <div style="padding: 12px 14px; background: #fff; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px;">
                    <a href="<?php echo $detay_link; ?>" style="flex: 1 1 0; min-width: 0; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; background: linear-gradient(to right, #dc2626, #e11d48); color: #fff; font-weight: 700; font-size: 12.5px; text-decoration: none; border-radius: 10px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        <?php echo $dil_en ? 'Details' : 'Detay'; ?>
                    </a>
                    <button type="button" onclick='openLightbox(<?php echo $item_json; ?>)' style="flex: 1 1 0; min-width: 0; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; background: #f1f5f9; color: #334155; font-weight: 700; font-size: 12.5px; border: none; border-radius: 10px; cursor: pointer;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <?php echo $U['izle'] ?? 'İzle'; ?>
                    </button>
                </div>
            </div>
        </div>
    </article>
    <?php return ob_get_clean();
}

function renderResimKart($item, $kategori_map, $lang, $U) {
    if (empty($item)) return '';
    $dil_en = ($lang === 'en');
    $kat_adi = $kategori_map[$item['kategori_id']] ?? ($dil_en ? 'Case' : 'Vaka');
    $img_src = !empty($item['thumbnail_url']) ? $item['thumbnail_url'] : $item['resim_url'];
    $item_json = htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8");
    ob_start(); ?>
    <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col group">
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100 shrink-0">
            <div onclick='openLightbox(<?php echo $item_json; ?>)' class="cursor-pointer w-full h-full">
                <?php if(!empty($img_src)): ?>
                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($item['baslik']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <?php else: ?>
                    <div class="w-full h-full bg-gradient-to-tr from-slate-900 to-blue-900 flex items-center justify-center text-white/30">
                        <svg class="w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    </div>
                <?php endif; ?>
            </div>
            <div style="position: absolute; top: 10px; left: 10px; z-index: 20; max-width: calc(100% - 100px);">
                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 9px; background: #ffffff; color: #1d4ed8; font-size: 10px; font-weight: 800; border-radius: 8px; text-transform: uppercase;">
                    <span style="width: 5px; height: 5px; border-radius: 50%; background: #2563eb;"></span>
                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($kat_adi); ?></span>
                </span>
            </div>
            <div class="absolute top-2.5 right-2.5 z-20">
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[9px] font-bold uppercase bg-blue-600 text-white">
                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    <?php echo $dil_en ? 'Photo' : 'Fotoğraf'; ?>
                </span>
            </div>
            <div class="absolute bottom-3 right-3">
                <span class="px-2 py-1 bg-slate-900/80 text-white text-[10px] font-bold rounded-md flex items-center gap-1">
                    <svg class="w-3 h-3 text-red-400" viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <?php echo number_format($item['begeni']); ?>
                </span>
            </div>
        </div>
        <div class="p-4 flex-1 flex flex-col justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                    <?php echo htmlspecialchars($item['tarih'] ?: date('Y')); ?>
                    <span class="text-slate-300">•</span>
                    <span class="text-blue-600"><?php echo $U['klinik_sonuc']; ?></span>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-blue-600 line-clamp-2 leading-snug mb-2">
                    <?php echo htmlspecialchars($item['baslik']); ?>
                </h3>
                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                    <?php echo htmlspecialchars($item['aciklama']); ?>
                </p>
            </div>
            <button type="button" onclick='openLightbox(<?php echo $item_json; ?>;)' class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] rounded-lg">
                <span><?php echo $U['incele']; ?></span>
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
    </article>
    <?php return ob_get_clean();
}

function renderListe($aktif_tip, $gosterilecek, $kategori_map, $lang, $U, $kategori_slug_map) {
    ob_start();
    if (count($gosterilecek) > 0) {
        $grid_class = ($aktif_tip === 'video') ? 'grid grid-cols-1 lg:grid-cols-3 gap-5' : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5';
        echo '<div id="yazilarAlan" class="' . $grid_class . '">';
        foreach ($gosterilecek as $item) {
            echo ($aktif_tip === 'video') ? renderVideoKart($item, $kategori_map, $lang, $U, $kategori_slug_map) : renderResimKart($item, $kategori_map, $lang, $U);
        }
        echo '</div>';
    } else { ?>
        <div id="yazilarAlan">
            <div class="py-20 text-center bg-white border-2 border-dashed border-slate-200 rounded-3xl">
                <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-blue-500">
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1"><?php echo $U['eslesen_yok']; ?></h3>
                <p class="text-sm text-slate-500"><?php echo $U['eslesen_yok_desc']; ?></p>
            </div>
        </div>
    <?php }
    return ob_get_clean();
}

function renderMediaTablar($aktif_tip, $U, $tip_sayilari) {
    ob_start(); ?>
    <div class="media-tab-wrapper">
        <a href="javascript:void(0)" data-tip="video" class="media-tab galeri-tab <?php echo $aktif_tip === 'video' ? 'active' : ''; ?>">
            <div class="media-tab-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </div>
            <div class="media-tab-text">
                <span class="media-tab-title"><?php echo $U['tab_video']; ?></span>
                <span class="media-tab-sub"><?php echo $U['tab_video_aciklama']; ?></span>
            </div>
            <span class="media-tab-count"><?php echo $tip_sayilari['video']; ?></span>
        </a>
        <a href="javascript:void(0)" data-tip="resim" class="media-tab galeri-tab <?php echo $aktif_tip === 'resim' ? 'active' : ''; ?>">
            <div class="media-tab-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            </div>
            <div class="media-tab-text">
                <span class="media-tab-title"><?php echo $U['tab_fotograf']; ?></span>
                <span class="media-tab-sub"><?php echo $U['tab_foto_aciklama']; ?></span>
            </div>
            <span class="media-tab-count"><?php echo $tip_sayilari['resim']; ?></span>
        </a>
    </div>
    <?php return ob_get_clean();
}

function renderSidebarKategoriler($aktif_kategori_id, $kategori_listesi, $U, $aktif_tip, $tip_sayilari) {
    ob_start(); ?>
    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full <?php echo $aktif_tip === 'video' ? 'bg-red-600' : 'bg-blue-600'; ?>"></span>
            <?php echo $U['kategoriler']; ?>
        </h3>
    </div>

    <?php if($aktif_tip === 'video'): ?>
        <div class="mb-3 p-3 border border-blue-900 rounded-xl text-[11px] text-blue-100 leading-relaxed" style="background-color:#3b4a60;">ⓘ <?php echo $U['foto_bilgi']; ?></div>
    <?php else: ?>
        <div class="mb-3 p-3 border border-blue-900 rounded-xl text-[11px] text-blue-100 leading-relaxed" style="background-color:#3b4a60;">ⓘ <?php echo $U['video_bilgi']; ?></div>
    <?php endif; ?>

    <div class="space-y-2">
        <?php $is_active_tumu = (intval($aktif_kategori_id) === 0); ?>
        <a href="/galeri/" data-kategori="0" class="kategori-btn w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border <?php echo $is_active_tumu ? 'bg-blue-600 text-white border-blue-600 shadow-md ring-2 ring-blue-600/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'; ?>">
            <span class="flex items-center gap-2.5 min-w-0">
                <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0 <?php echo $is_active_tumu ? 'text-white' : 'text-blue-600'; ?>"><?php echo getGaleriIcon('Image', 15); ?></span>
                <span class="truncate"><?php echo $U['tum_vakalar']; ?></span>
            </span>
            <span class="kat-count-badge px-2 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 <?php echo $is_active_tumu ? 'bg-white/20 text-white' : 'bg-white text-slate-700 border border-slate-200'; ?>">
                <?php echo $tip_sayilari[$aktif_tip]; ?>
            </span>
        </a>

        <?php foreach($kategori_listesi as $kat):
            $is_active = (intval($aktif_kategori_id) === intval($kat['id']));
            $kat_link = '/galeri/' . $kat['slug'] . '/';
        ?>
        <a href="<?php echo $kat_link; ?>" data-kategori="<?php echo $kat['id']; ?>" class="kategori-btn w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs md:text-sm font-bold transition-all duration-200 border <?php echo $is_active ? 'bg-blue-600 text-white border-blue-600 shadow-md ring-2 ring-blue-600/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'; ?>">
            <span class="flex items-center gap-2.5 min-w-0">
                <span class="flex items-center justify-center w-6 h-6 rounded-lg shrink-0 <?php echo $is_active ? 'text-white' : 'text-blue-600'; ?>"><?php echo getGaleriIcon($kat['icon'], 15); ?></span>
                <span class="truncate"><?php echo mb_strtoupper($kat['label'], 'UTF-8'); ?></span>
            </span>
            <span class="kat-count-badge px-2 py-0.5 rounded-full text-[11px] font-extrabold shrink-0 <?php echo $is_active ? 'bg-white/20 text-white' : 'bg-white text-slate-700 border border-slate-200'; ?>">
                <?php echo $kat['count']; ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
}

function renderSayfalama($sayfa, $toplam_sayfa) {
    ob_start();
    if ($toplam_sayfa > 1): ?>
        <nav class="inline-flex items-center gap-2 bg-white p-2 rounded-2xl border border-slate-200 shadow-sm">
            <?php if($sayfa > 1): ?>
            <a href="javascript:void(0)" data-sayfa="<?php echo $sayfa - 1; ?>" class="sayfalama-btn p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition-all">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <?php endif; ?>
            <?php for($i = max(1, $sayfa - 2); $i <= min($toplam_sayfa, $sayfa + 2); $i++): ?>
            <a href="javascript:void(0)" data-sayfa="<?php echo $i; ?>" class="sayfalama-btn w-10 h-10 flex items-center justify-center rounded-xl text-sm font-bold transition-all <?php echo $i == $sayfa ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($sayfa < $toplam_sayfa): ?>
            <a href="javascript:void(0)" data-sayfa="<?php echo $sayfa + 1; ?>" class="sayfalama-btn p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition-all">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
            <?php endif; ?>
        </nav>
    <?php endif;
    return ob_get_clean();
}

// ============================================================
// AJAX ÇIKIŞI (SEO override yapıldıktan sonra)
// ============================================================
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

$baslik_prefix = ($aktif_kategori_id === 0) ? $U['tum_vakalar'] : $aktif_kategori_adi;
$adet_label = ($aktif_tip === 'video') ? $U['adet_video'] : $U['adet_resim'];

if ($is_ajax) {
    header('Content-Type: text/html; charset=utf-8');
    
    // 🔥 TITLE için hidden data
    echo '<div id="ajaxTitleData" data-title="' . htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . '"></div>';
    
    // 🔥 SIDEBAR (yeni hali)
    echo '<div id="sidebarKategoriAlan">' . renderSidebarKategoriler($aktif_kategori_id, $kategori_listesi, $U, $aktif_tip, $tip_sayilari) . '</div>';
    
    // 🔥 MEDIA TABLAR (yeni hali)
    echo '<div id="mediaTabAlan">' . renderMediaTablar($aktif_tip, $U, $tip_sayilari) . '</div>';
    
    // 🔥 BAŞLIK
    echo '<div class="pb-4 border-b border-slate-200">';
    echo '<h2 class="text-xl md:text-2xl font-black text-slate-900" id="baslikAlan">' . htmlspecialchars($baslik_prefix) . ' <span class="text-blue-600 text-sm ml-2 font-bold">(' . $total_posts . ' ' . $adet_label . ')</span></h2>';
    echo '<p class="text-xs md:text-sm text-slate-500 mt-1">' . htmlspecialchars($U['alt_aciklama']) . '</p>';
    echo '</div>';
    
    // 🔥 LİSTE
    echo renderListe($aktif_tip, $gosterilecek, $kategori_map, $lang, $U, $kategori_slug_map);
    
    // 🔥 SAYFALAMA
    echo '<div id="sayfalamaAlan" class="pt-8 flex justify-center">' . renderSayfalama($sayfa, $toplam_sayfa) . '</div>';
    
    // 🔥 LIGHTBOX IMAGES data (JS güncellesin)
    echo '<script id="lightboxDataScript" type="application/json">' . json_encode($filtrelenmis_hepsi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    echo '<script id="aktifTipDataScript" type="application/json">' . json_encode($aktif_tip) . '</script>';
    
    exit;
}

$page_slug = 'galeri';

// ========== SCHEMA ==========
$tum_schemalar = [];
$tum_schemalar[] = [
    '@context' => 'https://schema.org','@type' => 'CollectionPage',
    '@id' => $site_adresi . '/galeri/' . ($kat_path ?: '') . '#collectionpage',
    'url' => $mevcut_canonical_link,'name' => $page_title,'description' => $seo_description,
    'inLanguage' => $dil_en ? 'en-US' : 'tr-TR',
    'isPartOf' => ['@id' => $site_adresi . '/#website'],
    'about' => ['@id' => $site_adresi . '/#medicalbusiness'],
    'publisher' => ['@id' => $site_adresi . '/#medicalbusiness'],
];

$breadcrumb_items = [
    ['@type' => 'ListItem', 'position' => 1, 'name' => $dil_en ? 'Home' : 'Anasayfa', 'item' => $site_adresi . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => $dil_en ? 'Gallery' : 'Galeri', 'item' => $site_adresi . '/galeri/'],
];
if ($aktif_kategori_id > 0 && !empty($aktif_kategori_adi) && $aktif_kategori_adi !== $U['tum_vakalar']) {
    $breadcrumb_items[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $aktif_kategori_adi, 'item' => $site_adresi . '/galeri/' . ($kat_path ?: '')];
}
$tum_schemalar[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumb_items];

$sayfa_schemalari = '';
foreach ($tum_schemalar as $schema) {
    $sayfa_schemalari .= '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

include dirname(__DIR__) . '/inc/header.php';
echo $sayfa_schemalari;
?>

<style>
.media-tab-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; max-width: 720px; margin: 0 auto; }
.media-tab { display: flex; align-items: center; gap: 14px; padding: 18px 22px; border-radius: 18px; background: #f8fafc; border: 2px solid #e2e8f0; text-decoration: none; transition: all 0.25s ease; cursor: pointer; }
.media-tab:hover { transform: translateY(-2px); box-shadow: 0 12px 30px -10px rgba(0,0,0,0.15); }
.media-tab.active[data-tip="resim"] { background: linear-gradient(135deg, #2563eb, #1d4ed8); border-color: #2563eb; color: #fff; box-shadow: 0 12px 30px -8px rgba(37,99,235,0.5); }
.media-tab.active[data-tip="video"] { background: linear-gradient(135deg, #dc2626, #b91c1c); border-color: #dc2626; color: #fff; box-shadow: 0 12px 30px -8px rgba(220,38,38,0.5); }
.media-tab-icon { width: 48px; height: 48px; border-radius: 14px; background: #fff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #475569; }
.media-tab.active .media-tab-icon { background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.3); color: #fff; }
.media-tab-text { display: flex; flex-direction: column; gap: 3px; flex: 1; min-width: 0; }
.media-tab-title { font-size: 16px; font-weight: 900; color: #0f172a; }
.media-tab.active .media-tab-title { color: #fff; }
.media-tab-sub { font-size: 11px; font-weight: 600; color: #94a3b8; }
.media-tab.active .media-tab-sub { color: rgba(255,255,255,0.8); }
.media-tab-count { padding: 5px 12px; border-radius: 10px; font-size: 13px; font-weight: 900; background: #fff; color: #0f172a; border: 1px solid #e2e8f0; flex-shrink: 0; }
.media-tab.active .media-tab-count { background: rgba(255,255,255,0.25); color: #fff; border-color: rgba(255,255,255,0.3); }
@media (max-width: 640px) { .media-tab-wrapper { grid-template-columns: 1fr; } .media-tab { padding: 14px 16px; } .media-tab-icon { width: 40px; height: 40px; } .media-tab-title { font-size: 14px; } .media-tab-sub { display: none; } }
.kategori-btn:hover { background-color: inherit !important; color: inherit !important; border-color: inherit !important; }
.kategori-btn.bg-blue-600:hover { background-color: #2563eb !important; color: white !important; }
.kategori-btn.bg-slate-50:hover { background-color: #f1f5f9 !important; color: #1e293b !important; border-color: #cbd5e1 !important; }
.kategori-btn { transition: all 0.2s ease-in-out !important; cursor: pointer; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<main class="min-h-screen bg-white w-full font-sans selection:bg-cyan-100 selection:text-cyan-900">

<section style="background: linear-gradient(180deg, #020617 0%, #0f172a 100%); color: #ffffff; padding: 30px 20px; border-bottom: 1px solid #1e293b;">
    <div style="max-width: 850px; margin: 0 auto; text-align: center;">
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 16px; background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); border-radius: 9999px; color: #60a5fa; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 14px;">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: #38bdf8;"></span>
            <?php echo $U['klinik_vakalar']; ?>
        </div>
        <h1 style="font-size: clamp(26px, 3.5vw, 38px); font-weight: 900; color: #ffffff; margin: 0 0 10px 0; line-height: 1.2;">
            <?php echo $U['gulus_galerisi']; ?> <span style="color: #38bdf8;"><?php echo $U['tedavi_sonuclari']; ?></span>
        </h1>
        <p style="color: #94a3b8; font-size: 15px; max-width: 560px; margin: 0 auto 24px auto; line-height: 1.5;">
            <?php echo $U['header_aciklama']; ?>
        </p>

        <div id="mediaTabAlan"><?php echo renderMediaTablar($aktif_tip, $U, $tip_sayilari); ?></div>

        <div style="position: relative; max-width: 620px; margin: 24px auto 0 auto; text-align: left;">
            <div style="display: flex; align-items: center; background: #1e293b; border: 2px solid #334155; border-radius: 15px; padding: 7px 14px;">
                <div style="color: #38bdf8; display: flex; align-items: center; padding-right: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <input type="text" id="galeriSearchInput" placeholder="<?php echo $U['arama_placeholder']; ?>" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 100%; background: transparent; border: none; outline: none; color: #ffffff; font-size: 16px; font-weight: 500; padding: 7px 0;" autocomplete="off">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div id="aramaSpinner" class="hidden" style="width: 18px; height: 18px; border: 2px solid #38bdf8; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <button id="aramaTemizle" class="<?php echo empty($search_query) ? 'hidden' : ''; ?>" type="button" style="width: 26px; height: 26px; border-radius: 7px; background: #334155; border: none; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                    <span id="aramaSonucSayaci" style="background: #2563eb; color: #ffffff; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 7px;"><?php echo $total_posts; ?></span>
                </div>
            </div>
            <div style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;">
                <span style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;"><?php echo $U['onerilenler']; ?></span>
                <button type="button" data-arama="implant" class="populer-arama" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; cursor: pointer;">🦷 <?php echo $U['implant']; ?></button>
                <button type="button" data-arama="zirkonyum" class="populer-arama" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; cursor: pointer;">✨ <?php echo $U['zirkonyum']; ?></button>
                <button type="button" data-arama="gülüş tasarımı" class="populer-arama" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; cursor: pointer;">😁 <?php echo $U['gulus_tasarimi']; ?></button>
                <button type="button" data-arama="lamine" class="populer-arama" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 5px 12px; border-radius: 9px; font-size: 12px; cursor: pointer;">💎 <?php echo $U['lamine']; ?></button>
            </div>
        </div>
    </div>
</section>

<div class="w-full mx-auto py-10" style="padding-left: clamp(20px,4vw,50px); padding-right: clamp(20px,4vw,50px); max-width: 1750px;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <aside class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-xl border border-slate-200/80">
                <div id="sidebarKategoriAlan"><?php echo renderSidebarKategoriler($aktif_kategori_id, $kategori_listesi, $U, $aktif_tip, $tip_sayilari); ?></div>
            </div>

            <?php if(!empty($populerVakalar)): ?>
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-xl border border-slate-200/80">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="text-blue-600"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                    <?php echo $U['populer_vakalar']; ?>
                </h3>
                <div class="space-y-3">
                    <?php foreach($populerVakalar as $index => $item):
                        $img_src = !empty($item['thumbnail_url']) ? $item['thumbnail_url'] : $item['resim_url'];
                        $is_video = ($item['medya_tipi'] === 'video');
                    ?>
                    <div class="flex items-center gap-3 cursor-pointer group p-2.5 rounded-2xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100" onclick='openLightbox(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8"); ?>)'>
                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-100 shrink-0 relative">
                            <?php if(!empty($img_src)): ?>
                                <img src="<?php echo htmlspecialchars($img_src); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-black text-sm"><?php echo $index+1; ?></div>
                            <?php endif; ?>
                            <?php if($is_video): ?>
                                <span class="absolute inset-0 flex items-center justify-center bg-slate-900/40"><svg width="14" height="14" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 group-hover:text-blue-600 line-clamp-2 leading-snug"><?php echo htmlspecialchars($item['baslik']); ?></h4>
                            <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-1"><span>❤️ <?php echo number_format($item['begeni']); ?></span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-blue-800 rounded-3xl p-5 sm:p-6 shadow-xl text-white">
                <div class="w-10 h-10 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <h3 class="font-extrabold text-base mb-1"><?php echo $U['bulten_baslik']; ?></h3>
                <p class="text-blue-100 text-xs mb-4 leading-relaxed"><?php echo $U['bulten_aciklama']; ?></p>
                <form action="/api/blog-abone.php" method="POST" class="space-y-2.5">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="email" name="email" required placeholder="<?php echo $U['eposta_adresiniz']; ?>" class="w-full px-3.5 py-2.5 bg-white/10 border border-white/20 rounded-xl text-white placeholder:text-white/60 text-xs">
                    <button type="submit" class="w-full py-2.5 bg-white text-blue-700 font-bold text-xs rounded-xl hover:bg-blue-50"><?php echo $U['abone_ol']; ?></button>
                </form>
            </div>
        </aside>

        <section class="lg:col-span-9 space-y-8">
            <div class="pb-4 border-b border-slate-200">
                <h2 class="text-xl md:text-2xl font-black text-slate-900" id="baslikAlan">
                    <?php echo $baslik_prefix; ?> <span class="text-blue-600 text-sm ml-2 font-bold">(<?php echo $total_posts; ?> <?php echo $adet_label; ?>)</span>
                </h2>
                <p class="text-xs md:text-sm text-slate-500 mt-1"><?php echo $U['alt_aciklama']; ?></p>
            </div>
            <?php echo renderListe($aktif_tip, $gosterilecek, $kategori_map, $lang, $U, $kategori_slug_map); ?>
            <div id="sayfalamaAlan" class="pt-8 flex justify-center"><?php echo renderSayfalama($sayfa, $toplam_sayfa); ?></div>
        </section>
    </div>
</div>

<div id="lightboxModal" class="fixed inset-0 z-[200] bg-slate-950/95 backdrop-blur-xl hidden items-center justify-center p-4">
<button onclick="closeLightbox()" type="button" style="position: fixed; top: 20px; right: 20px; z-index: 9999; width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.3); color: #ffffff; cursor: pointer; display: flex; align-items: center; justify-content: center;">
    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
</button>
    <button id="lightboxPrev" class="absolute left-4 md:left-8 top-1/2 -translate-y-1/2 z-30 w-14 h-14 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white cursor-pointer border border-white/20">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <button id="lightboxNext" class="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 z-30 w-14 h-14 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white cursor-pointer border border-white/20">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
    </button>
    <div class="relative w-full max-w-6xl max-h-[92vh] flex flex-col items-center gap-5 overflow-y-auto">
        <div id="lightboxMedia" class="w-full flex items-center justify-center"></div>
        <div class="w-full bg-slate-900 border border-slate-700 rounded-3xl p-6 md:p-8">
            <h3 id="lightboxTitle" class="text-white text-xl md:text-3xl font-black mb-4"></h3>
            <p id="lightboxDesc" class="text-slate-300 text-base md:text-lg mb-5"></p>
            <div id="lightboxMeta" class="flex flex-wrap items-center gap-3 text-sm md:text-base text-slate-300"></div>
        </div>
    </div>
</div>

<?php if(!empty($sss_list)): ?>
<section class="bg-gradient-to-b from-slate-50 to-slate-100 py-20 border-t border-slate-200">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-100/80 border border-blue-200 rounded-full text-blue-700 text-xs font-black uppercase tracking-wider mb-3">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                <?php echo $U['tedavi_rehberi']; ?>
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-slate-900"><?php echo $U['merak_edilenler']; ?></h2>
            <p class="text-slate-500 text-sm md:text-base mt-2 max-w-xl mx-auto"><?php echo $U['sss_aciklama']; ?></p>
        </div>
        <?php
        $tekil_sss = []; $gorulen_sorular = [];
        foreach($sss_list as $item) {
            $k = mb_strtolower(trim($item['soru']), 'UTF-8');
            if(!in_array($k, $gorulen_sorular)) { $gorulen_sorular[] = $k; $tekil_sss[] = $item; }
        }
        $sol_sutun = []; $sag_sutun = [];
        foreach($tekil_sss as $i => $item) { if($i % 2 == 0) $sol_sutun[] = $item; else $sag_sutun[] = $item; }
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div class="space-y-4">
                <?php foreach($sol_sutun as $sss): ?>
                <div class="faq-item bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <button type="button" class="faq-question w-full flex items-center justify-between p-5 md:p-6 text-left cursor-pointer">
                        <span class="faq-baslik font-extrabold text-slate-800 text-base md:text-lg pr-4"><?php echo htmlspecialchars(function_exists('t_cevir') ? t_cevir($sss['soru']) : $sss['soru']); ?></span>
                        <span class="faq-ikon-kutu w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 shrink-0">
                            <svg class="faq-ok w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer hidden px-5 md:px-6 pb-6">
                        <div class="p-5 bg-blue-50/80 border-l-4 border-l-blue-600 text-slate-700 text-sm md:text-base leading-relaxed">
                            <?php echo nl2br(htmlspecialchars(function_exists('t_cevir') ? t_cevir($sss['cevap']) : $sss['cevap'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="space-y-4">
                <?php foreach($sag_sutun as $sss): ?>
                <div class="faq-item bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <button type="button" class="faq-question w-full flex items-center justify-between p-5 md:p-6 text-left cursor-pointer">
                        <span class="faq-baslik font-extrabold text-slate-800 text-base md:text-lg pr-4"><?php echo htmlspecialchars(function_exists('t_cevir') ? t_cevir($sss['soru']) : $sss['soru']); ?></span>
                        <span class="faq-ikon-kutu w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 shrink-0">
                            <svg class="faq-ok w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer hidden px-5 md:px-6 pb-6">
                        <div class="p-5 bg-blue-50/80 border-l-4 border-l-blue-600 text-slate-700 text-sm md:text-base leading-relaxed">
                            <?php echo nl2br(htmlspecialchars(function_exists('t_cevir') ? t_cevir($sss['cevap']) : $sss['cevap'])); ?>
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
// ========== VIDEO HOVER ==========
(function() {
    function hoverVideoBaslat(kart) {
        if (!kart) return;
        const thumb = kart.querySelector('.kart-video-thumb');
        const player = kart.querySelector('.kart-video-player');
        if (!thumb || !player) return;
        if (!player.classList.contains('hidden')) return;
        if (player.querySelector('.hover-preview')) return;
        const video_tip = kart.getAttribute('data-video-tip') || '';
        const video_src = kart.getAttribute('data-video-src') || '';
        const video_embed = kart.getAttribute('data-video-embed') || '';
        let html = '';
        if (video_tip === 'dosya' && video_src) {
            html = '<video class="hover-preview" autoplay muted loop playsinline style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"><source src="' + video_src + '"></video>';
        } else if (video_embed) {
            html = '<iframe class="hover-preview" src="' + video_embed + '" style="position:absolute;inset:0;width:100%;height:100%;border:0;" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        } else return;
        player.innerHTML = html;
        player.classList.remove('hidden');
        thumb.style.opacity = '0';
    }
    function hoverVideoDurdur(kart) {
        if (!kart) return;
        const thumb = kart.querySelector('.kart-video-thumb');
        const player = kart.querySelector('.kart-video-player');
        if (!player || !thumb) return;
        if (!player.querySelector('.hover-preview')) return;
        player.innerHTML = '';
        player.classList.add('hidden');
        thumb.style.opacity = '1';
    }
    document.addEventListener('mouseover', function(e) {
        const kart = e.target.closest('.kart-media-alani');
        if (kart && !kart.contains(e.relatedTarget)) hoverVideoBaslat(kart);
    });
    document.addEventListener('mouseout', function(e) {
        const kart = e.target.closest('.kart-media-alani');
        if (kart && !kart.contains(e.relatedTarget)) hoverVideoDurdur(kart);
    });
})();

window.__kategoriSlugMap = <?php echo json_encode($kategori_slug_map, JSON_UNESCAPED_UNICODE); ?>;

// ========== SSS ==========
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.faq-question');
    if (!btn) return;
    e.preventDefault();
    const parent = btn.closest('.faq-item');
    const answer = parent.querySelector('.faq-answer');
    const arrow = btn.querySelector('.faq-ok');
    const iconBox = btn.querySelector('.faq-ikon-kutu');
    const baslik = btn.querySelector('.faq-baslik');
    const isHidden = answer.classList.contains('hidden');
    if (isHidden) {
        answer.classList.remove('hidden');
        arrow.style.transform = 'rotate(180deg)';
        iconBox.classList.remove('bg-slate-100', 'text-slate-500');
        iconBox.classList.add('bg-blue-600', 'text-white');
        baslik.classList.add('text-blue-600');
        parent.classList.add('border-blue-300');
    } else {
        answer.classList.add('hidden');
        arrow.style.transform = 'rotate(0deg)';
        iconBox.classList.remove('bg-blue-600', 'text-white');
        iconBox.classList.add('bg-slate-100', 'text-slate-500');
        baslik.classList.remove('text-blue-600');
        parent.classList.remove('border-blue-300');
    }
});

// ========== LIGHTBOX ==========
let lightboxImages = <?php echo json_encode($filtrelenmis_hepsi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
let currentLightboxIndex = 0;
let aktifMedyaTip = <?php echo json_encode($aktif_tip); ?>;

window.openLightbox = function(item) {
    if (!item || !lightboxImages.length) return;
    currentLightboxIndex = lightboxImages.findIndex(img => img.id === item.id);
    if (currentLightboxIndex === -1) currentLightboxIndex = 0;
    updateLightbox();
    const modal = document.getElementById('lightboxModal');
    if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; }
};
window.closeLightbox = function() {
    const modal = document.getElementById('lightboxModal');
    if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow = ''; }
    const media = document.getElementById('lightboxMedia'); if (media) media.innerHTML = '';
};
function updateLightbox() {
    if (!lightboxImages.length) return;
    const item = lightboxImages[currentLightboxIndex];
    if (!item) return;
    const media = document.getElementById('lightboxMedia');
    const title = document.getElementById('lightboxTitle');
    const desc = document.getElementById('lightboxDesc');
    const meta = document.getElementById('lightboxMeta');
    if (media) {
        if (item.medya_tipi === 'video') {
            if (item.video_tip === 'dosya') {
                media.innerHTML = '<video controls autoplay playsinline style="max-width:100%;max-height:70vh;border-radius:1rem;"><source src="' + item.video_src + '"></video>';
            } else if (item.video_embed) {
                media.innerHTML = '<div style="width:100%;max-width:1100px;aspect-ratio:16/9;border-radius:1rem;overflow:hidden;"><iframe src="' + item.video_embed + '" style="width:100%;height:100%;border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
            } else {
                media.innerHTML = '<div style="color:#94a3b8;padding:60px;text-align:center;">Video bulunamadı</div>';
            }
        } else {
            media.innerHTML = '<img src="' + (item.resim_url || item.thumbnail_url || '') + '" style="max-width:100%;max-height:70vh;object-fit:contain;border-radius:1rem;">';
        }
    }
    if (title) title.innerText = item.baslik || '';
    if (desc) desc.innerText = item.aciklama || '';
    if (meta) {
        const tipBadge = item.medya_tipi === 'video' ? '<span style="background:#dc2626;color:#fff;padding:2px 8px;border-radius:6px;font-weight:700;">🎬 Video</span>' : '<span style="background:#2563eb;color:#fff;padding:2px 8px;border-radius:6px;font-weight:700;">📷 Fotoğraf</span>';
        meta.innerHTML = tipBadge + '<span>📅 ' + (item.tarih || '<?php echo date("Y"); ?>') + '</span>' + '<span>❤️ ' + (item.begeni || 0) + '</span>';
    }
}
window.nextLightbox = function() { if (!lightboxImages.length) return; currentLightboxIndex = (currentLightboxIndex + 1) % lightboxImages.length; updateLightbox(); };
window.prevLightbox = function() { if (!lightboxImages.length) return; currentLightboxIndex = (currentLightboxIndex - 1 + lightboxImages.length) % lightboxImages.length; updateLightbox(); };

// ========== AJAX ==========
(function() {
    const searchInput = document.getElementById('galeriSearchInput');
    const sonucSayaci = document.getElementById('aramaSonucSayaci');
    const spinner = document.getElementById('aramaSpinner');
    const temizleBtn = document.getElementById('aramaTemizle');
    let searchTimeout;
    let seciliKategori = '<?php echo $aktif_kategori_id; ?>';
    let seciliTip = aktifMedyaTip;

    function veriYukle(kategori, arama, tip, sayfa) {
        sayfa = sayfa || 1;
        if (spinner) spinner.classList.remove('hidden');
        if (sonucSayaci) { sonucSayaci.textContent = '...'; }

        const params = new URLSearchParams();
        if (kategori && String(kategori) !== '0') params.set('kat', kategori);
        if (arama) params.set('q', arama);
        if (tip) params.set('tip', tip);
        if (sayfa > 1) params.set('sayfa', sayfa);

        // Temiz URL
        const kategoriSlug = (kategori && String(kategori) !== '0') ? (window.__kategoriSlugMap[String(kategori)] || '') : '';
        const temizParams = new URLSearchParams();
        if (arama) temizParams.set('q', arama);
        if (sayfa > 1) temizParams.set('sayfa', sayfa);
        const qs = temizParams.toString();
        let newUrl = '/galeri/';
        if (kategoriSlug) newUrl += kategoriSlug + '/';
        if (qs) newUrl += '?' + qs;
        window.history.pushState({}, '', newUrl);

        fetch('/galeri/?ajax=1&' + params.toString())
            .then(r => r.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');

                // 🔥 TITLE güncelle
                const titleData = doc.querySelector('#ajaxTitleData');
                if (titleData && titleData.dataset.title) {
                    document.title = titleData.dataset.title;
                }

                // 🔥 SIDEBAR güncelle
                const yeniSidebar = doc.querySelector('#sidebarKategoriAlan');
                const currentSidebar = document.getElementById('sidebarKategoriAlan');
                if (currentSidebar && yeniSidebar) currentSidebar.innerHTML = yeniSidebar.innerHTML;

                // 🔥 MEDIA TABLAR güncelle
                const yeniTabs = doc.querySelector('#mediaTabAlan');
                const currentTabs = document.getElementById('mediaTabAlan');
                if (currentTabs && yeniTabs) currentTabs.innerHTML = yeniTabs.innerHTML;

                // 🔥 BAŞLIK güncelle
                const yeniBaslik = doc.querySelector('#baslikAlan');
                const currentBaslik = document.getElementById('baslikAlan');
                if (currentBaslik && yeniBaslik) currentBaslik.innerHTML = yeniBaslik.innerHTML;

                // 🔥 LİSTE güncelle
                const yeniYazilar = doc.querySelector('#yazilarAlan');
                const currentYazilar = document.getElementById('yazilarAlan');
                if (currentYazilar && yeniYazilar) currentYazilar.outerHTML = yeniYazilar.outerHTML;

                // 🔥 SAYFALAMA güncelle
                const yeniSayfalama = doc.querySelector('#sayfalamaAlan');
                const currentSayfalama = document.getElementById('sayfalamaAlan');
                if (currentSayfalama && yeniSayfalama) currentSayfalama.innerHTML = yeniSayfalama.innerHTML;

                // 🔥 LIGHTBOX data güncelle
                const lightboxScript = doc.querySelector('#lightboxDataScript');
                if (lightboxScript) {
                    try { lightboxImages = JSON.parse(lightboxScript.textContent); } catch(e) {}
                }
                const tipScript = doc.querySelector('#aktifTipDataScript');
                if (tipScript) {
                    try { aktifMedyaTip = JSON.parse(tipScript.textContent); } catch(e) {}
                }

                // 🔥 Kategori event yeniden bağla
                bindKategoriEvents();

                // 🔥 Media tab event yeniden bağla (EKSİKTİ!)
                bindMediaTabEvents();

                // 🔥 Sonuç sayacı

                // 🔥 Sonuç sayacı
                const sonucYazisi = currentBaslik ? currentBaslik.innerText : '';
                const sayi = sonucYazisi.match(/\d+/);
                if (sonucSayaci) sonucSayaci.textContent = sayi ? sayi[0] : '0';
            })
            .catch(() => { if (sonucSayaci) sonucSayaci.textContent = '!'; })
            .finally(() => { if (spinner) spinner.classList.add('hidden'); });
    }

    // 🔥 Kategori butonlarını doğrudan bağla (capture phase)
    function bindKategoriEvents() {
        document.querySelectorAll('.kategori-btn').forEach(function(btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                const kategori = this.getAttribute('data-kategori') || '0';
                const arama = searchInput ? searchInput.value.trim() : '';
                seciliKategori = kategori;
                veriYukle(kategori, arama, seciliTip, 1);
                return false;
            }, true);
        });
    }

    // 🔥 Media tab event
    function bindMediaTabEvents() {
        document.querySelectorAll('.media-tab').forEach(function(tab) {
            if (tab.dataset.bound === '1') return;
            tab.dataset.bound = '1';
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                const yeniTip = this.getAttribute('data-tip');
                if (yeniTip === seciliTip) return false;
                seciliTip = yeniTip;
                aktifMedyaTip = yeniTip;
                // Sidebar'ı temizle ki kategori tekrar seçilsin
                seciliKategori = '0';
                const arama = searchInput ? searchInput.value.trim() : '';
                veriYukle('0', arama, yeniTip, 1);
                return false;
            }, true);
        });
    }

    // Sayfalama (delegation, çünkü dinamik geliyor)
    document.addEventListener('click', function(e) {
        const sayfaBtn = e.target.closest('.sayfalama-btn');
        if (sayfaBtn) {
            e.preventDefault();
            const hedefSayfa = parseInt(sayfaBtn.getAttribute('data-sayfa'), 10);
            if (hedefSayfa) {
                const arama = searchInput ? searchInput.value.trim() : '';
                veriYukle(seciliKategori, arama, seciliTip, hedefSayfa);
            }
        }
    });

    // İlk bağlama
    bindKategoriEvents();
    bindMediaTabEvents();

    // Arama
    if (searchInput) {
        if (temizleBtn) temizleBtn.classList.toggle('hidden', searchInput.value.length === 0);
        searchInput.addEventListener('input', function() {
            if (searchTimeout) clearTimeout(searchTimeout);
            if (temizleBtn) temizleBtn.classList.toggle('hidden', this.value.length === 0);
            searchTimeout = setTimeout(() => veriYukle(seciliKategori, this.value.trim(), seciliTip, 1), 300);
        });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); veriYukle(seciliKategori, searchInput.value.trim(), seciliTip, 1); }
        });
    }
    if (temizleBtn) {
        temizleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (searchInput) { searchInput.value = ''; searchInput.focus(); this.classList.add('hidden'); veriYukle(seciliKategori, '', seciliTip, 1); }
        });
    }

    document.querySelectorAll('.populer-arama').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const q = this.getAttribute('data-arama');
            if (searchInput) { searchInput.value = q; if (temizleBtn) temizleBtn.classList.remove('hidden'); veriYukle(seciliKategori, q, seciliTip, 1); }
        });
    });

    const prevBtn = document.getElementById('lightboxPrev');
    const nextBtn = document.getElementById('lightboxNext');
    if (prevBtn) prevBtn.onclick = window.prevLightbox;
    if (nextBtn) nextBtn.onclick = window.nextLightbox;

    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('lightboxModal');
        if (!modal || modal.classList.contains('hidden')) return;
        if (e.key === 'Escape') window.closeLightbox();
        if (e.key === 'ArrowLeft') window.prevLightbox();
        if (e.key === 'ArrowRight') window.nextLightbox();
    });
})();
</script>
<?php include dirname(__DIR__) . '/inc/footer.php'; ?>

<?php
function getGaleriIcon($name, $size = 16) {
    if (empty($name)) $name = 'Camera';
    $icons = [
        'Camera' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>',
        'Video' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
        'Image' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
        'Sparkles' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><circle cx="4" cy="20" r="2"/></svg>',
        'Smile' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>',
        'Heart' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'Star' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'Folder' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2 z"/></svg>'
    ];
    return isset($icons[$name]) ? $icons[$name] : $icons['Camera'];
}
?>