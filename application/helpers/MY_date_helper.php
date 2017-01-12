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

/**
 * 現在日付を返却する。
 */
function get_now_date(){
    $CI = &get_instance();
	$now_date = $CI->db->select('datetime(CURRENT_TIMESTAMP) as now_date')->get()->row()->now_date;
	$temp_date = new DateTime($now_date);
	return $temp_date->format('Y-m-d');
}