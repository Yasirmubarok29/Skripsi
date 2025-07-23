<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>A-Star Routing Demo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

    <style>
        #map {
            height: 100vh;
        }

        .leaflet-top.leaflet-left {
            margin-top: 60px;
        }

        #startBtn {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 999;
            padding: 8px 12px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            font-family: sans-serif;
        }
    </style>
</head>

<body>

    <div id="startBtn">Start A-Star</div>
    <div id="map"></div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

    <script>
        const map = L.map('map').setView([-6.82, 107.14], 13); // Lokasi sekitar Cianjur

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Titik A dan Titik B
        const titikA = L.latLng(-6.819, 107.134);
        const titikB = L.latLng(-6.825, 107.155);

        const markerA = L.marker(titikA).addTo(map).bindPopup("Titik A");
        const markerB = L.marker(titikB).addTo(map).bindPopup("Titik B");

        let routingControl = null;

        // Tombol Start A*
        document.getElementById('startBtn').addEventListener('click', function() {
            if (routingControl) {
                map.removeControl(routingControl);
            }

            routingControl = L.Routing.control({
                waypoints: [titikA, titikB],
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
                createMarker: function() {
                    return null;
                }, // nonaktifkan marker default
                addWaypoints: false,
                draggableWaypoints: false
            }).addTo(map);
        });
    </script>

</body>

</html>