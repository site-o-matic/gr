<?php

/*
 * Copyright (c) 2010-2013, Site-o-Matic.net; All rights reserved.
 */

class SOMSmartDataFilter {
	public static function get_data_direct($class, $column, $sorting, $condition_operation, $condition_value, $limit = 0, $filters = ""){
		$columns = explode("+",$column);
		
		$_conditions = array();
		foreach($columns as $col_name){
			$_conditions[] = Condition::make_simple_condition_escaped($col_name, $condition_value, $condition_operation);
		}
		
		$_filters = array();
		foreach(explode(";",$filters) as $_fdata){
			$_f = explode(",",$_fdata);
			if (count($_f) == 3){
				$_filters[] = Condition::make_simple_condition_escaped($_f[0], $_f[2], $_f[1]);
			}
		}
		
		
		$conditions = new Condition(Condition::T_AND, array(
			new Condition(Condition::T_OR, $_conditions),
			new Condition(Condition::T_AND, $_filters)
		));
		
		return $class::GET_ALL(array($columns[0] => $sorting), false, $conditions);
	}
}

?>
