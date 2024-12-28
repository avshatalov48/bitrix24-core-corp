<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 * 
 * @access private
 * 
 * This class is almost exact port of javascript GanttCalendar class
 */

namespace Bitrix\Tasks\Util;

use Bitrix\Tasks\Util\Type\DateTime;

final class Calendar
{
	protected $holidays = array();
	protected $weekEnds = array();
	protected $weekStart = false;
	protected $workTime = array();

	protected static $instance = array();

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		$class = get_called_class();
		if(!array_key_exists($class, static::$instance))
		{
			static::$instance[$class] = new static();
		}

		return static::$instance[$class];
	}

	public function __construct(array $settings = array())
	{
		if(!empty($settings))
		{
			$this->setSettings($settings);
		}
		else
		{
			$this->setSettings(static::getSettingsCached());
		}
	}

	// calendar functions below

	public function getClosestWorkTime(DateTime $oldDate, $isForward = true)
	{
		$date = clone $oldDate;

		$startDate = $isForward ? $date : null;
		$endDate = $isForward ? null : $date;

		$cb = function (DateTime $start, DateTime $end) use(&$date, $isForward)
		{
			$date = $isForward ? $start : $end;
			return false;
		};

		$this->processEachDay($startDate, $endDate, $isForward, $cb);

		return $date;
	}

	public function calculateDuration(DateTime $startDate, DateTime $endDate)
	{
		$duration = 0;
		if ($startDate->getTimestamp() < $endDate->getTimestamp())
		{
			$cb = function (DateTime $start, DateTime $end) use(&$duration)
			{
				$duration += ($end->getTimestamp() - $start->getTimestamp());
			};

			$this->processEachDay($startDate, $endDate, true, $cb);
		}
		else
		{
			$cb = function (DateTime $start, DateTime $end) use(&$duration)
			{
				$duration -= ($end->getTimestamp() - $start->getTimestamp());
			};

			$this->processEachDay($endDate, $startDate, true, $cb);
		}

		return $duration;
	}

	public function calculateStartDate($endDate, $duration)
	{
		$newDate = null;

		$cb = function (DateTime $start, DateTime $end) use(&$newDate, &$duration)
		{
			$interval = $end->getTimestamp() - $start->getTimestamp();
			if ($interval >= $duration)
			{
				$newDate = DateTime::createFromTimestampGmt($end->getTimestamp() - $duration);
				return false;
			}
			else
			{
				$duration -= $interval;
			}
		};

		$this->processEachDay(null, $endDate, false, $cb);

		return $newDate;
	}

	/**
	 * @param DateTime $startDate
	 * @param $duration
	 * @return DateTime
	 */
	public function calculateEndDate($startDate, $duration)
	{
		$newDate = null;

		$cb = function (DateTime $start, DateTime $end) use(&$newDate, &$duration)
		{
			$interval = $end->getTimestamp() - $start->getTimestamp();
			if ($interval >= $duration)
			{
				$newDate = DateTime::createFromTimestampGmt($start->getTimestamp() + $duration);
				return false;
			}
			else
			{
				$duration -= $interval;
			}
		};

		$this->processEachDay($startDate, null, true, $cb);

		return $newDate;
	}

	protected function processEachDay(DateTime $startDate = null, DateTime $endDate = null, $isForward = false, $callback = false)
	{
		if(!is_callable($callback))
		{
			return 0;
		}

		// you can not use DateTime::createFromTimestamp() here, because in this case, you`ll loose information about the timezone (was UTC, will be local zone)
		$currentDate = 	DateTime::createFromTimestampGmt($isForward ? $startDate->getTimestamp() : $endDate->getTimestamp());
		$endless = 		$isForward ? !$endDate : !$startDate;

		/*
		$intervals = $this->getWorkHours($currentDate);
		print_r('iv:'.PHP_EOL);
		print_r($intervals);
		*/
		/*
		$n = DateTime::createFromTimeStructGmt(array(
			'hours' => 10,
			'minutes' => 30,
			'seconds' => 0,
			'mon' => 9,
			'day' => 20,
			'year' => 2015
		), true);
		print_r('currentDate: '.$n->getInfoGmt().PHP_EOL);
		*/

		while ($endless || ($isForward ? ($currentDate->getTimestamp() < $endDate->getTimestamp()) : ($currentDate->getTimestamp() > $startDate->getTimestamp())))
		{
			//print_r('currentDate: '.$currentDate->getInfoGmt().PHP_EOL);

			$intervals = $this->getWorkHours($currentDate);
			// walk on $intervals from start to end or visa-versa
			for ($i = ($isForward ? 0 : count($intervals) - 1); ($isForward ? $i < count($intervals) : $i >= 0); ($isForward ? $i++ : $i--))
			{
				$interval = 		$intervals[$i];
				$intervalStart = 	$interval['startDate'];
				$intervalEnd = 		$interval['endDate'];

				//print_r('Interval:'.PHP_EOL);
				//print_r($intervalStart->getInfoGmt().' - '.$intervalEnd->getInfoGmt().PHP_EOL);

				if (($endDate !== null && $intervalStart->checkGT($endDate)) || ($startDate !== null && $intervalEnd->checkLT($startDate)))
				{
					continue;
				}

				$availableStart = 	($startDate !== null && $intervalStart->checkLT($startDate)) ? $startDate : $intervalStart;
				$availableEnd = 	($endDate !== null && $intervalEnd->checkGT($endDate)) ? $endDate : $intervalEnd;

				//print_r('once: '.$availableStart->getInfoGmt().' '.$availableEnd->getInfoGmt().PHP_EOL);
				if(call_user_func_array($callback, array($availableStart, $availableEnd)) === false)
				{
					return false;
				}
			}

			$currentDate->stripTime();
			$currentDate->addDay($isForward ? 1 : -1);
		}
	}

	public function getWorkHours(DateTime $date)
	{
		$hours = array();
		if ($this->isWeekend($date) || $this->isHoliday($date))
		{
			return $hours; // no work hours for $date
		}

		$year = 	$date->getYearGmt();
		$month = 	$date->getMonthGmt(true);
		$day = 		$date->getDayGmt();

		for ($i = 0; $i < count($this->workTime); $i++)
		{
			$time = $this->workTime[$i];

			array_push($hours, array(
				'startDate' => DateTime::createFromTimeStructGmt(array(
					'hours' => 		$time['start']['hours'],
					'minutes' => 	$time['start']['minutes'],
					'seconds' => 	0,
					'mon' => 		$month,
					'day' => 		$day,
					'year' => 		$year
				), true),
				'endDate' => DateTime::createFromTimeStructGmt(array(
					'hours' => 		$time['end']['hours'],
					'minutes' => 	$time['end']['minutes'],
					'seconds' => 	0,
					'mon' => 		$month,
					'day' => 		$day,
					'year' => 		$year
				), true)
			));
		}

		return $hours;
	}

	public function isWorkTime(DateTime $date)
	{
		if ($this->isWeekend($date) || $this->isHoliday($date))
		{
			return false;
		}

		$isWorkTime = null;
		$cb = function (DateTime $start, DateTime $end) use(&$isWorkTime, $date)
		{
			$isWorkTime = $date->checkGT($start, false) && $date->checkLT($end, false);
			return false;
		};

		$this->processEachDay($date, null, true, $cb);

		return $isWorkTime;
	}

	public function isHoliday(DateTime $date)
	{
		$month = 	$date->getMonthGmt(true);
		$day = 		$date->getDayGmt();

		return array_key_exists($month.'_'.$day, $this->holidays) && $this->holidays[$month.'_'.$day];
	}

	public function isWeekend(DateTime $date)
	{
		$day = $date->getWeekDayGmt();
		return array_key_exists($day, $this->weekEnds) && $this->weekEnds[$day];
	}

	/**
	 * @return DateTime
	 */
	public function getStartOfCurrentDayGmt()
	{
		$dateTime = DateTime::createFromUserTimeGmt((string) new DateTime());
		$dateTime->stripTime(); // this will only work on GMT zone

		return $dateTime;
	}

	/**
	 * @return DateTime
	 */
	public function getEndOfCurrentDayGmt()
	{
		$dateTime = $this->getStartOfCurrentDayGmt();
		$dateTime->addDay(1);

		return $dateTime;
	}

	// util

	public function setSettings(array $settings)
	{
		if(is_array($settings['HOURS']) && !empty($settings['HOURS']))
		{
			$h = $settings['HOURS'];

			$this->workTime = array(
				// currently one interval, no time for lunch
				array(
					'start' => 	array(
						'hours' => (int) $h['START']['H'],
						'minutes' => (int) $h['START']['M'],
						'time' => ((int) $h['START']['H']) * 60 +  ((int) $h['START']['M'])
					),
					'end' => 	array(
						'hours' => (int) $h['END']['H'],
						'minutes' => (int) $h['END']['M'],
						'time' => ((int) $h['END']['H']) * 60 +  ((int) $h['START']['M'])
					)
				)
			);
		}

		// holidays
		if(is_array($settings['HOLIDAYS']))
		{
			foreach($settings['HOLIDAYS'] as $day)
			{
				$this->holidays[(intval($day['M']) - 1).'_'.intval($day['D'])] = true;
			}
		}

		// week settings

		$dayMap = array(
			'MO' => 1,
			'TU' => 2,
			'WE' => 3,
			'TH' => 4,
			'FR' => 5,
			'SA' => 6,
			'SU' => 0,
		);

		$this->weekEnds = array();
		if(is_array($settings['WEEKEND']))
		{
			foreach($settings['WEEKEND'] as $day)
			{
				$this->weekEnds[$dayMap[$day]] = true;
			}
		}
		if(count($this->weekEnds) == 7) // wtf? the entire week is a one big weekend? fall back to "safe defaults"
		{
			$this->weekEnds = array($dayMap['SA'] => true, $dayMap['SU'] => true);
		}

		$this->weekStart = $dayMap[$settings['WEEK_START']];

		/*
		print_r('WeekEnds');
		print_r($this->weekEnds);
		print_r('weekStart');
		print_r($this->weekStart);
		print_r('holidays');
		print_r($this->holidays);
		print_r('worktime');
		print_r($this->workTime);
		*/
	}

	protected static function getSettingsCached()
	{
		static $settings;

		if($settings == null)
		{
			$settings = static::getSettings();
		}

		return $settings;
	}

    public static function getDefaultSettings(): array
	{
		return [
			'HOURS' => [
				'START' => [
					'H' => 9,
					'M' => 0,
					'S' => 0,
				],
				'END' => [
					'H' => 19,
					'M' => 0,
					'S' => 0,
				],
			],
			'HOLIDAYS' => [],
			'WEEKEND' => ['SA', 'SU'],
			'WEEK_START' => 'MO',
			'SERVER_OFFSET' => (new \DateTime())->getOffset(),
		];
	}

	public static function getSettings($siteId = false)
	{
		$result = static::getDefaultSettings();

		if($siteId === false)
		{
			$siteId = SITE_ID;
		}

		$site = \CSite::GetByID($siteId)->fetch();
		$weekDay = $site['WEEK_START'] ?? null;
		$weekDaysMap = array(
			'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'
		);
		if((string) $weekDay != '' && isset($weekDaysMap[$weekDay]))
		{
			$result['WEEK_START'] = $weekDaysMap[$weekDay];
		}

		$calendarSettings = \Bitrix\Tasks\Integration\Calendar::getSettings();
		if(!empty($calendarSettings))
		{
			if(is_array($calendarSettings['week_holidays']))
			{
				$result['WEEKEND'] = $calendarSettings['week_holidays'];
			}
			/*
			if((string) $calendarSettings['week_start'] != '')
			{
				$result['WEEK_START'] = $calendarSettings['week_start'];
			}
			*/
			if((string) $calendarSettings['year_holidays'] != '')
			{
				$holidays = explode(',', $calendarSettings['year_holidays']);
				if(is_array($holidays) && !empty($holidays))
				{
					foreach($holidays as $day)
					{
						$day = trim($day);
						list($day, $month) = explode('.', $day);
						$day = intval($day);
						$month = intval($month);

						if($day && $month)
						{
							$result['HOLIDAYS'][] = array('M' => $month, 'D' => $day);
						}
					}
				}
			}

			$timeStart = explode('.', (string)$calendarSettings['work_time_start']);
			if(isset($timeStart[0]))
			{
				$result['HOURS']['START']['H'] = (int)$timeStart[0];
			}
			if(isset($timeStart[1]))
			{
				$result['HOURS']['START']['M'] = (int)$timeStart[1];
			}

			$timeEnd = explode('.', (string)$calendarSettings['work_time_end']);
			if(isset($timeEnd[0]))
			{
				$result['HOURS']['END']['H'] = (int)$timeEnd[0];
			}
			if(isset($timeEnd[1]))
			{
				$result['HOURS']['END']['M'] = (int)$timeEnd[1];
			}
		}

		return $result;
	}

	public function getEndHour(): int
	{
		$settings = self::getSettings();
		return (int)($settings['HOURS']['END']['H'] ?? 0);
	}

	public function getEndMinute(): int
	{
		$settings = self::getSettings();
		return (int)($settings['HOURS']['END']['M'] ?? 0);
	}
}