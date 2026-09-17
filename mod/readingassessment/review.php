<?php
/**
 * Teacher Miscue Review & Score Override Page for ARAL Reading Program.
 *
 * @package    mod_readingassessment
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$attemptid = required_param('attemptid', PARAM_INT);
$id        = optional_param('id', 0, PARAM_INT);

$attempt = $DB->get_record('readingassessment_attempts', ['id' => $attemptid], '*', MUST_EXIST);
$raid    = $attempt->readingassessmentid;
$readingassessment = $DB->get_record('readingassessment', ['id' => $raid]);

if ($readingassessment && $readingassessment->course) {
    $course = $DB->get_record('course', ['id' => $readingassessment->course], '*', MUST_EXIST);
    $cm     = get_coursemodule_from_instance('readingassessment', $readingassessment->id, $course->id, false, MUST_EXIST);
    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
} else {
    $context = context_system::instance();
    require_login();
}

require_capability('mod/readingassessment:grade', $context);

$PAGE->set_url(new moodle_url('/mod/readingassessment/review.php', ['attemptid' => $attemptid]));
$PAGE->set_title('Teacher Review & Miscue Override - Attempt #' . $attempt->attempt);
$PAGE->set_heading('Teacher Review & Miscue Analysis');

$profile = null;
if ($attempt->profileid > 0) {
    $profile = $DB->get_record('readingassessment_profiles', ['id' => $attempt->profileid]);
}
$user = null;
if ($attempt->userid > 0) {
    $user = $DB->get_record('user', ['id' => $attempt->userid]);
}

$student_name = $profile ? $profile->student_name : ($user ? fullname($user) : 'Learner');
$lrn = $profile ? $profile->student_id_number : 'N/A';
$grade_level = $attempt->grade_level ?: ($profile ? $profile->grade_level : 7);

$message = '';

if (data_submitted() && confirm_sesskey()) {
    $teacher_notes = optional_param('teacher_notes', '', PARAM_TEXT);
    $overrides = optional_param_array('override', [], PARAM_INT);
    $comments  = optional_param_array('comment', [], PARAM_TEXT);

    // Fetch existing miscues
    $miscues = $DB->get_records('readingassessment_miscues', ['attemptid' => $attemptid]);
    $excused_count = 0;

    foreach ($miscues as $m) {
        $is_overridden = isset($overrides[$m->id]) ? 1 : 0;
        $t_comment     = $comments[$m->id] ?? '';

        if ($m->teacher_override != $is_overridden || $m->teacher_comment != $t_comment) {
            $DB->update_record('readingassessment_miscues', (object)[
                'id' => $m->id,
                'teacher_override' => $is_overridden,
                'teacher_comment'  => $t_comment
            ]);
        }
        if ($is_overridden) {
            $excused_count++;
        }
    }

    // Recalculate adjusted accuracy score if miscues were overridden
    $total_miscues = count($miscues);
    $effective_miscues = max(0, $total_miscues - $excused_count);

    // Update attempt teacher review state
    $attempt->teacher_reviewed = 1;
    $attempt->teacher_id       = $USER->id;
    $attempt->teacher_notes    = $teacher_notes;
    $attempt->timereviewed     = time();
    $attempt->miscue_count     = $effective_miscues;

    $DB->update_record('readingassessment_attempts', $attempt);
    $message = 'Teacher review and miscue overrides saved successfully!';
}

// Fetch all miscues for display
$miscues = $DB->get_records('readingassessment_miscues', ['attemptid' => $attemptid], 'word_index ASC');

echo $OUTPUT->header();
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>🔍 Teacher Review & Miscue Analysis</h2>
            <p class="text-muted">Review student speech performance, override false-positive miscues, and verify ARAL classification.</p>
        </div>
        <div>
            <a href="<?php echo new moodle_url('/mod/readingassessment/report.php', ['id' => $cm ? $cm->id : 0]); ?>" class="btn btn-outline-secondary">&larr; Back to Dashboard</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo s($message); ?>
        </div>
    <?php endif; ?>

    <!-- Student & Attempt Overview Card -->
    <div class="card shadow-sm mb-4 border-0" style="border-radius: 12px; background: linear-gradient(135deg, #f8fafc, #edf2f7);">
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-bold">Student Name</small>
                    <h5 class="mb-0 text-primary"><?php echo s($student_name); ?></h5>
                    <small class="text-secondary">LRN: <?php echo s($lrn); ?> | Grade <?php echo $grade_level; ?></small>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-bold">ARAL Category</small>
                    <div>
                        <span class="badge bg-primary fs-6"><?php echo s($attempt->level_category ?: 'Pending'); ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-bold">Oral Accuracy & Speed</small>
                    <h5 class="mb-0 text-success"><?php echo $attempt->accuracy_score; ?>% <small class="text-muted">(<?php echo $attempt->reading_speed; ?> WPM)</small></h5>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase fw-bold">Miscues Detected</small>
                    <h5 class="mb-0 text-warning"><?php echo $attempt->miscue_count; ?> miscues</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Granular Miscues Override Form -->
    <form method="post" action="<?php echo $PAGE->url->out(false); ?>">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
        <input type="hidden" name="attemptid" value="<?php echo $attemptid; ?>">

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Granular Miscues & Pronunciation Breakdown</h5>
                <span class="badge bg-secondary"><?php echo count($miscues); ?> Items Logged</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($miscues)): ?>
                    <div class="p-4 text-center text-muted">
                        No miscues recorded for this attempt. Student read all words with high accuracy!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Expected Word</th>
                                    <th>Recognized Word</th>
                                    <th>Miscue Type</th>
                                    <th>Phoneme Score</th>
                                    <th>Excuse / Override</th>
                                    <th>Teacher Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($miscues as $idx => $m): ?>
                                    <tr>
                                        <td><?php echo ($m->word_index + 1); ?></td>
                                        <td><strong class="text-dark"><?php echo s($m->expected_word ?: $m->word); ?></strong></td>
                                        <td><span class="text-danger fw-bold"><?php echo s($m->word); ?></span></td>
                                        <td>
                                            <span class="badge bg-warning text-dark"><?php echo strtoupper(s($m->miscue_type)); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark"><?php echo $m->phoneme_score; ?>%</span>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="override[<?php echo $m->id; ?>]" value="1" id="ov_<?php echo $m->id; ?>" <?php echo $m->teacher_override ? 'checked' : ''; ?>>
                                                <label class="form-check-label small text-muted" for="ov_<?php echo $m->id; ?>">
                                                    <?php echo $m->teacher_override ? 'Excused' : 'Counted'; ?>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="comment[<?php echo $m->id; ?>]" value="<?php echo s($m->teacher_comment); ?>" placeholder="e.g. Accent variation / regional dialect">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Teacher Action Plan & Notes Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Pedagogical Recommendations & Teacher Notes</h5>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label class="fw-bold text-secondary mb-2">Automated ARAL Individualized Action Plan:</label>
                    <textarea class="form-control bg-light" rows="6" readonly><?php echo s($attempt->action_plan); ?></textarea>
                </div>
                <div class="form-group mb-3">
                    <label for="teacher_notes" class="fw-bold text-primary mb-2">Teacher Manual Notes / Assessment Feedback:</label>
                    <textarea class="form-control" id="teacher_notes" name="teacher_notes" rows="4" placeholder="Enter custom feedback, observations, or remediation plan..."><?php echo s($attempt->teacher_notes); ?></textarea>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        Save Review & Update Classification
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
echo $OUTPUT->footer();
