<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

if (empty($_SESSION["user_id"])) {
    die("Unauthorized access.");
}

$id = $_GET["id"] ?? null;
$category = $_GET["category"] ?? "instructional";

if (!$id) {
    die("Missing material ID.");
}

// Simply remove row record data without needing folder unlinking logic
$deleteStmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
$deleteStmt->execute([$id]);

header("Location: material.php?category=" . urlencode($category));
exit();
