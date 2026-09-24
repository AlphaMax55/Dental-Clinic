<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sadece superadmin ve admin görebilir
if (!yetkiKontrol('admin')) {
    die('Bu sayfaya erişim yetkiniz yok.');
}

// ============= SAYFA VE ID =============
$sayfa = $_GET['sayfa'] ?? 'liste';
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

// ============= TÜM MODÜLLER VE YETKİLER =============
$moduller = [
    'yorumlar' => ['icon' => 'fa-star', 'title' => 'Yorumlar'],
    'tedaviler' => ['icon' => 'fa-tooth', 'title' => 'Tedaviler'],
    'blog' => ['icon' => 'fa-blog', 'title' => 'Blog'],
    'galeri' => ['icon' => 'fa-images', 'title' => 'Galeri'],
    'randevular' => ['icon' => 'fa-calendar-check', 'title' => 'Randevular'],
    'mesajlar' => ['icon' => 'fa-envelope', 'title' => 'Mesajlar'],
    'sayfalar' => ['icon' => 'fa-file', 'title' => 'Sayfalar'],
    'ayarlar' => ['icon' => 'fa-cog', 'title' => 'Ayarlar'],
    'seo' => ['icon' => 'fa-chart-line', 'title' => 'SEO'],
    'raporlar' => ['icon' => 'fa-chart-pie', 'title' => 'Raporlar'],
    'kullanicilar' => ['icon' => 'fa-users', 'title' => 'Kullanıcılar'],
];

$yetkiler = ['gorebilir', 'ekleyebilir', 'duzenleyebilir', 'silebilir', 'onaylayabilir'];
$yetki_adlari = [
    'gorebilir' => '👁️ Görebilir', 
    'ekleyebilir' => '➕ Ekleyebilir', 
    'duzenleyebilir' => '✏️ Düzenleyebilir', 
    'silebilir' => '🗑️ Silebilir', 
    'onaylayabilir' => '✅ Onaylayabilir'
];

