<?php
require_once($CFG->libdir.'/oauthlib.php');

class qtype_upchecker_dropbox extends oauth_helper {
    /**
     * @var string dropbox access type, can be dropbox or sandbox
     */
    private $mode = 'dropbox';
    /**
     * @var string dropbox api url
     */
    private $dropbox_api = 'https://api.dropbox.com/1';
    /**
     * @var string dropbox content api url
     */
    private $dropbox_content_api = 'https://api-content.dropbox.com/1';
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

    /**
     * Constructor for dropbox class
     *
     * @param array $args
     */
    function __construct($args) {
        $args['oauth_consumer_key'] = $this->dropboxconsumerkey;
        $args['oauth_consumer_secret'] = $this->dropboxconsumersecret;
        $args['api_root'] = 'https://api.dropbox.com/1/oauth';
        $args['authorize_url'] = 'https://www.dropbox.com/1/oauth/authorize';

        parent::__construct($args);
    }

    /**
     * Get file listing from dropbox
     *
     * @param string $path
     * @param string $token
     * @param string $secret
     * @return array
     */
    public function get_listing($path='/', $token='', $secret='') {
        $url = $this->dropbox_api.'/metadata/'.$this->mode.$path;
        $content = $this->get($url, array(), $token, $secret);
        $data = json_decode($content);
        return $data;
    }

    /**
     * Prepares the filename to pass to Dropbox API as part of URL
     *
     * @param string $filepath
     * @return string
     */
    protected function prepare_filepath($filepath) {
        $info = pathinfo($filepath);
        $dirname = $info['dirname'];
        $basename = $info['basename'];
        $filepath = $dirname . rawurlencode($basename);
        if ($dirname != '/') {
            $filepath = $dirname . '/' . $basename;
            $filepath = str_replace("%2F", "/", rawurlencode($filepath));
        }
        return $filepath;
    }

    /**
     * Retrieves the default (64x64) thumbnail for dropbox file
     *
     * @throws moodle_exception when file could not be downloaded
     *
     * @param string $filepath local path in Dropbox
     * @param string $saveas path to file to save the result
     * @param int $timeout request timeout in seconds, 0 means no timeout
     * @return array with attributes 'path' and 'url'
     */
    public function get_thumbnail($filepath, $saveas, $timeout = 0) {
        $url = $this->dropbox_content_api.'/thumbnails/'.$this->mode.$this->prepare_filepath($filepath);
        if (!($fp = fopen($saveas, 'w'))) {
            throw new moodle_exception('cannotwritefile', 'error', '', $saveas);
        }
        $this->setup_oauth_http_options(array('timeout' => $timeout, 'file' => $fp, 'BINARYTRANSFER' => true));
        $result = $this->get($url);
        fclose($fp);
        if ($result === true) {
            return array('path'=>$saveas, 'url'=>$url);
        } else {
            unlink($saveas);
            throw new moodle_exception('errorwhiledownload', 'repository', '', $result);
        }
    }

    /**
     * Downloads a file from Dropbox and saves it locally
     *
     * @throws moodle_exception when file could not be downloaded
     *
     * @param string $filepath local path in Dropbox
     * @param string $saveas path to file to save the result
     * @param int $timeout request timeout in seconds, 0 means no timeout
     * @return array with attributes 'path' and 'url'
     */
    public function get_file($filepath, $saveas, $timeout = 0) {
        $url = $this->dropbox_content_api.'/files/'.$this->mode.$this->prepare_filepath($filepath);
        if (!($fp = fopen($saveas, 'w'))) {
            throw new moodle_exception('cannotwritefile', 'error', '', $saveas);
        }
        $this->setup_oauth_http_options(array('timeout' => $timeout, 'file' => $fp, 'BINARYTRANSFER' => true));
        $result = $this->get($url);
        fclose($fp);
        if ($result === true) {
            return array('path'=>$saveas, 'url'=>$url);
        } else {
            unlink($saveas);
            throw new moodle_exception('errorwhiledownload', 'repository', '', $result);
        }
    }

    /**
     * Returns direct link to Dropbox file
     *
     * @param string $filepath local path in Dropbox
     * @param int $timeout request timeout in seconds, 0 means no timeout
     * @return string|null information object or null if request failed with an error
     */
    public function get_file_share_link($filepath, $timeout = 0) {
        $url = $this->dropbox_api.'/shares/'.$this->mode.$this->prepare_filepath($filepath);
        $this->setup_oauth_http_options(array('timeout' => $timeout));
        $result = $this->post($url, array('short_url'=>0));
        if (!$this->http->get_errno()) {
            $data = json_decode($result);
            if (isset($data->url)) {
                return $data->url;
            }
        }
        return null;
    }

    /**
     * Sets Dropbox API mode (dropbox or sandbox, default dropbox)
     *
     * @param string $mode
     */
    public function set_mode($mode) {
        $this->mode = $mode;
    }

    /**
     *
     * @param string $url
     * @param array $params
     * @return mixed
     */
    public function put($url, array $params = array()) {
        return $this->request('PUT', $url, $params);
    }

    /**
     *
     * @param string $filepath
     * @param string $uploadas
     * @return \stdClass
     */
    public function put_file($filepath, $uploadas) {
        $result = $this->put($this->dropbox_content_api.'/files_put/'
                .$this->prepare_filepath($this->mode.$uploadas),
                array('file' => $filepath));
        $data = json_decode($result);
        $this->check_error($data);

        return $data;
    }

    /**
     *
     * @return \stdClass
     */
    public function get_info() {
        $result = $this->get($this->dropbox_api.'/account/info');
        $data = json_decode($result);
        $this->check_error($data);

        return $data;
    }

    private function check_error(\stdClass $data) {
        if (!empty($data->error)) {
            throw new moodle_exception('dropboxerror', 'qtype_upchecker', '', serialize($data->error), serialize($data->error));
        }
    }
}
