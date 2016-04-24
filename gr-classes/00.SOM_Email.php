<?php

class SOMEmail {

	protected $attributes = array();
	private $crlf = "\n";
	
	protected static $smtp = null;
	
	public function __construct(){
		
		include_once('Mail.php');
		include_once('Mail/mime.php');
		
		if (sizeof($this->attributes) == 0){
			$this->attributes['text'] = "";
			$this->attributes['html'] = "";
			$this->attributes['from'] = "";
			$this->attributes['subject'] = "";
			$this->attributes['recipient'] = "";
			$this->attributes['attachments'] = array();
			$this->attributes['bcc'] = array();
			$this->attributes['cc'] = array();
		}
	}
	
	public function replyTo($email = null){
		if ($email === null){
			return $this->attributes['Reply-To'];
		}
		$this->attributes['Reply-To'] = $email;
	}
	
	public function send($detailed_feedback = false){
		
		if ($this->attributes['recipient'] == ""){
			if(count($this->attributes['cc']) > 0 ){
				$this->attributes['recipient'] = array_shift($this->attributes['cc']);
			}
		}
		
		$hdrs = array(
			'From'		=> $this->from(),
			'Subject'	=> $this->subject(),
			'To'		=> $this->attributes['recipient']
		);
		
		if (isset($this->attributes['Reply-To']) && trim($this->attributes['Reply-To'] != "")){
			$hdrs['Reply-To'] = $this->attributes['Reply-To'];
		}

		$mime = new Mail_mime();
		$mime->_build_params['html_charset']='UTF-8';
		$mime->_build_params['text_charset']='UTF-8';
		$mime->_build_params['head_charset']='UTF-8';
		$mime->_build_params['head_encoding']='base64';
		
		$mime->setTXTBody($this->text());
		$mime->setHTMLBody($this->html());
		foreach($this->attributes['attachments'] as $file){
			if ($file['sname'] === null){
				$mime->addAttachment($file['name'], $file['type']);
			}
			else {
				$mime->addAttachment($file['name'], $file['type'], $file['sname']);
			}
		}
		foreach($this->attributes['bcc'] as $bcc){
			$mime->addBcc($bcc);
		}
		foreach($this->attributes['cc'] as $cc){
			$mime->addCc($cc);
		}

		$body = $mime->get();
		$hdrs = $mime->headers($hdrs);

		if (self::$smtp == null){
			$mail =& Mail::factory('mail');
		}
		else {
			$mail =& Mail::factory('smtp', self::$smtp);
		}

		$result = $mail->send($this->recipient(), $hdrs, $body);

		if (!$detailed_feedback){
			return $result === true || $result === 1;
		}
		
		if (PEAR::isError($result)) {
			throw new Exception($result->getMessage(), -10);
		}
		else {
			return true;
		}
		
	}
	
	public function text($arg = null){
		return $this->access(__FUNCTION__,$arg);
	}

	public function html($arg = null){
		return $this->access(__FUNCTION__,$arg);
	}

	public function from($arg = null){
		return $this->access(__FUNCTION__,$arg);
	}

	public function subject($arg = null){
		return $this->access(__FUNCTION__,$arg);
	}

	public function recipient(){
		return $this->attributes['recipient'];
	}
	
	public function addRecipient($recipient){
		if($this->attributes['recipient'] == ""){
			$this->attributes['recipient'] = $recipient;
		}
		else {
			$this->addCC($recipient);
		}
	}
	public function addBCC($recipient){
		$this->attributes['bcc'][] = $recipient;
	}
	public function addCC($recipient){
		$this->attributes['cc'][] = $recipient;
	}
	
	public function addAttachment($filename,$type = "text/plain", $suggested_name = null){
		$this->attributes['attachments'][] = array(
			"name" => $filename,
			"type" => $type,
			"sname"=> $suggested_name
		);
	}
	
	protected function access($name = null, $arg = null){
		if ($arg === null){
			return $this->attributes[$name];
		}
		else {
			$this->attributes[$name] = $arg;
		}
	}
	
	/**
	 * array('host' => "", 'username' => "", 'password' => "")
	 * @param type $credentials 
	 * @return true if success, false if credentials are missing
	 */
	public static function useSMTP($credentials){
		if ($credentials == null){
			self::$smtp = null;
			return true;
		}
		if (!is_array($credentials) || !isset($credentials['host']) || !isset($credentials['username']) || !isset($credentials['password'])){
			return false;
		}
		self::$smtp = array_merge($credentials, array('auth' => true));
		return true;
	}
	
	public function get_all_recipients(){
		$array = array($this->recipient());
		$array = array_merge($array, $this->attributes['bcc']);
		$array = array_merge($array, $this->attributes['cc']);
		return $array;
	}
	
	public function clear_recipients(){
		$this->attributes['recipient'] = "";
		$this->attributes['bcc'] = array();
		$this->attributes['cc'] = array();
	}
	
}

?>