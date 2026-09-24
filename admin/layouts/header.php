<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Özel CSS -->
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <!-- SIDEBAR BURADA ÇAĞRILACAK -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Sağ içerik alanı -->
    <div class="flex-1 flex flex-col" style="width:78%">