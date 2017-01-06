<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class User extends MY_Model {
	protected $_primary_key = 'user_id';

    public $user_id; //--ユーザＩＤ
    public $user_hash; //--ユーザハッシュ
    public $user_role; //--ユーザロール(1…admin, 2…specific-user, 3…anonymous)
    public $name; //--ユーザ名
    public $sex; //--性別(1…男, 2…女)
    public $room_id; //--ルームＩＤ
    public $begin_message_id; //--入室した際の開始メッセージＩＤ
    public $icon_id; //--アイコンＩＤ
    public $fingerprint; //--フィンガープリント
    public $user_agent; //--ユーザエージェント
    public $ip_address; //--ユーザのアドレス
    public $port; //--ユーザのポート
    public $created_at; //--作成日

	public function __construct() {
		// CI_Model constructor の呼び出し
		parent :: __construct();
	}

	/**
	 * ユーザの存在チェックを厳密に行う。
	 * trueの場合はユーザが存在する。でない場合はfalse。
	 */
	public function existUser($room_id, $user_id) {
		return $this->db->from('rooms r')
		->join('users as u', 'u.room_id = r.room_id', 'inner')
		->where(array (
			'r.room_id' => $room_id,
			'u.user_id' => $user_id
		))->count_all_results() > 0;
	}

	/**
	 * ユーザを追加する。
	 * 追加したユーザIDを返却する。
	 */
	public function insert_user($name, $room_id, $user_role, $fingerprint, $sex, $icon_id) 
	{
	    $this->db->select_max('message_id');
	    $this->db->where('room_id', $room_id);
	    $result_row = $this->db->get('messages')->row();
		$max_message_id = empty($result_row->message_id) ? 0: $result_row->message_id;

		$this->load->helper('string');

		// ユーザ固有のハッシュ値を取得する（低確率でダブル可能性はある）
		$user_hash = random_string($type = 'alnum', $len = 10);

		$data = array(
		   'user_hash' => $user_hash ,
		   'user_role' => $user_role ,
		   'name' => $name ,
		   'sex' => $sex ,
		   'room_id' => $room_id ,
		   'begin_message_id' => $max_message_id ,
		   'icon_id' => $icon_id ,
		   'fingerprint' => $fingerprint ,
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

		return $user_id;
	}
}