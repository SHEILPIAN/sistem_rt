<?php
include 'config.php';

// Pastikan hanya admin yang bisa mendownload laporan
if (!isset($_SESSION['status_login']) || !in_array($_SESSION['role'], ['ketua rt', 'sekretaris'])) {
    header("Location: login.php");
    exit;
}

// Mengatur Header agar halaman ini didownload sebagai file Excel (.xls)
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Keuangan_Kas_RT31.xls");

// Mengambil Data Transaksi
$query = mysqli_query($conn, "SELECT * FROM keuangan ORDER BY tanggal ASC, id ASC");

// Menghitung Saldo Kas
$q_masuk = mysqli_query($conn, "SELECT SUM(nominal) as total_masuk FROM keuangan WHERE jenis='Masuk'");
$d_masuk = mysqli_fetch_assoc($q_masuk);
$tot_masuk = $d_masuk['total_masuk'] ? $d_masuk['total_masuk'] : 0;

$q_keluar = mysqli_query($conn, "SELECT SUM(nominal) as total_keluar FROM keuangan WHERE jenis='Keluar'");
$d_keluar = mysqli_fetch_assoc($q_keluar);
$tot_keluar = $d_keluar['total_keluar'] ? $d_keluar['total_keluar'] : 0;

$saldo = $tot_masuk - $tot_keluar;
?>

<table border="1">
    <thead>
        <tr>
            <th colspan="5" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #2f3e83; color: white;">
                LAPORAN KAS & KEUANGAN RT 31
            </th>
        </tr>
        <tr>
            <th style="background-color: #e2e8f0; text-align: center;">No</th>
            <th style="background-color: #e2e8f0; text-align: center;">Tanggal</th>
            <th style="background-color: #e2e8f0;">Keterangan Transaksi</th>
            <th style="background-color: #e2e8f0; text-align: center;">Jenis</th>
            <th style="background-color: #e2e8f0; text-align: right;">Nominal (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while($row = mysqli_fetch_assoc($query)){
        ?>
        <tr>
            <td style="text-align: center;"><?= $no++; ?></td>
            <td style="text-align: center; mso-number-format:'\@';"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
            <td><?= $row['keterangan']; ?></td>
            <td style="text-align: center;"><?= $row['jenis']; ?></td>
            <td style="text-align: right;"><?= number_format($row['nominal'], 0, ',', '.'); ?></td>
        </tr>
        <?php } ?>
        
        <!-- Baris Rekapitulasi di Bawah Tabel -->
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL PEMASUKAN</td>
            <td style="text-align: right; font-weight: bold; color: green;"><?= number_format($tot_masuk, 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL PENGELUARAN</td>
            <td style="text-align: right; font-weight: bold; color: red;"><?= number_format($tot_keluar, 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold; background-color: #fff3cd;">SISA SALDO KAS RT</td>
            <td style="text-align: right; font-weight: bold; background-color: #fff3cd;"><?= number_format($saldo, 0, ',', '.'); ?></td>
        </tr>
    </tbody>
</table>