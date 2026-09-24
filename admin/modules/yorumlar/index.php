<?php
ob_start();
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CSRF token oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Modül erişim kontrolü
if (!modulErisim('yorumlar')) {
    yetkiYok('yorumlar');
}

// Toplu İşlem
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toplu_islem'])) {
    $posted_token = $_POST['csrf_token'] ?? '';
    
    if(!isset($_SESSION['csrf_token']) || $posted_token !== $_SESSION['csrf_token']) {
        $_SESSION['mesaj'] = 'Güvenlik hatası!';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=yorumlar');
        exit;
    }
    
    $islem = $_POST['toplu_islem'];
    $secili_ids = $_POST['secili_ids'] ?? [];
    
    if(empty($secili_ids)) {
        $_SESSION['mesaj'] = 'Lütfen en az bir yorum seçin!';
        $_SESSION['mesaj_tip'] = 'warning';
        header('Location: ?modul=yorumlar');
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($secili_ids), '?'));
    
try {
    if($islem === 'onayla') {
        $stmt = $db->prepare("UPDATE yorumlar SET onay = 1, silinme_tarihi = NULL WHERE id IN ($placeholders)");
        $stmt->execute($secili_ids);
        $_SESSION['mesaj'] = count($secili_ids) . ' yorum başarıyla onaylandı.';
        $_SESSION['mesaj_tip'] = 'success';
        $redirect_tab = 'hepsi';
    } elseif($islem === 'sil') {
        $stmt = $db->prepare("UPDATE yorumlar SET silinme_tarihi = NOW(), onay = 0 WHERE id IN ($placeholders)");
        $stmt->execute($secili_ids);
        $_SESSION['mesaj'] = count($secili_ids) . ' yorum çöp kutusuna taşındı.';
        $_SESSION['mesaj_tip'] = 'success';
        $redirect_tab = $_GET['tab'] ?? 'hepsi';
    } elseif($islem === 'kalici_sil') {
        $stmt = $db->prepare("DELETE FROM yorumlar WHERE id IN ($placeholders)");
        $stmt->execute($secili_ids);
        $_SESSION['mesaj'] = count($secili_ids) . ' yorum kalıcı olarak silindi.';
        $_SESSION['mesaj_tip'] = 'success';
        $redirect_tab = 'cop';
} elseif($islem === 'geri_yukle') {
    $stmt = $db->prepare("UPDATE yorumlar SET silinme_tarihi = NULL WHERE id IN ($placeholders)");
    $stmt->execute($secili_ids);
    $_SESSION['mesaj'] = count($secili_ids) . ' yorum geri yüklendi.';
    $_SESSION['mesaj_tip'] = 'success';
    $redirect_tab = 'hepsi';  // <--- BURASI 'hepsi' OLSUN
} 
	elseif($islem === 'reddet') {
        $stmt = $db->prepare("UPDATE yorumlar SET silinme_tarihi = NOW(), onay = 0 WHERE id IN ($placeholders)");
        $stmt->execute($secili_ids);
        $_SESSION['mesaj'] = count($secili_ids) . ' yorum reddedildi.';
        $_SESSION['mesaj_tip'] = 'warning';
        $redirect_tab = 'bekleyen';
    }
} catch(Exception $e) {
    $_SESSION['mesaj'] = 'İşlem başarısız!';
    $_SESSION['mesaj_tip'] = 'error';
    $redirect_tab = $_GET['tab'] ?? 'hepsi';
}
    
$redirect_tab = $redirect_tab ?? ($islem === 'onayla' ? 'hepsi' : 'bekleyen');
header('Location: ?modul=yorumlar&tab=' . $redirect_tab);    exit;
}

// Tekil ONAYLA
if (isset($_GET['islem']) && $_GET['islem'] === 'onayla' && isset($_GET['id'])) {
    if (!yetkiVar('yorumlar', 'onaylayabilir')) {
        $_SESSION['mesaj'] = 'Bu işlem için yetkiniz yok.';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=yorumlar');
        exit;
    }
    
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $db->prepare("UPDATE yorumlar SET onay = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['mesaj'] = 'Yorum başarıyla onaylandı.';
            $_SESSION['mesaj_tip'] = 'success';
        } catch (Exception $e) {
            $_SESSION['mesaj'] = 'Onaylama başarısız!';
            $_SESSION['mesaj_tip'] = 'error';
        }
    }
    header('Location: ?modul=yorumlar');
    exit;
}

// Tekil SİL
if (isset($_GET['islem']) && $_GET['islem'] === 'sil' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $db->prepare("UPDATE yorumlar SET silinme_tarihi = NOW(), onay = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['mesaj'] = 'Yorum çöp kutusuna taşındı.';
            $_SESSION['mesaj_tip'] = 'success';
        } catch (Exception $e) {
            $_SESSION['mesaj'] = 'Silme başarısız!';
            $_SESSION['mesaj_tip'] = 'error';
        }
    }
    header('Location: ?modul=yorumlar&tab=' . ($_GET['tab'] ?? 'hepsi'));
    exit;
}
// Kalıcı Sil
if (isset($_GET['islem']) && $_GET['islem'] === 'kalici_sil' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $db->prepare("DELETE FROM yorumlar WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['mesaj'] = 'Yorum kalıcı olarak silindi.';
            $_SESSION['mesaj_tip'] = 'success';
        } catch (Exception $e) {
            $_SESSION['mesaj'] = 'Silme başarısız!';
            $_SESSION['mesaj_tip'] = 'error';
        }
    }
    header('Location: ?modul=yorumlar&tab=cop');
    exit;
}

