<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

if (empty($_SESSION["user_id"])) {
    die("Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

$title = trim($_POST["title"] ?? "");
$category = $_POST["category"] ?? "";

if ($title === "") {
    die("Title is required.");
}

$allowed = ["instructional", "struggling", "non-reader", "assessment"];
if (!in_array($category, $allowed)) {
    die("Invalid category.");
}

if (!isset($_FILES["fileToUpload"]) || $_FILES["fileToUpload"]["error"] !== UPLOAD_ERR_OK) {
    die("Upload error or no file provided.");
}

$file = $_FILES["fileToUpload"];

$extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
if ($extension !== "pdf" || mime_content_type($file["tmp_name"]) !== "application/pdf") {
    die("Only valid PDF files are allowed.");
}

// Stream file content directly into memory variable
$binaryFileData = file_get_contents($file["tmp_name"]);
if ($binaryFileData === false) {
    die("Failed to read file data.");
}

// Bind payload directly to the newly altered LONGBLOB column
$stmt = $pdo->prepare("
    INSERT INTO materials 
    (title, file_name, file_data, category) 
    VALUES (?, ?, ?, ?)
");

$stmt->execute([
    $title,
    $file["name"],
    $binaryFileData,
    $category
]);

header("Location: material.php?category=" . urlencode($category));
exit();
