<?php
include_once '../session_check.php'; // Gunakan session_check.php di folder akar

// Sambungan ke database
$conn = new mysqli("localhost", "root", "", "penguatkuasa");
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// Ambil no_komputer dari URL
$no_komputer = isset($_GET['no_komputer']) ? filter_input(INPUT_GET, 'no_komputer', FILTER_SANITIZE_STRING) : '';
if (empty($no_komputer)) {
    echo "<script>alert('No komputer tidak ditentukan.'); window.location='./senarai_anggota_klm.php';</script>";
    exit();
}

// Ambil nama dan gaji anggota
$sql_nama = "SELECT nama, gaji FROM anggota WHERE no_komputer = ?";
$stmt_nama = $conn->prepare($sql_nama);
$stmt_nama->bind_param("s", $no_komputer);
$stmt_nama->execute();
$result_nama = $stmt_nama->get_result();
$anggota = $result_nama->fetch_assoc();
$stmt_nama->close();

if (!$anggota) {
    echo "<script>alert('Anggota tidak ditemui.'); window.location='./senarai_anggota_klm.php';</script>";
    exit();
}

// Ambil gaji terkini dari sejarah_gaji untuk paparan info-box (tarikh semasa: April 2025)
$tarikh_rujukan_semasa = date('Y-m-d'); // 2025-04-12
$gaji_query = $conn->prepare("SELECT gaji FROM sejarah_gaji WHERE no_komputer = ? AND tarikh_mula <= ? AND (tarikh_tamat >= ? OR tarikh_tamat IS NULL) ORDER BY tarikh_mula DESC LIMIT 1");
$gaji_query->bind_param("sss", $no_komputer, $tarikh_rujukan_semasa, $tarikh_rujukan_semasa);
$gaji_query->execute();
$gaji_result = $gaji_query->get_result();
$gaji_pokok = $gaji_result->num_rows > 0 ? $gaji_result->fetch_assoc()['gaji'] : 0;
$gaji_query->close();

// Fallback ke gaji dalam table anggota jika tiada rekod dalam sejarah_gaji
if ($gaji_pokok == 0) {
    $gaji_pokok = $anggota['gaji'] ?? 0;
}

// Semak dan kemas kini sejarah gaji jika gaji berubah
if ($anggota && $no_komputer) {
    $gaji = $anggota['gaji'] ?? 0;

    // Semak gaji terkini dalam sejarah_gaji
    $gaji_sejarah_query = $conn->prepare("SELECT gaji FROM sejarah_gaji WHERE no_komputer = ? AND tarikh_tamat IS NULL ORDER BY tarikh_mula DESC LIMIT 1");
    $gaji_sejarah_query->bind_param("s", $no_komputer);
    $gaji_sejarah_query->execute();
    $gaji_sejarah_result = $gaji_sejarah_query->get_result();
    $gaji_sejarah = $gaji_sejarah_result->num_rows > 0 ? $gaji_sejarah_result->fetch_assoc()['gaji'] : null;
    $gaji_sejarah_query->close();

    if ($gaji_sejarah !== null && $gaji != $gaji_sejarah) {
        // Tamatkan rekod gaji lama
        $sql_tamat_gaji = "UPDATE sejarah_gaji SET tarikh_tamat = CURDATE() WHERE no_komputer = ? AND tarikh_tamat IS NULL";
        $stmt_tamat_gaji = $conn->prepare($sql_tamat_gaji);
        $stmt_tamat_gaji->bind_param("s", $no_komputer);
        $stmt_tamat_gaji->execute();
        $stmt_tamat_gaji->close();

        // Tambah rekod gaji baru
        $sql_sejarah_gaji = "INSERT INTO sejarah_gaji (no_komputer, gaji, tarikh_mula, tarikh_tamat) VALUES (?, ?, CURDATE(), NULL)";
        $stmt_sejarah_gaji = $conn->prepare($sql_sejarah_gaji);
        $stmt_sejarah_gaji->bind_param("sd", $no_komputer, $gaji);
        $stmt_sejarah_gaji->execute();
        $stmt_sejarah_gaji->close();
    }
}

// Ambil tahun dari URL untuk carian (jika ada)
$tahun_filter = isset($_GET['tahun']) ? filter_input(INPUT_GET, 'tahun', FILTER_SANITIZE_NUMBER_INT) : '';

