<?php
session_start();
include 'config.php';

// Pastikan hanya role dengan izin hapus_warga yang bisa menghapus data
if (!isset($_SESSION['status_login']) || !has_permission('hapus_warga')) {
    header("Location: warga.php");
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

    // 2. Hapus data warga dari database dan rekap iuran terkait jika ada
    $hapus = mysqli_query($conn, "DELETE FROM warga WHERE id='$id'");
    if ($hapus) {
        // Hapus juga baris iuran terkait jika warga ini terdaftar sebagai kepala keluarga
        mysqli_query($conn, "DELETE FROM iuran_warga WHERE warga_id='$id'");
        echo "<script>alert('Data warga berhasil dihapus!'); window.location='warga.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!'); window.location='warga.php';</script>";
    }
} else {
    header("Location: warga.php");
}
?>