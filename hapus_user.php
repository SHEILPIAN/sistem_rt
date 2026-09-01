<?php
include 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Tolak warga
if ($_SESSION['role'] == 'warga') {
    echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
    exit;
}


// Hanya ketua rt yang bisa menghapus user
if ($_SESSION['role'] !== 'ketua rt') {
    echo "<script>alert('Akses Ditolak! Hanya Ketua RT yang bisa menghapus data user.'); window.location='users.php';</script>";
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Jangan biarkan superadmin menghapus dirinya sendiri
    if ($id == $_SESSION['id_user']) {
        echo "<script>alert('Anda tidak bisa menghapus akun Anda sendiri!'); window.location='users.php';</script>";
        exit;
    }

    $hapus = mysqli_query($conn, "DELETE FROM users WHERE id = '$id'");

    if ($hapus) {
        echo "<script>alert('Data User Berhasil Dihapus!'); window.location='users.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data user!'); window.location='users.php';</script>";
    }
} else {
    header("Location: users.php");
}
?>
