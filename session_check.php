<?php
session_start();

// Semak jika pengguna belum log masuk
if (!isset($_SESSION['masuk']) || $_SESSION['masuk'] !== true) {
    header("Location: http://localhost/penguatkuasa/login.php");
    exit();
}

// Sekatan berdasarkan peranan (jika ada peranan dalam sistem)
if (isset($_SESSION['peranan'])) {
    $current_script = $_SERVER['SCRIPT_NAME'];

    // Sekat akses ke folder 'klm' jika bukan peranan 'klm'
    if (strpos($current_script, '/klm/') !== false && $_SESSION['peranan'] !== 'klm') {
        header("Location: http://localhost/penguatkuasa/anggota/menu.php");
        exit();
    }

    // Sekat akses ke folder 'anggota' jika bukan peranan 'anggota'
    if (strpos($current_script, '/anggota/') !== false && $_SESSION['peranan'] !== 'anggota') {
        header("Location: http://localhost/penguatkuasa/klm/borang_klm.php");
        exit();
    }
}
?>