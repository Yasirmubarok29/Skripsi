<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Login/login.php");
    exit;
}
require '../conection/db.php';

$success = '';
$error = '';

// Hapus data
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM titik_evakuasi WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Titik dengan ID $id berhasil dihapus.";
    } else {
        $error = "Gagal menghapus data. " . $conn->error;
    }
}

// Simpan data baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $nama = trim($_POST['nama'] ?? '');
    $lat  = $_POST['latitude'] ?? '';
    $lng  = $_POST['longitude'] ?? '';
    $ket  = trim($_POST['keterangan'] ?? '');
    $kapasitas = trim($_POST['kapasitas'] ?? '');
    $fasilitas = trim($_POST['fasilitas'] ?? '');
    $foto = trim($_POST['foto'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '' || $lat === '' || $lng === '') {
        $error = "Nama, Latitude dan Longitude wajib diisi.";
    } elseif (!is_numeric($lat) || !is_numeric($lng)) {
        $error = "Latitude/Longitude tidak valid.";
    } elseif (!is_in_cianjur($lat, $lng)) {
        $error = "Lokasi marker di luar batas administratif Kabupaten Cianjur. Silakan pilih lokasi di dalam Cianjur!";
    } else {
       $stmt = $conn->prepare("INSERT INTO titik_evakuasi (nama, latitude, longitude, keterangan, kapasitas, fasilitas, foto, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sddsssss", $nama, $lat, $lng, $ket, $kapasitas, $fasilitas, $foto, $deskripsi);
        if ($stmt->execute()) {
        $success = "Titik berhasil disimpan.";
    } else {
        $error = "Gagal menyimpan data. " . $conn->error;
        }
    }
}
//yasir
$all = $conn->query("SELECT * FROM titik_evakuasi ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$polygons = $conn->query("SELECT nama, color, geojson FROM wilayah_bencana")->fetch_all(MYSQLI_ASSOC);

// Fungsi point in polygon sederhana (untuk Polygon dan MultiPolygon)
function pointInPolygon($point, $polygon) {
    $x = $point[0];
    $y = $point[1];
    $inside = false;
    for ($i = 0, $j = count($polygon) - 1; $i < count($polygon); $j = $i++) {
        $xi = $polygon[$i][0]; $yi = $polygon[$i][1];
        $xj = $polygon[$j][0]; $yj = $polygon[$j][1];
        $intersect = (($yi > $y) != ($yj > $y)) &&
            ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 0.0001) + $xi);
        if ($intersect) $inside = !$inside;
    }
    return $inside;
}

