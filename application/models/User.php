<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class User extends CI_Model {

	public function __construct() {
		// CI_Model constructor の呼び出し
		parent :: __construct();
	}

	/**
	 * ユーザを追加する。
	 */
	public function insert_user($name, $room_id, $user_role, $icon_id) 
	{
	    $this->db->select_max('message_id');
	    $this->db->where('room_id', $room_id);
	    $result_row = $this->db->get('messages')->row();
		$max_message_id = empty($result_row->message_id) ? 0: $result_row->message_id;

		$this->load->helper('string');

		// ユーザ固有のハッシュ値を取得する（低確率でダブル可能性はある）
		// FIXME ハッシュテーブルから取得するようにする。
		$user_hash = random_string($type = 'alnum', $len = 10);

		$data = array(
		   'user_hash' => $user_hash ,
		   'user_role' => $user_role ,
		   'name' => $name ,
		   'room_id' => $room_id ,
		   'begin_message_id' => $max_message_id ,
		   'icon_id' => $icon_id ,
		   'user_agent' => $_SERVER['HTTP_USER_AGENT'] ,
		   'ip_address' => $_SERVER['REMOTE_ADDR'] ,
		   'port' => $_SERVER['REMOTE_PORT'] ,
		);

		$this->db->insert('users', $data);
		$user_id = $this->db->insert_id();

		$data = array(
		   'user_id' => $user_id ,
		   'room_id' => $room_id ,
		   'body' => $name ,
		   'type' => 3 , // 入室
		);

		$this->db->insert('messages', $data);

		return $user_hash;
		
	}
}