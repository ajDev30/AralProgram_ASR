<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Creates a new student profile for ARAL intake.
 */
function readingassessment_create_profile($student_name, $student_id_number, $grade_level, $section = '', $school_year = '') {
    global $DB;

    $token = bin2hex(random_bytes(32)); // 64 hex characters
    $now = time();

    $profile = new stdClass();
    $profile->token = $token;
    $profile->student_name = trim($student_name);
    $profile->student_id_number = trim($student_id_number);
    $profile->grade_level = (int)$grade_level;
    $profile->section = trim($section);
    $profile->school_year = trim($school_year) ?: date('Y') . '-' . (date('Y') + 1);
    $profile->reading_level_category = 'Pending';
    $profile->status = 'pending';
    $profile->timecreated = $now;
    $profile->timemodified = $now;

    $profile->id = $DB->insert_record('readingassessment_profiles', $profile);
    return $profile;
}

/**
 * Gets a student profile by token.
 */
function readingassessment_get_profile_by_token($token) {
    global $DB;
    if (empty($token)) {
        return false;
    }
    return $DB->get_record('readingassessment_profiles', ['token' => $token]);
}

/**
 * Gets default passages for Grades 7-10 if none in database.
 */
function readingassessment_get_default_passages() {
    return [
        7 => [
            'title' => 'Grade 7: The Philippine Eagle',
            'content' => 'The Philippine Eagle is one of the largest and most powerful birds of prey in the world. Endemic to the forests of the Philippines, it plays a vital role as a top predator in maintaining ecological balance. Unfortunately, deforestation, illegal hunting, and habitat destruction have pushed this majestic bird to the brink of extinction. Conservation efforts are crucial to protect its remaining forest habitats and preserve biodiversity for future generations.',
            'grade_level' => 7,
            'word_count' => 72
        ],
        8 => [
            'title' => 'Grade 8: Renewable Energy and Climate Resilience',
            'content' => 'Climate change poses significant threats to coastal communities across Southeast Asia. Developing sustainable energy solutions such as solar, wind, and hydroelectric power reduces reliance on fossil fuels and mitigates greenhouse gas emissions. Clean energy technology promotes environmental sustainability, creates economic opportunities, and fosters resilient communities prepared for future climate challenges.',
            'grade_level' => 8,
            'word_count' => 54
        ],
        9 => [
            'title' => 'Grade 9: Digital Literacy in the Modern Era',
            'content' => 'In the rapidly evolving digital landscape, digital literacy has become an essential skill for students and educators alike. Beyond simple computer operation, digital literacy encompasses critical thinking, ethical online communication, information verification, and responsible media consumption. Mastering these competencies empowers learners to navigate complex information ecosystems safely and effectively.',
            'grade_level' => 9,
            'word_count' => 54
        ],
        10 => [
            'title' => 'Grade 10: Sustainable Development and Youth Leadership',
            'content' => 'Sustainable development seeks to meet the needs of the present without compromising the ability of future generations to meet their own needs. Young leaders across the globe are driving meaningful social innovation by advocating for environmental justice, equitable access to quality education, and inclusive economic policies. Youth engagement remains a essential catalyst for achieving global prosperity and environmental stewardship.',
            'grade_level' => 10,
            'word_count' => 61
        ]
    ];
}

/**
 * Gets passage for a given grade level and assessment instance.
 */
