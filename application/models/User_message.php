<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class User_message extends MY_Model {
    protected $_primary_key = 'message_id';

    public function __construct() {
        // CI_Model constructor の呼び出し
        parent :: __construct();
    }

    /**
     * ユーザメッセージを追加する。
     * 日付が変わっていた場合は日付のメッセージを追加する。
     */
    public function insert_user_message($room_id, $user_id, $body) {
        $message_id = $this->stream_message->insert_stream_message(
            $room_id,
            $user_id,
            new MessageType(MessageType::MAKE_MESSAGE)
        );

        $this->user_message->insert([
            'message_id' => $message_id,
            'user_id' => $user_id,
            'body' => $body,
        ]);

        return $message_id;
    }
}