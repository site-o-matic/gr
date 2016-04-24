<?php
class SOM_SMART_ARRAY {
	private $array = array();
	private $member_size = 0;
	
	public function __construct($member_size) {
		$this->member_size = $member_size;
	}
	
	public function add($element){
		if (empty($this->array) || $this->member_size > 0 && count($this->array[count($this->array)-1]) == $this->member_size){
			$this->array[] = array();
		}
		$this->array[count($this->array)-1][] = $element;
	}
	
	public function get_array(){
		return $this->array;
	}
}
?>