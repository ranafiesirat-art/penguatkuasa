<?php
// Sambungan ke database
$conn = new mysqli("localhost", "root", "", "penguatkuasa");
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// Ambil no_komputer dan bulan dari URL
$no_komputer = isset($_GET['no_komputer']) ? filter_input(INPUT_GET, 'no_komputer', FILTER_SANITIZE_STRING) : '';
$bulan_filter = isset($_GET['bulan']) ? filter_input(INPUT_GET, 'bulan', FILTER_SANITIZE_STRING) : '';

if (empty($no_komputer)) {
    echo "<script>alert('No Komputer tidak sah.'); window.location.href='./senarai_klm.php';</script>";
    exit();
}

// Ambil maklumat anggota
$sql_anggota = "SELECT a.nama, a.no_komputer, a.kad_pengenalan, a.gaji, j.nama AS jawatan, u.nama AS unit_seksyen, jab.nama_jabatan AS jabatan 
                FROM anggota a 
                LEFT JOIN jawatan j ON a.id_jawatan = j.id 
                LEFT JOIN unit_seksyen u ON a.id_unit_seksyen = u.id 
                LEFT JOIN jabatan jab ON a.id_jabatan = jab.id 
                WHERE a.no_komputer = ?";
$stmt_anggota = $conn->prepare($sql_anggota);
$stmt_anggota->bind_param("s", $no_komputer);
$stmt_anggota->execute();
$result_anggota = $stmt_anggota->get_result();

if ($result_anggota->num_rows == 0) {
    echo "<script>alert('Anggota dengan No Komputer $no_komputer tidak wujud.'); window.location.href='./senarai_klm.php';</script>";
    exit();
}

$anggota = $result_anggota->fetch_assoc();
$stmt_anggota->close();

// Tukar data kepada huruf besar
$anggota['nama'] = strtoupper($anggota['nama']);
$anggota['no_komputer'] = strtoupper($anggota['no_komputer']);
$anggota['kad_pengenalan'] = strtoupper($anggota['kad_pengenalan'] ?: 'TIADA');
$anggota['jawatan'] = strtoupper($anggota['jawatan'] ?: 'TIADA');
$anggota['unit_seksyen'] = strtoupper($anggota['unit_seksyen'] ?: 'TIADA');
$anggota['jabatan'] = strtoupper($anggota['jabatan'] ?: 'TIADA');
$anggota['gaji'] = isset($anggota['gaji']) ? floatval($anggota['gaji']) : 0.00;

// Ambil rekod kerja lebih masa
$sql_klm = "SELECT k.kategori_hari, k.tarikh, k.masa_dari, k.masa_hingga, k.jumlah_jam, k.jam_A, k.jam_B, k.jam_C, k.jam_D, k.jam_E, k.gaji_dikira, k.kenyataan_tugasan 
            FROM klm_kerja k 
            WHERE k.no_komputer = ?";
$params = [$no_komputer];
$types = "s";
if ($bulan_filter) {
    $sql_klm .= " AND DATE_FORMAT(tarikh, '%Y-%m') = ?";
    $params[] = $bulan_filter;
    $types .= "s";
}
$sql_klm .= " ORDER BY k.tarikh ASC";

$stmt_klm = $conn->prepare($sql_klm);
$stmt_klm->bind_param($types, ...$params);
$stmt_klm->execute();
$result_klm = $stmt_klm->get_result();

$rekod_klm = [];
$total_jam_sebenar = 0;
$total_jam_A = 0;
$total_jam_B = 0;
$total_jam_C = 0;
$total_jam_D = 0;
$total_jam_E = 0;
$total_gaji = 0;

while ($row = $result_klm->fetch_assoc()) {
    // Kira JAM SEBENAR
    $datetime_dari = new DateTime("{$row['tarikh']} {$row['masa_dari']}");
    $datetime_hingga = new DateTime("{$row['tarikh']} {$row['masa_hingga']}");
    if ($datetime_hingga < $datetime_dari) {
        $datetime_hingga->modify('+1 day');
    }
    $interval = $datetime_dari->diff($datetime_hingga);
    $jumlah_jam_sebenar = $interval->h + ($interval->i / 60) + ($interval->s / 3600);

    $row['jumlah_jam_sebenar'] = $jumlah_jam_sebenar;
    $row['kategori_hari'] = strtoupper($row['kategori_hari']);
    $row['kenyataan_tugasan'] = strtoupper($row['kenyataan_tugasan']);
    $rekod_klm[] = $row;

    // Tambah jumlah untuk setiap kategori
    $total_jam_sebenar += $jumlah_jam_sebenar;
    $total_jam_A += $row['jam_A'];
    $total_jam_B += $row['jam_B'];
    $total_jam_C += $row['jam_C'];
    $total_jam_D += $row['jam_D'];
    $total_jam_E += $row['jam_E'];
    $total_gaji += $row['gaji_dikira'];
}
$stmt_klm->close();
$conn->close();

