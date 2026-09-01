<?php
include 'config.php';
$query = mysqli_query($conn, "SELECT * FROM kematian ORDER BY tanggal_wafat DESC") or die(mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kematian - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Data Kematian</h1>
        </div>

        <!-- Tombol Tambah -->
        <div class="p-4">
            <?php if(in_array($_SESSION['role'], ['ketua rt', 'sekretaris'])): ?>
            <a href="tambah_kematian.php" class="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-3 rounded-xl flex justify-center items-center gap-2 shadow-md transition">
                <i class="fa-solid fa-plus"></i> Tambah Data Kematian
            </a>
            <?php endif; ?>
        </div>

        <!-- List Data Kematian -->
        <div class="px-4 space-y-3">
            <?php while($row = mysqli_fetch_assoc($query)) : ?>
            <div class="bg-white border border-gray-200 p-3 rounded-xl shadow-sm flex flex-col gap-2">
                
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-100 text-slate-600 rounded-full flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-heart-crack"></i>
                    </div>
                    <div class="w-full">
                        <h3 class="font-bold text-gray-800 text-sm">Alm/Almh. <?= $row['nama_almarhum']; ?></h3>
                        <p class="text-[11px] text-gray-500">Wafat: <?= date('d M Y', strtotime($row['tanggal_wafat'])); ?></p>
                        <p class="text-[10px] text-gray-400">Pelapor: <?= $row['nama_pelapor']; ?> (<?= $row['hubungan_pelapor']; ?>)</p>
                    </div>
                </div>

                <!-- Tombol Cetak Surat Kematian PDF -->
                <div class="border-t border-gray-100 pt-2 mt-1 text-right">
                    <a href="export_surat_kematian.php?id=<?= $row['id']; ?>" class="inline-block bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold py-1.5 px-3 rounded-md shadow-sm transition">
                        <i class="fa-solid fa-file-pdf"></i> Cetak Surat PDF
                    </a>
                </div>

            </div>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($query) == 0): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-400 text-sm">Belum ada data kematian.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>