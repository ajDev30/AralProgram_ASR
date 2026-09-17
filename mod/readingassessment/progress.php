<?php
/**
 * ARAL Pre-Assessment vs Post-Assessment Progress Dashboard.
 *
 * @package    mod_readingassessment
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$id       = optional_param('id', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($id > 0) {
    $cm                 = get_coursemodule_from_id('readingassessment', $id, 0, false, MUST_EXIST);
    $course             = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $readingassessment = $DB->get_record('readingassessment', ['id' => $cm->instance], '*', MUST_EXIST);
    $context            = context_module::instance($cm->id);
} else if ($courseid > 0) {
    $course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($course->id);
} else {
    $context = context_system::instance();
}

require_login($course ?? null);
require_capability('mod/readingassessment:grade', $context);

$PAGE->set_url(new moodle_url('/mod/readingassessment/progress.php', ['id' => $id, 'courseid' => $courseid]));
$PAGE->set_title('ARAL Reading Program - Pre vs Post Progress Dashboard');
$PAGE->set_heading('Pre-Assessment vs Post-Assessment Progress Analytics');

// Query profiles and compare baseline attempt vs final post attempt
$sql = "SELECT p.*,
               att_pre.accuracy_score as pre_accuracy,
               att_pre.comprehension_score as pre_comprehension,
               att_pre.reading_speed as pre_wpm,
               att_pre.level_category as pre_category,
               att_post.accuracy_score as post_accuracy,
               att_post.comprehension_score as post_comprehension,
               att_post.reading_speed as post_wpm,
               att_post.level_category as post_category
        FROM {readingassessment_profiles} p
        LEFT JOIN {readingassessment_attempts} att_pre ON (att_pre.profileid = p.id AND att_pre.assessment_phase = 'pre')
        LEFT JOIN {readingassessment_attempts} att_post ON (att_post.profileid = p.id AND att_post.assessment_phase = 'post')
        ORDER BY p.grade_level ASC, p.student_name ASC";

$records = $DB->get_records_sql($sql);

$total_learners = count($records);
$completed_both = 0;
$total_acc_gain = 0;
$total_wpm_gain = 0;

foreach ($records as $r) {
    if (!empty($r->pre_accuracy) && !empty($r->post_accuracy)) {
        $completed_both++;
        $total_acc_gain += ($r->post_accuracy - $r->pre_accuracy);
        $total_wpm_gain += ($r->post_wpm - $r->pre_wpm);
    }
}

$avg_acc_growth = $completed_both > 0 ? round($total_acc_gain / $completed_both, 1) : 0;
$avg_wpm_growth = $completed_both > 0 ? round($total_wpm_gain / $completed_both, 1) : 0;

echo $OUTPUT->header();
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>📈 ARAL Pre vs. Post Progress Monitoring</h2>
            <p class="text-muted">Track learner growth, accuracy gain, WPM improvement, and reading category transitions (Grades 7–10).</p>
        </div>
        <div>
            <a href="<?php echo new moodle_url('/mod/readingassessment/report.php', ['id' => $id]); ?>" class="btn btn-outline-secondary">&larr; Back to Dashboard</a>
        </div>
    </div>

    <!-- Class Progress KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center border-0 bg-primary text-white p-3" style="border-radius: 12px;">
                <small class="text-white-50">Total Enrolled Learners</small>
                <h3 class="mb-0"><?php echo $total_learners; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center border-0 bg-success text-white p-3" style="border-radius: 12px;">
                <small class="text-white-50">Completed Both Pre & Post</small>
                <h3 class="mb-0"><?php echo $completed_both; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center border-0 bg-info text-white p-3" style="border-radius: 12px;">
                <small class="text-white-50">Avg. Accuracy Growth</small>
                <h3 class="mb-0"><?php echo ($avg_acc_growth >= 0 ? '+' : '') . $avg_acc_growth; ?>%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center border-0 bg-warning text-dark p-3" style="border-radius: 12px;">
                <small class="text-dark-50">Avg. Speed Growth</small>
                <h3 class="mb-0"><?php echo ($avg_wpm_growth >= 0 ? '+' : '') . $avg_wpm_growth; ?> WPM</h3>
            </div>
        </div>
    </div>

    <!-- Learner Progress Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Learner Baseline vs. Final Progress Matrix</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($records)): ?>
                <div class="p-4 text-center text-muted">
                    No learner profiles registered yet. Use <a href="<?php echo new moodle_url('/mod/readingassessment/profile.php', ['id' => $id]); ?>">Intake Page</a> to enroll students.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Learner Name</th>
                                <th>LRN / ID</th>
                                <th>Grade</th>
                                <th>Pre Accuracy</th>
                                <th>Post Accuracy</th>
                                <th>Accuracy Gain</th>
                                <th>Pre Category</th>
                                <th>Post Category</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                                <?php
                                $acc_gain = ($r->post_accuracy !== null && $r->pre_accuracy !== null) ? ($r->post_accuracy - $r->pre_accuracy) : null;
                                ?>
                                <tr>
                                    <td><strong><?php echo s($r->student_name); ?></strong></td>
                                    <td><?php echo s($r->student_id_number ?: 'N/A'); ?></td>
                                    <td><span class="badge bg-secondary">Grade <?php echo $r->grade_level; ?></span></td>
                                    <td><?php echo $r->pre_accuracy !== null ? $r->pre_accuracy . '%' : '<span class="text-muted">Pending</span>'; ?></td>
                                    <td><?php echo $r->post_accuracy !== null ? $r->post_accuracy . '%' : '<span class="text-muted">Pending</span>'; ?></td>
                                    <td>
                                        <?php if ($acc_gain !== null): ?>
                                            <span class="fw-bold <?php echo $acc_gain >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo ($acc_gain >= 0 ? '+' : '') . round($acc_gain, 1); ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-outline-secondary"><?php echo s($r->pre_category ?: 'N/A'); ?></span></td>
                                    <td><span class="badge bg-primary"><?php echo s($r->post_category ?: ($r->reading_level_category ?: 'Pending')); ?></span></td>
                                    <td>
                                        <?php if ($r->post_accuracy !== null): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif ($r->pre_accuracy !== null): ?>
                                            <span class="badge bg-warning text-dark">Pre Done (Awaiting Post)</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">Pending Pre</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
