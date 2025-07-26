<?php
session_start();
require 'db.php';

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM admin WHERE username = '$username' LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    if (password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin'] = $admin['username']; // gunakan 'admin' untuk konsistensi
        header("Location: ../Dashboard/index.php");
        exit;
    }
}

// Jika gagal login
header("Location: login.php?error=1");
exit;