// Format bulan untuk paparan
$bulan_tahun = $bulan_filter ? strtoupper(date('F Y', strtotime($bulan_filter . "-01"))) : '';

// Kira nilai Y untuk kategori A: Y = (gaji x 12 x jumlah jam A x 1.125) / 2504
$kadar_A = 1.125;
$nilai_Y_A = ($anggota['gaji'] * 12 * $total_jam_A * $kadar_A) / 2504;

// Kira nilai Y untuk kategori B: Y = (gaji x 12 x jumlah jam B x 1.25) / 2504
$kadar_B = 1.25;
$nilai_Y_B = ($anggota['gaji'] * 12 * $total_jam_B * $kadar_B) / 2504;

// Kira nilai Y untuk kategori C: Y = (gaji x 12 x jumlah jam C x 1.5) / 2504
$kadar_C = 1.5;
$nilai_Y_C = ($anggota['gaji'] * 12 * $total_jam_C * $kadar_C) / 2504;

// Kira nilai Y untuk kategori D: Y = (gaji x 12 x jumlah jam D x 1.75) / 2504
$kadar_D = 1.75;
$nilai_Y_D = ($anggota['gaji'] * 12 * $total_jam_D * $kadar_D) / 2504;

// Kira nilai Y untuk kategori E: Y = (gaji x 12 x jumlah jam E x 2.0) / 2504
$kadar_E = 2.0;
$nilai_Y_E = ($anggota['gaji'] * 12 * $total_jam_E * $kadar_E) / 2504;

// Kira jumlah keseluruhan kategori A, B, C, D, E
$total_kategori = $nilai_Y_A + $nilai_Y_B + $nilai_Y_C + $nilai_Y_D + $nilai_Y_E;

