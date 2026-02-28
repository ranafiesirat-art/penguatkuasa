<?php
session_start();

// Hentikan sesi
session_unset();
session_destroy();

// Redirect ke login.php
header("Location: login.php"); // Laluan betul kerana login.php di direktori sama
exit();
?>