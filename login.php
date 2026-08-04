<?php
include 'config.php';

// Jika sudah login, langsung arahkan ke index.php
if (isset($_SESSION['status_login']) && $_SESSION['status_login'] == true) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = MD5($_POST['password']); // Enkripsi MD5 mencocokkan dengan database

    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' AND password = '$password'");
    
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        
        // Simpan data ke session
        $_SESSION['status_login'] = true;
        $_SESSION['id_user'] = $data['id'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['role'] = $data['role']; 
        
        echo "<script>alert('Login Berhasil! Selamat datang.'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Username atau Password salah!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi RT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-200 flex justify-center items-center min-h-screen">

    <div class="w-full max-w-md bg-white min-h-screen sm:min-h-[600px] shadow-2xl sm:rounded-3xl relative overflow-hidden flex flex-col justify-center px-8">
        
        <!-- Background Dekorasi -->
        <div class="absolute top-0 left-0 w-full h-64 bg-blue-900 rounded-b-[50px] -z-0"></div>

        <div class="relative z-10 text-center mb-10 mt-10">
            <!-- Bagian ini yang diubah menjadi Gambar Logo -->
            <img src="image01.jpeg.png" alt="Logo RT" class="w-24 h-24 object-cover rounded-full mx-auto shadow-lg mb-4 border-0 border-white bg-white">
            
            <h1 class="text-2xl font-extrabold text-white">GRAHA KALIMAS RT 5</h1>
            <p class="text-blue-200 text-sm">Silakan login untuk mengelola data</p>
        </div>

        <!-- Form Login -->
        <div class="relative z-10 bg-white p-6 rounded-2xl shadow-xl border border-gray-100">
            <form action="" method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="username" required placeholder="Masukkan username..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" required placeholder="Masukkan password..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm outline-none transition">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" name="login" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-3 rounded-xl shadow-md transition duration-300">
                        Masuk Sistem
                    </button>
                </div>
            </form>
        </div>
        
        <p class="text-center text-gray-400 text-xs mt-8 relative z-10 pb-8">
            &copy; <?= date('Y'); ?> System Informasi RT 5
        </p>
    </div>

</body>
</html>