// Geri Yükle
if (isset($_GET['islem']) && $_GET['islem'] === 'geri_yukle' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $db->prepare("UPDATE yorumlar SET silinme_tarihi = NULL, onay = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['mesaj'] = 'Yorum geri yüklendi.';
            $_SESSION['mesaj_tip'] = 'success';
        } catch (Exception $e) {
            $_SESSION['mesaj'] = 'Geri yükleme başarısız!';
            $_SESSION['mesaj_tip'] = 'error';
        }
    }
    header('Location: ?modul=yorumlar&tab=hepsi');
    exit;
}
// DÜZENLE (GÜNCELLE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guncelle'])) {
    if (!yetkiVar('yorumlar', 'duzenleyebilir')) {
        $_SESSION['mesaj'] = 'Bu işlem için yetkiniz yok.';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=yorumlar');
        exit;
    }
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['mesaj'] = 'Güvenlik hatası!';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=yorumlar');
        exit;
    }
    
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tedavi = $_POST['tedavi'] ?? '';
    $yorum_metni = trim($_POST['yorum'] ?? '');
    $puan = filter_var($_POST['puan'] ?? 0, FILTER_VALIDATE_INT);
    
    if ($id && $ad_soyad && $yorum_metni) {
        try {
            $update = $db->prepare("UPDATE yorumlar SET ad_soyad = ?, email = ?, tedavi = ?, yorum = ?, puan = ? WHERE id = ?");
            $update->execute([$ad_soyad, $email, $tedavi, $yorum_metni, $puan, $id]);
            $_SESSION['mesaj'] = 'Yorum başarıyla güncellendi.';
            $_SESSION['mesaj_tip'] = 'success';
        } catch (Exception $e) {
            $_SESSION['mesaj'] = 'Güncelleme başarısız!';
            $_SESSION['mesaj_tip'] = 'error';
        }
    } else {
        $_SESSION['mesaj'] = 'Ad Soyad ve Yorum alanları boş olamaz!';
        $_SESSION['mesaj_tip'] = 'error';
    }
    header('Location: ?modul=yorumlar');
    exit;
}

// ========== VERİLERİ ÇEK ==========
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'hepsi';

if ($tab === 'cop') {
    $yorumlar = $db->query("SELECT * FROM yorumlar WHERE silinme_tarihi IS NOT NULL AND ust_id = 0 ORDER BY silinme_tarihi DESC")->fetchAll();
} else {
    // Tüm ana yorumlar (onay durumuna bakma, hepsini göster)
    $yorumlar = $db->query("SELECT * FROM yorumlar WHERE ust_id = 0 AND silinme_tarihi IS NULL ORDER BY id DESC")->fetchAll();
}


$onayli = $db->query("SELECT COUNT(*) FROM yorumlar WHERE onay = 1 AND ust_id = 0 AND silinme_tarihi IS NULL")->fetchColumn() ?: 0;
$cop = $db->query("SELECT COUNT(*) FROM yorumlar WHERE silinme_tarihi IS NOT NULL")->fetchColumn() ?: 0;
$toplam = $db->query("SELECT COUNT(*) FROM yorumlar WHERE ust_id = 0")->fetchColumn() ?: 0;

// Düzenlenecek yorum
$duzenlenecek = null;
if (isset($_GET['duzenle']) && $_GET['duzenle'] == 1 && isset($_GET['id'])) {
    if (yetkiVar('yorumlar', 'duzenleyebilir')) {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM yorumlar WHERE id = ?");
            $stmt->execute([$id]);
            $duzenlenecek = $stmt->fetch();
        }
    }
}
?>

<div class="space-y-6">
    <!-- Başlık ve İstatistik Kartları -->
    <div class="mb-8 px-2">
        <div class="bg-slate-900 rounded-2xl p-6 shadow-2xl border-l-8 border-blue-600 relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all duration-700"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-black text-white tracking-tight">Yorum Yönetimi</h1>
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 text-[10px] font-bold uppercase rounded-md border border-blue-500/30">Canlı Panel</span>
                    </div>
                    <p class="text-slate-400 text-sm font-medium mt-2 flex items-center gap-2">
                        <i class="fas fa-user-shield text-blue-500 text-xs"></i>
                        Hoş geldin, <span class="text-white font-bold"><?php echo $_SESSION['admin_adi'] ?? 'Süper Admin'; ?></span>
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
                    <button onclick="location.reload()" class="h-12 w-12 bg-slate-800 border border-slate-700 text-slate-300 rounded-xl hover:bg-blue-600 hover:text-white hover:border-blue-500 transition-all duration-300 shadow-lg active:scale-90 flex items-center justify-center">
                        <i class="fas fa-sync-alt group-hover:rotate-180 transition-transform duration-500"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mesaj Gösterimi -->
    <?php if (isset($_SESSION['mesaj'])): ?>
    <?php
    $mesaj_tip = $_SESSION['mesaj_tip'] ?? 'info';
    $mesaj_class = '';
    switch($mesaj_tip) {
        case 'success': $mesaj_class = 'bg-green-100 border-green-500 text-green-700'; break;
        case 'error': $mesaj_class = 'bg-red-100 border-red-500 text-red-700'; break;
        case 'warning': $mesaj_class = 'bg-yellow-100 border-yellow-500 text-yellow-700'; break;
        default: $mesaj_class = 'bg-blue-100 border-blue-500 text-blue-700';
    }
    ?>
    <div class="<?php echo $mesaj_class; ?> border-l-4 p-4 rounded-r-lg shadow-sm">
        <div class="flex items-center gap-2">
            <?php if($mesaj_tip == 'success'): ?>
            <i class="fas fa-check-circle"></i>
            <?php elseif($mesaj_tip == 'error'): ?>
            <i class="fas fa-exclamation-circle"></i>
            <?php elseif($mesaj_tip == 'warning'): ?>
            <i class="fas fa-exclamation-triangle"></i>
            <?php else: ?>
            <i class="fas fa-info-circle"></i>
            <?php endif; ?>
            <span class="font-medium"><?php echo $_SESSION['mesaj']; ?></span>
        </div>
    </div>
    <?php 
    unset($_SESSION['mesaj']);
    unset($_SESSION['mesaj_tip']);
    endif; 
    ?>

