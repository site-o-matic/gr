<?php
function getIncludeContents($filename) {
	if (is_file($filename)) {
		ob_start();
		include $filename;
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
	return false;
}

function validateID($id){
	if (!isset($id) || !ctype_digit($id) || $id <= 0){
		return false;
	}
	return true;
}

function postVarIs($var,$val){
	if (isset($var) && $var == $val){
		return true;
	}
	return false;
}

function postVarTrimmedOrEmpty($var){
	if (!isset(GlobalEnv::$_POST[$var])){
		return "";
	}
	else return trim(GlobalEnv::$_POST[$var]);
}

function validEmail($email){
	$isValid = true;
	$atIndex = strrpos($email, "@");
	if (is_bool($atIndex) && !$atIndex){
		$isValid = false;
	}
	else{
		$domain = substr($email, $atIndex+1);
		$local = substr($email, 0, $atIndex);
		$localLen = strlen($local);
		$domainLen = strlen($domain);
		if ($localLen < 1 || $localLen > 64){
			// local part length exceeded
			$isValid = false;
		}
		else if ($domainLen < 1 || $domainLen > 255){
			// domain part length exceeded
			$isValid = false;
		}
		else if ($local[0] == '.' || $local[$localLen-1] == '.'){
			// local part starts or ends with '.'
			$isValid = false;
		}
			else if (preg_match('/\\.\\./', $local)){
			// local part has two consecutive dots
			$isValid = false;
		}
		else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)){
			// character not valid in domain part
			$isValid = false;
		}
		else if (preg_match('/\\.\\./', $domain)){
			// domain part has two consecutive dots
			$isValid = false;
		}
		else if	(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',	str_replace("\\\\","",$local))){
			// character not valid in local part unless 
			// local part is quoted
			if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))){
				$isValid = false;
			}
		}
		
		if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))){
			// domain not found in DNS
			$isValid = false;
		}
	}
	return $isValid;
}

/**
* Puts &#13; (carriage return) character between all characters in the string making it unreadable for spam bots but preserving markup<br/>
* Should only be used on single line small bits of text like phone numbers or email addresses
* @param String $string
*/
function antiSpamSalt($string = ""){
	$array = str_split($string, 1);
	return implode("&#13;",$array);
}

