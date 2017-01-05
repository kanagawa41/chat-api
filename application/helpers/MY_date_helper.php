<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 第一引数の方が日付が大きい場合はtrue、でなければfalse
 */
function compare_date($raw_dateA, $raw_dateB){
	$temp_date = new DateTime($raw_dateA);
	$dateA = $temp_date->format('Y-m-d');
	$temp_date = new DateTime($raw_dateB);
	$dateB = $temp_date->format('Y-m-d');

	return strtotime($dateA) > strtotime($dateB);
}

/**
 * 曜日を算出し返却する。
 */
function get_days($date){
	$days = array( '日', '月', '火', '水', '木', '金', '土' );
	return $days[date('w', strtotime($date))];
}