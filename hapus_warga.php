<?php
session_start();
include 'config.php';

// Pastikan hanya admin yang bisa menghapus data
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. Cek data foto untuk dihapus dari folder 'uploads' agar tidak menjadi sampah file
    $cek = mysqli_query($conn, "SELECT foto_ktp, foto_kk FROM warga WHERE id='$id'");
    $data = mysqli_fetch_assoc($cek);
    
    if ($data) {
        if (!empty($data['foto_ktp']) && file_exists("uploads/" . $data['foto_ktp'])) {
            unlink("uploads/" . $data['foto_ktp']); // Hapus file KTP
        }
        if (!empty($data['foto_kk']) && file_exists("uploads/" . $data['foto_kk'])) {
            unlink("uploads/" . $data['foto_kk']); // Hapus file KK
        }
    }

    // 2. Hapus data warga dari database
    $hapus = mysqli_query($conn, "DELETE FROM warga WHERE id='$id'");

    if ($hapus) {
        echo "<script>alert('Data warga berhasil dihapus!'); window.location='warga.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!'); window.location='warga.php';</script>";
    }
} else {
    header("Location: warga.php");
}
?>