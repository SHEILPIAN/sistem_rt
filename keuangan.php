<?php
include 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login'])) {
    header("Location: login.php");
    exit;
}

$can_manage = has_permission('manage:keuangan');
$can_view   = has_permission('read:keuangan');
$can_view_nik = has_permission('view_nik');

// Default tab: 'iuran' sesuai permintaan rekap iuran bulanan (atau 'qris' untuk alat pembayaran)
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['iuran', 'kas', 'qris']) ? $_GET['tab'] : 'iuran';
$tahun_aktif = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 2026;
if ($tahun_aktif <= 0) $tahun_aktif = 2026;

$tarif_bulanan = get_tarif_iuran($conn, $tahun_aktif);

$kata_kunci = isset($_GET['cari']) ? trim($_GET['cari']) : '';

$pesan_sukses = "";
$pesan_error  = "";

// ==========================================
// PROSES AKSI POST (Hanya untuk yang berhak)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    $action = $_POST['action'] ?? '';

    // 1. Catat Pembayaran Iuran
    if ($action === 'catat_bayar') {
        $iuran_id = (int)($_POST['iuran_id'] ?? 0);
        $bulan    = strtolower(trim($_POST['bulan'] ?? ''));
        $nominal  = (float)($_POST['nominal'] ?? 0);
        $sync_kas = isset($_POST['sync_kas']) && $_POST['sync_kas'] == '1';

        if ($iuran_id > 0 && !empty($bulan) && $nominal >= 0) {
            if (catat_pembayaran_iuran($conn, $iuran_id, $bulan, $nominal, $sync_kas)) {
                $sync_info = $sync_kas ? " dan otomatis disinkronkan ke Kas Masuk RT." : ".";
                $pesan_sukses = "Pembayaran iuran bulan " . strtoupper($bulan) . " sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil dicatat" . $sync_info;
            } else {
                $pesan_error = "Gagal mencatat pembayaran iuran.";
            }
        } else {
            $pesan_error = "Data pembayaran tidak valid.";
        }
    }

    // 2. Simpan Perubahan Tarif Iuran
    elseif ($action === 'simpan_tarif') {
        $tahun_target  = (int)($_POST['tahun'] ?? 2026);
        $tarif_baru    = (float)($_POST['tarif_bulanan'] ?? 0);

        if ($tahun_target > 2000 && $tarif_baru > 0) {
            if (update_tarif_iuran($conn, $tahun_target, $tarif_baru)) {
                $tarif_bulanan = $tarif_baru;
                $pesan_sukses = "Tarif iuran bulanan tahun $tahun_target berhasil diperbarui menjadi Rp " . number_format($tarif_baru, 0, ',', '.') . "/bulan.";
            } else {
                $pesan_error = "Gagal memperbarui tarif iuran.";
            }
        } else {
            $pesan_error = "Nominal tarif harus lebih besar dari 0.";
        }
    }

    // 3. Tambah Warga ke Rekap Iuran
    elseif ($action === 'tambah_warga') {
        $tahun_warga = (int)($_POST['tahun'] ?? $tahun_aktif);
        $blok        = mysqli_real_escape_string($conn, trim($_POST['blok'] ?? ''));
        $nama        = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
        $tunggakan   = (int)($_POST['tunggakan_bulan_lalu'] ?? 0);
        $keterangan  = mysqli_real_escape_string($conn, trim($_POST['keterangan'] ?? ''));

        if (!empty($blok) && !empty($nama)) {
            // Cek apakah warga ada di master warga
            $q_w = mysqli_query($conn, "SELECT id, nik FROM warga WHERE nama = '$nama' OR alamat_rt LIKE '%$blok%' LIMIT 1");
            $w_id = null;
            $w_nik = sprintf('32013126%08d', rand(100, 9999));
            if ($q_w && mysqli_num_rows($q_w) > 0) {
                $rw = mysqli_fetch_assoc($q_w);
                $w_id = (int)$rw['id'];
                $w_nik = $rw['nik'];
            } else {
                $st_w = (strtolower($keterangan) === 'dikontrak') ? 'Kontrak' : 'Tetap';
                mysqli_query($conn, "INSERT INTO warga (nik, nama, alamat_rt, status_warga, jenis_kelamin, hubungan_keluarga) 
                    VALUES ('$w_nik', '$nama', 'Blok $blok', '$st_w', 'L', 'Suami (Kepala Rumah Tangga)')");
                $w_id = mysqli_insert_id($conn);
            }

            $sql = "INSERT INTO iuran_warga (tahun, blok, warga_id, nik, nama, tunggakan_bulan_lalu, keterangan) 
                    VALUES ($tahun_warga, '$blok', " . ($w_id ? $w_id : "NULL") . ", '$w_nik', '$nama', $tunggakan, '$keterangan')";
            if (mysqli_query($conn, $sql)) {
                $pesan_sukses = "Data warga blok $blok ($nama) berhasil ditambahkan ke rekap iuran.";
            } else {
                $pesan_error = "Gagal menambahkan warga: " . mysqli_error($conn);
            }
        } else {
            $pesan_error = "Blok dan Nama warga wajib diisi.";
        }
    }

    // 4. Edit Data Warga Iuran
    elseif ($action === 'edit_warga') {
        $id_warga    = (int)($_POST['id'] ?? 0);
        $blok        = mysqli_real_escape_string($conn, trim($_POST['blok'] ?? ''));
        $nama        = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
        $nik         = mysqli_real_escape_string($conn, trim($_POST['nik'] ?? ''));
        $tunggakan   = (int)($_POST['tunggakan_bulan_lalu'] ?? 0);
        $keterangan  = mysqli_real_escape_string($conn, trim($_POST['keterangan'] ?? ''));

        if ($id_warga > 0 && !empty($blok) && !empty($nama)) {
            $sql = "UPDATE iuran_warga SET blok = '$blok', nama = '$nama', nik = '$nik', tunggakan_bulan_lalu = $tunggakan, keterangan = '$keterangan' WHERE id = $id_warga";
            if (mysqli_query($conn, $sql)) {
                // Update juga ke tabel master warga jika ada relasi
                $q_cek_w = mysqli_query($conn, "SELECT warga_id FROM iuran_warga WHERE id = $id_warga");
                if ($q_cek_w && $row_w = mysqli_fetch_assoc($q_cek_w)) {
                    if (!empty($row_w['warga_id'])) {
                        $wid = (int)$row_w['warga_id'];
                        $nik_update = !empty($nik) ? ", nik = '$nik'" : "";
                        mysqli_query($conn, "UPDATE warga SET nama = '$nama', alamat_rt = 'Blok $blok' $nik_update WHERE id = $wid");
                    }
                }
                $pesan_sukses = "Data warga berhasil diperbarui.";
            } else {
                $pesan_error = "Gagal memperbarui data warga.";
            }
        } else {
            $pesan_error = "Data warga tidak valid.";
        }
    }

    // 5. Hapus Data Warga dari Rekap
    elseif ($action === 'hapus_warga') {
        $id_warga = (int)($_POST['id'] ?? 0);
        if ($id_warga > 0) {
            if (mysqli_query($conn, "DELETE FROM iuran_warga WHERE id = $id_warga")) {
                $pesan_sukses = "Data warga berhasil dihapus dari rekap iuran.";
            } else {
                $pesan_error = "Gagal menghapus data warga.";
            }
        }
    }

    // 6. Update Cepat Kekurangan Iuran Bulan Lalu (Des 2025)
    elseif ($action === 'update_tunggakan') {
        $id_iuran  = (int)($_POST['id'] ?? 0);
        $tunggakan = (int)($_POST['tunggakan_bulan_lalu'] ?? 0);

        if ($id_iuran > 0) {
            $update_sql = "UPDATE iuran_warga SET tunggakan_bulan_lalu = $tunggakan WHERE id = $id_iuran";
            if (mysqli_query($conn, $update_sql)) {
                $pesan_sukses = "Kekurangan iuran s/d Des " . ($tahun_aktif - 1) . " berhasil disimpan. Rumus total yang harus dibayar dan kekurangan uang s/d Des $tahun_aktif telah dihitung otomatis.";
            } else {
                $pesan_error = "Gagal memperbarui kekurangan iuran: " . mysqli_error($conn);
            }
        } else {
            $pesan_error = "Data iuran tidak ditemukan.";
        }
    }

    // 7. Edit / Koreksi Setoran Iuran Bulanan (Januari s/d Desember 2026)
    elseif ($action === 'edit_iuran_bulan') {
        $id_iuran     = (int)($_POST['id'] ?? 0);
        $bulan_asal   = strtolower(trim($_POST['bulan_asal'] ?? ''));
        $target_bulan = strtolower(trim($_POST['target_bulan'] ?? $bulan_asal));
        $nominal_baru = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal'] ?? 0);
        $sync_kas     = isset($_POST['sync_kas']) && $_POST['sync_kas'] == '1';

        if ($id_iuran > 0 && !empty($bulan_asal)) {
            if (edit_pembayaran_iuran_bulan($conn, $id_iuran, $bulan_asal, $nominal_baru, $target_bulan, $sync_kas)) {
                $pesan_sukses = "Setoran iuran bulan " . strtoupper($target_bulan) . " berhasil diperbarui menjadi Rp " . number_format($nominal_baru, 0, ',', '.') . ". Seluruh rekapitulasi telah diselaraskan.";
            } else {
                $pesan_error = "Gagal memperbarui setoran iuran bulanan.";
            }
        } else {
            $pesan_error = "Data setoran iuran tidak valid.";
        }
    }

    // 8. Edit Transaksi Kas Umum
    elseif ($action === 'edit_kas') {
        $id_kas     = (int)($_POST['id'] ?? 0);
        $tanggal    = mysqli_real_escape_string($conn, trim($_POST['tanggal'] ?? date('Y-m-d')));
        $keterangan = mysqli_real_escape_string($conn, trim($_POST['keterangan'] ?? ''));
        $jenis      = ($_POST['jenis'] === 'Keluar') ? 'Keluar' : 'Masuk';
        $nominal    = max(0, (float)str_replace(['.', ','], ['', '.'], $_POST['nominal'] ?? 0));

        if ($id_kas > 0 && !empty($keterangan) && $nominal > 0) {
            $sql_update_kas = "UPDATE keuangan SET tanggal = '$tanggal', keterangan = '$keterangan', jenis = '$jenis', nominal = $nominal WHERE id = $id_kas";
            if (mysqli_query($conn, $sql_update_kas)) {
                $pesan_sukses = "Data transaksi kas berhasil diperbarui.";
            } else {
                $pesan_error = "Gagal memperbarui transaksi kas: " . mysqli_error($conn);
            }
        } else {
            $pesan_error = "Data transaksi kas tidak lengkap.";
        }
    }

    // 9. Hapus Transaksi Kas Umum
    elseif ($action === 'hapus_kas') {
        $id_kas = (int)($_POST['id'] ?? 0);
        if ($id_kas > 0) {
            if (mysqli_query($conn, "DELETE FROM keuangan WHERE id = $id_kas")) {
                $pesan_sukses = "Transaksi kas berhasil dihapus dari buku kas.";
            } else {
                $pesan_error = "Gagal menghapus transaksi kas: " . mysqli_error($conn);
            }
        }
    }
}

// ==========================================
// DATA KAS UMUM
// ==========================================
$q_masuk = mysqli_query($conn, "SELECT SUM(nominal) as total_masuk FROM keuangan WHERE jenis='Masuk'");
$d_masuk = mysqli_fetch_assoc($q_masuk);
$tot_masuk = $d_masuk['total_masuk'] ? (float)$d_masuk['total_masuk'] : 0;

$q_keluar = mysqli_query($conn, "SELECT SUM(nominal) as total_keluar FROM keuangan WHERE jenis='Keluar'");
$d_keluar = mysqli_fetch_assoc($q_keluar);
$tot_keluar = $d_keluar['total_keluar'] ? (float)$d_keluar['total_keluar'] : 0;

$saldo_kas = $tot_masuk - $tot_keluar;

// Query transaksi kas umum
$q_transaksi = mysqli_query($conn, "SELECT * FROM keuangan ORDER BY tanggal DESC, id DESC");

// ==========================================
// DATA REKAP IURAN WARGA (80 WARGA)
// ==========================================
$sql_filter = "tahun = $tahun_aktif";
if (!empty($kata_kunci)) {
    $safe_kunci = mysqli_real_escape_string($conn, $kata_kunci);
    $sql_filter .= " AND (nama LIKE '%$safe_kunci%' OR blok LIKE '%$safe_kunci%' OR nik LIKE '%$safe_kunci%')";
}

$sql_order_blok = "
    REGEXP_SUBSTR(REGEXP_REPLACE(blok, '^Blok[[:space:]]+', ''), '^[A-Za-z]+') ASC,
    CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED) ASC,
    blok ASC
";

$q_iuran = mysqli_query($conn, "SELECT * FROM iuran_warga WHERE $sql_filter ORDER BY $sql_order_blok");
$daftar_iuran = [];
$total_warga_count = 0;
$counts_status = ['Kosong' => 0, 'dikontrak' => 0, 'Rumah ke 2' => 0, 'Penghuni' => 0];

$sum_uang_lalu = 0;
$sum_harus_bayar = 0;
$sum_iuran_terkumpul = 0;
$sum_tunggakan_sisa = 0;

// Query seluruh status hunian untuk card ringkasan (tanpa filter pencarian)
$q_all_status = mysqli_query($conn, "SELECT keterangan FROM iuran_warga WHERE tahun = $tahun_aktif");
if ($q_all_status) {
    while ($rs = mysqli_fetch_assoc($q_all_status)) {
        $ket_raw = trim($rs['keterangan'] ?? '');
        if (strtolower($ket_raw) === 'kosong') {
            $counts_status['Kosong']++;
        } elseif (strtolower($ket_raw) === 'dikontrak') {
            $counts_status['dikontrak']++;
        } elseif (strtolower($ket_raw) === 'rumah ke 2') {
            $counts_status['Rumah ke 2']++;
        } else {
            $counts_status['Penghuni']++;
        }
    }
}

