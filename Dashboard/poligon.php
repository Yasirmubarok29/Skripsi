<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../Login/login.php");
    exit;
}
require '../conection/db.php';

function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Hapus data
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM wilayah_bencana WHERE id = $id");
    header("Location: poligon.php");
    exit;
}

// Simpan data baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'simpan') {
    $nama = $_POST['nama'];
    $color = $_POST['color'];
    $geojson = $_POST['geojson'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("INSERT INTO wilayah_bencana (nama, color, geojson, status, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $nama, $color, $geojson, $status);
    $stmt->execute();
    header("Location: poligon.php");
    exit;
}

// Update data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
    $id = intval($_POST['id']);
    $nama = $_POST['nama'];
    $color = $_POST['color'];
    $geojson = $_POST['geojson'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE wilayah_bencana SET nama=?, color=?, geojson=?, status=? WHERE id=?");
    $stmt->bind_param("ssssi", $nama, $color, $geojson, $status, $id);
    $stmt->execute();
    header("Location: poligon.php");
    exit;
}

$polygons = $conn->query("SELECT * FROM wilayah_bencana ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$markers = $conn->query("SELECT nama, latitude, longitude FROM titik_evakuasi")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Kelola Polygon Bencana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-draw@1.0.4/dist/leaflet.draw.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background: #eef0f3;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
      overflow-x: hidden;
    }
    /* Sidebar, Topbar, Card, Table, etc: (copy dari file marker.php agar konsisten) */
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
      color: #198754;
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
      .main-content {
        margin-left: 0;
        padding: 15px 6px 6px 6px;
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
      .main-content {
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
    .main-content {
      margin-left: 260px;
      padding: 32px 24px 24px 24px;
      min-height: 100vh;
      background: #eef0f3;
      transition: margin-left .25s cubic-bezier(.4,2,.6,1);
    }
    .main-content.full {
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
      background: linear-gradient(90deg, #198754 0%, #157347 100%);
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
      height: 48vh;
      min-height: 320px;
      border-radius: 14px;
      box-shadow: 0 2px 10px rgba(33,37,41,.07);
      border: 1px solid #dee2e6;
      margin-bottom: 18px;
      background: linear-gradient(90deg, #e9ecef 0%, #f8f9fa 100%);
    }
    .table {
      border-radius: 10px;
      overflow: hidden;
      background: #fff;
      transition: box-shadow .2s;
    }
    .table thead {
      background: #f8f9fa;
    }
    .table-hover tbody tr:hover {
      background: #e9ecef !important;
      transition: background .2s;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background-color: #f6f6f6;
    }
    .btn-success {
      background: linear-gradient(90deg,#198754 60%,#157347 100%);
      border: none;
      transition: background .2s;
      font-weight: 600;
    }
    .btn-success:hover, .btn-warning:hover {
      background: linear-gradient(90deg,#157347 60%,#198754 100%);
      color: #fff;
    }
    .form-label {
      font-weight: 500;
      color: #157347;
    }
    .badge-color {
      display: inline-block;
      width: 16px;
      height: 16px;
      border-radius: 4px;
      margin-right: 8px;
      border: 1px solid #dee2e6;
    }
  </style>
</head>
<body>
<!-- SIDEBAR -->
<nav id="sidebar" class="sidebar d-flex flex-column">
  <div class="sidebar-header">
    <img src="../assets/logo1.png" alt="Logo1 WebGIS">
  </div>
  <div class="admin-info mb-0">
    <i class="bi bi-person-circle"></i>
    <span>Selamat datang, <b><?= esc($_SESSION['admin'] ?? 'Admin') ?></b></span>
  </div>
  <div class="nav-section">
    <a href="index.php" class="nav-link">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="marker.php" class="nav-link">
      <i class="bi bi-geo-alt-fill"></i> Titik Evakuasi
    </a>
    <a href="poligon.php" class="nav-link active">
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
    <span class="title"><i class="bi bi-vector-pen"></i> Wilayah Bencana <span class="version">v1.0</span></span>
  </div>
  <div class="user-box">
    <i class="bi bi-person-circle"></i>
    <?= esc($_SESSION['admin'] ?? 'Admin') ?>
  </div>
</div>
<div class="main-content">
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <div class="card shadow-sm h-100">
        <div class="card-header-modern"><i class="bi bi-map"></i> Peta Wilayah Bencana</div>
        <div class="p-3"><div id="map" class="mb-2"></div></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-header-modern"><i class="bi bi-pencil-square"></i> Form Polygon Wilayah Bencana</div>
        <div class="p-3">
          <form id="formPolygon" method="post">
            <input type="hidden" name="action" value="simpan" id="formAction">
            <input type="hidden" name="id" id="polygonId">
            <input type="hidden" name="geojson" id="geojsonData">
            <input type="hidden" name="color" id="color" value="#ff0000">
            <div class="mb-3">
              <label class="form-label">Nama Wilayah</label>
              <input type="text" name="nama" id="nama" class="form-control" placeholder="Nama Wilayah..." required>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status" id="status" class="form-select" required>
                <option value="BAHAYA">BAHAYA</option>
                <option value="SIAGA">SIAGA</option>
                <option value="WASPADA">WASPADA</option>
              </select>
            </div>
            <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-lg"></i> Simpan Polygon</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="card shadow-sm">
    <div class="card-header-modern d-flex justify-content-between align-items-center">
      <span><i class="bi bi-table"></i> Daftar Polygon Wilayah Bencana</span>
      <input type="text" id="searchTable" class="form-control form-control-sm w-auto" placeholder="Cari nama..." style="min-width:180px;">
    </div>
    <div class="p-3">
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped table-sm align-middle" id="polygonTable">
          <thead class="table-light">
            <tr>
              <th class="text-center">#</th>
              <th>Nama</th>
              <th>Status</th>
              <th>Warna</th>
              <th>Waktu</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php $no=1; foreach ($polygons as $p): ?>
            <tr>
              <td class="text-center"><?= $no++ ?></td>
              <td><?= esc($p['nama']) ?></td>
              <td>
                <span class="badge <?= $p['status'] === 'BAHAYA' ? 'bg-danger' : ($p['status'] === 'SIAGA' ? 'bg-warning text-dark' : 'bg-primary') ?>">
                  <i class="bi <?= $p['status'] === 'BAHAYA' ? 'bi-exclamation-triangle' : ($p['status'] === 'SIAGA' ? 'bi-exclamation-circle' : 'bi-info-circle') ?>"></i>
                  <?= esc(ucfirst($p['status'])) ?>
                </span>
              </td>
              <td>
                <span class="badge-color" style="background:<?= esc($p['color']) ?>"></span>
                <?= esc($p['color']) ?>
              </td>
              <td><?= esc($p['created_at']) ?></td>
              <td class="text-center">
                <button onclick='editPolygon(<?= json_encode($p) ?>)' class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Edit"><i class="bi bi-pencil"></i></button>
                <a href="#" class="btn btn-sm btn-outline-danger rounded-circle btn-delete" data-id="<?= $p['id'] ?>" title="Hapus"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Hapus -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="confirmDeleteLabel"><i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <p>Apakah Anda yakin ingin menghapus polygon ini?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <a href="#" id="btnDeleteConfirm" class="btn btn-danger">Hapus</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle (mobile)
const sidebar = document.querySelector('.sidebar');
const contentWrapper = document.querySelector('.main-content');
const btnToggle = document.getElementById('btnToggle');
if(btnToggle) {
    btnToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        contentWrapper.classList.toggle('full');
    });
}
// Interaktif: filter tabel polygon
document.getElementById('searchTable').addEventListener('input', function() {
    const val = this.value.toLowerCase();
    const rows = document.querySelectorAll('#polygonTable tbody tr');
    rows.forEach(row => {
        const nama = row.children[1]?.textContent.toLowerCase() || '';
        row.style.display = nama.includes(val) ? '' : 'none';
    });
});

// Modal konfirmasi hapus
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.btn-delete').forEach(function(el) {
    el.addEventListener('click', function(e) {
      e.preventDefault();
      var id = this.getAttribute('data-id');
      var deleteUrl = '?hapus=' + id;
      document.getElementById('btnDeleteConfirm').setAttribute('href', deleteUrl);
      var modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
      modal.show();
    });
  });
});
</script>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script>
const map = L.map('map').setView([-6.82, 107.14], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
const drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);
map.addControl(new L.Control.Draw({
  draw: { polygon: true, marker: false, polyline: false, rectangle: false, circle: false, circlemarker: false },
  edit: { featureGroup: drawnItems }
}));
map.on(L.Draw.Event.CREATED, e => {
  drawnItems.clearLayers();
  drawnItems.addLayer(e.layer);
});
const polygons = <?= json_encode($polygons, JSON_UNESCAPED_UNICODE) ?>;
polygons.forEach(p => {
  const layer = L.geoJSON(JSON.parse(p.geojson), {
    style: { color: p.color, fillColor: p.color, fillOpacity: 0.4 }
  }).addTo(map).bindPopup(`${p.nama} - ${p.status}`);
});
const markerData = <?= json_encode($markers, JSON_UNESCAPED_UNICODE) ?>;
markerData.forEach(m => {
  L.marker([m.latitude, m.longitude]).addTo(map).bindPopup(`Evakuasi: ${m.nama}`);
});
document.getElementById('formPolygon').addEventListener('submit', function(e) {
  if (drawnItems.getLayers().length === 0) {
    alert("Silakan gambar polygon terlebih dahulu.");
    e.preventDefault();
    return;
  }
  const geojson = drawnItems.toGeoJSON();
  document.getElementById('geojsonData').value = JSON.stringify(geojson);
});

// Tetapkan warna otomatis berdasarkan status
document.getElementById('status').addEventListener('change', function () {
  const status = this.value;
  const colorInput = document.getElementById('color');
  let color = '#cccccc';
  if (status === 'BAHAYA') color = '#dc3545';
  else if (status === 'SIAGA') color = '#ffc107';
  else if (status === 'WASPADA') color = '#0d6efd';
  colorInput.value = color;
});
window.addEventListener('DOMContentLoaded', function () {
  document.getElementById('status').dispatchEvent(new Event('change'));
});
function editPolygon(data) {
  drawnItems.clearLayers();
  const layer = L.geoJSON(JSON.parse(data.geojson));
  drawnItems.addLayer(layer);
  map.fitBounds(layer.getBounds());
  document.getElementById('formAction').value = 'update';
  document.getElementById('polygonId').value = data.id;
  document.getElementById('nama').value = data.nama;
  document.getElementById('status').value = data.status;
  let color = '#cccccc';
  if (data.status === 'BAHAYA') color = '#dc3545';
  else if (data.status === 'SIAGA') color = '#ffc107';
  else if (data.status === 'WASPADA') color = '#0d6efd';
  document.getElementById('color').value = color;
}
// Tambahkan batas administratif Cianjur (GeoJSON statis)
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