<?php
include 'config.php';

// Pastikan hanya admin yang bisa mendownload laporan
if (!isset($_SESSION['status_login']) || !in_array($_SESSION['role'], ['ketua rt', 'sekretaris'])) {
    header("Location: login.php");
    exit;
}

// Mengatur Header agar halaman ini didownload sebagai file Excel (.xls)
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Sensus_Warga_RT31.xls");

// MENGHITUNG REKAPITULASI TOTAL
// Menggunakan satu query efisien untuk menghitung semua kategori
$q_rekap = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as tot_pria,
    SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as tot_wanita,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as tot_balita,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 6 AND 11 THEN 1 ELSE 0 END) as tot_anak,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 12 AND 17 THEN 1 ELSE 0 END) as tot_remaja
    FROM warga");
$rekap = mysqli_fetch_assoc($q_rekap);

// MENGAMBIL DATA WARGA (Dikelompokkan berdasarkan No KK, lalu diurutkan Kepala Keluarga -> Istri -> Anak)
$kata_kunci = isset($_GET['cari']) ? $_GET['cari'] : "";
if ($kata_kunci != "") {
    $query = mysqli_query($conn, "SELECT *, TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) AS umur FROM warga WHERE nama LIKE '%$kata_kunci%' OR nik LIKE '%$kata_kunci%' ORDER BY no_kk ASC, FIELD(hubungan_keluarga, 'Ayah', 'Ibu', 'Anak') ASC, nama ASC");
} else {
    $query = mysqli_query($conn, "SELECT *, TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) AS umur FROM warga ORDER BY no_kk ASC, FIELD(hubungan_keluarga, 'Kepala Keluarga', 'Ayah', 'Suami', 'Ibu', 'Istri', 'Anak') ASC, tanggal_lahir ASC");
}
?>

<table border="1">
    <thead>
        <!-- JUDUL -->
        <tr>
            <th colspan="9" style="font-size: 18px; font-weight: bold; text-align: center; background-color: #1e3a8a; color: white; padding: 10px;">
                LAPORAN SENSUS PENDUDUK RT 31
            </th>
        </tr>
        
        <!-- REKAPITULASI -->
        <tr>
            <th colspan="9" style="text-align: left; background-color: #e2e8f0; font-weight: bold; font-size: 14px;">
                REKAPITULASI JUMLAH WARGA:
            </th>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold; color: blue;">Total Pria: <?= $rekap['tot_pria'] ?: 0; ?> Jiwa</td>
            <td colspan="2" style="font-weight: bold; color: #d81b60;">Total Wanita: <?= $rekap['tot_wanita'] ?: 0; ?> Jiwa</td>
            <td colspan="5" style="font-weight: bold; background-color: #fff3cd;">
                Kategori Umur -> Balita (1-5th): <?= $rekap['tot_balita'] ?: 0; ?> | Anak-anak (6-11th): <?= $rekap['tot_anak'] ?: 0; ?> | Remaja (12-17th): <?= $rekap['tot_remaja'] ?: 0; ?>
            </td>
        </tr>
        <tr><td colspan="9"></td></tr> <!-- Baris Kosong Pemisah -->

        <!-- HEADER TABEL DATA -->
        <tr>
            <th style="background-color: #94a3b8; color: white;">No. KK</th>
            <th style="background-color: #94a3b8; color: white;">NIK</th>
            <th style="background-color: #94a3b8; color: white;">Nama Lengkap</th>
            <th style="background-color: #94a3b8; color: white;">Status Keluarga</th>
            <th style="background-color: #94a3b8; color: white;">L/P</th>
            <th style="background-color: #94a3b8; color: white;">Tanggal Lahir</th>
            <th style="background-color: #94a3b8; color: white;">Umur</th>
            <th style="background-color: #94a3b8; color: white;">Alamat / Rumah</th>
            <th style="background-color: #94a3b8; color: white;">Status Warga</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $current_kk = "";
        $warna_baris = "#ffffff";
        
        while($row = mysqli_fetch_assoc($query)){
            // Logika untuk memberi warna selang-seling per Kartu Keluarga agar mudah dibaca di Excel
            if ($row['no_kk'] != $current_kk) {
                $current_kk = $row['no_kk'];
                $warna_baris = ($warna_baris == "#ffffff") ? "#f8fafc" : "#ffffff"; 
            }
        ?>
        <tr style="background-color: <?= $warna_baris; ?>;">
            <!-- mso-number-format agar angka panjang tidak error di Excel -->
            <td style="mso-number-format:'\@'; vertical-align: top;"><?= $row['no_kk']; ?></td>
            <td style="mso-number-format:'\@';"><?= $row['nik']; ?></td>
            <td style="font-weight: <?= ($row['hubungan_keluarga'] == 'Ayah' || $row['hubungan_keluarga'] == 'Kepala Keluarga') ? 'bold' : 'normal'; ?>;">
                <?= $row['nama']; ?>
            </td>
            <td><?= $row['hubungan_keluarga']; ?></td>
            <td style="text-align: center;"><?= $row['jenis_kelamin']; ?></td>
            <td style="text-align: center; mso-number-format:'\@';"><?= date('d-m-Y', strtotime($row['tanggal_lahir'])); ?></td>
            <td style="text-align: center;"><?= $row['umur']; ?> Thn</td>
            <td><?= $row['alamat_rt']; ?></td>
            <td style="text-align: center;"><?= $row['status_warga']; ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>