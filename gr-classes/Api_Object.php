<?php

class APIObject extends Singleton {
	
	private $json = array(
		'code'	=>	'0',
		'errors'=>	array(),
		'data'	=>	array()
	);

	public function addError($code,$message){
		$this->json['errors'][] = array(
			'code'		=>	$code,
			'message'	=>	$message
		);
	}
	
	public function addData($data_name,$data_value){
		$this->json['data'][$data_name] = $data_value;
	}
	
	public function end($code){
		$this->json['code'] = $code;
		Database::instance()->close();
		die(json_encode($this->json));
	}
	
	public function json($code){
		$this->json['code'] = $code;
		return json_encode($this->json);
	}
	
	public function end_bad_request(){
		$this->addError(-1, "Bad request");
		$this->end(-1);
	}
	
	/**
	 * @return APIObject
	 */
	public static function instance(){
		return self::getInstance();
	}
}
?>
