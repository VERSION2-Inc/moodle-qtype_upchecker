<?php // $Id: remote_grading.php 296 2014-04-16 00:04:48Z yama $
/**
 * remotegradelib.php
 *
 * @author VERSION2 Inc.
 * @version $Id: remote_grading.php 296 2014-04-16 00:04:48Z yama $
 * @package cch19
 */
defined('MOODLE_INTERNAL') || die();

/**
 * リモートサーバによる採点を実装
 */
class remote_grading {
    /** @var array HTTP応答を格納 */
    private $response;

    /** 手動採点 */
    const GRADETYPE_MANUAL = 0;
    /** XML */
    const GRADETYPE_XML = 1;
    /** テキスト */
    const GRADETYPE_TEXT = 2;

    /**
     * 採点対象ファイルを送信する
     *
     * @param string $url 採点スクリプトのURL
     * @param string $fileparam ファイルパラメータ名
     * @param stored_file $storedfile
     * @param string|array $restparams その他のパラメータ
     * @param array $vars パラメータ変数
     * @param stdClass $user
     * @return bool
     */
    public function post_file($url, $fileparam, stored_file $storedfile, $restparams, $vars = null, $user = null) {
        global $CFG, $USER;

        $restparams = $this->replace_param_vars($restparams, $vars);
        if (!is_array($restparams)) {
            $restparams = $this->query2array($restparams);
        }

        $params = $restparams;

//         $tmpdir = implode('/', array($CFG->tempdir, 'upchecker', $USER->id));
        $tmpdir = implode('/', array($CFG->tempdir, 'upchecker', $USER->id, time()));
//         error_log("tmpdir: $tmpdir");
        check_dir_exists($tmpdir, true, true);
        $tmppath = $tmpdir.'/'.$storedfile->get_filename();
//         error_log("tmppath: $tmppath");
        if (!$storedfile->copy_content_to($tmppath)) {
            throw new moodle_exception('failedtocreatetmpfile', 'qtype_upchecker', '', $tmppath);
        }
        $params[$fileparam] = $this->get_curl_file_param($tmppath);

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type' => 'multipart/form-data'
            )
        ));

        $this->response['body'] = curl_exec($ch);
        curl_close($ch);

