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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    #map {
      width: 100%;
      height: 48vh;
      min-height: 320px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,.10);
      border: 1px solid #eee;
      background: #eaeaea;
      z-index: 1;
    }
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
    .content-wrapper {
      margin-left: 250px;
      transition: margin-left .25s cubic-bezier(.4,2,.6,1);
      min-height: 100vh;
      background: #f5f5f5;
    }
    .content-wrapper.full {
      margin-left: 0;
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
  </style>
</head>
<body>

<!-- SIDEBAR -->
<nav id="sidebar" class="sidebar d-flex flex-column">
  <div class="sidebar-header justify-content-center">
    <img src="../assets/logo.png" alt="Logo" width="100" height="100" style="display:block; margin:0 auto;">
  </div>
  <ul class="nav nav-pills flex-column mb-auto mt-3" style="gap:2px;">
    <li class="nav-item">
      <a href="index.php" class="nav-link d-flex align-items-center gap-2">
        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="marker.php" class="nav-link active d-flex align-items-center gap-2">
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

<!-- MAIN CONTENT -->
<div id="contentWrapper" class="content-wrapper">
    <?php if ($error): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= esc($success) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100 border-0 bg-light">
                <div class="card-header bg-warning bg-gradient text-dark d-flex justify-content-between align-items-center border-0" style="border-radius:12px 12px 0 0; font-weight:600;">
                    <span><i class="bi bi-geo-alt-fill me-1"></i> Pilih lokasi pada peta atau isi koordinat manual</span>
                    <button class="btn btn-sm btn-outline-dark bg-white border-0" type="button" onclick="map.setView([-6.82, 107.14], 10)"><i class="bi bi-arrow-clockwise"></i> Reset Peta</button>
                </div>
                <div class="p-3 bg-white rounded-bottom"><div id="map"></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 border-0 bg-light">
                <div class="card-header bg-primary bg-gradient text-white border-0" style="border-radius:12px 12px 0 0; font-weight:600;">
                  <i class="bi bi-plus-circle me-1"></i> Form Tambah Titik
                </div>
                <div class="p-3 bg-white rounded-bottom">
                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Nama Titik/Posko</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Posko Utama">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="latitude" id="lat" class="form-control" required placeholder="-6.82">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="longitude" id="lng" class="form-control" required placeholder="107.14">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" placeholder="Keterangan tambahan..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Titik</button>
                </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4 shadow-sm">
        <div class="card-header bg-success bg-gradient text-white d-flex justify-content-between align-items-center border-0" style="border-radius:12px 12px 0 0; font-weight:600;">
            <span><i class="bi bi-list-check me-1"></i> Daftar Semua Titik Evakuasi</span>
            <input type="text" id="searchTable" class="form-control form-control-sm w-auto border-0" placeholder="Cari nama..." style="min-width:180px;">
        </div>
        <div class="p-3 bg-white rounded-bottom">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped table-sm align-middle" id="titikTable">
                <thead class="table-light align-middle">
                  <tr>
                    <th class="fw-bold text-center">#</th>
                    <th class="fw-bold">Nama</th>
                    <th class="fw-bold">Latitude</th>
                    <th class="fw-bold">Longitude</th>
                    <th class="fw-bold">Keterangan</th>
                    <th class="fw-bold">Dibuat</th>
                    <th class="fw-bold text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($all): $no=1; foreach($all as $row): ?>
                  <tr>
                    <td class="text-center text-secondary small"><?= $no++ ?></td>
                    <td class="fw-semibold text-dark"><?= esc($row['nama']) ?></td>
                    <td><span class="badge rounded-pill bg-primary-subtle text-primary small px-2 py-1"><?= esc($row['latitude']) ?></span></td>
                    <td><span class="badge rounded-pill bg-primary-subtle text-primary small px-2 py-1"><?= esc($row['longitude']) ?></span></td>
                    <td><?= esc($row['keterangan']) ? esc($row['keterangan']) : '<span class="text-muted fst-italic">-</span>' ?></td>
                    <td><span class="badge rounded-pill bg-secondary-subtle text-dark small px-2 py-1"><?= esc($row['waktu_dibuat']) ?></span></td>
                    <td class="text-center">
                      <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus" onclick="return confirm('Hapus titik ini?')">
                        <i class="bi bi-trash"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; else: ?>
                  <tr><td colspan="7" class="text-center text-muted">Belum ada data</td></tr>
                  <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>

    <!-- Floating Action Button (FAB) for quick add -->
    <button class="btn-fab d-lg-none" title="Tambah Titik" onclick="window.scrollTo({top:0,behavior:'smooth'})">+</button>

    <script>
    // Sidebar toggle (mobile)
    const sidebar = document.getElementById('sidebar');
    const contentWrapper = document.getElementById('contentWrapper');
    const btnToggle = document.getElementById('btnToggle');
    if(btnToggle) {
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
    </script>
</div>

<!-- LEAFLET LIBRARY -->
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

<script>
// Inisialisasi peta hanya sekali
const map = L.map('map').setView([-6.82, 107.14], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Tampilkan polygon bencana dari database
const polygonsFromDB = <?= json_encode($polygons, JSON_UNESCAPED_UNICODE) ?>;
polygonsFromDB.forEach(p => {
    try {
        const geojson = JSON.parse(p.geojson);
        L.geoJSON(geojson, {
            style: { color: p.color, fillColor: p.color, fillOpacity: 0.4 }
        }).addTo(map).bindTooltip(p.nama, {permanent:false, direction:'top'});
    } catch (e) {
        console.error("Error parsing polygon:", e);
    }
});

// Tampilkan marker titik posko dari database
const markers = <?= json_encode($all, JSON_UNESCAPED_UNICODE) ?>;
markers.forEach(m => {
    const marker = L.marker([m.latitude, m.longitude]).addTo(map);
    marker.bindPopup(`
      <div style='min-width:180px'>
        <div class='fw-bold mb-1'><i class='bi bi-geo-alt-fill text-primary'></i> ${m.nama}</div>
        <div class='small text-muted mb-1'>Lat: <b>${m.latitude}</b> | Lng: <b>${m.longitude}</b></div>
        <div class='mb-1'>${m.keterangan ? m.keterangan : '<span class=\'text-muted fst-italic\'>Tidak ada keterangan</span>'}</div>
        <div class='text-secondary small'>${m.waktu_dibuat ? 'Dibuat: ' + m.waktu_dibuat : ''}</div>
      </div>
    `);
    marker.bindTooltip(`<b>${m.nama}</b>`, {permanent:false, direction:'top'});
});

// Klik peta untuk pilih titik
let marker = null;
map.on('click', function(e) {
    const { lat, lng } = e.latlng;
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    if (latInput && lngInput) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
    }
    if (marker) map.removeLayer(marker);
    marker = L.marker([lat, lng]).addTo(map)
        .bindTooltip(`Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`, {permanent:false, direction:'top'}).openTooltip();
});

// Tambahkan batas administratif Cianjur (GeoJSON statis)
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
        }).addTo(map).bindTooltip("Wilayah Administratif Cianjur", {permanent:false, direction:'top'});
    })
    .catch(err => console.error("Gagal memuat Cianjur.geojson:", err));

// Interaktif: filter tabel
document.getElementById('searchTable').addEventListener('input', function() {
    const val = this.value.toLowerCase();
    const rows = document.querySelectorAll('#titikTable tbody tr');
    rows.forEach(row => {
        const nama = row.children[1]?.textContent.toLowerCase() || '';
        row.style.display = nama.includes(val) ? '' : 'none';
    });
});
</script>

</body>
</html>
