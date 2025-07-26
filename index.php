<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Evakuasi – A* + OSRM, Hindari Wilayah Bencana</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

    <style>
        #map {
            height: 90vh;
        }

        #infoBox {
            padding: 10px;
            background-color: #f8f9fa;
            border-top: 1px solid #ddd;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">WebGIS Evakuasi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <button id="startBtn" class="btn btn-light me-2">Jalur Evakuasi</button>
                    </li>
                    <li class="nav-item">
                        <a href="Login/login.php" class="btn btn-outline-light">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- PETA -->
    <div id="map"></div>

    <!-- INFO BOX -->
    <div id="infoBox" class="text-center">
        Klik tombol <strong>Jalur Evakuasi</strong> untuk menampilkan rute evakuasi terdekat yang aman dari wilayah bencana.
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // INIT MAP
        const map = L.map('map').setView([-6.82, 107.14], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // TITIK PENGGUNA
        const titikUser = L.latLng(-6.819, 107.134);
        L.marker(titikUser, {
            icon: L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/64/64113.png',
                iconSize: [26, 26]
            })
        }).addTo(map).bindPopup('Pengguna (dummy)').openPopup();

        // POLIGON BENCANA
        const wilayahBencana = L.polygon([
                [-6.824, 107.140],
                [-6.820, 107.150],
                [-6.812, 107.148],
                [-6.815, 107.135]
            ], {
                color: 'red',
                fillColor: '#f03',
                fillOpacity: 0.25
            })
            .addTo(map).bindPopup('Wilayah Bencana');

        // TITIK EVAKUASI
        const evakuasiPoints = [
            L.latLng(-6.825, 107.155),
            L.latLng(-6.810, 107.130),
            L.latLng(-6.818, 107.139), // dalam poligon
            L.latLng(-6.822, 107.148) // dalam poligon
        ];
        evakuasiPoints.forEach((pt, i) =>
            L.marker(pt).addTo(map).bindPopup(`Evakuasi ${i + 1}`)
        );

        // FUNGSI CEK TITIK DALAM POLIGON
        function isInsidePolygon(point, polygon) {
            const x = point.lat,
                y = point.lng;
            const vs = polygon.getLatLngs()[0];
            let inside = false;
            for (let i = 0, j = vs.length - 1; i < vs.length; j = i++) {
                const xi = vs[i].lat,
                    yi = vs[i].lng;
                const xj = vs[j].lat,
                    yj = vs[j].lng;
                const intersect = ((yi > y) != (yj > y)) &&
                    (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
                if (intersect) inside = !inside;
            }
            return inside;
        }

        function findNearestEvakuasi(from, points) {
            let minDist = Infinity,
                closest = null;
            points.forEach(pt => {
                if (isInsidePolygon(pt, wilayahBencana)) return;
                const d = from.distanceTo(pt);
                if (d < minDist) {
                    minDist = d;
                    closest = pt;
                }
            });
            return closest;
        }

        let routingControl = null;
        document.getElementById('startBtn').addEventListener('click', () => {
            if (routingControl) map.removeControl(routingControl);

            const nearest = findNearestEvakuasi(titikUser, evakuasiPoints);
            const info = document.getElementById('infoBox');

            if (!nearest) {
                info.innerHTML = '<span class="text-danger">Semua titik evakuasi berada di dalam wilayah bencana!</span>';
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
                        opacity: 0.6,
                        weight: 5
                    }]
                },
                createMarker: () => null,
                addWaypoints: false,
                draggableWaypoints: false
            }).addTo(map);

            info.innerHTML = `Jalur evakuasi berhasil ditampilkan. Jarak ke titik evakuasi terdekat: <strong>${(titikUser.distanceTo(nearest)/1000).toFixed(2)} km</strong>`;
        });
    </script>
</body>

</html>