<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Login/login.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard - Bencana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Leaflet & Leaflet Draw -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      overflow-x: hidden;
    }

    .sidebar {
      height: 100vh;
      background-color: #343a40;
      color: white;
      padding-top: 20px;
    }

    .sidebar a {
      color: white;
      display: block;
      padding: 10px 20px;
      text-decoration: none;
    }

    .sidebar a:hover {
      background-color: #495057;
    }

    .topbar {
      height: 60px;
      background-color: #007bff;
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 20px;
    }

    #map {
      height: 85vh;
      border-radius: 8px;
    }
  </style>
</head>
<body>

  <!-- Navbar Top -->
  <div class="topbar">
    <h5 class="mb-0">🛑 Admin Bencana</h5>
    <a href="../Login/logout.php" class="btn btn-light btn-sm">Logout</a>
  </div>

  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-2 sidebar">
        <a href="#">📊 Dashboard</a>
        <a href="#">📍 Tambah Marker Titik</a>
        <a href="#">🗺️ Tambah Polygon Bencana</a>
        
        <!-- Tambahkan menu lain di sini -->
      </div>

      <!-- Main Content -->
      <div class="col-md-10 p-4">
        <h4>Kontrol Wilayah Bencana</h4>
        <p>Gambar wilayah bencana di peta dan simpan untuk digunakan sistem.</p>
        <button class="btn btn-success mb-3" id="saveBtn">💾 Simpan Wilayah</button>
        <div class="input-group mb-3">
  <input type="text" class="form-control" id="searchInput" placeholder="Masukkan nama kecamatan...">
  <button class="btn btn-primary" id="searchBtn">🔍 Cari Kecamatan</button>
</div>


        <div id="map" class="shadow"></div>
      </div>
    </div>
  </div>

  <!-- Script -->
  <script>
    const map = L.map('map').setView([-6.82, 107.14], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    const drawControl = new L.Control.Draw({
      draw: {
        polygon: { allowIntersection: false, showArea: true, color: 'red' },
        marker: false, polyline: false,
        rectangle: false, circle: false, circlemarker: false
      },
      edit: { featureGroup: drawnItems }
    });
    map.addControl(drawControl);

    map.on(L.Draw.Event.CREATED, function (e) {
      drawnItems.clearLayers();
      drawnItems.addLayer(e.layer);
    });

    document.getElementById('saveBtn').addEventListener('click', function () {
      if (drawnItems.getLayers().length === 0) {
        alert("Silakan gambar wilayah bencana terlebih dahulu!");
        return;
      }

      const geojson = drawnItems.toGeoJSON();
      const data = JSON.stringify(geojson);

      fetch('save_polygon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: data
      })
        .then(res => res.text())
        .then(msg => {
          alert("✅ Wilayah bencana berhasil disimpan!");
        })
        .catch(err => {
          alert("❌ Gagal menyimpan data.");
          console.error(err);
        });
    });

    // Load polygon existing
    fetch('bencana.geojson')
      .then(res => res.json())
      .then(data => {
        L.geoJSON(data, {
          style: { color: 'red', fillColor: '#f03', fillOpacity: 0.4 }
        }).addTo(map);
      })
      .catch(err => console.warn("Belum ada data polygon."));
  </script>

</body>
</html>
