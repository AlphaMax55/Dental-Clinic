<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

if (!modulErisim('iletisim')) {
    yetkiYok('iletisim');
}

// ========== AKTİF TAB ==========
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'okunmadi';

// ========== SEO AYARLARINI ÇEK (TABLO YOKSA OLUŞTUR) ==========
$seo_title_tr = '';
$seo_title_en = '';
$seo_description_tr = '';
$seo_description_en = '';
$seo_keywords_tr = '';
$seo_keywords_en = '';
$seo_og_image = '';
$seo_canonical = '';

try {
    $db->exec("CREATE TABLE IF NOT EXISTS iletisim_seo_ayarlar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        anahtar VARCHAR(100) NOT NULL UNIQUE,
        deger TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $stmt = $db->query("SELECT anahtar, deger FROM iletisim_seo_ayarlar");
    while($row = $stmt->fetch()) {
        if($row['anahtar'] == 'seo_title_tr') $seo_title_tr = $row['deger'];
        if($row['anahtar'] == 'seo_title_en') $seo_title_en = $row['deger'];
        if($row['anahtar'] == 'seo_description_tr') $seo_description_tr = $row['deger'];
        if($row['anahtar'] == 'seo_description_en') $seo_description_en = $row['deger'];
        if($row['anahtar'] == 'seo_keywords_tr') $seo_keywords_tr = $row['deger'];
        if($row['anahtar'] == 'seo_keywords_en') $seo_keywords_en = $row['deger'];
        if($row['anahtar'] == 'seo_og_image') $seo_og_image = $row['deger'];
        if($row['anahtar'] == 'seo_canonical') $seo_canonical = $row['deger'];
    }
} catch(Exception $e) {}

// ========== CSRF TOKEN ==========
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========== SEO KAYDET ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seo_kaydet'])) {
    $posted_token = $_POST['csrf_token'] ?? '';
    
    if (!isset($_SESSION['csrf_token']) || $posted_token !== $_SESSION['csrf_token']) {
        $_SESSION['mesaj'] = 'Güvenlik hatası!';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=iletisim&tab=seo');
        exit;
    }
    
    $fields = [
        'seo_title_tr', 'seo_title_en', 
        'seo_description_tr', 'seo_description_en',
        'seo_keywords_tr', 'seo_keywords_en',
        'seo_og_image', 'seo_canonical'
    ];
    
    try {
        foreach ($fields as $key) {
            $value = isset($_POST[$key]) ? trim($_POST[$key]) : '';
            $stmt = $db->prepare("INSERT INTO iletisim_seo_ayarlar (anahtar, deger) VALUES (?, ?) ON DUPLICATE KEY UPDATE deger = ?");
            $stmt->execute([$key, $value, $value]);
        }
		
		        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        $url = $site_url . '/iletisim';
        indexNowTekliGonder($url);
		
        $_SESSION['mesaj'] = '✅ SEO ayarları kaydedildi!';
        $_SESSION['mesaj_tip'] = 'success';
    } catch (Exception $e) {
        $_SESSION['mesaj'] = 'Hata: ' . $e->getMessage();
        $_SESSION['mesaj_tip'] = 'error';
    }
    
    header('Location: ?modul=iletisim&tab=seo');
    exit;
}

