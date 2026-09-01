<?php
include 'config.php';

// Proses Simpan Data
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama_pemohon'];
    $nik = $_POST['nik_pemohon'];
    $jenis = $_POST['jenis_surat'];
    $keperluan = $_POST['keperluan'];
    
    $insert = mysqli_query($conn, "INSERT INTO surat (nama_pemohon, nik_pemohon, jenis_surat, keperluan) VALUES ('$nama', '$nik', '$jenis', '$keperluan')");

    if ($insert) {
        echo "<script>alert('Pengajuan Surat Berhasil Dikirim! Silakan tunggu konfirmasi RT.'); window.location='surat.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim pengajuan surat!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Surat - RT 5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex items-center gap-3">
            <a href="surat.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="font-bold text-lg">Form Pengajuan Surat</h1>
        </div>

        <!-- Form Input -->
        <div class="p-5">
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Pemohon</label>
                    <input type="text" name="nama_pemohon" value="<?= $_SESSION['role'] == 'warga' ? $_SESSION['nama_lengkap'] : ''; ?>" <?= $_SESSION['role'] == 'warga' ? 'readonly' : ''; ?> required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm <?= $_SESSION['role'] == 'warga' ? 'bg-gray-100 cursor-not-allowed' : ''; ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK Pemohon</label>
                    <input type="number" name="nik_pemohon" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Surat Pengantar</label>
                    <select name="jenis_surat" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="Pengantar Domisili">Pengantar Domisili</option>
                        <option value="Pengantar SKCK">Pengantar SKCK (Kepolisian)</option>
                        <option value="Keterangan Tidak Mampu (SKTM)">Keterangan Tidak Mampu (SKTM)</option>
                        <option value="Keterangan Usaha">Keterangan Usaha / Domisili Usaha</option>
                        <option value="Pengantar Nikah">Pengantar Nikah</option>
                        <option value="Lainnya">Lainnya...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keperluan / Keterangan Detail</label>
                    <textarea name="keperluan" required rows="3" placeholder="Misal: Untuk persyaratan melamar pekerjaan di PT ABC..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" name="simpan" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-md transition duration-200">
                        Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>