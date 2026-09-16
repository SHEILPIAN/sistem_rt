<?php
include 'config.php';

// Cek akses
if (!isset($_SESSION['status_login']) || !in_array($_SESSION['role'], ['ketua rt', 'sekretaris'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    // Validasi status
    if ($status == 'Selesai') {
        $query = mysqli_query($conn, "UPDATE pengaduan SET status_aduan = '$status' WHERE id = '$id'");
        
        if ($query) {
            echo "<script>alert('Aduan berhasil ditandai sebagai Selesai!'); window.location='aduan.php';</script>";
        } else {
            echo "<script>alert('Gagal mengupdate status aduan!'); window.location='aduan.php';</script>";
        }
    } else {
        echo "<script>alert('Status tidak valid!'); window.location='aduan.php';</script>";
    }
} else {
    header("Location: aduan.php");
}
?>
