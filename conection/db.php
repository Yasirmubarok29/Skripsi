<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // kosong jika default XAMPP
$dbname = 'evakuasi_db';

$conn = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
