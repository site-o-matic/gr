<?php

class Database extends Singleton{
	
	protected static $instance = null;
	private $db_name = _DBNAME_;
	private $db_user = _DBUSER_;
	private $db_pass = _DBPASS_;

	private $db_connection;
	
	private $error_text = "";
	private $throw_errors = false;
	
	private $transaction_mode = false;

	public function __construct(){
		parent::__construct();
		# Connects to the database and records the handler

		$db_name = $this->db_name;
		$db_user = $this->db_user;
		$db_pass = $this->db_pass;

		$this->db_connection = mysql_connect(_DBHOST_,$db_user,$db_pass);
		mysql_query("SET NAMES 'utf8'");
		mysql_select_db($db_name) or die('Unable to connect to the database');
		mysql_query('SET SESSION group_concat_max_len = 1000000;');
	}

	public function close(){
		# Closes the database connection

		if ($this->transaction_mode){
			$this->transaction_cancel();
		}
		
		return mysql_close($this->db_connection);
	}

	/**
	 * Performs a query to the database and returns an array where each element is a row from results table returned by database as result of this query<br/>
	 * If nothing was returned by the database, this method returns an empty array<br/>
	 * @param String $query
	 * @return array
	 */
	public function query($query = ""){
		#necho($query."<hr/>");

		$result = mysql_query($query);
		
		$this->error_text = mysql_error();

		if (!is_resource($result)){
			# DB returned error or void
			return $this->end(array());
		}

		$rows = mysql_num_rows($result);

		if ($rows == 0){
			# Empty set
			return $this->end(array());
		}

		$return_array = array();

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$return_array[] = $row;
		}

