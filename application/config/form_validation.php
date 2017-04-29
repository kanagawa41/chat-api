<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config = array(
        'create_message' => array(
                array(
                        'field' => 'body',
                        'label' => 'メッセージ',
                        'rules' => 'required|max_length[100]'
                ),
        )
        ,'create_image' => array(
                array(
                        'field' => 'path',
                        'label' => '画像パス',
                        'rules' => 'required|max_length[200]'
                ),
        )
        ,'create_note' => array(
                array(
                        'field' => 'content',
                        'label' => '内容',
                        'rules' => 'required|max_length[200]'
                ),
        )
        ,'update_note' => array(
                array(
                        'field' => 'content',
                        'label' => '内容',
                        'rules' => 'required|max_length[200]'
                ),
        )
        ,'create_user' => array(
                array(
                        'field' => 'name',
                        'label' => '名前',
                        'rules' => 'required|max_length[15]'
                ),
                array(
                        'field' => 'fingerprint',
                        'label' => 'フィンガープリント',
                        'rules' => 'required|numeric|max_length[20]'
                ),
                array(
                        'field' => 'sex',
                        'label' => '性別',
                        'rules' => 'required|callback__validate_sex'
                ),
                array(
                        'field' => 'icon',
                        'label' => 'アイコン',
                        'rules' => 'max_length[200]'
                ),
        )
        ,'update_user' => array(
                array(
                        'field' => 'name',
                        'label' => '名前',
                        'rules' => 'required|max_length[15]'
                ),
                array(
                        'field' => 'sex',
                        'label' => '性別',
                        'rules' => 'required|callback__validate_sex'
                ),
                array(
                        'field' => 'icon',
                        'label' => 'アイコン',
                        'rules' => 'max_length[200]'
                ),
        )
	,'create_room' => array(
                array(
                        'field' => 'name',
                        'label' => 'ルーム名',
                        'rules' => 'required|max_length[20]'
                ),
                array(
                        'field' => 'description',
                        'label' => '説明',
                        'rules' => 'max_length[50]'
                ),
                array(
                        'field' => 'name',
                        'label' => '名前',
                        'rules' => 'required|max_length[15]'
                ),
                array(
                        'field' => 'fingerprint',
                        'label' => 'フィンガープリント',
                        'rules' => 'required|numeric|max_length[20]'
                ),
                array(
                        'field' => 'sex',
                        'label' => '性別',
                        'rules' => 'required|callback__validate_sex'
                ),
                array(
                        'field' => 'icon',
                        'label' => 'アイコン',
                        'rules' => 'max_length[200]'
                ),
        )
	,'update_room' => array(
                array(
                        'field' => 'name',
                        'label' => 'ルーム名',
                        'rules' => 'required|max_length[20]'
                ),
                array(
                        'field' => 'description',
                        'label' => '説明',
                        'rules' => 'max_length[50]'
                ),
        )
        ,'create_feedback' => array(
                array(
                        'field' => 'mail',
                        'label' => 'メール',
                        'rules' => 'required|valid_email|max_length[200]'
                ),
                array(
                        'field' => 'genre',
                        'label' => 'ジャンル',
                        'rules' => 'required|numeric|callback__validate_feedback_genre'
                ),
                array(
                        'field' => 'content',
                        'label' => '報告内容',
                        'rules' => 'required|max_length[1000]'
                ),
        )
);