<!-- İstatistik Kartları - Modern Tasarım -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <!-- Toplam -->
    <div class="group relative bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Toplam Yorum</p>
                <p class="text-4xl font-black text-gray-800"><?php echo $toplam; ?></p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-comments text-white text-xl"></i>
            </div>
        </div>
        <div class="relative z-10 mt-4">
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-blue-500 h-1.5 rounded-full" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <!-- Onaylı -->
    <div class="group relative bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Onaylı</p>
                <p class="text-4xl font-black text-green-600"><?php echo $onayli; ?></p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-check-double text-white text-xl"></i>
            </div>
        </div>
        <div class="relative z-10 mt-4">
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-green-500 h-1.5 rounded-full" style="width: <?php echo $toplam > 0 ? ($onayli / $toplam) * 100 : 0; ?>%"></div>
            </div>
        </div>
    </div>



    <!-- Ortalama Puan -->
    <?php 
    $ortalamaPuan = $db->query("SELECT AVG(puan) FROM yorumlar WHERE onay = 1")->fetchColumn() ?: 0;
    ?>
    <div class="group relative bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Ortalama Puan</p>
                <div class="flex items-center gap-2">
                    <p class="text-4xl font-black text-purple-600"><?php echo number_format($ortalamaPuan, 1); ?></p>
                    <i class="fas fa-star text-amber-400 text-xl"></i>
                </div>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-chart-line text-white text-xl"></i>
            </div>
        </div>
        <div class="relative z-10 mt-4">
            <div class="flex gap-0.5">
                <?php for($i=1; $i<=5; $i++): ?>
                <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="bg-amber-400 h-full rounded-full" style="width: <?php echo $ortalamaPuan >= $i ? '100' : ($ortalamaPuan >= $i-1 ? ($ortalamaPuan - ($i-1)) * 100 : '0'); ?>%"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>
    <!-- Sekmeler -->
    <div class="border-b border-gray-200">
        <nav class="flex gap-6">
            <a href="?modul=yorumlar&tab=hepsi" class="pb-3 px-1 text-sm font-semibold <?php echo $tab === 'hepsi' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
                Tüm Sorular
            </a>

			<a href="?modul=yorumlar&tab=cop" class="pb-3 px-1 text-sm font-semibold <?php echo $tab === 'cop' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
    🗑️ Çöp Kutusu
    <?php if($cop > 0): ?>
    <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded-full"><?php echo $cop; ?></span>
    <?php endif; ?>
</a>
        </nav>
    </div>

    <?php if ($duzenlenecek): ?>
    <!-- DÜZENLEME FORMU -->
    <div class="bg-white rounded-xl shadow border overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <h3 class="text-white font-bold">Yorum Düzenle - ID: <?php echo $duzenlenecek['id']; ?></h3>
        </div>
        <div class="p-6">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $duzenlenecek['id']; ?>">
                <input type="hidden" name="guncelle" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Ad Soyad *</label>
                        <input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($duzenlenecek['ad_soyad']); ?>" required class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">E-posta</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($duzenlenecek['email']); ?>" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Tedavi</label>
                        <select name="tedavi" class="w-full px-4 py-2 border rounded-lg">
                            <option value="LAZER CERRAHİ" <?php echo $duzenlenecek['tedavi'] == 'LAZER CERRAHİ' ? 'selected' : ''; ?>>LAZER CERRAHİ</option>
                            <option value="DİJİTAL ÖLÇÜ" <?php echo $duzenlenecek['tedavi'] == 'DİJİTAL ÖLÇÜ' ? 'selected' : ''; ?>>DİJİTAL ÖLÇÜ</option>
                            <option value="CAD/CAM" <?php echo $duzenlenecek['tedavi'] == 'CAD/CAM' ? 'selected' : ''; ?>>CAD/CAM</option>
                            <option value="3D TOMOGRAFİ" <?php echo $duzenlenecek['tedavi'] == '3D TOMOGRAFİ' ? 'selected' : ''; ?>>3D TOMOGRAFİ</option>
                            <option value="GÜLÜŞ TASARIMI" <?php echo $duzenlenecek['tedavi'] == 'GÜLÜŞ TASARIMI' ? 'selected' : ''; ?>>GÜLÜŞ TASARIMI</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Puan</label>
                        <div class="flex gap-2">
                            <?php for($i=1; $i<=5; $i++): ?>
                            <label class="flex items-center gap-1 px-3 py-2 border rounded cursor-pointer <?php echo $duzenlenecek['puan'] == $i ? 'bg-amber-100 border-amber-400' : ''; ?>">
                                <input type="radio" name="puan" value="<?php echo $i; ?>" <?php echo $duzenlenecek['puan'] == $i ? 'checked' : ''; ?>> <?php echo $i; ?>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Yorum *</label>
                    <textarea name="yorum" rows="4" required class="w-full px-4 py-2 border rounded-lg"><?php echo htmlspecialchars($duzenlenecek['yorum']); ?></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Kaydet</button>
                    <a href="?modul=yorumlar" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">İptal</a>
                </div>
            </form>
        </div>
    </div>

    <?php else: ?>

        </div>
