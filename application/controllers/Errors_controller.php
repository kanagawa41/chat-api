<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Errors_controller extends REST_Controller {

	/**
	 * コンストラクタ
	 */
	function __construct(){
		parent::__construct();
	}

	/**
	 * エラー画面を表示
	 */
	function index_get() {
		$data = ['errors' => 'The page you requested was not found.'];
        $this->set_response($data, REST_Controller::HTTP_NOT_FOUND); return;
	}

	/**
	 * エラー画面を表示
	 */
	function index_post() {
		$data = ['errors' => 'The page you requested was not found.'];
        $this->set_response($data, REST_Controller::HTTP_NOT_FOUND); return;
	}

	/**
	 * エラー画面を表示
	 */
	function index_put() {
		$data = ['errors' => 'The page you requested was not found.'];
        $this->set_response($data, REST_Controller::HTTP_NOT_FOUND); return;
	}

	/**
	 * エラー画面を表示
	 */
	function index_delete() {
		$data = ['errors' => 'The page you requested was not found.'];
        $this->set_response($data, REST_Controller::HTTP_NOT_FOUND); return;
	}

	/**
	 * エラー画面を表示
	 */
	function index_patch() {
		$data = ['errors' => 'The page you requested was not found.'];
        $this->set_response($data, REST_Controller::HTTP_NOT_FOUND); return;
	}

	/**
	 * エラー画面を表示
	 */
	function error_404() {
		$data = ['errors' => 'The page you requested was not found.'];
        $this->set_response($data, REST_Controller::HTTP_NOT_FOUND); return;
	}
}