<?php

include 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Hanya yang memiliki izin kelola user (ketua rt) yang bisa akses
require_permission('manage_users', 'index.php', 'Akses Ditolak! Hanya Ketua RT yang bisa mengakses halaman ini.');

// Penanganan Form Simpan Hak Akses Dinamis
$pesan_sukses = null;
$pesan_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simpan_permission'])) {
        $target_role = isset($_POST['target_role']) ? strtolower(trim($_POST['target_role'])) : '';
        $selected_perms = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
        
        if (in_array($target_role, ['ketua rt', 'sekretaris', 'warga'])) {
            if (save_role_permissions($conn, $target_role, $selected_perms)) {
                $pesan_sukses = "Hak akses role '" . ucwords($target_role) . "' berhasil diperbarui dan disimpan!";
            } else {
                $pesan_error = "Gagal menyimpan hak akses ke database.";
            }
        }
    } elseif (isset($_POST['reset_permission'])) {
        $target_role = isset($_POST['target_role']) ? strtolower(trim($_POST['target_role'])) : '';
        if (in_array($target_role, ['ketua rt', 'sekretaris', 'warga'])) {
            if (reset_role_permissions($conn, $target_role)) {
                $pesan_sukses = "Hak akses role '" . ucwords($target_role) . "' berhasil dikembalikan ke rekomendasi awal!";
            } else {
                $pesan_error = "Gagal mereset hak akses.";
            }
        }
    }
}

