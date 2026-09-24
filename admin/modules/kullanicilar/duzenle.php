<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ID'yi POST'dan al
$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);

// Eğer POST'tan gelmediyse hata ver
if (!$id) {
    $_SESSION['mesaj'] = 'Geçersiz kullanıcı ID!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=kullanicilar');
    exit;
}

// CSRF token kontrolü
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Güvenlik hatası! Geçersiz token.');
}

$stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$stmt->execute([$id]);
$kullanici = $stmt->fetch();

if (!$kullanici) {
    $_SESSION['mesaj'] = 'Kullanıcı bulunamadı!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=kullanicilar');
    exit;
}

$ben = $_SESSION['admin_id'];
$ben_rol = $_SESSION['admin_rol'];

// Yetki kontrolü: Superadmin herkesi düzenleyebilir, admin sadece editor'leri
if ($ben_rol != 'superadmin') {
    if ($kullanici['rol'] == 'superadmin') {
        die('Süper Admin hesabını düzenleyemezsiniz!');
    }
    if ($ben_rol == 'admin' && $kullanici['rol'] == 'admin') {
        die('Admin hesabını düzenleyemezsiniz!');
    }
}

$hata = '';
$basarili = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guncelle'])) {
    // CSRF token kontrolü (güncelleme için)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Güvenlik hatası! Geçersiz token.');
    }
    
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? $kullanici['rol'];
    $durum = $_POST['durum'] ?? $kullanici['durum'];
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';

    if (empty($ad_soyad) || empty($email)) {
        $hata = 'Ad Soyad ve E-posta zorunludur!';
    } elseif (!empty($sifre) && $sifre !== $sifre_tekrar) {
        $hata = 'Şifreler eşleşmiyor!';
    } elseif (!empty($sifre) && strlen($sifre) < 6) {
        $hata = 'Şifre en az 6 karakter olmalı!';
    } else {
        $sql = "UPDATE kullanicilar SET ad_soyad = ?, email = ?, rol = ?, durum = ?";
        $params = [$ad_soyad, $email, $rol, $durum];
        
        if (!empty($sifre)) {
            $sql .= ", sifre = ?";
            $params[] = password_hash($sifre, PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $basarili = 'Kullanıcı başarıyla güncellendi!';
        
        // Kendi bilgilerini güncellediyse session'ı güncelle
        if ($id == $ben) {
            $_SESSION['admin_adi'] = $ad_soyad;
        }
        
        // Kullanıcı verilerini yenile
        $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
        $stmt->execute([$id]);
        $kullanici = $stmt->fetch();
    }
}

// Rol renkleri
$rol_renkleri = [
    'superadmin' => 'from-purple-500 to-indigo-600',
    'admin' => 'from-blue-500 to-cyan-600',
    'editor' => 'from-slate-400 to-slate-600'
];
$rol_ikonlari = [
    'superadmin' => 'fa-crown',
    'admin' => 'fa-shield-alt',
    'editor' => 'fa-pen-nib'
];
$rol_isimleri = [
    'superadmin' => 'Süper Admin',
    'admin' => 'Admin',
    'editor' => 'Editör'
];
?>

<div class="min-h-screen via-slate-800 to-slate-900 p-6">
    <!-- Başlık Bölümü -->
    <div class="max-w-3xl mx-auto mb-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-8 shadow-2xl">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
            <div class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-white/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-blue-500/30 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="?modul=kullanicilar" class="group flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-lg transition-all hover:bg-white/30 hover:scale-110 active:scale-95">
                        <i class="fas fa-arrow-left text-xl group-hover:-translate-x-1 transition-transform"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-3xl font-black text-white tracking-tight">Kullanıcı Düzenle</h1>
                            <span class="px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white text-xs font-bold border border-white/30">
                                ID: #<?php echo $kullanici['id']; ?>
                            </span>
                        </div>
                        <p class="text-blue-100 text-sm font-medium flex items-center gap-2">
                            <i class="fas fa-user-edit"></i>
                            <?php echo htmlspecialchars($kullanici['kullanici_adi']); ?> hesabını düzenliyorsunuz
                        </p>
                    </div>
                </div>
                
                <!-- Kullanıcı Avatarı -->
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/20">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br <?php echo $rol_renkleri[$kullanici['rol']]; ?> flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                        <?php echo strtoupper(substr($kullanici['kullanici_adi'], 0, 1)); ?>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-white font-bold text-sm"><?php echo htmlspecialchars($kullanici['ad_soyad']); ?></p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-bold">
                            <i class="fas <?php echo $rol_ikonlari[$kullanici['rol']]; ?>"></i>
                            <?php echo $rol_isimleri[$kullanici['rol']]; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Form Kartı -->
    <div class="max-w-3xl mx-auto">
        <div class="relative overflow-hidden rounded-3xl bg-white shadow-2xl border border-slate-200">
            <!-- Dekoratif üst çizgi -->
            <div class="h-2 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
            
            <div class="p-8 md:p-10">
                
                <!-- Hata Mesajı -->
                <?php if($hata): ?>
                <div class="mb-6 rounded-2xl bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 p-5 shadow-md animate-pulse">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 shadow-sm">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-red-800 text-lg">Hata Oluştu!</h4>
                            <p class="text-red-600 mt-1 font-medium"><?php echo htmlspecialchars($hata); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Başarı Mesajı -->
                <?php if($basarili): ?>
                <div class="mb-6 rounded-2xl bg-gradient-to-r from-emerald-50 to-green-50 border-l-4 border-emerald-500 p-5 shadow-md">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 shadow-sm">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-emerald-800 text-lg">Başarılı!</h4>
                            <p class="text-emerald-600 mt-1 font-medium"><?php echo htmlspecialchars($basarili); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $kullanici['id']; ?>">
                    <input type="hidden" name="guncelle" value="1">
                    
                    <!-- Kullanıcı Adı (Sadece görüntüleme) -->
                    <div class="group">
                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                            <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                <i class="fas fa-user-lock text-xs"></i>
                            </div>
                            Kullanıcı Adı <span class="text-xs text-slate-400 font-normal">(Değiştirilemez)</span>
                        </label>
                        <div class="relative">
                            <input type="text" value="<?php echo htmlspecialchars($kullanici['kullanici_adi']); ?>" disabled
                                   class="w-full rounded-xl border-2 border-slate-200 bg-slate-100 px-5 py-4 text-slate-500 font-medium cursor-not-allowed">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 flex items-center gap-1">
                            <i class="fas fa-info-circle text-slate-400"></i>
                            Kullanıcı adı sistem tarafından sabitlenmiştir
                        </p>
                    </div>

                    <!-- Ad Soyad -->
                    <div class="group">
                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                            <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                <i class="fas fa-id-card text-xs"></i>
                            </div>
                            Ad Soyad <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="ad_soyad" required 
                               value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>"
                               class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-purple-500 focus:bg-white focus:shadow-lg focus:shadow-purple-500/10"
                               placeholder="Ahmet Yılmaz">
                    </div>

                    <!-- E-posta -->
                    <div class="group">
                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                            <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                <i class="fas fa-envelope text-xs"></i>
                            </div>
                            E-posta Adresi <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="email" name="email" required 
                                   value="<?php echo htmlspecialchars($kullanici['email']); ?>"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:shadow-lg focus:shadow-emerald-500/10"
                                   placeholder="kullanici@site.com">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Şifre Alanları -->
                    <div class="rounded-2xl bg-amber-50 border-2 border-amber-200 p-5 space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500 text-white">
                                <i class="fas fa-key text-sm"></i>
                            </div>
                            <h4 class="font-bold text-amber-800">Şifre Güncelleme</h4>
                            <span class="text-xs text-amber-600 bg-amber-100 px-2 py-1 rounded-full">Opsiyonel</span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="group">
                                <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                    <i class="fas fa-lock text-rose-500"></i>
                                    Yeni Şifre
                                </label>
                                <div class="relative">
                                    <input type="password" name="sifre" 
                                           placeholder="Değiştirmek için doldurun"
                                           class="w-full rounded-xl border-2 border-slate-200 bg-white px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-rose-500 focus:shadow-lg focus:shadow-rose-500/10">
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                        <i class="fas fa-key"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="group">
                                <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                    <i class="fas fa-redo text-orange-500"></i>
                                    Şifre Tekrar
                                </label>
                                <div class="relative">
                                    <input type="password" name="sifre_tekrar" 
                                           placeholder="Yeni şifreyi tekrar girin"
                                           class="w-full rounded-xl border-2 border-slate-200 bg-white px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-orange-500 focus:shadow-lg focus:shadow-orange-500/10">
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-amber-700 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>
                            Boş bırakırsanız mevcut şifre değişmeyecektir. En az 6 karakter gerekir.
                        </p>
                    </div>

                    <!-- Rol ve Durum -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="group">
                            <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                    <i class="fas fa-crown text-xs"></i>
                                </div>
                                Kullanıcı Rolü
                            </label>
                            <div class="relative">
                                <select name="rol" 
                                        <?php echo ($ben_rol != 'superadmin') ? 'disabled' : ''; ?>
                                        class="w-full appearance-none rounded-xl border-2 border-slate-200 bg-slate-50 px-5 py-4 text-slate-800 outline-none transition-all focus:border-indigo-500 focus:bg-white focus:shadow-lg focus:shadow-indigo-500/10 cursor-pointer <?php echo ($ben_rol != 'superadmin') ? 'bg-slate-100 cursor-not-allowed opacity-70' : ''; ?>">
                                    <option value="editor" <?php echo $kullanici['rol'] == 'editor' ? 'selected' : ''; ?>>
                                        ✏️ Editör - İçerik Yönetimi
                                    </option>
                                    <option value="admin" <?php echo $kullanici['rol'] == 'admin' ? 'selected' : ''; ?>>
                                        🛡️ Admin - Tam Yetki
                                    </option>
                                    <option value="superadmin" <?php echo $kullanici['rol'] == 'superadmin' ? 'selected' : ''; ?>>
                                        👑 Süper Admin - Sistem Yöneticisi
                                    </option>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <?php if($ben_rol != 'superadmin'): ?>
                            <p class="mt-2 text-xs text-amber-600 flex items-center gap-1 bg-amber-50 p-2 rounded-lg border border-amber-200">
                                <i class="fas fa-lock text-amber-500"></i>
                                Sadece Süper Admin rol değiştirebilir
                            </p>
                            <?php else: ?>
                            <p class="mt-2 text-xs text-slate-500 flex items-center gap-1">
                                <i class="fas fa-shield-alt text-indigo-400"></i>
                                Rol değişikliği tüm yetkileri etkiler
                            </p>
                            <?php endif; ?>
                        </div>

                        <div class="group">
                            <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-green-100 text-green-600">
                                    <i class="fas fa-toggle-on text-xs"></i>
                                </div>
                                Hesap Durumu
                            </label>
                            <div class="relative">
                                <select name="durum" class="w-full appearance-none rounded-xl border-2 border-slate-200 bg-slate-50 px-5 py-4 text-slate-800 outline-none transition-all focus:border-green-500 focus:bg-white focus:shadow-lg focus:shadow-green-500/10 cursor-pointer">
                                    <option value="1" <?php echo $kullanici['durum'] == 1 ? 'selected' : ''; ?>>
                                        🟢 Aktif - Giriş yapabilir
                                    </option>
                                    <option value="0" <?php echo $kullanici['durum'] == 0 ? 'selected' : ''; ?>>
                                        🔴 Pasif - Giriş yapamaz
                                    </option>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500 flex items-center gap-1">
                                <i class="fas fa-info-circle text-green-400"></i>
                                Pasif kullanıcılar sisteme giriş yapamaz
                            </p>
                        </div>
                    </div>

                    <!-- Bilgi Kartı -->
                    <div class="rounded-2xl bg-blue-50 border-2 border-blue-200 p-4 flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                            <i class="fas fa-info-circle text-lg"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-blue-800 text-sm mb-1">Son Giriş Bilgisi</h5>
                            <p class="text-blue-600 text-xs">
                                <?php if($kullanici['son_giris']): ?>
                                <i class="fas fa-clock mr-1"></i>
                                Son giriş: <?php echo date('d.m.Y H:i:s', strtotime($kullanici['son_giris'])); ?>
                                <?php else: ?>
                                <i class="fas fa-minus-circle mr-1"></i>
                                Henüz giriş yapmadı
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t-2 border-slate-100">
                        <button type="submit" class="group relative flex-1 overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-4 font-bold text-white shadow-xl shadow-blue-500/30 transition-all hover:shadow-blue-500/50 hover:scale-[1.02] active:scale-95">
                            <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform group-hover:translate-x-full duration-700"></div>
                            <div class="relative flex items-center justify-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <span class="text-2xl font-black">D</span>
                                </div>
                                <div class="text-left">
                                    <span class="block text-lg">Değişiklikleri Kaydet</span>
                                    <span class="text-xs text-blue-100 font-medium">Güncellemeyi uygula</span>
                                </div>
                            </div>
                        </button>

                        <a href="?modul=kullanicilar" class="group flex items-center justify-center gap-3 rounded-2xl bg-slate-100 px-8 py-4 font-bold text-slate-600 shadow-lg transition-all hover:bg-slate-200 hover:text-slate-800 hover:scale-[1.02] active:scale-95 sm:w-auto">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-200 text-slate-500 group-hover:bg-slate-300 transition-colors">
                                <i class="fas fa-times text-lg"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-lg">İptal</span>
                                <span class="text-xs text-slate-400 font-medium">Listeye geri dön</span>
                            </div>
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Alt bilgi -->
            <div class="bg-slate-50 px-8 py-4 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <i class="fas fa-shield-alt text-blue-500"></i>
                    <span>Güvenli form - CSRF korumalı</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1">
                        <i class="fas fa-calendar text-slate-400"></i>
                        Kayıt: <?php echo date('d.m.Y', strtotime($kullanici['olusturma_tarihi'] ?? 'now')); ?>
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="fas fa-clock text-slate-400"></i>
                        <?php echo date('H:i'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Alt ipuçları -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-2xl bg-slate-800/50 backdrop-blur-md p-4 border border-slate-700 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-blue-500/20 flex items-center justify-center text-blue-400">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-200">Yetki Kontrolü</p>
                    <p class="text-xs text-slate-400">Sadece yetkili alanlar düzenlenebilir</p>
                </div>
            </div>
            <div class="rounded-2xl bg-slate-800/50 backdrop-blur-md p-4 border border-slate-700 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-purple-500/20 flex items-center justify-center text-purple-400">
                    <i class="fas fa-lock"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-200">Şifre Güvenliği</p>
                    <p class="text-xs text-slate-400">Boş bırakırsan değişmez</p>
                </div>
            </div>
            <div class="rounded-2xl bg-slate-800/50 backdrop-blur-md p-4 border border-slate-700 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-200">Anlık Güncelleme</p>
                    <p class="text-xs text-slate-400">Değişiklikler hemen aktif olur</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Input focus animasyonları */
input:focus, select:focus {
    transform: translateY(-1px);
}

/* Placeholder animasyonu */
input:focus::placeholder {
    opacity: 0;
    transform: translateX(10px);
    transition: all 0.3s ease;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-track {
    background: #1e293b;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb {
    background: #475569;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* Disabled input stil */
input:disabled {
    background-image: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 10px,
        rgba(0,0,0,0.02) 10px,
        rgba(0,0,0,0.02) 20px
    );
}
</style>

<script>
// Form validation için ekstra kontroller
document.querySelector('form').addEventListener('submit', function(e) {
    const sifre = document.querySelector('input[name="sifre"]').value;
    const sifreTekrar = document.querySelector('input[name="sifre_tekrar"]').value;
    
    if (sifre && sifre !== sifreTekrar) {
        e.preventDefault();
        alert('❌ Şifreler eşleşmiyor! Lütfen kontrol edin.');
        return false;
    }
    
    if (sifre && sifre.length < 6) {
        e.preventDefault();
        alert('❌ Şifre en az 6 karakter olmalıdır!');
        return false;
    }
    
    // Buton loading durumu
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Kaydediliyor...';
    submitBtn.disabled = true;
});
</script>