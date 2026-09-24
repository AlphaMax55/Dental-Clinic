<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
kontrol();

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// YETKİ KONTROLÜ
if (!yetkiVar('yorumlar', 'duzenleyebilir')) {
    yetkiYok('yorumlar', 'duzenleyebilir');
}

// ID'yi POST veya GET'ten al
$id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // POST ile gelirse (butondan)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Güvenlik hatası!');
    }
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
} else if (isset($_GET['id'])) {
    // GET ile gelirse (direkt URL'den) - yine de çalışsın ama daha az güvenli
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
}

if (!$id) {
    $_SESSION['mesaj'] = 'Geçersiz yorum ID!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=yorumlar');
    exit;
}

$stmt = $db->prepare("SELECT * FROM yorumlar WHERE id = ?");
$stmt->execute([$id]);
$yorum = $stmt->fetch();

if (!$yorum) {
    $_SESSION['mesaj'] = 'Yorum bulunamadı!';
    $_SESSION['mesaj_tip'] = 'error';
    header('Location: ?modul=yorumlar');
    exit;
}

// POST ile güncelleme (kaydetme)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guncelle'])) {
    // CSRF token kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Güvenlik hatası! Geçersiz token.');
    }
    
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tedavi = $_POST['tedavi'] ?? '';
    $yorum_metni = trim($_POST['yorum'] ?? '');
    $puan = filter_var($_POST['puan'] ?? 0, FILTER_VALIDATE_INT);
    
    if (empty($ad_soyad) || empty($yorum_metni)) {
        $_SESSION['mesaj'] = 'Ad Soyad ve Yorum alanları boş olamaz!';
        $_SESSION['mesaj_tip'] = 'error';
    } else {
        try {
            $update = $db->prepare("UPDATE yorumlar SET ad_soyad = ?, email = ?, tedavi = ?, yorum = ?, puan = ? WHERE id = ?");
            $update->execute([$ad_soyad, $email, $tedavi, $yorum_metni, $puan, $id]);
            
            $_SESSION['mesaj'] = 'Yorum başarıyla güncellendi.';
            $_SESSION['mesaj_tip'] = 'success';
            header('Location: ?modul=yorumlar');
            exit;
        } catch (Exception $e) {
            $_SESSION['mesaj'] = 'Güncelleme başarısız!';
            $_SESSION['mesaj_tip'] = 'error';
        }
    }
}

// Tarih alanını kontrol et - yoksa varsayılan değer kullan
$yorum_tarihi = isset($yorum['tarih']) ? $yorum['tarih'] : (isset($yorum['created_at']) ? $yorum['created_at'] : (isset($yorum['olusturma_tarihi']) ? $yorum['olusturma_tarihi'] : date('Y-m-d H:i:s')));