// Logika Pencarian Data
$kata_kunci = "";
if (isset($_GET['cari'])) {
    $kata_kunci = $_GET['cari'];
    $query = mysqli_query($conn, "SELECT * FROM users WHERE nama_lengkap LIKE '%$kata_kunci%' OR username LIKE '%$kata_kunci%' ORDER BY nama_lengkap ASC") or die(mysqli_error($conn));
} else {
    $query = mysqli_query($conn, "SELECT * FROM users ORDER BY nama_lengkap ASC") or die(mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data User - RT 31</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex justify-center">

    <div class="w-full max-w-md bg-white min-h-screen shadow-xl relative pb-20">
        
        <!-- Header -->
        <div class="bg-blue-900 text-white p-4 shadow-md flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="index.php" class="text-white text-xl"><i class="fa-solid fa-arrow-left"></i></a>
                <h1 class="font-bold text-lg">Data User</h1>
            </div>
            
            <a href="tambah_user.php" class="bg-white text-blue-900 px-3 py-1.5 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-100">
                <i class="fa-solid fa-plus"></i> User
            </a>
        </div>

        <!-- Tabs -->
        <?php $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'list'; ?>
        <div class="flex border-b border-gray-200 bg-white">
            <a href="users.php?tab=list" class="w-1/2 py-3 text-center text-sm font-semibold transition <?= $active_tab == 'list' ? 'text-blue-900 border-b-2 border-blue-900' : 'text-gray-500 hover:text-blue-700' ?>">
                <i class="fa-solid fa-users"></i> Daftar User
            </a>
            <a href="users.php?tab=permission" class="w-1/2 py-3 text-center text-sm font-semibold transition <?= $active_tab == 'permission' ? 'text-blue-900 border-b-2 border-blue-900' : 'text-gray-500 hover:text-blue-700' ?>">
                <i class="fa-solid fa-shield-halved"></i> Hak Akses (Assign)
            </a>
        </div>

        <?php if($active_tab == 'list'): ?>
        <!-- Kolom Pencarian -->
        <div class="p-4 bg-gray-50 border-b border-gray-200">
            <form action="" method="GET" class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                </div>
                <input type="text" name="cari" value="<?= $kata_kunci; ?>" placeholder="Cari nama atau username..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm outline-none transition">
                
                <?php if(isset($_GET['cari']) && $_GET['cari'] != ''): ?>
                    <a href="users.php" class="absolute inset-y-0 right-0 pr-3 flex items-center text-red-500 hover:text-red-700">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- List Data User -->
        <div class="px-4 mt-4 space-y-3">
            <?php 
            $jumlah_data = mysqli_num_rows($query);
            if ($jumlah_data > 0): 
            ?>
                <div class="flex justify-between items-center mb-3">
                    <p class="text-xs text-gray-500">Menampilkan <?= $jumlah_data; ?> data user.</p>
                </div>
                <?php while($row = mysqli_fetch_assoc($query)) : ?>
                <div class="bg-white border border-gray-200 p-3 rounded-xl shadow-sm flex items-start gap-3 hover:bg-blue-50 transition relative">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl shrink-0 mt-1 border border-blue-200">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="w-full pr-8">
                        <h3 class="font-bold text-gray-800 text-sm"><?= $row['nama_lengkap']; ?></h3>
                        <p class="text-xs text-gray-600 font-mono mb-1">@<?= $row['username']; ?></p>
                        
                        <div class="flex gap-2 mb-2">
                            <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded border border-blue-200 font-semibold uppercase"><?= $row['role']; ?></span>
                        </div>
                    </div>

                    <div class="absolute top-3 right-3 flex gap-2">
                        <a href="edit_user.php?id=<?= $row['id']; ?>" class="text-blue-400 hover:text-blue-600 bg-blue-50 hover:bg-blue-100 w-8 h-8 flex items-center justify-center rounded-lg transition border border-blue-100 shadow-sm">
                            <i class="fa-solid fa-pen text-sm"></i>
                        </a>
                        <?php if($row['id'] != $_SESSION['id_user']): ?>
                        <a href="hapus_user.php?id=<?= $row['id']; ?>" onclick="return confirm('Peringatan: Yakin ingin menghapus user ini?');" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 w-8 h-8 flex items-center justify-center rounded-lg transition border border-red-100 shadow-sm">
                            <i class="fa-solid fa-trash-can text-sm"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-users-slash text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-400 text-sm">Data user tidak ditemukan.</p>
                    <?php if(isset($_GET['cari'])): ?>
                        <a href="users.php" class="text-blue-600 text-xs mt-2 inline-block hover:underline">Tampilkan semua data</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Tab Assign Permission Dinamis -->
        <?php
        $role_param = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : 'warga';
        if (!in_array($role_param, ['ketua rt', 'sekretaris', 'warga'])) {
            $role_param = 'warga';
        }
        $active_role_perms = get_active_permissions_for_role($conn, $role_param);
        $system_modules = get_all_system_permissions();
        ?>

        <!-- Notifikasi Pesan -->
        <?php if($pesan_sukses): ?>
        <div class="m-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs flex items-center gap-2.5 shadow-sm">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
            <span class="font-medium"><?= $pesan_sukses; ?></span>
        </div>
        <?php endif; ?>

        <?php if($pesan_error): ?>
        <div class="m-4 p-3.5 bg-red-50 border border-red-200 text-red-800 rounded-xl text-xs flex items-center gap-2.5 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation text-red-600 text-base shrink-0"></i>
            <span class="font-medium"><?= $pesan_error; ?></span>
        </div>
        <?php endif; ?>

        <!-- Selector Role (Pills) -->
        <div class="p-3 bg-gray-50 border-b border-gray-200">
            <p class="text-[11px] font-bold text-gray-500 mb-2 uppercase tracking-wider">Pilih Role Yang Dikonfigurasi:</p>
            <div class="flex gap-2 overflow-x-auto pb-1">
                <a href="users.php?tab=permission&role=ketua rt" class="px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap <?= $role_param === 'ketua rt' ? 'bg-blue-900 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100' ?>">
                    <i class="fa-solid fa-user-shield"></i> Ketua RT
                </a>
                <a href="users.php?tab=permission&role=sekretaris" class="px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap <?= $role_param === 'sekretaris' ? 'bg-emerald-700 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100' ?>">
                    <i class="fa-solid fa-user-pen"></i> Sekretaris
                </a>
                <a href="users.php?tab=permission&role=warga" class="px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap <?= $role_param === 'warga' ? 'bg-orange-600 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100' ?>">
                    <i class="fa-solid fa-users"></i> Warga
                </a>
            </div>
        </div>

        <!-- Banner Info Role Terpilih -->
        <div class="p-4 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">
                        Atur Izin: <span class="uppercase text-blue-900 font-extrabold"><?= $role_param; ?></span>
                    </h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                        Aktifkan atau nonaktifkan hak akses untuk role ini di tabel bawah:
                    </p>
                </div>
                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg <?= $role_param === 'ketua rt' ? 'bg-blue-100 text-blue-800' : ($role_param === 'sekretaris' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'); ?>">
                    <?= count($active_role_perms); ?> Izin Aktif
                </span>
            </div>
        </div>

        <!-- Form Tabel Hak Akses -->
        <form action="users.php?tab=permission&role=<?= urlencode($role_param); ?>" method="POST">
            <input type="hidden" name="target_role" value="<?= htmlspecialchars($role_param); ?>">
            
            <div class="p-3 space-y-4">
                <?php foreach ($system_modules as $group_title => $permissions_list): ?>
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-gray-50/90 px-3.5 py-2.5 border-b border-gray-200 flex justify-between items-center">
                        <h4 class="font-bold text-[11px] text-gray-700 uppercase tracking-wide flex items-center gap-1.5">
                            <i class="fa-solid fa-folder-closed text-blue-900"></i> <?= $group_title; ?>
                        </h4>
                        <span class="text-[10px] text-gray-400 font-medium"><?= count($permissions_list); ?> Fitur</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-gray-100/60 border-b border-gray-200 text-[10px] text-gray-500 uppercase">
                                    <th class="py-2 px-3 font-semibold">Kode Permission & Fitur</th>
                                    <th class="py-2 px-3 font-semibold text-center w-16">Aktif</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($permissions_list as $perm_code => $perm_meta): 
                                    $is_active = in_array($perm_code, $active_role_perms, true);
                                ?>
                                <tr class="hover:bg-blue-50/40 transition <?= $is_active ? 'bg-white' : 'bg-gray-50/50 opacity-80' ?>">
                                    <td class="py-2.5 px-3 align-top">
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <code class="text-[10px] font-mono font-bold bg-blue-50 text-blue-900 px-2 py-0.5 rounded border border-blue-200"><?= $perm_code; ?></code>
                                        </div>
                                        <div class="font-bold text-gray-800 text-[11px] mt-1"><?= $perm_meta['name']; ?></div>
                                        <div class="text-[10px] text-gray-500 leading-tight mt-0.5"><?= $perm_meta['desc']; ?></div>
                                    </td>
                                    <td class="py-2.5 px-3 text-center align-middle">
                                        <label class="inline-flex items-center justify-center cursor-pointer p-1">
                                            <input type="checkbox" name="permissions[]" value="<?= $perm_code; ?>" <?= $is_active ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer">
                                        </label>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Tombol Aksi Simpan & Reset -->
                <div class="pt-2 pb-6 space-y-2.5">
                    <button type="submit" name="simpan_permission" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-3 px-4 rounded-xl shadow-md transition duration-200 flex justify-center items-center gap-2 text-xs">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Hak Akses (<?= ucwords($role_param); ?>)
                    </button>
                    
                    <button type="submit" name="reset_permission" onclick="return confirm('Apakah Anda yakin ingin mereset seluruh hak akses role <?= ucwords($role_param); ?> ke nilai rekomendasi bawaan?');" class="w-full bg-white hover:bg-gray-100 text-gray-600 font-semibold py-2.5 px-4 rounded-xl border border-gray-300 transition duration-200 text-xs flex justify-center items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-rotate-left"></i> Reset ke Rekomendasi Default
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <!-- Bottom Navigation Bar -->
        <div class="fixed bottom-0 w-full max-w-md bg-white border-t border-gray-200 flex justify-around py-3 text-gray-500 text-xs shadow-lg z-50">
            <a href="index.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="mt-1">Home</span>
            </a>
            <a href="warga.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-users text-lg"></i>
                <span class="mt-1">Warga</span>
            </a>
            <a href="users.php" class="flex flex-col items-center text-blue-600">
                <i class="fa-solid fa-user-shield text-lg"></i>
                <span class="mt-1 font-medium">User</span>
            </a>
            <a href="keuangan.php" class="flex flex-col items-center hover:text-blue-600">
                <i class="fa-solid fa-wallet text-lg"></i>
                <span class="mt-1">Keuangan</span>
            </a>
        </div>

    </div>
</body>
</html>
