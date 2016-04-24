<?php

class DatabaseObject extends AttributeGetSetter{
	protected $attributes = array();
	protected $db;
	protected $table = "";
	
	protected static $DBOA_CACHE = array();
	protected static $DBO_CACHE = array();
	
	const C_TRUE = 10;
	const C_FALSE = 11;
	
	
	public function __construct($id = -1, $data = array()){
		$this->db = Database::instance();
		if (count($data) > 0){
			$this->attributes = $data;
		}
		else {
			if (validateID($id)){
				if (isset(self::$DBOA_CACHE[get_class($this)][$id])){
					$this->attributes = self::$DBOA_CACHE[get_class($this)][$id];
				}
				else {
					$data = $this->db->query("SELECT * FROM `$this->table` WHERE `id`='$id'");
					$this->attributes = @$data[0];

					self::$DBOA_CACHE[get_class($this)][$this->id()] = $this->attributes;
					self::$DBO_CACHE[get_called_class()][$this->id()] = $this;
				}
			}
		}
	}

	public function save(){
		if ($this->id() > 0){
			$this->db->query($this->queryUpdate());
		}
		else {
			$this->db->query($this->queryInsert());
			$this->attributes['id'] = mysql_insert_id();
		}
		
		self::$DBOA_CACHE[get_class($this)][$this->id()] = $this->attributes;
		
		if (Database::instance()->error()){
			#throw new Exception(Database::instance()->error_text());
			return false;
		}
		
		return true;
	}


	public function remove(){
		unset(self::$DBOA_CACHE[get_class($this)][$this->id()]);
		$this->db->query("DELETE FROM `$this->table` WHERE  `id` ='".$this->id()."'");
	}

	public function id(){
		return $this->access(__FUNCTION__,null);
	}

	protected function queryUpdate(){
		$query = "UPDATE `$this->table` SET ";
		$first = true;
		foreach($this->attributes as $k => $v){
			$v = mysql_real_escape_string($v);
			if (!$first){
				$query .=", ";
			}
			$query .= "`$k` = '$v'";
			$first = false;
		}
		$query .= " WHERE `id` = '".$this->id()."'";
		return $query;
	}

	protected function queryInsert(){
		$query1 = "INSERT INTO `$this->table` (";
		$query2 = ") VALUES (";
		$query3 = ");";
		$first = true;
		foreach($this->attributes as $k => $v){
			$v = mysql_real_escape_string($v);
			$query1 .= ($first?"":", ")."`$k`";
			$query2 .= $first?"NULL":", '$v'";
			$first = false;
		}
		$query = $query1 . $query2 . $query3;
		return $query;
	}
	
	/**
	 * This function is here for use with PHP<5.3<br/>
	 * Gets an object by a value from DB
	 * @param type $field - field name in DB table
	 * @param type $value - value of field in DB table
	 * @param type $asObject - true if an object should be returned, instead of an array representing DB table row
	 * @return type mixed - if $asObject is false, then DB table row as array (or empty array if no such tuple)<br/>if $asObject is true, an object of this class with values filled from DB; if no tuple found, then a fresh object with default values
	 */
	public function getByField($field, $value, $asObject = false){
		$field = mysql_real_escape_string($field);
		$value = mysql_real_escape_string($value);
		$data = $this->db->query("SELECT * FROM `$this->table` WHERE `$field` = '$value'");
		
		if (!$asObject){
			if (count($data) > 0){
				return $data[0];
			}
			else{
				return array();
			}
		}
		else {
			$class_name = get_class($this);
			return $class_name::get(@$data[0]['id']);
		}
	}
	
