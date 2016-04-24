<?php

class GlobalEnv {
	public static $_POST = array();
	public static $_GET = array();
	public static $_DATA = array();
	public static $_NOW = 1;
	public static $_FILES = array();
	
	/**
	 * Checks whether a given attribute of $_POST or $_GET is equal to given value
	 * @param type $item attribute to check
	 * @param type $to value to check against
	 * @param type $safe if true === comparison is used, == otherwise
	 * @return boolean false if $item is not sent via $_GET or $_POST or if $item is not equal to $to
	 */
	public static function equals($item, $to, $safe = false){
		if(!isset(self::$_DATA[$item])){
			return false;
		}
		
		if ($safe === true){
			return self::$_DATA[$item] === $to;
		}
		
		return self::$_DATA[$item] == $to;
	}
	
	/**
	 * Checks multiple constraints on an array of given items in $_GET or $_POST against an array of allowed values each
	 * @param type $items_and_values array in form $item => array($value1, $value2, ...)
	 * @param type $allow_missing true if the missing $item is also allowed
	 * @param type $safe if true === comparison is used, == otherwise
	 * @return boolean true if $allow_missing and $item is missing from $_GET or $_POST, or if $item is equal to one of the allowed values, observing $safe option
	 */
	public static function check_simple_constraints($items_and_values, $allow_missing = true, $safe = false){
		foreach($items_and_values as $item => $values){
			
			$missing = !isset(self::$_DATA[$item]);
			
			if (!$allow_missing && $missing){
				return false;
			}
			
			if ($allow_missing && $missing){
				return true;
			}
			
			$match = false;
			
			foreach($values as $value){
				if (self::equals($item, $value, $safe)){
					$match = true;
				}
			}
			
			if (!$match){
				return false;
			}
		}
		return true;
	}
	
	public static function terminate($message){
		Database::instance()->close();
		die($message);
	}
}
GlobalEnv::$_POST = $_POST;
GlobalEnv::$_GET = $_GET;
GlobalEnv::$_NOW = time();
GlobalEnv::$_FILES = $_FILES;

if (isset($_POST) && !empty($_POST)){
	GlobalEnv::$_DATA = GlobalEnv::$_POST;
}
else {
	GlobalEnv::$_DATA = GlobalEnv::$_GET;
}