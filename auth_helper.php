<?php
/**
 * Helper Otentikasi dan Manajemen Hak Akses Dinamis (Dynamic Role-Based Permissions)
 * Sistem Informasi RT 31
 */

if (!function_exists('get_user_role')) {
    /**
     * Mendapatkan role user saat ini dari session dengan normalisasi.
     * Mengonversi alias lama (seperti 'admin') menjadi 'ketua rt' untuk kompatibilitas.
     *
     * @return string
     */
    function get_user_role() {
        if (!isset($_SESSION['role'])) {
            return '';
        }
        $role = strtolower(trim($_SESSION['role']));
        if ($role === 'admin' || $role === 'superadmin') {
            return 'ketua rt';
        }
        return $role;
    }
}

if (!function_exists('get_all_system_permissions')) {
    /**
     * Daftar Seluruh Hak Akses Sistem Terstandarisasi (Format action:resource)
     * Dikelompokkan berdasarkan Modul Fitur
     *
     * @return array
     */
    function get_all_system_permissions() {
        return [
            'Warga & Privasi NIK' => [
                'read:warga' => [
                    'name' => 'Melihat Daftar Warga',
                    'desc' => 'Dapat membuka modul data warga dan melihat nama, alamat, serta status warga.',
                    'aliases' => ['view_warga']
                ],
                'view:nik' => [
                    'name' => 'Melihat Nomor NIK Warga',
                    'desc' => 'Dapat melihat nomor NIK lengkap warga di seluruh modul. Jika dinonaktifkan, NIK warga dihilangkan total.',
                    'aliases' => ['view_nik']
                ],
                'view:document_warga' => [
                    'name' => 'Melihat Dokumen KTP & KK',
                    'desc' => 'Dapat membuka dan melihat berkas fisik foto KTP dan Kartu Keluarga warga.',
                    'aliases' => ['view_document_warga']
                ],
                'create:warga' => [
                    'name' => 'Menambah Data Warga',
                    'desc' => 'Dapat mengakses form tambah warga baru dan mengunggah dokumen.',
                    'aliases' => ['tambah_warga']
                ],
                'edit:warga' => [
                    'name' => 'Mengedit Data Warga',
                    'desc' => 'Dapat mengubah informasi dan profil data warga.',
                    'aliases' => ['edit_warga']
                ],
                'delete:warga' => [
                    'name' => 'Menghapus Data Warga',
                    'desc' => 'Dapat menghapus data warga secara permanen beserta berkas fotonya.',
                    'aliases' => ['hapus_warga']
                ],
                'export:warga' => [
                    'name' => 'Ekspor Sensus Warga (Excel)',
                    'desc' => 'Dapat mengunduh file rekapitulasi data kependudukan warga ke format Excel.',
                    'aliases' => ['export_warga']
                ],
            ],
            'Manajemen User' => [
                'create:user' => [
                    'name' => 'Menambah Akun User Baru',
                    'desc' => 'Dapat membuat akun pengguna baru untuk Ketua RT, Sekretaris, atau Warga.',
                    'aliases' => ['manage_users', 'tambah_user']
                ],
                'read:user' => [
                    'name' => 'Melihat Daftar User & Hak Akses',
                    'desc' => 'Dapat membuka menu Data User dan mengatur hak akses (assign permission).',
                    'aliases' => ['manage_users', 'view_user']
                ],
                'edit:user' => [
                    'name' => 'Mengedit Data User & Role',
                    'desc' => 'Dapat mengubah username, nama lengkap, password, dan role user.',
                    'aliases' => ['manage_users', 'edit_user']
                ],
                'delete:user' => [
                    'name' => 'Menghapus Akun User',
                    'desc' => 'Dapat menghapus akun user dari sistem.',
                    'aliases' => ['manage_users', 'hapus_user']
                ],
            ],
            'Layanan Surat' => [
                'read:surat' => [
                    'name' => 'Melihat Daftar Surat',
                    'desc' => 'Dapat membuka modul layanan surat pengantar kependudukan.',
                    'aliases' => ['view_surat']
                ],
                'create:surat' => [
                    'name' => 'Mengajukan Surat Pengantar',
                    'desc' => 'Dapat mengisi form permohonan surat pengantar.',
                    'aliases' => ['create_surat']
                ],
                'approve:surat' => [
                    'name' => 'Menyetujui / Menolak Surat',
                    'desc' => 'Dapat memproses status surat menjadi Selesai atau Ditolak.',
                    'aliases' => ['approve_surat', 'manage_surat']
                ],
                'export:surat' => [
                    'name' => 'Cetak Surat Pengantar PDF',
                    'desc' => 'Dapat mengunduh atau mencetak surat yang telah selesai ke format PDF.',
                    'aliases' => ['export_surat']
                ],
            ],
            'Pengaduan Warga' => [
                'read:aduan' => [
                    'name' => 'Melihat Daftar Aduan',
                    'desc' => 'Dapat melihat daftar keluhan / laporan yang masuk dari warga.',
                    'aliases' => ['view_aduan']
                ],
                'create:aduan' => [
                    'name' => 'Membuat Aduan Baru',
                    'desc' => 'Dapat mengirimkan laporan keluhan / aduan lingkungan RT.',
                    'aliases' => ['create_aduan']
                ],
                'manage:aduan' => [
                    'name' => 'Menindaklanjuti Aduan',
                    'desc' => 'Dapat memproses dan merubah status penyelesaian aduan warga.',
                    'aliases' => ['manage_aduan']
                ],
            ],
            'Keuangan Kas RT' => [
                'read:keuangan' => [
                    'name' => 'Melihat Laporan Kas Keuangan',
                    'desc' => 'Dapat memantau rekapitulasi saldo dan mutasi kas RT.',
                    'aliases' => ['view_keuangan']
                ],
                'manage:keuangan' => [
                    'name' => 'Kelola Transaksi Kas Masuk/Keluar',
                    'desc' => 'Dapat mencatat transaksi pemasukan dan pengeluaran kas RT.',
                    'aliases' => ['manage_keuangan']
                ],
                'export:keuangan' => [
                    'name' => 'Ekspor Laporan Kas (Excel)',
                    'desc' => 'Dapat mengunduh file laporan kas keuangan RT ke Excel.',
                    'aliases' => ['export_keuangan']
                ],
            ],
            'Mutasi & Inventaris' => [
                'manage:mutasi' => [
                    'name' => 'Kelola Mutasi Kependudukan',
                    'desc' => 'Dapat mencatat dan mengelola data kelahiran, kematian, dan mutasi pindah.',
                    'aliases' => ['view_mutasi', 'manage_mutasi']
                ],
                'manage:inventaris' => [
                    'name' => 'Kelola Inventaris Aset RT',
                    'desc' => 'Dapat mencatat dan memperbarui barang aset / sarana milik RT.',
                    'aliases' => ['view_inventaris', 'manage_inventaris']
                ],
                'manage:sumbangan' => [
                    'name' => 'Kelola Sumbangan Warga',
                    'desc' => 'Dapat mencatat dan mengelola penerimaan sumbangan / donasi warga.',
                    'aliases' => ['view_sumbangan', 'manage_sumbangan']
                ],
            ]
        ];
    }
}

