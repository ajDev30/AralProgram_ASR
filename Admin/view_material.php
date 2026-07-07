<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

if (empty($_SESSION["user_id"])) {
    die("Unauthorized access.");
}

$id = $_GET["id"] ?? null;
if (!$id) {
    die("Missing material ID.");
}

// Pull block stream out from the blob storage space
$stmt = $pdo->prepare("SELECT file_name, file_data FROM materials WHERE id = ?");
$stmt->execute([$id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    die("Material not found.");
}

// Intercept browser context to read database string stream as a structural PDF element
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"" . $material["file_name"] . "\"");

echo $material["file_data"];
exit();
