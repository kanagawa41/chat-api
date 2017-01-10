<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Room extends MY_Model {

    public function __construct() {
        // CI_Model constructor の呼び出し
        parent :: __construct();
    }

    public function exit_room($room_id){
        return $this->db->from('rooms')->
        where(array (
        'room_id' => $room_id
        ))->count_all_results() > 0;
    }
}