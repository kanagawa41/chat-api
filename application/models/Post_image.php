<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Post_image extends MY_Model {
	protected $_primary_key = 'message_id';

	public function __construct() {
		// CI_Model constructor の呼び出し
		parent :: __construct();
	}

    /**
     * インフォメッセージを追加する。
     */
    public function insert_post_image($room_id, $user_id, $path) {
        $message_id = $this->stream_message->insert_stream_message(
            $room_id,
            $user_id, 
            new MessageType(MessageType::IMAGE_POST)
        );

        $this->post_image->insert(array (
            'message_id' => $message_id,
            'user_id' => $user_id,
            'path' => $path,
        ));

        return $message_id;
    }

}