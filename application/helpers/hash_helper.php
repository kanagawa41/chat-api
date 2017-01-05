<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 引数から周知用のハッシュ値にエンコードする。
 * 「roomid_userid」の形式でbase64にエンコードする。
 * 「０」は自分でユーザ名を決められるアノニマスが使用できる。
 * 
 */
function room_hash_encode($room_id, $role, $user_id) {
	return base64_urlsafe_encode($room_id . '_' . $role . '_' . $user_id);
}

/**
 * 周知用のハッシュ値をデコードする。
 * 「ルームID(room_id)、ユーザID(user_id)」は配列にして返却する。
 * 
 */
function room_hash_decode($room_hash) {
	$raw_room_data = base64_urlsafe_decode($room_hash);
	$room_data = explode("_", $raw_room_data);

	// 部屋情報が３つでない場合は異常値
	if(count($room_data) == 3) {
		return array(
				'room_id' => $room_data[0]
				, 'role' => $room_data[1]
				, 'user_id' => $room_data[2]
		);
	} else {
		return array(
				'room_id' => ''
				, 'role' => ''
				, 'user_id' => ''
		);
	}

}

/**
 * URL safeなエンコードメソッド
 * 変換した後に後ろの「==」は削除する。
 * 
 */
function base64_urlsafe_encode($val) {
    $CI = &get_instance();
	$encode_val = $CI->encrypt->encode($val, $CI->config->item('room_encryption_key'));
	
	return substr(str_replace(array (
		'+',
		'/',
		'='
	), array (
		'_',
		'-',
		'.'
	), $encode_val), 0, -2);
}

/**
 * URL safeなデコードメソッド
 * 削除した「==」を付加してデコードさせる。
 * 
 */
function base64_urlsafe_decode($raw_val) {
	$val = str_replace(array (
		'_',
		'-',
		'.'
	), array (
		'+',
		'/',
		'='
	), $raw_val . '..');

    $CI = &get_instance();
	return $CI->encrypt->decode($val, $CI->config->item('room_encryption_key'));
}
