<?php
// 上層のパス
define("BASE_PATH", 'assets/image/user_image');
define("API_URL", isset($_POST["url"]) ? $_POST["url"] : '');

// 全てのパラメータを正しい構造で受け取った時のみ実行
if (
    isset($_POST['room_hash'], $_FILES['upfile']['error']) &&
    is_int($_FILES['upfile']['error']) &&
    isset($_POST["url"]) &&
    is_string($_POST['room_hash'])
) {
    try {

        /* ファイルのチェック */
        switch ($_FILES['upfile']['error']) {
            case UPLOAD_ERR_OK:
                // エラー無し
                break;
            case UPLOAD_ERR_NO_FILE:
                // ファイル未選択
                throw new RuntimeException('File is not selected');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                // 許可サイズを超過
                throw new RuntimeException('File is too large');
            default:
                throw new RuntimeException('Unknown error');
        }
        if (!in_array(
            @exif_imagetype($_FILES['upfile']['tmp_name']),
            array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
            ),
            true
        )) {
            // JPEG, PNG, GIF ではない
            throw new RuntimeException('Unsupported image format');
        }
        if (!preg_match('/\A(?!\.)[\w.-]++(?<!\.)\z/', $_FILES['upfile']['name'])) {
            // 無効なファイル名
            throw new RuntimeException('Invalid filename: ' . $_FILES['upfile']['name']);
        }
        if (!preg_match('/(?<!\.php)(?<!\.cgi)(?<!\.py)(?<!\.rb)\z/i', $_FILES['upfile']['name'])) {
            // トロイの木馬を弾くため、実行可能な拡張子は禁止する
            throw new RuntimeException(
                'This extension is forbidden for security reason: ' .
                $_FILES['upfile']['name']
            );
        }

        // ファイルのサイズチェック(バイト) 1mb = 1000000, 1kb = 1000
        if(filesize($_FILES['upfile']['tmp_name']) > 1500000){
            throw new RuntimeException('File size too big');
        }

        // ファイルの大きさチェック
        $image_info = getimagesize($_FILES['upfile']['tmp_name']);
        if($image_info[0] > 1000){ // 横幅
            throw new RuntimeException('File size too big');
        }
        if($image_info[1] > 1000){ // 縦幅
            throw new RuntimeException('File size too big');
        }

        /* ディレクトリ作成 */
        $response = easyCurlforJson(API_URL . '/rooms/' . $_POST['room_hash']);

        if(isset($response['errors'])){
            print_json(['errors' => json_encode($response['errors'])]); return;
        } else if(empty($response['key'])){
            print_json(['errors' => ['room_hash' => 'Invalid room_hash']]); return;
        }
        $room_key = $response['key'];

        $response = easyCurlforJson(API_URL . '/rooms/' . $_POST['room_hash'] . '/members');

        if(isset($response['errors'])){
            print_json(['errors' => json_encode($response['errors'])]); return;
        } else if(empty($response['user_hash'])){
            print_json(['errors' => ['room_hash' => 'Invalid room_hash']]); return;
        }
 
        $user_hash = $response['user_hash'];

        // 上層パスを指定
        $path = BASE_PATH . '/' . $room_key . '/' . $user_hash;
        // ファイル名取得
        // $file_name_base = pathinfo($_FILES['upfile']['name'], PATHINFO_FILENAME) . '_' . date("ymdHis");
        $date = date("YmdHis");
        $file_name = $date . '.' . pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION);

        /* ディレクトリの生成 */
        $deep = 0;
        foreach (explode('/', $path) as $i => $dir) {
            if ($deep > 10) {
                // 10階層を超過
                throw new RuntimeException('Hierarchy is too deep');
            }
            if ($dir === '') {
                if ($dir !== '' && !$i) {
                    // 絶対パスを検知
                    throw new RuntimeException('Absolute path is not allowed');
                }
                // 空文字列はスキップ
                continue;
            }
            if ($dir === '.') {
                // 「.」はスキップする
                continue;
            }
            if (!preg_match('/\A(?!\.)[\w.-]++(?<!\.)\z/', $dir)) {
                // 無効なディレクトリ名
                throw new RuntimeException('Invalid directory name: ' . $dir);
            }
            if (!is_dir($dir)) {
                // ディレクトリが存在していなければ生成を試みる
                if (!mkdir($dir)) {
                    // ディレクトリ生成に失敗
                    throw new RuntimeException('Failed to create directory: ' . $dir);
                }
                // パーミッションを0777に設定
                chmod($dir, 0777);
                $msgs[] = array('blue', 'Created directory "' . $dir . '"');
            }
            // カレントディレクトリを移動
            chdir($dir);
            ++$deep;
        }

        /* ファイルの移動 */
        if (!move_uploaded_file($_FILES['upfile']['tmp_name'], $file_name)) {
            // ファイル移動に失敗
            throw new RuntimeException('Failed to save uploaded file');
        }

        resizeImage($file_name,300);

        // ファイルのパーミッションを確実に0644に設定する
        chmod($file_name, 0644);

        /* メッセージをセット */
        $msgs = [
            'path' => ($path === '' ? '.' : $path) . '/' . $file_name
        ];
        print_json($msgs); return;
    } catch (RuntimeException $e) {
        $msgs = ['errors' => ['upfile' => $e->getMessage()]];
        // $msgs = ['errors' => ['upfile' => 'アップロードするファイルの形式が正しくありません。']];
        print_json($msgs); return;
    }
} else {
  sleep(5);
}

/**
 * JSON形式で返却する。
 */
function print_json($data) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data);
}

/**
 * 簡単にCurlを実行する
 */
function easyCurlforJson($url){
    $curl = curl_init();
    // ユーザの存在チェックを行う
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);

    curl_close($curl);

    return json_decode($response, true);
}

/**
 * 画像をリサイズする。
 */
function resizeImage($image_path,$new_width,$dir = "."){
    list($width,$height,$type) = getimagesize($image_path);
    $new_height = round($height*$new_width/$width);
    $emp_img = imagecreatetruecolor($new_width,$new_height);
    switch($type){
        case IMAGETYPE_JPEG:
            $new_image = imagecreatefromjpeg($image_path);
            break;
        case IMAGETYPE_GIF:
            $new_image = imagecreatefromgif($image_path);
            break;
        case IMAGETYPE_PNG:
            imagealphablending($emp_img, false);
            imagesavealpha($emp_img, true);
            $new_image = imagecreatefrompng($image_path);
            break;
    }
    imagecopyresampled($emp_img,$new_image,0,0,0,0,$new_width,$new_height,$width,$height);

    // ファイル名取得
    $image_name = pathinfo($image_path, PATHINFO_FILENAME) . '_thumbnail';

    switch($type){
        case IMAGETYPE_JPEG:
            imagejpeg($emp_img,$dir."/".$image_name.".jpg");
            break;
        case IMAGETYPE_GIF:
            $bgcolor = imagecolorallocatealpha($new_image,0,0,0,127);
            imagefill($emp_img, 0, 0, $bgcolor);
            imagecolortransparent($emp_img,$bgcolor);
            imagegif($emp_img,$dir."/".$image_name.".gif");
            break;
        case IMAGETYPE_PNG:
            imagepng($emp_img,$dir."/".$image_name.".png");
            break;
    }
    imagedestroy($emp_img);
    imagedestroy($new_image);
}