function readingassessment_get_passage_for_grade($grade_level, $passage_type = 'screening', $readingassessmentid = 0) {
    global $DB;
    $grade_level = (int)$grade_level;

    if ($readingassessmentid > 0) {
        $passage = $DB->get_record('readingassessment_passages', [
            'readingassessmentid' => $readingassessmentid,
            'grade_level' => $grade_level,
            'passage_type' => $passage_type
        ]);
        if ($passage) {
            return $passage;
        }
    }

    // Try finding any passage for this grade level in database
    $passage = $DB->get_record('readingassessment_passages', [
        'grade_level' => $grade_level,
        'passage_type' => $passage_type
    ]);
    if ($passage) {
        return $passage;
    }

    // Fallback to built-in default passage for Grade 7-10
    $defaults = readingassessment_get_default_passages();
    if (isset($defaults[$grade_level])) {
        $def = $defaults[$grade_level];
        $obj = new stdClass();
        $obj->id = 0;
        $obj->readingassessmentid = $readingassessmentid;
        $obj->title = $def['title'];
        $obj->grade_level = $grade_level;
        $obj->passage_type = $passage_type;
        $obj->content = $def['content'];
        $obj->word_count = $def['word_count'];
        return $obj;
    }

    // Fallback Grade 7 if unmapped
    $def = $defaults[7];
    $obj = new stdClass();
    $obj->id = 0;
    $obj->readingassessmentid = $readingassessmentid;
    $obj->title = $def['title'];
    $obj->grade_level = 7;
    $obj->passage_type = $passage_type;
    $obj->content = $def['content'];
    $obj->word_count = $def['word_count'];
    return $obj;
}

/**
 * Calculates 4-Category ARAL Reading Program classification based on DepEd ARAL framework.
 *
 * Categories:
 * 1. Non-Reader: Accuracy < 80% (or total inability to read/decode)
 * 2. Intervention (Frustration): Accuracy 80% - 89% OR Comprehension < 60%
 * 3. Consolidation (Instructional): Accuracy 90% - 96% AND Comprehension 60% - 79%
 * 4. Enhancement (Independent): Accuracy 97% - 100% AND Comprehension 80% - 100%
 */
function readingassessment_calculate_aral_classification($accuracy_rate, $comprehension_rate, $wpm = 0) {
    $accuracy = (float)$accuracy_rate;
    $comprehension = (float)$comprehension_rate;

    if ($accuracy < 80.0) {
        return [
            'category' => 'Non-Reader',
            'code' => 'non_reader',
            'badge' => 'badge-danger',
            'summary' => 'Student requires intensive foundational reading support, phonemic awareness, and basic decoding intervention.'
        ];
    } else if ($accuracy < 90.0 || $comprehension < 60.0) {
        return [
            'category' => 'Intervention (Frustration)',
            'code' => 'intervention',
            'badge' => 'badge-warning',
            'summary' => 'Student exhibits struggle with word recognition or text comprehension. Target structured remediation and guided practice.'
        ];
    } else if ($accuracy <= 96.0 || $comprehension < 80.0) {
        return [
            'category' => 'Consolidation (Instructional)',
            'code' => 'consolidation',
            'badge' => 'badge-info',
            'summary' => 'Student demonstrates adequate reading ability with guidance. Focus on vocabulary expansion and higher-order comprehension.'
        ];
    } else {
        return [
            'category' => 'Enhancement (Independent)',
            'code' => 'enhancement',
            'badge' => 'badge-success',
            'summary' => 'Student reads fluently with high accuracy and comprehension. Provide enriched reading materials and advanced critical thinking tasks.'
        ];
    }
}

/**
 * Generates structured pedagogical Action Plan for ARAL Reading Program.
 */
