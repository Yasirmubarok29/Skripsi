<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Dashboard/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">Registrasi Admin</div>
                    <div class="card-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
                        <?php endif; ?>
                        <form method="POST" action="register_process.php">
                            <div class="mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Daftar</button>
                            <a href="login.php" class="btn btn-link w-100 mt-2">Sudah punya akun? Login</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
