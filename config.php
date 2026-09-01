<?php
// Mengaktifkan session untuk fitur login
session_set_cookie_params(0); // Memastikan sesi hilang saat browser ditutup (jika pengaturan browser standar)
session_start();

$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['role']) && !in_array($current_page, ['login.php', 'logout.php', 'index.php', ''])) {
    die('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Gangguan Jaringan</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-100 h-screen flex items-center justify-center"><div class="bg-white p-8 rounded-2xl shadow-xl text-center max-w-sm w-full mx-4"><div class="text-red-500 mb-4"><svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div><h2 class="text-xl font-bold text-gray-800 mb-2">Maaf jaringan anda terputus</h2><p class="text-gray-500 mb-6 text-sm">Silakan coba login kembali untuk melanjutkan.</p><a href="login.php" class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">Ke Halaman Login</a></div></body></html>');
}

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