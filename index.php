<?php
include 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Mengambil data ringkasan untuk dashboard
$total_transaksi = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM keuangan"));
$total_aduan = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM pengaduan"));

// 1. Hitung Total Warga & Rincian Pria/Wanita
$q_warga = mysqli_query($conn, "SELECT id FROM warga");
$total_warga = mysqli_num_rows($q_warga);

$q_pria = mysqli_query($conn, "SELECT id FROM warga WHERE jenis_kelamin = 'L'");
$total_pria = mysqli_num_rows($q_pria);

$q_wanita = mysqli_query($conn, "SELECT id FROM warga WHERE jenis_kelamin = 'P'");
$total_wanita = mysqli_num_rows($q_wanita);

// 2. Hitung Kategori Umur (SINKRONISASI DIPERBAIKI)
// Balita (0 - 5 Tahun) -> Angka 0 dimasukkan agar bayi hitungan bulan ikut terhitung
$q_balita = mysqli_query($conn, "SELECT id FROM warga WHERE TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 0 AND 5");
$total_balita = mysqli_num_rows($q_balita);

// Anak-anak (6 - 11 Tahun)
$q_anak = mysqli_query($conn, "SELECT id FROM warga WHERE TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 6 AND 11");
$total_anak = mysqli_num_rows($q_anak);

