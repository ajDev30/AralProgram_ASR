<?php
session_start();
require_once __DIR__ . "../.env/config.php"; // Adjust path to config as needed

// 1. Authentication Guard
if (empty($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

// 2. Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Get and sanitize title
    $title = trim($_POST["title"] ?? '');
    
    // Default category fallback if not provided in form (adjust as needed)
    // If you add a category dropdown to your form, read it via $_POST['category']
    $category = $_POST["category"] ?? "instructional"; 

    // Validate textual inputs
    if (empty($title)) {
        die("Error: Title is required.");
    }

    // 3. File Upload Checks
    if (!isset($_FILES["pdf_file"]) || $_FILES["pdf_file"]["error"] !== UPLOAD_ERR_OK) {
        die("Error: File upload failed or no file was selected.");
    }

    $file = $_FILES["pdf_file"];
    $fileName = $file["name"];
    $fileTmpPath = $file["tmp_name"];

    // Validate MIME type strictly on the server side
    $fileMime = mime_content_type($fileTmpPath);
    if ($fileMime !== "application/pdf") {
        die("Error: Only PDF files are allowed.");
    }

    // 4. Prepare Destination Directory
    // Creates a directory named 'uploads' if it doesn't already exist
    $uploadDir = __DIR__ . "/../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Sanitize filename to prevent directory traversal attacks or naming conflicts
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $safeFileName = bin2hex(random_bytes(10)) . "." . $fileExtension; 
    $destinationPath = $uploadDir . $safeFileName;

    // Relative path to store in DB (useful for linking in your HTML cards)
    $relativeDbPath = "../uploads/" . $safeFileName;

    // 5. Move file and Save to Database
    if (move_uploaded_file($fileTmpPath, $destinationPath)) {
        try {
            // Prepare the insert query matches your exact DB layout
            $stmt = $pdo->prepare("
                INSERT INTO materials (title, file_name, file_path, category) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $title,
                $fileName,       // Original name (e.g., 'lesson1.pdf')
                $relativeDbPath, // Path to access it (e.g., '../uploads/abc123xyz.pdf')
                $category
            ]);

            // Redirect back to materials management page with success status
            header("Location: material.php?success=1");
            exit();

        } catch (PDOException $e) {
            // Clean up the uploaded file if DB entry fails
            if (file_exists($destinationPath)) {
                unlink($destinationPath);
            }
            die("Database Error: " . $e->getMessage());
        }
    } else {
        die("Error: Failed to move uploaded file to destination directory.");
    }
} else {
    // If someone accesses process_upload.php directly without posting
    header("Location: material.php");
    exit();
}