<?php if($tab === 'cop'): ?>
<!-- ÇÖP KUTUSU KARTLARI - aynı bekleyenlerdeki gibi ama butonlar farklı -->
<div class="bg-white rounded-xl shadow border overflow-hidden">
    <div class="bg-gradient-to-r from-red-600 to-rose-600 px-6 py-4">
        <h3 class="text-white font-bold flex items-center gap-2">
            <i class="fas fa-trash-alt"></i> Çöp Kutusu
            <span class="ml-2 px-2 py-0.5 bg-white/20 text-white text-xs rounded-full"><?php echo count($yorumlar); ?> yorum</span>
        </h3>
    </div>
    <div class="p-6">
        <!-- Toplu İşlem Çubuğu -->
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-sm font-bold text-red-700">Toplu İşlem:</span>
                <button type="button" id="topluGeriYukleBtn" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm rounded-lg transition cursor-pointer">📤 Geri Yükle</button>
                <button type="button" id="topluKaliciSilBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition cursor-pointer">🗑️ Kalıcı Sil</button>
                <button type="button" id="tumunuSecCop" class="px-3 py-1.5 bg-white border border-red-300 text-red-700 text-sm rounded-lg hover:bg-red-100 transition cursor-pointer">Tümünü Seç</button>
                <button type="button" id="secimiTemizleCop" class="px-3 py-1.5 bg-white border border-red-300 text-red-700 text-sm rounded-lg hover:bg-red-100 transition cursor-pointer">Seçimi Temizle</button>
            </div>
            <div class="text-sm text-red-600 bg-white px-4 py-2 rounded-full">
                <span id="seciliSayacCop">0</span> yorum seçildi
            </div>
        </div>
        
        <form id="topluIslemFormCop" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="toplu_islem" id="topluIslemTipiCop" value="">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($yorumlar as $y): ?>
                <div class="bg-white rounded-2xl border border-red-100 p-4 relative hover:shadow-lg transition">
                    <div class="absolute top-3 left-3 z-10">
                        <input type="checkbox" name="secili_ids[]" value="<?php echo $y['id']; ?>" class="yorum-checkbox-cop w-4 h-4 rounded border-red-400 text-red-500" onclick="event.stopPropagation()">
                    </div>
                    <div class="pt-8">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($y['ad_soyad']); ?></h4>
                                <span class="text-xs text-gray-400"><?php echo date('d.m.Y H:i', strtotime($y['created_at'])); ?></span>
                            </div>
                            <div class="flex items-center gap-1 bg-amber-50 px-2 py-1 rounded-lg">
                                <span class="font-bold text-amber-600"><?php echo $y['puan']; ?></span>
                                <i class="fas fa-star text-amber-400 text-xs"></i>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm my-3 line-clamp-3">"<?php echo htmlspecialchars(mb_substr($y['yorum'], 0, 100)); ?>..."</p>
                        <div class="flex gap-2 pt-2 border-t border-gray-100">
                            <a href="?modul=yorumlar&islem=geri_yukle&id=<?php echo $y['id']; ?>" class="flex-1 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs rounded-lg transition text-center">Geri Yükle</a>
                            <a href="?modul=yorumlar&islem=kalici_sil&id=<?php echo $y['id']; ?>" class="flex-1 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs rounded-lg transition text-center" onclick="return confirm('Kalıcı olarak silmek istediğinize emin misiniz?')">Kalıcı Sil</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>
     
<?php else: ?>
<!-- ========== TÜM YORUMLAR SEKMESİ ========== -->
<div class="bg-white rounded-xl shadow border overflow-hidden">
    <div class="bg-gray-800 px-6 py-4">
        <h3 class="text-white font-bold flex items-center gap-2">
            <i class="fas fa-comments"></i> Tüm Yorumlar
            <span class="ml-2 px-2 py-0.5 bg-gray-600 text-xs rounded-full"><?php echo count($yorumlar); ?> yorum</span>
        </h3>
    </div>
    
<div class="p-6">
    <!-- Toplu İşlem Çubuğu -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-sm font-bold text-blue-700">Toplu İşlem:</span>
            <button type="button" id="topluSilBtnTum" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm rounded-lg transition cursor-pointer">Çöp Kutusuna Taşı</button>
            <button type="button" id="tumunuSecTum" class="px-3 py-1.5 bg-white border border-blue-300 text-blue-700 text-sm rounded-lg hover:bg-blue-100 transition cursor-pointer">Tümünü Seç</button>
            <button type="button" id="secimiTemizleTum" class="px-3 py-1.5 bg-white border border-blue-300 text-blue-700 text-sm rounded-lg hover:bg-blue-100 transition cursor-pointer">Seçimi Temizle</button>
        </div>
        <div class="text-sm text-blue-600 bg-white px-4 py-2 rounded-full">
            <span id="seciliSayacTum">0</span> yorum seçildi
        </div>
    </div>
    
    <!-- TOPLU İŞLEM FORMU - Checkbox'lar ARTIK FORM İÇİNDE -->
    <form id="topluIslemFormTum" method="POST" action="?modul=yorumlar&tab=hepsi">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="toplu_islem" id="topluIslemTipiTum" value="">
        
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach($yorumlar as $y): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden relative cursor-pointer" onclick="openSoruModal(<?php echo $y['id']; ?>)">
        <div class="h-2 bg-gradient-to-r from-blue-500 to-purple-500"></div>
        
        <div class="absolute top-3 left-3 z-10">
            <input type="checkbox" name="secili_ids[]" value="<?php echo $y['id']; ?>" class="yorum-checkbox-tum w-4 h-4 rounded border-blue-400 text-blue-500" onclick="event.stopPropagation()">
        </div>
        
        <div class="p-5 pt-12">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-lg shadow-md">
                        <?php echo substr($y['ad_soyad'], 0, 1); ?>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($y['ad_soyad']); ?></h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-400"><?php echo date('d.m.Y', strtotime($y['created_at'])); ?></span>
                            <?php if($y['onay'] == 0): ?>
