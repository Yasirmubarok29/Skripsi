<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Dashboard/index.php");
    exit;
}

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: ../map.php");
    exit;
}

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">Login Admin</div>
                    <div class="card-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">Username atau Password salah!</div>
                        <?php endif; ?>
                        <form method="POST" action="login_process.php">
    <div class="mb-3">
        <label>Username</label>
        <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
</form>

<!-- Tambahkan tombol ini -->
<a href="register.php" class="btn btn-link w-100 mt-2 text-center">Belum punya akun? Daftar di sini</a>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
