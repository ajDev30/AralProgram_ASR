<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

// 1. Session and Verification Guards
if (empty($_SESSION["user_id"])) {
    die("Unauthorized access context.");
}

// 2. Extract context parameters from the GET request URL payload
$question_id = intval($_GET["id"] ?? 0);
$material_id = intval($_GET["material_id"] ?? 0);
$category = $_GET["category"] ?? "instructional";

if ($question_id === 0 || $material_id === 0) {
    die("Error: Target execution constraints missing.");
}

try {
    // 3. Execute Deletion Query
    // Thanks to ON DELETE CASCADE on your foreign keys, MySQL will automatically
    // wipe out the matching choice/pair rows inside question_options for this question ID.
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);

    // 4. Smoothly redirect back to the quiz manager list view matching state parameters
    header("Location: quiz_manager.php?material_id=" . $material_id . "&category=" . urlencode($category));
    exit();

} catch (Exception $e) {
    die("Critical runtime tracking failure during deletion: " . $e->getMessage());
}
