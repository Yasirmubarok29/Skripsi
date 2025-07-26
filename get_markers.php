<?php
require 'db.php';

$sql = "SELECT * FROM markers";
$result = $conn->query($sql);

$markers = [];
while ($row = $result->fetch_assoc()) {
    $markers[] = $row;
}

echo json_encode($markers);
?>
