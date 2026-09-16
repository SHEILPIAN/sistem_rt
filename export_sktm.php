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
    $query = mysqli_query($conn, "SELECT * FROM surat WHERE id = '$id'");
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
    $val_tgl_meta        = "........................................................";
    $nomor_surat_display = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    $bulan_romawi        = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    $tahun_surat_2digit  = date('y');

    $val_nama            = "...................................................................................................................";
    $val_nik             = "...................................................................................................................";
    $val_ttl             = "...................................................................................................................";
    $val_jk              = "...................................................................................................................";
    $val_pekerjaan       = "...................................................................................................................";
    $val_agama           = "...................................................................................................................";
    $val_status_kawin    = "...................................................................................................................";
    
    // Alamat titik-titik persis blangko
    $val_alamat_baris    = "Perum Graha Kalimas Jalan .......................................... Blok. .............. No. ........ RT.31/RW.009, Desa Setiadarma, Kec Tambun Selatan, Kabupaten Bekasi";

    $val_tgl_ttd         = "......................................... 20" . date('y');
    $pdf_filename        = "Blangko_Surat_Keterangan_Tidak_Mampu_RT31.pdf";
} else {
    $tgl_req = $data['tanggal_request'] ?? date('Y-m-d');
    $bln_req = date('n', strtotime($tgl_req));
    
    // Tanggal Meta (bawah KOP)
    $val_tgl_meta = format_indo($tgl_req);

    // Nomor Surat
    if (!empty($data['nomor_surat'])) {
        $nomor_surat_display = htmlspecialchars($data['nomor_surat']);
    } else {
        $nomor_surat_display = sprintf('%03d', (int)$data['id']);
    }

    $bulan_romawi       = bulan_ke_romawi($bln_req);
    $tahun_surat_2digit = date('y', strtotime($tgl_req));

    $val_nama = !empty($data['nama_pemohon']) ? htmlspecialchars($data['nama_pemohon']) : '-';
    $val_nik  = !empty($data['nik_pemohon']) ? htmlspecialchars($data['nik_pemohon']) : '-';

    // Tempat & Tanggal Lahir
    $ttl_arr = [];
    if (!empty($data['tempat_lahir'])) $ttl_arr[] = htmlspecialchars($data['tempat_lahir']);
    if (!empty($data['tanggal_lahir'])) $ttl_arr[] = format_indo($data['tanggal_lahir']);
    $val_ttl = !empty($ttl_arr) ? implode(', ', $ttl_arr) : '-';

    // Jenis Kelamin
    $jk_raw = strtoupper(trim($data['jenis_kelamin'] ?? ''));
    $val_jk = ($jk_raw === 'L' || $jk_raw === 'LAKI-LAKI') ? 'Laki-Laki' : (($jk_raw === 'P' || $jk_raw === 'PEREMPUAN') ? 'Perempuan' : (!empty($data['jenis_kelamin']) ? htmlspecialchars($data['jenis_kelamin']) : '-'));

    $val_pekerjaan    = !empty($data['pekerjaan']) ? htmlspecialchars($data['pekerjaan']) : '-';
    $val_agama        = !empty($data['agama']) ? htmlspecialchars($data['agama']) : '-';
    $val_status_kawin = !empty($data['status_perkawinan']) ? htmlspecialchars($data['status_perkawinan']) : '-';

    // Alamat
    $jalan_str = !empty($data['jalan']) ? htmlspecialchars($data['jalan']) : 'Harmoni Raya';
    $blok_str  = !empty($data['blok']) ? htmlspecialchars($data['blok']) : '-';
    $no_str    = !empty($data['no_rumah']) ? htmlspecialchars($data['no_rumah']) : '-';

    if (!empty($data['alamat']) && empty($data['jalan']) && empty($data['blok'])) {
        $val_alamat_baris = htmlspecialchars($data['alamat']);
    } else {
        $val_alamat_baris = "Perum Graha Kalimas Jalan " . $jalan_str . " Blok. " . $blok_str . " No. " . $no_str . " RT.31/RW.009, Desa Setiadarma, Kec Tambun Selatan, Kabupaten Bekasi";
    }

    $val_tgl_ttd  = format_indo($tgl_req);
    $pdf_filename = 'Surat_Keterangan_Tidak_Mampu_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['nama_pemohon']) . '.pdf';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_blangko ? 'Blangko Surat Keterangan Tidak Mampu' : 'Surat Keterangan Tidak Mampu - ' . htmlspecialchars($data['nama_pemohon'] ?? ''); ?></title>
    
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
            padding: 9px 18px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-back {
            background-color: #f1f5f9;
            color: #475569;
        }
        .btn-back:hover {
            background-color: #e2e8f0;
            color: #1e293b;
        }

        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }

        .btn-pdf {
            background-color: #dc2626;
            color: #ffffff;
        }
        .btn-pdf:hover {
            background-color: #b91c1c;
        }

        /* Lembar Kertas Dokumen Fisik A4 */
        .page-container {
            width: 210mm;
            min-height: 297mm;
            background: #ffffff;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            padding: 18mm 20mm 15mm 20mm;
            box-sizing: border-box;
            position: relative;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            font-size: 11pt;
            line-height: 1.45;
        }

        /* KOP Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .kop-table td {
            vertical-align: middle;
            padding: 0;
        }
        .kop-logo {
            width: 95px;
            text-align: left;
        }
        .kop-logo img {
            width: 82px;
            height: auto;
            display: block;
        }
        .kop-text {
            text-align: left;
            padding-left: 10px;
        }
        .kop-text h2 {
            margin: 0;
            font-size: 14pt;
            font-weight: 800;
            color: #2b354f;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-family: Arial, Helvetica, sans-serif;
        }
        .kop-text p {
            margin: 2px 0 0 0;
            font-size: 10pt;
            color: #374151;
            line-height: 1.35;
        }

        /* Garis Ganda Pembatas Kop */
        .kop-divider {
            margin-top: 10px;
            margin-bottom: 16px;
            border: none;
            border-top: 2.5px solid #1f2937;
            border-bottom: 1px solid #1f2937;
            height: 4px;
        }

        /* Meta Surat Kiri (Tanggal, Lampiran, Perihal) */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            margin-bottom: 18px;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        /* Judul Surat */
        .judul-area {
            text-align: center;
            margin: 10px 0 20px 0;
        }
        .judul-teks {
            font-size: 13.5pt;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 0.5px;
            color: #000;
        }
        .nomor-surat {
            font-size: 10.5pt;
            margin-top: 4px;
            color: #000;
        }

        /* Data Tabel Warga */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 10.5pt;
        }
        .data-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .data-table td.label-col {
            width: 25%;
            white-space: nowrap;
        }
        .data-table td.sep-col {
            width: 3%;
            text-align: center;
        }
        .data-table td.val-col {
            width: 72%;
            line-height: 1.4;
        }

        /* Paragraf Pernyataan */
        .paragraf {
            text-align: justify;
            text-indent: 0;
            line-height: 1.5;
            margin: 12px 0;
            font-size: 10.5pt;
        }

        /* Tanda Tangan */
        .ttd-wrapper {
            width: 100%;
            margin-top: 25px;
        }
        .ttd-meta-kanan {
            text-align: right;
            padding-right: 40px;
            font-size: 10.5pt;
            margin-bottom: 8px;
        }
        .ttd-mengetahui {
            text-align: center;
            font-size: 10.5pt;
            margin-bottom: 12px;
        }
        .ttd-grid {
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            box-sizing: border-box;
            text-align: center;
        }
        .ttd-col {
            width: 42%;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 10.5pt;
        }
        .ttd-spacer {
            height: 75px;
        }
        .ttd-nama {
            font-weight: bold;
            border-top: 1.5px solid #000;
            padding-top: 4px;
            min-width: 170px;
            display: inline-block;
            text-transform: uppercase;
        }

        /* Pop-up Notifikasi Download */
        #downloading-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(17, 24, 39, 0.95);
            color: #ffffff;
            padding: 20px 32px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            z-index: 1000;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
        }

        /* Pengaturan Khusus Saat Dicetak (Print) */
        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
            }
            .toolbar, #downloading-modal {
                display: none !important;
            }
            .page-container {
                box-shadow: none;
                padding: 15mm 18mm 12mm 18mm;
                margin: 0 auto;
                width: 100%;
                min-height: auto;
            }
        }
    </style>
