<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Rooms_controller extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
		$this->load->library(array('encrypt', 'classLoad'));
		$this->load->helper('hash');
		$this->load->model(array('user', 'message', 'room', 'read'));
    }	

	/**
	 * ルーム一覧を返却する
	 * GET
	 */
	public function select_rooms() {
		if(!$this->exist_token()){ return; }

		$this->load->database();

		$select_results = $this->db->select('r.room_id, r.name, r.description, r.updated_at, u.user_id')->from('rooms as r')->join('users as u', 'u.room_id = r.room_id and u.user_role = 1', 'inner')->get()->result();

		$data = array ();
		foreach ($select_results as $row) {
			$temp_row = array ();
			$temp_row['room_id'] = $row->room_id;
			$temp_row['room_admin_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ADMIN), $row->user_id); // 管理者ユーザで入室するためのハッシュ
			$temp_row['room_specificuser_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::SPECIFIC_USER), 0); // 特定ユーザで入室するための周知用のハッシュ
			$temp_row['room_anonymous_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ANONYMOUS_USER), 0); // 匿名入室するための周知用のハッシュ
			$temp_row['name'] = $row->name;
			$temp_row['message_num'] = $this->db->from('messages')->where('room_id', $row->room_id)->count_all_results();
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
		$room_data = room_hash_decode($room_hash);
		$room_id = $room_data['room_id'];
		
		$this->load->database();

		$row = $this->db->from('rooms')->where('room_id', $room_id)->get()->row();

		if (empty($row)) {
			$this->output->set_json_error_output(array('No target.')); return;
		}

		$data = array ();
		$data['name'] = $row->name;
		$data['description'] = $row->description;
		$data['last_message_id'] = $this->db->select_max('message_id')->from('messages')->where('room_id', $room_id)->get()->row()->message_id;

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

		$this->db->trans_start();

		$room_id = $this->room->insert(array (
			'name' => $name,
			'description' => $description,
		));

		$admin_name = $this->config->item('admin_name');
		
		// 管理者ユーザを生成する。
		$user_id = $this->user->insert_user($admin_name, $room_id, UserRole::ADMIN, Sex::NONE, null, 0);

		$this->message->insert(array(
		   'user_id' => $user_id ,
		   'room_id' => $room_id ,
		   'body' => $name ,
		   'type' => MessageType::MAKE_ROOM , // ルーム作成
		));

		$this->db->trans_complete();

		$this->message->insert(array(
		   'user_id' => $user_id ,
		   'room_id' => $room_id ,
		   'body' => $admin_name ,
		   'type' => MessageType::INTO_ROOM , // 入室
		));

		$response_data = array (
			'room_id' => $room_id
		);

		//$dataをJSONにして返す
		$this->output->set_json_output($response_data);
	}

	/**
	 * チャットのメンバー一覧を取得(トークンがあるなしで返却値が変わります)
	 * GET
	 */
	public function select_users($room_hash) {
		$token_flag = $this->exist_token();

		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);
		$room_id = $room_data['room_id'];

		if (!$this->room->exit_room($room_id)) {
			$this->output->set_json_error_output(array('It do not exist room.')); return;
		}

		$sql_result = $this->db->from('users')->where(array ('room_id' => $room_id))->get()->result();

		$data = array ();
		foreach ($sql_result as $row) {
			$temp_row = array ();
			$temp_row['name'] = $row->name;
			// 管理人が操作した場合
			if($token_flag){
				$temp_row['room_hash'] = room_hash_encode($room_id, new UserRole((string)$row->user_role), $row->user_id);
			}

			$data[] = $temp_row;
		}

		//$dataをJSONにして返す
		$this->output->set_json_output($data);
	}

	/**
	 * チャットのメンバー情報を取得
	 * GET
	 */
	public function select_user($room_hash) {
		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);

		$room_id = $room_data['room_id'];
		$user_id = $room_data['user_id'];

		if(!$this->user->exist_user($room_id, $user_id)){
			$this->output->set_json_error_output(array('It do not exist user.')); return;
		}

		$row = $this->user->find($user_id);

		$data = array ();
		$data['name'] = $row->name;
		$data['sex'] = $row->sex;
		$data['icon'] = $row->icon_id;
		$data['message_count'] = $this->message->message_count($room_id, $user_id);
		$data['begin_message_id'] = $row->begin_message_id;
		$data['last_create_time'] = $row->created_at;

		//$dataをJSONにして返す
		$this->output->set_json_output($data);
	}

	/**
	 * チャットのメッセージ一覧を取得。前回取得分からの差分を返します。(SSE対応)
	 * 送信は直打ちとしている。Codeigniterの方法だと連続で送信できなかったりするため。
	 * GET
	 */
	public function select_messages_sse($room_hash) {
		ini_set("max_execution_time", $this->config->item('sse_succession_time'));

		header("Content-Type: text/event-stream; charset=UTF-8");
		header('Cache-Control: no-cache');
		header("Connection: keep-alive");

		// 接続状態にするため、ダミー値を返却する。
		ob_flush();
		flush();

		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);
		$room_id = $room_data['room_id'];
		$user_id = $room_data['user_id'];

		if(!$this->user->exist_user($room_id, $user_id)){
			$this->output->set_json_error_output(array('It do not exist user.')); return;
		}

		// 処理を終えないように待機させる。
		while(true){
			$col = $this->message->unread_messages($room_id, $user_id);

			if (count ($col) == 0) {
				sleep($this->config->item('sse_sleep_time'));
				continue;
			}

			// デバッグ用
			//$this->output->set_json_error_output(array($this->db->last_query())); return;

			$data = array ();
			$last_message_id = null;
			foreach ($col as $row) {
				$temp_row = array ();
				$temp_row['message_id'] = $row->message_id;
				$temp_user_info = array ();
				$temp_user_info['name'] = $row->name;
				$temp_user_info['who'] = $row->user_id == $user_id ? "self" : "other";
				$temp_user_info['icon'] = $row->icon_id;
				$temp_user_info['sex'] = $row->sex;
				$temp_user_info['hash'] = $row->user_hash;
				$temp_row['user'] = $temp_user_info;
				$temp_row['body'] = (string)$row->body;
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

			$this->read->insert(array (
				'message_id' => $last_message_id,
				'user_id' => $user_id,
				'room_id' => $room_id
			));

			$this->db->trans_complete();

			$this->output->set_sse_output('messages', $data);

			ob_flush();
			flush();
			sleep($this->config->item('sse_sleep_time'));
		}
	}

	/**
	 * チャットのメッセージ一覧を取得。前回取得分からの差分を返します。
	 * GET
	 */
	public function select_messages($room_hash) {
		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);
		$room_id = $room_data['room_id'];
		$user_id = $room_data['user_id'];

		if(!$this->user->exist_user($room_id, $user_id)){
			$this->output->set_json_error_output(array('It do not exist user.')); return;
		}

		$col = $this->message->unread_messages($room_id, $user_id);

		// デバッグ用
		//$this->output->set_json_error_output(array($this->db->last_query())); return;

		$data = array ();
		$last_message_id = null;
		foreach ($col as $row) {
			$temp_row = array ();
			$temp_row['message_id'] = $row->message_id;
			$temp_user_info = array ();
			$temp_user_info['name'] = $row->name;
			$temp_user_info['who'] = $row->user_id == $user_id ? "self" : "other";
			$temp_user_info['icon'] = $row->icon_id;
			$temp_user_info['sex'] = $row->sex;
			$temp_user_info['hash'] = $row->user_hash;
			$temp_row['user'] = $temp_user_info;
			$temp_row['body'] = (string)$row->body;
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

		$this->read->insert(array (
			'message_id' => $last_message_id,
			'user_id' => $user_id,
			'room_id' => $room_id
		));

		$this->db->trans_complete();

		$this->output->set_json_output($data);
	}

	/**
	 * チャットの指定のメッセージ一を取得。
	 * GET
	 */
	public function select_message($room_hash, $message_id) {
		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);
		$room_id = $room_data['room_id'];
		$user_id = $room_data['user_id'];

		if(!$this->user->exist_user($room_id, $user_id)){
			$this->output->set_json_error_output(array('It do not exist user.')); return;
		}

		$row = $this->message->specific_message($room_id, $message_id);

		// デバッグ用
		//$this->output->set_json_error_output(array($this->db->last_query())); return;

		$data = array ();
		if (!empty($row)) {
			$data['message_id'] = $row->message_id;
			$temp_user_info = array ();
			$temp_user_info['name'] = $row->name;
			$temp_user_info['who'] = $row->user_id == $user_id ? "self" : "other";
			$temp_user_info['icon'] = $row->icon_id;
			$temp_user_info['sex'] = $row->sex;
			$temp_user_info['hash'] = $row->user_hash;
			$data['user'] = $temp_user_info;
			$data['body'] = (string)$row->body;
			$data['type'] = $row->type;
			$data['send_time'] = $row->created_at;
		}

		$this->db->trans_complete();

		$this->output->set_json_output($data);
	}

	/**
	 * 指定メッセージより過去のメッセージを取得する。
	 * 取得数は設定値に依存する。
	 */
	public function select_messages_past($room_hash, $message_id){
		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);
		$room_id = $room_data['room_id'];
		$user_id = $room_data['user_id'];

		if(!$this->user->exist_user($room_id, $user_id)){
			$this->output->set_json_error_output(array('It do not exist user.')); return;
		}

		$col = $this->message->past_messages($room_id, $user_id, $message_id);

		// デバッグ用
		// $this->output->set_json_error_output(array($this->db->last_query())); return;

		$data = array ();
		$last_message_id = null;
		foreach ($col as $row) {
			$temp_row = array ();
			$temp_row['message_id'] = $row->message_id;
			$temp_user_info = array ();
			$temp_user_info['name'] = $row->name;
			$temp_user_info['who'] = $row->user_id == $user_id ? "self" : "other";
			$temp_user_info['icon'] = $row->icon_id;
			$temp_user_info['sex'] = $row->sex;
			$temp_user_info['hash'] = $row->user_hash;
			$temp_row['user'] = $temp_user_info;
			$temp_row['body'] = (string)$row->body;
			$temp_row['type'] = $row->type;
			$temp_row['send_time'] = $row->created_at;

			$data[] = $temp_row;
			$last_message_id = $row->message_id;
		}

		if (empty ($last_message_id)) {
			$this->output->set_json_output(array ()); return;
		}

		$this->output->set_json_output($data);
	}

	/**
	 * チャットに新しいメッセージを追加。
	 * POST
	 */
	public function create_message($room_hash) {
		$body = $this->input->post('body');
		if (empty ($body)) {
			$this->output->set_json_error_output(array('Scarce value as "body".')); return;
		}

		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);
		$room_id = $room_data['room_id'];
		$user_id = $room_data['user_id'];

		if(!$this->user->exist_user($room_id, $user_id)){
			$this->output->set_json_error_output(array('It do not exist user.')); return;
		}

		$this->db->trans_start();

		$this->message->insert_date_message($room_id);

		$message_id = $this->message->insert(array (
			'user_id' => $user_id,
			'room_id' => $room_id,
			'body' => $body
		));

		$this->db->trans_complete();

		$row = $this->message->specific_message($room_id, $message_id);

		// デバッグ用
		//$this->output->set_json_error_output(array($this->db->last_query())); return;

		$data = array ();
		if (!empty($row)) {
			$data['message_id'] = $row->message_id;
			$temp_user_info = array ();
			$temp_user_info['name'] = $row->name;
			$temp_user_info['who'] = $row->user_id == $user_id ? "self" : "other";
			$temp_user_info['icon'] = $row->icon_id;
			$temp_user_info['sex'] = $row->sex;
			$temp_user_info['hash'] = $row->user_hash;
			$data['user'] = $temp_user_info;
			$data['body'] = (string)$row->body;
			$data['type'] = $row->type;
			$data['send_time'] = $row->created_at;
		}

		$this->output->set_json_output($data);
	}

	/**
	 * FIXME モデル系のリファクタリングを行う。
	 * チャットにユーザを追加。
	 * POST
	 */
	public function create_user($room_hash) {
		$name = $this->input->post('name');
		if (empty ($name)) {
			$this->output->set_json_error_output(array('Scarce value as "name".')); return;
		}

		$fingerprint = $this->input->post('fingerprint');
		if (empty ($fingerprint)) {
			$this->output->set_json_error_output(array('Scarce value as "fingerprint".')); return;
		}

		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);
		$room_id = $room_data['room_id'];

		if (empty($room_id)) {
			$this->output->set_json_error_output(array('It do not exist room_id.')); return;
		}

		$role = (String)$room_data['role'];

		if($role === UserRole::ADMIN) { // 管理人ハッシュで生成しようとした場合
			$this->output->set_json_error_output(array('This is hash of admin. It is not admit to create other user.')); return;
		} else if(!in_array($role, array(UserRole::SPECIFIC_USER, UserRole::ANONYMOUS_USER)) || $room_data['user_id'] !== '0') { // 特定ユーザ、アノニマスユーザ以外が指定
			$this->output->set_json_error_output(array('This is not right hash.')); return;
		}

		// 存在しないルームの場合
		if (!$this->room->exit_room($room_id)) {
			$this->output->set_json_error_output(array('It do not exist room.')); return;
		}

		if($this->user->duplicate_user($room_id, $fingerprint)){
			$this->output->set_json_error_output(array('User already exist.')); return;
		}

		if($role === '2'){ // 特定ユーザ
			$this->output->set_json_output($this->create_specific_user($room_id, $name)); return;
		} else { // アノニマスユーザ
			$this->output->set_json_output($this->create_anonymous_user($room_id, $name)); return;
		}
	}

	/**
	 * チャットに特定ユーザを追加。
	 * POST
	 */
	private function create_specific_user($room_id, $name) {
		$icon_id = $this->input->post('icon');
		if(empty($icon_id)){
			// ユーザのアイコンＩＤを設定します。（アイコンＩＤを増やしたらコンフィグの値を変更する。）
			$icon_id = rand(1, $this->config->item('icon_num'));
		}

		$this->db->trans_start();

		// 特定ユーザを生成する。
		$user_id = $this->user->insert_user($name, $room_id, new UserRole(UserRole::SPECIFIC_USER), $this->input->post('fingerprint'), new UserRole($this->input->post('sex')), $icon_id);

		$this->db->trans_complete();

		$data = array (
			'room_hash' => room_hash_encode($room_id, new UserRole(UserRole::SPECIFIC_USER), $user_id), // ユーザ専用のハッシュ値を生成する
			'user_hash' => $this->user->find($user_id)->user_hash
		);

		return $data;
	}

	/**
	 * チャットにユーザを追加。
	 * POST
	 */
	private function create_anonymous_user($room_id, $name) {
		// ユーザのアイコンＩＤを設定します。（アノニマスアイコン）
		$icon_id = 999;

		$this->db->trans_start();

		// アノニマスユーザを生成する。
		$user_id = $this->user->insert_user($name, $room_id, new UserRole(UserRole::ANONYMOUS_USER), $this->input->post('fingerprint'), new UserRole($this->input->post('sex')),$icon_id);

		$this->db->trans_complete();

		$data = array (
			'room_hash' => room_hash_encode($room_id, new UserRole(UserRole::ANONYMOUS_USER), $user_id), // ユーザ専用のハッシュ値を生成する
			'user_hash' => $this->user->find($user_id)->user_hash
		);

		return $data;
	}
}