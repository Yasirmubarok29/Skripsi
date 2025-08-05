<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Dashboard/index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require '../conection/db.php';

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Password tidak cocok";
    } else {
        // Cek apakah username sudah ada
        $check = $conn->prepare("SELECT id FROM admin WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username sudah digunakan";
        } else {
            // Simpan admin baru
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed);
            $stmt->execute();

            $success = "Registrasi berhasil. Silakan login.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Registrasi Admin - WebGIS Evakuasi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #f57c00, #f57c00); /* <- warna hijau */
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
    }
    .register-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.1);
      padding: 30px;
      width: 100%;
      max-width: 400px;
      position: relative;
    }
    .register-logo {
      width: 140px;
      height: 140px;
      object-fit: contain;
    }
    .btn-back-inside {
      position: absolute;
      top: 15px;
      left: 15px;
      font-size: 40px;
      background-color: transparent;
      border: none;
      color: #333;
      text-decoration: none;
    }
    .btn-back-inside:hover {
      color: #2e7d32;
    }
  </style>
</head>
<body>

<div class="register-card text-center">
  <!-- Tombol kembali -->
  <a href="../login/index.php" class="btn-back-inside" title="Kembali">←</a>

  <img src="../assets/logo.png" alt="Logo" class="register-logo mb-3">
  <h4 class="mb-3 text-success">Registrasi Admin</h4>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3 text-start">
      <label for="username" class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3 text-start">
      <label for="password" class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3 text-start">
      <label for="confirm_password" class="form-label">Konfirmasi Password</label>
      <input type="password" name="confirm_password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success w-100 mt-2">Daftar</button>
  </form>

  <div class="mt-3">
    <a href="../login" class="text-decoration-none text-secondary">Sudah punya akun? <strong>Login</strong></a>
  </div>

  <footer class="mt-4 text-muted" style="font-size: 13px;">
    © 2025 WebGIS Evakuasi – Kabupaten Cianjur
  </footer>
</div>

</body>
</html>
