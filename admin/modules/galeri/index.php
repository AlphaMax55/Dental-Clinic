<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();
yetkiKontrol('galeri', 'gorebilir');

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ============= MEDYA KOLONLARI VAR MI? =============
$kolon = $db->query("SHOW COLUMNS FROM galeri_resimler LIKE 'medya_tipi'")->fetch();
if (!$kolon) {
    try {
        $db->exec("ALTER TABLE galeri_resimler ADD COLUMN medya_tipi ENUM('resim','video') DEFAULT 'resim' AFTER kategori_id");
        $db->exec("ALTER TABLE galeri_resimler ADD COLUMN video_url VARCHAR(500) DEFAULT NULL AFTER resim_url");
    } catch(Exception $e) {}
}

$kolon_sure = $db->query("SHOW COLUMNS FROM galeri_resimler LIKE 'video_sure'")->fetch();
if (!$kolon_sure) {
    try {
        $db->exec("ALTER TABLE galeri_resimler ADD COLUMN video_sure VARCHAR(20) DEFAULT NULL AFTER video_url");
    } catch(Exception $e) {}
}

// ============= KATEGORİ MEDYA TİPİ KOLONU VAR MI? =============
$kolon_kat_medya = $db->query("SHOW COLUMNS FROM galeri_kategoriler LIKE 'medya_tipi'")->fetch();
if (!$kolon_kat_medya) {
    try {
        $db->exec("ALTER TABLE galeri_kategoriler ADD COLUMN medya_tipi ENUM('hepsi','resim','video') DEFAULT 'hepsi' AFTER ikon");
    } catch(Exception $e) {}
}

// ============= AKTİF TAB =============
$aktif_tip = isset($_GET['tip']) && $_GET['tip'] === 'video' ? 'video' : 'resim';

// ============= ANA SAYFA VERİLERİ =============
$kategoriler = $db->query("SELECT * FROM galeri_kategoriler ORDER BY sira ASC")->fetchAll();
$tum_medya = $db->query("SELECT r.*, k.kategori_adi FROM galeri_resimler r LEFT JOIN galeri_kategoriler k ON r.kategori_id = k.id WHERE r.silindi = 0 ORDER BY r.sira ASC, r.created_at DESC")->fetchAll();
$resimler = array_values(array_filter($tum_medya, fn($x) => ($x['medya_tipi'] ?? 'resim') === $aktif_tip));

$toplam_resim = count(array_filter($tum_medya, fn($x) => ($x['medya_tipi'] ?? 'resim') === 'resim'));
$toplam_video = count(array_filter($tum_medya, fn($x) => ($x['medya_tipi'] ?? 'resim') === 'video'));
$aktif_sayisi = 0;
foreach ($resimler as $r) if ($r['durum'] == 1) $aktif_sayisi++;
$cop_kutusu = $db->query("SELECT COUNT(*) FROM galeri_resimler WHERE silindi = 1")->fetchColumn();

// ============= SEO AYARLARINI ÇEK =============
$seo_title_tr = ''; $seo_title_en = '';
$seo_description_tr = ''; $seo_description_en = '';
$seo_keywords_tr = ''; $seo_keywords_en = '';
$seo_og_image = ''; $seo_canonical = '';

