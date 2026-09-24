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

// Sadece superadmin ekleyebilir
if (!yetkiKontrol('superadmin')) {
    die('Bu sayfaya erişim yetkiniz yok. Sadece Süper Admin kullanıcı ekleyebilir.');
}

$hata = '';
$basarili = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Güvenlik hatası! Geçersiz token.');
    }
    
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';
    $rol = $_POST['rol'] ?? 'editor';
    $durum = $_POST['durum'] ?? 1;

    // Validasyon
    if (empty($kullanici_adi) || empty($ad_soyad) || empty($email) || empty($sifre)) {
        $hata = 'Tüm alanları doldurun!';
    } elseif ($sifre !== $sifre_tekrar) {
        $hata = 'Şifreler eşleşmiyor!';
    } elseif (strlen($sifre) < 6) {
        $hata = 'Şifre en az 6 karakter olmalı!';
    } else {
        // Kullanıcı var mı kontrol et
        $kontrol = $db->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR email = ?");
        $kontrol->execute([$kullanici_adi, $email]);
        
        if ($kontrol->fetch()) {
            $hata = 'Bu kullanıcı adı veya e-posta zaten kayıtlı!';
        } else {
            $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO kullanicilar (kullanici_adi, ad_soyad, email, sifre, rol, durum) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$kullanici_adi, $ad_soyad, $email, $sifre_hash, $rol, $durum]);
            
            $basarili = 'Kullanıcı başarıyla eklendi!';
            
            // Formu temizle
            $_POST = [];
        }
    }
}
?>