// ========== TOPLU İŞLEM ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toplu_islem'])) {
    $posted_token = $_POST['csrf_token'] ?? '';
    
    if (!isset($_SESSION['csrf_token']) || $posted_token !== $_SESSION['csrf_token']) {
        $_SESSION['mesaj'] = 'Güvenlik hatası!';
        $_SESSION['mesaj_tip'] = 'error';
		
		
        header('Location: ?modul=iletisim');
        exit;
    }
    
    $islem = $_POST['toplu_islem'];
    $secili_ids = $_POST['secili_ids'] ?? [];
    
    if (empty($secili_ids)) {
        $_SESSION['mesaj'] = 'Lütfen en az bir mesaj seçin!';
        $_SESSION['mesaj_tip'] = 'warning';
        header('Location: ?modul=iletisim');
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($secili_ids), '?'));
    
    try {
        if ($islem === 'okundu') {
            $stmt = $db->prepare("UPDATE iletisim_mesajlari SET durum = 'okundu' WHERE id IN ($placeholders)");
            $stmt->execute($secili_ids);
            $_SESSION['mesaj'] = count($secili_ids) . ' mesaj okundu olarak işaretlendi.';
            $_SESSION['mesaj_tip'] = 'success';
        } elseif ($islem === 'sil') {
            $stmt = $db->prepare("UPDATE iletisim_mesajlari SET silindi = 1, silinme_tarihi = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($secili_ids);
            $_SESSION['mesaj'] = count($secili_ids) . ' mesaj çöp kutusuna taşındı.';
            $_SESSION['mesaj_tip'] = 'success';
        } elseif ($islem === 'kalici_sil') {
            $stmt = $db->prepare("DELETE FROM iletisim_mesajlari WHERE id IN ($placeholders)");
            $stmt->execute($secili_ids);
            $_SESSION['mesaj'] = count($secili_ids) . ' mesaj kalıcı olarak silindi.';
            $_SESSION['mesaj_tip'] = 'success';
        } elseif ($islem === 'geri_al') {
            $stmt = $db->prepare("UPDATE iletisim_mesajlari SET silindi = 0, silinme_tarihi = NULL WHERE id IN ($placeholders)");
            $stmt->execute($secili_ids);
            $_SESSION['mesaj'] = count($secili_ids) . ' mesaj geri alındı.';
            $_SESSION['mesaj_tip'] = 'success';
        }
		
		        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        $url = $site_url . '/iletisim';
        indexNowTekliGonder($url);
		
    } catch (Exception $e) {
        $_SESSION['mesaj'] = 'İşlem başarısız!';
        $_SESSION['mesaj_tip'] = 'error';
    }
    
    header('Location: ?modul=iletisim');
    exit;
}

// ========== TEKİL İŞLEMLER ==========
// Okundu işaretle
if (isset($_GET['islem']) && $_GET['islem'] === 'okundu' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $db->prepare("UPDATE iletisim_mesajlari SET durum = 'okundu' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mesaj'] = 'Mesaj okundu olarak işaretlendi.';
        $_SESSION['mesaj_tip'] = 'success';
    }
    header('Location: ?modul=iletisim');
    exit;
}

// Okunmadı işaretle (GERİ AL)
if (isset($_GET['islem']) && $_GET['islem'] === 'okunmadi' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $db->prepare("UPDATE iletisim_mesajlari SET durum = 'okunmadi' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mesaj'] = 'Mesaj okunmadı olarak işaretlendi.';
        $_SESSION['mesaj_tip'] = 'success';
    }
    header('Location: ?modul=iletisim');
    exit;
}

// Sil (çöp kutusuna)
if (isset($_GET['islem']) && $_GET['islem'] === 'sil' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $db->prepare("UPDATE iletisim_mesajlari SET silindi = 1, silinme_tarihi = NOW() WHERE id = ?");
        $stmt->execute([$id]);
		
		        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        $url = $site_url . '/iletisim';
        indexNowTekliGonder($url);
		
        $_SESSION['mesaj'] = 'Mesaj çöp kutusuna taşındı.';
        $_SESSION['mesaj_tip'] = 'success';
    }
    header('Location: ?modul=iletisim');
    exit;
}

// Kalıcı sil
if (isset($_GET['islem']) && $_GET['islem'] === 'kalici_sil' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $db->prepare("DELETE FROM iletisim_mesajlari WHERE id = ?");
        $stmt->execute([$id]);
		
		        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        $url = $site_url . '/iletisim';
        indexNowTekliGonder($url);
		
        $_SESSION['mesaj'] = 'Mesaj kalıcı olarak silindi.';
        $_SESSION['mesaj_tip'] = 'success';
    }
    header('Location: ?modul=iletisim&tab=silinen');
    exit;
}

