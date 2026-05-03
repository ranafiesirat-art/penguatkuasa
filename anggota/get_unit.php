<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "penguatkuasa";

$conn = new mysqli($servername, $username, $password, $dbname);

$bahagian_id = isset($_GET['bahagian_id']) ? (int)$_GET['bahagian_id'] : 0;

echo '<option value="">Pilih Unit</option>';

if ($bahagian_id > 0) {
    $stmt = $conn->prepare("SELECT id, nama FROM unit WHERE bahagian_id = ? ORDER BY nama");
    $stmt->bind_param("i", $bahagian_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo '<option value="">Tiada unit untuk bahagian ini</option>';
    } else {
        while ($row = $result->fetch_assoc()) {
            echo "<option value='{$row['id']}'>" . htmlspecialchars($row['nama']) . "</option>";
        }
    }
    $stmt->close();
}
$conn->close();
?>