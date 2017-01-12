<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Stream_message extends MY_Model {
    protected $_primary_key = 'message_id';

    public function __construct() {
        // CI_Model constructor の呼び出し
        parent :: __construct();
    }


    /**
     * 最大メッセージＩＤを取得する。
     */
    public function max_message_id($room_id) {
        return $this->db->select_max('message_id')->from('stream_messages')->where('room_id', $room_id)->get()->row()->message_id;
    }

    /**
     * 指定ユーザのメッセージ数を取得する。
     * trueの場合はユーザが存在する。でない場合はfalse。
     */
    public function message_count($room_id, $user_id) {
        return $this->db->from('stream_messages as sm')
        ->join('user_messages as u', 'u.message_id = sm.message_id', 'left')
        ->where(array(
            'sm.room_id' => $room_id,
            'u.user_id' => $user_id
        ))->count_all_results();
    }

    /**
     * 指定のメッセージを取得する。
     */
    public function specific_message($room_id, $message_id) {
        return $this->db->select(
            '
            sm.message_id
            , u.name
            , u.user_id
            , u.icon_id
            , u.sex
            , u.user_hash
            , CASE 
              WHEN um.message_id IS NOT NULL 
              THEN um.body 
              ELSE im.body 
              END AS body
            , im.type
            , sm.created_at
            '
        , false)
        ->from('stream_messages as sm')
        ->join('info_messages as im', 'im.message_id = sm.message_id', 'left')
        ->join('user_messages as um', 'um.message_id = sm.message_id', 'left')
        ->join('users as u', 'u.user_id = um.user_id', 'left')
        ->where(array (
            'sm.message_id' => $message_id,
            'sm.room_id' => $room_id,
        ))->get()->row();
    }
    
    /**
     * 未読のメッセージを取得する。
     */
    public function unread_messages($room_id, $user_id) {
        // 既読済のメッセージID
        $last_read_message_id = $this->read->read_message($user_id);

        return $this->db->select(
            '
            sm.message_id
            , u.name
            , u.user_id
            , u.icon_id
            , u.sex
            , u.user_hash
            , CASE 
              WHEN um.message_id IS NOT NULL 
              THEN um.body 
              ELSE im.body 
              END AS body
            , im.type
            , sm.created_at
            '
        , false)
        ->from('stream_messages as sm')
        ->join('info_messages as im', 'im.message_id = sm.message_id', 'left')
        ->join('user_messages as um', 'um.message_id = sm.message_id', 'left')
        ->join('users as u', 'u.user_id = um.user_id', 'left')
        ->where(array (
            'sm.room_id' => $room_id,
            'sm.message_id >' => $last_read_message_id,
            'u.user_id <>' => $user_id,
        ))->get()->result();
    }

    
    /**
     * 過去のメッセージを取得する。
     */
    public function past_messages($room_id, $user_id, $message_id) {
        $begin_message_id = $this->user->find($user_id)->begin_message_id;

        // ユーザが参照できる過去のメッセージ数を取得する。
        $massage_count = $this->db->select('count(*) as message_count')
        ->from('stream_messages as sm')
        ->join('info_messages as im', 'im.message_id = sm.message_id', 'left')
        ->join('user_messages as um', 'um.message_id = sm.message_id', 'left')
        ->join('users as u', 'u.user_id = um.user_id', 'left')
        ->where(array (
            'sm.room_id' => $room_id,
            'sm.message_id >' => $begin_message_id,
            'sm.message_id <' => (int)$message_id,
        ))->get()->row()->message_count;

        if($massage_count == 0){
            return array();
        }

        $past_message_max_count = $this->config->item('past_message_max_count');
        // FIXME このロジックは正しいか調査する必要がある。
        $massage_offset = $massage_count > $past_message_max_count ? $massage_count - $past_message_max_count : 0;

        return $this->db->select(
            '
            sm.message_id
            , u.name
            , u.user_id
            , u.icon_id
            , u.sex
            , u.user_hash
            , CASE 
              WHEN um.message_id IS NOT NULL 
              THEN um.body 
              ELSE im.body 
              END AS body
            , im.type
            , sm.created_at
            '
        , false)
        ->from('stream_messages as sm')
        ->join('info_messages as im', 'im.message_id = sm.message_id', 'left')
        ->join('user_messages as um', 'um.message_id = sm.message_id', 'left')
        ->join('users as u', 'u.user_id = um.user_id', 'left')
        ->where(array (
            'sm.room_id' => $room_id,
            'sm.message_id >' => $begin_message_id,
            'sm.message_id <' => (int)$message_id,
        ))->limit($past_message_max_count)
        ->offset($massage_offset)->get()->result();
    }
    
    /**
     * 日付が変わっていないか確認する。
     * 最終メッセージと現在の日付が変わっている場合はtrue、でない場合はfalse。
     */
     public function changed_date($room_id){
        $row = $this->db->select('datetime(CURRENT_TIMESTAMP) as now_date, created_at as last_message_date')
        ->from('stream_messages')
        ->where(array (
            'room_id' => $room_id
        ))
        ->order_by('message_id', 'DESC')
        ->get()->row();

        $this->load->helper('date');
        
        if(empty($row->last_message_date)){
            return true;
        }

        // 最終メッセージと日付が変わっていた場合
        return compare_date($row->now_date, $row->last_message_date);
     }

    /**
     * FIXME user_message.phpに移動する。
     * ユーザメッセージを追加する。
     * 日付が変わっていた場合は日付のメッセージを追加する。
     */
    public function insert_user_message($room_id, $user_id, $body) {

        $message_id = $this->stream_message->insert(array (
            'room_id' => $room_id,
        ));

        return $this->user_message->insert(array (
            'message_id' => $message_id,
            'user_id' => $user_id,
            'body' => $body,
        ));
    }

    /**
     * FIXME info_message.phpに移動する。
     * インフォメッセージを追加する。
     */
    public function insert_info_message($room_id, $body, DefineImpl $type) {
        $message_id = $this->stream_message->insert(array (
            'room_id' => $room_id,
        ));

        return $this->info_message->insert(array (
            'message_id' => $message_id,
            'body' => $body,
            'type' => $type,
        ));
    }

}