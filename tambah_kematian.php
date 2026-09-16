<?php
include 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Proses Simpan Data
if (isset($_POST['simpan'])) {
    $nomor_surat      = mysqli_real_escape_string($conn, $_POST['nomor_surat']);
    $nama             = mysqli_real_escape_string($conn, $_POST['nama_almarhum']);
    $tempat_lahir     = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir    = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $jk               = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $kewarganegaraan  = mysqli_real_escape_string($conn, $_POST['kewarganegaraan']);
    $agama            = mysqli_real_escape_string($conn, $_POST['agama']);
    $status_perkawinan= mysqli_real_escape_string($conn, $_POST['status_perkawinan']);
    $pekerjaan        = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $alamat           = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    $hari_wafat       = mysqli_real_escape_string($conn, $_POST['hari_wafat']);
    $tanggal_wafat    = mysqli_real_escape_string($conn, $_POST['tanggal_wafat']);
    $tempat_kematian  = mysqli_real_escape_string($conn, $_POST['tempat_kematian']);
    $sebab_kematian   = mysqli_real_escape_string($conn, $_POST['sebab_kematian']);
    
    $nama_pelapor     = mysqli_real_escape_string($conn, $_POST['nama_pelapor']);
    $hubungan_pelapor = mysqli_real_escape_string($conn, $_POST['hubungan_pelapor']);

    try {
        $insert = mysqli_query($conn, "INSERT INTO kematian (
            nomor_surat, nama_almarhum, tempat_lahir, tanggal_lahir, jenis_kelamin, 
            kewarganegaraan, agama, status_perkawinan, pekerjaan, alamat, 
            hari_wafat, tanggal_wafat, tempat_kematian, sebab_kematian, 
            nama_pelapor, hubungan_pelapor
        ) VALUES (
            '$nomor_surat', '$nama', '$tempat_lahir', '$tanggal_lahir', '$jk', 
            '$kewarganegaraan', '$agama', '$status_perkawinan', '$pekerjaan', '$alamat', 
            '$hari_wafat', '$tanggal_wafat', '$tempat_kematian', '$sebab_kematian', 
            '$nama_pelapor', '$hubungan_pelapor'
        )");

        if ($insert) {
            echo "<script>alert('Data Kematian Berhasil Ditambahkan!'); window.location='kematian.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan data!');</script>";
        }
    } catch (Exception $e) {
        $error_msg = addslashes($e->getMessage());
        echo "<script>alert('DB Error: $error_msg');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Kematian - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl pb-10">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="kematian.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Input Data Kematian</h1>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" class="space-y-4">
                
                <!-- Nomor Surat -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Surat Keterangan</label>
                    <input type="text" name="nomor_surat" placeholder="Contoh: 001/RT.31/09/2026" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                </div>

                <hr class="my-3 border-gray-200">
                <p class="text-xs font-bold text-blue-900 uppercase tracking-wider">Biodata Almarhum / Almarhumah</p>

                <!-- Nama -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_almarhum" required placeholder="Masukkan nama..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                </div>

                <!-- Tempat & Tanggal Lahir -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" required placeholder="Kota lahir..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                    </div>
                </div>

                <!-- Jenis Kelamin & Kewarganegaraan -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                        <select name="jenis_kelamin" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                            <option value="Laki-Laki">Laki-Laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kewarganegaraan</label>
                        <input type="text" name="kewarganegaraan" value="WNI" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                    </div>
                </div>

                <!-- Agama & Status Perkawinan -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Agama</label>
                        <select name="agama" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                            <option value="Islam">Islam</option>
                            <option value="Kristen">Kristen</option>
                            <option value="Katolik">Katolik</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddha">Buddha</option>
                            <option value="Khonghucu">Khonghucu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Perkawinan</label>
                        <select name="status_perkawinan" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                            <option value="Kawin">Kawin</option>
                            <option value="Belum Kawin">Belum Kawin</option>
                            <option value="Cerai Hidup">Cerai Hidup</option>
                            <option value="Cerai Mati">Cerai Mati</option>
                        </select>
                    </div>
                </div>

                <!-- Pekerjaan -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pekerjaan</label>
                    <input type="text" name="pekerjaan" required placeholder="Contoh: Karyawan Swasta / Ibu Rumah Tangga" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                </div>

                <!-- Alamat -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Duka / Domicil</label>
                    <textarea name="alamat" rows="2" required placeholder="Alamat lengkap..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm"></textarea>
                </div>

                <hr class="my-3 border-gray-200">
                <p class="text-xs font-bold text-blue-900 uppercase tracking-wider">Keterangan Meninggal Dunia</p>

                <!-- Hari & Tanggal Wafat -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hari Wafat</label>
                        <select name="hari_wafat" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Wafat</label>
                        <input type="date" name="tanggal_wafat" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                    </div>
                </div>

                <!-- Tempat & Sebab Kematian -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tempat Kematian</label>
                    <input type="text" name="tempat_kematian" required placeholder="Contoh: Rumah Duka / RSUD Bekasi" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sebab Kematian</label>
                    <input type="text" name="sebab_kematian" required placeholder="Contoh: Sakit / Usia Lanjut" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                </div>

                <hr class="my-3 border-gray-200">
                <p class="text-xs font-bold text-blue-900 uppercase tracking-wider">Data Pelapor</p>

                <!-- Nama Pelapor & Hubungan -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pelapor</label>
                        <input type="text" name="nama_pelapor" required placeholder="Nama pelapor..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hubungan Pelapor</label>
                        <input type="text" name="hubungan_pelapor" required placeholder="Anak / Istri / Suami" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-slate-500 focus:border-slate-500 text-sm">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" name="simpan" class="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        Simpan & Buat Data Surat
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>