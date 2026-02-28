<?php
$password = "12345"; // Kata laluan yang anda mahu gunakan
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo $hashed_password;
?>