<span class="text-[10px] px-2 py-0.5 bg-amber-100 text-amber-600 rounded-full">Onay Bekliyor</span>
<?php else: ?>
<span class="text-[10px] px-2 py-0.5 bg-green-100 text-green-600 rounded-full">Onaylı</span>
<?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1 bg-amber-50 px-2 py-1 rounded-lg">
                    <span class="font-bold text-amber-600 text-sm"><?php echo $y['puan']; ?></span>
                    <span class="text-gray-400 text-xs">/5</span>
                    <i class="fas fa-star text-amber-400 text-xs"></i>
                </div>
            </div>
            
            <!-- SORU METNİ (Kısaltılmış) -->
            <p class="text-gray-700 text-sm leading-relaxed line-clamp-3 mb-3">
                "<?php echo htmlspecialchars(mb_substr($y['yorum'], 0, 100)); ?>..."
            </p>
            
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-50 text-purple-700 text-xs font-semibold rounded-full">
                    <i class="fas fa-tooth text-[10px]"></i> <?php echo htmlspecialchars($y['tedavi']); ?>
                </span>
                <span class="text-xs text-blue-500">
                    <i class="fas fa-comments"></i> 
                    <?php 
                    $cevapSay = $db->prepare("SELECT COUNT(*) FROM yorumlar WHERE ust_id = ?");
                    $cevapSay->execute([$y['id']]);
                    echo $cevapSay->fetchColumn(); 
                    ?> yanıt
                </span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
	</form>
    
    <?php if(empty($yorumlar)): ?>
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-inbox text-2xl text-gray-400"></i>
        </div>
        <p class="text-gray-500">Henüz onaylı yorum bulunmuyor</p>
    </div>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>
    <?php endif; ?>

<!-- SORU DETAY MODALI -->
<div id="soruModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-[9999] transition-all duration-300">
    <div class="bg-white rounded-2xl max-w-3xl w-full mx-4 shadow-2xl transform transition-all scale-95 opacity-0 max-h-[90vh] overflow-hidden flex flex-col" id="soruModalContent">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 rounded-t-2xl flex justify-between items-center">
            <h3 class="text-white font-bold text-lg">Soruyu İncele</h3>
            <button onclick="closeSoruModal()" class="text-white/80 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6" id="soruModalBody">
            <div class="text-center py-8 text-gray-400">Yükleniyor...</div>
        </div>
        
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            <div class="flex gap-3">
                <textarea id="modalCevapMetni" rows="2" class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Bu soruya cevap yazın..."></textarea>
                <button onclick="modalCevapGonder()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Gönder</button>
            </div>
        </div>
    </div>
</div>

<style>
    #soruModalContent {
        transition: all 0.3s ease;
    }
    .modal-open {
        overflow: hidden;
    }
</style>

<script>
let aktifSoruId = null;
// ANA SORU ONAY KONTROLÜ (BURASI EKLENDİ)

function openSoruModal(soruId) {
	
	
    aktifSoruId = soruId;

    const modal = document.getElementById('soruModal');
    const content = document.getElementById('soruModalContent');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('modal-open');
    
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 50);
    
    // Soru ve cevapları yükle
    fetch('/admin/api/soru-detay.php?id=' + soruId)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                let html = '';
                    if(data.soru.onay == 0 || data.soru.onay == "0") {
    html += `
        <div class="bg-amber-50 border-l-4 border-amber-500 rounded-xl p-4 mb-4 flex justify-between items-center">
            <div>
                <span class="font-bold text-amber-700">⚠️ Bu soru onay bekliyor!</span>
                <p class="text-sm text-amber-600">Onayladıktan sonra herkese görünür olacak. Reddederseniz silinecektir.</p>
            </div>
            <div class="flex gap-2">
                <button onclick="onaylaSoru(${data.soru.id})" class="px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition">
                    <i class="fas fa-check-circle"></i> Onayla
                </button>
                <button onclick="reddetSoru(${data.soru.id})" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                    <i class="fas fa-trash-alt"></i> Reddet
                </button>
            </div>
        </div>
    `;
}
                // ANA SORU İÇERİĞİ
                html += `
                    <div class="bg-gray-50 rounded-xl p-4 mb-6 border-l-4 border-blue-500">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold">
                                ${(data.soru.ad_soyad || 'A').charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">${escapeHtml(data.soru.ad_soyad)}</h4>
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <span>${data.soru.tarih}</span>
                                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded">${escapeHtml(data.soru.tedavi || 'Genel')}</span>
                                </div>
                            </div>
                            <div class="ml-auto flex items-center gap-1 bg-amber-50 px-2 py-1 rounded-lg">
                                <span class="font-bold text-amber-600">${data.soru.puan || 5}</span>
                                <i class="fas fa-star text-amber-400 text-xs"></i>
                            </div>
                        </div>
                        <p class="text-gray-700">${escapeHtml(data.soru.yorum)}</p>
                    </div>
                `;
                
                // CEVAPLAR (HER BİRİ İÇİN AYRI ONAY BUTONU)
                if(data.cevaplar && data.cevaplar.length > 0) {
                    html += `<div class="space-y-3 mt-4"><h5 class="font-bold text-gray-700 mb-2">Cevaplar:</h5>`;
                    data.cevaplar.forEach(cevap => {
                        const isAdmin = cevap.ad_soyad && (cevap.ad_soyad.includes('Prof') || cevap.email === 'admin@dribrahimdurandentalclinic.com');
                        const onayBekliyor = (cevap.onay == 0 || cevap.onay == "0");
                        
                        html += `
                            <div class="${isAdmin ? 'bg-blue-50' : 'bg-gray-50'} rounded-lg p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full ${isAdmin ? 'bg-blue-600' : 'bg-gray-400'} flex items-center justify-center text-white text-[10px] font-bold">
                                            ${(cevap.ad_soyad || 'C').charAt(0).toUpperCase()}
                                        </div>
                                        <span class="font-bold text-sm ${isAdmin ? 'text-blue-800' : 'text-gray-700'}">${escapeHtml(cevap.ad_soyad)}</span>
                                        <span class="text-xs text-gray-400">${cevap.tarih}</span>
                                    </div>
                                    ${onayBekliyor ? 
                                        '<span class="text-[10px] px-2 py-0.5 bg-amber-100 text-amber-600 rounded-full">⏳ Onay Bekliyor</span>' : 
                                        '<span class="text-[10px] px-2 py-0.5 bg-green-100 text-green-600 rounded-full">✅ Onaylı</span>'
                                    }
                                </div>
                                <p class="text-gray-700 text-sm">${escapeHtml(cevap.yorum)}</p>
                                ${onayBekliyor ? `
                                <div class="mt-3 flex gap-2">
                                    <button onclick="onaylaCevap(${cevap.id})" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs rounded-lg transition flex items-center gap-1">
                                        <i class="fas fa-check-circle"></i> Onayla
                                    </button>
                                    <button onclick="reddetCevap(${cevap.id})" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs rounded-lg transition flex items-center gap-1">
                                        <i class="fas fa-trash-alt"></i> Reddet
                                    </button>
                                </div>
                                ` : ''}
                            </div>
                        `;
                    });
                    html += `</div>`;
                } else {
                    html += `<div class="text-center py-4 text-gray-400">Henüz cevap yok. İlk cevabı siz yazın!</div>`;
                }
                
                document.getElementById('soruModalBody').innerHTML = html;
                document.getElementById('modalCevapMetni').value = '';
            } else {
                document.getElementById('soruModalBody').innerHTML = '<div class="text-center py-8 text-red-500">Yüklenirken hata oluştu</div>';
            }
        });
}