if (!function_exists('get_default_role_permissions')) {
    /**
     * Konfigurasi Standar Rekomendasi Hak Akses per Role
     *
     * @return array
     */
    function get_default_role_permissions() {
        return [
            // Ketua RT (Superadmin): Memiliki seluruh izin
            'ketua rt' => [
                'read:warga',
                'view:nik',
                'view:document_warga',
                'create:warga',
                'edit:warga',
                'delete:warga',
                'export:warga',
                'create:user',
                'read:user',
                'edit:user',
                'delete:user',
                'read:surat',
                'create:surat',
                'approve:surat',
                'export:surat',
                'read:aduan',
                'create:aduan',
                'manage:aduan',
                'read:keuangan',
                'manage:keuangan',
                'export:keuangan',
                'manage:mutasi',
                'manage:inventaris',
                'manage:sumbangan',
            ],

            // Sekretaris (Admin Operasional): Izin operasional penuh tanpa manajemen user
            'sekretaris' => [
                'read:warga',
                'view:nik',
                'view:document_warga',
                'create:warga',
                'edit:warga',
                'delete:warga',
                'export:warga',
                'read:surat',
                'create:surat',
                'approve:surat',
                'export:surat',
                'read:aduan',
                'create:aduan',
                'manage:aduan',
                'read:keuangan',
                'manage:keuangan',
                'export:keuangan',
                'manage:mutasi',
                'manage:inventaris',
                'manage:sumbangan',
            ],

            // Warga: Akses mandiri & monitoring (view:nik dimatikan total secara default)
            'warga' => [
                'read:warga',
                'read:surat',
                'create:surat',
                'export:surat',
                'read:aduan',
                'create:aduan',
                'read:keuangan',
            ]
        ];
    }
}

