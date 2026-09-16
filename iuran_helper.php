<?php
/**
 * Helper Modul Iuran Bulanan Warga Sesuai Aturan Spreadsheet
 * Sistem Informasi RT 31
 */

if (!function_exists('get_data_80_warga_spreadsheet')) {
    /**
     * Mengembalikan daftar 80 data warga resmi RT 31 sesuai gambar spreadsheet.
     *
     * @return array
     */
    function get_data_80_warga_spreadsheet() {
        return [
            1 => ['no' => 1, 'blok' => 'I12B', 'nama' => 'Arshori/ Tony Amanda (I-23)', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            2 => ['no' => 2, 'blok' => 'I15', 'nama' => 'Basuki Hartono', 'tunggakan_bulan' => 0, 'ket' => ''],
            3 => ['no' => 3, 'blok' => 'I16', 'nama' => 'Mama Alma', 'tunggakan_bulan' => 0, 'ket' => ''],
            4 => ['no' => 4, 'blok' => 'I17', 'nama' => 'Yuli Astuti', 'tunggakan_bulan' => 0, 'apr' => 100000.00, 'ket' => ''],
            5 => ['no' => 5, 'blok' => 'I18', 'nama' => 'Noerhayati', 'tunggakan_bulan' => -24, 'ket' => ''],
            6 => ['no' => 6, 'blok' => 'I19', 'nama' => 'Ruli Lubis', 'tunggakan_bulan' => 0, 'ket' => 'Rumah ke 2'],
            7 => ['no' => 7, 'blok' => 'I20', 'nama' => 'Ruli Lubis', 'tunggakan_bulan' => 0, 'ket' => ''],
            8 => ['no' => 8, 'blok' => 'I21', 'nama' => 'Mahendra', 'tunggakan_bulan' => -60, 'ket' => ''],
            9 => ['no' => 9, 'blok' => 'I22', 'nama' => 'Kirno', 'tunggakan_bulan' => -36, 'ket' => ''],
            10 => ['no' => 10, 'blok' => 'I23', 'nama' => 'Antok M', 'tunggakan_bulan' => -29, 'ket' => ''],
            11 => ['no' => 11, 'blok' => 'I23A', 'nama' => 'Arshori/ Tony Amanda', 'tunggakan_bulan' => 0, 'ket' => ''],
            12 => ['no' => 12, 'blok' => 'I25', 'nama' => 'Adamas Arras', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            13 => ['no' => 13, 'blok' => 'J1', 'nama' => 'Puji Astuti', 'tunggakan_bulan' => -11, 'ket' => ''],
            14 => ['no' => 14, 'blok' => 'J2', 'nama' => 'Idam Karyanto', 'tunggakan_bulan' => -58, 'ket' => ''],
            15 => ['no' => 15, 'blok' => 'J3', 'nama' => 'Benyamin/ Ginting', 'tunggakan_bulan' => -45, 'mei' => 110000.00, 'ket' => ''],
            16 => ['no' => 16, 'blok' => 'J3A', 'nama' => 'Hanny Hardiono', 'tunggakan_bulan' => 0, 'apr' => 180000.00, 'ket' => ''],
            17 => ['no' => 17, 'blok' => 'J5', 'nama' => 'Wahudin Hasan', 'tunggakan_bulan' => 0, 'ket' => ''],
            18 => ['no' => 18, 'blok' => 'J6', 'nama' => 'dr. Gunawan', 'tunggakan_bulan' => -60, 'ket' => ''],
            19 => ['no' => 19, 'blok' => 'J7', 'nama' => 'Budiyono', 'tunggakan_bulan' => -24, 'ket' => ''],
            20 => ['no' => 20, 'blok' => 'J8', 'nama' => 'Tendy Setiadi', 'tunggakan_bulan' => 0, 'ket' => ''],
            21 => ['no' => 21, 'blok' => 'J9', 'nama' => 'IKAT/ Dwi Yuliyanto', 'tunggakan_bulan' => 0, 'ket' => 'dikontrak'],
            22 => ['no' => 22, 'blok' => 'J10', 'nama' => 'I Dwi Puliyanto', 'tunggakan_bulan' => 2, 'ket' => ''],
            23 => ['no' => 23, 'blok' => 'J11', 'nama' => 'Wahyudi Dwi Nugroho', 'tunggakan_bulan' => -7, 'mar' => 160000.00, 'ket' => ''],
            24 => ['no' => 24, 'blok' => 'J12', 'nama' => 'Iman Susanto', 'tunggakan_bulan' => 0, 'ket' => ''],
            25 => ['no' => 25, 'blok' => 'J12A', 'nama' => 'Ketty', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            26 => ['no' => 26, 'blok' => 'J12B', 'nama' => 'Deni Suhendi', 'tunggakan_bulan' => -3, 'ket' => ''],
            27 => ['no' => 27, 'blok' => 'J15', 'nama' => 'Roby Sunaryo', 'tunggakan_bulan' => -6, 'ket' => ''],
            28 => ['no' => 28, 'blok' => 'J16', 'nama' => 'Ferry Sijabat', 'tunggakan_bulan' => -51, 'nop' => 1800000.00, 'ket' => ''],
            29 => ['no' => 29, 'blok' => 'J17', 'nama' => 'Doby Silalahi', 'tunggakan_bulan' => -11, 'ket' => ''],
            30 => ['no' => 30, 'blok' => 'J18', 'nama' => 'Retno Mandoyo', 'tunggakan_bulan' => -36, 'feb' => 100000.00, 'ket' => ''],
            31 => ['no' => 31, 'blok' => 'J19', 'nama' => 'Roy Situmorang', 'tunggakan_bulan' => -48, 'ket' => ''],
            32 => ['no' => 32, 'blok' => 'J20', 'nama' => 'I Wayan Suprayitno/Yanita Hutasoit', 'tunggakan_bulan' => -58, 'ket' => ''],
            33 => ['no' => 33, 'blok' => 'J21', 'nama' => 'Maya Sibarani', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            34 => ['no' => 34, 'blok' => 'J22', 'nama' => 'Julius', 'tunggakan_bulan' => -3, 'mar' => 240000.00, 'ket' => ''],
            35 => ['no' => 35, 'blok' => 'J23', 'nama' => 'Asep Priatdiana', 'tunggakan_bulan' => 0, 'ket' => ''],
            36 => ['no' => 36, 'blok' => 'J23A', 'nama' => 'Mangunw', 'tunggakan_bulan' => 0, 'ket' => ''],
            37 => ['no' => 37, 'blok' => 'J25', 'nama' => 'Sellyono', 'tunggakan_bulan' => -36, 'ket' => ''],
            38 => ['no' => 38, 'blok' => 'J26', 'nama' => 'Gregorius Sahadewa', 'tunggakan_bulan' => 0, 'mar' => 240000.00, 'ket' => ''],
            39 => ['no' => 39, 'blok' => 'J27', 'nama' => 'Nakhudin', 'tunggakan_bulan' => -60, 'ket' => ''],
            40 => ['no' => 40, 'blok' => 'J28', 'nama' => 'Mahendra', 'tunggakan_bulan' => 0, 'ket' => ''],
            41 => ['no' => 41, 'blok' => 'J29', 'nama' => 'Sugini', 'tunggakan_bulan' => -55, 'ket' => ''],
            42 => ['no' => 42, 'blok' => 'K1', 'nama' => 'Husni', 'tunggakan_bulan' => 0, 'ket' => ''],
            43 => ['no' => 43, 'blok' => 'K2', 'nama' => 'M TH Muljadi/Enus Aprilliyanti', 'tunggakan_bulan' => 0, 'feb' => 60000.00, 'ket' => ''],
            44 => ['no' => 44, 'blok' => 'K3', 'nama' => 'Yoga Prasetyo', 'tunggakan_bulan' => 0, 'ket' => ''],
            45 => ['no' => 45, 'blok' => 'K3A', 'nama' => 'Atma Wijaya', 'tunggakan_bulan' => -6, 'ket' => ''],
            46 => ['no' => 46, 'blok' => 'K5', 'nama' => 'Salhelawan', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            47 => ['no' => 47, 'blok' => 'K6', 'nama' => 'Baban Irman', 'tunggakan_bulan' => -3, 'mei' => 80000.00, 'ket' => ''],
            48 => ['no' => 48, 'blok' => 'K7', 'nama' => 'Riyanto', 'tunggakan_bulan' => 1, 'jan' => 100000.00, 'ket' => ''],
            49 => ['no' => 49, 'blok' => 'K8', 'nama' => 'Gatot Sudarto', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            50 => ['no' => 50, 'blok' => 'K9', 'nama' => 'Agus Kisworo', 'tunggakan_bulan' => -24, 'apr' => 240000.00, 'ket' => 'dikontrak'],
            51 => ['no' => 51, 'blok' => 'K10', 'nama' => 'Oktadiansyah', 'tunggakan_bulan' => 0, 'jan' => 240000.00, 'ket' => ''],
            52 => ['no' => 52, 'blok' => 'K11', 'nama' => 'Jhonos Hutapea', 'tunggakan_bulan' => -3, 'ket' => ''],
            53 => ['no' => 53, 'blok' => 'K12', 'nama' => 'Aadir Nur', 'tunggakan_bulan' => 0, 'ket' => 'dikontrak'],
            54 => ['no' => 54, 'blok' => 'K12A', 'nama' => 'Ruby Budikristianto', 'tunggakan_bulan' => -48, 'jan' => 1200000.00, 'ket' => ''],
            55 => ['no' => 55, 'blok' => 'K12B', 'nama' => 'Cipto', 'tunggakan_bulan' => 0, 'sep' => 500000.00, 'ket' => ''],
            56 => ['no' => 56, 'blok' => 'K15', 'nama' => 'Atma Wijaya', 'tunggakan_bulan' => 0, 'ket' => 'rumah ke 2'],
            57 => ['no' => 57, 'blok' => 'K16', 'nama' => 'Gincy/ Yulia Veronika', 'tunggakan_bulan' => -60, 'ket' => ''],
            58 => ['no' => 58, 'blok' => 'K17', 'nama' => 'Damar Danu Sadewo', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            59 => ['no' => 59, 'blok' => 'K18', 'nama' => 'Azis Ashari', 'tunggakan_bulan' => -34, 'jan' => 120000.00, 'ket' => ''],
            60 => ['no' => 60, 'blok' => 'K19', 'nama' => 'Yohanes Untadas', 'tunggakan_bulan' => -60, 'ket' => ''],
            61 => ['no' => 61, 'blok' => 'K20', 'nama' => 'Irimusanto', 'tunggakan_bulan' => 0, 'ket' => ''],
            62 => ['no' => 62, 'blok' => 'K21', 'nama' => 'Heriyandie', 'tunggakan_bulan' => -55, 'ket' => ''],
            63 => ['no' => 63, 'blok' => 'K22', 'nama' => 'Suripto', 'tunggakan_bulan' => -35, 'ket' => ''],
            64 => ['no' => 64, 'blok' => 'K23', 'nama' => 'Joshua', 'tunggakan_bulan' => -1, 'feb' => 60000.00, 'ket' => ''],
            65 => ['no' => 65, 'blok' => 'K23A', 'nama' => 'Andi Syu', 'tunggakan_bulan' => -5, 'ket' => ''],
            66 => ['no' => 66, 'blok' => 'K25', 'nama' => 'Emi', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            67 => ['no' => 67, 'blok' => 'L1', 'nama' => 'Ayyid Buran', 'tunggakan_bulan' => -33, 'ket' => ''],
            68 => ['no' => 68, 'blok' => 'L2', 'nama' => 'Ari Suparyoto', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            69 => ['no' => 69, 'blok' => 'L3', 'nama' => 'Rossli Damanik', 'tunggakan_bulan' => -42, 'ket' => ''],
            70 => ['no' => 70, 'blok' => 'L3A', 'nama' => 'Marcus Sunarno', 'tunggakan_bulan' => 10, 'jun' => 100000.00, 'ket' => ''],
            71 => ['no' => 71, 'blok' => 'L5', 'nama' => 'Ilun Hilal', 'tunggakan_bulan' => 6, 'jun' => 120000.00, 'ket' => ''],
            72 => ['no' => 72, 'blok' => 'L6', 'nama' => 'Surono', 'tunggakan_bulan' => 1, 'ket' => ''],
            73 => ['no' => 73, 'blok' => 'L7', 'nama' => 'Rekha Zain', 'tunggakan_bulan' => 11, 'feb' => 100000.00, 'ket' => ''],
            74 => ['no' => 74, 'blok' => 'L8', 'nama' => 'Emi', 'tunggakan_bulan' => 0, 'ket' => 'Kosong'],
            75 => ['no' => 75, 'blok' => 'L9', 'nama' => 'Bhartri', 'tunggakan_bulan' => -9, 'ket' => ''],
            76 => ['no' => 76, 'blok' => 'L10', 'nama' => 'Tika Kartika', 'tunggakan_bulan' => -48, 'ket' => ''],
            77 => ['no' => 77, 'blok' => 'L11', 'nama' => 'Sudilo', 'tunggakan_bulan' => 0, 'ket' => ''],
            78 => ['no' => 78, 'blok' => 'L12', 'nama' => 'Agus/Rahma', 'tunggakan_bulan' => 0, 'ket' => 'dikontrak'],
            79 => ['no' => 79, 'blok' => 'L12A', 'nama' => 'Prihatin', 'tunggakan_bulan' => -60, 'ket' => ''],
            80 => ['no' => 80, 'blok' => 'L12B', 'nama' => 'Hadi Wijaya', 'tunggakan_bulan' => 0, 'ket' => ''],
        ];
    }
}

if (!function_exists('sync_warga_dan_iuran_80')) {
    /**
     * Melakukan matching data warga dan menyinkronkan 80 data iuran warga ke tabel warga & iuran_warga.
     * Mengisi NIK placeholder 16-digit standar untuk warga yang belum memiliki NIK.
     *
     * @param mysqli $conn
     * @param bool $force_reset
     * @return int Jumlah baris yang disinkronkan
     */
    function sync_warga_dan_iuran_80($conn, $force_reset = false) {
        if (!$conn) return 0;

        $daftar_80 = get_data_80_warga_spreadsheet();
        $tahun = 2026;
        $count_synced = 0;

        foreach ($daftar_80 as $item) {
            $no_urut   = (int)$item['no'];
            $blok      = mysqli_real_escape_string($conn, trim($item['blok']));
            $nama      = mysqli_real_escape_string($conn, trim($item['nama']));
            $tunggakan = (int)$item['tunggakan_bulan'];
            $ket       = mysqli_real_escape_string($conn, trim($item['ket'] ?? ''));

            // Generate NIK Placeholder 16 Digit: 320131 (Kode RT 31) + 26 (Tahun 2026) + 000000XX (Urutan 1-80)
            $placeholder_nik = sprintf('32013126%08d', $no_urut);

            // 1. MATCHING: Cari apakah warga sudah terdaftar di tabel master warga
            $warga_id = null;
            $final_nik = $placeholder_nik;

            // Prioritaskan kecocokan nama sama persis
            $q_match = mysqli_query($conn, "SELECT id, nik, nama FROM warga WHERE LOWER(TRIM(nama)) = LOWER('$nama') LIMIT 1");
            if (!$q_match || mysqli_num_rows($q_match) == 0) {
                // Jika nama tidak ada yang sama, cari kecocokan alamat yang persis blok ini (bukan substring)
                $q_match = mysqli_query($conn, "SELECT id, nik, nama FROM warga WHERE alamat_rt = 'Blok $blok' OR alamat_rt = '$blok' OR alamat_rt LIKE '%Blok $blok' OR alamat_rt LIKE '% $blok' LIMIT 1");
            }

            if ($q_match && mysqli_num_rows($q_match) > 0) {
                $row_warga = mysqli_fetch_assoc($q_match);
                $warga_id  = (int)$row_warga['id'];
                // Validasi NIK: jika NIK warga sudah 16 digit, gunakan NIK tersebut. Jika tidak (misal data dummy lama), gunakan placeholder 16 digit.
                if (!empty($row_warga['nik']) && strlen(trim($row_warga['nik'])) === 16) {
                    $final_nik = trim($row_warga['nik']);
                } else {
                    $final_nik = $placeholder_nik;
                }
            } else {
                // 2. INSERT BARU KE TABEL WARGA jika belum ada
                $alamat_rt_val = "Blok " . $blok;
                $status_warga_val = (strtolower($ket) === 'dikontrak') ? 'Kontrak' : 'Tetap';
                
                $sql_ins_warga = "INSERT INTO warga (nik, nama, alamat_rt, status_warga, jenis_kelamin, hubungan_keluarga) 
                                  VALUES ('$final_nik', '$nama', '$alamat_rt_val', '$status_warga_val', 'L', 'Kepala Keluarga')";
                if (mysqli_query($conn, $sql_ins_warga)) {
                    $warga_id = mysqli_insert_id($conn);
                }
            }

            // Kolom-kolom setoran bulanan
            $bulan_keys = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agt', 'sep', 'okt', 'nop', 'des'];
            $bulan_values = [];
            foreach ($bulan_keys as $bk) {
                $bulan_values[$bk] = (float)($item[$bk] ?? 0);
            }

            // 3. SINKRONISASI KE TABEL iuran_warga
            $cek_iuran = mysqli_query($conn, "SELECT id FROM iuran_warga WHERE tahun = $tahun AND blok = '$blok' LIMIT 1");
            if ($cek_iuran && mysqli_num_rows($cek_iuran) > 0) {
                $existing_iuran = mysqli_fetch_assoc($cek_iuran);
                $iuran_id = (int)$existing_iuran['id'];

                $update_sql = "UPDATE iuran_warga SET 
                    warga_id = " . ($warga_id ? $warga_id : "NULL") . ",
                    nik = '$final_nik',
                    nama = '$nama',
                    tunggakan_bulan_lalu = $tunggakan,
                    jan = {$bulan_values['jan']},
                    feb = {$bulan_values['feb']},
                    mar = {$bulan_values['mar']},
                    apr = {$bulan_values['apr']},
                    mei = {$bulan_values['mei']},
                    jun = {$bulan_values['jun']},
                    jul = {$bulan_values['jul']},
                    agt = {$bulan_values['agt']},
                    sep = {$bulan_values['sep']},
                    okt = {$bulan_values['okt']},
                    nop = {$bulan_values['nop']},
                    des = {$bulan_values['des']},
                    keterangan = '$ket'
                    WHERE id = $iuran_id";
                mysqli_query($conn, $update_sql);
            } else {
                $insert_sql = "INSERT INTO iuran_warga (tahun, blok, warga_id, nik, nama, tunggakan_bulan_lalu, 
                    jan, feb, mar, apr, mei, jun, jul, agt, sep, okt, nop, des, keterangan) 
                    VALUES ($tahun, '$blok', " . ($warga_id ? $warga_id : "NULL") . ", '$final_nik', '$nama', $tunggakan, 
                    {$bulan_values['jan']}, {$bulan_values['feb']}, {$bulan_values['mar']}, {$bulan_values['apr']}, 
                    {$bulan_values['mei']}, {$bulan_values['jun']}, {$bulan_values['jul']}, {$bulan_values['agt']}, 
                    {$bulan_values['sep']}, {$bulan_values['okt']}, {$bulan_values['nop']}, {$bulan_values['des']}, '$ket')";
                mysqli_query($conn, $insert_sql);
            }

            $count_synced++;
        }

        return $count_synced;
    }
}

if (!function_exists('init_iuran_tables')) {
    /**
     * Membuat tabel pengaturan_iuran dan iuran_warga serta melakukan migrasi 80 data warga.
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
            warga_id INT NULL,
            nik VARCHAR(20) NULL,
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

        // Pastikan kolom warga_id dan nik ada pada tabel iuran_warga
        $cek_kolom_warga_id = @mysqli_query($conn, "SHOW COLUMNS FROM iuran_warga LIKE 'warga_id'");
        if ($cek_kolom_warga_id && mysqli_num_rows($cek_kolom_warga_id) == 0) {
            @mysqli_query($conn, "ALTER TABLE iuran_warga ADD COLUMN warga_id INT NULL AFTER blok");
        }
        $cek_kolom_nik = @mysqli_query($conn, "SHOW COLUMNS FROM iuran_warga LIKE 'nik'");
        if ($cek_kolom_nik && mysqli_num_rows($cek_kolom_nik) == 0) {
            @mysqli_query($conn, "ALTER TABLE iuran_warga ADD COLUMN nik VARCHAR(20) NULL AFTER warga_id");
        }

        // Auto-seeder 80 warga dinonaktifkan agar database tetap kosong untuk input manual murni
    }
}

if (!function_exists('is_kepala_keluarga')) {
    /**
     * Memeriksa apakah status hubungan keluarga merupakan Kepala Rumah Tangga / Kepala Keluarga.
     *
     * @param string $hubungan_keluarga
     * @return bool
     */
    function is_kepala_keluarga($hubungan_keluarga) {
        $hub = strtolower(trim($hubungan_keluarga));
        return (strpos($hub, 'kepala rumah tangga') !== false || strpos($hub, 'kepala keluarga') !== false);
    }
}

if (!function_exists('sync_kepala_keluarga_ke_iuran')) {
    /**
     * Jika warga berstatus Kepala Rumah Tangga, daftarkan atau perbarui kavling di iuran_warga tahun 2026.
     *
     * @param mysqli $conn
     * @param int $warga_id
     * @param string $nama
     * @param string $nik
     * @param string $alamat_rt
     * @param string $hubungan_keluarga
     * @param int $tahun
     * @return int|bool
     */
    function sync_kepala_keluarga_ke_iuran($conn, $warga_id, $nama, $nik, $alamat_rt, $hubungan_keluarga, $tahun = 2026) {
        if (!$conn || !is_kepala_keluarga($hubungan_keluarga)) {
            return false;
        }

        $warga_id = (int)$warga_id;
        $blok = mysqli_real_escape_string($conn, trim($alamat_rt));
        $nama = mysqli_real_escape_string($conn, trim($nama));
        $nik  = mysqli_real_escape_string($conn, trim($nik));
        $tahun = (int)$tahun;

        // Cek apakah sudah ada kavling / warga_id ini di rekap iuran tahun aktif
        $cek = mysqli_query($conn, "SELECT id FROM iuran_warga WHERE tahun = $tahun AND (blok = '$blok' OR warga_id = $warga_id) LIMIT 1");
        if ($cek && mysqli_num_rows($cek) > 0) {
            $row = mysqli_fetch_assoc($cek);
            $iuran_id = (int)$row['id'];
            mysqli_query($conn, "UPDATE iuran_warga SET warga_id = $warga_id, nama = '$nama', nik = '$nik', blok = '$blok' WHERE id = $iuran_id");
            return $iuran_id;
        } else {
            mysqli_query($conn, "INSERT INTO iuran_warga (tahun, blok, warga_id, nik, nama, tunggakan_bulan_lalu, keterangan) 
                                 VALUES ($tahun, '$blok', $warga_id, '$nik', '$nama', 0, 'Penghuni')");
            return mysqli_insert_id($conn);
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

        $bulan_lalu = (int)($row['tunggakan_bulan_lalu'] ?? 0);
        $uang_lalu  = $bulan_lalu * $tarif_bulanan;

        // Pengecualian khusus sesuai spreadsheet asli:
        // - Blok J20 (I Wayan Suprayitno): Hanya ditagih tunggakan lalu (-1.160.000)
        // - Blok L12 (Agus/Rahma): Dikontrak bebas tagihan berjalan (0)
        $blok = trim($row['blok'] ?? '');
        if ($blok === 'J20') {
            $kewajiban_bulan_2026 = $bulan_lalu;
            $jumlah_harus_dibayar = $uang_lalu;
        } elseif ($blok === 'L12') {
            $kewajiban_bulan_2026 = '-';
            $jumlah_harus_dibayar = 0;
        } else {
            $kewajiban_bulan_2026 = $bulan_lalu - 12;
            $jumlah_harus_dibayar = $kewajiban_bulan_2026 * $tarif_bulanan;
        }

        // Total pembayaran tahun berjalan (Jan s/d Des)
        $bulan_keys = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agt', 'sep', 'okt', 'nop', 'des'];
        $total_bayar = 0;
        foreach ($bulan_keys as $b) {
            $total_bayar += (float)($row[$b] ?? 0);
        }

        // Sisa tagihan / kekurangan s/d Des 2026
        $sisa_kurang_2026 = (is_numeric($jumlah_harus_dibayar) ? $jumlah_harus_dibayar : 0) + $total_bayar;
        $is_lunas = ($sisa_kurang_2026 >= 0);

        return [
            'tunggakan_bulan_2025' => $bulan_lalu,
            'tunggakan_uang_2025'  => $uang_lalu,
            'kewajiban_bulan_2026' => $kewajiban_bulan_2026,
            'jumlah_harus_dibayar' => $jumlah_harus_dibayar,
            'total_bayar_2026'     => $total_bayar,
            'sisa_kurang_2026'     => $sisa_kurang_2026,
            'is_lunas'             => $is_lunas,
            'is_kosong'            => false,
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
