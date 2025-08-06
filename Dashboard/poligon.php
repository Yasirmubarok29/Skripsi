<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Login/login.php");
    exit;
}

require '../conection/db.php';

// DELETE polygon
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM wilayah_bencana WHERE id = $id");
    header("Location: poligon.php");
    exit;
}

// INSERT polygon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'simpan') {
    $nama = $_POST['nama'];
    $color = $_POST['color'];
    $geojson = $_POST['geojson'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO wilayah_bencana (nama, color, geojson, status, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $nama, $color, $geojson, $status);
    $stmt->execute();
    header("Location: poligon.php");
    exit;
}

// UPDATE polygon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
    $id = intval($_POST['id']);
    $nama = $_POST['nama'];
    $color = $_POST['color'];
    $geojson = $_POST['geojson'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE wilayah_bencana SET nama=?, color=?, geojson=?, status=? WHERE id=?");
    $stmt->bind_param("ssssi", $nama, $color, $geojson, $status, $id);
    $stmt->execute();
    header("Location: poligon.php");
    exit;
}

// Ambil data
$polygons = $conn->query("SELECT * FROM wilayah_bencana ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$markers = $conn->query("SELECT nama, latitude, longitude FROM titik_evakuasi")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Kelola Polygon Bencana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
  <style>
    :root {
      --orange: #ff6f00;
      --sidebar-bg: #1f2937;
      --sidebar-hover: #374151;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f5f5f5;
      overflow-x: hidden;
    }
    .sidebar {
      min-width: 250px;
      max-width: 250px;
      height: 100vh;
      background: var(--orange);
      border-right: none;
      padding: 0;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1030;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 8px rgba(0,0,0,.07);
    }
    .sidebar.collapsed {
      transform: translateX(-100%);
    }
    .sidebar .brand {
      display: flex;
      align-items: center;
      gap: .5rem;
      width: 100%;
      padding: 18px 20px 14px 20px;
      background: transparent;
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: 1px;
      border-bottom: 1px solid #fff2;
      color: #fff;
    }
    .sidebar .brand img {
      height: 36px;
      margin-right: 8px;
    }
    .sidebar .nav-links {
      width: 100%;
      padding: 16px 0;
    }
    .sidebar .nav-links a {
      display: block;
      color: #fff;
      padding: 12px 24px;
      text-decoration: none;
      border-radius: 6px 0 0 6px;
      margin-bottom: 2px;
      font-size: 1rem;
      transition: background .2s;
      font-weight: 500;
    }
    .sidebar .nav-links a.active,
    .sidebar .nav-links a:hover {
      background-color: #fff;
      color: var(--orange);
      font-weight: 700;
    }
    .sidebar .logout {
      margin-top: auto;
      padding: 16px 20px 24px 20px;
      border-top: 1px solid rgba(255,255,255,.08);
    }
    .sidebar .logout a {
      width: 100%;
      display: block;
      text-align: left;
      color: #fff !important;
      font-weight: 500;
      background: #d32f2f;
      border-radius: 6px;
      padding: 10px 18px;
      margin-top: 8px;
      transition: background .2s;
    }
    .sidebar .logout a:hover {
      background: #b71c1c;
      color: #fff !important;
    }
    .topbar {
      height: 64px;
      background: #ff6f00;
      color: white;
      padding: 0 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #fff2;
      margin-left: 250px;
      margin-bottom: 0;
      position: sticky;
      top: 0;
      z-index: 1100;
    }
    .btn-toggle {
      background: var(--orange);
      color: #fff;
      border: none;
      padding: 7px 12px;
      border-radius: 6px;
      font-size: 1.2rem;
      margin-right: 10px;
      transition: background .2s;
    }
    .btn-toggle:hover {
      background: #e65c00;
    }
    .main-content {
      margin-left: 250px;
      padding: 32px 24px 24px 24px;
      min-height: 100vh;
      background: #f5f5f5;
      transition: margin-left .25s cubic-bezier(.4,2,.6,1);
    }
    .main-content.full {
      margin-left: 0;
    }
    .card {
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.08);
      background: #fff;
      border: none;
      transition: box-shadow .2s;
    }
    .card-header-modern {
      background: linear-gradient(90deg, #ff6f00 0%, #ff9800 100%);
      color: #fff;
      border-radius: 12px 12px 0 0;
      padding: 18px 24px 12px 24px;
      font-size: 1.2rem;
      font-weight: 600;
      letter-spacing: .5px;
      margin-bottom: 0.5rem;
      box-shadow: 0 2px 8px rgba(255,111,0,.08);
    }
    #map {
      height: 48vh;
      min-height: 320px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,.10);
      border: 1px solid #eee;
      margin-bottom: 20px;
    }
    .table {
      border-radius: 10px;
      overflow: hidden;
      background: #fff;
      transition: box-shadow .2s;
    }
    .table thead {
      background: #f8f9fa;
    }
    .table-hover tbody tr:hover {
      background: #ffe0b2 !important;
      transition: background .2s;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background-color: #f6f6f6;
    }
    .btn-success {
      background: var(--orange);
      border: none;
      transition: background .2s;
    }
    .btn-success:hover, .btn-warning:hover {
      background: #e65c00;
      color: #fff;
    }
    .form-label {
      font-weight: 500;
    }
    @media (max-width: 991.98px) {
      .main-content {
        margin-left: 0;
        padding: 18px 6px 6px 6px;
      }
      .sidebar {
        width: 210px;
      }
      .topbar {
        padding: 0 12px;
        margin-left: 0;
      }
    }
  </style>
