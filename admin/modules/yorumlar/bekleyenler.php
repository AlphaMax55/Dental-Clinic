<?php
ob_start(); // Header hatası için

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// YETKİ KONTROLÜ
if (!yetkiVar('yorumlar', 'onaylayabilir')) {
    yetkiYok('yorumlar', 'onaylayabilir');
}

$bekleyenler = $db->query("SELECT * FROM yorumlar WHERE onay = 0 ORDER BY id DESC")->fetchAll();
$sayi = count($bekleyenler);
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">Bekleyen Yorumlar</h2>
        <a href="?modul=yorumlar" class="text-sm text-blue-600 hover:text-blue-800">← Tüm yorumlara dön</a>
    </div>

    <?php if($sayi == 0): ?>
        <div class="bg-white rounded-xl p-8 text-center text-gray-500 border border-gray-200">
            <i class="fas fa-check-circle text-5xl text-green-400 mb-3 block"></i>
            <p class="text-lg font-medium">Bekleyen yorum bulunmuyor</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-amber-50 px-6 py-4 border-b border-amber-200">
                <h3 class="text-lg font-bold text-amber-800 flex items-center gap-2">
                    <i class="fas fa-clock"></i>
                    <?php echo $sayi; ?> yorum onay bekliyor
                </h3>
            </div>
            <div class="divide-y divide-gray-200">
                <?php foreach($bekleyenler as $y): ?>
                <div class="p-6 hover:bg-gray-50 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 font-bold">
                                    <?php echo strtoupper(substr($y['ad_soyad'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900"><?php echo htmlspecialchars($y['ad_soyad']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($y['email']); ?></p>
                                </div>
                            </div>
                            <p class="text-gray-700 mt-2 italic">"<?php echo htmlspecialchars($y['yorum']); ?>"</p>
                            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                                <span class="flex items-center gap-1"><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($y['tedavi']); ?></span>
                                <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> <?php echo $y['puan']; ?>/5</span>
                                <span class="flex items-center gap-1"><i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($y['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="flex gap-2 ml-4">
                            <!-- ONAYLA - POST ile -->
                            <form action="?modul=yorumlar&sayfa=onayla" method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $y['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <button type="submit" 
                                        class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-600 hover:text-white transition-all flex items-center gap-1 cursor-pointer"
                                        onclick="return confirm('Bu yorumu onaylamak istediğinize emin misiniz?');">
                                    <i class="fas fa-check"></i> Onayla
                                </button>
                            </form>
                            
                            <!-- SİL - POST ile -->
<!-- SİL - POST ile -->
<form action="?modul=yorumlar&sayfa=sil" method="POST" style="display: inline;">
    <input type="hidden" name="id" value="<?php echo $y['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-600 hover:text-white transition-all flex items-center gap-1 cursor-pointer"
            onclick="return confirm('Bu yorumu silmek istediğinize emin misiniz?');">
        <i class="fas fa-trash"></i> Sil
    </button>
</form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php ob_end_flush(); ?>