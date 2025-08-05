<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Login/login.php");
    exit;
}
require '../conection/db.php';

$success = '';
$error = '';

// Hapus data
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM titik_evakuasi WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Titik dengan ID $id berhasil dihapus.";
    } else {
        $error = "Gagal menghapus data. " . $conn->error;
    }
}

// Simpan data baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $nama = trim($_POST['nama'] ?? '');
    $lat  = $_POST['latitude'] ?? '';
    $lng  = $_POST['longitude'] ?? '';
    $ket  = trim($_POST['keterangan'] ?? '');

    if ($nama === '' || $lat === '' || $lng === '') {
        $error = "Nama, Latitude dan Longitude wajib diisi.";
    } elseif (!is_numeric($lat) || !is_numeric($lng)) {
        $error = "Latitude/Longitude tidak valid.";
    } else {
        $stmt = $conn->prepare("INSERT INTO titik_evakuasi (nama, latitude, longitude, keterangan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdds", $nama, $lat, $lng, $ket);
        if ($stmt->execute()) {
            $success = "Titik berhasil disimpan.";
        } else {
            $error = "Gagal menyimpan data. " . $conn->error;
        }
    }
}

$all = $conn->query("SELECT * FROM titik_evakuasi ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$polygons = $conn->query("SELECT nama, color, geojson FROM wilayah_bencana")->fetch_all(MYSQLI_ASSOC);

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Marker Titik</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        #map { height: 60vh; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.1); }
        .card { border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .main-content { margin-left: 260px; padding: 20px; }

        :root{
      --orange:#ff6f00;
      --sidebar-bg:#1f2937;
      --sidebar-hover:#374151;
    }
        /* Sidebar Style */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background-color: #1f2937;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sidebar .brand{
            display:flex;
            align-items:center;
            gap:.5rem;
            width: 100%;
            padding:16px 20px;
            background:var(--orange);
            font-weight:600;
            }
            .sidebar .brand img{
            height:30px;
        }
        .sidebar .nav-links {
            width: 100%;
        }
        .sidebar .nav-links a {
            display: block;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
        }
        .sidebar .nav-links a.active,
        .sidebar .nav-links a:hover {
            background-color: #374151;
        }
        .sidebar .logout {
            margin-top: auto;
            padding: 20px;
        }

        .topbar {
            height: 60px;
            background: #ff6f00;
            color: white;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-left: 250px;
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
        <a href="index.php">🏠 Dashboard</a>
        <a href="marker.php" class="active">📍 Tambah Marker Titik</a>
        <a href="poligon.php">🗺️ Tambah Polygon Bencana</a>
    </nav>
    <div class="logout">
        <a href="../Login/logout.php" class="btn btn-outline-light btn-sm">🚪 Logout</a>
    </div>
</aside>

<!-- TOPBAR -->
<div class="topbar">
    <h5 class="mb-0">📍 Tambah Titik Evakuasi</h5>
    <span>Halo, <?= htmlspecialchars($_SESSION['admin']) ?></span>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <?php if ($error): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= esc($success) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card p-3">
                <p class="text-muted mb-2">Klik pada peta atau isi manual koordinat di bawah.</p>
                <div id="map"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3">
                <h5 class="mb-3">Form Tambah Titik</h5>
                <form method="post">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Nama Titik/Posko</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="latitude" id="lat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="longitude" id="lng" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Titik</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card p-3 mt-4">
        <h5 class="mb-3">📋 Daftar Semua Titik</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Keterangan</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($all): $no=1; foreach($all as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= esc($row['nama']) ?></td>
                        <td><?= esc($row['latitude']) ?></td>
                        <td><?= esc($row['longitude']) ?></td>
                        <td><?= esc($row['keterangan']) ?></td>
                        <td><?= esc($row['waktu_dibuat']) ?></td>
                        <td><a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus titik ini?')">Hapus</a></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center text-muted">Belum ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- LEAFLET LIBRARY -->
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

<script>
// Inisialisasi peta hanya sekali
const map = L.map('map').setView([-6.82, 107.14], 10);

// Tambahkan tile layer OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// ========== TAMPILKAN POLYGON BENCANA DARI DATABASE ==========
const polygonsFromDB = <?= json_encode($polygons, JSON_UNESCAPED_UNICODE) ?>;
polygonsFromDB.forEach(p => {
    try {
        const geojson = JSON.parse(p.geojson);
        L.geoJSON(geojson, {
            style: { color: p.color, fillColor: p.color, fillOpacity: 0.4 }
        }).addTo(map).bindPopup(p.nama);
    } catch (e) {
        console.error("Error parsing polygon:", e);
    }
});

// ========== TAMPILKAN MARKER TITIK POSKO DARI DATABASE ==========
const markers = <?= json_encode($all, JSON_UNESCAPED_UNICODE) ?>;
markers.forEach(m => {
    L.marker([m.latitude, m.longitude])
        .addTo(map)
        .bindPopup(`<b>${m.nama}</b><br>Lat: ${m.latitude}, Lng: ${m.longitude}`);
});

// ========== BISA KLIK PETA UNTUK PILIH TITIK ==========
let marker = null;
map.on('click', function(e) {
    const { lat, lng } = e.latlng;

    // Isi form input (jika ada)
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    if (latInput && lngInput) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
    }

    if (marker) map.removeLayer(marker);
    marker = L.marker([lat, lng]).addTo(map)
        .bindPopup(`Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`).openPopup();
});


// ========== TAMBAHKAN BATAS ADMINISTRATIF CIANJUR (GeoJSON statis) ==========
fetch('../assets/cianjur.geojson')
    .then(res => {
        if (!res.ok) throw new Error("Gagal memuat file Cianjur.geojson");
        return res.json();
    })
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
    .catch(err => console.error("Gagal memuat Cianjur.geojson:", err));
</script>

</body>
</html>