// ============= ROL YETKİLERİNİ KAYDET =============
if (isset($_POST['rol_yetkileri'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['mesaj'] = 'Güvenlik hatası!';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=kullanicilar');
        exit;
    }
    
    $rol = $_POST['rol'];
    $db->prepare("DELETE FROM rol_yetkileri WHERE rol = ?")->execute([$rol]);
    
    foreach ($moduller as $modul => $m) {
        foreach ($yetkiler as $yetki) {
            if (isset($_POST[$modul . '_' . $yetki])) {
                $stmt = $db->prepare("INSERT INTO rol_yetkileri (rol, modul, yetki, deger) VALUES (?, ?, ?, 1)");
                $stmt->execute([$rol, $modul, $yetki]);
            }
        }
    }
    $_SESSION['mesaj'] = $rol . ' rolü yetkileri güncellendi!';
    $_SESSION['mesaj_tip'] = 'success';
    header('Location: ?modul=kullanicilar');
    exit;
}

// ============= ÖZEL YETKİLERİ KAYDET =============
if (isset($_POST['ozel_yetkiler'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['mesaj'] = 'Güvenlik hatası!';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=kullanicilar');
        exit;
    }
    
    $kullanici_id = $_POST['kullanici_id'];
    $db->prepare("DELETE FROM yetkiler WHERE kullanici_id = ?")->execute([$kullanici_id]);
    
    foreach ($moduller as $modul => $m) {
        foreach ($yetkiler as $yetki) {
            if (isset($_POST[$modul . '_' . $yetki])) {
                $stmt = $db->prepare("INSERT INTO yetkiler (kullanici_id, modul, yetki, deger) VALUES (?, ?, ?, 1)");
                $stmt->execute([$kullanici_id, $modul, $yetki]);
            }
        }
    }
    $_SESSION['mesaj'] = 'Özel yetkiler güncellendi!';
    $_SESSION['mesaj_tip'] = 'success';
    header('Location: ?modul=kullanicilar');
    exit;
}

// ============= KULLANICI EKLE =============
if ($sayfa === 'ekle' && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['rol_yetkileri'])) {
    if (!yetkiKontrol('superadmin')) {
        $_SESSION['mesaj'] = 'Yetkiniz yok!';
        $_SESSION['mesaj_tip'] = 'error';
        header('Location: ?modul=kullanicilar');
        exit;
    }
    
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';
    $rol = $_POST['rol'] ?? 'editor';
    $durum = $_POST['durum'] ?? 1;
    
    if (empty($kullanici_adi) || empty($ad_soyad) || empty($email) || empty($sifre)) {
        $_SESSION['mesaj'] = 'Tüm alanları doldurun!';
        $_SESSION['mesaj_tip'] = 'error';
    } elseif ($sifre !== $sifre_tekrar) {
        $_SESSION['mesaj'] = 'Şifreler eşleşmiyor!';
        $_SESSION['mesaj_tip'] = 'error';
    } elseif (strlen($sifre) < 6) {
        $_SESSION['mesaj'] = 'Şifre en az 6 karakter!';
        $_SESSION['mesaj_tip'] = 'error';
    } else {
        $kontrol = $db->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR email = ?");
        $kontrol->execute([$kullanici_adi, $email]);
        if (!$kontrol->fetch()) {
            $stmt = $db->prepare("INSERT INTO kullanicilar (kullanici_adi, ad_soyad, email, sifre, rol, durum) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kullanici_adi, $ad_soyad, $email, password_hash($sifre, PASSWORD_DEFAULT), $rol, $durum]);
            $_SESSION['mesaj'] = 'Kullanıcı eklendi!';
            $_SESSION['mesaj_tip'] = 'success';
            header('Location: ?modul=kullanicilar');
            exit;
        }
        $_SESSION['mesaj'] = 'Kullanıcı adı veya e-posta kayıtlı!';
        $_SESSION['mesaj_tip'] = 'error';
    }
    header('Location: ?modul=kullanicilar&sayfa=ekle');
    exit;
}

// ============= KULLANICI DÜZENLE =============
if ($sayfa === 'duzenle' && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['rol_yetkileri']) && !isset($_POST['ozel_yetkiler'])) {
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? 'editor';
    $durum = $_POST['durum'] ?? 1;
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';
    
    if (empty($ad_soyad) || empty($email)) {
        $_SESSION['mesaj'] = 'Ad Soyad ve E-posta zorunlu!';
        $_SESSION['mesaj_tip'] = 'error';
    } elseif (!empty($sifre) && ($sifre !== $sifre_tekrar || strlen($sifre) < 6)) {
        $_SESSION['mesaj'] = 'Şifre en az 6 karakter ve eşleşmeli!';
        $_SESSION['mesaj_tip'] = 'error';
    } else {
        $sql = "UPDATE kullanicilar SET ad_soyad = ?, email = ?, rol = ?, durum = ?";
        $params = [$ad_soyad, $email, $rol, $durum];
        if (!empty($sifre)) {
            $sql .= ", sifre = ?";
            $params[] = password_hash($sifre, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $db->prepare($sql)->execute($params);
        $_SESSION['mesaj'] = 'Kullanıcı güncellendi!';
        $_SESSION['mesaj_tip'] = 'success';
        header('Location: ?modul=kullanicilar');
        exit;
    }
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=kullanicilar&sayfa=duzenle&id=' . $id);
    exit;
}

// ============= KULLANICI SİL =============
if ($sayfa === 'sil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (yetkiKontrol('superadmin') && $id != $_SESSION['admin_id']) {
        $kontrol = $db->prepare("SELECT rol FROM kullanicilar WHERE id = ?");
        $kontrol->execute([$id]);
        $kullanici = $kontrol->fetch();
        if ($kullanici && $kullanici['rol'] != 'superadmin') {
            $db->prepare("DELETE FROM kullanicilar WHERE id = ?")->execute([$id]);
            $_SESSION['mesaj'] = 'Kullanıcı silindi!';
            $_SESSION['mesaj_tip'] = 'success';
        } else {
            $_SESSION['mesaj'] = 'Süper Admin silinemez!';
            $_SESSION['mesaj_tip'] = 'error';
        }
    }
    header('Location: ?modul=kullanicilar');
    exit;
}

// ============= VERİLERİ ÇEK =============
$kullanicilar = $db->query("SELECT * FROM kullanicilar ORDER BY CASE rol WHEN 'superadmin' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END, id DESC")->fetchAll();
$ben = $_SESSION['admin_id'];
$ben_rol = $_SESSION['admin_rol'];

$toplam = count($kullanicilar);
$aktif = $db->query("SELECT COUNT(*) FROM kullanicilar WHERE durum = 1")->fetchColumn();
$superadmin = $db->query("SELECT COUNT(*) FROM kullanicilar WHERE rol = 'superadmin'")->fetchColumn();
$admin = $db->query("SELECT COUNT(*) FROM kullanicilar WHERE rol = 'admin'")->fetchColumn();
$editor = $db->query("SELECT COUNT(*) FROM kullanicilar WHERE rol = 'editor'")->fetchColumn();

// Mevcut rol yetkilerini çek
$mevcut_rol_yetkileri = [];
$stmt = $db->query("SELECT rol, modul, yetki FROM rol_yetkileri WHERE deger = 1");
while ($row = $stmt->fetch()) {
    $mevcut_rol_yetkileri[$row['rol']][$row['modul']][$row['yetki']] = true;
}

// Özel yetkili kullanıcıları çek
$ozel_yetkili_kullanicilar = [];
foreach ($kullanicilar as $k) {
    if ($k['id'] == 1) continue;
    $stmt = $db->prepare("SELECT modul, yetki FROM yetkiler WHERE kullanici_id = ? AND deger = 1");
    $stmt->execute([$k['id']]);
    $ozel = $stmt->fetchAll();
    if (!empty($ozel)) {
        $grouped = [];
        foreach ($ozel as $y) { $grouped[$y['modul']][] = $y['yetki']; }
        $ozel_yetkili_kullanicilar[] = [
            'id' => $k['id'], 
            'ad' => $k['ad_soyad'], 
            'kadi' => $k['kullanici_adi'], 
            'rol' => $k['rol'], 
            'yetkiler' => $grouped
        ];
    }
}

// Düzenlenecek kullanıcı
$duzenlenecek = null;
if ($sayfa === 'duzenle' && $id) {
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
    $stmt->execute([$id]);
    $duzenlenecek = $stmt->fetch();
}

// Özel yetki verilecek kullanıcı
$ozel_kullanici = null;
if ($sayfa === 'yetkiler' && $id) {
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
    $stmt->execute([$id]);
    $ozel_kullanici = $stmt->fetch();
    
    $mevcut_ozel = [];
    $stmt = $db->prepare("SELECT modul, yetki FROM yetkiler WHERE kullanici_id = ? AND deger = 1");
    $stmt->execute([$id]);
    while ($row = $stmt->fetch()) { $mevcut_ozel[$row['modul']][$row['yetki']] = true; }
}

// Mesaj
$mesaj = $_SESSION['mesaj'] ?? '';
$mesaj_tip = $_SESSION['mesaj_tip'] ?? '';
unset($_SESSION['mesaj'], $_SESSION['mesaj_tip']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kullanıcı Yönetimi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes slide-in-right { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .animate-slide-in-right { animation: slide-in-right 0.3s ease-out; }
    </style>
</head>
<body class="bg-slate-100">

<div class="space-y-6">

    <!-- MESAJ -->
    <?php if($mesaj): ?>
    <div class="fixed top-5 right-5 z-50 animate-slide-in-right">
        <div class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-2xl backdrop-blur-md <?php echo $mesaj_tip == 'success' ? 'bg-green-500/90 text-white' : 'bg-red-500/90 text-white'; ?>">
            <i class="fas <?php echo $mesaj_tip == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span class="font-medium"><?php echo htmlspecialchars($mesaj); ?></span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/70 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- BAŞLIK -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl p-6 shadow-xl mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-users-cog text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Kullanıcı Yönetimi</h1>
                    <p class="text-slate-400 text-sm">Hoş geldin, <?php echo $_SESSION['admin_adi'] ?? 'Admin'; ?></p>
                </div>
            </div>
            <div class="flex gap-3">
                <?php if($sayfa !== 'ekle' && $sayfa !== 'duzenle' && $sayfa !== 'yetkiler' && $sayfa !== 'roller'): ?>
                <a href="?modul=kullanicilar&sayfa=ekle" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition shadow-lg flex items-center gap-2">
                    <i class="fas fa-user-plus"></i> Yeni Kullanıcı
                </a>
                <?php endif; ?>
                <a href="?modul=kullanicilar&sayfa=roller" class="px-5 py-2.5 bg-purple-600 text-white text-sm font-bold rounded-xl hover:bg-purple-700 transition shadow-lg flex items-center gap-2">
                    <i class="fas fa-users-cog"></i> Rol Yetkileri
                </a>
                <button onclick="location.reload()" class="h-11 w-11 bg-slate-700 text-slate-300 rounded-xl hover:bg-blue-600 hover:text-white transition shadow-lg">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <?php if($sayfa === 'ekle'): ?>
    <!-- ============= KULLANICI EKLE ============= -->
    <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="h-1 bg-emerald-500"></div>
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-5">Yeni Kullanıcı Ekle</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div><input type="text" name="kullanici_adi" placeholder="Kullanıcı Adı" required class="w-full px-4 py-3 border rounded-xl focus:border-emerald-500 focus:outline-none"></div>
                <div><input type="text" name="ad_soyad" placeholder="Ad Soyad" required class="w-full px-4 py-3 border rounded-xl focus:border-emerald-500 focus:outline-none"></div>
                <div><input type="email" name="email" placeholder="E-posta" required class="w-full px-4 py-3 border rounded-xl focus:border-emerald-500 focus:outline-none"></div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="password" name="sifre" placeholder="Şifre" required class="px-4 py-3 border rounded-xl focus:border-emerald-500 focus:outline-none">
                    <input type="password" name="sifre_tekrar" placeholder="Şifre Tekrar" required class="px-4 py-3 border rounded-xl focus:border-emerald-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <select name="rol" class="px-4 py-3 border rounded-xl">
                        <option value="editor">✏️ Editör</option>
                        <option value="admin">🛡️ Admin</option>
                        <?php if($ben_rol == 'superadmin'): ?><option value="superadmin">👑 Süper Admin</option><?php endif; ?>
                    </select>
                    <select name="durum" class="px-4 py-3 border rounded-xl">
                        <option value="1">🟢 Aktif</option>
                        <option value="0">🔴 Pasif</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-3">
                    <button type="submit" class="flex-1 py-3 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-600 transition">Kullanıcı Ekle</button>
                    <a href="?modul=kullanicilar" class="flex-1 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition text-center">İptal</a>
                </div>
            </form>
        </div>
    </div>

    <?php elseif($sayfa === 'duzenle' && $duzenlenecek): ?>
    <!-- ============= KULLANICI DÜZENLE ============= -->
    <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="h-1 bg-blue-500"></div>
        <div class="p-6">
            <div class="flex items-center gap-3 mb-5">
                <a href="?modul=kullanicilar" class="text-gray-500"><i class="fas fa-arrow-left"></i></a>
                <h2 class="text-xl font-bold text-gray-800">Kullanıcı Düzenle: <?php echo htmlspecialchars($duzenlenecek['kullanici_adi']); ?></h2>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div><input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($duzenlenecek['ad_soyad']); ?>" required class="w-full px-4 py-3 border rounded-xl focus:border-blue-500 focus:outline-none"></div>
                <div><input type="email" name="email" value="<?php echo htmlspecialchars($duzenlenecek['email']); ?>" required class="w-full px-4 py-3 border rounded-xl focus:border-blue-500 focus:outline-none"></div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="password" name="sifre" placeholder="Yeni şifre (boş bırakırsan değişmez)" class="px-4 py-3 border rounded-xl">
                    <input type="password" name="sifre_tekrar" placeholder="Şifre tekrar" class="px-4 py-3 border rounded-xl">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <select name="rol" <?php echo $ben_rol != 'superadmin' ? 'disabled' : ''; ?> class="px-4 py-3 border rounded-xl <?php echo $ben_rol != 'superadmin' ? 'bg-gray-100' : ''; ?>">
                        <option value="editor" <?php echo $duzenlenecek['rol'] == 'editor' ? 'selected' : ''; ?>>✏️ Editör</option>
                        <option value="admin" <?php echo $duzenlenecek['rol'] == 'admin' ? 'selected' : ''; ?>>🛡️ Admin</option>
                        <?php if($ben_rol == 'superadmin'): ?><option value="superadmin" <?php echo $duzenlenecek['rol'] == 'superadmin' ? 'selected' : ''; ?>>👑 Süper Admin</option><?php endif; ?>
                    </select>
                    <select name="durum" class="px-4 py-3 border rounded-xl">
                        <option value="1" <?php echo $duzenlenecek['durum'] == 1 ? 'selected' : ''; ?>>🟢 Aktif</option>
                        <option value="0" <?php echo $duzenlenecek['durum'] == 0 ? 'selected' : ''; ?>>🔴 Pasif</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-3">
                    <button type="submit" class="flex-1 py-3 bg-blue-500 text-white font-bold rounded-xl hover:bg-blue-600 transition">Kaydet</button>
                    <a href="?modul=kullanicilar" class="flex-1 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition text-center">İptal</a>
                </div>
            </form>
        </div>
    </div>

    <?php elseif($sayfa === 'yetkiler' && $ozel_kullanici): ?>
    <!-- ============= ÖZEL YETKİLER ============= -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="h-1 bg-purple-500"></div>
        <div class="p-6">
            <div class="flex items-center gap-3 mb-5">
                <a href="?modul=kullanicilar" class="text-gray-500"><i class="fas fa-arrow-left"></i></a>
                <h2 class="text-xl font-bold text-gray-800">Özel Yetkiler: <?php echo htmlspecialchars($ozel_kullanici['ad_soyad']); ?></h2>
                <span class="text-sm text-gray-500">(@<?php echo htmlspecialchars($ozel_kullanici['kullanici_adi']); ?>)</span>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="kullanici_id" value="<?php echo $id; ?>">
                <input type="hidden" name="ozel_yetkiler" value="1">
                <div class="space-y-4 max-h-[500px] overflow-y-auto">
                    <?php foreach($moduller as $modul => $m): ?>
                    <div class="border rounded-lg p-3">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas <?php echo $m['icon']; ?> text-purple-500"></i>
                            <span class="font-bold text-gray-700"><?php echo $m['title']; ?></span>
                        </div>
                        <div class="grid grid-cols-5 gap-2 ml-6">
                            <?php foreach($yetkiler as $yetki): ?>
                            <label class="flex items-center gap-1 text-sm cursor-pointer">
                                <input type="checkbox" name="<?php echo $modul . '_' . $yetki; ?>" value="1" <?php echo isset($mevcut_ozel[$modul][$yetki]) ? 'checked' : ''; ?> class="w-4 h-4">
                                <span class="text-xs"><?php echo $yetki_adlari[$yetki]; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap-3 mt-6 pt-4 border-t">
                    <button type="submit" class="px-6 py-2 bg-purple-500 text-white font-bold rounded-xl hover:bg-purple-600 transition">Özel Yetkileri Kaydet</button>
                    <a href="?modul=kullanicilar" class="px-6 py-2 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition">İptal</a>
                </div>
            </form>
        </div>
    </div>

    <?php elseif($sayfa === 'roller'): ?>
    <!-- ============= ROL BAZLI YETKİLER ============= -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="h-1 bg-indigo-500"></div>
        <div class="p-6">
            <div class="flex items-center gap-3 mb-5">
                <a href="?modul=kullanicilar" class="text-gray-500"><i class="fas fa-arrow-left"></i></a>
                <h2 class="text-xl font-bold text-gray-800">Rol Bazlı Varsayılan Yetkiler</h2>
            </div>
            
            <!-- Admin Rolü -->
            <div class="mb-8 border rounded-xl overflow-hidden">
                <div class="bg-blue-50 px-4 py-3 font-bold text-blue-700">🛡️ Admin Rolü Varsayılan Yetkiler</div>
                <form method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="rol" value="admin">
                    <input type="hidden" name="rol_yetkileri" value="1">
                    <div class="space-y-3 max-h-[400px] overflow-y-auto">
                        <?php foreach($moduller as $modul => $m): ?>
                        <div class="border-b pb-2">
                            <div class="flex items-center gap-2 mb-1"><i class="fas <?php echo $m['icon']; ?> text-blue-500"></i><span class="font-medium"><?php echo $m['title']; ?></span></div>
                            <div class="grid grid-cols-5 gap-2 ml-6">
                                <?php foreach($yetkiler as $yetki): ?>
                                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="<?php echo $modul . '_' . $yetki; ?>" value="1" <?php echo isset($mevcut_rol_yetkileri['admin'][$modul][$yetki]) ? 'checked' : ''; ?>> <?php echo $yetki_adlari[$yetki]; ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition">Admin Yetkilerini Kaydet</button>
                </form>
            </div>
            
            <!-- Editor Rolü -->
            <div class="border rounded-xl overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 font-bold text-gray-700">✏️ Editor Rolü Varsayılan Yetkiler</div>
                <form method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="rol" value="editor">
                    <input type="hidden" name="rol_yetkileri" value="1">
                    <div class="space-y-3 max-h-[400px] overflow-y-auto">
                        <?php foreach($moduller as $modul => $m): ?>
                        <div class="border-b pb-2">
                            <div class="flex items-center gap-2 mb-1"><i class="fas <?php echo $m['icon']; ?> text-gray-500"></i><span class="font-medium"><?php echo $m['title']; ?></span></div>
                            <div class="grid grid-cols-5 gap-2 ml-6">
                                <?php foreach($yetkiler as $yetki): ?>
                                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="<?php echo $modul . '_' . $yetki; ?>" value="1" <?php echo isset($mevcut_rol_yetkileri['editor'][$modul][$yetki]) ? 'checked' : ''; ?>> <?php echo $yetki_adlari[$yetki]; ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition">Editor Yetkilerini Kaydet</button>
                </form>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ============= ANA SAYFA - KULLANICI LİSTESİ VE İSTATİSTİKLER ============= -->
    
    <!-- İSTATİSTİK KARTLARI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition">
            <div class="flex justify-between items-center"><div><p class="text-blue-100 text-xs">Toplam</p><p class="text-3xl font-black text-white"><?php echo $toplam; ?></p></div><div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i class="fas fa-users text-white"></i></div></div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition">
            <div class="flex justify-between items-center"><div><p class="text-green-100 text-xs">Aktif</p><p class="text-3xl font-black text-white"><?php echo $aktif; ?></p></div><div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i class="fas fa-check-circle text-white"></i></div></div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition">
            <div class="flex justify-between items-center"><div><p class="text-purple-100 text-xs">Süper Admin</p><p class="text-3xl font-black text-white"><?php echo $superadmin; ?></p></div><div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i class="fas fa-crown text-white"></i></div></div>
        </div>
        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition">
            <div class="flex justify-between items-center"><div><p class="text-cyan-100 text-xs">Admin</p><p class="text-3xl font-black text-white"><?php echo $admin; ?></p></div><div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i class="fas fa-shield-alt text-white"></i></div></div>
        </div>
        <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl p-4 shadow-lg hover:shadow-xl transition">
            <div class="flex justify-between items-center"><div><p class="text-gray-100 text-xs">Editör</p><p class="text-3xl font-black text-white"><?php echo $editor; ?></p></div><div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i class="fas fa-pen-alt text-white"></i></div></div>
        </div>
    </div>

    <!-- KULLANICI TABLOSU -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3"><i class="fas fa-table text-white"></i><h3 class="text-lg font-bold text-white">Kayıtlı Kullanıcılar</h3><span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded-full"><?php echo count($kullanicilar); ?> kayıt</span></div>
                <div class="relative"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i><input type="text" id="aramaInput" placeholder="Kullanıcı ara..." class="pl-8 pr-3 py-2 bg-gray-700 border-0 rounded-xl text-white placeholder:text-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64"></div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr><th class="px-6 py-3 text-left text-xs font-bold text-gray-500">ID</th><th class="px-6 py-3 text-left text-xs font-bold text-gray-500">Kullanıcı</th><th class="px-6 py-3 text-left text-xs font-bold text-gray-500">Ad Soyad</th><th class="px-6 py-3 text-left text-xs font-bold text-gray-500">E-posta</th><th class="px-6 py-3 text-center text-xs font-bold text-gray-500">Rol</th><th class="px-6 py-3 text-center text-xs font-bold text-gray-500">Durum</th><th class="px-6 py-3 text-center text-xs font-bold text-gray-500">Son Giriş</th><th class="px-6 py-3 text-center text-xs font-bold text-gray-500">İşlemler</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="kullaniciTbody">
                    <?php foreach($kullanicilar as $k): $isBen = ($k['id'] == $ben); ?>
                    <tr class="hover:bg-gray-50 transition <?php echo $isBen ? 'bg-blue-50' : ''; ?>">
                        <td class="px-6 py-4 font-mono text-sm font-bold">#<?php echo $k['id']; ?></td>
                        <td class="px-6 py-4"><div class="flex items-center gap-3"><div class="w-9 h-9 rounded-full bg-gradient-to-br <?php echo $k['rol'] == 'superadmin' ? 'from-purple-500 to-purple-600' : ($k['rol'] == 'admin' ? 'from-blue-500 to-blue-600' : 'from-gray-500 to-gray-600'); ?> flex items-center justify-center text-white font-bold"><?php echo strtoupper(substr($k['kullanici_adi'], 0, 1)); ?></div><div><p class="font-semibold text-gray-800"><?php echo htmlspecialchars($k['kullanici_adi']); ?></p><?php if($isBen): ?><span class="text-[10px] text-blue-600">(Siz)</span><?php endif; ?></div></div></td>
                        <td class="px-6 py-4 font-medium text-gray-700"><?php echo htmlspecialchars($k['ad_soyad']); ?></td>
                        <td class="px-6 py-4 text-gray-500 text-sm"><?php echo htmlspecialchars($k['email']); ?></td>
                        <td class="px-6 py-4 text-center"><span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold <?php echo $k['rol'] == 'superadmin' ? 'bg-purple-100 text-purple-700' : ($k['rol'] == 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'); ?>"><?php echo $k['rol'] == 'superadmin' ? '👑 Süper Admin' : ($k['rol'] == 'admin' ? '🛡️ Admin' : '✏️ Editor'); ?></span></td>
                        <td class="px-6 py-4 text-center"><?php echo $k['durum'] == 1 ? '<span class="text-green-600 text-xs font-medium">🟢 Aktif</span>' : '<span class="text-red-500 text-xs font-medium">🔴 Pasif</span>'; ?></td>
                        <td class="px-6 py-4 text-center text-sm text-gray-500"><?php echo $k['son_giris'] ? date('d.m.Y H:i', strtotime($k['son_giris'])) : '<span class="text-gray-400 text-xs"><i class="fas fa-clock mr-1"></i>Henüz yok</span>'; ?></td>
                        <td class="px-6 py-4 text-center"><div class="flex items-center justify-center gap-2">
                            <?php if($ben_rol == 'superadmin'): ?>
                            <a href="?modul=kullanicilar&sayfa=yetkiler&id=<?php echo $k['id']; ?>" class="px-3 py-1.5 bg-purple-500 text-white text-xs font-semibold rounded-lg hover:bg-purple-600 transition"><i class="fas fa-key mr-1"></i>Yetkiler</a>
                            <?php endif; ?>
                            <?php if(yetkiKontrol('admin') || $isBen): ?>
                            <form action="?modul=kullanicilar&sayfa=duzenle" method="POST" style="display:inline"><input type="hidden" name="id" value="<?php echo $k['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"><button type="submit" class="px-3 py-1.5 bg-blue-500 text-white text-xs font-semibold rounded-lg hover:bg-blue-600 transition"><i class="fas fa-edit mr-1"></i>Düzenle</button></form>
                            <?php endif; ?>
                            <?php if(yetkiKontrol('superadmin') && !$isBen && $k['rol'] != 'superadmin'): ?>
                            <form action="?modul=kullanicilar&sayfa=sil" method="POST" style="display:inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğine emin misin?')"><input type="hidden" name="id" value="<?php echo $k['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"><button type="submit" class="px-3 py-1.5 bg-red-500 text-white text-xs font-semibold rounded-lg hover:bg-red-600 transition"><i class="fas fa-trash-alt mr-1"></i>Sil</button></form>
                            <?php endif; ?>
                        </div></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if(empty($kullanicilar)): ?>
        <div class="text-center py-12"><i class="fas fa-user-slash text-gray-300 text-4xl mb-2 block"></i><p class="text-gray-400">Henüz kullanıcı yok</p></div>
        <?php endif; ?>
    </div>

    <!-- YETKİ BİLGİLERİ -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-200 shadow-lg">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-shield-alt text-white text-lg"></i>
            </div>
            <h3 class="text-xl font-bold text-blue-800">Yetki Yönetimi</h3>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Sol: Rol Bazlı Yetkiler -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-users-cog text-purple-600 text-lg"></i>
                    <span class="font-bold text-gray-800 text-lg">🎯 Genel Yetkiler (Rol Bazlı)</span>
                </div>
                <div class="bg-white rounded-xl p-5 border border-blue-100 shadow-sm">
                    <?php
                    // Admin ve Editor yetkilerini modül bazlı grupla
                    $admin_yetkiler = $db->query("SELECT modul, yetki FROM rol_yetkileri WHERE rol = 'admin' AND deger = 1")->fetchAll();
                    $editor_yetkiler = $db->query("SELECT modul, yetki FROM rol_yetkileri WHERE rol = 'editor' AND deger = 1")->fetchAll();
                    
                    $admin_grouped = [];
                    foreach ($admin_yetkiler as $y) {
                        $admin_grouped[$y['modul']][] = $y['yetki'];
                    }
                    
                    $editor_grouped = [];
                    foreach ($editor_yetkiler as $y) {
                        $editor_grouped[$y['modul']][] = $y['yetki'];
                    }
                    
                    $modul_ikonlari = [
                        'yorumlar' => ['icon' => 'fa-star', 'color' => 'blue', 'title' => 'Yorumlar'],
                        'tedaviler' => ['icon' => 'fa-tooth', 'color' => 'green', 'title' => 'Tedaviler'],
                        'blog' => ['icon' => 'fa-blog', 'color' => 'purple', 'title' => 'Blog'],
                        'galeri' => ['icon' => 'fa-images', 'color' => 'pink', 'title' => 'Galeri'],
                        'randevular' => ['icon' => 'fa-calendar-check', 'color' => 'amber', 'title' => 'Randevular'],
                        'mesajlar' => ['icon' => 'fa-envelope', 'color' => 'teal', 'title' => 'Mesajlar'],
                        'sayfalar' => ['icon' => 'fa-file', 'color' => 'indigo', 'title' => 'Sayfalar'],
                        'ayarlar' => ['icon' => 'fa-cog', 'color' => 'gray', 'title' => 'Ayarlar']
                    ];
                    
                    $yetki_adlari = [
                        'gorebilir' => '👁️ Görebilir',
                        'ekleyebilir' => '➕ Ekleyebilir',
                        'duzenleyebilir' => '✏️ Düzenleyebilir',
                        'silebilir' => '🗑️ Silebilir',
                        'onaylayabilir' => '✅ Onaylayabilir',
                        'cevaplayabilir' => '💬 Cevaplayabilir'
                    ];
                    ?>
                    
                    <!-- Admin Rolü (Açılır) -->
                    <div class="mb-5">
                        <button onclick="toggleAccordion('adminAccordion')" 
                                class="w-full flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-white rounded-xl hover:bg-blue-100 transition-all border border-blue-100">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-md">
                                    <i class="fas fa-shield-alt text-sm"></i>
                                </div>
                                <span class="font-bold text-blue-700">🛡️ Admin Rolü</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full"><?php echo count($admin_yetkiler); ?> yetki</span>
                                <i id="adminIcon" class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                            </div>
                        </button>
                        <div id="adminAccordion" class="hidden mt-3">
                            <div class="grid grid-cols-1 gap-2">
                                <?php if(empty($admin_grouped)): ?>
                                <div class="text-center py-4 text-gray-400 text-sm">Henüz yetki tanımlanmamış</div>
                                <?php else: ?>
                                <?php foreach($admin_grouped as $modul => $yetkiler): ?>
                                <?php $m = $modul_ikonlari[$modul] ?? ['icon' => 'fa-cube', 'color' => 'gray', 'title' => $modul]; ?>
                                <div class="border border-<?php echo $m['color']; ?>-200 rounded-xl overflow-hidden bg-<?php echo $m['color']; ?>-50/30">
                                    <div class="px-3 py-2 border-b border-<?php echo $m['color']; ?>-200 flex items-center gap-2 bg-white/50">
                                        <i class="fas <?php echo $m['icon']; ?> text-<?php echo $m['color']; ?>-500 text-sm"></i>
                                        <span class="font-bold text-sm text-gray-700"><?php echo $m['title']; ?></span>
                                        <span class="ml-auto text-xs text-gray-400"><?php echo count($yetkiler); ?> yetki</span>
                                    </div>
                                    <div class="p-2 flex flex-wrap gap-1">
                                        <?php foreach($yetkiler as $y): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-white text-<?php echo $m['color']; ?>-600 text-xs rounded-lg border border-<?php echo $m['color']; ?>-200 shadow-sm">
                                            <?php echo $yetki_adlari[$y] ?? $y; ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Editor Rolü (Açılır) -->
                    <div class="mb-4">
                        <button onclick="toggleAccordion('editorAccordion')" 
                                class="w-full flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-white rounded-xl hover:bg-gray-100 transition-all border border-gray-200">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gray-600 rounded-lg flex items-center justify-center text-white shadow-md">
                                    <i class="fas fa-pen-alt text-sm"></i>
                                </div>
                                <span class="font-bold text-gray-700">✏️ Editor Rolü</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full"><?php echo count($editor_yetkiler); ?> yetki</span>
                                <i id="editorIcon" class="fas fa-chevron-down text-gray-600 transition-transform"></i>
                            </div>
                        </button>
                        <div id="editorAccordion" class="hidden mt-3">
                            <div class="grid grid-cols-1 gap-2">
                                <?php if(empty($editor_grouped)): ?>
                                <div class="text-center py-4 text-gray-400 text-sm">Henüz yetki tanımlanmamış</div>
                                <?php else: ?>
                                <?php foreach($editor_grouped as $modul => $yetkiler): ?>
                                <?php $m = $modul_ikonlari[$modul] ?? ['icon' => 'fa-cube', 'color' => 'gray', 'title' => $modul]; ?>
                                <div class="border border-<?php echo $m['color']; ?>-200 rounded-xl overflow-hidden bg-<?php echo $m['color']; ?>-50/30">
                                    <div class="px-3 py-2 border-b border-<?php echo $m['color']; ?>-200 flex items-center gap-2 bg-white/50">
                                        <i class="fas <?php echo $m['icon']; ?> text-<?php echo $m['color']; ?>-500 text-sm"></i>
                                        <span class="font-bold text-sm text-gray-700"><?php echo $m['title']; ?></span>
                                        <span class="ml-auto text-xs text-gray-400"><?php echo count($yetkiler); ?> yetki</span>
                                    </div>
                                    <div class="p-2 flex flex-wrap gap-1">
                                        <?php foreach($yetkiler as $y): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-white text-<?php echo $m['color']; ?>-600 text-xs rounded-lg border border-<?php echo $m['color']; ?>-200 shadow-sm">
                                            <?php echo $yetki_adlari[$y] ?? $y; ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-2 text-right">
                        <a href="?modul=kullanicilar&sayfa=roller" class="text-sm text-purple-600 hover:text-purple-800 flex items-center justify-end gap-1 font-medium">
                            <i class="fas fa-edit"></i> Rol Yetkilerini Düzenle →
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Sağ: Özel Yetkili Kullanıcılar (Açılırlı) -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-user-tag text-amber-600 text-lg"></i>
                    <span class="font-bold text-gray-800 text-lg">⭐ Özel Yetkili Kullanıcılar</span>
                </div>
                <div class="bg-white rounded-xl p-5 border border-amber-100 shadow-sm">
                    <?php
                    $kullanicilar_list = $db->query("SELECT id, kullanici_adi, ad_soyad, rol FROM kullanicilar WHERE id != 1 AND durum = 1")->fetchAll();
                    $ozel_yetkili_kullanicilar = [];
                    
                    foreach ($kullanicilar_list as $k) {
                        $ozel_yetkiler = $db->prepare("SELECT modul, yetki FROM yetkiler WHERE kullanici_id = ? AND deger = 1");
                        $ozel_yetkiler->execute([$k['id']]);
                        $ozel_list = $ozel_yetkiler->fetchAll();
                        
                        if (!empty($ozel_list)) {
                            $grouped = [];
                            foreach ($ozel_list as $y) {
                                $grouped[$y['modul']][] = $y['yetki'];
                            }
                            $ozel_yetkili_kullanicilar[] = [
                                'id' => $k['id'],
                                'ad' => $k['ad_soyad'],
                                'kadi' => $k['kullanici_adi'],
                                'rol' => $k['rol'],
                                'yetkiler_grouped' => $grouped
                            ];
                        }
                    }
                    
                    $modul_ikonlari_full = [
                        'yorumlar' => ['icon' => 'fa-star', 'color' => 'blue'],
                        'tedaviler' => ['icon' => 'fa-tooth', 'color' => 'green'],
                        'blog' => ['icon' => 'fa-blog', 'color' => 'purple'],
                        'galeri' => ['icon' => 'fa-images', 'color' => 'pink'],
                        'randevular' => ['icon' => 'fa-calendar-check', 'color' => 'amber'],
                        'mesajlar' => ['icon' => 'fa-envelope', 'color' => 'teal'],
                        'sayfalar' => ['icon' => 'fa-file', 'color' => 'indigo'],
                        'ayarlar' => ['icon' => 'fa-cog', 'color' => 'gray'],
                        'seo' => ['icon' => 'fa-chart-line', 'color' => 'green'],
                        'raporlar' => ['icon' => 'fa-chart-pie', 'color' => 'amber'],
                        'kullanicilar' => ['icon' => 'fa-users', 'color' => 'blue'],
                    ];
                    ?>
                    
                    <?php if(empty($ozel_yetkili_kullanicilar)): ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-user-check text-amber-500 text-2xl"></i>
                        </div>
                        <p class="text-sm text-gray-500 font-medium">Özel yetkili kullanıcı bulunmuyor.</p>
                        <p class="text-xs text-gray-400 mt-1">Her kullanıcının yanındaki 🔑 butonuna tıklayarak özel yetki verebilirsiniz.</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                        <?php foreach($ozel_yetkili_kullanicilar as $k): ?>
                        <div class="border border-amber-200 rounded-xl overflow-hidden bg-white shadow-sm hover:shadow-md transition-all">
                            <!-- Kullanıcı Başlığı (Açılır Buton) -->
                            <button onclick="toggleUserAccordion(<?php echo $k['id']; ?>)" 
                                    class="w-full bg-gradient-to-r from-amber-50 to-white px-4 py-3 border-b border-amber-200 flex items-center justify-between hover:bg-amber-100 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center text-white font-bold shadow-md">
                                        <?php echo strtoupper(substr($k['ad'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <span class="font-bold text-gray-800"><?php echo htmlspecialchars($k['ad']); ?></span>
                                        <span class="text-xs ml-2 <?php echo $k['rol'] == 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'; ?> px-2 py-0.5 rounded-full">
                                            <?php echo $k['rol'] == 'admin' ? '🛡️ Admin' : '✏️ Editor'; ?>
                                        </span>
                                        <div class="text-xs text-gray-400 mt-0.5">@<?php echo htmlspecialchars($k['kadi']); ?></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-medium">
                                        <?php echo array_sum(array_map('count', $k['yetkiler_grouped'])); ?> yetki
                                    </span>
                                    <i id="userIcon_<?php echo $k['id']; ?>" class="fas fa-chevron-down text-amber-600 transition-transform"></i>
                                </div>
                            </button>
                            
                            <!-- Özel Yetkiler (Açılır İçerik) -->
                            <div id="userAccordion_<?php echo $k['id']; ?>" class="hidden">
                                <div class="p-3 space-y-2">
                                    <?php foreach($k['yetkiler_grouped'] as $modul => $yetkiler): ?>
                                    <?php $m = $modul_ikonlari_full[$modul] ?? ['icon' => 'fa-cube', 'color' => 'gray']; ?>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <div class="bg-gray-50 px-3 py-1.5 border-b border-gray-200 flex items-center gap-2">
                                            <i class="fas <?php echo $m['icon']; ?> text-<?php echo $m['color']; ?>-500 text-xs"></i>
                                            <span class="font-bold text-xs text-gray-700 uppercase"><?php echo $modul; ?></span>
                                            <span class="ml-auto text-xs text-gray-400"><?php echo count($yetkiler); ?> yetki</span>
                                        </div>
                                        <div class="p-2 flex flex-wrap gap-1">
                                            <?php foreach($yetkiler as $y): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-<?php echo $m['color']; ?>-50 text-<?php echo $m['color']; ?>-600 text-xs rounded-md">
                                                <?php echo $yetki_adlari[$y] ?? $y; ?>
                                            </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Düzenle Butonu (Altta) -->
                                    <div class="mt-2 pt-1 text-right">
                                        <a href="?modul=kullanicilar&sayfa=yetkiler&id=<?php echo $k['id']; ?>" 
                                           class="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-800 px-3 py-1 rounded-lg hover:bg-amber-50 transition-all">
                                            <i class="fas fa-edit"></i>
                                            Yetkileri Düzenle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 pt-3 border-t border-gray-200 text-right">
                        <a href="?modul=kullanicilar" class="text-xs text-amber-600 hover:text-amber-800 flex items-center justify-end gap-1 font-medium">
                            <i class="fas fa-user-cog"></i> Tüm kullanıcıları gör →
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 pt-3 border-t border-blue-200 text-xs text-blue-600 flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            <strong>Yetki Hiyerarşisi:</strong> Özel yetkiler, rol bazlı yetkilerin üzerine eklenir. Özel yetkisi olan kullanıcılar sağ tarafta listelenir.
        </div>
    </div>

    
    <?php endif; ?>
</div>

<script>
document.getElementById('aramaInput')?.addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#kullaniciTbody tr');
    rows.forEach(row => { row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none'; });
});
</script>
<script>
function toggleAccordion(id) {
    var element = document.getElementById(id);
    var icon = document.getElementById(id === 'adminAccordion' ? 'adminIcon' : 'editorIcon');
    
    if (element.classList.contains('hidden')) {
        element.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        element.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function toggleUserAccordion(id) {
    var element = document.getElementById('userAccordion_' + id);
    var icon = document.getElementById('userIcon_' + id);
    
    if (element.classList.contains('hidden')) {
        element.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        element.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}
</script>

</body>
</html>