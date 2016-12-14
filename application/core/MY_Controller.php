<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller 
{

    function __construct()
    {
        parent::__construct();
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
	 * URL safeなエンコードメソッド
	 * 変換した後に後ろの「==」は削除する。
	 * TODO ここに宣言すると呼び出せてしまうのでhelperなどに移動する。
	 * 
	 */
	public function base64_urlsafe_encode($val) {
		$encode_val = $this->encrypt->encode($val, $this->encrypt->get_key());
		
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

		return $this->encrypt->decode($val, $this->encrypt->get_key());
	}
}