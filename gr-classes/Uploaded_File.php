<?php
class UploadedFile{
	private $name = null;
	private $temp_name = null;
	private $extension = null;
	private $type = null;
	private $type_full = null;
	private $size = null;

	function UploadedFile($file = null){

		# $file is an entry from $_FILES

		if ($file != null){
			$this->name = $file['name'];
			$this->temp_name = $file['tmp_name'];
			$this->size = $file['size'];

			$type = explode('/',$file['type']);
			$this->type = $type[0];
			$this->type_full = $file['type'];

			$this->extension = strtolower(substr($this->name,strrpos($this->name, ".")+1));
		}
		else {
			return null;
		}
	}

	function name(){
		return $this->name;
	}

	function type(){
		return $this->type;
	}

	function type_full(){
		return $this->type_full;
	}

	function size(){
		return $this->size;
	}

	function tmp_name(){
		return $this->temp_name;
	}

	function extension(){
		return $this->extension;
	}

	function randomName(){
		return md5(rand()) . "." .$this->extension;
	}
	
	/**
	 * Returns the name part without extension and without dot preceding extension
	 * @return String 
	 */
	function nameNoExtension(){
		return substr($this->name, 0, strrpos($this->name,"."));
	}

	/**
	 *
	 * copies file from tmp_file to given path<br/>
	 * returns new file name if success<br/>
	 * returns false if failure<br/>
	 * will try to find a random name which is not taken in this path<br/>
	 * if $keep_name is true, will try to save under the current $name or return false<br/>
	 *
	 * @param string $path path to copy to ending with slash
	 * @param boolean $keep_name
	 * @param string defined name if you want a particular name for this file
	 * @param boolean indicate if you want to overwrite existing file
	 * @return mixed - false if unsuccessful, new file name if success
	 */
	function copyFromTemp($path = "",$keep_name = false, $define_name = "", $overwrite = false){
		$nname = $this->randomName();
		if ($keep_name){
			$nname = $this->name;
		}
		else if($define_name != ""){
			$nname = $define_name;
		}
		else {
			while (is_file($path . $nname)){
				$nname = $this->randomName();
			}
		}
		if (!$overwrite && is_file($path . $nname)){
			return false;
		}

		if (move_uploaded_file($this->temp_name,$path . $nname)){
			return $nname;
		}

		return false;
	}

	/**
	 * Processes $_FILES array at the current and returns an array of UploadedFile objects<br/>
	 * Should be used after a form with multiple file input was submitted
	 * @global array $_FILES
	 * @param String $field - name of the input element
	 * @return array - array of UploadedFile objects
	 */
	public static function GET_FILES($field){
		global $_FILES;
		
		$array = array();

		$n = count($_FILES[$field]['name']);

		for($i = 0; $i < $n; $i++){
			$array[] = new UploadedFile(array(
				"name" => $_FILES[$field]['name'][$i],
				"type" => $_FILES[$field]['type'][$i],
				"tmp_name" => $_FILES[$field]['tmp_name'][$i],
				"error" => $_FILES[$field]['error'][$i],
				"size" => $_FILES[$field]['size'][$i]
			));
		}
		return $array;
	}
}
/*
Array (
	[images] => Array (
		[name] => Array ( [0] => ___4039____by_sideshowsito.jpg [1] => _15__by_bittersweetvenom-d35mg06.jpg )
		[type] => Array ( [0] => image/jpeg [1] => image/jpeg )
		[tmp_name] => Array ( [0] => F:\xampp\tmp\php62.tmp [1] => F:\xampp\tmp\php63.tmp )
		[error] => Array ( [0] => 0 [1] => 0 )
		[size] => Array ( [0] => 357678 [1] => 61196 )
	)
)
*/

?>