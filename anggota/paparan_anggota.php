<?php
include_once '../session_check.php'; // Gunakan session_check.php di folder akar

// Sambungan ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "penguatkuasa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// Ambil no_komputer dari URL
$no_komputer = isset($_GET['no_komputer']) ? filter_input(INPUT_GET, 'no_komputer', FILTER_SANITIZE_STRING) : '';
if (empty($no_komputer)) {
    echo "<script>alert('No Komputer tidak sah.'); window.location.href='./senarai_anggota.php';</script>";
    exit();
}

// Query untuk ambil maklumat anggota
$sql = "SELECT a.*, j.nama AS jawatan, u.nama AS unit_seksyen, s.nama AS status, p.nama AS penempatan, jab.nama_jabatan AS jabatan 
        FROM anggota a 
        LEFT JOIN jawatan j ON a.id_jawatan = j.id 
        LEFT JOIN unit_seksyen u ON a.id_unit_seksyen = u.id 
        LEFT JOIN status s ON a.id_status = s.id 
        LEFT JOIN penempatan p ON a.id_penempatan = p.id 
        LEFT JOIN jabatan jab ON a.id_jabatan = jab.id 
        WHERE a.no_komputer = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $no_komputer);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Anggota dengan No Komputer $no_komputer tidak wujud.'); window.location.href='./senarai_anggota.php';</script>";
    exit();
}

$anggota = $result->fetch_assoc();
// Tukar semua data teks kepada huruf besar
$anggota['nama'] = strtoupper($anggota['nama']);
$anggota['no_komputer'] = strtoupper($anggota['no_komputer']);
$anggota['no_badan'] = strtoupper($anggota['no_badan'] ?: 'TIADA');
$anggota['kad_pengenalan'] = strtoupper($anggota['kad_pengenalan'] ?: 'TIADA');
$anggota['jawatan'] = strtoupper($anggota['jawatan'] ?: 'TIADA');
$anggota['unit_seksyen'] = strtoupper($anggota['unit_seksyen'] ?: 'TIADA');
$anggota['status'] = strtoupper($anggota['status'] ?: 'TIADA');
$anggota['penempatan'] = strtoupper($anggota['penempatan'] ?: 'TIADA');
$anggota['alamat'] = strtoupper($anggota['alamat'] ?: 'TIADA');
$anggota['no_telefon'] = strtoupper($anggota['no_telefon'] ?: 'TIADA');
$anggota['jabatan'] = strtoupper($anggota['jabatan'] ?: 'TIADA');
// Format gaji ke RM dengan dua tempat perpuluhan
$anggota['gaji'] = !empty($anggota['gaji']) ? 'RM' . number_format($anggota['gaji'], 2, '.', ',') : 'TIADA';

