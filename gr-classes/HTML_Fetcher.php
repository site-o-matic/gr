<?php

/**
 * Fetches HTML from templates<br/>
 * Requires _ROOT_ to be set
 */
class HTMLFetcher extends Singleton{

	protected static $instance = null;
    private $templates = array();
	private $pattern = "templates/*.*";
	
	public function __construct(){
		
		parent::__construct();
		
		$this->add_templates($this->pattern);
	}
	
	public function add_templates($pattern){
		if (!isset($pattern) || $pattern === null){
			$pattern = $this->pattern;
		}
		foreach(rglob($this->pattern) as $file){
			$name = substr($file, mb_strlen(dirname($this->pattern)."/","UTF-8"));
			if ($name != "index.html"){
				$tpl_name = substr($name, 0,strrpos($name, "."));
				$tpl_content = file_get_contents($file);
				$this->templates[$tpl_name] = $tpl_content;
			}
		}
	}

	/**
	 * Fetches HTML code from template and given array of parameters<br/>
	 * Requires _ROOT_ to be set
	 * @param String $tmpl_name - file name of the template
	 * @param array $name_val_hash - array in format $k - field name, $v - field value
	 * @return String - fetched HTML
	 */
	public function fetchHTML($tmpl_name = null,$name_val_hash = array()){
		if ($tmpl_name == null){
			return "";
		}
		#precho($tmpl_name);
		$code = $this->templates[$tmpl_name];
		if (!is_array($name_val_hash)){
			return $code;
		}
		
		return $this->fetch_text($code, $name_val_hash);
	}
	
	/**
	 * 
	 * @param type $code
	 * @param type $name_val_hash
	 * @param type $literal assume variables are not wrapped in {% %}
	 * @return type
	 */
	public function fetch_text($code = '', $name_val_hash = array(), $literal = false){
		if (!$literal){
			$code = str_replace("{%ROOT%}", _ROOT_, $code);
		}
		foreach($name_val_hash as $field => $value){
			if (is_array($value)){
				throw new Exception("Array given: ".print_r($value,true));
			}
			if (!$literal){
				$code = str_replace("{%$field%}", $value, $code);
			}
			else {
				$code = str_replace($field, $value, $code);
			}
		}
		return $code;
	}

	/**
	 * Returns a HTMLFetcher
	 * @return HTMLFetcher
	 */
	public static function instance($path_pattern = null){
		if ($path_pattern !== null){
			$this->pattern = $path_pattern;
		}
		return self::getInstance();
	}

}
?>