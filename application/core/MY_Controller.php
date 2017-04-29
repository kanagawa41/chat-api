<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

class MY_Controller extends REST_Controller {

    function __construct() {
        parent::__construct();

        $this->load->database();
        $this->db->query('SET NAMES utf8');

        // 各メソッドで適せん読み込むようにする
        $this->config->load('my_config');
        $this->lang->load('form_validation');
        $this->load->library(['form_validation', 'encrypt', 'classLoad']);
        $this->load->helper(['common', 'hash']);
        $this->load->model(['user', 'stream_message', 'user_message', 'post_image', 'room', 'read_message', 'note', 'feedback']);
	}
}