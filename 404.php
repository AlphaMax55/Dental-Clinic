<?php
// www/site/404.php
require_once 'inc/config.php';

// HTTP 404 durum kodunu gönder
http_response_code(404);

$page_title = t_cevir("Sayfa Bulunamadı") . " - 404 | " . ($site_baslik ?? 'RivaDent Diş Kliniği');
$page_description = t_cevir("Aradığınız sayfa bulunamadı. Prof. Dr. İbrahim Duran - Protetik Diş Tedavisi Uzmanı'nın sunduğu diğer hizmetleri keşfedin.");

include 'inc/header.php';
?>

<main class="min-h-screen bg-gradient-to-b from-white via-blue-50/20 to-white">
    <div class="max-w-7xl mx-auto px-4 py-20 lg:py-32">
        
        <div class="relative overflow-hidden rounded-3xl bg-white shadow-2xl border border-gray-100">
            
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-purple-500/5 to-pink-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 p-8 lg:p-16 text-center">
                
                <div class="mb-8">
                    <div class="inline-flex items-center gap-4">
                        <span class="text-8xl lg:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-600 animate-pulse">4</span>
                        <span class="text-8xl lg:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-600 animate-bounce">0</span>
                        <span class="text-8xl lg:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-600 animate-pulse">4</span>
                    </div>
                </div>
                
                <div class="max-w-2xl mx-auto space-y-6">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 rounded-full text-blue-600 text-sm font-semibold mx-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span><?php echo t_cevir('SAYFA BULUNAMADI'); ?></span>
                    </div>
                    
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-800">
                        <?php echo t_cevir('Üzgünüz, aradığınız sayfaya ulaşılamıyor'); ?>
                    </h1>
                    
                    <p class="text-gray-500 text-lg">
                        <?php echo t_cevir('Aradığınız sayfa taşınmış, silinmiş veya hiç var olmamış olabilir. Ana sayfamızdan hizmetlerimize göz atabilirsiniz.'); ?>
                    </p>
                    
                    <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                        <a href="<?php echo SITE_PATH; ?>/" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <?php echo t_cevir('Ana Sayfaya Dön'); ?>
                        </a>
                        <a href="<?php echo SITE_PATH; ?>/#tedaviler" class="inline-flex items-center gap-2 px-6 py-3 bg-white border-2 border-gray-200 text-gray-700 font-semibold rounded-xl hover:border-blue-300 hover:shadow-lg transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3.5a6 6 0 0 1 6 6c0 2.5-1.5 5-3 6.5s-3 3-3 5-1.5 2-3 2-3-1-3-2-1.5-3.5-3-5-3-4-3-6.5a6 6 0 0 1 6-6z"/></svg>
                            <?php echo t_cevir('Tedavileri Keşfet'); ?>
                        </a>
                    </div>
                </div>
                
<div class="mt-16 pt-8 border-t border-gray-100" id="tedaviler">
    <h2 class="text-xl font-semibold text-gray-700 mb-6"><?php echo t_cevir('Popüler Tedavilerimiz'); ?></h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-left">
        <?php
        // Aktif dil kodunu al (Sisteminde dil kontrolü nasıl yapılıyorsa, örn: $_SESSION['dil'] veya tanımlı bir sabit)
        // Eğer sisteminde benzer bir değişken varsa onu ata, yoksa varsayılan olarak 'tr' kabul etsin.
        $aktif_dil_kodu = defined('LANG') ? LANG : ($_SESSION['dil'] ?? 'tr');

        $populer_tedaviler = $db->query("SELECT baslik, slug FROM tedaviler WHERE aktif = 1 LIMIT 6")->fetchAll();
        foreach($populer_tedaviler as $tedavi):
            // Veritabanından gelen ham JSON veriyi PHP dizisine dönüştürüyoruz
            $baslik_verisi = json_decode($tedavi['baslik'], true);
            
            // Eğer veri başarıyla decode edildiyse ve aktif dilde karşılığı varsa onu al, yoksa ham halini veya tr karşılığını kullan
            if (json_last_error() === JSON_ERROR_NONE && is_array($baslik_verisi)) {
                $gosterilecek_baslik = $baslik_verisi[$aktif_dil_kodu] ?? ($baslik_verisi['tr'] ?? $tedavi['baslik']);
            } else {
                $gosterilecek_baslik = $tedavi['baslik'];
            }
        ?>
        <a href="<?php echo SITE_PATH; ?>/tedavi-detay.php?slug=<?php echo htmlspecialchars($tedavi['slug']); ?>" 
           class="group flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-blue-50 transition-all duration-300">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-cyan-100 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3.5a6 6 0 0 1 6 6c0 2.5-1.5 5-3 6.5s-3 3-3 5-1.5 2-3 2-3-1-3-2-1.5-3.5-3-5-3-4-3-6.5a6 6 0 0 1 6-6z"/></svg>
            </div>
            <div>
                <!-- Burada decode edilmiş temiz metni t_cevir fonksiyonuna gönderiyoruz -->
                <div class="font-semibold text-gray-800 group-hover:text-blue-600"><?php echo t_cevir($gosterilecek_baslik); ?></div>
                <div class="text-xs text-gray-400"><?php echo t_cevir('Detaylı Bilgi →'); ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
            </div>
            
            <div class="mt-12 bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl p-6 text-center text-white">
                <p class="text-gray-300 mb-2"><?php echo t_cevir('Hemen randevu almak veya sorularınızı iletmek ister misiniz?'); ?></p>
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $telefon ?? '+905052232343'); ?>" class="inline-flex items-center gap-2 text-blue-400 hover:text-blue-300 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <?php echo htmlspecialchars($telefon ?? '+90 505 223 23 43'); ?>
                    </a>
                    <span class="text-gray-600">|</span>
                    <a href="mailto:<?php echo htmlspecialchars($eposta ?? 'ibrahimdurandental@gmail.com'); ?>" class="inline-flex items-center gap-2 text-blue-400 hover:text-blue-300 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <?php echo htmlspecialchars($eposta ?? 'ibrahimdurandental@gmail.com'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; transform: scale(1.05); } }
    @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
    .animate-pulse { animation: pulse 2s ease-in-out infinite; }
    .animate-bounce { animation: bounce 1s ease-in-out infinite; }
</style>

<?php include 'inc/footer.php'; ?>