if (!function_exists('get_permission_alias_map')) {
    /**
     * Peta alias untuk backward compatibility antara format action:resource dan format lama.
     *
     * @return array
     */
    function get_permission_alias_map() {
        static $map = null;
        if ($map !== null) return $map;

        $map = [];
        $modules = get_all_system_permissions();
        foreach ($modules as $group => $items) {
            foreach ($items as $perm_key => $details) {
                // Key format baru memetakan ke alias
                if (!isset($map[$perm_key])) $map[$perm_key] = [];
                $map[$perm_key][] = $perm_key;

                if (!empty($details['aliases'])) {
                    foreach ($details['aliases'] as $alias) {
                        $map[$perm_key][] = $alias;
                        if (!isset($map[$alias])) $map[$alias] = [];
                        $map[$alias][] = $perm_key;
                        $map[$alias][] = $alias;
                    }
                }
            }
        }

        return $map;
    }
}

if (!function_exists('init_role_permissions_table')) {
    /**
     * Menginisialisasi tabel role_permissions di MySQL dan melakukan seeding data default.
     *
     * @param mysqli $conn
     */
    function init_role_permissions_table($conn) {
        if (!$conn) return;

        $create_sql = "CREATE TABLE IF NOT EXISTS role_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(50) NOT NULL,
            permission VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_role_perm (role, permission)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        @mysqli_query($conn, $create_sql);

        // Periksa apakah tabel sudah memiliki data
        $check = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM role_permissions");
        if ($check) {
            $data = mysqli_fetch_assoc($check);
            if ($data['total'] == 0) {
                // Seed data rekomendasi awal
                $defaults = get_default_role_permissions();
                foreach ($defaults as $role => $perms) {
                    foreach ($perms as $perm) {
                        $r = mysqli_real_escape_string($conn, $role);
                        $p = mysqli_real_escape_string($conn, $perm);
                        @mysqli_query($conn, "INSERT IGNORE INTO role_permissions (role, permission) VALUES ('$r', '$p')");
                    }
                }
            }
        }
    }
}

if (!function_exists('get_active_permissions_for_role')) {
    /**
     * Mengambil daftar hak akses yang aktif untuk role tertentu dari database.
     * Jika database belum terisi / offline, menggunakan nilai default rekomendasi.
     *
     * @param mysqli|null $conn
     * @param string|null $role
     * @param bool $force_reload
     * @return array
     */
    function get_active_permissions_for_role($conn, $role, $force_reload = false) {
        static $runtime_cache = [];
        if ($force_reload) {
            $runtime_cache = [];
            if ($role === null) return [];
        }

        if ($role === null) return [];

        $role = strtolower(trim($role));
        if ($role === 'admin' || $role === 'superadmin') $role = 'ketua rt';

        if (!$force_reload && isset($runtime_cache[$role])) {
            return $runtime_cache[$role];
        }

        $perms = [];
        if ($conn) {
            $r = mysqli_real_escape_string($conn, $role);
            $query = @mysqli_query($conn, "SELECT permission FROM role_permissions WHERE role = '$r'");
            if ($query && mysqli_num_rows($query) > 0) {
                while ($row = mysqli_fetch_assoc($query)) {
                    $perms[] = $row['permission'];
                }
            }
        }

        // Jika tidak ada data dari DB (misal tabel belum siap), fallback ke default
        if (empty($perms)) {
            $defaults = get_default_role_permissions();
            $perms = isset($defaults[$role]) ? $defaults[$role] : [];
        }

        $runtime_cache[$role] = $perms;
        return $perms;
    }
}

