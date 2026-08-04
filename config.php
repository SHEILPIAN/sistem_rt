<?php
// Mengaktifkan session untuk fitur login
session_start(); 

$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db = "db_sistem_rt"; 

$conn = mysqli_connect($host, $user, $pass, $db); 

if (!$conn) { 
    die("Koneksi database gagal: " . mysqli_connect_error()); 
} 
?>