if ($q_iuran) {
    while ($row = mysqli_fetch_assoc($q_iuran)) {
        $kalkulasi = hitung_rekap_baris_iuran($row, $tarif_bulanan);
        $row['kalkulasi'] = $kalkulasi;
        $daftar_iuran[] = $row;

        $total_warga_count++;
        if (!$kalkulasi['is_kosong']) {
            $sum_uang_lalu += $kalkulasi['tunggakan_uang_2025'];
            if (is_numeric($kalkulasi['jumlah_harus_dibayar'])) {
                $sum_harus_bayar += $kalkulasi['jumlah_harus_dibayar'];
            }
            $sum_iuran_terkumpul += $kalkulasi['total_bayar_2026'];
            $sum_tunggakan_sisa += $kalkulasi['sisa_kurang_2026'];
        }
    }
    // Urutkan daftar iuran kavling secara natural dari Blok A sampai Z dan seterusnya
    sort_iuran_by_blok($daftar_iuran, 'blok');
}

// Untuk modal catat iuran: selalu sediakan seluruh kavling terurut A - Z meskipun tabel sedang difilter pencarian
$semua_pilihan_iuran = $daftar_iuran;
if (!empty($kata_kunci)) {
    $q_all_iuran = mysqli_query($conn, "SELECT id, blok, nama, keterangan FROM iuran_warga WHERE tahun = $tahun_aktif");
    $semua_pilihan_iuran = [];
    if ($q_all_iuran) {
        while ($r_all = mysqli_fetch_assoc($q_all_iuran)) {
            $semua_pilihan_iuran[] = $r_all;
        }
        sort_iuran_by_blok($semua_pilihan_iuran, 'blok');
    }
}

