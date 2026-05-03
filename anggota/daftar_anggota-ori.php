<?php
include_once '../session_check.php'; // Gantikan semakan sesi manual dengan fail di akar

// Sambungan ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "penguatkuasa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// Proses data borang apabila "SIMPAN" diklik
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitasi input
    $no_komputer = filter_input(INPUT_POST, 'no_komputer', FILTER_SANITIZE_STRING);
    $no_badan = filter_input(INPUT_POST, 'no_badan', FILTER_SANITIZE_STRING);
    $nama = filter_input(INPUT_POST, 'nama', FILTER_SANITIZE_STRING);
    $kad_pengenalan = filter_input(INPUT_POST, 'kad_pengenalan', FILTER_SANITIZE_STRING);
    $gaji = filter_input(INPUT_POST, 'gaji', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $id_jabatan = filter_input(INPUT_POST, 'id_jabatan', FILTER_SANITIZE_NUMBER_INT);
    $no_telefon = filter_input(INPUT_POST, 'no_telefon', FILTER_SANITIZE_STRING);
    $tarikh_masuk_kerja = filter_input(INPUT_POST, 'tarikh_masuk_kerja', FILTER_SANITIZE_STRING);
    $alamat_no = filter_input(INPUT_POST, 'alamat_no', FILTER_SANITIZE_STRING);
    $alamat_jalan = filter_input(INPUT_POST, 'alamat_jalan', FILTER_SANITIZE_STRING);
    $alamat_taman = filter_input(INPUT_POST, 'alamat_taman', FILTER_SANITIZE_STRING);
    $poskod = filter_input(INPUT_POST, 'poskod', FILTER_SANITIZE_STRING);
    $daerah = filter_input(INPUT_POST, 'daerah', FILTER_SANITIZE_STRING);
    $negeri = filter_input(INPUT_POST, 'negeri', FILTER_SANITIZE_STRING);
    $id_jawatan = filter_input(INPUT_POST, 'id_jawatan', FILTER_SANITIZE_NUMBER_INT);
    $id_unit_seksyen = filter_input(INPUT_POST, 'id_unit_seksyen', FILTER_SANITIZE_NUMBER_INT);
    $id_status = filter_input(INPUT_POST, 'id_status', FILTER_SANITIZE_NUMBER_INT);
    $id_penempatan = filter_input(INPUT_POST, 'id_penempatan', FILTER_SANITIZE_NUMBER_INT);

    // Validasi No Komputer (contoh: maksimum 10 aksara, hanya nombor dan huruf)
    if (!preg_match('/^[a-zA-Z0-9]{1,10}$/', $no_komputer)) {
        echo "<script>alert('No Komputer tidak sah. Hanya nombor dan huruf dibenarkan, maksimum 10 aksara.');</script>";
        exit();
    }

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

    // Buat alamat kosong jika tiada input
    $alamat = (!empty($alamat_no) || !empty($alamat_jalan) || !empty($alamat_taman) || !empty($poskod) || !empty($daerah) || !empty($negeri))
        ? "$alamat_no, $alamat_jalan, $alamat_taman, $poskod $daerah, $negeri"
        : '';

    // Proses upload gambar (pilihan)
    $gambar = "";
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

    // SQL untuk masukkan data ke table anggota
    $sql = "INSERT INTO anggota (no_komputer, no_badan, nama, kad_pengenalan, gaji, id_jabatan, no_telefon, tarikh_masuk_kerja, alamat, gambar, id_jawatan, id_unit_seksyen, id_status, id_penempatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssdissssiiii", $no_komputer, $no_badan, $nama, $kad_pengenalan, $gaji, $id_jabatan, $no_telefon, $tarikh_masuk_kerja, $alamat, $gambar, $id_jawatan, $id_unit_seksyen, $id_status, $id_penempatan);

    try {
        if ($stmt->execute()) {
            // Tambah rekod pertama ke sejarah_penempatan
            $sql_sejarah = "INSERT INTO sejarah_penempatan (no_komputer, id_unit_seksyen, tarikh_lapor, tarikh_tamat, id_jawatan, id_status) VALUES (?, ?, ?, NULL, ?, ?)";
            $stmt_sejarah = $conn->prepare($sql_sejarah);
            $stmt_sejarah->bind_param("sisii", $no_komputer, $id_unit_seksyen, $tarikh_masuk_kerja, $id_jawatan, $id_status);
            $stmt_sejarah->execute();
            $stmt_sejarah->close();

            // Tambah rekod gaji ke sejarah_gaji jika gaji diisi
            if (!empty($gaji) && !empty($tarikh_masuk_kerja)) {
                $sql_sejarah_gaji = "INSERT INTO sejarah_gaji (no_komputer, gaji, tarikh_mula, tarikh_tamat) VALUES (?, ?, ?, NULL)";
                $stmt_sejarah_gaji = $conn->prepare($sql_sejarah_gaji);
                $stmt_sejarah_gaji->bind_param("sds", $no_komputer, $gaji, $tarikh_masuk_kerja);
                $stmt_sejarah_gaji->execute();
                $stmt_sejarah_gaji->close();
            }

            // Simpan log aktiviti
            $log_sql = "INSERT INTO log_aktiviti (aktiviti, tarikh_masa, no_komputer) VALUES (?, NOW(), ?)";
            $log_stmt = $conn->prepare($log_sql);
            $aktiviti = "Mendaftar anggota baru: $nama";
            $log_stmt->bind_param("ss", $aktiviti, $no_komputer);
            $log_stmt->execute();
            $log_stmt->close();

            // Berjaya, redirect dengan mesej
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berjaya!',
                        text: 'Anggota baru berjaya didaftarkan.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = './senarai_anggota.php';
                    });
                });
            </script>";
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ralat!',
                        text: 'No Komputer $no_komputer sudah wujud. Sila guna nombor lain.',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ralat!',
                        text: '" . addslashes($e->getMessage()) . "',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
        }
    }
    $stmt->close();
}

