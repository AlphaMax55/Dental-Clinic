<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

if (!modulErisim('randevular')) {
    yetkiYok('randevular');
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Toplu işlem (SADECE BEKLEYEN SEKMESİ İÇİN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toplu_islem'])) {
    $posted_token = $_POST['csrf_token'] ?? '';
    
    if (!isset($_SESSION['csrf_token']) || $posted_token !== $_SESSION['csrf_token']) {
        $_SESSION['mesaj'] = 'Güvenlik hatası!';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=randevular');
        exit;
    }
    
    $islem = $_POST['toplu_islem'];
    $secili_ids = $_POST['secili_ids'] ?? [];
    
    if (empty($secili_ids)) {
        $_SESSION['mesaj'] = 'Lütfen en az bir randevu seçin!';
        $_SESSION['mesaj_tip'] = 'warning';
        header('Location: ?modul=randevular');
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($secili_ids), '?'));
    
try {
    if ($islem === 'onayla') {
        $stmt = $db->prepare("UPDATE randevular SET durum = 'onaylandi' WHERE id IN ($placeholders)");
        $stmt->execute($secili_ids);
        $_SESSION['mesaj'] = count($secili_ids) . ' randevu onaylandı.';
        $_SESSION['mesaj_tip'] = 'success';
    } elseif ($islem === 'iptal') {
        $stmt = $db->prepare("UPDATE randevular SET durum = 'iptal' WHERE id IN ($placeholders)");
        $stmt->execute($secili_ids);
        $_SESSION['mesaj'] = count($secili_ids) . ' randevu iptal edildi.';
        $_SESSION['mesaj_tip'] = 'warning';
    } elseif ($islem === 'sil') {
        $stmt = $db->prepare("DELETE FROM randevular WHERE id IN ($placeholders)");
        $stmt->execute($secili_ids);
        $_SESSION['mesaj'] = count($secili_ids) . ' randevu silindi.';
        $_SESSION['mesaj_tip'] = 'success';
    }
} catch (Exception $e) {
    $_SESSION['mesaj'] = 'İşlem başarısız!';
    $_SESSION['mesaj_tip'] = 'error';
}
    
    header('Location: ?modul=randevular&tab=bekleyen');
    exit;
}

// Tekil Onayla
if (isset($_GET['islem']) && $_GET['islem'] === 'onayla' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $db->prepare("UPDATE randevular SET durum = 'onaylandi' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mesaj'] = 'Randevu onaylandı.';
        $_SESSION['mesaj_tip'] = 'success';
    }
    header('Location: ?modul=randevular&tab=onaylandi');
    exit;
}

// Tekil İptal
if (isset($_GET['islem']) && $_GET['islem'] === 'iptal' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        // SADECE DURUMU GÜNCELLE - SİLME
        $stmt = $db->prepare("UPDATE randevular SET durum = 'iptal' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mesaj'] = 'Randevu iptal edildi.';
        $_SESSION['mesaj_tip'] = 'warning';
    }
    header('Location: ?modul=randevular&tab=iptal');
    exit;
}

// Tekil Sil
if (isset($_GET['islem']) && $_GET['islem'] === 'sil' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $db->prepare("DELETE FROM randevular WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mesaj'] = 'Randevu silindi.';
        $_SESSION['mesaj_tip'] = 'success';
    }
    header('Location: ?modul=randevular');
    exit;
}

// Aktif tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'hepsi';

// Randevuları çek - HER TAB KENDİ RANDEVULARINI GÖSTERİR
if ($tab === 'bekleyen') {
    $randevular = $db->query("SELECT * FROM randevular WHERE durum = 'bekliyor' OR durum IS NULL OR durum = '' ORDER BY created_at DESC")->fetchAll();
} elseif ($tab === 'onaylandi') {
    $randevular = $db->query("SELECT * FROM randevular WHERE durum = 'onaylandi' ORDER BY created_at DESC")->fetchAll();
} elseif ($tab === 'iptal') {
    $randevular = $db->query("SELECT * FROM randevular WHERE durum = 'iptal' ORDER BY created_at DESC")->fetchAll();
} else {
    // Tüm randevular sekmesi -> SADECE ONAYLANANLAR
    $randevular = $db->query("SELECT * FROM randevular WHERE durum = 'onaylandi' ORDER BY created_at DESC")->fetchAll();
}

