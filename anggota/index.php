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

// Query untuk dropdown
$jawatan_result = $conn->query("SELECT id, nama FROM jawatan ORDER BY nama");
$unit_seksyen_result = $conn->query("SELECT id, nama FROM unit_seksyen ORDER BY nama");
$status_result = $conn->query("SELECT id, nama FROM status ORDER BY nama");
$penempatan_result = $conn->query("SELECT id, nama FROM penempatan ORDER BY nama");
$jabatan_result = $conn->query("SELECT id, nama_jabatan FROM jabatan ORDER BY nama_jabatan");
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DAFTAR ANGGOTA - SISTEM PENGUATKUASAAN</title>
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
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>SISTEM PENGUATKUASAAN</h1>
    </div>

    <div class="form-container">
        <h2 class="text-center mb-4">DAFTAR ANGGOTA BARU</h2>
        <form method="POST" action="./daftar_anggota.php" enctype="multipart/form-data">
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">NO KOMPUTER:</label>
                <div class="col-sm-8">
                    <input type="text" name="no_komputer" class="form-control" required>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">NO BADAN:</label>
                <div class="col-sm-8">
                    <input type="text" name="no_badan" class="form-control">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">NAMA:</label>
                <div class="col-sm-8">
                    <input type="text" name="nama" class="form-control" required>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">KAD PENGENALAN:</label>
                <div class="col-sm-8">
                    <input type="text" name="kad_pengenalan" class="form-control" id="kad_pengenalan" placeholder="123456-12-1234">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">NO TELEFON:</label>
                <div class="col-sm-8">
                    <input type="text" name="no_telefon" class="form-control" id="no_telefon" placeholder="01X-XXXXXXX">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">JAWATAN:</label>
                <div class="col-sm-8">
                    <select name="id_jawatan" class="form-control" required>
                        <option value="">PILIH JAWATAN</option>
                        <?php while ($row = $jawatan_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo strtoupper($row['nama']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">UNIT/SEKSYEN:</label>
                <div class="col-sm-8">
                    <select name="id_unit_seksyen" class="form-control" required>
                        <option value="">PILIH UNIT/SEKSYEN</option>
                        <?php while ($row = $unit_seksyen_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo strtoupper($row['nama']); ?></option>
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
                            <option value="<?php echo $row['id']; ?>"><?php echo strtoupper($row['nama']); ?></option>
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
                            <option value="<?php echo $row['id']; ?>"><?php echo strtoupper($row['nama']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">JABATAN:</label>
                <div class="col-sm-8">
                    <select name="id_jabatan" class="form-control">
                        <option value="">PILIH JABATAN</option>
                        <?php while ($row = $jabatan_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo strtoupper($row['nama_jabatan']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">TARIKH MASUK KERJA:</label>
                <div class="col-sm-8">
                    <input type="date" name="tarikh_masuk_kerja" class="form-control">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">ALAMAT:</label>
                <div class="col-sm-8">
                    <textarea name="alamat" class="form-control" placeholder="JALAN, TAMAN, POSKOD, DAERAH, NEGERI"></textarea>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">GAMBAR (PILIHAN):</label>
                <div class="col-sm-8">
                    <input type="file" name="gambar" class="form-control" id="gambar" accept="image/*">
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-success btn-custom"><i class="bi bi-save me-2"></i>SIMPAN</button>
                <a href="./senarai_anggota.php" class="btn btn-secondary btn-custom"><i class="bi bi-x-circle me-2"></i>BATAL</a>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
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