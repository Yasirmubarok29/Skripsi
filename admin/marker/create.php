<?php
require_once '../../db.php';
require_once '../auth.php';

$data = json_decode(file_get_contents("php://input"), true);
$name = $data['name'];
$desc = $data['description'];
$lat = $data['lat'];
$lng = $data['lng'];

$sql = "INSERT INTO markers (name, description, lat, lng) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdd", $name, $desc, $lat, $lng);

echo $stmt->execute() ? "Sukses" : "Gagal";
