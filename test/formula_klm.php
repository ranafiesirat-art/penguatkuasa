<?php
if (!file_exists('calculate_klm.php')) {
    die('Ralat: Fail calculate_klm.php tidak ditemui di direktori ' . __DIR__);
}
include 'calculate_klm.php';

// Fungsi untuk menukar nombor kepada perkataan dalam Bahasa Malaysia
function numberToWordsMYR($number) {
    $ones = [
        0 => '', 1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat',
        5 => 'Lima', 6 => 'Enam', 7 => 'Tujuh', 8 => 'Lapan', 9 => 'Sembilan'
    ];
    $tens = [
        10 => 'Sepuluh', 11 => 'Sebelas', 12 => 'Dua Belas', 13 => 'Tiga Belas',
        14 => 'Empat Belas', 15 => 'Lima Belas', 16 => 'Enam Belas',
        17 => 'Tujuh Belas', 18 => 'Lapan Belas', 19 => 'Sembilan Belas',
        2 => 'Dua Puluh', 3 => 'Tiga Puluh', 4 => 'Empat Puluh', 5 => 'Lima Puluh',
        6 => 'Enam Puluh', 7 => 'Tujuh Puluh', 8 => 'Lapan Puluh', 9 => 'Sembilan Puluh'
    ];
    $hundreds = [
        1 => 'Seratus', 2 => 'Dua Ratus', 3 => 'Tiga Ratus', 4 => 'Empat Ratus',
        5 => 'Lima Ratus', 6 => 'Enam Ratus', 7 => 'Tujuh Ratus', 8 => 'Lapan Ratus',
        9 => 'Sembilan Ratus'
    ];
    $thousands = [
        1 => 'Seribu', 2 => 'Dua Ribu', 3 => 'Tiga Ribu', 4 => 'Empat Ribu',
        5 => 'Lima Ribu', 6 => 'Enam Ribu', 7 => 'Tujuh Ribu', 8 => 'Lapan Ribu',
        9 => 'Sembilan Ribu'
    ];

    // Pisahkan bahagian ringgit dan sen
    $ringgit = floor($number);
    $sen = round(($number - $ringgit) * 100);

    $words = '';

    // Proses bahagian ringgit
    if ($ringgit == 0) {
        $words = 'Sifar';
    } else {
        if ($ringgit >= 1000) {
            $thousand = floor($ringgit / 1000);
            if ($thousand <= 9) {
                $words .= $thousands[$thousand] . ' ';
            }
            $ringgit %= 1000;
        }
        if ($ringgit >= 100) {
            $hundred = floor($ringgit / 100);
            $words .= $hundreds[$hundred] . ' ';
            $ringgit %= 100;
        }
        if ($ringgit >= 20) {
            $ten = floor($ringgit / 10);
            $words .= $tens[$ten] . ' ';
            $ringgit %= 10;
        } elseif ($ringgit >= 10) {
            $words .= $tens[$ringgit] . ' ';
            $ringgit = 0;
        }
        if ($ringgit > 0) {
            $words .= $ones[$ringgit] . ' ';
        }
    }

    $words = trim($words);
    if (!empty($words)) {
        $words .= ' Ringgit';
    }

    // Proses bahagian sen
    if ($sen > 0) {
        $sen_words = '';
        if ($sen >= 20) {
            $ten = floor($sen / 10);
            $sen_words .= $tens[$ten] . ' ';
            $sen %= 10;
        } elseif ($sen >= 10) {
            $sen_words .= $tens[$sen] . ' ';
            $sen = 0;
        }
        if ($sen > 0) {
            $sen_words .= $ones[$sen] . ' ';
        }
        $words .= ' Dan ' . trim($sen_words) . ' Sen';
    }

    // Tambah 'Sahaja' di hujung
    $words = trim($words);
    if (!empty($words)) {
        $words .= ' Sahaja';
    }

    // Tukar kepada huruf besar
    return strtoupper($words);
}

