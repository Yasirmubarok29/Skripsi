<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

require 'db.php';

$data = json_decode(file_get_contents("php://input"));

$name = $conn->real_escape_string($data->name);
$lat  = $data->latitude;
$lng  = $data->longitude;

$sql = "INSERT INTO markers (name, latitude, longitude) VALUES ('$name', $lat, $lng)";
if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Marker disimpan"]);
} else {
    echo json_encode(["success" => false, "message" => "Gagal menyimpan"]);
}
?>
