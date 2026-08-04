<?php
include 'config.php';
if (!isset($_SESSION['status_login'])) { header("Location: login.php"); exit; }

$id_user = $_SESSION['id_user'];

if (isset($_POST['update'])) {
    $nama = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    
    $update = mysqli_query($conn, "UPDATE users SET nama_lengkap='$nama', username='$username' WHERE id='$id_user'");
    if ($update) {
        $_SESSION['nama_lengkap'] = $nama; // Update session
        echo "<script>alert('Profil berhasil diperbarui!'); window.location='profil.php';</script>";
    }
}

$query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id_user'");
$user = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex justify-center">
    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        <div class="bg-[#2f3e83] text-white p-4 flex items-center gap-3">
            <a href="profil.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Edit Data Diri</h1>
        </div>
        <div class="p-5">
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= $user['nama_lengkap']; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-[#2f3e83] focus:border-[#2f3e83] text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username Login</label>
                    <input type="text" name="username" value="<?= $user['username']; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-[#2f3e83] focus:border-[#2f3e83] text-sm">
                </div>
                <button type="submit" name="update" class="w-full bg-[#2f3e83] hover:bg-blue-800 text-white font-bold py-3 rounded-xl mt-4">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</body>
</html>