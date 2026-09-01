<?php
include 'config.php';
if ($_SESSION['role'] == 'warga') {
    $nama_user = mysqli_real_escape_string($conn, $_SESSION['nama_lengkap']);
    $query = mysqli_query($conn, "SELECT * FROM surat WHERE nama_pemohon = '$nama_user' ORDER BY tanggal_request DESC") or die(mysqli_error($conn));
} else {
    $query = mysqli_query($conn, "SELECT * FROM surat ORDER BY tanggal_request DESC") or die(mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Surat - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Layanan Surat</h1>
        </div>

        <!-- Tombol Tambah Request Surat -->
        <div class="p-4">
            <a href="tambah_surat.php" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl flex justify-center items-center gap-2 shadow-md transition">
                <i class="fa-solid fa-envelope-open-text"></i> Ajukan Surat Pengantar
            </a>
        </div>

        <!-- List Data Surat -->
        <div class="px-4 space-y-3">
            <?php while($row = mysqli_fetch_assoc($query)) : ?>
            <div class="bg-white border border-gray-200 p-4 rounded-xl shadow-sm">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-sm">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm"><?= $row['jenis_surat']; ?></h3>
                            <p class="text-[11px] text-gray-500"><?= $row['nama_pemohon']; ?> (NIK: <?= $row['nik_pemohon']; ?>)</p>
                        </div>
                    </div>
                    <?php if($row['status_surat'] == 'Selesai'): ?>
                        <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-full whitespace-nowrap"><i class="fa-solid fa-check-double"></i> Selesai</span>
                    <?php elseif($row['status_surat'] == 'Ditolak'): ?>
                        <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-1 rounded-full whitespace-nowrap"><i class="fa-solid fa-ban"></i> Ditolak</span>
                    <?php else: ?>
                        <span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-1 rounded-full whitespace-nowrap"><i class="fa-solid fa-clock rotate-180"></i> Menunggu</span>
                    <?php endif; ?>
                </div>
                <div class="bg-gray-50 p-2 rounded border border-gray-100 mt-2">
                    <p class="text-xs text-gray-600"><strong>Keperluan:</strong> <?= $row['keperluan']; ?></p>
                </div>
                <div class="flex justify-between items-end mt-2">
                    <?php if(in_array($_SESSION['role'], ['ketua rt', 'sekretaris']) && $row['status_surat'] == 'Menunggu'): ?>
                    <div class="flex gap-2 mt-2">
                        <a href="update_surat.php?id=<?= $row['id']; ?>&status=Selesai" onclick="return confirm('Tandai pengajuan ini sebagai Selesai?')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-[10px] font-bold shadow-sm transition flex items-center gap-1"><i class="fa-solid fa-check"></i> Selesai</a>
                        <a href="update_surat.php?id=<?= $row['id']; ?>&status=Ditolak" onclick="return confirm('Tolak pengajuan surat ini?')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-[10px] font-bold shadow-sm transition flex items-center gap-1"><i class="fa-solid fa-xmark"></i> Tolak</a>
                    </div>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    <p class="text-[9px] text-gray-400 text-right mt-2"><?= date('d M Y, H:i', strtotime($row['tanggal_request'])); ?></p>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($query) == 0): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-file-circle-xmark text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-400 text-sm">Belum ada pengajuan surat.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>