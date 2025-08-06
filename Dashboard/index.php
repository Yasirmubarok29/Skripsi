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

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .sidebar .sidebar-header {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 28px 0 18px 0;
  background: linear-gradient(90deg, #ff6f00 0%, #ff9800 100%);
  border-bottom: 1px solid #ffe0b2;
}
.sidebar .sidebar-header img {
  height: 64px;
  width: 64px;
  object-fit: contain;
  margin: 0 auto;
  display: block;
}
.sidebar .nav {
  margin-top: 0.5rem;
}
.sidebar .nav-link {
  color: #333;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 12px 24px;
  border-radius: 0 20px 20px 0;
  margin-bottom: 2px;
  transition: background .2s, color .2s;
  font-size: 1rem;
}
.sidebar .nav-link.active, .sidebar .nav-link:hover {
  background: #ffe0b2;
  color: #ff6f00;
}
.sidebar .logout {
  padding: 16px 20px 8px 20px;
  border-top: 1px solid #ffe0b2;
}
.sidebar .logout .nav-link {
  color: #d32f2f;
  font-weight: 600;
  background: none;
  border-radius: 0 20px 20px 0;
}
.sidebar .logout .nav-link:hover {
  background: #ffe0b2;
  color: #b71c1c;
}
    body {
      background: #f5f5f5;
      font-family: 'Segoe UI', sans-serif;
      overflow-x: hidden;
    }
    .sidebar {
      min-width: 250px;
      max-width: 250px;
      height: 100vh;
      background: #fff;
      border-right: 1px solid #eee;
      padding: 0;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1030;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 8px rgba(0,0,0,.07);
    }
    .sidebar .sidebar-header {
      display: flex;
      align-items: center;
      gap: .5rem;
      padding: 18px 20px 14px 20px;
      background: linear-gradient(90deg, #ff6f00 0%, #ff9800 100%);
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: 1px;
      border-bottom: 1px solid #fff2;
      color: #fff;
    }
    .sidebar .sidebar-header img {
      height: 36px;
      margin-right: 8px;
    }
    .sidebar .nav {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      padding: 16px 0;
    }
    .sidebar .nav-link {
      color: #333;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: 12px 24px;
      border-radius: 0 20px 20px 0;
      margin-bottom: 2px;
      transition: background .2s, color .2s;
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover {
      background: #ffe0b2;
      color: #ff6f00;
    }
    .sidebar .logout {
      padding: 16px 20px 24px 20px;
      border-top: 1px solid #ffe0b2;
    }
    .sidebar .logout .nav-link {
      color: #d32f2f;
      font-weight: 600;
      background: none;
      border-radius: 0 20px 20px 0;
    }
    .sidebar .logout .nav-link:hover {
      background: #ffe0b2;
      color: #b71c1c;
    }
    .content-wrapper {
      margin-left: 250px;
      transition: margin-left .25s cubic-bezier(.4,2,.6,1);
      min-height: 100vh;
      background: #f5f5f5;
    }
    .content-wrapper.full {
      margin-left: 0;
    }
    #map {
      height: 74vh;
      min-height: 420px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,.10);
      border: 1px solid #eee;
    }
    .legend {
      position: absolute;
      bottom: 24px;
      right: 24px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,.13);
      padding: 14px 16px;
      font-size: 14px;
      z-index: 999;
      max-width: 260px;
      border: 1px solid #eee;
      transition: box-shadow .2s;
    }
    .legend h6 {
      font-size: 14px;
      margin-bottom: 8px;
      font-weight: 700;
    }
    .badge-color {
      display: inline-block;
      width: 16px;
      height: 16px;
      border-radius: 4px;
      margin-right: 8px;
      border: 1px solid #ddd;
    }
    .card-soft {
      border: none;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.08);
      background: #fff;
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
    @media (max-width: 991.98px) {
      .content-wrapper {
        margin-left: 0;
      }
      .sidebar {
        min-width: 210px;
        max-width: 210px;
      }
    }
    @media (max-width: 575.98px) {
      .legend {
        right: 8px;
        bottom: 8px;
        padding: 8px 8px;
      }
    }
  </style>
