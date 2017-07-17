# 推奨閲覧ツール

[Dillinger](http://dillinger.io/)

# 概要

これはチャットの画面を思想を記したものである。
ＡＪＡＸでAPIと通信して動作している。

# 設定ファイル
## assets/js/common/config.js
* api_domain
* self_domain


# 構想

## 実装する機能

###システム作り参考
[ChatWork](http://developer.chatwork.com/ja/)

###技術参考
[CSSで作った対談（会話）式吹き出しをLINE風にしてスマホにも対応させる](http://webkcampus.com/201411/829/)

[ローカルストレージ](http://qiita.com/mima_ita/items/363fd434f9c655944e3f)

[QRコード](https://github.com/jeromeetienne/jquery-qrcode)

[SNS系向きの利用規約](http://kiyaku.jp/hinagata/sns.html)

# TODO

### ●招待のURLコピーボタンが正常に動かない。（Jqueryを変えたため？）
* QRコードの位置を真ん中

### ●管理画面の構成を変える。一画面にすべてを詰め込むのではなく各画面に分ける。
× 初期登録が終わったら、ルームに飛ばすようにする。
* 招待、ルーム編集を管理者権限のみが行えるようにする

### ●LPの画像を取り換える。
* メイン画像と、操作方法をまとめた画像。

### ルームのメニューバーをbootstrapに入れ替え

### アイコンに使用できるファイルをみつける。
* アイコンを一つのファイルにまとめてcssの方で操作して表示する方式をとる

### 意見箱を設定する。


# TASK

### ●URLの改善
* http://www.weblog-life.net/entry/2016/03/30/070300


# WANT

### メモ機能のUIの実装。

### ユーザのプロフを入れる際に、iponeだと登録ボタンが、入力の完了ボタンの近くにあるため誤操作しやすい。


# DONE

### ●IOSで画像が選択できないバグの対応

### ●ユーザ情報を更新できるようにする。
* ユーザ情報を更新するAPIの作成。

### ●ユーザ一覧を取得するようにする。
* ユーザの一覧を返却するAPIの作成。

### ●アノニマスユーザの場合はメッセージを送信できないようにする。
* API側でユーザのロールも返却するように修正

### ●アイコンの画像パラメータをパスで登録するようにする。
* API側でユーザ作成時は、パスを登録する。

### ●画像送信を行えるようにする。
* ボタンを押したら写真撮影か、ファイルが選択できるようにする処理の実装。
* 選択されたファイルをリサイズする処理の実装。
* API側で送信された写真のバリッド、登録を行う。

### ●フロントのメッセージを一括管理する。

### ●パラサイトをindex.phpで作成する。
* SCROLLIFY https://github.com/lukehaas/Scrollify
* サンプル http://univ.peraichi.com/19#jQuery
* 画像 https://liginc.co.jp/web/design/material/36659
* http://www.chatwork.com/ja/mobile/index.html
* https://ferret-plus.com/3584
* 不要)skrollr https://github.com/Prinzhorn/skrollr

### ●メッセージ入力画面の検証
* http://chamo-chat.com/


### API修正メモ
* ×Admins_controller.php
197～205行

* form_validation_lang.php
37行

* Rooms_controller.php
265～267行