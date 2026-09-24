<?php
// admin/dashboard/tabs/iletisim.php - İletişim Mesajları Tab'ı

// İşlemleri yakala
if (isset($_GET['islem']) && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        if ($_GET['islem'] === 'okundu') {
            $stmt = $db->prepare("UPDATE iletisim_mesajlari SET durum = 'okundu' WHERE id = ?");
            $stmt->execute([$id]);
            echo '<script>window.location.href = "?tab=iletisim";</script>';
            exit;
        }
        if ($_GET['islem'] === 'goster') {
            $stmt = $db->prepare("UPDATE iletisim_mesajlari SET durum = 'okundu' WHERE id = ?");
            $stmt->execute([$id]);
            echo '<script>window.location.href = "?tab=iletisim";</script>';
            exit;
        }
    }
}

// Verileri çek
$stmt = $db->query("SELECT * FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL) ORDER BY created_at DESC");
$mesajlar = $stmt->fetchAll();

$okunmadi = $db->query("SELECT COUNT(*) FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL) AND (durum = 'okunmadi' OR durum = '' OR durum IS NULL)")->fetchColumn() ?: 0;
$okundu = $db->query("SELECT COUNT(*) FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL) AND (durum = 'okundu')")->fetchColumn() ?: 0;
$toplam = $db->query("SELECT COUNT(*) FROM iletisim_mesajlari WHERE (silindi = 0 OR silindi IS NULL)")->fetchColumn() ?: 0;
$silinen = $db->query("SELECT COUNT(*) FROM iletisim_mesajlari WHERE silindi = 1")->fetchColumn() ?: 0;
?>

<div class="space-y-6">

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

    <!-- Mesaj Listesi -->
    <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-envelope text-white text-lg"></i>
                    <h3 class="text-white font-bold text-base">İletişim Mesajları</h3>
                </div>
                <span class="bg-white/20 text-white text-[10px] px-3 py-1 rounded-full"><?php echo $toplam; ?> mesaj</span>
            </div>
        </div>

        <div class="divide-y divide-gray-100">
            <?php if (count($mesajlar) > 0): ?>
                <?php foreach ($mesajlar as $m):
                    $isNew = ($m['durum'] == 'okunmadi' || $m['durum'] == '' || $m['durum'] === null);
                ?>
                <div class="p-5 hover:bg-gray-50 transition-all <?php echo $isNew ? 'bg-amber-50/50 border-l-4 border-l-amber-400' : ''; ?>">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <!-- Sol: Bilgiler -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($m['ad_soyad']); ?></span>
                                <?php if($isNew): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-700 border border-amber-200">
                                    <i class="fas fa-inbox text-[8px]"></i> Okunmamış
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-green-100 text-green-700 border border-green-200">
                                    <i class="fas fa-check-double text-[8px]"></i> Okundu
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-4 mt-1 text-xs text-gray-500">
                                <span><i class="fas fa-phone-alt mr-1"></i> <?php echo htmlspecialchars($m['telefon']); ?></span>
                                <span><i class="fas fa-envelope mr-1"></i> <?php echo htmlspecialchars($m['email']); ?></span>
                                <span><i class="fas fa-tag mr-1"></i> <?php echo htmlspecialchars($m['konu'] ?: 'Konu belirtilmemiş'); ?></span>
                                <span><i class="far fa-clock mr-1"></i> <?php echo date('d.m.Y H:i', strtotime($m['created_at'])); ?></span>
                            </div>
                            <div class="mt-2 text-sm text-gray-700 bg-gray-50 p-3 rounded-lg line-clamp-2">
                                <?php echo nl2br(htmlspecialchars($m['mesaj'])); ?>
                            </div>
                        </div>

                        <!-- 🔥 Sağ: SADECE OKUNDU ve PANELDE GÖSTER Butonları -->
                        <div class="flex gap-2 flex-shrink-0 flex-wrap">
                            <?php if ($isNew): ?>
                            <a href="?tab=iletisim&islem=okundu&id=<?php echo $m['id']; ?>" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-lg transition">
                                <i class="fas fa-check-double mr-1"></i> Okundu Olarak İşaretle
                            </a>
                            <?php endif; ?>
                            
<a href="/admin/index.php?modul=iletisim" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded-lg transition">
    <i class="fas fa-external-link-alt mr-1"></i> İletişim Modülüne Git
</a>
                            
                            <!-- 🚫 SİL BUTONU YOK! -->
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-inbox text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500">Henüz mesaj bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>