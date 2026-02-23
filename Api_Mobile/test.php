<?php
include 'connection.php';

$plain = "admin";
$hash  = password_hash($plain, PASSWORD_DEFAULT);

$stmt = $connect->prepare("UPDATE users SET password=? WHERE email=?");
$stmt->bind_param("ss", $hash, $email);

// Update ghatan@gmail.com
$email = "kasir@gmail.com";
$stmt->execute();

// Update budi@gmail.com juga
$email = "chef@gmail.com";
$stmt->execute();

echo "Password kedua user diubah menjadi hash bcrypt dari: '$plain'";