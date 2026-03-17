<?php
include_once '../session_check.php'; // Gunakan session_check.php di folder akar

// Sambungan ke database
$conn = new mysqli("localhost", "root", "", "penguatkuasa");
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// Ambil no_komputer dan bulan dari URL jika ada
$no_komputer = isset($_GET['no_komputer']) ? filter_input(INPUT_GET, 'no_komputer', FILTER_SANITIZE_STRING) : '';
$bulan_filter = isset($_GET['bulan']) ? filter_input(INPUT_GET, 'bulan', FILTER_SANITIZE_STRING) : '';

// Proses padam rekod
if (isset($_GET['padam'])) {
    $id = filter_input(INPUT_GET, 'padam', FILTER_SANITIZE_NUMBER_INT);
    $delete_sql = "DELETE FROM klm_kerja WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Simpan log aktiviti
        $log_sql = "INSERT INTO log_aktiviti (aktiviti, tarikh_masa, no_komputer) VALUES (?, NOW(), ?)";
        $log_stmt = $conn->prepare($log_sql);
        $aktiviti = "Memadam rekod KLM (ID: $id) untuk $no_komputer";
        $log_stmt->bind_param("ss", $aktiviti, $no_komputer);
        $log_stmt->execute();
        $log_stmt->close();

        echo "<script>alert('Rekod berjaya dipadam!'); window.location='./senarai_klm.php" . ($no_komputer ? "?no_komputer=" . urlencode($no_komputer) : "") . ($bulan_filter ? "&bulan=" . urlencode($bulan_filter) : "") . "';</script>";
    } else {
        echo "<script>alert('Ralat memadam rekod: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// Query untuk senarai dengan penapisan
$sql = "SELECT k.id, k.no_komputer, k.kategori_hari, k.tarikh, k.masa_dari, k.masa_hingga, k.jumlah_jam, k.jam_A, k.jam_B, k.jam_C, k.jam_D, k.jam_E, k.gaji_dikira, k.kenyataan_tugasan, 
        a.nama, a.kad_pengenalan, a.gaji, j.nama_jabatan, jaw.nama AS jawatan 
        FROM klm_kerja k 
        JOIN anggota a ON k.no_komputer = a.no_komputer 
        JOIN jabatan j ON k.id_jabatan = j.id 
        JOIN jawatan jaw ON k.id_jawatan = jaw.id";
$params = [];
$types = "";
if ($no_komputer) {
    $sql .= " WHERE k.no_komputer = ?";
    $params[] = $no_komputer;
    $types .= "s";
}
if ($bulan_filter) {
    $sql .= $no_komputer ? " AND" : " WHERE";
    $sql .= " DATE_FORMAT(tarikh, '%Y-%m') = ?";
    $params[] = $bulan_filter;
    $types .= "s";
}
$sql .= " ORDER BY k.tarikh ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Ambil maklumat pertama untuk paparan atas
$info = $result->num_rows > 0 ? $result->fetch_assoc() : null;
$result->data_seek(0); // Reset pointer untuk jadual

// Tentukan tarikh rujukan untuk pemilihan gaji
$tarikh_rujukan = $bulan_filter ? $bulan_filter . '-01' : date('Y-m-d');

// Ambil gaji berdasarkan tarikh rujukan dari sejarah_gaji
if ($no_komputer) {
    $gaji_query = $conn->prepare("SELECT gaji FROM sejarah_gaji WHERE no_komputer = ? AND tarikh_mula <= ? AND (tarikh_tamat >= ? OR tarikh_tamat IS NULL) ORDER BY tarikh_mula DESC LIMIT 1");
    $gaji_query->bind_param("sss", $no_komputer, $tarikh_rujukan, $tarikh_rujukan);
    $gaji_query->execute();
    $gaji_result = $gaji_query->get_result();
    $gaji = $gaji_result->num_rows > 0 ? $gaji_result->fetch_assoc()['gaji'] : 0;
    $gaji_query->close();

    // Fallback ke gaji dalam table anggota jika tiada rekod dalam sejarah_gaji
    if ($gaji == 0 && $info) {
        $gaji = $info['gaji'] ?? 0;
    }
} else {
    $gaji = $info['gaji'] ?? 0;
}

// Semak dan kemas kini sejarah gaji jika gaji berubah
if ($info && $no_komputer) {
    // Gaji asal dari anggota (untuk semakan kemas kini sejarah_gaji)
    $gaji_asal = $info['gaji'] ?? 0;

    // Semak gaji terkini dalam sejarah_gaji
    $gaji_sejarah_query = $conn->prepare("SELECT gaji FROM sejarah_gaji WHERE no_komputer = ? AND tarikh_tamat IS NULL ORDER BY tarikh_mula DESC LIMIT 1");
    $gaji_sejarah_query->bind_param("s", $no_komputer);
    $gaji_sejarah_query->execute();
    $gaji_sejarah_result = $gaji_sejarah_query->get_result();
    $gaji_sejarah = $gaji_sejarah_result->num_rows > 0 ? $gaji_sejarah_result->fetch_assoc()['gaji'] : null;
    $gaji_sejarah_query->close();

    if ($gaji_sejarah !== null && $gaji_asal != $gaji_sejarah) {
        // Tamatkan rekod gaji lama
        $sql_tamat_gaji = "UPDATE sejarah_gaji SET tarikh_tamat = CURDATE() WHERE no_komputer = ? AND tarikh_tamat IS NULL";
        $stmt_tamat_gaji = $conn->prepare($sql_tamat_gaji);
        $stmt_tamat_gaji->bind_param("s", $no_komputer);
        $stmt_tamat_gaji->execute();
        $stmt_tamat_gaji->close();

        // Tambah rekod gaji baru
        $sql_sejarah_gaji = "INSERT INTO sejarah_gaji (no_komputer, gaji, tarikh_mula, tarikh_tamat) VALUES (?, ?, CURDATE(), NULL)";
        $stmt_sejarah_gaji = $conn->prepare($sql_sejarah_gaji);
        $stmt_sejarah_gaji->bind_param("sd", $no_komputer, $gaji_asal);
        $stmt_sejarah_gaji->execute();
        $stmt_sejarah_gaji->close();
    }
}

// Kira jumlah jam dan gaji
$total_jam_sebenar = 0; // Tambah untuk JAM SEBENAR
$total_jam_dapat = 0;   // Ubah untuk JAM DAPAT
$total_gaji = 0;
$rekod = [];
while ($row = $result->fetch_assoc()) {
    // Kira jumlah_jam_asal (JAM SEBENAR)
    $datetime_dari = new DateTime("{$row['tarikh']} {$row['masa_dari']}");
    $datetime_hingga = new DateTime("{$row['tarikh']} {$row['masa_hingga']}");
    if ($datetime_hingga < $datetime_dari) {
        $datetime_hingga->modify('+1 day');
    }
    $interval = $datetime_dari->diff($datetime_hingga);
    $jumlah_jam_asal = $interval->h + ($interval->i / 60) + ($interval->s / 3600);

    $row['jumlah_jam_sebenar'] = $jumlah_jam_asal; // Simpan dalam array rekod
    $rekod[] = $row;

    $total_jam_sebenar += $jumlah_jam_asal;
    $total_jam_dapat += $row['jumlah_jam'];
    $total_gaji += $row['gaji_dikira'];
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REKOD KERJA LEBIH MASA - SISTEM PENGUATKUASAAN</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e9ecef, #d6eaff);
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
        }
        .header {
            background-color: #0d1a40;
            color: white;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .container {
            max-width: 1500px;
            margin: 100px auto 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            border: 2px solid #0d1a40;
        }
        h2 {
            color: #0d1a40;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            font-weight: bold;
            margin-bottom: 25px;
            text-align: center;
            text-transform: uppercase;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            text-transform: uppercase;
        }
        .info-box .col-left, .info-box .col-right {
            flex: 1;
        }
        .info-box .label {
            color: #0d1a40;
            font-weight: normal; /* Unbold label */
        }
        .info-box .value {
            color: #0d1a40;
            font-weight: bold; /* Bold value */
        }
        .info-box p {
            margin: 5px 0;
            font-size: 1.1rem; /* Besarkan saiz fon untuk maklumat */
        }
        .table-container {
            overflow-x: auto;
        }
        .table {
            border-collapse: collapse;
            width: 100%;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-transform: uppercase;
        }
        .table thead th {
            background-color: #0d1a40;
            color: white;
            font-weight: 600;
            padding: 12px 10px;
            border: 1px solid #0d1a40;
            text-align: center;
            font-size: 0.9rem; /* Consistent font size with data */
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }
        .table tbody tr {
            transition: background-color 0.3s ease;
        }
        .table tbody tr:hover {
            background-color: #f1f5f9;
        }
        .table td {
            padding: 12px 10px;
            vertical-align: middle;
            border: 1px solid #dee2e6;
            font-size: 0.9rem;
            text-align: center;
            white-space: nowrap;
        }
        /* Color JAM DAPAT and RM in red */
        .table td:nth-child(7), /* JAM DAPAT */
        .table td:nth-child(13) { /* RM */
            color: #FF0000 !important;
        }
        /* Color total JAM DAPAT and RM in red in footer */
        .table tfoot td:nth-child(7), /* Total JAM DAPAT */
        .table tfoot td:nth-child(13) { /* Total RM */
            color: #FF0000 !important;
        }
        .table tfoot td {
            background-color: #e9ecef; /* Seragamkan warna latar belakang */
            font-weight: bold;
            padding: 12px 10px;
            border: 1px solid #dee2e6;
            font-size: 0.9rem; /* Consistent font size */
            color: #0d1a40;
            text-align: center; /* Pastikan semua sel footer diselaraskan ke tengah */
            white-space: nowrap;
        }
        /* Adjusted column widths for better appearance */
        .table th:nth-child(1), .table td:nth-child(1) { width: 50px !important; min-width: 50px !important; max-width: 50px !important; } /* BIL */
        .table th:nth-child(2), .table td:nth-child(2) { width: 120px !important; min-width: 120px !important; max-width: 120px !important; text-align: center !important; } /* HARI */
        .table th:nth-child(3), .table td:nth-child(3) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; } /* TARIKH */
        .table th:nth-child(4), .table td:nth-child(4) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; } /* MULA */
        .table th:nth-child(5), .table td:nth-child(5) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; } /* TAMAT */
        .table th:nth-child(6), .table td:nth-child(6) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; } /* JAM SEBENAR */
        .table th:nth-child(7), .table td:nth-child(7) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; } /* JAM DAPAT */
        /* Seragamkan lebar lajur A, B, C, D, dan E dengan lebih tegas */
        .table th:nth-child(8), .table td:nth-child(8) { width: 60px !important; min-width: 60px !important; max-width: 60px !important; } /* A */
        .table th:nth-child(9), .table td:nth-child(9) { width: 60px !important; min-width: 60px !important; max-width: 60px !important; } /* B */
        .table th:nth-child(10), .table td:nth-child(10) { width: 60px !important; min-width: 60px !important; max-width: 60px !important; } /* C */
        .table th:nth-child(11), .table td:nth-child(11) { width: 60px !important; min-width: 60px !important; max-width: 60px !important; } /* D */
        .table th:nth-child(12), .table td:nth-child(12) { width: 60px !important; min-width: 60px !important; max-width: 60px !important; } /* E */
        .table th:nth-child(13), .table td:nth-child(13) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; } /* RM */
        .table th:nth-child(14), .table td:nth-child(14) { width: 350px !important; min-width: 350px !important; max-width: 350px !important; text-align: left !important; white-space: normal !important; line-height: 1.4 !important; } /* KENYATAAN TUGASAN */
        .table th:nth-child(15), .table td:nth-child(15) { width: 180px !important; min-width: 180px !important; max-width: 180px !important; } /* TINDAKAN */
        /* Pastikan jumlah RM selari dengan lajur RM */
        .table tfoot td:nth-child(13) {
            width: 80px !important;
            min-width: 80px !important;
            max-width: 80px !important;
            text-align: center !important;
        }
        .btn-custom {
            padding: 10px 20px; /* Seragamkan padding */
            font-size: 1rem; /* Seragamkan saiz fon */
            font-weight: bold;
            border-radius: 8px;
            transition: transform 0.2s ease, background-color 0.3s ease;
            margin: 5px;
            min-width: 220px; /* Berikan lebar minimum untuk seragamkan saiz */
            text-align: center; /* Pastikan teks dalam butang diselaraskan ke tengah */
        }
        .btn-custom:hover {
            transform: scale(1.05);
        }
        .btn-primary {
            background-color: #0d1a40;
            border: none;
        }
        .btn-primary:hover {
            background-color: #1e3a8a;
        }
        .btn-danger {
            background-color: #dc3545;
            border: none;
            padding: 4px 8px; /* Kecilkan lagi padding */
            font-size: 0.75rem; /* Kecilkan lagi saiz fon */
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-warning {
            background-color: #ffc107;
            border: none;
            color: #0d1a40;
            padding: 4px 8px; /* Kecilkan lagi padding */
            font-size: 0.75rem; /* Kecilkan lagi saiz fon */
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .btn-success { /* Tambah gaya untuk butang CETAK */
            background-color: #28a745;
            border: none;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-klm {
            background-color: #17a2b8;
            border: none;
        }
        .btn-klm:hover {
            background-color: #138496;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px; /* Berikan margin atas untuk jarak dari jadual */
        }
        .action-buttons {
            display: flex;
            flex-direction: column; /* Susun menegak */
            gap: 5px; /* Jarak antara butang */
            justify-content: center; /* Selaraskan butang ke tengah */
            align-items: center;
        }
        .no-data {
            text-align: center;
            color: #6c757d;
            font-size: 1.2rem;
            padding: 20px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEM PENGUATKUASAAN</h1>
    </div>
    <div class="container">
        <h2>REKOD KERJA LEBIH MASA<?php echo $no_komputer && $info ? " - " . htmlspecialchars(strtoupper($info['nama'])) . ($bulan_filter ? " (" . strtoupper(date('F Y', strtotime($bulan_filter . "-01"))) . ")" : "") : ""; ?></h2>
        <?php if ($info) { ?>
            <div class="info-box">
                <div class="col-left">
                    <p><span class="label">NAMA:</span> <span class="value"><?php echo htmlspecialchars(strtoupper($info['nama'])); ?></span></p>
                    <p><span class="label">JAWATAN:</span> <span class="value"><?php echo htmlspecialchars(strtoupper($info['jawatan'])); ?></span></p>
                    <p><span class="label">JABATAN:</span> <span class="value"><?php echo htmlspecialchars(strtoupper($info['nama_jabatan'])); ?></span></p>
                </div>
                <div class="col-right">
                    <p><span class="label">NO KAD PENGENALAN:</span> <span class="value"><?php echo htmlspecialchars(strtoupper($info['kad_pengenalan'])); ?></span></p>
                    <p><span class="label">NO KOMPUTER:</span> <span class="value"><?php echo htmlspecialchars(strtoupper($info['no_komputer'])); ?></span></p>
                    <p><span class="label">GAJI:</span> <span class="value">RM <?php echo number_format($gaji, 2); ?> (GAJI TAHUN SEMASA)</span></p>
                </div>
            </div>
        <?php } ?>
        <?php if (!empty($rekod)) { ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>BIL</th>
                            <th>HARI</th>
                            <th>TARIKH</th>
                            <th>MULA</th>
                            <th>TAMAT</th>
                            <th>JAM SEBENAR</th>
                            <th>JAM DAPAT</th>
                            <th>A</th>
                            <th>B</th>
                            <th>C</th>
                            <th>D</th>
                            <th>E</th>
                            <th>RM</th>
                            <th>KENYATAAN TUGASAN</th>
                            <th>TINDAKAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $bil = 1;
                        foreach ($rekod as $row) { 
                            $tarikh = DateTime::createFromFormat('Y-m-d', $row['tarikh']);
                            $tarikh_format = $tarikh ? $tarikh->format('d/m/Y') : $row['tarikh'];
                            $masa_dari = DateTime::createFromFormat('H:i:s', $row['masa_dari']);
                            $masa_dari_format = $masa_dari ? $masa_dari->format('h:i A') : $row['masa_dari'];
                            $masa_hingga = DateTime::createFromFormat('H:i:s', $row['masa_hingga']);
                            $masa_hingga_format = $masa_hingga ? $masa_hingga->format('h:i A') : $row['masa_hingga'];
                        ?>
                            <tr>
                                <td><?php echo $bil++; ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($row['kategori_hari'])); ?></td>
                                <td><?php echo htmlspecialchars($tarikh_format); ?></td>
                                <td><?php echo htmlspecialchars($masa_dari_format); ?></td>
                                <td><?php echo htmlspecialchars($masa_hingga_format); ?></td>
                                <td><?php echo number_format($row['jumlah_jam_sebenar'], 2); ?></td>
                                <td><?php echo number_format($row['jumlah_jam'], 2); ?></td>
                                <td><?php echo ($row['jam_A'] == 0) ? '' : number_format($row['jam_A'], 2); ?></td>
                                <td><?php echo ($row['jam_B'] == 0) ? '' : number_format($row['jam_B'], 2); ?></td>
                                <td><?php echo ($row['jam_C'] == 0) ? '' : number_format($row['jam_C'], 2); ?></td>
                                <td><?php echo ($row['jam_D'] == 0) ? '' : number_format($row['jam_D'], 2); ?></td>
                                <td><?php echo ($row['jam_E'] == 0) ? '' : number_format($row['jam_E'], 2); ?></td>
                                <td><?php echo number_format($row['gaji_dikira'], 2); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($row['kenyataan_tugasan'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="./edit_klm.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-warning btn-custom">
                                            <i class="bi bi-pencil me-1"></i>EDIT
                                        </a>
                                        <a href="./senarai_klm.php?padam=<?php echo $row['id'] . ($no_komputer ? '&no_komputer=' . urlencode($no_komputer) : '') . ($bulan_filter ? '&bulan=' . urlencode($bulan_filter) : ''); ?>" 
                                           class="btn btn-danger btn-custom" 
                                           onclick="return confirm('Adakah anda pasti ingin memadam rekod ini?');">
                                            <i class="bi bi-trash me-1"></i>PADAM
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5"></td>
                            <td><?php echo number_format($total_jam_sebenar, 2); ?></td>
                            <td><?php echo number_format($total_jam_dapat, 2); ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><?php echo number_format($total_gaji, 2); ?></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="btn-group">
                <!-- Butang TAMBAH REKOD BARU kekal merujuk kepada borang_klm.php -->
                <a href="./borang_klm.php<?php echo $no_komputer ? '?no_komputer=' . urlencode($no_komputer) : ''; ?>" class="btn btn-primary btn-custom"><i class="bi bi-pencil-square me-2"></i>TAMBAH REKOD BARU</a>
                <a href="./senarai_anggota_klm.php" class="btn btn-klm btn-custom"><i class="bi bi-list-ul me-2"></i>SENARAI NAMA KLM</a>
                <?php if ($no_komputer) { ?>
                    <a href="./rekod_klm_anggota.php?no_komputer=<?php echo urlencode($no_komputer); ?>" class="btn btn-warning btn-custom"><i class="bi bi-file-earmark-text me-2"></i>REKOD KLM ANGGOTA</a>
                    <!-- Butang CETAK dirujuk kepada fail baharu borang_cetak_klm.php -->
                    <a href="./borang_cetak_klm.php?no_komputer=<?php echo urlencode($no_komputer); ?>&bulan=<?php echo urlencode($bulan_filter); ?>" 
                       class="btn btn-success btn-custom" 
                       target="_blank">
                        <i class="bi bi-printer me-2"></i>CETAK
                    </a>
                <?php } ?>
            </div>
        <?php } else { ?>
            <p class="no-data">TIADA REKOD KERJA LEBIH MASA DITEMUI UNTUK <?php echo $bulan_filter ? strtoupper(date('F Y', strtotime($bulan_filter . "-01"))) : 'BULAN INI'; ?></p>
            <div class="btn-group">
                <!-- Butang TAMBAH REKOD BARU kekal merujuk kepada borang_klm.php -->
                <a href="./borang_klm.php<?php echo $no_komputer ? '?no_komputer=' . urlencode($no_komputer) : ''; ?>" class="btn btn-primary btn-custom"><i class="bi bi-pencil-square me-2"></i>TAMBAH REKOD BARU</a>
                <a href="./senarai_anggota_klm.php" class="btn btn-klm btn-custom"><i class="bi bi-list-ul me-2"></i>SENARAI NAMA KLM</a>
                <?php if ($no_komputer) { ?>
                    <a href="./rekod_klm_anggota.php?no_komputer=<?php echo urlencode($no_komputer); ?>" class="btn btn-warning btn-custom"><i class="bi bi-file-earmark-text me-2"></i>REKOD KLM ANGGOTA</a>
                    <!-- Butang CETAK dirujuk kepada fail baharu borang_cetak_klm.php -->
                    <a href="./borang_cetak_klm.php?no_komputer=<?php echo urlencode($no_komputer); ?>&bulan=<?php echo urlencode($bulan_filter); ?>" 
                       class="btn btn-success btn-custom" 
                       target="_blank">
                        <i class="bi bi-printer me-2"></i>CETAK
                    </a>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>