// Geri al
if (isset($_GET['islem']) && $_GET['islem'] === 'geri_al' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $db->prepare("UPDATE iletisim_mesajlari SET silindi = 0, silinme_tarihi = NULL WHERE id = ?");
        $stmt->execute([$id]);
		
		        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/indexnow.php';
        $site_url = "https://www.dribrahimdurandentalclinic.com";
        $url = $site_url . '/iletisim';
        indexNowTekliGonder($url);
		
        $_SESSION['mesaj'] = 'Mesaj geri alındı.';
        $_SESSION['mesaj_tip'] = 'success';
    }
    header('Location: ?modul=iletisim&tab=silinen');
    exit;
}

// ========== MESAJLARI ÇEK ==========
if ($tab === 'silinen') {
    $mesajlar = $db->query("SELECT * FROM iletisim_mesajlari WHERE silindi = 1 ORDER BY silinme_tarihi DESC")->fetchAll();
} else {
    if ($tab === 'okunmadi') {
        $mesajlar = $db->query("SELECT * FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL) AND (durum = 'okunmadi' OR durum = '' OR durum IS NULL) ORDER BY created_at DESC")->fetchAll();
    } elseif ($tab === 'okundu') {
        $mesajlar = $db->query("SELECT * FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL) AND (durum = 'okundu') ORDER BY created_at DESC")->fetchAll();
    } else {
        $mesajlar = $db->query("SELECT * FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL) ORDER BY created_at DESC")->fetchAll();
    }
}

