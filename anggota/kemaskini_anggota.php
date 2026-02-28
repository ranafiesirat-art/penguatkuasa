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

// Query untuk ambil data anggota
$sql = "SELECT * FROM anggota WHERE no_komputer = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $no_komputer);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Anggota tidak wujud.'); window.location.href='./senarai_anggota.php';</script>";
    exit();
}

$anggota = $result->fetch_assoc();

// Proses kemaskini data bila form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = filter_input(INPUT_POST, 'nama', FILTER_SANITIZE_STRING);
    $no_badan = filter_input(INPUT_POST, 'no_badan', FILTER_SANITIZE_STRING);
    $kad_pengenalan = filter_input(INPUT_POST, 'kad_pengenalan', FILTER_SANITIZE_STRING);
    $gaji = filter_input(INPUT_POST, 'gaji', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $id_jabatan = filter_input(INPUT_POST, 'id_jabatan', FILTER_SANITIZE_NUMBER_INT);
    $no_telefon = filter_input(INPUT_POST, 'no_telefon', FILTER_SANITIZE_STRING);
    $tarikh_masuk_kerja = filter_input(INPUT_POST, 'tarikh_masuk_kerja', FILTER_SANITIZE_STRING);
    $alamat = filter_input(INPUT_POST, 'alamat', FILTER_SANITIZE_STRING);
    $id_jawatan = filter_input(INPUT_POST, 'id_jawatan', FILTER_SANITIZE_NUMBER_INT);
    $id_unit_seksyen = filter_input(INPUT_POST, 'id_unit_seksyen', FILTER_SANITIZE_NUMBER_INT);
    $id_status = filter_input(INPUT_POST, 'id_status', FILTER_SANITIZE_NUMBER_INT);
    $id_penempatan = filter_input(INPUT_POST, 'id_penempatan', FILTER_SANITIZE_NUMBER_INT);

    // Validasi Kad Pengenalan (jika diisi)
    if (!empty($kad_pengenalan) && !preg_match('/^\d{6}-\d{2}-\d{4}$/', $kad_pengenalan)) {
        echo "<script>alert('Kad Pengenalan tidak sah. Format: 123456-12-1234');</script>";
        exit();
    }

    // Validasi Gaji (jika diisi)
    if (!empty($gaji) && ($gaji < 0 || !is_numeric($gaji))) {
        echo "<script>alert('Gaji tidak sah. Sila masukkan nombor positif.');</script>";
        exit();
    }

    // Validasi No Telefon (jika diisi)
    if (!empty($no_telefon) && !preg_match('/^01[0-9]-[0-9]{7,8}$/', $no_telefon)) {
        echo "<script>alert('No Telefon tidak sah. Format: 01X-XXXXXXX');</script>";
        exit();
    }

    // Tambah Logik untuk Mengurus Sejarah Gaji
    if ($gaji != $anggota['gaji']) {
        // Tamatkan rekod gaji lama dalam sejarah_gaji
        $sql_tamat_gaji = "UPDATE sejarah_gaji SET tarikh_tamat = CURDATE() WHERE no_komputer = ? AND tarikh_tamat IS NULL";
        $stmt_tamat_gaji = $conn->prepare($sql_tamat_gaji);
        $stmt_tamat_gaji->bind_param("s", $no_komputer);
        $stmt_tamat_gaji->execute();
        $stmt_tamat_gaji->close();

        // Tambah rekod gaji baru ke sejarah_gaji
        $sql_sejarah_gaji = "INSERT INTO sejarah_gaji (no_komputer, gaji, tarikh_mula, tarikh_tamat) VALUES (?, ?, CURDATE(), NULL)";
        $stmt_sejarah_gaji = $conn->prepare($sql_sejarah_gaji);
        $stmt_sejarah_gaji->bind_param("sd", $no_komputer, $gaji);
        $stmt_sejarah_gaji->execute();
        $stmt_sejarah_gaji->close();
    }

    // Proses upload gambar (jika ada gambar baru)
    $gambar = $anggota['gambar']; // Kekalkan gambar lama jika tiada gambar baru
    if (!empty($_FILES['gambar']['name'])) {
        $target_dir = "C:/xampp/htdocs/penguatkuasa/anggota/uploads/";
        $file_extension = pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION);
        $gambar_filename = uniqid() . '.' . $file_extension;
        $gambar_full_path = $target_dir . $gambar_filename;
        $gambar_relative_path = "/penguatkuasa/anggota/uploads/" . $gambar_filename;
        $imageFileType = strtolower($file_extension);

        $check = getimagesize($_FILES["gambar"]["tmp_name"]);
        if ($check !== false) {
            if ($_FILES["gambar"]["size"] <= 5000000) { // 5MB
                if (in_array($imageFileType, ["jpg", "png", "jpeg"])) {
                    // Hapus gambar lama jika ada
                    if (!empty($anggota['gambar'])) {
                        $gambar_lama_path = "C:/xampp/htdocs" . $anggota['gambar'];
                        if (file_exists($gambar_lama_path)) {
                            unlink($gambar_lama_path);
                        }
                    }
                    // Upload gambar baru
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $gambar_full_path)) {
                        $gambar = $gambar_relative_path;
                    } else {
                        echo "<script>alert('Ralat memuat naik gambar.');</script>";
                    }
                } else {
                    echo "<script>alert('Hanya fail JPG, JPEG, PNG dibenarkan.');</script>";
                }
            } else {
                echo "<script>alert('Gambar terlalu besar. Maksimum 5MB.');</script>";
            }
        } else {
            echo "<script>alert('Fail bukan gambar.');</script>";
        }
    }

    // SQL untuk kemaskini data dalam table anggota
    $sql_update = "UPDATE anggota SET nama=?, no_badan=?, kad_pengenalan=?, gaji=?, id_jabatan=?, no_telefon=?, tarikh_masuk_kerja=?, alamat=?, id_jawatan=?, id_unit_seksyen=?, id_status=?, id_penempatan=?, gambar=? WHERE no_komputer=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssdissssiiiss", $nama, $no_badan, $kad_pengenalan, $gaji, $id_jabatan, $no_telefon, $tarikh_masuk_kerja, $alamat, $id_jawatan, $id_unit_seksyen, $id_status, $id_penempatan, $gambar, $no_komputer);

    if ($stmt_update->execute()) {
        // Semak jika ada perubahan pada unit/seksyen, jawatan, atau status
        if ($anggota['id_unit_seksyen'] != $id_unit_seksyen || $anggota['id_jawatan'] != $id_jawatan || $anggota['id_status'] != $id_status) {
            // Tamatkan rekod lama dalam sejarah_penempatan
            $sql_tamat = "UPDATE sejarah_penempatan SET tarikh_tamat = CURDATE() WHERE no_komputer = ? AND tarikh_tamat IS NULL";
            $stmt_tamat = $conn->prepare($sql_tamat);
            $stmt_tamat->bind_param("s", $no_komputer);
            $stmt_tamat->execute();
            $stmt_tamat->close();

            // Tambah rekod baru ke sejarah_penempatan
            $sql_sejarah = "INSERT INTO sejarah_penempatan (no_komputer, id_unit_seksyen, tarikh_lapor, tarikh_tamat, id_jawatan, id_status) VALUES (?, ?, CURDATE(), NULL, ?, ?)";
            $stmt_sejarah = $conn->prepare($sql_sejarah);
            $stmt_sejarah->bind_param("siii", $no_komputer, $id_unit_seksyen, $id_jawatan, $id_status);
            $stmt_sejarah->execute();
            $stmt_sejarah->close();
        }

        // Simpan log aktiviti
        $log_sql = "INSERT INTO log_aktiviti (aktiviti, tarikh_masa, no_komputer) VALUES (?, NOW(), ?)";
        $log_stmt = $conn->prepare($log_sql);
        $aktiviti = "Mengemaskini maklumat anggota: $nama";
        $log_stmt->bind_param("ss", $aktiviti, $no_komputer);
        $log_stmt->execute();
        $log_stmt->close();

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berjaya!',
                    text: 'Maklumat anggota berjaya dikemaskini.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href='./paparan_anggota.php?no_komputer=" . urlencode($no_komputer) . "';
                });
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Ralat!',
                    text: 'Ralat semasa mengemaskini maklumat.',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    }
    $stmt_update->close();
}

