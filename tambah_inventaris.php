<?php
include 'config.php';

// Proses Simpan Data
if (isset($_POST['simpan'])) {
    $kode = $_POST['kode_barang'];
    $nama = $_POST['nama_barang'];
    $kondisi = $_POST['kondisi'];
    $jumlah = $_POST['jumlah'];
    $tanggal = $_POST['tanggal_masuk'];
    $keterangan = $_POST['keterangan'];
    
    $insert = mysqli_query($conn, "INSERT INTO inventaris (kode_barang, nama_barang, kondisi, jumlah, tanggal_masuk, keterangan) VALUES ('$kode', '$nama', '$kondisi', '$jumlah', '$tanggal', '$keterangan')");

    if ($insert) {
        echo "<script>alert('Data Inventaris Berhasil Ditambahkan!'); window.location='inventaris.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Inventaris - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="inventaris.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Input Barang RT</h1>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Barang (Opsional)</label>
                    <input type="text" name="kode_barang" placeholder="Contoh: INV-001" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                    <input type="text" name="nama_barang" required placeholder="Contoh: Tenda Hajatan, Kursi Lipat" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                        <input type="number" name="jumlah" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" required value="<?= date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi Barang</label>
                    <select name="kondisi" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                        <option value="Baik">Baik</option>
                        <option value="Rusak Ringan">Rusak Ringan</option>
                        <option value="Rusak Berat">Rusak Berat</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Lokasi/Lainnya</label>
                    <textarea name="keterangan" rows="2" placeholder="Disimpan di gudang posko..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" name="simpan" class="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        Simpan Data Inventaris
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>