<?php
require_once dirname(__DIR__) . '/admin/includes/config.php';

$email = $_GET['email'] ?? '';
if ($email) {
    $db->prepare("DELETE FROM blog_aboneler WHERE email = ?")->execute([$email]);
    echo "Aboneliğiniz kaldırıldı.";
}
?>