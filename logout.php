<?php
session_start();
session_destroy(); // Menghapus semua data sesi
echo "<script>alert('Anda berhasil keluar dari sistem!'); window.location='login.php';</script>";
?>