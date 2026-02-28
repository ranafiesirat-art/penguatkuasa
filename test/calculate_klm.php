<?php
/**
 * Fungsi untuk mengira nilai Y bagi setiap kategori KLM dan jumlah keseluruhan
 * @param float $gaji Gaji pokok anggota
 * @param float $total_jam_A Jumlah jam untuk kategori A
 * @param float $total_jam_B Jumlah jam untuk kategori B
 * @param float $total_jam_C Jumlah jam untuk kategori C
 * @param float $total_jam_D Jumlah jam untuk kategori D
 * @param float $total_jam_E Jumlah jam untuk kategori E
 * @return array Hasil pengiraan untuk semua kategori dan jumlah
 */
function calculateKLM($gaji, $total_jam_A, $total_jam_B, $total_jam_C, $total_jam_D, $total_jam_E) {
    // Tetapkan kadar untuk setiap kategori
    $kadar_A = 1.125;
    $kadar_B = 1.25;
    $kadar_C = 1.5;
    $kadar_D = 1.75;
    $kadar_E = 2.0;

    // Kira nilai Y untuk setiap kategori: Y = (gaji x 12 x jumlah jam x kadar) / 2504
    $nilai_Y_A = ($gaji * 12 * $total_jam_A * $kadar_A) / 2504;
    $nilai_Y_B = ($gaji * 12 * $total_jam_B * $kadar_B) / 2504;
    $nilai_Y_C = ($gaji * 12 * $total_jam_C * $kadar_C) / 2504;
    $nilai_Y_D = ($gaji * 12 * $total_jam_D * $kadar_D) / 2504;
    $nilai_Y_E = ($gaji * 12 * $total_jam_E * $kadar_E) / 2504;

    // Kira jumlah keseluruhan kategori
    $total_kategori = $nilai_Y_A + $nilai_Y_B + $nilai_Y_C + $nilai_Y_D + $nilai_Y_E;

    // Format jumlah jam tanpa trailing zero
    $total_jam_A_formatted = rtrim(number_format($total_jam_A, 2), '0');
    $total_jam_B_formatted = rtrim(number_format($total_jam_B, 2), '0');
    $total_jam_C_formatted = rtrim(number_format($total_jam_C, 2), '0');
    $total_jam_D_formatted = rtrim(number_format($total_jam_D, 2), '0');
    $total_jam_E_formatted = rtrim(number_format($total_jam_E, 2), '0');

    return [
        'nilai_Y_A' => $nilai_Y_A,
        'nilai_Y_B' => $nilai_Y_B,
        'nilai_Y_C' => $nilai_Y_C,
        'nilai_Y_D' => $nilai_Y_D,
        'nilai_Y_E' => $nilai_Y_E,
        'total_kategori' => $total_kategori,
        'total_jam_A_formatted' => $total_jam_A_formatted,
        'total_jam_B_formatted' => $total_jam_B_formatted,
        'total_jam_C_formatted' => $total_jam_C_formatted,
        'total_jam_D_formatted' => $total_jam_D_formatted,
        'total_jam_E_formatted' => $total_jam_E_formatted
    ];
}
?>