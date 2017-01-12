<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Admins_controller extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        
        $this->lang->load('form_validation');
        $this->load->library(array('form_validation', 'encrypt', 'classLoad'));
        $this->load->helper('hash');
        $this->load->model(array('user', 'stream_message', 'user_message', 'info_message', 'room', 'read'));
    }   

    /**
     * この機能は使う予定がない
     * ルーム一覧を返却する
     * GET
     */
    public function select_rooms() {
        if(!$this->_exist_token()){ return; }

        $this->load->database();

        $select_results = $this->room->select_rooms();

        $data = array ();
        foreach ($select_results as $row) {
            $temp_row = array ();
            $temp_row['room_id'] = $row->room_id;
            $temp_row['room_admin_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ADMIN), $row->user_id); // 管理者ユーザで入室するためのハッシュ
            $temp_row['room_specificuser_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::SPECIFIC_USER), 0); // 特定ユーザで入室するための周知用のハッシュ
            $temp_row['room_anonymous_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ANONYMOUS_USER), 0); // 匿名入室するための周知用のハッシュ
            $temp_row['name'] = $row->name;
            $temp_row['message_num'] = $this->db->from('stream_messages')->where('room_id', $row->room_id)->count_all_results();
            $temp_row['last_update_time'] = $row->updated_at;

            $data[] = $temp_row;
        }

        $this->output->set_json_output($data);
    }

    /**
     * チャットの名前を取得
     * GET
     */
    public function select_room($room_hash) {
        if(!$this->_is_admin($room_hash)){ return; }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        
        if (!$this->room->exit_room($room_id)) {
            $this->output->set_json_error_output(array('room_hash' => $this->lang->line('exist_room'))); return;
        }

        $row = $this->room->select_room($room_id);

        $data = array ();
        $data['room_admin_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ADMIN), $room_data['user_id']); // 管理者ユーザで入室するためのハッシュ
        $data['room_specificuser_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::SPECIFIC_USER), 0); // 特定ユーザで入室するための周知用のハッシュ
        $data['room_anonymous_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ANONYMOUS_USER), 0); // 匿名入室するための周知用のハッシュ
        $data['name'] = $row->name;
        $data['description'] = $row->description;
        $data['message_num'] = $this->db->from('stream_messages')->where('room_id', $row->room_id)->count_all_results();

        $this->output->set_json_output($data);
    }

    /**
     * FIXME: 一旦保留にしている機能
     * チャットの名前をアップデート
     * PUT
     */
    public function update_room($room_hash) {
        if(!$this->_is_admin($room_hash)){ return; }

        $description = $this->input->input_stream('description');

        if (!$this->form_validation->run('update_room')) {
            $this->output->set_json_error_output($this->form_validation->error_array()); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];

        $this->db->where('room_id', $room_id)->update('rooms', array( 
            'name'=>  $this->input->input_stream('name'), 
            'description'   =>  $description
        ));

        if ($res = $this->db->affected_rows() === 0) {
            $this->output->set_json_error_output(array('room_hash' => $this->lang->line('exist_room'))); return;
        }

        $data = array (
            'room_id' => $room_id
        );

        //$dataをJSONにして返す
        $this->output->set_json_output($data);
    }

    /**
     * FIXME: 一旦保留にしている機能
     * チャットを削除
     * DELETE
     */
    public function delete_room($room_hash) {
        if(!$this->_is_admin($room_hash)){ return; }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];

        $this->db->where('room_id', $room_id)->delete('rooms');

        if ($res = $this->db->affected_rows() === 0) {
            $this->output->set_json_error_output(array('room_hash' => $this->lang->line('exist_room'))); return;
        }
    }

    /**
     * ルームを作成する
     * POST
     */
    public function create_room() {
        // FIXME 不用意に生成されないような仕組みを考える
        //if(!$this->_exist_token()){ return; }
        
        if (!$this->form_validation->run('create_room')) {
            $this->output->set_json_error_output($this->form_validation->error_array()); return;
        }

        $description = $this->input->post('description');
        $name = $this->input->post('name');

        $room_id = $this->room->insert(array (
            'name' => $name,
            'description' => $description,
        ));

        $admin_name = $this->config->item('admin_name');

        // 部屋作成メッセージ
        $this->stream_message->insert_info_message($room_id, $name, new MessageType(MessageType::MAKE_ROOM));
        
        // 管理者ユーザを生成する。
        $user_id = $this->user->insert_user($admin_name, $room_id, new UserRole(UserRole::ADMIN), null, new Sex(Sex::NONE), 0);

        $row = $this->room->select_room($room_id);

        $data = array ();
        $data['room_admin_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ADMIN), $user_id); // 管理者ユーザで入室するためのハッシュ

        $this->output->set_json_output($data);
        return;
    }

    /**
     * チャットのメンバー一覧を取得(トークンがあるなしで返却値が変わります)
     * GET
     */
    public function select_users($room_hash) {
        if(!$this->_is_admin($room_hash)){ return; }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];

        if (!$this->room->exit_room($room_id)) {
            $this->output->set_json_error_output(array('room_hash' => $this->lang->line('exist_room'))); return;
        }

        $col = $this->db->from('users')->where(array ('room_id' => $room_id))->get()->result();

        $data = array ();
        foreach ($col as $row) {
            $temp_row = array ();
            $temp_row['name'] = $row->name;
            $temp_row['user_hash'] = $row->user_hash;
            $temp_row['sex'] = $row->sex;

            $data[] = $temp_row;
        }

        //$dataをJSONにして返す
        $this->output->set_json_output($data);
    }
}