// Yıldız render fonksiyonu
function renderStars($puan) {
    $stars = '';
    for($i = 1; $i <= 5; $i++) {
        if($i <= $puan) {
            $stars .= '<i class="fas fa-star text-amber-400"></i>';
        } else {
            $stars .= '<i class="far fa-star text-slate-300"></i>';
        }
    }
    return $stars;
}
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
    <!-- Dekoratif arka plan elementleri -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute top-40 left-1/2 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative max-w-3xl mx-auto">
        <!-- Başlık Bölümü -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="?modul=yorumlar" class="group flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-lg border border-slate-200 text-slate-600 hover:text-blue-600 hover:border-blue-300 hover:shadow-xl transition-all hover:scale-110 active:scale-95">
                    <i class="fas fa-arrow-left text-lg group-hover:-translate-x-1 transition-transform"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight flex items-center gap-3">
                        <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Yorum Düzenle</span>
                        <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-bold border border-blue-200">#<?php echo $yorum['id']; ?></span>
                    </h1>
                    <p class="text-slate-500 text-sm mt-1 flex items-center gap-2">
                        <i class="fas fa-comment-dots text-blue-500"></i>
                        <?php echo htmlspecialchars($yorum['ad_soyad']); ?> tarafından yapılan yorumu düzenliyorsunuz
                    </p>
                </div>
            </div>
        </div>

        <!-- Ana Form Kartı -->
        <div class="relative bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 overflow-hidden">
            <!-- Üst dekoratif çizgi -->
            <div class="h-1.5 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
            
            <!-- Yıldızlı başlık bölümü -->
            <div class="px-8 pt-8 pb-6 bg-gradient-to-b from-blue-50/50 to-transparent border-b border-slate-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-3xl shadow-lg shadow-blue-500/25">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <?php echo renderStars($yorum['puan']); ?>
                                <span class="text-sm font-bold text-amber-500 ml-1"><?php echo $yorum['puan']; ?>/5</span>
                            </div>
                            <p class="text-xs text-slate-400 flex items-center gap-2">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('d.m.Y H:i', strtotime($yorum_tarihi)); ?>
                            </p>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-sm font-medium border border-slate-200">
                            <i class="fas fa-shield-alt text-blue-500"></i>
                            Güvenli Mod
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <?php if(isset($_SESSION['mesaj'])): ?>
                <div class="mb-6 rounded-2xl p-4 flex items-start gap-4 <?php echo $_SESSION['mesaj_tip'] == 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-rose-50 border border-rose-200 text-rose-700'; ?>">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl <?php echo $_SESSION['mesaj_tip'] == 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'; ?>">
                        <i class="fas <?php echo $_SESSION['mesaj_tip'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg"><?php echo $_SESSION['mesaj_tip'] == 'success' ? 'Başarılı!' : 'Hata!'; ?></h4>
                        <p class="text-sm opacity-90"><?php echo $_SESSION['mesaj']; ?></p>
                    </div>
                </div>
                <?php 
                unset($_SESSION['mesaj']);
                unset($_SESSION['mesaj_tip']);
                endif; 
                ?>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $yorum['id']; ?>">
                    <input type="hidden" name="guncelle" value="1">
                    
                    <!-- Ad Soyad -->
                    <div class="group">
                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 text-blue-600 shadow-sm">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            Ad Soyad <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($yorum['ad_soyad']); ?>" required
                               class="w-full rounded-xl border-2 border-slate-200 bg-white px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:shadow-lg focus:shadow-blue-500/10 hover:border-slate-300"
                               placeholder="Ziyaretçi adı soyadı">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- E-posta -->
                        <div class="group">
                            <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-50 text-emerald-600 shadow-sm">
                                    <i class="fas fa-envelope text-sm"></i>
                                </div>
                                E-posta
                            </label>
                            <div class="relative">
                                <input type="email" name="email" value="<?php echo htmlspecialchars($yorum['email']); ?>"
                                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-emerald-500 focus:shadow-lg focus:shadow-emerald-500/10 hover:border-slate-300"
                                       placeholder="ornek@email.com">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <i class="fas fa-at"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Tedavi -->
                        <div class="group">
                            <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-purple-50 text-purple-600 shadow-sm">
                                    <i class="fas fa-tooth text-sm"></i>
                                </div>
                                Tedavi Kategorisi
                            </label>
                            <div class="relative">
                                <select name="tedavi" class="w-full appearance-none rounded-xl border-2 border-slate-200 bg-white px-5 py-4 text-slate-800 outline-none transition-all focus:border-purple-500 focus:shadow-lg focus:shadow-purple-500/10 cursor-pointer hover:border-slate-300">
                                    <option value="LAZER CERRAHİ" <?php echo $yorum['tedavi'] == 'LAZER CERRAHİ' ? 'selected' : ''; ?>>🔴 LAZER CERRAHİ</option>
                                    <option value="DİJİTAL ÖLÇÜ" <?php echo $yorum['tedavi'] == 'DİJİTAL ÖLÇÜ' ? 'selected' : ''; ?>>🔵 DİJİTAL ÖLÇÜ</option>
                                    <option value="CAD/CAM" <?php echo $yorum['tedavi'] == 'CAD/CAM' ? 'selected' : ''; ?>>🟣 CAD/CAM</option>
                                    <option value="3D TOMOGRAFİ" <?php echo $yorum['tedavi'] == '3D TOMOGRAFİ' ? 'selected' : ''; ?>>🟢 3D TOMOGRAFİ</option>
                                    <option value="GÜLÜŞ TASARIMI" <?php echo $yorum['tedavi'] == 'GÜLÜŞ TASARIMI' ? 'selected' : ''; ?>>🟡 GÜLÜŞ TASARIMI</option>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Puan -->
                    <div class="group">
                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-amber-100 to-amber-50 text-amber-600 shadow-sm">
                                <i class="fas fa-star text-sm"></i>
                            </div>
                            Değerlendirme Puanı
                        </label>
                        <div class="grid grid-cols-5 gap-3">
                            <?php for($i=1; $i<=5; $i++): ?>
                            <label class="relative cursor-pointer group/rating">
                                <input type="radio" name="puan" value="<?php echo $i; ?>" <?php echo $yorum['puan'] == $i ? 'checked' : ''; ?> class="peer sr-only">
                                <div class="flex flex-col items-center justify-center p-4 rounded-xl border-2 border-slate-200 bg-white transition-all peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:shadow-lg peer-checked:shadow-amber-500/20 hover:border-amber-300 hover:bg-amber-50/50">
                                    <span class="text-2xl mb-1"><?php echo $i; ?></span>
                                    <i class="fas fa-star text-amber-400 text-sm"></i>
                                </div>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Yorum Metni -->
                    <div class="group">
                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-indigo-50 text-indigo-600 shadow-sm">
                                <i class="fas fa-comment-alt text-sm"></i>
                            </div>
                            Yorum İçeriği <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="yorum" rows="5" required
                                  class="w-full rounded-xl border-2 border-slate-200 bg-white px-5 py-4 text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-indigo-500 focus:shadow-lg focus:shadow-indigo-500/10 hover:border-slate-300 resize-none"
                                  placeholder="Yorum metnini buraya yazın..."><?php echo htmlspecialchars($yorum['yorum']); ?></textarea>
                        <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                            <span><i class="fas fa-info-circle mr-1"></i>Markdown desteklenmez</span>
                            <span id="charCount"><?php echo strlen($yorum['yorum']); ?> karakter</span>
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t-2 border-slate-100">
                        <button type="submit" class="group relative flex-1 overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 px-8 py-4 font-bold text-white shadow-xl shadow-blue-500/25 transition-all hover:shadow-blue-500/40 hover:scale-[1.02] active:scale-95">
                            <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform group-hover:translate-x-full duration-1000"></div>
                            <div class="relative flex items-center justify-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm border border-white/30">
                                    <span class="text-2xl font-black">D</span>
                                </div>
                                <div class="text-left">
                                    <span class="block text-lg font-bold">Değişiklikleri Kaydet</span>
                                    <span class="text-xs text-blue-100 font-medium">Güncellemeyi uygula</span>
                                </div>
                            </div>
                        </button>

                        <a href="?modul=yorumlar" class="group flex items-center justify-center gap-3 rounded-2xl bg-white px-8 py-4 font-bold text-slate-600 shadow-lg border-2 border-slate-200 transition-all hover:bg-slate-50 hover:text-slate-800 hover:border-slate-300 hover:shadow-xl hover:scale-[1.02] active:scale-95 sm:w-auto">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500 group-hover:bg-slate-200 group-hover:text-slate-700 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </div>
                            <div class="text-left">
                                <span class="block text-lg font-bold">İptal</span>
                                <span class="text-xs text-slate-400 font-medium">Listeye geri dön</span>
                            </div>
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Alt bilgi -->
            <div class="bg-slate-50/80 px-8 py-4 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                <div class="flex items-center gap-6">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-fingerprint text-blue-500"></i>
                        Yorum ID: <strong class="text-slate-700">#<?php echo $yorum['id']; ?></strong>
                    </span>
                    <span class="flex items-center gap-2">
                        <i class="fas fa-clock text-slate-400"></i>
                        Son güncelleme: <strong class="text-slate-700"><?php echo date('d.m.Y H:i:s'); ?></strong>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-shield-alt text-emerald-500"></i>
                    <span>CSRF Korumalı</span>
                </div>
            </div>
        </div>

        <!-- Alt ipuçları -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-2xl bg-white/60 backdrop-blur-md p-5 border border-white/50 shadow-lg flex items-center gap-4 hover:shadow-xl transition-shadow">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center text-blue-600 shadow-sm">
                    <i class="fas fa-user-check text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">Doğrulama</p>
                    <p class="text-xs text-slate-500">Zorunlu alanları doldurun</p>
                </div>
            </div>
            <div class="rounded-2xl bg-white/60 backdrop-blur-md p-5 border border-white/50 shadow-lg flex items-center gap-4 hover:shadow-xl transition-shadow">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-amber-100 to-amber-50 flex items-center justify-center text-amber-600 shadow-sm">
                    <i class="fas fa-star text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">Puanlama</p>
                    <p class="text-xs text-slate-500">1-5 arası değerlendirme</p>
                </div>
            </div>
            <div class="rounded-2xl bg-white/60 backdrop-blur-md p-5 border border-white/50 shadow-lg flex items-center gap-4 hover:shadow-xl transition-shadow">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center text-emerald-600 shadow-sm">
                    <i class="fas fa-sync-alt text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">Anlık</p>
                    <p class="text-xs text-slate-500">Değişiklikler hemen aktif</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Blob animasyonları */
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
.animation-delay-4000 {
    animation-delay: 4s;
}

/* Input hover ve focus efektleri */
input:hover, select:hover, textarea:hover {
    transform: translateY(-1px);
}
input:focus, select:focus, textarea:focus {
    transform: translateY(-2px);
}

/* Rating kart hover efekti */
.group\/rating:hover .peer-checked\:bg-amber-50 {
    transform: scale(1.05);
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<script>
// Karakter sayacı
const textarea = document.querySelector('textarea[name="yorum"]');
const charCount = document.getElementById('charCount');

textarea.addEventListener('input', function() {
    const count = this.value.length;
    charCount.textContent = count + ' karakter';
    charCount.className = count > 500 ? 'text-rose-500 font-bold' : 'text-slate-400';
});

// Form submit loading
document.querySelector('form').addEventListener('submit', function(e) {
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Kaydediliyor...';
    submitBtn.disabled = true;
});
</script>