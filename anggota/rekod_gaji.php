<?php
include_once '../session_check.php';

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

if (!$no_komputer) {
    echo "<script>alert('No komputer tidak ditemui.'); window.location='senarai_anggota.php';</script>";
    exit;
}

// Ambil nama anggota untuk paparan
$sql_anggota = "SELECT nama FROM anggota WHERE no_komputer = ?";
$stmt_anggota = $conn->prepare($sql_anggota);
$stmt_anggota->bind_param("s", $no_komputer);
$stmt_anggota->execute();
$result_anggota = $stmt_anggota->get_result();
$anggota = $result_anggota->fetch_assoc();
$stmt_anggota->close();

if (!$anggota) {
    echo "<script>alert('Data anggota tidak ditemui.'); window.location='senarai_anggota.php';</script>";
    exit;
}

// Proses tambah rekod gaji
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah'])) {
    $gaji = filter_input(INPUT_POST, 'gaji', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $tarikh_mula = filter_input(INPUT_POST, 'tarikh_mula', FILTER_SANITIZE_STRING);
    $tarikh_tamat = filter_input(INPUT_POST, 'tarikh_tamat', FILTER_SANITIZE_STRING);

    // Validasi input
    if (empty($gaji) || empty($tarikh_mula)) {
        echo "<script>alert('Sila isi semua medan yang diperlukan.');</script>";
    } else {
        // Semak jika ada rekod gaji yang masih aktif (tarikh_tamat = NULL)
        $check_active = $conn->prepare("SELECT id FROM sejarah_gaji WHERE no_komputer = ? AND tarikh_tamat IS NULL");
        $check_active->bind_param("s", $no_komputer);
        $check_active->execute();
        $active_result = $check_active->get_result();
        if ($active_result->num_rows > 0) {
            // Tamatkan rekod gaji aktif
            $update_active = $conn->prepare("UPDATE sejarah_gaji SET tarikh_tamat = ? WHERE no_komputer = ? AND tarikh_tamat IS NULL");
            $update_active->bind_param("ss", $tarikh_mula, $no_komputer);
            $update_active->execute();
            $update_active->close();
        }
        $check_active->close();

        // Tambah rekod gaji baru
        $sql_tambah = "INSERT INTO sejarah_gaji (no_komputer, gaji, tarikh_mula, tarikh_tamat) VALUES (?, ?, ?, ?)";
        $stmt_tambah = $conn->prepare($sql_tambah);
        $tarikh_tamat = empty($tarikh_tamat) ? NULL : $tarikh_tamat;
        $stmt_tambah->bind_param("sdss", $no_komputer, $gaji, $tarikh_mula, $tarikh_tamat);
        if ($stmt_tambah->execute()) {
            // Simpan log aktiviti
            $log_sql = "INSERT INTO log_aktiviti (aktiviti, tarikh_masa, no_komputer) VALUES (?, NOW(), ?)";
            $log_stmt = $conn->prepare($log_sql);
            $aktiviti = "Menambah rekod gaji untuk $no_komputer pada tarikh $tarikh_mula";
            $log_stmt->bind_param("ss", $aktiviti, $no_komputer);
            $log_stmt->execute();
            $log_stmt->close();

            echo "<script>alert('Rekod gaji berjaya ditambah.'); window.location='rekod_gaji.php?no_komputer=" . urlencode($no_komputer) . "';</script>";
        } else {
            echo "<script>alert('Ralat menambah rekod: " . $conn->error . "');</script>";
        }
        $stmt_tambah->close();
    }
}

