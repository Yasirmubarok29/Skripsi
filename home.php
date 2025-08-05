<?php
session_start();
require 'conection/db.php';

// Ambil titik evakuasi
$markers = [];
$res1 = $conn->query("SELECT id, nama, latitude, longitude FROM titik_evakuasi");
while ($r = $res1->fetch_assoc()) {
  $markers[] = [
    'lat' => (float)$r['latitude'],
    'lng' => (float)$r['longitude'],
    'nama' => $r['nama']
  ];
}
$marker_json = json_encode($markers);

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
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/pathfinding@0.4.18/visual/lib/pathfinding-browser.min.js"></script>

  <style>
    * {
      box-sizing: border-box;
    }

    html,
    body {
      height: 100%;
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      display: flex;
      flex-direction: row;
      height: 100vh;
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
      transition: all 0.3s ease-in-out;
    }

    .btn-evakuasi {
      background-color: white;
      color: #f57c00;
      font-weight: bold;
    }

    #map {
      flex-grow: 1;
      height: 100%;
      transition: filter 0.3s ease-in-out;
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

      .sidebar img {
        width: 100px;
      }

      .sidebar .btn {
        padding: 6px 10px;
        font-size: 14px;
      }

      #infoBox {
        font-size: 14px;
        padding: 8px;
      }

      #map .leaflet-marker-icon {
        transform: scale(0.8);
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
    <div id="infoBox" class="text-secondary">Klik tombol <strong>Jalur Aman</strong> untuk mencari rute terdekat ke titik evakuasi yang aman.</div>
  </div>
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
  <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
  <script>
    const map = L.map('map').setView([-6.82, 107.14], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    const titikUser = L.latLng(-6.817393, 107.122924);
    L.marker(titikUser, {
      icon: L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/64/64113.png',
        iconSize: [26, 26]
      })
    }).addTo(map).bindPopup('📍 Lokasi Anda (dummy)').openPopup();

    const markerData = <?= $marker_json ?>;
    const polygonData = <?= $polygon_json ?>;

    const polygonLayer = L.geoJSON(polygonData, {
      style: f => ({
        color: f.properties.color || 'red',
        fillColor: f.properties.color || 'red',
        fillOpacity: 0.4
      })
    }).addTo(map);

    const disasterPolygons = polygonData.features;

    const evakuasiPoints = [];
    markerData.forEach((m, i) => {
      const latlng = L.latLng(m.lat, m.lng);
      evakuasiPoints.push({
        latlng,
        nama: m.nama
      });
      L.marker(latlng).addTo(map).bindPopup(`🧱 Titik Evakuasi ${i + 1}: ${m.nama}`);
    });

    fetch('./assets/cianju.geojson')
      .then(res => res.json())
      .then(data => {
        L.geoJSON(data, {
          style: {
            color: '#0000ff',
            weight: 2,
            fillOpacity: 0.1,
            dashArray: '5,5'
          }
        }).addTo(map).bindPopup("Wilayah Administratif Cianjur");
      })
      .catch(err => console.error("Gagal memuat cianju.geojson:", err));

    function isInsideAnyDisasterPolygon(latlng) {
      const point = turf.point([latlng.lng, latlng.lat]);
      return disasterPolygons.some(poly => turf.booleanPointInPolygon(point, poly));
    }

    function findNearestSafeEvakuasi(from, points) {
      let nearest = null;
      let minDist = Infinity;
      points.forEach(p => {
        if (isInsideAnyDisasterPolygon(p.latlng)) return;
        const dist = from.distanceTo(p.latlng);
        if (dist < minDist) {
          minDist = dist;
          nearest = p.latlng;
        }
      });
      return nearest;
    }

    let routingControl = null;
    document.getElementById('startBtn').addEventListener('click', () => {
      if (routingControl) map.removeControl(routingControl);
      const nearest = findNearestSafeEvakuasi(titikUser, evakuasiPoints);
      const info = document.getElementById('infoBox');

      if (!nearest) {
        info.innerHTML = '<span class="text-danger">⚠️ Tidak ada titik evakuasi yang aman di luar wilayah bencana.</span>';
        return;
      }

      routingControl = L.Routing.control({
        waypoints: [titikUser, nearest],
        router: L.Routing.osrmv1({
          serviceUrl: 'https://router.project-osrm.org/route/v1'
        }),
        lineOptions: {
          styles: [{
            color: 'blue',
            weight: 5,
            opacity: 0.7
          }]
        },
        createMarker: () => null,
        addWaypoints: false,
        draggableWaypoints: false
      }).addTo(map);
      // 1. Buat grid peta (simulasi: ubah sesuai kebutuhan nyata)
      const width = 50,
        height = 50;
      let matrix = Array.from({
        length: height
      }, () => Array(width).fill(0));

      // 2. Tandai polygon bencana sebagai area terlarang (1 di matrix)
      disasterPolygons.forEach(poly => {
        const bounds = turf.bbox(poly);
        const [minX, minY, maxX, maxY] = bounds;

        for (let i = 0; i < height; i++) {
          for (let j = 0; j < width; j++) {
            const lng = 107.12 + j * 0.001; // sesuaikan koordinat dasar
            const lat = -6.85 + i * 0.001;

            const point = turf.point([lng, lat]);
            if (turf.booleanPointInPolygon(point, poly)) {
              matrix[i][j] = 1;
            }
          }
        }
      });

      // 3. Fungsi konversi koordinat ke grid
      function latlngToGrid(latlng) {
        const row = Math.round((latlng.lat + 6.85) / 0.001);
        const col = Math.round((latlng.lng - 107.12) / 0.001);
        return {
          row,
          col
        };
      }

      function gridToLatlng(row, col) {
        return L.latLng(-6.85 + row * 0.001, 107.12 + col * 0.001);
      }

      // 4. Ganti tombol "Jalur Aman" klik untuk menggunakan PathFinding.js
      document.getElementById('startBtn').addEventListener('click', () => {
        const from = latlngToGrid(titikUser);
        let target = null;
        let minDist = Infinity;

        evakuasiPoints.forEach(p => {
          if (isInsideAnyDisasterPolygon(p.latlng)) return;
          const dist = titikUser.distanceTo(p.latlng);
          if (dist < minDist) {
            minDist = dist;
            target = latlngToGrid(p.latlng);
          }
        });

        if (!target) {
          document.getElementById('infoBox').innerHTML = '<span class="text-danger">⚠️ Titik aman tidak ditemukan.</span>';
          return;
        }

        const grid = new PF.Grid(matrix);
        const finder = new PF.AStarFinder();
        const path = finder.findPath(from.col, from.row, target.col, target.row, grid);

        if (!path.length) {
          document.getElementById('infoBox').innerHTML = '<span class="text-danger">⚠️ Jalur tidak ditemukan.</span>';
          return;
        }

        const latlngs = path.map(([x, y]) => gridToLatlng(y, x));
        L.polyline(latlngs, {
          color: 'green',
          weight: 5
        }).addTo(map);
        document.getElementById('infoBox').innerHTML = `✅ Jalur ditemukan. Jarak: ${(minDist / 1000).toFixed(2)} km`;
      });

      info.innerHTML = `✅ Jalur aman ditampilkan.<br>Jarak: <strong>${(titikUser.distanceTo(nearest) / 1000).toFixed(2)} km</strong>`;
    });

    const loginModal = document.getElementById('loginModal');
    loginModal.addEventListener('show.bs.modal', () => document.getElementById('map').classList.add('blur-bg'));
    loginModal.addEventListener('hidden.bs.modal', () => document.getElementById('map').classList.remove('blur-bg'));

    fetch('data.geojson')
      .then(res => res.json())
      .then(data => {
        L.geoJSON(data, {
          style: {
            color: '#00cc66', // warna garis
            weight: 2,
            fillOpacity: 0.3,
            fillColor: '#00cc66' // warna isi
          },
          onEachFeature: function(feature, layer) {
            // Menampilkan info jika ada properti tertentu
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