<?php
// =============================
// Koneksi & Query Data Utama
// =============================
require 'conection/db.php';

// Fungsi untuk escape output
function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Ambil ringkasan data wilayah bencana terbaru (max 5)
$bencana = $conn->query("SELECT nama, color, status, created_at FROM wilayah_bencana ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Hitung jumlah titik evakuasi dan wilayah bencana
$jumlah_titik   = $conn->query("SELECT COUNT(*) FROM titik_evakuasi")->fetch_row()[0];
$jumlah_polygon = $conn->query("SELECT COUNT(*) FROM wilayah_bencana")->fetch_row()[0];

// Fungsi badge status
function status_color($status) {
    switch (strtolower($status)) {
        case 'bahaya': return 'bg-danger text-white';
        case 'siaga': return 'bg-warning text-dark';
        case 'waspada': return 'bg-primary text-white';
        default: return 'bg-secondary text-white';
    }
}
function status_icon($status) {
    switch (strtolower($status)) {
        case 'bahaya': return 'bi-exclamation-octagon';
        case 'siaga': return 'bi-exclamation-triangle';
        case 'waspada': return 'bi-exclamation-circle';
        default: return 'bi-question-circle';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>WebGIS Evakuasi – Informasi Bencana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Custom Styles -->
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f9f9f9; }
    .hero { background: linear-gradient(90deg,#ff9800 0%,#f57c00 100%); color: white; padding: 60px 0 40px 0; }
    .btn-start { background-color: #fff; color: #f57c00; font-weight: 600; border-radius: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .btn-start:hover { background: #ffe0b2; color: #d35400; }
    .badge-color { display: inline-block; width: 18px; height: 18px; border-radius: 4px; margin-right: 5px; border:1px solid #eee; }
    .section { padding: 40px 0; }
    .card-stat { border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.08); border:none; }
    .card-stat .icon { font-size:2.5rem; margin-bottom:10px; }
    .table th, .table td { vertical-align: middle !important; }
    .footer { background: #fff3e0; color: #f57c00; text-align: center; padding: 10px 0 6px 0; font-size: 1rem; letter-spacing: 1px; border-top: 1px solid #ffe0b2; }
  </style>
</head>
<body>


<!-- ============================= -->
<!-- HERO SECTION -->
<!-- ============================= -->
<div class="hero text-center">
  <div class="container">
    <div class="mb-3">
      <img src="assets/logo1.png" alt="Logo1" style="width:250px;object-fit:contain;">
    </div>
    <h1 class="display-5 fw-bold mb-2">
      <i class="bi bi-geo-alt-fill me-2"></i>Selamat Datang
    </h1>
    <h2 class="fw-bold mb-2">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>Sistem Informasi Jalur Evakuasi Bencana
    </h2>
    <p class="lead mb-3">
      <i class="bi bi-info-circle-fill me-2"></i>Pantau kondisi bencana dan akses jalur evakuasi terdekat secara real-time
    </p>
    <a href="home.php" class="btn btn-lg btn-start mt-3 d-flex align-items-center justify-content-center gap-2">
      <i class="bi bi-arrow-right-circle-fill"></i> Masuk ke Halaman Evakuasi
    </a>
  </div>
</div>


<!-- ============================= -->
<!-- STATISTIK WILAYAH (INFO KARTU) -->
<!-- ============================= -->
<div class="section bg-white text-center">
  <div class="container">
    <h4 class="mb-4">
      <i class="bi bi-bar-chart-fill me-2"></i>Statistik Wilayah
    </h4>
    <div class="row justify-content-center">
      <!-- Kartu Wilayah Bencana -->
      <div class="col-md-4 mb-3">
        <div class="card card-stat text-center p-4">
          <div class="icon text-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <h1 class="text-warning mb-0"><?= $jumlah_polygon ?></h1>
          <p class="mb-0">Wilayah Bencana Terdata</p>
        </div>
      </div>
      <!-- Kartu Titik Evakuasi -->
      <div class="col-md-4 mb-3">
        <div class="card card-stat text-center p-4">
          <div class="icon text-success"><i class="bi bi-flag-fill"></i></div>
          <h1 class="text-success mb-0"><?= $jumlah_titik ?></h1>
          <p class="mb-0">Titik Evakuasi Tersedia</p>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- ============================= -->
<!-- DAFTAR WILAYAH BENCANA TERKINI -->
<!-- ============================= -->
<div class="section">
  <div class="container">
    <h4 class="mb-4 text-center">
      <i class="bi bi-list-ul me-2"></i>Daftar Wilayah Bencana Terkini
    </h4>
    <?php if (count($bencana) > 0): ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center">
          <thead class="table-light">
            <tr>
              <th><i class="bi bi-geo-alt-fill"></i> Nama Wilayah</th>
              <th><i class="bi bi-shield-fill-exclamation"></i> Status Bencana</th>
              <th><i class="bi bi-clock-history"></i> Waktu Dicatat</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bencana as $b): ?>
              <tr>
                <td><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= esc($b['nama']) ?></td>
                <td>
                  <span class="badge <?= status_color($b['status']) ?>" style="font-size:1em;">
                    <i class="bi <?= status_icon($b['status']) ?>"></i>
                    <?= esc(ucfirst($b['status'])) ?>
                  </span>
                </td>
                <td><i class="bi bi-clock-history text-warning me-1"></i> <?= date('d M Y H:i', strtotime($b['created_at'])) ?></td>
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


<!-- ============================= -->
<!-- FOOTER -->
<!-- ============================= -->
<div class="text-center text-muted py-3 bg-light">
  <div class="footer">
    &copy; <?= date('Y') ?> WebGIS Evakuasi – Kabupaten Cianjur
  </div>
</div>

</body>
</html>