if (!function_exists('save_role_permissions')) {
    /**
     * Menyimpan daftar permission terpilih dari form admin ke database untuk suatu role.
     *
     * @param mysqli $conn
     * @param string $role
     * @param array $new_permissions
     * @return bool
     */
    function save_role_permissions($conn, $role, array $new_permissions) {
        if (!$conn) return false;
        $role = strtolower(trim($role));
        if ($role === 'admin' || $role === 'superadmin') $role = 'ketua rt';

        $r = mysqli_real_escape_string($conn, $role);
        @mysqli_query($conn, "DELETE FROM role_permissions WHERE role = '$r'");

        // Jika role adalah ketua rt, pastikan izin read:user dan edit:user selalu aktif agar tidak terkunci
        if ($role === 'ketua rt') {
            if (!in_array('read:user', $new_permissions)) $new_permissions[] = 'read:user';
            if (!in_array('edit:user', $new_permissions)) $new_permissions[] = 'edit:user';
        }

        foreach ($new_permissions as $perm) {
            $perm = trim($perm);
            if (!empty($perm)) {
                $p = mysqli_real_escape_string($conn, $perm);
                @mysqli_query($conn, "INSERT IGNORE INTO role_permissions (role, permission) VALUES ('$r', '$p')");
            }
        }

        // Bersihkan cache runtime agar perubahan langsung terbaca seketika
        get_active_permissions_for_role(null, null, true);

        return true;
    }
}

if (!function_exists('reset_role_permissions')) {
    /**
     * Mengembalikan permission suatu role ke pengaturan default.
     *
     * @param mysqli $conn
     * @param string $role
     * @return bool
     */
    function reset_role_permissions($conn, $role) {
        $defaults = get_default_role_permissions();
        $role = strtolower(trim($role));
        if ($role === 'admin' || $role === 'superadmin') $role = 'ketua rt';
        $perms = isset($defaults[$role]) ? $defaults[$role] : [];
        return save_role_permissions($conn, $role, $perms);
    }
}

if (!function_exists('has_permission')) {
    /**
     * Memeriksa apakah role user saat ini memiliki izin tertentu (secara dinamis).
     * Mendukung kode format baru (action:resource) dan format legacy alias.
     *
     * @param string $permission
     * @return bool
     */
    function has_permission($permission) {
        global $conn;
        $current_role = get_user_role();
        if (empty($current_role)) {
            return false;
        }

        $active_perms = get_active_permissions_for_role($conn, $current_role);

        // 1. Cocok langsung
        if (in_array($permission, $active_perms, true)) {
            return true;
        }

        // 2. Cocok melalui alias
        $alias_map = get_permission_alias_map();
        if (isset($alias_map[$permission])) {
            foreach ($alias_map[$permission] as $alt) {
                if (in_array($alt, $active_perms, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('can')) {
    /**
     * Alias singkat untuk has_permission
     *
     * @param string $permission
     * @return bool
     */
    function can($permission) {
        return has_permission($permission);
    }
}

if (!function_exists('has_role')) {
    /**
     * Memeriksa apakah pengguna memiliki salah satu role yang diberikan
     *
     * @param string|array $roles
     * @return bool
     */
    function has_role($roles) {
        $current_role = get_user_role();
        if (empty($current_role)) {
            return false;
        }

        if (is_array($roles)) {
            $normalized_roles = array_map(function($r) {
                $r = strtolower(trim($r));
                return ($r === 'admin' || $r === 'superadmin') ? 'ketua rt' : $r;
            }, $roles);
            return in_array($current_role, $normalized_roles, true);
        }

        $single_role = strtolower(trim($roles));
        if ($single_role === 'admin' || $single_role === 'superadmin') {
            $single_role = 'ketua rt';
        }
        return $current_role === $single_role;
    }
}

if (!function_exists('require_permission')) {
    /**
     * Memastikan user memiliki hak akses tertentu.
     * Jika tidak, hentikan proses dan alihkan dengan notifikasi.
     *
     * @param string $permission
     * @param string $redirect_url
     * @param string $message
     */
    function require_permission($permission, $redirect_url = 'index.php', $message = 'Akses Ditolak! Anda tidak memiliki izin untuk tindakan ini.') {
        if (!has_permission($permission)) {
            echo "<script>alert('" . addslashes($message) . "'); window.location='" . addslashes($redirect_url) . "';</script>";
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    /**
     * Memastikan user memiliki salah satu role tertentu.
     * Jika tidak, hentikan proses dan alihkan dengan notifikasi.
     *
     * @param string|array $roles
     * @param string $redirect_url
     * @param string $message
     */
    function require_role($roles, $redirect_url = 'index.php', $message = 'Akses Ditolak! Halaman ini hanya untuk pengguna dengan hak akses khusus.') {
        if (!has_role($roles)) {
            echo "<script>alert('" . addslashes($message) . "'); window.location='" . addslashes($redirect_url) . "';</script>";
            exit;
        }
    }
}
