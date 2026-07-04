<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

if (empty($_SESSION["user_id"])) {
    exit("Unauthorized");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request");
}

$title = trim($_POST["title"] ?? '');
$category = $_POST["category"] ?? 'instructional';

if ($title === '') {
    exit("Title required");
}

if (!isset($_FILES["pdf_file"])) {
    exit("No file");
}

$file = $_FILES["pdf_file"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    exit("Upload error");
}

if (mime_content_type($file["tmp_name"]) !== "application/pdf") {
    exit("Only PDF allowed");
}

$uploadDir = __DIR__ . "/../uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$fileName = bin2hex(random_bytes(10)) . ".pdf";
$path = $uploadDir . $fileName;

if (move_uploaded_file($file["tmp_name"], $path)) {

    $stmt = $pdo->prepare("
        INSERT INTO materials (title, file_name, file_path, category)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $title,
        $file["name"],
        "../uploads/" . $fileName,
        $category
    ]);

    echo "OK";
} else {
    echo "Failed";
}
