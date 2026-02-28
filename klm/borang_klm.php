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

// Ambil no_komputer dari URL jika ada
$no_komputer = isset($_GET['no_komputer']) ? filter_input(INPUT_GET, 'no_komputer', FILTER_SANITIZE_STRING) : '';
$anggota = null;
if ($no_komputer) {
    $sql_anggota = "SELECT a.no_komputer, a.nama, a.kad_pengenalan, a.gaji, a.id_jawatan, a.id_jabatan, 
                    j.nama AS nama_jawatan, d.nama_jabatan AS nama_jabatan 
                    FROM anggota a 
                    LEFT JOIN jawatan j ON a.id_jawatan = j.id 
                    LEFT JOIN jabatan d ON a.id_jabatan = d.id 
                    WHERE a.no_komputer = ?";
    $stmt_anggota = $conn->prepare($sql_anggota);
    $stmt_anggota->bind_param("s", $no_komputer);
    $stmt_anggota->execute();
    $result_anggota = $stmt_anggota->get_result();
    $anggota = $result_anggota->fetch_assoc();
    $stmt_anggota->close();
}

// Query untuk dropdown
$anggota_result = $conn->query("SELECT a.no_komputer, a.nama, a.kad_pengenalan, a.gaji, a.id_jawatan, a.id_jabatan, 
                                j.nama AS nama_jawatan, d.nama_jabatan AS nama_jabatan 
                                FROM anggota a 
                                LEFT JOIN jawatan j ON a.id_jawatan = j.id 
                                LEFT JOIN jabatan d ON a.id_jabatan = d.id 
                                ORDER BY a.nama");

// Proses borang
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $no_komputer = filter_input(INPUT_POST, 'no_komputer', FILTER_SANITIZE_STRING);
    $id_jawatan = filter_input(INPUT_POST, 'id_jawatan', FILTER_SANITIZE_NUMBER_INT);
    $id_jabatan = filter_input(INPUT_POST, 'id_jabatan', FILTER_SANITIZE_NUMBER_INT);
    $kategori_hari = strtoupper(filter_input(INPUT_POST, 'kategori_hari', FILTER_SANITIZE_STRING));
    $tarikh = filter_input(INPUT_POST, 'tarikh', FILTER_SANITIZE_STRING);
    $masa_dari = filter_input(INPUT_POST, 'masa_dari', FILTER_SANITIZE_STRING);
    $masa_hingga = filter_input(INPUT_POST, 'masa_hingga', FILTER_SANITIZE_STRING);
    $kenyataan_tugasan = filter_input(INPUT_POST, 'kenyataan_tugasan', FILTER_SANITIZE_STRING);

    // Validasi input
    if (empty($no_komputer) || empty($id_jawatan) || empty($kategori_hari) || empty($tarikh) || empty($masa_dari) || empty($masa_hingga) || empty($kenyataan_tugasan)) {
        echo "<script>alert('Sila isi semua medan yang diperlukan.');</script>";
    } else {
        // Ambil gaji dari table sejarah_gaji berdasarkan tarikh KLM
        $gaji_query = $conn->prepare("SELECT gaji FROM sejarah_gaji WHERE no_komputer = ? AND tarikh_mula <= ? AND (tarikh_tamat >= ? OR tarikh_tamat IS NULL) ORDER BY tarikh_mula DESC LIMIT 1");
        $gaji_query->bind_param("sss", $no_komputer, $tarikh, $tarikh);
        $gaji_query->execute();
        $gaji_result = $gaji_query->get_result();
        $gaji = $gaji_result->num_rows > 0 ? $gaji_result->fetch_assoc()['gaji'] : 0;
        $gaji_query->close();

        if ($gaji == 0) {
            // Jika tiada rekod gaji dalam sejarah_gaji, ambil dari table anggota sebagai fallback
            $gaji_fallback_query = $conn->prepare("SELECT gaji FROM anggota WHERE no_komputer = ?");
            $gaji_fallback_query->bind_param("s", $no_komputer);
            $gaji_fallback_query->execute();
            $gaji_fallback_result = $gaji_fallback_query->get_result()->fetch_assoc();
            $gaji = $gaji_fallback_result['gaji'] ?? 0;
            $gaji_fallback_query->close();
        }

        // Debug message untuk memastikan gaji yang dipilih
        $debug_message = "Tarikh KLM: $tarikh\\n";
        $debug_message .= "Gaji yang dipilih: RM " . number_format($gaji, 2) . "\\n";

        // Kira jumlah jam
        $datetime_dari = new DateTime("$tarikh $masa_dari");
        $datetime_hingga = new DateTime("$tarikh $masa_hingga");
        $tarikh_asal = $tarikh;
        if ($datetime_hingga < $datetime_dari) {
            $datetime_hingga->modify('+1 day');
        }
        $interval = $datetime_dari->diff($datetime_hingga);
        $jumlah_jam = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
        $jumlah_jam_asal = $jumlah_jam;

        // Bahagikan jam mengikut kadar
        $jam_A = 0; $jam_B = 0; $jam_C = 0; $jam_D = 0; $jam_E = 0;
        $jam_A_asal = 0; $jam_B_asal = 0; $jam_C_asal = 0; $jam_D_asal = 0; $jam_E_asal = 0;
        $konstanta = 2504;
        $gaji_dikira = 0;

        $start = clone $datetime_dari;
        $end = clone $datetime_hingga;

        $time_6am = (clone $start)->setTime(6, 0);
        $time_10pm = (clone $start)->setTime(22, 0);
        $time_6am_next = (clone $start)->setTime(6, 0)->modify('+1 day');
        $maghrib_start = (clone $start)->setTime(19, 1); // 7:01 PM
        $maghrib_end = (clone $start)->setTime(19, 30);  // 7:30 PM

        // Bahagikan jam berdasarkan kategori hari
        if ($kategori_hari == 'BIASA') {
            if ($start < $time_6am) {
                $b_start = $start;
                $b_end = min($end, $time_6am);
                if ($b_start < $b_end) {
                    $interval_B = $b_start->diff($b_end);
                    $jam_B_asal = $interval_B->h + ($interval_B->i / 60);
                    $jam_B = $jam_B_asal;
                }
            }
            if ($end > $time_6am && $start < $time_10pm) {
                $a_start = max($start, $time_6am);
                $a_end = min($end, $time_10pm);
                if ($a_start < $a_end) {
                    $interval_A = $a_start->diff($a_end);
                    $jam_A_asal = $interval_A->h + ($interval_A->i / 60);
                    $jam_A = $jam_A_asal;
                }
            }
            if ($end > $time_10pm || $start >= $time_10pm) {
                $b_start = max($start, $time_10pm);
                $b_end = min($end, $time_6am_next);
                if ($b_start < $b_end) {
                    $interval_B = $b_start->diff($b_end);
                    $jam_B_asal += $interval_B->h + ($interval_B->i / 60);
                    $jam_B = $jam_B_asal;
                }
            }
            if ($end > $time_6am_next) {
                $a_start = $time_6am_next;
                $a_end = $end;
                if ($a_start < $a_end) {
                    $interval_A = $a_start->diff($a_end);
                    $jam_A_asal += $interval_A->h + ($interval_A->i / 60);
                    $jam_A = $jam_A_asal;
                }
            }
        } elseif ($kategori_hari == 'MINGGU') {
            if ($start < $time_6am) {
                $c_start = $start;
                $c_end = min($end, $time_6am);
                if ($c_start < $c_end) {
                    $interval_C = $c_start->diff($c_end);
                    $jam_C_asal = $interval_C->h + ($interval_C->i / 60);
                    $jam_C = $jam_C_asal;
                }
            }
            if ($end > $time_6am && $start < $time_10pm) {
                $b_start = max($start, $time_6am);
                $b_end = min($end, $time_10pm);
                if ($b_start < $b_end) {
                    $interval_B = $b_start->diff($b_end);
                    $jam_B_asal = $interval_B->h + ($interval_B->i / 60);
                    $jam_B = $jam_B_asal;
                }
            }
            if ($end > $time_10pm || $start >= $time_10pm) {
                $c_start = max($start, $time_10pm);
                $c_end = min($end, $time_6am_next);
                if ($c_start < $c_end) {
                    $interval_C = $c_start->diff($c_end);
                    $jam_C_asal += $interval_C->h + ($interval_C->i / 60);
                    $jam_C = $jam_C_asal;
                }
            }
            if ($end > $time_6am_next) {
                $b_start = $time_6am_next;
                $b_end = $end;
                if ($b_start < $b_end) {
                    $interval_B = $b_start->diff($b_end);
                    $jam_B_asal += $interval_B->h + ($interval_B->i / 60);
                    $jam_B = $jam_B_asal;
                }
            }
        } elseif ($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') {
            if ($start < $time_6am) {
                $e_start = $start;
                $e_end = min($end, $time_6am);
                if ($e_start < $e_end) {
                    $interval_E = $e_start->diff($e_end);
                    $jam_E_asal = $interval_E->h + ($interval_E->i / 60);
                    $jam_E = $jam_E_asal;
                }
            }
            if ($end > $time_6am && $start < $time_10pm) {
                $d_start = max($start, $time_6am);
                $d_end = min($end, $time_10pm);
                if ($d_start < $d_end) {
                    $interval_D = $d_start->diff($d_end);
                    $jam_D_asal = $interval_D->h + ($interval_D->i / 60);
                    $jam_D = $jam_D_asal;
                }
            }
            if ($end > $time_10pm || $start >= $time_10pm) {
                $e_start = max($start, $time_10pm);
                $e_end = min($end, $time_6am_next);
                if ($e_start < $e_end) {
                    $interval_E = $e_start->diff($e_end);
                    $jam_E_asal += $interval_E->h + ($interval_E->i / 60);
                    $jam_E = $jam_E_asal;
                }
            }
            if ($end > $time_6am_next) {
                $d_start = $time_6am_next;
                $d_end = $end;
                if ($d_start < $d_end) {
                    $interval_D = $d_start->diff($d_end);
                    $jam_D_asal += $interval_D->h + ($interval_D->i / 60);
                    $jam_D = $jam_D_asal;
                }
            }
        }

        // Syarat tambahan
        $langgar_maghrib = ($start < $maghrib_end && $end >= $maghrib_start);
        $ada_dua_kadar = ($jam_A_asal > 0 && $jam_B_asal > 0) || ($jam_B_asal > 0 && $jam_C_asal > 0) || ($jam_D_asal > 0 && $jam_E_asal > 0);

        // Syarat 1 & 2: Kurang 9 jam, satu kadar
        if ($jumlah_jam_asal < 9 && !$ada_dua_kadar) {
            if ($langgar_maghrib) {
                if ($kategori_hari == 'BIASA' && $jam_A_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_A = max(0, $jam_A - 0.5);
                elseif ($kategori_hari == 'BIASA' && $jam_B_asal > 0 && !$jam_A_asal) $jam_B = max(0, $jam_B - 0.5);
                elseif ($kategori_hari == 'MINGGU' && $jam_B_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_B = max(0, $jam_B - 0.5);
                elseif ($kategori_hari == 'MINGGU' && $jam_C_asal > 0 && !$jam_B_asal) $jam_C = max(0, $jam_C - 0.5);
                elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_D_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_D = max(0, $jam_D - 0.5);
                elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_E_asal > 0 && !$jam_D_asal) $jam_E = max(0, $jam_E - 0.5);
            }
        }

        // Syarat 3: Sama atau melebihi 9 jam, satu kadar
        if ($jumlah_jam_asal >= 9 && !$ada_dua_kadar) {
            if ($kategori_hari == 'BIASA' && $jam_A > 0) $jam_A = max(0, $jam_A - 1);
            elseif ($kategori_hari == 'BIASA' && $jam_B > 0) $jam_B = max(0, $jam_B - 1);
            elseif ($kategori_hari == 'MINGGU' && $jam_B > 0) $jam_B = max(0, $jam_B - 1);
            elseif ($kategori_hari == 'MINGGU' && $jam_C > 0) $jam_C = max(0, $jam_C - 1);
            elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_D > 0) $jam_D = max(0, $jam_D - 1);
            elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_E > 0) $jam_E = max(0, $jam_E - 1);

            if ($langgar_maghrib) {
                if ($kategori_hari == 'BIASA' && $jam_A_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_A = max(0, $jam_A - 0.5);
                elseif ($kategori_hari == 'BIASA' && $jam_B_asal > 0 && !$jam_A_asal) $jam_B = max(0, $jam_B - 0.5);
                elseif ($kategori_hari == 'MINGGU' && $jam_B_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_B = max(0, $jam_B - 0.5);
                elseif ($kategori_hari == 'MINGGU' && $jam_C_asal > 0 && !$jam_B_asal) $jam_C = max(0, $jam_C - 0.5);
                elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_D_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_D = max(0, $jam_D - 0.5);
                elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_E_asal > 0 && !$jam_D_asal) $jam_E = max(0, $jam_E - 0.5);
            }
        }

        // Syarat 4 & 5: Dua kadar
        if ($ada_dua_kadar) {
            if ($jumlah_jam_asal >= 9) { // Syarat 5
                if ($kategori_hari == 'BIASA') {
                    if ($jam_A > $jam_B) $jam_A = max(0, $jam_A - 1);
                    else $jam_B = max(0, $jam_B - 1);
                } elseif ($kategori_hari == 'MINGGU') {
                    if ($jam_B > $jam_C) $jam_B = max(0, $jam_B - 1);
                    else $jam_C = max(0, $jam_C - 1);
                } elseif ($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') {
                    if ($jam_D > $jam_E) $jam_D = max(0, $jam_D - 1);
                    else $jam_E = max(0, $jam_E - 1);
                }
                if ($langgar_maghrib) {
                    if ($kategori_hari == 'BIASA' && $jam_A_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_A = max(0, $jam_A - 0.5);
                    elseif ($kategori_hari == 'BIASA' && $jam_B_asal > 0 && $start >= $time_10pm) $jam_B = max(0, $jam_B - 0.5);
                    elseif ($kategori_hari == 'MINGGU' && $jam_B_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_B = max(0, $jam_B - 0.5);
                    elseif ($kategori_hari == 'MINGGU' && $jam_C_asal > 0 && $start >= $time_10pm) $jam_C = max(0, $jam_C - 0.5);
                    elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_D_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_D = max(0, $jam_D - 0.5);
                    elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_E_asal > 0 && $start >= $time_10pm) $jam_E = max(0, $jam_E - 0.5);
                }
            } elseif ($jumlah_jam_asal < 9) { // Syarat 4
                if ($langgar_maghrib) {
                    if ($kategori_hari == 'BIASA' && $jam_A_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_A = max(0, $jam_A - 0.5);
                    elseif ($kategori_hari == 'BIASA' && $jam_B_asal > 0 && $start >= $time_10pm) $jam_B = max(0, $jam_B - 0.5);
                    elseif ($kategori_hari == 'MINGGU' && $jam_B_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_B = max(0, $jam_B - 0.5);
                    elseif ($kategori_hari == 'MINGGU' && $jam_C_asal > 0 && $start >= $time_10pm) $jam_C = max(0, $jam_C - 0.5);
                    elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_D_asal > 0 && $start < $maghrib_end && $time_10pm >= $maghrib_start) $jam_D = max(0, $jam_D - 0.5);
                    elseif (($kategori_hari == 'KELEPASAN' || $kategori_hari == 'PERISTIWA') && $jam_E_asal > 0 && $start >= $time_10pm) $jam_E = max(0, $jam_E - 0.5);
                }
            }
        }

        // Kira gaji berdasarkan formula
        $gaji_dikira = ($gaji * 12 * $jam_A * 1.125 / $konstanta) +
                       ($gaji * 12 * $jam_B * 1.25 / $konstanta) +
                       ($gaji * 12 * $jam_C * 1.5 / $konstanta) +
                       ($gaji * 12 * $jam_D * 1.75 / $konstanta) +
                       ($gaji * 12 * $jam_E * 2 / $konstanta);

        $jumlah_jam_akhir = $jam_A + $jam_B + $jam_C + $jam_D + $jam_E;

        // Debug message
        $debug_message .= "Jumlah jam asal: " . number_format($jumlah_jam_asal, 2) . " jam\\n";
        $debug_message .= "Langgar Maghrib: " . ($langgar_maghrib ? "Ya" : "Tidak") . "\\n";
        $debug_message .= "Ada 2 kadar: " . ($ada_dua_kadar ? "Ya" : "Tidak") . "\\n";
        $debug_message .= "Jam A asal: " . number_format($jam_A_asal, 2) . ", Jam B asal: " . number_format($jam_B_asal, 2) . ", Jam C asal: " . number_format($jam_C_asal, 2) . ", Jam D asal: " . number_format($jam_D_asal, 2) . ", Jam E asal: " . number_format($jam_E_asal, 2) . "\\n";
        $debug_message .= "Jam A: " . number_format($jam_A, 2) . ", Jam B: " . number_format($jam_B, 2) . ", Jam C: " . number_format($jam_C, 2) . ", Jam D: " . number_format($jam_D, 2) . ", Jam E: " . number_format($jam_E, 2) . "\\n";
        $debug_message .= "Jumlah jam akhir: " . number_format($jumlah_jam_akhir, 2) . " jam\\n";
        $debug_message .= "Gaji Dikira: RM" . number_format($gaji_dikira, 2);

        // Simpan ke database
        $sql = "INSERT INTO klm_kerja (no_komputer, id_jawatan, id_jabatan, kategori_hari, tarikh, masa_dari, masa_hingga, jumlah_jam, jam_A, jam_B, jam_C, jam_D, jam_E, gaji_dikira, kenyataan_tugasan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siissssddddddds", $no_komputer, $id_jawatan, $id_jabatan, $kategori_hari, $tarikh_asal, $masa_dari, $masa_hingga, $jumlah_jam_akhir, $jam_A, $jam_B, $jam_C, $jam_D, $jam_E, $gaji_dikira, $kenyataan_tugasan);
        if ($stmt->execute()) {
            // Simpan log aktiviti
            $log_sql = "INSERT INTO log_aktiviti (aktiviti, tarikh_masa, no_komputer) VALUES (?, NOW(), ?)";
            $log_stmt = $conn->prepare($log_sql);
            $aktiviti = "Menyimpan rekod KLM untuk $no_komputer pada tarikh $tarikh_asal";
            $log_stmt->bind_param("ss", $aktiviti, $no_komputer);
            $log_stmt->execute();
            $log_stmt->close();

            $bulan = date('Y-m', strtotime($tarikh_asal));
            echo "<script>alert('Data berjaya disimpan!\\n" . $debug_message . "'); window.location='./senarai_klm.php?no_komputer=" . urlencode($no_komputer) . "&bulan=" . urlencode($bulan) . "';</script>";
        } else {
            echo "<script>alert('Ralat menyimpan data: " . $conn->error . "\\n" . $debug_message . "');</script>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BORANG KLM<?php echo $anggota ? " - " . htmlspecialchars(strtoupper($anggota['nama'])) : ''; ?> - SISTEM PENGUATKUASAAN</title>
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
        .form-container {
            max-width: 900px;
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
        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-klm {
            background-color: #17a2b8;
            border: none;
        }
        .btn-klm:hover {
            background-color: #138496;
        }
        .btn-klm-list {
            background-color: #ffc107;
            border: none;
            color: #0d1a40;
        }
        .btn-klm-list:hover {
            background-color: #e0a800;
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
    <!-- Header -->
    <div class="header">
        <h1>SISTEM PENGUATKUASAAN</h1>
    </div>

    <div class="form-container">
        <h2 class="text-center">BORANG KLM<?php echo $anggota ? " - " . htmlspecialchars($nama_anggota = strtoupper($anggota['nama'])) : ''; ?></h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">NAMA:</label>
                <?php if ($anggota) { ?>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($nama_anggota); ?>" readonly>
                    <input type="hidden" name="no_komputer" value="<?php echo htmlspecialchars($anggota['no_komputer']); ?>">
                <?php } else { ?>
                    <select name="no_komputer" class="form-control" onchange="populateDetails(this)" required>
                        <option value="">PILIH NAMA</option>
                        <?php while ($row = $anggota_result->fetch_assoc()) { ?>
                            <option value="<?php echo $row['no_komputer']; ?>" 
                                    data-kp="<?php echo $row['kad_pengenalan']; ?>" 
                                    data-gaji="<?php echo $row['gaji']; ?>" 
                                    data-jawatan-id="<?php echo $row['id_jawatan']; ?>" 
                                    data-jabatan-id="<?php echo $row['id_jabatan']; ?>" 
                                    data-jawatan="<?php echo $row['nama_jawatan']; ?>" 
                                    data-jabatan="<?php echo $row['nama_jabatan']; ?>">
                                <?php echo strtoupper($row['nama']); ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } ?>
            </div>
            <div class="mb-3">
                <label class="form-label">NO KOMPUTER:</label>
                <input type="text" id="no_komputer_display" class="form-control" value="<?php echo $anggota ? htmlspecialchars($anggota['no_komputer']) : ''; ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">KAD PENGENALAN:</label>
                <input type="text" name="kad_pengenalan" id="kad_pengenalan" class="form-control" value="<?php echo $anggota ? htmlspecialchars($anggota['kad_pengenalan']) : ''; ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">JAWATAN:</label>
                <?php if ($anggota) { ?>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(strtoupper($anggota['nama_jawatan'])); ?>" readonly>
                    <input type="hidden" name="id_jawatan" value="<?php echo htmlspecialchars($anggota['id_jawatan']); ?>">
                <?php } else { ?>
                    <input type="text" id="jawatan" class="form-control" readonly>
                    <input type="hidden" name="id_jawatan" id="id_jawatan">
                <?php } ?>
            </div>
            <div class="mb-3">
                <label class="form-label">JABATAN:</label>
                <?php if ($anggota) { ?>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(strtoupper($anggota['nama_jabatan'])); ?>" readonly>
                    <input type="hidden" name="id_jabatan" value="<?php echo htmlspecialchars($anggota['id_jabatan']); ?>">
                <?php } else { ?>
                    <input type="text" id="jabatan" class="form-control" readonly>
                    <input type="hidden" name="id_jabatan" id="id_jabatan">
                <?php } ?>
            </div>
            <div class="mb-3">
                <label class="form-label">GAJI (RM):</label>
                <input type="text" id="gaji" class="form-control" value="<?php echo $anggota ? 'RM' . number_format($anggota['gaji'], 2) : ''; ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">KATEGORI HARI:</label>
                <select name="kategori_hari" class="form-control" required>
                    <option value="">PILIH KATEGORI</option>
                    <option value="BIASA">BIASA</option>
                    <option value="MINGGU">MINGGU</option>
                    <option value="KELEPASAN">KELEPASAN</option>
                    <option value="PERISTIWA">PERISTIWA</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">TARIKH:</label>
                <input type="date" name="tarikh" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">MASA DARI:</label>
                <input type="time" name="masa_dari" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">MASA HINGGA:</label>
                <input type="time" name="masa_hingga" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">KENYATAAN TUGASAN:</label>
                <textarea name="kenyataan_tugasan" class="form-control" required></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary btn-custom"><i class="bi bi-save me-2"></i>SIMPAN</button>
                <a href="./senarai_klm.php<?php echo $no_komputer ? '?no_komputer=' . urlencode($no_komputer) : ''; ?>" class="btn btn-secondary btn-custom"><i class="bi bi-list me-2"></i>SENARAI KERJA</a>
                <a href="./senarai_anggota_klm.php" class="btn btn-klm-list btn-custom"><i class="bi bi-list-ul me-2"></i>SENARAI NAMA KLM</a>
                <a href="../anggota/menu.php" class="btn btn-klm btn-custom"><i class="bi bi-house me-2"></i>KE HALAMAN UTAMA</a>
            </div>
        </form>
    </div>

    <?php if (!$anggota) { ?>
    <script>
        function populateDetails(select) {
            const selectedOption = select.options[select.selectedIndex];
            const noKomputer = selectedOption.value;
            const kadPengenalan = selectedOption.getAttribute('data-kp');
            const gaji = selectedOption.getAttribute('data-gaji');
            const jawatanId = selectedOption.getAttribute('data-jawatan-id');
            const jabatanId = selectedOption.getAttribute('data-jabatan-id');
            const jawatan = selectedOption.getAttribute('data-jawatan');
            const jabatan = selectedOption.getAttribute('data-jabatan');

            document.getElementById('kad_pengenalan').value = kadPengenalan || '';
            document.getElementById('no_komputer_display').value = noKomputer || '';
            document.getElementById('gaji').value = gaji ? 'RM' + parseFloat(gaji).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
            document.getElementById('jawatan').value = jawatan ? jawatan.toUpperCase() : '';
            document.getElementById('id_jawatan').value = jawatanId || '';
            document.getElementById('jabatan').value = jabatan ? jabatan.toUpperCase() : '';
            document.getElementById('id_jabatan').value = jabatanId || '';
        }
    </script>
    <?php } ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>