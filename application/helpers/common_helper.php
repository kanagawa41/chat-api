<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 管理ユーザか確認する。
 * 管理ユーザの場合はtrue、でない場合はfalse。
 */
function is_admin($room_hash) {
    // ルームＩＤをデコードする
    return room_hash_decode($room_hash)['role'] === UserRole::ADMIN;
}

/**
 * エラーメッセージのフォーマット
 */
function error_message_format($message) {
    return ['errors' => $message];
}
