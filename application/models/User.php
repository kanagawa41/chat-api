<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class User extends MY_Model {
    protected $_primary_key = 'user_id';

    public function __construct() {
        // CI_Model constructor の呼び出し
        parent :: __construct();
    }

    /**
     * ユーザの存在チェックを厳密に行う。
     * trueの場合はユーザが存在する。でない場合はfalse。
     */
    public function exist_user($room_id, $user_id) {
        return $this->db->from('rooms r')
        ->join('users as u', 'u.room_id = r.room_id', 'inner')
        ->where(array (
            'r.room_id' => $room_id,
            'u.user_id' => $user_id
        ))->count_all_results() > 0;
    }

    /**
     * ユーザが既に登録されていないか確認する。
     * 登録されていた場合はtrue、でない場合はfalseを返却する。
     */
    public function duplicate_user($room_id, $fingerprint) {
        return $this->db->from('users')
        ->where(array (
            'room_id' => $room_id,
            'fingerprint' => $fingerprint,
        ))->count_all_results() > 0;
    }

    /**
     * ユーザを追加する。
     * 追加したユーザIDを返却する。
     */
    public function insert_user($name, $room_id, DefineImpl $user_role, $fingerprint, DefineImpl $sex, $icon_name) {
        $raw_max_message_id = $this->stream_message->max_message_id($room_id);
        $max_message_id = empty($raw_max_message_id) ? 0: $raw_max_message_id;

        $this->load->helper('string');

        // ユーザ固有のハッシュ値を取得する（低確率でダブル可能性はある）
        $user_hash = random_string($type = 'alnum', $len = 10);

        $data = array(
           'user_hash' => $user_hash ,
           'user_role' => $user_role->valueOf() ,
           'name' => $name ,
           'sex' => $sex ,
           'room_id' => $room_id ,
           'begin_message_id' => $max_message_id ,
           'icon_name' => $icon_name ,
           'fingerprint' => $fingerprint ,
           'user_agent' => $_SERVER['HTTP_USER_AGENT'] ,
           'ip_address' => $_SERVER['REMOTE_ADDR'] ,
           'port' => $_SERVER['REMOTE_PORT'] ,
        );

        $user_id = $this->user->insert($data);

        // 入室メッセージ作成
        $this->stream_message->insert_info_message($room_id, $name, new MessageType(MessageType::INTO_ROOM));

        return $user_id;
    }
}