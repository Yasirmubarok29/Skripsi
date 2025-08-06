<?php
session_start();
header('Content-Type: application/json');
require_once 'conection/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        // Cek hash atau plaintext (fallback, legacy)
        if ((isset($admin['password']) && password_verify($password, $admin['password'])) || $admin['password'] === $password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin'] = $admin['username'];
            echo json_encode(['success' => true]);
            exit;
        }
    }
    echo json_encode(['success' => false]);
    exit;
}
echo json_encode(['success' => false]);
exit;
