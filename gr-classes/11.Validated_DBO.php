<?php

class ValidatedDBO extends DatabaseObject{
	
	public function printValidationMethods(){
		foreach($this->attributes as $attr => $v){
			echo "
public function validated_$attr(\$arg = null){
	\$arg = trim(\$arg);
	throw new Exception('Not implemented');
	\$this->values_set[] = '$attr';
	return \$arg;
}
				";
		}
	}
	
	public function printMethods(){
		$attributes = $this->attributes;
		unset($attributes['id']);
		$attributes = array_keys($attributes);
		foreach($attributes as $attr){
			echo "	
	public function $attr(\$arg = null){
		return \$this->access(__FUNCTION__,\$arg);
	}

	public function validated_$attr(\$arg = null){
		\$arg = trim(\$arg);
		throw new Exception('Not implemented');
		unset(\$this->validate['$attr']);
		return \$arg;
	}
	"	;
		}
		echo "
	public function save(){
		foreach(array_keys(\$this->validate) as \$attr){
			\$validation_function = \"validated_\$attr\";
			\$this->\$validation_function(\$this->\$attr());
		}
		return parent::save();
	}
	
	/**
	* 
	* @return ".get_called_class()."
	*/
	public static function get(\$id = -1, \$array = array()){
		return parent::get(\$id, \$array);
	}";
	}
	
	protected function access($name = null, $arg = null){
		if ($arg === null){
			return $this->attributes[$name];
		}
		else {
			$validation_function = "validated_$name";
			$arg = $this->$validation_function($arg);
			$this->attributes[$name] = $arg;
		}
	}
	
}

?>