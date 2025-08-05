<?php
require 'conection/db.php';

// Ambil ringkasan data wilayah bencana
$bencana = $conn->query("SELECT nama, color, created_at FROM wilayah_bencana ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Ambil jumlah titik evakuasi dan wilayah bencana
$jumlah_titik   = $conn->query("SELECT COUNT(*) FROM titik_evakuasi")->fetch_row()[0];
$jumlah_polygon = $conn->query("SELECT COUNT(*) FROM wilayah_bencana")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>WebGIS Evakuasi – Informasi Bencana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f9f9f9; }
    .hero { background-color: #f57c00; color: white; padding: 60px 0; }
    .btn-start { background-color: #fff; color: #f57c00; font-weight: 600; }
    .badge-color { display: inline-block; width: 16px; height: 16px; border-radius: 4px; margin-right: 5px; }
    .section { padding: 40px 0; }
  </style>
</head>
<body>

<!-- HERO -->
<div class="hero text-center">
  <div class="container">
    <h1 class="display-5 fw-bold">Selamata Datang</h1>
    <h1 class="display-5 fw-bold">Sistem Informasi Jalur Evakuasi Bencana</h1>
    <p class="lead">Pantau kondisi bencana dan akses jalur evakuasi terdekat secara real-time</p>
    <a href="home.php" class="btn btn-lg btn-start mt-3">Masuk ke Halaman Evakuasi</a>
  </div>
</div>

<!-- INFORMASI STATISTIK -->
<div class="section bg-white text-center">
  <div class="container">
    <h4 class="mb-4"> Statistik Wilayah</h4>
    <div class="row justify-content-center">
      <div class="col-md-4 mb-3">
        <div class="card shadow-sm p-4">
          <h1 class="text-warning"><?= $jumlah_polygon ?></h1>
          <p>Wilayah Bencana Terdata</p>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card shadow-sm p-4">
          <h1 class="text-success"><?= $jumlah_titik ?></h1>
          <p>Titik Evakuasi Tersedia</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- DAFTAR WILAYAH BENCANA -->
<div class="section">
  <div class="container">
    <h4 class="mb-4 text-center">Daftar Wilayah Bencana Terkini</h4>
    <?php if (count($bencana) > 0): ?>
      <div class="table-responsive">
        <table class="table table-bordered text-center">
          <thead class="table-light">
            <tr>
              <th>Nama Wilayah</th>
              <th>Warna Penanda</th>
              <th>Waktu Dicatat</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bencana as $b): ?>
              <tr>
                <td><?= htmlspecialchars($b['nama']) ?></td>
                <td><span class="badge-color" style="background:<?= $b['color'] ?>"></span><?= $b['color'] ?></td>
                <td><?= date('d M Y H:i', strtotime($b['created_at'])) ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-muted text-center">Belum ada data wilayah bencana yang tercatat.</p>
    <?php endif ?>
  </div>
</div>

<!-- FOOTER -->
<div class="text-center text-muted py-3 bg-light">
  &copy; <?= date('Y') ?> WebGIS Evakuasi – Kabupaten Cianjur
</div>

</body>
</html>