// Query untuk ambil data dari table lookup
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
    <title>Daftar Anggota - Sistem Penguatkuasaan</title>
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
            max-width: 900px;
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
        .form-label {
            font-weight: bold;
            color: #2c3e50;
        }
        .form-control {
            border: 2px solid #0d1a40;
            border-radius: 8px;
            padding: 10px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 0 0.2rem rgba(29, 58, 138, 0.25);
        }
        .form-group.row {
            margin-bottom: 20px;
        }
        .preview-img {
            max-width: 200px;
            margin-top: 10px;
            display: none;
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
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-navy {
            background-color: #0d1a40;
            border: none;
            color: white;
        }
        .btn-navy:hover {
            background-color: #1e3a8a;
            color: white;
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
        <h2 class="text-center mb-4">DAFTAR ANGGOTA BARU</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data" id="anggotaForm">
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">No Komputer (Wajib):</label>
                <div class="col-sm-8">
                    <input type="text" name="no_komputer" class="form-control" required maxlength="10">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">No Badan (Pilihan):</label>
                <div class="col-sm-8">
                    <input type="text" name="no_badan" class="form-control">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Nama (Wajib):</label>
                <div class="col-sm-8">
                    <input type="text" name="nama" class="form-control" required>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Kad Pengenalan (Pilihan):</label>
                <div class="col-sm-8">
                    <input type="text" name="kad_pengenalan" class="form-control" id="kad_pengenalan" placeholder="123456-12-1234">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Gaji (Pilihan):</label>
                <div class="col-sm-8">
                    <div class="input-group">
                        <span class="input-group-text">RM</span>
                        <input type="number" name="gaji" class="form-control" step="0.01" placeholder="Contoh: 3015.22">
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Jabatan (Pilihan):</label>
                <div class="col-sm-8">
                    <select name="id_jabatan" class="form-control">
                        <option value="">Pilih Jabatan</option>
                        <?php while ($row = $jabatan_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['nama_jabatan']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">No Telefon (Pilihan):</label>
                <div class="col-sm-8">
                    <input type="text" name="no_telefon" class="form-control" id="no_telefon" placeholder="01X-XXXXXXX">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Tarikh Masuk Kerja (Pilihan):</label>
                <div class="col-sm-8">
                    <input type="date" name="tarikh_masuk_kerja" class="form-control">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Jawatan Terkini (Wajib):</label>
                <div class="col-sm-8">
                    <select name="id_jawatan" class="form-control" id="id_jawatan" required>
                        <option value="">Pilih Jawatan</option>
                        <?php while ($row = $jawatan_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['nama']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Unit/Seksyen Terkini (Wajib):</label>
                <div class="col-sm-8">
                    <select name="id_unit_seksyen" class="form-control" id="id_unit_seksyen" required>
                        <option value="">Pilih Unit/Seksyen</option>
                        <?php
                        $unit_seksyen_result->data_seek(0); // Reset pointer
                        while ($row = $unit_seksyen_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['nama']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Status (Wajib):</label>
                <div class="col-sm-8">
                    <select name="id_status" class="form-control" required>
                        <option value="">Pilih Status</option>
                        <?php while ($row = $status_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['nama']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Penempatan (Wajib):</label>
                <div class="col-sm-8">
                    <select name="id_penempatan" class="form-control" required>
                        <option value="">Pilih Penempatan</option>
                        <?php while ($row = $penempatan_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['nama']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Alamat (Pilihan):</label>
                <div class="col-sm-8">
                    <div class="form-group row mb-2">
                        <label class="col-sm-3 col-form-label">No:</label>
                        <div class="col-sm-9">
                            <input type="text" name="alamat_no" class="form-control" placeholder="No">
                        </div>
                    </div>
                    <div class="form-group row mb-2">
                        <label class="col-sm-3 col-form-label">Jalan:</label>
                        <div class="col-sm-9">
                            <input type="text" name="alamat_jalan" class="form-control" placeholder="Jalan">
                        </div>
                    </div>
                    <div class="form-group row mb-2">
                        <label class="col-sm-3 col-form-label">Taman:</label>
                        <div class="col-sm-9">
                            <input type="text" name="alamat_taman" class="form-control" placeholder="Taman">
                        </div>
                    </div>
                    <div class="form-group row mb-2">
                        <label class="col-sm-3 col-form-label">Poskod:</label>
                        <div class="col-sm-9">
                            <input type="text" name="poskod" class="form-control" placeholder="Poskod">
                        </div>
                    </div>
                    <div class="form-group row mb-2">
                        <label class="col-sm-3 col-form-label">Daerah:</label>
                        <div class="col-sm-9">
                            <input type="text" name="daerah" class="form-control" placeholder="Daerah">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Negeri:</label>
                        <div class="col-sm-9">
                            <input type="text" name="negeri" class="form-control" placeholder="Negeri">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Gambar (Pilihan):</label>
                <div class="col-sm-8">
                    <input type="file" name="gambar" class="form-control" id="gambar" accept="image/*">
                    <img id="preview" class="preview-img" src="#" alt="Pratonton Gambar">
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-success btn-custom"><i class="bi bi-save me-2"></i>SIMPAN</button>
                <button type="reset" class="btn btn-secondary btn-custom"><i class="bi bi-arrow-repeat me-2"></i>RESET</button>
                <a href="./senarai_anggota.php" class="btn btn-primary btn-custom"><i class="bi bi-list-ul me-2"></i>SENARAI ANGGOTA</a>
                <a href="./menu.php" class="btn btn-navy btn-custom"><i class="bi bi-house-door me-2"></i>KE HALAMAN UTAMA</a>
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