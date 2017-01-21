<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Read_message extends MY_Model {

    public function __construct() {
        // CI_Model constructor の呼び出し
        parent :: __construct();
    }

    /**
     * 既読済みのメッセージＩＤを返却する。
     */
    public function read_message($user_id) {
        return $this->db->select('COALESCE(max(r.message_id), 0) as last_read_message_id', false)
        ->from('read_messages as r')
        ->join('user_messages as um', 'um.user_id = r.user_id', 'left')
        ->where(array (
            'r.user_id' => $user_id,
        ))->get()->row()->last_read_message_id;
    }
}