// Query untuk senarai tahun unik
$sql_tahun = "SELECT DISTINCT YEAR(tarikh) AS tahun FROM klm_kerja WHERE no_komputer = ? ORDER BY tahun DESC";
$stmt_tahun = $conn->prepare($sql_tahun);
$stmt_tahun->bind_param("s", $no_komputer);
$stmt_tahun->execute();
$tahun_result = $stmt_tahun->get_result();
$stmt_tahun->close();

// Query untuk rekod KLM
$sql = "SELECT tarikh, masa_dari, masa_hingga, jumlah_jam, gaji_dikira 
        FROM klm_kerja 
        WHERE no_komputer = ?";
if ($tahun_filter) {
    $sql .= " AND YEAR(tarikh) = ?";
}
$sql .= " ORDER BY tarikh";

if ($tahun_filter) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $no_komputer, $tahun_filter);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $no_komputer);
}
$stmt->execute();
$result = $stmt->get_result();

// Proses data untuk mengira jumlah_jam_asal dan gabung mengikut bulan/tahun
$rekod_per_bulan = [];
$total_jam_sebenar = 0;
$total_jam_dapat = 0;
$total_rm = 0;
while ($row = $result->fetch_assoc()) {
    $tahun = date('Y', strtotime($row['tarikh']));
    $bulan = date('n', strtotime($row['tarikh']));

    // Kira jumlah_jam_asal
    $datetime_dari = new DateTime("{$row['tarikh']} {$row['masa_dari']}");
    $datetime_hingga = new DateTime("{$row['tarikh']} {$row['masa_hingga']}");
    if ($datetime_hingga < $datetime_dari) {
        $datetime_hingga->modify('+1 day');
    }
    $interval = $datetime_dari->diff($datetime_hingga);
    $jumlah_jam_asal = $interval->h + ($interval->i / 60) + ($interval->s / 3600);

    // Gabungkan data mengikut tahun dan bulan
    $key = "$tahun-$bulan";
    if (!isset($rekod_per_bulan[$key])) {
        $rekod_per_bulan[$key] = [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'jumlah_jam_sebenar' => 0,
            'jumlah_jam_dapat' => 0,
            'jumlah_rm' => 0,
            'gaji' => 0 // Tambah untuk simpan gaji berdasarkan julat tarikh
        ];

        // Ambil gaji berdasarkan tarikh rujukan (tarikh pertama dalam bulan tersebut)
        $tarikh_rujukan = "$tahun-" . sprintf("%02d", $bulan) . "-01";
        $gaji_query = $conn->prepare("SELECT gaji FROM sejarah_gaji WHERE no_komputer = ? AND tarikh_mula <= ? AND (tarikh_tamat >= ? OR tarikh_tamat IS NULL) ORDER BY tarikh_mula DESC LIMIT 1");
        $gaji_query->bind_param("sss", $no_komputer, $tarikh_rujukan, $tarikh_rujukan);
        $gaji_query->execute();
        $gaji_result = $gaji_query->get_result();
        $gaji = $gaji_result->num_rows > 0 ? $gaji_result->fetch_assoc()['gaji'] : 0;
        $gaji_query->close();

        // Fallback ke gaji dalam table anggota jika tiada rekod dalam sejarah_gaji
        if ($gaji == 0) {
            $gaji = $anggota['gaji'] ?? 0;
        }

        $rekod_per_bulan[$key]['gaji'] = $gaji;
    }

    $rekod_per_bulan[$key]['jumlah_jam_sebenar'] += $jumlah_jam_asal;
    $rekod_per_bulan[$key]['jumlah_jam_dapat'] += $row['jumlah_jam'];
    $rekod_per_bulan[$key]['jumlah_rm'] += $row['gaji_dikira'];

    // Kira jumlah keseluruhan
    $total_jam_sebenar += $jumlah_jam_asal;
    $total_jam_dapat += $row['jumlah_jam'];
    $total_rm += $row['gaji_dikira'];
}

