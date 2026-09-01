<?php
include 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Tolak akses jika yang masuk bukan superadmin
if ($_SESSION['role'] != 'superadmin') {
    echo "<script>alert('Akses Ditolak! Hanya Superadmin yang bisa menambah user.'); window.location='index.php';</script>";
    exit;
}

// Proses Simpan Data
if (isset($_POST['simpan'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = MD5($_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Cek apakah username sudah ada
    $cek = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
    if(mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Username sudah digunakan! Silakan pilih username lain.');</script>";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO users (nama_lengkap, username, password, role) VALUES ('$nama_lengkap', '$username', '$password', '$role')");

        if ($insert) {
            echo "<script>alert('User Baru Berhasil Ditambahkan!'); window.location='users.php';</script>";
        } else {
            echo "<script>alert('Data gagal disimpan ke database!');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="users.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Tambah User Baru</h1>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="superadmin">Superadmin</option>
                        <option value="admin">Admin</option>
                        <option value="ketua rt">Ketua RT</option>
                        <option value="wakil rt">Wakil RT</option>
                    </select>
                </div>

                <div class="pt-4 pb-10">
                    <button type="submit" name="simpan" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>