<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-slate-900 p-6">
    <!-- Başlık Bölümü -->
    <div class="max-w-3xl mx-auto mb-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 p-8 shadow-2xl">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
            <div class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-white/20 blur-3xl"></div>
            
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="?modul=kullanicilar" class="group flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 text-white shadow-lg transition-all hover:bg-white/30 hover:scale-110 active:scale-95">
                        <i class="fas fa-arrow-left text-xl group-hover:-translate-x-1 transition-transform"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight">Yeni Kullanıcı Ekle</h1>
                        <p class="text-emerald-100 text-sm font-medium mt-1">Sistem yöneticisi oluşturma formu</p>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-4xl shadow-lg border border-white/30">
                        <i class="fas fa-user-plus text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Form Kartı -->
    <div class="max-w-3xl mx-auto">
        <div class="relative overflow-hidden rounded-3xl bg-white shadow-2xl border border-slate-200">
            <!-- Dekoratif üst çizgi -->
            <div class="h-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500"></div>
            
            <div class="p-8 md:p-10">
                
                <!-- Hata Mesajı -->
                <?php if($hata): ?>
                <div class="mb-6 rounded-2xl bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 p-5 shadow-md animate-pulse">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 shadow-sm">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div>
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
                            <a href="?modul=kullanicilar" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-emerald-500/30 transition-all hover:bg-emerald-700 hover:scale-105 active:scale-95">
                                <i class="fas fa-list"></i>
                                Listeye Dön
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Kullanıcı Adı -->
                    <div class="group">
                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                            <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                <i class="fas fa-user text-xs"></i>
                            </div>
                            Kullanıcı Adı <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" name="kullanici_adi" required 
                                   value="<?php echo htmlspecialchars($_POST['kullanici_adi'] ?? ''); ?>"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:shadow-lg focus:shadow-blue-500/10"
                                   placeholder="ornek.kullanici">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="fas fa-at"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 flex items-center gap-1">
                            <i class="fas fa-info-circle text-blue-400"></i>
                            Sistemde benzersiz olmalıdır
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
                               value="<?php echo htmlspecialchars($_POST['ad_soyad'] ?? ''); ?>"
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
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:shadow-lg focus:shadow-emerald-500/10"
                                   placeholder="kullanici@site.com">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Şifre Alanları -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="group">
                            <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                                    <i class="fas fa-lock text-xs"></i>
                                </div>
                                Şifre <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" name="sifre" required 
                                       class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-rose-500 focus:bg-white focus:shadow-lg focus:shadow-rose-500/10"
                                       placeholder="••••••">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <i class="fas fa-key"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">En az 6 karakter olmalı</p>
                        </div>

                        <div class="group">
                            <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-orange-100 text-orange-600">
                                    <i class="fas fa-lock text-xs"></i>
                                </div>
                                Şifre Tekrar <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" name="sifre_tekrar" required 
                                       class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:shadow-lg focus:shadow-orange-500/10"
                                       placeholder="••••••">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <i class="fas fa-redo"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rol ve Durum -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="group">
                            <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                                    <i class="fas fa-crown text-xs"></i>
                                </div>
                                Kullanıcı Rolü
                            </label>
                            <div class="relative">
                                <select name="rol" class="w-full appearance-none rounded-xl border-2 border-slate-200 bg-slate-50 px-5 py-4 text-slate-800 outline-none transition-all focus:border-amber-500 focus:bg-white focus:shadow-lg focus:shadow-amber-500/10 cursor-pointer">
                                    <option value="editor" <?php echo ($_POST['rol'] ?? '') == 'editor' ? 'selected' : ''; ?>>
                                        ✏️ Editör - İçerik Yönetimi
                                    </option>
                                    <option value="admin" <?php echo ($_POST['rol'] ?? '') == 'admin' ? 'selected' : ''; ?>>
                                        🛡️ Admin - Tam Yetki
                                    </option>
                                    <option value="superadmin" <?php echo ($_POST['rol'] ?? '') == 'superadmin' ? 'selected' : ''; ?>>
                                        👑 Süper Admin - Sistem Yöneticisi
                                    </option>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500 flex items-center gap-1">
                                <i class="fas fa-shield-alt text-amber-400"></i>
                                Süper Admin tüm sistem yetkilerine sahiptir
                            </p>
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
                                    <option value="1" <?php echo ($_POST['durum'] ?? 1) == 1 ? 'selected' : ''; ?>>
                                        🟢 Aktif - Giriş yapabilir
                                    </option>
                                    <option value="0" <?php echo ($_POST['durum'] ?? '') == 0 ? 'selected' : ''; ?>>
                                        🔴 Pasif - Giriş yapamaz
                                    </option>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t-2 border-slate-100">
                        <button type="submit" class="group relative flex-1 overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 px-8 py-4 font-bold text-white shadow-xl shadow-emerald-500/30 transition-all hover:shadow-emerald-500/50 hover:scale-[1.02] active:scale-95">
                            <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform group-hover:translate-x-full duration-700"></div>
                            <div class="relative flex items-center justify-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <i class="fas fa-save text-lg"></i>
                                </div>
                                <div class="text-left">
                                    <span class="block text-lg">Kullanıcıyı Ekle</span>
                                    <span class="text-xs text-emerald-100 font-medium">Bilgileri kaydet ve oluştur</span>
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
                    <i class="fas fa-shield-alt text-emerald-500"></i>
                    <span>Güvenli form - CSRF korumalı</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-clock text-slate-400"></i>
                    <span><?php echo date('d.m.Y H:i'); ?></span>
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
                    <p class="text-sm font-bold text-slate-200">Güvenlik</p>
                    <p class="text-xs text-slate-400">Güçlü şifre kullanın</p>
                </div>
            </div>
            <div class="rounded-2xl bg-slate-800/50 backdrop-blur-md p-4 border border-slate-700 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-purple-500/20 flex items-center justify-center text-purple-400">
                    <i class="fas fa-envelope"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-200">Doğrulama</p>
                    <p class="text-xs text-slate-400">Geçerli e-posta adresi</p>
                </div>
            </div>
            <div class="rounded-2xl bg-slate-800/50 backdrop-blur-md p-4 border border-slate-700 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-400">
                    <i class="fas fa-crown"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-200">Roller</p>
                    <p class="text-xs text-slate-400">Yetkiye göre seçin</p>
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

/* Select option stilleri */
select option {
    padding: 10px;
    font-size: 14px;
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
</style>

<script>
// Form validation için ekstra kontroller
document.querySelector('form').addEventListener('submit', function(e) {
    const sifre = document.querySelector('input[name="sifre"]').value;
    const sifreTekrar = document.querySelector('input[name="sifre_tekrar"]').value;
    
    if (sifre !== sifreTekrar) {
        e.preventDefault();
        alert('❌ Şifreler eşleşmiyor! Lütfen kontrol edin.');
        return false;
    }
    
    if (sifre.length < 6) {
        e.preventDefault();
        alert('❌ Şifre en az 6 karakter olmalıdır!');
        return false;
    }
    
    // Buton loading durumu
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Oluşturuluyor...';
    submitBtn.disabled = true;
});
</script>