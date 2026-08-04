<?php
include 'config.php';

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id_user'");
$user = mysqli_fetch_assoc($query);

// Menentukan label role
$role_label = ($user['role'] == 'admin') ? 'Administrator / Pengurus RT' : 'Warga RT 5';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Akun - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header Biru Profil -->
        <div class="bg-[#2f3e83] text-white p-4 rounded-b-3xl shadow-md text-center pb-8 pt-6">
            <div class="flex items-center absolute top-4 left-4">
                <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
                <span class="ml-3 font-bold text-lg">Profil Akun</span>
            </div>
            
            <div class="mt-8">
                <div class="w-24 h-24 bg-blue-100 rounded-full mx-auto flex items-center justify-center border-4 border-white shadow-lg text-[#2f3e83] text-4xl">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <h2 class="font-bold text-2xl mt-3"><?= $user['nama_lengkap']; ?></h2>
                <p class="text-sm text-blue-200"><?= $role_label; ?></p>
                
                <div class="inline-flex items-center bg-[#24316b] text-blue-100 px-4 py-1.5 rounded-full text-xs mt-3 border border-[#3b4c9b]">
                    <i class="fa-regular fa-id-card mr-2"></i> NIK: 3604XXXXXXXXXXXXX
                </div>
            </div>
        </div>

        <!-- Menu Pengaturan Akun -->
        <div class="px-5 mt-6">
            <p class="text-xs font-bold text-gray-500 mb-3 tracking-wider">PENGATURAN AKUN</p>
            
            <div class="space-y-3">
                <!-- Edit Data Diri -->
                <a href="edit_profil.php" class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-xl shadow-sm hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                            <i class="fa-solid fa-user-pen"></i>
                        </div>
                        <span class="font-bold text-gray-700 text-sm">Edit Data Diri</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-gray-400 text-sm"></i>
                </a>

                <!-- Keamanan & Password -->
                <a href="ubah_password.php" class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-xl shadow-sm hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <span class="font-bold text-gray-700 text-sm">Keamanan & Password</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-gray-400 text-sm"></i>
                </a>

                <!-- Bantuan Aplikasi -->
                <a href="bantuan.php" class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-xl shadow-sm hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                            <i class="fa-solid fa-circle-question"></i>
                        </div>
                        <span class="font-bold text-gray-700 text-sm">Bantuan Aplikasi</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-gray-400 text-sm"></i>
                </a>
            </div>

            <!-- Tombol Keluar -->
            <div class="mt-6">
                <a href="logout.php" onclick="return confirm('Yakin ingin keluar dari akun?')" class="flex items-center justify-center gap-2 p-4 bg-[#fdf2f2] border border-red-100 rounded-xl shadow-sm hover:bg-red-50 transition">
                    <i class="fa-solid fa-arrow-right-from-bracket text-[#c81e1e]"></i>
                    <span class="font-bold text-sm text-[#c81e1e]">Keluar (Logout)</span>
                </a>
            </div>
        </div>

    </div>
</body>
</html>