</head>
<body>

    <!-- Bilah Aksi Atas -->
    <div class="toolbar">
        <a href="surat.php" class="btn-action btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Layanan Surat
        </a>
        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fa-solid fa-print"></i> Cetak / Print Dokumen
        </button>
        <button onclick="unduhPDF()" class="btn-action btn-pdf">
            <i class="fa-solid fa-file-pdf"></i> Unduh PDF
        </button>
    </div>

    <!-- Modal Status Unduh -->
    <div id="downloading-modal">
        <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2"></i><br>
        Sedang membuat dokumen PDF...
    </div>

    <!-- Lembar Kertas Dokumen SKTM (A4) -->
    <div id="surat-dokumen" class="page-container">
        
        <!-- KOP SURAT -->
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    <img src="logo_bekasi.png" alt="Logo Kabupaten Bekasi" onerror="this.onerror=null; this.src='image.jpeg';">
                </td>
                <td class="kop-text">
                    <h2>RUKUN WARGA 09/1 PERUMAHAN GRAHA KALIMAS</h2>
                    <p>Desa Setiadarma, Kecamatan Tambun Selatan, Kabupaten Bekasi</p>
                    <p>Sekretariat: Perumahan Grahakalimas Jl. Harmoni Raya, 17510</p>
                </td>
            </tr>
        </table>

        <!-- Garis Pembatas Ganda -->
        <div class="kop-divider"></div>

        <!-- META SURAT KIRI -->
        <table class="meta-table">
            <tr>
                <td style="width: 14%;">Tanggal</td>
                <td style="width: 3%;">:</td>
                <td style="width: 83%;"><?= $val_tgl_meta; ?></td>
            </tr>
            <tr>
                <td>Lampiran</td>
                <td>:</td>
                <td>-</td>
            </tr>
            <tr>
                <td>Perihal</td>
                <td>:</td>
                <td><b>Surat Keterangan Tidak Mampu</b></td>
            </tr>
        </table>

        <!-- JUDUL SURAT -->
        <div class="judul-area">
            <div class="judul-teks">SURAT Keterangan Tidak Mampu</div>
            <div class="nomor-surat">
                No: &nbsp;<?= $nomor_surat_display; ?> &ndash; SKTM &ndash; RT31/<?= $bulan_romawi; ?>/20<?= $tahun_surat_2digit; ?>
            </div>
        </div>

        <!-- PEMBUKA -->
        <div style="font-size: 10.5pt; margin-bottom: 6px;">Dengan hormat,</div>
        <p class="paragraf" style="margin-top: 4px;">
            Yang bertanda tangan di bawah ini Ketua RT 31 dan Ketua RW 09/01 Desa Setiadarma Tambun Selatan &ndash; Bekasi Menerangkan bahwa:
        </p>

        <!-- DATA WARGA / PEMOHON -->
        <table class="data-table">
            <tr>
                <td class="label-col">Nama</td>
                <td class="sep-col">:</td>
                <td class="val-col" style="font-weight: bold;"><?= $val_nama; ?></td>
            </tr>
            <tr>
                <td class="label-col">No. KTP</td>
                <td class="sep-col">:</td>
                <td class="val-col"><?= $val_nik; ?></td>
            </tr>
            <tr>
                <td class="label-col">Tempat/ Tanggal Lahir</td>
                <td class="sep-col">:</td>
                <td class="val-col"><?= $val_ttl; ?></td>
            </tr>
            <tr>
                <td class="label-col">Jenis Kelamin</td>
                <td class="sep-col">:</td>
                <td class="val-col"><?= $val_jk; ?></td>
            </tr>
            <tr>
                <td class="label-col">Pekerjaan</td>
                <td class="sep-col">:</td>
                <td class="val-col"><?= $val_pekerjaan; ?></td>
            </tr>
            <tr>
                <td class="label-col">Agama</td>
                <td class="sep-col">:</td>
                <td class="val-col"><?= $val_agama; ?></td>
            </tr>
            <tr>
                <td class="label-col">Status Perkawinan</td>
                <td class="sep-col">:</td>
                <td class="val-col"><?= $val_status_kawin; ?></td>
            </tr>
            <tr>
                <td class="label-col">Alamat</td>
                <td class="sep-col">:</td>
                <td class="val-col"><?= $val_alamat_baris; ?></td>
            </tr>
        </table>

        <!-- PARAGRAF PERNYATAAN KURANG MAMPU -->
        <p class="paragraf">
            Orang tersebut diatas, adalah benar-benar warga RT.31/RW.009, Desa Setiadarma dan bertempat tinggal di alamat yang telah disebutkan. Berdasarkan data dan fakta yang dapat disimpulkan, bahwa orang yang bersangkutan termasuk dalam golongan warga yang kurang mampu.
        </p>

        <p class="paragraf">
            Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya. Atas perhatiannya saya ucapkan terima kasih.
        </p>

        <!-- TANDA TANGAN GANDA (DUAL SIGNATURES) -->
        <div class="ttd-wrapper">
            <div class="ttd-meta-kanan">
                Bekasi, <?= $val_tgl_ttd; ?>
            </div>

            <div class="ttd-mengetahui">
                Mengetahui,
            </div>

            <div class="ttd-grid">
                <!-- Ketua RT 31 -->
                <div class="ttd-col">
                    <div>Ketua RT 31,</div>
                    <div class="ttd-spacer"></div>
                    <div class="ttd-nama">HERMANTO</div>
                </div>

                <!-- Ketua RW 09/01 -->
                <div class="ttd-col">
                    <div>Ketua RW 09/01</div>
                    <div class="ttd-spacer"></div>
                    <div class="ttd-nama">JONI IRAWAN</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Script Unduh PDF Menggunakan html2pdf.js -->
    <script>
        function unduhPDF() {
            var modal = document.getElementById('downloading-modal');
            modal.style.display = 'block';

            var element = document.getElementById('surat-dokumen');
            
            var opt = {
                margin:       [0, 0, 0, 0],
                filename:     '<?= $pdf_filename; ?>',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2.5, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(function() {
                modal.style.display = 'none';
            }).catch(function(err) {
                console.error(err);
                modal.style.display = 'none';
                alert('Terjadi kesalahan saat mengunduh PDF.');
            });
        }
    </script>
</body>
</html>
