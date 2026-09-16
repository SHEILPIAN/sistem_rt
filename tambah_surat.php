<?php
include 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Data warga untuk autofill jika role warga
$default_nik  = '';
$default_nama = $_SESSION['role'] == 'warga' ? ($_SESSION['nama_lengkap'] ?? '') : '';
$default_warga = null;

if ($_SESSION['role'] == 'warga' && !empty($default_nama)) {
    $nama_safe = mysqli_real_escape_string($conn, $default_nama);
    $qw = mysqli_query($conn, "SELECT * FROM warga WHERE nama = '$nama_safe' LIMIT 1");
    if ($qw && mysqli_num_rows($qw) > 0) {
        $default_warga = mysqli_fetch_assoc($qw);
        $default_nik = $default_warga['nik'] ?? '';
    }
}

// Load daftar warga untuk pencarian/autofill dinamis
$warga_list = [];
$qw_all = mysqli_query($conn, "SELECT nik, nama, tanggal_lahir, jenis_kelamin, alamat_rt FROM warga ORDER BY nama ASC");
if ($qw_all) {
    while ($rw = mysqli_fetch_assoc($qw_all)) {
        $warga_list[$rw['nik']] = [
            'nama'          => $rw['nama'],
            'tanggal_lahir' => $rw['tanggal_lahir'],
            'jenis_kelamin' => ($rw['jenis_kelamin'] == 'L' ? 'Laki-Laki' : 'Perempuan'),
            'alamat'        => $rw['alamat_rt']
        ];
    }
}