// Cevap onaylama
function onaylaCevap(cevapId) {
    if(confirm('Bu cevabı onaylamak istediğinize emin misiniz?')) {
        fetch('/admin/api/soru-onayla.php?id=' + cevapId)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('✅ Cevap başarıyla onaylandı!');
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            });
    }
}

// Cevap reddetme
function reddetCevap(cevapId) {
    if(confirm('Bu cevabı reddetmek istediğinize emin misiniz? Kalıcı olarak silinecektir.')) {
        fetch('/admin/api/soru-reddet.php?id=' + cevapId)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('✅ Cevap reddedildi ve silindi!');
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            });
    }
}

function onaylaSoru(soruId) {
    fetch('/admin/api/soru-onayla.php?id=' + soruId)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('✅ Soru başarıyla onaylandı!');
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        });
}

function reddetSoru(soruId) {
    if(confirm('Bu soruyu reddetmek istediğinize emin misiniz?')) {
        fetch('/admin/api/soru-reddet.php?id=' + soruId)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('✅ Soru reddedildi!');
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            });
    }
}

function closeSoruModal() {
    const modal = document.getElementById('soruModal');
    const content = document.getElementById('soruModalContent');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('modal-open');
    }, 200);
}

function modalCevapGonder() {
    const yorum = document.getElementById('modalCevapMetni').value;
    if(!yorum) {
        alert('Lütfen bir cevap yazın!');
        return;
    }
    
    const data = {
        ust_id: aktifSoruId,
        yorum: yorum,
        ad_soyad: 'Prof. Dr. İbrahim Duran',
        email: 'admin@dribrahimdurandentalclinic.com',
        tedavi: 'Cevap',
        onay: 1,
        csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
    };
    
    fetch('/api/cevap-gonder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if(result.success) {
            alert('✅ Cevap gönderildi!');
            location.reload();
        } else {
            alert('❌ ' + result.message);
        }
    });
}

function escapeHtml(str) {
    if(!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

// Modal dışına tıklayınca kapat
document.getElementById('soruModal')?.addEventListener('click', function(e) {
    if(e.target === this) closeSoruModal();
});

// Esc tuşu ile kapat
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape' && !document.getElementById('soruModal')?.classList.contains('hidden')) {
        closeSoruModal();
    }
});
function onaylaSoru(soruId) {
    fetch('/admin/api/soru-onayla.php?id=' + soruId)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('✅ Soru başarıyla onaylandı!');
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        });
}
function reddetSoru(soruId) {
    if(confirm('Bu soruyu reddetmek istediğinize emin misiniz? Soru kalıcı olarak silinecektir.')) {
        fetch('/admin/api/soru-reddet.php?id=' + soruId)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('✅ Soru reddedildi ve silindi!');
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            });
    }
}
function closeSoruModal() {
    const modal = document.getElementById('soruModal');
    const content = document.getElementById('soruModalContent');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('modal-open');
    }, 200);
}

function modalCevapGonder() {
    const yorum = document.getElementById('modalCevapMetni').value;
    if(!yorum) {
        alert('Lütfen bir cevap yazın!');
        return;
    }
    
    const data = {
        ust_id: aktifSoruId,
        yorum: yorum,
        ad_soyad: 'Prof. Dr. İbrahim Duran',
        email: 'admin@dribrahimdurandentalclinic.com',
        tedavi: 'Cevap',
        onay: 1,
        csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
    };
    
    fetch('/api/cevap-gonder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if(result.success) {
            // Modal'ı kapatmadan cevabı ekle
            const cevapHtml = `
                <div class="bg-blue-50 rounded-lg p-3 mt-3">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-white text-[10px] font-bold">P</div>
                        <span class="font-bold text-sm text-blue-800">Prof. Dr. İbrahim Duran</span>
                        <span class="text-xs text-gray-400">Şimdi</span>
                    </div>
                    <p class="text-gray-700 text-sm">${escapeHtml(yorum)}</p>
                </div>
            `;
            
            const container = document.getElementById('soruModalBody');
            const mevcutHtml = container.innerHTML;
            
            if(mevcutHtml.includes('Henüz cevap yok')) {
                container.innerHTML = mevcutHtml.replace('Henüz cevap yok. İlk cevabı siz yazın!', cevapHtml);
            } else {
                // Cevap listesinin sonuna ekle
                const cevapDiv = container.querySelector('.space-y-3');
                if(cevapDiv) {
                    cevapDiv.innerHTML += cevapHtml;
                } else {
                    container.innerHTML = mevcutHtml + cevapHtml;
                }
            }
            
            document.getElementById('modalCevapMetni').value = '';
            alert('✅ Cevap gönderildi!');
        } else {
            alert('❌ ' + result.message);
        }
    })
    .catch(error => {
        alert('❌ Bir hata oluştu: ' + error.message);
    });
}

