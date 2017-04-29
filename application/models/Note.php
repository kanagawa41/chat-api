<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Note extends MY_Model {
    protected $_primary_key = 'note_id';

    public function __construct() {
        // CI_Model constructor の呼び出し
        parent :: __construct();
    }

    /**
     * ルームが所有しているノートを返却する。
     */
    public function select_notes($room_id) {
        return $this->db->select('n.note_id, n.content, u.name, u.user_hash, n.updated_at')
        ->from('notes as n')
        ->join('users as u', 'u.user_id = n.update_user_id', 'inner')
        ->where(['n.room_id' => $room_id])
        ->get()->result();
    }

    /**
     * 指定のノートを返却する。
     */
    public function select_note($note_id) {
        return $this->db->select('n.note_id, n.content, u.name, u.user_hash, n.updated_at')
        ->from('notes as n')
        ->join('users as u', 'u.user_id = n.update_user_id', 'inner')
        ->where(['n.note_id' => $note_id])
        ->get()->row();
    }

    /**
     * ノートを投稿する。
     */
    public function insert_note($room_id, $user_id, $content) {
        $note_id = $this->note->insert(array (
            'room_id' => $room_id,
            'user_id' => $user_id,
            'update_user_id' => $user_id,
            'content' => $content,
        ));

        $last_message_id = $this->stream_message->insert_stream_message(
            $room_id,
            $user_id,
            new MessageType(MessageType::NOTE_POST)
        );

        $this->read_message->insert(array (
            'message_id' => $last_message_id,
            'user_id' => $user_id,
            'room_id' => $room_id
        ));

        return $note_id;
    }
}