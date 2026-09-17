<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_readingassessment_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024081401) {
        $table = new xmldb_table('readingassessment');
        $field = new xmldb_field('maxattempts', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0', 'questions_json');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024081401, 'readingassessment');
    }

    if ($oldversion < 2024081402) {
        $table = new xmldb_table('readingassessment');
        $field = new xmldb_field('grademethod', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'maxattempts');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024081402, 'readingassessment');
    }

    if ($oldversion < 2024081403) {
        $table = new xmldb_table('readingassessment');
        
        $field_type = new xmldb_field('activitytype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'assessment', 'introformat');
        if (!$dbman->field_exists($table, $field_type)) {
            $dbman->add_field($table, $field_type);
        }

        $field_voice = new xmldb_field('tts_voice', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'alloy', 'activitytype');
        if (!$dbman->field_exists($table, $field_voice)) {
            $dbman->add_field($table, $field_voice);
        }

        upgrade_mod_savepoint(true, 2024081403, 'readingassessment');
    }

    if ($oldversion < 2024081404) {
        $table_ra = new xmldb_table('readingassessment');
        
        $field_mastery = new xmldb_field('mastery_repetitions', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '2', 'tts_voice');
        if (!$dbman->field_exists($table_ra, $field_mastery)) {
            $dbman->add_field($table_ra, $field_mastery);
        }

        $field_quiz = new xmldb_field('linked_quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'questions_json');
        if (!$dbman->field_exists($table_ra, $field_quiz)) {
            $dbman->add_field($table_ra, $field_quiz);
        }

        $table_att = new xmldb_table('readingassessment_attempts');

        $field_time = new xmldb_field('reading_time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'final_grade');
        if (!$dbman->field_exists($table_att, $field_time)) {
            $dbman->add_field($table_att, $field_time);
        }

        $field_speed = new xmldb_field('reading_speed', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0.00', 'reading_time');
        if (!$dbman->field_exists($table_att, $field_speed)) {
            $dbman->add_field($table_att, $field_speed);
        }

        upgrade_mod_savepoint(true, 2024081404, 'readingassessment');
    }

    if ($oldversion < 2024091700) {
        // 1. Add fields to 'readingassessment'
        $table_ra = new xmldb_table('readingassessment');
        
        $f_tg = new xmldb_field('target_grade', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '7', 'grade');
        if (!$dbman->field_exists($table_ra, $f_tg)) {
            $dbman->add_field($table_ra, $f_tg);
        }
        $f_aral = new xmldb_field('is_aral', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'target_grade');
        if (!$dbman->field_exists($table_ra, $f_aral)) {
            $dbman->add_field($table_ra, $f_aral);
        }
        $f_mode = new xmldb_field('assessment_mode', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pre_post', 'is_aral');
        if (!$dbman->field_exists($table_ra, $f_mode)) {
            $dbman->add_field($table_ra, $f_mode);
        }

        // 2. Add fields to 'readingassessment_attempts'
        $table_att = new xmldb_table('readingassessment_attempts');

        $fields_to_add = [
            new xmldb_field('profileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'userid'),
            new xmldb_field('assessment_phase', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'screening', 'attempt'),
            new xmldb_field('grade_level', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '7', 'assessment_phase'),
            new xmldb_field('passageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'grade_level'),
            new xmldb_field('oral_reading_score', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0.00', 'comprehension_score'),
            new xmldb_field('words_per_minute', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0.00', 'reading_speed'),
            new xmldb_field('wpm', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0.00', 'words_per_minute'),
            new xmldb_field('miscue_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'wpm'),
            new xmldb_field('level_category', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'miscue_count'),
            new xmldb_field('action_plan', XMLDB_TYPE_TEXT, null, null, null, null, null, 'level_category'),
            new xmldb_field('teacher_reviewed', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'answers_json'),
            new xmldb_field('teacher_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'teacher_reviewed'),
            new xmldb_field('teacher_notes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'teacher_id'),
            new xmldb_field('timereviewed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'teacher_notes')
        ];

        foreach ($fields_to_add as $f) {
            if (!$dbman->field_exists($table_att, $f)) {
                $dbman->add_field($table_att, $f);
            }
        }

        // 3. Create table 'readingassessment_profiles'
        $table_prof = new xmldb_table('readingassessment_profiles');
        $table_prof->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table_prof->add_field('token', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table_prof->add_field('student_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table_prof->add_field('student_id_number', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table_prof->add_field('grade_level', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '7');
        $table_prof->add_field('section', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table_prof->add_field('school_year', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table_prof->add_field('reading_level_category', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table_prof->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table_prof->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_prof->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_prof->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table_prof->add_index('token', XMLDB_INDEX_UNIQUE, ['token']);
        $table_prof->add_index('grade_level', XMLDB_INDEX_NOTUNIQUE, ['grade_level']);
        if (!$dbman->table_exists($table_prof)) {
            $dbman->create_table($table_prof);
        }

        // 4. Create table 'readingassessment_passages'
        $table_pass = new xmldb_table('readingassessment_passages');
        $table_pass->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table_pass->add_field('readingassessmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_pass->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table_pass->add_field('grade_level', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '7');
        $table_pass->add_field('passage_type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'screening');
        $table_pass->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table_pass->add_field('word_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_pass->add_field('sortorder', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table_pass->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_pass->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table_pass->add_key('readingassessmentid', XMLDB_KEY_FOREIGN, ['readingassessmentid'], 'readingassessment', ['id']);
        $table_pass->add_index('grade_type', XMLDB_INDEX_NOTUNIQUE, ['grade_level', 'passage_type']);
        if (!$dbman->table_exists($table_pass)) {
            $dbman->create_table($table_pass);
        }

        // 5. Create table 'readingassessment_questionnaires'
        $table_qnr = new xmldb_table('readingassessment_questionnaires');
        $table_qnr->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table_qnr->add_field('readingassessmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_qnr->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table_qnr->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pre');
        $table_qnr->add_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table_qnr->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_qnr->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table_qnr->add_key('readingassessmentid', XMLDB_KEY_FOREIGN, ['readingassessmentid'], 'readingassessment', ['id']);
        if (!$dbman->table_exists($table_qnr)) {
            $dbman->create_table($table_qnr);
        }

        // 6. Create table 'readingassessment_questions'
        $table_quest = new xmldb_table('readingassessment_questions');
        $table_quest->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table_quest->add_field('questionnaireid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_quest->add_field('passageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_quest->add_field('question_text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table_quest->add_field('question_type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'multiple_choice');
        $table_quest->add_field('options_json', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table_quest->add_field('correct_answer', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table_quest->add_field('sortorder', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table_quest->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table_quest->add_index('questionnaireid', XMLDB_INDEX_NOTUNIQUE, ['questionnaireid']);
        $table_quest->add_index('passageid', XMLDB_INDEX_NOTUNIQUE, ['passageid']);
        if (!$dbman->table_exists($table_quest)) {
            $dbman->create_table($table_quest);
        }

        // 7. Create table 'readingassessment_responses'
        $table_resp = new xmldb_table('readingassessment_responses');
        $table_resp->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table_resp->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_resp->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_resp->add_field('user_answer', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table_resp->add_field('is_correct', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table_resp->add_field('score', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0.00');
        $table_resp->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table_resp->add_key('attemptid', XMLDB_KEY_FOREIGN, ['attemptid'], 'readingassessment_attempts', ['id']);
        if (!$dbman->table_exists($table_resp)) {
            $dbman->create_table($table_resp);
        }

        // 8. Create table 'readingassessment_miscues'
        $table_misc = new xmldb_table('readingassessment_miscues');
        $table_misc->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table_misc->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_misc->add_field('word', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table_misc->add_field('expected_word', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table_misc->add_field('miscue_type', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $table_misc->add_field('word_index', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table_misc->add_field('phoneme_score', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0.00');
        $table_misc->add_field('teacher_override', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table_misc->add_field('teacher_comment', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table_misc->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table_misc->add_key('attemptid', XMLDB_KEY_FOREIGN, ['attemptid'], 'readingassessment_attempts', ['id']);
        if (!$dbman->table_exists($table_misc)) {
            $dbman->create_table($table_misc);
        }

        upgrade_mod_savepoint(true, 2024091700, 'readingassessment');
    }

    return true;
}