// Format jumlah jam A, B, C, D, dan E tanpa trailing zero (contoh: 4.50 jadi 4.5)
$total_jam_A_formatted = rtrim(number_format($total_jam_A, 2), '0');
$total_jam_B_formatted = rtrim(number_format($total_jam_B, 2), '0');
$total_jam_C_formatted = rtrim(number_format($total_jam_C, 2), '0');
$total_jam_D_formatted = rtrim(number_format($total_jam_D, 2), '0');
$total_jam_E_formatted = rtrim(number_format($total_jam_E, 2), '0');
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BORANG KLM - MAKLUMAT ANGGOTA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            margin: 0;
            padding: 10px;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .borang-container {
            width: 100%;
            max-width: 200mm; /* Disesuaikan untuk A4 dengan margin 5mm */
            background-color: white;
            padding: 10px;
            border: 2px solid #000;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .logo-header {
            text-align: center;
            margin-bottom: 5px;
        }
        .logo-placeholder {
            width: 80px;
            height: 80px;
            border: 1px dashed #000;
            display: inline-block;
            line-height: 80px;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
        .borang-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .borang-header h1 {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .borang-header p {
            font-size: 8pt;
            margin: 2px 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        .info-section {
            margin-bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .info-column {
            width: 48%;
            margin-bottom: 5px;
        }
        .info-row {
            display: flex;
            margin-bottom: 2px;
            font-size: 8pt;
            align-items: center;
        }
        .info-label {
            width: 130px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            padding-right: 5px;
        }
        .info-colon {
            display: inline-block;
            width: 5px;
            text-align: left;
            margin-left: -2px; /* Jarak untuk NO KAD PENGENALAN, NO KOMPUTER, GAJI POKOK */
        }
        .info-colon-left {
            display: inline-block;
            width: 5px;
            text-align: left;
            margin-left: -21px; /* Jarak untuk NAMA, JAWATAN, JABATAN seperti yang tuan ubah */
        }
        .info-value {
            flex: 1;
            text-transform: uppercase;
            border-bottom: 1px solid #000000;
            padding-bottom: 1px;
            display: inline-block;
            width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 5px; /* Anjak teks ke kanan untuk semua medan */
        }
        .table-section {
            margin-bottom: 10px;
        }
        .table-section table {
            width: 100%;
            max-width: 200mm;
            border-collapse: collapse;
            font-size: 6pt; /* Saiz fon seragam untuk semua sel jadual kecuali KENYATAAN */
            text-transform: uppercase;
            table-layout: fixed;
        }
        .table-section th, .table-section td {
            border: 1px solid #000; /* Anggaran 0.264mm */
            padding: 1mm; /* Padding untuk semua lajur */
            text-align: center;
            min-height: 5mm; /* Ketinggian minimum untuk semua sel */
            box-sizing: border-box;
        }
        .table-section th {
            background-color: #d3d3d3;
            font-weight: bold;
            font-size: 7pt; /* Saiz fon untuk header */
        }
        /* Lajur KENYATAAN */
        .lajur-kenyataan {
            width: 41.5% !important; /* 41.5% daripada 177.1mm ≈ 73.50mm */
            text-align: left !important; /* Selaraskan teks ke kiri untuk rupa lebih kemas */
            font-size: 4.5pt !important; /* Saiz fon khusus untuk lajur KENYATAAN */
            white-space: normal !important; /* Benarkan teks berbalik ke baris berikutnya */
            word-wrap: break-word; /* Pastikan perkataan panjang terbelah jika perlu */
        }
        /* Lajur TARIKH */
        .lajur-tarikh {
            width: 10% !important; /* 10% daripada 177.1mm ≈ 17.71mm */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Lajur MASA BERTUGAS MULA */
        .lajur-masa-mula {
            width: 18% !important; /* 18% daripada 177.1mm ≈ 31.88mm */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Lajur MASA BERTUGAS TAMAT */
        .lajur-masa-tamat {
            width: 18% !important; /* 18% daripada 177.1mm ≈ 31.88mm */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Lajur JAM */
        .lajur-jam {
            width: 5% !important; /* 5% daripada 177.1mm ≈ 8.86mm */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Lajur A, B, C, D, E */
        .lajur-a, .lajur-b, .lajur-c, .lajur-d, .lajur-e {
            width: 1.5% !important; /* 1.5% daripada 177.1mm ≈ 2.66mm */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Saiz fon untuk JUMLAH */
        .table-section tfoot td {
            font-size: 7pt; /* Saiz fon untuk teks JUMLAH */
            font-weight: bold;
        }
        /* Pastikan lajur KENYATAAN dalam tfoot menggunakan saiz fon 4.5pt */
        .table-section tfoot .lajur-kenyataan {
            font-size: 4.5pt !important;
        }
        .additional-calculation {
            margin-top: 10px;
            font-size: 8pt;
            text-transform: uppercase;
            text-align: left;
            margin-left: 10px;
        }
        .additional-calculation .calc-row {
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .additional-calculation .calc-row-malam {
            margin-bottom: 0;
            position: relative;
        }
        .additional-calculation .calc-row-total {
            margin-bottom: 10px;
            position: relative;
            display: flex;
            align-items: center;
        }
        .additional-calculation p {
            margin: 0;
            line-height: 1.5;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .additional-calculation .label-a, 
        .additional-calculation .label-b, 
        .additional-calculation .label-c, 
        .additional-calculation .label-d, 
        .additional-calculation .label-e,
        .additional-calculation .label-total {
            display: inline-block;
            width: 20px;
            text-align: left;
        }
        .additional-calculation .label-text {
            display: inline-block;
            width: 150px;
        }
        .additional-calculation .label-text-total {
            display: inline-block;
            width: 150px;
            font-weight: bold;
        }
        .additional-calculation .label-text-a::after,
        .additional-calculation .label-text-b::after,
        .additional-calculation .label-text-c::after,
        .additional-calculation .label-text-d::after,
        .additional-calculation .label-text-e::after {
            content: ":";
            margin-left: 2px;
        }
        .additional-calculation .label-text-b,
        .additional-calculation .label-text-c {
            display: inline-block;
            width: 150px;
            position: relative;
        }
        .additional-calculation .label-text-d,
        .additional-calculation .label-text-e {
            display: inline-block;
            width: 150px;
        }
        .additional-calculation .label-text-malam {
            display: inline-block;
            width: 150px;
            margin-left: 0;
            position: absolute;
            top: 14px;
            left: 0;
            line-height: 1.5;
        }
        .additional-calculation .formula-container {
            display: inline-block;
            width: 280px;
            position: relative;
            vertical-align: middle;
        }
        .additional-calculation .formula {
            display: inline-flex;
            align-items: center;
            width: 100%;
            justify-content: center;
            padding-bottom: 2px;
            border-bottom: 1px solid #000;
            font-size: 7pt; /* Saiz fon untuk formula */
        }
        .additional-calculation .formula span {
            margin: 0 3px;
        }
        .additional-calculation .gaji-below {
            position: absolute;
            left: 0;
            right: 0;
            text-align: center;
            top: 100%;
            margin-top: 6px;
            line-height: 1.5;
            font-size: 7pt; /* Saiz fon untuk gaji-below */
        }
        .additional-calculation .result {
            display: inline-block;
            margin-left: 15px;
        }
        .additional-calculation .total-formula-container {
            display: inline-block;
            width: 280px; /* Selaraskan dengan lebar .formula-container */
            position: relative;
            vertical-align: middle;
        }
        .additional-calculation .total-formula {
            display: inline-flex;
            align-items: center;
            width: 100%;
            justify-content: center;
            font-size: 7pt; /* Saiz fon sama seperti formula */
        }
        .additional-calculation .total-result {
            display: inline-block;
            margin-left: 15px; /* Selaraskan dengan jarak .result */
        }
        .additional-calculation .label-formula-container {
            display: flex;
            flex-direction: column;
            position: relative;
        }
        /* Gaya untuk butang CETAK */
        .print-button-container {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .print-button {
            padding: 8px 20px;
            font-size: 10pt;
            font-weight: bold;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-transform: uppercase;
        }
        .print-button:hover {
            background-color: #0056b3;
        }
        /* CSS untuk mengawal cetakan */
        @media print {
            @page {
                size: A4 portrait;
                margin: 5mm;
            }
            body * {
                visibility: hidden;
            }
            .borang-container, .borang-container * {
                visibility: visible;
            }
            .borang-container {
                position: absolute;
                left: 5mm;
                top: 5mm;
                width: 100%;
                max-width: 200mm;
            }
            .table-section table {
                max-width: 200mm;
            }
            .print-button-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="borang-container">
        <!-- Logo -->
        <div class="logo-header">
            <div class="logo-placeholder">RUANG LOGO</div>
        </div>

        <!-- Header Borang -->
        <div class="borang-header">
            <h1>JABATAN PENGUATKUASAAN</h1>
            <p>MAJLIS BANDARAYA JOHOR BAHRU</p>
            <p>TUNTUTAN KERJA LEBIH MASA BAGI BULAN <?php echo htmlspecialchars($bulan_tahun); ?></p>
        </div>

        <!-- Maklumat Anggota -->
        <div class="info-section">
            <div class="info-column">
                <div class="info-row">
                    <div class="info-label">NAMA</div>
                    <div class="info-colon-left">:</div>
                    <div class="info-value"><?php echo htmlspecialchars($anggota['nama']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">JAWATAN</div>
                    <div class="info-colon-left">:</div>
                    <div class="info-value"><?php echo htmlspecialchars($anggota['jawatan']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">JABATAN</div>
                    <div class="info-colon-left">:</div>
                    <div class="info-value"><?php echo htmlspecialchars($anggota['jabatan']); ?></div>
                </div>
            </div>
            <div class="info-column">
                <div class="info-row">
                    <div class="info-label">NO KAD PENGENALAN</div>
                    <div class="info-colon">:</div>
                    <div class="info-value"><?php echo htmlspecialchars($anggota['kad_pengenalan']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">NO KOMPUTER</div>
                    <div class="info-colon">:</div>
                    <div class="info-value"><?php echo htmlspecialchars($anggota['no_komputer']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">GAJI POKOK</div>
                    <div class="info-colon">:</div>
                    <div class="info-value"><?php echo number_format($anggota['gaji'], 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Jadual KLM -->
        <div class="table-section">
            <?php
            // Hadkan 12 baris per halaman
            $rows_per_page = 12;
            $pages = array_chunk($rekod_klm, $rows_per_page);

            foreach ($pages as $page_index => $page_rows) {
                // Kira semula jumlah untuk setiap halaman
                $page_total_jam_sebenar = 0;
                $page_total_jam_A = 0;
                $page_total_jam_B = 0;
                $page_total_jam_C = 0;
                $page_total_jam_D = 0;
                $page_total_jam_E = 0;

                foreach ($page_rows as $row) {
                    $page_total_jam_sebenar += $row['jumlah_jam_sebenar'];
                    $page_total_jam_A += $row['jam_A'];
                    $page_total_jam_B += $row['jam_B'];
                    $page_total_jam_C += $row['jam_C'];
                    $page_total_jam_D += $row['jam_D'];
                    $page_total_jam_E += $row['jam_E'];
                }
            ?>
                <table class="page-table" style="<?php echo $page_index > 0 ? 'page-break-before: always;' : ''; ?>">
                    <thead>
                        <tr>
                            <th rowspan="2" class="lajur-tarikh">TARIKH</th>
                            <th colspan="2">MASA BERTUGAS</th>
                            <th rowspan="2" class="lajur-jam">JAM</th>
                            <th colspan="5">JUMLAH K.L.M</th>
                            <th rowspan="2" class="lajur-kenyataan">KENYATAAN</th>
                        </tr>
                        <tr>
                            <th class="lajur-masa-mula">MULA</th>
                            <th class="lajur-masa-tamat">TAMAT</th>
                            <th class="lajur-a">A</th>
                            <th class="lajur-b">B</th>
                            <th class="lajur-c">C</th>
                            <th class="lajur-d">D</th>
                            <th class="lajur-e">E</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Paparkan 12 baris atau kurang
                        $row_count = count($page_rows);
                        for ($i = 0; $i < 12; $i++) {
                            if ($i < $row_count) {
                                $row = $page_rows[$i];
                                $tarikh = DateTime::createFromFormat('Y-m-d', $row['tarikh']);
                                $tarikh_format = $tarikh ? $tarikh->format('d/m/Y') : $row['tarikh'];
                                $masa_dari = DateTime::createFromFormat('H:i:s', $row['masa_dari']);
                                $masa_dari_format = $masa_dari ? $masa_dari->format('h:i A') : $row['masa_dari'];
                                $masa_hingga = DateTime::createFromFormat('H:i:s', $row['masa_hingga']);
                                $masa_hingga_format = $masa_hingga ? $masa_hingga->format('h:i A') : $row['masa_hingga'];
                        ?>
                                <tr>
                                    <td class="lajur-tarikh"><?php echo htmlspecialchars($tarikh_format); ?></td>
                                    <td class="lajur-masa-mula"><?php echo htmlspecialchars($masa_dari_format); ?></td>
                                    <td class="lajur-masa-tamat"><?php echo htmlspecialchars($masa_hingga_format); ?></td>
                                    <td class="lajur-jam"><?php echo number_format($row['jumlah_jam_sebenar'], 2); ?></td>
                                    <td class="lajur-a"><?php echo ($row['jam_A'] == 0) ? '' : number_format($row['jam_A'], 2); ?></td>
                                    <td class="lajur-b"><?php echo ($row['jam_B'] == 0) ? '' : number_format($row['jam_B'], 2); ?></td>
                                    <td class="lajur-c"><?php echo ($row['jam_C'] == 0) ? '' : number_format($row['jam_C'], 2); ?></td>
                                    <td class="lajur-d"><?php echo ($row['jam_D'] == 0) ? '' : number_format($row['jam_D'], 2); ?></td>
                                    <td class="lajur-e"><?php echo ($row['jam_E'] == 0) ? '' : number_format($row['jam_E'], 2); ?></td>
                                    <td class="lajur-kenyataan"><?php echo htmlspecialchars($row['kenyataan_tugasan']); ?></td>
                                </tr>
                        <?php
                            } else {
                        ?>
                                <!-- Baris kosong untuk mencukupi 12 baris -->
                                <tr>
                                    <td class="lajur-tarikh"> </td>
                                    <td class="lajur-masa-mula"> </td>
                                    <td class="lajur-masa-tamat"> </td>
                                    <td class="lajur-jam"> </td>
                                    <td class="lajur-a"> </td>
                                    <td class="lajur-b"> </td>
                                    <td class="lajur-c"> </td>
                                    <td class="lajur-d"> </td>
                                    <td class="lajur-e"> </td>
                                    <td class="lajur-kenyataan"> </td>
                                </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="lajur-tarikh">JUMLAH</td>
                            <td class="lajur-jam"><?php echo number_format($page_total_jam_sebenar, 2); ?></td>
                            <td class="lajur-a"><?php echo ($page_total_jam_A == 0) ? '0' : number_format($page_total_jam_A, 2); ?></td>
                            <td class="lajur-b"><?php echo ($page_total_jam_B == 0) ? '0' : number_format($page_total_jam_B, 2); ?></td>
                            <td class="lajur-c"><?php echo ($page_total_jam_C == 0) ? '0' : number_format($page_total_jam_C, 2); ?></td>
                            <td class="lajur-d"><?php echo ($page_total_jam_D == 0) ? '0' : number_format($page_total_jam_D, 2); ?></td>
                            <td class="lajur-e"><?php echo ($page_total_jam_E == 0) ? '0' : number_format($page_total_jam_E, 2); ?></td>
                            <td class="lajur-kenyataan">-</td>
                        </tr>
                    </tfoot>
                </table>
            <?php
            }
            ?>
        </div>

        <!-- Pengiraan untuk Kategori A, B, C, D, dan E -->
        <div class="additional-calculation">
            <div class="calc-row">
                <p>
                    <span class="label-a">A.</span>
                    <span class="label-text label-text-a">HARI KERJA BIASA</span>
                    <span class="formula-container">
                        <span class="formula">RM <span><?php echo number_format($anggota['gaji'], 2); ?></span> x <span>12</span> x <span><?php echo $total_jam_A_formatted; ?></span> x <span>1.125</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_A, 2); ?></span>
                </p>
            </div>

            <div class="calc-row">
                <p>
                    <span class="label-b">B.</span>
                    <span class="label-formula-container">
                        <span class="label-text label-text-b">HARI KERJA AHAD</span>
                        <span class="label-text-malam">HARI BIASA (MALAM)</span>
                    </span>
                    <span class="formula-container">
                        <span class="formula">RM <span><?php echo number_format($anggota['gaji'], 2); ?></span> x <span>12</span> x <span><?php echo $total_jam_B_formatted; ?></span> x <span>1.25</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_B, 2); ?></span>
                </p>
            </div>

            <div class="calc-row">
                <p>
                    <span class="label-c">C.</span>
                    <span class="label-formula-container">
                        <span class="label-text label-text-c">KERJA MALAM</span>
                        <span class="label-text-malam">HARI AHAD (MALAM)</span>
                    </span>
                    <span class="formula-container">
                        <span class="formula">RM <span><?php echo number_format($anggota['gaji'], 2); ?></span> x <span>12</span> x <span><?php echo $total_jam_C_formatted; ?></span> x <span>1.5</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_C, 2); ?></span>
                </p>
            </div>

            <div class="calc-row">
                <p>
                    <span class="label-d">D.</span>
                    <span class="label-text label-text-d">HARI KELEPASAN</span>
                    <span class="formula-container">
                        <span class="formula">RM <span><?php echo number_format($anggota['gaji'], 2); ?></span> x <span>12</span> x <span><?php echo $total_jam_D_formatted; ?></span> x <span>1.75</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_D, 2); ?></span>
                </p>
            </div>

            <div class="calc-row">
                <p>
                    <span class="label-e">E.</span>
                    <span class="label-text label-text-e">HARI KELEPASAN (MALAM)</span>
                    <span class="formula-container">
                        <span class="formula">RM <span><?php echo number_format($anggota['gaji'], 2); ?></span> x <span>12</span> x <span><?php echo $total_jam_E_formatted; ?></span> x <span>2.0</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_E, 2); ?></span>
                </p>
            </div>

            <!-- Baris JUMLAH -->
            <div class="calc-row-total">
                <p>
                    <span class="label-total"></span>
                    <span class="label-text-total">JUMLAH</span>
                    <span class="total-formula-container">
                        <span class="total-formula">RM</span>
                    </span>
                    <span class="total-result"><?php echo number_format($total_kategori, 2); ?></span>
                </p>
            </div>
        </div>

        <!-- Butang CETAK -->
        <div class="print-button-container">
            <button class="print-button" onclick="window.print()">CETAK</button>
        </div>
    </div>
</body>
</html>