// Remaja (12 - 17 Tahun)
$q_remaja = mysqli_query($conn, "SELECT id FROM warga WHERE TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 12 AND 17");
$total_remaja = mysqli_num_rows($q_remaja);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Informasi RT 31</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome untuk Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <!-- Container HP (Mobile Frame) -->
    <div class="w-full max-w-md bg-white min-h-screen shadow-xl flex flex-col justify-between pb-20">
        
        <div>
            <!-- Header Biru -->
            <div class="bg-blue-900 text-white p-4 rounded-b-3xl shadow-md">
                <div class="flex justify-between items-center">
                    
                    <!-- Bagian Kiri: Logo dan Judul -->
                    <div class="flex items-center space-x-3">
                        <!-- Menampilkan Logo RT -->
                        <img src="image.jpeg" alt="Logo RT 31" class="w-12 h-12 rounded-full object-cover border-1 border-white shadow-sm bg-white shrink-0">
                        
                        <!-- Teks Judul -->
                        <div>
                            <h1 class="font-bold text-base leading-tight">SYSTEM INFORMASI RT 31</h1>
                            <p class="text-[8px] text-blue-200 tracking-wider mt-0.5">AMAN, BERSIH, MODERN, TRANSPARAN, EFISIEN</p>
                        </div>
                    </div>
                    
                    <!-- Bagian Kanan: Lonceng dan Power -->
                    <div class="flex items-center space-x-3 shrink-0">
                         <!-- Icon Notifikasi (Lonceng) Terhubung ke Aduan -->
                        <a href="aduan.php" class="relative block transition hover:opacity-80">
                            <i class="fa-regular fa-bell text-xl text-white"></i>
                            <!-- Logika PHP: Angka merah -->
                            <?php if($total_aduan > 0): ?>
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold shadow-sm">
                                    <?= $total_aduan; ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <!-- Icon Logout (Power) Terhubung ke logout.php -->
                        <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');" class="bg-white p-1.5 w-8 h-8 flex items-center justify-center rounded-full shadow-md hover:bg-gray-100 transition cursor-pointer">
                            <i class="fa-solid fa-power-off text-red-500 text-lg"></i>
                        </a>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="mt-5">
                    <input type="text" placeholder="Cari data atau menu..." class="w-full py-2.5 px-4 rounded-xl text-gray-700 text-sm focus:outline-none shadow">
                </div>
            </div>

            <!-- Menu Grid Utama -->
            <div class="grid grid-cols-4 gap-4 px-4 py-6 text-center">
                <!-- Warga -->
                <a href="warga.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-users"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Warga</span>
                </a>
                <!-- Kelahiran -->
                <a href="kelahiran.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-venus-mars"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Kelahiran</span>
                </a>
                <!-- Kematian -->
                <a href="kematian.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-heart-crack"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Kematian</span>
                </a>
                <!-- Pindah Masuk -->
                <a href="pindah_masuk.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-right-to-bracket"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Pindah Masuk</span>
                </a>
                <!-- Pindah Keluar -->
                <a href="pindah_keluar.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-right-from-bracket"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Pindah Keluar</span>
                </a>
                <!-- Aduan -->
                <a href="aduan.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-comments"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Aduan</span>
                </a>
                <!-- Surat -->
                <a href="surat.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-file-lines"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Surat</span>
                </a>
                <!-- Keuangan -->
                <a href="keuangan.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-wallet"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Keuangan</span>
                </a>
                <!-- Sumbangan -->
                <a href="sumbangan.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-gift"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Sumbangan</span>
                </a>
                <!-- Inventaris -->
                <a href="inventaris.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Inventaris</span>
                </a>
                <!-- Profil Saya -->
                <a href="profil.php" class="flex flex-col items-center space-y-1 group">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center shadow-sm text-lg group-hover:bg-blue-600 group-hover:text-white transition"><i class="fa-solid fa-id-card"></i></div>
                    <span class="text-xs text-gray-700 font-medium">Profil Saya</span>
                </a>
            </div>

            <!-- Rekap Ringkasan RT -->
            <div class="px-4 mt-2">
                <h2 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-blue-900"></i> Rekap Ringkasan RT
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    
                    <!-- Card 1: Total Warga (Dengan Kategori Umur) -->
                    <div class="bg-blue-600 text-white p-4 rounded-2xl shadow-md relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-[11px] uppercase tracking-wider text-blue-100 font-medium mb-1">Total Warga</p>
                            <h3 class="text-2xl font-extrabold mb-2"><?php echo $total_warga; ?> <span class="text-sm font-normal text-blue-200">Orang</span></h3>
                            
                            <div class="space-y-1.5">
                                <!-- Rincian Pria & Wanita -->
                                <div class="flex items-center gap-2 text-[10px] bg-blue-700 bg-opacity-40 w-fit px-2 py-1 rounded-md border border-blue-500">
                                    <span class="flex items-center gap-1"><i class="fa-solid fa-mars text-blue-300"></i> Pria: <?php echo $total_pria; ?></span>
                                    <span class="w-px h-2.5 bg-blue-400"></span>
                                    <span class="flex items-center gap-1"><i class="fa-solid fa-venus text-pink-300"></i> Wnt: <?php echo $total_wanita; ?></span>
                                </div>
                                
                                <!-- Rincian Kategori Umur Anak -->
                                <div class="flex items-center justify-between text-[8px] bg-blue-700 bg-opacity-40 w-full px-1.5 py-1 rounded-md border border-blue-500">
                                    <span title="Usia 0-5 Tahun">Balita: <b><?php echo $total_balita; ?></b></span>
                                    <span class="w-px h-2.5 bg-blue-400"></span>
                                    <span title="Usia 6-11 Tahun">Anak: <b><?php echo $total_anak; ?></b></span>
                                    <span class="w-px h-2.5 bg-blue-400"></span>
                                    <span title="Usia 12-17 Tahun">Remaja: <b><?php echo $total_remaja; ?></b></span>
                                </div>
                            </div>
                        </div>
                        <!-- Background Icon Transparan -->
                        <i class="fa-solid fa-users absolute -bottom-3 -right-3 text-6xl text-blue-500 opacity-30 z-0"></i>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-teal-500 text-white p-4 rounded-2xl shadow-md">
                        <p class="text-[11px] uppercase tracking-wider text-teal-100 font-medium">Transaksi Beres</p>
                        <h3 class="text-xl font-extrabold mt-1"><?php echo $total_transaksi; ?> Data</h3>
                    </div>
                    
                    <!-- Card 3 -->
                    <div class="bg-orange-500 text-white p-4 rounded-2xl shadow-md">
                        <p class="text-[11px] uppercase tracking-wider text-orange-100 font-medium">Aduan Masuk</p>
                        <h3 class="text-xl font-extrabold mt-1"><?php echo $total_aduan; ?> Kasus</h3>
                    </div>
                    
                    <!-- Card 4 -->
                    <div class="bg-slate-700 text-white p-4 rounded-2xl shadow-md">
                        <p class="text-[11px] uppercase tracking-wider text-slate-300 font-medium">Status Sistem</p>
                        <h3 class="text-lg font-extrabold mt-1">Aktif RT</h3>
                    </div>
                    
                </div>
            </div>
        </div>

        <!-- Bottom Navigation Bar (Fixed di Bawah Frame HP) -->
        <div class="fixed bottom-0 w-full max-w-md bg-white border-t border-gray-200 flex justify-around py-3 text-gray-500 text-xs shadow-lg z-50">
            <a href="index.php" class="flex flex-col items-center text-blue-600">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="mt-1 font-medium">Home</span>
            </a>
            <a href="warga.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-users text-lg"></i>
                <span class="mt-1">Warga</span>
            </a>
            <a href="aduan.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-comments text-lg"></i>
                <span class="mt-1">Aduan</span>
            </a>
            <a href="keuangan.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-wallet text-lg"></i>
                <span class="mt-1">Keuangan</span>
            </a>
        </div>

    </div>

</body>
</html>