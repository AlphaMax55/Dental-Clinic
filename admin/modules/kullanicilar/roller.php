<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sadece Süper Admin rol yönetebilir
if ($_SESSION['admin_rol'] != 'superadmin') {
    yetkiYok('kullanicilar', 'rol_yonetebilir');
}

// TÜM MODÜLLER VE YETKİLER - BURASI EKLENDİ
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

$basarili = '';

// Varsayılan yetkileri kaydet
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Güvenlik hatası! Geçersiz token.');
    }
    
    $rol = $_POST['rol'] ?? 'editor';
    
    // Önce o rolün eski varsayılan yetkilerini sil
    $db->prepare("DELETE FROM rol_yetkileri WHERE rol = ?")->execute([$rol]);
    
    foreach ($moduller as $modul => $yetkiler) {
        foreach ($yetkiler as $yetki) {
            if (isset($_POST[$modul . '_' . $yetki])) {
                $stmt = $db->prepare("INSERT INTO rol_yetkileri (rol, modul, yetki, deger) VALUES (?, ?, ?, 1)");
                $stmt->execute([$rol, $modul, $yetki]);
            }
        }
    }
    
    $basarili = $rol . ' rolü için varsayılan yetkiler güncellendi!';
}

// Mevcut yetkileri çek
$mevcut_yetkiler = [];
$stmt = $db->query("SELECT rol, modul, yetki FROM rol_yetkileri WHERE deger = 1");
while ($row = $stmt->fetch()) {
    $mevcut_yetkiler[$row['rol']][$row['modul']][$row['yetki']] = true;
}

// Admin ve Editor rolleri için varsayılan yetkileri al
$admin_yetkiler = $mevcut_yetkiler['admin'] ?? [];
$editor_yetkiler = $mevcut_yetkiler['editor'] ?? [];

// Modül ikonları
$modul_icons = [
    'yorumlar' => ['icon' => 'fa-star', 'color' => 'blue', 'title' => 'Yorumlar'],
    'tedaviler' => ['icon' => 'fa-tooth', 'color' => 'green', 'title' => 'Tedaviler'],
    'blog' => ['icon' => 'fa-blog', 'color' => 'purple', 'title' => 'Blog'],
    'galeri' => ['icon' => 'fa-images', 'color' => 'pink', 'title' => 'Galeri'],
    'randevular' => ['icon' => 'fa-calendar-check', 'color' => 'amber', 'title' => 'Randevular'],
    'mesajlar' => ['icon' => 'fa-envelope', 'color' => 'teal', 'title' => 'Mesajlar'],
    'sayfalar' => ['icon' => 'fa-file', 'color' => 'indigo', 'title' => 'Sayfalar'],
    'ayarlar' => ['icon' => 'fa-cog', 'color' => 'gray', 'title' => 'Ayarlar'],
    'seo' => ['icon' => 'fa-chart-line', 'color' => 'green', 'title' => 'SEO'],
    'raporlar' => ['icon' => 'fa-chart-pie', 'color' => 'amber', 'title' => 'Raporlar'],
    'kullanicilar' => ['icon' => 'fa-users', 'color' => 'blue', 'title' => 'Kullanıcılar'],
];

