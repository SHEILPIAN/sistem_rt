<?php
include 'config.php';
require_once 'iuran_helper.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Tolak akses jika tidak memiliki izin tambah_warga
if (!has_permission('tambah_warga')) {
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
    
    // Status hubungan keluarga
    $hubungan = $_POST['hubungan_keluarga'] ?? '';
    if ($hubungan === 'Janda') {
        $hubungan = $_POST['sub_janda'] ?? 'Janda (Kepala Rumah Tangga)';
    }

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
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }
    
    if(move_uploaded_file($tmp_ktp, $path_ktp) && move_uploaded_file($tmp_kk, $path_kk)) {
        
        // Simpan ke database jika upload berhasil (Query diupdate untuk memasukkan hubungan_keluarga)
        $insert = mysqli_query($conn, "INSERT INTO warga (nik, nama, tanggal_lahir, jenis_kelamin, alamat_rt, status_warga, hubungan_keluarga, foto_ktp, foto_kk) VALUES ('$nik', '$nama', '$tgl_lahir', '$jk', '$alamat', '$status', '$hubungan', '$ktp_baru', '$kk_baru')");

        if ($insert) {
            $warga_id = mysqli_insert_id($conn);
            
            // Jika status adalah Kepala Rumah Tangga, otomatis hubungkan/daftarkan ke Rekap Iuran 2026
            if (is_kepala_keluarga($hubungan)) {
                sync_kepala_keluarga_ke_iuran($conn, $warga_id, $nama, $nik, $alamat, $hubungan, 2026);
            }

            echo "<script>alert('Data Warga & Dokumen Berhasil Ditambahkan!'); window.location='warga.php';</script>";
        } else {
            echo "<script>alert('Data gagal disimpan ke database: " . addslashes(mysqli_error($conn)) . "');</script>";
        }

    } else {
        $error_ktp = $_FILES['foto_ktp']['error'] ?? 'Unknown';
        $error_kk = $_FILES['foto_kk']['error'] ?? 'Unknown';
        echo "<script>alert('Gagal mengunggah foto KTP atau KK! (Kode Error KTP: $error_ktp, KK: $error_kk)');</script>";
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
                    <select name="jenis_kelamin" id="select_jk" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <!-- KOLOM STATUS DALAM KELUARGA -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status dalam Keluarga <span class="text-red-500">*</span></label>
                    <select name="hubungan_keluarga" id="select_hubungan" required onchange="cekStatusJanda()" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">-- Pilih Status Keluarga --</option>
                        <option value="Suami (Kepala Rumah Tangga)">Suami (Kepala Rumah Tangga)</option>
                        <option value="Istri (Mengurus Rumah Tangga)">Istri (Mengurus Rumah Tangga)</option>
                        <option value="Anak">Anak</option>
                        <option value="Janda">Janda</option>
                    </select>
                </div>

                <!-- KOLOM SELECT TAMBAHAN JIKA STATUS JANDA -->
                <div id="kolom_sub_janda" class="hidden p-3 bg-amber-50 rounded-xl border border-amber-200 transition-all">
                    <label class="block text-sm font-bold text-amber-900 mb-1">
                        <i class="fa-solid fa-person-circle-question"></i> Peran / Status Tambahan Janda <span class="text-red-500">*</span>
                    </label>
                    <select name="sub_janda" id="select_sub_janda" class="w-full px-4 py-2 border border-amber-300 rounded-xl focus:ring-amber-500 focus:border-amber-500 text-sm bg-white">
                        <option value="Janda (Kepala Rumah Tangga)">Janda (Kepala Rumah Tangga - Tercatat di Rekap Iuran)</option>
                        <option value="Janda (Anggota Keluarga)">Janda (Anggota Keluarga - Ikut Anak / Keluarga Lain)</option>
                    </select>
                    <p class="text-[11px] text-amber-700 mt-1">Pilih <strong>Kepala Rumah Tangga</strong> jika bertindak sebagai penanggung jawab utama kavling/rumah.</p>
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

    <!-- JavaScript untuk interaksi dinamis kolom Janda & otomatisasi Jenis Kelamin -->
    <script>
        function cekStatusJanda() {
            const selectHubungan = document.getElementById('select_hubungan');
            const kolomSubJanda = document.getElementById('kolom_sub_janda');
            const selectSubJanda = document.getElementById('select_sub_janda');
            const selectJk = document.getElementById('select_jk');

            if (selectHubungan.value === 'Janda') {
                kolomSubJanda.classList.remove('hidden');
                selectSubJanda.required = true;
                selectJk.value = 'P'; // Janda otomatis Perempuan
            } else {
                kolomSubJanda.classList.add('hidden');
                selectSubJanda.required = false;

                // Membantu otomatisasi jenis kelamin agar pengguna tidak salah pilih
                if (selectHubungan.value === 'Suami (Kepala Rumah Tangga)') {
                    selectJk.value = 'L';
                } else if (selectHubungan.value === 'Istri (Mengurus Rumah Tangga)') {
                    selectJk.value = 'P';
                }
            }
        }
    </script>
</body>
</html>