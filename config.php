<?php
// Mengaktifkan session untuk fitur login
session_start(); 

$host = "trolley.proxy.rlwy.net"; 
$user = "root"; 
$pass = "LnxeTAZtJLuuKhfgwekDgjHJZJHDCxGe"; 
$db = "railway";
$port = "49923";

$conn = mysqli_connect($host, $user, $pass, $db); 

if (!$conn) { 
    die("Koneksi database gagal: " . mysqli_connect_error()); 
} 
?>