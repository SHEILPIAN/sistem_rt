<?php
include 'config.php';
$query = mysqli_query($conn, "SELECT * FROM inventaris ORDER BY tanggal_masuk DESC") or die(mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaris - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Inventaris RT</h1>
        </div>

        <!-- Tombol Tambah Barang -->
        <div class="p-4">
            <?php if(in_array($_SESSION['role'], ['ketua rt', 'sekretaris'])): ?>
            <a href="tambah_inventaris.php" class="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-3 rounded-xl flex justify-center items-center gap-2 shadow-md transition">
                <i class="fa-solid fa-plus"></i> Tambah Data Barang
            </a>
            <?php endif; ?>
        </div>

        <!-- List Data Inventaris -->
        <div class="px-4 space-y-3">
            <?php while($row = mysqli_fetch_assoc($query)) : ?>
            <div class="bg-white border border-gray-200 p-3 rounded-xl shadow-sm flex items-start gap-3">
                <div class="w-10 h-10 bg-slate-100 text-slate-700 rounded-lg flex items-center justify-center text-lg shrink-0 mt-1">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <div class="w-full">
                    <div class="flex justify-between items-start mb-1">
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm"><?= $row['nama_barang']; ?></h3>
                            <p class="text-[10px] text-gray-500">Kode: <?= $row['kode_barang']; ?> • Masuk: <?= date('d M Y', strtotime($row['tanggal_masuk'])); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-extrabold text-slate-700"><?= $row['jumlah']; ?></span>
                            <span class="text-[10px] text-gray-500 block -mt-1">Unit</span>
                        </div>
                    </div>
                    
                    <div class="mt-2 flex items-center gap-2">
                        <?php if($row['kondisi'] == 'Baik'): ?>
                            <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded"><i class="fa-solid fa-check-circle"></i> Baik</span>
                        <?php elseif($row['kondisi'] == 'Rusak Ringan'): ?>
                            <span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-0.5 rounded"><i class="fa-solid fa-triangle-exclamation"></i> Rusak Ringan</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded"><i class="fa-solid fa-xmark-circle"></i> Rusak Berat</span>
                        <?php endif; ?>
                        
                        <?php if($row['keterangan']): ?>
                            <span class="text-[10px] text-gray-400 italic">"<?= $row['keterangan']; ?>"</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($query) == 0): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-boxes-stacked text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-400 text-sm">Belum ada data inventaris.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>