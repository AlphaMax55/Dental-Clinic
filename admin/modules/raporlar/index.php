<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
$mevcut_modul = isset($_GET['modul']) ? $_GET['modul'] : 'raporlar';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'siteici';
$tarih_baslangic = isset($_GET['baslangic']) ? $_GET['baslangic'] : '2025-01-01';
$tarih_bitis = isset($_GET['bitis']) ? $_GET['bitis'] : date('Y-m-d');
$alt_grup = isset($_GET['alt_grup']) ? $_GET['alt_grup'] : 'tum';

// Bot koşulu oluşturma
$bot_kosul = "";
if ($alt_grup == 'insan') {
    $bot_kosul = " AND is_bot = 0";
} elseif ($alt_grup == 'bot') {
    $bot_kosul = " AND is_bot = 1";
}

// CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rapor_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Tarih', 'Ziyaret', 'Tekil Ziyaretçi', 'Ortalama Süre (sn)', 'Ortalama Sayfa', 'Bot', 'İnsan']);
    
    $stmt = $db->prepare("SELECT 
        DATE(tarih) as gun, 
        COUNT(*) as ziyaret, 
        COUNT(DISTINCT oturum_id) as tekil,
        AVG(oturum_suresi) as ortalama_sure,
        AVG(sayfa_sayisi) as ortalama_sayfa,
        COUNT(CASE WHEN is_bot = 1 THEN 1 END) as bot_sayisi,
        COUNT(CASE WHEN is_bot = 0 THEN 1 END) as insan_sayisi
        FROM site_istatistikler 
        WHERE DATE(tarih) BETWEEN ? AND ? {$bot_kosul}
        GROUP BY DATE(tarih) 
        ORDER BY gun ASC");
    $stmt->execute([$tarih_baslangic, $tarih_bitis]);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['gun'], 
            $row['ziyaret'], 
            $row['tekil'],
            round($row['ortalama_sure'] ?? 0, 1),
            round($row['ortalama_sayfa'] ?? 0, 1),
            $row['bot_sayisi'] ?? 0,
            $row['insan_sayisi'] ?? 0
        ]);
    }
    fclose($output);
    exit;
}