	/**
	 * PHP 5.3+
	 * @param type $field - field name in DB table
	 * @param type $value - value of field in DB table
	 * @param type $asObject - true if an object should be returned, instead of an array representing DB table row
	 * @return type mixed - if $asObject is false, then DB table row as array (or empty array if no such tuple)<br/>if $asObject is true, an object of this class with values filled from DB; if no tuple found, then a fresh object with default values
	 */
	public static function GET_BY_FIELD($field, $value, $asObject = false){
		return self::get()->getByField($field, $value, $asObject);
	}
	
	
	/**
	 * Builds an array of tuples (or DatabaseObjects of appropriate DatabaseObject subclass type) with given sorting and given conditions
	 * @param array $sorting - array of sorting instructions in form of "attribute" => "direction", ... <br/> e.g. array("name" => "ASC") <br/> Note that attribute name is case sensitive <br/> To get random order, just use array("RAND()")
	 * @param boolean $asObjects - true if DatabaseObjects of appropriate DatabaseObject subclass type are required, false if tuples from database are required (in form of MD array)
	 * @param array $conditions - MD array of conditions in a form of array("attribute","operator","value",["logic" => "logical_operator"]), ... <br/>e.g. array(array("id",">","5"),array("price","=","100","logic" => "and"))<br/> Note that attribute name is case sensitive<br/>Note that 'logic' can be omitted only for first or single condition. For all other conditions 'AND' will be used if 'logic' is omitted.
	 * @return mixed either MD array of tuples or array of DatabaseObjects of appropriate DatabaseObject subclass type
	 */
	public static function GET_ALL($sorting = array(), $asObjects = false, $conditions = array()){
		#echo "<br/><b>get_all ".get_called_class()."\n</b>";
		#print_r(debug_backtrace());
		$table = self::get()->getTable();
		
		$sorting_string = "";
		foreach ($sorting as $field => $dir){
			$sorting_string .= (($sorting_string == "")?"":", ")."`".  mysql_real_escape_string($field)."` ". strtoupper(mysql_real_escape_string($dir));
		}
		
		if (isset($sorting["RAND()"])){
			$sorting_string = "RAND()";
		}
		
		$condition_string = "";
		if (is_array($conditions)){
			foreach($conditions as $condition){
				$logic = "";
				if ($condition_string != ""){
					$logic = " AND ";
					if (isset($condition['logic'])){
						$condition['logic'] = trim(strtolower($condition['logic']));
						if (in_array($condition['logic'], array("or","and"))){
							$logic = strtoupper($condition['logic']);
						}
					}
				}
				$operator = trim(strtolower($condition[1]));
				if (!in_array($operator, array(">",">=","=","<=","<","!=","<>","like","not like","regexp"))){
					// allow only valid operations
					continue;
				}
				$attribute = mysql_real_escape_string(trim($condition[0]));
				$value = mysql_real_escape_string(trim($condition[2]));

				$condition_string .= " $logic ".((in_array(@$condition['prefix'],array("(")))?$condition['prefix']:"")." `$attribute` $operator '$value'".((in_array(@$condition['suffix'],array(")")))?" ".$condition['suffix']:"");
			}
		}
		else if (get_class($conditions) == "Condition"){
			$condition_string = $conditions->toString();
		}
		
		$query = "SELECT * FROM `$table` ".(($condition_string == "")?"":"WHERE")." $condition_string ".(($sorting_string == "")?"":"ORDER BY")." $sorting_string";
		#precho($query);
		$data = Database::instance()->query($query);
		
		if (!$asObjects){
			return $data;
		}
		else {
			$objects = array();
			foreach($data as $row){
				$objects[] = self::get($row['id'],$row);
			}
			return $objects;
		}
	}
	
	public static function GET_INSTANCE($args = null, $data = array()){
		$class = get_called_class();
		return $class::get($args, $data);
	}
	
	public function getTable(){
		return $this->table;
	}
	
	public static function wipeCache($all_classes = false){
		if ($all_classes){
			self::$DBOA_CACHE = array();
			return;
		}
		self::$DBOA_CACHE[get_called_class()] = array();
	}
	
	public function __toString(){
		return get_called_class().$this->id();
	}

	/**
	 * 
	 * @param int $id ID of the object to get
	 * @param array attributes array to create object with (instead of looking up by ID in the database)
	 * @return DatabaseObject
	 */
	public static function get($id = -1, $data = array()){
		$class = get_called_class();
		$id = @trim($id);
		if (!validateID($id)){
			$id = -1;
		}
		
		if (isset(self::$DBO_CACHE[$class][$id])){
			return self::$DBO_CACHE[$class][$id];
		}
		
		else return new $class($id,$data);
	}
	
	public static function get_class_constants(){
		$reflect = new ReflectionClass(get_called_class());
		return $reflect->getConstants();
	}
	
	public function attributes_as_array_caps($no_tags = false){
		$array = array();
		foreach($this->attributes as $attr_name => $attr_value){
			if (method_exists(get_called_class(),$attr_name)){
				$put = $this->$attr_name();
				if ($no_tags){
					$put = noTags($put);
				}
				$array[strtoupper($attr_name)] = $put;
			}
		}
		return $array;
	}
	
	public function attributes(){
		return $this->attributes;
	}
	
		
	/**
	 * Don't use unless for client sudden fantasies; Sets a value in the attributes array
	 * @param type $arg_name
	 * @param type $arg_value 
	 */
	public function force_set($arg_name, $arg_value){
		$this->attributes[$arg_name] = $arg_value;
	}

}

?>