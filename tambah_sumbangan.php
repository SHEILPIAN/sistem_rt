<?php
include 'config.php';

// Proses Simpan Data
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama_donatur'];
    $tanggal = $_POST['tanggal'];
    $bentuk = $_POST['bentuk_sumbangan'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    
    $insert = mysqli_query($conn, "INSERT INTO sumbangan (nama_donatur, tanggal, bentuk_sumbangan, jumlah, keterangan) VALUES ('$nama', '$tanggal', '$bentuk', '$jumlah', '$keterangan')");

    if ($insert) {
        echo "<script>alert('Data Sumbangan Berhasil Dicatat!'); window.location='sumbangan.php';</script>";
    } else {
        echo "<script>alert('Gagal mencatat sumbangan!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catat Sumbangan - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="sumbangan.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Input Sumbangan</h1>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Donatur</label>
                    <input type="text" name="nama_donatur" required placeholder="Hamba Allah / Nama Warga" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-cyan-500 focus:border-cyan-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" required value="<?= date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-cyan-500 focus:border-cyan-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bentuk Sumbangan</label>
                    <select name="bentuk_sumbangan" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-cyan-500 focus:border-cyan-500 text-sm">
                        <option value="Uang Tunai">Uang Tunai</option>
                        <option value="Barang / Sembako">Barang / Sembako</option>
                        <option value="Material Bangunan">Material Bangunan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah / Nominal</label>
                    <input type="text" name="jumlah" required placeholder="Contoh: Rp 500.000 atau 10 Dus Mie" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-cyan-500 focus:border-cyan-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Peruntukan</label>
                    <textarea name="keterangan" rows="2" placeholder="Contoh: Untuk konsumsi kerja bakti" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-cyan-500 focus:border-cyan-500 text-sm"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" name="simpan" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        Simpan Sumbangan
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>