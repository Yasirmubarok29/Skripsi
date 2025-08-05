<?php
session_start();
session_unset();         // Menghapus semua variabel session
session_destroy();       // Menghancurkan session di server

// Redirect ke halaman login
header("Location: ../index.php");
exit;
?>
