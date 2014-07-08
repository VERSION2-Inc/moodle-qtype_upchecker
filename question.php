<?php
defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/question/type/upchecker/locallib.php';
require_once $CFG->dirroot . '/question/type/upchecker/class/remote_grading.php';
use upchecker\upchecker as uc;

class qtype_upchecker_question extends question_graded_automatically {
    /**
     *
     * @var string
     */
    private $dropboxconsumerkey = 'h2tpzl0f4z8l98z';
    /**
     *
     * @var string
     */
    private $dropboxconsumersecret = 'zfn1ckxb1g4cs8j';
    /**
     *
     * @var \stdClass
     */
    private $replaceparams;
    /**
     *
     * @var \stdClass
     */
    public $upcheckerattempt;
    /**
     *
     * @var int
     */
    private $questionattemptid;
    /**
     *
     * @var \stdClass
     */
    private $questionattempt;
    /**
     *
     * @var stdClass
     */
    public $resyncfileuser;

    /**
     *
     * @param question_attempt_step $step
     * @param int $variant
     */
    public function start_attempt(question_attempt_step $step, $variant) {
        global $DB;

        $upcheckerattempt = (object)array(
                'question' => $this->id
        );
        $id = $DB->insert_record('question_upchecker_attempts', $upcheckerattempt);
        $step->set_qt_var('_upcheckerattempt', $id);
    }

    public function apply_attempt_state(question_attempt_step $step) {
        global $DB;

        $id = $step->get_qt_var('_upcheckerattempt');
        $this->upcheckerattempt = $DB->get_record('question_upchecker_attempts', array('id' => $id));

        if (!$this->upcheckerattempt->questionattempt) {
            $this->questionattemptid = $DB->get_field('question_attempt_steps', 'questionattemptid',
                    array('id' => $step->get_id()), MUST_EXIST);
            $this->upcheckerattempt->questionattempt = $this->questionattemptid;
            $this->save_upchecker_attempt();
        } else {
            $this->questionattemptid = $this->upcheckerattempt->questionattempt;
        }

        parent::apply_attempt_state($step);
    }

    /**
     *
     * @return array
     */
    public function get_expected_data() {
        return array('answer' => question_attempt::PARAM_FILES);
    }

    /**
     *
     * @param array $response
     * @return string
     */
    public function summarise_response(array $response) {
        if ($file = $this->get_uploaded_file()) {
            return $file->get_filename();
        }
        return uc::str('nofile');
    }