/**
*
* @return String encrypted session id
*/
function makeSessionKey(){
	$ip = "localhost";
	if(isset($_SERVER['REMOTE_ADDR'])){
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	
	return md5(session_id().$ip);
}

/**
	*
	* @param array $items - array of items in format ($val => $option, $val => $option)
	* @param mixed $selected - $val item which is selected in the list, default is null
	* @param boolean $header - default true, if true, will wrap <option> tags list with <select>; use false if you want to use your own <select>
	* @param mixed $default_option - Either false, or array('value' => 'value', 'text' => 'text to display', 'disabled' => false, 'persistent' => false)
	* @return String - html of the select list
	*/
function generateSelectList($items,$selected = null,$header = true, $default_option = false){

	if (!is_array($items) || count($items) < 1){
		return "";
	}

	$selected .= "";

	$html = "";
	
	if ($default_option !== false && ($default_option['persistent'] || !in_array($selected, array_keys($items)) || $selected == "")){
		$selected_switch = ((!in_array($selected, array_keys($items)) || $selected == "")?'selected="selected"':"");
		$value = $default_option['value'];
		$disabled = (($default_option['disabled'])?'disabled="disabled"':'');
		$text = $default_option['text'];
		$html = "<option $selected_switch value=\"$value\" $disabled>$text</option>";
	}

	foreach ($items as $value => $text){
		$selected_switch = (($selected==$value)?"selected=\"selected\"":"");
		$html .= "\n<option value=\"$value\" $selected_switch>$text</option>";
	}
	
	if ($header){
		$html = "<select>\n$html</select>";
	}

	return $html;
}

function textToHTML($text = "",$preserve_tags = false, $empty_line_nbsp = false){
	if (trim($text) == ""){
		return "";
	}
	$lines = explode("\n",trim($text));
	foreach ($lines as &$line){
		if (!$preserve_tags){
			$line = htmlspecialchars($line,ENT_QUOTES);
		}
		
		if ($empty_line_nbsp && trim($line) == ""){
			$line = "&nbsp;";
		}
		
		$line = "<p>$line</p>";
	}
	return implode("\n",$lines);
}

function pecho($string){
	echo "<p>$string</p>";
}

function necho($string){
	echo $string."\n";
}

function elapsed_time($timestamp, $precision = 2, $defaults = array('year' => 31557600, 'month' => 2629800, 'week' => 604800, 'day' => 86400, 'hour' => 3600, 'min' => 60, 'sec' => 1)) {
	$time = time() - $timestamp;
	$i = 0;
	foreach($defaults as $k => $v) {
		$$k = floor($time/$v);
		if ($$k) $i++;
		$time = $i >= $precision ? 0 : $time - $$k * $v;
		$s = $$k > 1 ? 's' : '';
		$$k = $$k ? $$k.' '.$k.$s.' ' : '';
		@$result .= $$k;
	}
	return $result ;
}

function noTags($html){
	$text = str_replace("<", "&lt;", $html);
	$text = str_replace(">", "&gt;", $text);
	$text = str_replace("\"", "&quot;", $text);
	$text = str_replace("'", "&#39;", $text);
	return $text;
}

function calculateTextBox($text,$fontFile,$fontSize,$fontAngle) { 
    /************ 
    simple function that calculates the *exact* bounding box (single pixel precision). 
    The function returns an associative array with these keys: 
    left, top:  coordinates you will pass to imagettftext 
    width, height: dimension of the image you have to create 
    *************/ 
    $rect = imagettfbbox($fontSize,$fontAngle,$fontFile,$text); 
    $minX = min(array($rect[0],$rect[2],$rect[4],$rect[6])); 
    $maxX = max(array($rect[0],$rect[2],$rect[4],$rect[6])); 
    $minY = min(array($rect[1],$rect[3],$rect[5],$rect[7])); 
    $maxY = max(array($rect[1],$rect[3],$rect[5],$rect[7])); 
    
    return array( 
     "left"   => abs($minX) - 1, 
     "top"    => abs($minY) - 1, 
     "width"  => $maxX - $minX, 
     "height" => $maxY - $minY, 
     "box"    => $rect 
    ); 
}

function arrayTableFilter($table_tuples = array(), $filtering_values = array(), $filter_value = ""){
	if (!is_array($table_tuples) || !is_array($filtering_values) || count($table_tuples) != count($filtering_values) || count($filtering_values) == 0){
		return array();
	}
	
	if (trim($filter_value) == ""){
		return $table_tuples;
	}
	$filter_value = trim($filter_value);
	
	foreach($table_tuples as $tuple_key => $tuple){
		if (strrpos($filtering_values[$tuple_key], $filter_value) === false){
			unset($table_tuples[$tuple_key]);
		}
	}
	return $table_tuples;
}

function arrayTableSort($table_tuples = array(), $sorting_values = array(), $sort_index = 0, $sort_ascending = true){
	
	if (!is_array($table_tuples) || !is_array($sorting_values) || count($table_tuples) != count($sorting_values) || count($table_tuples) == 0){
		return array();
	}
	
	$keys = array_keys($table_tuples);
	
	if(!isset($table_tuples[$keys[0]][$sort_index])){
		return array();
	}
	
	$count = count($table_tuples);
	
	$data_array = array();
	
	if ($sort_ascending == true){
		$sort_direction = 1;
	}
	else {
		$sort_direction = -1;
	}

	foreach($keys as $key){
		$data_array[] = array(
			"row_values"	=>	$table_tuples[$key],
			"sort_values"	=>	$sorting_values[$key],
			"sort_index"	=>	$sort_index,
			"sort_direction"=>	$sort_direction,
		);
	}
	
	usort($data_array, function($a, $b){
		$a_val = mb_strtolower($a["sort_values"][$a["sort_index"]],"UTF-8");
		$b_val = mb_strtolower($b["sort_values"][$b["sort_index"]],"UTF-8");
		
		if (is_numeric($a_val) && is_numeric($b_val)){
			$result = ($a_val - $b_val);
		}
		else {
			$result = strcmp($a_val, $b_val);
		}
		return $a["sort_direction"] * $result;
	});
	
	$array = array();
	#sprint_r($data_array);
	#die("");
	foreach($data_array as $data_array_item){
		$array[] = $data_array_item['row_values'];
	}
	
	return $array;
}

function errorEmailTooBig() {
	$error = error_get_last();
	if($error !== NULL){
		if (strstr($error['message'], "Allowed memory size of") !== false){
			$info = "Email too big";
			addAjaxResponse(-1, $info);
			#addAjaxResponse(0, htmlspecialchars($error['message'], ENT_QUOTES));
			endAjax();
		}
	}
	else{
		addAjaxResponse(-1, "Error");
		endAjax();
	}
}

function getCombinations($base,$n){

	$baselen = count($base);
	if($baselen == 0){
		return;
	}
	if($n == 1){
		$return = array();
		foreach($base as $b){
			$return[] = array($b);
		}
		return $return;
	}
	else{
		//get one level lower combinations
		$oneLevelLower = getCombinations($base,$n-1);
		//for every one level lower combinations add one element to them that the last element of a combination is preceeded by the element which follows it in base array if there is none, does not add
		$newCombs = array();
		foreach($oneLevelLower as $oll){
			$lastEl = $oll[$n-2];
			$found = false;
			foreach($base as  $key => $b){
				if($b == $lastEl){
					$found = true;
					continue;
					//last element found
				}
				if($found == true){
					//add to combinations with last element
					if($key < $baselen){
						$tmp = $oll;
						$newCombination = array_slice($tmp,0);
						$newCombination[]=$b;
						$newCombs[] = array_slice($newCombination,0);
					}
				}
			}
		}
	}

	return $newCombs;
}

/**
	 * Sorts an array of objects
	 * @param type $object_array
	 * @param type $condition_array
	 * @return type 
	 */
function sort_object_array_by_functions($object_array, $condition_array){
	if (count($condition_array) == 0){
		return $array;
	}
	usort($object_array, function($o1, $o2) use ($condition_array){
		/*
		$condition_array = array(
			array(
				"function"	=>	"functionA",
				"direction"	=>	1,
			),
			array(
				"function"	=>	"functionB",
				"direction"	=>	-1
			)
		);
		*/
		
		
		foreach($condition_array as $condition){
			if (!method_exists($o1, $condition['function']) || !method_exists($o2, $condition['function'])){
				continue;
			}
			$a_val = $o1->$condition['function']();
			$b_val = $o2->$condition['function']();

			if (is_numeric($a_val) && is_numeric($b_val)){
				$result = ($a_val - $b_val);
			}
			else {
				$result = strcmp($a_val, $b_val);
			}
			if ($result != 0){
				return $result * $condition['direction'];
			}
		}
		
		return 0;
	});
	return $object_array;
}

function sort_2d_array_by_keys($array, $condition_array){
	if (count($condition_array) == 0){
		return $array;
	}
	usort($array, function($av1, $av2) use ($condition_array){
		foreach($condition_array as $condition){
			if (!isset($av1[$condition['key']]) || !isset($av2[$condition['key']])){
				continue;
			}
			$a_val = $av1[$condition['key']];
			$b_val = $av2[$condition['key']];

			if (is_numeric($a_val) && is_numeric($b_val)){
				$result = ($a_val - $b_val);
			}
			else {
				$result = strcmp($a_val, $b_val);
			}
			if ($result != 0){
				return $result * $condition['direction'];
			}
		}
		
		return 0;
	});
	return $array;
}

function unicode_alphanumeric_only($string = ""){
	return preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);
}