try {
    $stmt = $db->query("SELECT anahtar, deger FROM galeri_ayarlar");
    while($row = $stmt->fetch()) {
        $k = $row['anahtar']; $v = $row['deger'];
        if(isset($$k)) $$k = $v;
    }
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Galeri Yönetimi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; }
        .galeri-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 28px; }
        .stat-card { background: white; border-radius: 20px; padding: 16px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .stat-card .value { font-size: 26px; font-weight: 800; }
        .stat-card .label { font-size: 10px; color: #94a3b8; margin-top: 4px; letter-spacing: 0.5px; }
        .stat-card .icon-box { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .stat-card .icon-box i { font-size: 1.1rem; }
        
        .medya-tabs { display: flex; gap: 8px; margin-bottom: 20px; background: white; padding: 8px; border-radius: 60px; border: 1px solid #e2e8f0; max-width: fit-content; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .medya-tab { display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; border-radius: 40px; font-size: 14px; font-weight: 700; text-decoration: none; transition: all 0.2s; color: #64748b; background: transparent; }
        .medya-tab:hover { background: #f1f5f9; color: #1e293b; }
        .medya-tab.active { color: white; }
        .medya-tab.active.resim { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 6px 16px rgba(37,99,235,0.3); }
        .medya-tab.active.video { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); box-shadow: 0 6px 16px rgba(220,38,38,0.3); }
        .medya-tab .badge { padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 800; background: rgba(0,0,0,0.08); }
        .medya-tab.active .badge { background: rgba(255,255,255,0.25); }
        
        .kategori-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; align-items: center; }
        .kategori-tab-btn { padding: 8px 18px; border: 1px solid #cbd5e1; background: white; color: #475569; border-radius: 40px; font-size: 0.8rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .kategori-tab-btn:hover { background: #f1f5f9; }
        .kategori-tab-btn.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; }
        .kategori-tab-btn .badge { background: #e2e8f0; padding: 2px 8px; border-radius: 30px; margin-left: 6px; font-size: 0.7rem; }
        .kategori-tab-btn.active .badge { background: rgba(255,255,255,0.2); color: white; }
        
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
        .gallery-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; transition: all 0.3s; }
        .gallery-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
        .gallery-card-img { height: 200px; overflow: hidden; position: relative; background: #f1f5f9; }
        .gallery-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-card-badge { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); padding: 4px 10px; border-radius: 20px; font-size: 10px; color: white; }
        
        .video-play-overlay { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(15,23,42,0.15); pointer-events: none; }
        .video-play-btn { width: 60px; height: 60px; border-radius: 50%; background: rgba(255,255,255,0.95); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,0.35); }
        .video-play-btn svg { width: 26px; height: 26px; fill: #dc2626; margin-left: 3px; }
        
        .tip-rozet { position: absolute; top: 10px; left: 10px; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; color: white; text-transform: uppercase; letter-spacing: 0.05em; backdrop-filter: blur(6px); }
        .tip-rozet.resim { background: rgba(37,99,235,0.9); }
        .tip-rozet.video { background: rgba(220,38,38,0.9); }
        
        .gallery-card-content { padding: 12px; }
        .gallery-card-title { font-weight: 700; font-size: 0.9rem; color: #1e293b; display: flex; justify-content: space-between; align-items: center; }
        .gallery-card-cat { font-size: 0.7rem; color: #667eea; margin-bottom: 8px; }
        .gallery-card-stats { display: flex; gap: 15px; margin-top: 8px; font-size: 0.75rem; color: #64748b; }
        .gallery-card-actions { display: flex; gap: 8px; padding: 10px 12px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        .btn-action { flex: 1; padding: 8px; border: none; border-radius: 10px; font-size: 0.7rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-edit { background: #eff6ff; color: #2563eb; }
        .btn-edit:hover { background: #2563eb; color: white; }
        .btn-delete { background: #fef2f2; color: #dc2626; }
        .btn-delete:hover { background: #dc2626; color: white; }
        
        .section-header { display: flex; justify-content: space-between; align-items: center; margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
        .section-header h4 { font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .btn-add { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 20px; border-radius: 40px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-add:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 20px; border: 1px solid #e2e8f0; }
        .empty-state i { font-size: 4rem; color: #cbd5e1; margin-bottom: 15px; display: block; }
        
        .gallery-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 9999; overflow: auto; }
        .modal-dialog-custom { margin: 30px auto; max-width: 1100px; width: 90%; }
        .modal-content-custom { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 32px; overflow: hidden; }
        .modal-header-custom { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .modal-header-custom h5 { color: white; margin: 0; }
        .modal-header-custom button { background: rgba(255,255,255,0.1); border: none; width: 34px; height: 34px; border-radius: 50%; color: white; cursor: pointer; }
        .two-columns { display: flex; flex-wrap: wrap; }
        .form-col { flex: 0 0 60%; padding: 1.5rem; border-right: 1px solid #e2e8f0; }
        .preview-col { flex: 0 0 40%; background: #f1f5f9; padding: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { font-weight: 600; color: #1e293b; font-size: 0.75rem; margin-bottom: 0.25rem; display: block; }
        .form-control { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; transition: all 0.2s; font-family: inherit; }
        .form-control:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .upload-btn { background: #f1f5f9; padding: 10px 16px; border-radius: 12px; cursor: pointer; font-size: 0.75rem; border: 1px solid #e2e8f0; display: inline-block; }
        .upload-btn:hover { background: #e2e8f0; }
        .preview-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .preview-img { height: 200px; width: 100%; object-fit: cover; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn-submit { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px 28px; border-radius: 40px; border: none; font-weight: 600; cursor: pointer; }
        .btn-cancel { background: #e2e8f0; padding: 10px 28px; border-radius: 40px; border: none; cursor: pointer; }
        
        .medya-tip-selector { display: flex; gap: 8px; padding: 12px 1.5rem 0; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
        .medya-tip-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: none; background: transparent; border-bottom: 3px solid transparent; font-weight: 700; font-size: 13px; color: #94a3b8; cursor: pointer; transition: all 0.2s; }
        .medya-tip-btn:hover { color: #1e293b; }
        .medya-tip-btn.active.resim { color: #2563eb; border-bottom-color: #2563eb; background: rgba(37,99,235,0.04); }
        .medya-tip-btn.active.video { color: #dc2626; border-bottom-color: #dc2626; background: rgba(220,38,38,0.04); }
        
        .toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ef4444; transition: 0.3s; border-radius: 28px; }
        .toggle-knob { position: absolute; height: 22px; width: 22px; left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%; }
        .toggle-switch input:checked + .toggle-slider { background-color: #10b981; }
        .toggle-switch input:checked + .toggle-slider + .toggle-knob { transform: translateX(24px); }
        
        .seo-lang-tabs { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
        .seo-lang-btn { padding: 6px 16px; border: none; background: none; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; cursor: pointer; }
        .seo-lang-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .seo-box { background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        
        .toast-container { position: fixed; bottom: 30px; right: 30px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; }
        .toast { padding: 12px 20px; border-radius: 12px; font-size: 0.85rem; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease; }
        .toast-success { background: #10b981; color: white; }
        .toast-error { background: #ef4444; color: white; }
        
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
        
        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .form-col, .preview-col { flex: 0 0 100%; border-right: none; }
            .two-columns { flex-direction: column; }
            .gallery-grid { grid-template-columns: 1fr; }
        }
        
        .form-tabs {
            display: flex;
            gap: 6px;
            padding: 0 1.5rem;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        .form-tab {
            padding: 14px 22px;
            border: none;
            background: transparent;
            font-weight: 700;
            font-size: 13px;
            color: #64748b;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .form-tab:hover { color: #1e293b; }
        .form-tab.active { color: #667eea; border-bottom-color: #667eea; }
        .form-tab.tab-seo.active { color: #8b5cf6; border-bottom-color: #8b5cf6; }
        .form-tab-panel { display: none; }
        .form-tab-panel.active { display: block; }

        #gorselModal .modal-dialog-custom { max-width: 1300px; width: 95%; }
        #gorselModal .modal-content-custom { max-height: 92vh; display: flex; flex-direction: column; }
        #gorselModal .two-columns { flex: 1; overflow: hidden; min-height: 0; }
        #gorselModal .form-col { overflow-y: auto; max-height: 75vh; }
        #gorselModal .preview-col { overflow-y: auto; max-height: 75vh; }
    </style>
</head>
<body>

<div class="galeri-container">

    <!-- BAŞLIK -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 24px; padding: 25px 30px; margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 52px; height: 52px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-images text-white" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h1 style="margin: 0; color: white; font-weight: 800;">Galeri Yönetimi</h1>
                    <p style="font-size: 0.7rem; color: rgba(255,255,255,0.8); margin-top: 4px;">
                        <?php echo $toplam_resim; ?> fotoğraf · <?php echo $toplam_video; ?> video · <?php echo count($kategoriler); ?> kategori
                    </p>
                </div>
            </div>
            <button onclick="location.reload()" style="background: rgba(255,255,255,0.2); border: none; width: 40px; height: 40px; border-radius: 12px; color: white; cursor: pointer;"><i class="fas fa-sync-alt"></i></button>
        </div>
    </div>

    <!-- İSTATİSTİKLER -->
    <div class="stats-grid">
        <div class="stat-card">
            <div><div class="value" style="color:#2563eb;"><?php echo $toplam_resim; ?></div><div class="label">FOTOĞRAF</div></div>
            <div class="icon-box" style="background:#dbeafe;"><i class="fas fa-image" style="color:#2563eb;"></i></div>
        </div>
        <div class="stat-card">
            <div><div class="value" style="color:#dc2626;"><?php echo $toplam_video; ?></div><div class="label">VİDEO</div></div>
            <div class="icon-box" style="background:#fee2e2;"><i class="fas fa-video" style="color:#dc2626;"></i></div>
        </div>
        <div class="stat-card">
            <div><div class="value" style="color:#10b981;"><?php echo $aktif_sayisi; ?></div><div class="label">AKTİF</div></div>
            <div class="icon-box" style="background:#d1fae5;"><i class="fas fa-eye" style="color:#10b981;"></i></div>
        </div>
        <div class="stat-card">
            <div><div class="value" style="color:#ef4444;"><?php echo count($resimler) - $aktif_sayisi; ?></div><div class="label">PASİF</div></div>
            <div class="icon-box" style="background:#fee2e2;"><i class="fas fa-eye-slash" style="color:#ef4444;"></i></div>
        </div>
        <div class="stat-card" onclick="copKutusunuAc()" style="cursor:pointer;">
            <div><div class="value" style="color:#64748b;"><?php echo $cop_kutusu; ?></div><div class="label">ÇÖP KUTUSU</div></div>
            <div class="icon-box" style="background:#f1f5f9;"><i class="fas fa-trash-alt" style="color:#64748b;"></i></div>
        </div>
        <div class="stat-card">
            <div><div class="value" style="color:#f59e0b;"><?php echo count($kategoriler); ?></div><div class="label">KATEGORİ</div></div>
            <div class="icon-box" style="background:#fef3c7;"><i class="fas fa-folder" style="color:#f59e0b;"></i></div>
        </div>
    </div>

<!-- MEDYA TİP SEKMELERİ -->
<div class="medya-tabs">
    <a href="?modul=galeri&tip=video" class="medya-tab video <?php echo $aktif_tip === 'video' ? 'active' : ''; ?>">
        <i class="fas fa-video"></i> Videolar
        <span class="badge"><?php echo $toplam_video; ?></span>
    </a>
    <a href="?modul=galeri&tip=resim" class="medya-tab resim <?php echo $aktif_tip === 'resim' ? 'active' : ''; ?>">
        <i class="fas fa-image"></i> Fotoğraflar
        <span class="badge"><?php echo $toplam_resim; ?></span>
    </a>
</div>

    <!-- ÇÖP KUTUSU PANELİ -->
    <div id="copPaneli" style="display:none; margin-bottom:30px;">
        <div style="background:white; border-radius:20px; border:1px solid #e2e8f0; overflow:hidden;">
            <div style="background:linear-gradient(135deg,#64748b 0%,#475569 100%); padding:15px 20px; display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-trash-alt" style="color:white; font-size:1.2rem;"></i>
                    <h3 style="color:white; margin:0; font-size:1rem;">Çöp Kutusu</h3>
                    <span id="copSayisi" style="background:rgba(255,255,255,0.2); padding:2px 8px; border-radius:20px; font-size:0.7rem; color:white;"><?php echo $cop_kutusu; ?></span>
                </div>
                <div style="display:flex; gap:10px;">
                    <button onclick="copTemizle()" style="background:#dc2626; color:white; border:none; padding:6px 16px; border-radius:30px; cursor:pointer; font-size:0.7rem;"><i class="fas fa-trash-alt"></i> Tümünü Sil</button>
                    <button onclick="kapatCopPaneli()" style="background:rgba(255,255,255,0.2); color:white; border:none; padding:6px 16px; border-radius:30px; cursor:pointer; font-size:0.7rem;"><i class="fas fa-times"></i> Kapat</button>
                </div>
            </div>
            <div id="copListesi" style="padding:20px; min-height:200px;">
                <div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:2rem; color:#667eea;"></i><p>Yükleniyor...</p></div>
            </div>
        </div>
    </div>

    <!-- KATEGORİ TABS -->
    <div class="kategori-tabs">
        <button class="kategori-tab-btn active" data-kategori="hepsi" onclick="filterByKategori('hepsi')">
            <i class="fas fa-images"></i> Tümü <span class="badge"><?php echo count($resimler); ?></span>
        </button>
        <?php foreach ($kategoriler as $kat):
            $kat_tip = $kat['medya_tipi'] ?? 'hepsi';
            if ($kat_tip !== 'hepsi' && $kat_tip !== $aktif_tip) continue;
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM galeri_resimler WHERE kategori_id = ? AND silindi = 0 AND medya_tipi = ?");
            $stmt->execute([$kat['id'], $aktif_tip]);
            $sayi = $stmt->fetchColumn();
        ?>
        <button class="kategori-tab-btn" data-kategori="<?php echo $kat['id']; ?>" onclick="filterByKategori(<?php echo $kat['id']; ?>)">
            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($kat['kategori_adi']); ?> <span class="badge"><?php echo $sayi; ?></span>
        </button>
        <?php endforeach; ?>
        
        <div style="flex:1;"></div>
        <div style="display:flex; gap:6px;">
            <select id="kategoriDuzenleSelect" style="padding:6px 12px; border:1px solid #e2e8f0; border-radius:30px; background:white; font-size:0.7rem; cursor:pointer; width:160px;">
                <option value="">📁 Kategori Seç</option>
                <?php foreach ($kategoriler as $kat):
                    $kat_tip = $kat['medya_tipi'] ?? 'hepsi';
                    if ($kat_tip !== 'hepsi' && $kat_tip !== $aktif_tip) continue;
                    
                    $stmt = $db->prepare("SELECT COUNT(*) FROM galeri_resimler WHERE kategori_id = ? AND silindi = 0");
                    $stmt->execute([$kat['id']]);
                    $sayi = $stmt->fetchColumn();
                ?>
                <option value="<?php echo $kat['id']; ?>"><?php echo htmlspecialchars($kat['kategori_adi']); ?> (<?php echo $sayi; ?>)</option>
                <?php endforeach; ?>
            </select>
            <button onclick="duzenleSeciliKategori()" style="padding:6px 14px; background:#f59e0b; color:white; border:none; border-radius:30px; cursor:pointer; font-size:0.7rem; font-weight:500;"><i class="fas fa-edit"></i> Düzenle</button>
            <button onclick="openKategoriModal()" style="padding:6px 14px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; border:none; border-radius:30px; cursor:pointer; font-size:0.7rem; font-weight:500;"><i class="fas fa-plus"></i> Yeni Kategori</button>
            <button onclick="openGorselModal()" style="padding:6px 14px; background:<?php echo $aktif_tip === 'video' ? 'linear-gradient(135deg,#dc2626 0%,#b91c1c 100%)' : 'linear-gradient(135deg,#667eea 0%,#764ba2 100%)'; ?>; color:white; border:none; border-radius:30px; cursor:pointer; font-size:0.7rem; font-weight:500;">
                <i class="fas fa-plus"></i> Yeni <?php echo $aktif_tip === 'video' ? 'Video' : 'Fotoğraf'; ?>
            </button>
            <button onclick="openModal('seoModal')" style="padding:6px 14px; background:linear-gradient(135deg,#8b5cf6 0%,#6d28d9 100%); color:white; border:none; border-radius:30px; cursor:pointer; font-size:0.7rem; font-weight:500; margin-left:4px;"><i class="fas fa-search"></i> SEO</button>
        </div>
    </div>

    <!-- LİSTE BAŞLIĞI -->
    <div class="section-header">
        <h4>
            <i class="fas fa-<?php echo $aktif_tip === 'video' ? 'video' : 'images'; ?>" style="color:<?php echo $aktif_tip === 'video' ? '#dc2626' : '#667eea'; ?>;"></i>
            <span id="aktifKategoriBaslik"><?php echo $aktif_tip === 'video' ? 'Tüm Videolar' : 'Tüm Fotoğraflar'; ?></span>
        </h4>
    </div>

    <!-- LİSTE -->
    <div id="gorsellerContainer">
        <?php if(empty($resimler)): ?>
        <div class="empty-state">
            <i class="fas fa-<?php echo $aktif_tip === 'video' ? 'video' : 'image'; ?>"></i>
            <p>Henüz <?php echo $aktif_tip === 'video' ? 'video' : 'fotoğraf'; ?> eklenmemiş</p>
            <button class="btn-add" onclick="openGorselModal()" style="margin-top:15px;">İlk <?php echo $aktif_tip === 'video' ? 'Videoyu' : 'Fotoğrafı'; ?> Ekle</button>
        </div>
        <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($resimler as $resim):
                $is_video = (($resim['medya_tipi'] ?? 'resim') === 'video');
                $thumb = !empty($resim['thumbnail_url']) ? $resim['thumbnail_url'] : $resim['resim_url'];
                if ($is_video && empty($thumb) && !empty($resim['video_url'])) {
                    if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?(?:.*&)?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $resim['video_url'], $mm)) {
                        $thumb = 'https://img.youtube.com/vi/' . $mm[1] . '/mqdefault.jpg';
                    }
                }
            ?>
            <div class="gallery-card" data-kategori-id="<?php echo $resim['kategori_id']; ?>">
                <div class="gallery-card-img">
                    <?php if(!empty($thumb)): ?>
                        <img src="<?php echo htmlspecialchars($thumb); ?>" onerror="this.src='https://placehold.co/400x300/667eea/white?text=Resim+Yok'">
                    <?php else: ?>
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1e293b,#0f172a);color:#64748b;">
                            <i class="fas fa-<?php echo $is_video ? 'video' : 'image'; ?>" style="font-size:3rem;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <span class="tip-rozet <?php echo $is_video ? 'video' : 'resim'; ?>">
                        <i class="fas fa-<?php echo $is_video ? 'video' : 'image'; ?>"></i>
                        <?php echo $is_video ? 'VIDEO' : 'FOTO'; ?>
                    </span>
                    
                    <?php if($is_video): ?>
                    <div class="video-play-overlay">
                        <div class="video-play-btn">
                            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="gallery-card-badge">Sıra: <?php echo $resim['sira']; ?></div>
                </div>
                <div class="gallery-card-content">
                    <div class="gallery-card-title">
                        <?php echo htmlspecialchars(mb_substr($resim['baslik'], 0, 35)); ?>
                        <button onclick="toggleGorselDurum(<?php echo $resim['id']; ?>)" 
                                style="padding:4px 12px; border:none; border-radius:20px; font-size:0.65rem; font-weight:500; cursor:pointer; background:<?php echo $resim['durum'] ? '#10b981' : '#ef4444'; ?>; color:white;">
                            <?php echo $resim['durum'] ? 'Aktif' : 'Pasif'; ?>
                        </button>
                    </div>
                    <div class="gallery-card-cat"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($resim['kategori_adi'] ?? 'Kategorisiz'); ?></div>
                    
                    <?php if(!empty($resim['seo_title'])): ?>
                    <div style="font-size:0.65rem; color:#8b5cf6; margin-top:6px; padding:4px 8px; background:#f5f3ff; border-radius:6px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <i class="fas fa-search"></i> SEO: <?php echo htmlspecialchars(mb_substr($resim['seo_title'], 0, 40)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($is_video && !empty($resim['video_url'])): ?>
                    <div style="font-size:0.65rem; color:#94a3b8; margin-top:6px; padding:4px 8px; background:#f8fafc; border-radius:6px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($resim['video_url']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($is_video && !empty($resim['video_sure'])): ?>
                    <div style="font-size:0.65rem; color:#dc2626; margin-top:6px; padding:4px 8px; background:#fef2f2; border-radius:6px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <i class="fas fa-clock"></i> Süre: <?php echo htmlspecialchars($resim['video_sure']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="gallery-card-stats">
                        <span><i class="fas fa-heart" style="color:#ef4444;"></i> <?php echo number_format($resim['begeni']); ?></span>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo $resim['tarih']; ?></span>
                    </div>
                </div>
                <div class="gallery-card-actions">
                    <button class="btn-action btn-edit" onclick="editGorsel(<?php echo $resim['id']; ?>)"><i class="fas fa-edit"></i> Düzenle</button>
                    <button class="btn-action btn-delete" onclick="deleteGorsel(<?php echo $resim['id']; ?>)"><i class="fas fa-trash"></i> Sil</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========== SEO MODAL ========== -->
    <div id="seoModal" class="gallery-modal" style="z-index:100000;">
        <div class="modal-dialog-custom" style="max-width:900px;">
            <div class="modal-content-custom">
                <div class="modal-header-custom" style="background:linear-gradient(135deg,#8b5cf6 0%,#6d28d9 100%);">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-search" style="color:white; font-size:1.2rem;"></i>
                        <h5 style="color:white; margin:0;">SEO Ayarları - Galeri Sayfası</h5>
                    </div>
                    <button onclick="closeModal('seoModal')" style="background:rgba(255,255,255,0.2); border:none; width:34px; height:34px; border-radius:50%; color:white; cursor:pointer;"><i class="fas fa-times"></i></button>
                </div>
                
                <div style="padding:25px; max-height:75vh; overflow-y:auto;">
                    <form id="seoForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="seo-lang-tabs">
                            <button type="button" class="seo-lang-btn active" data-lang="tr">🇹🇷 Türkçe</button>
                            <button type="button" class="seo-lang-btn" data-lang="en">🇬🇧 English</button>
                        </div>
                        
                        <div class="seo-lang-content" data-lang="tr">
                            <div class="seo-box">
                                <div style="margin-bottom:16px;">
                                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px;">📌 SEO Başlık (Title) - TR</label>
                                    <input type="text" name="seo_title_tr" class="form-control" value="<?php echo htmlspecialchars($seo_title_tr ?: 'Galeri | Prof. Dr. İbrahim Duran | Diş Kliniği Samsun'); ?>" maxlength="70">
                                </div>
                                <div style="margin-bottom:16px;">
                                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px;">📝 Meta Açıklama (Description) - TR</label>
                                    <textarea name="seo_description_tr" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($seo_description_tr ?: 'Prof. Dr. İbrahim Duran Samsun Atakum\'da gerçekleştirdiği diş tedavilerinin öncesi ve sonrası fotoğrafları ve videoları.'); ?></textarea>
                                </div>
                                <div>
                                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px;">🏷️ Anahtar Kelimeler (Keywords) - TR</label>
                                    <input type="text" name="seo_keywords_tr" class="form-control" value="<?php echo htmlspecialchars($seo_keywords_tr ?: 'galeri, öncesi sonrası fotoğraflar, video, Samsun diş hekimi'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="seo-lang-content" data-lang="en" style="display:none;">
                            <div class="seo-box">
                                <div style="margin-bottom:16px;">
                                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px;">📌 SEO Title - EN</label>
                                    <input type="text" name="seo_title_en" class="form-control" value="<?php echo htmlspecialchars($seo_title_en ?: 'Gallery | Prof. Dr. İbrahim Duran | Dental Clinic Samsun'); ?>" maxlength="70">
                                </div>
                                <div style="margin-bottom:16px;">
                                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px;">📝 Meta Description - EN</label>
                                    <textarea name="seo_description_en" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($seo_description_en ?: 'Before and after photos and videos of dental treatments by Prof. Dr. İbrahim Duran in Samsun Atakum.'); ?></textarea>
                                </div>
                                <div>
                                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px;">🏷️ Keywords - EN</label>
                                    <input type="text" name="seo_keywords_en" class="form-control" value="<?php echo htmlspecialchars($seo_keywords_en ?: 'gallery, before after photos, video, Samsun dentist'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="seo-box">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                <div>
                                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px;">🖼️ OG Görsel</label>
                                    <div style="display:flex; gap:8px;">
                                        <input type="text" name="seo_og_image" class="form-control" value="<?php echo htmlspecialchars($seo_og_image ?: '/uploads/galeri/galeri-og.webp'); ?>">
                                        <button type="button" class="upload-btn" onclick="seoResimYukle()" style="margin:0; padding:10px 16px;"><i class="fas fa-upload"></i></button>
                                    </div>
                                </div>
                                <div>
                                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px;">🔗 Canonical URL</label>
                                    <input type="text" name="seo_canonical" class="form-control" value="<?php echo htmlspecialchars($seo_canonical ?: 'https://www.dribrahimdurandentalclinic.com/galeri/'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div style="display:flex; gap:12px; justify-content:flex-end;">
                            <button type="button" class="btn-cancel" onclick="closeModal('seoModal')">Kapat</button>
                            <button type="button" class="btn-submit" onclick="saveSeo()"><i class="fas fa-save"></i> SEO Ayarlarını Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ========== GÖRSEL/VİDEO MODAL ========== -->
    <div id="gorselModal" class="gallery-modal">
        <div class="modal-dialog-custom">
            <div class="modal-content-custom">
                <div class="modal-header-custom">
                    <h5 id="gorselModalTitle">Görsel Düzenle</h5>
                    <button onclick="closeModal('gorselModal')"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="medya-tip-selector">
                    <button type="button" class="medya-tip-btn resim active" id="tipBtnResim" onclick="switchMedyaTip('resim')">
                        <i class="fas fa-image"></i> Fotoğraf
                    </button>
                    <button type="button" class="medya-tip-btn video" id="tipBtnVideo" onclick="switchMedyaTip('video')">
                        <i class="fas fa-video"></i> Video
                    </button>
                </div>
                
                <div class="form-tabs">
                    <button type="button" class="form-tab active" data-tab="temel" onclick="switchFormTab('temel')">
                        <i class="fas fa-info-circle"></i> Temel Bilgiler
                    </button>
                    <button type="button" class="form-tab tab-seo" data-tab="seo" onclick="switchFormTab('seo')">
                        <i class="fas fa-search"></i> SEO Ayarları
                    </button>
                </div>
                
                <form id="gorselForm" style="flex:1; overflow:hidden; display:flex; flex-direction:column;">
                    <input type="hidden" name="id" id="gorselId">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="medya_tipi" id="gorselMedyaTipi" value="resim">
                    
                    <div class="two-columns">
                        <div class="form-col">
                            
                            <div class="form-tab-panel active" data-tab-panel="temel">
                                <div class="form-group"><label>Başlık *</label><input type="text" name="baslik" id="gorselBaslik" class="form-control" required></div>
                                
                                <div class="form-group">
                                    <label>URL Slug <span style="color:#94a3b8; font-weight:400; font-size:11px;">(boş bırakılırsa başlıktan üretilir)</span></label>
                                    <input type="text" name="slug" id="gorselSlug" class="form-control" placeholder="otomatik-olusturulur">
                                </div>
                                
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <div style="display:flex; gap:8px;">
                                        <select name="kategori_id" id="gorselKategori" class="form-control" style="flex:1;">
                                            <option value="">Seçiniz</option>
                                            <?php foreach ($kategoriler as $kat): ?>
                                            <option value="<?php echo $kat['id']; ?>"><?php echo htmlspecialchars($kat['kategori_adi']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" onclick="kategoriDuzenleFromModal()" style="padding:0 12px; background:#f59e0b; color:white; border:none; border-radius:12px; cursor:pointer; font-size:0.7rem;"><i class="fas fa-edit"></i></button>
                                        <button type="button" onclick="yeniKategoriFromModal()" style="padding:0 12px; background:#10b981; color:white; border:none; border-radius:12px; cursor:pointer; font-size:0.7rem;"><i class="fas fa-plus"></i></button>
                                    </div>
                                </div>
                                
                                <div id="resimAlani">
                                    <div class="form-group">
                                        <label>Resim URL</label>
                                        <div style="display:flex; gap:10px;">
                                            <input type="text" name="resim_url" id="gorselResim" class="form-control" placeholder="https://...">
                                            <label class="upload-btn"><i class="fas fa-upload"></i> Dosya Seç<input type="file" id="resimDosya" accept="image/*" style="display:none;"></label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="videoAlani" style="display:none;">
                                    <div class="form-group">
                                        <label>🎬 Video URL *</label>
                                        <input type="text" name="video_url" id="gorselVideoUrl" class="form-control" placeholder="https://youtube.com/watch?v=... veya .mp4">
                                        <div style="font-size:10px; color:#94a3b8; margin-top:4px;"><i class="fas fa-info-circle"></i> YouTube, Vimeo veya MP4/WEBM desteklenir</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>⏱️ Video Süresi <span style="color:#94a3b8; font-weight:400; font-size:11px;">(Google Schema için)</span></label>
                                        <input type="text" name="video_sure" id="gorselVideoSure" class="form-control" placeholder="Örnek: PT1M30S">
                                        <div style="font-size:10px; color:#94a3b8; margin-top:4px; line-height:1.5;">
                                            <i class="fas fa-info-circle"></i> Format: <b>PT[dakika]M[saniye]S</b><br>
                                            • 45 saniye → <code style="background:#f1f5f9; padding:1px 4px; border-radius:4px;">PT45S</code><br>
                                            • 1 dakika 30 saniye → <code style="background:#f1f5f9; padding:1px 4px; border-radius:4px;">PT1M30S</code><br>
                                            • 2 dakika → <code style="background:#f1f5f9; padding:1px 4px; border-radius:4px;">PT2M</code>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label id="thumbLabel">Küçük Resim (Thumbnail)</label>
                                    <div style="display:flex; gap:10px;">
                                        <input type="text" name="thumbnail_url" id="gorselThumb" class="form-control" placeholder="Boş bırakılırsa otomatik">
                                        <label class="upload-btn"><i class="fas fa-upload"></i> Dosya Seç<input type="file" id="thumbDosya" accept="image/*" style="display:none;"></label>
                                        <button type="button" class="upload-btn" onclick="kirpModalAc('gorselThumb')" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9); color:white; border:none; white-space:nowrap;">
                                            <i class="fas fa-crop-alt"></i> Kırp
                                        </button>
                                    </div>
                                    <div id="thumbInfo" style="font-size:10px; color:#94a3b8; margin-top:4px; display:none;">
                                        <i class="fas fa-info-circle"></i> Video için boş bırakılırsa YouTube kapak görseli otomatik çekilir
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Açıklama</label>
                                    <textarea name="aciklama" id="gorselAciklama" class="form-control" rows="3"></textarea>
                                </div>
                                
                                <div style="display:flex; gap:12px;">
                                    <div class="form-group" style="flex:1;"><label>Tarih</label><input type="text" name="tarih" id="gorselTarih" class="form-control" value="<?php echo date('Y'); ?>"></div>
                                    <div class="form-group" style="flex:1;"><label>Sıra</label><input type="number" name="sira" id="gorselSira" class="form-control" value="0"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label style="display:flex; align-items:center; justify-content:space-between; cursor:pointer;">
                                        <span style="font-weight:600; color:#1e293b;">📌 Durum</span>
                                        <div class="toggle-switch">
                                            <input type="checkbox" name="durum" id="gorselDurum" checked>
                                            <span class="toggle-slider"></span>
                                            <span class="toggle-knob"></span>
                                        </div>
                                    </label>
                                    <span id="durumText" style="font-size:0.7rem; display:inline-block; color:#10b981;">🟢 Aktif</span>
                                </div>
                            </div>
                            
                            <div class="form-tab-panel" data-tab-panel="seo">
                                <div style="background:#f5f3ff; border-radius:14px; padding:14px 16px; margin-bottom:16px; display:flex; align-items:center; gap:10px; border:1px solid #ddd6fe;">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#8b5cf6,#6d28d9); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i class="fas fa-search" style="color:white; font-size:14px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:800; color:#1e293b; font-size:13px;">Bu İçeriğe Özel SEO</div>
                                        <div style="font-size:11px; color:#7c3aed;">Boş bırakılan alanlar otomatik doldurulur</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>📌 SEO Başlık <span style="color:#94a3b8; font-weight:400;">(60-70 karakter)</span></label>
                                    <input type="text" name="seo_title" id="gorselSeoTitle" class="form-control" placeholder="Boş bırakırsan başlık kullanılır" maxlength="70">
                                    <div style="font-size:10px; color:#94a3b8; text-align:right; margin-top:3px;"><span id="gorselSeoTitleCount" style="font-weight:700;">0</span>/70</div>
                                </div>

                                <div class="form-group">
                                    <label>📝 Meta Açıklama <span style="color:#94a3b8; font-weight:400;">(150-160 karakter)</span></label>
                                    <textarea name="seo_description" id="gorselSeoDesc" class="form-control" rows="4" placeholder="Boş bırakırsan açıklama kullanılır" maxlength="160"></textarea>
                                    <div style="font-size:10px; color:#94a3b8; text-align:right; margin-top:3px;"><span id="gorselSeoDescCount" style="font-weight:700;">0</span>/160</div>
                                </div>

                                <div class="form-group">
                                    <label>🏷️ Anahtar Kelimeler</label>
                                    <input type="text" name="seo_keywords" id="gorselSeoKeywords" class="form-control" placeholder="Virgül ile ayır: röntgen, panoramik, dijital">
                                    <div style="font-size:10px; color:#94a3b8; margin-top:3px;"><i class="fas fa-info-circle"></i> SEO için önemli, 5-10 kelime önerilir</div>
                                </div>

                                <div class="form-group">
                                    <label>🖼️ Sosyal Medya Görseli (OG)</label>
                                    <input type="text" name="seo_og_image" id="gorselSeoOg" class="form-control" placeholder="Boş bırakırsan resim kullanılır">
                                    <div style="font-size:10px; color:#94a3b8; margin-top:3px;"><i class="fas fa-info-circle"></i> Facebook/Twitter paylaşımında görünecek görsel (1200x630)</div>
                                </div>

                                <div style="background:#f0fdf4; border-radius:12px; padding:12px 14px; border-left:3px solid #10b981; margin-top:16px;">
                                    <div style="display:flex; align-items:center; gap:8px; font-size:11px; color:#059669; font-weight:600;">
                                        <i class="fas fa-lightbulb"></i>
                                        <span>İpucu: SEO başlığı 60-70 karakter, açıklama 150-160 karakter arasında olursa Google'da tam görünür.</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display:flex; gap:12px; justify-content:flex-end; padding-top:16px; margin-top:16px; border-top:1px solid #e2e8f0; position:sticky; bottom:0; background:linear-gradient(180deg,transparent 0%,#fff 30%);">
                                <button type="button" class="btn-cancel" onclick="closeModal('gorselModal')">İptal</button>
                                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Kaydet</button>
                            </div>
                        </div>
                        
                        <div class="preview-col">
                            <div class="preview-card">
                                <div style="position:relative;">
                                    <img id="onizlemeResim" src="https://placehold.co/400x300/667eea/white?text=Önizleme" class="preview-img">
                                    <div id="onizlemeVideoOverlay" style="display:none; position:absolute; inset:0; background:rgba(220,38,38,0.15); align-items:center; justify-content:center;">
                                        <div style="width:60px; height:60px; border-radius:50%; background:rgba(255,255,255,0.95); display:flex; align-items:center; justify-content:center; box-shadow:0 8px 24px rgba(0,0,0,0.3);">
                                            <svg style="width:26px; height:26px; fill:#dc2626; margin-left:3px;" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <div style="padding:15px;">
                                    <h5 id="onizlemeBaslik" style="font-size:1rem; font-weight:700;">Başlık</h5>
                                    <p id="onizlemeKategori" style="font-size:0.7rem; color:#667eea; margin-bottom:8px;"><i class="fas fa-folder"></i> Kategori</p>
                                    <p id="onizlemeAciklama" style="font-size:0.75rem; color:#64748b;">Açıklama...</p>
                                </div>
                            </div>
                            
                            <div style="margin-top:16px; background:white; border-radius:14px; padding:14px; box-shadow:0 4px 12px rgba(0,0,0,0.06); border:1px solid #e2e8f0;">
                                <div style="font-size:10px; font-weight:700; color:#94a3b8; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">
                                    <i class="fas fa-google" style="color:#4285f4;"></i> Google Önizleme
                                </div>
                                <div id="seoGooglePreviewTitle" style="color:#1a0dab; font-size:16px; line-height:1.2; font-weight:500; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">Sayfa Başlığı</div>
                                <div style="color:#006621; font-size:11px; margin-top:2px;">dribrahimdurandentalclinic.com › galeri</div>
                                <div id="seoGooglePreviewDesc" style="color:#545454; font-size:12px; margin-top:4px; line-height:1.4; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical;">Meta açıklama burada görünecek...</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== KIRPMA MODALI ========== -->
    <div id="kirpModal" class="gallery-modal" style="z-index:100002;">
        <div class="modal-dialog-custom" style="max-width:900px;">
            <div class="modal-content-custom">
                <div class="modal-header-custom" style="background:linear-gradient(135deg,#8b5cf6 0%,#6d28d9 100%);">
                    <h5 style="color:white; margin:0;"><i class="fas fa-crop-alt"></i> Görsel Kırp</h5>
                    <button onclick="kirpModalKapat()" style="background:rgba(255,255,255,0.2); border:none; width:34px; height:34px; border-radius:50%; color:white; cursor:pointer;"><i class="fas fa-times"></i></button>
                </div>
                <div style="padding:16px; background:#0f172a;">
                    <div style="max-height:55vh; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                        <img id="kirpImage" crossorigin="anonymous" style="max-width:100%; max-height:55vh; display:block;">
                    </div>
                </div>
                <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; background:#f8fafc;">
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <span style="font-size:11px; color:#64748b; align-self:center; font-weight:700; margin-right:4px;">ORAN:</span>
                        <button type="button" onclick="kirpOran(16,9)" style="padding:6px 14px; background:white; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600;">16:9</button>
                        <button type="button" onclick="kirpOran(4,3)" style="padding:6px 14px; background:white; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600;">4:3</button>
                        <button type="button" onclick="kirpOran(1,1)" style="padding:6px 14px; background:white; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600;">1:1</button>
                        <button type="button" onclick="kirpOran(0,0)" style="padding:6px 14px; background:white; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600;">Serbest</button>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn-cancel" onclick="kirpModalKapat()">İptal</button>
                        <button type="button" class="btn-submit" onclick="kirpKaydet()"><i class="fas fa-check"></i> Kırp ve Kaydet</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KATEGORİ MODAL -->
    <div id="kategoriModal" class="gallery-modal">
        <div class="modal-dialog-custom" style="max-width:500px;">
            <div class="modal-content-custom">
                <div class="modal-header-custom">
                    <h5 id="kategoriModalTitle">Kategori</h5>
                    <button onclick="closeModal('kategoriModal')"><i class="fas fa-times"></i></button>
                </div>
                <form id="kategoriForm" style="padding:25px;">
                    <input type="hidden" name="id" id="kategoriId">
                    <div class="form-group"><label>Kategori Adı *</label><input type="text" name="kategori_adi" id="kategoriAdi" class="form-control" required></div>
                    <div class="form-group"><label>Slug *</label><input type="text" name="kategori_slug" id="kategoriSlug" class="form-control" required></div>
                    <div class="form-group">
                        <label>Medya Tipi</label>
                        <select name="medya_tipi" id="kategoriMedyaTipi" class="form-control">
                            <option value="hepsi">🔄 Hepsi (Fotoğraf + Video)</option>
                            <option value="resim">📷 Sadece Fotoğraf</option>
                            <option value="video">🎥 Sadece Video</option>
                        </select>
                        <div style="font-size:10px; color:#94a3b8; margin-top:4px;">
                            <i class="fas fa-info-circle"></i> "Hepsi" seçilirse hem video hem fotoğraf sekmesinde görünür
                        </div>
                    </div>
                    <div class="form-group"><label>İkon</label>
                        <select name="ikon" id="kategoriIkon" class="form-control">
                            <option value="Camera">📷 Camera</option><option value="Video">🎥 Video</option>
                            <option value="Image">🖼️ Image</option><option value="Heart">❤️ Heart</option>
                            <option value="Star">⭐ Star</option><option value="Folder">📁 Folder</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Sıra</label><input type="number" name="sira" id="kategoriSira" class="form-control" value="0"></div>
                    <div id="kategoriSilDiv" style="display:none; margin-top:15px; padding-top:15px; border-top:1px solid #e2e8f0;">
                        <button type="button" onclick="kategoriSilFromModal()" style="width:100%; padding:10px; background:#ef4444; color:white; border:none; border-radius:12px; cursor:pointer; font-size:0.8rem; font-weight:500;"><i class="fas fa-trash-alt"></i> Bu Kategoriyi Sil</button>
                        <p style="font-size:0.6rem; color:#ef4444; margin-top:8px; text-align:center;"><i class="fas fa-exclamation-triangle"></i> Kategori içindeki tüm görseller de silinir!</p>
                    </div>
                    <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:20px;">
                        <button type="button" class="btn-cancel" onclick="closeModal('kategoriModal')">İptal</button>
                        <button type="submit" class="btn-submit">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CONFIRM MODAL -->
    <div id="confirmModal" class="gallery-modal" style="z-index:100000;">
        <div class="modal-dialog-custom" style="max-width:400px;">
            <div class="modal-content-custom">
                <div class="modal-header-custom" style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);">
                    <h5 id="confirmTitle" style="color:white; margin:0;">Onay</h5>
                    <button onclick="closeConfirmModal()" style="background:rgba(255,255,255,0.2); border:none; width:30px; height:30px; border-radius:50%; color:white; cursor:pointer;"><i class="fas fa-times"></i></button>
                </div>
                <div style="padding:25px;">
                    <p id="confirmMessage" style="margin-bottom:25px; color:#1e293b;">İşlemi onaylıyor musunuz?</p>
                    <div style="display:flex; gap:12px; justify-content:flex-end;">
                        <button onclick="closeConfirmModal()" style="padding:8px 20px; background:#e2e8f0; border:none; border-radius:10px; cursor:pointer;">İptal</button>
                        <button id="confirmOkBtn" style="padding:8px 20px; background:#10b981; color:white; border:none; border-radius:10px; cursor:pointer;">Evet, Onayla</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container" id="customToast"></div>

</div>

<script>
// ========== CSRF ==========
const csrfToken = '<?php echo $_SESSION["csrf_token"]; ?>';
const ajaxUrl = '/admin/modules/galeri/ajax.php';
const aktifTip = '<?php echo $aktif_tip; ?>';

// ========== TOAST ==========
function showToast(msg, type = 'success') {
    const container = document.getElementById('customToast');
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    toast.innerHTML = `<i class="fas ${icon}"></i> ${msg}`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'slideOut 0.3s ease'; setTimeout(() => toast.remove(), 300); }, 3000);
}

// ========== CONFIRM ==========
let confirmCallback = null;
function showConfirm(title, message, callback) {
    document.getElementById('confirmTitle').innerHTML = title;
    document.getElementById('confirmMessage').innerHTML = message;
    confirmCallback = callback;
    openModal('confirmModal');
}
function closeConfirmModal() { closeModal('confirmModal'); confirmCallback = null; }
document.getElementById('confirmOkBtn').onclick = function() { if(confirmCallback) confirmCallback(true); closeConfirmModal(); };

// ========== MODAL ==========
function closeModal(id) { document.getElementById(id).style.display = 'none'; document.body.style.overflow = ''; }
function openModal(id) { document.getElementById(id).style.display = 'block'; document.body.style.overflow = 'hidden'; }

// ========== MEDYA TİP DEĞİŞTİR ==========
function switchMedyaTip(tip) {
    const medyaTipiInput = document.getElementById('gorselMedyaTipi');
    if (medyaTipiInput) medyaTipiInput.value = tip;
    
    const btnResim = document.getElementById('tipBtnResim');
    const btnVideo = document.getElementById('tipBtnVideo');
    const resimAlani = document.getElementById('resimAlani');
    const videoAlani = document.getElementById('videoAlani');
    const thumbLabel = document.getElementById('thumbLabel');
    const thumbInfo = document.getElementById('thumbInfo');
    const videoOverlay = document.getElementById('onizlemeVideoOverlay');
    
    if (btnResim) btnResim.classList.remove('active');
    if (btnVideo) btnVideo.classList.remove('active');
    
    if (tip === 'resim') {
        if (btnResim) btnResim.classList.add('active');
        if (resimAlani) resimAlani.style.display = 'block';
        if (videoAlani) videoAlani.style.display = 'none';
        if (thumbLabel) thumbLabel.textContent = 'Küçük Resim (Thumbnail)';
        if (thumbInfo) thumbInfo.style.display = 'none';
        if (videoOverlay) videoOverlay.style.display = 'none';
    } else {
        if (btnVideo) btnVideo.classList.add('active');
        if (resimAlani) resimAlani.style.display = 'none';
        if (videoAlani) videoAlani.style.display = 'block';
        if (thumbLabel) thumbLabel.textContent = 'Kapak Görseli (Thumbnail)';
        if (thumbInfo) thumbInfo.style.display = 'block';
        if (videoOverlay) videoOverlay.style.display = 'flex';
    }
}

// ========== FORM SEKMELERİ ==========
function switchFormTab(tabName) {
    document.querySelectorAll('#gorselModal .form-tab').forEach(t => t.classList.remove('active'));
    document.querySelector('#gorselModal .form-tab[data-tab="' + tabName + '"]')?.classList.add('active');
    
    document.querySelectorAll('#gorselModal .form-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelector('#gorselModal .form-tab-panel[data-tab-panel="' + tabName + '"]')?.classList.add('active');
    
    const formCol = document.querySelector('#gorselModal .form-col');
    if (formCol) formCol.scrollTop = 0;
}

// ========== SEO CANLI ÖNİZLEME ==========
function updateSeoGooglePreview() {
    const titleInput = document.getElementById('gorselSeoTitle');
    const descInput = document.getElementById('gorselSeoDesc');
    const baslikInput = document.getElementById('gorselBaslik');
    const aciklamaInput = document.getElementById('gorselAciklama');
    
    const seoTitle = (titleInput?.value || '').trim();
    const seoDesc = (descInput?.value || '').trim();
    const baslik = (baslikInput?.value || '').trim();
    const aciklama = (aciklamaInput?.value || '').trim();
    
    const previewTitle = document.getElementById('seoGooglePreviewTitle');
    const previewDesc = document.getElementById('seoGooglePreviewDesc');
    
    if (previewTitle) previewTitle.textContent = seoTitle || baslik || 'Sayfa Başlığı';
    if (previewDesc) previewDesc.textContent = seoDesc || aciklama || 'Meta açıklama burada görünecek...';
}

document.getElementById('gorselSeoTitle')?.addEventListener('input', updateSeoGooglePreview);
document.getElementById('gorselSeoDesc')?.addEventListener('input', updateSeoGooglePreview);
document.getElementById('gorselBaslik')?.addEventListener('input', updateSeoGooglePreview);
document.getElementById('gorselAciklama')?.addEventListener('input', updateSeoGooglePreview);

// ========== RESİM YÜKLE ==========
function uploadFile(input, targetId) {
    if (!input.files?.[0]) return;
    let fd = new FormData();
    fd.append('resim', input.files[0]);
    fd.append('csrf_token', csrfToken);
    fetch(ajaxUrl + '?islem=resim_yukle', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById(targetId).value = data.url;
                if (targetId === 'gorselResim') document.getElementById('onizlemeResim').src = data.url;
                if (targetId === 'gorselThumb') document.getElementById('onizlemeResim').src = data.url;
                showToast('✅ Resim yüklendi');
            } else { showToast('❌ ' + (data.message || 'Yükleme hatası'), 'error'); }
        })
        .catch(err => showToast('❌ Yükleme hatası', 'error'));
    input.value = '';
}
document.getElementById('resimDosya')?.addEventListener('change', function() { uploadFile(this, 'gorselResim'); });
document.getElementById('thumbDosya')?.addEventListener('change', function() { uploadFile(this, 'gorselThumb'); });

// ========== TOGGLE DURUM ==========
function toggleGorselDurum(id) {
    fetch(ajaxUrl + '?islem=toggle_durum&id=' + id, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'csrf_token=' + csrfToken })
        .then(res => res.json())
        .then(data => { if(data.success) { location.reload(); } else { showToast('❌ İşlem başarısız', 'error'); } })
        .catch(err => showToast('❌ Hata', 'error'));
}

// ========== GÖRSEL MODAL ==========
function openGorselModal(id = null) {
    document.getElementById('gorselForm').reset();
    
    ['gorselSeoTitle','gorselSeoDesc','gorselSeoKeywords','gorselSeoOg'].forEach(function(elId){
        var el = document.getElementById(elId);
        if (el) el.value = '';
    });
    var tc = document.getElementById('gorselSeoTitleCount'); if (tc) tc.textContent = '0';
    var dc = document.getElementById('gorselSeoDescCount'); if (dc) dc.textContent = '0';
    
    document.getElementById('gorselId').value = '';
    document.getElementById('gorselSlug').value = '';
    document.getElementById('gorselSlug').removeAttribute('data-manual');
    document.getElementById('gorselTarih').value = new Date().getFullYear();
    document.getElementById('gorselMedyaTipi').value = aktifTip;
    document.getElementById('gorselVideoSure').value = '';
    document.getElementById('onizlemeResim').src = 'https://placehold.co/400x300/667eea/white?text=Önizleme';
    document.getElementById('onizlemeBaslik').innerHTML = 'Başlık';
    document.getElementById('onizlemeKategori').innerHTML = '<i class="fas fa-folder"></i> Kategori';
    document.getElementById('onizlemeAciklama').innerHTML = 'Açıklama...';
    document.getElementById('gorselModalTitle').innerHTML = id ? '✏️ Düzenle' : '➕ Yeni ' + (aktifTip === 'video' ? 'Video' : 'Fotoğraf');
    document.getElementById('gorselDurum').checked = true;
    updateToggleText();
    switchMedyaTip(aktifTip);
    
    if (id) {
        fetch(ajaxUrl + '?islem=get_gorsel&id=' + id)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    let g = data.data;
                    document.getElementById('gorselId').value = g.id;
                    document.getElementById('gorselBaslik').value = g.baslik || '';
                    document.getElementById('gorselSlug').value = g.slug || '';
                    document.getElementById('gorselSlug').dataset.manual = '1';
                    document.getElementById('gorselKategori').value = g.kategori_id || '';
                    document.getElementById('gorselResim').value = g.resim_url || '';
                    document.getElementById('gorselThumb').value = g.thumbnail_url || '';
                    document.getElementById('gorselVideoUrl').value = g.video_url || '';
                    document.getElementById('gorselVideoSure').value = g.video_sure || '';
                    document.getElementById('gorselAciklama').value = g.aciklama || '';
                    document.getElementById('gorselTarih').value = g.tarih || new Date().getFullYear();
                    document.getElementById('gorselSira').value = g.sira || 0;
                    document.getElementById('gorselDurum').checked = (g.durum == 1);
                    updateToggleText();
                    
                    document.getElementById('gorselSeoTitle').value = g.seo_title || '';
                    document.getElementById('gorselSeoDesc').value = g.seo_description || '';
                    document.getElementById('gorselSeoKeywords').value = g.seo_keywords || '';
                    document.getElementById('gorselSeoOg').value = g.seo_og_image || '';
                    document.getElementById('gorselSeoTitleCount').textContent = (g.seo_title || '').length;
                    document.getElementById('gorselSeoDescCount').textContent = (g.seo_description || '').length;
                    
                    const tip = g.medya_tipi || 'resim';
                    switchMedyaTip(tip);
                    
                    let previewSrc = g.thumbnail_url || g.resim_url || '';
                    if (tip === 'video' && !previewSrc && g.video_url) {
                        const m = g.video_url.match(/(?:youtube(?:-nocookie)?\.com\/(?:embed\/|watch\?(?:.*&)?v=|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
                        if (m) previewSrc = 'https://img.youtube.com/vi/' + m[1] + '/mqdefault.jpg';
                    }
                    if (previewSrc) document.getElementById('onizlemeResim').src = previewSrc;
                    document.getElementById('onizlemeBaslik').innerHTML = g.baslik || 'Başlık';
                    document.getElementById('onizlemeKategori').innerHTML = '<i class="fas fa-folder"></i> ' + (g.kategori_adi || 'Kategori');
                    document.getElementById('onizlemeAciklama').innerHTML = (g.aciklama || 'Açıklama...').substring(0, 100);
                } else { showToast('❌ Kayıt bulunamadı', 'error'); }
            })
            .catch(err => showToast('❌ Veri çekme hatası', 'error'));
    } else {
        fetch(ajaxUrl + '?islem=son_sira')
            .then(res => res.json())
            .then(data => { document.getElementById('gorselSira').value = data.sira || 1; })
            .catch(err => document.getElementById('gorselSira').value = 1);
    }
    openModal('gorselModal');
}
function editGorsel(id) { if(id) openGorselModal(id); }

function deleteGorsel(id) {
    showConfirm('🗑️ Sil', 'Bu öğeyi çöp kutusuna taşımak istediğinize emin misiniz?', (confirmed) => {
        if (confirmed) {
            fetch(ajaxUrl + '?islem=sil_gorsel&id=' + id, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'csrf_token=' + csrfToken })
                .then(res => res.json())
                .then(data => { if(data.success){ showToast('✅ Çöp kutusuna taşındı'); setTimeout(() => location.reload(), 500); } else { showToast('❌ Silme hatası', 'error'); } })
                .catch(err => showToast('❌ Hata', 'error'));
        }
    });
}

// ========== KATEGORİ İŞLEMLERİ ==========
function openKategoriModal(id = null) {
    document.getElementById('kategoriForm').reset();
    document.getElementById('kategoriId').value = '';
    document.getElementById('kategoriAdi').value = '';
    document.getElementById('kategoriSlug').value = '';
    document.getElementById('kategoriIkon').value = 'Camera';
    document.getElementById('kategoriMedyaTipi').value = aktifTip;
    document.getElementById('kategoriSira').value = 0;
    document.getElementById('kategoriSilDiv').style.display = 'none';
    document.getElementById('kategoriModalTitle').innerHTML = id ? '✏️ Kategori Düzenle' : '➕ Yeni Kategori';
    
    if (id && id > 0) {
        document.getElementById('kategoriSilDiv').style.display = 'block';
        fetch(ajaxUrl + '?islem=get_kategori&id=' + id)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    let k = data.data;
                    document.getElementById('kategoriId').value = k.id;
                    document.getElementById('kategoriAdi').value = k.kategori_adi || '';
                    document.getElementById('kategoriSlug').value = k.kategori_slug || '';
                    document.getElementById('kategoriIkon').value = k.ikon || 'Camera';
                    document.getElementById('kategoriMedyaTipi').value = k.medya_tipi || 'hepsi';
                    document.getElementById('kategoriSira').value = k.sira || 0;
                } else { showToast('❌ Kategori bulunamadı', 'error'); }
            })
            .catch(err => showToast('❌ Veri çekme hatası', 'error'));
    } else {
        fetch(ajaxUrl + '?islem=son_kategori_sira')
            .then(res => res.json())
            .then(data => { document.getElementById('kategoriSira').value = data.sira || 1; })
            .catch(err => document.getElementById('kategoriSira').value = 1);
    }
    openModal('kategoriModal');
}
function editKategori(id) { if(id) openKategoriModal(id); }

function deleteKategori(id) {
    showConfirm('🗑️ Kategori Sil', 'Bu kategori ve içindeki TÜM görseller silinecek! Devam etmek istediğinize emin misiniz?', (confirmed) => {
        if (confirmed) {
            fetch(ajaxUrl + '?islem=sil_kategori&id=' + id, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'csrf_token=' + csrfToken })
                .then(res => res.json())
                .then(data => { if(data.success){ showToast('✅ Kategori silindi'); setTimeout(() => location.reload(), 500); } else { showToast('❌ Silme hatası', 'error'); } })
                .catch(err => showToast('❌ Hata', 'error'));
        }
    });
}
function kategoriSilFromModal() {
    const id = document.getElementById('kategoriId').value;
    const name = document.getElementById('kategoriAdi').value;
    if (!id) { showToast('❌ Silinecek kategori bulunamadı', 'error'); return; }
    showConfirm('🗑️ Kategori Sil', `"${name}" kategorisini ve içindeki TÜM görselleri silmek istediğinize emin misiniz?`, (confirmed) => {
        if (confirmed) {
            fetch(ajaxUrl + '?islem=sil_kategori&id=' + id, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'csrf_token=' + csrfToken })
                .then(res => res.json())
                .then(data => { if(data.success){ showToast('✅ Kategori silindi'); closeModal('kategoriModal'); setTimeout(() => location.reload(), 500); } else { showToast('❌ Silme hatası', 'error'); } })
                .catch(err => showToast('❌ Hata', 'error'));
        }
    });
}
function kategoriDuzenleFromModal() {
    const select = document.getElementById('gorselKategori');
    const id = select.value;
    if (!id) { showToast('❌ Lütfen bir kategori seçin!', 'error'); return; }
    closeModal('gorselModal');
    openKategoriModal(parseInt(id));
}
function duzenleSeciliKategori() {
    const select = document.getElementById('kategoriDuzenleSelect');
    const id = select.value;
    if (!id) { showToast('❌ Lütfen bir kategori seçin!', 'error'); return; }
    openKategoriModal(parseInt(id));
}
function yeniKategoriFromModal() {
    closeModal('gorselModal');
    setTimeout(() => openKategoriModal(), 300);
}

// ========== FORM SUBMIT ==========
document.getElementById('gorselForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    let fd = new FormData(this);
    fd.append('csrf_token', csrfToken);
    fetch(ajaxUrl + '?islem=kaydet_gorsel', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => { if(data.success){ showToast('✅ Kaydedildi'); setTimeout(() => location.reload(), 500); } else { showToast('❌ ' + (data.message || 'Hata'), 'error'); } })
        .catch(err => showToast('❌ Kaydetme hatası', 'error'));
});
document.getElementById('kategoriForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    let fd = new FormData(this);
    fd.append('csrf_token', csrfToken);
    fetch(ajaxUrl + '?islem=kaydet_kategori', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => { if(data.success){ showToast('✅ Kategori kaydedildi'); setTimeout(() => location.reload(), 500); } else { showToast('❌ Hata', 'error'); } })
        .catch(err => showToast('❌ Kaydetme hatası', 'error'));
});

// ========== SLUG OTOMATİK ==========
document.getElementById('kategoriAdi')?.addEventListener('input', function() {
    let slug = this.value.toLowerCase().replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s').replace(/ı/g,'i').replace(/ö/g,'o').replace(/ç/g,'c').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    document.getElementById('kategoriSlug').value = slug;
});
document.getElementById('gorselBaslik')?.addEventListener('input', function() {
    const slugInput = document.getElementById('gorselSlug');
    if (slugInput && !slugInput.dataset.manual) {
        let slug = this.value.toLowerCase()
            .replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s')
            .replace(/ı/g,'i').replace(/ö/g,'o').replace(/ç/g,'c')
            .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
        slugInput.value = slug;
    }
});

document.getElementById('gorselSlug')?.addEventListener('input', function() {
    this.dataset.manual = '1';
});

// ========== SEO KARAKTER SAYAÇLARI ==========
document.getElementById('gorselSeoTitle')?.addEventListener('input', function() {
    document.getElementById('gorselSeoTitleCount').textContent = this.value.length;
});
document.getElementById('gorselSeoDesc')?.addEventListener('input', function() {
    document.getElementById('gorselSeoDescCount').textContent = this.value.length;
});

// ========== ÖNİZLEME ==========
document.getElementById('gorselBaslik')?.addEventListener('input', function() {
    document.getElementById('onizlemeBaslik').innerHTML = this.value || 'Başlık';
});
document.getElementById('gorselAciklama')?.addEventListener('input', function() {
    document.getElementById('onizlemeAciklama').innerHTML = (this.value || 'Açıklama...').substring(0, 100);
});
document.getElementById('gorselResim')?.addEventListener('input', function() {
    if (this.value) document.getElementById('onizlemeResim').src = this.value;
});
document.getElementById('gorselThumb')?.addEventListener('input', function() {
    if (this.value) document.getElementById('onizlemeResim').src = this.value;
});
document.getElementById('gorselVideoUrl')?.addEventListener('input', function() {
    const url = this.value.trim();
    if (!url) return;
    const m = url.match(/(?:youtube(?:-nocookie)?\.com\/(?:embed\/|watch\?(?:.*&)?v=|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
    if (m) {
        document.getElementById('onizlemeResim').src = 'https://img.youtube.com/vi/' + m[1] + '/mqdefault.jpg';
    }
});
document.getElementById('gorselKategori')?.addEventListener('change', function() {
    let text = this.options[this.selectedIndex]?.text || 'Kategori';
    document.getElementById('onizlemeKategori').innerHTML = '<i class="fas fa-folder"></i> ' + text;
});

// ========== TOGGLE SWITCH ==========
function updateToggleText() {
    const checkbox = document.getElementById('gorselDurum');
    const text = document.getElementById('durumText');
    if (checkbox && text) {
        if (checkbox.checked) {
            text.innerHTML = '🟢 Aktif';
            text.style.color = '#10b981';
        } else {
            text.innerHTML = '🔴 Pasif';
            text.style.color = '#ef4444';
        }
    }
}
document.getElementById('gorselDurum')?.addEventListener('change', updateToggleText);

// ========== FİLTRELEME ==========
function filterByKategori(kategoriId) {
    document.querySelectorAll('.kategori-tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'white';
        btn.style.color = '#475569';
        btn.style.border = '1px solid #cbd5e1';
    });
    const aktifBtn = document.querySelector(`.kategori-tab-btn[data-kategori="${kategoriId}"]`);
    if (aktifBtn) {
        aktifBtn.classList.add('active');
        aktifBtn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        aktifBtn.style.color = 'white';
        aktifBtn.style.border = 'none';
    }
    const baslikSpan = document.getElementById('aktifKategoriBaslik');
    const baseTitle = aktifTip === 'video' ? 'Tüm Videolar' : 'Tüm Fotoğraflar';
    if (kategoriId === 'hepsi') { baslikSpan.innerHTML = baseTitle; }
    else if (aktifBtn) { baslikSpan.innerHTML = aktifBtn.innerText.replace(/\(\d+\)/, '').trim(); }
    
    document.querySelectorAll('.gallery-card').forEach(card => {
        if (kategoriId === 'hepsi' || card.getAttribute('data-kategori-id') == kategoriId) {
            card.style.display = '';
        } else { card.style.display = 'none'; }
    });
}

// ========== ÇÖP KUTUSU ==========
function copKutusunuAc() {
    const panel = document.getElementById('copPaneli');
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        copListesiniGetir();
    } else { panel.style.display = 'none'; }
}
function kapatCopPaneli() { document.getElementById('copPaneli').style.display = 'none'; }

function copListesiniGetir() {
    fetch(ajaxUrl + '?islem=cop_listesi')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('copListesi');
            if (data.success && data.data.length > 0) {
                let html = '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px,1fr)); gap:20px;">';
                data.data.forEach(resim => {
                    const isVid = (resim.medya_tipi === 'video');
                    let thumb = resim.thumbnail_url || resim.resim_url || '';
                    if (isVid && !thumb && resim.video_url) {
                        const m = resim.video_url.match(/(?:youtube(?:-nocookie)?\.com\/(?:embed\/|watch\?(?:.*&)?v=|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
                        if (m) thumb = 'https://img.youtube.com/vi/' + m[1] + '/mqdefault.jpg';
                    }
                    html += `
                        <div style="background:white; border-radius:20px; overflow:hidden; border:1px solid #e2e8f0;">
                            <div style="height:200px; overflow:hidden; position:relative; background:#f1f5f9;">
                                ${thumb ? `<img src="${thumb}" style="width:100%; height:100%; object-fit:cover;">` : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#cbd5e1;"><i class="fas fa-${isVid?'video':'image'}" style="font-size:3rem;"></i></div>`}
                                <div style="position:absolute; top:10px; left:10px; background:${isVid?'rgba(220,38,38,0.9)':'rgba(37,99,235,0.9)'}; color:white; padding:3px 9px; border-radius:20px; font-size:10px; font-weight:800;">${isVid?'VİDEO':'FOTO'}</div>
                            </div>
                            <div style="padding:15px;">
                                <strong>${escapeHtml((resim.baslik||'').substring(0, 50))}</strong>
                                <div style="display:flex; gap:15px; margin:10px 0; font-size:0.75rem; color:#64748b;">
                                    <span><i class="fas fa-folder" style="color:#f59e0b;"></i> ${escapeHtml(resim.kategori_adi || 'Kategorisiz')}</span>
                                    <span><i class="fas fa-calendar-alt" style="color:#10b981;"></i> ${resim.tarih}</span>
                                </div>
                                <div style="display:flex; gap:12px;">
                                    <button onclick="geriAl(${resim.id})" style="flex:1; padding:10px; background:#10b981; color:white; border:none; border-radius:12px; cursor:pointer; font-size:0.8rem; font-weight:500;"><i class="fas fa-undo"></i> Geri Al</button>
                                    <button onclick="kaliciSil(${resim.id})" style="flex:1; padding:10px; background:#ef4444; color:white; border:none; border-radius:12px; cursor:pointer; font-size:0.8rem; font-weight:500;"><i class="fas fa-trash"></i> Kalıcı Sil</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
                document.getElementById('copSayisi').innerHTML = data.data.length;
            } else {
                container.innerHTML = `<div style="text-align:center; padding:80px 20px;"><i class="fas fa-trash-alt" style="font-size:4rem; color:#cbd5e1;"></i><p style="margin-top:20px; color:#94a3b8; font-size:1rem;">Çöp kutusu boş</p></div>`;
            }
        })
        .catch(err => { document.getElementById('copListesi').innerHTML = '<div style="text-align:center; padding:40px; color:red;">Yüklenirken hata oluştu</div>'; });
}
function geriAl(id) {
    showConfirm('↩️ Geri Al', 'Bu öğeyi ana listeye geri almak istediğinize emin misiniz?', (confirmed) => {
        if (confirmed) {
            fetch(ajaxUrl + '?islem=geri_al&id=' + id, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'csrf_token=' + csrfToken })
                .then(res => res.json())
                .then(data => { if(data.success){ showToast('✅ Geri alındı'); copListesiniGetir(); setTimeout(() => location.reload(), 500); } else { showToast('❌ Hata', 'error'); } })
                .catch(err => showToast('❌ Hata', 'error'));
        }
    });
}
function kaliciSil(id) {
    showConfirm('⚠️ Kalıcı Sil', 'Bu öğeyi KALICI OLARAK silmek istediğinize emin misiniz?', (confirmed) => {
        if (confirmed) {
            fetch(ajaxUrl + '?islem=kalici_sil&id=' + id, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'csrf_token=' + csrfToken })
                .then(res => res.json())
                .then(data => { if(data.success){ showToast('✅ Kalıcı olarak silindi'); copListesiniGetir(); setTimeout(() => location.reload(), 500); } else { showToast('❌ Hata', 'error'); } })
                .catch(err => showToast('❌ Hata', 'error'));
        }
    });
}
function copTemizle() {
    showConfirm('🗑️ Çöp Kutusunu Temizle', 'Çöp kutusundaki TÜM öğeleri KALICI OLARAK silmek istediğinize emin misiniz?', (confirmed) => {
        if (confirmed) {
            fetch(ajaxUrl + '?islem=cöp_temizle', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'csrf_token=' + csrfToken })
                .then(res => res.json())
                .then(data => { if(data.success){ showToast('✅ Çöp kutusu temizlendi'); kapatCopPaneli(); setTimeout(() => location.reload(), 500); } else { showToast('❌ Hata', 'error'); } })
                .catch(err => showToast('❌ Hata', 'error'));
        }
    });
}

// ========== SEO (GENEL SAYFA) ==========
document.querySelectorAll('#seoModal .seo-lang-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('#seoModal .seo-lang-btn').forEach(b => {
            b.classList.remove('active');
        });
        this.classList.add('active');
        const lang = this.dataset.lang;
        document.querySelectorAll('#seoModal .seo-lang-content').forEach(el => {
            el.style.display = el.dataset.lang === lang ? 'block' : 'none';
        });
    });
});

function seoResimYukle() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('resim', file);
        formData.append('csrf_token', csrfToken);
        fetch(ajaxUrl + '?islem=resim_yukle', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    document.querySelector('#seoModal input[name="seo_og_image"]').value = data.url;
                    showToast('✅ Resim yüklendi');
                } else {
                    showToast('❌ Hata: ' + data.message, 'error');
                }
            })
            .catch(err => showToast('❌ Bağlantı hatası', 'error'));
    };
    input.click();
}

function saveSeo() {
    const form = document.getElementById('seoForm');
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => { data[key] = value; });
    const btn = document.querySelector('#seoModal .btn-submit');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    btn.disabled = true;
    fetch(ajaxUrl + '?islem=seo_kaydet', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                showToast('✅ SEO ayarları kaydedildi!');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('❌ Hata: ' + (result.message || 'Bilinmeyen hata'), 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            showToast('❌ Bağlantı hatası', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) { return m === '&' ? '&amp;' : m === '<' ? '&lt;' : '&gt;'; });
}

// ========== GÖRSEL KIRPMA ==========
let kirpCropper = null;
let kirpHedefInput = null;

function kirpModalAc(inputId) {
    const input = document.getElementById(inputId);
    let src = input.value.trim();
    
    if (!src) {
        const resimInput = document.getElementById('gorselResim');
        const videoInput = document.getElementById('gorselVideoUrl');
        if (resimInput && resimInput.value) {
            src = resimInput.value;
        } else if (videoInput && videoInput.value) {
            const m = videoInput.value.match(/(?:youtube(?:-nocookie)?\.com\/(?:embed\/|watch\?(?:.*&)?v=|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
            if (m) src = 'https://img.youtube.com/vi/' + m[1] + '/maxresdefault.jpg';
        }
    }
    
    if (!src) { showToast('❌ Önce bir görsel seçin!', 'error'); return; }
    
    kirpHedefInput = inputId;
    const img = document.getElementById('kirpImage');
    img.src = src;
    
    openModal('kirpModal');
    
    if (kirpCropper) { kirpCropper.destroy(); kirpCropper = null; }
    
    img.onload = function() {
        kirpCropper = new Cropper(img, {
            viewMode: 2,
            dragMode: 'move',
            autoCropArea: 0.9,
            aspectRatio: NaN,
            background: false,
            responsive: true,
            checkCrossOrigin: false,
            crossOrigin: 'anonymous'
        });
    };
}

function kirpOran(w, h) {
    if (!kirpCropper) return;
    kirpCropper.setAspectRatio((w === 0) ? NaN : (w / h));
}

function kirpModalKapat() {
    if (kirpCropper) { kirpCropper.destroy(); kirpCropper = null; }
    closeModal('kirpModal');
}

function kirpKaydet() {
    if (!kirpCropper) { showToast('❌ Kırpma başlatılamadı', 'error'); return; }
    
    const canvas = kirpCropper.getCroppedCanvas({
        maxWidth: 1920,
        maxHeight: 1920,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });
    
    if (!canvas) { showToast('❌ Kırpma hatası', 'error'); return; }
    
    showToast('⏳ Kaydediliyor...');
    
    canvas.toBlob(function(blob) {
        const fd = new FormData();
        fd.append('kirp_resim', blob, 'kirpilan_' + Date.now() + '.png');
        fd.append('csrf_token', csrfToken);
        
        fetch(ajaxUrl + '?islem=kirp_yukle', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(kirpHedefInput).value = data.url;
                    document.getElementById('onizlemeResim').src = data.url;
                    showToast('✅ Görsel kırpıldı!');
                    kirpModalKapat();
                } else {
                    showToast('❌ ' + (data.message || 'Hata'), 'error');
                }
            })
            .catch(() => showToast('❌ Bağlantı hatası', 'error'));
    }, 'image/png');
}
</script>
</body>
</html>