function readingassessment_generate_action_plan($category_code, $grade_level, $accuracy, $comprehension, $wpm) {
    $plan = [];
    $plan[] = "=== ARAL READING PROGRAM INDIVIDUALIZED ACTION PLAN ===";
    $plan[] = "Grade Level Target: Grade {$grade_level}";
    $plan[] = "Current Performance: Accuracy: {$accuracy}% | Comprehension: {$comprehension}% | WPM: {$wpm}";
    $plan[] = "";

    switch ($category_code) {
        case 'non_reader':
            $plan[] = "RECOMMENDED INTERVENTIONS:";
            $plan[] = "1. Immediate enrollment in ARAL Intensive Reading Intervention module.";
            $plan[] = "2. Daily 20-minute direct instruction in phoneme-grapheme correspondence and high-frequency sight words.";
            $plan[] = "3. Use audio-assisted paired reading and multimodal phonics software.";
            $plan[] = "4. Weekly progress monitoring of word recognition and letter sound mastery.";
            break;
        case 'intervention':
            $plan[] = "RECOMMENDED INTERVENTIONS:";
            $plan[] = "1. Assignment to ARAL Targeted Reading Remediation group.";
            $plan[] = "2. Guided oral reading with teacher modeling and immediate miscue correction.";
            $plan[] = "3. Explicit instruction on context clues, graphic organizers, and paragraph summarization.";
            $plan[] = "4. Bi-weekly fluency and comprehension tracking.";
            break;
        case 'consolidation':
            $plan[] = "RECOMMENDED STRATEGIES:";
            $plan[] = "1. Integration into regular classroom reading activities with scaffolded questions.";
            $plan[] = "2. Practice inferential and evaluative reading comprehension questions.";
            $plan[] = "3. Vocabulary journal keeping and root word morphological analysis.";
            $plan[] = "4. Monthly reading assessment check-in.";
            break;
        case 'enhancement':
            $plan[] = "RECOMMENDED STRATEGIES:";
            $plan[] = "1. Enrichment reading pathway with complex informational and literary texts.";
            $plan[] = "2. Engagement in peer tutoring and collaborative book clubs.";
            $plan[] = "3. Project-based literature analysis and creative synthesis tasks.";
            $plan[] = "4. Periodic progress verification.";
            break;
    }

    return implode("\n", $plan);
}

/**
 * Gets default pre/post questionnaire questions.
 */
function readingassessment_get_default_questionnaire($type = 'pre') {
    if ($type === 'pre') {
        return [
            [
                'text' => 'How comfortable do you feel reading English passages out loud?',
                'type' => 'likert',
                'options' => ['Very Uncomfortable', 'Uncomfortable', 'Neutral', 'Comfortable', 'Very Comfortable']
            ],
            [
                'text' => 'How often do you read English storybooks, articles, or news outside of school?',
                'type' => 'likert',
                'options' => ['Never', 'Rarely', 'Sometimes', 'Often', 'Daily']
            ],
            [
                'text' => 'When you meet a difficult word in a sentence, what do you usually do?',
                'type' => 'multiple_choice',
                'options' => [
                    'Skip it completely',
                    'Try to sound out the phonemes/letters',
                    'Ask a teacher or classmate for help',
                    'Look up the definition in a dictionary or context'
                ]
            ],
            [
                'text' => 'What area of reading do you think you need the most help with?',
                'type' => 'multiple_choice',
                'options' => [
                    'Pronouncing words correctly',
                    'Reading faster and smoother',
                    'Understanding the main idea of the story',
                    'Answering comprehension questions'
                ]
            ]
        ];
    } else {
        return [
            [
                'text' => 'After participating in the reading program, how much has your confidence in oral reading improved?',
                'type' => 'likert',
                'options' => ['Not at all', 'Slightly', 'Moderately', 'Significantly', 'Extremely']
            ],
            [
                'text' => 'How helpful was the real-time AI speech feedback during your reading practice?',
                'type' => 'likert',
                'options' => ['Not helpful', 'Slightly helpful', 'Neutral', 'Very helpful', 'Extremely helpful']
            ],
            [
                'text' => 'Which feature helped you the most in improving your reading skills?',
                'type' => 'multiple_choice',
                'options' => [
                    'Real-time pronunciation feedback and word highlighting',
                    'Coach mode audio playback and phoneme hints',
                    'Pre/Post questionnaire self-reflection',
                    'Teacher review and miscue correction'
                ]
            ],
            [
                'text' => 'Do you feel more prepared to read Grade-level English texts now?',
                'type' => 'multiple_choice',
                'options' => ['Yes, fully prepared', 'Somewhat prepared', 'Need a little more practice', 'Not yet prepared']
            ]
        ];
    }
}

/**
 * Saves or updates a reading assessment attempt with full ARAL Reading Program profiling,
 * 4-category classification matrix, miscue logging, and action plan generation.
 */