function escapeHtml(str) {
    if(!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

// Modal dışına tıklayınca kapat
document.getElementById('soruModal')?.addEventListener('click', function(e) {
    if(e.target === this) closeSoruModal();
});

// Esc tuşu ile kapat
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape' && !document.getElementById('soruModal')?.classList.contains('hidden')) {
        closeSoruModal();
    }
});
</script>
<style>
    #cevapModalContent {
        transition: all 0.3s ease;
    }
    .modal-open {
        overflow: hidden;
    }
</style>

<script>
<?php if($tab === 'bekleyen'): ?>
function showConfirm(title, message, callback) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 overflow-hidden shadow-2xl">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center ${title === 'Onayla' ? 'bg-emerald-100 text-emerald-600' : (title === 'Sil' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600')}">
                        <i class="fas ${title === 'Onayla' ? 'fa-check-circle' : (title === 'Sil' ? 'fa-trash-alt' : 'fa-times-circle')} text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">${title}</h3>
                </div>
                <p class="text-gray-600 mb-6">${message}</p>
                <div class="flex gap-3">
                    <button id="confirmCancel" class="flex-1 px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition cursor-pointer">İptal</button>
                    <button id="confirmOk" class="flex-1 px-5 py-2 ${title === 'Onayla' ? 'bg-emerald-500 hover:bg-emerald-600' : (title === 'Sil' ? 'bg-red-500 hover:bg-red-600' : 'bg-amber-500 hover:bg-amber-600')} text-white rounded-lg transition cursor-pointer">${title}</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    modal.querySelector('#confirmCancel').onclick = () => { modal.remove(); callback(false); };
    modal.querySelector('#confirmOk').onclick = () => { modal.remove(); callback(true); };
}

function updateSeciliSayac() {
    let secili = document.querySelectorAll('.yorum-checkbox:checked').length;
    document.getElementById('seciliSayac').innerText = secili;
}

document.querySelectorAll('.yorum-checkbox').forEach(cb => cb.addEventListener('change', updateSeciliSayac));

document.getElementById('topluOnaylaBtn')?.addEventListener('click', function() {
    let secili = document.querySelectorAll('.yorum-checkbox:checked');
    if(secili.length === 0) { alert('Yorum seçin'); return; }
    showConfirm('Onayla', secili.length + ' yorumu onayla?', (confirmed) => {
        if(confirmed) { document.getElementById('topluIslemTipi').value = 'onayla'; document.getElementById('topluIslemForm').submit(); }
    });
});
document.getElementById('topluSilBtn')?.addEventListener('click', function() {
    let secili = document.querySelectorAll('.yorum-checkbox:checked');
    if(secili.length === 0) { alert('Yorum seçin'); return; }
    showConfirm('Sil', secili.length + ' Yorumu Çöp Kutusuna Taşı?', (confirmed) => {
        if(confirmed) { document.getElementById('topluIslemTipi').value = 'sil'; document.getElementById('topluIslemForm').submit(); }
    });
});
document.getElementById('topluReddetBtn')?.addEventListener('click', function() {
    let secili = document.querySelectorAll('.yorum-checkbox:checked');
    if(secili.length === 0) { alert('Yorum seçin'); return; }
    showConfirm('Reddet', secili.length + ' yorumu reddet ve sil?', (confirmed) => {
        if(confirmed) { document.getElementById('topluIslemTipi').value = 'reddet'; document.getElementById('topluIslemForm').submit(); }
    });
});
document.getElementById('tumunuSec')?.addEventListener('click', function() {
    document.querySelectorAll('.yorum-checkbox').forEach(cb => cb.checked = true);
    updateSeciliSayac();
});
document.getElementById('secimiTemizle')?.addEventListener('click', function() {
    document.querySelectorAll('.yorum-checkbox').forEach(cb => cb.checked = false);
    updateSeciliSayac();
});
document.querySelectorAll('.tek-islem-onayla').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let id = this.getAttribute('data-id');
        showConfirm('Onayla', 'Bu yorumu onayla?', (confirmed) => {
            if(confirmed) window.location.href = '?modul=yorumlar&islem=onayla&id=' + id;
        });
    });
});
document.querySelectorAll('.tek-islem-sil').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let id = this.getAttribute('data-id');
        showConfirm('Sil', 'Bu Yorumu Çöp Kutusuna Taşımak İstermisin?', (confirmed) => {
            if(confirmed) window.location.href = '?modul=yorumlar&islem=sil&id=' + id;
        });
    });
});
updateSeciliSayac();
<?php endif; ?>