</head>
<body>

<nav id="sidebar" class="sidebar d-flex flex-column">
  <div class="sidebar-header justify-content-center" style="padding-bottom:18px; border-bottom:1px solid #ffe0b2; background:linear-gradient(90deg,#ff6f00 0%,#ff9800 100%);">
    <img src="../assets/logo.png" alt="Logo" width="100" height="100" style="display:block; margin:0 auto;">
  </div>
  <ul class="nav nav-pills flex-column mb-auto mt-3" style="gap:2px;">
    <li class="nav-item">
      <a href="#" class="nav-link active d-flex align-items-center gap-2" aria-current="page">
        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="marker.php" class="nav-link d-flex align-items-center gap-2">
        <i class="bi bi-geo-alt-fill"></i> <span>Tambah Marker Titik</span>
      </a>
    </li>
    <li>
      <a href="poligon.php" class="nav-link d-flex align-items-center gap-2">
        <i class="bi bi-vector-pen"></i> <span>Tambah Polygon Bencana</span>
      </a>
    </li>
  </ul>
  <div class="logout mt-auto mb-2">
    <a href="../Login/logout.php" class="nav-link d-flex align-items-center gap-2">
      <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
    </a>
  </div>
</nav>

<!-- SIDEBAR (Bootstrap 5.3 style) -->
<nav id="sidebar" class="sidebar d-flex flex-column">
  <div class="sidebar-header">
    <img src="../assets/logo.png" alt="Logo" width="200" height="200">
  
  </div>
  <ul class="nav nav-pills flex-column mb-auto">
    <li class="nav-item">
      <a href="#" class="nav-link active" aria-current="page">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
    </li>
    <li>
      <a href="marker.php" class="nav-link">
        <i class="bi bi-geo-alt-fill"></i> Tambah Marker Titik
      </a>
    </li>
    <li>
      <a href="poligon.php" class="nav-link">
        <i class="bi bi-vector-pen"></i> Tambah Polygon Bencana
      </a>
    </li>
  </ul>
  <div class="logout mt-auto">
    <a href="../Login/logout.php" class="nav-link">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</nav>

<!-- MAIN -->
<div id="contentWrapper" class="content-wrapper">
  <!-- TOPBAR -->
  <div class="topbar">
    <button id="btnToggle" class="btn btn-light d-lg-none me-2" style="font-size:1.3rem;"><i class="bi bi-list"></i></button>
    <div class="d-flex align-items-center gap-2">
      <h5 class="mb-0"><i class="bi bi-speedometer2 me-1"></i> Dashboard Admin Bencana</h5>
      <span class="badge bg-warning text-dark">v1.0</span>
    </div>
    <span class="text-muted small d-none d-lg-inline">Hi, <?= htmlspecialchars($_SESSION['admin'] ?? 'Admin') ?></span>
  </div>

  <main class="container-fluid py-4">
    <div class="row g-3">
      <div class="col-lg-12">
        <div class="card card-soft p-0">
          <div class="card-header-modern">Visualisasi Peta Wilayah & Titik Evakuasi</div>
          <div class="p-3">
            <p class="text-muted mb-3">Menampilkan semua <strong>titik evakuasi</strong> dan <strong>wilayah bencana</strong> dari database.</p>
            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <div class="input-group">
                  <input type="text" class="form-control" id="searchInput" placeholder="Cari kecamatan (cth: cugenang, cipanas, pacet)...">
                  <button class="btn btn-primary" id="searchBtn">Cari</button>
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
    if (sidebar.style.transform === 'translateX(-100%)') {
      sidebar.style.transform = '';
      contentWrapper.classList.remove('full');
    } else {
      sidebar.style.transform = 'translateX(-100%)';
      contentWrapper.classList.add('full');
    }
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
