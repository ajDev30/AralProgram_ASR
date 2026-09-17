<?php
/**
 * Student Profiling and Intake Page for ARAL Reading Program.
 *
 * @package    mod_readingassessment
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID (optional)
$cm = null;
$course = null;
$readingassessment = null;

if ($id > 0) {
    $cm = get_coursemodule_from_id('readingassessment', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $readingassessment = $DB->get_record('readingassessment', ['id' => $cm->instance], '*', MUST_EXIST);
    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    $PAGE->set_cm($cm);
    $PAGE->set_context($context);
} else {
    // Public / Standalone intake mode
    $context = context_system::instance();
    $PAGE->set_context($context);
}

$PAGE->set_url(new moodle_url('/mod/readingassessment/profile.php', ['id' => $id]));
$PAGE->set_title(get_string('pluginname', 'mod_readingassessment') . ' - ARAL Learner Intake & Profiling');
$PAGE->set_heading('ARAL Reading Program - Learner Intake & Profiling');

$errors = [];
$submitted = optional_param('submit_profile', 0, PARAM_INT);

if ($submitted && data_submitted() && confirm_sesskey()) {
    $student_name = required_param('student_name', PARAM_TEXT);
    $student_id_number = optional_param('student_id_number', '', PARAM_TEXT);
    $grade_level = required_param('grade_level', PARAM_INT);
    $section = optional_param('section', '', PARAM_TEXT);
    $school_year = optional_param('school_year', '', PARAM_TEXT);

    if (empty($student_name)) {
        $errors[] = 'Please enter student full name.';
    }
    if ($grade_level < 7 || $grade_level > 10) {
        $errors[] = 'Grade level must be between Grade 7 and Grade 10 for ARAL Program.';
    }

    if (empty($errors)) {
        $profile = readingassessment_create_profile($student_name, $student_id_number, $grade_level, $section, $school_year);
        
        // Redirect to assessment attempt with token/profileid
        $redirect_url = new moodle_url('/mod/readingassessment/attempt.php', [
            'id' => $id,
            'profileid' => $profile->id,
            'token' => $profile->token,
            'grade' => $grade_level
        ]);
        redirect($redirect_url, 'Learner profile registered successfully! Proceeding to reading assessment...', 2);
    }
}

echo $OUTPUT->header();
?>

<style>
.aral-card {
    max-width: 750px;
    margin: 2rem auto;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    background: #fff;
    padding: 2.5rem;
    font-family: inherit;
}
.aral-header {
    text-align: center;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1.5rem;
    margin-bottom: 2rem;
}
.aral-header h2 {
    color: #1d2125;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.aral-header p {
    color: #6c757d;
    margin-bottom: 0;
}
.aral-badge {
    display: inline-block;
    background-color: #0d6efd;
    color: #fff;
    padding: 0.35rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 1rem;
}
.form-group label {
    font-weight: 600;
    color: #495057;
}
.btn-aral-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-size: 1.05rem;
    width: 100%;
}
.btn-aral-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}
</style>

<div class="container">
    <div class="aral-card">
        <div class="aral-header">
            <span class="aral-badge">ARAL Reading Program (Grades 7–10)</span>
            <h2>Learner Registration & Screening Intake</h2>
            <p>Please enter student details to begin computerized speech screening and reading assessment.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo s($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $PAGE->url->out(false); ?>">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            <input type="hidden" name="submit_profile" value="1">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="form-group mb-3">
                <label for="student_name">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="student_name" name="student_name" required placeholder="e.g. Juan De La Cruz" value="<?php echo s(optional_param('student_name', '', PARAM_TEXT)); ?>">
            </div>

            <div class="row">
                <div class="col-md-6 form-group mb-3">
                    <label for="student_id_number">Learner Reference Number (LRN) / Student ID</label>
                    <input type="text" class="form-control" id="student_id_number" name="student_id_number" placeholder="12-digit LRN or Student ID" value="<?php echo s(optional_param('student_id_number', '', PARAM_TEXT)); ?>">
                </div>
                <div class="col-md-6 form-group mb-3">
                    <label for="grade_level">Grade Level <span class="text-danger">*</span></label>
                    <select class="form-control" id="grade_level" name="grade_level" required>
                        <option value="7" <?php echo (optional_param('grade_level', 7, PARAM_INT) == 7) ? 'selected' : ''; ?>>Grade 7</option>
                        <option value="8" <?php echo (optional_param('grade_level', 7, PARAM_INT) == 8) ? 'selected' : ''; ?>>Grade 8</option>
                        <option value="9" <?php echo (optional_param('grade_level', 7, PARAM_INT) == 9) ? 'selected' : ''; ?>>Grade 9</option>
                        <option value="10" <?php echo (optional_param('grade_level', 7, PARAM_INT) == 10) ? 'selected' : ''; ?>>Grade 10</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group mb-4">
                    <label for="section">Section / Class</label>
                    <input type="text" class="form-control" id="section" name="section" placeholder="e.g. Section Sampaguita" value="<?php echo s(optional_param('section', '', PARAM_TEXT)); ?>">
                </div>
                <div class="col-md-6 form-group mb-4">
                    <label for="school_year">School Year</label>
                    <input type="text" class="form-control" id="school_year" name="school_year" placeholder="e.g. 2024-2025" value="<?php echo s(optional_param('school_year', '2024-2025', PARAM_TEXT)); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-aral-primary">
                Proceed to Reading Assessment &rarr;
            </button>
        </form>
    </div>
</div>

<?php
echo $OUTPUT->footer();
