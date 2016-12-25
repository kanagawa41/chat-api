<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Health_controller extends CI_Controller {

	public function index() {
		$data = array (
			'health-check' => true
		);

		//$dataをJSONにして返す
		$this->output->set_content_type('application/json')->set_output(json_encode($data));
	}
}