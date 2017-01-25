<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Room extends MY_Model {

    public function __construct() {
        // CI_Model constructor の呼び出し
        parent :: __construct();
    }

    /**
     * 部屋が存在するかチェックする
     */
    public function exit_room($room_id){
        return $this->db->from('rooms')
        ->where(array (
        'room_id' => $room_id,
        ))->count_all_results() > 0;
    }

    /**
     * 部屋をすべて取得する。
     */
    public function select_rooms(){
        return $this->db->select('r.room_id, r.name, r.description, r.updated_at, u.user_id')
        ->from('rooms as r')
        ->join('users as u', 'u.room_id = r.room_id and u.user_role = ' . UserRole::ADMIN, 'inner')
        ->get()->result();
    }

    /**
     * 指定の部屋の情報を取得する。
     */
    public function select_room($room_id){
        return $this->db->select('r.room_id, r.name, r.description, r.room_key, r.updated_at, u.user_id')
        ->from('rooms as r')
        ->join('users as u', 'u.room_id = r.room_id', 'inner')
        ->where(array (
            'r.room_id' => $room_id,
        ))->get()->row();
    }

}