<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Rooms_controller extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
		
		$this->load->library('encrypt');
		$this->load->helper('hash');
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
			$temp_row['room_admin_hash'] = room_hash_encode($row->room_id, 1, $row->user_id); // 管理者ユーザで入室するためのハッシュ
			$temp_row['room_specificuser_hash'] = room_hash_encode($row->room_id, 2, 0); // 特定ユーザで入室するための周知用のハッシュ
			$temp_row['room_anonymous_hash'] = room_hash_encode($row->room_id, 3, 0); // 匿名入室するための周知用のハッシュ
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
		$user_id = $this->user->insert_user($admin_name, $room_id, 1, 0, null, 0);

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

		$this->load->database();

		// 存在しないルームの場合
		if ($this->db->from('rooms')->where(array ('room_id' => $room_id))->count_all_results() == 0) {
			$this->output->set_json_error_output(array('It do not exist room.')); return;
		}

		$sql_result = $this->db->from('users')->where(array (
			'room_id' => $room_id
		))->get()->result();

		$data = array ();
		foreach ($sql_result as $row) {
			$temp_row = array ();
			$temp_row['name'] = $row->name;
			// 管理人が操作した場合
			if($token_flag){
				$temp_row['room_hash'] = room_hash_encode($room_id, $row->user_role, $row->user_id);
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

		$this->load->database();

		$room_id = $room_data['room_id'];
		// 存在しないルームの場合
		if ($this->db->from('rooms')->where(array ('room_id' => $room_id))->count_all_results() == 0) {
			$this->output->set_json_error_output(array('It do not exist room.')); return;
		}

		$user_id = $room_data['user_id'];
		// 存在しないユーザの場合
		$row = $this->db->from('users')->where(array ('user_id' => $user_id))->get()->row();
		if (empty ($row)) {
			$this->output->set_json_error_output(array('It do not exist user.')); return;
		}

		$message_count = $this->db->from('messages')->where(array('room_id' => $room_id, 'user_id' => $user_id))->count_all_results();

		$data = array ();
		$data['name'] = $row->name;
		$data['sex'] = $row->sex;
		$data['icon'] = $row->icon_id;
		$data['message_count'] = $message_count;
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

		$this->load->library('encrypt');

		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);

		$this->load->database();

		$room_id = $room_data['room_id'];
		// 存在しないルームの場合
		if ($this->db->from('rooms')->where(array ('room_id' => $room_id))->count_all_results() == 0) {
			$this->output->set_sse_error_output(array('It do not exist room.')); return;
		}

		$user_id = $room_data['user_id'];
		// 存在しないユーザの場合
		$row = $this->db->from('users')->where(array (
			'user_id' => $user_id
		))->get()->row();
		if (empty ($row)) {
			$this->output->set_sse_error_output(array('It do not exist user.')); return;
		}

		$begin_message_id = $row->begin_message_id;

		// 処理を終えないように待機させる。
		while(true){

			// 既読済のメッセージIDを返却する
			$sql = 'select COALESCE(max(r.message_id), 0) as last_read_message_id from messages m inner join reads r on r.user_id = ? and m.user_id = r.user_id;';

			$query_result = $this->db->query($sql, array (
				$user_id,
			))->row();

			$last_read_message_id = $query_result->last_read_message_id;

			$last_read_message_id = $last_read_message_id < $begin_message_id ? $begin_message_id : $last_read_message_id;

			$select_results = $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')->from('messages as m')->join('users as u', 'u.user_id = m.user_id', 'inner')->where(array (
				'm.room_id' => $room_id,
				'm.message_id >' => $last_read_message_id,
				'm.user_id <>' => $user_id
			))->get()->result();

			// デバッグ用
			//$this->output->set_json_error_output(array($this->db->last_query())); return;

			if (count ($select_results) == 0) {
				sleep($this->config->item('sse_sleep_time'));
				continue;
			}

			$data = array ();
			$last_message_id = null;
			foreach ($select_results as $row) {
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

			// 取得した最後のメッセージを既読済にする
			$this->db->trans_start();

			$insert_data = array (
				'message_id' => $last_message_id,
				'user_id' => $user_id,
				'room_id' => $room_id
			);
			$this->db->insert('reads', $insert_data);

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
		$this->load->library('encrypt');

		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);

		$this->load->database();

		$room_id = $room_data['room_id'];
		// 存在しないルームの場合
		if ($this->db->from('rooms')->where(array ('room_id' => $room_id))->count_all_results() == 0) {
			$this->output->set_json_error_output(array('It do not exist room.')); return;
		}

		$user_id = $room_data['user_id'];
		// 存在しないユーザの場合
		$row = $this->db->from('users')->where(array (
			'user_id' => $user_id
		))->get()->row();
		if (empty ($row)) {
			$this->output->set_json_error_output(array('It do not exist user.')); return;
		}

		$begin_message_id = $row->begin_message_id;

		// 既読済のメッセージIDを返却する
		$sql = 'select COALESCE(max(r.message_id), 0) as last_read_message_id from messages m inner join reads r on r.user_id = ? and m.user_id = r.user_id;';

		$query_result = $this->db->query($sql, array (
			$user_id,
		))->row();

		$last_read_message_id = $query_result->last_read_message_id;

		$last_read_message_id = $last_read_message_id < $begin_message_id ? $begin_message_id : $last_read_message_id;

		$select_results = $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')->from('messages as m')->join('users as u', 'u.user_id = m.user_id', 'inner')->where(array (
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
	 * チャットの指定のメッセージ一を取得。
	 * GET
	 */
	public function select_message($room_hash, $message_id) {
		$this->load->library('encrypt');

		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);

		$this->load->database();

		$room_id = $room_data['room_id'];
		// 存在しないルームの場合
		if ($this->db->from('rooms')->where(array ('room_id' => $room_id))->count_all_results() == 0) {
			$this->output->set_json_error_output(array('It do not exist room.')); return;
		}

		$user_id = $room_data['user_id'];
		// 存在しないユーザの場合
		$row = $this->db->from('users')->where(array (
			'user_id' => $user_id
		))->get()->row();
		if (empty ($row)) {
			$data = array (
				'error' => 'It do not exist user_id.'
			);
			$this->output->set_json_output($data);
			return;
		}

		$row = $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')->from('messages as m')->join('users as u', 'u.user_id = m.user_id', 'inner')->where(array (
			'm.message_id' => $message_id,
			'm.room_id' => $room_id,
		))->get()->row();

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
		$this->load->library('encrypt');

		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);

		$this->load->database();

		$room_id = $room_data['room_id'];
		// 存在しないルームの場合
		if ($this->db->from('rooms')->where(array ('room_id' => $room_id))->count_all_results() == 0) {
			$this->output->set_json_error_output(array('It do not exist room.')); return;
		}

		$user_id = $room_data['user_id'];
		// 存在しないユーザの場合
		$row = $this->db->from('users')->where(array (
			'user_id' => $user_id
		))->get()->row();
		if (empty ($row)) {
			$data = array (
				'error' => 'It do not exist user_id.'
			);
			$this->output->set_json_output($data);
			return;
		}

		$begin_message_id = $row->begin_message_id;

		$past_message_max_count = $this->config->item('past_message_max_count');
		$massage_count = $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')->from('messages as m')->join('users as u', 'u.user_id = m.user_id', 'inner')->where(array (
			'm.room_id' => $room_id,
			'm.message_id>' => $begin_message_id,
			'm.message_id<' => $message_id,
		))->count_all_results();		
		$massage_offset = $massage_count > $past_message_max_count ? $massage_count - $past_message_max_count : 0;

		/**
		 * FIXME 取得件数を変更できるようにする
		 */
		$select_results = $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')->from('messages as m')->join('users as u', 'u.user_id = m.user_id', 'inner')->where(array (
			'm.room_id' => $room_id,
			'm.message_id>' => $begin_message_id,
			'm.message_id<' => $message_id,
		))->limit($past_message_max_count)->offset($massage_offset)->get()->result();

		// デバッグ用
		// $this->output->set_json_error_output(array($this->db->last_query())); return;

		$data = array ();
		$last_message_id = null;
		foreach ($select_results as $row) {
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

		$this->load->database();

		$room_id = $room_data['room_id'];
		// 存在しないルームの場合
		if ($this->db->from('rooms')->where(array ('room_id' => $room_id))->count_all_results() == 0) {
			$this->output->set_json_error_output(array('It do not exist room.')); return;
		}

		$user_id = $room_data['user_id'];
		// 存在しないユーザの場合
		if ($this->db->from('users')->where(array ('user_id' => $user_id))->count_all_results() == 0) {
			$this->output->set_json_error_output(array('It do not exist user.')); return;
		}

		$this->db->trans_start();

		$row = $this->db->select('datetime(CURRENT_TIMESTAMP) as now_date, created_at as last_message_date')->from('messages')->where(array ('room_id' => $room_id))->order_by('message_id', 'DESC')->get()->row();

		$this->load->helper('date');
		
		// 最終メッセージと日付が変わっていた場合
		if(compare_date($row->now_date, $row->last_message_date)){
			$insert_data = array (
				'user_id' => 0,
				'room_id' => $room_id,
				'body' => $now_date + get_days($row->now_date),
				'type' => 4, // 日付
			);
			$this->db->insert('messages', $insert_data);
		}

		$insert_data = array (
			'user_id' => $user_id,
			'room_id' => $room_id,
			'body' => $body
		);
		$this->db->insert('messages', $insert_data);

		$this->db->trans_complete();

		$row = $this->db->select('m.message_id, u.name, u.user_id, u.icon_id, u.sex, u.user_hash, m.body, m.type, m.created_at')->from('messages as m')->join('users as u', 'u.user_id = m.user_id', 'inner')->where(array (
			'm.message_id' => $this->db->insert_id(),
			'm.room_id' => $room_id,
		))->get()->row();

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
	 * チャットにユーザを追加。
	 * POST
	 */
	public function create_user($room_hash) {
		// ルームＩＤをデコードする
		$room_data = room_hash_decode($room_hash);
		$room_id = $room_data['room_id'];

		if (empty($room_id)) {
			$this->output->set_json_error_output(array('It do not exist room_id.')); return;
		}

		$role = $room_data['role'];

		if($role === '1') { // 管理人ハッシュで生成しようとした場合
			$this->output->set_json_error_output(array('This is hash of admin. It is not admit to create other user.')); return;
		} else if(!in_array($role, array('2', '3')) || $room_data['user_id'] !== '0') { // 特定ユーザ、アノニマスユーザ以外が指定
			$this->output->set_json_error_output(array('This is not right hash.')); return;
		}
		
		$this->load->database();

		// 存在しないルームの場合
		if ($this->db->from('rooms')->where(array ('room_id' => $room_id))->count_all_results() == 0) {
			$this->output->set_json_error_output(array('It do not exist room_id.')); return;
		}

		$name = $this->input->post('name');
		if (empty ($name)) {
			$this->output->set_json_error_output(array('Scarce value as "name".')); return;
		}

		$select_count = $this->db->select('*')->from('users')->where(array (
			'room_id' => $room_id,
			'fingerprint' => $this->input->post('fingerprint'),
		))->count_all_results();

		if($select_count > 0){
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
		$sex = $this->input->post('sex');
		if(empty($icon_id)){
			// ユーザのアイコンＩＤを設定します。（アイコンＩＤを増やしたらコンフィグの値を変更する。）
			$icon_id = rand(1, $this->config->item('icon_num'));
		}

		$this->load->model('user');
		$this->db->trans_start();

		// 特定ユーザを生成する。
		$user_id = $this->user->insert_user($name, $room_id, 2, $this->input->post('fingerprint'), $sex, $icon_id);

		$select_result = $this->db->select('*')->from('users')->where(array (
			'user_id' => $user_id,
		))->get()->row();

		$this->db->trans_complete();

		$data = array (
			'room_hash' => room_hash_encode($room_id, 2, $user_id), // ユーザ専用のハッシュ値を生成する
			'user_hash' => $select_result->user_hash
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

		$this->load->model('user');
		$this->db->trans_start();

		// アノニマスユーザを生成する。
		$user_id = $this->user->insert_user($name, $room_id, 3, $this->input->post('fingerprint'), $this->input->post('sex'),$icon_id);

		$select_result = $this->db->select('user_hash')->from('users')->where(array (
			'user_id' => $user_id,
		))->get()->row();

		$this->db->trans_complete();

		$data = array (
			'room_hash' => room_hash_encode($room_id, 3, $user_id), // ユーザ専用のハッシュ値を生成する
			'user_hash' => $select_result->user_hash
		);

		return $data;
	}
}