<?php
include 'config.php';

// Pastikan user sudah login dan memiliki akses membaca keuangan
if (!isset($_SESSION['status_login'])) {
    header("Location: login.php");
    exit;
}

$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 2026;
if ($tahun <= 0) $tahun = 2026;

$tarif_bulanan = get_tarif_iuran($conn, $tahun);
$can_view_nik = has_permission('view_nik');

// Mengatur Header agar didownload sebagai file Excel (.xls)
if (!headers_sent()) {
    header("Content-type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=Rekap_Iuran_Bulanan_RT31_{$tahun}.xls");
}

// Ambil 80 data iuran warga untuk tahun terpilih
$query = mysqli_query($conn, "SELECT * FROM iuran_warga WHERE tahun = $tahun ORDER BY id ASC");

// Inisialisasi total akumulasi
$sum_uang_lalu = 0;
$sum_harus_bayar = 0;
$sum_bulan = [
    'jan' => 0, 'feb' => 0, 'mar' => 0, 'apr' => 0,
    'mei' => 0, 'jun' => 0, 'jul' => 0, 'agt' => 0,
    'sep' => 0, 'okt' => 0, 'nop' => 0, 'des' => 0
];
$sum_sisa_kurang = 0;

$counts_status = ['Kosong' => 0, 'dikontrak' => 0, 'Rumah ke 2' => 0, 'Penghuni' => 0];

