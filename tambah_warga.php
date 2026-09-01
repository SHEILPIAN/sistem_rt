<?php
include 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Tolak akses jika yang masuk bukan admin
if (!in_array($_SESSION['role'], ['ketua rt', 'sekretaris'])) {
    echo "<script>alert('Akses Ditolak! Hanya Pengurus RT yang bisa menambah data ini.'); window.location='warga.php';</script>";
    exit;
}

// Proses Simpan Data
if (isset($_POST['simpan'])) {
    $nik = $_POST['nik'];
    $nama = $_POST['nama'];
    $tgl_lahir = $_POST['tanggal_lahir']; // <-- MENANGKAP DATA TANGGAL LAHIR
    $jk = $_POST['jenis_kelamin'];
    $alamat = $_POST['alamat_rt'];
    $status = $_POST['status_warga'];

    // Proses Upload KTP
    $foto_ktp = $_FILES['foto_ktp']['name'];
    $tmp_ktp = $_FILES['foto_ktp']['tmp_name'];
    $ktp_baru = $nik . "_KTP_" . $foto_ktp; // Rename file pakai NIK biar unik
    $path_ktp = "uploads/" . $ktp_baru;

    // Proses Upload KK
    $foto_kk = $_FILES['foto_kk']['name'];
    $tmp_kk = $_FILES['foto_kk']['tmp_name'];
    $kk_baru = $nik . "_KK_" . $foto_kk; // Rename file pakai NIK biar unik
    $path_kk = "uploads/" . $kk_baru;

    // Pindahkan file dari penyimpanan sementara ke folder uploads
    if(move_uploaded_file($tmp_ktp, $path_ktp) && move_uploaded_file($tmp_kk, $path_kk)) {
        
        // Simpan ke database jika upload berhasil (Query diupdate untuk memasukkan tanggal_lahir)
        $insert = mysqli_query($conn, "INSERT INTO warga (nik, nama, tanggal_lahir, jenis_kelamin, alamat_rt, status_warga, foto_ktp, foto_kk) VALUES ('$nik', '$nama', '$tgl_lahir', '$jk', '$alamat', '$status', '$ktp_baru', '$kk_baru')");

        if ($insert) {
            echo "<script>alert('Data Warga & Dokumen Berhasil Ditambahkan!'); window.location='warga.php';</script>";
        } else {
            echo "<script>alert('Data gagal disimpan ke database!');</script>";
        }

    } else {
        echo "<script>alert('Gagal mengunggah foto KTP atau KK! Pastikan folder uploads sudah dibuat.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Warga - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="warga.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Tambah Warga Baru</h1>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK (Nomor Induk Kependudukan)</label>
                    <input type="number" name="nik" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                
                <!-- KOLOM TANGGAL LAHIR BARU -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                    <!-- Menggunakan type="date" agar muncul kalender pop-up di HP/Laptop -->
                    <input type="date" name="tanggal_lahir" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <!-- ========================= -->

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat (Blok / No. Rumah)</label>
                    <input type="text" name="alamat_rt" required placeholder="Contoh: Blok A No. 12" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Warga</label>
                    <select name="status_warga" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="Tetap">Warga Tetap</option>
                        <option value="Kontrak">Warga Kontrak / Kos</option>
                    </select>
                </div>
                
                <!-- Input Upload KTP -->
                <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <label class="block text-sm font-bold text-blue-900 mb-1"><i class="fa-solid fa-id-card"></i> Upload Foto KTP</label>
                    <input type="file" name="foto_ktp" accept="image/*" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-900 file:text-white hover:file:bg-blue-800">
                </div>

                <!-- Input Upload KK -->
                <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <label class="block text-sm font-bold text-blue-900 mb-1"><i class="fa-solid fa-users-viewfinder"></i> Upload Foto KK</label>
                    <input type="file" name="foto_kk" accept="image/*" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-900 file:text-white hover:file:bg-blue-800">
                </div>

                <div class="pt-4 pb-10">
                    <button type="submit" name="simpan" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>