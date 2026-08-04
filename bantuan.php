<?php
include 'config.php';
if (!isset($_SESSION['status_login'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan Aplikasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex justify-center">
    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        <div class="bg-[#2f3e83] text-white p-4 flex items-center gap-3">
            <a href="profil.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Bantuan Aplikasi</h1>
        </div>
        <div class="p-5 space-y-4">
            <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl">
                <h3 class="font-bold text-blue-900 mb-1"><i class="fa-solid fa-headset"></i> Kontak Pengurus RT</h3>
                <p class="text-sm text-gray-700">Jika mengalami kendala terkait data atau aplikasi, silakan hubungi:<br><strong>WhatsApp:</strong> 0812-3456-7890</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl">
                <h3 class="font-bold text-gray-800 mb-1">Versi Aplikasi</h3>
                <p class="text-sm text-gray-600">Sistem Informasi RT 5 - v1.0.0</p>
            </div>
        </div>
    </div>
</body>
</html>