$yetki_adlari = [
    'gorebilir' => '👁️ Görebilir',
    'ekleyebilir' => '➕ Ekleyebilir',
    'duzenleyebilir' => '✏️ Düzenleyebilir',
    'silebilir' => '🗑️ Silebilir',
    'onaylayabilir' => '✅ Onaylayabilir'
];
?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="?modul=kullanicilar" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Rol Bazlı Varsayılan Yetkiler</h1>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
            <div>
                <p class="text-sm text-blue-800 font-medium">Bu sayfada yapacağınız değişiklikler, o role sahip TÜM kullanıcıları etkiler.</p>
                <p class="text-xs text-blue-600 mt-1">Özel yetkiler (bireysel) bu varsayılanların üzerine eklenir. Özel yetki verdiğiniz kullanıcılar varsayılanın dışına çıkar.</p>
            </div>
        </div>
    </div>

    <?php if($basarili): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($basarili); ?>
    </div>
    <?php endif; ?>

    <!-- Admin Rolü Varsayılan Yetkileri -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">🛡️ Admin Rolü Varsayılan Yetkiler</h2>
                    <p class="text-sm text-blue-100">Admin rolündeki tüm kullanıcılar bu yetkilerle başlar</p>
                </div>
            </div>
        </div>
        
        <form method="POST" class="p-6">
            <input type="hidden" name="rol" value="admin">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="flex justify-end mb-4">
                <button type="button" onclick="selectAll('admin')" class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-all mr-2">
                    <i class="fas fa-check-double mr-1"></i> Tümünü Seç
                </button>
                <button type="button" onclick="deselectAll('admin')" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-all">
                    <i class="fas fa-times mr-1"></i> Seçimi Kaldır
                </button>
            </div>
            
            <div class="space-y-6">
                <?php foreach($moduller as $modul_key => $modul_yetkiler): 
                    $m = $modul_icons[$modul_key] ?? ['icon' => 'fa-cube', 'color' => 'gray', 'title' => ucfirst($modul_key)];
                ?>
                <div>
                    <h3 class="text-md font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas <?php echo $m['icon']; ?> text-<?php echo $m['color']; ?>-600"></i> <?php echo $m['title']; ?>
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-6">
                        <?php foreach($modul_yetkiler as $yetki): ?>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="<?php echo $modul_key . '_' . $yetki; ?>" value="1" <?php echo isset($admin_yetkiler[$modul_key][$yetki]) ? 'checked' : ''; ?>> 
                            <?php echo $yetki_adlari[$yetki]; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 pt-4 border-t border-gray-200">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all cursor-pointer">
                    <i class="fas fa-save mr-2"></i>Admin Rolü Yetkilerini Kaydet
                </button>
            </div>
        </form>
    </div>

    <!-- Editor Rolü Varsayılan Yetkileri -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-pen-alt text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">✏️ Editor Rolü Varsayılan Yetkiler</h2>
                    <p class="text-sm text-gray-300">Editor rolündeki tüm kullanıcılar bu yetkilerle başlar</p>
                </div>
            </div>
        </div>
        
        <form method="POST" class="p-6">
            <input type="hidden" name="rol" value="editor">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="flex justify-end mb-4">
                <button type="button" onclick="selectAll('editor')" class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-all mr-2">
                    <i class="fas fa-check-double mr-1"></i> Tümünü Seç
                </button>
                <button type="button" onclick="deselectAll('editor')" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-all">
                    <i class="fas fa-times mr-1"></i> Seçimi Kaldır
                </button>
            </div>
            
            <div class="space-y-6">
                <?php foreach($moduller as $modul_key => $modul_yetkiler): 
                    $m = $modul_icons[$modul_key] ?? ['icon' => 'fa-cube', 'color' => 'gray', 'title' => ucfirst($modul_key)];
                ?>
                <div>
                    <h3 class="text-md font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas <?php echo $m['icon']; ?> text-<?php echo $m['color']; ?>-600"></i> <?php echo $m['title']; ?>
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 ml-6">
                        <?php foreach($modul_yetkiler as $yetki): ?>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="<?php echo $modul_key . '_' . $yetki; ?>" value="1" <?php echo isset($editor_yetkiler[$modul_key][$yetki]) ? 'checked' : ''; ?>> 
                            <?php echo $yetki_adlari[$yetki]; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 pt-4 border-t border-gray-200">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all cursor-pointer">
                    <i class="fas fa-save mr-2"></i>Editor Rolü Yetkilerini Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function selectAll(rol) {
    var form = document.querySelector('form input[name="rol"][value="' + rol + '"]').closest('form');
    var checkboxes = form.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = true;
    });
}

function deselectAll(rol) {
    var form = document.querySelector('form input[name="rol"][value="' + rol + '"]').closest('form');
    var checkboxes = form.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = false;
    });
}
</script>