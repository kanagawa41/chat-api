<?php
defined('BASEPATH') OR exit ('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

class Health_controller extends REST_Controller {
	public function index() {
		$data = array (
			'health-check' => true
		);

        $this->set_response($data, REST_Controller::HTTP_OK); return;
	}
}