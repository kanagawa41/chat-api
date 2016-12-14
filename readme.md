# 推奨閲覧ツール

[Dillinger](http://dillinger.io/)

# 概要

これはチャットのＡＰＩの思想を記したものである。
ＡＪＡＸとの通信を想定している。

# 構想

## 実装する機能

###参考
[ChatWork](http://developer.chatwork.com/ja/)
[jqueryチャット](http://studio-key.com/646.html)

* ルームという概念がある
* ルームはハッシュ化されたURLで入室が可能となる。
* ユーザにくばられたハッシュ化したIDで入室が可能となる
* ユーザはアナウンスを受けることができる

###技術録
[phpLiteAdmin](http://www.hiskip.com/pg-notes/dbtools/phpLiteAdmin.html)

## DDL

#### ルーム情報
```
CREATE TABLE rooms (
    /** ルーム情報 **/
    room_id INTEGER, --ルームＩＤ
    name STRING NOT NULL, --作成したいグループチャットのチャット名
    description STRING NOT NULL, --グループチャットの概要説明テキスト
    created_at default CURRENT_TIMESTAMP NOT NULL, --作成日
    updated_at default CURRENT_TIMESTAMP NOT NULL, --更新日
    PRIMARY KEY(room_id AUTOINCREMENT)
);
```


#### ユーザ情報
```
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
```


#### メッセージ情報
```
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
```


#### 既読情報
```
CREATE TABLE reads (
    /** 既読情報 **/
    message_id INTEGER, --メッセージＩＤ
    user_id INTEGER, --ユーザＩＤ
    room_id INTEGER, --ルームＩＤ
    created_at default CURRENT_TIMESTAMP NOT NULL --作成日
);
```


## APIの種類

### 3. _GET_ __/rooms__

#### チャット一覧の取得

【リクエスト】
```
curl -X GET -H "X-ChatToken: 管理人のAPIトークン" "https://api.emeraldchat.com/v1/rooms"
```

【レスポンス】
```
[
  {
    "room_id": 123,
    "name": "Group Chat Name",
    "message_num": 122,
    "last_update_time": 1298905200
  }
]
```


### 4. _POST_ __/rooms__

#### グループチャットを新規作成

【リクエスト】
```
curl -X POST -H "X-ChatToken: 管理人のAPIトークン" -d "description=group+chat+description&name=Website+renewal+project" "https://api.emeraldchat.com/v1/rooms"
```

* description・・・グループチャットの概要説明テキスト
* name・・・作成したいグループチャットのチャット名

【レスポンス】
```
{
  "room_id": 1234,
  "room_hash": "Y1_5w5GbrFh-vW-g4k_yjy6Hma1yYcoQtaGqhOETdOPtyGpo6Jg2C5YoHyvn6BFhJmLYrsm2N7dRhQcmRbAzbA"
}
```

### 5. _GET_ __/rooms/{room_id}__

#### チャットの名前を取得

【リクエスト】
```
curl -X GET -H "X-ChatToken: 管理人のAPIトークン" "https://api.emeraldchat.com/v1/rooms/{room_id}"
```

【レスポンス】
```
{
  "name": "Group Chat Name",
  "description": "room description text"
}
```


### 6. _PUT_ __/rooms/{room_id}__

#### チャットの名前をアップデート

【リクエスト】
```
curl -X PUT -H "X-ChatToken: 管理人のAPIトークン" -d "description=group+chat+description&name=Website+renewal+project" "https://api.emeraldchat.com/v1/rooms/{room_id}"
```

* description・・・グループチャットの概要説明テキスト
* name・・・作成したいグループチャットのチャット名

【レスポンス】
```
{
  "room_id": 1234
}
```


### 7. _DELETE_ __/rooms/{room_id}__

#### グループチャットを削除する

【リクエスト】
```
curl -X DELETE -H "X-ChatToken: 管理人のAPIトークン" -d "https://api.emeraldchat.com/v1/rooms/{room_id}"
```

【レスポンス】
```
なし
```


### 8. _GET_ __/rooms/{room_hash}/members__

#### チャットのメンバー一覧を取得

【リクエスト】
```
curl -X GET "https://api.emeraldchat.com/v1/rooms/{room_hash}/members"
```

【レスポンス】
```
[
  {
    "user_id": 123,
    "name": "John Smith"
  }
]
```


### 8.1. _GET_ __/rooms/{room_hash}/members__/{user_hash}

#### チャットのメンバー情報を取得

【リクエスト】
```
curl -X GET "https://api.emeraldchat.com/v1/rooms/{room_hash}/members/{user_hash}"
```

【レスポンス】
```
[
  {
    "name": "John Smith",
    "message_count": 3,
    "begin_message_id": "123",
    "last_create_time": 1298905200
  }
]
```


### 9. _GET_ __/rooms/{room_hash}/members/{user_hash}/messages__

#### チャットのメッセージ一覧を取得。前回取得分からの差分のみを返します。

【リクエスト】
```
curl -X GET "https://api.emeraldchat.com/v1/rooms/rooms/{room_hash}/members/{user_hash}/messages"
```


【レスポンス】
```
[
  {
    "message_id": 1,
    "user":{
        "name": "Test1"
        "who": "self"
    },
    "body": "Test1",
    "type": 2,
    "send_time": "2016-12-13 09:50:50"
  }
]
```


### 10. _POST_ __/rooms/{room_hash}/members/{user_hash}/messages__

#### チャットに新しいメッセージを追加

【リクエスト】
```
curl -X POST -d "body=Hello+EmeraldChat%21" "https://api.emeraldchat.com/v1/rooms/{room_hash}/members/{user_hash}/messages"
```

* body・・・メッセージ本文

【レスポンス】
```
{
  "message_id": 1234
}
```


### 11. _GET_ __/rooms/{room_id}/messages/{message_id}__

#### メッセージ情報を取得

【リクエスト】
```
curl -X GET -H "X-ChatToken: 管理人のAPIトークン" "https://api.emeraldchat.com/v1/rooms/{room_id}/messages/{message_id}"
```

【レスポンス】
```
{
  "message_id": 5,
  "user": {
    "name": "Bob",
  },
  "body": "Hello Chatwork!",
  "send_time": 1384242850
}
```


### 12. _POST_ __/rooms/{room_id}/members__

#### チャットにユーザを追加

【リクエスト】
```
curl -X POST -d "name=Ryuji" "https://api.emeraldchat.com/v1/rooms/{room_id}/members"
```

* name・・・ユーザ名

【レスポンス】
```
{
  "user_hash": 2uhimbRJ6T
}
```


# 利用フロー

* ルームを作成し、生成されたルームID(ハッシュ値)を取得する(API構想済み)
{room_id:FJOIngow2489u53345lFEklEC}

* ルームにログインします。(API構想済み)
http://chat/rooms/FJOIngow2489u53345lFEklEC

* ユーザ名を送信します。(上限10文字)(API構想済み)
レスポンス：{"user_id": 1234}

* メッセージを取得します。(API構想済み)
レスポンス：[{"message_id": 5,"user": {"name": "Bob"},"body": "Hello Chatwork!","send_time": 1384242850,"update_time": 0}]

* メッセージを送信します。(API構想済み)
リクエスト：{"message_id": 1234}

* 定期的にメッセージを受信します。(API構想済み)
リクエスト：[{"message_id": 5,"user": {"name": "Bob"},"body": "Hello Chatwork!","send_time": 1384242850,"update_time": 0}]

* 過去分を参照する。(API構想済み)
レスポンス：[{"message_id": 5,"user": {"name": "Bob"},"body": "Hello Chatwork!","send_time": 1384242850,"update_time": 0}]