// Proses kemas kini rekod gaji
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kemas_kini'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $gaji = filter_input(INPUT_POST, 'gaji', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $tarikh_mula = filter_input(INPUT_POST, 'tarikh_mula', FILTER_SANITIZE_STRING);
    $tarikh_tamat = filter_input(INPUT_POST, 'tarikh_tamat', FILTER_SANITIZE_STRING);

    // Validasi input
    if (empty($gaji) || empty($tarikh_mula)) {
        echo "<script>alert('Sila isi semua medan yang diperlukan.');</script>";
    } else {
        $sql_kemas_kini = "UPDATE sejarah_gaji SET gaji = ?, tarikh_mula = ?, tarikh_tamat = ? WHERE id = ? AND no_komputer = ?";
        $stmt_kemas_kini = $conn->prepare($sql_kemas_kini);
        $tarikh_tamat = empty($tarikh_tamat) ? NULL : $tarikh_tamat;
        $stmt_kemas_kini->bind_param("dssis", $gaji, $tarikh_mula, $tarikh_tamat, $id, $no_komputer);
        if ($stmt_kemas_kini->execute()) {
            // Simpan log aktiviti
            $log_sql = "INSERT INTO log_aktiviti (aktiviti, tarikh_masa, no_komputer) VALUES (?, NOW(), ?)";
            $log_stmt = $conn->prepare($log_sql);
            $aktiviti = "Mengemas kini rekod gaji untuk $no_komputer pada tarikh $tarikh_mula";
            $log_stmt->bind_param("ss", $aktiviti, $no_komputer);
            $log_stmt->execute();
            $log_stmt->close();

            echo "<script>alert('Rekod gaji berjaya dikemas kini.'); window.location='rekod_gaji.php?no_komputer=" . urlencode($no_komputer) . "';</script>";
        } else {
            echo "<script>alert('Ralat mengemas kini rekod: " . $conn->error . "');</script>";
        }
        $stmt_kemas_kini->close();
    }
}

// Proses padam rekod gaji
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['padam'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

    $sql_padam = "DELETE FROM sejarah_gaji WHERE id = ? AND no_komputer = ?";
    $stmt_padam = $conn->prepare($sql_padam);
    $stmt_padam->bind_param("is", $id, $no_komputer);
    if ($stmt_padam->execute()) {
        // Simpan log aktiviti
        $log_sql = "INSERT INTO log_aktiviti (aktiviti, tarikh_masa, no_komputer) VALUES (?, NOW(), ?)";
        $log_stmt = $conn->prepare($log_sql);
        $aktiviti = "Memadam rekod gaji untuk $no_komputer";
        $log_stmt->bind_param("ss", $aktiviti, $no_komputer);
        $log_stmt->execute();
        $log_stmt->close();

        echo "<script>alert('Rekod gaji berjaya dipadam.'); window.location='rekod_gaji.php?no_komputer=" . urlencode($no_komputer) . "';</script>";
    } else {
        echo "<script>alert('Ralat memadam: " . $conn->error . "');</script>";
    }
    $stmt_padam->close();
}

