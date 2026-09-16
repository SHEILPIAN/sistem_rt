<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0); // Memastikan sesi hilang saat browser ditutup (jika pengaturan browser standar)
    session_start();
}

// Memuat helper otentikasi & hak akses (Role-Based Permissions)
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/iuran_helper.php';

$current_page = basename($_SERVER['PHP_SELF'] ?? '');
if (php_sapi_name() !== 'cli' && !isset($_SESSION['role']) && !in_array($current_page, ['login.php', 'logout.php', 'index.php', ''])) {
    die('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Gangguan Jaringan</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-100 h-screen flex items-center justify-center"><div class="bg-white p-8 rounded-2xl shadow-xl text-center max-w-sm w-full mx-4"><div class="text-red-500 mb-4"><svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div><h2 class="text-xl font-bold text-gray-800 mb-2">Maaf jaringan anda terputus</h2><p class="text-gray-500 mb-6 text-sm">Silakan coba login kembali untuk melanjutkan.</p><a href="login.php" class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">Ke Halaman Login</a></div></body></html>');
}

// Mendukung default Railway MySQL (MYSQLHOST dll) atau custom DB_HOST
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$user = getenv('MYSQLUSER') ?: (getenv('DB_USERNAME') ?: 'root');
$pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASSWORD') ?: '');
$db   = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'sistem_rt');
$port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306);

$conn = @mysqli_connect($host, $user, $pass, $db, (int)$port);

// Fallback untuk lingkungan database lokal XAMPP jika sistem_rt belum dibuat
if (!$conn && $db === 'sistem_rt') {
    $conn = @mysqli_connect($host, $user, $pass, 'db_sistem_rt', (int)$port);
}

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Inisialisasi otomatis tabel role_permissions jika belum ada di database
init_role_permissions_table($conn);

// Inisialisasi otomatis tabel pengaturan_iuran dan iuran_warga
init_iuran_tables($conn);

// Inisialisasi pembaruan kolom skema otomatis (kematian, warga, iuran_warga)
if (!function_exists('init_schema_updates')) {
    function init_schema_updates($conn) {
        if (!$conn) return;
        // 1. Kolom nik, pukul_wafat, tutup_usia pada tabel kematian
        $cek_nik = @mysqli_query($conn, "SHOW COLUMNS FROM kematian LIKE 'nik'");
        if ($cek_nik && mysqli_num_rows($cek_nik) == 0) {
            @mysqli_query($conn, "ALTER TABLE kematian ADD COLUMN nik VARCHAR(20) NULL AFTER nama_almarhum");
        }
        $cek_pukul = @mysqli_query($conn, "SHOW COLUMNS FROM kematian LIKE 'pukul_wafat'");
        if ($cek_pukul && mysqli_num_rows($cek_pukul) == 0) {
            @mysqli_query($conn, "ALTER TABLE kematian ADD COLUMN pukul_wafat VARCHAR(20) NULL AFTER tanggal_wafat");
        }
        $cek_usia = @mysqli_query($conn, "SHOW COLUMNS FROM kematian LIKE 'tutup_usia'");
        if ($cek_usia && mysqli_num_rows($cek_usia) == 0) {
            @mysqli_query($conn, "ALTER TABLE kematian ADD COLUMN tutup_usia VARCHAR(20) NULL AFTER hari_wafat");
        }
        $cek_sebab = @mysqli_query($conn, "SHOW COLUMNS FROM kematian LIKE 'sebab_kematian'");
        if ($cek_sebab && mysqli_num_rows($cek_sebab) == 0) {
            @mysqli_query($conn, "ALTER TABLE kematian ADD COLUMN sebab_kematian VARCHAR(100) NULL DEFAULT ''");
        }
        $cek_penyebab = @mysqli_query($conn, "SHOW COLUMNS FROM kematian LIKE 'penyebab'");
        if ($cek_penyebab && mysqli_num_rows($cek_penyebab) > 0) {
            @mysqli_query($conn, "ALTER TABLE kematian MODIFY COLUMN penyebab VARCHAR(100) NULL DEFAULT ''");
        } else {
            @mysqli_query($conn, "ALTER TABLE kematian ADD COLUMN penyebab VARCHAR(100) NULL DEFAULT ''");
        }
        $cek_jk = @mysqli_query($conn, "SHOW COLUMNS FROM kematian LIKE 'jenis_kelamin'");
        if ($cek_jk && $r_jk = mysqli_fetch_assoc($cek_jk)) {
            if (strpos(strtolower($r_jk['Type']), 'enum') !== false) {
                @mysqli_query($conn, "ALTER TABLE kematian MODIFY COLUMN jenis_kelamin VARCHAR(20) NOT NULL DEFAULT 'L'");
            }
        }
        // 2. Kolom hubungan_keluarga pada tabel warga agar muat status panjang
        $cek_hub = @mysqli_query($conn, "SHOW COLUMNS FROM warga LIKE 'hubungan_keluarga'");
        if ($cek_hub && $r_hub = mysqli_fetch_assoc($cek_hub)) {
            if (strpos(strtolower($r_hub['Type']), 'varchar(20)') !== false) {
                @mysqli_query($conn, "ALTER TABLE warga MODIFY COLUMN hubungan_keluarga VARCHAR(100) NULL");
            }
        }
        // 3. Kolom blok pada iuran_warga
        $cek_blok = @mysqli_query($conn, "SHOW COLUMNS FROM iuran_warga LIKE 'blok'");
        if ($cek_blok && $r_blok = mysqli_fetch_assoc($cek_blok)) {
            if (strpos(strtolower($r_blok['Type']), 'varchar(20)') !== false) {
                @mysqli_query($conn, "ALTER TABLE iuran_warga MODIFY COLUMN blok VARCHAR(50) NOT NULL");
            }
        }
    }
}
init_schema_updates($conn);
?>