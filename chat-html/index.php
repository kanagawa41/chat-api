<?php
// 絶対パスで指定した方がいいらしい。
$url = 'http://www.llchat.xyz/llchat-hp/index.html';
header( "HTTP/1.1 301 Moved Permanently" ); 
header("Location: {$url}");
exit;