<?php
include 'config.php';
require_once 'iuran_helper.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Tolak akses jika tidak memiliki izin edit_warga
if (!has_permission('edit_warga')) {
    echo "<script>alert('Akses Ditolak! Anda tidak memiliki izin untuk mengedit data warga.'); window.location='warga.php';</script>";
    exit;
}

// Ambil ID Warga
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: warga.php");
    exit;
}

// Ambil data warga saat ini
$q_warga = mysqli_query($conn, "SELECT * FROM warga WHERE id = $id LIMIT 1");
if (!$q_warga || mysqli_num_rows($q_warga) == 0) {
    echo "<script>alert('Data warga tidak ditemukan!'); window.location='warga.php';</script>";
    exit;
}
$warga = mysqli_fetch_assoc($q_warga);

// Proses Update Data
if (isset($_POST['update'])) {
    $nik       = mysqli_real_escape_string($conn, trim($_POST['nik']));
    $nama      = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $tgl_lahir = mysqli_real_escape_string($conn, trim($_POST['tanggal_lahir']));
    $jk        = mysqli_real_escape_string($conn, trim($_POST['jenis_kelamin']));
    $alamat    = mysqli_real_escape_string($conn, trim($_POST['alamat_rt']));
    $status    = mysqli_real_escape_string($conn, trim($_POST['status_warga']));
    
    // Status hubungan keluarga
    $hubungan = $_POST['hubungan_keluarga'] ?? '';
    if ($hubungan === 'Janda') {
        $hubungan = $_POST['sub_janda'] ?? 'Janda (Kepala Rumah Tangga)';
    }
    $hubungan = mysqli_real_escape_string($conn, trim($hubungan));

    // File Foto KTP Lama
    $foto_ktp_final = $warga['foto_ktp'];
    if (!empty($_FILES['foto_ktp']['name'])) {
        $foto_ktp = $_FILES['foto_ktp']['name'];
        $tmp_ktp  = $_FILES['foto_ktp']['tmp_name'];
        $ktp_baru = $nik . "_KTP_" . time() . "_" . $foto_ktp;
        $path_ktp = "uploads/" . $ktp_baru;

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        if (move_uploaded_file($tmp_ktp, $path_ktp)) {
            // Hapus foto KTP lama jika ada
            if (!empty($warga['foto_ktp']) && file_exists("uploads/" . $warga['foto_ktp'])) {
                unlink("uploads/" . $warga['foto_ktp']);
            }
            $foto_ktp_final = $ktp_baru;
        }
    }

    // File Foto KK Lama
    $foto_kk_final = $warga['foto_kk'];
    if (!empty($_FILES['foto_kk']['name'])) {
        $foto_kk = $_FILES['foto_kk']['name'];
        $tmp_kk  = $_FILES['foto_kk']['tmp_name'];
        $kk_baru = $nik . "_KK_" . time() . "_" . $foto_kk;
        $path_kk = "uploads/" . $kk_baru;

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        if (move_uploaded_file($tmp_kk, $path_kk)) {
            // Hapus foto KK lama jika ada
            if (!empty($warga['foto_kk']) && file_exists("uploads/" . $warga['foto_kk'])) {
                unlink("uploads/" . $warga['foto_kk']);
            }
            $foto_kk_final = $kk_baru;
        }
    }

    // Query Update Data Warga
    $sql_update = "UPDATE warga SET 
        nik = '$nik',
        nama = '$nama',
        tanggal_lahir = " . (!empty($tgl_lahir) ? "'$tgl_lahir'" : "NULL") . ",
        jenis_kelamin = '$jk',
        alamat_rt = '$alamat',
        status_warga = '$status',
        hubungan_keluarga = '$hubungan',
        foto_ktp = " . (!empty($foto_ktp_final) ? "'$foto_ktp_final'" : "NULL") . ",
        foto_kk = " . (!empty($foto_kk_final) ? "'$foto_kk_final'" : "NULL") . "
        WHERE id = $id";

    $update = mysqli_query($conn, $sql_update);

    if ($update) {
        // Sinkronisasi dengan Rekap Iuran 2026:
        if (is_kepala_keluarga($hubungan)) {
            // Jika Kepala Rumah Tangga, sinkronkan atau daftarkan ke iuran_warga
            sync_kepala_keluarga_ke_iuran($conn, $id, $nama, $nik, $alamat, $hubungan, 2026);
        } else {
            // Jika berubah menjadi bukan Kepala Rumah Tangga (misal: Anak/Istri), hapus baris kavlingnya di iuran_warga jika pernah terdaftar
            mysqli_query($conn, "DELETE FROM iuran_warga WHERE warga_id = $id");
        }

        echo "<script>alert('Data Warga Berhasil Diperbarui!'); window.location='warga.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal memperbarui data: " . addslashes(mysqli_error($conn)) . "');</script>";
    }
}