// Ambil senarai rekod gaji, susun mengikut tarikh_mula menaik (ASC)
$sql_rekod = "SELECT id, gaji, tarikh_mula, tarikh_tamat FROM sejarah_gaji WHERE no_komputer = ? ORDER BY tarikh_mula ASC";
$stmt_rekod = $conn->prepare($sql_rekod);
$stmt_rekod->bind_param("s", $no_komputer);
$stmt_rekod->execute();
$rekod_gaji = $stmt_rekod->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REKOD KENAIKAN GAJI - <?php echo htmlspecialchars(strtoupper($anggota['nama'])); ?> - SISTEM PENGUATKUASAAN</title>
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
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border: 2px solid #0d1a40;
        }
        h2 {
            color: #0d1a40;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            font-weight: bold;
            text-transform: uppercase;
        }
        .form-label {
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
        }
        .form-control {
            border: 2px solid #0d1a40;
            border-radius: 8px;
            padding: 10px;
            transition: border-color 0.3s ease;
            text-transform: uppercase;
        }
        .form-control:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 0 0.2rem rgba(29, 58, 138, 0.25);
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
        .btn-success {
            background-color: #28a745;
            border: none;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-warning {
            background-color: #ffc107;
            border: none;
            color: #0d1a40;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .btn-danger {
            background-color: #dc3545;
            border: none;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .table {
            margin-top: 20px;
            border: 2px solid #0d1a40;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
        .table th {
            background-color: #0d1a40;
            color: white;
            text-transform: uppercase;
        }
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>SISTEM PENGUATKUASAAN</h1>
    </div>

    <div class="container">
        <h2 class="text-center">REKOD KENAIKAN GAJI - <?php echo htmlspecialchars(strtoupper($anggota['nama'])); ?></h2>

        <!-- Borang Tambah Rekod Gaji -->
        <h4 class="mt-4">Tambah Rekod Gaji Baru</h4>
        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">GAJI (RM):</label>
                    <input type="number" step="0.01" name="gaji" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">TARIKH MULA:</label>
                    <input type="date" name="tarikh_mula" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">TARIKH TAMAT:</label>
                    <input type="date" name="tarikh_tamat" class="form-control">
                </div>
            </div>
            <div class="text-center">
                <button type="submit" name="tambah" class="btn btn-success btn-custom"><i class="bi bi-plus-circle me-2"></i>TAMBAH</button>
            </div>
        </form>

        <!-- Senarai Rekod Gaji -->
        <h4 class="mt-5">Senarai Rekod Gaji</h4>
        <?php if ($rekod_gaji->num_rows > 0) { ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>BIL</th>
                        <th>GAJI (RM)</th>
                        <th>TARIKH MULA</th>
                        <th>TARIKH TAMAT</th>
                        <th>TINDAKAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $bil = 1; while ($row = $rekod_gaji->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $bil++; ?></td>
                            <td><?php echo number_format($row['gaji'], 2); ?></td>
                            <td>
                                <?php 
                                // Format TARIKH MULA ke dd/mm/yyyy
                                $tarikh_mula = DateTime::createFromFormat('Y-m-d', $row['tarikh_mula']);
                                echo $tarikh_mula ? $tarikh_mula->format('d/m/Y') : $row['tarikh_mula'];
                                ?>
                            </td>
                            <td>
                                <?php 
                                // Format TARIKH TAMAT ke dd/mm/yyyy, atau papar "GAJI TERKINI" jika NULL
                                if ($row['tarikh_tamat']) {
                                    $tarikh_tamat = DateTime::createFromFormat('Y-m-d', $row['tarikh_tamat']);
                                    echo $tarikh_tamat ? $tarikh_tamat->format('d/m/Y') : $row['tarikh_tamat'];
                                } else {
                                    echo 'GAJI TERKINI';
                                }
                                ?>
                            </td>
                            <td>
                                <!-- Butang Edit (Modal) -->
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                    <i class="bi bi-pencil"></i> EDIT
                                </button>
                                <!-- Butang Padam -->
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="padam" class="btn btn-danger btn-sm" onclick="return confirm('Adakah anda pasti untuk memadam rekod ini?');">
                                        <i class="bi bi-trash"></i> PADAM
                                    </button>
                                </form>

                                <!-- Modal untuk Edit -->
                                <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editModalLabel<?php echo $row['id']; ?>">Kemas Kini Rekod Gaji</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">GAJI (RM):</label>
                                                        <input type="number" step="0.01" name="gaji" class="form-control" value="<?php echo $row['gaji']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">TARIKH MULA:</label>
                                                        <input type="date" name="tarikh_mula" class="form-control" value="<?php echo $row['tarikh_mula']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">TARIKH TAMAT:</label>
                                                        <input type="date" name="tarikh_tamat" class="form-control" value="<?php echo $row['tarikh_tamat']; ?>">
                                                    </div>
                                                    <div class="text-center">
                                                        <button type="submit" name="kemas_kini" class="btn btn-primary btn-custom"><i class="bi bi-save me-2"></i>KEMAS KINI</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="text-center">Tiada rekod gaji untuk anggota ini.</p>
        <?php } ?>

        <!-- Butang Navigasi -->
        <div class="btn-group">
            <a href="paparan_anggota.php?no_komputer=<?php echo urlencode($no_komputer); ?>" class="btn btn-secondary btn-custom"><i class="bi bi-arrow-left me-2"></i>KEMBALI KE PAPARAN <?php echo htmlspecialchars(strtoupper($anggota['nama'])); ?></a>
            <a href="menu.php" class="btn btn-primary btn-custom"><i class="bi bi-house me-2"></i>KE HALAMAN UTAMA</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$stmt_rekod->close();
$conn->close();
?>