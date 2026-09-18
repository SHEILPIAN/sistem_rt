<?php

include 'config.php';
require_once 'iuran_helper.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Inisialisasi tabel iuran jika belum ada
init_iuran_tables($conn);
$tarif_iuran = get_tarif_iuran($conn, 2026);

// Peta data iuran 2026 berdasarkan warga_id dan nik
$map_iuran = [];
$q_iuran = @mysqli_query($conn, "SELECT * FROM iuran_warga WHERE tahun = 2026");
if ($q_iuran) {
    while ($ir = mysqli_fetch_assoc($q_iuran)) {
        if (!empty($ir['warga_id'])) {
            $map_iuran['id_' . $ir['warga_id']][] = $ir;
        }
        if (!empty($ir['nik'])) {
            $map_iuran['nik_' . $ir['nik']][] = $ir;
        }
    }
}

// Logika Pencarian & Pengurutan Data Berdasarkan Blok Rumah (A - Z)
$kata_kunci = "";
$order_blok_sql = "
    REGEXP_SUBSTR(REGEXP_REPLACE(alamat_rt, '^Blok[[:space:]]+', ''), '^[A-Za-z]+') ASC,
    CAST(REGEXP_SUBSTR(alamat_rt, '[0-9]+') AS UNSIGNED) ASC,
    alamat_rt ASC,
    FIELD(hubungan_keluarga, 'Suami (Kepala Rumah Tangga)', 'Janda (Kepala Rumah Tangga)', 'Kepala Keluarga', 'Istri (Mengurus Rumah Tangga)', 'Anak', 'Janda (Anggota Keluarga)') ASC,
    nama ASC
";

if (isset($_GET['cari'])) {
    $kata_kunci = mysqli_real_escape_string($conn, trim($_GET['cari']));
    // Role dengan izin 'view_nik' dapat mencari berdasarkan NIK, nama, atau alamat/blok. Role biasa mencari nama atau alamat/blok.
    $filter_pencarian = has_permission('view_nik')
        ? "(nama LIKE '%$kata_kunci%' OR nik LIKE '%$kata_kunci%' OR alamat_rt LIKE '%$kata_kunci%')"
        : "(nama LIKE '%$kata_kunci%' OR alamat_rt LIKE '%$kata_kunci%')";
    $query = mysqli_query($conn, "SELECT * FROM warga WHERE $filter_pencarian ORDER BY $order_blok_sql") or die(mysqli_error($conn));
} else {
    // Jika tidak melakukan pencarian, tampilkan semua data terurut blok A-Z
    $query = mysqli_query($conn, "SELECT * FROM warga ORDER BY $order_blok_sql") or die(mysqli_error($conn));
}

