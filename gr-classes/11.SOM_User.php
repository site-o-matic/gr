<?php

abstract class SOMUser extends DatabaseObject {
	
	protected static $CurrentUser = null;

	const RememberUserFor = 604800; # keep logged in for one week after a log in or a visit while being logged in
	const RememberUserForMax = 2592000; # but still ask to enter email and password every this period (i.e. remembering option
	
	const PASSWORD_MIN = 8;
	const PASSWORD_MAX = 16;
	
	protected $table = "user";

	public function __construct($id = -1){

		parent::__construct($id);
		if (sizeof($this->attributes) == 0){
			$this->attributes['id'] = -1;
			$this->attributes['password'] = "";
			$this->attributes['date'] = time();
		}
	}

	public function password($arg = null){
		if ($arg != null){
			$arg = $this->getEncPass($arg);
		}
		return $this->access(__FUNCTION__,$arg);
	}

	public function date(){
		return $this->access(__FUNCTION__,null);
	}

	public function resetPassword(){
		$new_password = substr(md5(time().$this->password()),5,10);
		$this->password($new_password);
		if ($this->save()){
			return $new_password;
		}
		return false;
	}

	public function getEncPass($password = ""){
		# Encrypt the password using registration timestamp as salt
		return hash('sha256',$this->date().$password);
	}


	public function login($password, $force = false){
		# Uncomment the line below to force one instance of user in the system (can't use multiple browsers or machines)
		
		$this->log_out_before_log_in();
		
		if ($this->id() <= 0 || !($force || $this->checkPassword($password))){
			return false;
		}
		$this->db->query("INSERT INTO `session` (`uid`,`session_key`, `date`) VALUES ('".$this->id()."','".makeSessionKey()."', '".time()."')");
		
		# Clear up outdated sessions
		self::CLEAR_OLD_SESSIONS();
		
		return true;
	}
	
	public function checkPassword($password){
		return ($this->getEncPass($password) == $this->password());
	}

	public function logout(){
		
		$this->db->query("DELETE FROM `session` WHERE `session_key` = '".makeSessionKey()."'");
		$this->forget();
		# this logs out user from all browsers
		#$this->db->query("DELETE FROM `session` WHERE `uid` = '".$this->id()."'");
	}

	public function remember($extension = false){
		if ($this->id() <= 0){
			return false;
		}
		$expire = time() + self::RememberUserFor;
		setcookie("user",$this->email(),$expire,"/", null, null, true);
		setcookie("password", $this->getEncPass($this->password()), $expire, "/", null, null, true);
		if (!$extension){
			setcookie("original","original",time() + self::RememberUserForMax,"/", null, null, true);
		}
		return true;
	}

	public function forget(){
		setcookie("user", "", 2,"/", null, null, true);		# email
		setcookie("password", "", 2,"/", null, null, true);	# encrypted password
		setcookie("original", "", 2,"/", null, null, true);	# time of first log in for this remember series
	}

	public static function CLEAR_OLD_SESSIONS(){
		$now = time();
		$t_ago = $now - 60*60*24*3;
		Database::instance()->query("DELETE FROM `session` WHERE `date` < '$t_ago'");
	}
	
	/**
	* @return User
	*/
	public static function getCurrentUser($reset = false){
		if ($reset){
			self::$CurrentUser = null;
		}
		
		if (self::$CurrentUser == null){

			# check for remembered user
			if (isset($_COOKIE["original"]) && isset($_COOKIE["user"]) && isset($_COOKIE["password"])){
				$user = User::GET_BY_FIELD("email", urldecode($_COOKIE["user"]), true);
				if ($user->getEncPass($user->password()) == $_COOKIE["password"]){
					self::$CurrentUser = $user;
					$user->remember(true);
				}
			}
			else {
				# check for original session
				$session_key = makeSessionKey();

				$data = Database::instance()->query("SELECT * FROM `session` WHERE `session_key` = '$session_key'");

				self::$CurrentUser = @(User::get($data[0]['uid']));
			}
		}
		
		return self::$CurrentUser;
	}

	/**
	 * Override this function for a custom policy regarding the number of allowed log in sessions etc
	 */
	protected function log_out_before_log_in(){
		# Unconditionally log out before log in
		$this->logout();
		
		# Or can check for the number of existing log in sessions etc
	}
}

?>