$stmt->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAKLUMAT ANGGOTA: <?php echo htmlspecialchars($anggota['no_komputer']); ?> - SISTEM PENGUATKUASAAN</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e9ecef, #d6eaff);
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding-left: 250px; /* Ruang untuk sidebar */
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
        .popup-container {
            max-width: 700px;
            margin: 80px auto 40px auto;
            padding: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            border: 2px solid #0d1a40;
        }
        .anggota-img {
            max-width: 150px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 2px solid #0d1a40;
        }
        .anggota-name {
            font-size: 2.2rem;
            font-weight: bold;
            color: #000000;
            margin-bottom: 15px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        .info-row {
            margin-bottom: 15px;
            align-items: center;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0;
            text-transform: uppercase;
        }
        .info-value {
            color: #495057;
            text-transform: uppercase;
        }
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #0d1a40;
            padding-top: 70px; /* Ruang untuk header */
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
            z-index: 999;
            transition: transform 0.3s ease;
        }
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            color: white;
            padding: 15px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .sidebar .nav-link:hover {
            transform: translateX(5px);
        }
        /* Gradient untuk setiap butang */
        .nav-kemas-kini {
            background: linear-gradient(90deg, #ffc107, #ffca2c);
            color: #0d1a40 !important;
        }
        .nav-kemas-kini:hover {
            background: linear-gradient(90deg, #e0a800, #f1c40f);
        }
        .nav-padam {
            background: linear-gradient(90deg, #dc3545, #e74c3c);
        }
        .nav-padam:hover {
            background: linear-gradient(90deg, #c82333, #d32f2f);
        }
        .nav-rekod-gaji {
            background: linear-gradient(90deg, #28a745, #2ecc71);
        }
        .nav-rekod-gaji:hover {
            background: linear-gradient(90deg, #218838, #27ae60);
        }
        .nav-sejarah-penempatan {
            background: linear-gradient(90deg, #6c757d, #95a5a6);
        }
        .nav-sejarah-penempatan:hover {
            background: linear-gradient(90deg, #5a6268, #7f8c8d);
        }
        .nav-senarai-anggota {
            background: linear-gradient(90deg, #17a2b8, #3498db);
        }
        .nav-senarai-anggota:hover {
            background: linear-gradient(90deg, #138496, #2980b9);
        }
        .nav-halaman-utama {
            background: linear-gradient(90deg, #0d1a40, #34495e);
        }
        .nav-halaman-utama:hover {
            background: linear-gradient(90deg, #1e3a8a, #2c3e50);
        }
        /* Responsif: Sembunyi sidebar pada skrin kecil */
        @media (max-width: 768px) {
            body {
                padding-left: 0;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .hamburger {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                font-size: 1.5rem;
                color: white;
                background: none;
                border: none;
                z-index: 1001;
            }
        }
        /* Sembunyikan hamburger pada skrin besar */
        @media (min-width: 769px) {
            .hamburger {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>SISTEM PENGUATKUASAAN</h1>
    </div>

    <!-- Hamburger Menu untuk skrin kecil -->
    <button class="hamburger" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="./kemaskini_anggota.php?no_komputer=<?php echo urlencode($anggota['no_komputer']); ?>" class="nav-link nav-kemas-kini"><i class="bi bi-pencil-square"></i>KEMAS KINI</a>
        <a href="#" class="nav-link nav-padam" disabled><i class="bi bi-trash"></i>PADAM</a>
        <a href="./rekod_gaji.php?no_komputer=<?php echo urlencode($anggota['no_komputer']); ?>" class="nav-link nav-rekod-gaji"><i class="bi bi-currency-dollar"></i>REKOD KENAIKAN GAJI</a>
        <a href="./sejarah_penempatan.php?no_komputer=<?php echo urlencode($anggota['no_komputer']); ?>" class="nav-link nav-sejarah-penempatan"><i class="bi bi-clock-history"></i>SEJARAH PENEMPATAN</a>
        <a href="./senarai_anggota.php" class="nav-link nav-senarai-anggota"><i class="bi bi-list-ul"></i>SENARAI ANGGOTA</a>
        <a href="./menu.php" class="nav-link nav-halaman-utama"><i class="bi bi-house"></i>KE HALAMAN UTAMA</a>
    </div>

    <div class="popup-container">
        <!-- Nama sebagai tajuk -->
        <h1 class="anggota-name text-center"><?php echo htmlspecialchars($anggota['nama']); ?></h1>

        <!-- Gambar -->
        <div class="text-center">
            <?php
            $gambar_path = !empty($anggota['gambar']) ? $anggota['gambar'] : '';
            $gambar_full_path = "C:/xampp/htdocs" . $gambar_path;
            if (!empty($gambar_path) && file_exists($gambar_full_path)) {
            ?>
                <img src="<?php echo htmlspecialchars($gambar_path); ?>" alt="Gambar Anggota" class="anggota-img">
            <?php } else { ?>
                <img src="https://via.placeholder.com/150" alt="Tiada Gambar" class="anggota-img">
            <?php } ?>
        </div>

        <!-- Maklumat Anggota -->
        <div class="info-row row">
            <div class="col-sm-4 info-label">NO KOMPUTER:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['no_komputer']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">NO BADAN:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['no_badan']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">KAD PENGENALAN:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['kad_pengenalan']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">GAJI:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['gaji']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">JABATAN:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['jabatan']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">JAWATAN:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['jawatan']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">TARIKH MASUK KERJA:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['tarikh_masuk_kerja'] ?: 'TIADA'); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">UNIT/SEKSYEN:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['unit_seksyen']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">STATUS:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['status']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">PENEMPATAN:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['penempatan']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">ALAMAT:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['alamat']); ?></div>
        </div>
        <div class="info-row row">
            <div class="col-sm-4 info-label">NO TELEFON:</div>
            <div class="col-sm-8 info-value"><?php echo htmlspecialchars($anggota['no_telefon']); ?></div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>