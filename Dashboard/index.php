<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Login/login.php");
    exit;
}
require '../conection/db.php';

// --- Titik evakuasi (marker) ---
$markers = [];
$res1 = $conn->query("SELECT id, nama, latitude, longitude, waktu_dibuat FROM titik_evakuasi");
while ($r = $res1->fetch_assoc()) {
    $markers[] = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [(float)$r['longitude'], (float)$r['latitude']]
        ],
        'properties' => [
            'id'           => (int)$r['id'],
            'nama'         => $r['nama'],
            'waktu_dibuat' => $r['waktu_dibuat']
        ]
    ];
}
$marker_geojson = json_encode([
    'type' => 'FeatureCollection',
    'features' => $markers
], JSON_UNESCAPED_UNICODE);

// --- Polygon wilayah bencana ---
$polygons = [];
$res2 = $conn->query("SELECT id, nama, geojson, color, created_at FROM wilayah_bencana");
while ($r = $res2->fetch_assoc()) {
    $geojson = json_decode($r['geojson'], true);
    if ($geojson) {
        foreach ($geojson['features'] as &$feature) {
            $feature['properties']['id']         = (int)$r['id'];
            $feature['properties']['nama']       = $r['nama'];
            $feature['properties']['color']      = $r['color'];
            $feature['properties']['created_at'] = $r['created_at'];
        }
        $polygons = array_merge($polygons, $geojson['features']);
    }
}
$polygon_geojson = json_encode([
    'type' => 'FeatureCollection',
    'features' => $polygons
], JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard Admin – WebGIS Evakuasi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />

  <style>
    :root{
      --orange:#ff6f00;
      --sidebar-bg:#1f2937;
      --sidebar-hover:#374151;
    }
    body{
      background:#f5f5f5;
      font-family:'Segoe UI', sans-serif;
      overflow-x:hidden;
    }
    /* Sidebar */
    .sidebar{
      position:fixed;
      top:0; left:0;
      width:250px;
      height:100vh;
      background:var(--sidebar-bg);
      color:#fff;
      display:flex;
      flex-direction:column;
      z-index:1000;
      transition:transform .25s ease;
    }
    .sidebar.collapsed{
      transform:translateX(-100%);
    }
    .sidebar .brand{
      display:flex;
      align-items:center;
      gap:.5rem;
      padding:16px 20px;
      background:var(--orange);
      font-weight:600;
    }
    .sidebar .brand img{
      height:30px;
    }
    .nav-links{
      flex:1;
      padding:12px 0;
      overflow-y:auto;
    }
    .nav-links a{
      color:#e5e7eb;
      text-decoration:none;
      display:flex;
      align-items:center;
      gap:.5rem;
      padding:10px 20px;
      transition:background .2s;
      font-size:0.95rem;
    }
    .nav-links a.active,
    .nav-links a:hover{
      background:var(--sidebar-hover);
      color:#fff;
    }
    .logout{
      padding:12px 20px 20px 20px;
      border-top:1px solid rgba(255,255,255,.08);
    }
    .logout a{
      width:100%;
      display:block;
      text-align:left;
      color:#fca5a5 !important;
    }

    /* Topbar */
    .topbar {
      height: 60px;
      background: #ff6f00;
      color: white;
      padding: 0 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .btn-toggle{
      background:var(--orange);
      color:#fff;
      border:none;
      padding:6px 10px;
      border-radius:6px;
    }

    /* Main */
    .content-wrapper{
      margin-left:250px;
      transition:margin-left .25s ease;
    }
    .content-wrapper.full{
      margin-left:0;
    }
    #map{
      height:78vh;
      min-height:500px;
      border-radius:10px;
      box-shadow:0 2px 6px rgba(0,0,0,.08);
    }
    .card-soft{
      border:none;
      border-radius:12px;
      box-shadow:0 2px 8px rgba(0,0,0,.06);
    }
    .legend{
      position:absolute;
      bottom:20px;
      right:20px;
      background:#fff;
      border-radius:8px;
      box-shadow:0 2px 6px rgba(0,0,0,.15);
      padding:10px 12px;
      font-size:13px;
      z-index:999;
      max-width:240px;
    }
    .legend h6{
      font-size:13px;
      margin-bottom:6px;
      font-weight:600;
    }
    .badge-color{
      display:inline-block;
      width:14px;
      height:14px;
      border-radius:3px;
      margin-right:6px;
      border:1px solid #ddd;
    }

    @media (max-width: 991.98px){
      .content-wrapper{
        margin-left:0;
      }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar">
  <div class="brand">
    <img src="../assets/logo.png" alt="Logo" width="110" height="100">
  </div>

  <nav class="nav-links">
    <a href="#" class="active">🏠 Dashboard</a>
    <a href="marker.php">📍 Tambah Marker Titik</a>
    <a href="poligon.php">🗺️ Tambah Polygon Bencana</a>
  </nav>

  <div class="logout">
    <a href="../Login/logout.php" class="btn btn-outline-light btn-sm">🚪 Logout</a>
  </div>
</aside>

<!-- MAIN -->
<div id="contentWrapper" class="content-wrapper">
  <!-- TOPBAR -->
  <div class="topbar">
    <button id="btnToggle" class="btn-toggle d-lg-none">☰</button>
    <div class="d-flex align-items-center gap-2">
      <h5 class="mb-0">📊 Dashboard Admin Bencana</h5>
      <span class="badge bg-warning text-dark">v1.0</span>
    </div>
    <span class="text-muted small d-none d-lg-inline">Hi, <?= htmlspecialchars($_SESSION['admin'] ?? 'Admin') ?></span>
  </div>

  <main class="container-fluid py-4">
    <div class="row g-3">
      <div class="col-lg-12">
        <div class="card card-soft p-3">
          <h5 class="mb-1">Visualisasi Peta</h5>
          <p class="text-muted mb-3">Menampilkan semua <strong>titik evakuasi</strong> & <strong>wilayah bencana</strong> dari database.</p>

          <div class="row g-2 mb-2">
            <div class="col-md-6">
              <div class="input-group">
                <input type="text" class="form-control" id="searchInput" placeholder="Cari kecamatan (cth: cugenang, cipanas, pacet)...">
                <button class="btn btn-primary" id="searchBtn">🔍 Cari</button>
              </div>
            </div>
          </div>

          <div class="position-relative">
            <div id="map"></div>
            <div id="legend" class="legend d-none">
              <h6>Legenda Wilayah Bencana</h6>
              <div id="legendList"></div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>
</div>

<!-- JS -->
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Sidebar toggle (mobile)
  const sidebar = document.getElementById('sidebar');
  const contentWrapper = document.getElementById('contentWrapper');
  const btnToggle = document.getElementById('btnToggle');
  btnToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    contentWrapper.classList.toggle('full');
  });

  // Leaflet map init
  const map = L.map('map', { zoomControl: true }).setView([-6.82, 107.14], 11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map);

  // Markers
  const markers = <?= $marker_geojson ?>;
  L.geoJSON(markers, {
    pointToLayer: (feature, latlng) => L.marker(latlng),
    onEachFeature: (feature, layer) => {
      const p = feature.properties;
      layer.bindPopup(
        `<strong>${p.nama}</strong><br>` +
        `<small>Dibuat: ${p.waktu_dibuat}</small>`
      );
    }
  }).addTo(map);

  // Polygons
  const polygons = <?= $polygon_geojson ?>;
  const colorSet = new Set();
  const namaColorMap = new Map(); // nama -> color (untuk legend)

  L.geoJSON(polygons, {
    style: (feature) => {
      const color = feature.properties.color || 'red';
      colorSet.add(color);
      namaColorMap.set(feature.properties.nama, color);
      return {
        color: color,
        fillColor: color,
        fillOpacity: 0.35,
        weight: 2
      };
    },
    onEachFeature: (feature, layer) => {
      const p = feature.properties;
      layer.bindPopup(
        `<strong>Wilayah: ${p.nama}</strong><br>` +
        `<small>Dibuat: ${p.created_at}</small>`
      );
    }
  }).addTo(map);

  // Build Legend from polygons
  if (namaColorMap.size > 0) {
    const legendEl = document.getElementById('legend');
    const legendList = document.getElementById('legendList');
    legendEl.classList.remove('d-none');
    let html = '';
    namaColorMap.forEach((color, nama) => {
      html += `<div class="d-flex align-items-center mb-1">
        <span class="badge-color" style="background:${color}"></span>
        <span>${nama}</span>
      </div>`;
    });
    legendList.innerHTML = html;
  }

  // Quick search kecamatan (dummy dictionary – sesuaikan)
  document.getElementById('searchBtn').addEventListener('click', () => {
    const q = document.getElementById('searchInput').value.trim().toLowerCase();
    const lokasi = {
      'warungkondang': [-6.81, 107.09],
      'cugenang': [-6.835, 107.12],
      'cipanas': [-6.724, 107.01],
      'pacet': [-6.79, 107.14],
      'karangtengah': [-6.8, 107.04],
      'sukaresmi': [-6.785, 107.02],
      'cianjur': [-6.823, 107.142]
    };
    const pos = lokasi[q];
    if (pos) {
      map.setView(pos, 13);
    } else {
      alert("Kecamatan tidak ditemukan.");
    }
  });
</script>
</body>
</html>
