<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
kontrol();

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'listele';

// SSS ekle/güncelle/sil işlemleri burada olacak
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">❓ SSS Yönetimi</h2>
        <button onclick="sssEkle()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-plus"></i> Yeni Soru Ekle
        </button>
    </div>
    
    <div class="p-6">
        <div id="sssListesi"></div>
    </div>
</div>

<script>
function sssEkle() {
    window.location.href = '?modul=sss&sayfa=duzenle';
}

function sssListele() {
    fetch('/admin/api/sss.php?islem=listele')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('sssListesi');
            if(data.data && data.data.length > 0) {
                let html = '<div class="space-y-3">';
                data.data.forEach(item => {
                    html += `
                        <div class="border rounded-xl p-4 bg-white hover:shadow-md transition">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">${item.kategori}</span>
                                        <span class="text-xs text-gray-400">Sıra: ${item.sira}</span>
                                    </div>
                                    <h3 class="font-bold text-gray-800">${escapeHtml(item.soru)}</h3>
                                    <p class="text-gray-600 text-sm mt-2 line-clamp-2">${escapeHtml(item.cevap)}</p>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <button onclick="sssDuzenle(${item.id})" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                                    <button onclick="sssSil(${item.id})" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-center py-8 text-gray-400">Henüz SSS kaydı yok</div>';
            }
        });
}

function sssDuzenle(id) {
    window.location.href = `?modul=sss&sayfa=duzenle&id=${id}`;
}

function sssSil(id) {
    if(confirm('Bu soruyu silmek istediğinize emin misiniz?')) {
        fetch(`/admin/api/sss.php?islem=sil&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    sssListele();
                } else {
                    alert('Silme başarısız!');
                }
            });
    }
}

function escapeHtml(str) {
    if(!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

sssListele();
</script>