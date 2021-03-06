<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| 過去メッセージリクエスト時に取得可能な最大件数
| -------------------------------------------------------------------------
| ユーザが過去メッセージをリクエストした際に、メッセージを再取得する際のその上限値。
*/
$config['past_message_max_count'] = 30;

/*
|--------------------------------------------------------------------------
| ルームのハッシュ値を生成する際のキー値
|--------------------------------------------------------------------------
| ルームのハッシュ値を生成する際に用いる。32文字を設定する。
|
| $config['encryption_key'] = '318595d2b7102232fb3d2dc7aa94889b';
| 上記を使用する場合は注意すること。取得しようとした際にさらに暗号化されている模様。
*/
$config['room_encryption_key'] = '905e3ada3e70936a5cf80c0ae3b7067e';

/*
| -------------------------------------------------------------------------
| 管理者名
| -------------------------------------------------------------------------
| 管理者ユーザ名は固定とする。
*/
$config['admin_name'] = '管理者';
