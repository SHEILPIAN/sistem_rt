<?php
// Mengaktifkan session untuk fitur login
session_start();

$host = getenv('DB_HOST') ?: 'trolley.proxy.rlwy.net';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'LnxeTAZtJLuuKhfgwekDgjHJZJHDCxGe';
$db = getenv('DB_NAME') ?: 'railway';
$port = getenv('DB_PORT') ?: 49923;

$conn = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>