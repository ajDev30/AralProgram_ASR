<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (empty($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

$currentCategory = $_GET["category"] ?? "instructional";

// Fetch current user details
$userStmt = $pdo->prepare("
    SELECT name
    FROM users
    WHERE id = ?
");
$userStmt->execute([$_SESSION["user_id"]]);
$currentUser = $userStmt->fetch();

// Fetch materials filtering by category (excluding binary data here for performance)
$stmt = $pdo->prepare("
    SELECT id, title, file_name, category, uploaded_at
    FROM materials
    WHERE category = ?
    ORDER BY uploaded_at DESC
");
$stmt->execute([$currentCategory]);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Materials</title>
    <link rel="stylesheet" href="material.css">
</head>
<body>

<div class="container">

    <aside class="sidebar">
        <div class="profile-box">
            <?= htmlspecialchars($currentUser["name"] ?? "Admin") ?>
        </div>
        <nav>
            <a href="dashboard.php">Student</a>
            <a href="material.php" class="active">Materials</a>
            <a href="monitor.php">Monitor</a>
            <a href="../logout.php">Log out</a>
        </nav>
    </aside>

    <main class="content">

        <div class="top-actions">
            <button type="button" class="open-btn" onclick="openUploadModal()" style="padding: 10px 20px; cursor: pointer;">
                + Upload Material
            </button>
         </div>

        <div class="category-bar">
            <a href="?category=instructional" class="<?= $currentCategory === 'instructional' ? 'selected' : '' ?>">
                instructional
            </a>
            <a href="?category=struggling" class="<?= $currentCategory === 'struggling' ? 'selected' : '' ?>">
                struggling
            </a>
            <a href="?category=non-reader" class="<?= $currentCategory === 'non-reader' ? 'selected' : '' ?>">
                non-reader
            </a>
            <a href="?category=assessment" class="<?= $currentCategory === 'assessment' ? 'selected' : '' ?>">
                assessment
            </a>
        </div>

        <div class="materials-grid">
            <?php if (empty($materials)): ?>
                <div class="empty">
                    No materials uploaded.
                </div>
            <?php else: ?>
                <?php foreach ($materials as $material): ?>
                    <div class="card">
                        <div class="file-icon">📄</div>
                        <div class="title">
                            <?= htmlspecialchars($material["title"]) ?>
                        </div>
                        <div class="actions">
                            <a class="open-btn" href="view_material.php?id=<?= $material['id'] ?>" target="_blank">
                                Open
                            </a>
                            <a class="delete-btn" 
                               href="delete_material.php?id=<?= $material['id'] ?>&category=<?= urlencode($currentCategory) ?>" 
                               onclick="return confirm('Delete material?')">
                                Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

</div>

<div id="uploadModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index: 9999;">
    <div style="background:#fff; padding:20px; width:400px; border-radius:10px; box-shadow: 0px 4px 10px rgba(0,0,0,0.3);">
        <h3 style="margin-top: 0;">Upload PDF Material</h3>
        
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">

            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Document Title:</label>
                <input type="text" name="title" required style="width: 95%; padding: 8px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Select File (PDF only):</label>
                <input type="file" name="fileToUpload" accept=".pdf" required style="width: 100%;">
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="closeUploadModal()" style="padding: 8px 15px; margin-right: 5px; background: #ccc; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 8px 15px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUploadModal() {
    document.getElementById('uploadModal').style.display = 'flex';
}

function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
}
</script>

<script src="scripts/script.js"></script>
</body>
</html>
