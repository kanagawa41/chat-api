<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Message extends MY_Model {
	protected $_primary_key = 'message_id';

    public $message_id; //--メッセージＩＤ
    public $user_id; //--ユーザＩＤ
    public $room_id; //--ルームＩＤ
    public $body; //--メッセージ内容
    public $type; //--メッセージの種類(1…ルーム作成、2…メッセージ、3…入室、4…日付)
    public $created_at; //--作成日

	public function __construct() {
		// CI_Model constructor の呼び出し
		parent :: __construct();
	}

	/**
	 * 指定ユーザのメッセージ数を取得する。
	 * trueの場合はユーザが存在する。でない場合はfalse。
	 */
	public function message_count($room_id, $user_id) {
		return $this->db->from('messages')->
		where(array(
			'room_id' => $room_id,
			'user_id' => $user_id
		))->count_all_results();
	}

	/**
	 * 指定のメッセージを取得する。
	 */
	public function specific_message($room_id, $message_id) {
		return $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')
		->from('messages as m')
		->join('users as u', 'u.user_id = m.user_id', 'inner')
		->where(array (
			'm.message_id' => $message_id,
			'm.room_id' => $room_id,
		))->get()->row();
	}
	
	/**
	 * 未読のメッセージを取得する。
	 */
	public function unread_messages($room_id, $user_id) {
		// 開始メッセージＩＤ
		$begin_message_id = $this->user->find($user_id)->begin_message_id;

		// 既読済のメッセージID
		$raw_last_read_message_id = $this->read->read_message($user_id);
		$last_read_message_id = $raw_last_read_message_id < $begin_message_id ? $begin_message_id : $raw_last_read_message_id;

		return $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')
		->from('messages as m')
		->join('users as u', 'u.user_id = m.user_id', 'inner')
		->where(array (
			'm.room_id' => $room_id,
			'm.message_id >' => $last_read_message_id,
			'm.user_id <>' => $user_id
		))->get()->result();
	}

	
	/**
	 * 過去のメッセージを取得する。
	 */
	public function past_messages($room_id, $user_id, $message_id) {
		$begin_message_id = $this->user->find($user_id)->begin_message_id;

		// ユーザが参照できる過去のメッセージ数を取得する。
		$massage_count = $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')
		->from('messages as m')
		->join('users as u', 'u.user_id = m.user_id', 'inner')
		->where(array (
			'm.room_id' => $room_id,
			'm.message_id>' => $begin_message_id,
			'm.message_id<' => $message_id,
		))->count_all_results();

		if($massage_count == 0){
			return array();
		}

		$past_message_max_count = $this->config->item('past_message_max_count');
		// FIXME このロジックは正しいか調査する必要がある。
		$massage_offset = $massage_count > $past_message_max_count ? $massage_count - $past_message_max_count : 0;

		return $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')
		->from('messages as m')
		->join('users as u', 'u.user_id = m.user_id', 'inner')
		->where(array (
			'm.room_id' => $room_id,
			'm.message_id>' => $begin_message_id,
			'm.message_id<' => $message_id,
		))
		->limit($past_message_max_count)
		->offset($massage_offset)
		->get()->result();
	}
	
	/**
	 * 日付が変わっていないか確認する。
	 * 最終メッセージと現在の日付が変わっている場合はtrue、でない場合はfalse。
	 */
	 public function changed_date($room_id){
		$row = $this->db->select('datetime(CURRENT_TIMESTAMP) as now_date, created_at as last_message_date')
		->from('messages')
		->where(array (
			'room_id' => $room_id
		))
		->order_by('message_id', 'DESC')
		->get()->row();

		$this->load->helper('date');
		
		// 最終メッセージと日付が変わっていた場合
		return compare_date($row->now_date, $row->last_message_date);
	 }
}