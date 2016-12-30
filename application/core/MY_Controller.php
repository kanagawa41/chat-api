<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller 
{

    function __construct()
    {
        parent::__construct();

		$this->config->load('my_config');
	}
	
	/**
	 * トークンが存在するか確認する。
	 * 正しいトークンがない場合は、falseを返却する。
	 */
	public function exist_token() {
		$this->config->load('my_config');

		$token = $this->input->get_request_header('X-ChatToken');

		// トークンが含まれていない場合
		if ($this->config->item('chat_token') != $token) {
			$data = array (
				'error' => 'you have to set X-ChatToken to http header.'
			);
			$this->output->set_content_type('application/json')->set_output(json_encode($data));
			return false;
		}

		// トークンが違う場合		
		if ($this->config->item('chat_token') != $token) {
			$data = array (
				'error' => 'you have not authority.'
			);
			$this->output->set_content_type('application/json')->set_output(json_encode($data));
			return false;
		}
		
		return true;
	}

	/**
	 * 引数から周知用のハッシュ値にエンコードする。
	 * 「roomid_userid」の形式でbase64にエンコードする。
	 * 「０」は自分でユーザ名を決められるアノニマスが使用できる。
	 * TODO ここに宣言すると呼び出せてしまうのでhelperなどに移動する。
	 * 
	 */
	public function room_hash_encode($room_id, $role, $user_id) {
		return $this->base64_urlsafe_encode($room_id . '_' . $role . '_' . $user_id);
	}

	/**
	 * 周知用のハッシュ値をデコードする。
	 * 「ルームID(room_id)、ユーザID(user_id)」は配列にして返却する。
	 * TODO ここに宣言すると呼び出せてしまうのでhelperなどに移動する。
	 * 
	 */
	public function room_hash_decode($room_hash) {
		$raw_room_data = $this->base64_urlsafe_decode($room_hash);
		$room_data = explode("_", $raw_room_data);

		// 部屋情報が２つでない場合は異常値
		if(count($room_data) == 3) {
			return array(
					'room_id' => $room_data[0]
					, 'role' => $room_data[1]
					, 'user_id' => $room_data[2]
			);
		} else {
			return array(
					'room_id' => ''
					, 'role' => ''
					, 'user_id' => ''
			);
		}

	}

	/**
	 * URL safeなエンコードメソッド
	 * 変換した後に後ろの「==」は削除する。
	 * TODO ここに宣言すると呼び出せてしまうのでhelperなどに移動する。
	 * 
	 */
	public function base64_urlsafe_encode($val) {
		$encode_val = $this->encrypt->encode($val, $this->config->item('room_encryption_key'));
		
		return substr(str_replace(array (
			'+',
			'/',
			'='
		), array (
			'_',
			'-',
			'.'
		), $encode_val), 0, -2);
	}

	/**
	 * URL safeなデコードメソッド
	 * 削除した「==」を付加してデコードさせる。
	 * TODO ここに宣言すると呼び出せてしまうのでhelperなどに移動する。
	 * 
	 */
	public function base64_urlsafe_decode($raw_val) {
		$val = str_replace(array (
			'_',
			'-',
			'.'
		), array (
			'+',
			'/',
			'='
		), $raw_val . '..');

		return $this->encrypt->decode($val, $this->config->item('room_encryption_key'));
	}
}