// ========== ORTALAMA SÜRE ve SAYFA SAYISI ==========
$stmt = $db->prepare("SELECT 
    AVG(oturum_suresi) as ortalama_sure,
    AVG(sayfa_sayisi) as ortalama_sayfa,
    COUNT(CASE WHEN oturum_suresi < 60 AND sayfa_sayisi <= 1 THEN 1 END) as hemen_cikan,
    COUNT(*) as toplam_oturum
    FROM site_istatistikler 
    WHERE DATE(tarih) BETWEEN ? AND ? {$bot_kosul}
    AND oturum_id IN (SELECT DISTINCT oturum_id FROM site_istatistikler WHERE DATE(tarih) BETWEEN ? AND ? {$bot_kosul})");
$stmt->execute([$tarih_baslangic, $tarih_bitis, $tarih_baslangic, $tarih_bitis]);
$istatistikler = $stmt->fetch();

// Site içi istatistikler
$stmt = $db->prepare("SELECT DATE(tarih) as gun, COUNT(*) as ziyaret, COUNT(DISTINCT oturum_id) as tekil FROM site_istatistikler WHERE DATE(tarih) BETWEEN ? AND ? {$bot_kosul} GROUP BY DATE(tarih) ORDER BY gun ASC");
$stmt->execute([$tarih_baslangic, $tarih_bitis]);
$gunluk = $stmt->fetchAll();

// ========== En çok ziyaret edilen sayfalar ==========
$stmt = $db->prepare("SELECT 
    sayfa, 
    COUNT(*) as ziyaret,
    AVG(oturum_suresi) as ortalama_sure,
    AVG(sayfa_sayisi) as ortalama_sayfa,
    COUNT(DISTINCT oturum_id) as tekil_ziyaretci
    FROM site_istatistikler 
    WHERE DATE(tarih) BETWEEN ? AND ? {$bot_kosul}
    AND sayfa IS NOT NULL 
    AND sayfa != ''
    GROUP BY sayfa 
    ORDER BY ziyaret DESC 
    ");
$stmt->execute([$tarih_baslangic, $tarih_bitis]);
$top_sayfalar = $stmt->fetchAll();

// Toplam istatistikler
$stmt = $db->prepare("SELECT 
    COUNT(*) as toplam, 
    COUNT(DISTINCT oturum_id) as tekil_ziyaretci, 
    COUNT(DISTINCT ip) as tekil_ip,
    AVG(oturum_suresi) as ortalama_sure,
    AVG(sayfa_sayisi) as ortalama_sayfa
    FROM site_istatistikler 
    WHERE DATE(tarih) BETWEEN ? AND ? {$bot_kosul}");
$stmt->execute([$tarih_baslangic, $tarih_bitis]);
$genel = $stmt->fetch();

// ========== Hemen Çıkma Oranı ==========
$bounce_rate = 0;
if ($genel['tekil_ziyaretci'] > 0) {
    $stmt = $db->prepare("SELECT COUNT(DISTINCT oturum_id) as hemen_cikan 
        FROM site_istatistikler 
        WHERE DATE(tarih) BETWEEN ? AND ? {$bot_kosul}
        AND oturum_suresi < 60 
        AND sayfa_sayisi <= 1");
    $stmt->execute([$tarih_baslangic, $tarih_bitis]);
    $bounce = $stmt->fetch();
    $bounce_rate = round(($bounce['hemen_cikan'] / $genel['tekil_ziyaretci']) * 100, 1);
}

// ========== LOKASYON RAPORU ==========
$stmt = $db->prepare("SELECT ulke, COUNT(*) as sayi FROM site_istatistikler 
    WHERE ulke IS NOT NULL AND ulke != '' 
    AND DATE(tarih) BETWEEN ? AND ? {$bot_kosul}
    GROUP BY ulke ORDER BY sayi DESC LIMIT 10");
$stmt->execute([$tarih_baslangic, $tarih_bitis]);
$ulkeler = $stmt->fetchAll();

$stmt = $db->prepare("SELECT sehir, COUNT(*) as sayi FROM site_istatistikler 
    WHERE sehir IS NOT NULL AND sehir != '' 
    AND DATE(tarih) BETWEEN ? AND ? {$bot_kosul}
    GROUP BY sehir ORDER BY sayi DESC LIMIT 10");
$stmt->execute([$tarih_baslangic, $tarih_bitis]);
$sehirler = $stmt->fetchAll();

// ========== BOT/İNSAN ÖZETİ ==========
$stmt_bot_total = $db->prepare("SELECT 
    COUNT(CASE WHEN is_bot = 1 THEN 1 END) as bot_sayisi,
    COUNT(CASE WHEN is_bot = 0 THEN 1 END) as insan_sayisi
    FROM site_istatistikler 
    WHERE DATE(tarih) BETWEEN ? AND ?");
$stmt_bot_total->execute([$tarih_baslangic, $tarih_bitis]);
$bot_total = $stmt_bot_total->fetch();

// Süre formatla
function sure_formatla($saniye) {
    if (!$saniye || $saniye < 0) return '0sn';
    $dk = floor($saniye / 60);
    $sn = $saniye % 60;
    if ($dk > 0) {
        return $dk . 'dk ' . $sn . 'sn';
    }
    return $sn . 'sn';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>İstatistik ve Raporlar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ========== TIKLAYINCA POPUP ========== */
.click-tip {
    position: relative;
    cursor: pointer;
    border-bottom: 1px dashed #2563eb;
    color: #2563eb;
    font-weight: 500;
    transition: all 0.2s;
}

.click-tip:hover {
    color: #1d4ed8;
    border-bottom-color: #1d4ed8;
}

/* Popup Container */
.tip-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #0f172a;
    color: #f1f5f9;
    padding: 20px 28px;
    border-radius: 16px;
    font-size: 14px;
    font-weight: 400;
    max-width: 600px;
    min-width: 300px;
    max-height: 400px;
    overflow-y: auto;
    word-break: break-all;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    z-index: 99999;
    border: 1px solid rgba(255,255,255,0.1);
    line-height: 1.6;
    text-align: left;
}

.tip-popup .tip-close {
    position: absolute;
    top: 8px;
    right: 14px;
    cursor: pointer;
    font-size: 22px;
    color: #94a3b8;
    transition: color 0.2s;
    background: none;
    border: none;
    font-weight: 700;
}

.tip-popup .tip-close:hover {
    color: #ef4444;
}

.tip-popup .tip-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #94a3b8;
    margin-bottom: 8px;
    display: block;
}

.tip-popup .tip-content {
    font-size: 13px;
    color: #e2e8f0;
    word-break: break-all;
    white-space: pre-wrap;
}

/* Popup arka plan overlay */
.tip-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4);
    z-index: 99998;
    backdrop-filter: blur(4px);
}

@media (max-width: 768px) {
    .tip-popup {
        max-width: 90%;
        min-width: unset;
        padding: 16px 20px;
        font-size: 13px;
        max-height: 300px;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border-radius: 12px;
    }
    .tip-popup .tip-content {
        font-size: 12px;
    }
}

    /* KÜRESEL MARGİN SIFIRLAMASI BURADAN KALDIRILDI! Sadece .stats-container içine etki etmeli */
    .stats-container * { box-sizing: border-box; }
    .stats-container { padding: 20px; background: #f8fafc; min-height: 100vh; width: 100%; max-width: 100%; overflow-x: hidden; }
    .stats-header { margin-bottom: 30px; margin-top: 0; }
    .stats-header h2 { font-size: 24px; color: #0f172a; margin-bottom: 8px; margin-top: 0; }
    .stats-header p { color: #64748b; font-size: 14px; margin: 0; }
    
    .stats-tabs { display: flex; gap: 8px; margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 0; }
    .stats-tab { padding: 12px 24px; font-size: 14px; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .stats-tab:hover { color: #2563eb; }
    .stats-tab.active { color: #2563eb; border-bottom-color: #2563eb; }

    /* ========== FİLTRE BUTONLARI ========== */
    .group-filters { display: flex; gap: 10px; margin-bottom: 25px; align-items: center; flex-wrap: wrap; }
    .group-btn { padding: 8px 18px; border-radius: 12px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; background: white; color: #475569; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .group-btn:hover { background: #f1f5f9; border-color: #94a3b8; }
    .group-btn.active-tum { background: #3b82f6; color: white; border-color: #3b82f6; }
    .group-btn.active-insan { background: #10b981; color: white; border-color: #10b981; }
    .group-btn.active-bot { background: #ef4444; color: white; border-color: #ef4444; }
    
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; width: 100%; max-width: 100%; }
    .stat-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.3s; }
    .stat-card:hover { box-shadow: 0 10px 40px rgba(0,0,0,0.08); transform: translateY(-2px); }
    .stat-card .icon { width: 48px; height: 48px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .stat-card .value { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
    .stat-card .label { font-size: 13px; color: #64748b; }
    .stat-card .trend { font-size: 12px; margin-top: 8px; }
    .stat-card .trend.danger { color: #ef4444; }
    .stat-card .trend.success { color: #10b981; }
    
    .filter-bar { background: white; border-radius: 16px; padding: 20px; margin-bottom: 30px; border: 1px solid #e2e8f0; display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; width: 100%; }
    .filter-group { display: flex; flex-direction: column; gap: 8px; }
    .filter-group label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
    .filter-group input { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; transition: all 0.2s; }
    .filter-group input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .btn-primary { background: #2563eb; color: white; border: none; padding: 10px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
    .btn-success { background: #10b981; color: white; border: none; padding: 10px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-success:hover { background: #059669; }
    .btn-google { background: #ea4335; color: white; border: none; padding: 10px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-google:hover { background: #c5221f; }
    
    .table-wrapper { background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 30px; width: 100%; max-width: 100%; }
    .table-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; background: #fafbfc; }
    .table-header h3 { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
    .table-header small { font-size: 13px; color: #64748b; font-weight: 400; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    th { background: #f8fafc; font-weight: 600; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    td { color: #334155; font-size: 13px; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    .badge-green { background: #d1fae5; color: #065f46; }
    .badge-purple { background: #e9d5ff; color: #6b21a5; }
    .badge-orange { background: #fed7aa; color: #92400e; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-cyan { background: #cffafe; color: #0891b2; }
    .badge-secondary { background: #e2e8f0; color: #475569; }
    .badge-pink { background: #fce7f3; color: #be185d; }
    .badge-indigo { background: #e0e7ff; color: #3730a3; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
    
    .sc-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    .sc-stat-card { background: linear-gradient(135deg, #1e1b4b 0%, #2e1065 100%); border-radius: 20px; padding: 24px; color: white; }
    .sc-stat-card .value { font-size: 32px; font-weight: 800; margin-bottom: 8px; }
    .sc-stat-card .label { font-size: 13px; opacity: 0.8; }
    
    .chart-container { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 30px; width: 100%; max-width: 100%; overflow: hidden; }
    .chart-container h3 { font-size: 16px; font-weight: 600; margin-bottom: 20px; color: #0f172a; margin-top: 0; }
    
    .canli-badge {
        animation: pulse 2s infinite;
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #22c55e;
        margin-right: 6px;
    }
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(0.8); }
        100% { opacity: 1; transform: scale(1); }
    }
    
    .visitor-table tbody tr {
        transition: background 0.3s ease;
    }
    .visitor-table tbody tr.new-row {
        animation: highlightRow 1.5s ease;
    }
    @keyframes highlightRow {
        0% { background: #fffbcc; }
        100% { background: transparent; }
    }
    
    .lokasyon-etiketi {
        display: inline-block;
        background: #f0fdf4;
        color: #065f46;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        margin: 1px 0;
        border: 1px solid #bbf7d0;
    }
    
    .btn-danger {
        background: #dc2626;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
    }
    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    .btn-danger:active {
        transform: translateY(0);
    }
    
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .sc-stats { grid-template-columns: 1fr 1fr; }
        .filter-bar { flex-direction: column; align-items: stretch; }
        th, td { padding: 8px 10px; font-size: 11px; }
        .table-responsive { overflow-x: auto; }
    }
</style>
</head>
<body>

<div class="stats-container">
    <div class="stats-header">
        <h2>📊 İstatistik ve Raporlar</h2>
        <p>Site trafiği, ziyaretçi analizleri ve Google arama performansınız tek bir yerde.</p>
    </div>
    
    <div class="stats-tabs">
<button class="stats-tab <?php echo $activeTab == 'siteici' ? 'active' : ''; ?>" onclick="location.href='?modul=<?php echo $mevcut_modul; ?>&tab=siteici'">📈 Site İçi İstatistikler</button>
<button class="stats-tab <?php echo $activeTab == 'searchconsole' ? 'active' : ''; ?>" onclick="location.href='?modul=<?php echo $mevcut_modul; ?>&tab=searchconsole'">🔍 Google Search Console</button>
    </div>
    
    <?php if ($activeTab == 'siteici'): ?>
    <div class="stats-tab-content">
        
        <!-- ========== GRUP FİLTRE BUTONLARI ========== -->
        <div class="group-filters">
            <span style="font-size: 13px; font-weight: 600; color: #475569;">Grup Filtresi:</span>
<a href="?modul=<?php echo $mevcut_modul; ?>&tab=siteici&alt_grup=tum&baslangic=<?php echo $tarih_baslangic; ?>&bitis=<?php echo $tarih_bitis; ?>" class="group-btn <?php echo $alt_grup == 'tum' ? 'active-tum' : ''; ?>">
    🌐 Tüm Ziyaretçiler (<?php echo ($bot_total['bot_sayisi'] ?? 0) + ($bot_total['insan_sayisi'] ?? 0); ?>)
</a>
<a href="?modul=<?php echo $mevcut_modul; ?>&tab=siteici&alt_grup=insan&baslangic=<?php echo $tarih_baslangic; ?>&bitis=<?php echo $tarih_bitis; ?>" class="group-btn <?php echo $alt_grup == 'insan' ? 'active-insan' : ''; ?>">
    👤 İnsan Grubu (<?php echo $bot_total['insan_sayisi'] ?? 0; ?>)
</a>
<a href="?modul=<?php echo $mevcut_modul; ?>&tab=siteici&alt_grup=bot&baslangic=<?php echo $tarih_baslangic; ?>&bitis=<?php echo $tarih_bitis; ?>" class="group-btn <?php echo $alt_grup == 'bot' ? 'active-bot' : ''; ?>">
    🤖 Bot Grubu (<?php echo $bot_total['bot_sayisi'] ?? 0; ?>)
</a>
        </div>

        <div class="filter-bar">
            <div class="filter-group"><label>📅 Başlangıç</label><input type="date" id="baslangic" value="<?php echo $tarih_baslangic; ?>"></div>
            <div class="filter-group"><label>📅 Bitiş</label><input type="date" id="bitis" value="<?php echo $tarih_bitis; ?>"></div>
            <div class="filter-group"><button class="btn-primary" onclick="filterDate()">🎯 Filtrele</button></div>
            <div class="filter-group"><button class="btn-success" onclick="exportData()">📥 CSV Dışa Aktar</button></div>
        </div>
        
        <!-- ========== ÖZET KARTLARI ========== -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon" style="background:#dbeafe;"><span style="font-size:24px;">📄</span></div>
                <div class="value"><?php echo number_format($genel['toplam'] ?? 0); ?></div>
                <div class="label">Toplam Sayfa Görüntüleme</div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#d1fae5;"><span style="font-size:24px;">👤</span></div>
                <div class="value"><?php echo number_format($genel['tekil_ziyaretci'] ?? 0); ?></div>
                <div class="label">Tekil Ziyaretçi</div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#fed7aa;"><span style="font-size:24px;">🌐</span></div>
                <div class="value"><?php echo number_format($genel['tekil_ip'] ?? 0); ?></div>
                <div class="label">Tekil IP</div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#e9d5ff;"><span style="font-size:24px;">📊</span></div>
                <div class="value"><?php echo number_format(count($gunluk)); ?></div>
                <div class="label">Aktif Gün</div>
            </div>
            
            <div class="stat-card">
                <div class="icon" style="background:#fce7f3;"><span style="font-size:24px;">⏱️</span></div>
                <div class="value"><?php echo sure_formatla($genel['ortalama_sure'] ?? 0); ?></div>
                <div class="label">Ortalama Oturum Süresi</div>
                <div class="trend <?php echo ($genel['ortalama_sure'] ?? 0) > 60 ? 'success' : 'danger'; ?>">
                    <?php echo ($genel['ortalama_sure'] ?? 0) > 60 ? '✅ İyi' : '⚠️ Kısa'; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#d1fae5;"><span style="font-size:24px;">📑</span></div>
                <div class="value"><?php echo round($genel['ortalama_sayfa'] ?? 0, 1); ?></div>
                <div class="label">Ortalama Sayfa / Oturum</div>
                <div class="trend <?php echo ($genel['ortalama_sayfa'] ?? 0) > 2 ? 'success' : 'danger'; ?>">
                    <?php echo ($genel['ortalama_sayfa'] ?? 0) > 2 ? '✅ Derin Gezinme' : '⚠️ Yüzeysel'; ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon" style="background:#e0e7ff;"><span style="font-size:24px;">🤖</span></div>
                <div class="value">
                    <?php echo ($bot_total['bot_sayisi'] ?? 0) + ($bot_total['insan_sayisi'] ?? 0); ?>
                </div>
                <div class="label">
                    <span class="badge badge-red">🤖 Bot: <?php echo $bot_total['bot_sayisi'] ?? 0; ?></span>
                    <span class="badge badge-green">👤 İnsan: <?php echo $bot_total['insan_sayisi'] ?? 0; ?></span>
                </div>
                <div class="trend success">Toplam Ziyaret</div>
            </div>
        </div>
        
        <!-- ========== 🌍 LOKASYON RAPORU ========== -->
        <div class="stats-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div class="stat-card">
                <div class="icon" style="background:#dbeafe;"><span style="font-size:24px;">🌍</span></div>
                <h4 style="margin-bottom: 16px;">Ülke Dağılımı</h4>
                <?php if (count($ulkeler) > 0): ?>
                    <?php foreach ($ulkeler as $u): ?>
                        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f1f5f9;">
                            <span><?php echo htmlspecialchars($u['ulke']); ?></span>
                            <span class="badge badge-blue"><?php echo $u['sayi']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px 0;">Henüz lokasyon verisi yok</p>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#d1fae5;"><span style="font-size:24px;">📍</span></div>
                <h4 style="margin-bottom: 16px;">Şehir Dağılımı</h4>
                <?php if (count($sehirler) > 0): ?>
                    <?php foreach ($sehirler as $s): ?>
                        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f1f5f9;">
                            <span><?php echo htmlspecialchars($s['sehir']); ?></span>
                            <span class="badge badge-green"><?php echo $s['sayi']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px 0;">Henüz lokasyon verisi yok</p>
                <?php endif; ?>
            </div>
        </div>
        
<!-- ========== 🔥 ZİYARETÇİ AKIŞI & VERİ YÖNETİMİ ========== -->
<div class="table-wrapper" style="border-color: #fecaca; margin-top: 30px;">
    <div class="table-header" style="background: #fef2f2; border-bottom-color: #fecaca; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="color: #991b1b; margin:0;">🗑️ Veri Yönetimi <span class="badge badge-red">Dikkat!</span></h3>
            <small class="text-muted">Seçili kayıtları kalıcı olarak siler. Bu işlem geri alınamaz!</small>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button class="btn btn-danger" onclick="confirmDeleteSelected()">🗑️ Seçilenleri Sil</button>
            <button class="btn btn-danger" onclick="confirmDeleteAll()" style="background: #b91c1c;">⚠️ Tümünü Sil</button>
        </div>
    </div>
    <div style="padding: 12px 20px; background: #fef2f2; border-bottom: 1px solid #fecaca; display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: #991b1b; font-size: 14px;">
            <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes(this)" style="width: 18px; height: 18px; cursor: pointer;">
            Tümünü Seç
        </label>
        <span style="font-size: 13px; color: #64748b;">
            <span id="selectedCount">0</span> kayıt seçili
        </span>
        <span style="font-size: 12px; color: #ef4444;">⚠️ Seçili kayıtlar kalıcı olarak silinecek</span>
    </div>
    <div style="width: 100%; max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table style="min-width: 1100px; width: 100%;">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="selectAllTable" onchange="toggleAllCheckboxes(this)" style="width: 18px; height: 18px; cursor: pointer;">
                    </th>
                    <th>#</th>
                    <th>IP</th>
                    <th>📍 Konum</th>
                    <th>İlçe</th>
                    <th>Sayfa</th>
                    <th>Durum</th>
                    <th>🤖 Bot Tipi</th>
                    <th>🧠 AI Kaynak</th>
                    <th>Süre</th>
                    <th>Sayfa Sayısı</th>
                    <th>📱 Tarayıcı</th>
                    <th>🔗 Referer</th>
                    <th>🌐 Yayın Adı (İSS)</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stmt_delete = $db->prepare("SELECT 
                    id, ip, sehir, ulke, ilce, sayfa,
                    oturum_suresi, sayfa_sayisi, tarih,
                    is_bot, bot_tipi, ai_kaynak, user_agent, referer,
                    yayin_adi
                    FROM site_istatistikler 
                    WHERE DATE(tarih) BETWEEN ? AND ? {$bot_kosul}
                    AND sayfa IS NOT NULL 
                    AND sayfa != ''
                    ORDER BY id DESC 
                    ");
                $stmt_delete->execute([$tarih_baslangic, $tarih_bitis]);
                $delete_list = $stmt_delete->fetchAll();
                
                $i = 1; 
                foreach ($delete_list as $s): 
                    $sure = $s['oturum_suresi'] ?? 0;
                    if ($sure > 3600) $sure = 0;
                    $dk = floor($sure / 60);
                    $sn = $sure % 60;
                    $sureText = $dk > 0 ? $dk . 'dk ' . $sn . 'sn' : $sn . 'sn';
                    
                    $lokasyon = '';
                    $bayrak = '🌍';
                    if ($s['sehir'] && $s['ulke']) {
                        $lokasyon = $s['sehir'] . ', ' . $s['ulke'];
                    } elseif ($s['sehir']) {
                        $lokasyon = $s['sehir'];
                    } elseif ($s['ulke']) {
                        $lokasyon = $s['ulke'];
                    } else {
                        $lokasyon = 'Bilinmiyor';
                    }
                    
                    $ilce_text = $s['ilce'] ? htmlspecialchars($s['ilce']) : '-';
                    
                    $ulkeler = [
                        'Türkiye' => '🇹🇷', 'Turkey' => '🇹🇷',
                        'Almanya' => '🇩🇪', 'Germany' => '🇩🇪',
                        'İngiltere' => '🇬🇧', 'United Kingdom' => '🇬🇧',
                        'Amerika' => '🇺🇸', 'United States' => '🇺🇸',
                        'Russia' => '🇷🇺', 'Rusya' => '🇷🇺',
                        'Mısır' => '🇪🇬', 'Egypt' => '🇪🇬',
                    ];
                    $bayrak = $ulkeler[$s['ulke']] ?? '🌍';
                    
                    // DURUM BADGE
                    $durum_badge = '';
                    if ($s['is_bot'] == 1) {
                        if (strpos($s['bot_tipi'], 'Google') !== false) {
                            $durum_badge = '<span class="badge badge-red">🤖 Google</span>';
                        } elseif (strpos($s['bot_tipi'], 'Yandex') !== false) {
                            $durum_badge = '<span class="badge badge-red">🤖 Yandex</span>';
                        } elseif (strpos($s['bot_tipi'], 'Bing') !== false) {
                            $durum_badge = '<span class="badge badge-cyan">🤖 Bing</span>';
                        } elseif (strpos($s['bot_tipi'], 'AI') !== false) {
                            $durum_badge = '<span class="badge badge-purple">🤖 AI</span>';
                        } else {
                            $durum_badge = '<span class="badge badge-secondary">🤖 Bot</span>';
                        }
                    } else {
                        $durum_badge = '<span class="badge badge-green">👤 İnsan</span>';
                    }
                    
                    $bot_tipi_text = $s['bot_tipi'] ? htmlspecialchars($s['bot_tipi']) : '-';
                    $ai_kaynak_text = $s['ai_kaynak'] ? '<span class="badge badge-purple">🤖 ' . htmlspecialchars($s['ai_kaynak']) . '</span>' : '<span class="badge badge-secondary">-</span>';
        
                    // YAYIN ADI (İSS)
                    $yayin_adi_text = $s['yayin_adi'] ? htmlspecialchars($s['yayin_adi']) : '-';
                ?>
                <tr>
                    <td>
                        <input type="checkbox" class="row-checkbox" value="<?php echo $s['id']; ?>" style="width: 18px; height: 18px; cursor: pointer;">
                    </td>
                    <td><?php echo $i++; ?></td>
                    <td><code><?php echo htmlspecialchars($s['ip'] ?? '-'); ?></code></td>
                    <td><?php echo $bayrak . ' ' . htmlspecialchars($lokasyon); ?></td>
                    <td><span class="badge badge-secondary"><?php echo $ilce_text; ?></span></td>
                    <td><strong><?php echo htmlspecialchars($s['sayfa'] ?: '/'); ?></strong></td>
                    <td><?php echo $durum_badge; ?></td>
                    <td><span class="badge badge-secondary"><?php echo $bot_tipi_text; ?></span></td>
                    <td><?php echo $ai_kaynak_text; ?></td>
                    <td><span class="badge badge-cyan"><?php echo $sureText; ?></span></td>
                    <td><span class="badge badge-orange"><?php echo $s['sayfa_sayisi'] ?? 0; ?></span></td>
<!-- Tarayıcı - TIKLAYINCA POPUP -->
<td>
    <?php if ($s['user_agent']): ?>
        <span class="click-tip" data-tip="<?php echo htmlspecialchars($s['user_agent']); ?>" onclick="showTip(this)">
            <?php echo htmlspecialchars(substr($s['user_agent'], 0, 20) . '...'); ?>
        </span>
    <?php else: ?>
        -
    <?php endif; ?>
</td>

<!-- Referer - TIKLAYINCA POPUP -->
<td>
    <?php if ($s['referer'] && $s['referer'] != ''): ?>
        <span class="click-tip" data-tip="<?php echo htmlspecialchars($s['referer']); ?>" onclick="showTip(this)">
            <?php echo htmlspecialchars(substr($s['referer'], 0, 20) . '...'); ?>
        </span>
    <?php else: ?>
        <span class="badge badge-secondary">Direkt</span>
    <?php endif; ?>
</td>
                    <td><small><?php echo $yayin_adi_text; ?></small></td>
                    <td><?php echo date('d.m.Y H:i:s', strtotime($s['tarih'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($delete_list) == 0): ?>
                <tr><td colspan="19" style="text-align:center; padding:30px; color:#94a3b8;">📭 Silinecek veri yok</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <span style="font-size: 13px; color: #64748b;">
            <span id="selectedCountBottom">0</span> kayıt seçili
        </span>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-danger" onclick="confirmDeleteSelected()">🗑️ Seçilenleri Sil</button>
            <button class="btn btn-danger" onclick="confirmDeleteAll()" style="background: #b91c1c;">⚠️ Tümünü Sil</button>
        </div>
    </div>
</div>


    </div>
    <?php endif; ?>
    
    <?php if ($activeTab == 'searchconsole'): ?>
    <div class="stats-tab-content">
        <div class="filter-bar">
            <div class="filter-group"><label>🔍 Başlangıç</label><input type="date" id="sc_start" value="<?php echo date('Y-m-d', strtotime('-290 days')); ?>"></div>
            <div class="filter-group"><label>🔍 Bitiş</label><input type="date" id="sc_end" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="filter-group"><button class="btn-google" onclick="fetchSearchConsole()">📊 Verileri Getir</button></div>
        </div>
        <div id="searchConsoleResults">
            <div class="stat-card" style="text-align:center; padding:60px;">
                <span style="font-size:48px;">🔍</span>
                <p style="margin-top:16px; color:#64748b;">Tarih seçip "Verileri Getir" butonuna tıklayın.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ========== GRAFİK YÜKLEME ==========
document.addEventListener('DOMContentLoaded', function() {
    loadChart();
    loadPieChart();
});

function loadChart() {
    const canvas = document.getElementById('gunlukChart');
    if (!canvas) return;
    
    let baslangic = document.getElementById('baslangic')?.value || '<?php echo $tarih_baslangic; ?>';
    let bitis = document.getElementById('bitis')?.value || '<?php echo $tarih_bitis; ?>';
    let altGrup = '<?php echo $alt_grup; ?>';
    
    fetch(`/api/istatistik-grafik.php?baslangic=${baslangic}&bitis=${bitis}&alt_grup=${altGrup}`)
        .then(r => r.json())
        .then(data => {
            if (data.labels && data.labels.length) {
                if (window.myChart) window.myChart.destroy();
                
                window.myChart = new Chart(canvas, { 
                    type: 'line', 
                    data: { 
                        labels: data.labels, 
                        datasets: [{ 
                            label: 'Ziyaret Sayısı', 
                            data: data.values, 
                            borderColor: '#3b82f6', 
                            backgroundColor: 'rgba(59,130,246,0.1)', 
                            borderWidth: 2,
                            fill: true, 
                            tension: 0.3,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#fff',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }] 
                    }, 
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                            x: { grid: { display: false } }
                        }
                    } 
                });
            } else {
                canvas.style.display = 'none';
                const parent = canvas.parentElement;
                if (parent && !parent.querySelector('.no-data-message')) {
                    parent.innerHTML += '<div class="no-data-message" style="margin-top:20px; padding:20px; text-align:center; color:#64748b;">📊 Bu tarih aralığında grafik verisi bulunamadı.</div>';
                }
            }
        }).catch(e => console.error('Grafik hatası:', e));
}

function loadPieChart() {
    const canvas = document.getElementById('pastaChart');
    if (!canvas) return;
    
    let baslangic = document.getElementById('baslangic')?.value || '<?php echo $tarih_baslangic; ?>';
    let bitis = document.getElementById('bitis')?.value || '<?php echo $tarih_bitis; ?>';
    let altGrup = '<?php echo $alt_grup; ?>';
    
    fetch(`/api/istatistik-pasta.php?baslangic=${baslangic}&bitis=${bitis}&alt_grup=${altGrup}`)
        .then(r => r.json())
        .then(data => {
            if (data.labels && data.labels.length) {
                if (window.pieChart) window.pieChart.destroy();
                
                window.pieChart = new Chart(canvas, { 
                    type: 'pie', 
                    data: { 
                        labels: data.labels, 
                        datasets: [{ 
                            data: data.values, 
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'],
                            borderWidth: 0
                        }] 
                    }, 
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                            tooltip: { callbacks: { label: function(context) { 
                                return `${context.label}: ${context.raw} ziyaret (${Math.round(context.raw / data.toplam * 100)}%)`; 
                            } } }
                        }
                    } 
                });
            }
        }).catch(e => console.error('Pasta grafik hatası:', e));
}

// ========== FİLTRELEME ==========
function filterDate() {
    let baslangic = document.getElementById('baslangic').value;
    let bitis = document.getElementById('bitis').value;
    let alt_grup = '<?php echo $alt_grup; ?>';
    let modul = '<?php echo $mevcut_modul; ?>';
    location.href = '?modul=' + modul + '&tab=siteici&baslangic=' + baslangic + '&bitis=' + bitis + '&alt_grup=' + alt_grup;
}

function exportData() {
    let baslangic = document.getElementById('baslangic').value;
    let bitis = document.getElementById('bitis').value;
    let alt_grup = '<?php echo $alt_grup; ?>';
    location.href = '?modul=raporlar&export=csv&baslangic=' + baslangic + '&bitis=' + bitis + '&alt_grup=' + alt_grup;
}

// ========== SEARCH CONSOLE ==========
function fetchSearchConsole() {
    let start = document.getElementById('sc_start').value;
    let end = document.getElementById('sc_end').value;
    let container = document.getElementById('searchConsoleResults');
    
    container.innerHTML = '<div class="stat-card" style="text-align:center; padding:60px;"><div style="width:40px; height:40px; border:3px solid #e2e8f0; border-top-color:#2563eb; border-radius:50%; animation:spin 1s linear infinite; margin:0 auto;"></div><p style="margin-top:16px;">Veriler yükleniyor...</p></div>';
    
    fetch(`/api/search-console.php?start=${start}&end=${end}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                let html = '';
                html += `<div class="sc-stats">
                    <div class="sc-stat-card"><div class="value">${data.ozet?.toplam_tiklama || 0}</div><div class="label">Toplam Tıklama</div></div>
                    <div class="sc-stat-card"><div class="value">${data.ozet?.toplam_gosterim || 0}</div><div class="label">Toplam Gösterim</div></div>
                    <div class="sc-stat-card"><div class="value">${data.ozet?.ortalama_ctr || 0}%</div><div class="label">Ortalama TO</div></div>
                    <div class="sc-stat-card"><div class="value">${data.ozet?.ortalama_pozisyon || 0}</div><div class="label">Ortalama Konum</div></div>
                </div>`;
                
                if (data.detay && data.detay.length) {
                    html += `<div style="background:white; border-radius:20px; border:1px solid #e2e8f0; overflow:hidden;">
                        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; background:#f8fafc; font-weight:600;">🔎 Anahtar Kelime Performansı</div>
                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse:collapse;">
                                <thead><tr>
                                    <th style="padding:12px 16px; text-align:left;">Anahtar Kelime</th>
                                    <th style="padding:12px 16px; text-align:left;">Tıklama</th>
                                    <th style="padding:12px 16px; text-align:left;">Gösterim</th>
                                    <th style="padding:12px 16px; text-align:left;">CTR</th>
                                    <th style="padding:12px 16px; text-align:left;">Konum</th>
                                </tr></thead><tbody>`;
                    data.detay.slice(0, 30).forEach(row => {
                        html += `<tr>
                            <td style="padding:12px 16px;"><strong style="color:#2563eb;">${escapeHtml(row.kelime || '-')}</strong></td>
                            <td style="padding:12px 16px;"><span class="badge badge-green">${row.tiklama || 0}</span></td>
                            <td style="padding:12px 16px;"><span class="badge badge-purple">${row.gosterim || 0}</span></td>
                            <td style="padding:12px 16px;"><span class="badge badge-orange">${row.ctr || 0}%</span></td>
                            <td style="padding:12px 16px;"><span class="badge badge-blue">${row.sira || 0}</span></td>
                        </tr>`;
                    });
                    html += `</tbody></table></div>
                        <div style="padding:12px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; font-size:12px; color:#64748b;">📊 Toplam ${data.detay.length} anahtar kelime</div>
                    </div>`;
                }
                container.innerHTML = html;
            } else {
                container.innerHTML = `<div style="background:white; border-radius:20px; padding:60px; text-align:center; color:#dc2626;">❌ API Hatası: ${data.error || 'Bilinmeyen hata'}</div>`;
            }
        }).catch(e => {
            container.innerHTML = `<div style="background:white; border-radius:20px; padding:60px; text-align:center; color:#dc2626;">⚠️ Bağlantı hatası: ${e.message}</div>`;
        });
}

function escapeHtml(text) {
    if (!text) return '-';
    return text.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

const style = document.createElement('style');
style.textContent = `@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`;
document.head.appendChild(style);

// ========== 🔥 CANLI ZİYARETÇİ TAKİBİ ==========
(function() {
    function updateVisitorTable(data) {
        var tbody = document.getElementById('visitorBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">Şu an ziyaretçi yok</td></tr>';
            return;
        }
        
        data.data.forEach(function(v, index) {
            var sure = v.oturum_suresi || 0;
            if (sure > 3600) sure = 0;
            
            var dk = Math.floor(sure / 60);
            var sn = sure % 60;
            var sureText = dk > 0 ? dk + 'dk ' + sn + 'sn' : sn + 'sn';
            
            var lokasyon = '';
            var bayrak = '';
            if (v.sehir && v.ulke) {
                lokasyon = v.sehir + ', ' + v.ulke;
            } else if (v.sehir) {
                lokasyon = v.sehir;
            } else if (v.ulke) {
                lokasyon = v.ulke;
            } else {
                lokasyon = '🌍 Bilinmiyor';
            }
            
            var ulkeler = {
                'Türkiye': '🇹🇷', 'Turkey': '🇹🇷',
                'Almanya': '🇩🇪', 'Germany': '🇩🇪',
                'İngiltere': '🇬🇧', 'United Kingdom': '🇬🇧',
                'Amerika': '🇺🇸', 'United States': '🇺🇸',
                'Fransa': '🇫🇷', 'France': '🇫🇷',
                'Rusya': '🇷🇺', 'Russia': '🇷🇺'
            };
            bayrak = ulkeler[v.ulke] || '🌍';
            
            // 🔥 DURUM
            var durum = '';
            if (v.is_bot == 1) {
                if (v.bot_tipi && (v.bot_tipi.indexOf('Gemini') !== -1 || 
                    v.bot_tipi.indexOf('ChatGPT') !== -1 || 
                    v.bot_tipi.indexOf('Claude') !== -1 || 
                    v.bot_tipi.indexOf('Perplexity') !== -1)) {
                    durum = '<span class="badge badge-purple">🤖 AI: ' + v.bot_tipi + '</span>';
                } else {
                    durum = '<span class="badge badge-secondary">🤖 ' + (v.bot_tipi || 'Bot') + '</span>';
                }
            } else {
                durum = '<span class="badge badge-green">👤 İnsan</span>';
            }
            
            // 🔥 KAYNAK
            var kaynak = '';
            if (v.ai_kaynak) {
                kaynak = '<span class="badge badge-purple">🤖 ' + v.ai_kaynak + '</span>';
            } else {
                kaynak = '<span class="badge badge-secondary">🌐 Direkt</span>';
            }
            
            var isNew = index < 3 ? 'new-row' : '';
            
            tbody.innerHTML += `
                <tr class="${isNew}">
                    <td><code>${v.ip || '-'}</code></td>
                    <td>${bayrak} ${lokasyon}</td>
                    <td>${v.sayfa || '-'}</td>
                    <td>${durum}</td>
                    <td>${kaynak}</td>
                    <td><span class="badge badge-cyan">${v.sayfa_sayisi || 0}</span></td>
                    <td><span class="badge ${sure > 60 ? 'badge-green' : 'badge-orange'}">${sureText}</span></td>
                    <td>${v.tarih ? new Date(v.tarih).toLocaleTimeString('tr-TR') : '-'}</td>
                </tr>
            `;
        });
    }
    
    setInterval(function() {
        fetch('/api/son-ziyaretciler.php')
            .then(res => res.json())
            .then(data => updateVisitorTable(data))
            .catch(function(e) { console.log('Takip hatası:', e); });
    }, 5000);
})();

// ========== 🗑️ TOPLU SİLME ==========
function toggleAllCheckboxes(master) {
    var checkboxes = document.querySelectorAll('.row-checkbox');
    var selectAll = document.getElementById('selectAll');
    var selectAllTable = document.getElementById('selectAllTable');
    
    checkboxes.forEach(function(cb) {
        cb.checked = master.checked;
    });
    
    if (selectAll) selectAll.checked = master.checked;
    if (selectAllTable) selectAllTable.checked = master.checked;
    updateSelectedCount();
}

function updateSelectedCount() {
    var checkboxes = document.querySelectorAll('.row-checkbox:checked');
    var count = checkboxes.length;
    
    var selectedCount = document.getElementById('selectedCount');
    var selectedCountBottom = document.getElementById('selectedCountBottom');
    
    if (selectedCount) selectedCount.textContent = count;
    if (selectedCountBottom) selectedCountBottom.textContent = count;
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-checkbox')) {
        updateSelectedCount();
    }
});

function confirmDeleteSelected() {
    var checkboxes = document.querySelectorAll('.row-checkbox:checked');
    var ids = [];
    checkboxes.forEach(function(cb) {
        ids.push(cb.value);
    });
    
    if (ids.length === 0) {
        alert('❌ Lütfen silmek için en az bir kayıt seçin!');
        return;
    }
    
    if (confirm('⚠️ ' + ids.length + ' kaydı kalıcı olarak silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz!')) {
        deleteRecords(ids);
    }
}

function confirmDeleteAll() {
    var checkboxes = document.querySelectorAll('.row-checkbox');
    if (checkboxes.length === 0) {
        alert('❌ Silinecek kayıt yok!');
        return;
    }
    
    if (confirm('⚠️ TÜM ' + checkboxes.length + ' kaydı kalıcı olarak silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz!')) {
        var ids = [];
        checkboxes.forEach(function(cb) {
            ids.push(cb.value);
        });
        deleteRecords(ids);
    }
}

function deleteRecords(ids) {
    var loadingDiv = document.createElement('div');
    loadingDiv.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); display:flex; align-items:center; justify-content:center; z-index:99999;';
    loadingDiv.innerHTML = '<div style="background:white; padding:30px 50px; border-radius:20px;"><div style="width:40px; height:40px; border:3px solid #e2e8f0; border-top-color:#2563eb; border-radius:50%; animation:spin 1s linear infinite; margin:0 auto;"></div><p style="margin-top:16px;">' + ids.length + ' kayıt siliniyor...</p></div>';
    document.body.appendChild(loadingDiv);
    
    var formData = new FormData();
    formData.append('ids', ids.join(','));
    
    fetch('/api/toplu-sil.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())  // Önce text olarak al
    .then(text => {
        document.body.removeChild(loadingDiv);
        
        console.log('Gelen cevap:', text);  // Konsolda göster
        
        try {
            var data = JSON.parse(text);
            if (data.success) {
                alert('✅ ' + data.silinen + ' kayıt silindi!');
                location.reload();
            } else {
                alert('❌ Hata: ' + data.error);
            }
        } catch(e) {
            // JSON parse edilemedi, direkt text'i göster
            alert('❌ Hata: ' + text);
        }
    })
    .catch(function(error) {
        document.body.removeChild(loadingDiv);
        alert('❌ Bağlantı hatası: ' + error.message);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});

// ========== TIKLAYINCA POPUP GÖSTER ==========
function showTip(element) {
    var tipText = element.getAttribute('data-tip');
    var label = element.closest('td').previousElementSibling ? 'Bilgi' : 'Detay';
    
    // Eski popup varsa kaldır
    var oldPopup = document.querySelector('.tip-popup');
    var oldOverlay = document.querySelector('.tip-overlay');
    if (oldPopup) oldPopup.remove();
    if (oldOverlay) oldOverlay.remove();
    
    // Overlay oluştur
    var overlay = document.createElement('div');
    overlay.className = 'tip-overlay';
    overlay.onclick = function() {
        this.remove();
        var popup = document.querySelector('.tip-popup');
        if (popup) popup.remove();
    };
    document.body.appendChild(overlay);
    
    // Popup oluştur
    var popup = document.createElement('div');
    popup.className = 'tip-popup';
    popup.innerHTML = `
        <button class="tip-close" onclick="closeTip(this)">✕</button>
        <span class="tip-label">📋 ${label}</span>
        <div class="tip-content">${escapeHtml(tipText)}</div>
    `;
    document.body.appendChild(popup);
    
    // Escape tuşu ile kapat
    document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') {
            closeTip();
            document.removeEventListener('keydown', escHandler);
        }
    });
}

function closeTip() {
    var popup = document.querySelector('.tip-popup');
    var overlay = document.querySelector('.tip-overlay');
    if (popup) popup.remove();
    if (overlay) overlay.remove();
}

// HTML özel karakterleri kaçış
function escapeHtml(text) {
    if (!text) return '-';
    return text.replace(/[&<>"]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        if (m === '"') return '&quot;';
        return m;
    });
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Ülke dağılımı tablosundaki satırları buluyoruz (Ülke dağılımı kartındaki divleri veya tabloyu hedefliyoruz)
    // Eğer liste divler halinde listeleniyorsa:
    const ulkeSatirlari = document.querySelectorAll('.stat-card div[style*="display:flex"]');
    
    let toplamTurkiyeSayisi = 0;
    let hedefElement = null;

    ulkeSatirlari.forEach(satir => {
        const spanlar = satir.querySelectorAll('span');
        if (spanlar.length >= 2) {
            let ulkeAdi = spanlar[0].textContent.trim().toLowerCase();
            let sayi = parseInt(spanlar[1].textContent.trim()) || 0;

            // Eğer ülke turkey veya türkiye varyasyonundaysa
            if (ulkeAdi === 'turkey' || ulkeAdi === 'türkiye') {
                toplamTurkiyeSayisi += sayi;
                if (!hedefElement) {
                    hedefElement = spanlar[0]; // İlk yakaladığımız Türkiye elementini tutuyoruz
                } else {
                    satir.remove(); // İkinci gelen fazlalık satırı (Turkey olanı) DOM'dan uçuruyoruz
                }
            }
        }
    });

    // Bulunan toplam sayıyı tek satırda birleştirip yazdırıyoruz
    if (hedefElement) {
        hedefElement.textContent = 'Türkiye';
        const sayiBadge = hedefElement.closest('div').querySelector('.badge');
        if (sayiBadge) {
            sayiBadge.textContent = toplamTurkiyeSayisi;
        }
    }
});
</script>