// Data input untuk paparan (boleh diubah atau diambil dari GET)
$gaji = isset($_GET['gaji']) ? floatval($_GET['gaji']) : 3000.00;
$total_jam_A = isset($_GET['jam_A']) ? floatval($_GET['jam_A']) : 10.50;
$total_jam_B = isset($_GET['jam_B']) ? floatval($_GET['jam_B']) : 8.00;
$total_jam_C = isset($_GET['jam_C']) ? floatval($_GET['jam_C']) : 6.25;
$total_jam_D = isset($_GET['jam_D']) ? floatval($_GET['jam_D']) : 4.75;
$total_jam_E = isset($_GET['jam_E']) ? floatval($_GET['jam_E']) : 2.00;

// Ambil input teks dari parameter GET atau gunakan teks lalai untuk Pengakuan Yang Menuntut
$statement1 = isset($_GET['statement1']) ? $_GET['statement1'] : 'Dengan ini saya mengaku bahawa saya telah menjalankan tugas dengan membuat kerja lebih masa';
$statement2 = isset($_GET['statement2']) ? $_GET['statement2'] : 'sebagaimana yang dinyatakan di atas.';
$name = isset($_GET['name']) ? $_GET['name'] : 'NAMA ANGGOTA';
$position = isset($_GET['position']) ? $_GET['position'] : 'JAWATAN';
$department = isset($_GET['department']) ? $_GET['department'] : 'JABATAN';

// Ambil input teks dari parameter GET atau gunakan teks lalai untuk Pengakuan Pegawai Penjaga
$officer_statement1 = isset($_GET['officer_statement1']) ? $_GET['officer_statement1'] : 'Dengan ini saya mengaku bahawa tuntutan kerja lebih masa dibuat oleh penuntut adalah perlu untuk';
$officer_statement2 = isset($_GET['officer_statement2']) ? $_GET['officer_statement2'] : 'menjalankan tugas-tugas rasmi.';

// Ambil input teks dari parameter GET atau gunakan teks lalai untuk Pengakuan Ketua Jabatan
$department_head_statement1 = isset($_GET['department_head_statement1']) ? $_GET['department_head_statement1'] : 'Disahkan boleh dibayar menurut Arahan Perbendaharaan, Perintah Am dari Pekeliling Kerajaan.';
$department_head_statement2 = isset($_GET['department_head_statement2']) ? $_GET['department_head_statement2'] : '';

// Panggil fungsi calculateKLM
$result = calculateKLM($gaji, $total_jam_A, $total_jam_B, $total_jam_C, $total_jam_D, $total_jam_E);

// Simpan hasil pengiraan
$nilai_Y_A = $result['nilai_Y_A'];
$nilai_Y_B = $result['nilai_Y_B'];
$nilai_Y_C = $result['nilai_Y_C'];
$nilai_Y_D = $result['nilai_Y_D'];
$nilai_Y_E = $result['nilai_Y_E'];
$total_kategori = $result['total_kategori'];
$total_jam_A_formatted = $result['total_jam_A_formatted'];
$total_jam_B_formatted = $result['total_jam_B_formatted'];
$total_jam_C_formatted = $result['total_jam_C_formatted'];
$total_jam_D_formatted = $result['total_jam_D_formatted'];
$total_jam_E_formatted = $result['total_jam_E_formatted'];

