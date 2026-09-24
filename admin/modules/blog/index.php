<?php
//admin/modules/blog/index.php

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

$sub = isset($_GET['sub']) ? $_GET['sub'] : 'blog';
$selectedCat = isset($_GET['kat']) ? $_GET['kat'] : '';

// ============================================================
// 🔥 KATEGORİ SLUG MAP (SEO İÇİN)
// ============================================================
$kategoriler = $db->query("SELECT * FROM blog_kategoriler WHERE silindi = 0 OR silindi IS NULL ORDER BY sira ASC")->fetchAll();
$kategori_slug_map = [];
foreach ($kategoriler as $kat) {
    $kategori_slug_map[$kat['kategori_slug']] = $kat['kategori_adi'];
}

// ============================================================
// 🔥 YAZI DETAYI (SEO İÇİN) - SEO ALANLARI DAHİL
// ============================================================
$yazi_slug = isset($_GET['yazi']) ? $_GET['yazi'] : '';
$yazi_detay = null;
if ($yazi_slug) {
    $stmt = $db->prepare("SELECT 
        id, baslik, slug, kategori, ozet, icerik, resim, 
        yazar, yazar_unvan, goruntulenme, begeni, yorum_sayisi, durum, 
        created_at, updated_at, silindi, silinme_tarihi,
        seo_title, seo_description, seo_keywords
        FROM blog_yazilar 
        WHERE slug = ? AND durum = 1 AND (silindi = 0 OR silindi IS NULL)");
    $stmt->execute([$yazi_slug]);
    $yazi_detay = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ============================================================
// 🔥 GENEL SEO AYARLARI (VERİTABANINDAN ÇEKME)
// ============================================================
$seo_title_tr = '';
$seo_title_en = '';
$seo_description_tr = '';
$seo_description_en = '';
$seo_keywords_tr = '';
$seo_keywords_en = '';
$seo_og_image = '';
$seo_canonical = '';

try {
    $stmt_seo = $db->query("SELECT anahtar, deger FROM blog_seo_ayarlar");
    if ($stmt_seo) {
        while ($row = $stmt_seo->fetch(PDO::FETCH_ASSOC)) {
            if ($row['anahtar'] == 'seo_title_tr') $seo_title_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_title_en') $seo_title_en = $row['deger'];
            if ($row['anahtar'] == 'seo_description_tr') $seo_description_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_description_en') $seo_description_en = $row['deger'];
            if ($row['anahtar'] == 'seo_keywords_tr') $seo_keywords_tr = $row['deger'];
            if ($row['anahtar'] == 'seo_keywords_en') $seo_keywords_en = $row['deger'];
            if ($row['anahtar'] == 'seo_og_image') $seo_og_image = $row['deger'];
            if ($row['anahtar'] == 'seo_canonical') $seo_canonical = $row['deger'];
        }
    }
} catch (Exception $e) {}

// Varsayılan SEO Değerleri
if ($yazi_detay) {
    $page_title = !empty($yazi_detay['seo_title']) 
        ? $yazi_detay['seo_title'] 
        : $yazi_detay['baslik'] . ' | Blog | Prof. Dr. İbrahim Duran';
    
    $seo_description = !empty($yazi_detay['seo_description']) 
        ? $yazi_detay['seo_description'] 
        : $yazi_detay['ozet'];
    
    $seo_keywords = !empty($yazi_detay['seo_keywords']) 
        ? $yazi_detay['seo_keywords'] 
        : $yazi_detay['baslik'] . ', diş sağlığı, blog, Prof. Dr. İbrahim Duran';
    
    $seo_og_image_display = $yazi_detay['resim'] ?: '/uploads/blog/blog-og.webp';
    $seo_canonical_display = 'https://www.dribrahimdurandentalclinic.com/blog/' . $selectedCat . '/' . $yazi_slug;
    $robots = 'index, follow';
} else {
    $page_title = $seo_title_tr ?: 'Blog | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun';
    $seo_description = $seo_description_tr ?: 'Prof. Dr. İbrahim Duran\'ın kaleminden diş sağlığı, estetik diş hekimliği, implant tedavisi, gülüş tasarımı, zirkonyum kaplama ve ağız sağlığı üzerine uzman görüşleri.';
    $seo_keywords = $seo_keywords_tr ?: 'blog, diş sağlığı, implant, gülüş tasarımı, estetik diş hekimliği, Samsun diş hekimi';
    $seo_og_image_display = $seo_og_image ?: '/uploads/blog/blog-og.webp';
    $seo_canonical_display = $seo_canonical ?: 'https://www.dribrahimdurandentalclinic.com/blog/';
    $robots = 'index, follow';
}

// SSS verileri
$sssList = $db->query("SELECT * FROM blog_sss WHERE silindi = 0 OR silindi IS NULL ORDER BY sira ASC")->fetchAll();
$cop_sss = $db->query("SELECT COUNT(*) FROM blog_sss WHERE silindi = 1")->fetchColumn();

// Yazı verileri (seçili kategoriye göre filtrele)
if ($selectedCat && $selectedCat != '') {
    $selectedCatName = isset($kategori_slug_map[$selectedCat]) ? $kategori_slug_map[$selectedCat] : $selectedCat;
    $yazilar = $db->query("SELECT * FROM blog_yazilar WHERE (silindi = 0 OR silindi IS NULL) AND kategori = '" . addslashes($selectedCatName) . "' ORDER BY created_at DESC")->fetchAll();
} else {
    $yazilar = $db->query("SELECT * FROM blog_yazilar WHERE silindi = 0 OR silindi IS NULL ORDER BY created_at DESC")->fetchAll();
}

// Abone verileri
$aboneler = $db->query("SELECT * FROM blog_aboneler ORDER BY created_at DESC")->fetchAll();

// Çöp kutusu sayıları
$cop_kutusu = $db->query("SELECT COUNT(*) FROM blog_yazilar WHERE silindi = 1")->fetchColumn();
$cop_kategori = $db->query("SELECT COUNT(*) FROM blog_kategoriler WHERE silindi = 1")->fetchColumn();

// İstatistik hesaplamaları
$yazilar_count = $db->query("SELECT COUNT(*) FROM blog_yazilar WHERE silindi = 0 OR silindi IS NULL")->fetchColumn();
$aktif_yazi = $db->query("SELECT COUNT(*) FROM blog_yazilar WHERE (silindi = 0 OR silindi IS NULL) AND durum = 1")->fetchColumn();
$toplam_goruntulenme = $db->query("SELECT SUM(goruntulenme) FROM blog_yazilar WHERE silindi = 0 OR silindi IS NULL")->fetchColumn();
$pasif_yazi = $yazilar_count - $aktif_yazi;
$aktif_sss = $db->query("SELECT COUNT(*) FROM blog_sss WHERE (silindi = 0 OR silindi IS NULL) AND durum = 1")->fetchColumn();

// Her kategori için yazı sayısını hesapla
$kategoriSayilari = [];
foreach($kategoriler as $kat) {
    $say = $db->query("SELECT COUNT(*) FROM blog_yazilar WHERE kategori = '" . addslashes($kat['kategori_adi']) . "' AND (silindi = 0 OR silindi IS NULL)")->fetchColumn();
    $kategoriSayilari[$kat['kategori_adi']] = $say;
}
$toplamYazi = array_sum($kategoriSayilari);

// ============================================================
// 🔥 VERİTABANINA SEO ALANLARINI EKLE (KONTROL)
// ============================================================
try {
    $columns = $db->query("SHOW COLUMNS FROM blog_yazilar LIKE 'seo_title'")->fetch();
    if (!$columns) {
        $db->exec("ALTER TABLE blog_yazilar ADD COLUMN seo_title VARCHAR(255) DEFAULT NULL");
        $db->exec("ALTER TABLE blog_yazilar ADD COLUMN seo_description TEXT DEFAULT NULL");
        $db->exec("ALTER TABLE blog_yazilar ADD COLUMN seo_keywords VARCHAR(255) DEFAULT NULL");
        $db->exec("ALTER TABLE blog_yazilar ADD COLUMN seo_og_image VARCHAR(500) DEFAULT NULL");
    }
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seo_keywords); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($seo_og_image_display); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($seo_canonical_display); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($robots); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; }
        
        .top-menu { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; background: white; padding: 6px; border-radius: 60px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .top-menu a { padding: 10px 24px; border-radius: 40px; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: all 0.2s; background: transparent; color: #64748b; }
        .top-menu a:hover { background: #f1f5f9; color: #1e293b; }
        .top-menu a.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 12px rgba(102,126,234,0.3); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 24px; }
        .filter-chip { background: white; border-radius: 60px; padding: 8px 20px; border: 1px solid #e2e8f0; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-block; }
        .filter-chip:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .filter-chip-active { background: #667eea; border-color: #667eea; }
        .filter-chip-active div:first-child { color: white !important; }
        .filter-chip-active div:last-child { color: rgba(255,255,255,0.8) !important; }
        
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 10px; font-size: 0.7rem; font-weight: 600; text-decoration: none; transition: all 0.2s; cursor: pointer; border: none; }
        .btn-primary { background: #eff6ff; color: #2563eb; }
        .btn-primary:hover { background: #2563eb; color: white; }
        .btn-danger { background: #fef2f2; color: #dc2626; }
        .btn-danger:hover { background: #dc2626; color: white; }
        .btn-add { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px 24px; border-radius: 40px; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; }
        
        .toast-notification { position: fixed; bottom: 30px; right: 30px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; }
        .toast-item { background: white; border-radius: 16px; padding: 14px 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; animation: toastSlideIn 0.3s ease; border-left: 4px solid; }
        .toast-item.success { border-left-color: #10b981; }
        .toast-item.error { border-left-color: #ef4444; }
        .toast-item.info { border-left-color: #3b82f6; }
        .toast-item.warning { border-left-color: #f59e0b; }
        .toast-item .toast-icon { font-size: 1.2rem; }
        .toast-item.success .toast-icon { color: #10b981; }
        .toast-item.error .toast-icon { color: #ef4444; }
        .toast-item.info .toast-icon { color: #3b82f6; }
        .toast-item.warning .toast-icon { color: #f59e0b; }
        .toast-item .toast-message { font-size: 0.8rem; color: #1e293b; font-weight: 500; }
        @keyframes toastSlideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .toast-item.slide-out { animation: toastSlideOut 0.3s ease forwards; }
        @keyframes toastSlideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
        
        .btn-cancel-custom { background: #e2e8f0; padding: 10px 28px; border-radius: 40px; border: none; cursor: pointer; font-weight: 600; }
        .btn-submit { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px 28px; border-radius: 40px; border: none; font-weight: 600; cursor: pointer; }
        
        .gallery-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 99999; overflow: auto; }
        .modal-dialog-custom { margin: 30px auto; max-width: 1100px; width: 90%; }
        .modal-content-custom { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 32px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .modal-header-custom { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .form-group-custom { margin-bottom: 1rem; }
        .form-group-custom label { font-weight: 600; color: #1e293b; font-size: 0.75rem; margin-bottom: 0.25rem; display: block; }
        .form-control-custom { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; transition: all 0.2s; }
        .form-control-custom:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .upload-btn { background: #f1f5f9; padding: 10px 16px; border-radius: 12px; cursor: pointer; font-size: 0.75rem; border: 1px solid #e2e8f0; display: inline-block; }

        .modal-tabs { display: flex; gap: 0; background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 0 24px; flex-shrink: 0; position: relative; }
        .modal-tab { display: flex; align-items: center; gap: 8px; padding: 14px 22px; border: none; background: transparent; font-size: 13px; font-weight: 600; color: #94a3b8; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.3s ease; white-space: nowrap; position: relative; }
        .modal-tab:hover { color: #1e293b; background: rgba(0,0,0,0.03); border-bottom-color: #cbd5e1; }
        .modal-tab.active { color: #3b82f6; border-bottom-color: #3b82f6; background: rgba(59,130,246,0.05); }
        .modal-tab.active:hover { color: #2563eb; background: rgba(59,130,246,0.08); border-bottom-color: #2563eb; }
        .hidden { display: none !important; }
    </style>
</head>
<body>

<div style="padding: 24px; max-width: 1400px; margin: 0 auto;">
    
    <!-- BAŞLIK -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 28px; padding: 28px 32px; margin-bottom: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 18px;">
                <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-blog" style="font-size: 1.6rem; color: white;"></i>
                </div>
                <div>
                    <h1 style="margin: 0; color: white; font-weight: 800; font-size: 1.6rem;">Blog Yönetimi</h1>
                    <p style="font-size: 0.7rem; color: rgba(255,255,255,0.8); margin-top: 5px;">Yazılar, SSS ve Aboneler</p>
                </div>
            </div>
            <button onclick="location.reload()" style="background: rgba(255,255,255,0.15); border: none; width: 42px; height: 42px; border-radius: 14px; color: white; cursor: pointer;"><i class="fas fa-sync-alt"></i></button>
        </div>
    </div>

    <!-- ÜST MENU - 4 TAB -->
    <div class="top-menu">
        <a href="?modul=blog&sub=blog" class="<?php echo $sub == 'blog' ? 'active' : ''; ?>"><i class="fas fa-blog"></i> Blog</a>
        <a href="?modul=blog&sub=sss" class="<?php echo $sub == 'sss' ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> SSS</a>
        <a href="?modul=blog&sub=aboneler" class="<?php echo $sub == 'aboneler' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Aboneler</a>
        <a href="?modul=blog&sub=seo" class="<?php echo $sub == 'seo' ? 'active' : ''; ?>"><i class="fas fa-search"></i> SEO</a>
    </div>

    <?php if ($sub == 'blog'): ?>
    <!-- ============= BLOG (Yazılar + Kategori Filtre) ============= -->
    
    <!-- İSTATİSTİK KARTLARI -->
    <div class="stats-grid">
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="width: 36px; height: 36px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                <i class="fas fa-newspaper" style="color: #3b82f6; font-size: 1rem;"></i>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo $yazilar_count; ?></div>
            <div style="font-size: 10px; color: #94a3b8;">Toplam Yazı</div>
            <div style="font-size: 9px; margin-top: 5px; color: #10b981;"><i class="fas fa-arrow-up"></i> <?php echo $aktif_yazi; ?> aktif</div>
        </div>
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="width: 36px; height: 36px; background: #dcfce7; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                <i class="fas fa-eye" style="color: #10b981; font-size: 1rem;"></i>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo number_format($toplam_goruntulenme); ?></div>
            <div style="font-size: 10px; color: #94a3b8;">Toplam Görüntülenme</div>
        </div>
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="width: 36px; height: 36px; background: #fef3c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                <i class="fas fa-folder" style="color: #f59e0b; font-size: 1rem;"></i>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo count($kategoriler); ?></div>
            <div style="font-size: 10px; color: #94a3b8;">Kategori</div>
        </div>
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="width: 36px; height: 36px; background: #fee2e2; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                <i class="fas fa-eye-slash" style="color: #ef4444; font-size: 1rem;"></i>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo $pasif_yazi; ?></div>
            <div style="font-size: 10px; color: #94a3b8;">Pasif Yazı</div>
        </div>
        <div id="copKutusuKart" style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="openCopPanel()">
            <div>
                <div style="font-size: 22px; font-weight: 800; color: #64748b;" id="copSayisiToplam"><?php echo $cop_kutusu; ?></div>
                <div style="font-size: 10px; color: #94a3b8;">Yazı Çöp Kutusu</div>
            </div>
            <i class="fas fa-trash-alt" style="color: #64748b; font-size: 1rem;"></i>
        </div>
        <div id="copKategoriKart" style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="openKategoriCopPanel()">
            <div>
                <div style="font-size: 22px; font-weight: 800; color: #64748b;" id="copKategoriSayisi"><?php echo $cop_kategori; ?></div>
                <div style="font-size: 10px; color: #94a3b8;">Kategori Çöp Kutusu</div>
            </div>
            <i class="fas fa-trash-alt" style="color: #64748b; font-size: 1rem;"></i>
        </div>
    </div>

    <!-- KATEGORİ FİLTRE CHIP'LERİ -->
    <div style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-folder-tree" style="color: #667eea;"></i>
                <span style="font-weight: 600; font-size: 0.85rem;">📁 Kategori Seç</span>
            </div>
            <div style="display: flex; gap: 8px;">
                <button onclick="openKategoriModal()" class="btn-icon" style="background: #667eea; color: white;"><i class="fas fa-plus"></i> Kategori Ekle / Düzenle - Sil</button>
            </div>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            <a href="?modul=blog&sub=blog" class="filter-chip <?php echo $selectedCat == '' ? 'filter-chip-active' : ''; ?>">
                <div style="font-weight: 700; font-size: 0.9rem; color: <?php echo $selectedCat == '' ? 'white' : '#1e293b'; ?>">Tümü</div>
                <div style="font-size: 0.7rem; color: <?php echo $selectedCat == '' ? 'rgba(255,255,255,0.8)' : '#94a3b8'; ?>"><?php echo $toplamYazi; ?> yazı</div>
            </a>
            <?php foreach($kategoriler as $kat): 
                $kat_adi = $kat['kategori_adi'];
                $kat_slug = $kat['kategori_slug'];
            ?>
            <a href="?modul=blog&sub=blog&kat=<?php echo urlencode($kat_slug); ?>" class="filter-chip <?php echo $selectedCat == $kat_slug ? 'filter-chip-active' : ''; ?>">
                <div style="font-weight: 700; font-size: 0.9rem; color: <?php echo $selectedCat == $kat_slug ? 'white' : '#1e293b'; ?>">
                    <i class="fas fa-<?php echo strtolower($kat['ikon']); ?>"></i> <?php echo htmlspecialchars($kat_adi); ?>
                </div>
                <div style="font-size: 0.7rem; color: <?php echo $selectedCat == $kat_slug ? 'rgba(255,255,255,0.8)' : '#94a3b8'; ?>">
                    <?php echo isset($kategoriSayilari[$kat_adi]) ? $kategoriSayilari[$kat_adi] : 0; ?> yazı
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- KATEGORİ ÇÖP KUTUSU PANELİ -->
    <div id="copKategoriPanel" style="display: none; background: white; border-radius: 24px; margin-top: 24px; border: 1px solid #e2e8f0; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #64748b 0%, #475569 100%); padding: 16px 20px; display: flex; justify-content: space-between;">
            <div><i class="fas fa-trash-alt"></i> <span style="color: white; margin-left: 10px;">Kategori Çöp Kutusu</span> <span id="copKategoriPanelSayi" style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 20px; color: white;">0</span></div>
            <div><button onclick="emptyKategoriCopPanel()" style="background: #dc2626; color: white; border: none; padding: 6px 16px; border-radius: 30px; cursor: pointer;">Tümünü Temizle</button> <button onclick="closeKategoriCopPanel()" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 6px 16px; border-radius: 30px; cursor: pointer;">Kapat</button></div>
        </div>
        <div id="copKategoriPanelListesi" style="padding: 20px; text-align: center;">Yükleniyor...</div>
    </div>

    <!-- YAZI ÇÖP KUTUSU PANELİ -->
    <div id="copPanel" style="display: none; background: white; border-radius: 24px; margin-bottom: 24px; border: 1px solid #e2e8f0; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #64748b 0%, #475569 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div><i class="fas fa-trash-alt" style="color: white;"></i> <span style="color: white; margin-left: 10px;">Yazı Çöp Kutusu</span> <span id="copPanelSayi" style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 20px; color: white;">0</span></div>
            <div><button onclick="emptyCopPanel()" style="background: #dc2626; color: white; border: none; padding: 6px 16px; border-radius: 30px; cursor: pointer;">Tümünü Temizle</button> <button onclick="closeCopPanel()" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 6px 16px; border-radius: 30px; cursor: pointer;">Kapat</button></div>
        </div>
        <div id="copPanelListesi" style="padding: 20px; text-align: center;">Yükleniyor...</div>
    </div>
    
    <!-- YAZILAR BAŞLIK + EKLE BUTONU -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="font-size: 1.2rem; font-weight: 700; color: #0f172a;">
            <i class="fas fa-list" style="color: #667eea;"></i> 
            <?php 
            if ($selectedCat && $selectedCat != ''): 
                echo htmlspecialchars(isset($kategori_slug_map[$selectedCat]) ? $kategori_slug_map[$selectedCat] : $selectedCat);
            else: 
                echo 'Tüm Yazılar';
            endif; 
            ?>
            <span style="font-size: 0.8rem; background: #e2e8f0; padding: 2px 10px; border-radius: 20px; margin-left: 10px;">
                <?php echo count($yazilar); ?> yazı
            </span>
        </h3>
        <button onclick="openYaziModal()" class="btn-add">➕ Yeni Yazı Ekle</button>
    </div>

    <!-- YAZI KARTLARI -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 24px;">
        <?php foreach ($yazilar as $yazi): ?>
        <div style="background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="height: 200px; overflow: hidden; position: relative; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <?php if(!empty($yazi['resim'])): ?>
                <img src="<?php echo htmlspecialchars($yazi['resim']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-newspaper" style="font-size: 4rem; color: rgba(255,255,255,0.3);"></i></div>
                <?php endif; ?>
                <div style="position: absolute; top: 15px; right: 15px;"><span style="background: <?php echo $yazi['durum'] ? '#10b981' : '#64748b'; ?>; color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.65rem;"><?php echo $yazi['durum'] ? 'Yayında' : 'Taslak'; ?></span></div>
                <div style="position: absolute; bottom: 15px; left: 15px;"><span style="background: rgba(0,0,0,0.6); color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.65rem;">
                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($yazi['kategori']); ?>
                </span></div>
            </div>
            <div style="padding: 20px;">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 10px;"><?php echo htmlspecialchars(mb_substr($yazi['baslik'], 0, 60)); ?></h3>
                <p style="font-size: 0.75rem; color: #64748b; margin-bottom: 16px;"><?php echo htmlspecialchars(mb_substr($yazi['ozet'], 0, 100)); ?>...</p>
                <div style="display: flex; gap: 12px;">
                    <button onclick="openYaziModal(<?php echo $yazi['id']; ?>)" class="btn-icon btn-primary" style="flex: 1; justify-content: center;"><i class="fas fa-edit"></i> Düzenle</button>
                    <button onclick="deleteItem('yazilar', <?php echo $yazi['id']; ?>)" class="btn-icon btn-danger" style="flex: 1; justify-content: center;"><i class="fas fa-trash"></i> Sil</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if(count($yazilar) == 0): ?>
    <div style="text-align: center; padding: 80px 20px; background: white; border-radius: 24px;"><i class="fas fa-newspaper" style="font-size: 4rem; color: #cbd5e1;"></i><p style="margin-top: 15px;">Henüz yazı eklenmemiş</p><button onclick="openYaziModal()" class="btn-add" style="margin-top: 15px;">İlk Yazıyı Ekle</button></div>
    <?php endif; ?>

    <?php elseif ($sub == 'sss'): ?>
    <!-- ============= SSS ============= -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px;">
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="width: 36px; height: 36px; background: #d1fae5; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-question-circle" style="color: #10b981; font-size: 1rem;"></i>
                </div>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo count($sssList); ?></div>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Toplam SSS</div>
            <div style="font-size: 9px; margin-top: 5px; color: #10b981;"><i class="fas fa-check-circle" style="font-size: 8px;"></i> <?php echo $aktif_sss; ?> aktif</div>
        </div>
        
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="width: 36px; height: 36px; background: #fee2e2; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-eye-slash" style="color: #ef4444; font-size: 1rem;"></i>
                </div>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo count($sssList) - $aktif_sss; ?></div>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Pasif Soru</div>
            <div style="font-size: 9px; margin-top: 5px; color: #ef4444;"><i class="fas fa-clock" style="font-size: 8px;"></i> gizli</div>
        </div>

        <div id="copSssKart" style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="openSssCopPanel()">
            <div>
                <div style="font-size: 22px; font-weight: 800; color: #64748b;" id="copSssSayisi"><?php echo $cop_sss; ?></div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">SSS ÇÖP KUTUSU</div>
            </div>
            <div style="width: 36px; height: 36px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-trash-alt" style="color: #64748b; font-size: 1rem;"></i>
            </div>
        </div>
        <?php 
        $min_sira = count($sssList) > 0 ? min(array_column($sssList, 'sira')) : 0;
        $max_sira = count($sssList) > 0 ? max(array_column($sssList, 'sira')) : 0;
        ?>
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="width: 36px; height: 36px; background: #fef3c7; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-sort-numeric-down" style="color: #f59e0b; font-size: 1rem;"></i>
                </div>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo $min_sira; ?> - <?php echo $max_sira; ?></div>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Sıra Aralığı</div>
            <div style="font-size: 9px; margin-top: 5px; color: #f59e0b;"><i class="fas fa-arrow-right" style="font-size: 8px;"></i> sıralama</div>
        </div>
    </div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="font-size: 1.2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-question-circle" style="color: #667eea;"></i> Sık Sorulan Sorular
        </h3>
        <button onclick="openSssModal()" class="btn-add">➕ Yeni SSS Ekle</button>
    </div>
    
    <div id="copSssPanel" style="display: none; background: white; border-radius: 24px; margin-bottom: 24px; border: 1px solid #e2e8f0; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #64748b 0%, #475569 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <i class="fas fa-trash-alt" style="color: white;"></i> 
                <span style="color: white; margin-left: 10px; font-weight: 600;">SSS Çöp Kutusu</span> 
                <span id="copSssPanelSayi" style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 20px; color: white; margin-left: 8px;">0</span>
            </div>
            <div>
                <button onclick="emptySssCopPanel()" style="background: #dc2626; color: white; border: none; padding: 6px 16px; border-radius: 30px; cursor: pointer; font-size: 0.7rem; font-weight: 500;">
                    <i class="fas fa-trash-alt"></i> Tümünü Temizle
                </button>
                <button onclick="closeSssCopPanel()" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 6px 16px; border-radius: 30px; cursor: pointer; font-size: 0.7rem; font-weight: 500; margin-left: 8px;">
                    <i class="fas fa-times"></i> Kapat
                </button>
            </div>
        </div>
        <div id="copSssPanelListesi" style="padding: 20px; min-height: 200px; text-align: center;">Yükleniyor...</div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 24px;">
        <?php foreach ($sssList as $sss): ?>
        <div style="background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; transition: all 0.3s ease;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; position: relative;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-question-circle" style="color: white; font-size: 1.4rem;"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 30px; font-size: 0.65rem; color: white;">
                                <i class="fas fa-sort-numeric-down"></i> Sıra: <?php echo $sss['sira']; ?>
                            </span>
                            <?php if($sss['durum']): ?>
                            <span style="background: #10b981; color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.65rem; font-weight: 600;">
                                <i class="fas fa-check-circle"></i> Aktif
                            </span>
                            <?php else: ?>
                            <span style="background: #64748b; color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.65rem; font-weight: 600;">
                                <i class="fas fa-eye-slash"></i> Pasif
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="padding: 20px;">
                <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 12px; line-height: 1.4;">
                    <?php echo htmlspecialchars($sss['soru']); ?>
                </h3>
                <div style="background: #f8fafc; border-radius: 16px; padding: 14px; margin-bottom: 16px;">
                    <p style="font-size: 0.8rem; color: #475569; line-height: 1.5; margin: 0;">
                        <?php echo htmlspecialchars(mb_substr($sss['cevap'], 0, 120)); ?>...
                    </p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button onclick="openSssModal(<?php echo $sss['id']; ?>)" class="btn-icon btn-primary"><i class="fas fa-edit"></i> Düzenle</button>
                    <button onclick="deleteItem('sss', <?php echo $sss['id']; ?>)" class="btn-icon btn-danger" style="flex: 1; justify-content: center;"><i class="fas fa-trash"></i> Sil</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php elseif ($sub == 'aboneler'): ?>
    <!-- ============= ABONELER ============= -->
    <?php 
    $son_7_gun = 0; $son_30_gun = 0;
    foreach($aboneler as $a) { if(strtotime($a['created_at']) > strtotime('-7 days')) $son_7_gun++; if(strtotime($a['created_at']) > strtotime('-30 days')) $son_30_gun++; }
    ?>
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px;">
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="width: 36px; height: 36px; background: #d1fae5; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-envelope" style="color: #10b981; font-size: 1rem;"></i>
                </div>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo count($aboneler); ?></div>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Toplam Abone</div>
        </div>
        
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="width: 36px; height: 36px; background: #fef3c7; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-calendar-week" style="color: #f59e0b; font-size: 1rem;"></i>
                </div>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;">+<?php echo $son_7_gun; ?></div>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Son 7 Gün</div>
        </div>
        
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="width: 36px; height: 36px; background: #e0e7ff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-calendar-month" style="color: #6366f1; font-size: 1rem;"></i>
                </div>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;">+<?php echo $son_30_gun; ?></div>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Son 30 Gün</div>
        </div>
        
        <div style="background: white; border-radius: 20px; padding: 14px 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="width: 36px; height: 36px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-chart-simple" style="color: #64748b; font-size: 1rem;"></i>
                </div>
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo round($son_30_gun / 30, 1); ?></div>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Günlük Ortalama</div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="font-size: 1.2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-users" style="color: #667eea;"></i> Abone Listesi
        </h3>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
        <?php foreach ($aboneler as $abone): ?>
        <div style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <i class="fas fa-envelope" style="color: #667eea;"></i>
                <span style="font-size: 0.9rem; font-weight: 600; color: #0f172a; word-break: break-all;"><?php echo htmlspecialchars($abone['email']); ?></span>
            </div>
            <div style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 16px;">
                <i class="fas fa-calendar-alt"></i> Kayıt: <?php echo date('d.m.Y H:i', strtotime($abone['created_at'])); ?>
            </div>
            <button onclick="deleteItem('aboneler', <?php echo $abone['id']; ?>)" class="btn-icon btn-danger" style="width: 100%; justify-content: center;">
                <i class="fas fa-trash"></i> Aboneyi Sil
            </button>
        </div>
        <?php endforeach; ?>
    </div>

    <?php elseif ($sub == 'seo'): ?>
    <!-- ============= SEO PANELİ ============= -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="font-size: 1.2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-search" style="color: #667eea;"></i> Blog SEO Ayarları
        </h3>
        <span style="background: #8b5cf6; color: white; padding: 4px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 500;">
            <i class="fas fa-globe"></i> Ana Blog Sayfası
        </span>
    </div>

    <div style="background: white; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); padding: 16px 24px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-cog" style="color: white;"></i>
                <span style="color: white; font-weight: 600;">SEO Meta Ayarları</span>
            </div>
        </div>
        
        <div style="padding: 24px;">
            <form id="blogSeoForm" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
<div class="seo-lang-tabs" style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
    <button type="button" class="blog-seo-lang-btn active" data-lang="tr" onclick="switchBlogAnaSeoLang('tr')" style="padding: 8px 20px; border: none; background: none; font-weight: 600; color: #3b82f6; border-bottom: 2px solid #3b82f6; cursor: pointer;">🇹🇷 Türkçe</button>
    <button type="button" class="blog-seo-lang-btn" data-lang="en" onclick="switchBlogAnaSeoLang('en')" style="padding: 8px 20px; border: none; background: none; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; cursor: pointer;">🇬🇧 English</button>
</div>
                
                <div class="blog-seo-lang-content" data-lang="tr">
                    <div style="background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">📌 SEO Başlık (Title) - TR</label>
                            <input type="text" name="seo_title_tr" id="blog_seo_title_tr" class="form-control-custom" value="<?php echo htmlspecialchars($seo_title_tr); ?>">
                        </div>
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">📝 Meta Açıklama (Description) - TR</label>
                            <textarea name="seo_description_tr" id="blog_seo_description_tr" class="form-control-custom" rows="4"><?php echo htmlspecialchars($seo_description_tr); ?></textarea>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">🏷️ Anahtar Kelimeler (Keywords) - TR</label>
                            <input type="text" name="seo_keywords_tr" id="blog_seo_keywords_tr" class="form-control-custom" value="<?php echo htmlspecialchars($seo_keywords_tr); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="blog-seo-lang-content" data-lang="en" style="display: none;">
                    <div style="background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">📌 SEO Title - EN</label>
                            <input type="text" name="seo_title_en" id="blog_seo_title_en" class="form-control-custom" value="<?php echo htmlspecialchars($seo_title_en); ?>">
                        </div>
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">📝 Meta Description - EN</label>
                            <textarea name="seo_description_en" id="blog_seo_description_en" class="form-control-custom" rows="4"><?php echo htmlspecialchars($seo_description_en); ?></textarea>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">🏷️ Keywords - EN</label>
                            <input type="text" name="seo_keywords_en" id="blog_seo_keywords_en" class="form-control-custom" value="<?php echo htmlspecialchars($seo_keywords_en); ?>">
                        </div>
                    </div>
                </div>
                
                <div style="background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">🖼️ OG Görsel (Sosyal Medya)</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" name="seo_og_image" id="blog_seo_og_image" class="form-control-custom" value="<?php echo htmlspecialchars($seo_og_image); ?>">
                                <button type="button" class="upload-btn" onclick="blogSeoResimYukle()" style="margin: 0; padding: 10px 16px;"><i class="fas fa-upload"></i></button>
                            </div>
                            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">1200x630 px önerilir</div>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;">🔗 Canonical URL</label>
                            <input type="text" name="seo_canonical" id="blog_seo_canonical" class="form-control-custom" value="<?php echo htmlspecialchars($seo_canonical); ?>">
                            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Boş bırakırsanız otomatik oluşur</div>
                        </div>
                    </div>
                </div>
                
                <div style="background: #f1f5f9; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                    <div style="font-size: 11px; font-weight: 600; color: #94a3b8; margin-bottom: 8px;"><i class="fas fa-eye"></i> Google Arama Sonucu Önizlemesi</div>
                    <div style="background: white; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div id="blog_seo_preview_title" style="color: #1a0dab; font-size: 18px;"><?php echo htmlspecialchars($seo_title_tr ?: 'Blog | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun'); ?></div>
                        <div id="blog_seo_preview_url" style="color: #006621; font-size: 14px; margin-top: 2px;"><?php echo htmlspecialchars($seo_canonical ?: 'www.dribrahimdurandentalclinic.com/blog/'); ?></div>
                        <div id="blog_seo_preview_desc" style="color: #545454; font-size: 14px; margin-top: 4px;"><?php echo htmlspecialchars($seo_description_tr ?: 'Prof. Dr. İbrahim Duran\'ın kaleminden diş sağlığı makaleleri.'); ?></div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="submit" class="btn-submit" id="blogSeoKaydetBtn"><i class="fas fa-save"></i> SEO Ayarlarını Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>
<!-- MODERN ONAY MODALI -->
<div id="confirmModal" class="gallery-modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-dialog-custom" style="max-width: 420px; width: 90%;">
        <div class="modal-content-custom" style="text-align: center; padding: 30px;">
            <div style="width: 64px; height: 64px; background: #fef2f2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; font-size: 28px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h4 id="confirmTitle" style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Emin misiniz?</h4>
            <p id="confirmMessage" style="font-size: 0.85rem; color: #64748b; margin-bottom: 24px; line-height: 1.5;">Bu işlemi gerçekleştirmek istediğinize emin misiniz?</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn-cancel-custom" onclick="closeConfirmModal()" style="flex: 1; padding: 12px; border-radius: 12px;">Vazgeç</button>
                <button type="button" id="confirmOkBtn" style="flex: 1; background: #dc2626; color: white; padding: 12px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer;">Evet, Sil</button>
            </div>
        </div>
    </div>
</div>
<!-- ============================================================ -->
<!-- YAZI MODAL (GELİŞMİŞ GÖRSEL MOTORU EKLENDİ) -->
<!-- ============================================================ -->
<div id="yaziModal" class="gallery-modal">
    <div class="modal-dialog-custom">
        <div class="modal-content-custom">
            <div class="modal-header-custom">
                <h5 id="yaziModalTitle" style="color: white;">Yazı Ekle/Düzenle</h5>
                <button onclick="closeModal('yaziModal')" style="background: none; border: none; color: white;">✕</button>
            </div>
            
<div class="modal-tabs">
    <button type="button" class="modal-tab active" data-tab="tab1" onclick="switchTab('tab1')">
        <i class="fas fa-info-circle"></i> <span>Temel</span>
    </button>
    <button type="button" class="modal-tab" data-tab="tab2" onclick="switchTab('tab2')">
        <i class="fas fa-file-alt"></i> <span>İçerik & Görsel</span>
    </button>
    <button type="button" class="modal-tab" data-tab="tab3" onclick="switchTab('tab3')">
        <i class="fas fa-search"></i> <span>🔍 SEO</span>
    </button>
</div>
            
            <form id="yaziForm" style="padding: 30px;">
                <input type="hidden" name="id" id="yaziId">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <!-- TAB 1: TEMEL -->
                <div class="tab-pane active" id="tab1">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div><label>Başlık</label><input type="text" name="baslik" id="yaziBaslik" class="form-control-custom" required></div>
                        <div><label>Slug</label><input type="text" name="slug" id="yaziSlug" class="form-control-custom" required></div>
                        <div><label>Kategori</label><select name="kategori" id="yaziKategori" class="form-control-custom">
                            <?php foreach($kategoriler as $kat): ?>
                                <option value="<?php echo $kat['kategori_adi']; ?>"><?php echo $kat['kategori_adi']; ?></option>
                            <?php endforeach; ?>
                        </select></div>
                        <div><label>Durum</label><select name="durum" id="yaziDurum" class="form-control-custom"><option value="1">Aktif</option><option value="0">Pasif</option></select></div>
                        <div><label>Yazar</label><input type="text" name="yazar" id="yaziYazar" class="form-control-custom"></div>
                        <div><label>Yazar Ünvanı</label><input type="text" name="yazar_unvan" id="yaziUnvan" class="form-control-custom"></div>
                    </div>
                    <div style="margin-top:16px;">
                        <button type="button" class="btn-primary btn-icon" onclick="switchTab('tab2')" style="padding:8px 20px; float:right; border-radius:10px;">İçerik & Görsel <i class="fas fa-arrow-right"></i></button>
                        <div style="clear:both;"></div>
                    </div>
                </div>
                
                <!-- TAB 2: İÇERİK & GÖRSEL (TEDAVİLER PANELİNDEKİ YAPI) -->
                <div class="tab-pane" id="tab2" style="display:none;">
                    <div><label>Özet</label><textarea name="ozet" id="yaziOzet" rows="3" class="form-control-custom"></textarea></div>
                    <div style="margin-top:12px;"><label>İçerik</label><textarea name="icerik" id="yaziIcerik" rows="8" class="form-control-custom"></textarea></div>
                    
                    <!-- 📸 GELİŞMİŞ GÖRSEL KARTI -->
                    <div style="margin-top:16px; border:1px solid #e2e8f0; border-radius:16px; padding:16px; background:#f8fafc;">
                        <label style="font-weight:700; font-size:13px; color:#1e293b; display:flex; align-items:center; gap:6px; margin-bottom:12px;">
                            <i class="fas fa-image text-blue-600"></i> 📸 Blog Kapak Görseli
                        </label>
                        
                        <div style="border: 2px dashed #cbd5e1; border-radius: 14px; padding: 14px; text-align: center; background: white; position: relative;"
                             id="blogDropZone"
                             ondragover="event.preventDefault(); this.style.borderColor='#3b82f6';"
                             ondragleave="this.style.borderColor='#cbd5e1';"
                             ondrop="event.preventDefault(); this.style.borderColor='#cbd5e1'; handleDropBlog(event)">
                            
                            <!-- Önizleme -->
                            <div id="blogOnizleme" class="hidden" style="margin-bottom: 10px;">
                                <img id="blogImg" src="" alt="Önizleme" style="max-height: 180px; width: auto; object-fit: contain; margin: 0 auto; border-radius: 10px; border: 1px solid #e2e8f0; display:block;">
                            </div>
                            
                            <!-- Boş Yükleme Alanı -->
                            <div id="blogYukleAlan">
                                <svg style="width: 38px; height: 38px; color: #94a3b8; margin: 0 auto;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="2" width="20" height="20" rx="2"/><circle cx="8.5" cy="8.5" r="2.5"/><path d="M21 15l-5-5-6 6-3-3-4 4"/>
                                </svg>
                                <p style="font-size: 13px; color: #475569; margin-top: 4px;">Görseli sürükleyin veya <span style="color: #2563eb; font-weight: 700; cursor: pointer;" onclick="document.getElementById('blogDosyaInput').click()">tıklayın</span></p>
                                <p style="font-size: 11px; color: #94a3b8;">PNG, JPG, WEBP (Otomatik WebP'ye dönüştürülür)</p>
                            </div>
                            <input type="file" id="blogDosyaInput" accept="image/*" class="hidden" onchange="resimYukleBlog(this.files[0])">
                            
                            <!-- Aksiyon / Kırp / Döndür Butonları -->
                            <div id="blogCropContainer" class="hidden" style="margin-top: 10px;">
                                <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                                    <button type="button" onclick="cropBlog()" style="padding: 6px 14px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600;">✂️ Kes</button>
                                    <button type="button" onclick="resmiOrijinalHalGetirBlog()" id="blogOrijinalButon" class="hidden" style="padding: 6px 14px; background: #10b981; color: white; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600;">🔄 Orijinal</button>
                                    <button type="button" onclick="resmiKaldirBlog()" style="padding: 6px 14px; background: #ef4444; color: white; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600;">❌ Kaldır</button>
                                    <button type="button" onclick="document.getElementById('blogDosyaInput').click()" style="padding: 6px 14px; background: #64748b; color: white; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600;">📤 Değiştir</button>
                                    <button type="button" onclick="dondurResimBlog(90)" style="padding: 6px 14px; background: #f97316; color: white; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600;">↺ -90°</button>
                                    <button type="button" onclick="dondurResimBlog(-90)" style="padding: 6px 14px; background: #f97316; color: white; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600;">↻ +90°</button>
                                </div>
                            </div>
                            
                            <!-- URL İle Ekle -->
                            <div style="margin-top: 10px; padding: 6px; background: #eff6ff; border-radius: 10px; border: 1px solid #bfdbfe;">
                                <div style="display: flex; gap: 6px;">
                                    <input type="text" id="blogUrlInput" class="form-control-custom" placeholder="https://..." style="padding: 6px 10px; font-size: 12px; flex: 1;">
                                    <button type="button" onclick="resimUrlIleEkleBlog()" style="padding: 6px 16px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                                        <i class="fas fa-download"></i> Ekle
                                    </button>
                                </div>
                            </div>
                            
                            <input type="hidden" name="resim" id="yaziResim" value="">
                        </div>
                    </div>

                    <div style="margin-top:16px; display:flex; justify-content:space-between;">
                        <button type="button" class="btn-cancel-custom" onclick="switchTab('tab1')"><i class="fas fa-arrow-left"></i> Temel</button>
                        <button type="button" class="btn-primary btn-icon" onclick="switchTab('tab3')" style="padding:8px 20px; border-radius:10px;">SEO <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                
                <!-- TAB 3: SEO -->
                <div class="tab-pane" id="tab3" style="display:none;">
                    <div class="form-card">
                        <div class="form-card-header" style="background:#f8fafc; padding:12px 18px; border-bottom:1px solid #e2e8f0; font-weight:600; font-size:13px; color:#1e293b; display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-search text-indigo-500"></i>
                            <span>🔍 Yazıya Özel SEO</span>
                        </div>
                        <div class="form-card-body" style="padding:16px 18px 18px;">
<div class="seo-lang-tabs" style="display:flex; gap:4px; margin-bottom:16px; background:#f1f5f9; border-radius:12px; padding:4px;">
    <button type="button" class="seo-lang-btn active" data-lang="tr" onclick="switchYaziSeoLang('tr')" style="padding:8px 20px; border:none; background:white; font-weight:600; font-size:13px; color:#3b82f6; border-radius:8px; cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,0.06);">🇹🇷 Türkçe</button>
    <button type="button" class="seo-lang-btn" data-lang="en" onclick="switchYaziSeoLang('en')" style="padding:8px 20px; border:none; background:transparent; font-weight:600; font-size:13px; color:#94a3b8; border-radius:8px; cursor:pointer;">🇬🇧 English</button>
</div>
                            
                            <div class="seo-lang-content" data-lang="tr">
                                <div style="margin-bottom:12px;">
                                    <label class="form-label">📌 SEO Başlık (Title) - TR</label>
                                    <input type="text" name="seo_title_tr" id="seo_title_tr" class="form-control-custom">
                                </div>
                                <div style="margin-bottom:12px;">
                                    <label class="form-label">📝 Meta Açıklama (Description) - TR</label>
                                    <textarea name="seo_description_tr" id="seo_description_tr" class="form-control-custom" rows="2"></textarea>
                                </div>
                                <div>
                                    <label class="form-label">🏷️ Anahtar Kelimeler (Keywords) - TR</label>
                                    <input type="text" name="seo_keywords_tr" id="seo_keywords_tr" class="form-control-custom">
                                </div>
                            </div>
                            
                            <div class="seo-lang-content" data-lang="en" style="display:none;">
                                <div style="margin-bottom:12px;">
                                    <label class="form-label">📌 SEO Title - EN</label>
                                    <input type="text" name="seo_title_en" id="seo_title_en" class="form-control-custom">
                                </div>
                                <div style="margin-bottom:12px;">
                                    <label class="form-label">📝 Meta Description - EN</label>
                                    <textarea name="seo_description_en" id="seo_description_en" class="form-control-custom" rows="2"></textarea>
                                </div>
                                <div>
                                    <label class="form-label">🏷️ Keywords - EN</label>
                                    <input type="text" name="seo_keywords_en" id="seo_keywords_en" class="form-control-custom">
                                </div>
                            </div>
                            
                            <div style="margin-top:12px;">
                                <label class="form-label">🖼️ OG Görsel (Sosyal Medya)</label>
                                <input type="text" name="seo_og_image" id="seo_og_image" class="form-control-custom">
                            </div>
                        </div>
                    </div>
                    

                </div>
				                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                        <button type="button" class="btn-cancel-custom" onclick="closeModal('yaziModal')">İptal</button>
                        <button type="button" class="btn-submit" onclick="saveYazi()">Kaydet</button>
                    </div>
				
            </form>
        </div>
    </div>
</div>

<!-- SSS MODAL -->
<div id="sssModal" class="gallery-modal"><div class="modal-dialog-custom" style="max-width: 700px;"><div class="modal-content-custom"><div class="modal-header-custom"><h5 id="sssModalTitle" style="color: white;">SSS Ekle/Düzenle</h5><button onclick="closeModal('sssModal')" style="background: none; border: none; color: white;">✕</button></div><form id="sssForm" style="padding: 25px;"><input type="hidden" name="id" id="sssId"><div><label>Soru</label><input type="text" name="soru" id="sssSoru" class="form-control-custom" required></div><div><label>Cevap</label><textarea name="cevap" id="sssCevap" rows="6" class="form-control-custom" required></textarea></div><div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;"><div><label>Sıra</label><input type="number" name="sira" id="sssSira" class="form-control-custom"></div><div><label>Durum</label><select name="durum" id="sssDurum" class="form-control-custom"><option value="1">Aktif</option><option value="0">Pasif</option></select></div></div><div style="display: flex; justify-content: flex-end; gap: 12px;"><button type="button" class="btn-cancel-custom" onclick="closeModal('sssModal')">İptal</button><button type="submit" class="btn-submit">Kaydet</button></div></form></div></div></div>

<!-- KATEGORİ MODAL -->
<div id="kategoriModal" class="gallery-modal">
    <div class="modal-dialog-custom" style="max-width: 500px;">
        <div class="modal-content-custom">
            <div class="modal-header-custom">
                <h5 id="kategoriModalTitle" style="color: white;">Kategori İşlemleri</h5>
                <button onclick="closeModal('kategoriModal')" style="background: none; border: none; color: white; font-size: 1.2rem;">✕</button>
            </div>
<form id="kategoriForm" style="padding: 25px;" onsubmit="event.preventDefault(); kaydetKategori();">
    <input type="hidden" name="id" id="kategoriId">
    <div class="form-group-custom" style="margin-bottom: 20px;">
        <label><i class="fas fa-search"></i> Düzenlenecek Kategori</label>
        <select id="kategoriSec" class="form-control-custom" onchange="kategoriSecildi()">
            <option value="">-- Yeni Kategori Ekle --</option>
            <?php foreach($kategoriler as $kat): ?>
            <option value="<?php echo $kat['id']; ?>" data-adi="<?php echo htmlspecialchars($kat['kategori_adi']); ?>" data-slug="<?php echo htmlspecialchars($kat['kategori_slug']); ?>" data-ikon="<?php echo $kat['ikon']; ?>" data-sira="<?php echo $kat['sira']; ?>">
                <?php echo htmlspecialchars($kat['kategori_adi']); ?> (Sıra: <?php echo $kat['sira']; ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <hr style="margin: 15px 0; border-color: #e2e8f0;">
    <div class="form-group-custom">
        <label><i class="fas fa-tag"></i> Kategori Adı *</label>
        <input type="text" name="kategori_adi" id="kategoriAdi" class="form-control-custom" required placeholder="Örn: Sağlık">
    </div>
    <div class="form-group-custom">
        <label><i class="fas fa-link"></i> Slug *</label>
        <input type="text" name="kategori_slug" id="kategoriSlug" class="form-control-custom" required placeholder="saglik">
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group-custom">
            <label><i class="fas fa-icons"></i> İkon</label>
            <select name="ikon" id="kategoriIkon" class="form-control-custom">
                <option value="FileText">📄 FileText</option>
                <option value="Sparkles">✨ Sparkles</option>
                <option value="Syringe">💉 Syringe</option>
                <option value="Activity">📈 Activity</option>
                <option value="Shield">🛡️ Shield</option>
                <option value="Smile">😊 Smile</option>
                <option value="Award">🏆 Award</option>
                <option value="HeartPulse">❤️ HeartPulse</option>
            </select>
        </div>
        <div class="form-group-custom">
            <label><i class="fas fa-sort-numeric-down"></i> Sıra</label>
            <input type="number" name="sira" id="kategoriSira" class="form-control-custom" placeholder="Otomatik">
        </div>
    </div>
    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
        <button type="button" id="btnSilKategori" onclick="silSeciliKategori()" style="background: #ef4444; color: white; padding: 10px 20px; border-radius: 40px; border: none; cursor: pointer; display: none;">
            <i class="fas fa-trash"></i> Seçili Kategoriyi Sil
        </button>
        <button type="button" class="btn-cancel-custom" onclick="closeModal('kategoriModal')">İptal</button>
        <!-- Buton tipini button yaptık ve doğrudan kaydet fonksiyonunu bağladık -->
        <button type="button" class="btn-submit" onclick="kaydetKategori()"><i class="fas fa-save"></i> Kaydet</button>
    </div>    
</form>
        </div>
    </div>
</div>

<div id="toastContainer" class="toast-notification"></div>

<script>
// ============================================================
// ========== BLOG YÖNETİMİ - GÜNCEL JS MOTORU ==========
// ============================================================
var CSRF_TOKEN = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
var orijinalBlogResim = '';
var guncelBlogResim = '';
var cropper = null;
var cropperModal = null;

function metniSlugaCevir(metin) {
    var turkceKarakterler = {
        'ç': 'c', 'Ç': 'c',
        'ğ': 'g', 'Ğ': 'g',
        'ı': 'i', 'I': 'i', 'İ': 'i',
        'ö': 'o', 'Ö': 'o',
        'ş': 's', 'Ş': 's',
        'ü': 'u', 'Ü': 'u'
    };
    return metin
        .replace(/[çÇğĞıIİöÖşŞüÜ]/g, function(harf) { return turkceKarakterler[harf] || harf; })
        .toLowerCase().trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast-item ${type}`;
    let icon = type === 'success' ? '<i class="fas fa-check-circle toast-icon"></i>' : '<i class="fas fa-exclamation-circle toast-icon"></i>';
    toast.innerHTML = `${icon}<span class="toast-message">${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.classList.add('slide-out'); setTimeout(() => toast.remove(), 300); }, 3000);
}

function openModal(id) { 
    let m = document.getElementById(id); 
    if(m){ m.style.display = 'flex'; document.body.style.overflow = 'hidden'; } 
}
function closeModal(id) { 
    let m = document.getElementById(id); 
    if(m){ m.style.display = 'none'; document.body.style.overflow = ''; } 
    if (cropper) { cropper.destroy(); cropper = null; }
    if (cropperModal) { cropperModal.remove(); cropperModal = null; }
}

// ========== MODERN ONAY VE SİLME MOTORU ==========
let confirmCallback = null;

function showConfirm(title, message, callback) { 
    const titleEl = document.getElementById('confirmTitle');
    const msgEl = document.getElementById('confirmMessage');
    if(titleEl) titleEl.innerHTML = title; 
    if(msgEl) msgEl.innerHTML = message; 
    confirmCallback = callback; 
    openModal('confirmModal'); 
}

function closeConfirmModal() { 
    closeModal('confirmModal'); 
    confirmCallback = null; 
}

document.addEventListener('DOMContentLoaded', function() {
    const okBtn = document.getElementById('confirmOkBtn');
    if (okBtn) {
        okBtn.onclick = function() { 
            if(confirmCallback) confirmCallback(true); 
            closeConfirmModal(); 
        };
    }
});

function deleteItem(type, id) { 
    let title = 'Silme Onayı', message = 'Bu öğeyi çöp kutusuna taşımak istiyor musunuz?'; 
    if (type == 'yazilar') { 
        title = 'Yazıyı Sil'; 
        message = 'Bu yazıyı çöp kutusuna taşımak istiyor musunuz?'; 
    } else if (type == 'sss') { 
        title = 'SSS Sil'; 
        message = 'Bu soruyu çöp kutusuna taşımak istiyor musunuz?'; 
    } else if (type == 'kategoriler') { 
        title = 'Kategori Sil'; 
        message = 'Bu kategoriyi çöp kutusuna taşımak istiyor musunuz?'; 
    } else if (type == 'aboneler') { 
        title = 'Abone Sil'; 
        message = 'Bu aboneyi kalıcı olarak silmek istiyor musunuz?'; 
    } 
    
    showConfirm(title, message, (confirmed) => { 
        if (confirmed) { 
            let url = '/admin/modules/blog/ajax.php?ajax=delete&sub=' + type + '&id=' + id; 
            if (type != 'aboneler') {
                url += '&to_cop=1';
            }
            url += '&csrf_token=' + CSRF_TOKEN;

            fetch(url)
                .then(r => r.json())
                .then(d => { 
                    if (d.success) { 
                        showToast('✅ İşlem başarılı', 'success'); 
                        setTimeout(() => location.reload(), 500); 
                    } else { 
                        showToast('❌ Hata: ' + (d.message || 'Bilinmeyen hata'), 'error'); 
                    } 
                })
                .catch(err => {
                    showToast('❌ Bağlantı hatası', 'error');
                }); 
        } 
    }); 
}
document.getElementById('confirmOkBtn').onclick = function() { 
    if(confirmCallback) confirmCallback(true); 
    closeConfirmModal(); 
};

function switchTab(tabId) {
    document.querySelectorAll('.tab-pane').forEach(function(pane) {
        pane.classList.remove('active');
        pane.style.display = 'none';
    });
    var target = document.getElementById(tabId);
    if (target) {
        target.classList.add('active');
        target.style.display = 'block';
    }
    document.querySelectorAll('.modal-tab').forEach(function(btn) {
        btn.classList.remove('active');
        if (btn.getAttribute('data-tab') === tabId) {
            btn.classList.add('active');
        }
    });
}

function openYaziModal(id = null) { 
    document.getElementById('yaziForm').reset(); 
    document.getElementById('yaziId').value = ''; 
    resmiKaldirBlog();
    switchTab('tab1');
    
    if (id) { 
        fetch('/admin/modules/blog/ajax.php?ajax=get&sub=yazilar&id=' + id)
            .then(res => res.json())
            .then(d => { 
                if (d.success) { 
                    var g = d.data; 
                    document.getElementById('yaziId').value = g.id || ''; 
                    document.getElementById('yaziBaslik').value = g.baslik || ''; 
                    document.getElementById('yaziSlug').value = g.slug || ''; 
                    document.getElementById('yaziKategori').value = g.kategori || ''; 
                    document.getElementById('yaziYazar').value = g.yazar || 'Prof. Dr. İbrahim Duran'; 
                    document.getElementById('yaziUnvan').value = g.yazar_unvan || ''; 
                    document.getElementById('yaziOzet').value = g.ozet || ''; 
                    document.getElementById('yaziIcerik').value = g.icerik || ''; 
                    document.getElementById('yaziDurum').value = g.durum || 1; 
                    
                    if (g.resim) {
                        guncelBlogResim = g.resim;
                        orijinalBlogResim = g.resim;
                        document.getElementById('yaziResim').value = g.resim;
                        document.getElementById('blogImg').src = g.resim;
                        document.getElementById('blogUrlInput').value = g.resim;
                        document.getElementById('blogOnizleme').classList.remove('hidden');
                        document.getElementById('blogYukleAlan').classList.add('hidden');
                        document.getElementById('blogCropContainer').classList.remove('hidden');
                    }
                    
                    document.getElementById('seo_title_tr').value = g.seo_title || '';
                    document.getElementById('seo_description_tr').value = g.seo_description || '';
                    document.getElementById('seo_keywords_tr').value = g.seo_keywords || '';
                    document.getElementById('seo_title_en').value = g.seo_title_en || '';
                    document.getElementById('seo_description_en').value = g.seo_description_en || '';
                    document.getElementById('seo_keywords_en').value = g.seo_keywords_en || '';
                    document.getElementById('seo_og_image').value = g.seo_og_image || g.resim || '';
                    
                    document.getElementById('yaziModalTitle').innerHTML = '✏️ Yazı Düzenle';
                }
            }); 
    } else { 
        document.getElementById('yaziModalTitle').innerHTML = '➕ Yeni Yazı Ekle';
    }
    openModal('yaziModal'); 
}

// ========== GELİŞMİŞ GÖRSEL YÖNETİMİ ==========
function resimYukleBlog(file) {
    if (!file) return;
    if (file.size > 20 * 1024 * 1024) {
        showToast('Dosya çok büyük! Max 20MB.', 'error');
        return;
    }
    showToast('Resim yükleniyor...', 'info');
    var fd = new FormData();
    fd.append('resim', file);
    fd.append('csrf_token', CSRF_TOKEN);

    fetch('/admin/modules/blog/ajax.php?ajax=upload', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            guncelBlogResim = data.url;
            if (!orijinalBlogResim) orijinalBlogResim = data.url;
            document.getElementById('yaziResim').value = data.url;
            document.getElementById('seo_og_image').value = data.url;
            document.getElementById('blogImg').src = data.url + '?v=' + Date.now();
            document.getElementById('blogUrlInput').value = data.url;
            document.getElementById('blogOnizleme').classList.remove('hidden');
            document.getElementById('blogYukleAlan').classList.add('hidden');
            document.getElementById('blogCropContainer').classList.remove('hidden');
            document.getElementById('blogOrijinalButon').classList.remove('hidden');
            showToast('✅ Kapak resmi yüklendi!', 'success');
        } else {
            showToast('❌ Hata: ' + data.message, 'error');
        }
    });
}

function resimUrlIleEkleBlog() {
    var url = document.getElementById('blogUrlInput').value.trim();
    if (!url) { showToast('⚠️ URL girin!', 'warning'); return; }
    showToast('⏳ Resim indiriliyor...', 'info');
    var fd = new FormData();
    fd.append('resim_url', url);
    fd.append('csrf_token', CSRF_TOKEN);

    fetch('/admin/modules/blog/ajax.php?ajax=upload', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            guncelBlogResim = result.url;
            if (!orijinalBlogResim) orijinalBlogResim = result.url;
            document.getElementById('yaziResim').value = result.url;
            document.getElementById('seo_og_image').value = result.url;
            document.getElementById('blogImg').src = result.url + '?v=' + Date.now();
            document.getElementById('blogUrlInput').value = result.url;
            document.getElementById('blogOnizleme').classList.remove('hidden');
            document.getElementById('blogYukleAlan').classList.add('hidden');
            document.getElementById('blogCropContainer').classList.remove('hidden');
            document.getElementById('blogOrijinalButon').classList.remove('hidden');
            showToast('✅ Resim başarıyla indirildi!', 'success');
        } else {
            showToast('❌ Hata: ' + result.message, 'error');
        }
    });
}

function resmiKaldirBlog() {
    document.getElementById('blogOnizleme')?.classList.add('hidden');
    document.getElementById('blogYukleAlan')?.classList.remove('hidden');
    document.getElementById('blogCropContainer')?.classList.add('hidden');
    if (document.getElementById('yaziResim')) document.getElementById('yaziResim').value = '';
    if (document.getElementById('blogImg')) document.getElementById('blogImg').src = '';
    if (document.getElementById('blogDosyaInput')) document.getElementById('blogDosyaInput').value = '';
    if (document.getElementById('blogUrlInput')) document.getElementById('blogUrlInput').value = '';
    guncelBlogResim = '';
    orijinalBlogResim = '';
}

function resmiOrijinalHalGetirBlog() {
    if (orijinalBlogResim) {
        guncelBlogResim = orijinalBlogResim;
        document.getElementById('blogImg').src = orijinalBlogResim;
        document.getElementById('yaziResim').value = orijinalBlogResim;
        document.getElementById('blogUrlInput').value = orijinalBlogResim;
        document.getElementById('seo_og_image').value = orijinalBlogResim;
        showToast('🔄 Orijinal resme dönüldü!', 'info');
    }
}

function handleDropBlog(e) {
    const files = e.dataTransfer.files;
    if (files.length > 0) resimYukleBlog(files[0]);
}

function loadCropper() {
    return new Promise(function(resolve) {
        if (typeof Cropper !== 'undefined') { resolve(); return; }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css';
        document.head.appendChild(link);
        var script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js';
        script.onload = resolve;
        document.head.appendChild(script);
    });
}

async function cropBlog() {
    var resimKaynagi = guncelBlogResim || document.getElementById('blogImg').src;
    if (!resimKaynagi) { showToast('⚠️ Lütfen önce bir resim yükleyin!', 'warning'); return; }
    await loadCropper();
    if (cropperModal) { cropperModal.remove(); cropperModal = null; }
    if (cropper) { cropper.destroy(); cropper = null; }

    cropperModal = document.createElement('div');
    cropperModal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:999999;display:flex;align-items:center;justify-content:center;padding:20px;';
    cropperModal.innerHTML = `
        <div style="background:white;border-radius:20px;max-width:850px;width:100%;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,0.5);">
            <div style="padding:16px 24px;background:#1e293b;color:white;display:flex;justify-content:space-between;align-items:center;">
                <h4 style="margin:0;">✂️ Blog Görselini Kırp</h4>
                <button onclick="closeCropper()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">✕</button>
            </div>
            <div style="padding:20px;background:#f1f5f9;">
                <div style="max-height:450px;display:flex;align-items:center;justify-content:center;">
                    <img id="cropImageTarget" src="${resimKaynagi}" style="max-width:100%;max-height:400px;">
                </div>
            </div>
            <div style="padding:16px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:12px;">
                <button onclick="closeCropper()" style="padding:8px 20px;border:none;border-radius:8px;cursor:pointer;">İptal</button>
                <button onclick="applyCropBlog()" style="padding:8px 24px;background:#2563eb;color:white;border:none;border-radius:8px;font-weight:bold;cursor:pointer;">✅ Kes ve Kaydet</button>
            </div>
        </div>
    `;
    document.body.appendChild(cropperModal);
    var cropImg = document.getElementById('cropImageTarget');
    cropImg.onload = function() {
        cropper = new Cropper(cropImg, { aspectRatio: 16/9, viewMode: 1 });
    };
}

function closeCropper() {
    if (cropper) { cropper.destroy(); cropper = null; }
    if (cropperModal) { cropperModal.remove(); cropperModal = null; }
}

function applyCropBlog() {
    if (!cropper) return;
    var canvas = cropper.getCroppedCanvas({ width: 1200, height: 675 });
    canvas.toBlob(function(blob) {
        var formData = new FormData();
        formData.append('resim', blob, 'cropped_' + Date.now() + '.webp');
        formData.append('csrf_token', CSRF_TOKEN);
        fetch('/admin/modules/blog/ajax.php?ajax=upload', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                guncelBlogResim = data.url;
                document.getElementById('yaziResim').value = data.url;
                document.getElementById('blogImg').src = data.url + '?v=' + Date.now();
                document.getElementById('blogUrlInput').value = data.url;
                document.getElementById('seo_og_image').value = data.url;
                showToast('✅ Görsel kırpıldı ve kaydedildi!', 'success');
                closeCropper();
            }
        });
    }, 'image/webp', 0.85);
}

// ========== ANA BLOG SEO DİL DEĞİŞTİRME ==========
function switchBlogAnaSeoLang(lang) {
    document.querySelectorAll('.blog-seo-lang-btn').forEach(function(b) {
        b.classList.remove('active');
        b.style.color = '#94a3b8';
        b.style.borderBottom = '2px solid transparent';
    });
    var activeBtn = document.querySelector('.blog-seo-lang-btn[data-lang="' + lang + '"]');
    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.color = '#3b82f6';
        activeBtn.style.borderBottom = '2px solid #3b82f6';
    }
    document.querySelectorAll('.blog-seo-lang-content').forEach(function(content) {
        content.style.display = (content.getAttribute('data-lang') === lang) ? 'block' : 'none';
    });
}

// ========== YAZI ÖZEL SEO DİL DEĞİŞTİRME ==========
function switchYaziSeoLang(lang) {
    document.querySelectorAll('#yaziModal .seo-lang-btn').forEach(function(b) {
        b.classList.remove('active');
        b.style.background = 'transparent';
        b.style.color = '#94a3b8';
        b.style.boxShadow = 'none';
    });
    var activeBtn = document.querySelector('#yaziModal .seo-lang-btn[data-lang="' + lang + '"]');
    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.background = 'white';
        activeBtn.style.color = '#3b82f6';
        activeBtn.style.boxShadow = '0 1px 4px rgba(0,0,0,0.06)';
    }
    document.querySelectorAll('#yaziModal .seo-lang-content').forEach(function(content) {
        content.style.display = (content.getAttribute('data-lang') === lang) ? 'block' : 'none';
    });
}

function switchBlogSeoLang(lang) {
    // 1. Butonların aktiflik stillerini güncelle
    document.querySelectorAll('.seo-lang-btn').forEach(function(b) {
        b.classList.remove('active');
        b.style.background = 'transparent';
        b.style.color = '#94a3b8';
        b.style.boxShadow = 'none';
    });

    // Tıklanan butonu aktif yap
    var activeBtn = document.querySelector('.seo-lang-btn[data-lang="' + lang + '"]');
    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.background = 'white';
        activeBtn.style.color = '#3b82f6';
        activeBtn.style.boxShadow = '0 1px 4px rgba(0,0,0,0.06)';
    }

    // 2. TR / EN içerik alanlarını göster/gizle
    document.querySelectorAll('.seo-lang-content').forEach(function(content) {
        if (content.getAttribute('data-lang') === lang) {
            content.style.display = 'block';
        } else {
            content.style.display = 'none';
        }
    });
}

function dondurResimBlog(yon) {
    var mevcutResim = document.getElementById('yaziResim').value;
    if (!mevcutResim) { showToast('⚠️ Lütfen önce bir resim yükleyin!', 'warning'); return; }
    showToast('⏳ Resim döndürülüyor...', 'info');

    fetch('/admin/modules/tedaviler/index.php?islem=resim_dondur', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: mevcutResim, yon: yon, csrf_token: CSRF_TOKEN })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            guncelBlogResim = res.url;
            document.getElementById('yaziResim').value = res.url;
            document.getElementById('blogImg').src = res.url + '?v=' + Date.now();
            document.getElementById('blogUrlInput').value = res.url;
            document.getElementById('seo_og_image').value = res.url;
            showToast('✅ Resim döndürüldü!', 'success');
        }
    });
}



function saveYazi() {
    var form = document.getElementById('yaziForm');
    var fd = new FormData(form);
    fd.append('csrf_token', CSRF_TOKEN);
    
    var btn = document.querySelector('.btn-submit');
    var originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    btn.disabled = true;
    
    fetch('/admin/modules/blog/ajax.php?ajax=save&sub=yazilar', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(d => { 
        if (d.success) { 
            showToast('✅ ' + d.message, 'success'); 
            closeModal('yaziModal'); 
            setTimeout(() => location.reload(), 800); 
        } else { 
            showToast('❌ Hata: ' + d.message, 'error'); 
            btn.innerHTML = originalText;
            btn.disabled = false;
        } 
    })
    .catch(() => {
        showToast('❌ Bağlantı hatası', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function uploadImage(targetId) {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        var file = e.target.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('resim', file);
        fd.append('csrf_token', CSRF_TOKEN);
        showToast('Resim yükleniyor...', 'info');
        fetch('/admin/modules/blog/ajax.php?ajax=upload', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById(targetId).value = data.url;
                showToast('✅ Resim yüklendi', 'success');
            } else {
                showToast('❌ Hata: ' + data.message, 'error');
            }
        })
        .catch(() => showToast('❌ Bağlantı hatası', 'error'));
    };
    input.click();
}

function blogSeoResimYukle() {
    uploadImage('blog_seo_og_image');
}

document.getElementById('blogSeoForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('blogSeoKaydetBtn');
    var originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    btn.disabled = true;

    var formData = new FormData(this);
    var jsonData = {};
    formData.forEach((value, key) => { jsonData[key] = value; });

    fetch('/admin/modules/blog/ajax.php?ajax=seo_kaydet', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(jsonData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('✅ ' + data.message, 'success');
        } else {
            showToast('❌ Hata: ' + data.message, 'error');
        }
    })
    .catch(err => {
        showToast('❌ Bağlantı hatası oluştu', 'error');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});

function openSssModal(id=null){ 
    document.getElementById('sssForm').reset(); 
    document.getElementById('sssId').value=''; 
    if(id){ 
        fetch('/admin/modules/blog/ajax.php?ajax=get&sub=sss&id='+id)
            .then(r=>r.json())
            .then(d=>{ 
                if(d.success){ 
                    let s=d.data; 
                    document.getElementById('sssId').value=s.id; 
                    document.getElementById('sssSoru').value=s.soru; 
                    document.getElementById('sssCevap').value=s.cevap; 
                    document.getElementById('sssSira').value=s.sira; 
                    document.getElementById('sssDurum').value=s.durum; 
                } 
            }); 
    } else { 
        fetch('/admin/modules/blog/ajax.php?ajax=son_sira_sss')
            .then(r=>r.json())
            .then(d=>{ 
                if(d.success) document.getElementById('sssSira').value=d.sira; 
            }); 
    } 
    openModal('sssModal'); 
}

document.getElementById('sssForm')?.addEventListener('submit', function(e){ 
    e.preventDefault(); 
    let fd=new FormData(this); 
    fd.append('csrf_token', CSRF_TOKEN); 
    fetch('/admin/modules/blog/ajax.php?ajax=save&sub=sss',{
        method:'POST',
        body:fd
    }).then(r=>r.json()).then(d=>{ 
        if(d.success){ 
            showToast('✅ SSS kaydedildi', 'success'); 
            closeModal('sssModal'); 
            setTimeout(()=>location.reload(),500); 
        } else { 
            showToast('❌ Hata: '+d.message,'error'); 
        } 
    }); 
});

function kategoriSecildi() {
    const sec = document.getElementById('kategoriSec');
    const secilenId = sec.value;
    const kategoriId = document.getElementById('kategoriId');
    const kategoriAdi = document.getElementById('kategoriAdi');
    const kategoriSlug = document.getElementById('kategoriSlug');
    const kategoriIkon = document.getElementById('kategoriIkon');
    const kategoriSira = document.getElementById('kategoriSira');
    const modalTitle = document.getElementById('kategoriModalTitle');
    const btnSil = document.getElementById('btnSilKategori');
    
    if (secilenId && secilenId != '') {
        const secilenOption = sec.options[sec.selectedIndex];
        kategoriId.value = secilenId;
        kategoriAdi.value = secilenOption.getAttribute('data-adi') || '';
        kategoriSlug.value = secilenOption.getAttribute('data-slug') || '';
        kategoriIkon.value = secilenOption.getAttribute('data-ikon') || 'FileText';
        kategoriSira.value = secilenOption.getAttribute('data-sira') || '';
        modalTitle.innerHTML = '✏️ Kategori Düzenle: ' + (secilenOption.getAttribute('data-adi') || '');
        if (btnSil) btnSil.style.display = 'inline-flex';
    } else {
        kategoriId.value = '';
        kategoriAdi.value = '';
        kategoriSlug.value = '';
        kategoriIkon.value = 'FileText';
        kategoriSira.value = '';
        modalTitle.innerHTML = '➕ Yeni Kategori Ekle';
        if (btnSil) btnSil.style.display = 'none';
        fetch('/admin/modules/blog/ajax.php?ajax=son_sira_kategori')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('kategoriSira').value = data.sira;
                }
            })
            .catch(() => {
                document.getElementById('kategoriSira').value = 1;
            });
    }
}

function silSeciliKategori() {
    const kategoriSec = document.getElementById('kategoriSec');
    const secilenId = kategoriSec ? kategoriSec.value : null;
    const secilenOption = kategoriSec ? kategoriSec.options[kategoriSec.selectedIndex] : null;
    const kategoriAdi = secilenOption ? secilenOption.getAttribute('data-adi') : '';
    
    if (!secilenId || secilenId == '') {
        showToast('Lütfen silmek için bir kategori seçin', 'error');
        return;
    }
    
    if (confirm(`"${kategoriAdi}" kategorisini çöp kutusuna taşımak istediğinize emin misiniz?`)) {
        fetch('/admin/modules/blog/ajax.php?ajax=delete&sub=kategoriler&id=' + secilenId + '&to_cop=1')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('✅ Kategori çöp kutusuna taşındı', 'success');
                    closeModal('kategoriModal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast('❌ Silme hatası: ' + (data.message || 'Bilinmeyen hata'), 'error');
                }
            });
    }
}

function openKategoriModal() {
    const form = document.getElementById('kategoriForm');
    if (form) form.reset();
    const kategoriSec = document.getElementById('kategoriSec');
    if (kategoriSec) kategoriSec.value = '';
    const kategoriId = document.getElementById('kategoriId');
    if (kategoriId) kategoriId.value = '';
    const modalTitle = document.getElementById('kategoriModalTitle');
    if (modalTitle) modalTitle.innerHTML = '➕ Yeni Kategori Ekle';
    const btnSil = document.getElementById('btnSilKategori');
    if (btnSil) btnSil.style.display = 'none';
    
    fetch('/admin/modules/blog/ajax.php?ajax=son_sira_kategori')
        .then(res => res.json())
        .then(data => {
            if (data.success && document.getElementById('kategoriSira')) {
                document.getElementById('kategoriSira').value = data.sira;
            }
        });
    openModal('kategoriModal');
}

function kaydetKategori() {
    var form = document.getElementById('kategoriForm');
    var secilenId = document.getElementById('kategoriSec').value;
    if (secilenId && secilenId != '') {
        document.getElementById('kategoriId').value = secilenId;
    }
    
    var fd = new FormData(form);
    fd.append('csrf_token', CSRF_TOKEN);

    fetch('/admin/modules/blog/ajax.php?ajax=save&sub=kategoriler', { 
        method: 'POST', 
        body: fd 
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Kategori kaydedildi', 'success');
            closeModal('kategoriModal');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('❌ Hata: ' + (data.message || 'Bilinmeyen hata'), 'error');
        }
    })
    .catch(err => {
        showToast('❌ Bağlantı hatası', 'error');
    });
}

document.getElementById('kategoriForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    let kategoriSec = document.getElementById('kategoriSec');
    let secilenId = kategoriSec ? kategoriSec.value : null;
    let kategoriIdInput = document.getElementById('kategoriId');
    
    if (secilenId && secilenId != '') {
        kategoriIdInput.value = secilenId;
    }
    
    let fd = new FormData(this);
    fd.append('csrf_token', CSRF_TOKEN);
    
    fetch('/admin/modules/blog/ajax.php?ajax=save&sub=kategoriler', { 
        method: 'POST', 
        body: fd 
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Kategori kaydedildi', 'success');
            closeModal('kategoriModal');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('❌ Hata: ' + (data.message || 'Bilinmeyen hata'), 'error');
        }
    })
    .catch(err => showToast('❌ Kayıt hatası: ' + err, 'error'));
});





function openCopPanel(){ 
    let p = document.getElementById('copPanel'); 
    if(p.style.display === 'none'){ 
        p.style.display = 'block'; 
        loadCopList(); 
    } else { 
        p.style.display = 'none'; 
    } 
}
function closeCopPanel(){ document.getElementById('copPanel').style.display='none'; }

function restoreFromCop(id){ 
    fetch('/admin/modules/blog/ajax.php?ajax=restore&sub=yazilar&id='+id)
        .then(r=>r.json())
        .then(d=>{ 
            if(d.success){ 
                showToast('Yazı geri alındı', 'success'); 
                setTimeout(()=>location.reload(),500); 
            } 
        }); 
}
function permanentDelete(id){ 
    if(confirm('Kalıcı olarak silmek istediğinize emin misiniz?')){ 
        fetch('/admin/modules/blog/ajax.php?ajax=permanent_delete&sub=yazilar&id='+id)
            .then(r=>r.json())
            .then(d=>{ 
                if(d.success){ 
                    showToast('Kalıcı silindi', 'success'); 
                    setTimeout(()=>location.reload(),500); 
                } 
            }); 
    } 
}
function emptyCopPanel(){ 
    if(confirm('Tüm yazıları kalıcı silmek istiyor musunuz?')){ 
        fetch('/admin/modules/blog/ajax.php?ajax=empty_cop&sub=yazilar')
            .then(r=>r.json())
            .then(d=>{ 
                if(d.success){ 
                    showToast('Çöp kutusu temizlendi', 'success'); 
                    setTimeout(()=>location.reload(),500); 
                } 
            }); 
    } 
}

function openSssCopPanel(){ 
    let p = document.getElementById('copSssPanel'); 
    if(!p) return; 
    if(p.style.display === 'none'){ 
        p.style.display = 'block'; 
        loadSssCopList(); 
    } else { 
        p.style.display = 'none'; 
    } 
}
function closeSssCopPanel(){ document.getElementById('copSssPanel').style.display='none'; }
function loadSssCopList() { 
    fetch('/admin/modules/blog/ajax.php?ajax=sss_cop_listesi')
        .then(res => res.json())
        .then(data => { 
            let c = document.getElementById('copSssPanelListesi'); 
            let sayiSpan = document.getElementById('copSssPanelSayi'); 
            let sayiToplam = document.getElementById('copSssSayisi'); 
            
            if (data.success && data.data && data.data.length > 0) { 
                if (sayiSpan) sayiSpan.innerHTML = data.data.length; 
                if (sayiToplam) sayiToplam.innerHTML = data.data.length; 
                
                let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 24px;">'; 
                data.data.forEach(i => { 
                    html += `
                        <div style="background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 20px;">
                            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 12px;">${escapeHtml(i.soru)}</h3>
                            <div style="display: flex; gap: 12px;">
                                <button onclick="restoreSssFromCop(${i.id})" style="flex: 1; background: #10b981; color: white; border: none; padding: 10px; border-radius: 12px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-undo"></i> Geri Al</button>
                                <button onclick="permanentDeleteSss(${i.id})" style="flex: 1; background: #ef4444; color: white; border: none; padding: 10px; border-radius: 12px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-trash"></i> Kalıcı Sil</button>
                            </div>
                        </div>
                    `; 
                }); 
                html += '</div>'; 
                c.innerHTML = html; 
            } else { 
                c.innerHTML = '<div style="text-align:center; padding:60px;"><i class="fas fa-trash-alt" style="font-size:4rem; color:#cbd5e1;"></i><p style="margin-top:15px; color:#94a3b8;">SSS çöp kutusu boş</p></div>'; 
                if (sayiSpan) sayiSpan.innerHTML = '0'; 
                if (sayiToplam) sayiToplam.innerHTML = '0'; 
            } 
        }); 
}
function restoreSssFromCop(id){ 
    fetch('/admin/modules/blog/ajax.php?ajax=sss_restore&id='+id)
        .then(r=>r.json())
        .then(d=>{ 
            if(d.success){ 
                showToast('SSS geri alındı', 'success'); 
                setTimeout(()=>location.reload(),500); 
            } 
        }); 
}
function permanentDeleteSss(id){ 
    if(confirm('Kalıcı silmek istiyor musunuz?')){ 
        fetch('/admin/modules/blog/ajax.php?ajax=sss_permanent_delete&id='+id)
            .then(r=>r.json())
            .then(d=>{ 
                if(d.success){ 
                    showToast('Kalıcı silindi', 'success'); 
                    setTimeout(()=>location.reload(),500); 
                } 
            }); 
    } 
}

function openKategoriCopPanel(){ 
    let p = document.getElementById('copKategoriPanel'); 
    if(p.style.display === 'none'){ 
        p.style.display = 'block'; 
        loadKategoriCopList(); 
    } else { 
        p.style.display = 'none'; 
    } 
}
function closeKategoriCopPanel(){ document.getElementById('copKategoriPanel').style.display='none'; }
function loadKategoriCopList() { 
    fetch('/admin/modules/blog/ajax.php?ajax=kategori_cop_listesi')
        .then(res => res.json())
        .then(data => { 
            let c = document.getElementById('copKategoriPanelListesi'); 
            let sayiSpan = document.getElementById('copKategoriPanelSayi'); 
            let sayiToplam = document.getElementById('copKategoriSayisi'); 
            
            if (data.success && data.data && data.data.length > 0) { 
                if (sayiSpan) sayiSpan.innerHTML = data.data.length; 
                if (sayiToplam) sayiToplam.innerHTML = data.data.length; 
                
                let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">'; 
                data.data.forEach(i => { 
                    html += `
                        <div style="background: white; border-radius: 24px; padding: 20px; border: 1px solid #e2e8f0;">
                            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">${escapeHtml(i.kategori_adi)}</h3>
                            <div style="display: flex; gap: 12px;">
                                <button onclick="restoreKategoriFromCop(${i.id})" style="flex: 1; background: #10b981; color: white; border: none; padding: 10px; border-radius: 12px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-undo"></i> Geri Al</button>
                                <button onclick="permanentDeleteKategori(${i.id})" style="flex: 1; background: #ef4444; color: white; border: none; padding: 10px; border-radius: 12px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-trash"></i> Kalıcı Sil</button>
                            </div>
                        </div>
                    `; 
                }); 
                html += '</div>'; 
                c.innerHTML = html; 
            } else { 
                c.innerHTML = '<div style="text-align:center; padding:60px;"><i class="fas fa-trash-alt" style="font-size:4rem; color:#cbd5e1;"></i><p style="margin-top:15px; color:#94a3b8;">Kategori çöp kutusu boş</p></div>'; 
                if (sayiSpan) sayiSpan.innerHTML = '0'; 
                if (sayiToplam) sayiToplam.innerHTML = '0'; 
            } 
        }); 
}
function restoreKategoriFromCop(id){ 
    fetch('/admin/modules/blog/ajax.php?ajax=kategori_restore&id='+id)
        .then(r=>r.json())
        .then(d=>{ 
            if(d.success){ 
                showToast('Kategori geri alındı', 'success'); 
                setTimeout(()=>location.reload(),500); 
            } 
        }); 
}
function permanentDeleteKategori(id){ 
    if(confirm('Kalıcı silmek istiyor musunuz?')){ 
        fetch('/admin/modules/blog/ajax.php?ajax=kategori_permanent_delete&id='+id)
            .then(r=>r.json())
            .then(d=>{ 
                if(d.success){ 
                    showToast('Kalıcı silindi', 'success'); 
                    setTimeout(()=>location.reload(),500); 
                } 
            }); 
    } 
}

function loadCopList() {
    fetch('/admin/modules/blog/ajax.php?ajax=cop_listesi&sub=yazilar')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('copPanelListesi');
            const sayiSpan = document.getElementById('copPanelSayi');
            const sayiToplam = document.getElementById('copSayisiToplam');
            
            if (data.success && data.data && data.data.length > 0) {
                if (sayiSpan) sayiSpan.innerHTML = data.data.length;
                if (sayiToplam) sayiToplam.innerHTML = data.data.length;
                
                let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 24px;">';
                data.data.forEach(item => {
                    html += `
                        <div style="background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 20px;">
                            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 10px;">${escapeHtml(item.baslik)}</h3>
                            <div style="display: flex; gap: 12px;">
                                <button onclick="restoreFromCop(${item.id})" style="flex: 1; background: #10b981; color: white; border: none; padding: 10px; border-radius: 12px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-undo"></i> Geri Al</button>
                                <button onclick="permanentDelete(${item.id})" style="flex: 1; background: #ef4444; color: white; border: none; padding: 10px; border-radius: 12px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-trash"></i> Kalıcı Sil</button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div style="text-align: center; padding: 60px;"><i class="fas fa-trash-alt" style="font-size: 4rem; color: #cbd5e1;"></i><p style="margin-top: 15px; color: #94a3b8;">Çöp kutusu boş</p></div>';
                if (sayiSpan) sayiSpan.innerHTML = '0';
                if (sayiToplam) sayiToplam.innerHTML = '0';
            }
        });
}

// ========== CROOPER ORAN DEĞİŞTİRME ==========
function setCropAspectBlog(w, h) {
    if (cropper) {
        cropper.setAspectRatio(w === 0 ? NaN : w / h);
    }
}

// ========== GELİŞMİŞ SERBEST KIRPMA MODALI ==========
async function cropBlog() {
    var resimKaynagi = guncelBlogResim || document.getElementById('blogImg').src;
    if (!resimKaynagi) { 
        showToast('⚠️ Lütfen önce bir resim yükleyin!', 'warning'); 
        return; 
    }
    await loadCropper();
    if (cropperModal) { cropperModal.remove(); cropperModal = null; }
    if (cropper) { cropper.destroy(); cropper = null; }

    cropperModal = document.createElement('div');
    cropperModal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:999999;display:flex;align-items:center;justify-content:center;padding:20px;';
    cropperModal.innerHTML = `
        <div style="background:white;border-radius:20px;max-width:850px;width:100%;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,0.5);display:flex;flex-direction:column;max-height:95vh;">
            <div style="padding:16px 24px;background:#1e293b;color:white;display:flex;justify-content:space-between;align-items:center;">
                <h4 style="margin:0;font-size:16px;font-weight:700;"><i class="fas fa-crop-alt"></i> Blog Görselini Kırp</h4>
                <button onclick="closeCropper()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">✕</button>
            </div>
            <div style="padding:20px;background:#f1f5f9;overflow:auto;flex:1;">
                <div style="max-height:420px;display:flex;align-items:center;justify-content:center;background:#0f172a;border-radius:12px;overflow:hidden;">
                    <img id="cropImageTarget" src="${resimKaynagi}" style="max-width:100%;max-height:400px;display:block;">
                </div>
                
                <!-- ORAN SEÇİM BUTONLARI -->
                <div style="display:flex; gap:8px; margin-top:14px; justify-content:center; flex-wrap:wrap;">
                    <button type="button" onclick="setCropAspectBlog(16,9)" style="padding:6px 14px; background:white; border:1px solid #cbd5e1; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;">16:9 (Yatay)</button>
                    <button type="button" onclick="setCropAspectBlog(4,3)" style="padding:6px 14px; background:white; border:1px solid #cbd5e1; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;">4:3</button>
                    <button type="button" onclick="setCropAspectBlog(1,1)" style="padding:6px 14px; background:white; border:1px solid #cbd5e1; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;">1:1 (Kare)</button>
                    <button type="button" onclick="setCropAspectBlog(0,0)" style="padding:6px 14px; background:#eff6ff; border:1.5px solid #3b82f6; color:#2563eb; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer;">✂️ Serbest</button>
                </div>
            </div>
            <div style="padding:16px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:12px;">
                <button onclick="closeCropper()" style="padding:8px 20px;border:none;background:#e2e8f0;border-radius:8px;cursor:pointer;font-weight:600;">İptal</button>
                <button onclick="applyCropBlog()" style="padding:8px 24px;background:#2563eb;color:white;border:none;border-radius:8px;font-weight:bold;cursor:pointer;">✅ Kes ve Kaydet</button>
            </div>
        </div>
    `;
    document.body.appendChild(cropperModal);
    var cropImg = document.getElementById('cropImageTarget');
    cropImg.onload = function() {
        cropper = new Cropper(cropImg, { 
            aspectRatio: NaN, // Varsayılan serbest başlasın
            viewMode: 1,
            autoCropArea: 0.9
        });
    };
}

function escapeHtml(str){ 
    if(!str) return ''; 
    return str.replace(/[&<>]/g,function(m){ 
        if(m==='&') return '&amp;'; 
        if(m==='<') return '&lt;'; 
        if(m==='>') return '&gt;'; 
        return m; 
    }); 
}



// ========== BAŞLANGIÇ ==========
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.gallery-modal').forEach(m=>m.style.display='none');
    
    // Modal tab'ları bağla
    document.querySelectorAll('.modal-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            switchTab(this.getAttribute('data-tab'));
        });
    });

    // 1. ANA BLOG SEO SAYFASI DİL TABLARI (TR / EN)
    document.querySelectorAll('.blog-seo-lang-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var lang = this.getAttribute('data-lang');
            document.querySelectorAll('.blog-seo-lang-btn').forEach(function(b) {
                b.classList.remove('active');
                b.style.color = '#94a3b8';
                b.style.borderBottom = '2px solid transparent';
            });
            this.classList.add('active');
            this.style.color = '#3b82f6';
            this.style.borderBottom = '2px solid #3b82f6';
            
            document.querySelectorAll('.blog-seo-lang-content').forEach(function(content) {
                content.style.display = (content.getAttribute('data-lang') === lang) ? 'block' : 'none';
            });
        });
    });

    // 2. YAZI EKLE / DÜZENLE MODALI İÇİNDEKİ SEO DİL TABLARI (TR / EN)
    document.querySelectorAll('.seo-lang-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var lang = this.getAttribute('data-lang');
            document.querySelectorAll('.seo-lang-btn').forEach(function(b) {
                b.classList.remove('active');
                b.style.background = 'transparent';
                b.style.color = '#94a3b8';
            });
            this.classList.add('active');
            this.style.background = 'white';
            this.style.color = '#3b82f6';
            this.style.boxShadow = '0 2px 6px rgba(0,0,0,0.05)';
            
            document.querySelectorAll('.seo-lang-content').forEach(function(content) {
                content.style.display = (content.getAttribute('data-lang') === lang) ? 'block' : 'none';
            });
        });
    });

    var yaziBaslikInput = document.getElementById('yaziBaslik');
    var yaziSlugInput = document.getElementById('yaziSlug');
    var yaziIdInput = document.getElementById('yaziId');

    if (yaziBaslikInput && yaziSlugInput) {
        yaziBaslikInput.addEventListener('input', function() {
            if (!yaziIdInput.value) { 
                yaziSlugInput.value = metniSlugaCevir(this.value);
            }
        });
    }

    var kategoriAdiInput = document.getElementById('kategoriAdi');
    var kategoriSlugInput = document.getElementById('kategoriSlug');
    var kategoriIdInput = document.getElementById('kategoriId');

    if (kategoriAdiInput && kategoriSlugInput) {
        kategoriAdiInput.addEventListener('input', function() {
            if (!kategoriIdInput.value) { 
                kategoriSlugInput.value = metniSlugaCevir(this.value);
            }
        });
    }
});
</script>
</body>
</html>