# 推奨閲覧ツール

[Dillinger](http://dillinger.io/)

# 概要

これはチャットのＡＰＩの思想を記したものである。
ＡＪＡＸとの通信を想定している。

# 構想

## 実装する機能

###システム作り参考
[ChatWork](http://developer.chatwork.com/ja/)

[jqueryチャット](http://studio-key.com/646.html)

[feeder](https://www.x-feeder.info/)

* ルームという概念がある
* ルームにはハッシュ化されたURLで入室が可能となる。
* ユーザにくばられたハッシュ化したIDで入室が可能となる
* ユーザは任意のハッシュ値を保持し、次回以降はそれを使用して入室ができる。（つまり同一ユーザを使い続けられる。）

###技術参考
[phpLiteAdmin](http://www.hiskip.com/pg-notes/dbtools/phpLiteAdmin.html)

[CSSで作った対談（会話）式吹き出しをLINE風にしてスマホにも対応させる](http://webkcampus.com/201411/829/)

[ローカルストレージ](http://qiita.com/mima_ita/items/363fd434f9c655944e3f)

[Redis](http://d.hatena.ne.jp/yk5656/touch/20140923/1411889810)

## DDL

#### ルーム
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


#### ユーザ
```
CREATE TABLE users (
    /** ユーザ情報 **/
    user_id INTEGER, --ユーザＩＤ
    user_hash STRING NOT NULL, --ユーザハッシュ
    user_role INTEGER DEFAULT 3, --ユーザロール(1…admin, 2…specific-user, 3…anonymous)
    name STRING NOT NULL, --ユーザ名
    sex STRING, --性別(0…性別なし, 1…男, 2…女)
    room_id INTEGER, --ルームＩＤ
    begin_message_id INTEGER, --入室した際の開始メッセージＩＤ
    icon_id INTEGER, --アイコンＩＤ
    fingerprint INTEGER, --フィンガープリント
    user_agent STRING, --ユーザエージェント
    ip_address STRING, --ユーザのアドレス
    port INTEGER, --ユーザのポート
    created_at default CURRENT_TIMESTAMP NOT NULL, --作成日
    PRIMARY KEY(user_id AUTOINCREMENT)
);
```


#### メッセージストリーム
```
CREATE TABLE stream_messages (
    /** メッセージ情報 **/
    message_id INTEGER, --メッセージＩＤ
    room_id INTEGER, --ルームＩＤ
    created_at default CURRENT_TIMESTAMP NOT NULL, --作成日
    PRIMARY KEY(message_id AUTOINCREMENT)
);
```

#### ユーザメッセージ
```
CREATE TABLE user_messages (
    /** メッセージ情報 **/
    message_id INTEGER, --メッセージＩＤ
    user_id INTEGER, --ユーザＩＤ
    body STRING NOT NULL, --メッセージ内容
    PRIMARY KEY(message_id)
);
```

#### お知らせメッセージ
```
CREATE TABLE info_messages (
    /** メッセージ情報 **/
    message_id INTEGER, --メッセージＩＤ
    body STRING NOT NULL, --メッセージ内容
    type INTEGER, --メッセージの種類(1…ルーム作成、2…入室、3…日付)
    PRIMARY KEY(message_id)
);
```


#### 既読
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
    "icon": 1,
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
        "name": "Test1",
        "who": "self",
        "icon": 1
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


### 11. _POST_ __/rooms/{room_hash}/members__

#### チャットにユーザを追加

【リクエスト】
```
curl -X POST -H "X-ChatToken: 管理人のAPIトークン"  -d "name=Ryuji&specific_user_flg=1" "https://api.emeraldchat.com/v1/rooms/{room_id}/members"
```

* name・・・ユーザ名
* specific_user_flg・・・特定ユーザの生成フラグ(X-ChatTokenの設定が必須)。設定がなければアノニマス（匿名）ユーザ。

【レスポンス】
```
{
  "room_hash": baCR29qKf_JHzsvlhphGsyy-MTho3lbmYVg2KiKcMIIC3AP6HiHQonviBN9scQfhkIqupm8l9_iLcX87nMmTbQ,
  "user_hash": 2uhimbRJ6T
}
```


# FIXME 現状と剥離しているので修正要。
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

---
---

# TODO

### ●コントローラーのbefore、afterを実装する

* [codeigniter hook](http://d.hatena.ne.jp/ninoseki/20110611/1307778923)

### ●画面を開いた際のＡＰＩを作成する。（既読をチェックしている）

### ●画面とＡＰＩのフォルダを切り分ける

### ●例外処理の仕組みを作る

### ●ルームをリードオンリー（送信できない）モードをつける。

### ●APIのリクエストの記載を「curl」主体から「ajax」風に書き直す

### ●利用フローを書き直す。


# FIX

### SSE処理？でエラーが発生する。
* Blink deferred a task in order to make scrolling smoother. Your timer and network tasks should take less than 50ms to run to avoid this. Please see https://developers.google.com/web/tools/chrome-devtools/profile/evaluate-performance/rail and https://crbug.com/574343#c40 for more information.

### ●過去のメッセージを参照しようとした場合にたまにエラーが発生する。しかし改めてＵＲＬを送信してもエラーは発現しない。


# DONE

### ×メッセージをユーザ用テーブルとお知らせ用テーブルで分離させる。

### ×管理者が気軽に更新できる、管理画面を作成する。

### ×サーバでＳＳＥが効いていない可能性がある。

### ×新着メッセージの位置判定が正しく行われていない。スクロール位置が正しく取得できていないため誤作動を起こす

### ×日付の表示がちゃんとされるようにする。
* ×日付を各メッセージに要素として設定して、それを使用して比較を行うようにする。
* ×日付もメッセージとして登録するようにする。
* ×現状の日付を比較する処理の削除。

### ×ユーザがアイコンを指定できるようにする。

### ×アイコンイメージを男用、女用を集める
* ×画像を収集
* ×女：#f19ec2、男：#243a73　で背景色を染める

### ×ルームの画面にラインみたく日付を表示する。（https://github.com/protonet/jquery.inview）

### ×画面のほうを新しいユーザ生成方法に対応させる。（roomid_role_userid）

### ×画面処理で送信する前に、再度受信をしてから送信するようにする。
* ×たまに受信中に送信してエラーが発生する場合があるので対策をする。

### ×ＡＰＩのルームに所属するユーザの役割も返却するようにする。（X-ChatToken有りの場合は）

### ×トースターの実装（→参考：http://ryus.co.jp/blog/toast-toastr/）

### ×部屋に特定ユーザを一括で追加できるＡＰＩを作成する。（やっぱり画面側で工夫してもらうことに。）

### ×ＡＰＩのルームに所属するユーザの名前とハッシュも返却するようにする。（X-ChatToken有りの場合は）

### ×テーブル作成コントローラーの削除。

### ×ＡＰＩのルーム作成時の「room_hash」は「room_anonymous_hash」に変更する。

### ×ＡＰＩのルーム一覧時の「room_admin_hash」も返却するようにする。

### ×ユーザを識別するためのハッシュ値を生成する仕組みを考える。（２ｃｈみたいに）
* ×ユーザを識別するには名前しかないが、それだと悪戯できてしまう。user_hashは再入場に使用するため他人に教えてはいけないため使用できない。ユーザが保持する「ipaddress」、「port」からハッシュ値を求めて頭数ケタを使用する。
* ×ハッシュ値の桁数は2chを参考。（かぶらないようにできていると思うので）

### ×画面のレイアウトを整える。（ＬＩＮＥを踏襲）
* ×入室時にuser_hashが指定されていない場合は、ダイアログが中央に表示され、ユーザ入室したのち消える。
* ×生成したurlをコピーできるボタンを作成する。→自動的に専用のURLに繊維するため必要なし。
* ×ヘッダーにルーム名を表示する。（よくある「＋」をおすとメニューがでる感じを出したい。）

### ×ユーザに役割の値を保持させる。（admin, public）
* ×adminは入室する際に特定のフラグを立てれば、再入場の閲覧制限を気にすることなく、全てのメッセージを閲覧する事ができる。
* ×publicは特に意味なし。→anonymousという役割に変更。adminが直接作成したユーザは、specific-userという役割になる。

### ×ハッシュプールを利用してuser_hashを求めるようにする。→必要なし

### ×ユーザハッシュを利用して再入場した場合は、入室した直前の100件(?)を取得できるAPIを作成する。
* ×名前は「/rooms/{room_hash}/members/{user_hash}/messages/reentry」
* ×画面側からも取得件数の指定ができるようにパラメータを付ける。

### ×コンフィグファイルに主要な設定値をまとめる。
* ×ハッシュ値の種
* ×再入場の取得件数
* ×アイコンの上限数
