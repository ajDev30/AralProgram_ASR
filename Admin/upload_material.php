<?php
// 1. Database Connection (Adjust with your actual credentials)
require_once __DIR__ . "/../.env/config.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Handle File Upload
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf_file'])) {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $file = $_FILES['pdf_file'];

    // Basic Validation
    if (empty($title) || empty($category)) {
        $message = "All fields are required.";
    } else {
        $file_name = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Strictly accept only PDF
        if ($file_ext !== 'pdf') {
            $message = "Error: Only PDF files are allowed.";
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $message = "Error uploading file.";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Prevent overwriting by generating a unique name
            $unique_file_name = time() . '_' . $file_name;
            $file_path = $upload_dir . $unique_file_name;

            // Move file to destination folder
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                
                // Prepare SQL statement to prevent SQL injection
                $stmt = $conn->prepare("INSERT INTO materials (title, file_name, file_path, category) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $title, $unique_file_name, $file_path, $category);

                if ($stmt->execute()) {
                    $message = "Material uploaded successfully!";
                } else {
                    $message = "Database error: " . $conn->error;
                }
                $stmt->close();
            } else {
                $message = "Failed to move uploaded file.";
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Material</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f4f9; }
        .form-container { max-width: 400px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        
        /* Hiding the default ugly file input */
        .hidden-file-input { display: none; }
        
        /* Custom styled trigger button */
        .custom-upload-btn { display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-align: center; }
        .custom-upload-btn:hover { background-color: #0056b3; }
        .file-chosen-text { font-style: italic; color: #555; margin-left: 10px; }
        
        .submit-btn { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        .submit-btn:hover { background-color: #218838; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: #e2e3e5; color: #383d41; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Upload New Material</h2>
    
    <?php if(!empty($message)): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="upload_material.php" method="POST" enctype="multipart/form-data">
        
        <div class="form-group">
            <label for="title">Material Title</label>
            <input type="text" name="title" id="title" required placeholder="e.g., Remedial Reading Module 1">
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select name="category" id="category" required>
                <option value="">-- Select Category --</option>
                <option value="instructional">Instructional</option>
                <option value="struggling">Struggling</option>
                <option value="non-reader">Non-Reader</option>
                <option value="assessment">Assessment</option>
            </select>
        </div>

        <div class="form-group">
            <label>Select PDF File</label>
            <input type="file" name="pdf_file" id="pdf_file" class="hidden-file-input" accept=".pdf" required>
            
            <button type="button" class="custom-upload-btn" onclick="document.getElementById('pdf_file').click();">
                Browse PDF
            </button>
            <span id="file-name-display" class="file-chosen-text">No file chosen</span>
        </div>

        <button type="submit" class="submit-btn">Upload & Save to DB</button>
    </form>
</div>

<script>
    // Simple JS to update the UI text when a file is picked
    document.getElementById('pdf_file').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : "No file chosen";
        document.getElementById('file-name-display').textContent = fileName;
    });
</script>

</body>
</html>
