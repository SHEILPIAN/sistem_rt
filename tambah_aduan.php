<?php
include 'config.php';

if (isset($_POST['simpan'])) {
    $pelapor = $_POST['pelapor'];
    $isi_aduan = $_POST['isi_aduan'];
    
    $insert = mysqli_query($conn, "INSERT INTO pengaduan (pelapor, isi_aduan) VALUES ('$pelapor', '$isi_aduan')");

    if ($insert) {
        echo "<script>alert('Aduan Berhasil Dikirim!'); window.location='aduan.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim aduan!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tulis Aduan - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="aduan.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Tulis Aduan Baru</h1>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pelapor</label>
                    <input type="text" name="pelapor" required placeholder="Nama Anda" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-orange-500 focus:border-orange-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Isi Aduan / Laporan</label>
                    <textarea name="isi_aduan" required rows="5" placeholder="Contoh: Lampu jalan di depan Blok B padam..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-orange-500 focus:border-orange-500 text-sm"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" name="simpan" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        Kirim Aduan
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>