// Query untuk dropdown
$jawatan_result = $conn->query("SELECT id, nama FROM jawatan");
$unit_seksyen_result = $conn->query("SELECT id, nama FROM unit_seksyen");
$status_result = $conn->query("SELECT id, nama FROM status");
$penempatan_result = $conn->query("SELECT id, nama FROM penempatan");
$jabatan_result = $conn->query("SELECT id, nama_jabatan FROM jabatan");
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KEMASKINI ANGGOTA: <?php echo htmlspecialchars(strtoupper($no_komputer)); ?> - SISTEM PENGUATKUASAAN</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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
        }
        .form-container {
            max-width: 800px;
            margin: 80px auto 40px auto;
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
        }
        .form-group.row {
            margin-bottom: 20px;
            align-items: center;
        }
        .form-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0;
            text-transform: uppercase;
        }
        .form-control {
            border: 2px solid #0d1a40;
            border-radius: 8px;
            padding: 10px;
            transition: border-color 0.3s ease;
            width: 100%;
            text-transform: uppercase;
        }
        .form-control:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 0 0.2rem rgba(29, 58, 138, 0.25);
        }
        .preview-img {
            max-width: 200px;
            margin-top: 10px;
            display: none;
            border-radius: 10px;
            border: 2px solid #0d1a40;
        }
        .current-img {
            max-width: 200px;
            margin-top: 10px;
            border-radius: 10px;
            border: 2px solid #0d1a40;
        }
        .btn-custom {
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 8px;
            transition: transform 0.1s ease, background-color 0.3s ease;
            min-width: 150px;
            text-align: center;
        }
        .btn-custom:hover {
            transform: scale(1.05);
        }
        .btn-success {
            background-color: #0d1a40;
            border: none;
        }
        .btn-success:hover {
            background-color: #1e3a8a;
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
            flex-wrap: nowrap;
            margin-top: 20px;
        }
        .input-group-text {
            background-color: #0d1a40;
            color: white;
            border: 2px solid #0d1a40;
            border-radius: 8px 0 0 8px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>SISTEM PENGUATKUASAAN</h1>
    </div>

    <div class="form-container">
        <h2 class="text-center mb-4">KEMASKINI MAKLUMAT ANGGOTA</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?no_komputer=' . urlencode($no_komputer); ?>" enctype="multipart/form-data">
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">NO KOMPUTER:</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(strtoupper($anggota['no_komputer'])); ?>" disabled>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">NAMA:</label>
                <div class="col-sm-8">
                    <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars(strtoupper($anggota['nama'])); ?>" required>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">NO BADAN:</label>
                <div class="col-sm-8">
                    <input type="text" name="no_badan" class="form-control" value="<?php echo htmlspecialchars(strtoupper($anggota['no_badan'])); ?>">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">KAD PENGENALAN:</label>
                <div class="col-sm-8">
                    <input type="text" name="kad_pengenalan" class="form-control" id="kad_pengenalan" value="<?php echo htmlspecialchars(strtoupper($anggota['kad_pengenalan'])); ?>" placeholder="123456-12-1234">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">GAJI:</label>
                <div class="col-sm-8">
                    <div class="input-group">
                        <span class="input-group-text">RM</span>
                        <input type="number" name="gaji" class="form-control" step="0.01" value="<?php echo htmlspecialchars($anggota['gaji']); ?>" placeholder="Contoh: 3015.22">
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">JABATAN:</label>
                <div class="col-sm-8">
                    <select name="id_jabatan" class="form-control">
                        <option value="">PILIH JABATAN</option>
                        <?php while ($row = $jabatan_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($row['id'] == $anggota['id_jabatan']) echo 'selected'; ?>><?php echo strtoupper($row['nama_jabatan']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">NO TELEFON:</label>
                <div class="col-sm-8">
                    <input type="text" name="no_telefon" class="form-control" id="no_telefon" value="<?php echo htmlspecialchars(strtoupper($anggota['no_telefon'])); ?>" placeholder="01X-XXXXXXX">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">TARIKH MASUK KERJA:</label>
                <div class="col-sm-8">
                    <input type="date" name="tarikh_masuk_kerja" class="form-control" value="<?php echo htmlspecialchars($anggota['tarikh_masuk_kerja']); ?>">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">ALAMAT:</label>
                <div class="col-sm-8">
                    <textarea name="alamat" class="form-control"><?php echo htmlspecialchars(strtoupper($anggota['alamat'])); ?></textarea>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">GAMBAR SEKARANG:</label>
                <div class="col-sm-8">
                    <?php if (!empty($anggota['gambar'])) { ?>
                        <img src="<?php echo htmlspecialchars($anggota['gambar']); ?>" alt="Gambar Sekarang" class="current-img">
                    <?php } else { ?>
                        <p>TIADA GAMBAR</p>
                    <?php } ?>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">GAMBAR BARU (PILIHAN):</label>
                <div class="col-sm-8">
                    <input type="file" name="gambar" class="form-control" id="gambar" accept="image/*">
                    <img id="preview" class="preview-img" src="#" alt="Pratonton Gambar">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">JAWATAN:</label>
                <div class="col-sm-8">
                    <select name="id_jawatan" class="form-control" required>
                        <option value="">PILIH JAWATAN</option>
                        <?php while ($row = $jawatan_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($row['id'] == $anggota['id_jawatan']) echo 'selected'; ?>><?php echo strtoupper($row['nama']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">UNIT/SEKSYEN:</label>
                <div class="col-sm-8">
                    <select name="id_unit_seksyen" class="form-control" required>
                        <option value="">PILIH UNIT/SEKSYEN</option>
                        <?php
                        $unit_seksyen_result->data_seek(0);
                        while ($row = $unit_seksyen_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($row['id'] == $anggota['id_unit_seksyen']) echo 'selected'; ?>><?php echo strtoupper($row['nama']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">STATUS:</label>
                <div class="col-sm-8">
                    <select name="id_status" class="form-control" required>
                        <option value="">PILIH STATUS</option>
                        <?php while ($row = $status_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($row['id'] == $anggota['id_status']) echo 'selected'; ?>><?php echo strtoupper($row['nama']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">PENEMPATAN:</label>
                <div class="col-sm-8">
                    <select name="id_penempatan" class="form-control" required>
                        <option value="">PILIH PENEMPATAN</option>
                        <?php while ($row = $penempatan_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($row['id'] == $anggota['id_penempatan']) echo 'selected'; ?>><?php echo strtoupper($row['nama']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-success btn-custom"><i class="bi bi-save me-2"></i>SIMPAN</button>
                <a href="./paparan_anggota.php?no_komputer=<?php echo urlencode($no_komputer); ?>" class="btn btn-secondary btn-custom"><i class="bi bi-x-circle me-2"></i>BATAL</a>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Pratonton gambar
        document.getElementById('gambar').addEventListener('change', function(event) {
            const preview = document.getElementById('preview');
            const file = event.target.files[0];
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });

        // Auto-format Kad Pengenalan
        document.getElementById('kad_pengenalan').addEventListener('input', function(event) {
            let value = event.target.value.replace(/\D/g, '');
            if (value.length > 6) value = value.substring(0, 6) + '-' + value.substring(6);
            if (value.length > 9) value = value.substring(0, 9) + '-' + value.substring(9);
            event.target.value = value.substring(0, 14);
        });

        // Auto-format No Telefon
        document.getElementById('no_telefon').addEventListener('input', function(event) {
            let value = event.target.value.replace(/\D/g, '');
            if (value.length > 3) value = value.substring(0, 3) + '-' + value.substring(3);
            event.target.value = value.substring(0, 12);
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>