    /**
     *
     * @param array $response
     * @return array
     */
    public function grade_response(array $response) {
        $fraction = 0;

        $file = $this->get_uploaded_file();

        if ($file = $this->get_uploaded_file()) {
            $this->store_file($file);

            $remotegrading = new remote_grading();

            $remotegrading->post_file($this->checkurl, $this->fileparam, $file, $this->restparams);

            if ($this->gradetype != 'manual') {
                if ($this->gradetype == 'xml') {
                    $result = $remotegrading->parse_result_xml($this);
                } else if ($this->gradetype == 'text') {
                    $result = $remotegrading->parse_result_text();
                }

//                 $fraction = $this->penalize_grade($result->grade);
                $fraction = $this->penalize_grade($result->grade) / $this->get_question_attempt_field('maxmark');
                $this->upcheckerattempt->serverresult = $result->serverresult;
                $this->upcheckerattempt->feedback = $result->feedback;
                $this->save_upchecker_attempt();
            }
        } else {
        	error_log('Uploaded file not loaded');
        }

        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    /**
     *
     * @param array $response
     * @return boolean
     */
    public function is_complete_response(array $response) {
        return !empty($response['answer']);
    }

    /**
     *
     * @param array $response
     * @return string
     */
    public function get_validation_error(array $response) {
        return '';
    }

    /**
     *
     * @param array $prevresponse
     * @param array $newresponse
     * @return boolean
     */
    public function is_same_response(array $prevresponse, array $newresponse) {
        return $prevresponse == $newresponse;
    }

    public function get_correct_response() {
        return '';
    }

    /**
     *
     * @return array
     */
    public function get_answers() {
        return array();
    }

    /**
     *
     * @param array $response
     * @param question_answer $answer
     * @return boolean
     */
    public function compare_response_with_answer(array $response, question_answer $answer) {
        return true;
    }

    /**
     *
     * @return boolean
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && ($filearea == 'questiontext' || $filearea == 'response_answer')) {
            return true;
        }
        return false;
    }

    /**
     *
     * @return stored_file
     */
    public function get_uploaded_file() {
        global $DB;

        $steps = $DB->get_records('question_attempt_steps',
                array('questionattemptid' => $this->questionattemptid), 'sequencenumber DESC', 'id');

        $contextid = $DB->get_field_sql('
        		SELECT qu.contextid
        		FROM {question_usages} qu
        			JOIN {question_attempts} qa ON qu.id = qa.questionusageid
        		WHERE qa.id = :questionattemptid
        		',
        		['questionattemptid' => $this->questionattemptid]
        );
        if (!$contextid) {
        	return null;
        }

        $fs = get_file_storage();

        foreach ($steps as $step) {
            if ($files = $fs->get_area_files($contextid, 'question', 'response_answer', $step->id,
                    'itemid, filepath, filename', false)) {
                return reset($files);
            }
        }
        return null;
    }

    /**
     *
     * @param stored_file $file
     */
    public function store_file(stored_file $file) {
        global $CFG, $DB, $COURSE;

        if ($this->storagetype == 'dropbox') {
            require_once $CFG->dirroot . '/question/type/upchecker/class/dropbox.php';

            $tmpfile = $this->get_tmp_path();
            $file->copy_content_to($tmpfile);
//             var_dump($file);

            $setting = $DB->get_record('block_upchecker_setting_crs', array('course' => $COURSE->id));

            $dropbox = new qtype_upchecker_dropbox(array(
                    'access_token' => $setting->accesstoken,
                    'access_token_secret' => $setting->accesssecret
            ));

            $filename = $file->get_filename();
            $this->replaceparams = (object)array(
                    'origname' => pathinfo($filename, PATHINFO_FILENAME),
                    'origext' => pathinfo($filename, PATHINFO_EXTENSION)
            );

            $uploadas = '/'.$this->replace_upload_filename($this->uploadfilename, $file);
            $result = $dropbox->put_file($tmpfile, $uploadas);

            if (!empty($result->error)) {
                debugging('Dropboxエラー: '.$result->error);
            }

            @unlink($tmpfile);
        }
    }

    public function set_replace_params(stored_file $file) {
    	$filename = $file->get_filename();
    	$this->replaceparams = (object)array(
    			'origname' => pathinfo($filename, PATHINFO_FILENAME),
    			'origext' => pathinfo($filename, PATHINFO_EXTENSION)
    	);
    }

	/**
	 *
	 * @param string $filename
	 * @param stored_file $file
	 * @return string
	 */
	public function replace_upload_filename($filename, stored_file $file) {
		return preg_replace_callback('/\{([a-z_]+?)\}/',
				// array($this, 'replace_upload_filename_callback'),
				function ($matches) use($file) {
					global $USER, $DB;

					$varname = $matches[1];
					// if ($this->resyncfileuser) {
					// $user = $this->resyncfileuser;
					// } else {
					// $user = $USER;
					// }
					if ($file) {
						$user = $DB->get_record('user', [
								'id' => $file->get_userid()
						]);
					} else {
						$user = $USER;
					}
					$quizattempt = $this->get_quiz_attempt_by_file_item_id($file->get_itemid());

					switch ($varname) {
						case 'filename':
							// return $this->replaceparams->origname;
							return pathinfo($file->get_filename(), PATHINFO_FILENAME);
						case 'ext':
							// return $this->replaceparams->origext;
							return pathinfo($file->get_filename(), PATHINFO_EXTENSION);
						case 'quizname':
							$quiz = $this->get_quiz();
							return $quiz->name;
						case 'quizid':
							$quiz = $this->get_quiz();
							return $quiz->id;
						case 'cmid':
							$quiz = $this->get_quiz();
							$cm = get_coursemodule_from_instance('quiz', $quiz->id);
							return $cm->id;
						case 'questionname':
							return $this->name;
						case 'questionid':
							return $this->id;
						case 'lastname':
							return $user->lastname;
						case 'firstname':
							return $user->firstname;
						case 'fullname':
							return fullname($user);
						case 'username':
							return $user->username;
						case 'idnumber':
							return $user->idnumber;
						case 'email':
							return $user->email;
						case 'institution':
							return $user->institution;
						case 'department':
							return $user->department;
						case 'date':
							// return date('Y-m-d', $this->get_question_attempt_field('timemodified'));
// 							return date('Y-m-d', $file->get_timemodified());
							return date('Y-m-d', $quizattempt->timestart);
							case 'time':
							// return date('H-i-s', $this->get_question_attempt_field('timemodified'));
// 							return date('H-i-s', $file->get_timemodified());
							return date('H-i-s', $quizattempt->timestart);
						default:
							return $matches[0];
					}
				}, $filename);
	}

    /**
     *
     * @param string $field
     * @return string
     */
    private function get_question_attempt_field($field) {
    	global $DB;

    	if (!$this->questionattempt) {
    		$this->questionattempt
    		    = $DB->get_record('question_attempts', array('id' => $this->questionattemptid),
    		            '*', MUST_EXIST);
    	}

    	return $this->questionattempt->$field;
    }

    public function save_upchecker_attempt() {
        global $DB;

        $DB->update_record('question_upchecker_attempts', $this->upcheckerattempt);
    }

    /**
     *
     * @return boolean
     */
    public function is_overdue() {
        return $this->duedate && time() >= $this->duedate;
    }

    /**
     *
     * @return boolean
     */
    public function is_open() {
        return $this->permitlate || !$this->is_overdue();
    }

    /**
     *
     * @param float $grade
     * @return float
     */
    private function penalize_grade($grade) {
        if ($this->is_overdue()) {
            $grade *= $this->lategrade;
        }

        return $grade;
    }

    /**
     *
     * @return \stdClass
     */
    private function get_quiz() {
        global $DB;

        $sql = '
                SELECT q.*
                    FROM {quiz} q
                        JOIN {course_modules} cm ON q.id = cm.instance
                        JOIN {context} c ON cm.id = c.instanceid
                        JOIN {question_usages} qu ON c.id = qu.contextid
                        JOIN {question_attempts} qa ON qu.id = qa.questionusageid
                    WHERE qa.id = :qaid
                ';
        $params = array('qaid' => $this->questionattemptid);
        return $DB->get_record_sql($sql, $params);
    }

    /**
     * @param int $itemid
     * @return \stdClass
     */
    private function get_quiz_attempt_by_file_item_id($itemid) {
    	global $DB;

    	return $DB->get_record_sql('
    			SELECT qa.*
    			FROM {quiz_attempts} qa
    				JOIN {question_usages} qu ON qa.uniqueid = qu.id
    				JOIN {question_attempts} qta ON qu.id = qta.questionusageid
    				JOIN {question_attempt_steps} qtas ON qta.id = qtas.questionattemptid
    			WHERE qtas.id = :itemid
    			',
    			['itemid' => $itemid]
        );
    }

    /**
     *
     * @return string|boolean
     */
    private function get_tmp_path() {
    	$dir = make_temp_directory('upchecker');

    	for ($i = 0; $i < 30; $i++) {
    		$tmpfile = "$dir/dbup_".sprintf('%04X', mt_rand(0, 0xFFFF));
    		if (!file_exists($tmpfile)) {
    			return $tmpfile;
    		}
    	}
    	return false;
    }
}
