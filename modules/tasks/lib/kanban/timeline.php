<?php
namespace Bitrix\Tasks\Kanban;

use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Context;
use \Bitrix\Tasks\Util\Calendar;

class DateTimeTimeLine extends DateTime
{
	/**
	 * Border of day (0 - first, 1 - second).
	 * @var int
	 */
	protected $border = null;

	/**
	 * Converts date to string, using Culture and global timezone settings.
	 * @param Context\Culture $culture Culture contains datetime format.
	 * @return string
	 */
	public function toString(Context\Culture $culture = null)
	{
		if (\CTimeZone::Enabled())
		{
			$userTime = clone $this;
			$userTime->toUserTime();

			$format = static::getFormat($culture);

			if ($this->border === 0)
			{
				$format = str_replace(['H', 'i', 's'], '00', $format);
			}
			else if ($this->border === 1)
			{
				$format = str_replace(['H', 'i', 's'], [23, 59, 59], $format);
			}

			return $userTime->format($format);
		}
		else
		{
			return parent::toString($culture);
		}
	}

	/**
	 * Set time value.
	 * @param int $hour Hour value.
	 * @param int $minute Minute value.
	 * @param int $second Second value.
	 * @param int $microseconds Microseconds value.
	 * @return DateTimeTimeLine
	 */
	public function setTime($hour, $minute, $second = 0, $microseconds = 0)
	{
		if ($hour == 23 && $minute == 59 && $second == 59)
		{
			$this->border = 1;
		}
		else if ($hour == 0 && $minute == 0 && $second == 0)
		{
			$this->border = 0;
		}
		else
		{
			$this->border = null;
		}
		$this->value->setTime($hour, $minute, $second, $microseconds);
		return $this;
	}
}

class TimeLineTable// *Table for unity structure
{
	/**
	 * Gets stages for timeline.
	 * @return array
	 */
	public static function getStages()
	{
		static $timeLineStages = [];

		if ($timeLineStages)
		{
			return $timeLineStages;
		}

		$date = new DateTimeTimeLine;
		$currentWeekDay = date('N', $date->getTimestamp());

		$date1 = new DateTimeTimeLine;
		$date2 = new DateTimeTimeLine;
		$date3 = new DateTimeTimeLine;
		$date4 = new DateTimeTimeLine;

		$timeLineStages = [
			// overdue
			'PERIOD1' => [
				'COLOR' => 'FF5752',
				'FILTER' => [
					'<=DEADLINE' => $date1
				],
				'UPDATE' => [],
				'UPDATE_ACCESS' => false
			],
			// today
			'PERIOD2' => [
				'COLOR' => '9DCF00',
				'FILTER' => [
					'>DEADLINE' => $date1,
					'<=DEADLINE' => $date2->setTime(23, 59, 59)
				],
				'UPDATE' => [
					'DEADLINE' => self::getClosestWorkHour($date1)
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
			// on this week
			'PERIOD3' => [
				'COLOR' => '2FC6F6',
				'FILTER' => [
					'>DEADLINE' => $date2,
					'<=DEADLINE' => $date3
										->add('+' . (7 - $currentWeekDay) . ' days')
										->setTime(23, 59, 59)
				],
				'UPDATE' => [
					'DEADLINE' => self::getClosestWorkHour($date3)
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
			// on next week
			'PERIOD4' => [
				'COLOR' => '55D0E0',
				'FILTER' => [
					'>DEADLINE' => $date3,
					'<=DEADLINE' => $date4
										->add('+' . (2*7 - $currentWeekDay) . ' days')
										->setTime(23, 59, 59)
				],
				'UPDATE' => [
					'DEADLINE' => self::getClosestWorkHour($date4)
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
			// without deadline
			'PERIOD5' => [
				'COLOR' => 'A8ADB4',
				'FILTER' => [
					'DEADLINE' => false
				],
				'UPDATE' => [
					'DEADLINE' => false
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
			// over next week
			'PERIOD6' => [
				($date5 = clone $date4),
				'COLOR' => '468EE5',
				'FILTER' => [
					'>DEADLINE' => $date4,
				],
				'UPDATE' => [
					'DEADLINE' => self::getClosestWorkHour($date5->add('+1 week'))
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
		];

		return $timeLineStages;
	}

	/**
	 * Gets first work hour in the past or in the future.
	 * @param DateTime $date Some date.
	 * @param bool $past In the past by default.
	 * @return DateTime
	 */
	public static function getClosestWorkHour(DateTime $date, $past = true)
	{
		static $timeOffset = null;
		static $daysOff = null;
		static $calendarSettings = null;

		$date = clone $date;

		if ($timeOffset === null)
		{
			$timeOffset = time() + \CTimeZone::getOffset();
		}

		if ($calendarSettings === null)
		{
			$calendarSettings = Calendar::getSettings();
		}

		// prepare days off
		if ($daysOff === null)
		{
			$daysOff = [
				0 => []// weekends
			];
			// holidays
			if (isset($calendarSettings['HOLIDAYS']))
			{
				foreach ((array)$calendarSettings['HOLIDAYS'] as $item)
				{
					if (
						isset($item['M']) &&
						isset($item['D'])
					)
					{
						$item['M'] = intval($item['M']);
						$item['D'] = intval($item['D']);
						if (!isset($daysOff[$item['M']]))
						{
							$daysOff[$item['M']] = [];
						}
						$daysOff[$item['M']][] = $item['D'];
					}
				}
			}
			// weekends
			$dayMap = array(
				'MO' => 1,
				'TU' => 2,
				'WE' => 3,
				'TH' => 4,
				'FR' => 5,
				'SA' => 6,
				'SU' => 0
			);
			if (
				isset($calendarSettings['WEEKEND']) &&
				is_array($calendarSettings['WEEKEND'])
			)
			{
				foreach ($calendarSettings['WEEKEND'] as $weekend)
				{
					if (
						is_string($weekend) &&
						isset($dayMap[$weekend])
					)
					{
						$daysOff[0][] = $dayMap[$weekend];
					}
				}
			}
		}

		// get in the past, first work day
		$count = 0;
		while ($date->getTimestamp() > $timeOffset)
		{
			$timeDate = $date->getTimestamp();
			$nDate = date('n', $timeDate);// month's day
			$jDate = date('j', $timeDate);// day without zero
			$wDate = date('w', $timeDate);// week's day

			if (
				(
					isset($daysOff[$nDate]) &&
					in_array($jDate, $daysOff[$nDate])
				)
				||
				in_array($wDate, $daysOff[0])
			)
			{
				if ($past)
				{
					$date->add('-1 day');
				}
				else
				{
					$date->add('+1 day');
				}
			}
			else
			{
				break;
			}
			if (++$count >= 365)
			{
				break;
			}
		}

		// if date is overdue
		if ($date->getTimestamp() < $timeOffset)
		{
			$date = new DateTime;
		}

		// set hour in selected day
		if (
			isset($calendarSettings['HOURS']['END']['H']) &&
			isset($calendarSettings['HOURS']['END']['M'])
		)
		{
			$date->setTime(
				$calendarSettings['HOURS']['END']['H'],
				$calendarSettings['HOURS']['END']['M']
			);
			$date->add((-1 * \CTimeZone::getOffset()) . ' seconds');
		}

		return $date;
	}
}