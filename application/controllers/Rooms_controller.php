<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Rooms_controller extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
		$this->load->library('encrypt');
    }	

	/**
	 * ルーム一覧を返却する
	 * GET
	 */
	public function select_rooms() {
		if(!$this->exist_token()){ return; }

		$this->load->database();

		$select_results = $this->db->select('room_id, name, description, updated_at')->from('rooms')->get()->result();

		$data = array ();
		foreach ($select_results as $row) {
			$temp_row = array ();
			$temp_row['room_id'] = $row->room_id;
			$temp_row['room_id_hash'] = $this->base64_urlsafe_encode($row->room_id); // 周知用のID
			$temp_row['name'] = $row->name;
			$temp_row['message_num'] = ''; // TODO メッセージを返却できるようにする
			$temp_row['last_update_time'] = $row->updated_at;

			$data[] = $temp_row;
		}

		$this->output->set_json_output($data);
		return;
	}

	/**
	 * チャットの名前を取得
	 * GET
	 */
	public function select_room($room_hash) {
		// ルーム番号が指定されていない場合
		if (empty ($room_hash)) {
			$this->output->set_json_error_output(array('you have to set room_id in url.')); return;
		}

		// ルームＩＤをデコードする
		$room_id = $this->base64_urlsafe_decode($room_hash);

		$this->load->database();

		$row = $this->db->select('room_id, name, description, updated_at')->from('rooms')->where('room_id', $room_id)->get()->row();

		if (empty($row)) {
			$this->output->set_json_error_output(array('No target.')); return;
		}

		$message_count = $this->db->from('messages')->where('room_id', $room_id)->count_all_results();

		$data = array ();
		$data['name'] = $row->name;
		$data['description'] = $row->description;

		$this->output->set_json_output($data);
	}

	/**
	 * チャットの名前をアップデート
	 * PUT
	 */
	public function update_room($room_id) {
		// ルーム番号が指定されていない場合
		if (empty ($room_id)) {
			$this->output->set_json_error_output(array('you have to set room_id to url.')); return;
		}

		if(!$this->exist_token()){ return; }

		$description = $this->input->input_stream('description');

		$name = $this->input->input_stream('name');
		if (empty ($name)) {
			$this->output->set_json_error_output(array('Scarce value as "name".')); return;
		}

		$this->load->database();

		$this->db->where('room_id', $room_id)->update('rooms', array( 
			'name'=>  $name, 
			'description'	=>  $description
		));

		if ($res = $this->db->affected_rows() === 0) {
			$this->output->set_json_error_output(array('No target.')); return;
		}

		$data = array (
			'room_id' => $room_id
		);

		//$dataをJSONにして返す
		$this->output->set_json_output($data);
	}

	/**
	 * チャットを削除
	 * DELETE
	 */
	public function delete_room($room_id) {
		// ルーム番号が指定されていない場合
		if (empty ($room_id)) {
			$this->output->set_json_error_output(array('you have to set room_id to url.')); return;
		}

		if(!$this->exist_token()){ return; }

		$this->load->database();

		$this->db->where('room_id', $room_id)->delete('rooms');

		if ($res = $this->db->affected_rows() === 0) {
			$this->output->set_json_error_output(array('No target.')); return;
		}
	}

	/**
	 * ルームを作成する
	 * POST
	 */
	public function create_room() {
		if(!$this->exist_token()){ return; }
		
		$description = $this->input->post('description');

		$name = $this->input->post('name');
		if (empty ($name)) {
			$this->output->set_json_error_output(array('Scarce value as "name".')); return;
		}

		$this->load->database();
		$this->load->model('user');

		$this->db->trans_start();

		$this->db->insert('rooms', array (
			'name' => $name,
			'description' => $description,
		));

		$room_id = $this->db->insert_id();

		$admin_name = $this->config->item('admin_name');
		
		// 管理者ユーザを生成する。
		$user_hash = $this->user->insert_user($admin_name, $room_id, 1, 0);

		$user_id = $this->db->insert_id();

		$insert_data = array(
		   'user_id' => $user_id ,
		   'room_id' => $room_id ,
		   'body' => $name ,
		   'type' => 1 , // ルーム作成
		);

		$this->db->insert('messages', $insert_data);

		$this->db->trans_complete();

		$data = array(
		   'user_id' => $user_id ,
		   'room_id' => $room_id ,
		   'body' => $admin_name ,
		   'type' => 3 , // 入室
		);

		$this->db->insert('messages', $data);

		$response_data = array (
			'room_id' => $room_id,
			'room_hash' => $this->base64_urlsafe_encode($room_id),
			'admin_user_hash' => $user_hash,
		);

		//$dataをJSONにして返す
		$this->output->set_json_output($response_data);
	}

	/**
	 * チャットのメンバー一覧を取得
	 * GET
	 */
	public function select_users($room_hash) {
		// ルームＩＤをデコードする
		$room_id = $this->base64_urlsafe_decode($room_hash);

		$this->load->database();

		// 存在しないルームの場合
		$room_count = $this->db->from('rooms')->where(array (
			'room_id' => $room_id
		))->count_all_results();
		if ($room_count == 0) {
			$this->output->set_json_error_output(array('It do not exist room_id.')); return;
		}

		$sql_result = $this->db->from('users')->where(array (
			'room_id' => $room_id
		))->get()->result();

		$data = array ();
		foreach ($sql_result as $row) {
			$temp_row = array ();
			$temp_row['name'] = $row->name;

			$data[] = $temp_row;
		}

		//$dataをJSONにして返す
		$this->output->set_json_output($data);
	}

	/**
	 * チャットのメンバー情報を取得
	 * GET
	 */
	public function select_user($room_hash, $user_hash) {
		// ルームＩＤをデコードする
		$room_id = $this->base64_urlsafe_decode($room_hash);

		$this->load->database();

		// 存在しないルームの場合
		$room_count = $this->db->from('rooms')->where(array (
			'room_id' => $room_id
		))->count_all_results();
		if ($room_count == 0) {
			$this->output->set_json_error_output(array('It do not exist room_id.')); return;
		}

		// 存在しないユーザの場合
		$row = $this->db->from('users')->where(array (
			'user_hash' => $user_hash
		))->get()->row();
		if (empty ($row)) {
			$this->output->set_json_error_output(array('It do not exist user_id.')); return;
		}

		$message_count = $this->db->from('messages')->where(array('room_id' => $room_id, 'user_id' => $row->user_id))->count_all_results();

		$data = array ();
		$data['name'] = $row->name;
		$data['icon'] = $row->icon_id;
		$data['message_count'] = $message_count;
		$data['begin_message_id'] = $row->begin_message_id;
		$data['last_create_time'] = $row->created_at;

		//$dataをJSONにして返す
		$this->output->set_json_output($data);
	}

	/**
	 * チャットのメッセージ一覧を取得。前回取得分からの差分を返します。
	 * GET
	 */
	public function select_messages($room_hash, $user_hash) {
		$this->load->library('encrypt');

		// ルームＩＤをデコードする
		$room_id = $this->base64_urlsafe_decode($room_hash);

		$this->load->database();

		// 存在しないルームの場合
		$room_count = $this->db->from('rooms')->where(array (
			'room_id' => $room_id
		))->count_all_results();
		if ($room_count == 0) {
			$this->output->set_json_error_output(array('It do not exist room_id.')); return;
		}

		// 存在しないユーザの場合
		$row = $this->db->from('users')->where(array (
			'user_hash' => $user_hash
		))->get()->row();
		if (empty ($row)) {
			$data = array (
				'error' => 'It do not exist user_id.'
			);
			$this->output->set_json_output($data);
			return;
		}

		$user_id = $row->user_id;
		$begin_message_id = $row->begin_message_id;

		// 既読済のメッセージIDを返却する
		$sql = 'select COALESCE(max(r.message_id), 0) as last_read_message_id from messages m inner join reads r on r.user_id = ? and m.user_id = r.user_id;';

		$query_result = $this->db->query($sql, array (
			$user_id,
		))->row();

		$last_read_message_id = $query_result->last_read_message_id;

		$last_read_message_id = $last_read_message_id < $begin_message_id ? $begin_message_id : $last_read_message_id;

		$select_results = $this->db->select('m.message_id, u.name, u.user_hash, u.icon_id, m.body, m.type, m.created_at')->from('messages as m')->join('users as u', 'u.user_id = m.user_id', 'inner')->where(array (
			'm.room_id' => $room_id,
			'm.message_id >' => $last_read_message_id,
			'm.user_id <>' => $user_id
		))->get()->result();

		// デバッグ用
		//$this->output->set_json_error_output(array($this->db->last_query())); return;


		$data = array ();
		$last_message_id = null;
		foreach ($select_results as $row) {
			$temp_row = array ();
			$temp_row['message_id'] = $row->message_id;
			$temp_user_info = array ();
			$temp_user_info['name'] = $row->name;
			$temp_user_info['who'] = $row->user_hash === $user_hash ? "self" : "other";
			$temp_user_info['icon'] = $row->icon_id;
			$temp_row['user'] = $temp_user_info;
			$temp_row['body'] = $row->body;
			$temp_row['type'] = $row->type;
			$temp_row['send_time'] = $row->created_at;

			$data[] = $temp_row;
			$last_message_id = $row->message_id;
		}

		if (empty ($last_message_id)) {
			$this->output->set_json_output(array ()); return;
		}

		// 取得した最後のメッセージを既読済にする
		$this->db->trans_start();

		$insert_data = array (
			'message_id' => $last_message_id,
			'user_id' => $user_id,
			'room_id' => $room_id
		);
		$this->db->insert('reads', $insert_data);

		$this->db->trans_complete();

		$this->output->set_json_output($data);
	}

	/**
	 * TODO 返却形式を「select_messages」に合わせる
	 * チャットのメッセージ一覧を入室した時から全て取得。
	 * GET
	 */
	public function select_messages_all($room_id, $user_hash) {
		$this->load->library('encrypt');

		// ルームＩＤをデコードする
		$decode_room_id = $this->base64_urlsafe_decode($room_id);

		$this->load->database();

		// 存在しないルームの場合
		$room_count = $this->db->from('rooms')->where(array (
			'room_id' => $decode_room_id
		))->count_all_results();
		if ($room_count == 0) {
			$data = array (
				'error' => 'It do not exist room_id.'
			);
			$this->output->set_json_output($data);

			return;
		}

		// 存在しないユーザの場合
		$row = $this->db->from('users')->where(array (
			'user_hash' => $user_hash
		))->get()->row();
		if (empty ($row)) {
			$data = array (
				'error' => 'It do not exist user_id.'
			);
			$this->output->set_json_output($data);
			return;
		}

		$user_id = $row->user_id;
		// 参加開始メッセージID
		$begin_message_id = $row->begin_message_id;

		// ルームに入った際のメッセージＩＤ～未読のメッセージＩＤまで
		$select_results = $this->db->select('m.message_id, u.name, m.body, m.type, m.created_at')->from('messages as m')->join('users as u', 'u.user_id = m.user_id', 'inner')->where(array (
			'm.room_id' => $decode_room_id,
			'm.message_id >' => $begin_message_id
		))->get()->result();

		$data = array ();
		$last_message_id = null;
		foreach ($select_results as $row) {
			$temp_row = array ();
			$temp_row['message_id'] = $row->message_id;
			$temp_row['name'] = $row->name;
			$temp_row['body'] = $row->body;
			$temp_row['type'] = $row->type;
			$temp_row['created_at'] = $row->created_at;

			$data[] = $temp_row;
			$last_message_id = $row->message_id;
		}

		if (empty ($last_message_id)) {
			$this->output->set_content_type('application/json')->set_output(json_encode(array ()));
			return;
		}

		// 取得した最後のメッセージを既読済にする
		$this->db->trans_start();

		$insert_data = array (
			'message_id' => $last_message_id,
			'user_id' => $user_id,
			'room_id' => $decode_room_id
		);
		$this->db->insert('reads', $insert_data);

		$this->db->trans_complete();

		$this->output->set_json_output($data);
	}

	/**
	 * チャットに新しいメッセージを追加。
	 * POST
	 */
	public function create_message($room_hash, $user_hash) {
		// ルームＩＤをデコードする
		$room_id = $this->base64_urlsafe_decode($room_hash);

		$this->load->database();

		// 存在しないルームの場合
		$room_count = $this->db->from('rooms')->where(array (
			'room_id' => $room_id
		))->count_all_results();
		if ($room_count == 0) {
			$this->output->set_json_error_output(array('It do not exist room_id.')); return;
		}

		$body = $this->input->post('body');
		if (empty ($body)) {
			$this->output->set_json_error_output(array('Scarce value as "body".')); return;
		}

		$row = $this->db->from('users')->where(array (
			'user_hash' => $user_hash
		))->get()->row();
		if (empty ($row)) {
			$this->output->set_json_error_output(array('It do not exist user_id.')); return;
		}
		$user_id = $row->user_id;

		$this->db->trans_start();

		$insert_data = array (
			'user_id' => $user_id,
			'room_id' => $room_id,
			'body' => $body
		);
		$this->db->insert('messages', $insert_data);

		$this->db->trans_complete();

		$data = array (
			'message_id' => $this->db->insert_id()
		);

		$this->output->set_json_output($data);
	}

	/**
	 * チャットにユーザを追加。
	 * POST
	 */
	public function create_user($room_hash) {
		// ルームＩＤをデコードする
		$room_id = $this->base64_urlsafe_decode($room_hash);

		$this->load->database();

		// 存在しないルームの場合
		$room_count = $this->db->from('rooms')->where(array (
			'room_id' => $room_id
		))->count_all_results();
		if ($room_count == 0) {
			$this->output->set_json_error_output(array('It do not exist room_id.')); return;
		}

		$name = $this->input->post('name');
		if (empty ($name)) {
			$this->output->set_json_error_output(array('Scarce value as "name".')); return;
		}

		// ユーザのアイコンＩＤを設定します。（アイコンＩＤを増やしたらコンフィグの値を変更する。）
		$icon_id = rand(1, $this->config->item('icon_num'));

		$this->load->model('user');
		$this->db->trans_start();

		// 一般ユーザを生成する。
		$user_hash = $this->user->insert_user($name, $room_id, 2, $icon_id);

		$this->db->trans_complete();

		$data = array (
			'user_hash' => $user_hash
		);

		//$dataをJSONにして返す
		$this->output->set_json_output($data);
	}
}