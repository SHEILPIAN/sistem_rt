<?php
include 'config.php';
$query = mysqli_query($conn, "SELECT * FROM sumbangan ORDER BY tanggal DESC, id DESC") or die(mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sumbangan - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Data Sumbangan</h1>
        </div>

        <!-- Tombol Tambah -->
        <div class="p-4">
            <?php if(in_array($_SESSION['role'], ['ketua rt', 'sekretaris'])): ?>
            <a href="tambah_sumbangan.php" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 rounded-xl flex justify-center items-center gap-2 shadow-md transition">
                <i class="fa-solid fa-gift"></i> Catat Sumbangan Baru
            </a>
            <?php endif; ?>
        </div>

        <!-- List Data Sumbangan -->
        <div class="px-4 space-y-3">
            <?php while($row = mysqli_fetch_assoc($query)) : ?>
            <div class="bg-white border border-gray-200 p-3 rounded-xl shadow-sm flex items-start gap-3">
                <div class="w-10 h-10 bg-cyan-100 text-cyan-600 rounded-full flex items-center justify-center text-lg shrink-0 mt-1">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
                <div class="w-full">
                    <div class="flex justify-between items-center mb-1">
                        <h3 class="font-bold text-gray-800 text-sm"><?= $row['nama_donatur']; ?></h3>
                        <span class="text-[10px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded"><?= date('d M Y', strtotime($row['tanggal'])); ?></span>
                    </div>
                    <p class="text-xs font-semibold text-cyan-600 mb-1"><?= $row['jumlah']; ?> (<?= $row['bentuk_sumbangan']; ?>)</p>
                    <p class="text-[11px] text-gray-500 italic">"<?= $row['keterangan']; ?>"</p>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($query) == 0): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-box-open text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-400 text-sm">Belum ada catatan sumbangan.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>