// Analisis hubungan keluarga saat ini untuk nilai awal dropdown
$cur_hub = $warga['hubungan_keluarga'] ?? '';
$is_janda = (strpos(strtolower($cur_hub), 'janda') !== false);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Warga - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl pb-16">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="warga.php" class="text-white text-xl hover:text-blue-200 transition"><i class="fa-solid fa-arrow-left"></i></a>
                <h1 class="font-bold text-lg">Edit Data Warga</h1>
            </div>
            <span class="text-xs bg-blue-800 text-blue-200 px-2.5 py-1 rounded-full font-mono">ID #<?= $warga['id']; ?></span>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK (Nomor Induk Kependudukan)</label>
                    <input type="number" name="nik" required value="<?= htmlspecialchars($warga['nik']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" required value="<?= htmlspecialchars($warga['nama']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                
                <!-- KOLOM TANGGAL LAHIR -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($warga['tanggal_lahir'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="select_jk" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="L" <?= ($warga['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="P" <?= ($warga['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>

                <!-- KOLOM STATUS DALAM KELUARGA -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status dalam Keluarga <span class="text-red-500">*</span></label>
                    <select name="hubungan_keluarga" id="select_hubungan" required onchange="cekStatusJanda()" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">-- Pilih Status Keluarga --</option>
                        <option value="Suami (Kepala Rumah Tangga)" <?= ($cur_hub === 'Suami (Kepala Rumah Tangga)') ? 'selected' : ''; ?>>Suami (Kepala Rumah Tangga)</option>
                        <option value="Istri (Mengurus Rumah Tangga)" <?= ($cur_hub === 'Istri (Mengurus Rumah Tangga)') ? 'selected' : ''; ?>>Istri (Mengurus Rumah Tangga)</option>
                        <option value="Anak" <?= ($cur_hub === 'Anak') ? 'selected' : ''; ?>>Anak</option>
                        <option value="Janda" <?= $is_janda ? 'selected' : ''; ?>>Janda</option>
                    </select>
                </div>

                <!-- KOLOM SELECT TAMBAHAN JIKA STATUS JANDA -->
                <div id="kolom_sub_janda" class="<?= $is_janda ? '' : 'hidden'; ?> p-3 bg-amber-50 rounded-xl border border-amber-200 transition-all">
                    <label class="block text-sm font-bold text-amber-900 mb-1">
                        <i class="fa-solid fa-person-circle-question"></i> Peran / Status Tambahan Janda <span class="text-red-500">*</span>
                    </label>
                    <select name="sub_janda" id="select_sub_janda" class="w-full px-4 py-2 border border-amber-300 rounded-xl focus:ring-amber-500 focus:border-amber-500 text-sm bg-white">
                        <option value="Janda (Kepala Rumah Tangga)" <?= ($cur_hub === 'Janda (Kepala Rumah Tangga)') ? 'selected' : ''; ?>>Janda (Kepala Rumah Tangga - Tercatat di Rekap Iuran)</option>
                        <option value="Janda (Anggota Keluarga)" <?= ($cur_hub === 'Janda (Anggota Keluarga)') ? 'selected' : ''; ?>>Janda (Anggota Keluarga - Ikut Anak / Keluarga Lain)</option>
                    </select>
                    <p class="text-[11px] text-amber-700 mt-1">Pilih <strong>Kepala Rumah Tangga</strong> jika bertindak sebagai penanggung jawab utama kavling/rumah.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat (Blok / No. Rumah)</label>
                    <input type="text" name="alamat_rt" required value="<?= htmlspecialchars($warga['alamat_rt']); ?>" placeholder="Contoh: Blok A No. 12" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Warga</label>
                    <select name="status_warga" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="Tetap" <?= ($warga['status_warga'] == 'Tetap') ? 'selected' : ''; ?>>Warga Tetap</option>
                        <option value="Kontrak" <?= ($warga['status_warga'] == 'Kontrak') ? 'selected' : ''; ?>>Warga Kontrak / Kos</option>
                    </select>
                </div>
                
                <!-- Input Upload KTP -->
                <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-bold text-blue-900"><i class="fa-solid fa-id-card"></i> Foto KTP</label>
                        <?php if (!empty($warga['foto_ktp'])): ?>
                            <a href="uploads/<?= $warga['foto_ktp']; ?>" target="_blank" class="text-[11px] text-blue-700 hover:underline font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-eye"></i> Lihat KTP Saat Ini
                            </a>
                        <?php else: ?>
                            <span class="text-[11px] text-gray-400">Belum ada KTP</span>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="foto_ktp" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-900 file:text-white hover:file:bg-blue-800">
                    <p class="text-[10px] text-gray-400 mt-1">Biarkan kosong jika tidak ingin mengubah foto KTP.</p>
                </div>

                <!-- Input Upload KK -->
                <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-bold text-blue-900"><i class="fa-solid fa-users-viewfinder"></i> Foto Kartu Keluarga</label>
                        <?php if (!empty($warga['foto_kk'])): ?>
                            <a href="uploads/<?= $warga['foto_kk']; ?>" target="_blank" class="text-[11px] text-teal-700 hover:underline font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-eye"></i> Lihat KK Saat Ini
                            </a>
                        <?php else: ?>
                            <span class="text-[11px] text-gray-400">Belum ada KK</span>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="foto_kk" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-900 file:text-white hover:file:bg-blue-800">
                    <p class="text-[10px] text-gray-400 mt-1">Biarkan kosong jika tidak ingin mengubah foto KK.</p>
                </div>

                <div class="pt-4 flex gap-2">
                    <a href="warga.php" class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl text-center text-sm transition">
                        Batal
                    </a>
                    <button type="submit" name="update" class="w-2/3 bg-blue-900 hover:bg-blue-800 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        <i class="fa-solid fa-check"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- JavaScript untuk interaksi dinamis kolom Janda & otomatisasi Jenis Kelamin -->
    <script>
        function cekStatusJanda() {
            const selectHubungan = document.getElementById('select_hubungan');
            const kolomSubJanda  = document.getElementById('kolom_sub_janda');
            const selectSubJanda = document.getElementById('select_sub_janda');
            const selectJk       = document.getElementById('select_jk');

            if (selectHubungan.value === 'Janda') {
                kolomSubJanda.classList.remove('hidden');
                selectSubJanda.required = true;
                selectJk.value = 'P'; // Janda otomatis Perempuan
            } else {
                kolomSubJanda.classList.add('hidden');
                selectSubJanda.required = false;

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
