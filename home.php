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
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
  <style>
    html,
    body {
      height: 100%;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: row;
    }

    body {
      flex: 1;
      overflow: hidden;
    }

    .sidebar {
      width: 250px;
      background: #f57c00;
      color: white;
      padding: 20px;
      flex-shrink: 0;
    }

    #map {
      flex: 1;
      height: 100vh;
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <h4>Evakuasi</h4>
    <button id="startBtn" class="btn btn-evakuasi">🚨 Jalur Aman</button>
  </div>
  <div id="map"></div>

  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
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

    // jalur evakuasi
    markerData.forEach((m, i) => {
      L.marker([m.lat, m.lng]).addTo(map).bindPopup(`🧱 Titik Evakuasi ${i + 1}: ${m.nama}`);
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

    document.getElementById("startBtn").addEventListener("click", () => {
      if (!navigator.geolocation) return alert("Geolocation tidak didukung.");
      navigator.geolocation.getCurrentPosition(pos => {
        const userLatLng = L.latLng(pos.coords.latitude, pos.coords.longitude);
        const startNode = findNearestNode(userLatLng);
        let nearestEvakuasi = null,
          shortestDist = Infinity;
        markerData.forEach(m => {
          const d = haversineDistance(userLatLng, m);
          if (d < shortestDist) {
            shortestDist = d;
            nearestEvakuasi = m;
          }
        });
        const goalNode = findNearestNode(nearestEvakuasi);
        const path = aStar(startNode, goalNode);
        if (path) {
          L.polyline(path, {
            color: 'blue',
            weight: 4
          }).addTo(map);
          map.fitBounds(L.polyline(path).getBounds());
        } else {
          alert("Jalur tidak ditemukan!");
        }
      });
    });
  </script>
</body>

</html>