</head>
<body>
<nav class="sidebar d-flex flex-column">
  <div class="brand justify-content-center">
    <img src="../assets/logo.png" alt="Logo" height="36" style="margin-right:8px;">
    <span style="color:#fff;">Evakuasi</span>
  </div>
  <div class="nav-links flex-grow-1">
    <a href="index.php" class="d-flex align-items-center gap-2 nav-link">
      <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
    </a>
    <a href="marker.php" class="d-flex align-items-center gap-2 nav-link">
      <i class="bi bi-geo-alt-fill"></i> <span>Tambah Marker Titik</span>
    </a>
    <a href="poligon.php" class="d-flex align-items-center gap-2 nav-link active">
      <i class="bi bi-vector-pen"></i> <span>Tambah Polygon Bencana</span>
    </a>
  </div>
  <div class="logout mt-auto mb-2">
    <a href="../Login/logout.php" class="nav-link d-flex align-items-center gap-2">
      <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
    </a>
  </div>
</nav>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<div class="topbar">
  <button id="btnToggle" class="btn-toggle d-lg-none me-2">☰</button>
  <h5 class="mb-0">Kelola Polygon Wilayah Bencana</h5>
  <span>Halo, <?= htmlspecialchars($_SESSION['admin']) ?></span>
</div>
<div class="main-content">
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <div class="card shadow-sm h-100">
        <div class="card-header-modern">Peta Wilayah Bencana</div>
        <div class="p-3"><div id="map" class="mb-2"></div></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-header-modern">Form Polygon Wilayah Bencana</div>
        <div class="p-3">
          <form id="formPolygon" method="post">
            <input type="hidden" name="action" value="simpan" id="formAction">
            <input type="hidden" name="id" id="polygonId">
            <input type="hidden" name="geojson" id="geojsonData">
            <input type="hidden" name="color" id="color" value="#ff0000">
            <div class="mb-3">
              <label class="form-label">Nama Wilayah</label>
              <input type="text" name="nama" id="nama" class="form-control" placeholder="Nama Wilayah" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status" id="status" class="form-select" required>
                <option value="bahaya">Bahaya</option>
                <option value="siaga">Siaga</option>
                <option value="waspada">Waspada</option>
              </select>
            </div>
            <button type="submit" class="btn btn-success w-100">Simpan Polygon</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="card shadow-sm">
    <div class="card-header-modern d-flex justify-content-between align-items-center">
      <span>Daftar Polygon Wilayah Bencana</span>
      <input type="text" id="searchTable" class="form-control form-control-sm w-auto" placeholder="Cari nama..." style="min-width:180px;">
    </div>
    <div class="p-3">
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped table-sm align-middle" id="polygonTable">
          <thead class="table-light">
            <tr><th>#</th><th>Nama</th><th>Status</th><th>Warna</th><th>Waktu</th><th>Aksi</th></tr>
          </thead>
          <tbody>
          <?php $no=1; foreach ($polygons as $p): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars($p['nama']) ?></td>
              <td><?= htmlspecialchars($p['status']) ?></td>
              <td><span style="background:<?= $p['color'] ?>;width:40px;height:20px;display:inline-block;border-radius:4px;"></span></td>
              <td><?= $p['created_at'] ?></td>
              <td>
                <button onclick='editPolygon(<?= json_encode($p) ?>)' class="btn btn-sm btn-warning">Edit</button>
                <a href="?hapus=<?= $p['id'] ?>" onclick="return confirm('Hapus?')" class="btn btn-sm btn-danger">Hapus</a>
              </td>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
