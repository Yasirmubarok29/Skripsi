<?php
require_once '../../db.php';
require_once '../auth.php';

$data = json_decode(file_get_contents("php://input"), true);
$name = $data['name'];
$geojson = json_encode($data['geojson']);

$sql = "INSERT INTO polygons (name, geojson) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $name, $geojson);

echo $stmt->execute() ? "Sukses" : "Gagal";
