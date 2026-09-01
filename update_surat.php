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
    if (in_array($status, ['Selesai', 'Ditolak'])) {
        $query = mysqli_query($conn, "UPDATE surat SET status_surat = '$status' WHERE id = '$id'");
        
        if ($query) {
            echo "<script>alert('Status surat berhasil diupdate menjadi $status!'); window.location='surat.php';</script>";
        } else {
            echo "<script>alert('Gagal mengupdate status surat!'); window.location='surat.php';</script>";
        }
    } else {
        echo "<script>alert('Status tidak valid!'); window.location='surat.php';</script>";
    }
} else {
    header("Location: surat.php");
}
?>
