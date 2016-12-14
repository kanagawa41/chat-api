<?php
defined('BASEPATH') OR exit ('No direct script access allowed');

class Create_db extends CI_Model {

	public function __construct() {
		// CI_Model constructor の呼び出し
		parent :: __construct();
	}

	/**
	 * roomsテーブルを作成する
	 */
	public function create_rooms_table() {
		$sql = "
				CREATE TABLE rooms (
				    /** ルーム情報 **/
				    room_id INTEGER, --ルームＩＤ
				    room_hash STRING NOT NULL, --ルームハッシュ
				    room_name STRING NOT NULL, --作成したいグループチャットのチャット名
				    description STRING NOT NULL, --グループチャットの概要説明テキスト
				    created_at default CURRENT_TIMESTAMP NOT NULL, --作成日
				    updated_at default CURRENT_TIMESTAMP NOT NULL, --更新日
				    PRIMARY KEY(room_id AUTOINCREMENT)
				);
		                ";

		return $this->db->query($sql);
	}

	/**
	 * usersテーブルを作成する
	 */
	public function create_users_table() {
		$sql = "
		                    CREATE TABLE users (
		                        /** ユーザ情報 **/
		                        user_id INTEGER, --ユーザＩＤ
								user_hash STRING NOT NULL, --ユーザハッシュ
		                        name STRING NOT NULL, --ユーザ名
		                        room_id INTEGER, --ルームＩＤ
		                        begin_message_id INTEGER, --入室した際の開始メッセージＩＤ
							    user_agent STRING, --ユーザエージェント
							    ip_address STRING, --ユーザのアドレス
							    port INTEGER, --ユーザのポート
							    created_at default CURRENT_TIMESTAMP NOT NULL, --作成日
							    PRIMARY KEY(user_id AUTOINCREMENT)
		                    );
		                ";

		return $this->db->query($sql);
	}

	/**
	 * messagesテーブルを作成する
	 */
	public function create_messages_table() {
		$sql = "
							CREATE TABLE messages (
							    /** メッセージ情報 **/
							    message_id INTEGER, --メッセージＩＤ
							    user_id INTEGER, --ユーザＩＤ
							    room_id INTEGER, --ルームＩＤ
							    body STRING NOT NULL, --メッセージ内容
							    type INTEGER default 1, --メッセージの種類(1・・・メッセージ、2・・・入室)
							    created_at default CURRENT_TIMESTAMP NOT NULL, --作成日
							    PRIMARY KEY(message_id AUTOINCREMENT)
							);
		                ";

		return $this->db->query($sql);
	}

	/**
	 * readsテーブルを作成する
	 */
	public function create_reads_table() {
		$sql = "
							CREATE TABLE reads (
							    /** 既読情報 **/
							    message_id INTEGER, --メッセージＩＤ
							    user_id INTEGER, --ユーザＩＤ
							    room_id INTEGER, --ルームＩＤ
							    created_at default CURRENT_TIMESTAMP NOT NULL --作成日
							);
		                ";

		return $this->db->query($sql);
	}

	/**
	 * 指定のテーブルを削除する
	 */
	public function drop_table($table_name) {
		$sql = "DROP TABLE " . $table_name;
		return $this->db->query($sql);
	}
}