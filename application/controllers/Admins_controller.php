<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Admins_controller extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }   

    /**
     * チャットの名前を取得
     * GET
     */
    public function select_room_get($room_hash) {
        if(!is_admin($room_hash)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('no_admin')]), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        
        if (!$this->room->exit_room($room_id)) {
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_room')]), REST_Controller::HTTP_OK); return;
        }

        $row = $this->room->select_room($room_id);

        $data = array ();
        $data['room_admin_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ADMIN), $room_data['user_id']); // 管理者ユーザで入室するためのハッシュ
        $data['room_specificuser_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::SPECIFIC_USER), 0); // 特定ユーザで入室するための周知用のハッシュ
        $data['room_anonymous_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ANONYMOUS_USER), 0); // 匿名入室するための周知用のハッシュ
        $data['name'] = $row->name;
        $data['description'] = $row->description;
        // $data['message_num'] = $this->stream_message->count_all($row->room_id);

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * FIXME: 返却地はＩＤ以外にする。
     * チャットの名前をアップデート
     * PUT
     */
    public function update_room_put($room_hash) {
        if(!is_admin($room_hash)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('no_admin')]), REST_Controller::HTTP_OK); return;
        }

        $this->form_validation->set_data($this->put());

        $this->config->load("form_validation");
        $this->form_validation->set_rules($this->config->item('update_room'));

        // form_validationを呼ぶ方法だと、検証が正しく行われない。
        if (!$this->form_validation->run()) {
            $this->set_response(error_message_format($this->form_validation->error_array()), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];

        $this->db->where('room_id', $room_id)->update('rooms', array( 
            'name'=>  $this->put('name'), 
            'description'   =>  $this->put('description'),
        ));

        // if ($res = $this->db->affected_rows() == 0) {
        //     $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_room')]), REST_Controller::HTTP_OK); return;
        // }

        $data = array (
            // 'room_id' => $room_id
        );

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * FIXME: 一旦保留にしている機能。あと名前を変える
     * チャットを削除
     * DELETE
     */
    public function delete_room_delete($room_hash) {
        if(!is_admin($room_hash)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('no_admin')]), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];

        $this->db->where('room_id', $room_id)->delete('rooms');

        if ($res = $this->db->affected_rows() == 0) {
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_room')]), REST_Controller::HTTP_OK); return;
        }
    }

    /**
     * ルームを作成する
     * POST
     */
    public function create_room_post() {
        // FIXME 不用意に生成されないような仕組みを考える
        //if(!$this->_exist_token()){ return; }
        if (!$this->form_validation->run('create_room')) {
            $this->set_response(error_message_format($this->form_validation->error_array()), REST_Controller::HTTP_OK); return;
        }

        $description = $this->input->post('description');
        $name = $this->input->post('name');

        $this->load->helper('string');
        // ランダムにキーを生成する。
        $room_key = random_string('alnum', 15);

        $room_id = $this->room->insert(array (
            'name' => $name,
            'description' => $description,
            'room_key' => $room_key,
        ));

        // ユーザIDを挿入しないのは、admin権限で突き止められるから
        // 部屋作成メッセージ
        $this->stream_message->insert_stream_message($room_id, null, new MessageType(MessageType::MAKE_ROOM));
        
        // 管理者ユーザを生成する。
        $user_id = $this->user->insert_user($this->input->post('user_name'), $room_id, new UserRole(UserRole::ADMIN), $this->input->post('fingerprint'), new SEX("1"), $this->input->post('icon'));

        $row = $this->room->select_room($room_id);

        $data = array ();
        $data['room_admin_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ADMIN), $user_id); // 管理者ユーザで入室するためのハッシュ
        $data['room_specificuser_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::SPECIFIC_USER), 0); // 特定ユーザで入室するための周知用のハッシュ
        $data['room_anonymous_hash'] = room_hash_encode($row->room_id, new UserRole(UserRole::ANONYMOUS_USER), 0); // 匿名入室するための周知用のハッシュ

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * 性別のバリデーション
     */
    public function _validate_sex($value){
        if($value === SEX::MAN || $value === SEX::WOMAN || $value === SEX::NONE){
            return TRUE;
        }
        $this->form_validation->set_message('_validate_sex', $this->lang->line('_validate_sex'));
        return FALSE;
    }

    /**
     * チャットのメンバー一覧を取得
     * GET
     */
    public function select_users_get($room_hash) {
        if(!is_admin($room_hash)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('no_admin')]), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];

        if (!$this->room->exit_room($room_id)) {
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_room')]), REST_Controller::HTTP_OK); return;
        }

        $col = $this->user->select_user_belong_room($room_id);

        $data = array ();
        foreach ($col as $row) {
            $temp_row = array ();
            $temp_row['name'] = $row->name;
            $temp_row['user_hash'] = $row->user_hash;
            $temp_row['sex'] = $row->sex;

            // FIXME 文字列情報をenumにもたせる
            if($row->user_role == UserRole::ADMIN){
                $role = 'admin';
            }else if($row->user_role == UserRole::SPECIFIC_USER){
                $role = 'specific-user';
            }else if($row->user_role == UserRole::ANONYMOUS_USER){
                $role = 'anonymous';
            }
            $temp_row['role'] = $role;

            $data[] = $temp_row;
        }

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }
}