<?php if($tab !== 'bekleyen'): ?>
function showConfirm(title, message, callback) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 overflow-hidden shadow-2xl">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center ${title === 'Onayla' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'}">
                        <i class="fas ${title === 'Onayla' ? 'fa-check-circle' : 'fa-trash-alt'} text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">${title}</h3>
                </div>
                <p class="text-gray-600 mb-6">${message}</p>
                <div class="flex gap-3">
                    <button id="confirmCancel" class="flex-1 px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition cursor-pointer">İptal</button>
                    <button id="confirmOk" class="flex-1 px-5 py-2 ${title === 'Onayla' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600'} text-white rounded-lg transition cursor-pointer">${title}</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    modal.querySelector('#confirmCancel').onclick = () => { modal.remove(); callback(false); };
    modal.querySelector('#confirmOk').onclick = () => { modal.remove(); callback(true); };
}
document.querySelectorAll('.tek-islem-onayla').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let id = this.getAttribute('data-id');
        showConfirm('Onayla', 'Bu yorumu onayla?', (confirmed) => {
            if(confirmed) window.location.href = '?modul=yorumlar&islem=onayla&id=' + id;
        });
    });
});
document.querySelectorAll('.tek-islem-sil').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let id = this.getAttribute('data-id');
        showConfirm('Sil', 'Bu Yorumu Çöp Kutusuna Taşımak İstermisin?', (confirmed) => {
            if(confirmed) window.location.href = '?modul=yorumlar&islem=sil&id=' + id;
        });
    });
});
<?php endif; ?>
</script>
<?php if($tab === 'hepsi'): ?>
<script>
function showConfirmTum(title, message, callback) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 overflow-hidden shadow-2xl">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                        <i class="fas fa-trash-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">${title}</h3>
                </div>
                <p class="text-gray-600 mb-6">${message}</p>
                <div class="flex gap-3">
                    <button id="confirmCancel" class="flex-1 px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition cursor-pointer">İptal</button>
                    <button id="confirmOk" class="flex-1 px-5 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition cursor-pointer">${title}</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    modal.querySelector('#confirmCancel').onclick = () => { modal.remove(); callback(false); };
    modal.querySelector('#confirmOk').onclick = () => { modal.remove(); callback(true); };
}

function updateSeciliSayacTum() {
    let secili = document.querySelectorAll('.yorum-checkbox-tum:checked').length;
    document.getElementById('seciliSayacTum').innerText = secili;
}

document.querySelectorAll('.yorum-checkbox-tum').forEach(cb => cb.addEventListener('change', updateSeciliSayacTum));

document.getElementById('topluSilBtnTum')?.addEventListener('click', function() {
    let secili = document.querySelectorAll('.yorum-checkbox-tum:checked');
    if(secili.length === 0) { alert('Yorum seçin'); return; }
    showConfirmTum('Sil', secili.length + ' Yorumu Çöp Kutusuna Taşı?', (confirmed) => {
        if(confirmed) { 
            document.getElementById('topluIslemTipiTum').value = 'sil';
            document.getElementById('topluIslemFormTum').submit(); 
        }
    });
});

document.getElementById('tumunuSecTum')?.addEventListener('click', function() {
    document.querySelectorAll('.yorum-checkbox-tum').forEach(cb => cb.checked = true);
    updateSeciliSayacTum();
});

document.getElementById('secimiTemizleTum')?.addEventListener('click', function() {
    document.querySelectorAll('.yorum-checkbox-tum').forEach(cb => cb.checked = false);
    updateSeciliSayacTum();
});

document.querySelectorAll('.tek-islem-sil-tum').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let id = this.getAttribute('data-id');
        showConfirmTum('Sil', 'Bu Yorumu Çöp Kutusuna Taşımak İstermisin?', (confirmed) => {
            if(confirmed) window.location.href = '?modul=yorumlar&islem=sil&id=' + id;
        });
    });
});

updateSeciliSayacTum();
</script>


<?php endif; ?>

<script>
// Çöp Kutusu için JavaScript
function updateSeciliSayacCop() {
    let secili = document.querySelectorAll('.yorum-checkbox-cop:checked').length;
    let sayac = document.getElementById('seciliSayacCop');
    if(sayac) sayac.innerText = secili;
}

document.querySelectorAll('.yorum-checkbox-cop').forEach(cb => {
    cb.addEventListener('change', updateSeciliSayacCop);
});

// Tümünü Seç
document.getElementById('tumunuSecCop')?.addEventListener('click', function() {
    document.querySelectorAll('.yorum-checkbox-cop').forEach(cb => cb.checked = true);
    updateSeciliSayacCop();
});

// Seçimi Temizle
document.getElementById('secimiTemizleCop')?.addEventListener('click', function() {
    document.querySelectorAll('.yorum-checkbox-cop').forEach(cb => cb.checked = false);
    updateSeciliSayacCop();
});

// Toplu Geri Yükle
document.getElementById('topluGeriYukleBtn')?.addEventListener('click', function() {
    let secili = document.querySelectorAll('.yorum-checkbox-cop:checked');
    if(secili.length === 0) {
        alert('Lütfen en az bir yorum seçin!');
        return;
    }
    if(confirm(secili.length + ' yorumu geri yüklemek istediğinize emin misiniz?')) {
        document.getElementById('topluIslemTipiCop').value = 'geri_yukle';
        document.getElementById('topluIslemFormCop').submit();
    }
});

// Toplu Kalıcı Sil
document.getElementById('topluKaliciSilBtn')?.addEventListener('click', function() {
    let secili = document.querySelectorAll('.yorum-checkbox-cop:checked');
    if(secili.length === 0) {
        alert('Lütfen en az bir yorum seçin!');
        return;
    }
    if(confirm(secili.length + ' yorumu KALICI OLARAK silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
        document.getElementById('topluIslemTipiCop').value = 'kalici_sil';
        document.getElementById('topluIslemFormCop').submit();
    }
});

updateSeciliSayacCop();
</script>

<?php ob_end_flush(); ?>