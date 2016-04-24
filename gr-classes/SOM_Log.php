<?php

class SOMLog{
	
	const LOG_FPATH = "event_log/";
	
	private $entries = array();
	
	private $string = null;
	
	public function __construct() {
		global $_SERVER;
		
		$this->entries = array(
			"start"		=>	microtime(true),
			"ip"		=>	$_SERVER['REMOTE_ADDR'],
			"end"		=>	0,
			"duration"	=>	0,
			"memory"	=>	0,
			"script"	=>	str_replace("	", "", @$_SERVER['SCRIPT_NAME']),
			"path"		=>	str_replace("	", "", @$_SERVER['PATH_INFO']),
			"query"		=>	str_replace("	", "", @$_SERVER['QUERY_STRING']),
			"extra"		=>	"",
			"agent"		=>	@$_SERVER['HTTP_USER_AGENT'],
			"referer"	=>	@$_SERVER['HTTP_REFERER']
		);
	}
	
	public function setExtra($extra){
		$this->entries['extra'] .= str_replace("	", "", $extra);
	}
	
	public function getExtra(){
		return $this->entries['extra'];
	}
	
	public function save(){
		return file_put_contents(self::LOG_FPATH."log_".date("Y-m-d",time()).".log", $this->toString(), FILE_APPEND | LOCK_EX);
	}
	
	public function toString(){
		if ($this->string === null){
			$this->entries['end'] = microtime(true);
			$this->entries['duration'] = round($this->entries['end'] - $this->entries['start'], 2);
			$this->entries['start'] = date("Y/m/d H:i:s",$this->entries['start']);
			$this->entries['end'] = date("Y/m/d H:i:s",$this->entries['end']);
			$this->entries['memory'] = memory_get_peak_usage(true)/1024/1024;
			$data = mb_ereg_replace("(\n|\r)","",implode("	",$this->entries))."\n";
			$data = iconv("UTF-8","CP1251",$data);
			$this->string = $data;
		}
		return $this->string;
	}
}

?>
