<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <a href="<?php echo SITE_PATH; ?>/" class="flex items-center gap-2">
                <img src="<?php echo htmlspecialchars($header_logo); ?>" class="w-12 h-12 object-contain" alt="Logo">
                <span class="font-bold text-xl text-gray-800"><?php echo htmlspecialchars($site_baslik); ?></span>
            </a>
            
            <div class="hidden md:flex items-center gap-6">
                <a href="<?php echo SITE_PATH; ?>/" class="hover:text-blue-600 transition"><?php echo t_cevir("Ana Sayfa"); ?></a>
                <a href="<?php echo SITE_PATH; ?>/kurumsal.php" class="hover:text-blue-600 transition"><?php echo t_cevir("Kurumsal"); ?></a>
                <a href="<?php echo SITE_PATH; ?>/tedaviler.php" class="hover:text-blue-600 transition"><?php echo t_cevir("Tedaviler"); ?></a>
                <a href="<?php echo SITE_PATH; ?>/iletisim.php" class="hover:text-blue-600 transition"><?php echo t_cevir("İletişim"); ?></a>
                <a href="<?php echo SITE_PATH; ?>/randevu.php" class="bg-blue-600 text-white px-5 py-2 rounded-full text-sm font-bold hover:bg-blue-700 transition"><?php echo t_cevir("Randevu Al"); ?></a>
            </div>
            
            <button class="md:hidden text-gray-600 mobile-menu-btn">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
        
        <div class="hidden md:hidden pb-4 mobile-menu">
            <div class="flex flex-col gap-3">
                <a href="<?php echo SITE_PATH; ?>/" class="hover:text-blue-600 py-2"><?php echo t_cevir("Ana Sayfa"); ?></a>
                <a href="<?php echo SITE_PATH; ?>/kurumsal.php" class="hover:text-blue-600 py-2"><?php echo t_cevir("Kurumsal"); ?></a>
                <a href="<?php echo SITE_PATH; ?>/tedaviler.php" class="hover:text-blue-600 py-2"><?php echo t_cevir("Tedaviler"); ?></a>
                <a href="<?php echo SITE_PATH; ?>/iletisim.php" class="hover:text-blue-600 py-2"><?php echo t_cevir("İletişim"); ?></a>
            </div>
        </div>
    </div>
</nav>