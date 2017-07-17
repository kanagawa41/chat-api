<?php
if(empty($_GET["room_hash"])){
    sleep(5); return;
}

header("Content-Type: text/event-stream; charset=UTF-8");
header('Cache-Control: no-cache');
header("Connection: keep-alive");

// 実行時間を無制限
// ※phpの起動時間を指定されている場合、APIを叩いた直前で落ちてしまい不整合が発生するのを防ぐ
//  起動時間を自前で計測して以上になった場合に処理を終了するようにしている。
ini_set("max_execution_time", 0);

// 環境ごと変更する必要がある
define("BASE_URL", 'http://localhost/chat-api');
define("WAIT_TIME", 3); // 待機時間
define("SUCESSION_CONECTIN_TIME", 180); // 起動時間
define("START_TIME", microtime(true)); // スタートタイム


// ログインテスト
// var_dump(json_decode(easyCurl(BASE_URL . '/rooms/' . $_GET["room_hash"] . '/members'), true));
// メッセージテスト
// var_dump(json_decode(easyCurl(BASE_URL . '/rooms/' . $_GET["room_hash"] . '/messages'), true));

// 接続を促すためレスポンスを返す
print_chunk(null, '');

execute($_GET["room_hash"]);

/**
 * チャットのメッセージ一覧を取得し続けます。
 */
function execute($room_hash) {
    $raw_response = easyCurl(BASE_URL . '/rooms/' . $_GET["room_hash"] . '/members');
    $response = json_decode($raw_response, true);

    if($response['errors']){
        print_chunk('errors', $response['errors']);
        return;
    }

    // 処理を終えないように待機させる。
    while((microtime(true) - START_TIME < SUCESSION_CONECTIN_TIME)){
        $raw_response = easyCurl(BASE_URL . '/rooms/' . $_GET["room_hash"] . '/messages');

        // 空ではない場合
        if($raw_response !== '[]'){
            print_chunk('messages', $raw_response);
        } else {
            print_chunk(null, '');
        }

        sleep(WAIT_TIME);
    }

    print_chunk('timeout', $raw_response);
}

/**
 * 簡単にCurlを実行する
 */
function easyCurl($url){
    $curl = curl_init();
    // ユーザの存在チェックを行う
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}

/**
 * メッセージを手軽に投げれる
 */
function print_chunk($event, $data){
    if(isset($event)){
        echo 'event:' . $event . "\n";
    }
    echo 'data:' . $data . "\n\n";

    ob_flush();
    flush();
}