function unicode_html_characters($string = ""){
	return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
}

function unicode_html_entities($string = ""){
	return htmlentities($string, ENT_QUOTES, "UTF-8");
}

function unicode_words_to_max_len($string = "", $max_len = 0){
	$new_string = "";
	foreach(explode(' ', $string) as $token){
		$temp = $new_string . " " . $token;
		if (mb_strlen($temp, "UTF-8") <= $max_len){
			$new_string = $temp;
		}
		else {
			break;
		}
	}
	return trim($new_string);
}

function factorial($n) {
    if ($n <= 1) {
        return 1;
    }
	return factorial($n - 1) * $n;
}
 
function combinations($n, $k) {
    //note this defualts to 0 if $n < $k
    if ($n < $k) {
        return 0;
    }
	
	$numerator = 1;
	
	for ($i = $n; $i > $n-$k; $i--){
		$numerator *= $i;
	}
	
	return $numerator/factorial($k);
}

function array_value_by_index($array, $value_index){
	if (!isset($array[$value_index])){
		#throw new Exception("Undefined key [$value_index] in [".  str_replace("\n","",print_r($array,true))."]");
		#necho("Undefined key [$value_index] in [".  print_r($array,true)."] at: ".print_r(debug_backtrace(),true));
	}
	return $array[$value_index];
}

