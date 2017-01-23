<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Rooms_controller extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }   

    /**
     * チャットの名前を取得
     * GET
     */
    public function select_room_get($room_hash) {
        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        
        $this->load->database();

        if (!$this->room->exit_room($room_id)) {
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_room')]), REST_Controller::HTTP_OK); return;
        }

        $row = $this->room->select_room($room_id);

        $data = array ();
        $data['name'] = $row->name;
        $data['description'] = $row->description;
        $data['last_message_id'] = $this->stream_message->max_message_id($room_id);

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットのメンバー一覧を取得(トークンがあるなしで返却値が変わります)
     * GET
     */
    public function select_users_get($room_hash) {
        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];

        if (!$this->room->exit_room($room_id)) {
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_room')]), REST_Controller::HTTP_OK); return;
        }

        $sql_result = $this->db->from('users')->where(array ('room_id' => $room_id))->get()->result();

        $data = array ();
        foreach ($sql_result as $row) {
            $temp_row = array ();
            $temp_row['name'] = $row->name;

            $data[] = $temp_row;
        }

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットのメンバー情報を取得
     * GET
     */
    public function select_user_get($room_hash) {
        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);

        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $row = $this->user->find($user_id);

        $data = array ();
        $data['name'] = $row->name;
        $data['sex'] = $row->sex;
        $data['icon'] = $row->icon_name;
        $data['user_hash'] = $row->user_hash;
        $data['message_count'] = $this->stream_message->message_count($room_id, $user_id);
        $data['begin_message_id'] = $row->begin_message_id;
        $data['last_create_time'] = $row->created_at;

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットのメッセージ一覧を取得。前回取得分からの差分を返します。
     * GET
     */
    public function select_messages_get($room_hash) {
        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $col = $this->stream_message->unread_messages($room_id, $user_id);

        // デバッグ用
        //$this->set_response([$this->db->last_query()], REST_Controller::HTTP_OK); return;

        $data = array ();
        $last_message_id = null;
        foreach ($col as $row) {
            $temp_row = array ();
            $temp_row['message_id'] = $row->message_id;
            $temp_user_info = array ();
            $temp_user_info['name'] = $row->name;
            $temp_user_info['who'] = $row->user_id == $user_id ? UserWho::SELF_USER : UserWho::OTHER_USER;
            $temp_user_info['icon'] = $row->icon_name;
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
            $this->set_response([], REST_Controller::HTTP_OK); return;
        }

        // 取得した最後のメッセージを既読済にする
        $this->db->trans_start();

        $this->read_message->insert(array (
            'message_id' => $last_message_id,
            'user_id' => $user_id,
            'room_id' => $room_id
        ));

        $this->db->trans_complete();

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットの指定のメッセージ一を取得。
     * GET
     */
    public function select_message_get($room_hash, $message_id) {
        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $row = $this->stream_message->specific_message($room_id, $message_id);

        // デバッグ用
        // $this->set_response(error_message_format([$this->db->last_query()]), REST_Controller::HTTP_OK); return;

        $data = array ();
        if (!empty($row)) {
            $data['message_id'] = $row->message_id;
            $temp_user_info = array ();
            $temp_user_info['name'] = $row->name;
            $temp_user_info['who'] = $row->user_id == $user_id ? UserWho::SELF_USER : UserWho::OTHER_USER;
            $temp_user_info['icon'] = $row->icon_name;
            $temp_user_info['sex'] = $row->sex;
            $temp_user_info['hash'] = $row->user_hash;
            $data['user'] = $temp_user_info;
            $data['body'] = (string)$row->body;
            $data['type'] = $row->type;
            $data['send_time'] = $row->created_at;
        }

        $this->db->trans_complete();

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * 指定メッセージより過去のメッセージを取得する。
     * 取得数は設定値に依存する。
     * GET
     */
    public function select_messages_past_get($room_hash, $message_id){
        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $col = $this->stream_message->past_messages($room_id, $user_id, $message_id);

        // デバッグ用
        // $this->set_response(error_message_format([$this->db->last_query()]), REST_Controller::HTTP_OK); return;

        $data = array ();
        $last_message_id = null;
        foreach ($col as $row) {
            $temp_row = array ();
            $temp_row['message_id'] = $row->message_id;
            $temp_user_info = array ();
            $temp_user_info['name'] = $row->name;
            $temp_user_info['who'] = $row->user_id == $user_id ? UserWho::SELF_USER : UserWho::OTHER_USER;
            $temp_user_info['icon'] = $row->icon_name;
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
            $this->set_response([], REST_Controller::HTTP_OK); return;
        }

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットに新しいメッセージを追加。
     * POST
     */
    public function create_message_post($room_hash) {
        if (!$this->form_validation->run('create_message')) {
            $this->set_response(error_message_format($this->form_validation->error_array()), REST_Controller::HTTP_OK); return;
        }

        $body = $this->input->post('body');

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];
        $role = $room_data['role'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }
        // 20170123追加
        // 匿名ユーザの場合
        if($role == UserRole::ANONYMOUS_USER){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('anonymous_user_not_say')]), REST_Controller::HTTP_OK); return;
        }

        $message_id = $this->stream_message->insert_user_message($room_id, $user_id, $body);

        $row = $this->stream_message->specific_message($room_id, $message_id);

        // デバッグ用
        // $this->set_response([$message_id, $this->db->last_query()], REST_Controller::HTTP_OK); return;

        $data = array ();
        if (!empty($row)) {
            $data['message_id'] = $row->message_id;
            $temp_user_info = array ();
            $temp_user_info['name'] = $row->name;
            $temp_user_info['who'] = $row->user_id == $user_id ? UserWho::SELF_USER : UserWho::OTHER_USER;
            $temp_user_info['icon'] = $row->icon_name;
            $temp_user_info['sex'] = $row->sex;
            $temp_user_info['hash'] = $row->user_hash;
            $data['user'] = $temp_user_info;
            $data['body'] = (string)$row->body;
            $data['type'] = $row->type;
            $data['send_time'] = $row->created_at;
        }

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットにユーザを追加。
     * POST
     */
    public function create_user_post($room_hash) {
        if($this->post('method') == 'PUT'){ $this->update_user_put($room_hash); return; }

        if (!$this->form_validation->run('create_user')) {
            $this->set_response(error_message_format($this->form_validation->error_array()), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];

        if (empty($room_id)) {
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_room')]), REST_Controller::HTTP_OK); return;
        }

        $role = (string)$room_data['role'];

        if($role === UserRole::ADMIN) { // 管理人ハッシュで生成しようとした場合
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('is_admin')]), REST_Controller::HTTP_OK); return;
        } else if(!in_array($role, [UserRole::SPECIFIC_USER, UserRole::ANONYMOUS_USER]) || $room_data['user_id'] !== '0') { // 特定ユーザ、アノニマスユーザ以外が指定、ユーザＩＤが既に指定されている
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('wrong_hash')]), REST_Controller::HTTP_OK); return;
        }

        if($this->user->duplicate_user($room_id, $this->input->post('fingerprint'))){
            $this->set_response(error_message_format(['fingerprint' => $this->lang->line('exist_user_already')]), REST_Controller::HTTP_OK); return;
        }

        if($role === '2'){ // 特定ユーザ
            $this->set_response($this->_create_specific_user($room_id, $this->input->post('name')), REST_Controller::HTTP_OK); return;
        } else { // アノニマスユーザ
            $this->set_response($this->_create_anonymous_user($room_id, $this->input->post('name')), REST_Controller::HTTP_OK); return;
        }
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
     * チャットに特定ユーザを追加。
     */
    private function _create_specific_user($room_id, $name) {
        $icon_id = $this->input->post('icon');
        if(empty($icon_id)){
            // ユーザのアイコンＩＤを設定します。（アイコンＩＤを増やしたらコンフィグの値を変更する。）
            $icon_id = rand(1, $this->config->item('icon_num'));
        }

        $this->db->trans_start();

        // 特定ユーザを生成する。
        $user_id = $this->user->insert_user($name, $room_id, new UserRole(UserRole::SPECIFIC_USER), $this->input->post('fingerprint'), new SEX($this->input->post('sex')), $icon_id);

        $this->db->trans_complete();

        $data = array (
            'room_hash' => room_hash_encode($room_id, new UserRole(UserRole::SPECIFIC_USER), $user_id), // ユーザ専用のハッシュ値を生成する
            'user_hash' => $this->user->find($user_id)->user_hash
        );

        return $data;
    }

    /**
     * チャットにユーザを追加。
     */
    private function _create_anonymous_user($room_id, $name) {
        // ユーザのアイコンＩＤを設定します。（アノニマスアイコン）
        $icon_id = 999;

        $this->db->trans_start();

        // アノニマスユーザを生成する。
        $user_id = $this->user->insert_user($name, $room_id, new UserRole(UserRole::ANONYMOUS_USER), $this->input->post('fingerprint'), new SEX($this->input->post('sex')),$icon_id);

        $this->db->trans_complete();

        $data = array (
            'room_hash' => room_hash_encode($room_id, new UserRole(UserRole::ANONYMOUS_USER), $user_id), // ユーザ専用のハッシュ値を生成する
            'user_hash' => $this->user->find($user_id)->user_hash
        );

        return $data;
    }

    /**
     * ユーザ情報を更新する。
     * POSTからの分岐もあり得るので注意する。
     * PUT
     */
    public function update_user_put($room_hash) {
        $this->form_validation->set_data($this->post());

        $valid_config = array(
                array(
                        'field' => 'name',
                        'label' => '名前',
                        'rules' => 'required|max_length[10]'
                ),
                array(
                        'field' => 'sex',
                        'label' => '性別',
                        'rules' => 'required|callback__validate_sex'
                ),
        );

        $this->form_validation->set_rules($valid_config);

        // form_validationを呼ぶ方法だと、検証が正しく行われない。
        if (!$this->form_validation->run()) {
            $this->set_response(error_message_format($this->form_validation->error_array()), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];

        if (empty($room_id)) {
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_room')]), REST_Controller::HTTP_OK); return;
        }

        $role = (string)$room_data['role'];

        if($role === UserRole::ADMIN) { // 管理人ハッシュで生成しようとした場合
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('is_admin')]), REST_Controller::HTTP_OK); return;
        } else if(!in_array($role, [UserRole::SPECIFIC_USER, UserRole::ANONYMOUS_USER]) || $room_data['user_id'] === '0') { // 特定ユーザ、アノニマスユーザ以外が指定、登録されていないユーザ
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('wrong_hash')]), REST_Controller::HTTP_OK); return;
        }

        $user_id = $room_data['user_id'];

        $this->user->update($user_id, [
            'name'=>  $this->post('name'),
            'sex'   =>  $this->post('sex'),
            'icon_name' => $this->post('icon'),
        ]);

        $this->set_response(['room_hash' => $room_hash], REST_Controller::HTTP_OK); return;
    }
}