function is_in_cianjur($lat, $lng) {
    $geojson_path = __DIR__ . '/../data/batas_cianjur.geojson';
    if (!file_exists($geojson_path)) return false;
    $geojson = json_decode(file_get_contents($geojson_path), true);
    if (!$geojson) return false;

    if ($geojson['type'] === 'FeatureCollection') {
        foreach ($geojson['features'] as $feature) {
            if ($feature['geometry']['type'] === 'Polygon') {
                foreach ($feature['geometry']['coordinates'] as $polygon) {
                    if (pointInPolygon([$lng, $lat], $polygon)) return true;
                }
            }
            if ($feature['geometry']['type'] === 'MultiPolygon') {
                foreach ($feature['geometry']['coordinates'] as $multi) {
                    foreach ($multi as $polygon) {
                        if (pointInPolygon([$lng, $lat], $polygon)) return true;
                    }
                }
            }
        }
    }
    return false;
}

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Marker Titik</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background: #eef0f3;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
      overflow-x: hidden;
    }
    .sidebar {
      min-width: 260px;
      max-width: 260px;
      height: 100vh;
      background: linear-gradient(135deg, #343a40 0%, #212529 100%);
      padding: 0;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1040;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 16px rgba(34,34,34,0.08);
      color: #fff;
    }
    .sidebar-header {
      padding: 28px 0 22px 0;
      text-align: center;
      background: linear-gradient(90deg, #198754 0%, #157347 100%);
      border-bottom: 1px solid #dee2e6;
    }
    .sidebar-header img {
      height: 60px;
      object-fit: contain;
      margin-bottom: 10px;
      border-radius: 14px;
      box-shadow: 0 2px 14px 2px #15734750;
    }
    .sidebar-header h5 {
      font-weight: 700;
      font-size: 1.18rem;
      margin-bottom: 0;
      letter-spacing: 1px;
      color: #e9ecef;
      text-shadow: 0 1px 4px #19875433;
    }
    .nav-section {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      padding: 24px 0 8px 0;
      gap: 2px;
    }
    .sidebar .nav-link {
      color: #e9ecef;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: .8rem;
      padding: 10px 32px;
      font-size: 1.04rem;
      border-radius: 0 22px 22px 0;
      margin-bottom: 2px;
      position: relative;
      background: none;
      transition: background .18s, color .18s;
      text-shadow: 0 1px 6px #21252910;
      border: none;
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover {
      background: linear-gradient(90deg, #198754 22%, #157347 100%);
      color: #fff;
      box-shadow: 0 2px 12px 0 #19875430;
      font-weight: 700;
    }
    .sidebar .nav-link.active:before, .sidebar .nav-link:hover:before {
      content: '';
      position: absolute;
      left: 17px;
      top: 50%;
      transform: translateY(-50%);
      width: 6px;
      height: 20px;
      border-radius: 10px;
      background: linear-gradient(90deg, #198754 40%, #157347 100%);
      box-shadow: 0 2px 6px #15734720;
    }
    .sidebar .nav-link .bi {
      font-size: 1.25em;
      filter: drop-shadow(0 1px 3px #e9ecefcc);
    }
    .sidebar .logout {
      padding: 16px 32px 20px 32px;
      border-top: 1px solid #dee2e6;
      margin-top: auto;
    }
    .sidebar .logout .nav-link {
      color: #fff;
      background: #dc3545;
      font-weight: 600;
      border-radius: 0 22px 22px 0;
      text-shadow: 0 1px 6px #dc354520;
      transition: background .16s, color .16s;
      border: none;
    }
    .sidebar .logout .nav-link:hover {
      background: #b02a37;
      color: #fff;
      text-shadow: none;
    }
    .sidebar .admin-info {
      padding: 13px 32px 11px 32px;
      font-size: 0.97em;
      color: #ced4da;
      border-bottom: 1px solid #dee2e6;
      text-align: left;
      background: linear-gradient(90deg, #15734710 0%, #198754 100%);
      margin-bottom: 0;
    }
    .sidebar .admin-info i {
      font-size: 1.15em;
      margin-right: 8px;
      color: #e9ecef;
    }
    /* TOPBAR - Bootstrap style */
    .topbar-modern {
      background: linear-gradient(90deg,#f8f9fa 0%,#dee2e6 100%);
      color: #212529;
      border-radius: 0 0 14px 0;
      min-height: 58px;
      box-shadow: 0 1px 8px 0 #21252910;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 40px 0 290px;
      position: sticky;
      top: 0;
      z-index: 101;
      border-bottom: 1px solid #dee2e6;
    }
    .topbar-modern .left {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .topbar-modern .title {
      font-weight: 700;
      font-size: 1.13rem;
      letter-spacing: 1px;
      color: #0d6efd;
      margin-bottom: 2px;
      display: flex;
      align-items: center;
      gap: 9px;
    }
    .topbar-modern .version {
      background: #e9ecef;
      color: #157347;
      font-size: 0.83rem;
      font-weight: 600;
      border-radius: 12px;
      padding: 2px 10px;
      margin-left: 8px;
      box-shadow: 0 1px 5px #15734710;
    }
    .topbar-modern .user-box {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #f8f9fa;
      color: #157347;
      border-radius: 12px;
      padding: 3px 16px;
      font-size: 1rem;
      font-weight: 600;
      box-shadow: 0 2px 10px #15734710;
      border: 1px solid #dee2e6;
    }
    .topbar-modern .user-box .bi {
      font-size: 1.07em;
      margin-right: 4px;
    }
    @media (max-width: 991.98px) {
      .content-wrapper {
        margin-left: 0;
      }
      .sidebar {
        min-width: 210px;
        max-width: 210px;
      }
      .topbar-modern {
        padding: 0 8px 0 0;
        border-radius: 0 0 8px 0;
      }
      .topbar-modern .title {
        font-size: 1.01rem;
      }
    }
    @media (max-width: 575.98px) {
      .sidebar {
        min-width: 100vw;
        max-width: 100vw;
        position: relative;
        height: auto;
      }
      .content-wrapper {
        margin-left: 0;
      }
      .topbar-modern {
        padding: 0 4px 0 0;
        border-radius: 0 0 8px 0;
        min-height: 46px;
      }
      .topbar-modern .title {
        font-size: 0.94rem;
      }
      .topbar-modern .user-box {
        font-size: 0.89em;
        padding: 2px 2px;
      }
    }
    .content-wrapper {
      margin-left: 260px;
      padding: 32px 24px 24px 24px;
      min-height: 100vh;
      background: #eef0f3;
      transition: margin-left .25s cubic-bezier(.4,2,.6,1);
    }
    .content-wrapper.full {
      margin-left: 0;
    }
    .card {
      border-radius: 16px;
      box-shadow: 0 2px 12px rgba(33,37,41,.08);
      background: #fff;
      border: none;
      transition: box-shadow .2s;
    }
    .card-header-modern {
      background: linear-gradient(90deg, #0d6efd 0%, #157347 100%);
      color: #fff;
      border-radius: 16px 16px 0 0;
      padding: 18px 24px 12px 24px;
      font-size: 1.15rem;
      font-weight: 600;
      letter-spacing: .5px;
      margin-bottom: 0.5rem;
      box-shadow: 0 2px 8px rgba(33,37,41,.07);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    #map {
      width: 100%;
      height: 48vh;
      min-height: 320px;
      border-radius: 14px;
      box-shadow: 0 2px 10px rgba(33,37,41,.07);
      border: 1px solid #dee2e6;
      margin-bottom: 18px;
      background: linear-gradient(90deg, #e9ecef 0%, #f8f9fa 100%);
    }
    .card-form-marker {
      border: none;
      border-radius: 16px;
      box-shadow: 0 2px 12px rgba(33,37,41,.08);
      background: #fff;
      transition: box-shadow .2s;
      overflow: hidden;
    }
    .card-header-marker {
      background: linear-gradient(90deg,#0d6efd 0%,#157347 100%);
      color: #fff;
      border-radius: 16px 16px 0 0;
      padding: 18px 24px 12px 24px;
      font-size: 1.10rem;
      font-weight: 600;
      letter-spacing: .5px;
      box-shadow: 0 2px 8px rgba(33,37,41,.07);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .card-form-marker .form-label {
      font-weight: 600;
      color: #157347;
      font-size: 1.01em;
      margin-bottom: 5px;
    }
    .card-form-marker .form-control,
    .card-form-marker textarea {
      border-radius: 12px;
      border: 1px solid #dee2e6;
      padding: 10px 14px;
      font-size: 1em;
      box-shadow: 0 2px 8px #dee2e670;
      margin-bottom: 10px;
      background: #f8f9fa;
      color: #333;
    }
    .card-form-marker textarea {
      min-height: 60px;
    }
    .card-form-marker .btn-primary {
      background: linear-gradient(90deg,#0d6efd 60%,#157347 100%);
      border: none;
      border-radius: 12px;
      font-weight: 700;
      font-size: 1em;
      box-shadow: 0 2px 8px #15734740;
      margin-top: 10px;
    }
    .card-form-marker .btn-primary:hover {
      background: linear-gradient(90deg,#157347 60%,#0d6efd 100%);
      color: #fffbe7;
    }
    .panel-shadow {
      box-shadow: 0 2px 18px rgba(33,37,41,.09);
      border-radius: 14px;
      background: #fff;
      margin-bottom: 16px;
    }
    .btn-fab {
      position: fixed;
      right: 22px;
      bottom: 22px;
      width: 54px;
      height: 54px;
      border-radius: 50%;
      background: linear-gradient(90deg, #0d6efd 60%, #157347 100%);
      color: #fff;
      font-size: 2.3em;
      font-weight: 900;
      box-shadow: 0 2px 18px #15734769;
      border: none;
      z-index: 999;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .2s;
    }
    .btn-fab:hover {
      background: linear-gradient(90deg, #157347 60%, #0d6efd 100%);
      color: #fffbe7;
    }
    .table thead {
      background: #f8f9fa;
    }
    .table-hover tbody tr:hover {
      background-color: #e9ecef !important;
      transition: background .2s;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background-color: #f6f6f6;
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<nav id="sidebar" class="sidebar d-flex flex-column">
  <div class="sidebar-header">
    <img src="../assets/logo1.png" alt="Logo1 WebGIS">
  </div>
  <div class="admin-info mb-0">
    <i class="bi bi-person-circle"></i>
    <span>Selamat datang, <b><?= htmlspecialchars($_SESSION['admin'] ?? 'Admin') ?></b></span>
  </div>
  <div class="nav-section">
    <a href="index.php" class="nav-link">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="marker.php" class="nav-link active">
      <i class="bi bi-geo-alt-fill"></i> Titik Evakuasi
    </a>
    <a href="poligon.php" class="nav-link">
      <i class="bi bi-vector-pen"></i> Wilayah Bencana
    </a>
  </div>
  <div class="logout mt-auto">
    <a href="../Login/logout.php" class="nav-link">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</nav>

<!-- TOPBAR MODERN -->
<div class="topbar-modern">
  <div class="left">
    <span class="title"><i class="bi bi-geo-alt-fill"></i> Marker Titik Evakuasi <span class="version">v1.0</span></span>
  </div>
  <div class="user-box">
    <i class="bi bi-person-circle"></i>
    <?= htmlspecialchars($_SESSION['admin'] ?? 'Admin') ?>
  </div>
</div>

<!-- MAIN CONTENT -->
<div id="contentWrapper" class="content-wrapper">
    <?php if ($error): ?><div class="alert alert-danger mt-3"><?= esc($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success mt-3"><?= esc($success) ?></div><?php endif; ?>

    <div class="row g-4 mb-2">
        <div class="col-lg-8">
            <div class="panel-shadow mb-3">
                <div class="card-header-modern"><i class="bi bi-geo-alt-fill"></i> Pilih lokasi pada peta atau isi koordinat manual
                    <button class="btn btn-sm btn-outline-dark float-end" type="button" onclick="map.setView([-6.82, 107.14], 10)"><i class="bi bi-arrow-clockwise"></i> Reset Peta</button>
                </div>
                <div class="p-3 bg-white rounded-bottom"><div id="map"></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-form-marker">
                <div class="card-header-marker">
                  <i class="bi bi-plus-circle"></i> Form Tambah Titik Evakuasi
                </div>
                <div class="p-4">
                <form method="post" autocomplete="off" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="create">
                  <div class="mb-2">
                    <label class="form-label" for="nama">Nama Titik/Posko</label>
                    <input type="text" name="nama" id="nama" class="form-control" required placeholder="Contoh: Posko Utama">
                  </div>
                  <div class="mb-2">
                    <label class="form-label" for="kapasitas">Kapasitas</label>
                    <input type="text" name="kapasitas" id="kapasitas" class="form-control" placeholder="Misal: 100 orang">
                  </div>
                  <div class="mb-2">
                    <label class="form-label" for="fasilitas">Fasilitas Dasar</label>
                    <input type="text" name="fasilitas" id="fasilitas" class="form-control" placeholder="Misal: Toilet, Air Bersih, Dapur Umum">
                  </div>
                  <div class="row mb-2">
                    <div class="col-md-6">
                      <label class="form-label" for="lat">Latitude</label>
                      <input type="text" name="latitude" id="lat" class="form-control" required placeholder="-6.82">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="lng">Longitude</label>
                      <input type="text" name="longitude" id="lng" class="form-control" required placeholder="107.14">
                    </div>
                  </div>
                  <div class="mb-2">
                    <label class="form-label" for="foto">Foto (URL)</label>
                    <input type="text" name="foto" id="foto" class="form-control" placeholder="URL gambar atau upload">
                    <!-- Atau bisa <input type="file"> jika ingin upload, tapi perlu handling upload di PHP -->
                  </div>
                  <div class="mb-2">
                    <label class="form-label" for="deskripsi">Deskripsi Kondisi</label>
                    <textarea name="deskripsi" id="deskripsi" class="form-control" placeholder="Deskripsi kondisi tempat..."></textarea>
                  </div>
                  <div class="d-grid gap-2 mt-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Simpan Titik</button>
                  </div>
                </form>
                <div class="mt-3 small text-muted">
                  <i class="bi bi-info-circle"></i> Klik pada peta untuk mengisi otomatis koordinat Latitude & Longitude.
                </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4 shadow-sm">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check"></i> Daftar Semua Titik Evakuasi</span>
            <input type="text" id="searchTable" class="form-control form-control-sm w-auto" placeholder="Cari nama..." style="min-width:180px;">
        </div>
        <div class="p-3 bg-white rounded-bottom">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped table-sm align-middle" id="titikTable">
          <thead class="table-light align-middle">
            <tr>
              <th class="fw-bold text-center">#</th>
              <th class="fw-bold">Nama Titik Evakuasi</th>
              <th class="fw-bold">Kapasitas</th>
              <th class="fw-bold">Fasilitas Dasar</th>
              <th class="fw-bold">Koordinat/Lokasi</th>
              <th class="fw-bold">Foto/Deskripsi Kondisi</th>
              <th class="fw-bold">Dibuat</th>
              <th class="fw-bold text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($all): $no=1; foreach($all as $row): ?>
            <tr>
              <td class="text-center text-secondary small"><?= $no++ ?></td>
              <td class="fw-semibold text-dark"><?= esc($row['nama']) ?></td>
              <td><?= esc($row['kapasitas'] ?? '-') ?></td>
              <td><?= esc($row['fasilitas'] ?? '-') ?></td>
              <td>
                <span class="badge rounded-pill bg-primary-subtle text-primary small px-2 py-1"><?= esc($row['latitude']) ?></span>
                <span class="badge rounded-pill bg-primary-subtle text-primary small px-2 py-1"><?= esc($row['longitude']) ?></span>
              </td>
              <td>
                <?php if (!empty($row['foto'])): ?>
                  <img src="<?= esc($row['foto']) ?>" alt="Foto" style="max-width:80px;max-height:60px;border-radius:6px"><br>
                <?php endif; ?>
                <?= esc($row['deskripsi'] ?? '-') ?>
              </td>
              <td><span class="badge rounded-pill bg-secondary-subtle text-dark small px-2 py-1"><?= esc($row['waktu_dibuat']) ?></span></td>
              <td class="text-center">
                <a href="#" class="btn btn-sm btn-outline-danger rounded-circle btn-delete" data-id="<?= $row['id'] ?>" title="Hapus">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="8" class="text-center text-muted">Belum ada data</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        </div>
        </div>
    </div>

    <!-- Floating Action Button (FAB) for quick add -->
    <button class="btn-fab d-lg-none" title="Tambah Titik" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="bi bi-plus-lg"></i></button>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="confirmDeleteLabel"><i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus titik ini?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <a href="#" id="btnDeleteConfirm" class="btn btn-danger">Hapus</a>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Sidebar toggle (mobile)
    const sidebar = document.getElementById('sidebar');
    const contentWrapper = document.getElementById('contentWrapper');
    const btnToggle = document.getElementById('btnToggle');
    if(btnToggle) {
        btnToggle.addEventListener('click', () => {
            if (sidebar.style.transform === 'translateX(-100%)') {
                sidebar.style.transform = '';
                contentWrapper.classList.remove('full');
            } else {
                sidebar.style.transform = 'translateX(-100%)';
                contentWrapper.classList.add('full');
            }
        });
    }

    // Modal konfirmasi hapus
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.btn-delete').forEach(function(el) {
        el.addEventListener('click', function(e) {
          e.preventDefault();
          var id = this.getAttribute('data-id');
          var deleteUrl = '?delete=' + id;
          document.getElementById('btnDeleteConfirm').setAttribute('href', deleteUrl);
          var modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
          modal.show();
        });
      });
    });

    // Interaktif: filter tabel
    document.getElementById('searchTable').addEventListener('input', function() {
        const val = this.value.toLowerCase();
        const rows = document.querySelectorAll('#titikTable tbody tr');
        rows.forEach(row => {
            const nama = row.children[1]?.textContent.toLowerCase() || '';
            row.style.display = nama.includes(val) ? '' : 'none';
        });
    });
    </script>

</div>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
// Inisialisasi peta hanya sekali
const map = L.map('map').setView([-6.82, 107.14], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Tampilkan polygon bencana dari database
map.createPane('polygonPane');
map.getPane('polygonPane').style.zIndex = 650;
const polygons = <?= json_encode($polygons, JSON_UNESCAPED_UNICODE) ?>;
polygons.forEach(p => {
  const layer = L.geoJSON(JSON.parse(p.geojson), {
    pane: 'polygonPane',
    style: { color: p.color, fillColor: p.color, fillOpacity: 0.4 }
  }).addTo(map);

  // pastikan selalu di atas
  layer.bringToFront();

  let popupHtml = `
    <div style="min-width:220px; position: relative; z-index: 1;">
      <div class="fw-bold mb-1"><i class="bi bi-vector-pen text-success"></i> ${p.nama}</div>
      <div class="mb-1">
        <span class="badge " style="background:${p.color};">
          ${p.status}
        </span>
      </div>
      <div class="mb-1"><b>Luas:</b> ${p.luas ? (Number(p.luas).toLocaleString('id') + ' m²') : '-'}</div>
      <div class="mb-1"><b>Waktu dibuat:</b> ${p.created_at}</div>
    </div>
  `;
  
  layer.bindPopup(popupHtml);
});

// Tampilkan marker titik posko dari database
const markers = <?= json_encode($all, JSON_UNESCAPED_UNICODE) ?>;
markers.forEach(m => {
    const marker = L.marker([m.latitude, m.longitude]).addTo(map);
    marker.bindPopup(`
      <div style='min-width:180px'>
        <div class='fw-bold mb-1'><i class='bi bi-geo-alt-fill text-primary'></i> ${m.nama}</div>
        <div class='mb-1'><b>Kapasitas:</b> ${m.kapasitas || '-'}<br><b>Fasilitas:</b> ${m.fasilitas || '-'}</div>
        <div class='small text-muted mb-1'>Lat: <b>${m.latitude}</b> | Lng: <b>${m.longitude}</b></div>
        <div class='mb-1'>${m.deskripsi ? m.deskripsi : '<span class="text-muted fst-italic">Tidak ada deskripsi</span>'}</div>
        ${m.foto ? `<img src="${m.foto}" alt="Foto" style="max-width:100px;max-height:80px;border-radius:6px"><br>` : ''}
        <div class='text-secondary small'>${m.waktu_dibuat ? 'Dibuat: ' + m.waktu_dibuat : ''}</div>
      </div>
    `);
    marker.bindTooltip(`<b>${m.nama}</b>`, {permanent:false, direction:'top'});
});

// Klik peta untuk pilih titik
let marker = null;
map.on('click', function(e) {
    const { lat, lng } = e.latlng;
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    if (latInput && lngInput) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
    }
    if (marker) map.removeLayer(marker);
    marker = L.marker([lat, lng]).addTo(map)
        .bindTooltip(`Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`, {permanent:false, direction:'top'}).openTooltip();
});



// Tambahkan batas administratif Cianjur (GeoJSON statis)
const boundaryLayers = [
  {
    url: '../data/batas_cianjur.geojson',
    tooltip: 'Wilayah Administratif Cianjur'
  },
  {
    url: '../data/batas_kecamatan.geojson',
    tooltip: 'Wilayah Administratif Kecamatan Cianjur'
  },
  {
    url: '../data/batas_kelurahan.geojson',
    tooltip: 'Wilayah Administratif Kelurahan Cianjur'
  }
];

boundaryLayers.forEach(({url, tooltip}) => {
  fetch(url)
    .then(res => {
      if (!res.ok) throw new Error(`Gagal memuat file ${url}`);
      return res.json();
    })
    .then(data => {
      L.geoJSON(data, {
        style: {
          color: '#198754',
          weight: 2,
          fillOpacity: 0.1,
          dashArray: '5,5'
        }
      }).addTo(map).bindTooltip(tooltip, {permanent: false, direction: 'top'});
    })
    .catch(err => console.error(`Gagal memuat ${url}:`, err));
});
</script>
</body>
</html>