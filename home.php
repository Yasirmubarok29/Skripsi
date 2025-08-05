<?php
session_start();
require 'conection/db.php';

// Ambil titik evakuasi
$titikEvakuasi = [];
$res1 = $conn->query("SELECT id, nama, latitude, longitude FROM titik_evakuasi");
while ($r = $res1->fetch_assoc()) {
  $titikEvakuasi[] = [
    'lat' => (float)$r['latitude'],
    'lng' => (float)$r['longitude'],
    'nama' => $r['nama']
  ];
}
$marker_json = json_encode($titikEvakuasi);

// Ambil polygon wilayah bencana
$polygonFeatures = [];
$res2 = $conn->query("SELECT geojson, color FROM wilayah_bencana");
while ($r = $res2->fetch_assoc()) {
  $geojson = json_decode($r['geojson'], true);
  $color = $r['color'];
  if ($geojson && isset($geojson['features'])) {
    foreach ($geojson['features'] as &$feature) {
      $feature['properties']['color'] = $color;
    }
    $polygonFeatures = array_merge($polygonFeatures, $geojson['features']);
  }
}
$polygon_json = json_encode([
  "type" => "FeatureCollection",
  "features" => $polygonFeatures
]);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <title>WebGIS Evakuasi Aman</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
  <style>
    html,
    body {
      height: 100%;
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      flex-direction: row;
      overflow: hidden;
    }

    .sidebar {
      width: 250px;
      background-color: #f57c00;
      color: white;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .btn-evakuasi {
      background-color: white;
      color: #f57c00;
      font-weight: bold;
    }

    #map {
      flex-grow: 1;
      height: 100%;
    }

    #map.blur-bg {
      filter: blur(5px);
    }

    #infoBox {
      padding: 10px;
      background-color: #f8f9fa;
      border-top: 1px solid #ddd;
      text-align: center;
    }

    .d-flex-column {
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }

    @media (max-width: 768px) {
      body {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
      }
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <img src="./assets/logo.png" alt="Logo" width="150">
    <a href="index.php" class="btn btn-outline-light">🏠 Beranda</a>
    <button id="startBtn" class="btn btn-evakuasi">🚨 Jalur Aman</button>
    <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#loginModal">🔐 Login Admin</button>
  </div>
  <div class="d-flex-column">
    <div id="map"></div>
    <div id="infoBox" class="text-secondary">
      Klik tombol <strong>Jalur Aman</strong> untuk mencari rute terdekat ke titik evakuasi yang aman.
    </div>
  </div>

  <!-- Modal Login Admin -->
  <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content p-0 border-0" style="background: transparent;">
        <div class="position-absolute top-0 start-0 w-100 h-100" style="backdrop-filter: blur(10px); background-color: rgba(255,255,255,0.2); z-index: 1; border-radius: 12px;"></div>
        <div class="position-relative bg-white p-4 rounded-4 shadow" style="z-index: 2;">
          <div class="text-center mb-3">
            <img src="./assets/logo.png" width="120" class="mb-2">
            <h4 class="text-primary">Login Admin</h4>
          </div>
          <?php
          if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
              $admin = $result->fetch_assoc();
              if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin'] = $admin['username'];
                echo "<script>window.location.href='Dashboard/index.php'</script>";
                exit;
              } else {
                echo '<div class="alert alert-danger">Password salah</div>';
              }
            } else {
              echo '<div class="alert alert-danger">Username tidak ditemukan</div>';
            }
          }
          ?>
          <form method="POST">
            <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary w-100">Masuk</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const map = L.map('map').setView([-6.82, 107.14], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    const markerData = <?= $marker_json ?>;
    const polygonData = <?= $polygon_json ?>;

    // wilayah bencana
    L.geoJSON(polygonData, {
      style: f => ({
        color: f.properties.color || 'red',
        fillColor: f.properties.color || 'red',
        fillOpacity: 0.4
      })
    }).addTo(map);

    // titip evakuasi
    markerData.forEach((m, i) => {
      L.marker([m.lat, m.lng]).addTo(map).bindPopup(`🧱 Titik Evakuasi ${i + 1}: ${m.nama}`);
    });

    // Data jalur akan digunakan nanti
    fetch('data/jalan_cianjur.geojson')
      .then(res => res.json())
      .then(data => {
        L.geoJSON(data, {
          style: {
            color: '#00cc66',
            weight: 2
          },
          onEachFeature: function(feature, layer) {
            if (feature.properties && feature.properties.nama) {
              layer.bindPopup("Nama: " + feature.properties.nama);
            }
          }
        }).addTo(map);
      })
      .catch(err => console.error("Gagal memuat data.geojson:", err));
  </script>
</body>

</html>