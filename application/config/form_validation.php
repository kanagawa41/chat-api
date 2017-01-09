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
		,'create_user' => array(
                array(
                        'field' => 'name',
                        'label' => '名前',
                        'rules' => 'required|max_length[10]'
                ),
                array(
                        'field' => 'fingerprint',
                        'label' => 'フィンガープリント',
                        'rules' => 'required|numeric|max_length[20]'
                ),
        )
		,'create_room' => array(
                array(
                        'field' => 'name',
                        'label' => 'ルーム名',
                        'rules' => 'required|max_length[10]'
                ),
                array(
                        'field' => 'description',
                        'label' => '説明',
                        'rules' => 'max_length[50]'
                ),
        )
		,'update_room' => array(
                array(
                        'field' => 'name',
                        'label' => 'ルーム名',
                        'rules' => 'required|max_length[10]'
                ),
                array(
                        'field' => 'description',
                        'label' => '説明',
                        'rules' => 'max_length[50]'
                ),
        )
);
