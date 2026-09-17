<?php
/**
 * Passage Repository Management Page for ARAL Reading Program (Grades 7-10).
 *
 * @package    mod_readingassessment
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT); // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA);
$passageid = optional_param('passageid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('readingassessment', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$readingassessment = $DB->get_record('readingassessment', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_url(new moodle_url('/mod/readingassessment/edit_passage.php', ['id' => $id]));
$PAGE->set_title('Passage Repository - ' . format_string($readingassessment->name));
$PAGE->set_heading('ARAL Grade 7–10 Reading Passages Repository');

$message = '';
$error = '';

if (data_submitted() && confirm_sesskey()) {
    if ($action === 'save') {
        $title = required_param('title', PARAM_TEXT);
        $grade_level = required_param('grade_level', PARAM_INT);
        $passage_type = required_param('passage_type', PARAM_TEXT);
        $content = required_param('content', PARAM_RAW);
        $word_count = count(preg_split('/\s+/', trim(strip_tags($content))));

        $passage = new stdClass();
        $passage->readingassessmentid = $readingassessment->id;
        $passage->title = $title;
        $passage->grade_level = $grade_level;
        $passage->passage_type = $passage_type;
        $passage->content = $content;
        $passage->word_count = $word_count;

        if ($passageid > 0) {
            $passage->id = $passageid;
            $DB->update_record('readingassessment_passages', $passage);
            $message = 'Passage updated successfully!';
        } else {
            $passage->timecreated = time();
            $DB->insert_record('readingassessment_passages', $passage);
            $message = 'New Grade ' . $grade_level . ' passage saved successfully!';
        }
        $action = '';
    } else if ($action === 'delete' && $passageid > 0) {
        $DB->delete_records('readingassessment_passages', ['id' => $passageid, 'readingassessmentid' => $readingassessment->id]);
        $message = 'Passage deleted successfully.';
        $action = '';
    }
}

echo $OUTPUT->header();
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>📖 Grade 7–10 ARAL Passage Repository</h2>
            <p class="text-muted">Manage screening, pre-assessment, and post-assessment reading texts for learners.</p>
        </div>
        <div>
            <a href="<?php echo new moodle_url('/mod/readingassessment/view.php', ['id' => $id]); ?>" class="btn btn-outline-secondary">&larr; Back to Activity</a>
            <?php if ($action !== 'edit' && $action !== 'add'): ?>
                <a href="<?php echo new moodle_url('/mod/readingassessment/edit_passage.php', ['id' => $id, 'action' => 'add']); ?>" class="btn btn-primary">+ Add New Passage</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo s($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <?php
        $editing_passage = null;
        if ($passageid > 0) {
            $editing_passage = $DB->get_record('readingassessment_passages', ['id' => $passageid, 'readingassessmentid' => $readingassessment->id]);
        }
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?php echo $editing_passage ? 'Edit Passage' : 'Add New ARAL Passage'; ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo $PAGE->url->out(false); ?>">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="passageid" value="<?php echo $passageid; ?>">

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label for="title">Passage Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required value="<?php echo s($editing_passage ? $editing_passage->title : ''); ?>" placeholder="e.g. Grade 7: The Philippine Eagle">
                        </div>
                        <div class="col-md-3 form-group mb-3">
                            <label for="grade_level">Target Grade Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="grade_level" name="grade_level" required>
                                <option value="7" <?php echo ($editing_passage && $editing_passage->grade_level == 7) ? 'selected' : ''; ?>>Grade 7</option>
                                <option value="8" <?php echo ($editing_passage && $editing_passage->grade_level == 8) ? 'selected' : ''; ?>>Grade 8</option>
                                <option value="9" <?php echo ($editing_passage && $editing_passage->grade_level == 9) ? 'selected' : ''; ?>>Grade 9</option>
                                <option value="10" <?php echo ($editing_passage && $editing_passage->grade_level == 10) ? 'selected' : ''; ?>>Grade 10</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group mb-3">
                            <label for="passage_type">Assessment Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="passage_type" name="passage_type" required>
                                <option value="screening" <?php echo ($editing_passage && $editing_passage->passage_type === 'screening') ? 'selected' : ''; ?>>Screening / Placement</option>
                                <option value="pre" <?php echo ($editing_passage && $editing_passage->passage_type === 'pre') ? 'selected' : ''; ?>>Pre-Assessment</option>
                                <option value="post" <?php echo ($editing_passage && $editing_passage->passage_type === 'post') ? 'selected' : ''; ?>>Post-Assessment</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="content">Passage Content <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="10" required placeholder="Enter passage text here..."><?php echo s($editing_passage ? $editing_passage->content : ''); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo new moodle_url('/mod/readingassessment/edit_passage.php', ['id' => $id]); ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Save Passage</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Existing Grade 7–10 Passages</h5>
        </div>
        <div class="card-body p-0">
            <?php
            $passages = $DB->get_records('readingassessment_passages', ['readingassessmentid' => $readingassessment->id], 'grade_level ASC, passage_type ASC');
            if (empty($passages)) {
                // Show default built-in passages info
                $defaults = readingassessment_get_default_passages();
                ?>
                <div class="p-4 text-center text-muted">
                    <p class="mb-2">No custom passages created yet. System is currently using default ARAL Grade 7–10 baseline passages:</p>
                    <div class="row text-start mt-3">
                        <?php foreach ($defaults as $g => $def): ?>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 bg-light">
                                    <span class="badge bg-info text-dark mb-2">Grade <?php echo $g; ?> Default</span>
                                    <h6><?php echo s($def['title']); ?></h6>
                                    <p class="small text-secondary mb-1"><?php echo s(substr($def['content'], 0, 120)); ?>...</p>
                                    <small class="text-muted">Word Count: <?php echo $def['word_count']; ?> words</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Grade</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Word Count</th>
                                <th>Preview</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($passages as $p): ?>
                                <tr>
                                    <td><span class="badge bg-primary">Grade <?php echo $p->grade_level; ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo strtoupper($p->passage_type); ?></span></td>
                                    <td><strong><?php echo s($p->title); ?></strong></td>
                                    <td><?php echo $p->word_count; ?> words</td>
                                    <td><small class="text-muted"><?php echo s(substr(strip_tags($p->content), 0, 80)); ?>...</small></td>
                                    <td>
                                        <a href="<?php echo new moodle_url('/mod/readingassessment/edit_passage.php', ['id' => $id, 'action' => 'edit', 'passageid' => $p->id]); ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <a href="<?php echo new moodle_url('/mod/readingassessment/edit_passage.php', ['id' => $id, 'action' => 'delete', 'passageid' => $p->id, 'sesskey' => sesskey()]); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this passage?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
