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

    /**
     * 登校された画像一覧を取得し返却する。
     */
    public function select_post_images($room_id, $user_id){
        $begin_message_id = $this->user->find($user_id)->begin_message_id;

        return $this->db->select(
            '
            sm.message_id,
            u.name,
            u.user_hash,
            pi.path,
            sm.created_at
            '
        )
        ->from('stream_messages as sm')
        ->join('users as u', 'u.user_id = sm.user_id', 'inner')
        ->join('post_images as pi', 'pi.message_id = sm.message_id', 'left')
        ->where([
            'sm.room_id' => $room_id,
            'pi.message_id IS NOT NULL' => null,
            'sm.message_id >' => $begin_message_id,
        ])
        ->get()->result();
    }

}