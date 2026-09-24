<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

// Yorum onayla
if (isset($_GET['onayla'])) {
    $id = $_GET['onayla'];
    $db->prepare("UPDATE randevular SET durum = 'onaylandi' WHERE id = ?")->execute([$id]);
    header('Location: ?modul=randevular');
    exit;
}

// Yorum sil
if (isset($_GET['sil'])) {
    $id = $_GET['sil'];
    $db->prepare("DELETE FROM randevular WHERE id = ?")->execute([$id]);
    header('Location: ?modul=randevular');
    exit;
}

// Randevu listesini çek
$stmt = $db->query("SELECT * FROM randevular ORDER BY created_at DESC");
$randevular = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📅 Randevu Talepleri</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ad Soyad</th>
                                    <th>Telefon</th>
                                    <th>E-posta</th>
                                    <th>Konu</th>
                                    <th>Mesaj</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($randevular) > 0): ?>
                                    <?php foreach ($randevular as $r): ?>
                                    <tr>
                                        <td><?php echo $r['id']; ?></td>
                                        <td><?php echo htmlspecialchars($r['ad_soyad']); ?></td>
                                        <td><?php echo htmlspecialchars($r['telefon']); ?></td>
                                        <td><?php echo htmlspecialchars($r['email']); ?></td>
                                        <td><?php echo htmlspecialchars($r['konu']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($r['mesaj'], 0, 50)); ?>...</td>
                                        <td>
                                            <?php if ($r['durum'] == 'bekliyor'): ?>
                                                <span class="badge bg-warning">⏳ Bekliyor</span>
                                            <?php elseif ($r['durum'] == 'onaylandi'): ?>
                                                <span class="badge bg-success">✅ Onaylandı</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">❌ İptal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $r['created_at']; ?></td>
                                        <td>
                                            <?php if ($r['durum'] == 'bekliyor'): ?>
                                                <a href="?modul=randevular&onayla=<?php echo $r['id']; ?>" class="btn btn-success btn-sm">✅ Onayla</a>
                                            <?php endif; ?>
                                            <a href="?modul=randevular&sil=<?php echo $r['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinize emin misiniz?')">🗑️ Sil</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" class="text-center">Henüz randevu talebi yok</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>