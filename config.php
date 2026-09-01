<?php
// Mengaktifkan session untuk fitur login
session_set_cookie_params(0); // Memastikan sesi hilang saat browser ditutup (jika pengaturan browser standar)
session_start();

$host = getenv('DB_HOST') ?: 'trolley.proxy.rlwy.net';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'LnxeTAZtJLuuKhfgwekDgjHJZJHDCxGe';
$db = getenv('DB_NAME') ?: 'railway';
$port = getenv('DB_PORT') ?: 49923;

$conn = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>