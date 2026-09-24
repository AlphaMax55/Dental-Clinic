</main>
<footer class="bg-slate-900 pt-20 pb-10 text-slate-300 font-sans relative overflow-hidden border-t border-slate-800">
    <div class="absolute top-0 left-1/4 w-1/2 h-1/2 bg-blue-900/20 blur-[120px] rounded-full pointer-events-none"></div>
    
    <div class="max-w-[1400px] mx-auto px-6 relative z-10">
        
        <div class="relative bg-gradient-to-r from-slate-800 to-slate-900 p-10 lg:p-16 rounded-[3rem] mb-20 overflow-hidden flex flex-col lg:flex-row items-center justify-between shadow-2xl border border-slate-700/50 group text-center lg:text-left">
            <div class="absolute top-1/2 -translate-y-1/2 right-0 w-2/3 h-full opacity-10 pointer-events-none group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-activity w-full h-full text-blue-400"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path></svg>
            </div>
            <div class="z-10 mb-8 lg:mb-0 max-w-2xl">
                <h2 class="text-3xl lg:text-5xl font-black text-white mb-4 leading-tight">
                    <?php echo t_cevir("Sağlıklı bir gülüş için"); ?> <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400"><?php echo t_cevir("ilk adımı bugün atın."); ?></span>
                </h2>
                <p class="text-slate-100 text-lg"><?php echo t_cevir("Uzman kadromuzla tanışmak için hemen iletişime geçin."); ?></p>
            </div>
            <div class="z-10 flex flex-wrap gap-4 justify-center">
                <a class="bg-white text-slate-900 px-8 py-4 rounded-full font-bold hover:scale-105 transition-all flex items-center gap-2 no-underline" href="<?php echo SITE_PATH; ?>/iletisim">
                    <?php echo t_cevir("Hemen İletişime Geçin"); ?> 
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                </a>
                <a class="bg-transparent border-2 border-blue-500 text-blue-400 px-8 py-4 rounded-full font-bold hover:bg-blue-500/10 transition-all no-underline" href="<?php echo SITE_PATH; ?>/iletisim/"><?php echo t_cevir("Bize Ulaşın"); ?></a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-12 mb-16">
            <div class="lg:col-span-4 space-y-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"></path><path d="M20 2v4"></path><path d="M22 4h-4"></path><circle cx="4" cy="20" r="2"></circle></svg>
                    </div>
                    <span class="text-2xl font-black text-white tracking-tight"><?php echo htmlspecialchars($site_baslik); ?></span>
                </div>
                <p class="text-slate-200 leading-relaxed pr-6 italic">
                    <?php echo htmlspecialchars($footer_aciklama); ?>
                </p>
                <div class="flex gap-4">
                    <?php if(!empty($instagram)): ?>
                    <a href="<?php echo htmlspecialchars($instagram); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="hover:text-blue-400 transition-colors" 
                       aria-label="İbrahim Duran Dental Clinic Instagram sayfası">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line>
                        </svg>
                    </a>
                    <?php endif; ?>

                    <?php if(!empty($facebook)): ?>
                    <a href="<?php echo htmlspecialchars($facebook); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="hover:text-blue-400 transition-colors" 
                       aria-label="İbrahim Duran Dental Clinic Facebook sayfası">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ===== HIZLI ERİŞİM (h3 → p) ===== -->
            <div class="lg:col-span-2 space-y-6">
                <p class="text-white font-bold text-lg mb-6 underline decoration-blue-500/30 underline-offset-8"><?php echo t_cevir("Hızlı Erişim"); ?></p>
                <ul class="space-y-3 font-medium text-sm">
                    <li><a class="hover:text-blue-400 transition-all no-underline" href="<?php echo SITE_PATH; ?>/"><?php echo t_cevir("Anasayfa"); ?></a></li>
                    <li><a class="hover:text-blue-400 transition-all no-underline" href="<?php echo SITE_PATH; ?>/kurumsal/"><?php echo t_cevir("Kurumsal"); ?></a></li>
                    <li><a class="hover:text-blue-400 transition-all no-underline" href="<?php echo SITE_PATH; ?>/iletisim/"><?php echo t_cevir("İletişim"); ?></a></li>
                </ul>
            </div>
            
            <!-- ===== POPÜLER TEDAVİLER (h4 → p) ===== -->
            <div class="lg:col-span-3 space-y-6">
                <p class="text-white font-bold text-lg mb-6 underline decoration-blue-500/30 underline-offset-8"><?php echo t_cevir("Popüler Tedaviler"); ?></p>
                <ul class="space-y-3 font-medium text-sm">
                    <li><a class="hover:text-blue-400 transition-all no-underline" href="<?php echo SITE_PATH; ?>/tedaviler/implant"><?php echo t_cevir("İmplant Tedavisi"); ?></a></li>
                    <li><a class="hover:text-blue-400 transition-all no-underline" href="<?php echo SITE_PATH; ?>/tedaviler/zirkonyum-kaplama"><?php echo t_cevir("Zirkonyum Kaplama"); ?></a></li>
                    <li><a class="hover:text-blue-400 transition-all no-underline" href="<?php echo SITE_PATH; ?>/tedaviler/gulus-tasarimi"><?php echo t_cevir("Gülüş Tasarımı"); ?></a></li>
                </ul>
            </div>
            
            <!-- ===== İLETİŞİM BİLGİLERİ (h4 → p) ===== -->
            <div class="lg:col-span-3 space-y-6">
                <p class="text-white font-bold text-lg mb-6 underline decoration-blue-500/30 underline-offset-8"><?php echo t_cevir("İletişim Bilgileri"); ?></p>
                <ul class="space-y-5">
                    <li class="flex gap-4 items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin text-blue-700 shrink-0"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <div class="flex flex-col gap-1">
                            <span class="text-slate-100 text-sm">
                                <?php 
                                $adres_son = $db->query("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'adres'")->fetchColumn();
                                if (empty($adres_son)) {
                                    $adres_son = 'Mimar Sinan Mah. Atatürk Bulvarı Riva İş Merkezi No:260 Kat:1 Daire:2, 55200 Atakum/Samsun, Türkiye';
                                }
                                echo htmlspecialchars($adres_son);
                                ?>
                            </span>
                            <a class="text-[10px] font-black text-blue-400 uppercase flex items-center gap-1 no-underline" href="<?php echo SITE_PATH; ?>/iletisim/">
                                <?php echo t_cevir("Yol Tarifi"); ?> 
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                            </a>
                        </div>
                    </li>
                    
                    <li class="flex gap-4 items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone text-blue-700 shrink-0"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path></svg>
                        <span class="text-lg font-bold text-white tracking-tighter">
                            <?php 
                            $telefon_son = $db->query("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'header1_telefon'")->fetchColumn();
                            if (empty($telefon_son)) {
                                $telefon_son = $db->query("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'telefon'")->fetchColumn();
                            }
                            echo htmlspecialchars($telefon_son ?: '+90 505 223 23 43');
                            ?>
                        </span>
                    </li>
                    
                    <li class="flex gap-4 items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail text-blue-700 shrink-0"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path><rect x="2" y="4" width="20" height="16" rx="2"></rect></svg>
                        <span class="text-sm font-bold text-slate-100">
                            <?php 
                            $eposta_son = $db->query("SELECT ayar_value FROM site_ayarlari WHERE ayar_key = 'eposta'")->fetchColumn();
                            echo htmlspecialchars($eposta_son ?: 'ibrahimdurandental@gmail.com');
                            ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="pt-8 border-t border-slate-800 flex flex-col md:flex-row items-center justify-between text-sm text-slate-500">
            <p><?php echo htmlspecialchars($footer_copyright); ?></p>
            <div class="flex gap-4 mt-4 md:mt-0 text-[10px] font-bold uppercase tracking-widest">
                <a class="hover:text-white no-underline" href="<?php echo SITE_PATH; ?>/kvkk/">KVKK</a>
                <a class="hover:text-white no-underline" href="<?php echo SITE_PATH; ?>/cerez-politikasi/"><?php echo t_cevir("Çerez Politikası"); ?></a>
