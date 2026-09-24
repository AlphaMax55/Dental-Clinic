<?php
// Session ve buffering başlat - HEADER HATASINI ÖNLEMEK İÇİN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

$aktif_modul = $_GET['modul'] ?? 'dashboard';
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!-- Sidebar -->
<div class="w-72 bg-gray-900 text-white flex flex-col h-screen sticky top-0 overflow-y-auto">

    <!-- Logo -->
    <div class="p-6 border-b border-gray-800">
        <h2 class="text-xl font-bold">ADMIN PANEL</h2>
        <p class="text-xs text-gray-500 mt-1"><?php echo $_SESSION['admin_adi'] ?? 'Admin'; ?></p>
    </div>

    <!-- Menü -->
    <nav class="flex-1 p-4">
        <ul class="space-y-1">

            <!-- KONTROL PANELİ -->
            <li>
                <a href="?modul=dashboard" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'dashboard' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-home w-5"></i>
                    <span>Kontrol Paneli</span>
                </a>
            </li>

            <!-- İÇERİK YÖNETİMİ -->
            <li class="pt-4 mt-4 border-t border-gray-800">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider px-4">İÇERİK YÖNETİMİ</span>
            </li>
            <li>
                <a href="?modul=anasayfa" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'anasayfa' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-house w-5"></i>
                    <span>Anasayfa</span>
                </a>
            </li>
            <li>
                <a href="?modul=tedaviler" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'tedaviler' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-tooth w-5"></i>
                    <span>Tedaviler</span>
                </a>
            </li>
            <li>
                <a href="?modul=kurumsal" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'kurumsal' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-building w-5"></i>
                    <span>Kurumsal</span>
                </a>
            </li>
            <li>
                <a href="?modul=blog" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'blog' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-blog w-5"></i>
                    <span>Blog</span>
                </a>
            </li>

            <li>
                <a href="?modul=galeri" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'galeri' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-images w-5"></i>
                    <span>Galeri</span>
                </a>
            </li>



            <!-- RANDEVU & İLETİŞİM -->
            <li class="pt-4 mt-4 border-t border-gray-800">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider px-4">RANDEVU & İLETİŞİM</span>
            </li>
			  <!--
            <li>
                <a href="?modul=randevular" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'randevular' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-calendar-check w-5"></i>
                    <span  style="    text-decoration: line-through;
    color: #94a3b8; /* İsteğe bağlı rengi soluklaştırabilirsin */">Randevular</span>
                </a>
            </li>
-->
            <li>
                <a href="?modul=iletisim" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'iletisim' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-envelope w-5"></i>
                    <span>İletişim</span>
                </a>
            </li>

            <li>
                <a href="?modul=yorumlar" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'yorumlar' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-star w-5"></i>
                    <span>Yorumlar</span>
                </a>
            </li>

            <!-- KULLANICI YÖNETİMİ -->
            <li class="pt-4 mt-4 border-t border-gray-800">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider px-4">KULLANICI YÖNETİMİ</span>
            </li>

            <li>
                <a href="?modul=kullanicilar" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'kullanicilar' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-users w-5"></i>
                    <span>Kullanıcılar</span>
                </a>
            </li>

            <!-- AYARLAR & SEO -->
            <li class="pt-4 mt-4 border-t border-gray-800">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider px-4">AYARLAR & SEO</span>
            </li>

            <li>
                <a href="?modul=raporlar" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'raporlar' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-chart-pie w-5"></i>
                    <span>Raporlar</span>
                </a>
            </li>

            <li>
                <a href="?modul=ayarlar" 
                   class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all
                   <?php echo $aktif_modul == 'ayarlar' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                    <i class="fas fa-cog w-5"></i>
                    <span>Sistem Ayarları</span>
                </a>
            </li>

        </ul>
    </nav>

    <!-- Alt Kısım (Çıkış) -->
    <div class="p-4 border-t border-gray-800">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition-all">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Çıkış Yap</span>
        </a>
    </div>
</div>