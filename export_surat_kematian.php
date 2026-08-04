<?php
include 'config.php';

// Pastikan yang akses adalah admin
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil ID dari URL
if (!isset($_GET['id'])) {
    die("Data tidak ditemukan!");
}
$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM kematian WHERE id = '$id'");
$data = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Kematian - <?= $data['nama_almarhum']; ?></title>
    
    <!-- Ikon FontAwesome untuk Loading & Tombol -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Library untuk mengubah HTML ke PDF Otomatis -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        body {
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            padding: 20px;
            font-family: 'Times New Roman', Times, serif;
        }
        .kertas {
            background-color: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            box-sizing: border-box;
            margin: 0 auto;
        }
        /* Desain Isi Surat */
        .garis { border-bottom: 3px solid black; margin-top: 10px; margin-bottom: 20px; }
        .judul { text-align: center; font-size: 14pt; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
        .nomor { text-align: center; font-size: 12pt; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; font-size: 12pt; }
        td { padding: 4px; vertical-align: top; }
        .ttd-area { width: 100%; margin-top: 40px; text-align: right; }
        .ttd-box { display: inline-block; text-align: center; width: 250px; }
        
        /* Layar Loading */
        #loading { 
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
            background: white; padding: 20px 30px; border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-family: sans-serif; 
            text-align: center; font-weight: bold; color: #1e3a8a; z-index: 999;
        }
    </style>
</head>
<body>

    <!-- Popup Loading saat PDF sedang dibuat -->
    <div id="loading">
        <i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i><br>
        Memproses File PDF...
    </div>

    <!-- Area Kertas yang akan dicetak ke PDF -->
    <div id="area-cetak" class="kertas">
        
        <!-- KOP SURAT DENGAN LOGO -->
        <table style="width: 100%; margin-bottom: 5px; border: none;">
            <tr>
                <!-- Bagian Logo Kiri -->
                <td style="width: 15%; text-align: center; vertical-align: middle; padding: 0;">
                    <!-- Pastikan nama file gambar logo benar, di sini menggunakan image.jpeg -->
                    <img src="image.jpeg" style="width: 85px; height: auto;">
                </td>
                
                <!-- Bagian Teks Tengah -->
                <td style="width: 85%; text-align: center; vertical-align: middle; padding: 0; padding-right: 15%;">
                    <div style="font-size: 15pt; font-weight: bold; line-height: 1.2; font-family: 'Times New Roman', Times, serif;">
                      
                        PENGURUS RT 31 RW 09/1 GRAHA KALIMAS
                    </div>
                </td>
            </tr>
        </table>
        <div class="garis"></div>

        <!-- JUDUL SURAT -->
        <div class="judul">SURAT KETERANGAN KEMATIAN</div>
        <div class="nomor">Nomor: <?= $data['nomor_surat'] ? $data['nomor_surat'] : '..../RT.31/......./2026'; ?></div>

        <!-- ISI SURAT -->
        <p style="font-size: 12pt;">Yang bertanda tangan di bawah ini Ketua RT 31 menerangkan sesungguhnya bahwa:</p>

        <table>
            <tr><td style="width: 5%;"></td><td style="width: 25%;">Nama</td><td style="width: 3%;">:</td><td style="width: 67%;"><b><?= $data['nama_almarhum']; ?></b></td></tr>
            <tr><td></td><td>Tempat / Tanggal Lahir</td><td>:</td><td><?= $data['tempat_lahir']; ?> / <?= ($data['tanggal_lahir'] != NULL) ? date('d-m-Y', strtotime($data['tanggal_lahir'])) : ''; ?></td></tr>
            <tr><td></td><td>Jenis Kelamin</td><td>:</td><td><?= $data['jenis_kelamin']; ?></td></tr>
            <tr><td></td><td>Kewarganegaraan</td><td>:</td><td><?= $data['kewarganegaraan']; ?></td></tr>
            <tr><td></td><td>Agama</td><td>:</td><td><?= $data['agama']; ?></td></tr>
            <tr><td></td><td>Status Perkawinan</td><td>:</td><td><?= $data['status_perkawinan']; ?></td></tr>
            <tr><td></td><td>Pekerjaan</td><td>:</td><td><?= $data['pekerjaan']; ?></td></tr>
            <tr><td></td><td>Alamat</td><td>:</td><td><?= $data['alamat']; ?></td></tr>
        </table>

        <p style="font-size: 12pt; margin-top: 20px;">Telah Meninggal Pada :</p>

        <table>
            <tr><td style="width: 5%;"></td><td style="width: 25%; padding-left: 20px;">Hari / Tanggal</td><td style="width: 3%;">:</td><td style="width: 67%;"><?= $data['hari_wafat']; ?>, <?= ($data['tanggal_wafat'] != NULL) ? date('d-m-Y', strtotime($data['tanggal_wafat'])) : ''; ?></td></tr>
            <tr><td></td><td style="padding-left: 20px;">Tempat Kematian</td><td>:</td><td><?= $data['tempat_kematian']; ?></td></tr>
            <tr><td></td><td style="padding-left: 20px;">Sebab Kematian</td><td>:</td><td><?= $data['sebab_kematian']; ?></td></tr>
        </table>

        <p style="font-size: 12pt; margin-top: 30px;">Demikian surat keterangan ini kami buat sebenar-benarnya agar digunakan seperlunya.</p>

        <!-- TANDA TANGAN -->
        <div class="ttd-area">
            <div class="ttd-box">
                Bekasi, <?= date('d M Y'); ?><br>
                Ketua RT 31
                <br><br><br><br><br>
                <b>( ........................................... )</b>
            </div>
        </div>
    </div>

    <!-- Script JavaScript untuk memicu download PDF otomatis -->
    <script>
        window.onload = function() {
            var element = document.getElementById('area-cetak');
            
            // Pengaturan Kualitas PDF
            var opt = {
                margin:       [0, 0, 0, 0],
                filename:     'Surat_Kematian_<?= str_replace(" ", "_", $data['nama_almarhum']); ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Jalankan proses ubah ke PDF lalu download
            html2pdf().set(opt).from(element).save().then(function() {
                // Tampilkan tombol KEMBALI setelah berhasil
                document.getElementById('loading').innerHTML = `
                    <p style="color: green;"><b><i class="fa-solid fa-check"></i> PDF Berhasil Diunduh!</b></p>
                    <a href="kematian.php" style="display: inline-block; margin-top: 15px; background: #1e3a8a; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Data Kematian
                    </a>
                `;
            });
        };
    </script>
</body>
</html>