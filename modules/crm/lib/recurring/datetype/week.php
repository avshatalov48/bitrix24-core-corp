<?php
namespace Bitrix\Crm\Recurring\DateType;

use Bitrix\Main;
use Bitrix\Main\Type\Date;

class Week extends Base
{
	const TYPE_ALTERNATING_WEEKDAYS = 1;
	const TYPE_A_FEW_WEEKS_BEFORE = 2;
	const TYPE_A_FEW_WEEKS_AFTER = 3;

	const FIELD_INTERVAL_NAME = 'INTERVAL_WEEK';
	const FIELD_WEEKDAYS_NAME = 'WEEKDAYS';

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
			self::TYPE_ALTERNATING_WEEKDAYS,
			self::TYPE_A_FEW_WEEKS_BEFORE,
			self::TYPE_A_FEW_WEEKS_AFTER
		]);
	}

	/**
	 * @return Date
	 */
	public function calculate()
	{
		if (empty($this->type))
		{
			return $this->startDate;
		}

		if ($this->type === self::TYPE_A_FEW_WEEKS_BEFORE || $this->type === self::TYPE_A_FEW_WEEKS_AFTER)
		{
			return $this->calculateAlternatingWeeks();
		}
		else
		{
			return $this->calculateAlternatingWithDays();
		}
	}

	/**
	 * Return the date with weeks interval.
	 *
	 * Example: repeat every {count weeks} week
	 *
	 * @return Date
	 */
	private function calculateAlternatingWeeks()
	{
		$interval = $this->interval;
		if ($this->type === self::TYPE_A_FEW_WEEKS_BEFORE)
		{
			$interval = "-{$interval}";
		}

		return $this->startDate->add($interval." weeks");
	}

	/**
	 * Return the date with weeks interval and day offset.
	 *
	 * Example: repeat every {list of weekdays} of every the {count weeks} weeks
	 * 		#Repeat every monday and friday of every the 4th week#
	 *
	 * @return Date
	 */
	private function calculateAlternatingWithDays()
	{
		$monday = 1;
		$days = is_array($this->params[self::FIELD_WEEKDAYS_NAME]) ? $this->params[self::FIELD_WEEKDAYS_NAME] : array($monday);
		sort($days);
		$currentDay = (int)($this->startDate->format("N"));
		$nextDay = null;

		foreach ($days as $day)
		{
			if ($day >= $currentDay)
			{
				$nextDay = $day;
				break;
			}
		}

		if ($nextDay)
		{
			$dataText = "+" . ($nextDay - $currentDay) . " days";
			if ($this->interval > 1)
			{
				$dataText = " +" . $this->interval - 1 . " weeks ".$dataText;
			}
		}
		else
		{
			$dataText = " +" . $this->interval . " weeks +" . ($days[0] - $currentDay) . " days";
		}

		return $this->startDate->add($dataText);
	}
}