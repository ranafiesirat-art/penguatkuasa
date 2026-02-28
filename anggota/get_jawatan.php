<?php
include_once '../session_check.php'; // Gunakan session_check.php di folder akar

header('Content-Type: application/json'); // Tetapkan header untuk output JSON

// Sambungan ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "penguatkuasa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Sambungan ke pangkalan data gagal']);
    exit();
}

// Ambil no_komputer dari GET dengan sanitasi
$no_komputer = isset($_GET['no_komputer']) ? filter_input(INPUT_GET, 'no_komputer', FILTER_SANITIZE_STRING) : '';
if (empty($no_komputer)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No Komputer tidak disediakan']);
    exit();
}

// Query untuk mendapatkan jawatan
$sql = "SELECT j.nama AS jawatan 
        FROM anggota a 
        JOIN jawatan j ON a.id_jawatan = j.id 
        WHERE a.no_komputer = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Ralat menyediakan kenyataan SQL']);
    exit();
}

$stmt->bind_param("s", $no_komputer);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Output JSON
echo json_encode([
    'jawatan' => strtoupper($row['jawatan'] ?? 'TIADA') // Huruf besar untuk konsistensi
]);

// Tutup sambungan
$stmt->close();
$conn->close();
?>