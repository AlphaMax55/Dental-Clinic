<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

// Sadece Süper Admin yetki verebilir
if ($_SESSION['admin_rol'] != 'superadmin') {
    yetkiYok('kullanicilar', 'yetki_verebilir');
}

$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$stmt->execute([$id]);
$kullanici = $stmt->fetch();

if (!$kullanici) {
    die('Kullanıcı bulunamadı!');
}

// Yetkileri kaydet
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Önce eski yetkileri sil
    $db->prepare("DELETE FROM yetkiler WHERE kullanici_id = ?")->execute([$id]);
    
    // Modül bazlı yetkileri ekle - TÜM MODÜLLER İÇİN 5 YETKİ
$moduller = [
    'yorumlar' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'tedaviler' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'blog' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'galeri' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'randevular' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'mesajlar' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'sayfalar' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'ayarlar' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'seo' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'raporlar' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
    'kullanicilar' => ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'],
];
    
    foreach ($moduller as $modul => $yetkiler) {
        foreach ($yetkiler as $yetki) {
            if (isset($_POST[$modul . '_' . $yetki])) {
                $stmt = $db->prepare("INSERT INTO yetkiler (kullanici_id, modul, yetki, deger) VALUES (?, ?, ?, 1)");
                $stmt->execute([$id, $modul, $yetki]);
            }
        }
    }
    
    $basarili = 'Yetkiler başarıyla güncellendi!';
}

// Mevcut yetkileri çek
$mevcut_yetkiler = [];
$stmt = $db->prepare("SELECT modul, yetki FROM yetkiler WHERE kullanici_id = ? AND deger = 1");
$stmt->execute([$id]);
while ($row = $stmt->fetch()) {
    $mevcut_yetkiler[$row['modul']][$row['yetki']] = true;
}
?>

<div class="max-w-6xl mx-auto">
    <!-- Başlık ve Geri Butonu -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="?modul=kullanicilar" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">
                Yetkiler: <?php echo $kullanici['ad_soyad']; ?>
                <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo $kullanici['kullanici_adi']; ?>)</span>
            </h1>
        </div>
        <div class="text-sm text-gray-500">
            Rol: 
            <?php if($kullanici['rol'] == 'superadmin'): ?>
            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full">👑 Süper Admin</span>
            <?php elseif($kullanici['rol'] == 'admin'): ?>
            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full">🛡️ Admin</span>
            <?php else: ?>
            <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full">✏️ Editor</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if(isset($basarili)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i> <?php echo $basarili; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <!-- Tümünü Seç / Seçimi Kaldır Butonları -->
<div class="px-6 pt-4 pb-2 bg-gray-50 border-b border-gray-200 flex justify-end gap-2">
    <button type="button" onclick="selectAllPage()" class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-all">
        <i class="fas fa-check-double mr-1"></i> Tümünü Seç
    </button>
    <button type="button" onclick="deselectAllPage()" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-all">
        <i class="fas fa-times mr-1"></i> Seçimi Kaldır
    </button>
</div>
            <!-- Yetki Tablosu Başlığı -->
            <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-key"></i>
                    Modül Bazlı Yetkiler
                </h2>
                <p class="text-xs text-gray-400 mt-1">Sadece işaretlediğiniz yetkiler kullanıcıya tanımlanacaktır</p>
            </div>

            <!-- Yetki Tablosu -->
            <div class="divide-y divide-gray-200">
                
                <!-- Yorumlar Modülü -->
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Yorumlar</h3>
                        <span class="text-xs text-gray-400">Yorum yönetimi</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="yorumlar_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['yorumlar']['gorebilir']) ? 'checked' : ''; ?>>
                            <span class="text-sm">👁️ Görebilir</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="yorumlar_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['yorumlar']['ekleyebilir']) ? 'checked' : ''; ?>>
                            <span class="text-sm">➕ Ekleyebilir</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="yorumlar_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['yorumlar']['duzenleyebilir']) ? 'checked' : ''; ?>>
                            <span class="text-sm">✏️ Düzenleyebilir</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="yorumlar_silebilir" value="1" <?php echo isset($mevcut_yetkiler['yorumlar']['silebilir']) ? 'checked' : ''; ?>>
                            <span class="text-sm">🗑️ Silebilir</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="yorumlar_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['yorumlar']['onaylayabilir']) ? 'checked' : ''; ?>>
                            <span class="text-sm">✅ Onaylayabilir</span>
                        </label>
                    </div>
                </div>
<!-- Kullanıcılar Modülü -->
<div class="p-6 hover:bg-gray-50 transition-colors">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
            <i class="fas fa-users"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Kullanıcılar</h3>
        <span class="text-xs text-gray-400">Kullanıcı yönetimi</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
        <label class="flex items-center gap-2"><input type="checkbox" name="kullanicilar_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['kullanicilar']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="kullanicilar_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['kullanicilar']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="kullanicilar_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['kullanicilar']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="kullanicilar_silebilir" value="1" <?php echo isset($mevcut_yetkiler['kullanicilar']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="kullanicilar_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['kullanicilar']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
    </div>
</div>
<!-- SEO Yönetimi Modülü -->
<div class="p-6 hover:bg-gray-50 transition-colors">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
            <i class="fas fa-chart-line"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">SEO Yönetimi</h3>
        <span class="text-xs text-gray-400">SEO ayarları, site haritası</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
        <label class="flex items-center gap-2"><input type="checkbox" name="seo_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['seo']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="seo_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['seo']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="seo_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['seo']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="seo_silebilir" value="1" <?php echo isset($mevcut_yetkiler['seo']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="seo_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['seo']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
    </div>
