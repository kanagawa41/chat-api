<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 定数を呼び出すためのクラス。
 * librariesを呼び出す際はインスタンス化してしまうため間接的な手法を用いている。
 */
class ClassLoad { 
	function __construct(){
		foreach (glob(APPPATH."libraries/defines/*.php") as $filename){
			require_once($filename);
		}
	}
}