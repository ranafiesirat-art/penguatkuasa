<?php
// Sambungan database manual
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "penguatkuasa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Sambungan gagal");
}

$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;

echo '<option value="">Pilih Seksyen</option>';

if ($unit_id > 0) {
    $stmt = $conn->prepare("SELECT id, nama FROM seksyen WHERE unit_id = ? ORDER BY nama");
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['id']}'>" . htmlspecialchars($row['nama']) . "</option>";
    }
    $stmt->close();
}
$conn->close();
?>