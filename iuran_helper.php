<?php
/**
 * Helper Modul Iuran Bulanan Warga Sesuai Aturan Spreadsheet
 * Sistem Informasi RT 31
 */

if (!function_exists('init_iuran_tables')) {
    /**
     * Membuat tabel pengaturan_iuran dan iuran_warga serta melakukan seeding data awal dari gambar.
     *
     * @param mysqli $conn
     */
    function init_iuran_tables($conn) {
        if (!$conn) return;

        // 1. Tabel Pengaturan Tarif Iuran
        $sql_pengaturan = "CREATE TABLE IF NOT EXISTS pengaturan_iuran (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tahun INT NOT NULL UNIQUE,
            tarif_bulanan DECIMAL(12,2) NOT NULL DEFAULT 20000.00,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @mysqli_query($conn, $sql_pengaturan);

        // Seed tarif default untuk tahun 2026
        @mysqli_query($conn, "INSERT IGNORE INTO pengaturan_iuran (tahun, tarif_bulanan) VALUES (2026, 20000.00)");

        // 2. Tabel Rekap Iuran Warga Bulanan
        $sql_iuran = "CREATE TABLE IF NOT EXISTS iuran_warga (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tahun INT NOT NULL DEFAULT 2026,
            blok VARCHAR(20) NOT NULL,
            nama VARCHAR(100) NOT NULL,
            tunggakan_bulan_lalu INT DEFAULT 0,
            jan DECIMAL(12,2) DEFAULT 0.00,
            feb DECIMAL(12,2) DEFAULT 0.00,
            mar DECIMAL(12,2) DEFAULT 0.00,
            apr DECIMAL(12,2) DEFAULT 0.00,
            mei DECIMAL(12,2) DEFAULT 0.00,
            jun DECIMAL(12,2) DEFAULT 0.00,
            jul DECIMAL(12,2) DEFAULT 0.00,
            agt DECIMAL(12,2) DEFAULT 0.00,
            sep DECIMAL(12,2) DEFAULT 0.00,
            okt DECIMAL(12,2) DEFAULT 0.00,
            nop DECIMAL(12,2) DEFAULT 0.00,
            des DECIMAL(12,2) DEFAULT 0.00,
            keterangan VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @mysqli_query($conn, $sql_iuran);

        // Seed 6 Data Warga Awal Sesuai Gambar jika tabel masih kosong
        $cek = @mysqli_query($conn, "SELECT COUNT(*) as total FROM iuran_warga WHERE tahun = 2026");
        if ($cek) {
            $data = mysqli_fetch_assoc($cek);
            if ($data['total'] == 0) {
                $sample_rows = [
                    ['tahun' => 2026, 'blok' => 'I12B', 'nama' => 'Arshori/ Tony /Amanda (I-23)', 'tunggakan_bulan_lalu' => 0, 'apr' => 0, 'keterangan' => 'Kosong'],
                    ['tahun' => 2026, 'blok' => 'I15', 'nama' => 'Basuki Hartono', 'tunggakan_bulan_lalu' => 0, 'apr' => 0, 'keterangan' => '74(LXII)'],
                    ['tahun' => 2026, 'blok' => 'I16', 'nama' => 'Mama Alma', 'tunggakan_bulan_lalu' => 0, 'apr' => 0, 'keterangan' => ''],
                    ['tahun' => 2026, 'blok' => 'I17', 'nama' => 'Yuli Astuti', 'tunggakan_bulan_lalu' => 0, 'apr' => 100000.00, 'keterangan' => ''],
                    ['tahun' => 2026, 'blok' => 'I18', 'nama' => 'Noerhayati', 'tunggakan_bulan_lalu' => -24, 'apr' => 0, 'keterangan' => ''],
                    ['tahun' => 2026, 'blok' => 'I19', 'nama' => 'Ruli Lubis', 'tunggakan_bulan_lalu' => 0, 'apr' => 0, 'keterangan' => 'Rumah ke 2'],
                ];

                foreach ($sample_rows as $row) {
                    $thn = (int)$row['tahun'];
                    $blk = mysqli_real_escape_string($conn, $row['blok']);
                    $nm = mysqli_real_escape_string($conn, $row['nama']);
                    $tgk = (int)$row['tunggakan_bulan_lalu'];
                    $apr = (float)$row['apr'];
                    $ket = mysqli_real_escape_string($conn, $row['keterangan']);

                    mysqli_query($conn, "INSERT INTO iuran_warga (tahun, blok, nama, tunggakan_bulan_lalu, apr, keterangan) 
                        VALUES ($thn, '$blk', '$nm', $tgk, $apr, '$ket')");
                }
            }
        }
    }
}

if (!function_exists('get_tarif_iuran')) {
    /**
     * Mengambil tarif bulanan iuran untuk tahun tertentu.
     *
     * @param mysqli $conn
     * @param int $tahun
     * @return float
     */
    function get_tarif_iuran($conn, $tahun = 2026) {
        $tahun = (int)$tahun;
        $res = @mysqli_query($conn, "SELECT tarif_bulanan FROM pengaturan_iuran WHERE tahun = $tahun");
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            return (float)$row['tarif_bulanan'];
        }
        return 20000.00;
    }
}

