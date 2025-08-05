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
    body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
    .main-content { margin-left: 260px; padding: 20px; }
    #map { height: 60vh; border-radius: 8px; margin-bottom: 20px; }
    :root{
      --orange:#ff6f00;
      --sidebar-bg:#1f2937;
      --sidebar-hover:#374151;
    }
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: 250px;
      height: 100vh;
      background-color: #1f2937;
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .sidebar .brand{
      display:flex;
      align-items:center;
      gap:.5rem;
      width: 100%;
      padding:16px 20px;
      background:var(--orange);
      font-weight:600;
    }
    .sidebar .brand img{
      height:30px;
    }
    .sidebar .nav-links {
      width: 100%;
    }
    .sidebar .nav-links a {
      display: block;
      color: #fff;
      padding: 10px 20px;
      text-decoration: none;
    }
    .sidebar .nav-links a.active,
    .sidebar .nav-links a:hover {
      background-color: #374151;
    }
    .sidebar .logout {
      margin-top: auto;
      padding: 20px;
    }
    .topbar {
      height: 60px;
      background: #ff6f00;
      color: white;
      padding: 0 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-left: 250px;
    }
  </style>
</head>
<body>
<aside class="sidebar">
  <div class="brand">
    <img src="../assets/logo.png" alt="Logo" width="110" height="100" />
  </div>
  <nav class="nav-links">
    <a href="index.php">🏠 Dashboard</a>
    <a href="marker.php">📍 Tambah Marker Titik</a>
    <a href="poligon.php" class="active">🗺️ Tambah Polygon Bencana</a>
  </nav>
  <div class="logout">
    <a href="../Login/logout.php" class="btn btn-outline-light btn-sm">🚪 Logout</a>
  </div>
</aside>
<div class="topbar">
  <h5 class="mb-0">🗺️ Kelola Polygon Wilayah Bencana</h5>
  <span>Halo, <?= htmlspecialchars($_SESSION['admin']) ?></span>
</div>
<div class="main-content">
  <form id="formPolygon" method="post">
    <input type="hidden" name="action" value="simpan" id="formAction">
    <input type="hidden" name="id" id="polygonId">
    <input type="hidden" name="geojson" id="geojsonData">
    <input type="hidden" name="color" id="color" value="#ff0000">
    <div class="row g-2">
      <div class="col-md-3"><input type="text" name="nama" id="nama" class="form-control" placeholder="Nama" required></div>
      <div class="col-md-3">
        <select name="status" id="status" class="form-select" required>
          <option value="bahaya">🔴 Bahaya</option>
          <option value="siaga">🟠 Siaga</option>
          <option value="waspada">🟡 Waspada</option>
        </select>
      </div>
      <div class="col-md-6"><button type="submit" class="btn btn-success w-100">💾 Simpan</button></div>
    </div>
  </form>
  <div id="map" class="my-3"></div>
  <table class="table table-bordered table-sm">
    <thead><tr><th>#</th><th>Nama</th><th>Status</th><th>Warna</th><th>Waktu</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php $no=1; foreach ($polygons as $p): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($p['nama']) ?></td>
        <td><?= htmlspecialchars($p['status']) ?></td>
        <td><span style="background:<?= $p['color'] ?>;width:40px;height:20px;display:inline-block;"></span></td>
        <td><?= $p['created_at'] ?></td>
        <td>
          <button onclick='editPolygon(<?= json_encode($p) ?>)' class="btn btn-sm btn-warning">✏ Edit</button>
          <a href="?hapus=<?= $p['id'] ?>" onclick="return confirm('Hapus?')" class="btn btn-sm btn-danger">🗑</a>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
</div>
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