// Ambil semua data warga ke array dan urutkan secara natural A - Z
$daftar_warga = [];
if ($query) {
    while ($rw = mysqli_fetch_assoc($query)) {
        $daftar_warga[] = $rw;
    }
    sort_warga_by_blok($daftar_warga, 'alamat_rt');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Warga - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
                <h1 class="font-bold text-lg">Data Warga</h1>
            </div>
            
            <?php if(has_permission('tambah_warga')): ?>
            <a href="tambah_warga.php" class="bg-white text-blue-900 px-3 py-1.5 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-100">
                <i class="fa-solid fa-plus"></i> Warga
            </a>
            <?php endif; ?>
        </div>

        <!-- Kolom Pencarian yang Sudah Aktif -->
        <div class="p-4 bg-gray-50 border-b border-gray-200">
            <form action="" method="GET" class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                </div>
                <!-- Input pencarian menyimpan value kata kunci agar tidak hilang saat di-enter -->
                <input type="text" name="cari" value="<?= $kata_kunci; ?>" placeholder="<?= has_permission('view_nik') ? 'Cari nama, blok, atau NIK warga...' : 'Cari nama atau blok warga...'; ?>" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm outline-none transition">
                
                <?php if(isset($_GET['cari']) && $_GET['cari'] != ''): ?>
                    <a href="warga.php" class="absolute inset-y-0 right-0 pr-3 flex items-center text-red-500 hover:text-red-700">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- List Data Warga -->
        <div class="px-4 mt-4 space-y-3">
            <?php 
            $jumlah_data = count($daftar_warga);
            if ($jumlah_data > 0): 
            ?>
                <div class="flex justify-between items-center mb-3">
                    <p class="text-xs text-gray-500">Menampilkan <?= $jumlah_data; ?> data warga (Urut Blok A - Z).</p>
    
                    <!-- Tombol Export Excel (Hanya untuk Admin) -->
                    <?php if(has_permission('export_warga')): ?>
                    <a href="export_warga.php<?= (isset($_GET['cari']) && $_GET['cari'] != '') ? '?cari='.$_GET['cari'] : '' ?>" class="bg-green-600 hover:bg-green-700 text-white text-[10px] font-bold py-1.5 px-3 rounded-lg shadow-sm flex items-center gap-1 transition">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </a>
                    <?php endif; ?>
                </div>
                <?php foreach($daftar_warga as $row) : ?>
                <!-- Tambahkan class 'relative' di div utama ini -->
                <div class="bg-white border border-gray-200 p-3 rounded-xl shadow-sm flex items-start gap-3 hover:bg-blue-50 transition relative">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl shrink-0 mt-1 border border-blue-200">
                        <i class="fa-solid <?= ($row['jenis_kelamin'] == 'L') ? 'fa-user-tie' : 'fa-user'; ?>"></i>
                    </div>
                    <div class="w-full pr-20">
                        <h3 class="font-bold text-gray-800 text-sm"><?= $row['nama']; ?></h3>
                        <?php if (has_permission('view_nik')): ?>
                        <p class="text-xs text-gray-600 font-mono mb-1">NIK: <?= $row['nik']; ?></p>
                        <?php endif; ?>
                        
                        <div class="flex flex-wrap gap-1.5 mb-2 items-center">
                            <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded border border-gray-200"><i class="fa-solid fa-house"></i> <?= $row['alamat_rt']; ?></span>
                            <span class="text-[10px] <?= ($row['status_warga'] == 'Tetap') ? 'bg-green-100 text-green-700 border-green-200' : 'bg-orange-100 text-orange-700 border-orange-200'; ?> px-2 py-0.5 rounded border font-semibold"><?= $row['status_warga']; ?></span>
                            <?php if(!empty($row['hubungan_keluarga'])): 
                                $hub = $row['hubungan_keluarga'];
                                $badge_style = 'bg-blue-100 text-blue-800 border-blue-200';
                                $hub_icon = 'fa-user';
                                if (strpos(strtolower($hub), 'suami') !== false || strpos(strtolower($hub), 'kepala') !== false) {
                                    $badge_style = 'bg-indigo-100 text-indigo-800 border-indigo-200 font-bold';
                                    $hub_icon = 'fa-crown';
                                } elseif (strpos(strtolower($hub), 'istri') !== false) {
                                    $badge_style = 'bg-pink-100 text-pink-800 border-pink-200';
                                    $hub_icon = 'fa-heart';
                                } elseif (strpos(strtolower($hub), 'anak') !== false) {
                                    $badge_style = 'bg-teal-100 text-teal-800 border-teal-200';
                                    $hub_icon = 'fa-child';
                                } elseif (strpos(strtolower($hub), 'janda') !== false) {
                                    $badge_style = 'bg-amber-100 text-amber-800 border-amber-300 font-medium';
                                    $hub_icon = 'fa-person-half-dress';
                                }
                            ?>
                                <span class="text-[10px] <?= $badge_style; ?> px-2 py-0.5 rounded border flex items-center gap-1">
                                    <i class="fa-solid <?= $hub_icon; ?>"></i> <?= htmlspecialchars($hub); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Status Iuran RT 2026 -->
                        <?php 
                        $warga_iurans = $map_iuran['id_' . $row['id']] ?? ($map_iuran['nik_' . $row['nik']] ?? []);
                        if (!empty($warga_iurans)): 
                        ?>
                        <div class="my-2 pt-2 pb-1 border-t border-dashed border-gray-200">
                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                <?php foreach ($warga_iurans as $item_ir): 
                                    $rekap = hitung_rekap_baris_iuran($item_ir, $tarif_iuran);
                                    $blok_label = htmlspecialchars($item_ir['blok']);
                                ?>
                                    <?php if ($rekap['is_kosong']): ?>
                                        <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md border border-gray-300 flex items-center gap-1 font-medium">
                                            <i class="fa-regular fa-circle text-gray-400"></i> <?= $blok_label; ?>: Kosong
                                        </span>
                                    <?php elseif ($rekap['is_lunas']): ?>
                                        <span class="text-[10px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md border border-emerald-200 flex items-center gap-1 font-semibold">
                                            <i class="fa-solid fa-circle-check text-emerald-500"></i> <?= $blok_label; ?>: Lunas
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] bg-rose-50 text-rose-700 px-2 py-0.5 rounded-md border border-rose-200 flex items-center gap-1 font-semibold">
                                            <i class="fa-solid fa-circle-exclamation text-rose-500"></i> <?= $blok_label; ?>: -Rp <?= number_format(abs($rekap['sisa_kurang_2026']), 0, ',', '.'); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <a href="keuangan.php?tab=iuran&cari=<?= urlencode($row['nama']); ?>" class="inline-flex items-center gap-1 text-[10px] text-blue-700 hover:text-blue-900 font-medium hover:underline">
                                <i class="fa-solid fa-receipt text-xs"></i> Rekap Iuran &rarr;
                            </a>
                        </div>
                        <?php endif; ?>

                        <!-- Tombol Lihat Dokumen (Khusus Admin / Yang punya izin) -->
                        <?php if(has_permission('view_document_warga')): ?>
                        <div class="flex gap-2 pt-2 mt-1 border-t border-gray-100">
                            <!-- Cek apakah foto KTP ada -->
                            <?php if(!empty($row['foto_ktp'])): ?>
                                <a href="uploads/<?= $row['foto_ktp']; ?>" target="_blank" class="text-[10px] bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded-md shadow-sm transition flex items-center gap-1">
                                    <i class="fa-solid fa-id-card"></i> Lihat KTP
                                </a>
                            <?php else: ?>
                                <span class="text-[10px] bg-gray-100 text-gray-400 px-2 py-1 rounded-md border border-gray-200 flex items-center gap-1">
                                    <i class="fa-solid fa-id-card"></i> KTP Kosong
                                </span>
                            <?php endif; ?>

                            <!-- Cek apakah foto KK ada -->
                            <?php if(!empty($row['foto_kk'])): ?>
                                <a href="uploads/<?= $row['foto_kk']; ?>" target="_blank" class="text-[10px] bg-teal-600 hover:bg-teal-700 text-white px-2 py-1 rounded-md shadow-sm transition flex items-center gap-1">
                                    <i class="fa-solid fa-users-viewfinder"></i> Lihat KK
                                </a>
                            <?php else: ?>
                                <span class="text-[10px] bg-gray-100 text-gray-400 px-2 py-1 rounded-md border border-gray-200 flex items-center gap-1">
                                    <i class="fa-solid fa-users-viewfinder"></i> KK Kosong
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tombol Aksi: Edit & Hapus -->
                    <div class="absolute top-3 right-3 flex items-center gap-1.5">
                        <?php if(has_permission('edit_warga')): ?>
                        <a href="edit_warga.php?id=<?= $row['id']; ?>" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 w-8 h-8 flex items-center justify-center rounded-lg transition border border-blue-200 shadow-sm" title="Edit Data Warga">
                            <i class="fa-solid fa-pen-to-square text-sm"></i>
                        </a>
                        <?php endif; ?>

                        <?php if(has_permission('hapus_warga')): ?>
                        <a href="hapus_warga.php?id=<?= $row['id']; ?>" onclick="return confirm('Peringatan: Yakin ingin menghapus permanen data <?= htmlspecialchars(addslashes($row['nama'])); ?> beserta fotonya?');" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 w-8 h-8 flex items-center justify-center rounded-lg transition border border-red-100 shadow-sm" title="Hapus Data Warga">
                            <i class="fa-solid fa-trash-can text-sm"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-users-slash text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-400 text-sm">Data warga tidak ditemukan.</p>
                    <?php if(isset($_GET['cari'])): ?>
                        <a href="warga.php" class="text-blue-600 text-xs mt-2 inline-block hover:underline">Tampilkan semua data</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bottom Navigation Bar -->
        <div class="fixed bottom-0 w-full max-w-md bg-white border-t border-gray-200 flex justify-around py-3 text-gray-500 text-xs shadow-lg z-50">
            <a href="index.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="mt-1">Home</span>
            </a>
            <a href="warga.php" class="flex flex-col items-center text-blue-600">
                <i class="fa-solid fa-users text-lg"></i>
                <span class="mt-1 font-medium">Warga</span>
            </a>
            <a href="aduan.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-comments text-lg"></i>
                <span class="mt-1">Aduan</span>
            </a>
            <a href="keuangan.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-wallet text-lg"></i>
                <span class="mt-1">Keuangan</span>
            </a>
        </div>

    </div>
</body>
</html>