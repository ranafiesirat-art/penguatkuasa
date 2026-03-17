<?php
session_start();
if (!isset($_SESSION['masuk']) || $_SESSION['masuk'] !== true) {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Sistem Penguatkuasaan </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e9ecef, #d6eaff);
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            color: #000000;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0.05;
            z-index: -1;
        }
        .header {
            background: linear-gradient(90deg, #0d1a40, #1e3a8a);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .menu-container {
            max-width: 1200px;
            margin: 100px auto 40px auto;
            padding: 30px;
        }
        h2 {
            color: #0d1a40;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
            font-weight: bold;
            margin-bottom: 40px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .menu-card {
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
            border: 2px solid #0d1a40;
            position: relative;
            overflow: hidden;
        }
        .menu-card:hover:not(.disabled) {
            transform: scale(1.08);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            background-color: #f8f9fa;
        }
        .menu-card.disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .menu-card .btn {
            width: 100%;
            padding: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 8px;
            border: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
            color: white;
        }
        .menu-card .btn-primary {
            background-color: #000000;
        }
        .menu-card .btn-primary:hover {
            background-color: #444444;
            transform: translateY(-2px);
        }
        .menu-card .btn-secondary {
            background-color: #000000;
        }
        .menu-card .btn-secondary:hover {
            background-color: #444444;
        }
        .menu-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #00ccff;
            transition: color 0.3s ease;
        }
        .menu-card:hover:not(.disabled) .icon {
            color: #ffcc00;
        }
        .menu-card.disabled .icon {
            color: #6c757d;
        }
        .image-container {
            text-align: center;
            padding: 20px;
        }
        .side-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            border: 2px solid #0d1a40;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease, opacity 0.3s ease;
            position: relative;
        }
        .side-image:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }
        .side-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            z-index: 1;
            transition: background 0.3s ease;
        }
        .side-image:hover::after {
            background: rgba(0, 0, 0, 0.4);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEM BAPAK NAPIE PUNYA</h1>
    </div>

    <div class="menu-container">
        <div class="row">
            <div class="col-md-4 image-container">
                <img src="/penguatkuasa/anggota/gambar/ENFORCEMENT1.jpg" alt="Gambar Samping" class="side-image">
                <img src="/penguatkuasa/anggota/gambar/ENFORCEMENT2.jpg" alt="Gambar Samping 2" class="side-image">
                <img src="/penguatkuasa/anggota/gambar/uniform.jpg" alt="Gambar Samping 3" class="side-image">
            </div>
            <div class="col-md-8">
                <h2 class="text-center">PUSAT PEMERINTAHAN</h2>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="menu-card disabled">
                            <i class="bi bi-person-plus-fill icon"></i>
                            <button class="btn btn-secondary" disabled>DAFTAR PAHLAWAN</button>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="menu-card">
                            <i class="bi bi-list-ul icon"></i>
                            <a href="senarai_anggota.php" class="btn btn-primary">SENARAI PAHLAWAN</a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="menu-card disabled">
                            <i class="bi bi-file-earmark-text icon"></i>
                            <button class="btn btn-secondary" disabled>LAPORAN</button>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="menu-card disabled">
                            <i class="bi bi-gear-fill icon"></i>
                            <button class="btn btn-secondary" disabled>TETAPAN</button>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="menu-card">
                            <i class="bi bi-file-earmark-check icon"></i>
                            <a href="../klm/senarai_anggota_klm.php" class="btn btn-primary">SENARAI ANGGOTA KLM</a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="menu-card">
                            <i class="bi bi-box-arrow-right icon"></i>
                            <a href="../logout.php" class="btn btn-primary">LOG KELUAR</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>