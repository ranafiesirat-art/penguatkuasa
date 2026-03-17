<?php
// Nota: Lajur TARIKH, MASA BERTUGAS MULA, MASA BERTUGAS TAMAT, JAM, A, B, C, D, E, dan KENYATAAN disertakan untuk eksperimen lebar lajur.
// Lebar lajur dalam %; padding dan border dalam mm untuk konsistensi.
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eksperimen Lebar Lajur Tarikh, Masa, Jam, KLM dan Kenyataan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 6pt;
            margin: 5mm; /* Margin praktikal untuk A4 */
            padding: 0mm;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .borang-container {
            width: 100%;
            max-width: 200mm; /* Disesuaikan untuk A4 dengan margin 5mm */
            background-color: white;
            padding: 0mm;
            border: 1px solid #000;
            box-shadow: 0 0 5mm rgba(0, 0, 0, 0.1);
        }
        .table-section table {
            width: 100%;
            max-width: 200mm;
            border-collapse: collapse;
            font-size: 6pt;
            text-transform: uppercase;
            table-layout: fixed;
        }
        .table-section th, .table-section td {
            border: 1px solid #000; /* Anggaran 0.264mm */
            padding: 1mm; /* Padding untuk semua lajur */
            text-align: center;
            height: 5mm;
            box-sizing: border-box;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table-section th {
            background-color: #d3d3d3;
            font-weight: bold;
            font-size: 6pt; /* Saiz fon eksplisit untuk header */
        }
        /* Saiz fon khusus untuk data A, B, C, D, E */
        .lajur-a, .lajur-b, .lajur-c, .lajur-d, .lajur-e {
            font-size: 4pt; /* Untuk memastikan teks seperti '10' muat dalam ~0.66mm */
        }
        /* Lajur TARIKH */
        .lajur-tarikh {
            width: 10% !important; /* 10% daripada 177.1mm ≈ 17.71mm */
        }
        /* Lajur MASA BERTUGAS MULA */
        .lajur-masa-mula {
            width: 18% !important; /* 18% daripada 177.1mm ≈ 31.88mm */
        }
        /* Lajur MASA BERTUGAS TAMAT */
        .lajur-masa-tamat {
            width: 18% !important; /* 18% daripada 177.1mm ≈ 31.88mm */
        }
        /* Lajur JAM */
        .lajur-jam {
            width: 5% !important; /* 5% daripada 177.1mm ≈ 8.86mm */
        }
        /* Lajur A, B, C, D, E */
        .lajur-a, .lajur-b, .lajur-c, .lajur-d, .lajur-e {
            width: 1.5% !important; /* 1.5% daripada 177.1mm ≈ 2.66mm */
        }
        /* Lajur KENYATAAN */
        .lajur-kenyataan {
            width: 41.5% !important; /* 41.5% daripada 177.1mm ≈ 73.50mm */
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 5mm;
            }
            body * {
                visibility: hidden;
            }
            .borang-container, .borang-container * {
                visibility: visible;
            }
            .borang-container {
                position: absolute;
                left: 5mm;
                top: 5mm;
                width: 100%;
                max-width: 200mm;
            }
            .table-section table {
                max-width: 200mm;
            }
        }
    </style>
</head>
<body>
    <div class="borang-container">
        <!-- Jadual Utama -->
        <div class="table-section">
            <table class="page-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="lajur-tarikh">TARIKH</th>
                        <th colspan="2">MASA BERTUGAS</th>
                        <th rowspan="2" class="lajur-jam">JAM</th>
                        <th colspan="5">JUMLAH K.L.M</th>
                        <th rowspan="2" class="lajur-kenyataan">KENYATAAN</th>
                    </tr>
                    <tr>
                        <th class="lajur-masa-mula">MULA</th>
                        <th class="lajur-masa-tamat">TAMAT</th>
                        <th class="lajur-a">A</th>
                        <th class="lajur-b">B</th>
                        <th class="lajur-c">C</th>
                        <th class="lajur-d">D</th>
                        <th class="lajur-e">E</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < 11; $i++): ?>
                        <tr>
                            <td class="lajur-tarikh"> </td>
                            <td class="lajur-masa-mula"> </td>
                            <td class="lajur-masa-tamat"> </td>
                            <td class="lajur-jam"> </td>
                            <td class="lajur-a"> </td>
                            <td class="lajur-b"> </td>
                            <td class="lajur-c"> </td>
                            <td class="lajur-d"> </td>
                            <td class="lajur-e"> </td>
                            <td class="lajur-kenyataan"> </td>
                        </tr>
                    <?php endfor; ?>
                    <tr>
                        <td colspan="3" class="lajur-tarikh" style="font-weight: bold;">JUMLAH</td>
                        <td class="lajur-jam"> </td>
                        <td class="lajur-a"> </td>
                        <td class="lajur-b"> </td>
                        <td class="lajur-c"> </td>
                        <td class="lajur-d"> </td>
                        <td class="lajur-e"> </td>
                        <td class="lajur-kenyataan"> </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>