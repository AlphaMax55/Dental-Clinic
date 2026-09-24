<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/admin/includes/config.php';

$baslangic = $_GET['baslangic'] ?? date('Y-m-01');
$bitis = $_GET['bitis'] ?? date('Y-m-d');

// Sayfa bazlı ziyaret sayıları (top 5)
$stmt = $db->prepare("SELECT sayfa, COUNT(*) as ziyaret FROM site_istatistikler WHERE DATE(tarih) BETWEEN ? AND ? GROUP BY sayfa ORDER BY ziyaret DESC LIMIT 5");
$stmt->execute([$baslangic, $bitis]);
$data = $stmt->fetchAll();

$labels = [];
$values = [];
$toplam = 0;

foreach ($data as $row) {
    // Sayfa adını kısalt
    $sayfa = $row['sayfa'];
    if ($sayfa === '/' || $sayfa === 'http://localhost:3000/') $sayfa = 'Ana Sayfa';
    elseif ($sayfa === '/iletisim/') $sayfa = 'İletişim';
    elseif ($sayfa === '/tedaviler/') $sayfa = 'Tedaviler';
    elseif ($sayfa === '/kurumsal/') $sayfa = 'Kurumsal';
    elseif ($sayfa === '/blog/') $sayfa = 'Blog';
    elseif ($sayfa === '/galeri/') $sayfa = 'Galeri';
    
    $labels[] = $sayfa;
    $values[] = $row['ziyaret'];
    $toplam += $row['ziyaret'];
}

echo json_encode(['labels' => $labels, 'values' => $values, 'toplam' => $toplam]);
?>