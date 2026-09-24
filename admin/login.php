<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$hata = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = $_POST['kullanici_adi'] ?? '';
    $sifre = $_POST['sifre'] ?? '';
    
    if (login($kullanici_adi, $sifre)) {
        header('Location: index.php');
        exit;
    } else {
        $hata = 'Kullanıcı adı veya şifre hatalı!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-96">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Admin Paneli</h1>
            <p class="text-sm text-gray-500 mt-1">Prof. Dr. İbrahim Duran</p>
        </div>
        
        <?php if ($hata): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-xl mb-4 text-sm">
                <?php echo $hata; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kullanıcı Adı / E-posta
                </label>
                <input type="text" name="kullanici_adi" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Şifre
                </label>
                <input type="password" name="sifre" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <button type="submit" 
                    class="w-full py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-all">
                Giriş Yap
            </button>
        </form>
    </div>
</div>

</body>
</html>