<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Create_db_controller extends CI_Controller {

	public function index()
	{
		$this->load->database();
		$this->load->model('create_db');

		$result = $this->create_db->create_rooms_table();

		$result = $this->create_db->create_users_table();

		$result = $this->create_db->create_messages_table();

		$result = $this->create_db->create_reads_table();

        //postデータをもとに$arrayからデータを抽出
        $data = array('result' => $result);
		
        //$dataをJSONにして返す
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($data));		
	}
	
	public function drop()
	{
		$this->load->database();
		$this->load->model('create_db');

		$this->create_db->drop_table('rooms');

		$this->create_db->drop_table('users');

		$this->create_db->drop_table('messages');

		$this->create_db->drop_table('reads');

        //postデータをもとに$arrayからデータを抽出
        $data = array('result' => 'true');
		
        //$dataをJSONにして返す
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($data));		
	}
}
