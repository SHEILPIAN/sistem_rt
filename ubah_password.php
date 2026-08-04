<?php
include 'config.php';
if (!isset($_SESSION['status_login'])) { header("Location: login.php"); exit; }

$id_user = $_SESSION['id_user'];

if (isset($_POST['ubah_pass'])) {
    $pass_lama = MD5($_POST['pass_lama']);
    $pass_baru = MD5($_POST['pass_baru']);
    
    // Cek password lama
    $cek = mysqli_query($conn, "SELECT password FROM users WHERE id='$id_user' AND password='$pass_lama'");
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "UPDATE users SET password='$pass_baru' WHERE id='$id_user'");
        echo "<script>alert('Password berhasil diubah! Silakan login kembali.'); window.location='logout.php';</script>";
    } else {
        echo "<script>alert('Password lama salah!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keamanan & Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex justify-center">
    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        <div class="bg-[#2f3e83] text-white p-4 flex items-center gap-3">
            <a href="profil.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Keamanan & Password</h1>
        </div>
        <div class="p-5">
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Lama</label>
                    <input type="password" name="pass_lama" required class="w-full px-4 py-2 border border-gray-300 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input type="password" name="pass_baru" required class="w-full px-4 py-2 border border-gray-300 rounded-xl text-sm">
                </div>
                <button type="submit" name="ubah_pass" class="w-full bg-[#2f3e83] hover:bg-blue-800 text-white font-bold py-3 rounded-xl mt-4">Ubah Password</button>
            </form>
        </div>
    </div>
</body>
</html>