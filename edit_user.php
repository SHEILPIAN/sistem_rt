<?php
include 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Tolak akses jika yang masuk bukan ketua rt
if ($_SESSION['role'] != 'ketua rt') {
    echo "<script>alert('Akses Ditolak! Hanya Ketua RT yang bisa mengedit data user.'); window.location='index.php';</script>";
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$id = $_GET['id'];
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id'");
$data_user = mysqli_fetch_assoc($query_user);

if (!$data_user) {
    echo "<script>alert('Data user tidak ditemukan!'); window.location='users.php';</script>";
    exit;
}

// Proses Update Data
if (isset($_POST['simpan'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Cek apakah username dipakai orang lain
    $cek_username = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' AND id != '$id'");
    if(mysqli_num_rows($cek_username) > 0) {
        echo "<script>alert('Username sudah digunakan! Silakan pilih username lain.');</script>";
    } else {
        // Jika password diisi, maka update password juga
        if (!empty($_POST['password'])) {
            $password = MD5($_POST['password']);
            $update = mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama_lengkap', username='$username', password='$password', role='$role' WHERE id='$id'");
        } else {
            $update = mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama_lengkap', username='$username', role='$role' WHERE id='$id'");
        }

        if ($update) {
            echo "<script>alert('Data User Berhasil Diperbarui!'); window.location='users.php';</script>";
        } else {
            echo "<script>alert('Data gagal diperbarui!');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="users.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Edit User</h1>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= $data_user['nama_lengkap'] ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" value="<?= $data_user['username'] ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru (Opsional)</label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin ganti password" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="ketua rt" <?= $data_user['role'] == 'ketua rt' ? 'selected' : '' ?>>Ketua RT (Superadmin)</option>
                        <option value="sekretaris" <?= $data_user['role'] == 'sekretaris' ? 'selected' : '' ?>>Sekretaris (Admin)</option>
                        <option value="warga" <?= $data_user['role'] == 'warga' ? 'selected' : '' ?>>Warga</option>
                    </select>
                </div>

                <div class="pt-4 pb-10">
                    <button type="submit" name="simpan" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>
