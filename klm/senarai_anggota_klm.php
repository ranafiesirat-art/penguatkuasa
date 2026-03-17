<?php
include_once '../session_check.php'; // Gunakan session_check.php di folder akar

// Sambungan ke database
$conn = new mysqli("localhost", "root", "", "penguatkuasa");
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// Query untuk senarai anggota yang telah mengisi borang KLM
$sql = "SELECT DISTINCT a.no_komputer, a.nama 
        FROM anggota a
        INNER JOIN klm_kerja k ON a.no_komputer = k.no_komputer 
        ORDER BY a.nama ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENARAI ANGGOTA KLM - SISTEM PENGUATKUASAAN</title>
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
            padding: 12px 15px;
            border: 1px solid #0d1a40;
            text-align: center;
            font-size: 0.95rem;
        }
        .table tbody tr {
            transition: background-color 0.3s ease;
        }
        .table tbody tr:hover {
            background-color: #f1f5f9;
        }
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border: 1px solid #dee2e6;
            font-size: 0.9rem;
            text-align: center;
        }
        .table td.align-left {
            text-align: left;
        }
        .nama-link {
            color: #0d1a40;
            text-decoration: none;
            font-weight: bold;
        }
        .nama-link:hover {
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
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEM PENGUATKUASAAN</h1>
    </div>
    <div class="container">
        <h2>SENARAI ANGGOTA BERTUGAS KERJA LEBIH MASA</h2>
        <?php if ($result->num_rows > 0) { ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>BIL</th>
                        <th>NO KOMPUTER</th>
                        <th>NAMA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $bil = 1;
                    while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $bil++; ?></td>
                            <td><?php echo htmlspecialchars(strtoupper($row['no_komputer'])); ?></td>
                            <td class="align-left">
                                <a href="./rekod_klm_anggota.php?no_komputer=<?php echo urlencode($row['no_komputer']); ?>" class="nama-link">
                                    <?php echo htmlspecialchars(strtoupper($row['nama'])); ?>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="no-data">TIADA ANGGOTA YANG TELAH MENGISI BORANG KLM</p>
        <?php } ?>
        <div class="btn-group">
            <a href="../anggota/menu.php" class="btn btn-primary btn-custom"><i class="bi bi-arrow-left me-2"></i>KEMBALI KE MENU</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>