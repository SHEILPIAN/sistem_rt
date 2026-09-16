<?php
/**
 * Helper Otentikasi dan Manajemen Hak Akses (Role-Based Permissions)
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

if (!function_exists('get_role_permissions_matrix')) {
    /**
     * Matriks Hak Akses (Permissions) per Role
     *
     * @return array
     */
    function get_role_permissions_matrix() {
        return [
            // Ketua RT (Superadmin): Akses Penuh
            'ketua rt' => [
                'view_warga',
                'view_nik',
                'tambah_warga',
                'edit_warga',
                'hapus_warga',
                'export_warga',
                'view_document_warga', // Melihat berkas fisik KTP & KK
                'manage_users',        // Kelola user
                'view_keuangan',
                'manage_keuangan',
                'export_keuangan',
                'view_surat',
                'create_surat',
                'manage_surat',
                'approve_surat',
                'export_surat',
                'view_aduan',
                'create_aduan',
                'manage_aduan',
                'view_inventaris',
                'manage_inventaris',
                'view_sumbangan',
                'manage_sumbangan',
                'view_mutasi',         // Kelahiran, kematian, pindah
                'manage_mutasi',
                'view_profil',
                'edit_profil',
            ],

            // Sekretaris (Admin Operasional)
            'sekretaris' => [
                'view_warga',
                'view_nik',
                'tambah_warga',
                'edit_warga',
                'hapus_warga',
                'export_warga',
                'view_document_warga',
                'view_keuangan',
                'manage_keuangan',
                'export_keuangan',
                'view_surat',
                'create_surat',
                'manage_surat',
                'approve_surat',
                'export_surat',
                'view_aduan',
                'create_aduan',
                'manage_aduan',
                'view_inventaris',
                'manage_inventaris',
                'view_sumbangan',
                'manage_sumbangan',
                'view_mutasi',
                'manage_mutasi',
                'view_profil',
                'edit_profil',
            ],

            // Warga: Akses Terbatas & Mandiri (NIK & Dokumen Kependudukan Disembunyikan Total)
            'warga' => [
                'view_warga',        // Hanya melihat nama, alamat, dan status warga
                'view_keuangan',
                'view_surat',
                'create_surat',
                'view_own_surat',
                'view_aduan',
                'create_aduan',
                'view_inventaris',
                'view_sumbangan',
                'view_profil',
                'edit_profil',
            ]
        ];
    }
}

if (!function_exists('has_permission')) {
    /**
     * Memeriksa apakah role saat ini memiliki izin tertentu
     *
     * @param string $permission
     * @return bool
     */
    function has_permission($permission) {
        $current_role = get_user_role();
        if (empty($current_role)) {
            return false;
        }

        $matrix = get_role_permissions_matrix();
        if (!isset($matrix[$current_role])) {
            return false;
        }

        return in_array($permission, $matrix[$current_role], true);
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

