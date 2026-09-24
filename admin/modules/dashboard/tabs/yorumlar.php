<div class="space-y-6">

    <!-- İstatistik Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Toplam Veri</p>
                    <p class="text-4xl font-black text-gray-900 mt-1"><?php echo $toplam; ?></p>
                </div>
                <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl group-hover:scale-110 transition-transform duration-500">
                    <i class="fas fa-comments text-xl"></i>
                </div>
            </div>
            <a href="?modul=yorumlar" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gray-900 hover:bg-blue-600 text-white text-sm font-bold rounded-xl transition-all duration-300 shadow-lg shadow-gray-200 hover:shadow-blue-200 group/btn">
                <span>Yorumları Yönet</span>
                <i class="fas fa-chevron-right text-[10px] group-hover/btn:translate-x-1 transition-transform"></i>
            </a>
        </div>
        
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Onaylı</p>
                    <p class="text-4xl font-black text-green-600 mt-1"><?php echo $onayli; ?></p>
                </div>
                <div class="p-4 bg-green-50 text-green-600 rounded-2xl">
                    <i class="fas fa-check-double text-xl"></i>
                </div>
            </div>
            <div class="w-full py-3 px-4 bg-green-50/50 rounded-xl border border-green-100/50">
                <p class="text-[11px] text-green-700 font-bold text-center uppercase tracking-tighter">Yayındaki Yorum Sayısı</p>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group relative overflow-hidden">
            <?php if($bekleyen > 0): ?>
                <div class="absolute -top-4 -right-4 w-20 h-20 bg-amber-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700"></div>
            <?php endif; ?>

            <div class="flex items-center justify-between mb-5 relative z-10">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Bekleyen</p>
                    <p class="text-4xl font-black text-amber-600 mt-1"><?php echo $bekleyen; ?></p>
                </div>
                <div class="p-4 <?php echo $bekleyen > 0 ? 'bg-amber-100 text-amber-600 animate-bounce' : 'bg-gray-50 text-gray-400'; ?> rounded-2xl transition-all">
                    <i class="fas <?php echo $bekleyen > 0 ? 'fa-bell' : 'fa-bell-slash'; ?> text-xl"></i>
                </div>
            </div>
            
            <?php if($bekleyen > 0): ?>
                <a href="?modul=yorumlar&sayfa=bekleyenler" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl transition-all duration-300 shadow-lg shadow-amber-100 hover:shadow-amber-200 group/btn">
                    <i class="fas fa-user-check text-xs"></i>
                    <span>İncele ve Onayla</span>
                    <span class="ml-1 flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                    </span>
                </a>
            <?php else: ?>
                <div class="w-full py-3 px-4 bg-gray-50 rounded-xl border border-dashed border-gray-200 text-center">
                    <span class="text-xs font-bold text-gray-400 uppercase">Her Şey Güncel</span>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Yorumlar Tablosu -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                Son Yorumlar
            </h3>
            <span class="text-xs text-gray-400 bg-gray-700 px-3 py-1 rounded-full"><?php echo count($yorumlar); ?> kayıt</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">İsim</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Yorum</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tedavi</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Puan</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($yorumlar as $y): ?>
                    <tr class="hover:bg-gray-50 transition-colors <?php echo $y['onay'] == 0 ? 'bg-amber-50/40' : ''; ?>">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                    <?php echo strtoupper(substr($y['ad_soyad'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm"><?php echo $y['ad_soyad']; ?></p>
                                    <p class="text-xs text-gray-400"><?php echo date('d.m.Y', strtotime($y['created_at'] ?? 'now')); ?></p>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-600 max-w-xs line-clamp-2 leading-relaxed">"<?php echo substr($y['yorum'], 0, 60); ?><?php echo strlen($y['yorum']) > 60 ? '...' : ''; ?>"</p>
                        </td>
                        
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                <?php echo $y['tedavi'] ?? 'Genel'; ?>
                            </span>
                        </td>
                        
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <span class="text-lg font-bold <?php echo $y['puan'] >= 4 ? 'text-green-600' : ($y['puan'] >= 3 ? 'text-yellow-600' : 'text-red-600'); ?>"><?php echo $y['puan']; ?></span>
                                <span class="text-gray-400 text-sm">/5</span>
                                <svg class="w-4 h-4 text-yellow-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 text-center">
                            <?php if($y['onay'] == 0): ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full border border-amber-200">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                                Bekliyor
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full border border-green-200">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Onaylı
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if(empty($yorumlar)): ?>
        <div class="text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
            </div>
            <p class="text-gray-500 font-medium">Henüz yorum bulunmuyor</p>
        </div>
        <?php endif; ?>
    </div>
</div>