// Tukar jumlah kiraan kepada perkataan
$total_words = numberToWordsMYR($total_kategori);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAPARAN PENGIRAAN KLM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            margin: 0;
            padding: 10px;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .calculation-container {
            width: 100%;
            max-width: 200mm;
            background-color: white;
            padding: 10px;
            border: 2px solid #000;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .borang-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .borang-header h1 {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .additional-calculation {
            margin-top: 10px;
            font-size: 8pt;
            text-transform: uppercase;
            text-align: left;
            margin-left: 10px;
        }
        .additional-calculation .calc-row {
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .additional-calculation .calc-row-malam {
            margin-bottom: 0;
            position: relative;
        }
        .additional-calculation .calc-row-total {
            margin-bottom: 10px;
            position: relative;
            display: flex;
            align-items: center;
        }
        .additional-calculation p {
            margin: 0;
            line-height: 1.5;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .additional-calculation .label-a, 
        .additional-calculation .label-b, 
        .additional-calculation .label-c, 
        .additional-calculation .label-d, 
        .additional-calculation .label-e,
        .additional-calculation .label-total {
            display: inline-block;
            width: 20px;
            text-align: left;
        }
        .additional-calculation .label-text {
            display: inline-block;
            width: 150px;
        }
        .additional-calculation .label-text-total {
            display: inline-block;
            width: 150px;
            font-weight: bold;
            margin-left: 273px; /* Laras kedudukan JUMLAH */
        }
        .additional-calculation .label-text-a::after,
        .additional-calculation .label-text-b::after,
        .additional-calculation .label-text-c::after,
        .additional-calculation .label-text-d::after,
        .additional-calculation .label-text-e::after {
            content: ":";
            margin-left: 2px;
        }
        .additional-calculation .label-text-b,
        .additional-calculation .label-text-c {
            display: inline-block;
            width: 150px;
            position: relative;
        }
        .additional-calculation .label-text-d,
        .additional-calculation .label-text-e {
            display: inline-block;
            width: 150px;
        }
        .additional-calculation .label-text-malam {
            display: inline-block;
            width: 150px;
            margin-left: 0;
            position: absolute;
            top: 14px;
            left: 0;
            line-height: 1.5;
        }
        .additional-calculation .formula-container {
            display: inline-block;
            width: 280px;
            position: relative;
            vertical-align: middle;
        }
        .additional-calculation .formula {
            display: inline-flex;
            align-items: center;
            width: 100%;
            justify-content: center;
            padding-bottom: 2px;
            border-bottom: 1px solid #000;
            font-size: 7pt;
        }
        .additional-calculation .formula span {
            margin: 0 3px;
        }
        .additional-calculation .gaji-below {
            position: absolute;
            left: 0;
            right: 0;
            text-align: center;
            top: 100%;
            margin-top: 6px;
            line-height: 1.5;
            font-size: 7pt;
        }
        .additional-calculation .result {
            display: inline-block;
            margin-left: 30px; /* Jarak tambahan ke kanan dari tanda = */
        }
        /* CSS untuk teks RM di sebelah kanan kategori */
        .additional-calculation .category-currency {
            display: inline-block;
            font-weight: bold;
            font-size: 8pt;
        }
        .additional-calculation .category-currency-a {
            margin-left: 4px; /* Laras kedudukan mendatar (kiri/kanan) untuk RM kategori A */
            position: relative;
            top: 8px; /* Laras kedudukan menegak (atas/bawah): positif = ke bawah, negatif = ke atas */
        }
        .additional-calculation .category-currency-b {
            margin-left: 155px; /* Laras kedudukan mendatar (kiri/kanan) untuk RM kategori B */
            position: relative;
            top: 0px; /* Laras kedudukan menegak (atas/bawah): positif = ke bawah, negatif = ke atas */
        }
        .additional-calculation .category-currency-c {
            margin-left: 155px; /* Laras kedudukan mendatar (kiri/kanan) untuk RM kategori C */
            position: relative;
            top: 0px; /* Laras kedudukan menegak (atas/bawah): positif = ke bawah, negatif = ke atas */
        }
        .additional-calculation .category-currency-d {
            margin-left: 5px; /* Laras kedudukan mendatar (kiri/kanan) untuk RM kategori D */
            position: relative;
            top: 8px; /* Laras kedudukan menegak (atas/bawah): positif = ke bawah, negatif = ke atas */
        }
        .additional-calculation .category-currency-e {
            margin-left: 5px; /* Laras kedudukan mendatar (kiri/kanan) untuk RM kategori E */
            position: relative;
            top: 8px; /* Laras kedudukan menegak (atas/bawah): positif = ke bawah, negatif = ke atas */
        }
        /* CSS khusus untuk JUMLAH */
        .additional-calculation .total-currency {
            display: inline-block;
            margin-left: -80px; /* Laras kedudukan RM */
            font-size: 8pt;
        }
        .additional-calculation .total-ringgit {
            display: inline-block;
            margin-left: 45px; /* Laras kedudukan RINGGIT MALAYSIA */
            font-size: 8pt;
            font-weight: bold; /* Jadikan teks tebal */
            white-space: nowrap; /* Pastikan teks kekal dalam satu baris */
        }
        .additional-calculation .total-value {
            display: inline-block;
            margin-left: 5px; /* Laras kedudukan nilai kiraan */
            font-weight: bold;
            font-size: 8pt;
        }
        /* CSS untuk kotak jumlah dalam perkataan */
        .additional-calculation .total-word-box {
            position: absolute;
            left: 170px; /* Laras kedudukan kotak ke kiri */
            border: 1px solid #000;
            padding: 6px; /* Tambah padding untuk ruang dalaman */
            width: 260px; /* Lebar sedikit lebih besar untuk teks panjang */
            font-size: 7pt; /* Saiz fon sesuai, boleh ubah ke 6.5pt jika perlu */
            font-weight: bold; /* Jadikan teks tebal */
            text-align: left; /* Kekalkan penjajaran kiri */
            background-color: #fff;
            line-height: 1.3; /* Jarak baris lebih selesa */
            text-transform: uppercase; /* Pastikan teks huruf besar */
        }
        .additional-calculation .label-formula-container {
            display: flex;
            flex-direction: column;
            position: relative;
        }
        /* CSS untuk bahagian Pengakuan */
        .acknowledgment-section {
            margin-top: 20px; /* Jarak dari bahagian pengiraan */
            margin-left: 10px; /* Selari dengan pengiraan */
            font-weight: bold; /* Teks tebal lalai */
        }
        .acknowledgment-section .ack-item {
            margin-bottom: 8px; /* Jarak antara baris */
            display: block;
        }
        .acknowledgment-section .ack-item.signature-row {
            display: flex;
            justify-content: space-between; /* Penjajaran TARIKH di kiri, TANDATANGAN di kanan */
            align-items: flex-start; /* Penjajaran atas untuk bekas */
            position: relative;
            width: 100%; /* Pastikan lebar penuh bekas */
            height: 60px; /* Tinggi tetap untuk memastikan ruang mencukupi */
        }
        .acknowledgment-section .date-container {
            display: flex;
            align-items: center;
            position: relative;
            flex-shrink: 0; /* Pastikan div tidak mengecut */
        }
        .acknowledgment-section .signature-container {
            position: relative;
            min-width: 300px; /* Pastikan ruang mencukupi untuk semua elemen */
            height: 60px; /* Tinggi sepadan dengan signature-row */
        }
        .acknowledgment-section .ack-number {
            display: inline-block;
            margin-left: 0px; /* Laras kedudukan mendatar untuk teks "1)" */
            position: relative;
            top: 0px; /* Laras kedudukan menegak */
            font-size: 6pt; /* Saiz fon untuk "1)" */
        }
        .acknowledgment-section .ack-title {
            display: inline-block;
            margin-left: 10px; /* Laras kedudukan mendatar untuk "Pengakuan Yang Menuntut" */
            position: relative;
            top: 0px; /* Laras kedudukan menegak */
            text-decoration: underline; /* Garis bawah */
            font-size: 7pt; /* Saiz fon untuk "PENGAKUAN YANG MENUNTUT" */
        }
        .acknowledgment-section .ack-statement-1 {
            display: inline-block;
            margin-left: 20px; /* Laras kedudukan mendatar untuk teks pernyataan 1 */
            position: relative;
            top: 0px; /* Laras kedudukan menegak */
            max-width: 700px; /* Had lebar untuk teks panjang */
            font-size: 7pt; /* Saiz fon untuk teks pernyataan 1 */
            text-transform: none; /* Memastikan teks kekal seperti ditaip */
        }
        .acknowledgment-section .ack-statement-2 {
            display: inline-block;
            margin-left: 20px; /* Laras kedudukan mendatar untuk teks pernyataan 2 */
            position: relative;
            top: -13px; /* Laras kedudukan menegak */
            max-width: 500px; /* Had lebar untuk teks panjang */
            font-size: 7pt; /* Saiz fon untuk teks pernyataan 2 */
            text-transform: none; /* Memastikan teks kekal seperti ditaip */
        }
        .acknowledgment-section .ack-date-label {
            display: inline-block;
            margin-left: 20px; /* Laras kedudukan mendatar (kiri) untuk "TARIKH" */
            margin-right: 5px; /* Laras kedudukan mendatar (kanan) */
            position: relative;
            top: 13px; /* Laras kedudukan menegak */
            font-size: 6pt; /* Saiz fon untuk "TARIKH" */
        }
        .acknowledgment-section .ack-date-colon {
            display: inline-block;
            margin-left: 5px; /* Laras kedudukan mendatar (kiri) untuk ":" */
            margin-right: 5px; /* Laras kedudukan mendatar (kanan) */
            position: relative;
            top: 13px; /* Laras kedudukan menegak */
        }
        .acknowledgment-section .ack-date-line {
            display: inline-block;
            margin-left: 5px; /* Laras kedudukan mendatar (kiri) untuk garisan */
            margin-right: 5px; /* Laras kedudukan mendatar (kanan) */
            position: relative;
            top: 18px; /* Laras kedudukan menegak */
            width: 150px; /* Panjang garisan */
            border-bottom: 1px solid #000; /* Ketebalan garisan */
        }
        .acknowledgment-section .ack-sign-label {
            position: absolute;
            left: -45px; /* Kedudukan mendatar untuk "TANDATANGAN" */
            top: 15px; /* Kedudukan menegak */
            font-size: 6pt; /* Saiz fon */
            white-space: nowrap; /* Elakkan pembalutan teks */
        }
        .acknowledgment-section .ack-sign-colon {
            position: absolute;
            left: 30px; /* Kedudukan mendatar untuk ":" */
            top: 15px; /* Kedudukan menegak */
            font-size: 6pt;
        }
        .acknowledgment-section .ack-sign-line {
            position: absolute;
            left: 45px; /* Kedudukan mendatar untuk garisan */
            top: 26px; /* Kedudukan menegak */
            width: 150px; /* Panjang garisan */
            border-bottom: 1px solid #000; /* Ketebalan garisan */
        }
        .acknowledgment-section .ack-name-label {
            position: absolute;
            left: 78px; /* Kedudukan mendatar, sejajar dengan permulaan garisan */
            top: 30px; /* Kedudukan menegak */
            font-size: 6pt; /* Saiz fon untuk "NAMA ANGGOTA" */
            text-transform: none; /* Kekalkan teks asal */
            white-space: nowrap; /* Elakkan pembalutan teks */
        }
        .acknowledgment-section .ack-position-label {
            position: absolute;
            left: 91px; /* Kedudukan mendatar, lebih ke kanan */
            top: 40px; /* Kedudukan menegak */
            font-size: 6pt; /* Saiz fon untuk "JAWATAN" */
            text-transform: none; /* Kekalkan teks asal */
            white-space: nowrap; /* Elakkan pembalutan teks */
        }
        .acknowledgment-section .ack-department-label {
            position: absolute;
            left: 92px; /* Kedudukan mendatar, lebih ke kanan */
            top: 50px; /* Kedudukan menegak */
            font-size: 6pt; /* Saiz fon untuk "JABATAN" */
            text-transform: none; /* Kekalkan teks asal */
            white-space: nowrap; /* Elakkan pembalutan teks */
        }
        .acknowledgment-section .officer-acknowledgment {
            margin-top: 10px; /* Jarak dari bahagian Pengakuan Yang Menuntut */
        }
        .acknowledgment-section .department-head-acknowledgment {
            margin-top: 20px; /* Jarak dari bahagian Pengakuan Pegawai Penjaga */
        }
        .print-button-container {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .print-button {
            padding: 8px 20px;
            font-size: 10pt;
            font-weight: bold;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-transform: uppercase;
        }
        .print-button:hover {
            background-color: #0056b3;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 5mm;
            }
            body * {
                visibility: hidden;
            }
            .calculation-container, .calculation-container * {
                visibility: visible;
            }
            .calculation-container {
                position: absolute;
                left: 5mm;
                top: 5mm;
                width: 100%;
                max-width: 200mm;
            }
            .print-button-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="calculation-container">
        <!-- Header -->
        <div class="borang-header">
            <h1>Pengiraan Tuntutan Kerja Lebih Masa</h1>
        </div>

        <!-- Pengiraan untuk Kategori A, B, C, D, dan E -->
        <div class="additional-calculation">
            <div class="calc-row">
                <p>
                    <span class="label-a">A.</span>
                    <span class="label-text label-text-a">Hari Kerja Biasa</span>
                    <span class="category-currency category-currency-a">RM</span>
                    <span class="formula-container">
                        <span class="formula"><span><?php echo number_format($gaji, 2); ?></span> x <span>12</span> x <span><?php echo htmlspecialchars($total_jam_A_formatted); ?></span> x <span>1.125</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_A, 2); ?></span>
                </p>
            </div>

            <div class="calc-row">
                <p>
                    <span class="label-b">B.</span>
                    <span class="label-formula-container">
                        <span class="label-text label-text-b">Hari Kerja Ahad</span>
                        <span class="category-currency category-currency-b">RM</span>
                        <span class="label-text-malam">Hari Biasa (Malam)</span>
                    </span>
                    <span class="formula-container">
                        <span class="formula"><span><?php echo number_format($gaji, 2); ?></span> x <span>12</span> x <span><?php echo htmlspecialchars($total_jam_B_formatted); ?></span> x <span>1.25</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_B, 2); ?></span>
                </p>
            </div>

            <div class="calc-row">
                <p>
                    <span class="label-c">C.</span>
                    <span class="label-formula-container">
                        <span class="label-text label-text-c">Kerja Malam</span>
                        <span class="category-currency category-currency-c">RM</span>
                        <span class="label-text-malam">Hari Ahad (Malam)</span>
                    </span>
                    <span class="formula-container">
                        <span class="formula"><span><?php echo number_format($gaji, 2); ?></span> x <span>12</span> x <span><?php echo htmlspecialchars($total_jam_C_formatted); ?></span> x <span>1.5</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_C, 2); ?></span>
                </p>
            </div>

            <div class="calc-row">
                <p>
                    <span class="label-d">D.</span>
                    <span class="label-text label-text-d">Hari Kelepasan</span>
                    <span class="category-currency category-currency-d">RM</span>
                    <span class="formula-container">
                        <span class="formula"><span><?php echo number_format($gaji, 2); ?></span> x <span>12</span> x <span><?php echo htmlspecialchars($total_jam_D_formatted); ?></span> x <span>1.75</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_D, 2); ?></span>
                </p>
            </div>

            <div class="calc-row">
                <p>
                    <span class="label-e">E.</span>
                    <span class="label-text label-text-e">Hari Kelepasan (Malam)</span>
                    <span class="category-currency category-currency-e">RM</span>
                    <span class="formula-container">
                        <span class="formula"><span><?php echo number_format($gaji, 2); ?></span> x <span>12</span> x <span><?php echo htmlspecialchars($total_jam_E_formatted); ?></span> x <span>2.0</span></span>
                        <span class="gaji-below"><?php echo number_format(2504, 0, '.', ''); ?></span>
                    </span>
                    <span class="result"><?php echo "= RM " . number_format($nilai_Y_E, 2); ?></span>
                </p>
            </div>

            <!-- Baris JUMLAH (dipisahkan untuk pengeditan manual) -->
            <div class="calc-row-total">
                <!-- Teks RINGGIT MALAYSIA -->
                <span class="total-ringgit">Ringgit Malaysia</span>
                <!-- Kotak jumlah dalam perkataan -->
                <div class="total-word-box"><?php echo htmlspecialchars($total_words); ?></div>
                <p>
                    <!-- Label JUMLAH -->
                    <span class="label-total"></span>
                    <span class="label-text-total">Jumlah</span>
                    <!-- Teks RM -->
                    <span class="total-currency">RM</span>
                    <!-- Nilai kiraan -->
                    <span class="total-value"><?php echo number_format($total_kategori, 2); ?></span>
                </p>
            </div>

            <!-- Bahagian Pengakuan -->
            <div class="acknowledgment-section">
                <!-- Pengakuan Yang Menuntut -->
                <div class="ack-item">
                    <span class="ack-number">1)</span>
                    <span class="ack-title">Pengakuan Yang Menuntut</span>
                </div>
                <div class="ack-item">
                    <span class="ack-statement-1"><?php echo htmlspecialchars($statement1); ?></span>
                </div>
                <div class="ack-item">
                    <span class="ack-statement-2"><?php echo htmlspecialchars($statement2); ?></span>
                </div>
                <div class="ack-item signature-row">
                    <div class="date-container">
                        <span class="ack-date-label">Tarikh</span>
                        <span class="ack-date-colon">:</span>
                        <span class="ack-date-line"></span>
                    </div>
                    <div class="signature-container">
                        <span class="ack-sign-label">Tandatangan</span>
                        <span class="ack-sign-colon">:</span>
                        <span class="ack-sign-line"></span>
                        <span class="ack-name-label"><?php echo htmlspecialchars($name); ?></span>
                        <span class="ack-position-label"><?php echo htmlspecialchars($position); ?></span>
                        <span class="ack-department-label"><?php echo htmlspecialchars($department); ?></span>
                    </div>
                </div>

                <!-- Pengakuan Pegawai Penjaga -->
                <div class="officer-acknowledgment">
                    <div class="ack-item">
                        <span class="ack-number">2)</span>
                        <span class="ack-title">Pengakuan Pegawai Penjaga</span>
                    </div>
                    <div class="ack-item">
                        <span class="ack-statement-1"><?php echo htmlspecialchars($officer_statement1); ?></span>
                    </div>
                    <div class="ack-item">
                        <span class="ack-statement-2"><?php echo htmlspecialchars($officer_statement2); ?></span>
                    </div>
                    <div class="ack-item signature-row">
                        <div class="date-container">
                            <span class="ack-date-label">Tarikh</span>
                            <span class="ack-date-colon">:</span>
                            <span class="ack-date-line"></span>
                        </div>
                        <div class="signature-container">
                            <span class="ack-sign-label">Tandatangan</span>
                            <span class="ack-sign-colon">:</span>
                            <span class="ack-sign-line"></span>
                        </div>
                    </div>
                </div>

                <!-- Pengakuan Ketua Jabatan -->
                <div class="department-head-acknowledgment">
                    <div class="ack-item">
                        <span class="ack-number">3)</span>
                        <span class="ack-title">Pengakuan Ketua Jabatan</span>
                    </div>
                    <div class="ack-item">
                        <span class="ack-statement-1"><?php echo htmlspecialchars($department_head_statement1); ?></span>
                    </div>
                    <div class="ack-item">
                        <span class="ack-statement-2"><?php echo htmlspecialchars($department_head_statement2); ?></span>
                    </div>
                    <div class="ack-item signature-row">
                        <div class="date-container">
                            <span class="ack-date-label">Tarikh</span>
                            <span class="ack-date-colon">:</span>
                            <span class="ack-date-line"></span>
                        </div>
                        <div class="signature-container">
                            <span class="ack-sign-label">Tandatangan</span>
                            <span class="ack-sign-colon">:</span>
                            <span class="ack-sign-line"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Butang CETAK -->
        <div class="print-button-container">
            <button class="print-button" onclick="window.print()">Cetak</button>
        </div>
    </div>
</body>
</html>