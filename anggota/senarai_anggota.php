<?php
include_once '../session_check.php'; // Gunakan session_check.php di folder akar

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "penguatkuasa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// Proses carian
$search_no_komputer = isset($_GET['search_no_komputer']) ? filter_input(INPUT_GET, 'search_no_komputer', FILTER_SANITIZE_STRING) : '';
$search_nama = isset($_GET['search_nama']) ? filter_input(INPUT_GET, 'search_nama', FILTER_SANITIZE_STRING) : '';
$filter_unit = isset($_GET['filter_unit']) ? filter_input(INPUT_GET, 'filter_unit', FILTER_SANITIZE_NUMBER_INT) : '';
$filter_status = isset($_GET['filter_status']) ? filter_input(INPUT_GET, 'filter_status', FILTER_SANITIZE_NUMBER_INT) : '';
$filter_jawatan = isset($_GET['filter_jawatan']) ? filter_input(INPUT_GET, 'filter_jawatan', FILTER_SANITIZE_NUMBER_INT) : '';

$sql = "SELECT a.no_komputer, a.nama, a.gaji, j.nama AS jawatan, u.nama AS unit_seksyen, s.nama AS status
        FROM anggota a
        LEFT JOIN jawatan j ON a.id_jawatan = j.id
        LEFT JOIN unit_seksyen u ON a.id_unit_seksyen = u.id
        LEFT JOIN status s ON a.id_status = s.id WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_no_komputer)) {
    $sql .= " AND a.no_komputer LIKE ?";
    $params[] = "%" . $search_no_komputer . "%";
    $types .= "s";
}
if (!empty($search_nama)) {
    $sql .= " AND a.nama LIKE ?";
    $params[] = "%" . $search_nama . "%";
    $types .= "s";
}
if (!empty($filter_unit)) {
    $sql .= " AND a.id_unit_seksyen = ?";
    $params[] = $filter_unit;
    $types .= "i";
}
if (!empty($filter_status)) {
    $sql .= " AND a.id_status = ?";
    $params[] = $filter_status;
    $types .= "i";
}
if (!empty($filter_jawatan)) {
    $sql .= " AND a.id_jawatan = ?";
    $params[] = $filter_jawatan;
    $types .= "i";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Simpan semua rekod dalam array untuk diproses
$anggota_list = [];
while ($row = $result->fetch_assoc()) {
    $anggota_list[] = $row;
}

// Semak dan kemas kini sejarah gaji untuk setiap anggota
foreach ($anggota_list as $anggota) {
    $no_komputer = $anggota['no_komputer'];
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

// Ambil data untuk dropdown
$unit_result = $conn->query("SELECT id, nama FROM unit_seksyen ORDER BY nama");
$status_result = $conn->query("SELECT id, nama FROM status ORDER BY nama");
$jawatan_result = $conn->query("SELECT id, nama FROM jawatan ORDER BY nama");
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENARAI ANGGOTA - SISTEM PENGUATKUASAAN</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e9ecef, #d6eaff);
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
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
        }
        .container {
            max-width: 1300px;
            margin: 100px auto 40px auto;
            padding: 20px;
        }
        .table-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            border: 2px solid #0d1a40;
            padding: 30px;
        }
        h2 {
            color: #0d1a40;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
            font-weight: bold;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }
        .search-container {
            margin-bottom: 30px;
        }
        .search-box {
            margin-bottom: 15px;
        }
        .search-box label {
            font-weight: bold;
            color: #0d1a40;
            margin-bottom: 5px;
            display: block;
            text-align: center;
        }
        .search-box input, .search-box select {
            width: 100%;
            padding: 10px;
            border: 2px solid #0d1a40;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            text-transform: uppercase;
        }
        .search-box input:focus, .search-box select:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 0 0.2rem rgba(29, 58, 138, 0.25);
            outline: none;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
        }
        .table th {
            background-color: #0d1a40;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            border-bottom: 3px solid #1e3a8a;
            white-space: nowrap;
            vertical-align: middle;
        }
        .table th.bil {
            width: 60px;
        }
        .table td {
            vertical-align: middle;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            text-align: center;
        }
        .table td.align-left {
            text-align: left;
        }
        .table tr:hover {
            background-color: #e6f0fa;
            transition: background-color 0.3s ease;
        }
        /* Gaya untuk lajur BIL, NO KOMPUTER, NAMA, JAWATAN, UNIT/SEKSYEN, dan STATUS */
        .table td:nth-child(1), /* BIL */
        .table td:nth-child(2), /* NO KOMPUTER */
        .table td:nth-child(3), /* NAMA */
        .table td:nth-child(4), /* JAWATAN */
        .table td:nth-child(5), /* UNIT/SEKSYEN */
        .table td:nth-child(6) { /* STATUS */
            font-size: 0.85rem; /* Saiz fon lebih kecil untuk paparan penuh */
            white-space: normal; /* Benarkan teks berbalut */
            max-width: none; /* Buang had lebar maksimum */
            overflow: visible; /* Pastikan teks tidak terpotong */
            text-overflow: initial; /* Buang ellipsis */
        }
        /* Laraskan lebar lajur untuk memuatkan teks penuh */
        .table th:nth-child(1), .table td:nth-child(1) { width: 60px; min-width: 60px; } /* BIL */
        .table th:nth-child(2), .table td:nth-child(2) { width: 120px; min-width: 120px; } /* NO KOMPUTER */
        .table th:nth-child(3), .table td:nth-child(3) { width: 250px; min-width: 250px; } /* NAMA */
        .table th:nth-child(4), .table td:nth-child(4) { width: 200px; min-width: 200px; } /* JAWATAN */
        .table th:nth-child(5), .table td:nth-child(5) { width: 200px; min-width: 200px; } /* UNIT/SEKSYEN */
        .table th:nth-child(6), .table td:nth-child(6) { width: 100px; min-width: 100px; } /* STATUS */
        .table th:nth-child(7), .table td:nth-child(7) { width: 120px; min-width: 120px; } /* TINDAKAN */
        .no-komputer-link {
            color: #0d1a40;
            text-decoration: none;
            font-weight: bold;
        }
        .no-komputer-link:hover {
            text-decoration: underline;
            color: #1e3a8a;
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
        .btn-daftar {
            background-color: #0d1a40;
            color: white;
            border: none;
        }
        .btn-daftar:hover {
            background-color: #1e3a8a;
        }
        .btn-senarai {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        .btn-senarai:hover {
            background-color: #5a6268;
        }
        .btn-home {
            background-color: #0d1a40;
            color: white;
            border: none;
        }
        .btn-home:hover {
            background-color: #1e3a8a;
        }
        .btn-klm {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 8px 15px;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            min-width: 100px;
        }
        .btn-klm:hover {
            background-color: #138496;
            transform: scale(1.05);
        }
        .action-column {
            min-width: 120px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEM PENGUATKUASAAN</h1>
    </div>

    <div class="container">
        <div class="table-container">
            <h2 class="text-center">SENARAI ANGGOTA YANG DIDAFTARKAN</h2>
            
            <div class="search-container">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-6 search-box">
                            <label>NO KOMPUTER</label>
                            <input type="text" name="search_no_komputer" value="<?php echo htmlspecialchars($search_no_komputer); ?>" placeholder="Cari No Komputer..." class="form-control">
                        </div>
                        <div class="col-md-6 search-box">
                            <label>NAMA</label>
                            <input type="text" name="search_nama" value="<?php echo htmlspecialchars($search_nama); ?>" placeholder="Cari Nama..." class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 search-box">
                            <label>UNIT/SEKSYEN</label>
                            <select name="filter_unit" class="form-control">
                                <option value="">Semua Unit/Seksyen</option>
                                <?php while ($row = $unit_result->fetch_assoc()) { ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo $filter_unit == $row['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(strtoupper($row['nama'])); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4 search-box">
                            <label>JAWATAN</label>
                            <select name="filter_jawatan" class="form-control">
                                <option value="">Semua Jawatan</option>
                                <?php while ($row = $jawatan_result->fetch_assoc()) { ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo $filter_jawatan == $row['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(strtoupper($row['nama'])); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4 search-box">
                            <label>STATUS</label>
                            <select name="filter_status" class="form-control">
                                <option value="">Semua Status</option>
                                <?php while ($row = $status_result->fetch_assoc()) { ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo $filter_status == $row['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(strtoupper($row['nama'])); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-daftar btn-custom"><i class="bi bi-search me-2"></i>CARI</button>
                    </div>
                </form>
            </div>

            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th class="bil"><i class="bi bi-hash me-1"></i>BIL</th>
                        <th><i class="bi bi-pc-display me-1"></i>NO KOMPUTER</th>
                        <th><i class="bi bi-person me-1"></i>NAMA</th>
                        <th><i class="bi bi-person-badge me-1"></i>JAWATAN</th>
                        <th><i class="bi bi-building me-1"></i>UNIT/SEKSYEN</th>
                        <th><i class="bi bi-info-circle me-1"></i>STATUS</th>
                        <th><i class="bi bi-file-earmark-plus me-1"></i>TINDAKAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $bil = 1;
                    if (!empty($anggota_list)) {
                        foreach ($anggota_list as $row) {
                            echo "<tr>";
                            echo "<td>" . $bil++ . "</td>";
                            echo "<td>";
                            echo "<a href='./paparan_anggota.php?no_komputer=" . urlencode($row['no_komputer']) . "' class='no-komputer-link'>";
                            echo htmlspecialchars(strtoupper($row['no_komputer']));
                            echo "</a>";
                            echo "</td>";
                            echo "<td class='align-left'>" . htmlspecialchars(strtoupper($row['nama'])) . "</td>";
                            echo "<td>" . htmlspecialchars(strtoupper($row['jawatan'])) . "</td>";
                            echo "<td class='align-left'>" . htmlspecialchars(strtoupper($row['unit_seksyen'])) . "</td>";
                            echo "<td>" . htmlspecialchars(strtoupper($row['status'])) . "</td>";
                            echo "<td class='action-column'>";
                            echo "<a href='../klm/borang_klm.php?no_komputer=" . urlencode($row['no_komputer']) . "' class='btn btn-klm'><i class='bi bi-file-earmark-plus me-1'></i>ISI KLM</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>Tiada data anggota ditemui.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="text-center mt-4">
                <a href="./daftar_anggota.php" class="btn btn-daftar btn-custom"><i class="bi bi-person-plus-fill me-2"></i>DAFTAR BARU</a>
                <a href="./senarai_anggota.php" class="btn btn-senarai btn-custom"><i class="bi bi-list-ul me-2"></i>SENARAI ANGGOTA</a>
                <a href="./menu.php" class="btn btn-home btn-custom"><i class="bi bi-house-door me-2"></i>KE HALAMAN UTAMA</a>
            </div>
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