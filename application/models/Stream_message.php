<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Stream_message extends MY_Model {
    protected $_primary_key = 'message_id';

    public function __construct() {
        // CI_Model constructor の呼び出し
        parent :: __construct();
    }

    /**
     * お知らせメッセージのみ追加する。
     */
    public function insert_stream_message($room_id, $user_id, DefineImpl $type) {
        $message_id = $this->stream_message->insert([
            'room_id' => $room_id,
            'user_id' => $user_id,
            'type' => $type,
        ]);
        return $message_id;
    }

    /**
     * 最大メッセージＩＤを取得する。
     */
    public function max_message_id($room_id) {
        return $this->db->
        select_max('message_id')
        ->from('stream_messages')
        ->where('room_id', $room_id)
        ->get()->row()->message_id;
    }

    /**
     * 指定ユーザのメッセージ数を取得する。
     * trueの場合はユーザが存在する。でない場合はfalse。
     */
    public function message_count($room_id, $user_id) {
        return $this->db
        ->from('stream_messages as sm')
        ->join('user_messages as u', 'u.message_id = sm.message_id', 'left')
        ->where(array(
            'sm.room_id' => $room_id,
            'u.user_id' => $user_id
        ))
        ->count_all_results();
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
            , u.icon_name
            , u.sex
            , u.user_hash
            , CASE 
              WHEN um.message_id IS NOT NULL THEN um.body 
              ELSE pi.path
              END AS body
            , sm.type
            , sm.created_at
            '
        , false)
        ->from('stream_messages as sm')
        ->join('post_images as pi', 'pi.message_id = sm.message_id', 'left')
        ->join('user_messages as um', 'um.message_id = sm.message_id', 'left')
        ->join('users as u', 'u.user_id = sm.user_id', 'left')
        ->where([
            'sm.message_id' => $message_id,
            'sm.room_id' => $room_id,
        ])
        ->get()->row();
    }
    
    /**
     * 未読のメッセージを取得する。
     */
    public function unread_messages($room_id, $user_id) {
        // 既読済のメッセージID
        $last_read_message_id = $this->read_message->read_message($user_id);

        return $this->db->select(
            '
            sm.message_id
            , u.name
            , u.user_id
            , u.icon_name
            , u.sex
            , u.user_hash
            , CASE 
              WHEN um.message_id IS NOT NULL THEN um.body 
              ELSE pi.path
              END AS body
            , sm.type
            , sm.created_at
            '
        , false)
        ->from('stream_messages as sm')
        ->join('post_images as pi', 'pi.message_id = sm.message_id', 'left')
        ->join('user_messages as um', 'um.message_id = sm.message_id', 'left')
        ->join('users as u', 'u.user_id = sm.user_id', 'left')
        ->where([
            'sm.room_id' => $room_id,
            'sm.message_id >' => $last_read_message_id,
        ])
        ->group_start()
          ->where('u.user_id <>', $user_id)
          ->or_where('u.user_id IS NULL')
        ->group_end()
        ->get()->result();
    }

    /**
     * TODO このメソッドを使用する場合は改修してください。
     * 過去のメッセージを取得する。
     */
    public function past_messages($room_id, $user_id, $message_id) {
        $begin_message_id = $this->user->find($user_id)->begin_message_id;

        // ユーザが参照できる過去のメッセージ数を取得する。
        $massage_count = $this->db
        ->select('count(sm.message_id) as message_count')
        ->from('stream_messages as sm')
        ->join('post_images as pi', 'pi.message_id = sm.message_id', 'left')
        ->join('user_messages as um', 'um.message_id = sm.message_id', 'left')
        ->join('users as u', 'u.user_id = um.user_id', 'left')
        ->where(array (
            'sm.room_id' => $room_id,
            'sm.message_id >' => (int)$begin_message_id,
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
            , CASE 
              WHEN um.message_id IS NOT NULL 
              THEN um.user_id
              ELSE ua.user_id 
              END AS user_id
            , u.icon_name
            , u.sex
            , u.user_hash
            , CASE 
              WHEN um.message_id IS NOT NULL 
              THEN um.body 
              ELSE ua.body 
              END AS body
            , sm.type
            , sm.created_at
            '
        , false)
        ->from('stream_messages as sm')
        ->join('post_images as pi', 'pi.message_id = sm.message_id', 'left')
        ->join('user_messages as um', 'um.message_id = sm.message_id', 'left')
        ->join('users as u', 'u.user_id = um.user_id', 'left')
        ->where([
            'sm.room_id' => $room_id,
            'sm.message_id >' => (int)$begin_message_id,
            'sm.message_id <' => (int)$message_id,
        ])
        ->limit($past_message_max_count)
        ->offset($massage_offset)->get()->result();
    }
    
    /**
     * FIXME 使われていないがどうするか要確認する。
     * 日付が変わっていないか確認する。
     * 最終メッセージと現在の日付が変わっている場合はtrue、でない場合はfalse。
     */
     public function changed_date($room_id){
        $row = $this->db
        ->select('datetime(CURRENT_TIMESTAMP) as now_date, created_at as last_message_date')
        ->from('stream_messages')
        ->where([
            'room_id' => $room_id
        ])
        ->order_by('message_id', 'DESC')
        ->get()->row();

        $this->load->helper('date');
        
        if(empty($row->last_message_date)){
            return true;
        }

        // 最終メッセージと日付が変わっていた場合
        return compare_date($row->now_date, $row->last_message_date);
     }
}