// ========== İSTATİSTİKLER ==========
$okunmadi = $db->query("SELECT COUNT(*) FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL) AND (durum = 'okunmadi' OR durum = '' OR durum IS NULL)")->fetchColumn() ?: 0;
$okundu = $db->query("SELECT COUNT(*) FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL) AND (durum = 'okundu')")->fetchColumn() ?: 0;
$toplam = $okunmadi + $okundu;
$silinen = $db->query("SELECT COUNT(*) FROM iletisim_mesajlari WHERE silindi = 1")->fetchColumn() ?: 0;
?>
<div class="space-y-6">
    <!-- Başlık -->
    <div class="mb-8 px-2">
        <div class="bg-slate-900 rounded-2xl p-6 shadow-2xl border-l-8 border-purple-600">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-white tracking-tight">İletişim Mesajları</h1>
                    <p class="text-slate-400 text-sm mt-1">Hoş geldin, <?php echo $_SESSION['admin_adi'] ?? 'Süper Admin'; ?></p>
                </div>
                <button onclick="location.reload()" class="h-12 w-12 bg-slate-800 border border-slate-700 text-slate-300 rounded-xl hover:bg-purple-600 hover:text-white transition-all">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mesaj -->
    <?php if (isset($_SESSION['mesaj'])): ?>
    <div class="p-4 rounded-lg <?php echo $_SESSION['mesaj_tip'] == 'success' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'; ?> border">
        <?php echo $_SESSION['mesaj']; ?>
    </div>
    <?php unset($_SESSION['mesaj'], $_SESSION['mesaj_tip']); endif; ?>

    <!-- İstatistik Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow border hover:shadow-lg transition-all">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Toplam Mesaj</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $toplam; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-envelope text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow border hover:shadow-lg transition-all <?php echo $okunmadi > 0 ? 'ring-2 ring-amber-400 ring-opacity-50' : ''; ?>">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Okunmamış</p>
                    <p class="text-3xl font-bold text-amber-600 mt-1"><?php echo $okunmadi; ?></p>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center <?php echo $okunmadi > 0 ? 'animate-pulse' : ''; ?>">
                    <i class="fas fa-inbox text-amber-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow border hover:shadow-lg transition-all">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Okunan</p>
                    <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $okundu; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-double text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow border hover:shadow-lg transition-all <?php echo $silinen > 0 ? 'ring-2 ring-red-400 ring-opacity-50' : ''; ?>">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Silinen</p>
                    <p class="text-3xl font-bold text-red-600 mt-1"><?php echo $silinen; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-trash-alt text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Sekmeler -->
    <div class="border-b border-gray-200">
        <nav class="flex gap-6 flex-wrap">
            <a href="?modul=iletisim&tab=okunmadi" class="pb-3 px-1 text-sm font-semibold transition-all duration-200 <?php echo $tab === 'okunmadi' ? 'text-amber-600 border-b-2 border-amber-600' : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300'; ?>">
                <i class="fas fa-inbox mr-2"></i>Okunmamış
                <?php if($okunmadi > 0): ?>
                <span class="ml-1 px-2 py-0.5 bg-amber-100 text-amber-600 text-xs rounded-full animate-pulse"><?php echo $okunmadi; ?></span>
                <?php endif; ?>
            </a>
            <a href="?modul=iletisim&tab=okundu" class="pb-3 px-1 text-sm font-semibold transition-all duration-200 <?php echo $tab === 'okundu' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300'; ?>">
                <i class="fas fa-check-double mr-2"></i>Okunan
                <span class="ml-1 text-xs">(<?php echo $okundu; ?>)</span>
            </a>
            <a href="?modul=iletisim&tab=hepsi" class="pb-3 px-1 text-sm font-semibold transition-all duration-200 <?php echo $tab === 'hepsi' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300'; ?>">
                <i class="fas fa-envelope mr-2"></i>Tümü
                <span class="ml-1 text-xs">(<?php echo $toplam; ?>)</span>
            </a>
            <a href="?modul=iletisim&tab=silinen" class="pb-3 px-1 text-sm font-semibold transition-all duration-200 <?php echo $tab === 'silinen' ? 'text-red-600 border-b-2 border-red-600' : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300'; ?>">
                <i class="fas fa-trash-alt mr-2"></i>Silinen
                <?php if($silinen > 0): ?>
                <span class="ml-1 px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded-full"><?php echo $silinen; ?></span>
                <?php endif; ?>
            </a>
            <a href="?modul=iletisim&tab=seo" class="pb-3 px-1 text-sm font-semibold transition-all duration-200 <?php echo $tab === 'seo' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300'; ?>">
                <i class="fas fa-search mr-2"></i>SEO
            </a>
        </nav>
    </div>

    <?php if ($tab === 'seo'): ?>
    
    <!-- SEO PANELİ (Aynı kalacak) -->
    <!-- ... SEO kısmı aynen devam eder ... -->
    
    <?php else: ?>
    
    <!-- Toplu İşlem Çubuğu -->
    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6 flex flex-wrap items-center justify-between gap-4 shadow-sm">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-sm font-bold text-gray-700"><i class="fas fa-tasks mr-1"></i> Toplu İşlem:</span>
            
            <?php if ($tab === 'silinen'): ?>
            <button type="button" id="topluGeriAlBtn" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm rounded-lg transition cursor-pointer flex items-center gap-1">
                <i class="fas fa-undo"></i> Geri Al
            </button>
            <button type="button" id="topluKaliciSilBtn" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm rounded-lg transition cursor-pointer flex items-center gap-1">
                <i class="fas fa-trash-alt"></i> Kalıcı Sil
            </button>
            <?php else: ?>
            <button type="button" id="topluOkunduBtn" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm rounded-lg transition cursor-pointer flex items-center gap-1">
                <i class="fas fa-check-double"></i> Okundu İşaretle
            </button>
            <button type="button" id="topluSilBtn" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm rounded-lg transition cursor-pointer flex items-center gap-1">
                <i class="fas fa-trash-alt"></i> Toplu Sil
            </button>
            <?php endif; ?>
            
            <div class="w-px h-6 bg-gray-300 mx-1"></div>
            <button type="button" id="tumunuSec" class="px-3 py-1.5 bg-gray-100 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition cursor-pointer">Tümünü Seç</button>
            <button type="button" id="secimiTemizle" class="px-3 py-1.5 bg-gray-100 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition cursor-pointer">Seçimi Temizle</button>
        </div>
        <div class="text-sm text-gray-600 bg-gray-100 px-4 py-2 rounded-full">
            <i class="far fa-check-circle mr-1"></i>
            <span id="seciliSayac">0</span> mesaj seçildi
        </div>
    </div>
    
    <!-- Mesaj Kartları Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (count($mesajlar) > 0): ?>
            <?php foreach ($mesajlar as $m): 
                $isNew = ($m['durum'] == 'okunmadi' || $m['durum'] == '' || $m['durum'] === null);
            ?>
            <div class="bg-white rounded-2xl border <?php echo $isNew ? 'border-amber-300 ring-2 ring-amber-200' : 'border-gray-100'; ?> shadow-md hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <div class="h-2 <?php echo $isNew ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-green-500 to-emerald-500'; ?>"></div>
                
                <div class="p-5">
                    <!-- ÜST KISIM: ID ve Durum -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600 text-sm font-bold">
                                #<?php echo $m['id']; ?>
                            </div>
                            <span class="text-xs text-gray-400"><?php echo date('d.m.Y H:i', strtotime($m['created_at'])); ?></span>
                            <?php if($tab === 'silinen'): ?>
                            <span class="text-xs text-red-400 ml-1">(Silindi: <?php echo date('d.m.Y H:i', strtotime($m['silinme_tarihi'])); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php if($isNew): ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 border border-amber-200 animate-pulse">
                            <i class="fas fa-inbox text-[10px]"></i> Okunmamış
                        </span>
                        <?php elseif($tab === 'silinen'): ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 border border-red-200">
                            <i class="fas fa-trash-alt text-[10px]"></i> Silinmiş
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 border border-green-200">
                            <i class="fas fa-check-double text-[10px]"></i> Okundu
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Gönderen Bilgileri -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br <?php echo $isNew ? 'from-amber-500 to-orange-600' : 'from-purple-500 to-pink-600'; ?> flex items-center justify-center text-white font-bold text-lg shadow-md">
                            <?php echo substr($m['ad_soyad'], 0, 1); ?>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 text-base"><?php echo htmlspecialchars($m['ad_soyad']); ?></h4>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-500 flex items-center gap-1">
                                    <i class="fas fa-phone-alt text-[10px]"></i> <?php echo htmlspecialchars($m['telefon']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- İletişim Bilgileri -->
                    <div class="mb-3 p-2 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <i class="fas fa-envelope text-gray-400"></i>
                            <span class="truncate"><?php echo htmlspecialchars($m['email']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Konu ve Mesaj -->
                    <div class="mb-3">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fas fa-tag text-gray-400 text-xs"></i>
<span class="text-xs font-semibold <?php echo $isNew ? 'text-amber-700' : 'text-gray-700'; ?>">
    <?php 
    $konu_goster = $m['konu'] ?: 'Konu belirtilmemiş';
    // Eğer konu "Diğer:" ile başlıyorsa, sadece "Diğer" yazıp yanına özel konuyu ekleyelim
    if (strpos($konu_goster, 'Konu :  ') === 0) {
        $ozel_konu = trim(str_replace('KONU : ', '', $konu_goster));
        echo '📝 KONU :  <span class="text-blue-600 font-bold">' . htmlspecialchars($ozel_konu) . '</span>';
    } else {
        echo htmlspecialchars($konu_goster);
    }
    ?>
</span>                       
					   </div>
                        <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg line-clamp-3 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($m['mesaj'])); ?>
                        </p>
                    </div>
                    
                    <!-- Checkbox -->
                    <div class="flex items-center justify-end mb-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-xs text-gray-500">Seç</span>
                            <input type="checkbox" name="secili_ids[]" value="<?php echo $m['id']; ?>" class="mesaj-checkbox w-4 h-4 rounded border-gray-300 text-emerald-500 cursor-pointer">
                        </label>
                    </div>
                    
                    <!-- 🔥 İŞLEM BUTONLARI (ONAYLA/REDDET EKLENDİ) -->
                    <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
                        <?php if ($tab === 'silinen'): ?>
                        <a href="?modul=iletisim&islem=geri_al&id=<?php echo $m['id']; ?>" data-id="<?php echo $m['id']; ?>" data-url="?modul=iletisim&islem=geri_al&id=<?php echo $m['id']; ?>" class="geri-al-btn flex-1 px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs rounded-lg transition text-center">
                            <i class="fas fa-undo mr-1"></i> Geri Al
                        </a>
                        <a href="?modul=iletisim&islem=kalici_sil&id=<?php echo $m['id']; ?>" data-id="<?php echo $m['id']; ?>" data-url="?modul=iletisim&islem=kalici_sil&id=<?php echo $m['id']; ?>" class="kalici-sil-btn flex-1 px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-xs rounded-lg transition text-center">
                            <i class="fas fa-trash-alt"></i> Kalıcı Sil
                        </a>
                        <?php else: ?>
                        
                        <!-- ✅ OKUNDU İŞARETLE (Okunmamışsa) -->
                        <?php if ($isNew): ?>
                        <a href="?modul=iletisim&islem=okundu&id=<?php echo $m['id']; ?>" data-id="<?php echo $m['id']; ?>" data-url="?modul=iletisim&islem=okundu&id=<?php echo $m['id']; ?>" class="okundu-btn flex-1 px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs rounded-lg transition text-center">
                            <i class="fas fa-check-double mr-1"></i> Okundu Olarak İşaretle
                        </a>
                        <?php else: ?>
                        <!-- ⏪ OKUNMADI İŞARETLE (Okunduysa) -->
                        <a href="?modul=iletisim&islem=okunmadi&id=<?php echo $m['id']; ?>" data-id="<?php echo $m['id']; ?>" data-url="?modul=iletisim&islem=okunmadi&id=<?php echo $m['id']; ?>" class="okunmadi-btn flex-1 px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs rounded-lg transition text-center">
                            <i class="fas fa-undo mr-1"></i> Okunmadı Olarak İşaretle
                        </a>
                        <?php endif; ?>
                        
                        <!-- 🗑️ SİL -->
                        <a href="?modul=iletisim&islem=sil&id=<?php echo $m['id']; ?>" data-id="<?php echo $m['id']; ?>" data-url="?modul=iletisim&islem=sil&id=<?php echo $m['id']; ?>" class="sil-btn px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-xs rounded-lg transition text-center">
                            <i class="fas fa-trash-alt"></i> Sil
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-inbox text-2xl text-gray-400"></i>
                </div>
                <p class="text-gray-500">Bu kategoride mesaj bulunmuyor</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>

</div>

<form id="topluIslemForm" method="POST" action="?modul=iletisim" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="toplu_islem" id="topluIslemTipi" value="">
</form>
<script>
// ========== CONFIRM MODAL (Aynı) ==========
function showConfirm(title, message, callback) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;';
    
    let iconClass = '';
    let buttonClass = '';
    let icon = '';
    let buttonText = '';
    
    if (title === 'Okundu İşaretle' || title === 'Okunmadı İşaretle') {
        iconClass = 'bg-amber-100 text-amber-600';
        buttonClass = 'bg-amber-500 hover:bg-amber-600';
        icon = 'fa-undo';
        buttonText = 'Evet, Onayla';
    } else if (title === 'Mesajları Sil' || title === 'Mesajı Sil' || title === 'Mesajları Kalıcı Sil') {
        iconClass = 'bg-red-100 text-red-600';
        buttonClass = 'bg-red-500 hover:bg-red-600';
        icon = 'fa-trash-alt';
        buttonText = 'Evet, Sil';
    } else if (title === 'Mesajları Geri Al' || title === 'Mesajı Geri Al') {
        iconClass = 'bg-emerald-100 text-emerald-600';
        buttonClass = 'bg-emerald-500 hover:bg-emerald-600';
        icon = 'fa-undo';
        buttonText = 'Evet, Geri Al';
    } else {
        iconClass = 'bg-blue-100 text-blue-600';
        buttonClass = 'bg-blue-500 hover:bg-blue-600';
        icon = 'fa-info-circle';
        buttonText = 'Evet';
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
                    <button id="confirmOk" class="flex-1 px-5 py-2 ${buttonClass} text-white rounded-lg transition cursor-pointer">${buttonText}</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.querySelector('#confirmCancel').onclick = function() { 
        modal.remove(); 
        if (callback) callback(false); 
    };
    modal.querySelector('#confirmOk').onclick = function() { 
        modal.remove(); 
        if (callback) callback(true); 
    };
}

// ========== SEO DİL DEĞİŞTİRME (Aynı) ==========
// ... (önceki kod aynen devam eder) ...

// ========== TOPLU İŞLEM (Aynı) ==========
function updateSeciliSayac() {
    var secili = document.querySelectorAll('.mesaj-checkbox:checked').length;
    var sayac = document.getElementById('seciliSayac');
    if (sayac) sayac.innerText = secili;
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('mesaj-checkbox')) {
        updateSeciliSayac();
    }
});

