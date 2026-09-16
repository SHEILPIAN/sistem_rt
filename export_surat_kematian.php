<?php
include 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

// Cek apakah mode blangko kosong atau cetak data tertentu
$is_blangko = isset($_GET['blangko']) || !isset($_GET['id']) || empty($_GET['id']);
$data = [];

if (!$is_blangko) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = mysqli_query($conn, "SELECT * FROM kematian WHERE id = '$id'");
    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
    } else {
        $is_blangko = true; // Jika ID tidak valid, tampilkan blangko kosong
    }
}

// Fungsi Konversi Bulan ke Angka Romawi
if (!function_exists('bulan_ke_romawi')) {
    function bulan_ke_romawi($bln) {
        $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        return $romawi[(int)$bln] ?? 'I';
    }
}

// Fungsi Format Tanggal Bahasa Indonesia
if (!function_exists('format_indo')) {
    function format_indo($tgl) {
        if (empty($tgl) || $tgl == '0000-00-00') return '';
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $time = strtotime($tgl);
        $d = date('j', $time);
        $m = $bulan[(int)date('n', $time)] ?? '';
        $y = date('Y', $time);
        return "$d $m $y";
    }
}

// Menyiapkan variabel isi surat
if ($is_blangko) {
    $nomor_surat_display = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    $bulan_romawi        = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    $tahun_surat_2digit  = date('y');
    
    $val_nama   = "&nbsp;";
    $val_nik    = "&nbsp;";
    $val_ttl    = "&nbsp;";
    $val_jk     = "&nbsp;";
    $val_agama  = "&nbsp;";
    $val_alamat = "&nbsp;<br>&nbsp;";

    $val_hari_tgl_wafat = "&nbsp;";
    $val_pukul          = "&nbsp;";
    $val_tutup_usia     = "&nbsp;";
    $val_sebab          = "&nbsp;";

    $tgl_surat_display  = ".................................................. 20" . date('y');
    $pdf_filename       = "Blangko_Surat_Kematian_RT31.pdf";
} else {
    $tgl_wafat_raw = $data['tanggal_wafat'] ?? date('Y-m-d');
    $bln_wafat = date('n', strtotime($tgl_wafat_raw));
    
    // Nomor Surat: Jika diinput ada, pakai yang diinput, jika tidak gunakan template SKKM
    if (!empty($data['nomor_surat'])) {
        $nomor_surat_display = htmlspecialchars($data['nomor_surat']);
    } else {
        $nomor_surat_display = sprintf('%03d', (int)$data['id']);
    }
    
    $bulan_romawi       = bulan_ke_romawi($bln_wafat);
    $tahun_surat_2digit = date('y', strtotime($tgl_wafat_raw));

    $val_nama   = !empty($data['nama_almarhum']) ? htmlspecialchars($data['nama_almarhum']) : '-';
    $val_nik    = !empty($data['nik']) ? htmlspecialchars($data['nik']) : '-';

    // Tempat & Tanggal Lahir
    $ttl_arr = [];
    if (!empty($data['tempat_lahir'])) $ttl_arr[] = htmlspecialchars($data['tempat_lahir']);
    if (!empty($data['tanggal_lahir'])) $ttl_arr[] = format_indo($data['tanggal_lahir']);
    $val_ttl = !empty($ttl_arr) ? implode(', ', $ttl_arr) : '-';

    // Jenis Kelamin
    $jk_raw = strtoupper(trim($data['jenis_kelamin'] ?? ''));
    $val_jk = ($jk_raw === 'L' || $jk_raw === 'LAKI-LAKI') ? 'Laki-laki' : (($jk_raw === 'P' || $jk_raw === 'PEREMPUAN') ? 'Perempuan' : '-');

    $val_agama  = !empty($data['agama']) ? htmlspecialchars($data['agama']) : '-';
    $val_alamat = !empty($data['alamat']) ? nl2br(htmlspecialchars($data['alamat'])) : '-';

    // Keterangan Meninggal
    $hari_tgl_arr = [];
    if (!empty($data['hari_wafat'])) $hari_tgl_arr[] = htmlspecialchars($data['hari_wafat']);
    if (!empty($data['tanggal_wafat'])) $hari_tgl_arr[] = format_indo($data['tanggal_wafat']);
    $val_hari_tgl_wafat = !empty($hari_tgl_arr) ? implode(', ', $hari_tgl_arr) : '-';

    $val_pukul = !empty($data['pukul_wafat']) ? htmlspecialchars($data['pukul_wafat']) : '-';

    // Tutup Usia: Ambil dari kolom tutup_usia atau hitung dari selisih tanggal lahir dan wafat
    if (!empty($data['tutup_usia'])) {
        $val_tutup_usia = htmlspecialchars($data['tutup_usia']);
    } elseif (!empty($data['tanggal_lahir']) && !empty($data['tanggal_wafat'])) {
        $d1 = new DateTime($data['tanggal_lahir']);
        $d2 = new DateTime($data['tanggal_wafat']);
        $val_tutup_usia = $d1->diff($d2)->y . " Tahun";
    } else {
        $val_tutup_usia = '-';
    }

    $sebab_raw = $data['sebab_kematian'] ?? ($data['penyebab'] ?? '');
    $val_sebab = !empty($sebab_raw) ? htmlspecialchars($sebab_raw) : '-';

    $tgl_surat_display = format_indo($data['tanggal_wafat'] ?? date('Y-m-d'));
    $pdf_filename      = 'Surat_Kematian_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['nama_almarhum']) . '.pdf';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_blangko ? 'Blangko Surat Kematian' : 'Surat Kematian - ' . htmlspecialchars($data['nama_almarhum'] ?? ''); ?></title>
    
    <!-- Ikon FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Library html2pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        /* Gaya Tampilan Layar */
        body {
            background-color: #525659;
            margin: 0;
            padding: 20px 0 60px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }

        /* Bilah Menu Aksi Atas */
        .toolbar {
            position: sticky;
            top: 15px;
            z-index: 100;
            background: #ffffff;
            padding: 10px 20px;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 25px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: bold;
            padding: 8px 16px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
        }
        .btn-print { background: #1e3a8a; color: white; }
        .btn-print:hover { background: #172554; }
        .btn-pdf { background: #dc2626; color: white; }
        .btn-pdf:hover { background: #b91c1c; }
        .btn-back { background: #f3f4f6; color: #374151; }
        .btn-back:hover { background: #e5e7eb; }

        /* Halaman Kertas A4 Standar */
        .kertas-a4 {
            background-color: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 18mm 20mm 15mm 20mm;
            box-sizing: border-box;
            box-shadow: 0 6px 25px rgba(0,0,0,0.35);
            font-size: 11pt;
            line-height: 1.4;
            color: #000000;
            position: relative;
        }

        /* Garis Ganda Kop Surat */
        .garis-kop {
            border-top: 2.5px solid #000000;
            border-bottom: 1px solid #000000;
            height: 3px;
            margin-top: 8px;
            margin-bottom: 20px;
        }

        /* Tabel Data Format Rapi */
        .tabel-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }
        .tabel-data td {
            padding: 3px 0;
            vertical-align: top;
        }

        /* Titik-titik untuk Blangko */
        .garis-titik {
            display: inline-block;
            width: 100%;
            border-bottom: 1px dotted #555;
            min-height: 14px;
        }

        /* Pengaturan Cetak Media Print */
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .toolbar {
                display: none !important;
            }
            .kertas-a4 {
                box-shadow: none !important;
                width: 100% !important;
                min-height: auto !important;
                padding: 10mm 15mm 10mm 15mm !important;
            }
            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Bilah Tombol Tindakan (Tidak ikut dicetak) -->
    <div class="toolbar">
        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fa-solid fa-print"></i> Cetak / Print Dokumen
        </button>
        <button onclick="unduhPDF()" id="btn-pdf-dl" class="btn-action btn-pdf">
            <i class="fa-solid fa-file-pdf"></i> Unduh PDF
        </button>
        <a href="kematian.php" class="btn-action btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Data Kematian
        </a>
    </div>

    <!-- AREA KERTAS SURAT (A4) SESUAI BLANGKO RESMI -->
    <div id="area-cetak" class="kertas-a4">
        
        <!-- KOP SURAT (DENGAN LOGO KABUPATEN BEKASI) -->
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <!-- Logo Kabupaten Bekasi Sesuai Gambar -->
                <td style="width: 80px; vertical-align: middle; text-align: left; padding: 0;">
                    <img src="logo_bekasi.png" alt="Logo Kabupaten Bekasi" style="width: 70px; height: auto; display: block;">
                </td>
                <!-- Teks Kop Surat -->
                <td style="text-align: center; vertical-align: middle; padding: 0 10px 0 0;">
                    <div style="font-size: 13pt; font-weight: bold; letter-spacing: 0.5px; line-height: 1.2;">
                        RUKUN WARGA 09/1 PERUMAHAN GRAHA KALIMAS
                    </div>
                    <div style="font-size: 10.5pt; margin-top: 3px;">
                        Desa Setiadarma, Kecamatan Tambun Selatan, Kabupaten Bekasi
                    </div>
                    <div style="font-size: 9.5pt; margin-top: 2px;">
                        Sekretariat: Perumahan Grahakalimas Jl. Harmoni Raya, 17510
                    </div>
                </td>
            </tr>
        </table>

        <!-- GARIS PEMISAH KOP SURAT GANDA -->
        <div class="garis-kop"></div>

        <!-- JUDUL & NOMOR SURAT -->
        <div style="text-align: center; margin-bottom: 22px;">
            <div style="font-size: 13pt; font-weight: bold; text-decoration: underline; letter-spacing: 1px;">
                SURAT KETERANGAN
            </div>
            <div style="font-size: 11pt; margin-top: 4px;">
                <u>No : <?= $nomor_surat_display; ?> – SKKM – RT31/ <?= $bulan_romawi; ?> /20<?= $tahun_surat_2digit; ?></u>
            </div>
        </div>

        <!-- SALUTASI & PEMBUKA -->
        <p style="margin: 0 0 8px 0; font-size: 11pt;">Dengan hormat,</p>
        <p style="margin: 0 0 14px 0; font-size: 11pt; text-align: justify; line-height: 1.5;">
            Yang bertanda tangan di bawah ini Ketua RT 31 dan Ketua RW 09/01 Desa Setiadarma Kecamatan Tambun Selatan – Bekasi <u>Menerangkan bahwa :</u>
        </p>

        <!-- DATA IDENTITAS WARGA / ALMARHUM -->
        <table class="tabel-data" style="margin-bottom: 14px;">
            <tr>
                <td style="width: 25%;">Nama</td>
                <td style="width: 3%; text-align: center;">:</td>
                <td style="width: 72%; font-weight: <?= $is_blangko ? 'normal' : 'bold'; ?>;">
                    <?= $is_blangko ? '<span class="garis-titik"></span>' : $val_nama; ?>
                </td>
            </tr>
            <tr>
                <td>NIK</td>
                <td style="text-align: center;">:</td>
                <td>
                    <?= $is_blangko ? '<span class="garis-titik"></span>' : $val_nik; ?>
                </td>
            </tr>
            <tr>
                <td>Tempat/ Tanggal Lahir</td>
                <td style="text-align: center;">:</td>
                <td>
                    <?= $is_blangko ? '<span class="garis-titik"></span>' : $val_ttl; ?>
                </td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td>
                <td style="text-align: center;">:</td>
                <td>
                    <?= $is_blangko ? '<span class="garis-titik"></span>' : $val_jk; ?>
                </td>
            </tr>
            <tr>
                <td>Agama</td>
                <td style="text-align: center;">:</td>
                <td>
                    <?= $is_blangko ? '<span class="garis-titik"></span>' : $val_agama; ?>
                </td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td style="text-align: center;">:</td>
                <td>
                    <?= $is_blangko ? '<span class="garis-titik"></span><br><span class="garis-titik" style="margin-top: 4px;"></span>' : $val_alamat; ?>
                </td>
            </tr>
        </table>

        <!-- KETERANGAN MENINGGAL DUNIA -->
        <p style="margin: 0 0 10px 0; font-size: 11pt;">
            <u>Menerangkan bahwa nama di atas telah <strong>MENINGGAL DUNIA</strong> pada :</u>
        </p>

        <!-- DATA KEMATIAN -->
        <table class="tabel-data" style="margin-bottom: 22px;">
            <tr>
                <td style="width: 25%;">Hari/Tanggal</td>
                <td style="width: 3%; text-align: center;">:</td>
                <td style="width: 72%;">
                    <?= $is_blangko ? '<span class="garis-titik"></span>' : $val_hari_tgl_wafat; ?>
                </td>
            </tr>
            <tr>
                <td>Pukul</td>
                <td style="text-align: center;">:</td>
                <td>
                    <?= $is_blangko ? '<span class="garis-titik"></span>' : $val_pukul; ?>
                </td>
            </tr>
            <tr>
                <td>Tutup Usia</td>
                <td style="text-align: center;">:</td>
                <td>
                    <?= $is_blangko ? '<span class="garis-titik"></span>' : $val_tutup_usia; ?>
                </td>
            </tr>
            <tr>
                <td>Dikarenakan</td>
                <td style="text-align: center;">:</td>
                <td>
                    <?= $is_blangko ? '<span class="garis-titik"></span>' : $val_sebab; ?>
                </td>
            </tr>
        </table>

        <!-- PENUTUP -->
        <p style="margin: 0 0 30px 0; font-size: 11pt; text-align: justify; line-height: 1.5;">
            Demikian surat Keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
        </p>

        <!-- AREA TANDA TANGAN KETUA RT & KETUA RW -->
        <div style="margin-left: auto; width: 45%; text-align: center; font-size: 11pt; margin-bottom: 6px;">
            Bekasi, <?= $tgl_surat_display; ?>
        </div>
        <div style="width: 100%; text-align: center; font-size: 11pt; margin-bottom: 12px;">
            Mengetahui,
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 11pt; margin-bottom: 35px;">
            <tr>
                <!-- TTD Ketua RT 31 -->
                <td style="width: 50%; text-align: center; vertical-align: top; padding: 0 10px;">
                    Ketua RT 31,
                    <br><br><br><br><br>
                    <div style="display: inline-block; width: 200px; border-bottom: 1px solid #000000; padding-bottom: 2px;">
                        <strong>Hermanto</strong>
                    </div>
                </td>
                <!-- TTD Ketua RW 09/01 -->
                <td style="width: 50%; text-align: center; vertical-align: top; padding: 0 10px;">
                    Ketua RW 09/01
                    <br><br><br><br><br>
                    <div style="display: inline-block; width: 200px; border-bottom: 1px solid #000000; padding-bottom: 2px;">
                        <strong>Joni Irawan</strong>
                    </div>
                </td>
            </tr>
        </table>

        <!-- TEMBUSAN -->
        <div style="font-size: 10pt; line-height: 1.6;">
            <u>Tembusan :</u><br>
            1. ___________________<br>
            2. ___________________<br>
            3. <u>Arsip</u>
        </div>

    </div>

    <!-- Script Download PDF Otomatis -->
    <script>
        function unduhPDF() {
            var element = document.getElementById('area-cetak');
            var btn = document.getElementById('btn-pdf-dl');
            var oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengunduh...';
            btn.disabled = true;

            var opt = {
                margin:       [0, 0, 0, 0],
                filename:     '<?= $pdf_filename; ?>',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(function() {
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Selesai!';
                setTimeout(function() {
                    btn.innerHTML = oldHtml;
                    btn.disabled = false;
                }, 2000);
            });
        }
    </script>
</body>
</html>