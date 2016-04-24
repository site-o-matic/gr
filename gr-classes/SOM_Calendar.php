<?php

class SOMCalendar{

	private $config = array(
		'month'		=>	1,
		'year'		=>	2000,
		'classes'	=>	array(
			'table'	=>	'',
			'tr'	=>	'',
			'th'	=>	'',
			'td'	=>	'',			# Day of requested month
			'today'	=>	'today',
			'ntd'	=>	'ntd'		# Non requested month
		),
		'days'		=>	array('Mo','Tu','We','Th','Fr','Sa','Su')
	);
	
	private $table = "";
	
	private function __construct($config, $callback = null){
		$this->config = array_merge($this->config, $config);
		
		# Start table
		$calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';

		# Table headings
		$headings = $this->config['days'];
		$calendar .= '<tr class="' . $this->config['classes']['tr'] . '"><th class="' . $this->config['classes']['th'] . '"><span>';
		$calendar .= implode('</span></th><th class="'.$this->config['classes']['th'] . '"><span>',$headings) . '</span></th></tr>';

		# Init
		$running_day = date('N',mktime(0,0,0,$this->config['month'],1,$this->config['year'])) - 1;
		$days_in_month = date('t',mktime(0,0,0,$this->config['month'],1,$this->config['year']));
		$days_in_this_week = 1;
		$day_counter = 0;
		$week_counter = 0;

		# Row for week one
		$calendar .= '<tr class="' . $this->config['classes']['tr'] . '">';

		# Print "blank" days until the first of the current week up to first day of the month
		for($x = 0; $x < $running_day; $x++){
			$calendar .= '<td class="' . $this->config['classes']['ntd'] . '"><div></div></td>';
			$days_in_this_week++;
		}

		# Draw current month
		for($list_day = 1; $list_day <= $days_in_month; $list_day++){
			$_classes = $this->config['classes']['td'];
			
			# Add today classes
			if ($list_day == date("j") && $this->config['month'] == date('m') && $this->config['year'] == date('Y')){
				$_classes .= ' ' . $this->config['classes']['today'];
			}
			$calendar .= '<td class="' . $_classes . '">';
			
			# Print week day
			# Use custom callback function if presented
			if ($callback !== null){
				$calendar .= call_user_func($callback, $list_day);
			}
			else {
				$calendar .= '<div class="day-number">' . $list_day . '</div>';
			}

			$calendar .= '</td>';
			if($running_day == 6){
				$calendar .= '</tr>';
				$week_counter++;
				if(($day_counter + 1) != $days_in_month){
					$calendar .= '<tr class="' . $this->config['classes']['tr'] . '">';
				}
				$running_day = -1;
				$days_in_this_week = 0;
			}
			$days_in_this_week++; $running_day++; $day_counter++;
		}

		# Finish the rest of the days in the week with days from next month
		if ($days_in_this_week > 1){
			# Month ended on Sunday
			if($days_in_this_week < 8){
				for($x = 1; $x <= (8 - $days_in_this_week); $x++){
					$calendar .= '<td class="' . $this->config['classes']['ntd'] . '"> </td>';
				}
			}
			# Final row
			$week_counter++;
			$calendar .= '</tr>';
		}
		
		
		# Add rows to maintain 6 row count
		for($week_counter; $week_counter < 6; $week_counter++){
			$calendar .= '<tr class="' . $this->config['classes']['tr'] . '">';
			$calendar .= str_repeat('<td class="' . $this->config['classes']['ntd'] . '"><div></div></td>', 7);
			$calendar .= '</tr>';
		}

		# End the table
		$calendar.= '</table>';

		$calendar = preg_replace("/(<[\/]?tr|<[\/]?td|<th|<[\/]?a|<[\/]?table)/", "\n$1", $calendar);
		
		# All done, return result
		$this->table = $calendar;
	}
	
	public static function get($config, $callback = null){
		return new SOMCalendar($config, $callback);
	}
	
	public function get_table(){
		return $this->table;
	}
}
?>