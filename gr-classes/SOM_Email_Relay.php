<?php
class SOMEmailRelay extends Singleton{
	
	protected static $instance = null;
	
	const TABLE = 'email_relay';			# Table where relay data is stored
	
	const RESET_SCENARIO_TIMER = 10;		# Scenario: reset N seconds after last change
	const RESET_SCENARIO_HOUR = 20;			# Scenario: reset each new hour

	const RESET_TIMER = 3600;				# Reset relay counter after this amount of seconds passed since last transmission
	
	const RESET_SCENARIO = self::RESET_SCENARIO_HOUR;
	
	protected $relays = array(
		'template@example.com'	=>	array(
			'email'			=>	'template@example.com',
			'count'			=>	0,
			'max_count'		=>	0,
			'date_changed'	=>	-1,
			'host'			=>	'mail.example.com',
			'port'			=>	'25',
			'password'		=>	'1password23',
			'auth'			=>	11			# DatabaseObject::C_TRUE | DatabaseObject::C_FALSE
		)
	);
	
	public function __construct() {
		
		$SELF = get_called_class();
		
		parent::__construct();
		
		$this->relays = Database::instance()->query_map('SELECT * FROM `'.$SELF::TABLE.'` ORDER BY `count` ASC', array('email' => array()));
		$reset_ts = time() - $SELF::RESET_TIMER;
		$current_hour = date('H');
		foreach($this->relays as $relay => $data){
			if ($data['count'] > 0 && $SELF::RESET_SCENARIO == self::RESET_SCENARIO_TIMER && $data['date_changed'] < $reset_ts){
				$this->relays[$relay]['count'] = 0;
			}
			
			if ($data['count'] > 0 && $SELF::RESET_SCENARIO == self::RESET_SCENARIO_HOUR && date('H', $data['date_changed']) != $current_hour){
				$this->relays[$relay]['count'] = 0;
			}
		}
	}
	
	protected function get_available_relay(){
		foreach($this->relays as $relay_data){
			if ($relay_data['count'] < $relay_data['count_max']){
				return $relay_data['email'];
			}
		}
		throw new Exception('No available relays', -2);
	}
	
	public function send($email){
		
		if (!is_subclass_of($email, 'SOMEmail')){
			throw new Exception('Invalid email object type', -1);
		}
		
		$relay = $this->get_available_relay();
		
		$smtp = array(
			'host'		=> $this->relays[$relay]['host'],
			'username'	=> $this->relays[$relay]['email'],
			'password'	=> $this->relays[$relay]['password'],
			'port'		=> $this->relays[$relay]['port']
		);
		
		if ($relay['auth'] == DatabaseObject::C_TRUE){
			$smtp['auth'] = true;
		}
		
		$email::useSMTP($smtp);
		
		try {
			$email->send(true);
		}
		catch(Exception $e){
			throw new Exception($e->getMessage(), -2);
		}

		$this->relays[$relay]['count']++;
		$this->relays[$relay]['date_changed'] = time();
		
		$email::useSMTP(null);
		return true;
	}
	
	public function save(){
		$SELF = get_called_class();
		
		# Wipe old data
		Database::instance()->query('DELETE FROM `'.$SELF::TABLE.'`');
		
		if (Database::instance()->error()){
			throw new Exception(Database::instance()->error_text(),-2);
		}
		
		# and replace
		Database::instance()->mass_insert($SELF::TABLE, array_values($this->relays));
		
		if (Database::instance()->error()){
			throw new Exception(Database::instance()->error_text(),-2);
		}
		
		return true;
	}
	
	/**
	 * 
	 * @return SOMEmailRelay
	 */
	public static function instance() {
		return parent::getInstance();
	}
	
	public function status(){
		$array = array_map(function($relay){
			return array();
		}, $this->relays);
	}

}
