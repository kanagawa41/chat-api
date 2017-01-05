<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Output extends CI_Output 
{

    function __construct()
    {
        parent::__construct();
        
        
    }

	/**
	 * sse対応の形式でレスポンスを出力する。
	 * 
	 * @param 配列オブジェクト
	 */
	public function set_sse_output($event, $data)
	{
		echo 'event:' . $event . "\n";
		echo 'data:' . json_encode($data) . "\n\n";
	}

	/**
	 * sse対応の形式でエラーのレスポンスを出力する。
	 * 
	 * @param 配列オブジェクト
	 */
	public function set_sse_error_output($data)
	{
		echo 'event:' . "errors" . "\n";
		echo 'data:' . json_encode($data) . "\n\n";
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