<?php

class AttributeGetSetter {
	
	protected $attributes = array();
	
	public function printMethods(){
		$attributes = $this->attributes;
		unset($attributes['id']);
		$attributes = array_keys($attributes);
		foreach($attributes as $attr){
			echo "	
	public function $attr(\$arg = null){
		if (\$arg !== null){
			\$arg = trim(\$arg);
		}
		return \$this->access(__FUNCTION__,\$arg);
	}

	public function validate_$attr(){
		if (\$this->$attr()){
			throw new Exception('Not yet implemented: '.\$this->$attr());
		}
	}
	";
		}
		
		echo "
	public function save(){"
		.  call_user_func(function()use($attributes){
			$text = '';
			foreach($attributes as $attr){
				$text .= "\n		\$this->validate_$attr();";
			}
			return $text;
		}).
"
		return parent::save();
	}
	";

		echo "
	/**
	* 
	* @return ".get_called_class()."
	*/
	public static function get(\$id = -1, \$array = array()){
		return parent::get(\$id, \$array);
	}";
	}
	
	public function asArray(){
		return $this->attributes;
	}
	
	public function replaceAttributes($data){
		foreach($data as $attr => $val){
			if (isset($this->attributes[$attr])){
				$this->attributes[$attr] = $val;
			}
		}
	}
	
	public function hasAttr($attr){
		return isset($this->attributes[$attr]);
	}
	
	protected function access($name = null, $arg = null){
		if ($arg === null){
			return $this->attributes[$name];
		}
		else {
			$this->attributes[$name] = $arg;
		}
	}
}
?>
