<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Output extends CI_Output 
{

    function __construct()
    {
        parent::__construct();
        
        
    }

	/**
	 * json形式でレスポンスを出力する。
	 * 
	 * @param 配列オブジェクト
	 */
	public function set_json_output($data)
	{
		$this->set_content_type('application/json')->set_output(json_encode($data));
	}

	/**
	 * json形式でエラーのレスポンスを出力する。
	 * 
	 * @param メッセージ配列
	 */
	public function set_json_error_output($messages)
	{
		$data = array (
			'errors' => $messages
		);
		
		$this->set_json_output($data);
	}

}