$bekleyen = $db->query("SELECT COUNT(*) FROM randevular WHERE durum = 'bekliyor' OR durum IS NULL OR durum = ''")->fetchColumn() ?: 0;
$onaylandi = $db->query("SELECT COUNT(*) FROM randevular WHERE durum = 'onaylandi'")->fetchColumn() ?: 0;
$iptal = $db->query("SELECT COUNT(*) FROM randevular WHERE durum = 'iptal'")->fetchColumn() ?: 0;
$toplam = $db->query("SELECT COUNT(*) FROM randevular")->fetchColumn() ?: 0;
?>

<div class="space-y-6">
    <!-- Başlık -->
    <div class="mb-8 px-2">
        <div class="bg-slate-900 rounded-2xl p-6 shadow-2xl border-l-8 border-blue-600">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-white tracking-tight">Randevu Yönetimi</h1>
                    <p class="text-slate-400 text-sm mt-1">Hoş geldin, <?php echo $_SESSION['admin_adi'] ?? 'Süper Admin'; ?></p>
                </div>
                <button onclick="location.reload()" class="h-12 w-12 bg-slate-800 border border-slate-700 text-slate-300 rounded-xl hover:bg-blue-600 hover:text-white transition-all">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mesaj -->
    <?php if (isset($_SESSION['mesaj'])): ?>
    <div class="p-4 rounded-lg <?php echo $_SESSION['mesaj_tip'] == 'success' ? 'bg-green-100 text-green-700 border-green-200' : ($_SESSION['mesaj_tip'] == 'warning' ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-red-100 text-red-700 border-red-200'); ?> border">
        <?php echo $_SESSION['mesaj']; ?>
    </div>
    <?php unset($_SESSION['mesaj'], $_SESSION['mesaj_tip']); endif; ?>

    <!-- İstatistik Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow border hover:shadow-lg transition-all">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Toplam Randevu</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $toplam; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow border hover:shadow-lg transition-all <?php echo $bekleyen > 0 ? 'ring-2 ring-amber-400 ring-opacity-50' : ''; ?>">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Bekleyen</p>
                    <p class="text-3xl font-bold text-amber-600 mt-1"><?php echo $bekleyen; ?></p>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center <?php echo $bekleyen > 0 ? 'animate-pulse' : ''; ?>">
                    <i class="fas fa-clock text-amber-600 text-xl"></i>
                </div>
            </div>
            <?php if($bekleyen > 0): ?>
            <a href="?modul=randevular&tab=bekleyen" class="mt-3 block text-center text-sm bg-amber-500 text-white py-2 rounded-lg hover:bg-amber-600">Hemen İncele →</a>
            <?php endif; ?>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow border hover:shadow-lg transition-all">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Onaylanan</p>
                    <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $onaylandi; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow border hover:shadow-lg transition-all">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">İptal</p>
                    <p class="text-3xl font-bold text-red-600 mt-1"><?php echo $iptal; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Sekmeler -->
    <div class="border-b border-gray-200">
        <nav class="flex gap-6">
            <a href="?modul=randevular&tab=hepsi" class="pb-3 px-1 text-sm font-semibold transition-all duration-200 <?php echo $tab === 'hepsi' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300'; ?>">
                <i class="fas fa-list mr-2"></i>Tüm Randevular
                <span class="ml-1 text-xs">(<?php echo $onaylandi; ?>)</span>
            </a>
            <a href="?modul=randevular&tab=bekleyen" class="pb-3 px-1 text-sm font-semibold transition-all duration-200 <?php echo $tab === 'bekleyen' ? 'text-amber-600 border-b-2 border-amber-600' : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300'; ?>">
                <i class="fas fa-clock mr-2"></i>Bekleyen
                <?php if($bekleyen > 0): ?>
                <span class="ml-1 px-2 py-0.5 bg-amber-100 text-amber-600 text-xs rounded-full animate-pulse"><?php echo $bekleyen; ?></span>
                <?php endif; ?>
            </a>
            <a href="?modul=randevular&tab=onaylandi" class="pb-3 px-1 text-sm font-semibold transition-all duration-200 <?php echo $tab === 'onaylandi' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300'; ?>">
                <i class="fas fa-check-circle mr-2"></i>Onaylanan
                <span class="ml-1 text-xs">(<?php echo $onaylandi; ?>)</span>
            </a>
            <a href="?modul=randevular&tab=iptal" class="pb-3 px-1 text-sm font-semibold transition-all duration-200 <?php echo $tab === 'iptal' ? 'text-red-600 border-b-2 border-red-600' : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300'; ?>">
                <i class="fas fa-times-circle mr-2"></i>İptal
                <span class="ml-1 text-xs">(<?php echo $iptal; ?>)</span>
            </a>
        </nav>
    </div>

    <?php if ($tab === 'bekleyen'): ?>
    <!-- Toplu İşlem Çubuğu - SADECE BEKLEYEN SEKMESİNDE -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-sm font-bold text-amber-700"><i class="fas fa-tasks mr-1"></i> Toplu İşlem:</span>
            <button type="button" id="topluOnaylaBtn" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm rounded-lg transition cursor-pointer flex items-center gap-1">
                <i class="fas fa-check-circle"></i> Onayla
            </button>
            <button type="button" id="topluIptalBtn" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm rounded-lg transition cursor-pointer flex items-center gap-1">
                <i class="fas fa-times-circle"></i> İptal
            </button>
            <button type="button" id="topluSilBtn" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm rounded-lg transition cursor-pointer flex items-center gap-1">
                <i class="fas fa-trash-alt"></i> Sil
            </button>
            <div class="w-px h-6 bg-amber-200 mx-1"></div>
            <button type="button" id="tumunuSec" class="px-3 py-1.5 bg-white border border-amber-300 text-amber-700 text-sm rounded-lg hover:bg-amber-100 transition cursor-pointer">Tümünü Seç</button>
            <button type="button" id="secimiTemizle" class="px-3 py-1.5 bg-white border border-amber-300 text-amber-700 text-sm rounded-lg hover:bg-amber-100 transition cursor-pointer">Seçimi Temizle</button>
        </div>
        <div class="text-sm text-amber-600 bg-white px-4 py-2 rounded-full">
            <i class="far fa-check-circle mr-1"></i>
            <span id="seciliSayac">0</span> randevu seçildi
        </div>
    </div>
    <?php endif; ?>

