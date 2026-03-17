<?php
// Nota: Saiz lajur JAM ditetapkan sama dengan A, B, C, D, E.
// Unit piksel (px) digunakan untuk memastikan konsistensi merentasi penyemak imbas.
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Jadual KLM</title>
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
        .borang-container {
            width: 100%;
            max-width: 750px; /* Tetapkan lebar maksimum */
            background-color: white;
            padding: 10px;
            border: 1px solid #000;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .table-section table {
            width: 100%;
            max-width: 750px;
            border-collapse: collapse;
            font-size: 8pt;
            text-transform: uppercase;
            table-layout: fixed;
        }
        .table-section th, .table-section td {
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
            height: 20px;
            box-sizing: border-box;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table-section th {
            background-color: #d3d3d3;
            font-weight: bold;
        }
        /* Lajur TARIKH */
        .table-section th:nth-child(1), .table-section td:nth-child(1) { 
            width: 30px !important; /* Tetap */
        }
        /* Lajur MASA BERTUGAS MULA */
        .table-section th:nth-child(2), .table-section td:nth-child(2) { 
            width: 50px !important; /* Tetap */
        }
        /* Lajur MASA BERTUGAS TAMAT */
        .table-section th:nth-child(3), .table-section td:nth-child(3) { 
            width: 50px !important; /* Tetap */
        }
        /* Lajur JAM */
        .table-section th:nth-child(4), .table-section td:nth-child(4) { 
            width: 50px !important; /* Disamakan dengan A, B, C, D, E */
        }
        /* Lajur A */
        .table-section th:nth-child(5), .table-section td:nth-child(5) { 
            width: 50px !important; /* Tetap */
        }
        /* Lajur B */
        .table-section th:nth-child(6), .table-section td:nth-child(6) { 
            width: 50px !important; /* Tetap */
        }
        /* Lajur C */
        .table-section th:nth-child(7), .table-section td:nth-child(7) { 
            width: 50px !important; /* Tetap */
        }
        /* Lajur D */
        .table-section th:nth-child(8), .table-section td:nth-child(8) { 
            width: 50px !important; /* Tetap */
        }
        /* Lajur E */
        .table-section th:nth-child(9), .table-section td:nth-child(9) { 
            width: 50px !important; /* Tetap */
        }
        /* Lajur KENYATAAN */
        .table-section th:nth-child(10), .table-section td:nth-child(10) { 
            width: 420px !important; /* Lebarkan untuk mencukupi ruang */
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            body * {
                visibility: hidden;
            }
            .borang-container, .borang-container * {
                visibility: visible;
            }
            .borang-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 750px;
            }
            .table-section table {
                max-width: 750px;
            }
        }
    </style>
</head>
<body>
    <div class="borang-container">
        <!-- Jadual KLM -->
        <div class="table-section">
            <table class="page-table">
                <thead>
                    <tr>
                        <th rowspan="2">TARIKH</th>
                        <th colspan="2">MASA BERTUGAS</th>
                        <th rowspan="2">JAM</th>
                        <th colspan="5">JUMLAH K.L.M.</th>
                        <th rowspan="2">KENYATAAN</th>
                    </tr>
                    <tr>
                        <th>MULA</th>
                        <th>TAMAT</th>
                        <th>A</th>
                        <th>B</th>
                        <th>C</th>
                        <th>D</th>
                        <th>E</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Baris kosong untuk mencukupi 12 baris -->
                    <?php for ($i = 0; $i < 12; $i++): ?>
                        <tr>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                            <td> </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">JUMLAH</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</body>
</html>