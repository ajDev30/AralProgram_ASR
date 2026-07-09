<?php
session_start();
require_once __DIR__ . "/../.env/config.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);






if (empty($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

$material_id = intval($_GET["material_id"] ?? 0);
$category = $_GET["category"] ?? "instructional";

if ($material_id === 0) {
    die("Invalid material scope context allocation target.");
}

// Fetch material details
$matStmt = $pdo->prepare("SELECT title FROM materials WHERE id = ?");
$matStmt->execute([$material_id]);
$material = $matStmt->fetch();
if (!$material) { die("Material reference missing."); }

// Initialize/Verify Quiz Record Existence
$quizStmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE material_id = ?");
$quizStmt->execute([$material_id]);
$quiz = $quizStmt->fetch();

if (!$quiz) {
    // Automatically generate parent container entry if missing
    $insQuiz = $pdo->prepare("INSERT INTO quizzes (material_id, title) VALUES (?, ?)");
    $insQuiz->execute([$material_id, "Assessment quiz for " . $material["title"]]);
    
    $quizId = $pdo->lastInsertId();
    $quizTitle = "Assessment quiz for " . $material["title"];
} else {
    $quizId = $quiz["id"];
    $quizTitle = $quiz["title"];
}

// Fetch existing configured questions
$qStmt = $pdo->prepare("SELECT id, question_text, question_type, points FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$qStmt->execute([$quizId]);
$questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Builder Interface</title>
    <link rel="stylesheet" href="material.css">
    <style>
        .box { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #eef2f5;}
        .q-card { background: #f8f9fa; border-left: 5px solid #1e3d75; padding: 15px; margin-bottom: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;}
        .btn-green { background: #28a745; color:#fff; border:none; padding:10px 15px; border-radius:4px; cursor:pointer; font-weight:bold; }
        .form-row { margin-bottom: 15px; }
        label { display:block; font-weight:bold; margin-bottom:5px; }
    </style>
</head>
<body style="background: #f4f7f6; padding: 30px;">

<div style="max-width: 850px; margin: 0 auto;">
    <a href="material.php?category=<?= urlencode($category) ?>" style="text-decoration:none; color:#1e3d75; font-weight:bold;">← Return to Materials</a>
    
    <h2 style="color: #1e3d75; margin-top: 15px;"><?= htmlspecialchars($quizTitle) ?></h2>
    <p style="color: #555; font-size:14px; margin-top:-10px;">Configure comprehensive combined testing models for your students.</p>

    <div class="box">
        <h3 style="margin-top:0; border-bottom:2px solid #1e3d75; padding-bottom:8px;">Current Question List (<?= count($questions) ?> items)</h3>
        <?php if(empty($questions)): ?>
            <p style="color:#777; font-style:italic;">No question elements added to this module assessment yet.</p>
        <?php else: ?>
            <?php foreach($questions as $index => $q): ?>
                <div class="q-card">
                    <div>
                        <strong>#<?= $index + 1 ?> [<?= strtoupper(str_replace('_',' ',$q['question_type'])) ?>]</strong> - (<?= $q['points'] ?> pts)
                        <div style="margin-top:5px; color:#333;"><?= htmlspecialchars($q['question_text']) ?></div>
                    </div>
                    <a href="delete_question.php?id=<?= $q['id'] ?>&material_id=<?= $material_id ?>&category=<?= urlencode($category) ?>" onclick="return confirm('Remove question item?')" style="color:#dc3545; text-decoration:none; font-weight:bold; font-size:14px;">Remove</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="box">
        <h3 style="margin-top:0; color: #28a745;">+ Add New Question Form</h3>
        <form action="save_question.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
            <input type="hidden" name="material_id" value="<?= $material_id ?>">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">

            <div class="form-row">
                <label>Question Prompt / Text Statement:</label>
                <textarea name="question_text" required style="width:98%; height:50px; padding:8px; border-radius:4px; border:1px solid #ccc;"></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;" class="form-row">
                <div>
                    <label>Question Type Structuring Mode:</label>
                    <select name="question_type" id="qTypeSelect" onchange="transformForm()" style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="true_false">True / False</option>
                        <option value="enumeration">Enumeration</option>
                        <option value="spelling">Spelling</option>
                        <option value="essay">Essay (Manual Grading Logic)</option>
                        <option value="matching">Matching Pairs (Text or Picture Profiles)</option>
                    </select>
                </div>
                <div>
                    <label>Assigned Point Weight:</label>
                    <input type="number" name="points" value="1" min="1" required style="width:95%; padding:7px; border-radius:4px; border:1px solid #ccc;">
                </div>
            </div>

            <hr style="border:0; border-top:1px solid #ddd; margin:20px 0;">
            
            <div id="dynamicFormContainer"></div>

            <div style="margin-top:20px; text-align:right;">
                <button type="submit" class="btn-green">Save Item to Test Bundle</button>
            </div>
        </form>
    </div>
</div>

<script>
function transformForm() {

    const type = document.getElementById('qTypeSelect').value;
    const target = document.getElementById('dynamicFormContainer');
    target.innerHTML = '';

    if(type === 'multiple_choice') {
        target.innerHTML = `
            <label style="margin-bottom:10px;">Configure Alternatives (Check corresponding item for correct answer calibration):</label>
            ${[0,1,2,3].map(i => `
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                    <input type="radio" name="mc_correct" value="${i}" ${i===0?'checked':''}>
                    <input type="text" name="mc_options[]" placeholder="Choice Entry Option ${i+1}" required style="flex:1; padding:7px; border-radius:4px; border:1px solid #ccc;">
                </div>
            `).join('')}
        `;
    } else if(type === 'true_false') {
        target.innerHTML = `
            <label>Correct Truth Orientation Key:</label>
            <label style="font-weight:normal; margin-bottom:5px;"><input type="radio" name="tf_correct" value="True" checked> True Orientation Value</label>
            <label style="font-weight:normal;"><input type="radio" name="tf_correct" value="False"> False Orientation Value</label>
        `;
    } else if(type === 'enumeration') {
        target.innerHTML = `
            <label>Expected Target Value Terms:</label>
            <span style="font-size:12px; color:#666; display:block; margin-top:-5px; margin-bottom:5px;">Separate matching target terms using commas if varied terms are accepted.</span>
            <input type="text" name="enum_answer" required placeholder="e.g., Photosynthesis, Chlorophyll" style="width:97%; padding:8px; border-radius:4px; border:1px solid #ccc;">
        `;
    } else if(type === 'spelling') {
        target.innerHTML = `
            <label>Correct Evaluation Target Word Spelling:</label>
            <input type="text" name="spelling_answer" required placeholder="Exact spelling expected from students" style="width:97%; padding:8px; border-radius:4px; border:1px solid #ccc;">
        `;
    } else if(type === 'essay') {
        target.innerHTML = `
            <div style="background:#fff3cd; color:#856404; padding:12px; border-radius:4px; font-size:14px;">
                <strong>System Flag Notice:</strong> Essays skip auto-grader modules. Student submissions save automatically to review logs for direct teacher scoring via your DSS tracking view.
            </div>
        `;
    } else if(type === 'matching') {
        target.innerHTML = `
            <label>Configure Matching Pairs (Rows map Left to Correct Right Target Match):</label>
            <span style="font-size:12px; color:#666; display:block; margin-top:-5px; margin-bottom:10px;">Select Text inputs or use the file input selectors to load image assets directly.</span>
            <div id="matchRowsWrapper">
                ${[1,2,3].map(i => generateMatchingRowHtml(i)).join('')}
            </div>
            <button type="button" onclick="appendMatchingRow()" style="background:#6c757d; color:#white; padding:5px 10px; border:none; border-radius:3px; color:#fff; cursor:pointer; font-size:12px; margin-top:5px;">+ Add Additional Pair Row</button>
        `;
    }
}

function generateMatchingRowHtml(index) {
    return `
        <div style="background:#fcfcfc; border:1px solid #ddd; padding:12px; border-radius:6px; display:flex; gap:15px; margin-bottom:10px; align-items:center;">
            <div style="flex:1;">
                <span style="font-size:11px; font-weight:bold; display:block; color:#1e3d75; margin-bottom:4px;">COLUMN A (PAIR ${index} LEFT)</span>
                <input type="text" name="match_text_A[]" placeholder="Text Content (Optional if uploading image)" style="width:90%; padding:5px; margin-bottom:4px; border-radius:3px; border:1px solid #ccc;"><br>
                <input type="file" name="match_img_A_${index-1}" accept="image/*" style="font-size:11px;">
            </div>
            <div style="font-weight:bold; font-size:18px; color:#888;">➔</div>
            <div style="flex:1;">
                <span style="font-size:11px; font-weight:bold; display:block; color:#28a745; margin-bottom:4px;">COLUMN B (CORRECT RIGHT MATCH)</span>
                <input type="text" name="match_text_B[]" placeholder="Text Content (Optional if uploading image)" style="width:90%; padding:5px; margin-bottom:4px; border-radius:3px; border:1px solid #ccc;"><br>
                <input type="file" name="match_img_B_${index-1}" accept="image/*" style="font-size:11px;">
            </div>
        </div>
    `;
}

function appendMatchingRow() {
    const wrapper = document.getElementById('matchRowsWrapper');
    const nextIndex = wrapper.children.length + 1;
    const temporaryDiv = document.createElement('div');
    temporaryDiv.innerHTML = generateMatchingRowHtml(nextIndex);
    wrapper.appendChild(temporaryDiv.firstElementChild);
}

// Instantiate dynamic form layout states on load tracking
document.addEventListener("DOMContentLoaded", transformForm);
</script>
</body>
</html>