<button type="button" onclick="cerezTercihleriSifirla()" class="text-sm text-gray-400 hover:text-cyan-400 transition-colors bg-transparent border-0 cursor-pointer p-0">
    ÇEREZ TERCİHLERİNİ DEĞİŞTİR
</button>
		   </div>
        </div>

    </div>
</footer>
</body>
</html>
<!-- footer.php - EN SONA EKLE -->
<?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') === false): ?>
<script>
(function() {
    'use strict';
    
    // 1. Aynı sayfada tekrar kaydetmeyi önle (3 saniyelik güvenlik kilidi)
    var key = 'ist_kaydedildi_' + window.location.pathname;
    var lastTime = sessionStorage.getItem(key + '_time');
    var now = Date.now();
    
    // Eğer son 3 saniye içinde kaydedilmişse, bir daha atma (Çift kayıt önleme)
    if (lastTime && (now - parseInt(lastTime)) < 3000) {
        return;
    }
    sessionStorage.setItem(key + '_time', now);
    
    // 2. Sayfa bilgileri
    var sayfa = window.location.pathname;
    var slug = '';
    if (sayfa.indexOf('/tedaviler/') !== -1) {
        slug = sayfa.split('/tedaviler/')[1]?.split('/')[0] || '';
    }
    
    // 3. OTURUM BAŞLANGICI (ilk ziyaret)
    var oturumBaslangic = sessionStorage.getItem('oturum_baslangic');
    if (!oturumBaslangic) {
        oturumBaslangic = new Date().toISOString().slice(0, 19).replace('T', ' ');
        sessionStorage.setItem('oturum_baslangic', oturumBaslangic);
    }
    
    // 4. Kaç sayfa gezdi?
    var sayfaSayisi = parseInt(sessionStorage.getItem('sayfa_sayisi') || '0') + 1;
    sessionStorage.setItem('sayfa_sayisi', sayfaSayisi);
    
    // 5. API'ye gönder (sendBeacon ile)
    function sendStats() {
        try {
            var data = new URLSearchParams({ 
                sayfa: sayfa, 
                slug: slug,
                oturum_baslangic: oturumBaslangic,
                sayfa_sayisi: sayfaSayisi
            });
            navigator.sendBeacon('/api/istatistik-kaydet.php', data);
        } catch(e) {
            // Sessizce hata
        }
    }
    
    // 6. En iyi performans için idle callback
    if ('requestIdleCallback' in window) {
        requestIdleCallback(sendStats, { timeout: 2000 });
    } else {
        if (document.readyState === 'complete') {
            setTimeout(sendStats, 100);
        } else {
            window.addEventListener('load', function() {
                setTimeout(sendStats, 100);
            }, { once: true, passive: true });
        }
    }
    
    // 7. Sayfa kapanırken süreyi gönder
    window.addEventListener('beforeunload', function() {
        var baslangic = new Date(oturumBaslangic);
        var simdi = new Date();
        var fark = Math.floor((simdi - baslangic) / 1000);
        
        var data = new URLSearchParams({ 
            sayfa: sayfa,
            oturum_suresi: fark,
            sayfa_sayisi: sayfaSayisi
        });
        navigator.sendBeacon('/api/oturum-kapat.php', data);
    });
})();
</script>

<?php endif; ?>