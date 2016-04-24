<?php

abstract class SOMSetting extends DatabaseObject{
	
	const S_YES = 2;
	const S_NO = 3;
	
	public function __construct($id = -1){

		$this->table = "setting";

		parent::__construct($id);
		
		if (sizeof($this->attributes) == 0){
			$this->attributes['id']		= -1;
			$this->attributes['name']	= "";
			$this->attributes['value']	= "";
		}
	}

	public function name($arg = null){
		return $this->access(__FUNCTION__,$arg);
	}
	
	public function value($arg = null){
		return $this->access(__FUNCTION__,$arg);
	}
	
	/**
	 * Checks that all settings defined in static $settings array are present in the database, otherwise creates the missing settings with given default values
	 * Also defines constants in format _SETTING_NAME_ (upper case, spaces replaced by underscore)
	 */
	public static function initialise(){
		$settings = static::default_values();
		$current_settings = self::GET_ALL(array(),false);
		foreach($current_settings as $cur_setting){
			define("_".str_replace(" ","_",strtoupper($cur_setting['name']))."_",$cur_setting['value']);
			unset($settings[$cur_setting['name']]);
		}
		foreach($settings as $ns_n => $ns_v){
			$ns = Setting::get();
			$ns->name($ns_n);
			$ns->value($ns_v);
			if ($ns->save()){
				$cur_setting = $ns->asArray();
				define("_".str_replace(" ","_",strtoupper($cur_setting['name']))."_",$cur_setting['value']);
			}
		}
	}
	
	protected static function default_values(){
		throw new Exception("Not implemented");
	}
}

?>