//         @unlink($tmppath);
//         fulldelete($tmpdir);

        return true;
    }

    /**
     * 採点対象データを送信する
     *
     * @param string $url 採点スクリプトのURL
     * @param string|array $param パラメータ
     * @param string $restParam
     * @param array $vars パラメータ変数
     * @param stdClass $user
     * @return bool
     */
    public function post_data($url, $params, $restparams, $vars = null, $user = null) {
        global $CFG, $USER;

        $restparams = $this->replace_param_vars($restparams, $vars);
        if (!is_array($restparams)) {
            $restparams = $this->query2array($restparams);
        }

        $ch = curl_init($url);
        $mergedparams = $restparams;
        $postdata = http_build_query($mergedparams);
        $postdata = str_replace('&amp;','&',$postdata);

        $tmpdir = implode('/',array($CFG->tempdir,'upchecker',$USER->id));
        check_dir_exists($tmpdir, true, true);
        $tmppath = $tmpdir.'/'.time();
        $fp = fopen($tmppath, 'w');
        fwrite($fp, reset($params));
        fclose($fp);
        $mergedparams[key($params)] = $this->get_curl_file_param($tmppath);

        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postdata,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE=>true,
            CURLOPT_HTTPHEADER=>array(
                'Content-Type' => 'multipart/form-data'
            )
        ));

        $this->response['body'] = curl_exec($ch);
        curl_close($ch);

        return true;
    }

    /**
     * サーバによる採点結果を得る
     *
     * @param string $inputEnc スクリプトの出力エンコーディング
     * @return string スクリプトから返された採点結果
     */
    public function get_result($inputEnc = null) {
        if ($inputEnc) {
            return mb_convert_encoding($this->response['body'], 'utf-8',
                                       $inputEnc);
        } else {
            return $this->response['body'];
        }
    }

    /**
     * クエリ文字列を配列に変換
     *
     * @param string $query
     * @return array
     */
    public function query2array($query) {
        $params = array();
        $query = preg_replace('/\#[^#]*$/', '', $query);

        if ($query == '') {
            return $params;
        }

        $items = explode('&', $query);
        foreach ($items as $item) {
            list($name, $value) = explode('=', $item);
            $params[$name] = $value;
        }
        return $params;
    }

    /**
     * パラメータの中の変数を置換する
     *
     * @param string $params
     * @param \stdClass $user
     * @return string
     */
    private function replace_param_vars($params, \stdClass $user = null) {
        global $USER, $COURSE;

        if (empty($user)) {
            $user = $USER;
        }

        // パラメータの中の変数を置換する
        $vars = array(
            '{username}' => $user->username,
            '{kanjiname}' => $user->firstname,
            '{latinname}' => $user->lastname,
            '{firstname}' => $user->firstname,
            '{lastname}' => $user->lastname,
            '{email}' => $user->email,
            '{lang}' => $user->lang,
            '{institution}' => $user->institution,
            '{department}' => $user->department,
            '{idnumber}' => $user->idnumber
        );

        if (strpos($params, '{group}') !== false) {
            $groups = groups_get_user_groups($COURSE->id, $user->id);
            $groupnames = implode(',', array_map('groups_get_group_name', $groups[0]));
            $vars['{group}'] = $groupnames;
        }

        return strtr($params, $vars);
    }

    /**
     *
     * @param string $filename
     * @param string $origname
     * @param string $submitteddata
     * @return string
     */
    public function format_result($filename = '', $origname = '', $submitteddata = '') {
        $result = $this->get_result();

        $escapeByte = "\0..\x1f\"\x7f..\xff";

        return sprintf(
                '{"filename":"%s","origname":"%s","submitteddata":"%s","serverresult":"%s"}',
                addcslashes($filename, $escapeByte),
                addcslashes($origname, $escapeByte),
                addcslashes($submitteddata, $escapeByte),
                addcslashes($result, $escapeByte)
        );
    }

    /**
     *
     * @param stdClass $options
     * @param string $filename
     * @param string $origname
     * @param string $submitteddata
     * @param string $serverresult
     * @return \stdClass
     */
    public function parse_result_xml($options, $filename = '', $origname = '', $submitteddata = '', $serverresult = null) {
        $parser = xml_parser_create();

        $data = new stdClass();
        $data->filename = $filename;
        $data->origname = $origname;
        $data->submitteddata = $submitteddata;

        if ($serverresult) {
            $data->serverresult = $serverresult;
        } else {
            $data->serverresult = $this->get_result();
        }

        xml_parse_into_struct($parser, $data->serverresult, $values, $index);
        xml_parser_free($parser);

        $tag = strtoupper($options->gradetag);
        if (isset($index[$tag]) && isset($values[$index[$tag][0]]['value'])) {
            $data->grade = (float)$values[$index[$tag][0]]['value'];
        } else {
            $data->grade = 0;
        }

        // 締め切り後は得点を制限する
//         if ($options->duedate && time() >= $options->duedate) {
//             $grade *= $options->lategrade;
//         }

        $tag = strtoupper($options->feedbacktag);
        if (isset($index[$tag]) && isset($values[$index[$tag][0]]['value'])) {
            $data->feedback = $values[$index[$tag][0]]['value'];
        } else {
            $data->feedback = '';
        }

        return $data;
    }

    /**
     *
     * @param string $filename
     * @param string $origName
     * @param string $submitteddata
     * @param string $serverresult
     * @return array
     */
    public function parse_result_text($filename = '', $origName = '',
                             $submitteddata = '', $serverresult = null) {
        $data = new stdClass();
        $data->filename = $filename;
        $data->origname = $origName;
        $data->submitteddata = $submitteddata;

        if ($serverresult) {
            $data->serverresult = $serverresult;
        } else {
            $data->serverresult = $this->getResult();
        }

        $lines = explode("\n", $data->serverresult);
        $grade = (float)array_shift($lines);
        $data->feedback = implode("\n", $lines);

        $answer = json_encode($data);

        return array($grade, $answer);
    }

    /**
     *
     * @param string $path
     * @return CURLFile|string
     */
    private function get_curl_file_param($path) {
    	if (class_exists('CURLFile')) {
    		return new CURLFile($path);
    	}
    	return "@$path";
    }
}