document.getElementById('tumunuSec')?.addEventListener('click', function() {
    document.querySelectorAll('.mesaj-checkbox').forEach(function(cb) {
        cb.checked = true;
    });
    updateSeciliSayac();
});

document.getElementById('secimiTemizle')?.addEventListener('click', function() {
    document.querySelectorAll('.mesaj-checkbox').forEach(function(cb) {
        cb.checked = false;
    });
    updateSeciliSayac();
});

// ========== TOPLU OKUNDU ==========
document.getElementById('topluOkunduBtn')?.addEventListener('click', function() {
    var secili = document.querySelectorAll('.mesaj-checkbox:checked');
    if (secili.length === 0) {
        alert('Lütfen en az bir mesaj seçin!');
        return;
    }
    
    showConfirm('Okundu İşaretle', secili.length + ' mesajı okundu olarak işaretlemek istediğinize emin misiniz?', function(confirmed) {
        if (confirmed) {
            document.querySelectorAll('#topluIslemForm input[name="secili_ids[]"]').forEach(function(el) {
                el.remove();
            });
            
            secili.forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'secili_ids[]';
                input.value = cb.value;
                document.getElementById('topluIslemForm').appendChild(input);
            });
            
            document.getElementById('topluIslemTipi').value = 'okundu';
            document.getElementById('topluIslemForm').submit();
        }
    });
});