// Sidebar toggle (mobile)
const sidebar = document.querySelector('.sidebar');
const contentWrapper = document.querySelector('.main-content');
const btnToggle = document.getElementById('btnToggle');
if(btnToggle) {
    btnToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        contentWrapper.classList.toggle('full');
    });
}
// Interaktif: filter tabel polygon
document.getElementById('searchTable').addEventListener('input', function() {
    const val = this.value.toLowerCase();
    const rows = document.querySelectorAll('#polygonTable tbody tr');
    rows.forEach(row => {
        const nama = row.children[1]?.textContent.toLowerCase() || '';
        row.style.display = nama.includes(val) ? '' : 'none';
    });
});
</script>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script>
const map = L.map('map').setView([-6.82, 107.14], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
const drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);
map.addControl(new L.Control.Draw({
  draw: { polygon: true, marker: false, polyline: false, rectangle: false, circle: false, circlemarker: false },
  edit: { featureGroup: drawnItems }
}));
map.on(L.Draw.Event.CREATED, e => {
  drawnItems.clearLayers();
  drawnItems.addLayer(e.layer);
});
const polygons = <?= json_encode($polygons, JSON_UNESCAPED_UNICODE) ?>;
polygons.forEach(p => {
  const layer = L.geoJSON(JSON.parse(p.geojson), {
    style: { color: p.color, fillColor: p.color, fillOpacity: 0.4 }
  }).addTo(map).bindPopup(`${p.nama} - ${p.status}`);
});
const markerData = <?= json_encode($markers, JSON_UNESCAPED_UNICODE) ?>;
markerData.forEach(m => {
  L.marker([m.latitude, m.longitude]).addTo(map).bindPopup(`Evakuasi: ${m.nama}`);
});
document.getElementById('formPolygon').addEventListener('submit', function(e) {
  if (drawnItems.getLayers().length === 0) {
    alert("Silakan gambar polygon terlebih dahulu.");
    e.preventDefault();
    return;
  }
  const geojson = drawnItems.toGeoJSON();
  document.getElementById('geojsonData').value = JSON.stringify(geojson);
});

// Tetapkan warna otomatis berdasarkan status
document.getElementById('status').addEventListener('change', function () {
  const status = this.value;
  const colorInput = document.getElementById('color');
  let color = '#cccccc';
  if (status === 'bahaya') color = '#ff0000';
  else if (status === 'siaga') color = '#ffa500';
  else if (status === 'waspada') color = '#ffff00';
  colorInput.value = color;
});

// Jalankan di awal
window.addEventListener('DOMContentLoaded', function () {
  document.getElementById('status').dispatchEvent(new Event('change'));
});

function editPolygon(data) {
  drawnItems.clearLayers();
  const layer = L.geoJSON(JSON.parse(data.geojson));
  drawnItems.addLayer(layer);
  map.fitBounds(layer.getBounds());

  document.getElementById('formAction').value = 'update';
  document.getElementById('polygonId').value = data.id;
  document.getElementById('nama').value = data.nama;
  document.getElementById('status').value = data.status;

  // Tetapkan warna otomatis berdasarkan status
  let color = '#cccccc';
  if (data.status === 'bahaya') color = '#ff0000';
  else if (data.status === 'siaga') color = '#ffa500';
  else if (data.status === 'waspada') color = '#ffff00';
  document.getElementById('color').value = color;
}
</script>
</body>
</html>