// Susun mengikut tahun dan bulan (terbalik untuk tahun, menaik untuk bulan)
usort($rekod_per_bulan, function($a, $b) {
    if ($a['tahun'] == $b['tahun']) {
        return $a['bulan'] - $b['bulan'];
    }
    return $b['tahun'] - $a['tahun'];
});
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REKOD KLM - <?php echo htmlspecialchars(strtoupper($anggota['nama'])); ?> - SISTEM PENGUATKUASAAN</title>
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
            max-width: 1200px;
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
        .filter-container {
            margin-bottom: 20px;
            text-align: center;
        }
        .filter-container select {
            width: 200px;
            padding: 8px;
            border: 2px solid #0d1a40;
            border-radius: 8px;
            font-size: 1rem;
            text-transform: uppercase;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            font-size: 0.95rem;
        }
        .info-box strong {
            color: #0d1a40;
            font-weight: 600;
        }
        .table {
            text-transform: uppercase;
            border-collapse: collapse;
            width: 100%;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .table thead th {
            background-color: #0d1a40;
            color: white;
            font-weight: 600;
            padding: 12px 10px;
            border: 1px solid #0d1a40;
            text-align: center;
            font-size: 0.95rem;
            white-space: normal;
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
        .table tfoot td {
            background-color: #e9ecef;
            font-weight: bold;
            padding: 12px 10px;
            border: 1px solid #dee2e6;
            font-size: 0.95rem;
            color: #0d1a40;
            text-align: center;
            white-space: nowrap;
        }
        .table th:nth-child(1), .table td:nth-child(1) { width: 80px; min-width: 80px; max-width: 80px; } /* TAHUN */
        .table th:nth-child(2), .table td:nth-child(2) { width: 60px; min-width: 60px; max-width: 60px; } /* BIL */
        .table th:nth-child(3), .table td:nth-child(3) { width: 120px; min-width: 120px; max-width: 120px; text-align: center; } /* BULAN */
        .table th:nth-child(4), .table td:nth-child(4) { width: 100px; min-width: 100px; max-width: 100px; } /* JAM SEBENAR */
        .table th:nth-child(5), .table td:nth-child(5) { width: 100px; min-width: 100px; max-width: 100px; } /* JAM DAPAT */
        .table th:nth-child(6), .table td:nth-child(6) { width: 120px; min-width: 120px; max-width: 120px; text-align: center; } /* JUMLAH RM */
        .table th:nth-child(7), .table td:nth-child(7) { width: 120px; min-width: 120px; max-width: 120px; text-align: center; } /* 1/3 GAJI (RM) */
        .table th:nth-child(8), .table td:nth-child(8) { width: 160px; min-width: 160px; max-width: 160px; text-align: center; white-space: normal; line-height: 1.2; } /* LEBIH/ KURANG (RM) */
        .table th:nth-child(9), .table td:nth-child(9) { width: 100px; min-width: 100px; max-width: 100px; } /* PERATUS */
        .bulan-link {
            color: #0d1a40;
            text-decoration: none;
            font-weight: bold;
        }
        .bulan-link:hover {
            text-decoration: underline;
            color: #1e3a8a;
        }
        .no-data {
            text-align: center;
            color: #6c757d;
            font-size: 1.2rem;
            padding: 20px;
            text-transform: uppercase;
        }
        .btn-custom {
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 8px;
            transition: transform 0.2s ease, background-color 0.3s ease;
            margin: 5px;
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
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .peratus-box {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        .peratus-box-green {
            background-color: #009900; /* Dark Lime Green */
            color: #FFFFFF; /* Teks putih */
        }
        .peratus-box-yellow {
            background-color: #FFFF00; /* Pure Yellow */
            color: #000000; /* Teks hitam */
        }
        .peratus-box-red {
            background-color: #FF4040; /* Merah cerah */
            color: #FFFFFF; /* Teks putih */
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEM PENGUATKUASAAN</h1>
    </div>
    <div class="container">
        <h2>REKOD KLM - <?php echo htmlspecialchars(strtoupper($anggota['nama'])); ?></h2>
        
        <!-- Maklumat Gaji Pokok -->
        <div class="info-box">
            <p><strong>NO KOMPUTER:</strong> <?php echo htmlspecialchars(strtoupper($no_komputer)); ?> | 
               <strong>GAJI POKOK:</strong> RM <?php echo number_format($gaji_pokok, 2); ?> (TERKINI PADA <?php echo date('F Y', strtotime($tarikh_rujukan_semasa)); ?>)</p>
        </div>

        <!-- Dropdown Carian Tahun -->
        <div class="filter-container">
            <form method="GET" action="">
                <input type="hidden" name="no_komputer" value="<?php echo htmlspecialchars($no_komputer); ?>">
                <label for="tahun" class="form-label">PILIH TAHUN:</label>
                <select name="tahun" id="tahun" class="form-select d-inline-block" onchange="this.form.submit()">
                    <option value="">SEMUA TAHUN</option>
                    <?php while ($tahun_row = $tahun_result->fetch_assoc()) { ?>
                        <option value="<?php echo $tahun_row['tahun']; ?>" <?php echo $tahun_filter == $tahun_row['tahun'] ? 'selected' : ''; ?>>
                            <?php echo $tahun_row['tahun']; ?>
                        </option>
                    <?php } ?>
                </select>
            </form>
        </div>

        <?php if (!empty($rekod_per_bulan)) { ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>TAHUN</th>
                        <th>BIL</th>
                        <th>BULAN</th>
                        <th>JAM SEBENAR</th>
                        <th>JAM DAPAT</th>
                        <th>JUMLAH RM</th>
                        <th>1/3 GAJI (RM)</th>
                        <th>LEBIH/ KURANG (RM)</th>
                        <th>PERATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $bulan_nama = [
                        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MAC', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUN',
                        7 => 'JULAI', 8 => 'OGOS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DISEMBER'
                    ];
                    foreach ($rekod_per_bulan as $row) {
                        // Kira 1/3 gaji berdasarkan gaji dari sejarah_gaji
                        $satu_tiga_gaji = $row['gaji'] / 3;
                        // Kira lebihan/kurang
                        $lebihan_kurang = $row['jumlah_rm'] - $satu_tiga_gaji;
                        // Kira peratusan
                        $peratus = ($row['gaji'] > 0) ? ($row['jumlah_rm'] / $row['gaji']) * 100 : 0;
                        
                        // Tentukan kelas kotak berdasarkan peratusan
                        $kotak_kelas = '';
                        if ($peratus <= 30) {
                            $kotak_kelas = 'peratus-box-green';
                        } elseif ($peratus > 30 && $peratus <= 50) {
                            $kotak_kelas = 'peratus-box-yellow';
                        } elseif ($peratus >= 70) {
                            $kotak_kelas = 'peratus-box-red';
                        }

                        echo "<tr>";
                        echo "<td>" . $row['tahun'] . "</td>";
                        echo "<td>" . $row['bulan'] . "</td>";
                        echo "<td>";
                        echo "<a href='./senarai_klm.php?no_komputer=" . urlencode($no_komputer) . "&bulan=" . $row['tahun'] . "-" . sprintf("%02d", $row['bulan']) . "' class='bulan-link'>";
                        echo $bulan_nama[$row['bulan']];
                        echo "</a>";
                        echo "</td>";
                        echo "<td>" . number_format($row['jumlah_jam_sebenar'], 2) . "</td>";
                        echo "<td>" . number_format($row['jumlah_jam_dapat'], 2) . "</td>";
                        echo "<td>RM " . number_format($row['jumlah_rm'], 2) . "</td>";
                        echo "<td>RM " . number_format($satu_tiga_gaji, 2) . "</td>";
                        echo "<td>RM " . number_format($lebihan_kurang, 2) . "</td>";
                        echo "<td><span class='peratus-box $kotak_kelas'>" . number_format($peratus, 2) . "%</span></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td><?php echo number_format($total_jam_sebenar, 2); ?></td>
                        <td><?php echo number_format($total_jam_dapat, 2); ?></td>
                        <td><?php echo "RM " . number_format($total_rm, 2); ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        <?php } else { ?>
            <p class="no-data">TIADA REKOD KLM DITEMUI UNTUK ANGGOTA INI</p>
        <?php } ?>
        <div class="btn-group">
            <a href="./senarai_anggota_klm.php" class="btn btn-primary btn-custom"><i class="bi bi-arrow-left me-2"></i>KEMBALI KE SENARAI ANGGOTA KLM</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>