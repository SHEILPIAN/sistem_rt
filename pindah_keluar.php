<?php
include 'config.php';
$query = mysqli_query($conn, "SELECT * FROM pindah_keluar ORDER BY tanggal_pindah DESC") or die(mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pindah Keluar - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Data Pindah Keluar</h1>
        </div>

        <!-- Tombol Tambah -->
        <div class="p-4">
            <a href="tambah_pindah_keluar.php" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-xl flex justify-center items-center gap-2 shadow-md transition">
                <i class="fa-solid fa-plus"></i> Tambah Warga Keluar
            </a>
        </div>

        <!-- List Data Pindah Keluar -->
        <div class="px-4 space-y-3">
            <?php while($row = mysqli_fetch_assoc($query)) : ?>
            <div class="bg-white border border-gray-200 p-3 rounded-xl shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-lg">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm"><?= $row['nama']; ?></h3>
                        <p class="text-[11px] text-gray-500">Tgl Keluar: <?= date('d M Y', strtotime($row['tanggal_pindah'])); ?> • NIK: <?= $row['nik']; ?></p>
                        <p class="text-[10px] text-gray-400">Tujuan: <?= $row['tujuan_alamat']; ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($query) == 0): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-400 text-sm">Belum ada data pindah keluar.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>