<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Errors_controller extends CI_Controller {

	/**
	 * コンストラクタ
	 */
	function __construct(){
		parent::__construct();
	}

	/**
	 * エラー画面を表示
	 */
	function error_404() {
		$this->output->set_status_header('404');
		$this->load->view('errors/html/error_404', array('heading' => '404 Page Not Found', 'message' => 'The page you requested was not found.'));
	}
}