// Proses Simpan Data
if (isset($_POST['simpan'])) {
    $nama             = mysqli_real_escape_string($conn, $_POST['nama_pemohon']);
    $nik              = mysqli_real_escape_string($conn, $_POST['nik_pemohon']);
    $jenis            = mysqli_real_escape_string($conn, $_POST['jenis_surat']);
    $keperluan        = mysqli_real_escape_string($conn, $_POST['keperluan']);
    
    // Data Tambahan untuk SKTM
    $tempat_lahir     = mysqli_real_escape_string($conn, $_POST['tempat_lahir'] ?? '');
    $tgl_lahir_val    = !empty($_POST['tanggal_lahir']) ? "'" . mysqli_real_escape_string($conn, $_POST['tanggal_lahir']) . "'" : "NULL";
    $jenis_kelamin    = mysqli_real_escape_string($conn, $_POST['jenis_kelamin'] ?? '');
    $pekerjaan        = mysqli_real_escape_string($conn, $_POST['pekerjaan'] ?? '');
    $agama            = mysqli_real_escape_string($conn, $_POST['agama'] ?? '');
    $status_perkawinan= mysqli_real_escape_string($conn, $_POST['status_perkawinan'] ?? '');
    $jalan            = mysqli_real_escape_string($conn, $_POST['jalan'] ?? '');
    $blok             = mysqli_real_escape_string($conn, $_POST['blok'] ?? '');
    $no_rumah         = mysqli_real_escape_string($conn, $_POST['no_rumah'] ?? '');
    $alamat_custom    = mysqli_real_escape_string($conn, $_POST['alamat_custom'] ?? '');

    // Format alamat
    if (!empty($jalan) || !empty($blok) || !empty($no_rumah)) {
        $alamat = "Perum Graha Kalimas Jalan " . ($jalan ?: 'Harmoni Raya') . " Blok. " . ($blok ?: '-') . " No. " . ($no_rumah ?: '-') . " RT.31/RW.009, Desa Setiadarma, Kec Tambun Selatan, Kabupaten Bekasi";
    } else {
        $alamat = $alamat_custom ?: 'Perumahan Graha Kalimas RT 31 RW 09/1';
    }

    try {
        $insert = mysqli_query($conn, "INSERT INTO surat (
            nama_pemohon, nik_pemohon, jenis_surat, keperluan,
            tempat_lahir, tanggal_lahir, jenis_kelamin, pekerjaan, agama, status_perkawinan,
            jalan, blok, no_rumah, alamat, status_surat
        ) VALUES (
            '$nama', '$nik', '$jenis', '$keperluan',
            '$tempat_lahir', $tgl_lahir_val, '$jenis_kelamin', '$pekerjaan', '$agama', '$status_perkawinan',
            '$jalan', '$blok', '$no_rumah', '$alamat', 'Menunggu'
        )");

        if ($insert) {
            echo "<script>alert('Pengajuan Surat Berhasil Dikirim! Silakan tunggu konfirmasi RT.'); window.location='surat.php';</script>";
        } else {
            echo "<script>alert('Gagal mengirim pengajuan surat: " . addslashes(mysqli_error($conn)) . "');</script>";
        }
    } catch (Exception $e) {
        $err = addslashes($e->getMessage());
        echo "<script>alert('Error: $err');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Surat - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center py-6">

    <div class="w-full max-w-lg bg-white min-h-screen shadow-xl rounded-2xl overflow-hidden pb-12">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-5 shadow-md flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="surat.php" class="text-white text-xl hover:text-blue-200 transition"><i class="fa-solid fa-arrow-left"></i></a>
                <div>
                    <h1 class="font-bold text-lg leading-tight">Form Pengajuan Surat</h1>
                    <p class="text-xs text-blue-200">RT 31 / RW 09/1 Graha Kalimas</p>
                </div>
            </div>
            <a href="export_sktm.php?blangko=1" target="_blank" class="bg-blue-800 hover:bg-blue-700 text-white text-[11px] font-semibold py-1.5 px-3 rounded-lg border border-blue-600 transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Blangko SKTM
            </a>
        </div>

        <!-- Form Input -->
        <div class="p-6">
            <form action="" method="POST" class="space-y-4" id="formSurat">
                
                <!-- Jenis Surat Pengantar (Ditaruh di atas agar form menyesuaikan) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">
                        Jenis Surat Pengantar <span class="text-red-500">*</span>
                    </label>
                    <select name="jenis_surat" id="jenis_surat" required onchange="cekJenisSurat()" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm font-medium bg-gray-50">
                        <option value="Keterangan Tidak Mampu (SKTM)" <?= (isset($_GET['jenis']) && $_GET['jenis'] == 'sktm') ? 'selected' : ''; ?>>Surat Keterangan Tidak Mampu (SKTM)</option>
                        <option value="Pengantar Domisili">Pengantar Domisili</option>
                        <option value="Pengantar SKCK">Pengantar SKCK (Kepolisian)</option>
                        <option value="Keterangan Usaha">Keterangan Usaha / Domisili Usaha</option>
                        <option value="Pengantar Nikah">Pengantar Nikah</option>
                        <option value="Lainnya">Lainnya...</option>
                    </select>
                </div>

                <!-- NIK Pemohon -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">
                        NIK (Nomor Induk Kependudukan) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="nik_pemohon" id="nik_pemohon" value="<?= htmlspecialchars($default_nik); ?>" required placeholder="Masukkan 16 digit NIK..." oninput="autoFillByNik(this.value)" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm font-mono">
                        <span class="absolute right-3 top-2.5 text-xs text-gray-400"><i class="fa-solid fa-id-card"></i></span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Data warga akan terisi otomatis jika NIK sudah terdaftar di sistem.</p>
                </div>

                <!-- Nama Lengkap Pemohon -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">
                        Nama Lengkap Pemohon <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_pemohon" id="nama_pemohon" value="<?= htmlspecialchars($default_nama); ?>" required placeholder="Nama lengkap sesuai KTP..." class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm">
                </div>

                <!-- SECTION KHUSUS KETERANGAN TIDAK MAMPU (SKTM) -->
                <div id="section-sktm" class="border border-blue-200 bg-blue-50/50 rounded-2xl p-4 space-y-3.5 transition-all">
                    <div class="flex items-center gap-2 border-b border-blue-200 pb-2">
                        <div class="w-7 h-7 bg-blue-600 text-white rounded-lg flex items-center justify-center text-xs">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-blue-950 uppercase tracking-wider">Kelengkapan Data Blangko SKTM Resmi</h3>
                            <p class="text-[10px] text-blue-700">Wajib diisi untuk pencetakan dokumen resmi RT 31 & RW 09/1</p>
                        </div>
                    </div>

                    <!-- Tempat & Tanggal Lahir -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="tempat_lahir" placeholder="Kota lahir..." class="w-full px-3.5 py-2 border border-gray-300 rounded-xl bg-white text-xs focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="<?= $default_warga['tanggal_lahir'] ?? ''; ?>" class="w-full px-3.5 py-2 border border-gray-300 rounded-xl bg-white text-xs focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Jenis Kelamin & Agama -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="jenis_kelamin" class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-xs focus:ring-blue-500 focus:border-blue-500">
                                <option value="Laki-Laki" <?= ($default_warga['jenis_kelamin'] ?? '') == 'L' ? 'selected' : ''; ?>>Laki-Laki</option>
                                <option value="Perempuan" <?= ($default_warga['jenis_kelamin'] ?? '') == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Agama</label>
                            <select name="agama" id="agama" class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-xs focus:ring-blue-500 focus:border-blue-500">
                                <option value="Islam">Islam</option>
                                <option value="Kristen">Kristen</option>
                                <option value="Katholik">Katholik</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Buddha">Buddha</option>
                                <option value="Konghucu">Konghucu</option>
                            </select>
                        </div>
                    </div>

                    <!-- Pekerjaan & Status Perkawinan -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Pekerjaan</label>
                            <input type="text" name="pekerjaan" id="pekerjaan" placeholder="Contoh: Buruh / Karyawan" class="w-full px-3.5 py-2 border border-gray-300 rounded-xl bg-white text-xs focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Status Perkawinan</label>
                            <select name="status_perkawinan" id="status_perkawinan" class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-xs focus:ring-blue-500 focus:border-blue-500">
                                <option value="Belum Kawin">Belum Kawin</option>
                                <option value="Kawin">Kawin</option>
                                <option value="Cerai Hidup">Cerai Hidup</option>
                                <option value="Cerai Mati">Cerai Mati / Janda / Duda</option>
                            </select>
                        </div>
                    </div>

                    <!-- Alamat Lengkap Graha Kalimas Sesuai Template -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Alamat Rumah (Perum Graha Kalimas)
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="col-span-1">
                                <span class="text-[10px] text-gray-500 block mb-0.5">Nama Jalan</span>
                                <input type="text" name="jalan" id="jalan" placeholder="Harmoni Raya" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-xs">
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-500 block mb-0.5">Blok</span>
                                <input type="text" name="blok" id="blok" placeholder="Contoh: B2" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-xs">
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-500 block mb-0.5">No. Rumah</span>
                                <input type="text" name="no_rumah" id="no_rumah" placeholder="Contoh: 15" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-xs">
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1">RT.31/RW.009, Desa Setiadarma, Kec. Tambun Selatan, Kab. Bekasi.</p>
                    </div>
                </div>

                <!-- Keperluan / Keterangan Detail -->
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">
                        Keperluan / Keterangan Pengajuan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="keperluan" id="keperluan" required rows="3" placeholder="Contoh: Persyaratan pengajuan beasiswa kuliah / KIP / Bantuan Sosial / Keringanan Biaya Rumah Sakit..." class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm"></textarea>
                </div>

                <!-- Tombol Submit -->
                <div class="pt-3">
                    <button type="submit" name="simpan" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-xl transition duration-200 flex items-center justify-center gap-2 text-sm">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Pengajuan Surat
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Script Autofill & Toggle Dinamis -->
    <script>
        const wargaDb = <?= json_encode($warga_list); ?>;

        function autoFillByNik(nik) {
            nik = nik.trim();
            if (wargaDb[nik]) {
                const data = wargaDb[nik];
                if (data.nama && !document.getElementById('nama_pemohon').value) {
                    document.getElementById('nama_pemohon').value = data.nama;
                }
                if (data.tanggal_lahir) {
                    document.getElementById('tanggal_lahir').value = data.tanggal_lahir;
                }
                if (data.jenis_kelamin) {
                    document.getElementById('jenis_kelamin').value = data.jenis_kelamin;
                }
                if (data.alamat) {
                    // Coba parse jika ada blok di alamat
                    const matchBlok = data.alamat.match(/blok\s*([a-z0-9]+)/i);
                    const matchNo   = data.alamat.match(/no\.?\s*([a-z0-9]+)/i);
                    if (matchBlok && !document.getElementById('blok').value) {
                        document.getElementById('blok').value = matchBlok[1];
                    }
                    if (matchNo && !document.getElementById('no_rumah').value) {
                        document.getElementById('no_rumah').value = matchNo[1];
                    }
                }
            }
        }

        function cekJenisSurat() {
            const val = document.getElementById('jenis_surat').value;
            const sec = document.getElementById('section-sktm');
            if (val.includes('Tidak Mampu') || val.includes('SKTM')) {
                sec.style.display = 'block';
            } else {
                // Tetap tampilkan jika ingin data lengkap atau bisa disembunyikan
                sec.style.display = 'block'; // Tetap aktif agar informasinya lengkap dan mudah disimpan
            }
        }

        // Inisialisasi awal
        window.addEventListener('DOMContentLoaded', () => {
            cekJenisSurat();
            const initialNik = document.getElementById('nik_pemohon').value;
            if (initialNik) {
                autoFillByNik(initialNik);
            }
        });
    </script>
</body>
</html>