</div>
<!-- Raporlar Modülü -->
<div class="p-6 hover:bg-gray-50 transition-colors">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600">
            <i class="fas fa-chart-pie"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Raporlar</h3>
        <span class="text-xs text-gray-400">İstatistikler, analizler</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
        <label class="flex items-center gap-2"><input type="checkbox" name="raporlar_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['raporlar']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="raporlar_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['raporlar']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="raporlar_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['raporlar']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="raporlar_silebilir" value="1" <?php echo isset($mevcut_yetkiler['raporlar']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="raporlar_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['raporlar']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
    </div>
</div>
                <!-- Tedaviler Modülü -->
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                            <i class="fas fa-tooth"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Tedaviler</h3>
                        <span class="text-xs text-gray-400">Tedavi yönetimi</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
                        <label class="flex items-center gap-2"><input type="checkbox" name="tedaviler_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['tedaviler']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="tedaviler_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['tedaviler']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="tedaviler_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['tedaviler']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="tedaviler_silebilir" value="1" <?php echo isset($mevcut_yetkiler['tedaviler']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="tedaviler_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['tedaviler']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
                    </div>
                </div>

                <!-- Blog Modülü -->
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                            <i class="fas fa-blog"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Blog</h3>
                        <span class="text-xs text-gray-400">Blog yazıları</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
                        <label class="flex items-center gap-2"><input type="checkbox" name="blog_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['blog']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="blog_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['blog']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="blog_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['blog']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="blog_silebilir" value="1" <?php echo isset($mevcut_yetkiler['blog']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="blog_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['blog']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
                    </div>
                </div>

                <!-- Galeri Modülü -->
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center text-pink-600">
                            <i class="fas fa-images"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Galeri</h3>
                        <span class="text-xs text-gray-400">Görsel yönetimi</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
                        <label class="flex items-center gap-2"><input type="checkbox" name="galeri_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['galeri']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="galeri_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['galeri']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="galeri_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['galeri']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="galeri_silebilir" value="1" <?php echo isset($mevcut_yetkiler['galeri']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="galeri_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['galeri']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
                    </div>
                </div>

                <!-- Randevular Modülü -->
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Randevular</h3>
                        <span class="text-xs text-gray-400">Randevu yönetimi</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
                        <label class="flex items-center gap-2"><input type="checkbox" name="randevular_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['randevular']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="randevular_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['randevular']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="randevular_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['randevular']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="randevular_silebilir" value="1" <?php echo isset($mevcut_yetkiler['randevular']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="randevular_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['randevular']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
                    </div>
                </div>

                <!-- Mesajlar Modülü -->
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center text-teal-600">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Mesajlar</h3>
                        <span class="text-xs text-gray-400">İletişim mesajları</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
                        <label class="flex items-center gap-2"><input type="checkbox" name="mesajlar_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['mesajlar']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="mesajlar_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['mesajlar']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="mesajlar_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['mesajlar']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="mesajlar_silebilir" value="1" <?php echo isset($mevcut_yetkiler['mesajlar']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="mesajlar_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['mesajlar']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
                    </div>
                </div>

                <!-- Site Sayfaları Modülü -->
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600">
                            <i class="fas fa-file"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Site Sayfaları</h3>
                        <span class="text-xs text-gray-400">Hakkimizda, İletişim vb.</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
                        <label class="flex items-center gap-2"><input type="checkbox" name="sayfalar_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['sayfalar']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="sayfalar_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['sayfalar']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="sayfalar_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['sayfalar']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="sayfalar_silebilir" value="1" <?php echo isset($mevcut_yetkiler['sayfalar']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="sayfalar_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['sayfalar']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
                    </div>
                </div>

                <!-- Sistem Ayarları Modülü -->
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center text-white">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Sistem Ayarları</h3>
                        <span class="text-xs text-gray-400">Site ayarları, SEO</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-11">
                        <label class="flex items-center gap-2"><input type="checkbox" name="ayarlar_gorebilir" value="1" <?php echo isset($mevcut_yetkiler['ayarlar']['gorebilir']) ? 'checked' : ''; ?>> 👁️ Görebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="ayarlar_ekleyebilir" value="1" <?php echo isset($mevcut_yetkiler['ayarlar']['ekleyebilir']) ? 'checked' : ''; ?>> ➕ Ekleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="ayarlar_duzenleyebilir" value="1" <?php echo isset($mevcut_yetkiler['ayarlar']['duzenleyebilir']) ? 'checked' : ''; ?>> ✏️ Düzenleyebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="ayarlar_silebilir" value="1" <?php echo isset($mevcut_yetkiler['ayarlar']['silebilir']) ? 'checked' : ''; ?>> 🗑️ Silebilir</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="ayarlar_onaylayabilir" value="1" <?php echo isset($mevcut_yetkiler['ayarlar']['onaylayabilir']) ? 'checked' : ''; ?>> ✅ Onaylayabilir</label>
                    </div>
                </div>
            </div>

            <!-- Kaydet Butonu -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    Yetkileri Kaydet
                </button>
                <a href="?modul=kullanicilar" class="px-6 py-2.5 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 transition-all">
                    <i class="fas fa-times"></i>
                    İptal
                </a>
            </div>
        </div>
    </form>
	
	<script>
function selectAllPage() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = true;
    });
}

function deselectAllPage() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = false;
    });
}
</script>
</div>