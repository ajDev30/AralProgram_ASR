<?php
// 1. Database Connection (Adjust these settings to match your setup)
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = '';

// 2. Handle the File Upload Backend Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_material'])) {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $file = $_FILES['pdf_material'];

    // Define allowed categories based on your ENUM
    $allowed_categories = ['instructional', 'struggling', 'non-reader', 'assessment'];

    // Validation
    if (empty($title)) {
        $message = "❌ Title is required.";
    } elseif (!in_array($category, $allowed_categories)) {
        $message = "❌ Invalid category selected.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "❌ Error uploading file. Code: " . $file['error'];
    } else {
        // Validate file extension and MIME type
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension']);
        $mime_type = mime_content_type($file['tmp_name']);

        if ($extension !== 'pdf' || $mime_type !== 'application/pdf') {
            $message = "❌ Only PDF files are allowed.";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate a unique file name to prevent overwriting
            $unique_file_name = uniqid('mat_', true) . '.' . $extension;
            $destination = $upload_dir . $unique_file_name;

            // Move the file from temp storage to your uploads folder
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                
                // Insert into your database table
                $sql = "INSERT INTO materials (title, file_name, file_path, category) VALUES (:title, :file_name, :file_path, :category)";
                $stmt = $pdo->prepare($sql);

                try {
                    $stmt->execute([
                        ':title' => $title,
                        ':file_name' => $file['name'], // Original name
                        ':file_path' => $destination,  // Path to the saved file
                        ':category' => $category
                    ]);
                    $message = "✅ Material uploaded and saved successfully!";
                } catch (PDOException $e) {
                    // Clean up the uploaded file if database insert fails
                    if (file_exists($destination)) {
                        unlink($destination);
                    }
                    $message = "❌ Database error: " . $e->getMessage();
                }

            } else {
                $message = "❌ Failed to move uploaded file.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Material</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f4f9; }
        .form-container { max-width: 500px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn-submit { background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        .btn-submit:hover { background-color: #0056b3; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Upload Material</h2>

    <?php if (!empty($message)): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="upload_material.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Material Title:</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="">-- Select Category --</option>
                <option value="instructional">Instructional</option>
                <option value="struggling">Struggling</option>
                <option value="non-reader">Non-Reader</option>
                <option value="assessment">Assessment</option>
            </select>
        </div>

        <div class="form-group">
            <label for="pdf_material">Select PDF File:</label>
            <input type="file" id="pdf_material" name="pdf_material" accept=".pdf" required>
        </div>

        <button type="submit" class="btn-submit">Upload Material</button>
    </form>
</div>

</body>
</html>
