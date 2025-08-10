<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Login/index.php");
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

function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
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
  <!-- Bootstrap Icons -->
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
      height: 74vh;
      min-height: 420px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(33,37,41,.07);
      border: 1px solid #dee2e6;
      margin-bottom: 18px;
      background: linear-gradient(90deg, #e9ecef 0%, #f8f9fa 100%);
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
      border: 1px solid #dee2e6;
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
      border: 1px solid #dee2e6;
    }
    .card-soft {
      border: none;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(33,37,41,.08);
      background: #fff;
      transition: box-shadow .2s;
    }
  </style>
</head>
<body>

<nav id="sidebar" class="sidebar d-flex flex-column">
  <div class="sidebar-header">
    <img src="../assets/logo1.png" alt="Logo1 WebGIS">
  </div>
  <div class="admin-info mb-0">
    <i class="bi bi-person-circle"></i>
    <span>Selamat datang, <b><?= esc($_SESSION['admin'] ?? 'Admin') ?></b></span>
  </div>
  <div class="nav-section">
    <a href="index.php" class="nav-link active" aria-current="page">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="marker.php" class="nav-link">
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
    <span class="title"><i class="bi bi-speedometer2"></i> Dashboard Admin <span class="version">v1.0</span></span>
  </div>
  <div class="user-box">
    <i class="bi bi-person-circle"></i>
    <?= esc($_SESSION['admin'] ?? 'Admin') ?>
  </div>
</div>

<!-- MAIN -->
<div id="contentWrapper" class="content-wrapper">
  <main class="container-fluid py-4">
    <div class="row g-3">
      <div class="col-lg-12">
        <div class="card card-soft p-0">
          <div class="card-header-modern"><i class="bi bi-map"></i> Visualisasi Peta Wilayah & Titik Evakuasi</div>
          <div class="p-3">
            <p class="text-muted mb-3">Menampilkan semua <strong>titik evakuasi</strong> dan <strong>wilayah bencana</strong> dari database.</p>
            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <div class="input-group">
                  <input type="text" class="form-control" id="searchInput" placeholder="Cari kecamatan (cth: cugenang, cipanas, pacet)...">
                  <button class="btn btn-primary" id="searchBtn"><i class="bi bi-search"></i> Cari</button>
                </div>
              </div>
            </div>
            <div class="position-relative">
              <div id="map"></div>
              <div id="legend" class="legend d-none">
                <h6><i class="bi bi-palette me-1"></i> Legenda Wilayah Bencana</h6>
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
  if (btnToggle) {
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
  const namaColorMap = new Map();

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

  // Load batas Cianjur GeoJSON
  fetch('../data/batas_cianjur.geojson')
    .then(res => {
        if (!res.ok) throw new Error("Gagal memuat file Cianjur.geojson");
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
        }).addTo(map).bindTooltip("Wilayah Administratif Cianjur", {permanent:false, direction:'top'});
    })
    .catch(err => console.error("Gagal memuat Cianjur.geojson:", err));

     fetch('../data/batas_kecamatan.geojson')
    .then(res => {
        if (!res.ok) throw new Error("Gagal memuat file Cianjur.geojson");
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
        }).addTo(map).bindTooltip("Wilayah Administratif Kecamatan Cianjur", {permanent:false, direction:'top'});
    })
    .catch(err => console.error("Gagal memuat Cianjur.geojson:", err));

    fetch('../data/batas_kelurahan.geojson')
    .then(res => {
        if (!res.ok) throw new Error("Gagal memuat file Cianjur.geojson");
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
        }).addTo(map).bindTooltip("Wilayah Administratif Kelurahan Cianjur", {permanent:false, direction:'top'});
    })
    .catch(err => console.error("Gagal memuat Cianjur.geojson:", err));
</script>
</body>
</html>