// ========== TOPLU SİL ==========
document.getElementById('topluSilBtn')?.addEventListener('click', function() {
    var secili = document.querySelectorAll('.mesaj-checkbox:checked');
    if (secili.length === 0) {
        alert('Lütfen en az bir mesaj seçin!');
        return;
    }
    
    showConfirm('Mesajları Sil', secili.length + ' mesajı ÇÖP KUTUSUNA taşımak istediğinize emin misiniz?', function(confirmed) {
        if (confirmed) {
            document.querySelectorAll('#topluIslemForm input[name="secili_ids[]"]').forEach(function(el) {
                el.remove();
            });
            
            secili.forEach(function(cb) {
                var input = document.createElement('input');
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

// ========== SİLİNENLER - TOPLU GERİ AL ==========
document.getElementById('topluGeriAlBtn')?.addEventListener('click', function() {
    var secili = document.querySelectorAll('.mesaj-checkbox:checked');
    if (secili.length === 0) {
        alert('Lütfen en az bir mesaj seçin!');
        return;
    }
    
    showConfirm('Mesajları Geri Al', secili.length + ' mesajı geri almak istediğinize emin misiniz?', function(confirmed) {
        if (confirmed) {
            document.querySelectorAll('#topluIslemForm input[name="secili_ids[]"]').forEach(function(el) {
                el.remove();
            });
            
            secili.forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'secili_ids[]';
                input.value = cb.value;
                document.getElementById('topluIslemForm').appendChild(input);
            });
            
            document.getElementById('topluIslemTipi').value = 'geri_al';
            document.getElementById('topluIslemForm').submit();
        }
    });
});

// ========== SİLİNENLER - TOPLU KALICI SİL ==========
document.getElementById('topluKaliciSilBtn')?.addEventListener('click', function() {
    var secili = document.querySelectorAll('.mesaj-checkbox:checked');
    if (secili.length === 0) {
        alert('Lütfen en az bir mesaj seçin!');
        return;
    }
    
    showConfirm('Mesajları Kalıcı Sil', secili.length + ' mesajı KALICI OLARAK silmek istediğinize emin misiniz? Bu işlem geri alınamaz!', function(confirmed) {
        if (confirmed) {
            document.querySelectorAll('#topluIslemForm input[name="secili_ids[]"]').forEach(function(el) {
                el.remove();
            });
            
            secili.forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'secili_ids[]';
                input.value = cb.value;
                document.getElementById('topluIslemForm').appendChild(input);
            });
            
            document.getElementById('topluIslemTipi').value = 'kalici_sil';
            document.getElementById('topluIslemForm').submit();
        }
    });
});

// ========== TEKİL İŞLEMLER ==========
// Okundu işaretle
document.querySelectorAll('.okundu-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var url = this.getAttribute('data-url');
        showConfirm('Okundu İşaretle', 'Bu mesajı okundu olarak işaretlemek istediğinize emin misiniz?', function(confirmed) {
            if (confirmed) window.location.href = url;
        });
    });
});

