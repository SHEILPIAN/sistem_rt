<?php
include 'config.php';

if (isset($_POST['simpan'])) {
    $tanggal = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $jenis = $_POST['jenis'];
    $nominal = str_replace('.', '', $_POST['nominal']); // Menghilangkan titik jika diinput manual
    
    $insert = mysqli_query($conn, "INSERT INTO keuangan (tanggal, keterangan, jenis, nominal) VALUES ('$tanggal', '$keterangan', '$jenis', '$nominal')");

    if ($insert) {
        echo "<script>alert('Transaksi Berhasil Dicatat!'); window.location='keuangan.php';</script>";
    } else {
        echo "<script>alert('Gagal mencatat transaksi!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catat Transaksi - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="keuangan.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Input Transaksi Kas</h1>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transaksi</label>
                    <input type="date" name="tanggal" required value="<?= date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan (Uraian)</label>
                    <input type="text" name="keterangan" required placeholder="Contoh: Iuran Warga Blok A, Beli Galon Poskamling" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                    <select name="jenis" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="Masuk">Pemasukan (Uang Masuk)</option>
                        <option value="Keluar">Pengeluaran (Uang Keluar)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
                    <input type="number" name="nominal" required placeholder="Contoh: 50000" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <p class="text-[10px] text-gray-400 mt-1">Tulis angka saja tanpa titik/koma.</p>
                </div>

                <div class="pt-4">
                    <button type="submit" name="simpan" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>