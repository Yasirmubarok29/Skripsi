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
  <title>Evakuasi A*</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
    <style>
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
        background: #f5f5f5;
      }
      body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }
      .main-content {
        flex: 1;
        display: flex;
        flex-direction: row;
        min-height: 0;
      }
      .sidebar {
        width: 300px;
        background: linear-gradient(135deg, #f57c00 60%, #ff9800 100%);
        color: white;
        padding: 32px 24px 24px 24px;
        flex-shrink: 0;
        box-shadow: 2px 0 16px 0 rgba(0,0,0,0.08);
        border-top-right-radius: 24px;
        border-bottom-right-radius: 24px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        z-index: 10;
      }
      .sidebar h4 {
        font-weight: bold;
        margin-bottom: 24px;
        letter-spacing: 1px;
      }
      .btn-evakuasi {
        background: linear-gradient(90deg, #ff9800 60%, #f57c00 100%);
        color: #fff;
        font-weight: bold;
        font-size: 1.1rem;
        border: none;
        border-radius: 24px;
        box-shadow: 0 2px 8px 0 rgba(0,0,0,0.10);
        padding: 12px 32px;
        margin-bottom: 16px;
        transition: background 0.2s, box-shadow 0.2s;
      }
      .btn-evakuasi:hover {
        background: linear-gradient(90deg, #f57c00 60%, #ff9800 100%);
        box-shadow: 0 4px 16px 0 rgba(0,0,0,0.18);
      }
      .sidebar .info {
        margin-top: 32px;
        font-size: 0.98rem;
        background: #fffbe7;
        color: #7a4f01;
        border-radius: 12px;
        padding: 18px 18px 12px 18px;
        box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);
        border: 1px solid #ffe0b2;
      }
      #map {
        flex: 1;
        height: 100vh;
        border-radius: 24px;
        margin: 16px 16px 16px 0;
        box-shadow: 0 2px 16px 0 rgba(0,0,0,0.10);
      }
      .footer {
        background: #fff3e0;
        color: #f57c00;
        text-align: center;
        padding: 10px 0 6px 0;
        font-size: 1rem;
        letter-spacing: 1px;
        border-top: 1px solid #ffe0b2;
      }
      @media (max-width: 900px) {
        .sidebar { width: 100%; border-radius: 0; box-shadow: none; }
        .main-content { flex-direction: column; }
        #map { margin: 0; border-radius: 0; }
      }
    </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-lg" style="background: linear-gradient(90deg, #ff9800 0%, #f57c00 100%); min-height:70px;">
    <div class="container-fluid px-4">
      <a class="navbar-brand d-flex align-items-center gap-2" href="#" style="font-weight:700; font-size:1.3rem; letter-spacing:1px;">
        <img src="assets/logo.png" alt="Evastar Logo" style="width:48px;height:48px;object-fit:contain;">
        <span class="d-none d-md-inline" style="color:#fff;">Evakuasi Cerdas Cianjur</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topbarNav" aria-controls="topbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="topbarNav">
        <ul class="navbar-nav mb-2 mb-lg-0 align-items-center gap-2">
          <li class="nav-item">
          
            </button>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="main-content">
    <div class="sidebar">
      <h4 class="d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Evakuasi Bencana</h4>
      <a href="index.php" class="btn btn-outline-primary w-100 mb-3 d-flex align-items-center justify-content-center gap-2" style="font-weight:bold; border-radius:20px;"><i class="bi bi-house-door"></i> Beranda</a>
      <button id="startBtn" class="btn btn-evakuasi mb-3 w-100 d-flex align-items-center justify-content-center gap-2"><i class="bi bi-geo-alt-fill"></i> Jalur Aman</button>
      <div class="info mb-3">
        <div class="fw-bold mb-2" style="color:#b26a00;"><i class="bi bi-info-circle-fill me-1"></i> Petunjuk</div>
        <div class="d-flex align-items-start mb-2">
          <i class="bi bi-geo-alt-fill me-2" style="color:#0d6efd;font-size:1.2em;"></i>
          <span>Klik tombol <b>Jalur Aman</b> untuk mencari rute evakuasi terdekat dari lokasi Anda.</span>
        </div>
        <div class="d-flex align-items-start mb-2">
          <i class="bi bi-cursor me-2" style="color:#f57c00;font-size:1.2em;"></i>
          <span>Jika lokasi Anda di luar Cianjur, pilih lokasi manual dengan klik pada peta.</span>
        </div>
        <div class="d-flex align-items-start mb-2">
          <i class="bi bi-flag-fill me-2" style="color:#198754;font-size:1.2em;"></i>
          <span>Titik evakuasi ditandai ikon <i class="bi bi-flag-fill" style="color:#198754;"></i>.</span>
        </div>
        <div class="d-flex align-items-start">
          <i class="bi bi-exclamation-triangle-fill me-2" style="color:#dc3545;font-size:1.2em;"></i>
          <span>Area bencana berwarna merah transparan.</span>
        </div>
      </div>
      <button id="loginBtn" type="button" class="btn btn-outline-warning w-100 d-flex align-items-center justify-content-center gap-2" style="font-weight:bold; border-radius:20px;" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-person-circle"></i> Login</button>

      <!-- Tabel info jalan yang dilalui -->
      <div id="jalanInfoContainer" class="mt-3" style="display:none;">
        <div class="card shadow-sm" style="border-radius:12px;">
          <div class="card-header py-2 px-3" style="background:linear-gradient(90deg,#ff9800 0%,#f57c00 100%);color:#fff;font-weight:600;font-size:1rem;border-radius:12px 12px 0 0;">Jalan yang Dilalui</div>
          <div class="card-body p-2">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0" style="font-size:0.95em;">
                <thead class="table-light">
                  <tr><th>Nama Jalan</th><th>Jarak (m)</th></tr>
                </thead>
                <tbody id="jalanInfoTable"></tbody>
                <tfoot>
                  <tr style="background:#fffbe7;font-weight:700;"><td>Total Jarak</td><td id="totalJarakCell"></td></tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Blur background saat modal login aktif
    const loginModal = document.getElementById('loginModal');
    const mainContent = document.querySelector('.main-content');
    const footer = document.querySelector('.footer');
    if (loginModal) {
      loginModal.addEventListener('show.bs.modal', function() {
        mainContent.style.filter = 'blur(6px)';
        footer.style.filter = 'blur(6px)';
      });
      loginModal.addEventListener('hidden.bs.modal', function() {
        mainContent.style.filter = '';
        footer.style.filter = '';
      });
    }
  </script>
    </div>
    <div id="map"></div>
  </div>

  <!-- Modal Login (pindahkan ke luar .sidebar dan .main-content) -->
  <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loginModalLabel">Login</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="login-card p-0 m-0 border-0 shadow-none" style="background:transparent;box-shadow:none;max-width:100%;">
            <div class="text-center mb-3">
              <img src="assets/logo.png" alt="Logo" class="login-logo mb-2" style="width:100px;height:100px;object-fit:contain;">
              <h4 class="mb-2 text-primary">Login Admin</h4>
            </div>
            <div id="loginError" style="display:none;" class="alert alert-danger py-2">Username atau Password salah!</div>
            <form id="modalLoginForm" autocomplete="off">
              <div class="mb-3 text-start">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" id="modalUsername" required autofocus>
              </div>
              <div class="mb-3 text-start">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" id="modalPassword" required>
              </div>
              <button type="submit" class="btn btn-primary w-100 mt-2">Masuk</button>
            </form>
            <footer class="mt-4 text-muted text-center" style="font-size: 13px;">© 2025 WebGIS Evakuasi – Kabupaten Cianjur</footer>
          </div>
  <script>
    // Login AJAX untuk modal login
    document.addEventListener('DOMContentLoaded', function() {
      const loginForm = document.getElementById('modalLoginForm');
      const loginError = document.getElementById('loginError');
      if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
          e.preventDefault();
          loginError.style.display = 'none';
          const username = document.getElementById('modalUsername').value;
          const password = document.getElementById('modalPassword').value;
          fetch('login_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              window.location.href = 'Dashboard/index.php';
            } else {
              loginError.style.display = 'block';
            }
          })
          .catch(() => { loginError.style.display = 'block'; });
        });
      }
    });
  </script>
        </div>
      </div>
    </div>
  </div>
  </div>
  <footer class="footer">
    &copy; 2025 Sistem Evakuasi Cerdas Cianjur &mdash; Powered by Leaflet & Bootstrap
  </footer>

  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
  <script>
    const map = L.map('map').setView([-6.82, 107.14], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    const markerData = <?= $marker_json ?>;
    const polygonData = <?= $polygon_json ?>;

    // Batas wilayah Cianjur (GeoJSON)
    let batasCianjur = null;
    fetch('data/batas_cianjur.geojson')
      .then(res => res.json())
      .then(data => {
        batasCianjur = data;
        L.geoJSON(batasCianjur, {
          style: { color: '#d21919ff', weight: 2, fillOpacity: 0 }
        }).addTo(map);
      });

    // // Batas wilayah kecamatan (GeoJSON)
    // let batasKecamatan = null;
    // fetch('data/batas_kecamatan.geojson')
    //   .then(res => res.json())
    //   .then(data => {
    //     batasKecamatan = data;
    //     L.geoJSON(batasKecamatan, {
    //       style: { color: '#f2c512ff', weight: 2, fillOpacity: 0 }
    //     }).addTo(map);
    //   });
    // // Batas wilayah kelurahan (GeoJSON)
    // let batasKelurahan = null;
    // fetch('data/batas_kelurahan.geojson')
    //   .then(res => res.json())
    //   .then(data => {
    //     batasKelurahan = data;
    //     L.geoJSON(batasKelurahan, {
    //       style: { color: '#eb12f2ff', weight: 2, fillOpacity: 0 }
    //     }).addTo(map);
    //   });

    // wilayah bencana
    L.geoJSON(polygonData, {
      style: f => ({
        color: f.properties.color || 'red',
        fillColor: f.properties.color || 'red',
        fillOpacity: 0.4
      })
    }).addTo(map);

    // Titik evakuasi hanya dari database (markerData)
    markerData.forEach((m, i) => {
      L.marker([m.lat, m.lng], {icon: L.icon({iconUrl: 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/icons/flag-fill.svg', iconSize: [28, 28], iconAnchor: [14, 28]})}).addTo(map).bindPopup(`<i class='bi bi-flag-fill text-success'></i> Titik Evakuasi: ${m.nama}`);
    });

    let graphNodes = {};
    let graphEdges = [];

    function pointToKey(latlng) {
      return latlng.lat.toFixed(6) + ',' + latlng.lng.toFixed(6);
    }

    function haversineDistance(latlng1, latlng2) {
      const R = 6371e3;
      const rad = x => x * Math.PI / 180;
      const dLat = rad(latlng2.lat - latlng1.lat);
      const dLng = rad(latlng2.lng - latlng1.lng);
      const a = Math.sin(dLat / 2) ** 2 +
        Math.cos(rad(latlng1.lat)) * Math.cos(rad(latlng2.lat)) *
        Math.sin(dLng / 2) ** 2;
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }

    function isInDisasterArea(latlng) {
      return polygonData.features.some(polygon =>
        turf.booleanPointInPolygon(turf.point([latlng.lng, latlng.lat]), polygon)
      );
    }

    function addEdge(a, b, weight) {
      if (isInDisasterArea(a) || isInDisasterArea(b)) return;
      const keyA = pointToKey(a);
      const keyB = pointToKey(b);
      if (!graphNodes[keyA]) graphNodes[keyA] = a;
      if (!graphNodes[keyB]) graphNodes[keyB] = b;
      graphEdges.push({
        from: keyA,
        to: keyB,
        weight
      });
      graphEdges.push({
        from: keyB,
        to: keyA,
        weight
      });
    }

    // manggil grafik jalur evakuasi

    fetch('data/jalan_cianjur.geojson')
      .then(res => res.json())
      .then(data => {
        L.geoJSON(data, {
          style: {
            color: '#00cc66',
            weight: 2
          },
          onEachFeature: function(feature, layer) {

            if (!feature.geometry || feature.geometry.type !== 'LineString') return;

            const coords = feature.geometry.coordinates;
            if (!Array.isArray(coords)) return;

            for (let i = 0; i < coords.length - 1; i++) {
              const coordA = coords[i];
              const coordB = coords[i + 1];

              if (
                Array.isArray(coordA) && coordA.length === 2 &&
                Array.isArray(coordB) && coordB.length === 2
              ) {
                const a = L.latLng(coordA[1], coordA[0]);
                const b = L.latLng(coordB[1], coordB[0]);
                const distance = haversineDistance(a, b);
                addEdge(a, b, distance);
              }
            }
          }

        }).addTo(map);
      });

    function aStar(start, goal) {
      const startKey = pointToKey(start);
      const goalKey = pointToKey(goal);
      const openSet = new Set([startKey]);
      const cameFrom = {},
        gScore = {},
        fScore = {};
      for (let key in graphNodes) {
        gScore[key] = Infinity;
        fScore[key] = Infinity;
      }
      gScore[startKey] = 0;
      fScore[startKey] = haversineDistance(start, goal);

      while (openSet.size > 0) {
        let currentKey = [...openSet].reduce((a, b) => fScore[a] < fScore[b] ? a : b);
        if (currentKey === goalKey) {
          let path = [graphNodes[goalKey]];
          while (cameFrom[currentKey]) {
            currentKey = cameFrom[currentKey];
            path.unshift(graphNodes[currentKey]);
          }
          return path;
        }
        openSet.delete(currentKey);
        const neighbors = graphEdges.filter(e => e.from === currentKey);
        for (let edge of neighbors) {
          const tentativeG = gScore[currentKey] + edge.weight;
          if (tentativeG < gScore[edge.to]) {
            cameFrom[edge.to] = currentKey;
            gScore[edge.to] = tentativeG;
            fScore[edge.to] = tentativeG + haversineDistance(graphNodes[edge.to], goal);
            openSet.add(edge.to);
          }
        }
      }
      return null;
    }

    function findNearestNode(latlng) {
      let nearest = null,
        minDist = Infinity;
      for (let key in graphNodes) {
        const dist = haversineDistance(latlng, graphNodes[key]);
        if (dist < minDist) {
          minDist = dist;
          nearest = graphNodes[key];
        }
      }
      return nearest;
    }

    function findNearestSafeNode(latlng) {
      let nearest = null,
        minDist = Infinity;
      for (let key in graphNodes) {
        const node = graphNodes[key];
        if (!isInDisasterArea(node)) {
          const dist = haversineDistance(latlng, node);
          if (dist < minDist) {
            minDist = dist;
            nearest = node;
          }
        }
      }
      return nearest;
    }

    let manualMode = false;
    let manualMarker = null;

    document.getElementById("startBtn").addEventListener("click", () => {
      if (!navigator.geolocation) return alert("Geolocation tidak didukung.");
      navigator.geolocation.getCurrentPosition(pos => {
        const userLatLng = L.latLng(pos.coords.latitude, pos.coords.longitude);
        // Pastikan batasCianjur sudah dimuat
        if (!batasCianjur) {
          alert("Batas wilayah Cianjur belum dimuat. Silakan coba lagi.");
          return;
        }
        // Cek apakah user di dalam Cianjur
        const isInCianjur = turf.booleanPointInPolygon(
          turf.point([userLatLng.lng, userLatLng.lat]),
          batasCianjur.features ? batasCianjur.features[0] : batasCianjur
        );
        if (!isInCianjur) {
          alert("Lokasi Anda di luar Kabupaten Cianjur! Silakan pilih lokasi manual di dalam Cianjur dengan klik pada peta.");
          manualMode = true;
          if (manualMarker) {
            map.removeLayer(manualMarker);
            manualMarker = null;
          }
          return;
        }
        jalankanEvakuasi(userLatLng);
      });
    });

    // Fungsi untuk menjalankan proses evakuasi dari lokasi tertentu
    function jalankanEvakuasi(userLatLng) {
      const startNode = findNearestSafeNode(userLatLng);
      let candidates = [];
      markerData.forEach(m => {
        const node = findNearestSafeNode(m);
        if (node) {
          const dist = haversineDistance(userLatLng, m);
          candidates.push({ marker: m, node, dist });
        }
      });
      if (candidates.length === 0 || !startNode) {
        alert("Tidak ada jalur aman ke titik evakuasi!");
        return;
      }
      candidates.sort((a, b) => a.dist - b.dist);
      const goalNode = candidates[0].node;
      const path = aStar(startNode, goalNode);
      // Tampilkan polyline dan info jalan
      if (path) {
        L.polyline(path, {
          color: 'blue',
          weight: 4
        }).addTo(map);
        map.fitBounds(L.polyline(path).getBounds());

        // Ambil info jalan dari GeoJSON jalan_cianjur
        fetch('data/jalan_cianjur.geojson')
          .then(res => res.json())
          .then(jalanGeojson => {
            // Cari ruas jalan yang dilalui
            let jalanDilalui = [];
            let totalJarak = 0;
            for (let i = 0; i < path.length - 1; i++) {
              const a = path[i];
              const b = path[i + 1];
              let namaJalan = '-';
              let minDist = Infinity;
              let found = false;
              // Cari ruas terdekat di GeoJSON
              jalanGeojson.features.forEach(f => {
                if (f.geometry.type === 'LineString') {
                  for (let j = 0; j < f.geometry.coordinates.length - 1; j++) {
                    const ca = f.geometry.coordinates[j];
                    const cb = f.geometry.coordinates[j + 1];
                    const la = {lat: ca[1], lng: ca[0]};
                    const lb = {lat: cb[1], lng: cb[0]};
                    // Cek apakah ruas ini mirip dengan ruas path
                    const distA = haversineDistance(a, la);
                    const distB = haversineDistance(b, lb);
                    const distAB = haversineDistance(a, b);
                    const distLAB = haversineDistance(la, lb);
                    if (distA < 30 && distB < 30 && Math.abs(distAB - distLAB) < 30) {
                      namaJalan = f.properties && f.properties.nama ? f.properties.nama : '-';
                      found = true;
                      break;
                    }
                  }
                }
              });
              const jarak = haversineDistance(a, b);
              totalJarak += jarak;
              jalanDilalui.push({nama: namaJalan, jarak: jarak});
            }
            // Tampilkan di tabel
            const tableBody = document.getElementById('jalanInfoTable');
            tableBody.innerHTML = '';
            const maxRows = 6;
            jalanDilalui.slice(0, maxRows).forEach(j => {
              const tr = document.createElement('tr');
              tr.innerHTML = `<td>${j.nama}</td><td>${j.jarak.toFixed(1)}</td>`;
              tableBody.appendChild(tr);
            });
            if (jalanDilalui.length > maxRows) {
              const tr = document.createElement('tr');
              tr.innerHTML = `<td colspan='2' class='text-center text-muted'>...</td>`;
              tableBody.appendChild(tr);
            }
            document.getElementById('totalJarakCell').textContent = totalJarak.toFixed(1);
            document.getElementById('jalanInfoContainer').style.display = 'block';
          });
      } else {
        alert("Jalur tidak ditemukan!");
        document.getElementById('jalanInfoContainer').style.display = 'none';
      }
    }

    // Event klik peta untuk mode manual
    map.on('click', function(e) {
      if (!manualMode) return;
      const latlng = e.latlng;
      // Pastikan klik di dalam Cianjur
      if (!batasCianjur) return;
      const isInCianjur = turf.booleanPointInPolygon(
        turf.point([latlng.lng, latlng.lat]),
        batasCianjur.features ? batasCianjur.features[0] : batasCianjur
      );
      if (!isInCianjur) {
        alert("Silakan pilih lokasi di dalam Kabupaten Cianjur!");
        return;
      }
      if (manualMarker) map.removeLayer(manualMarker);
      manualMarker = L.marker(latlng, { draggable: true }).addTo(map).bindPopup('Lokasi Manual').openPopup();
      manualMode = false;
      jalankanEvakuasi(latlng);
    });
  </script>
</body>

</html>