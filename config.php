<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "penguatkuasa";

$conn = new mysqli($host, $user, $pass, $dbname);

// Semak sambungan
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
