<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Dashboard/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require '../conection/db.php';

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin'] = $admin['username'];
            header("Location: ../Dashboard/index.php");
            exit;
        }
    }

    header("Location: index.php?error=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Admin - WebGIS Evakuasi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      height: 100vh;
      background: url('../assets/map-bg.jpg') center center / cover no-repeat; /* contoh gambar latar */
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
      position: relative;
    }

    /* Overlay blur */
    .blur-overlay {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      background-color: rgba(255, 255, 255, 0.1);
      z-index: 1;
    }

    /* Login card tetap solid di atas blur */
    .login-card {
      position: relative;
      z-index: 2;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.15);
      padding: 30px;
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    .login-logo {
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
      color: #ff9800;
    }
  </style>
</head>
<body>

<div class="blur-overlay"></div>

<div class="login-card">
  <a href="../home.php" class="btn-back-inside" title="Kembali">←</a>

  <img src="../assets/logo.png" alt="Logo" class="login-logo mb-3">
  <h4 class="mb-3 text-primary">Login Admin</h4>

  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">Username atau Password salah!</div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3 text-start">
      <label class="form-label">Username</label>
      <input type="text" class="form-control" name="username" required autofocus>
    </div>
    <div class="mb-3 text-start">
      <label class="form-label">Password</label>
      <input type="password" class="form-control" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100 mt-2">Masuk</button>
  </form>

  <div class="mt-3">
    <a href="../register" class="text-decoration-none text-secondary">Belum punya akun? <strong>Daftar</strong></a>
  </div>

  <footer class="mt-4 text-muted" style="font-size: 13px;">
    © 2025 WebGIS Evakuasi – Kabupaten Cianjur
  </footer>
</div>

</body>
</html>
