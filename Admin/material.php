<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (empty($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

/*
TABLE:

CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    category ENUM(
        'instructional',
        'struggling',
        'non-reader',
        'assessment'
    ) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
*/

$currentCategory = $_GET["category"] ?? "instructional";

$userStmt = $pdo->prepare("
    SELECT name
    FROM users
    WHERE id = ?
");

$userStmt->execute([$_SESSION["user_id"]]);

$currentUser = $userStmt->fetch();

$stmt = $pdo->prepare("
    SELECT *
    FROM materials
    WHERE category = ?
    ORDER BY uploaded_at DESC
");

$stmt->execute([$currentCategory]);

$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Materials</title>
    <link rel="stylesheet" href="material.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="profile-box">
            <?= htmlspecialchars($currentUser["name"]) ?>
        </div>

        <nav>

            <a href="dashboard.php">
                Student
            </a>

            <a href="material.php" class="active">
                Materials
            </a>

            <a href="monitor.php">
                Monitor
            </a>

            <a href="../logout.php">
                Log out
            </a>

        </nav>

    </aside>

    <!-- CONTENT -->

    <main class="content">

        <div class="top-actions">

            <a class="btn-add" href="../Admin/upload_material.php" onclick="openUploadModal(event)">
              ADD
            </a>

            <a
                class="btn-remove"
                href="delete_mode.php"
            >
                Remove
            </a>

        </div>

        <div class="category-bar">

            <a
                href="?category=instructional"
                class="<?= $currentCategory === "instructional"
                    ? "selected"
                    : "" ?>"
            >
                instructional
            </a>

            <a
                href="?category=struggling"
                class="<?= $currentCategory === "struggling"
                    ? "selected"
                    : "" ?>"
            >
                struggling
            </a>

            <a
                href="?category=non-reader"
                class="<?= $currentCategory === "non-reader"
                    ? "selected"
                    : "" ?>"
            >
                non-reader
            </a>

            <a
                href="?category=assessment"
                class="<?= $currentCategory === "assessment"
                    ? "selected"
                    : "" ?>"
            >
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

                        <div class="file-icon">
                            📄
                        </div>

                        <div class="title">
                            <?= htmlspecialchars($material["title"]) ?>
                        </div>

                        <div class="actions">

                            <a
                                class="open-btn"
                                href="<?= htmlspecialchars(
                                    $material["file_path"],
                                ) ?>"
                                target="_blank"
                            >
                                Open
                            </a>

                            <a
                                class="delete-btn"
                                href="delete_material.php?id=<?= $material[
                                    "id"
                                ] ?>"
                                onclick="return confirm('Delete material?')"
                            >
                                Delete
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </main>

</div>
<!-- UPLOAD MODAL -->
<div id="uploadModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">

    <div style="background:#fff; padding:20px; width:400px; border-radius:10px;">

        <h3>Upload PDF Material</h3>

        <input type="text" id="title" placeholder="Title" style="width:100%; margin-bottom:10px;">

        <select id="category" style="width:100%; margin-bottom:10px;">
            <option value="instructional">instructional</option>
            <option value="struggling">struggling</option>
            <option value="non-reader">non-reader</option>
            <option value="assessment">assessment</option>
        </select>

        <input type="file" id="pdf" accept="application/pdf">

        <br><br>

        <button onclick="uploadFile()">Upload</button>
        <button onclick="closeUploadModal()">Cancel</button>

    </div>

</div>

</body>
</html>