$rows_data = [];
while ($r = mysqli_fetch_assoc($query)) {
    $ket_raw = trim($r['keterangan'] ?? '');
    if (strtolower($ket_raw) === 'kosong') {
        $counts_status['Kosong']++;
    } elseif (strtolower($ket_raw) === 'dikontrak') {
        $counts_status['dikontrak']++;
    } elseif (strtolower($ket_raw) === 'rumah ke 2') {
        $counts_status['Rumah ke 2']++;
    } else {
        $counts_status['Penghuni']++;
    }
    $rows_data[] = $r;
}
?>
<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 10pt;">
    <thead>
        <tr>
            <th colspan="<?= $can_view_nik ? 23 : 22; ?>" style="font-size: 13pt; font-weight: bold; text-align: center; background-color: #facc15; padding: 10px; border: 1px solid #333;">
                REKAP IURAN WARGA RT 31 - TAHUN <?= $tahun; ?> (Tarif: Rp <?= number_format($tarif_bulanan, 0, ',', '.'); ?>/bln)
            </th>
        </tr>
        <tr style="background-color: #facc15; font-weight: bold; text-align: center;">
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle;">NO</th>
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle;">BLOK</th>
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle; min-width: 220px;">NAMA</th>
            <?php if ($can_view_nik): ?>
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle;">NIK</th>
            <?php endif; ?>
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle;">Jumlah Kekurangan Iuran dalam bulan - s/d bulan Des <?= $tahun - 1; ?></th>
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle;">Jumlah Kekurangan Iuran dalam uang - s/d bulan Des <?= $tahun - 1; ?></th>
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle;">Jumlah Kekurangan Iuran dalam bulan - s/d bulan Des <?= $tahun; ?></th>
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle;">Jumlah yang harus dibayar - s/d bulan Des <?= $tahun; ?></th>
            <th colspan="12" style="border: 1px solid #333; text-align: center;">TAHUN <?= $tahun; ?></th>
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle;">Jumlah Kekurangan Iuran dalam uang - s/d bulan Des <?= $tahun; ?></th>
            <th rowspan="2" style="border: 1px solid #333; vertical-align: middle;">Keterangan</th>
        </tr>
        <tr style="background-color: #facc15; font-weight: bold; text-align: center;">
            <th style="border: 1px solid #333;">JAN</th>
            <th style="border: 1px solid #333;">FEB</th>
            <th style="border: 1px solid #333;">MAR</th>
            <th style="border: 1px solid #333;">APR</th>
            <th style="border: 1px solid #333;">MEI</th>
            <th style="border: 1px solid #333;">JUN</th>
            <th style="border: 1px solid #333;">JUL</th>
            <th style="border: 1px solid #333;">AGT</th>
            <th style="border: 1px solid #333;">SEP</th>
            <th style="border: 1px solid #333;">OKT</th>
            <th style="border: 1px solid #333;">NOP</th>
            <th style="border: 1px solid #333;">DES</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        foreach ($rows_data as $row) :
            $kalkulasi = hitung_rekap_baris_iuran($row, $tarif_bulanan);
            if (!$kalkulasi['is_kosong']) {
                $sum_uang_lalu += $kalkulasi['tunggakan_uang_2025'];
                if (is_numeric($kalkulasi['jumlah_harus_dibayar'])) {
                    $sum_harus_bayar += $kalkulasi['jumlah_harus_dibayar'];
                }
                $sum_sisa_kurang += $kalkulasi['sisa_kurang_2026'];
            }
        ?>
        <tr>
            <td style="text-align: center; border: 1px solid #333;"><?= $no++; ?></td>
            <td style="text-align: center; border: 1px solid #333; font-weight: bold; mso-number-format:'\@';"><?= htmlspecialchars($row['blok']); ?></td>
            <td style="border: 1px solid #333;"><?= htmlspecialchars($row['nama']); ?></td>
            <?php if ($can_view_nik): ?>
            <td style="text-align: center; border: 1px solid #333; font-family: monospace; mso-number-format:'\@';"><?= htmlspecialchars($row['nik'] ?? '-'); ?></td>
            <?php endif; ?>
            
            <!-- Tunggakan Bulan 2025 -->
            <td style="text-align: center; border: 1px solid #333;">
                <?= $kalkulasi['is_kosong'] ? '-' : ($kalkulasi['tunggakan_bulan_2025'] == 0 ? '-' : $kalkulasi['tunggakan_bulan_2025']); ?>
            </td>
            
            <!-- Tunggakan Uang 2025 -->
            <td style="text-align: right; border: 1px solid #333;">
                <?php if ($kalkulasi['is_kosong'] || $kalkulasi['tunggakan_uang_2025'] == 0): ?>
                    -
                <?php else: ?>
                    <?= $kalkulasi['tunggakan_uang_2025'] < 0 ? '-' . number_format(abs($kalkulasi['tunggakan_uang_2025']), 0, ',', '.') : number_format($kalkulasi['tunggakan_uang_2025'], 0, ',', '.'); ?>
                <?php endif; ?>
            </td>
            
            <!-- Kewajiban Bulan 2026 -->
            <td style="text-align: center; border: 1px solid #333;">
                <?= $kalkulasi['is_kosong'] ? '-' : $kalkulasi['kewajiban_bulan_2026']; ?>
            </td>
            
            <!-- Jumlah yang harus dibayar 2026 -->
            <td style="text-align: right; border: 1px solid #333;">
                <?php if ($kalkulasi['is_kosong'] || !is_numeric($kalkulasi['jumlah_harus_dibayar']) || $kalkulasi['jumlah_harus_dibayar'] == 0): ?>
                    <?= $kalkulasi['is_kosong'] ? '-' : ($kalkulasi['jumlah_harus_dibayar'] === 0 ? '0' : '-'); ?>
                <?php else: ?>
                    <?= $kalkulasi['jumlah_harus_dibayar'] < 0 ? '-' . number_format(abs($kalkulasi['jumlah_harus_dibayar']), 0, ',', '.') : number_format($kalkulasi['jumlah_harus_dibayar'], 0, ',', '.'); ?>
                <?php endif; ?>
            </td>

            <!-- Bulan Jan s/d Des -->
            <?php foreach (['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agt', 'sep', 'okt', 'nop', 'des'] as $b): 
                $val = (float)($row[$b] ?? 0);
                if (!$kalkulasi['is_kosong']) {
                    $sum_bulan[$b] += $val;
                }
            ?>
            <td style="text-align: right; border: 1px solid #333;">
                <?php if ($kalkulasi['is_kosong']): ?>
                    -
                <?php else: ?>
                    <?= $val > 0 ? number_format($val, 0, ',', '.') : '-'; ?>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>

            <!-- Sisa Kekurangan Iuran 2026 (Merah jika minus) -->
            <td style="text-align: right; border: 1px solid #333; <?= (!$kalkulasi['is_kosong'] && $kalkulasi['sisa_kurang_2026'] < 0) ? 'color: #dc2626; font-weight: bold;' : ''; ?>">
                <?php if ($kalkulasi['is_kosong']): ?>
                    -
                <?php else: ?>
                    <?= $kalkulasi['sisa_kurang_2026'] < 0 ? '-' . number_format(abs($kalkulasi['sisa_kurang_2026']), 0, ',', '.') : number_format($kalkulasi['sisa_kurang_2026'], 0, ',', '.'); ?>
                <?php endif; ?>
            </td>

            <!-- Keterangan -->
            <td style="border: 1px solid #333; text-align: center;">
                <?= htmlspecialchars($row['keterangan'] ?? ''); ?>
            </td>
        </tr>
        <?php endforeach; ?>

        <!-- Baris Total Akumulasi Sesuai Gambar -->
        <tr style="background-color: #fef08a; font-weight: bold; border: 1px solid #333;">
            <td colspan="<?= $can_view_nik ? 5 : 4; ?>" style="text-align: right; border: 1px solid #333;">JUMLAH TOTAL :</td>
            <td style="text-align: right; border: 1px solid #333; color: #dc2626;">
                <?= $sum_uang_lalu < 0 ? '-' . number_format(abs($sum_uang_lalu), 0, ',', '.') : number_format($sum_uang_lalu, 0, ',', '.'); ?>
            </td>
            <td style="text-align: center; border: 1px solid #333;">-</td>
            <td style="text-align: right; border: 1px solid #333; color: #dc2626;">
                <?= $sum_harus_bayar < 0 ? '-' . number_format(abs($sum_harus_bayar), 0, ',', '.') : number_format($sum_harus_bayar, 0, ',', '.'); ?>
            </td>
            <?php foreach (['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agt', 'sep', 'okt', 'nop', 'des'] as $b): ?>
            <td style="text-align: right; border: 1px solid #333;">
                <?= $sum_bulan[$b] > 0 ? number_format($sum_bulan[$b], 0, ',', '.') : '-'; ?>
            </td>
            <?php endforeach; ?>
            <td style="text-align: right; border: 1px solid #333; color: #dc2626;">
                <?= $sum_sisa_kurang < 0 ? '-' . number_format(abs($sum_sisa_kurang), 0, ',', '.') : number_format($sum_sisa_kurang, 0, ',', '.'); ?>
            </td>
            <td style="border: 1px solid #333;"></td>
        </tr>
    </tbody>
