<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/admin/includes/config.php';

$baslangic = $_GET['baslangic'] ?? date('Y-m-01');
$bitis = $_GET['bitis'] ?? date('Y-m-d');

// Debug için log
error_log("Grafik sorgusu: $baslangic - $bitis");

$stmt = $db->prepare("SELECT DATE(tarih) as gun, COUNT(*) as ziyaret FROM site_istatistikler WHERE DATE(tarih) BETWEEN ? AND ? GROUP BY DATE(tarih) ORDER BY gun ASC");
$stmt->execute([$baslangic, $bitis]);
$data = $stmt->fetchAll();

$labels = [];
$values = [];
foreach ($data as $row) {
    $labels[] = date('d.m', strtotime($row['gun']));
    $values[] = $row['ziyaret'];
}

// Hiç veri yoksa son 7 günü göster
if (empty($labels)) {
    for ($i = 6; $i >= 0; $i--) {
        $labels[] = date('d.m', strtotime("-$i days"));
        $values[] = 0;
    }
}

echo json_encode(['labels' => $labels, 'values' => $values]);
?>