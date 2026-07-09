<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

if (empty($_SESSION["user_id"])) {
    die("Unauthorized processing context access.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request framework method.");
}

$quiz_id = intval($_POST["quiz_id"] ?? 0);
$material_id = intval($_POST["material_id"] ?? 0);
$question_text = trim($_POST["question_text"] ?? "");
$question_type = $_POST["question_type"] ?? "";
$points = max(1, intval($_POST["points"] ?? 1));
$category = $_POST["category"] ?? "instructional";

if ($quiz_id === 0 || empty($question_text) || empty($question_type)) {
    die("Validation Error: Missing configuration parameters.");
}

try {
    $pdo->beginTransaction();

    // 1. Insert Base question record referencing its core Quiz Parent container
    $stmt = $pdo->prepare("
        INSERT INTO questions (quiz_id, question_text, question_type, points)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$quiz_id, $question_text, $question_type, $points]);
    $question_id = $pdo->lastInsertId();

    // 2. Polymorphically process options based on the chosen type configuration
    switch ($question_type) {
        case 'multiple_choice':
            $options = $_POST["mc_options"] ?? [];
            $correctIndex = intval($_POST["mc_correct"] ?? 0);
            foreach ($options as $index => $txt) {
                $isCorrect = ($index === $correctIndex) ? 1 : 0;
                $opt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $opt->execute([$question_id, trim($txt), $isCorrect]);
            }
            break;

        case 'true_false':
            $tfCorrect = $_POST["tf_correct"] ?? "True";
            $opt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, 'True', ?)");
            $opt->execute([$question_id, ($tfCorrect === 'True' ? 1 : 0)]);
            
            $opt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, 'False', ?)");
            $opt->execute([$question_id, ($tfCorrect === 'False' ? 1 : 0)]);
            break;

        case 'enumeration':
            $enumVal = trim($_POST["enum_answer"] ?? "");
            $opt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, 1)");
            $opt->execute([$question_id, $enumVal]);
            break;

        case 'spelling':
            $spellVal = trim($_POST["spelling_answer"] ?? "");
            $opt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, 1)");
            $opt->execute([$question_id, $spellVal]);
            break;

        case 'matching':
            $textsA = $_POST["match_text_A"] ?? [];
            $textsB = $_POST["match_text_B"] ?? [];

            foreach ($textsA as $i => $textA) {
                $textB = $textsB[$i] ?? "";
                
                // Initialize binary variables
                $imgNameA = null; $imgDataA = null;
                $imgNameB = null; $imgDataB = null;

                // Process image data if uploaded for Left Side (Column A)
                $fileKeyA = "match_img_A_" . $i;
                if (isset($_FILES[$fileKeyA]) && $_FILES[$fileKeyA]["error"] === UPLOAD_ERR_OK) {
                    $imgNameA = $_FILES[$fileKeyA]["name"];
                    $imgDataA = file_get_contents($_FILES[$fileKeyA]["tmp_name"]);
                }

                // Process image data if uploaded for Right Side (Column B)
                $fileKeyB = "match_img_B_" . $i;
                if (isset($_FILES[$fileKeyB]) && $_FILES[$fileKeyB]["error"] === UPLOAD_ERR_OK) {
                    $imgNameB = $_FILES[$fileKeyB]["name"];
                    $imgDataB = file_get_contents($_FILES[$fileKeyB]["tmp_name"]);
                }

                // Save if there is text or an image present in the row fields
                if (!empty($textA) || !empty($textB) || $imgDataA !== null || $imgDataB !== null) {
                    $opt = $pdo->prepare("
                        INSERT INTO question_options 
                        (question_id, option_text, option_image_name, option_image_data, match_text, match_image_name, match_image_data, is_correct)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $opt->execute([
                        $question_id, 
                        trim($textA), $imgNameA, $imgDataA,
                        trim($textB), $imgNameB, $imgDataB
                    ]);
                }
            }
            break;

        case 'essay':
            // Essays skip default choices and save directly as a base question row context layout
            break;
    }

    $pdo->commit();
    header("Location: quiz_manager.php?material_id=" . $material_id . "&category=" . urlencode($category));
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Transaction execution rollback safety fault triggered: " . $e->getMessage());
}