</table>

<br>

<!-- Informasi Transfer Bank BCA & Kontak Konfirmasi (Sesuai Bawah Spreadsheet) -->
<table cellpadding="4" cellspacing="0" style="font-family: Arial, sans-serif; font-size: 10pt; width: 100%;">
    <tr>
        <td colspan="10" style="font-weight: bold; font-size: 11pt; padding-bottom: 5px;">
            Pembayaran Iuran RT dapat melalui Transfer ke :
        </td>
        <td colspan="5" style="border: 1px solid #333; background-color: #f1f5f9; font-weight: bold;">
            Kosong
        </td>
        <td colspan="4" style="border: 1px solid #333; text-align: center; font-weight: bold;">
            <?= $counts_status['Kosong']; ?>
        </td>
    </tr>
    <tr>
        <td colspan="10" style="font-weight: bold; padding-left: 20px;">
            BANK CENTRAL ASIA
        </td>
        <td colspan="5" style="border: 1px solid #333; background-color: #f1f5f9; font-weight: bold;">
            Dikontrak
        </td>
        <td colspan="4" style="border: 1px solid #333; text-align: center; font-weight: bold;">
            <?= $counts_status['dikontrak']; ?>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="padding-left: 20px; font-weight: bold;">No.Rekening</td>
        <td colspan="7" style="font-weight: bold; font-family: monospace; color: #1e3a8a; mso-number-format:'\@';">: 7285861195</td>
        <td colspan="5" style="border: 1px solid #333; background-color: #f1f5f9; font-weight: bold;">
            Rumah ke 2
        </td>
        <td colspan="4" style="border: 1px solid #333; text-align: center; font-weight: bold;">
            <?= $counts_status['Rumah ke 2']; ?>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="padding-left: 20px; font-weight: bold;">Atas Nama</td>
        <td colspan="7" style="font-weight: bold;">: Maria Suparmijati</td>
        <td colspan="5" style="border: 1px solid #333; background-color: #f1f5f9; font-weight: bold;">
            Penghuni
        </td>
        <td colspan="4" style="border: 1px solid #333; text-align: center; font-weight: bold;">
            <?= $counts_status['Penghuni']; ?>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="padding-left: 20px; font-weight: bold;">Konfirmasi 1</td>
        <td colspan="7">: Ibu MARIA.S /MANIK (081237418441)</td>
        <td colspan="5" style="border: 1px solid #333; background-color: #e2e8f0; font-weight: bold;">
            TOTAL KAVLING
        </td>
        <td colspan="4" style="border: 1px solid #333; text-align: center; font-weight: bold; background-color: #e2e8f0;">
            <?= array_sum($counts_status); ?>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="padding-left: 20px; font-weight: bold;">Konfirmasi 2</td>
        <td colspan="7">: Ibu MAYDIWATI (081250468187)</td>
        <td colspan="9"></td>
    </tr>
</table>