		return $this->end($return_array);
	}

	public function status(){
		return $this->db_connection;
	}
	
	/**
	 * 
	 * @return Database
	 */
	public static function instance(){
		return self::getInstance();
	}
	
	
	public function getRow($table, $field, $value){
		$table = mysql_real_escape_string($table);
		$field = mysql_real_escape_string($field);
		$value = mysql_real_escape_string($value);
		return $this->end($this->query("SELECT * FROM `$table` WHERE `$field` = '$value'"));
	}
	
	const Q_INSERT = 11;
	const Q_UPDATE = 12;
	public static function makeQuery($data_array, $table, $type = self::Q_INSERT, $escaped = true){
		if (!is_string($table) || !is_array($data_array) || count($data_array) == 0){
			return "";
		}
		$headers = array();
		$values = array();
		$pairs = array();
		foreach($data_array as $header => $value){
			$header = "`".($escaped?mysql_real_escape_string($header):$header)."`";
			$headers[] = $header;
			if ($value !== null){
				$value = "'".($escaped?mysql_real_escape_string($value):$value)."'";
			}
			else {
				$value = 'NULL';
			}
			$values[] = $value;
			$pairs[] = "$header = $value";
		}
		
		if ($type == self::Q_INSERT){
			return "INSERT INTO `$table` (".  implode(", ", $headers).") VALUES (".  implode(", ", $values).")";
		}
		
		if ($type == self::Q_UPDATE){
			return "UPDATE `$table` SET ".  implode(", ", $pairs);
		}
		
		return "";
	}
	
	public function insert($table = "", $columns = array(), $values = array()){
		$query = "INSERT ".$this->new_tuple_query($table, $columns, $values);
		return $this->end(Database::instance()->query($query));
	}
	
	public function replace($table = "", $columns = array(), $values = array()){
		$query = "REPLACE ".$this->new_tuple_query($table, $columns, $values);
		return $this->end(Database::instance()->query($query));
	}
	
	private function new_tuple_query($table = "", $columns = array(), $values = array()){
		if (!is_array($columns) || !is_array($values) || empty($columns)){
			throw new Exception("Two non empty arrays required for columns and values");
		}
		$count_c = count($columns);
		$count_v = count($values);
		
		if ($count_c != $count_v){
			throw new Exception("Columns and values arrays must be equal size");
		}

		
		$table = mysql_real_escape_string($table);
		
		$string_c = "";
		$string_v = "";
		
		for($i = 0; $i < $count_c; $i++){
			if ($i != 0){
				$string_c .= ", ";
				$string_v .= ", ";
			}
			$string_c .= "`".mysql_real_escape_string($columns[$i])."`";
			if ($values[$i] === null){
				$string_v .= 'NULL';
			}
			else {
				$string_v .= "'".mysql_real_escape_string($values[$i])."'";
			}
		}
		return " INTO `$table` ($string_c) VALUES ($string_v)";
	}
	
	public function delete_conditioned($table, $condition){
		$table = mysql_real_escape_string($table);
		
		if (gettype($condition) != "object" || get_class($condition) != "Condition"){
			return false;
		}
		
		$query = "DELETE FROM `$table` WHERE ".$condition->toString();
		
		Database::instance()->query($query);
		return $this->end(true);
	}
	
	public function backup($prefix){
		foreach(Database::instance()->tables() as $table){
			$backup_file = $prefix.$table.'.sql';
			if (!mysql_query("SELECT * INTO OUTFILE '$backup_file' FROM `$table`")){
				throw new Exception("Could not back up `$table`: ".mysql_error());
			}
		}
	}
	
	public function restore($prefix){
		foreach(glob($prefix.'*') as $file){
			$table = substr($file, strlen($prefix));
			$table = substr($table, 0, strrpos($table, '.'));
			if (!mysql_query("LOAD DATA INFILE '$file' INTO TABLE `$table`")){
				throw new Exception("Could not restore `$table`: ".mysql_error());
			}
		}
	}
	
	public function empty_db($except = array()){
		foreach(array_diff(Database::instance()->tables(), $except) as $table){
			Database::instance()->query("TRUNCATE `$table`");
		}
	}
	
	public function tables(){
		$table_data = Database::instance()->query('SHOW TABLES');
		$table_data = array_map('array_values',$table_data);
		$table_data = call_user_func_array('array_merge',$table_data);
		return $table_data;
	}

	public function error(){
		return ($this->error_text != "");
	}
	
	public function error_text(){
		return $this->error_text;
	}
	
	public function transaction_start(){
		$this->transaction_mode = true;
		mysql_query("START TRANSACTION");
	}
	public function transaction_commit(){
		$this->transaction_mode = false;
		mysql_query("COMMIT");
	}
	public function transaction_cancel(){
		$this->transaction_mode = false;
		mysql_query("ROLLBACK");
	}
	
	public function transaction_mode(){
		return $this->transaction_mode;
	}
	
	/**
	 * 
	 * @param String $table name of the table
	 * @param Array $rows in format array(($key1 => $value1, $key2 => $value2), ($key1 => $value1, $key2 => $value2))
	 * @return boolean
	 */
	public function mass_insert($table, $rows = array(), $ignore_errors = false){
		if (empty($rows)){
			return true;
		}
		
		$table = mysql_real_escape_string($table);
		
		$headers = array_map("mysql_real_escape_string", array_keys($rows[0]));
		
		$IGNORE = '';
		if ($ignore_errors){
			$IGNORE = 'IGNORE';
		}
		
		$query = "INSERT $IGNORE INTO `$table` (`".implode("`, `", $headers)."`) VALUES ";
		
		$rows = array_map("array_values",$rows);
		$rows = array_map(function($row){return "(".implode(", ",array_map(function($value){
			if ($value === null){
				$value = 'NULL';
			}
			else {
				$value = "'".mysql_real_escape_string($value)."'";
			}
			return $value;
		},$row)).")";},$rows);
		$rows = implode(",\n",$rows);
		
		$query = "$query $rows ;";
		#necho($query);
		#return;
		return $this->end($this->query($query));
	}
	
	public function query_count($query = ""){
		$result = mysql_query("SELECT COUNT(*) AS `count` FROM ($query) AS `som_virtual_table_1`");
		$this->error_text = mysql_error();

		if (!is_resource($result)){
			return $this->end(array());
		}
		return array_value_by_index(mysql_fetch_assoc($result),'count');
	}
	
	public function query_map($query = "", $structure = array(), $append_columns = array()){
		$result = mysql_query($query);
		
		$this->error_text = mysql_error();

		if (!is_resource($result)){
			if($this->error()){
				# DB returned error
				throw new Exception($this->error_text());
			}
			
			# Or void
			return $this->end(array());
		}

		$rows = mysql_num_rows($result);

		if ($rows == 0){
			# Empty set
			return $this->end(array());
		}

		$return_array = array();
		
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if (empty($structure)){
				# No special structure required, just fill in return array
				$return_array[] = $row;
				continue;
			}
			$return_array = self::nest_value($return_array, $structure, $row, $append_columns);
		}

		return $this->end($return_array);
	}
	
	private static function nest_value($return_array = array(), $structure = array(), $data_row = array(), $append_columns = array()){
		if((is_array($return_array) && is_array($return_array) && is_array($return_array) && is_array($append_columns)) === false){
			throw new Exception("Invalid parameters, arrays expected");
		}
		
		# Get the first key (should only have one key, but meh).
		reset($structure);
		$key = key($structure);
		
		if (empty($structure[$key])){
			if (!empty($append_columns)){
				foreach($append_columns as $append_key){
					if (!isset($data_row[$append_key])){
						throw new Exception("`$append_key` not found (or is NULL) in: ".implode(", ",array_keys($data_row)));
					}
					$data_row[$append_key] = array($data_row[$append_key]);
					if (isset($return_array[$data_row[$key]])){
						$data_row[$append_key] = array_merge($return_array[$data_row[$key]][$append_key], $data_row[$append_key]);
					}
				}
			}
			$return_array[$data_row[$key]] = $data_row;
			return $return_array;
		}
		
		if (!isset($data_row[$key])){
			throw new Exception("`$key` not found (or is NULL) in: ".implode(", ",array_keys($data_row)));
		}
		
		if(!isset($return_array[$data_row[$key]])){
			$return_array[$data_row[$key]] = array();
		}
		
		$return_array[$data_row[$key]] = self::nest_value($return_array[$data_row[$key]], $structure[$key], $data_row, $append_columns);
		
		return $return_array;
	}
	
	/**
	 * 
	 * @param String $query fully escaped query string
	 * @param array $key name of the attribute to become the key
	 * @param array $value name of the attribute to become the value
	 * @return array $key => $value pair array
	 */
	public function query_list($query, $key, $value){
		
		$result = mysql_query($query);
		
		$this->error_text = mysql_error();

		if (!is_resource($result)){
			if($this->error()){
				# DB returned error
				throw new Exception($this->error_text());
			}
			
			# Or void
			return $this->end(array());
		}

		$rows = mysql_num_rows($result);

		if ($rows == 0){
			# Empty set
			return $this->end(array());
		}

		$return_array = array();
		
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$return_array[$row[$key]] = $row[$value];
		}

		return $this->end($return_array);
	}
	
	public function throw_errors($boolean){
		$this->throw_errors = $boolean;
	}
	
	private function end($return = null){
		if ($this->throw_errors & $this->error()){
			throw new Exception($this->error_text);
		}
		return $return;
	}
}