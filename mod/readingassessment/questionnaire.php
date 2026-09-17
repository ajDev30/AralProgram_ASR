<?php
/**
 * ARAL Pre-Assessment & Post-Assessment Questionnaire Module.
 *
 * @package    mod_readingassessment
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$profileid = optional_param('profileid', 0, PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
$type = optional_param('type', 'pre', PARAM_ALPHA); // 'pre' or 'post'

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
    $context = context_system::instance();
    $PAGE->set_context($context);
}

$profile = null;
if ($profileid > 0) {
    $profile = $DB->get_record('readingassessment_profiles', ['id' => $profileid]);
}

$PAGE->set_url(new moodle_url('/mod/readingassessment/questionnaire.php', [
    'id' => $id,
    'profileid' => $profileid,
    'attemptid' => $attemptid,
    'type' => $type
]));

$title_label = ($type === 'post') ? 'Post-Assessment Self-Reflection & Progress Questionnaire' : 'Pre-Assessment Baseline Reading Questionnaire';
$PAGE->set_title('ARAL Program - ' . $title_label);
$PAGE->set_heading($title_label);

$questions = readingassessment_get_default_questionnaire($type);
$submitted = optional_param('submit_questionnaire', 0, PARAM_INT);
$message = '';

if ($submitted && data_submitted() && confirm_sesskey()) {
    $user_answers = [];
    foreach ($questions as $idx => $q) {
        $param_name = 'q_' . $idx;
        $ans = optional_param($param_name, '', PARAM_TEXT);
        $user_answers[$idx] = $ans;

        if ($attemptid > 0) {
            $resp = new stdClass();
            $resp->attemptid = $attemptid;
            $resp->questionid = $idx + 1;
            $resp->user_answer = $ans;
            $resp->is_correct = 1;
            $resp->score = 1.00;
            $DB->insert_record('readingassessment_responses', $resp);
        }
    }

    if ($type === 'pre') {
        // Redirect to actual oral reading assessment
        $redirect_url = new moodle_url('/mod/readingassessment/attempt.php', [
            'id' => $id,
            'profileid' => $profileid,
            'phase' => 'oral_reading'
        ]);
        redirect($redirect_url, 'Pre-assessment questionnaire completed! Proceeding to Oral Reading Assessment...', 2);
    } else {
        // Post assessment complete -> redirect to results summary
        $redirect_url = new moodle_url('/mod/readingassessment/report.php', [
            'id' => $id,
            'profileid' => $profileid
        ]);
        redirect($redirect_url, 'Post-assessment completed successfully! Viewing progress report...', 2);
    }
}

echo $OUTPUT->header();
?>

<style>
.q-card {
    max-width: 800px;
    margin: 2rem auto;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    padding: 2.5rem;
}
.q-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1.5rem;
    margin-bottom: 2rem;
}
.q-badge {
    background: <?php echo ($type === 'post') ? '#198754' : '#0d6efd'; ?>;
    color: #fff;
    padding: 0.35rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 1rem;
}
.question-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.question-title {
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1rem;
    font-size: 1.05rem;
}
.btn-submit-q {
    background: <?php echo ($type === 'post') ? '#198754' : '#0d6efd'; ?>;
    color: #fff;
    font-weight: 700;
    padding: 0.85rem 2.5rem;
    border-radius: 8px;
    border: none;
    width: 100%;
    font-size: 1.1rem;
}
.btn-submit-q:hover {
    opacity: 0.92;
    color: #fff;
}
</style>

<div class="container">
    <div class="q-card">
        <div class="q-header">
            <span class="q-badge"><?php echo strtoupper($type); ?>-ASSESSMENT QUESTIONNAIRE</span>
            <h2><?php echo s($title_label); ?></h2>
            <?php if ($profile): ?>
                <p class="text-muted mb-0">Learner: <strong><?php echo s($profile->student_name); ?></strong> (Grade <?php echo $profile->grade_level; ?>)</p>
            <?php else: ?>
                <p class="text-muted mb-0">Evaluate reading baseline performance, confidence, and strategy awareness.</p>
            <?php endif; ?>
        </div>

        <form method="post" action="<?php echo $PAGE->url->out(false); ?>">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            <input type="hidden" name="submit_questionnaire" value="1">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="profileid" value="<?php echo $profileid; ?>">
            <input type="hidden" name="attemptid" value="<?php echo $attemptid; ?>">
            <input type="hidden" name="type" value="<?php echo s($type); ?>">

            <?php foreach ($questions as $idx => $q): ?>
                <div class="question-item">
                    <div class="question-title">
                        Q<?php echo ($idx + 1); ?>. <?php echo s($q['text']); ?>
                    </div>

                    <?php if ($q['type'] === 'likert'): ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2">
                            <?php foreach ($q['options'] as $o_idx => $opt): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="q_<?php echo $idx; ?>" id="q_<?php echo $idx; ?>_<?php echo $o_idx; ?>" value="<?php echo s($opt); ?>" required>
                                    <label class="form-check-label" for="q_<?php echo $idx; ?>_<?php echo $o_idx; ?>"><?php echo s($opt); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="pt-2">
                            <?php foreach ($q['options'] as $o_idx => $opt): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="q_<?php echo $idx; ?>" id="q_<?php echo $idx; ?>_<?php echo $o_idx; ?>" value="<?php echo s($opt); ?>" required>
                                    <label class="form-check-label" for="q_<?php echo $idx; ?>_<?php echo $o_idx; ?>"><?php echo s($opt); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-submit-q">
                Submit Questionnaire & Continue &rarr;
            </button>
        </form>
    </div>
</div>

<?php
echo $OUTPUT->footer();
