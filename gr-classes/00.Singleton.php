<?php

/**
 * Subclass is required to call parent's constructor 
 */
abstract class Singleton{
	
	private static $instances = array();
	
	protected function __construct(){
		
		self::$instances[get_called_class()] = $this;
	}

	protected static function getInstance(){
		$class = get_called_class();
		if (!isset(self::$instances[$class])){
			$object = new $class();
			self::$instances[$class] = $object;
		}
		
		return self::$instances[$class];
	}
	
	public static function instance(){
		throw new Exception("Not implemented");
	}
}
?>