function rglob($pattern, $flags = 0) {
	$files = glob($pattern, $flags);
	
	if (!is_array($files)){
		$files = array();
	}
	
	$_files = glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT);
	
	if (!is_array($_files)){
		$_files = array();
	}
	foreach ($_files as $dir) {
		$files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
	}
	return $files;
}

function generate_select_options($rows, $val_name, $text_name, $selected_val){
	if (!is_array($rows)){
		throw new Exception("Not an array: ".print_r($rows,true));
	}
	if (empty($rows)) {
		return "";
	}
	
	$val_name = trim($val_name);
	$text_name = trim($text_name);
	if (empty($val_name) || empty($text_name)){
		return "";
	}
	
	if (!isset($rows[0][$val_name]) || !isset($rows[0][$text_name])){
		throw new Exception("No such columns: `$val_name`, `$text_name`");
	}
	
	$html = "";
	foreach($rows as $row){
		$value = $row[$val_name];
		$text = $row[$text_name];
		$selected = (($selected_val == $row[$val_name])?'selected="selected"':"");
		$html .= "<option value=\"$value\" $selected>$text</option>";
	}
	return $html;
}

function precho($arg,$return = false){
	$string = "<pre>";
	$string .= print_r($arg, true);
	$string .= "</pre>";
	if ($return){
		return $string;
	}
	echo $string;
}

function get_expected_post_args($args = array()){
	$array = array();
	foreach($args as $arg){
		$array[$arg] = @trim(GlobalEnv::$_POST[$arg]);
	}
	return $array;
}

function sort_db_view($sort_key, $tuples = array(), $sort_ascending = true){
	
	if (count($tuples) == 0){
		return array();
	}

	if (!isset($tuples[0][$sort_key])){
		throw new Exception("Invalid key: $sort_key");
	}

	if ($sort_ascending == true){
		$sort_direction = 1;
	}
	else {
		$sort_direction = -1;
	}
	
	$args = array("key" => $sort_key, "dir" => $sort_direction);

	usort($tuples, function($a, $b) use ($args){
		$a_val = mb_strtolower($a[$args["key"]],"UTF-8");
		$b_val = mb_strtolower($b[$args["key"]],"UTF-8");
		
		if (is_numeric($a_val) && is_numeric($b_val)){
			$result = ($a_val - $b_val);
		}
		else {
			$result = strcmp($a_val, $b_val);
		}
		return $args["dir"] * $result;
	});

	return $tuples;
}

function mround($val, $f=2, $d=0){
    return sprintf("%".$d.".".$f."f", $val);
}

function curl_simple($url){
	$curl_handle=curl_init();
	curl_setopt($curl_handle,CURLOPT_URL,$url);
	curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,300);
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
	$data = curl_exec($curl_handle);
	curl_close($curl_handle);
	return $data;
}

function encodeURI($url) {
    // http://php.net/manual/en/function.rawurlencode.php
    // https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/encodeURI
    $unescaped = array(
        '%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', '%7E'=>'~',
        '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
    );
    $reserved = array(
        '%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
        '%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
    );
    $score = array(
        '%23'=>'#'
    );
    return strtr(rawurlencode($url), array_merge($reserved,$unescaped,$score));

}
?>