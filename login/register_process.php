<?php
require 'db.php';

$username = trim($_POST['username']);
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

if ($password !== $confirm) {
    header("Location: register.php?error=Password tidak cocok");
    exit;
}

// Cek apakah username sudah ada
$check = $conn->prepare("SELECT id FROM admin WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    header("Location: register.php?error=Username sudah digunakan");
    exit;
}

// Simpan admin baru
$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hashed);
$stmt->execute();

header("Location: register.php?success=Registrasi berhasil. Silakan login.");
exit;