<!-- Randevu Kartları Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (count($randevular) > 0): ?>
        <?php foreach ($randevular as $r): 
            $isBekleyen = ($r['durum'] == 'bekliyor' || $r['durum'] == '' || $r['durum'] === null);
            $durumClass = $isBekleyen ? 'bg-amber-100 text-amber-700 border-amber-200' : ($r['durum'] == 'onaylandi' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200');
            $durumIcon = $isBekleyen ? 'fa-clock' : ($r['durum'] == 'onaylandi' ? 'fa-check-circle' : 'fa-times-circle');
            $durumText = $isBekleyen ? 'Bekliyor' : ($r['durum'] == 'onaylandi' ? 'Onaylandı' : 'İptal');
        ?>
        <div class="bg-white rounded-2xl border <?php echo $isBekleyen ? 'border-amber-200 ring-1 ring-amber-200' : ($r['durum'] == 'onaylandi' ? 'border-green-200' : 'border-red-200'); ?> shadow-md hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 overflow-hidden relative">
            <div class="h-2 bg-gradient-to-r <?php echo $isBekleyen ? 'from-amber-500 to-orange-500' : ($r['durum'] == 'onaylandi' ? 'from-green-500 to-emerald-500' : 'from-red-500 to-rose-500'); ?>"></div>
            
            <?php if ($tab === 'bekleyen'): ?>
            <div class="absolute top-3 right-3 z-10">
                <input type="checkbox" name="secili_ids[]" value="<?php echo $r['id']; ?>" class="randevu-checkbox w-5 h-5 rounded border-2 border-amber-400 bg-white checked:bg-emerald-500 checked:border-emerald-500 cursor-pointer">
            </div>
            <?php endif; ?>
            
            <div class="p-5">
                <!-- Üst Kısım: ID ve Durum -->
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600 text-sm font-bold">
                            #<?php echo $r['id']; ?>
                        </div>
                        <span class="text-xs text-gray-400"><?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></span>
                    </div>
                    <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full <?php echo $durumClass; ?>">
                        <i class="fas <?php echo $durumIcon; ?> text-[10px]"></i> <?php echo $durumText; ?>
                    </span>
                </div>
                
                <!-- Hasta Bilgileri -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br <?php echo $isBekleyen ? 'from-amber-500 to-orange-600' : ($r['durum'] == 'onaylandi' ? 'from-green-500 to-emerald-600' : 'from-red-500 to-rose-600'); ?> flex items-center justify-center text-white font-bold text-lg shadow-md">
                        <?php echo substr($r['ad_soyad'], 0, 1); ?>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-base"><?php echo htmlspecialchars($r['ad_soyad']); ?></h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500 flex items-center gap-1">
                                <i class="fas fa-phone-alt text-[10px]"></i> <?php echo htmlspecialchars($r['telefon']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- İletişim Bilgileri -->
                <div class="mb-3 p-2 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                        <i class="fas fa-envelope text-gray-400 text-xs"></i>
                        <span class="truncate"><?php echo htmlspecialchars($r['email']); ?></span>
                    </div>
                </div>
                
                <!-- Randevu Bilgileri -->
                <div class="mb-3 bg-blue-50/30 p-2 rounded-lg">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-calendar-alt text-blue-500 text-xs"></i>
                            <span class="text-sm font-semibold text-gray-700"><?php echo date('d.m.Y', strtotime($r['randevu_tarihi'])); ?></span>
                        </div>
                        <?php if(!empty($r['randevu_saati'])): ?>
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-clock text-blue-500 text-xs"></i>
                            <span class="text-sm font-semibold text-gray-700"><?php echo substr($r['randevu_saati'], 0, 5); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if(!empty($r['konu'])): ?>
                    <div class="flex items-center gap-1.5 mt-2 pt-1 border-t border-blue-100">
                        <i class="fas fa-tag text-blue-500 text-xs"></i>
                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($r['konu']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Hasta Detay Bilgileri -->
                <?php if(!empty($r['cinsiyet']) || !empty($r['dogum_tarihi']) || !empty($r['tc_no'])): ?>
                <div class="mt-2 mb-2 p-2 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <?php if(!empty($r['cinsiyet'])): ?>
                        <div class="flex flex-col items-center gap-0.5">
                            <i class="fas fa-<?php echo $r['cinsiyet'] == 'male' ? 'mars' : 'venus'; ?> text-blue-500 text-sm"></i>
                            <span class="text-[11px] font-medium text-gray-600"><?php echo $r['cinsiyet'] == 'male' ? 'Erkek' : 'Kadın'; ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($r['dogum_tarihi'])): ?>
                        <div class="flex flex-col items-center gap-0.5">
                            <i class="fas fa-birthday-cake text-blue-500 text-sm"></i>
                            <span class="text-[11px] font-medium text-gray-600"><?php echo date('d.m.Y', strtotime($r['dogum_tarihi'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($r['tc_no'])): ?>
                        <div class="flex flex-col items-center gap-0.5">
                            <i class="fas fa-id-card text-blue-500 text-sm"></i>
                            <span class="text-[11px] font-medium text-gray-600"><?php echo substr($r['tc_no'], 0, 5); ?>***</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
<!-- Sağlık Bilgileri -->
<?php 
// Değişkenleri doğru şekilde boolean'a çevir
$ilac_kullaniyor = ($r['ilac_kullaniyor'] == 1);
$alerji_var = ($r['alerji_var'] == 1);
$hamile = ($r['hamile'] == 1);
$diyabet = ($r['diyabet'] == 1);
$kalp_hastaligi = ($r['kalp_hastaligi'] == 1);
$kan_sulandirici = ($r['kan_sulandirici'] == 1);
?>

<?php if($ilac_kullaniyor || $alerji_var || $hamile || $diyabet || $kalp_hastaligi || $kan_sulandirici): ?>
<div class="mt-2 mb-3 p-2 bg-amber-50/30 rounded-lg">
    <div class="flex flex-wrap gap-2">
        <?php if($ilac_kullaniyor): ?>
        <span class="inline-flex items-center gap-1 text-[10px] px-2 py-1 bg-blue-100 text-blue-700 rounded-full">
            <i class="fas fa-pills text-[9px]"></i> İlaç Kullanıyor
        </span>
        <?php endif; ?>
        <?php if($alerji_var): ?>
        <span class="inline-flex items-center gap-1 text-[10px] px-2 py-1 bg-red-100 text-red-700 rounded-full">
            <i class="fas fa-allergies text-[9px]"></i> Alerji Var
        </span>
        <?php endif; ?>
        <?php if($hamile): ?>
        <span class="inline-flex items-center gap-1 text-[10px] px-2 py-1 bg-pink-100 text-pink-700 rounded-full">
            <i class="fas fa-baby-carriage text-[9px]"></i> Hamile
        </span>
        <?php endif; ?>
        <?php if($diyabet): ?>
        <span class="inline-flex items-center gap-1 text-[10px] px-2 py-1 bg-orange-100 text-orange-700 rounded-full">
            <i class="fas fa-tint text-[9px]"></i> Diyabet
        </span>
        <?php endif; ?>
        <?php if($kalp_hastaligi): ?>
        <span class="inline-flex items-center gap-1 text-[10px] px-2 py-1 bg-purple-100 text-purple-700 rounded-full">
            <i class="fas fa-heartbeat text-[9px]"></i> Kalp Hastalığı
        </span>
        <?php endif; ?>
        <?php if($kan_sulandirici): ?>
        <span class="inline-flex items-center gap-1 text-[10px] px-2 py-1 bg-amber-100 text-amber-700 rounded-full">
            <i class="fas fa-tint text-[9px]"></i> Kan Sulandırıcı
        </span>
        <?php endif; ?>
    </div>
    
    <!-- İlaç Detayı -->
    <?php if($ilac_kullaniyor && !empty($r['ilac_detay'])): ?>
    <div class="mt-2 flex items-start gap-1.5 text-[10px] text-gray-600 bg-white/50 p-1.5 rounded">
        <i class="fas fa-notes-medical text-blue-400 text-[9px] mt-0.5"></i>
        <span class="flex-1"><?php echo htmlspecialchars(substr($r['ilac_detay'], 0, 50)); ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Alerji Detayı -->
    <?php if($alerji_var && !empty($r['alerji_detay'])): ?>
    <div class="mt-1 flex items-start gap-1.5 text-[10px] text-gray-600 bg-white/50 p-1.5 rounded">
        <i class="fas fa-exclamation-triangle text-red-400 text-[9px] mt-0.5"></i>
        <span class="flex-1"><?php echo htmlspecialchars(substr($r['alerji_detay'], 0, 50)); ?></span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<!-- İşlem Butonları -->
<div class="flex gap-2 pt-3 border-t border-gray-100 mt-2">
    <?php if ($isBekleyen && $tab === 'bekleyen'): ?>
    <a href="?modul=randevular&islem=onayla&id=<?php echo $r['id']; ?>" class="flex-1 px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition text-center" data-id="<?php echo $r['id']; ?>">
        <i class="fas fa-check-circle mr-1"></i> Onayla
    </a>
    <a href="?modul=randevular&islem=iptal&id=<?php echo $r['id']; ?>" class="flex-1 px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded-lg transition text-center" data-id="<?php echo $r['id']; ?>">
        <i class="fas fa-times-circle mr-1"></i> İptal
    </a>
    <?php endif; ?>
    
    <!-- DETAYLAR BUTONU -->
    <button type="button" onclick="openRandevuModal(<?php echo htmlspecialchars(json_encode($r)); ?>)" class="flex-1 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-lg transition text-center">
        <i class="fas fa-info-circle mr-1"></i> Detaylar
    </button>
    
    <a href="?modul=randevular&islem=sil&id=<?php echo $r['id']; ?>" class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-lg transition text-center" data-id="<?php echo $r['id']; ?>">
        <i class="fas fa-trash-alt"></i>
    </a>
</div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full text-center py-12">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-times text-2xl text-gray-400"></i>
            </div>
            <p class="text-gray-500">Bu kategoride randevu bulunmuyor</p>
        </div>
    <?php endif; ?>
</div>
</div>

<form id="topluIslemForm" method="POST" action="?modul=randevular" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="toplu_islem" id="topluIslemTipi" value="">
</form>

<!-- Randevu Detay Modalı -->
<div id="randevuModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all duration-300" onclick="closeRandevuModalOnOutside(event)">
    <div class="relative w-full max-w-4xl max-h-[90vh] bg-white rounded-3xl shadow-2xl overflow-hidden" id="randevuModalContainer" onclick="event.stopPropagation()">
        
        <!-- Gradient Border Top -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 z-10"></div>
        
        <!-- Close Button -->
        <button onclick="closeRandevuModal()" class="absolute top-5 right-5 z-20 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:shadow-xl hover:scale-110 transition-all duration-200 border border-gray-200">
            <svg class="w-5 h-5 text-gray-500 hover:text-gray-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        
        <!-- Modal Content -->
        <div class="overflow-y-auto max-h-[85vh]" id="randevuModalContent">
            <!-- İçerik dinamik olarak doldurulacak -->
        </div>
    </div>
</div>
<script>
// Randevu Detay Modalı
function openRandevuModal(randevu) {
    const modal = document.getElementById('randevuModal');
    const modalContent = document.getElementById('randevuModalContent');
    
    if(modal && modalContent) {
        // Durum için renk ve ikon
        let durumClass = '', durumIcon = '', durumText = '';
        if(randevu.durum == 'onaylandi') {
            durumClass = 'bg-green-100 text-green-700 border-green-200';
            durumIcon = 'fa-check-circle';
            durumText = 'Onaylandı';
        } else if(randevu.durum == 'iptal') {
            durumClass = 'bg-red-100 text-red-700 border-red-200';
            durumIcon = 'fa-times-circle';
            durumText = 'İptal';
        } else {
            durumClass = 'bg-amber-100 text-amber-700 border-amber-200';
            durumIcon = 'fa-clock';
            durumText = 'Bekliyor';
        }
        
        modalContent.innerHTML = `
            <div class="p-6 lg:p-8">
                <!-- Başlık -->
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg shadow-md">
                                ${randevu.ad_soyad ? randevu.ad_soyad.charAt(0) : '?'}
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">${escapeHtml(randevu.ad_soyad)}</h2>
                                <p class="text-sm text-gray-500">Randevu ID: #${randevu.id}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full ${durumClass} text-sm font-semibold">
                            <i class="fas ${durumIcon}"></i> ${durumText}
                        </span>
                        <p class="text-xs text-gray-400 mt-2">Oluşturulma: ${new Date(randevu.created_at).toLocaleString('tr-TR')}</p>
                    </div>
                </div>
                
                <!-- İletişim Bilgileri -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-phone-alt text-blue-500 text-sm"></i>
                            <span class="text-xs font-semibold text-gray-500 uppercase">Telefon</span>
                        </div>
                        <p class="text-base font-bold text-gray-800">${escapeHtml(randevu.telefon)}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-envelope text-blue-500 text-sm"></i>
                            <span class="text-xs font-semibold text-gray-500 uppercase">E-posta</span>
                        </div>
                        <p class="text-base font-bold text-gray-800">${escapeHtml(randevu.email || 'Belirtilmemiş')}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-id-card text-blue-500 text-sm"></i>
                            <span class="text-xs font-semibold text-gray-500 uppercase">TC Kimlik</span>
                        </div>
                        <p class="text-base font-bold text-gray-800">${randevu.tc_no ? randevu.tc_no : 'Belirtilmemiş'}</p>
                    </div>
                </div>
                
                <!-- Randevu Bilgileri -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-blue-600"></i>
                        Randevu Bilgileri
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Randevu Tarihi</p>
                            <p class="text-base font-semibold text-gray-800">${randevu.randevu_tarihi ? new Date(randevu.randevu_tarihi).toLocaleDateString('tr-TR') : '-'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Randevu Saati</p>
                            <p class="text-base font-semibold text-gray-800">${randevu.randevu_saati ? randevu.randevu_saati.substring(0,5) : '-'}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 mb-1">Tedavi</p>
                            <p class="text-base font-semibold text-gray-800">${escapeHtml(randevu.konu || 'Belirtilmemiş')}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Hasta Bilgileri -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="border rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-${randevu.cinsiyet == 'male' ? 'mars' : 'venus'} text-purple-500 text-sm"></i>
                            <span class="text-xs font-semibold text-gray-500 uppercase">Cinsiyet</span>
                        </div>
                        <p class="text-base font-semibold text-gray-800">${randevu.cinsiyet == 'male' ? 'Erkek' : (randevu.cinsiyet == 'female' ? 'Kadın' : 'Belirtilmemiş')}</p>
                    </div>
                    <div class="border rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-birthday-cake text-purple-500 text-sm"></i>
                            <span class="text-xs font-semibold text-gray-500 uppercase">Doğum Tarihi</span>
                        </div>
                        <p class="text-base font-semibold text-gray-800">${randevu.dogum_tarihi ? new Date(randevu.dogum_tarihi).toLocaleDateString('tr-TR') : 'Belirtilmemiş'}</p>
                    </div>
                    <div class="border rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-user-md text-purple-500 text-sm"></i>
                            <span class="text-xs font-semibold text-gray-500 uppercase">Hasta Tipi</span>
                        </div>
                        <p class="text-base font-semibold text-gray-800">${randevu.hasta_tipi || 'Yeni Hasta'}</p>
                    </div>
                </div>
                
                <!-- Şikayet ve Notlar -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-comment-dots text-blue-600"></i>
                        Şikayet / Notlar
                    </h3>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">${escapeHtml(randevu.not || 'Şikayet belirtilmemiş')}</p>
                    </div>
                </div>
                
<!-- Sağlık Bilgileri -->
<div class="mb-6">
    <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
        <i class="fas fa-heartbeat text-red-500"></i>
        Sağlık Bilgileri
    </h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <div class="flex items-center justify-between p-3 ${randevu.ilac_kullaniyor == 1 ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50'} rounded-xl">
            <span class="text-sm text-gray-700">💊 İlaç Kullanımı</span>
            <span class="text-sm font-bold ${randevu.ilac_kullaniyor == 1 ? 'text-green-600' : 'text-red-500'}">${randevu.ilac_kullaniyor == 1 ? 'Evet' : 'Hayır'}</span>
        </div>
        <div class="flex items-center justify-between p-3 ${randevu.alerji_var == 1 ? 'bg-red-50 border border-red-200' : 'bg-gray-50'} rounded-xl">
            <span class="text-sm text-gray-700">⚠️ Alerji</span>
            <span class="text-sm font-bold ${randevu.alerji_var == 1 ? 'text-red-600' : 'text-green-600'}">${randevu.alerji_var == 1 ? 'Evet' : 'Hayır'}</span>
        </div>
        <div class="flex items-center justify-between p-3 ${randevu.hamile == 1 ? 'bg-pink-50 border border-pink-200' : 'bg-gray-50'} rounded-xl">
            <span class="text-sm text-gray-700">🤰 Hamilelik</span>
            <span class="text-sm font-bold ${randevu.hamile == 1 ? 'text-pink-600' : 'text-green-600'}">${randevu.hamile == 1 ? 'Evet' : 'Hayır'}</span>
        </div>
        <div class="flex items-center justify-between p-3 ${randevu.diyabet == 1 ? 'bg-orange-50 border border-orange-200' : 'bg-gray-50'} rounded-xl">
            <span class="text-sm text-gray-700">🩸 Diyabet</span>
            <span class="text-sm font-bold ${randevu.diyabet == 1 ? 'text-orange-600' : 'text-green-600'}">${randevu.diyabet == 1 ? 'Evet' : 'Hayır'}</span>
        </div>
        <div class="flex items-center justify-between p-3 ${randevu.kalp_hastaligi == 1 ? 'bg-purple-50 border border-purple-200' : 'bg-gray-50'} rounded-xl">
            <span class="text-sm text-gray-700">❤️ Kalp Hastalığı</span>
            <span class="text-sm font-bold ${randevu.kalp_hastaligi == 1 ? 'text-purple-600' : 'text-green-600'}">${randevu.kalp_hastaligi == 1 ? 'Evet' : 'Hayır'}</span>
        </div>
        <div class="flex items-center justify-between p-3 ${randevu.kan_sulandirici == 1 ? 'bg-amber-50 border border-amber-200' : 'bg-gray-50'} rounded-xl">
            <span class="text-sm text-gray-700">💉 Kan Sulandırıcı</span>
            <span class="text-sm font-bold ${randevu.kan_sulandirici == 1 ? 'text-amber-600' : 'text-green-600'}">${randevu.kan_sulandirici == 1 ? 'Evet' : 'Hayır'}</span>
        </div>
    </div>
    
    <!-- İlaç Detayı -->
    ${randevu.ilac_detay && randevu.ilac_kullaniyor == 1 ? `
    <div class="mt-3 p-3 bg-blue-50 rounded-xl">
        <p class="text-xs font-semibold text-blue-700 mb-1">💊 İlaç Detayı</p>
        <p class="text-sm text-gray-700">${escapeHtml(randevu.ilac_detay)}</p>
    </div>
    ` : ''}
    
    <!-- Alerji Detayı -->
    ${randevu.alerji_detay && randevu.alerji_var == 1 ? `
    <div class="mt-3 p-3 bg-red-50 rounded-xl">
        <p class="text-xs font-semibold text-red-700 mb-1">⚠️ Alerji Detayı</p>
        <p class="text-sm text-gray-700">${escapeHtml(randevu.alerji_detay)}</p>
    </div>
    ` : ''}
</div>
                
                <!-- Alt Butonlar -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <a href="?modul=randevular&islem=onayla&id=${randevu.id}" class="flex-1 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl text-center transition">✅ Onayla</a>
                    <a href="?modul=randevular&islem=iptal&id=${randevu.id}" class="flex-1 py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-xl text-center transition">❌ İptal</a>
                    <button onclick="closeRandevuModal()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-xl transition">Kapat</button>
                </div>
            </div>
        `;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
}

function closeRandevuModal() {
    const modal = document.getElementById('randevuModal');
    if(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
}

function closeRandevuModalOnOutside(event) {
    const modalContainer = document.getElementById('randevuModalContainer');
    if(modalContainer && !modalContainer.contains(event.target)) {
        closeRandevuModal();
    }
}

function escapeHtml(str) {
    if(!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}
</script>
<script>
function showConfirm(title, message, callback) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;';
    
    let iconClass = '';
    let buttonClass = '';
    let icon = '';
    if (title === 'Onayla') {
        iconClass = 'bg-emerald-100 text-emerald-600';
        buttonClass = 'bg-emerald-500 hover:bg-emerald-600';
        icon = 'fa-check-circle';
    } else if (title === 'İptal') {
        iconClass = 'bg-amber-100 text-amber-600';
        buttonClass = 'bg-amber-500 hover:bg-amber-600';
        icon = 'fa-times-circle';
    } else {
        iconClass = 'bg-red-100 text-red-600';
        buttonClass = 'bg-red-500 hover:bg-red-600';
        icon = 'fa-trash-alt';
    }
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 overflow-hidden shadow-2xl">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center ${iconClass}">
                        <i class="fas ${icon} text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">${title}</h3>
                </div>
                <p class="text-gray-600 mb-6">${message}</p>
                <div class="flex gap-3">
                    <button id="confirmCancel" class="flex-1 px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition cursor-pointer">İptal</button>
                    <button id="confirmOk" class="flex-1 px-5 py-2 ${buttonClass} text-white rounded-lg transition cursor-pointer">${title === 'Onayla' ? 'Evet, Onayla' : (title === 'İptal' ? 'Evet, İptal' : 'Evet, Sil')}</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.querySelector('#confirmCancel').onclick = () => { modal.remove(); callback(false); };
    modal.querySelector('#confirmOk').onclick = () => { modal.remove(); callback(true); };
}

<?php if ($tab === 'bekleyen'): ?>
function updateSeciliSayac() {
    let secili = document.querySelectorAll('.randevu-checkbox:checked').length;
    document.getElementById('seciliSayac').innerText = secili;
}

document.querySelectorAll('.randevu-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSeciliSayac);
});

document.getElementById('tumunuSec')?.addEventListener('click', function() {
    document.querySelectorAll('.randevu-checkbox').forEach(cb => cb.checked = true);
    updateSeciliSayac();
});

document.getElementById('secimiTemizle')?.addEventListener('click', function() {
    document.querySelectorAll('.randevu-checkbox').forEach(cb => cb.checked = false);
    updateSeciliSayac();
});

document.getElementById('topluOnaylaBtn')?.addEventListener('click', function() {
    let secili = document.querySelectorAll('.randevu-checkbox:checked');
    if(secili.length === 0) { alert('Lütfen en az bir randevu seçin!'); return; }
    showConfirm('Onayla', secili.length + ' randevuyu onaylamak istediğinize emin misiniz?', (confirmed) => {
        if(confirmed) {
            secili.forEach(cb => {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'secili_ids[]';
                input.value = cb.value;
                document.getElementById('topluIslemForm').appendChild(input);
            });
            document.getElementById('topluIslemTipi').value = 'onayla';
            document.getElementById('topluIslemForm').submit();
        }
    });
});

document.getElementById('topluIptalBtn')?.addEventListener('click', function() {
    let secili = document.querySelectorAll('.randevu-checkbox:checked');
    if(secili.length === 0) { alert('Lütfen en az bir randevu seçin!'); return; }
    showConfirm('İptal', secili.length + ' randevuyu iptal etmek istediğinize emin misiniz?', (confirmed) => {
        if(confirmed) {
            secili.forEach(cb => {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'secili_ids[]';
                input.value = cb.value;
                document.getElementById('topluIslemForm').appendChild(input);
            });
            document.getElementById('topluIslemTipi').value = 'iptal';
            document.getElementById('topluIslemForm').submit();
        }
    });
});

document.getElementById('topluSilBtn')?.addEventListener('click', function() {
    let secili = document.querySelectorAll('.randevu-checkbox:checked');
    if(secili.length === 0) { alert('Lütfen en az bir randevu seçin!'); return; }
    showConfirm('Sil', secili.length + ' randevuyu KALICI OLARAK silmek istediğinize emin misiniz?', (confirmed) => {
        if(confirmed) {
            secili.forEach(cb => {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'secili_ids[]';
                input.value = cb.value;
                document.getElementById('topluIslemForm').appendChild(input);
            });
            document.getElementById('topluIslemTipi').value = 'sil';
            document.getElementById('topluIslemForm').submit();
        }
    });
});

updateSeciliSayac();
<?php endif; ?>

// Tekil işlemler için modern confirm
document.querySelectorAll('a[href*="islem=onayla"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let url = this.href;
        showConfirm('Onayla', 'Bu randevuyu onaylamak istediğinize emin misiniz?', (confirmed) => {
            if(confirmed) window.location.href = url;
        });
    });
});

document.querySelectorAll('a[href*="islem=iptal"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let url = this.href;
        showConfirm('İptal', 'Bu randevuyu iptal etmek istediğinize emin misiniz?', (confirmed) => {
            if(confirmed) window.location.href = url;
        });
    });
});

document.querySelectorAll('a[href*="islem=sil"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let url = this.href;
        showConfirm('Sil', 'Bu randevuyu KALICI OLARAK silmek istediğinize emin misiniz?', (confirmed) => {
            if(confirmed) window.location.href = url;
        });
    });
});
</script>