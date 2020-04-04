<?php
namespace Bitrix\Crm\Recurring\DateType;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;

class Day extends Base
{
	const TYPE_ALTERNATING_DAYS = 1;
	const TYPE_A_FEW_DAYS_BEFORE = 2;
	const TYPE_A_FEW_DAYS_AFTER = 3;

	const FIELD_INTERVAL_NAME = 'INTERVAL_DAY';
	const FIELD_IS_WORKDAY_NAME = 'IS_WORKDAY';

	/**
	 * @param array $params
	 * @param Date $startDate
	 *
	 * @return Date
	 */
	public static function calculateDate(array $params, Date $startDate)
	{
		$week = new self($params);
		$week->setType($params[self::FIELD_TYPE_NAME]);
		$week->setStartDate($startDate);
		$week->setInterval($params[self::FIELD_INTERVAL_NAME]);
		return $week->calculate();
	}

	/**
	 * @param int $type
	 *
	 * @return bool
	 */
	protected function checkType($type)
	{
		return in_array((int)$type, [
			self::TYPE_ALTERNATING_DAYS,
			self::TYPE_A_FEW_DAYS_BEFORE,
			self::TYPE_A_FEW_DAYS_AFTER
		]);
	}

	/**
	 * @return bool
	 */
	private function isWorkdayType()
	{
		return $this->params[self::FIELD_IS_WORKDAY_NAME] === 'Y';
	}

	/**
	 *  Calculate date with offset days
	 *
	 * @return Date
	 */
	public function calculate()
	{
		if ($this->isWorkdayType() && $this->type === self::TYPE_ALTERNATING_DAYS)
		{
			$resultDate = self::calculateForWorkingDays($this->params, $this->startDate);
		}
		else
		{
			if ($this->type === self::TYPE_ALTERNATING_DAYS)
			{
				$today = new Date();
				if ($this->interval > 1 || $this->startDate->getTimestamp() !== $today->getTimestamp())
				{
					$this->interval--;
				}
			}
			$text = $this->interval;
			if ($this->type === self::TYPE_A_FEW_DAYS_BEFORE)
			{
				$text = "-".$text;
			}

			$resultDate = $this->startDate->add("{$text} days");
		}

		return $resultDate;
	}

	/**
	 * Calculate date with offset of only working days
	 *
	 * @param array $params
	 * @param Date $date
	 * @param int $limit
	 *
	 * @return Date $date
	 */
	public static function calculateForWorkingDays(array $params, Date $date, $limit = null)
	{
		$dayNumber = 0;
		$limit = (int)$limit;
		$isLimit = $limit > 0;
		$weekDays = array('SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6);

		if (!Loader::includeModule('calendar'))
		{
			return $date;
		}
		$calendarSettings = \CCalendar::GetSettings();
		$weekHolidays = array_keys(array_intersect(array_flip($weekDays), $calendarSettings['week_holidays']));
		$yearHolidays = explode(',', $calendarSettings['year_holidays']);
		$lastWorkingDateInLimit = $date;
		$interval = (int)$params[self::FIELD_INTERVAL_NAME];

		while ($interval > 0)
		{
			if (!in_array($date->format("j.m"), $yearHolidays) && !in_array($date->format("w"), $weekHolidays))
			{
				if ($isLimit && $dayNumber < $limit)
				{
					$lastWorkingDateInLimit = clone($date);
				}
				$interval--;
			}

			if ($isLimit && $dayNumber == $limit)
			{
				return $lastWorkingDateInLimit;
			}

			if ($interval > 0)
			{
				$date->add("+ 1 days");
				$dayNumber++;
			}			
		}

		return $date;
	}
}