<?php
/**
 * ユーザの役割の定数
 */
final class MessageType extends Enum {
	const MAKE_ROOM = '100'; //ルーム作成
	const UPDATE_ROOM = '110'; //ルーム更新
	const DELETE_ROOM = '120'; //ルーム削除
	const ROOM_READ_ONLY = '130'; //ルームリードオンリー
	const MAKE_USER = '200'; //入室(ユーザ追加)
	const UPDATE_USER = '210'; //ユーザ情報更新
	const DELTE_USER = '220'; //ユーザ削除
    const MAKE_MESSAGE = '300'; //メッセージ作成
	const IMAGE_POST = '400'; //画像投稿
	const NOTE_POST = '500'; //ノート投稿
}