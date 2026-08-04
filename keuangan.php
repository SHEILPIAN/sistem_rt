<?php
include 'config.php';

// Menghitung Saldo Kas
$q_masuk = mysqli_query($conn, "SELECT SUM(nominal) as total_masuk FROM keuangan WHERE jenis='Masuk'");
$d_masuk = mysqli_fetch_assoc($q_masuk);
$tot_masuk = $d_masuk['total_masuk'] ? $d_masuk['total_masuk'] : 0;

$q_keluar = mysqli_query($conn, "SELECT SUM(nominal) as total_keluar FROM keuangan WHERE jenis='Keluar'");
$d_keluar = mysqli_fetch_assoc($q_keluar);
$tot_keluar = $d_keluar['total_keluar'] ? $d_keluar['total_keluar'] : 0;

$saldo = $tot_masuk - $tot_keluar;

// Mengambil Data Transaksi
$query = mysqli_query($conn, "SELECT * FROM keuangan ORDER BY tanggal DESC, id DESC") or die(mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keuangan - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Kas & Keuangan</h1>
        </div>

        <!-- Card Saldo -->
        <div class="p-4">
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
                <i class="fa-solid fa-wallet absolute -right-4 -bottom-4 text-7xl text-white opacity-20"></i>
                <p class="text-xs text-blue-200 font-medium tracking-wide">TOTAL SALDO KAS RT</p>
                <h2 class="text-3xl font-extrabold mt-1">Rp <?= number_format($saldo, 0, ',', '.'); ?></h2>
                <div class="flex gap-4 mt-4 text-[11px]">
                    <div>
                        <p class="text-blue-200">Total Masuk</p>
                        <p class="font-bold text-green-300">+ Rp <?= number_format($tot_masuk, 0, ',', '.'); ?></p>
                    </div>
                    <div>
                        <p class="text-blue-200">Total Keluar</p>
                        <p class="font-bold text-red-300">- Rp <?= number_format($tot_keluar, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Tambah Transaksi -->
        <div class="px-4 pb-2">
            <a href="tambah_keuangan.php" class="w-full bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 font-bold py-3 rounded-xl flex justify-center items-center gap-2 shadow-sm transition">
                <i class="fa-solid fa-plus"></i> Catat Transaksi
            </a>
        </div>

        <!-- List Data Keuangan -->
        <!-- List Data Keuangan -->
        <div class="px-4 mt-3 space-y-3">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-bold text-sm text-gray-700">Riwayat Transaksi</h3>
                
                <!-- Tombol Export Excel (Hanya untuk Admin) -->
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <a href="export_keuangan.php" class="bg-green-600 hover:bg-green-700 text-white text-[10px] font-bold py-1.5 px-3 rounded-lg shadow-sm flex items-center gap-1 transition">
                    <i class="fa-solid fa-file-excel"></i> Export Laporan
                </a>
                <?php endif; ?>
            </div>
            
            <?php while($row = mysqli_fetch_assoc($query)) : ?>
            <div class="bg-white border border-gray-200 p-3 rounded-xl shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <?php if($row['jenis'] == 'Masuk'): ?>
                        <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-lg">
                            <i class="fa-solid fa-arrow-down"></i>
                        </div>
                    <?php else: ?>
                        <div class="w-10 h-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-lg">
                            <i class="fa-solid fa-arrow-up"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm"><?= $row['keterangan']; ?></h3>
                        <p class="text-[10px] text-gray-500"><?= date('d M Y', strtotime($row['tanggal'])); ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <?php if($row['jenis'] == 'Masuk'): ?>
                        <p class="font-bold text-green-600 text-sm">+ Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></p>
                    <?php else: ?>
                        <p class="font-bold text-red-600 text-sm">- Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($query) == 0): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-receipt text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-400 text-sm">Belum ada catatan transaksi.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bottom Navigation Bar -->
        <div class="fixed bottom-0 w-full max-w-md bg-white border-t border-gray-200 flex justify-around py-3 text-gray-500 text-xs shadow-lg z-50">
            <a href="index.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="mt-1">Home</span>
            </a>
            <a href="warga.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-users text-lg"></i>
                <span class="mt-1">Warga</span>
            </a>
            <a href="aduan.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-comments text-lg"></i>
                <span class="mt-1">Aduan</span>
            </a>
            <a href="keuangan.php" class="flex flex-col items-center text-blue-600">
                <i class="fa-solid fa-wallet text-lg"></i>
                <span class="mt-1 font-medium">Keuangan</span>
            </a>
        </div>
    </div>
</body>
</html>