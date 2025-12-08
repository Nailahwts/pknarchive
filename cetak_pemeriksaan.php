<?php
require_once 'koneksi.php';
cek_login();

// Ambil parameter
$filter_bulan = $_POST['bulan'] ?? date('Y-m');
$filter_peserta = isset($_POST['peserta']) ? intval($_POST['peserta']) : 0;
$kepala_unit = $_POST['kepala_unit'] ?? 'KEPALA UNIT';
$nama_kepala = $_POST['nama_kepala'] ?? '(...............................)';
$tim_medis = $_POST['tim_medis'] ?? 'TIM MEDIS';
$nama_dokter = $_POST['nama_dokter'] ?? '(...............................)';

// Ambil tanggal terbaru dari bulan yang dipilih
$date_query = "SELECT MAX(tanggal_periksa) as latest_date 
               FROM pemeriksaan 
               WHERE DATE_FORMAT(tanggal_periksa, '%Y-%m') = ?";
$stmt = $koneksi->prepare($date_query);
$stmt->bind_param("s", $filter_bulan);
$stmt->execute();
$date_result = $stmt->get_result()->fetch_assoc();
$latest_date = $date_result['latest_date'] ?? date('Y-m-d');
$stmt->close();

// Format tanggal Indonesia
$bulan_indo = [
    1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
    5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
    9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
];
$date_parts = explode('-', $latest_date);
$tanggal_formatted = $date_parts[2] . ' ' . $bulan_indo[(int)$date_parts[1]] . ' ' . $date_parts[0];

// Query data pemeriksaan
$query = "SELECT 
    pm.*,
    ps.nama,
    ps.gender,
    ps.bidang,
    ps.tgl_lahir
FROM pemeriksaan pm
INNER JOIN peserta ps ON pm.id_peserta = ps.id_peserta
WHERE DATE_FORMAT(pm.tanggal_periksa, '%Y-%m') = ?";

$params = [$filter_bulan];
$types = 's';

if ($filter_peserta > 0) {
    $query .= " AND pm.id_peserta = ?";
    $params[] = $filter_peserta;
    $types .= 'i';
}

$query .= " ORDER BY pm.tanggal_periksa ASC, ps.nama ASC";

$stmt = $koneksi->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Pemeriksaan Kesehatan - <?= htmlspecialchars($tanggal_formatted) ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        
        .container {
            width: 100%;
            max-width: 280mm;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            align-items: center;
            gap: -20px;
            margin-bottom: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            padding-left: 30px;
        }

        .header-logo img {
            width: 100px;
            height: auto;
        }

        .header-content {
            flex: 1;
            text-align: center;
        }

        .header-content h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .header-content h2 {
            margin: 5px 0;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 9pt;
        }
        
        th {
            background-color: #f5f5f5;
            color: black;
            font-weight: bold;
        }
        
        td.text-left {
            text-align: left;
            padding-left: 8px;
        }
        
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature {
            width: 45%;
            text-align: center;
        }
        
        .signature .title {
            font-weight: bold;
            margin-bottom: 60px;
        }
        
        .signature .name {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            min-width: 200px;
        }
        
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            @page {
                margin: 10mm;
            }
            
            button, .no-print {
                display: none !important;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .print-button:hover {
            background-color: #0056b3;
        }
        
        .form-settings {
            position: fixed;
            top: 70px;
            right: 20px;
            background: white;
            padding: 15px;
            border: 2px solid #007bff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 999;
            max-width: 300px;
        }
        
        .form-settings h4 {
            margin-bottom: 10px;
            color: #007bff;
        }
        
        .form-settings input {
            width: 100%;
            padding: 5px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .form-settings button {
            width: 100%;
            padding: 8px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .form-settings button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Cetak / Save PDF</button>
    
    <div class="form-settings no-print">
        <h4><i>⚙️</i> Pengaturan Tanda Tangan</h4>
        <form method="POST" action="">
            <input type="hidden" name="bulan" value="<?= htmlspecialchars($filter_bulan) ?>">
            <input type="hidden" name="peserta" value="<?= htmlspecialchars($filter_peserta) ?>">
            
            <label style="font-size: 11px; font-weight: bold;">Jabatan Kiri:</label>
            <input type="text" name="kepala_unit" placeholder="Contoh: KEPALA UNIT" 
                   value="<?= htmlspecialchars($kepala_unit) ?>">
            
            <label style="font-size: 11px; font-weight: bold;">Nama Kiri:</label>
            <input type="text" name="nama_kepala" placeholder="Nama Kepala Unit" 
                   value="<?= htmlspecialchars($nama_kepala) ?>">
            
            <label style="font-size: 11px; font-weight: bold;">Jabatan Kanan:</label>
            <input type="text" name="tim_medis" placeholder="Contoh: TIM MEDIS" 
                   value="<?= htmlspecialchars($tim_medis) ?>">
            
            <label style="font-size: 11px; font-weight: bold;">Nama Kanan:</label>
            <input type="text" name="nama_dokter" placeholder="Nama Dokter" 
                   value="<?= htmlspecialchars($nama_dokter) ?>">
            
            <button type="submit">✓ Update</button>
        </form>
    </div>
    
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-logo">
                <img src="assets/images/logopkn.jpg" alt="Logo PKN">
            </div>

            <div class="header-content">
                <h1>Daftar Hadir Konsultasi & Pemeriksaan Kesehatan Dokter Perusahaan PT PKN</h1>
                <h2>Tanggal : <?= htmlspecialchars($tanggal_formatted) ?></h2>
            </div>
        </div>
        
        <!-- TABEL DATA -->
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">NO</th>
                    <th style="width: 22%;">NAMA</th>
                    <th style="width: 18%;">BIDANG</th>
                    <th style="width: 12%;">TEKANAN<br>DARAH</th>
                    <th style="width: 10%;">ASAM<br>URAT</th>
                    <th style="width: 8%;">DM</th>
                    <th style="width: 12%;">KOLESTEROL</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= $no ?></td>
                    <td class="text-left"><?= strtoupper(htmlspecialchars($row['nama'])) ?></td>
                    <td class="text-left"><?= strtoupper(htmlspecialchars($row['bidang'])) ?></td>
                    <td><?= htmlspecialchars($row['tensi']) ?: '-' ?></td>
                    <td><?= $row['asam_urat'] ? number_format($row['asam_urat'], 1) : '-' ?></td>
                    <td><?= $row['gdp'] ? number_format($row['gdp'], 0) : '-' ?></td>
                    <td><?= $row['kolesterol'] ? number_format($row['kolesterol'], 0) : '-' ?></td>
                </tr>
                <?php 
                    $no++;
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">
                        Tidak ada data pemeriksaan pada bulan ini
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- FOOTER TANDA TANGAN -->
        <div class="footer">
            <div class="signature">
                <div class="title"><?= htmlspecialchars($kepala_unit) ?></div>
                <br>
                <div class="name"><?= htmlspecialchars($nama_kepala) ?></div>
            </div>
            
            <div class="signature">
                <div class="title"><?= htmlspecialchars($tim_medis) ?></div>
                <br>
                <div class="name"><?= htmlspecialchars($nama_dokter) ?></div>
            </div>
        </div>
    </div>
    
    <script>
        // Instruksi untuk user
        window.onload = function() {
            console.log('Untuk menyimpan sebagai PDF:');
            console.log('1. Klik tombol "Cetak / Save PDF"');
            console.log('2. Pilih "Save as PDF" atau "Microsoft Print to PDF"');
            console.log('3. Klik Save');
        };
    </script>
</body>
</html>
<?php
$stmt->close();
$koneksi->close();
?>