function readingassessment_save_aral_attempt($data) {
    global $DB;

    $raid           = (int)($data['readingassessmentid'] ?? 0);
    $userid         = (int)($data['userid'] ?? 0);
    $profileid      = (int)($data['profileid'] ?? 0);
    $assessment_phase = $data['assessment_phase'] ?? 'screening';
    $grade_level    = (int)($data['grade_level'] ?? 7);
    $passageid      = (int)($data['passageid'] ?? 0);
    $transcript     = $data['transcript'] ?? '';
    $accuracy       = (float)($data['accuracy_score'] ?? 0.0);
    $comprehension  = (float)($data['comprehension_score'] ?? 0.0);
    $reading_time   = (int)($data['reading_time'] ?? 0);
    $reading_speed  = (float)($data['reading_speed'] ?? 0.0);
    $miscues_json   = $data['miscues_json'] ?? '[]';
    $answers_json   = $data['answers_json'] ?? '[]';

    $final_grade = round(($accuracy * 0.6) + ($comprehension * 0.4), 2);
    $aral_info = readingassessment_calculate_aral_classification($accuracy, $comprehension, $reading_speed);
    $action_plan = readingassessment_generate_action_plan($aral_info['code'], $grade_level, $accuracy, $comprehension, $reading_speed);

    // Decode miscues array
    $miscues_array = json_decode($miscues_json, true) ?: [];
    $miscue_count = count($miscues_array);

    // Count attempt number
    $attempt_num = 1;
    if ($userid > 0) {
        $attempt_num = $DB->count_records('readingassessment_attempts', [
            'readingassessmentid' => $raid,
            'userid' => $userid
        ]) + 1;
    } else if ($profileid > 0) {
        $attempt_num = $DB->count_records('readingassessment_attempts', [
            'profileid' => $profileid
        ]) + 1;
    }

    $attempt = new stdClass();
    $attempt->readingassessmentid = $raid;
    $attempt->userid = $userid;
    $attempt->profileid = $profileid;
    $attempt->attempt = $attempt_num;
    $attempt->assessment_phase = $assessment_phase;
    $attempt->grade_level = $grade_level;
    $attempt->passageid = $passageid;
    $attempt->transcript = $transcript;
    $attempt->accuracy_score = $accuracy;
    $attempt->comprehension_score = $comprehension;
    $attempt->oral_reading_score = $accuracy;
    $attempt->final_grade = $final_grade;
    $attempt->reading_time = $reading_time;
    $attempt->reading_speed = $reading_speed;
    $attempt->words_per_minute = $reading_speed;
    $attempt->wpm = $reading_speed;
    $attempt->miscue_count = $miscue_count;
    $attempt->level_category = $aral_info['category'];
    $attempt->action_plan = $action_plan;
    $attempt->miscues_json = $miscues_json;
    $attempt->answers_json = $answers_json;
    $attempt->teacher_reviewed = 0;
    $attempt->timecompleted = time();

    $attempt_id = $DB->insert_record('readingassessment_attempts', $attempt);

    // Store individual miscues into readingassessment_miscues table
    foreach ($miscues_array as $idx => $m) {
        $misc = new stdClass();
        $misc->attemptid = $attempt_id;
        $misc->word = $m['word'] ?? ($m['target'] ?? '');
        $misc->expected_word = $m['expected'] ?? ($m['target'] ?? '');
        $misc->miscue_type = $m['type'] ?? ($m['miscue_type'] ?? 'mispronunciation');
        $misc->word_index = (int)($m['word_index'] ?? ($m['index'] ?? $idx));
        $misc->phoneme_score = (float)($m['phoneme_score'] ?? ($m['accuracy'] ?? 0.0));
        $misc->teacher_override = 0;
        $DB->insert_record('readingassessment_miscues', $misc);
    }

    // Update profile status and category if linked
    if ($profileid > 0) {
        $DB->execute(
            "UPDATE {readingassessment_profiles}
             SET reading_level_category = ?, status = 'completed', timemodified = ?
             WHERE id = ?",
            [$aral_info['category'], time(), $profileid]
        );
    }

    $attempt->id = $attempt_id;
    return $attempt;
}