$bulan_labels = [
    'jan' => 'JAN', 'feb' => 'FEB', 'mar' => 'MAR', 'apr' => 'APR',
    'mei' => 'MEI', 'jun' => 'JUN', 'jul' => 'JUL', 'agt' => 'AGT',
    'sep' => 'SEP', 'okt' => 'OKT', 'nop' => 'NOP', 'des' => 'DES'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keuangan & Iuran RT - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-yellow-header th {
            background-color: #facc15;
            color: #1f2937;
            font-weight: 700;
            border: 1px solid #d97706;
            vertical-align: middle;
            text-align: center;
        }
        .table-yellow-cell {
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <div class="w-full max-w-7xl mx-auto bg-white min-h-screen shadow-xl relative pb-24">
        
        <!-- Header Navigasi Atas -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="index.php" class="text-white text-xl hover:text-blue-200 transition"><i class="fa-solid fa-arrow-left"></i></a>
                <div>
                    <h1 class="font-bold text-lg leading-tight">Keuangan & Rekap Iuran RT</h1>
                    <p class="text-xs text-blue-200">Buku Kas Umum dan Rekapitulasi Iuran Warga RT 31</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="keuangan.php?tab=qris" class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white text-xs font-bold py-2 px-3 rounded-lg shadow-sm flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-qrcode text-sm"></i> <span class="hidden sm:inline">Alat Bayar</span> <span>QRIS RT</span>
                </a>
                <?php if ($active_tab === 'iuran'): ?>
                    <a href="export_iuran.php?tahun=<?= $tahun_aktif; ?>" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-3.5 rounded-lg shadow-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-file-excel text-sm"></i> <span>Export Excel (.xls)</span>
                    </a>
                <?php else: ?>
                    <?php if ($can_manage): ?>
                        <a href="export_keuangan.php" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-3.5 rounded-lg shadow-sm flex items-center gap-1.5 transition">
                            <i class="fa-solid fa-file-excel text-sm"></i> <span>Export Kas (.xls)</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alert Notifikasi Flash Message -->
        <?php if (!empty($pesan_sukses)): ?>
            <div class="mx-4 mt-4 p-3 bg-green-50 border-l-4 border-green-500 text-green-800 text-sm rounded-r-lg flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-green-600 text-base"></i>
                    <span><?= htmlspecialchars($pesan_sukses); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-900"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_error)): ?>
            <div class="mx-4 mt-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-800 text-sm rounded-r-lg flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-600 text-base"></i>
                    <span><?= htmlspecialchars($pesan_error); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-900"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <!-- Sub Tabs Navigasi: Alat Pembayaran QRIS vs Rekap Iuran Bulanan vs Buku Kas Umum -->
        <div class="flex border-b border-gray-200 bg-white sticky top-0 z-20 overflow-x-auto shadow-sm">
            <a href="keuangan.php?tab=qris" class="flex-1 min-w-[170px] py-3.5 text-center text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 <?= $active_tab === 'qris' ? 'text-red-700 border-b-2 border-red-600 bg-red-50/70' : 'text-gray-500 hover:text-red-600 hover:bg-gray-50' ?>">
                <i class="fa-solid fa-qrcode text-red-600 text-base"></i> Alat Bayar QRIS & Bank
            </a>
            <a href="keuangan.php?tab=iuran&tahun=<?= $tahun_aktif; ?>" class="flex-1 min-w-[170px] py-3.5 text-center text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 <?= $active_tab === 'iuran' ? 'text-blue-900 border-b-2 border-blue-900 bg-blue-50/50' : 'text-gray-500 hover:text-blue-700 hover:bg-gray-50' ?>">
                <i class="fa-solid fa-table text-yellow-500 text-base"></i> Rekap Iuran <?= $tahun_aktif; ?> (80 Kavling)
            </a>
            <a href="keuangan.php?tab=kas" class="flex-1 min-w-[170px] py-3.5 text-center text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 <?= $active_tab === 'kas' ? 'text-blue-900 border-b-2 border-blue-900 bg-blue-50/50' : 'text-gray-500 hover:text-blue-700 hover:bg-gray-50' ?>">
                <i class="fa-solid fa-book text-emerald-600 text-base"></i> Buku Kas Umum RT
            </a>
        </div>

        <?php if ($active_tab === 'qris'): ?>
        <!-- ========================================== -->
        <!-- KONTEN TAB: ALAT PEMBAYARAN RESMI QRIS RT  -->
        <!-- ========================================== -->
        <div class="p-4 sm:p-6 space-y-6 max-w-5xl mx-auto">
            
            <!-- Banner Judul Besar -->
            <div class="bg-gradient-to-r from-red-600 via-rose-600 to-red-800 text-white p-6 rounded-3xl shadow-lg relative overflow-hidden">
                <i class="fa-solid fa-qrcode absolute -right-6 -bottom-6 text-9xl opacity-15 pointer-events-none"></i>
                <div class="relative z-10 max-w-2xl">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="bg-white text-red-700 text-xs font-black uppercase px-2.5 py-1 rounded-lg tracking-wider shadow-sm">QRIS RESMI</span>
                        <span class="bg-red-900/60 text-white text-xs font-bold uppercase px-2.5 py-1 rounded-lg">GPN NASIONAL</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight">KAS RT31 GRAHA KALIMAS</h2>
                    <p class="text-sm text-red-100 mt-1">Alat pembayaran resmi penerimaan kas dan iuran warga RT 31 secara nontunai (Cashless). Cepat, praktis, langsung masuk ke kas RT.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Kolom Kiri: Tampilan Poster QRIS Penuh (5 Cols) -->
                <div class="lg:col-span-5 bg-white border-2 border-red-200 rounded-3xl p-5 shadow-lg flex flex-col items-center text-center">
                    <div class="w-full flex items-center justify-between pb-3 border-b border-gray-100 mb-3">
                        <span class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-camera text-red-600"></i> Scan Barcode QRIS
                        </span>
                        <span class="text-[10px] bg-red-50 text-red-700 font-bold px-2 py-0.5 rounded-full border border-red-200">Siap Scan</span>
                    </div>

                    <!-- Gambar QRIS Asli (Besar & Jelas) -->
                    <div class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-2.5 shadow-inner cursor-pointer group relative" onclick="bukaModalQRIS()" title="Klik untuk membuka layar penuh">
                        <img src="qris_kas_rt31.jpg" alt="Barcode QRIS KAS RT31 GRAHA KALIMAS" class="w-full h-auto max-w-[360px] mx-auto object-contain rounded-xl shadow-md transition-transform duration-200 group-hover:scale-[1.02]">
                        <div class="absolute inset-0 bg-black/25 opacity-0 group-hover:opacity-100 rounded-xl flex items-center justify-center text-white font-bold text-sm transition">
                            <span class="bg-black/70 px-3 py-1.5 rounded-xl flex items-center gap-1.5"><i class="fa-solid fa-expand"></i> Klik Perbesar</span>
                        </div>
                    </div>

                    <!-- Info NMID & Merchant -->
                    <div class="w-full mt-4 p-3 bg-red-50/80 rounded-2xl border border-red-200 text-left">
                        <p class="text-[10px] text-red-700 font-bold uppercase tracking-wider">Nama Merchant Resmi:</p>
                        <h4 class="font-black text-sm text-gray-900">KAS RT31 GRAHA KALIMAS</h4>
                        <div class="flex items-center justify-between mt-1 pt-1 border-t border-red-200/60">
                            <div>
                                <span class="text-[10px] text-gray-500 block">National Merchant ID:</span>
                                <span class="font-mono text-xs font-black text-red-800">ID1026545858353</span>
                            </div>
                            <button type="button" onclick="navigator.clipboard.writeText('ID1026545858353'); alert('NMID ID1026545858353 berhasil disalin!');" class="text-xs bg-red-600 hover:bg-red-700 text-white font-bold px-2.5 py-1.5 rounded-lg transition flex items-center gap-1">
                                <i class="fa-regular fa-copy"></i> Salin
                            </button>
                        </div>
                    </div>

                    <!-- Tombol Aksi Unduh & Perbesar -->
                    <div class="w-full flex gap-2 mt-4">
                        <a href="qris_kas_rt31.jpg" download="QRIS_KAS_RT31_GRAHA_KALIMAS.jpg" class="flex-1 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-3 px-4 rounded-xl shadow transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-download"></i> Unduh Gambar QRIS
                        </a>
                        <button type="button" onclick="bukaModalQRIS()" class="bg-gray-800 hover:bg-black text-white text-xs font-bold py-3 px-4 rounded-xl shadow transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-expand"></i> Layar Penuh
                        </button>
                    </div>
                </div>

                <!-- Kolom Kanan: Panduan Bayar, Bank Alternatif & Link Cek Iuran (7 Cols) -->
                <div class="lg:col-span-7 space-y-5">
                    
                    <!-- Card Transfer Alternatif: BCA Resmi -->
                    <div class="bg-gradient-to-br from-blue-900 via-blue-950 to-indigo-950 text-white p-5 rounded-3xl shadow-lg relative overflow-hidden">
                        <i class="fa-solid fa-building-columns absolute -right-3 -bottom-3 text-7xl opacity-10 pointer-events-none"></i>
                        <div class="flex items-center justify-between mb-3">
                            <span class="bg-yellow-400 text-blue-950 text-[10px] font-black uppercase px-2 py-0.5 rounded tracking-wider">Alternatif Transfer</span>
                            <span class="text-xs text-blue-200">Rekening Bank Resmi RT 31</span>
                        </div>
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-yellow-400 text-2xl shrink-0">
                                <i class="fa-solid fa-building-columns"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-base text-white">BANK CENTRAL ASIA (BCA)</h4>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="font-mono text-xl font-black text-yellow-300 tracking-wider">7285861195</span>
                                    <button type="button" onclick="navigator.clipboard.writeText('7285861195'); alert('Nomor rekening BCA 7285861195 berhasil disalin!');" class="text-xs bg-white/20 hover:bg-white/30 text-white px-2 py-0.5 rounded transition">
                                        <i class="fa-regular fa-copy"></i> Salin
                                    </button>
                                </div>
                                <p class="text-xs text-blue-200 mt-0.5">Atas Nama: <strong class="text-white font-bold">Maria Suparmijati</strong></p>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-white/15 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div class="bg-white/5 p-2.5 rounded-xl">
                                <span class="text-blue-300 text-[10px] block font-medium">Konfirmasi WA 1:</span>
                                <strong class="text-white text-xs">Ibu MARIA.S / MANIK</strong>
                                <a href="https://wa.me/6281237418441" target="_blank" class="text-emerald-400 block font-mono hover:underline text-[11px] mt-0.5"><i class="fa-brands fa-whatsapp"></i> 081237418441</a>
                            </div>
                            <div class="bg-white/5 p-2.5 rounded-xl">
                                <span class="text-blue-300 text-[10px] block font-medium">Konfirmasi WA 2:</span>
                                <strong class="text-white text-xs">Ibu MAYDIWATI</strong>
                                <a href="https://wa.me/6281250468187" target="_blank" class="text-emerald-400 block font-mono hover:underline text-[11px] mt-0.5"><i class="fa-brands fa-whatsapp"></i> 081250468187</a>
                            </div>
                        </div>
                    </div>

                    <!-- Panduan Cara Membayar QRIS -->
                    <div class="bg-white border border-gray-200 rounded-3xl p-5 shadow-sm space-y-3">
                        <h4 class="font-extrabold text-sm text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-mobile-screen-button text-red-600"></i> Panduan Cara Pembayaran via QRIS:
                        </h4>
                        <ol class="space-y-2.5 text-xs text-gray-700">
                            <li class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-red-100 text-red-700 font-black text-[11px] flex items-center justify-center shrink-0 mt-0.5">1</span>
                                <span>Buka aplikasi <strong>Mobile Banking</strong> (BCA, Livin' Mandiri, BRImo, BNI, BSI, dll) atau <strong>E-Wallet</strong> (GoPay, OVO, DANA, ShopeePay, LinkAja).</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-red-100 text-red-700 font-black text-[11px] flex items-center justify-center shrink-0 mt-0.5">2</span>
                                <span>Pilih menu <strong>Scan / Bayar QRIS</strong> di halaman utama aplikasi.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-red-100 text-red-700 font-black text-[11px] flex items-center justify-center shrink-0 mt-0.5">3</span>
                                <span>Arahkan kamera HP ke gambar QRIS di samping atau pilih dari galeri HP jika sudah diunduh.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-red-100 text-red-700 font-black text-[11px] flex items-center justify-center shrink-0 mt-0.5">4</span>
                                <span>Pastikan nama merchant tertera: <strong class="text-red-700">KAS RT31 GRAHA KALIMAS</strong>.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-red-100 text-red-700 font-black text-[11px] flex items-center justify-center shrink-0 mt-0.5">5</span>
                                <span>Masukkan nominal iuran/setoran kas, selesaikan pembayaran dan kirimkan bukti transfer ke Bendahara RT.</span>
                            </li>
                        </ol>

                        <div class="pt-3 border-t border-gray-100">
                            <p class="text-[11px] text-gray-500 font-medium mb-2">Aplikasi Pembayaran yang Didukung:</p>
                            <div class="flex flex-wrap gap-1.5 text-[10px] font-bold text-gray-700">
                                <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">BCA Mobile</span>
                                <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">Livin' Mandiri</span>
                                <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">BRImo</span>
                                <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">BNI Mobile</span>
                                <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">GoPay</span>
                                <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">OVO</span>
                                <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">DANA</span>
                                <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">ShopeePay</span>
                                <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">LinkAja</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Cek Rekap Iuran & Kas RT -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <a href="keuangan.php?tab=iuran" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-yellow-950 font-bold py-3 px-4 rounded-2xl text-xs text-center shadow-sm transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-table"></i> Buka Rekap Iuran Warga (80 Kavling)
                        </a>
                        <a href="keuangan.php?tab=kas" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-2xl text-xs text-center shadow-sm transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-book"></i> Buka Buku Kas Umum RT
                        </a>
                    </div>

                </div>
            </div>

        </div>

        <?php elseif ($active_tab === 'iuran'): ?>
        <!-- ========================================== -->
        <!-- KONTEN TAB: REKAP IURAN WARGA 2026        -->
        <!-- ========================================== -->
        <div class="p-4 space-y-4">
            
            <!-- Baris Ringkasan Statistik Finansial -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Card Saldo Kas RT -->
                <div class="bg-gradient-to-br from-blue-800 to-blue-950 p-4 rounded-xl text-white shadow relative overflow-hidden">
                    <i class="fa-solid fa-wallet absolute -right-2 -bottom-2 text-6xl opacity-15"></i>
                    <p class="text-[11px] text-blue-200 font-medium">SALDO KAS RT TERKINI</p>
                    <h3 class="text-2xl font-extrabold mt-0.5">Rp <?= number_format($saldo_kas, 0, ',', '.'); ?></h3>
                    <p class="text-[10px] text-blue-300 mt-2"><i class="fa-solid fa-arrow-down text-emerald-400"></i> Masuk: <?= number_format($tot_masuk, 0, ',', '.'); ?> | Keluar: <?= number_format($tot_keluar, 0, ',', '.'); ?></p>
                </div>

                <!-- Card Tarif Iuran Aktif -->
                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl shadow-sm relative">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[11px] text-yellow-800 font-medium">TARIF IURAN BULANAN (<?= $tahun_aktif; ?>)</p>
                            <h3 class="text-2xl font-bold text-yellow-900 mt-0.5">Rp <?= number_format($tarif_bulanan, 0, ',', '.'); ?> <span class="text-xs font-normal text-gray-500">/bln</span></h3>
                        </div>
                        <?php if ($can_manage): ?>
                        <button onclick="bukaModalTarif()" class="bg-yellow-400 hover:bg-yellow-500 text-yellow-950 text-xs font-bold px-2.5 py-1.5 rounded-lg shadow-sm transition flex items-center gap-1">
                            <i class="fa-solid fa-gear"></i> Ubah Tarif
                        </button>
                        <?php endif; ?>
                    </div>
                    <p class="text-[10px] text-yellow-700 mt-2">Diterapkan otomatis untuk kewajiban iuran warga</p>
                </div>

                <!-- Card Total Terkumpul Tahun Ini -->
                <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl shadow-sm">
                    <p class="text-[11px] text-emerald-800 font-medium">TOTAL TERKUMPUL (<?= $tahun_aktif; ?>)</p>
                    <h3 class="text-2xl font-bold text-emerald-700 mt-0.5">Rp <?= number_format($sum_iuran_terkumpul, 0, ',', '.'); ?></h3>
                    <p class="text-[10px] text-emerald-600 mt-2">Akumulasi setoran Jan - Des <?= $tahun_aktif; ?></p>
                </div>

                <!-- Card Sisa Tunggakan Warga -->
                <div class="bg-red-50 border border-red-200 p-4 rounded-xl shadow-sm">
                    <p class="text-[11px] text-red-800 font-medium">TOTAL KEKURANGAN S/D DES <?= $tahun_aktif; ?></p>
                    <h3 class="text-2xl font-bold text-red-600 mt-0.5"><?= $sum_tunggakan_sisa < 0 ? '- Rp ' . number_format(abs($sum_tunggakan_sisa), 0, ',', '.') : 'Rp ' . number_format($sum_tunggakan_sisa, 0, ',', '.'); ?></h3>
                    <p class="text-[10px] text-red-500 mt-2">Termasuk tunggakan s/d Des <?= $tahun_aktif - 1; ?> (-Rp <?= number_format(abs($sum_uang_lalu), 0, ',', '.'); ?>)</p>
                </div>
            </div>

            <!-- Banner Utama Alat Pembayaran QRIS RT (Tampak Langsung di Atas Tabel Tanpa Perlu Scroll) -->
            <div class="bg-gradient-to-r from-red-600 via-rose-600 to-red-700 text-white rounded-2xl p-4 shadow-md flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white rounded-xl shadow p-1 flex items-center justify-center shrink-0 cursor-pointer group" onclick="bukaModalQRIS()" title="Klik untuk memperbesar QRIS">
                        <img src="qris_kas_rt31.jpg" alt="QRIS KAS RT31" class="w-full h-full object-contain rounded group-hover:scale-105 transition">
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="bg-white text-red-700 text-[10px] font-black uppercase px-2 py-0.5 rounded shadow-sm">ALAT BAYAR RESMI</span>
                            <span class="text-xs text-red-100">Scan & Bayar Iuran Nontunai</span>
                        </div>
                        <h4 class="font-extrabold text-base text-white mt-0.5">KAS RT31 GRAHA KALIMAS</h4>
                        <p class="text-xs text-red-100 mt-0.5">
                            NMID: <strong class="font-mono text-white">ID1026545858353</strong> &bull; 
                            BCA: <strong class="font-mono text-white">7285861195</strong> (Maria Suparmijati)
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end">
                    <button type="button" onclick="bukaModalQRIS()" class="bg-white hover:bg-gray-100 text-red-700 text-xs font-bold py-2.5 px-3.5 rounded-xl shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-qrcode"></i> Buka QRIS Penuh
                    </button>
                    <a href="qris_kas_rt31.jpg" download="QRIS_KAS_RT31_GRAHA_KALIMAS.jpg" class="bg-red-800/80 hover:bg-red-800 text-white text-xs font-bold py-2.5 px-3.5 rounded-xl shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-download"></i> Unduh
                    </a>
                </div>
            </div>

            <!-- Toolbar Aksi & Filter Pencarian -->
            <div class="flex flex-wrap items-center justify-between gap-2 bg-gray-50 p-3 rounded-xl border border-gray-200">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-gray-700"><i class="fa-solid fa-filter text-blue-600"></i> Tahun:</span>
                    <form method="GET" action="keuangan.php" class="inline">
                        <input type="hidden" name="tab" value="iuran">
                        <?php if (!empty($kata_kunci)): ?>
                            <input type="hidden" name="cari" value="<?= htmlspecialchars($kata_kunci); ?>">
                        <?php endif; ?>
                        <select name="tahun" onchange="this.form.submit()" class="border border-gray-300 rounded-lg text-xs font-bold py-1.5 px-3 bg-white text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="2025" <?= $tahun_aktif == 2025 ? 'selected' : ''; ?>>2025</option>
                            <option value="2026" <?= $tahun_aktif == 2026 ? 'selected' : ''; ?>>2026</option>
                            <option value="2027" <?= $tahun_aktif == 2027 ? 'selected' : ''; ?>>2027</option>
                        </select>
                    </form>

                    <!-- Form Pencarian Cepat Nama / Blok -->
                    <form method="GET" action="keuangan.php" class="flex items-center gap-1.5 ml-2">
                        <input type="hidden" name="tab" value="iuran">
                        <input type="hidden" name="tahun" value="<?= $tahun_aktif; ?>">
                        <div class="relative">
                            <input type="text" name="cari" value="<?= htmlspecialchars($kata_kunci); ?>" placeholder="Cari nama atau blok..." class="border border-gray-300 rounded-lg text-xs py-1.5 pl-7 pr-2 w-36 sm:w-48 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                        </div>
                        <button type="submit" class="bg-gray-800 hover:bg-black text-white text-xs font-bold py-1.5 px-2.5 rounded-lg transition">Cari</button>
                        <?php if (!empty($kata_kunci)): ?>
                            <a href="keuangan.php?tab=iuran&tahun=<?= $tahun_aktif; ?>" class="text-xs text-red-500 hover:underline"><i class="fa-solid fa-xmark"></i> Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="bukaModalQRIS()" class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white text-xs font-bold py-2 px-3 rounded-lg shadow-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-qrcode"></i> QRIS Bayar
                    </button>
                    <?php if ($can_manage): ?>
                    <button onclick="bukaModalBayar()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-3 rounded-lg shadow-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-money-bill-wave"></i> Catat Bayar
                    </button>
                    <button onclick="bukaModalTambahWarga()" class="bg-gray-800 hover:bg-black text-white text-xs font-bold py-2 px-3 rounded-lg shadow-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-plus"></i> Tambah Blok
                    </button>
                    <?php endif; ?>
                    <a href="export_iuran.php?tahun=<?= $tahun_aktif; ?>" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-3 rounded-lg shadow-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-file-excel"></i> Export (.xls)
                    </a>
                </div>
            </div>

            <!-- Tabel Spreadsheet Kuning Sesuai Gambar -->
            <div class="overflow-x-auto rounded-xl border border-gray-300 shadow-md">
                <table class="w-full text-xs text-left border-collapse table-auto min-w-[1350px]">
                    <thead class="table-yellow-header">
                        <tr>
                            <th rowspan="2" class="p-2 border border-yellow-600 w-10">NO</th>
                            <th rowspan="2" class="p-2 border border-yellow-600 w-16">BLOK</th>
                            <th rowspan="2" class="p-2 border border-yellow-600 min-w-[220px]">NAMA KEPALA KELUARGA</th>
                            <th rowspan="2" class="p-2 border border-yellow-600 max-w-[130px] leading-tight">
                                Jumlah Kekurangan Iuran dalam bulan - s/d bulan Des <?= $tahun_aktif - 1; ?>
                                <?php if ($can_manage): ?>
                                    <span class="block text-[9px] font-normal text-yellow-900 mt-1 opacity-80"><i class="fa-solid fa-pen-to-square"></i> Klik untuk input/edit</span>
                                <?php endif; ?>
                            </th>
                            <th rowspan="2" class="p-2 border border-yellow-600 max-w-[140px] leading-tight">Jumlah Kekurangan Iuran dalam uang - s/d bulan Des <?= $tahun_aktif - 1; ?></th>
                            <th rowspan="2" class="p-2 border border-yellow-600 max-w-[130px] leading-tight">Jumlah Kekurangan Iuran dalam bulan - s/d bulan Des <?= $tahun_aktif; ?></th>
                            <th rowspan="2" class="p-2 border border-yellow-600 max-w-[140px] leading-tight">Jumlah yang harus dibayar - s/d bulan Des <?= $tahun_aktif; ?></th>
                            <th colspan="12" class="p-2 border border-yellow-600 text-center tracking-wider bg-yellow-400">
                                TAHUN <?= $tahun_aktif; ?>
                                <?php if ($can_manage): ?>
                                    <span class="block text-[9px] font-normal normal-case text-yellow-950 mt-0.5"><i class="fa-solid fa-pen-to-square"></i> Klik kolom bulan untuk edit/koreksi setoran</span>
                                <?php endif; ?>
                            </th>
                            <th rowspan="2" class="p-2 border border-yellow-600 max-w-[140px] leading-tight">Jumlah Kekurangan Iuran dalam uang - s/d bulan Des <?= $tahun_aktif; ?></th>
                            <th rowspan="2" class="p-2 border border-yellow-600 min-w-[120px]">Keterangan</th>
                            <?php if ($can_manage): ?>
                            <th rowspan="2" class="p-2 border border-yellow-600 w-24">Aksi</th>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <?php foreach ($bulan_labels as $key => $lbl): ?>
                            <th class="p-1.5 border border-yellow-600 w-16 text-center"><?= $lbl; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php 
                        $no = 1;
                        $tot_uang_lalu = 0;
                        $tot_harus_bayar = 0;
                        $tot_bulan = array_fill_keys(array_keys($bulan_labels), 0);
                        $tot_sisa_kurang = 0;

                        if (empty($daftar_iuran)): 
                        ?>
                        <tr>
                            <td colspan="<?= $can_manage ? 22 : 21; ?>" class="p-8 text-center text-gray-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 text-gray-300"></i>
                                <p>Tidak ada data iuran yang cocok dengan filter pencarian.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($daftar_iuran as $row): 
                                $k = $row['kalkulasi'];
                                if (!$k['is_kosong']) {
                                    $tot_uang_lalu += $k['tunggakan_uang_2025'];
                                    if (is_numeric($k['jumlah_harus_dibayar'])) {
                                        $tot_harus_bayar += $k['jumlah_harus_dibayar'];
                                    }
                                    $tot_sisa_kurang += $k['sisa_kurang_2026'];
                                }
                            ?>
                            <tr class="hover:bg-yellow-50/40 transition <?= $k['is_kosong'] ? 'bg-gray-50 text-gray-400' : 'bg-white text-gray-800'; ?>">
                                <td class="p-2 border border-gray-300 text-center font-semibold"><?= $no++; ?></td>
                                <td class="p-2 border border-gray-300 text-center font-bold text-blue-900"><?= htmlspecialchars($row['blok']); ?></td>
                                <td class="p-2 border border-gray-300 font-medium">
                                    <div class="flex items-center justify-between">
                                        <span><?= htmlspecialchars($row['nama']); ?></span>
                                        <?php if ($k['is_kosong']): ?>
                                             <span class="ml-1 text-[10px] bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded">Kosong</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($can_view_nik && !empty($row['nik'])): ?>
                                        <span class="block text-[10px] text-gray-400 font-mono">NIK: <?= htmlspecialchars($row['nik']); ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Tunggakan Bulan 2025 -->
                                <td class="p-2 border border-gray-300 text-center font-mono">
                                    <?php if ($can_manage && !$k['is_kosong']): ?>
                                        <button type="button" 
                                                onclick='bukaModalEditTunggakan(<?= json_encode($row); ?>)' 
                                                class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-lg bg-yellow-100 hover:bg-yellow-200 text-blue-950 font-bold border border-yellow-300 shadow-sm hover:shadow transition group" 
                                                title="Klik untuk input atau edit kekurangan iuran s/d Des <?= $tahun_aktif - 1; ?>">
                                            <span><?= (int)$row['tunggakan_bulan_lalu']; ?></span>
                                            <i class="fa-solid fa-pen-to-square text-[10px] text-amber-700 opacity-60 group-hover:opacity-100"></i>
                                        </button>
                                    <?php else: ?>
                                        <?= $k['is_kosong'] ? '-' : ($k['tunggakan_bulan_2025'] == 0 ? '-' : $k['tunggakan_bulan_2025']); ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Tunggakan Uang 2025 -->
                                <td class="p-2 border border-gray-300 text-right font-mono">
                                    <?php if ($k['is_kosong'] || $k['tunggakan_uang_2025'] == 0): ?>
                                        -
                                    <?php else: ?>
                                        <?= $k['tunggakan_uang_2025'] < 0 ? '-' . number_format(abs($k['tunggakan_uang_2025']), 0, ',', '.') : number_format($k['tunggakan_uang_2025'], 0, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Kewajiban Bulan 2026 -->
                                <td class="p-2 border border-gray-300 text-center font-mono font-semibold">
                                    <?= $k['is_kosong'] ? '-' : $k['kewajiban_bulan_2026']; ?>
                                </td>

                                <!-- Jumlah yang Harus Dibayar 2026 -->
                                <td class="p-2 border border-gray-300 text-right font-mono font-semibold">
                                    <?php if ($k['is_kosong'] || !is_numeric($k['jumlah_harus_dibayar']) || $k['jumlah_harus_dibayar'] == 0): ?>
                                        <?= $k['is_kosong'] ? '-' : ($k['jumlah_harus_dibayar'] === 0 ? '0' : '-'); ?>
                                    <?php else: ?>
                                        <?= $k['jumlah_harus_dibayar'] < 0 ? '-' . number_format(abs($k['jumlah_harus_dibayar']), 0, ',', '.') : number_format($k['jumlah_harus_dibayar'], 0, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Kolom Bulan JAN - DES 2026 -->
                                <?php foreach ($bulan_labels as $b_key => $b_lbl): 
                                    $val = (float)($row[$b_key] ?? 0);
                                    if (!$k['is_kosong']) {
                                        $tot_bulan[$b_key] += $val;
                                    }
                                ?>
                                <td class="p-0 border border-gray-300 text-right font-mono transition <?= $val > 0 ? 'bg-emerald-50/90 text-emerald-800 font-bold hover:bg-emerald-100' : 'hover:bg-yellow-50/80 text-gray-400'; ?>">
                                    <?php if ($k['is_kosong']): ?>
                                        <span class="text-gray-300 block py-1.5 px-2">-</span>
                                    <?php elseif ($can_manage): ?>
                                        <button type="button" 
                                                onclick='bukaModalEditBulan(<?= json_encode($row); ?>, "<?= $b_key; ?>", "<?= $b_lbl; ?>", <?= (float)$val; ?>)'
                                                class="w-full h-full py-1.5 px-2 rounded flex items-center justify-end gap-1 group/m transition cursor-pointer" 
                                                title="Klik untuk input/edit setoran Bulan <?= $b_lbl; ?> <?= $tahun_aktif; ?>">
                                            <span class="<?= $val > 0 ? 'text-emerald-700 font-bold' : 'text-gray-300 group-hover/m:text-amber-700'; ?>"><?= $val > 0 ? number_format($val, 0, ',', '.') : '-'; ?></span>
                                            <i class="fa-solid fa-pen-to-square text-[9px] opacity-0 group-hover/m:opacity-90 text-amber-600 shrink-0"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="block py-1.5 px-2"><?= $val > 0 ? number_format($val, 0, ',', '.') : '-'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>

                                <!-- Sisa Tunggakan Uang 2026 (Merah jika menunggak/minus) -->
                                <td class="p-2 border border-gray-300 text-right font-mono <?= (!$k['is_kosong'] && $k['sisa_kurang_2026'] < 0) ? 'text-red-600 font-bold bg-red-50' : 'text-gray-800 font-bold'; ?>">
                                    <?php if ($k['is_kosong']): ?>
                                        -
                                    <?php else: ?>
                                        <?= $k['sisa_kurang_2026'] < 0 ? '-' . number_format(abs($k['sisa_kurang_2026']), 0, ',', '.') : number_format($k['sisa_kurang_2026'], 0, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Keterangan -->
                                <td class="p-2 border border-gray-300 text-center text-[11px] text-gray-600 italic">
                                    <?= htmlspecialchars($row['keterangan'] ?? ''); ?>
                                </td>

                                <!-- Tombol Aksi -->
                                <?php if ($can_manage): ?>
                                <td class="p-1.5 border border-gray-300 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick='bukaModalBayarKhusus(<?= json_encode($row); ?>)' title="Catat Setoran" class="p-1 text-blue-600 hover:text-blue-900 hover:bg-blue-100 rounded">
                                            <i class="fa-solid fa-coins"></i>
                                        </button>
                                        <button onclick='bukaModalEditWarga(<?= json_encode($row); ?>)' title="Edit Warga" class="p-1 text-amber-600 hover:text-amber-900 hover:bg-amber-100 rounded">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" action="keuangan.php?tab=iuran&tahun=<?= $tahun_aktif; ?>" onsubmit="return confirm('Hapus data warga blok <?= $row['blok']; ?> dari rekap iuran?')" class="inline">
                                            <input type="hidden" name="action" value="hapus_warga">
                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                            <button type="submit" title="Hapus" class="p-1 text-red-500 hover:text-red-800 hover:bg-red-100 rounded">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-yellow-200 font-bold border-t-2 border-yellow-600 text-gray-900">
                        <tr>
                            <td colspan="4" class="p-2.5 text-right border border-yellow-600">JUMLAH TOTAL :</td>
                            <td class="p-2.5 text-right border border-yellow-600 font-mono">
                                <?= $tot_uang_lalu < 0 ? '-' . number_format(abs($tot_uang_lalu), 0, ',', '.') : number_format($tot_uang_lalu, 0, ',', '.'); ?>
                            </td>
                            <td class="p-2.5 text-center border border-yellow-600">-</td>
                            <td class="p-2.5 text-right border border-yellow-600 font-mono">
                                <?= $tot_harus_bayar < 0 ? '-' . number_format(abs($tot_harus_bayar), 0, ',', '.') : number_format($tot_harus_bayar, 0, ',', '.'); ?>
                            </td>
                            <?php foreach ($bulan_labels as $b_key => $b_lbl): ?>
                            <td class="p-2 border border-yellow-600 text-right font-mono">
                                <?= $tot_bulan[$b_key] > 0 ? number_format($tot_bulan[$b_key], 0, ',', '.') : '-'; ?>
                            </td>
                            <?php endforeach; ?>
                            <td class="p-2.5 text-right border border-yellow-600 font-mono text-red-700">
                                <?= $tot_sisa_kurang < 0 ? '-' . number_format(abs($tot_sisa_kurang), 0, ',', '.') : number_format($tot_sisa_kurang, 0, ',', '.'); ?>
                            </td>
                            <td class="border border-yellow-600"></td>
                            <?php if ($can_manage): ?>
                            <td class="border border-yellow-600"></td>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Panel Informasi Saluran Pembayaran Resmi & Rekapitulasi Status Hunian -->
            <div class="pt-2 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-extrabold text-sm text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-wallet text-blue-900"></i> Alat & Saluran Pembayaran Resmi Kas RT 31
                    </h3>
                    <span class="text-xs text-gray-500 hidden sm:inline">Pilihan transaksi nontunai praktis, aman & otomatis</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Card 1: QRIS Standar Pembayaran Nasional (GPN) -->
                    <div class="bg-gradient-to-br from-red-600 via-rose-700 to-red-900 text-white rounded-2xl p-5 shadow-lg relative overflow-hidden flex flex-col justify-between">
                        <i class="fa-solid fa-qrcode absolute -right-4 -bottom-4 text-8xl opacity-10 pointer-events-none"></i>
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="bg-white text-red-700 text-[10px] font-black uppercase px-2 py-0.5 rounded tracking-wider shadow-sm">QRIS</span>
                                    <span class="bg-red-800/80 text-white text-[10px] font-bold uppercase px-2 py-0.5 rounded">GPN</span>
                                </div>
                                <span class="text-[11px] text-red-200">Scan & Bayar Instan</span>
                            </div>

                            <div class="bg-white text-gray-900 rounded-xl p-3 shadow-inner flex flex-col sm:flex-row items-center gap-3">
                                <div class="relative group cursor-pointer shrink-0" onclick="bukaModalQRIS()" title="Klik untuk memperbesar QRIS">
                                    <img src="qris_kas_rt31.jpg" alt="QRIS KAS RT31 GRAHA KALIMAS" class="w-24 h-24 sm:w-28 sm:h-28 object-contain rounded-lg border border-gray-200 shadow-sm transition-transform duration-200 group-hover:scale-105 bg-white">
                                    <span class="absolute inset-0 bg-black/30 rounded-lg opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold transition">
                                        <i class="fa-solid fa-magnifying-glass-plus text-base"></i>
                                    </span>
                                </div>
                                <div class="w-full text-left">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-red-600">Merchant Resmi</span>
                                    <h4 class="font-extrabold text-sm text-gray-900 leading-tight">KAS RT31 GRAHA KALIMAS</h4>
                                    <p class="text-[11px] text-gray-500 font-mono mt-0.5">NMID: <strong class="text-gray-800">ID1026545858353</strong></p>
                                    
                                    <div class="flex items-center gap-1.5 mt-2">
                                        <button type="button" onclick="bukaModalQRIS()" class="bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold py-1.5 px-2.5 rounded-lg shadow-sm transition flex items-center gap-1">
                                            <i class="fa-solid fa-expand"></i> Perbesar QRIS
                                        </button>
                                        <a href="qris_kas_rt31.jpg" download="QRIS_KAS_RT31_GRAHA_KALIMAS.jpg" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-[11px] font-bold py-1.5 px-2 rounded-lg transition flex items-center gap-1" title="Unduh File QRIS">
                                            <i class="fa-solid fa-download"></i> Unduh
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-white/20">
                            <p class="text-[10px] text-red-100 mb-1.5">Mendukung Pembayaran Seluruh Bank & E-Wallet:</p>
                            <div class="flex flex-wrap gap-1 text-[9px] font-bold text-white/90">
                                <span class="bg-white/20 px-1.5 py-0.5 rounded">BCA</span>
                                <span class="bg-white/20 px-1.5 py-0.5 rounded">Mandiri</span>
                                <span class="bg-white/20 px-1.5 py-0.5 rounded">BRI</span>
                                <span class="bg-white/20 px-1.5 py-0.5 rounded">GoPay</span>
                                <span class="bg-white/20 px-1.5 py-0.5 rounded">OVO</span>
                                <span class="bg-white/20 px-1.5 py-0.5 rounded">DANA</span>
                                <span class="bg-white/20 px-1.5 py-0.5 rounded">ShopeePay</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Rekening BCA Resmi RT -->
                    <div class="bg-gradient-to-br from-blue-900 via-blue-950 to-indigo-950 text-white rounded-2xl p-5 shadow-lg relative overflow-hidden flex flex-col justify-between">
                        <i class="fa-solid fa-building-columns absolute -right-4 -bottom-4 text-8xl opacity-10 pointer-events-none"></i>
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="bg-yellow-400 text-blue-950 text-[10px] font-black uppercase px-2 py-0.5 rounded tracking-wider">Transfer Bank</span>
                                <span class="text-xs text-blue-200">Rekening Resmi RT 31</span>
                            </div>
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-yellow-400 text-2xl font-bold shrink-0">
                                    <i class="fa-solid fa-building-columns"></i>
                                </div>
                                <div>
                                    <h4 class="font-extrabold text-base tracking-wide text-white">BANK CENTRAL ASIA (BCA)</h4>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="font-mono text-xl font-black text-yellow-300 tracking-wider">7285861195</span>
                                        <button type="button" onclick="navigator.clipboard.writeText('7285861195'); alert('Nomor rekening BCA 7285861195 berhasil disalin!');" class="text-xs bg-white/20 hover:bg-white/30 text-white px-2 py-0.5 rounded transition" title="Salin No Rekening">
                                            <i class="fa-regular fa-copy"></i> Salin
                                        </button>
                                    </div>
                                    <p class="text-xs text-blue-200 mt-0.5">Atas Nama: <strong class="text-white font-bold">Maria Suparmijati</strong></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3 border-t border-white/15 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div class="bg-white/5 p-2.5 rounded-xl">
                                <span class="text-blue-300 text-[10px] block font-medium">Konfirmasi 1:</span>
                                <strong class="text-white text-xs">Ibu MARIA.S / MANIK</strong>
                                <a href="https://wa.me/6281237418441" target="_blank" class="text-emerald-400 block font-mono hover:underline text-[11px] mt-0.5"><i class="fa-brands fa-whatsapp"></i> 081237418441</a>
                            </div>
                            <div class="bg-white/5 p-2.5 rounded-xl">
                                <span class="text-blue-300 text-[10px] block font-medium">Konfirmasi 2:</span>
                                <strong class="text-white text-xs">Ibu MAYDIWATI</strong>
                                <a href="https://wa.me/6281250468187" target="_blank" class="text-emerald-400 block font-mono hover:underline text-[11px] mt-0.5"><i class="fa-brands fa-whatsapp"></i> 081250468187</a>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Statistik Status Hunian (Kotak Kanan Bawah Spreadsheet) -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                    <i class="fa-solid fa-chart-pie text-yellow-500"></i> Rekapitulasi Status Hunian
                                </h4>
                                <span class="text-xs bg-blue-50 text-blue-800 font-bold px-2.5 py-1 rounded-full border border-blue-200">Total 80 Kavling</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5 text-xs">
                                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                                    <div class="text-emerald-700 text-[11px] font-semibold flex items-center gap-1.5"><i class="fa-solid fa-house-user"></i> Penghuni Aktif</div>
                                    <div class="text-2xl font-black text-emerald-800 mt-1"><?= $counts_status['Penghuni'] ?? 64; ?> <span class="text-xs font-normal text-emerald-600">Rumah</span></div>
                                </div>
                                <div class="p-3 bg-gray-100 border border-gray-300 rounded-xl">
                                    <div class="text-gray-600 text-[11px] font-semibold flex items-center gap-1.5"><i class="fa-solid fa-door-closed"></i> Kosong (Bebas Tagihan)</div>
                                    <div class="text-2xl font-black text-gray-700 mt-1"><?= $counts_status['Kosong'] ?? 10; ?> <span class="text-xs font-normal text-gray-500">Kavling</span></div>
                                </div>
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
                                    <div class="text-amber-800 text-[11px] font-semibold flex items-center gap-1.5"><i class="fa-solid fa-file-contract"></i> Dikontrak</div>
                                    <div class="text-2xl font-black text-amber-900 mt-1"><?= $counts_status['dikontrak'] ?? 4; ?> <span class="text-xs font-normal text-amber-700">Rumah</span></div>
                                </div>
                                <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl">
                                    <div class="text-blue-800 text-[11px] font-semibold flex items-center gap-1.5"><i class="fa-solid fa-city"></i> Rumah ke 2</div>
                                    <div class="text-2xl font-black text-blue-900 mt-1"><?= $counts_status['Rumah ke 2'] ?? 2; ?> <span class="text-xs font-normal text-blue-700">Rumah</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-gray-100 text-[11px] text-gray-500 flex items-center justify-between">
                            <span>Data tersinkronisasi kependudukan RT</span>
                            <a href="export_iuran.php?tahun=<?= $tahun_aktif; ?>" class="text-green-700 font-bold hover:underline flex items-center gap-1">
                                <i class="fa-solid fa-file-excel"></i> Unduh Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keterangan & Rumus Perhitungan -->
            <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 text-xs text-blue-900 space-y-1">
                <div class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-circle-info text-blue-600"></i> Aturan & Rumus Perhitungan Rekap Iuran:</div>
                <ul class="list-disc list-inside space-y-0.5 text-blue-800 text-[11px] ml-1">
                    <li><strong>Status Kosong:</strong> Rumah/kavling dengan keterangan <em>Kosong</em> tidak dikenakan tagihan iuran (ditampilkan tanda -).</li>
                    <li><strong>Kewajiban Bulan <?= $tahun_aktif; ?>:</strong> Dihitung dari <code>Tunggakan Bulan <?= $tahun_aktif - 1; ?> - 12</code> (misal: 0 - 12 = -12 bln; atau -24 - 12 = -36 bln).</li>
                    <li><strong>Jumlah Harus Dibayar:</strong> <code>Kewajiban Bulan × Tarif Iuran Bulanan (Rp <?= number_format($tarif_bulanan, 0, ',', '.'); ?>)</code>.</li>
                    <li><strong>Sisa Kekurangan Iuran:</strong> <code>Jumlah Harus Dibayar + Total Setoran (Jan s/d Des)</code> (angka minus berwarna merah menandakan masih memiliki sisa tunggakan).</li>
                    <li><strong>Sinkronisasi Kas Otomatis:</strong> Setiap pembayaran iuran yang disimpan otomatis tercatat ke <strong>Buku Kas Umum</strong> sebagai <em>Kas Masuk</em>.</li>
                </ul>
            </div>

        </div>

        <?php else: ?>
        <!-- ========================================== -->
        <!-- KONTEN TAB: BUKU KAS UMUM RT               -->
        <!-- ========================================== -->
        <div class="p-4 space-y-4">
            
            <!-- Card Saldo -->
            <div class="bg-gradient-to-r from-blue-800 to-blue-950 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
                <i class="fa-solid fa-wallet absolute -right-4 -bottom-4 text-8xl text-white opacity-15"></i>
                <p class="text-xs text-blue-200 font-medium tracking-wide">TOTAL SALDO KAS RT 31</p>
                <h2 class="text-3xl font-extrabold mt-1">Rp <?= number_format($saldo_kas, 0, ',', '.'); ?></h2>
                <div class="flex gap-6 mt-4 text-xs">
                    <div>
                        <p class="text-blue-200">Total Masuk</p>
                        <p class="font-bold text-green-300 text-sm">+ Rp <?= number_format($tot_masuk, 0, ',', '.'); ?></p>
                    </div>
                    <div>
                        <p class="text-blue-200">Total Keluar</p>
                        <p class="font-bold text-red-300 text-sm">- Rp <?= number_format($tot_keluar, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Banner Alat Pembayaran Resmi Kas RT 31 -->
            <div class="bg-gradient-to-r from-red-50 via-white to-blue-50 border border-gray-200 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3.5">
                    <div class="w-14 h-14 bg-white rounded-xl shadow-sm border border-red-200 p-1 flex items-center justify-center shrink-0 cursor-pointer group" onclick="bukaModalQRIS()" title="Klik untuk membuka QRIS">
                        <img src="qris_kas_rt31.jpg" alt="QRIS RT 31" class="w-full h-full object-contain rounded group-hover:scale-105 transition">
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="bg-red-600 text-white text-[10px] font-black uppercase px-2 py-0.5 rounded shadow-sm">QRIS RESMI</span>
                            <span class="text-xs font-bold text-gray-800">KAS RT31 GRAHA KALIMAS</span>
                        </div>
                        <p class="text-[11px] text-gray-600 mt-0.5">Alat pembayaran nontunai resmi RT 31 via QRIS (Semua Bank & E-Wallet) atau Transfer BCA <strong class="font-mono text-blue-900">7285861195</strong> a/n Maria Suparmijati.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="bukaModalQRIS()" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2 px-3.5 rounded-xl shadow-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-qrcode"></i> Buka QRIS Kas RT
                    </button>
                    <a href="qris_kas_rt31.jpg" download="QRIS_KAS_RT31_GRAHA_KALIMAS.jpg" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold py-2 px-3 rounded-xl transition flex items-center gap-1.5">
                        <i class="fa-solid fa-download"></i> Unduh
                    </a>
                </div>
            </div>

            <!-- Tombol Aksi Transaksi Kas -->
            <?php if ($can_manage): ?>
            <div class="flex gap-2">
                <a href="tambah_keuangan.php" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl flex justify-center items-center gap-2 shadow-sm transition">
                    <i class="fa-solid fa-plus"></i> Catat Transaksi Kas
                </a>
            </div>
            <?php endif; ?>

            <!-- Riwayat Transaksi Kas Umum -->
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-sm text-gray-700">Riwayat Transaksi Kas Umum</h3>
                    <span class="text-xs text-gray-500">Termasuk penerimaan iuran warga otomatis</span>
                </div>

                <div class="space-y-2">
                    <?php if (mysqli_num_rows($q_transaksi) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($q_transaksi)): ?>
                        <div class="bg-white border border-gray-200 p-3.5 rounded-xl shadow-sm flex items-center justify-between hover:shadow transition">
                            <div class="flex items-center gap-3">
                                <?php if ($row['jenis'] == 'Masuk'): ?>
                                    <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-lg shrink-0">
                                        <i class="fa-solid fa-arrow-down"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="w-10 h-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-lg shrink-0">
                                        <i class="fa-solid fa-arrow-up"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($row['keterangan']); ?></h4>
                                    <p class="text-[11px] text-gray-500"><?= date('d M Y', strtotime($row['tanggal'])); ?></p>
                                </div>
                            </div>
                            <div class="text-right shrink-0 flex items-center gap-3">
                                <div>
                                    <?php if ($row['jenis'] == 'Masuk'): ?>
                                        <p class="font-bold text-green-600 text-sm">+ Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></p>
                                    <?php else: ?>
                                        <p class="font-bold text-red-600 text-sm">- Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($can_manage): ?>
                                <div class="flex items-center gap-1 pl-2 border-l border-gray-200">
                                    <button type="button" onclick='bukaModalEditKas(<?= json_encode($row); ?>)' title="Edit Transaksi Kas" class="p-1.5 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-lg transition">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <form method="POST" action="keuangan.php?tab=kas" onsubmit="return confirm('Hapus transaksi ini dari buku kas?')" class="inline">
                                        <input type="hidden" name="action" value="hapus_kas">
                                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                        <button type="submit" title="Hapus Transaksi" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-10 bg-gray-50 rounded-xl border border-gray-200">
                            <i class="fa-solid fa-receipt text-4xl text-gray-300 mb-2"></i>
                            <p class="text-gray-400 text-sm">Belum ada catatan transaksi kas.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php endif; ?>

        <!-- Bottom Navigation Bar -->
        <div class="fixed bottom-0 left-0 right-0 max-w-7xl mx-auto bg-white border-t border-gray-200 flex justify-around py-3 text-gray-500 text-xs shadow-lg z-30">
            <a href="index.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="mt-1">Home</span>
            </a>
            <a href="warga.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-users text-lg"></i>
                <span class="mt-1">Warga</span>
            </a>
            <a href="aduan.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-comments text-lg"></i>
                <span class="mt-1">Aduan</span>
            </a>
            <a href="keuangan.php" class="flex flex-col items-center text-blue-600">
                <i class="fa-solid fa-wallet text-lg"></i>
                <span class="mt-1 font-bold">Keuangan</span>
            </a>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: CATAT BAYAR IURAN                 -->
    <!-- ========================================== -->
    <div id="modalBayar" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-money-bill-wave text-blue-600"></i> Catat Pembayaran Iuran
                </h3>
                <button onclick="tutupModalBayar()" class="text-gray-400 hover:text-gray-700 text-lg"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" action="keuangan.php?tab=iuran&tahun=<?= $tahun_aktif; ?>" class="mt-4 space-y-4">
                <input type="hidden" name="action" value="catat_bayar">

                <!-- Pilih Warga -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Blok / Warga <span class="text-red-500">*</span></label>
                    <select id="input_bayar_iuran_id" name="iuran_id" required class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Pilih Warga --</option>
                        <?php foreach ($semua_pilihan_iuran as $item): 
                            $is_kosong_item = (isset($item['kalkulasi']) ? $item['kalkulasi']['is_kosong'] : (strtolower(trim($item['keterangan'] ?? '')) === 'kosong'));
                        ?>
                            <option value="<?= $item['id']; ?>">Blok <?= htmlspecialchars($item['blok']); ?> - <?= htmlspecialchars($item['nama']); ?> <?= $is_kosong_item ? '(Kosong)' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Pilih Bulan -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Bulan Pembayaran <span class="text-red-500">*</span></label>
                    <select id="input_bayar_bulan" name="bulan" required class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <?php foreach ($bulan_labels as $m_key => $m_lbl): ?>
                            <option value="<?= $m_key; ?>" <?= strtolower(date('M')) == substr($m_key, 0, 3) ? 'selected' : ''; ?>><?= $m_lbl; ?> (Tahun <?= $tahun_aktif; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Input Nominal Pembayaran -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nominal Pembayaran (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 text-gray-400 font-bold text-sm">Rp</span>
                        <input type="number" id="input_bayar_nominal" name="nominal" value="<?= (int)$tarif_bulanan; ?>" min="0" step="1000" required class="w-full border border-gray-300 rounded-xl pl-11 pr-3 py-2.5 text-sm font-bold text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Default otomatis terisi tarif bulanan aktif: Rp <?= number_format($tarif_bulanan, 0, ',', '.'); ?></p>
                </div>

                <!-- Checkbox Sinkronisasi Otomatis ke Kas RT -->
                <div class="bg-blue-50 p-3 rounded-xl border border-blue-200">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="sync_kas" value="1" checked class="mt-0.5 w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                        <span class="text-xs text-blue-900 leading-snug">
                            <strong>Sinkronkan otomatis ke Kas RT</strong><br>
                            <span class="text-blue-700 text-[11px]">Pemasukan ini otomatis tercatat sebagai Kas Masuk di Buku Kas Umum.</span>
                        </span>
                    </label>
                </div>

                <!-- Info Alat Pembayaran QRIS RT -->
                <div class="bg-gradient-to-r from-red-50 to-amber-50 p-3 rounded-xl border border-red-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <img src="qris_kas_rt31.jpg" alt="QRIS RT" class="w-9 h-9 rounded-lg object-contain bg-white border border-red-200 shadow-sm p-0.5 cursor-pointer" onclick="bukaModalQRIS()" title="Perbesar QRIS">
                        <div>
                            <p class="text-xs font-bold text-gray-800">Alat Bayar: QRIS Kas RT 31</p>
                            <p class="text-[10px] text-gray-600">Scan QRIS KAS RT31 GRAHA KALIMAS</p>
                        </div>
                    </div>
                    <button type="button" onclick="bukaModalQRIS()" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm transition flex items-center gap-1">
                        <i class="fa-solid fa-qrcode"></i> Tampilkan QRIS
                    </button>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="tutupModalBayar()" class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm transition">Batal</button>
                    <button type="submit" class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-sm shadow-md transition">Simpan Setoran</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: ATUR TARIF IURAN BULANAN          -->
    <!-- ========================================== -->
    <div id="modalTarif" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl relative">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-gear text-yellow-500"></i> Pengaturan Tarif Iuran
                </h3>
                <button onclick="tutupModalTarif()" class="text-gray-400 hover:text-gray-700 text-lg"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" action="keuangan.php?tab=iuran&tahun=<?= $tahun_aktif; ?>" class="mt-4 space-y-4">
                <input type="hidden" name="action" value="simpan_tarif">

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tahun Anggaran</label>
                    <input type="number" name="tahun" value="<?= $tahun_aktif; ?>" readonly class="w-full bg-gray-100 border border-gray-300 rounded-xl px-3 py-2 text-sm font-semibold text-gray-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tarif Iuran per Bulan (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 text-gray-400 font-bold text-sm">Rp</span>
                        <input type="number" name="tarif_bulanan" value="<?= (int)$tarif_bulanan; ?>" min="1000" step="1000" required class="w-full border border-gray-300 rounded-xl pl-11 pr-3 py-2.5 text-sm font-bold text-gray-800 focus:ring-2 focus:ring-yellow-500 focus:outline-none">
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Mengubah nilai ini akan memperbarui perhitungan tagihan seluruh warga untuk tahun <?= $tahun_aktif; ?>.</p>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="tutupModalTarif()" class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm transition">Batal</button>
                    <button type="submit" class="w-1/2 bg-yellow-500 hover:bg-yellow-600 text-yellow-950 font-bold py-2.5 rounded-xl text-sm shadow-md transition">Simpan Tarif</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 3: TAMBAH WARGA IURAN                -->
    <!-- ========================================== -->
    <div id="modalTambahWarga" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-gray-800"></i> Tambah Blok Warga
                </h3>
                <button onclick="tutupModalTambahWarga()" class="text-gray-400 hover:text-gray-700 text-lg"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" action="keuangan.php?tab=iuran&tahun=<?= $tahun_aktif; ?>" class="mt-4 space-y-3">
                <input type="hidden" name="action" value="tambah_warga">
                <input type="hidden" name="tahun" value="<?= $tahun_aktif; ?>">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Blok Rumah <span class="text-red-500">*</span></label>
                        <input type="text" name="blok" placeholder="Misal: I20" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Tunggakan Lalu (Bulan)</label>
                        <input type="number" id="tambah_warga_tunggakan" name="tunggakan_bulan_lalu" value="0" oninput="hitungPreviewTambahWarga()" placeholder="0 atau minus" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono">
                    </div>
                </div>

                <!-- Preview Rumus Tambah Warga -->
                <div class="bg-gray-900 text-white p-3 rounded-xl text-xs space-y-1">
                    <div class="text-yellow-400 font-bold flex items-center gap-1 text-[11px]">
                        <i class="fa-solid fa-calculator"></i> Perhitungan Otomatis Sesuai Rumus:
                    </div>
                    <div class="flex justify-between text-gray-300">
                        <span>Total Harus Dibayar (Des <?= $tahun_aktif; ?>):</span>
                        <span id="preview_tambah_harus_bayar" class="font-mono font-bold text-yellow-300">-Rp 240.000</span>
                    </div>
                    <div class="flex justify-between text-gray-300">
                        <span>Kekurangan Uang s/d Des <?= $tahun_aktif; ?>:</span>
                        <span id="preview_tambah_sisa_kurang" class="font-mono font-bold text-red-300">-Rp 240.000</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nama Kepala Keluarga / Penghuni <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" placeholder="Misal: Bpk. Ahmad" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan Khusus</label>
                    <input type="text" name="keterangan" placeholder="Kosong / dikontrak / Rumah ke 2 / lainnya" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <p class="text-[11px] text-gray-500 mt-1">Ketik <strong>Kosong</strong> jika rumah tidak berpenghuni agar bebas tagihan iuran.</p>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="tutupModalTambahWarga()" class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm transition">Batal</button>
                    <button type="submit" class="w-1/2 bg-gray-900 hover:bg-black text-white font-bold py-2.5 rounded-xl text-sm shadow-md transition">Simpan Warga</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 4: EDIT DATA WARGA IURAN             -->
    <!-- ========================================== -->
    <div id="modalEditWarga" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-amber-600"></i> Edit Data Warga & NIK
                </h3>
                <button onclick="tutupModalEditWarga()" class="text-gray-400 hover:text-gray-700 text-lg"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" action="keuangan.php?tab=iuran&tahun=<?= $tahun_aktif; ?>" class="mt-4 space-y-3">
                <input type="hidden" name="action" value="edit_warga">
                <input type="hidden" id="edit_warga_id" name="id" value="">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Blok Rumah <span class="text-red-500">*</span></label>
                        <input type="text" id="edit_warga_blok" name="blok" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Tunggakan s/d Des <?= $tahun_aktif - 1; ?> (Bulan)</label>
                        <input type="number" id="edit_warga_tunggakan" name="tunggakan_bulan_lalu" oninput="hitungPreviewEditWarga()" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                    </div>
                </div>

                <!-- Preview Rumus Edit Warga -->
                <div class="bg-gray-900 text-white p-3 rounded-xl text-xs space-y-1">
                    <div class="text-yellow-400 font-bold flex items-center gap-1 text-[11px]">
                        <i class="fa-solid fa-calculator"></i> Perhitungan Otomatis Sesuai Rumus:
                    </div>
                    <div class="flex justify-between text-gray-300">
                        <span>Total Harus Dibayar (Des <?= $tahun_aktif; ?>):</span>
                        <span id="preview_edit_harus_bayar" class="font-mono font-bold text-yellow-300">-</span>
                    </div>
                    <div class="flex justify-between text-gray-300">
                        <span>Kekurangan Uang s/d Des <?= $tahun_aktif; ?>:</span>
                        <span id="preview_edit_sisa_kurang" class="font-mono font-bold text-red-300">-</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nama Penghuni <span class="text-red-500">*</span></label>
                    <input type="text" id="edit_warga_nama" name="nama" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Induk Kependudukan (NIK)</label>
                    <input type="text" id="edit_warga_nik" name="nik" placeholder="16 digit NIK" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <p class="text-[10px] text-gray-500 mt-1">Dapat diisi NIK asli KTP jika berkas kependudukan telah diterima.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan (Kosong / dikontrak / Rumah ke 2)</label>
                    <input type="text" id="edit_warga_keterangan" name="keterangan" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="tutupModalEditWarga()" class="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm transition">Batal</button>
                    <button type="submit" class="w-1/2 bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 rounded-xl text-sm shadow-md transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 4B: EDIT KEKURANGAN IURAN DES 2025   -->
    <!-- ========================================== -->
    <div id="modalEditTunggakan" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <!-- Header Modal -->
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-calculator text-amber-600"></i> Input / Edit Kekurangan Iuran (Des <?= $tahun_aktif - 1; ?>)
                </h3>
                <button type="button" onclick="tutupModalEditTunggakan()" class="text-gray-400 hover:text-gray-700 text-lg"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" action="keuangan.php?tab=iuran&tahun=<?= $tahun_aktif; ?>" class="mt-4 space-y-4">
                <input type="hidden" name="action" value="update_tunggakan">
                <input type="hidden" id="tunggakan_modal_id" name="id" value="">

                <!-- Info Warga & Blok -->
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex justify-between items-center">
                    <div>
                        <span class="text-[11px] text-amber-800 font-semibold block uppercase tracking-wider">Kavling & Kepala Keluarga:</span>
                        <span id="tunggakan_modal_nama_label" class="font-bold text-sm text-gray-900">Blok -</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-gray-500 block">Tarif Bulanan Aktif</span>
                        <span class="text-xs font-bold font-mono text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded">Rp <?= number_format($tarif_bulanan, 0, ',', '.'); ?>/bln</span>
                    </div>
                </div>

                <!-- Pemilih Status Cepat: Kurang / Lunas / Lebih -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Pilih Kategori Status:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" id="btn_status_kurang" onclick="setTipeTunggakan('kurang')" class="py-2 px-2 text-xs font-bold rounded-xl border border-red-300 bg-red-50 text-red-700 hover:bg-red-100 flex items-center justify-center gap-1 transition">
                            <i class="fa-solid fa-circle-minus"></i> Kurang Bayar (-)
                        </button>
                        <button type="button" id="btn_status_lunas" onclick="setTipeTunggakan('lunas')" class="py-2 px-2 text-xs font-bold rounded-xl border border-green-300 bg-green-50 text-green-700 hover:bg-green-100 flex items-center justify-center gap-1 transition">
                            <i class="fa-solid fa-circle-check"></i> Lunas (0)
                        </button>
                        <button type="button" id="btn_status_lebih" onclick="setTipeTunggakan('lebih')" class="py-2 px-2 text-xs font-bold rounded-xl border border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100 flex items-center justify-center gap-1 transition">
                            <i class="fa-solid fa-circle-plus"></i> Lebih Bayar (+)
                        </button>
                    </div>
                </div>

                <!-- Input Angka Bulan -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-xs font-bold text-gray-700">Jumlah Kekurangan (dalam Bulan) <span class="text-red-500">*</span></label>
                        <span id="tunggakan_modal_indicator" class="text-[11px] font-bold font-mono text-red-600 bg-red-50 px-2.5 py-0.5 rounded-full border border-red-200">
                            -0 Bulan
                        </span>
                    </div>
                    <div class="relative">
                        <input type="number" 
                               id="tunggakan_modal_input" 
                               name="tunggakan_bulan_lalu" 
                               required 
                               oninput="hitungOtomatisRumusTunggakan()" 
                               placeholder="Contoh: -12 jika kurang 12 bulan" 
                               class="w-full border-2 border-amber-400 rounded-xl px-4 py-2.5 text-base font-bold font-mono text-gray-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    
                    <!-- Tombol Shortcut Preset Cepat -->
                    <div class="flex flex-wrap gap-1.5 mt-2 items-center">
                        <span class="text-[10px] text-gray-400 font-semibold">Preset Cepat:</span>
                        <button type="button" onclick="setPresetTunggakan(0)" class="text-[11px] px-2.5 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-mono font-bold border border-gray-200">0 (Lunas)</button>
                        <button type="button" onclick="setPresetTunggakan(-6)" class="text-[11px] px-2.5 py-0.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-mono font-bold border border-red-200">-6 bln</button>
                        <button type="button" onclick="setPresetTunggakan(-12)" class="text-[11px] px-2.5 py-0.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-mono font-bold border border-red-200">-12 bln</button>
                        <button type="button" onclick="setPresetTunggakan(-24)" class="text-[11px] px-2.5 py-0.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-mono font-bold border border-red-200">-24 bln</button>
                        <button type="button" onclick="setPresetTunggakan(-36)" class="text-[11px] px-2.5 py-0.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-mono font-bold border border-red-200">-36 bln</button>
                        <button type="button" onclick="setPresetTunggakan(-60)" class="text-[11px] px-2.5 py-0.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-mono font-bold border border-red-200">-60 bln</button>
                    </div>
                </div>

                <!-- KOTAK RUMUS PERHITUNGAN OTOMATIS REAL-TIME -->
                <div class="bg-gray-900 text-white rounded-2xl p-4 space-y-2.5 shadow-xl text-xs border border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-700 pb-2">
                        <span class="font-bold text-yellow-400 flex items-center gap-1.5 text-xs">
                            <i class="fa-solid fa-square-root-variable"></i> RUMUS PERHITUNGAN OTOMATIS
                        </span>
                        <span class="text-[10px] text-gray-400 font-mono">Tahun <?= $tahun_aktif; ?></span>
                    </div>

                    <!-- Step 1: Kekurangan Uang Des 2025 -->
                    <div class="flex justify-between items-center">
                        <span class="text-gray-300">1. Kekurangan Uang s/d Des <?= $tahun_aktif - 1; ?>:</span>
                        <span id="calc_uang_lalu" class="font-mono font-bold text-yellow-300 text-sm">Rp 0</span>
                    </div>
                    <div class="text-[10px] text-gray-400 -mt-1 font-mono" id="calc_uang_lalu_detail">
                        (0 bln × Rp <?= number_format($tarif_bulanan, 0, ',', '.'); ?>)
                    </div>

                    <!-- Step 2: Kewajiban Bulan Des 2026 -->
                    <div class="flex justify-between items-center pt-1.5 border-t border-gray-800">
                        <span class="text-gray-300">2. Kewajiban Bulan s/d Des <?= $tahun_aktif; ?>:</span>
                        <span id="calc_kewajiban_bulan" class="font-mono font-bold text-blue-300 text-sm">-12 Bulan</span>
                    </div>
                    <div class="text-[10px] text-gray-400 -mt-1 font-mono" id="calc_kewajiban_bulan_detail">
                        (Tunggakan 0 bln - 12 bln tahun berjalan)
                    </div>

                    <!-- Step 3: Total Jumlah yang Harus Dibayar Des 2026 -->
                    <div class="p-2.5 bg-gray-800/80 rounded-xl border border-yellow-500/40">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-yellow-400">3. Total Jumlah yang Harus Dibayar:</span>
                            <span id="calc_harus_bayar" class="font-mono font-bold text-base text-yellow-300">-Rp 240.000</span>
                        </div>
                        <div class="text-[10px] text-gray-400 font-mono mt-0.5" id="calc_harus_bayar_detail">
                            (-12 bln × Rp <?= number_format($tarif_bulanan, 0, ',', '.'); ?>)
                        </div>
                    </div>

                    <!-- Info Setoran Masuk 2026 -->
                    <div class="flex justify-between items-center text-gray-400 pt-0.5 px-1">
                        <span>Total Setoran Masuk di <?= $tahun_aktif; ?> (Jan - Des):</span>
                        <span id="calc_total_bayar_2026" class="font-mono font-semibold text-emerald-400">+Rp 0</span>
                    </div>

                    <!-- Step 4: Jumlah Kekurangan Iuran dalam Uang s/d Des 2026 -->
                    <div class="p-3 bg-gradient-to-r from-red-950/70 to-yellow-950/70 rounded-xl border border-red-700/60 shadow">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="font-bold text-white block">4. Jumlah Kekurangan Uang (s/d Des <?= $tahun_aktif; ?>):</span>
                                <span class="text-[10px] text-gray-300 font-mono" id="calc_sisa_kurang_detail">(Total Harus Dibayar + Setoran)</span>
                            </div>
                            <span id="calc_sisa_kurang" class="font-mono font-bold text-lg text-red-400">-Rp 240.000</span>
                        </div>
                    </div>
                </div>

                <!-- Tombol Batal & Simpan -->
                <div class="flex gap-2.5 pt-2">
                    <button type="button" onclick="tutupModalEditTunggakan()" class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm transition">
                        Batal
                    </button>
                    <button type="submit" class="w-2/3 bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 rounded-xl text-sm shadow-md flex items-center justify-center gap-2 transition">
                        <i class="fa-solid fa-check"></i> Simpan & Perbarui Rekap
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 4C: EDIT SETORAN BULANAN (JAN - DES) -->
    <!-- ========================================== -->
    <div id="modalEditBulan" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative animate-fade-in">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-money-bill-transfer text-emerald-600"></i> Edit / Koreksi Setoran Bulanan
                </h3>
                <button type="button" onclick="tutupModalEditBulan()" class="text-gray-400 hover:text-gray-700 text-lg"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" action="keuangan.php?tab=iuran&tahun=<?= $tahun_aktif; ?>" class="mt-4 space-y-4">
                <input type="hidden" name="action" value="edit_iuran_bulan">
                <input type="hidden" id="edit_bulan_id" name="id" value="">
                <input type="hidden" id="edit_bulan_asal" name="bulan_asal" value="">

                <!-- Info Kavling & Warga -->
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] text-emerald-800 font-semibold uppercase tracking-wider block">Kavling / Warga:</span>
                        <span id="edit_bulan_nama_label" class="font-bold text-sm text-gray-900">Blok -</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-gray-500 block">Bulan Terpilih:</span>
                        <span id="edit_bulan_label" class="text-xs font-bold font-mono text-emerald-800 bg-white border border-emerald-300 px-2 py-0.5 rounded shadow-sm">JAN 2026</span>
                    </div>
                </div>

                <!-- Opsi Koreksi: Pindah ke Bulan Lain jika salah input bulan -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Penempatan Bulan Setoran:</label>
                    <select id="edit_bulan_target" name="target_bulan" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <?php foreach ($bulan_labels as $m_k => $m_l): ?>
                            <option value="<?= $m_k; ?>"><?= $m_l; ?> (Tahun <?= $tahun_aktif; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[10px] text-gray-500 mt-1">Bila ada salah letak bulan, pilih bulan tujuan di atas untuk memindahkan setoran.</p>
                </div>

                <!-- Input Nominal Pembayaran -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-xs font-bold text-gray-700">Nominal Setoran (Rp) <span class="text-red-500">*</span></label>
                        <span id="edit_bulan_nominal_sebelumnya" class="text-[11px] font-mono text-gray-500">
                            Sebelumnya: Rp 0
                        </span>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold text-sm">Rp</span>
                        <input type="number" 
                               id="edit_bulan_nominal" 
                               name="nominal" 
                               required 
                               min="0" 
                               step="1000"
                               placeholder="Contoh: 20000" 
                               class="w-full border-2 border-emerald-400 rounded-xl pl-10 pr-4 py-2.5 text-base font-bold font-mono text-gray-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    
                    <!-- Preset Cepat Nominal -->
                    <div class="flex flex-wrap gap-1.5 mt-2 items-center">
                        <span class="text-[10px] text-gray-400 font-semibold">Preset Cepat:</span>
                        <button type="button" onclick="setPresetNominalBulan(0)" class="text-[11px] px-2 py-0.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-mono font-bold border border-red-200">Rp 0 (Hapus)</button>
                        <button type="button" onclick="setPresetNominalBulan(<?= (int)$tarif_bulanan; ?>)" class="text-[11px] px-2 py-0.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg font-mono font-bold border border-emerald-200"><?= number_format($tarif_bulanan, 0, ',', '.'); ?></button>
                        <button type="button" onclick="setPresetNominalBulan(<?= (int)$tarif_bulanan * 2; ?>)" class="text-[11px] px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-mono font-bold border border-gray-200"><?= number_format($tarif_bulanan * 2, 0, ',', '.'); ?></button>
                        <button type="button" onclick="setPresetNominalBulan(100000)" class="text-[11px] px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-mono font-bold border border-gray-200">100.000</button>
                        <button type="button" onclick="setPresetNominalBulan(200000)" class="text-[11px] px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-mono font-bold border border-gray-200">200.000</button>
                        <button type="button" onclick="setPresetNominalBulan(<?= (int)$tarif_bulanan * 12; ?>)" class="text-[11px] px-2 py-0.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-800 rounded-lg font-mono font-bold border border-yellow-200"><?= number_format($tarif_bulanan * 12, 0, ',', '.'); ?> (1 Thn)</button>
                    </div>
                </div>

                <!-- Opsi Sinkronisasi Kas -->
                <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" name="sync_kas" value="1" checked class="mt-0.5 text-emerald-600 rounded focus:ring-emerald-500 w-4 h-4">
                        <span class="text-xs text-gray-700">
                            <strong>Sinkronkan otomatis ke Buku Kas Umum RT</strong>
                            <span class="block text-[10px] text-gray-500 mt-0.5">Otomatis memperbarui nilai pemasukan kas atau menghapus transaksi kas jika nominal diubah jadi Rp 0.</span>
                        </span>
                    </label>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex gap-2.5 pt-2">
                    <button type="button" onclick="tutupModalEditBulan()" class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm transition">
                        Batal
                    </button>
                    <button type="submit" class="w-2/3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-sm shadow-md flex items-center justify-center gap-2 transition">
                        <i class="fa-solid fa-check"></i> Simpan Koreksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 4D: EDIT TRANSAKSI KAS UMUM          -->
    <!-- ========================================== -->
    <div id="modalEditKas" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative animate-fade-in">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-blue-600"></i> Edit Transaksi Kas Umum
                </h3>
                <button type="button" onclick="tutupModalEditKas()" class="text-gray-400 hover:text-gray-700 text-lg"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" action="keuangan.php?tab=kas" class="mt-4 space-y-4">
                <input type="hidden" name="action" value="edit_kas">
                <input type="hidden" id="edit_kas_id" name="id" value="">

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Transaksi <span class="text-red-500">*</span></label>
                    <input type="date" id="edit_kas_tanggal" name="tanggal" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan / Uraian <span class="text-red-500">*</span></label>
                    <input type="text" id="edit_kas_keterangan" name="keterangan" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Jenis Transaksi</label>
                        <select id="edit_kas_jenis" name="jenis" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none font-bold">
                            <option value="Masuk">Pemasukan (Masuk)</option>
                            <option value="Keluar">Pengeluaran (Keluar)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nominal (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" id="edit_kas_nominal" name="nominal" required min="1" step="1000" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex gap-2.5 pt-2">
                    <button type="button" onclick="tutupModalEditKas()" class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm transition">
                        Batal
                    </button>
                    <button type="submit" class="w-2/3 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-sm shadow-md flex items-center justify-center gap-2 transition">
                        <i class="fa-solid fa-check"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 5: ALAT PEMBAYARAN QRIS RT 31        -->
    <!-- ========================================== -->
    <div id="modalQRIS" class="fixed inset-0 bg-black/75 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-lg">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-gray-900 text-base leading-tight">QRIS Kas RT 31 Graha Kalimas</h3>
                        <p class="text-[11px] text-gray-500">Standar Pembayaran Nasional (GPN / Bank Indonesia)</p>
                    </div>
                </div>
                <button onclick="tutupModalQRIS()" class="text-gray-400 hover:text-gray-700 text-xl w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="mt-4 space-y-4">
                <!-- Merchant Info Box -->
                <div class="bg-gradient-to-r from-red-600 to-rose-700 text-white p-3.5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] bg-white/20 text-white font-black uppercase px-2 py-0.5 rounded tracking-wider">Penerima Resmi</span>
                        <h4 class="font-extrabold text-base tracking-wide mt-1">KAS RT31 GRAHA KALIMAS</h4>
                        <p class="text-xs text-red-100 font-mono mt-0.5">NMID: <span id="qris_nmid_text">ID1026545858353</span></p>
                    </div>
                    <button type="button" onclick="navigator.clipboard.writeText('ID1026545858353'); alert('NMID ID1026545858353 berhasil disalin!');" class="bg-white/20 hover:bg-white/30 text-white text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1 shrink-0" title="Salin NMID">
                        <i class="fa-regular fa-copy"></i> Salin NMID
                    </button>
                </div>

                <!-- Gambar QRIS Utama -->
                <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-2xl p-3 flex flex-col items-center justify-center relative group">
                    <img src="qris_kas_rt31.jpg" alt="QRIS KAS RT31 GRAHA KALIMAS" class="w-full max-w-[340px] h-auto object-contain rounded-xl shadow-md border border-gray-200 transition-transform duration-200 group-hover:scale-[1.01]">
                    <span class="text-[11px] text-gray-500 mt-2 font-medium flex items-center gap-1.5">
                        <i class="fa-solid fa-camera text-red-500"></i> Arahkan kamera atau scan barcode dari aplikasi pembayaran
                    </span>
                </div>

                <!-- Cara Pembayaran -->
                <div class="bg-blue-50 border border-blue-200 rounded-2xl p-3.5 text-xs text-blue-950 space-y-1.5">
                    <h5 class="font-bold flex items-center gap-1.5 text-blue-900">
                        <i class="fa-solid fa-circle-info text-blue-600"></i> Panduan Pembayaran:
                    </h5>
                    <ol class="list-decimal list-inside text-[11px] text-blue-900/90 space-y-1 ml-1 leading-relaxed">
                        <li>Buka aplikasi <strong>M-Banking</strong> (BCA, Mandiri, BRI, BNI, BSI, dll) atau <strong>E-Wallet</strong> (GoPay, OVO, DANA, ShopeePay, LinkAja).</li>
                        <li>Pilih menu <strong>Scan / Bayar QRIS</strong>.</li>
                        <li>Scan gambar kode QR di atas (atau pilih gambar dari galeri HP jika sudah diunduh).</li>
                        <li>Pastikan nama merchant penerima adalah: <strong>KAS RT31 GRAHA KALIMAS</strong>.</li>
                        <li>Masukkan nominal pembayaran iuran atau setoran kas, lalu selesaikan transaksi.</li>
                        <li>Simpan resi / bukti transfer pembayaran dan kirimkan ke Bendahara RT.</li>
                    </ol>
                </div>

                <!-- Dukungan Aplikasi -->
                <div class="text-center pt-1">
                    <p class="text-[11px] text-gray-500 mb-2 font-medium">Mendukung Seluruh Layanan Pembayaran Digital & Bank:</p>
                    <div class="flex flex-wrap items-center justify-center gap-1.5 text-[10px] font-bold text-gray-700">
                        <span class="bg-gray-100 border border-gray-300 px-2 py-0.5 rounded-full">BCA Mobile</span>
                        <span class="bg-gray-100 border border-gray-300 px-2 py-0.5 rounded-full">Livin' Mandiri</span>
                        <span class="bg-gray-100 border border-gray-300 px-2 py-0.5 rounded-full">BRImo</span>
                        <span class="bg-gray-100 border border-gray-300 px-2 py-0.5 rounded-full">BNI Mobile</span>
                        <span class="bg-gray-100 border border-gray-300 px-2 py-0.5 rounded-full">GoPay</span>
                        <span class="bg-gray-100 border border-gray-300 px-2 py-0.5 rounded-full">OVO</span>
                        <span class="bg-gray-100 border border-gray-300 px-2 py-0.5 rounded-full">DANA</span>
                        <span class="bg-gray-100 border border-gray-300 px-2 py-0.5 rounded-full">ShopeePay</span>
                        <span class="bg-gray-100 border border-gray-300 px-2 py-0.5 rounded-full">LinkAja</span>
                    </div>
                </div>

                <!-- Tombol Aksi Modal -->
                <div class="flex gap-2.5 pt-2 border-t border-gray-200">
                    <a href="qris_kas_rt31.jpg" download="QRIS_KAS_RT31_GRAHA_KALIMAS.jpg" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs text-center shadow-md transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-download"></i> Unduh Gambar QRIS
                    </a>
                    <button type="button" onclick="tutupModalQRIS()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-5 rounded-xl text-xs transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT INTERAKSI MODAL -->
    <script>
        var activeRowForTunggakan = null;
        var activeRowForEditWarga = null;
        var tarifBulananAktif = <?= (float)$tarif_bulanan; ?>;

        function formatRupiah(num) {
            var isMinus = num < 0;
            var abs = Math.abs(Math.round(num));
            var formatted = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return (isMinus ? "-Rp " : "Rp ") + formatted;
        }

        function bukaModalBayar() {
            document.getElementById('modalBayar').classList.remove('hidden');
        }
        function tutupModalBayar() {
            document.getElementById('modalBayar').classList.add('hidden');
        }

        function bukaModalBayarKhusus(row) {
            document.getElementById('input_bayar_iuran_id').value = row.id;
            bukaModalBayar();
        }

        function bukaModalTarif() {
            document.getElementById('modalTarif').classList.remove('hidden');
        }
        function tutupModalTarif() {
            document.getElementById('modalTarif').classList.add('hidden');
        }

        function bukaModalTambahWarga() {
            document.getElementById('modalTambahWarga').classList.remove('hidden');
            hitungPreviewTambahWarga();
        }
        function tutupModalTambahWarga() {
            document.getElementById('modalTambahWarga').classList.add('hidden');
        }

        function bukaModalEditWarga(row) {
            activeRowForEditWarga = row;
            document.getElementById('edit_warga_id').value = row.id;
            document.getElementById('edit_warga_blok').value = row.blok;
            document.getElementById('edit_warga_nama').value = row.nama;
            document.getElementById('edit_warga_nik').value = row.nik || '';
            document.getElementById('edit_warga_tunggakan').value = row.tunggakan_bulan_lalu;
            document.getElementById('edit_warga_keterangan').value = row.keterangan || '';
            hitungPreviewEditWarga();
            document.getElementById('modalEditWarga').classList.remove('hidden');
        }
        function tutupModalEditWarga() {
            document.getElementById('modalEditWarga').classList.add('hidden');
            activeRowForEditWarga = null;
        }

        // ==========================================
        // HANDLER MODAL EDIT TUNGGAKAN DES 2025
        // ==========================================
        function bukaModalEditTunggakan(row) {
            activeRowForTunggakan = row;
            document.getElementById('tunggakan_modal_id').value = row.id;
            document.getElementById('tunggakan_modal_nama_label').textContent = 'Blok ' + (row.blok || '') + ' - ' + (row.nama || '');
            
            var val = parseInt(row.tunggakan_bulan_lalu, 10);
            if (isNaN(val)) val = 0;
            
            document.getElementById('tunggakan_modal_input').value = val;
            hitungOtomatisRumusTunggakan();
            
            var modal = document.getElementById('modalEditTunggakan');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function tutupModalEditTunggakan() {
            var modal = document.getElementById('modalEditTunggakan');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            activeRowForTunggakan = null;
        }

        function setTipeTunggakan(tipe) {
            var input = document.getElementById('tunggakan_modal_input');
            var val = parseInt(input.value, 10);
            if (isNaN(val)) val = 0;
            
            if (tipe === 'kurang') {
                if (val >= 0) val = (val === 0) ? -12 : -Math.abs(val);
            } else if (tipe === 'lunas') {
                val = 0;
            } else if (tipe === 'lebih') {
                if (val <= 0) val = (val === 0) ? 1 : Math.abs(val);
            }
            input.value = val;
            hitungOtomatisRumusTunggakan();
        }

        function setPresetTunggakan(val) {
            document.getElementById('tunggakan_modal_input').value = val;
            hitungOtomatisRumusTunggakan();
        }

        function updateTipeTunggakanUI(val) {
            var btnKurang = document.getElementById('btn_status_kurang');
            var btnLunas  = document.getElementById('btn_status_lunas');
            var btnLebih  = document.getElementById('btn_status_lebih');
            if (!btnKurang || !btnLunas || !btnLebih) return;
            
            btnKurang.className = "py-2 px-2 text-xs font-bold rounded-xl border flex items-center justify-center gap-1 transition " + 
                (val < 0 ? "border-red-500 bg-red-600 text-white shadow-sm" : "border-red-200 bg-red-50 text-red-700 hover:bg-red-100");
            btnLunas.className = "py-2 px-2 text-xs font-bold rounded-xl border flex items-center justify-center gap-1 transition " + 
                (val === 0 ? "border-green-500 bg-green-600 text-white shadow-sm" : "border-green-200 bg-green-50 text-green-700 hover:bg-green-100");
            btnLebih.className = "py-2 px-2 text-xs font-bold rounded-xl border flex items-center justify-center gap-1 transition " + 
                (val > 0 ? "border-blue-500 bg-blue-600 text-white shadow-sm" : "border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100");
        }

        function hitungOtomatisRumusTunggakan() {
            var rawInput = document.getElementById('tunggakan_modal_input').value;
            var bulan = parseInt(rawInput, 10);
            if (isNaN(bulan)) bulan = 0;
            
            updateTipeTunggakanUI(bulan);
            
            var blok = activeRowForTunggakan ? (activeRowForTunggakan.blok || '').trim() : '';
            var totalBayar2026 = 0;
            if (activeRowForTunggakan) {
                var months = ['jan','feb','mar','apr','mei','jun','jul','agt','sep','okt','nop','des'];
                months.forEach(function(m) {
                    totalBayar2026 += parseFloat(activeRowForTunggakan[m] || 0);
                });
            }
            
            // Rumus 1: Kekurangan Uang Des 2025
            var uangLalu = bulan * tarifBulananAktif;
            
            // Rumus 2 & 3: Kewajiban Bulan 2026 & Total yang Harus Dibayar
            var kewajibanBulan2026 = 0;
            var harusBayar2026 = 0;
            
            if (blok === 'J20') {
                kewajibanBulan2026 = bulan;
                harusBayar2026 = uangLalu;
            } else if (blok === 'L12') {
                kewajibanBulan2026 = 0;
                harusBayar2026 = 0;
            } else {
                kewajibanBulan2026 = bulan - 12;
                harusBayar2026 = kewajibanBulan2026 * tarifBulananAktif;
            }
            
            // Rumus 4: Jumlah Kekurangan Iuran dalam Uang s/d Des 2026
            var sisaKurang2026 = harusBayar2026 + totalBayar2026;
            
            // Update UI elements
            document.getElementById('calc_uang_lalu').textContent = formatRupiah(uangLalu);
            document.getElementById('calc_uang_lalu_detail').textContent = '(' + bulan + ' bln × Rp ' + tarifBulananAktif.toLocaleString('id-ID') + ')';
            
            document.getElementById('calc_kewajiban_bulan').textContent = (kewajibanBulan2026 > 0 ? '+' : '') + kewajibanBulan2026 + ' Bulan';
            document.getElementById('calc_kewajiban_bulan_detail').textContent = '(' + bulan + ' bln - 12 bln tahun berjalan)';
            
            document.getElementById('calc_harus_bayar').textContent = formatRupiah(harusBayar2026);
            document.getElementById('calc_harus_bayar_detail').textContent = '(' + kewajibanBulan2026 + ' bln × Rp ' + tarifBulananAktif.toLocaleString('id-ID') + ')';
            
            document.getElementById('calc_total_bayar_2026').textContent = '+ ' + formatRupiah(totalBayar2026);
            
            var sisaEl = document.getElementById('calc_sisa_kurang');
            sisaEl.textContent = formatRupiah(sisaKurang2026);
            if (sisaKurang2026 < 0) {
                sisaEl.className = "font-mono font-bold text-lg text-red-400";
                document.getElementById('calc_sisa_kurang_detail').textContent = '(Harus dibayar ' + formatRupiah(harusBayar2026) + ' + Setoran ' + formatRupiah(totalBayar2026) + ' -> Kurang Bayar)';
            } else if (sisaKurang2026 === 0) {
                sisaEl.className = "font-mono font-bold text-lg text-emerald-400";
                document.getElementById('calc_sisa_kurang_detail').textContent = '(Lunas - Tidak ada kekurangan)';
            } else {
                sisaEl.className = "font-mono font-bold text-lg text-blue-400";
                document.getElementById('calc_sisa_kurang_detail').textContent = '(Lebih Bayar s/d akhir tahun)';
            }
            
            var indEl = document.getElementById('tunggakan_modal_indicator');
            if (bulan < 0) {
                indEl.textContent = bulan + ' Bulan (Kurang Bayar)';
                indEl.className = 'text-[11px] font-bold font-mono text-red-600 bg-red-50 px-2.5 py-0.5 rounded-full border border-red-200';
            } else if (bulan === 0) {
                indEl.textContent = '0 Bulan (Lunas)';
                indEl.className = 'text-[11px] font-bold font-mono text-green-700 bg-green-50 px-2.5 py-0.5 rounded-full border border-green-200';
            } else {
                indEl.textContent = '+' + bulan + ' Bulan (Lebih Bayar)';
                indEl.className = 'text-[11px] font-bold font-mono text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200';
            }
        }

        // ==========================================
        // HANDLER PREVIEW EDIT & TAMBAH WARGA
        // ==========================================
        function hitungPreviewEditWarga() {
            var raw = document.getElementById('edit_warga_tunggakan').value;
            var bulan = parseInt(raw, 10);
            if (isNaN(bulan)) bulan = 0;
            
            var blok = (document.getElementById('edit_warga_blok').value || '').trim();
            var totalBayar = 0;
            if (activeRowForEditWarga) {
                var months = ['jan','feb','mar','apr','mei','jun','jul','agt','sep','okt','nop','des'];
                months.forEach(function(m) {
                    totalBayar += parseFloat(activeRowForEditWarga[m] || 0);
                });
            }
            
            var kewajiban = (blok === 'J20') ? bulan : (blok === 'L12' ? 0 : (bulan - 12));
            var harusBayar = (blok === 'L12') ? 0 : (kewajiban * tarifBulananAktif);
            var sisaKurang = harusBayar + totalBayar;
            
            document.getElementById('preview_edit_harus_bayar').textContent = formatRupiah(harusBayar);
            var elSisa = document.getElementById('preview_edit_sisa_kurang');
            elSisa.textContent = formatRupiah(sisaKurang);
            elSisa.className = "font-mono font-bold " + (sisaKurang < 0 ? "text-red-300" : (sisaKurang === 0 ? "text-emerald-300" : "text-blue-300"));
        }

        function hitungPreviewTambahWarga() {
            var raw = document.getElementById('tambah_warga_tunggakan').value;
            var bulan = parseInt(raw, 10);
            if (isNaN(bulan)) bulan = 0;
            
            var kewajiban = bulan - 12;
            var harusBayar = kewajiban * tarifBulananAktif;
            var sisaKurang = harusBayar;
            
            document.getElementById('preview_tambah_harus_bayar').textContent = formatRupiah(harusBayar);
            var elSisa = document.getElementById('preview_tambah_sisa_kurang');
            elSisa.textContent = formatRupiah(sisaKurang);
            elSisa.className = "font-mono font-bold " + (sisaKurang < 0 ? "text-red-300" : (sisaKurang === 0 ? "text-emerald-300" : "text-blue-300"));
        }

        // ==========================================
        // HANDLER MODAL EDIT SETORAN BULANAN (JAN - DES)
        // ==========================================
        function bukaModalEditBulan(row, bulanKey, bulanLabel, currentVal) {
            document.getElementById('edit_bulan_id').value = row.id;
            document.getElementById('edit_bulan_asal').value = bulanKey;
            document.getElementById('edit_bulan_target').value = bulanKey;
            document.getElementById('edit_bulan_nama_label').textContent = 'Blok ' + (row.blok || '') + ' - ' + (row.nama || '');
            document.getElementById('edit_bulan_label').textContent = bulanLabel.toUpperCase() + ' ' + (row.tahun || '2026');
            
            var nominal = parseFloat(currentVal) || 0;
            document.getElementById('edit_bulan_nominal').value = nominal;
            document.getElementById('edit_bulan_nominal_sebelumnya').textContent = 'Saat Ini: ' + formatRupiah(nominal);
            
            var modal = document.getElementById('modalEditBulan');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function tutupModalEditBulan() {
            var modal = document.getElementById('modalEditBulan');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }

        function setPresetNominalBulan(nominal) {
            document.getElementById('edit_bulan_nominal').value = nominal;
        }

        // ==========================================
        // HANDLER MODAL EDIT TRANSAKSI KAS
        // ==========================================
        function bukaModalEditKas(row) {
            document.getElementById('edit_kas_id').value = row.id;
            document.getElementById('edit_kas_tanggal').value = row.tanggal;
            document.getElementById('edit_kas_keterangan').value = row.keterangan;
            document.getElementById('edit_kas_jenis').value = row.jenis;
            document.getElementById('edit_kas_nominal').value = parseFloat(row.nominal) || 0;
            
            var modal = document.getElementById('modalEditKas');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function tutupModalEditKas() {
            var modal = document.getElementById('modalEditKas');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }

        function bukaModalQRIS() {
            var m = document.getElementById('modalQRIS');
            m.classList.remove('hidden');
            m.style.display = 'flex';
        }
        function tutupModalQRIS() {
            var m = document.getElementById('modalQRIS');
            m.classList.add('hidden');
            m.style.display = 'none';
        }
    </script>
</body>
</html>