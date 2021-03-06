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
        $data['key'] = $row->room_key;
        $data['last_message_id'] = $this->stream_message->max_message_id($room_id);
        $data['room_specificuser_hash'] = room_hash_encode($room_id, new UserRole(UserRole::SPECIFIC_USER), 0); // 特定ユーザで入室するための周知用のハッシュ

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
        if($row->user_role == UserRole::ADMIN){
            $role = 'admin';
        }else if($row->user_role == UserRole::SPECIFIC_USER){
            $role = 'specific-user';
        }else if($row->user_role == UserRole::ANONYMOUS_USER){
            $role = 'anonymous';
        }
        $data['role'] = $role;
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

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];
        $role = $room_data['role'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        // 匿名ユーザは除外
        if($role == UserRole::ANONYMOUS_USER){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('anonymous_user_not_say')]), REST_Controller::HTTP_OK); return;
        }

        $body = $this->input->post('body');

        $message_id = $this->user_message->insert_user_message($room_id, $user_id, $body);

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
     * チャットに新しいイメージを追加。
     * POST
     */
    public function create_image_post($room_hash) {
        if (!$this->form_validation->run('create_image')) {
            $this->set_response(error_message_format($this->form_validation->error_array()), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];
        $role = $room_data['role'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        // 匿名ユーザは除外
        if($role == UserRole::ANONYMOUS_USER){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('anonymous_user_not_say')]), REST_Controller::HTTP_OK); return;
        }

        //　imageを取得
        $message_id = $this->post_image->insert_post_image($room_id, $user_id, $this->input->post('path'));

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
        // TODO: 前までこのルートが発生していたようだが、今は確認できない。
        // if($this->post('method') == 'PUT'){ $this->update_user_put($room_hash); return; }

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
        $this->form_validation->set_data($this->put());

        $this->config->load("form_validation");
        $this->form_validation->set_rules($this->config->item('update_user'));

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

        if($room_data['user_id'] === '0') { // 登録されていないユーザ
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('wrong_hash')]), REST_Controller::HTTP_OK); return;
        }

        $user_id = $room_data['user_id'];

        $this->user->update($user_id, [
            'name'=>  $this->put('name'),
            'sex'   =>  $this->put('sex'),
            'icon_name' => $this->put('icon'),
        ]);

        $this->set_response(['room_hash' => $room_hash], REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットのメンバー一覧を取得
     * 今の所返却値はAdminと変わらない。
     * GET
     */
    public function select_users_get($room_hash) {
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

    /**
     * イメージを取得。
     * GET
     */
    public function select_images_get($room_hash) {
        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $col = $this->post_image->select_post_images($room_id, $user_id);

        // デバッグ用
        // $this->set_response([$message_id, $this->db->last_query()], REST_Controller::HTTP_OK); return;

        $data = array ();
        foreach ($col as $row) {
            $temp_row = array ();
            $temp_row['message_id'] = $row->message_id;
            $temp_row['content'] = $row->path;
            $temp_row['user_name'] = $row->name;
            $temp_row['user_hash'] = $row->user_hash;
            $temp_row['created_at'] = $row->created_at;

            $data[] = $temp_row;
        }

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットのノートを取得する。
     * GET
     */
    public function select_notes_get($room_hash) {
        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $cols = $this->note->select_notes($room_id);

        $data = array ();
        foreach ($cols as $row) {
            $temp_row = array ();
            $temp_row['note_id'] = $row->note_id;
            $temp_row['content'] = $row->content;
            $temp_row['user_name'] = $row->name;
            $temp_row['user_hash'] = $row->user_hash;
            $temp_row['updated_at'] = $row->updated_at;

            $data[] = $temp_row;
        }

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットに新しいノートを追加する。
     * GET
     */
    public function create_note_post($room_hash) {
        if (!$this->form_validation->run('create_note')) {
            $this->set_response(error_message_format($this->form_validation->error_array()), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $note_id = $this->note->insert_note($room_id, $user_id, $this->post('content'));

        $row = $this->note->select_note($note_id);

        $data = array ();
        $data['note_id'] = $row->note_id;
        $data['content'] = $row->content;
        $data['user_name'] = $row->name;
        $data['user_hash'] = $row->user_hash;
        $data['updated_at'] = $row->updated_at;

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットのノートを更新する。
     * PUT
     */
    public function update_note_put($room_hash, $note_id) {
        $this->form_validation->set_data($this->put());

        $this->config->load("form_validation");
        $this->form_validation->set_rules($this->config->item('update_note'));

        // form_validationを呼ぶ方法だと、検証が正しく行われない。
        if (!$this->form_validation->run()) {
            $this->set_response(error_message_format($this->form_validation->error_array()), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $this->note->update($note_id, [
            'content'=>  $this->put('content'),
            'update_user_id'   =>  $user_id,
        ]);

        $row = $this->note->select_note($note_id);

        $data = array ();
        $data['note_id'] = $row->note_id;
        $data['content'] = $row->content;
        $data['user_name'] = $row->name;
        $data['user_hash'] = $row->user_hash;
        $data['updated_at'] = $row->updated_at;

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * チャットのノートを削除する。
     * DELETE
     */
    public function delete_note_delete($room_hash, $note_id) {
        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $this->note->delete($note_id);

        $data = array ();
        $data['note_id'] = $note_id;

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * フィードバックを作成する
     * POST
     */
    public function create_feedback_post($room_hash) {
        if (!$this->form_validation->run('create_feedback')) {
            $this->set_response(error_message_format($this->form_validation->error_array()), REST_Controller::HTTP_OK); return;
        }

        // ルームＩＤをデコードする
        $room_data = room_hash_decode($room_hash);
        $room_id = $room_data['room_id'];
        $user_id = $room_data['user_id'];

        if(!$this->user->exist_user($room_id, $user_id)){
            $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        }

        $this->feedback->insert(array (
            'user_id' => $user_id,
            'mail' => $this->post('mail'),
            'genre' => $this->post('genre'),
            'content' => $this->post('content'),
            'debug' => $this->post('debug'),
        ));

        $data = array ();

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * フィンガープリントを元にユーザの過去のルームを検索し返却する。
     * GET
     */
    public function select_pastroom_get($fingerprint) {
        // FIXME: フィンガープリント単品だと特定しやすいのでされにくい仕組みを作る
        // ルームＩＤをデコードする
        // $room_data = room_hash_decode($room_hash);
        // $room_id = $room_data['room_id'];
        // $user_id = $room_data['user_id'];

        // if(!$this->user->exist_user($room_id, $user_id)){
        //     $this->set_response(error_message_format(['room_hash' => $this->lang->line('exist_user')]), REST_Controller::HTTP_OK); return;
        // }

        $cols = $this->user->select_user_by_fingerprint($fingerprint);

        $data = array ();
        foreach ($cols as $row) {
            $temp_row = array ();
            $temp_row['room_hash'] = room_hash_encode($row->room_id, new UserRole($row->user_role), $row->user_id);
            $temp_row['room_name'] = $row->room_name;
            $temp_row['user_name'] = $row->user_name;
            $temp_row['into_date'] = $row->created_at;

            $data[] = $temp_row;
        }

        $this->set_response($data, REST_Controller::HTTP_OK); return;
    }

    /**
     * 報告ジャンルのバリデーション
     */
    public function _validate_feedback_genre($value){
        if($value === FeedbackGenre::OFFENCE || $value === FeedbackGenre::IMPROVE || $value === FeedbackGenre::OTHER){
            return TRUE;
        }
        $this->form_validation->set_message('_validate_feedback_genre', $this->lang->line('_validate_feedback_genre'));
        return FALSE;
    }
}