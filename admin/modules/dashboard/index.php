<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

// Veri katmanını yükle
require_once dirname(__DIR__) . '/dashboard/data/dashboard-data.php';

// Aktif tab'ı belirle
$aktif_tab = $_GET['tab'] ?? 'yorumlar';
$izinli_tablar = ['yorumlar', 'iletisim', 'istatistikler', 'tedaviler', 'randevular', 'gelirler', 'bildirimler'];
if (!in_array($aktif_tab, $izinli_tablar)) {
    $aktif_tab = 'yorumlar';
}

// ===== İLETİŞİM MESAJLARI İSTATİSTİKLERİ =====
$bekleyen_mesaj = $db->query("SELECT COUNT(*) FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL) AND (durum = 'okunmadi' OR durum = '' OR durum IS NULL)")->fetchColumn() ?: 0;

// Tüm verileri çek
$cashflow = getCashflowData($db);
$patient_stats = getPatientStats($db);
$popular_treatments = getPopularTreatments($db);
$expenses = getExpenseData();
$stock = getStockData();
$alerts = getPendingAlerts();
$recent_activities = getRecentActivities();
$patient_summary = getPatientSummary();

// Yorum verilerini çek
$yorumlar = $db->query("SELECT * FROM yorumlar ORDER BY onay ASC, id DESC LIMIT 10")->fetchAll();
$onayli = $db->query("SELECT COUNT(*) FROM yorumlar WHERE onay = 1")->fetchColumn() ?: 0;
$bekleyen = $db->query("SELECT COUNT(*) FROM yorumlar WHERE onay = 0")->fetchColumn() ?: 0;
$toplam = $db->query("SELECT COUNT(*) FROM yorumlar")->fetchColumn() ?: 0;

require_once dirname(__DIR__, 2) . '/layouts/header.php';
require_once dirname(__DIR__, 2) . '/layouts/sidebar.php';
?>
<title>
    <?php if($bekleyen_mesaj > 0): ?>
    (<?php echo $bekleyen_mesaj; ?>) 
    <?php endif; ?>
    Kontrol Paneli
</title>
<!-- Dashboard Content -->
<div class="flex-1 overflow-y-auto bg-gray-50">

<div class="mb-8 px-2">
 <div class="bg-slate-900 rounded-2xl p-6 shadow-2xl border-l-8 border-blue-600 relative overflow-hidden group">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all duration-700"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-black text-white tracking-tight">KONTROL PANELİ</h1>
                    <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 text-[10px] font-bold uppercase rounded-md border border-blue-500/30">Canlı Panel</span>
                </div>
                <p class="text-slate-400 text-sm font-medium mt-2 flex items-center gap-2">
                    <i class="fas fa-user-shield text-blue-500 text-xs"></i>
                    Hoş geldin, <span class="text-white font-bold decoration-blue-500 underline-offset-4"><?php echo $_SESSION['admin_adi'] ?? 'Süper Admin'; ?></span>
                </p>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Veritabanı Durumu</p>
                    <div class="flex items-center gap-2 justify-end mt-1">
                        <span class="text-xs text-slate-300 font-bold tracking-tighter">AKTİF</span>
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                    </div>
                </div>
                <button onclick="location.reload()" class="h-12 w-12 bg-slate-800 border border-slate-700 text-slate-300 rounded-xl hover:bg-blue-600 hover:text-white hover:border-blue-500 transition-all duration-300 shadow-lg active:scale-90 flex items-center justify-center group/btn">
                    <i class="fas fa-sync-alt group-hover/btn:rotate-180 transition-transform duration-500"></i>
                </button>
            </div>
        </div>
    </div>
</div>

    <div class="p-6">

<!-- ===== TAB MENÜLER ===== -->
<div class="border-b border-gray-200 mb-6">
    <nav class="flex flex-wrap gap-1">
        <a href="?tab=yorumlar" class="px-6 py-3 text-sm font-bold rounded-t-lg transition-all <?php echo $aktif_tab == 'yorumlar' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-star mr-2"></i>Yorumlar
            <?php if($bekleyen > 0): ?>
            <span class="ml-2 px-2 py-0.5 text-xs bg-red-500 text-white rounded-full"><?php echo $bekleyen; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="?tab=iletisim" class="px-6 py-3 text-sm font-bold rounded-t-lg transition-all <?php echo $aktif_tab == 'iletisim' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-envelope mr-2"></i>İletişim Mesajları
            <?php if($bekleyen_mesaj > 0): ?>
            <span class="ml-2 px-2 py-0.5 text-xs bg-red-500 text-white rounded-full animate-pulse"><?php echo $bekleyen_mesaj; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="?tab=istatistikler" class="px-6 py-3 text-sm font-bold rounded-t-lg transition-all <?php echo $aktif_tab == 'istatistikler' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-chart-line mr-2"></i>İstatistikler
        </a>
        <a href="?tab=tedaviler" class="px-6 py-3 text-sm font-bold rounded-t-lg transition-all <?php echo $aktif_tab == 'tedaviler' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-tooth mr-2"></i>Tedaviler
        </a>
        <a href="?tab=randevular" class="px-6 py-3 text-sm font-bold rounded-t-lg transition-all <?php echo $aktif_tab == 'randevular' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-calendar-check mr-2"></i>Randevular
        </a>
        <a href="?tab=gelirler" class="px-6 py-3 text-sm font-bold rounded-t-lg transition-all <?php echo $aktif_tab == 'gelirler' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-chart-pie mr-2"></i>Gelir/Gider
        </a>
        <a href="?tab=bildirimler" class="px-6 py-3 text-sm font-bold rounded-t-lg transition-all <?php echo $aktif_tab == 'bildirimler' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-bell mr-2"></i>Bildirimler
        </a>
    </nav>
</div>

        <!-- ===== TAB İÇERİKLERİ ===== -->
        <?php include 'tabs/' . $aktif_tab . '.php'; ?>

    </div>
</div>

<?php require_once dirname(__DIR__,2) . '/layouts/footer.php'; ?>