// 🔥 OKUNMADI İŞARETLE (YENİ)
document.querySelectorAll('.okunmadi-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var url = this.getAttribute('data-url');
        showConfirm('Okunmadı İşaretle', 'Bu mesajı okunmadı olarak işaretlemek istediğinize emin misiniz?', function(confirmed) {
            if (confirmed) window.location.href = url;
        });
    });
});

// Sil
document.querySelectorAll('.sil-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var url = this.getAttribute('data-url');
        showConfirm('Mesajı Sil', 'Bu mesajı ÇÖP KUTUSUNA taşımak istediğinize emin misiniz?', function(confirmed) {
            if (confirmed) window.location.href = url;
        });
    });
});

// Geri Al - Tekil
document.querySelectorAll('.geri-al-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var url = this.getAttribute('data-url');
        showConfirm('Mesajı Geri Al', 'Bu mesajı geri almak istediğinize emin misiniz?', function(confirmed) {
            if (confirmed) window.location.href = url;
        });
    });
});

// Kalıcı Sil - Tekil
document.querySelectorAll('.kalici-sil-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var url = this.getAttribute('data-url');
        showConfirm('Mesajı Kalıcı Sil', 'Bu mesajı KALICI OLARAK silmek istediğinize emin misiniz? Bu işlem geri alınamaz!', function(confirmed) {
            if (confirmed) window.location.href = url;
        });
    });
});

// Sayfa yüklendiğinde sayaç güncelle
document.addEventListener('DOMContentLoaded', function() {
    updateSeciliSayac();
});
</script>