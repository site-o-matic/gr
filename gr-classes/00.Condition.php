<?php

class Condition {
	const T_AND = " AND ";
	const T_OR = " OR ";
	
	private $conditions;
	private $type;
	
	public function __construct($type, $conditions = array()) {
		$this->type = $type;
		$this->conditions = $conditions;
	}
	
	public function toString(){
		$tokens = array();
		if (empty($this->conditions)){
			return "(1)";
		}
		foreach($this->conditions as $key => $val){
			if (is_object($val) && is_a($val,"Condition")){
				$tokens[] = $val->toString();
			}
			else {
				$tokens[] = $val;
			}
		}
		return "(".implode($this->type,$tokens).")";
	}
	
	public static function make_simple_condition_escaped($a,$b,$operation){
		$value = mysql_real_escape_string($b);
		if (!in_array(strtolower($operation), array("in", "not in"))){
			$value = "'$value'";
		}
		return "`".mysql_real_escape_string($a)."` $operation $value";
	}
}

?>