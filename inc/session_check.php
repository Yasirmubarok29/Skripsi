<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Login/login.php?error=1");
    exit;
}
?>
