<?php
include 'config.php';
if ($_SESSION['role'] == 'warga') {
    $nama_user = mysqli_real_escape_string($conn, $_SESSION['nama_lengkap']);
    $query = mysqli_query($conn, "SELECT * FROM pengaduan WHERE pelapor = '$nama_user' ORDER BY tanggal DESC") or die(mysqli_error($conn));
} else {
    $query = mysqli_query($conn, "SELECT * FROM pengaduan ORDER BY tanggal DESC") or die(mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aduan Warga - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Aduan Warga</h1>
        </div>

        <!-- Tombol Tambah Aduan -->
        <div class="p-4">
            <a href="tambah_aduan.php" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl flex justify-center items-center gap-2 shadow-md transition">
                <i class="fa-solid fa-bullhorn"></i> Tulis Laporan/Aduan
            </a>
        </div>

        <!-- List Data Aduan -->
        <div class="px-4 space-y-3">
            <?php while($row = mysqli_fetch_assoc($query)) : ?>
            <div class="bg-white border border-gray-200 p-4 rounded-xl shadow-sm">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-sm">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm"><?= $row['pelapor']; ?></h3>
                            <p class="text-[10px] text-gray-500"><?= date('d M Y, H:i', strtotime($row['tanggal'])); ?></p>
                        </div>
                    </div>
                    <?php if($row['status_aduan'] == 'Selesai'): ?>
                        <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-solid fa-check"></i> Selesai</span>
                    <?php else: ?>
                        <span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-solid fa-spinner"></i> Proses</span>
                    <?php endif; ?>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 mt-2">
                    <p class="text-sm text-gray-700">"<?= $row['isi_aduan']; ?>"</p>
                </div>
                <?php if(in_array($_SESSION['role'], ['ketua rt', 'sekretaris']) && $row['status_aduan'] == 'Proses'): ?>
                <div class="mt-3 flex justify-end">
                    <a href="update_aduan.php?id=<?= $row['id']; ?>&status=Selesai" onclick="return confirm('Tandai aduan ini sudah diselesaikan?')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded-md text-xs font-bold shadow-sm transition flex items-center gap-1"><i class="fa-solid fa-check-double"></i> Selesaikan Aduan</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($query) == 0): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-comments text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-400 text-sm">Belum ada aduan masuk.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Masukkan Bottom Navigation Bar Di Sini (Sama seperti di atas) -->
        <div class="fixed bottom-0 w-full max-w-md bg-white border-t border-gray-200 flex justify-around py-3 text-gray-500 text-xs shadow-lg z-50">
            <a href="index.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="mt-1">Home</span>
            </a>
            <a href="warga.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-users text-lg"></i>
                <span class="mt-1">Warga</span>
            </a>
            <a href="aduan.php" class="flex flex-col items-center text-blue-600">
                <i class="fa-solid fa-comments text-lg"></i>
                <span class="mt-1 font-medium">Aduan</span>
            </a>
            <a href="keuangan.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-wallet text-lg"></i>
                <span class="mt-1">Keuangan</span>
            </a>
        </div>
    </div>
</body>
</html>