if (!function_exists('update_tarif_iuran')) {
    /**
     * Memperbarui tarif iuran bulanan untuk suatu tahun.
     *
     * @param mysqli $conn
     * @param int $tahun
     * @param float $tarif_baru
     * @return bool
     */
    function update_tarif_iuran($conn, $tahun, $tarif_baru) {
        $tahun = (int)$tahun;
        $tarif_baru = (float)$tarif_baru;
        if ($tarif_baru <= 0) return false;

        $res = @mysqli_query($conn, "INSERT INTO pengaturan_iuran (tahun, tarif_bulanan) 
            VALUES ($tahun, $tarif_baru) 
            ON DUPLICATE KEY UPDATE tarif_bulanan = $tarif_baru");
        return (bool)$res;
    }
}

if (!function_exists('hitung_rekap_baris_iuran')) {
    /**
     * Menghitung seluruh angka dan kolom turunan untuk 1 baris iuran sesuai aturan di gambar.
     *
     * @param array $row
     * @param float $tarif_bulanan
     * @return array
     */
    function hitung_rekap_baris_iuran($row, $tarif_bulanan) {
        $is_kosong = (strtolower(trim($row['keterangan'] ?? '')) === 'kosong');

        if ($is_kosong) {
            return [
                'tunggakan_bulan_2025' => '-',
                'tunggakan_uang_2025' => '-',
                'kewajiban_bulan_2026' => '-',
                'jumlah_harus_dibayar' => '-',
                'total_bayar_2026' => 0,
                'sisa_kurang_2026' => '-',
                'is_lunas' => true,
                'is_kosong' => true,
            ];
        }

        $bulan_lalu = (int)($row['tunggakan_bulan_lalu'] ?? 0); // e.g. 0 atau -24
        $uang_lalu = $bulan_lalu * $tarif_bulanan; // e.g. 0 atau -480000

        // Kewajiban tahun 2026 (12 bulan tahun berjalan + tunggakan lalu)
        // Contoh: 0 - 12 = -12 bulan; atau -24 - 12 = -36 bulan
        $kewajiban_bulan_2026 = $bulan_lalu - 12;
        $jumlah_harus_dibayar = $kewajiban_bulan_2026 * $tarif_bulanan; // e.g. -240000 atau -720000

        // Total pembayaran tahun berjalan (Jan s/d Des)
        $bulan_keys = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agt', 'sep', 'okt', 'nop', 'des'];
        $total_bayar = 0;
        foreach ($bulan_keys as $b) {
            $total_bayar += (float)($row[$b] ?? 0);
        }

        // Sisa tagihan / kekurangan s/d Des 2026
        // e.g. -240000 + 100000 = -140000
        $sisa_kurang_2026 = $jumlah_harus_dibayar + $total_bayar;
        $is_lunas = ($sisa_kurang_2026 >= 0);

        return [
            'tunggakan_bulan_2025' => $bulan_lalu,
            'tunggakan_uang_2025' => $uang_lalu,
            'kewajiban_bulan_2026' => $kewajiban_bulan_2026,
            'jumlah_harus_dibayar' => $jumlah_harus_dibayar,
            'total_bayar_2026' => $total_bayar,
            'sisa_kurang_2026' => $sisa_kurang_2026,
            'is_lunas' => $is_lunas,
            'is_kosong' => false,
        ];
    }
}

if (!function_exists('catat_pembayaran_iuran')) {
    /**
     * Mencatat setoran pembayaran iuran dan secara otomatis menyinkronkan ke Kas Masuk RT.
     *
     * @param mysqli $conn
     * @param int $iuran_id
     * @param string $bulan ('jan', 'feb', ..., 'des')
     * @param float $nominal
     * @param bool $sync_kas
     * @return bool
     */
    function catat_pembayaran_iuran($conn, $iuran_id, $bulan, $nominal, $sync_kas = true) {
        $iuran_id = (int)$iuran_id;
        $bulan = strtolower(trim($bulan));
        $nominal = (float)$nominal;
        $valid_months = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agt', 'sep', 'okt', 'nop', 'des'];

        if (!in_array($bulan, $valid_months) || $nominal < 0) {
            return false;
        }

        // Ambil data warga
        $q_warga = mysqli_query($conn, "SELECT * FROM iuran_warga WHERE id = $iuran_id");
        if (!$q_warga || mysqli_num_rows($q_warga) == 0) {
            return false;
        }
        $data_warga = mysqli_fetch_assoc($q_warga);

        // Update nominal di kolom bulan yang bersangkutan
        $update = mysqli_query($conn, "UPDATE iuran_warga SET $bulan = $nominal WHERE id = $iuran_id");
        if (!$update) {
            return false;
        }

        // Sinkronisasi otomatis ke Kas Masuk RT jika sync_kas bernilai true dan nominal > 0
        if ($sync_kas && $nominal > 0) {
            $tgl_sekarang = date('Y-m-d');
            $nama_bulan_label = strtoupper($bulan);
            $tahun_iuran = $data_warga['tahun'];
            $blok_warga = $data_warga['blok'];
            $nama_warga = $data_warga['nama'];

            $uraian = "Iuran RT Blok $blok_warga - $nama_warga (Bulan $nama_bulan_label $tahun_iuran)";
            $uraian_safe = mysqli_real_escape_string($conn, $uraian);

            mysqli_query($conn, "INSERT INTO keuangan (tanggal, keterangan, jenis, nominal) 
                VALUES ('$tgl_sekarang', '$uraian_safe', 'Masuk', $nominal)");
        }

        return true;
    }
}

