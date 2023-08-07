<?php
namespace Bitrix\Crm\Recurring\DateType;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class Month extends Base
{
	const TYPE_DAY_OF_ALTERNATING_MONTHS = 1;
	const TYPE_WEEKDAY_OF_ALTERNATING_MONTHS = 2;
	const TYPE_A_FEW_MONTHS_BEFORE = 3;
	const TYPE_A_FEW_MONTHS_AFTER = 4;
	const FIRST_MONTH_DAY = 1;
	const LAST_MONTH_DAY = 0;
	const LAST_WEEK_IN_MONTH_VALUE = 4;

	const FIELD_INTERVAL_NAME = 'INTERVAL_MONTH';
	const FIELD_WEEKDAY_NAME = 'WEEKDAY';

	/** @var $monthBeginning Date */
	private $monthBeginning = null;

	/**
	 * @param array $params
	 * @param Date $startDate
	 *
	 * @return Date
	 */
	public static function calculateDate(array $params, Date $startDate)
	{
		$month = new self($params);
		$month->setType($params[self::FIELD_TYPE_NAME]);
		$month->setStartDate($startDate);
		$month->setInterval($params[self::FIELD_INTERVAL_NAME]);
		return $month->calculate();
	}

	/**
	 * @param int $type
	 *
	 * @return bool
	 */
	protected function checkType($type)
	{
		return in_array((int)$type, [
			self::TYPE_DAY_OF_ALTERNATING_MONTHS,
			self::TYPE_WEEKDAY_OF_ALTERNATING_MONTHS,
			self::TYPE_A_FEW_MONTHS_BEFORE,
			self::TYPE_A_FEW_MONTHS_AFTER
		]);
	}

	/**
	 * @return bool
	 */
	private function isWorkdayType()
	{
		return $this->params[Day::FIELD_IS_WORKDAY_NAME] === 'Y';
	}

	/**
	 * Result calculating.
	 *
	 * @return Date
	 */
	public function calculate()
	{
		if (empty($this->type))
		{
			return $this->startDate;
		}

		if ($this->type === self::TYPE_A_FEW_MONTHS_AFTER || $this->type === self::TYPE_A_FEW_MONTHS_BEFORE)
		{
			return $this->calculateAlternatingMonths();
		}
		else
		{
			return $this->calculateAlternatingWithDays();
		}
	}

	/**
	 * Return the date with months interval.
	 *
	 * Example: repeat every {count months} month
	 *
	 * @return Date
	 */
	private function calculateAlternatingMonths()
	{
		$interval = $this->interval;
		if ($this->type === self::TYPE_A_FEW_MONTHS_BEFORE)
		{
			$interval = "-{$interval}";
		}

		return $this->startDate->add($interval." months");
	}

	/**
	 * Set first day of the months with offset
	 */
	private function setMonthBeginning()
	{
		$monthValue = (int)$this->startDate->format("n");

		if (
			$this->interval === 1
			&& $this->type === self::TYPE_DAY_OF_ALTERNATING_MONTHS
			&& (int)$this->params[Day::FIELD_INTERVAL_NAME] > 0
			&& (int)$this->startDate->format("j") > (int)$this->params[Day::FIELD_INTERVAL_NAME]
		)
		{
			$monthValue++;
		}
		elseif ($this->interval > 1)
		{
			$monthValue += $this->interval;
		}

		$yearValue = (int)$this->startDate->format("Y");

		$ratio = $monthValue / 12;
		if ($ratio > 1)
		{
			$ratio = floor($ratio);
			$monthValue = $monthValue - (12 * $ratio);
			$yearValue += $ratio;
		}
		$firstMonthDayTimestamp = mktime(0, 0, 0, $monthValue, 1, $yearValue);
		$this->monthBeginning = Date::createFromTimestamp($firstMonthDayTimestamp);
	}

	/**
	 * Return the date with months interval and day offset.
	 *
	 * Example:
	 * 		TYPE_DAY_OF_ALTERNATING_MONTHS: repeat every {number day in month} {working|usual} day of every the {count months} month
	 * 			#Repeat every the 10th working day of every the 4th month#
	 * 		TYPE_WEEKDAY_OF_CERTAIN_MONTH: repeat every {number} {weekday} day of every the {count months} month
	 * 			#Repeat every the 2nd of friday of every the 6th month#
	 *
	 * @return Date
	 */
	private function calculateAlternatingWithDays()
	{
		$this->setMonthBeginning();
		$resultDate = $this->startDate;
		if ($this->type === self::TYPE_DAY_OF_ALTERNATING_MONTHS)
		{
			$resultDate = clone($this->monthBeginning);
			if (!$this->isWorkdayType())
			{
				$day = $this->getDayNumber();
				if ($day === self::LAST_MONTH_DAY)
				{
					$resultDate->add("+ 1 month")->add("- 1 day");
				}
				elseif ($day > 1)
				{
					$day = $day - 1;
					$resultDate->add("{$day} days");
				}
				$timestamp = $resultDate->getTimestamp();
				if ($timestamp < $this->startDate->getTimestamp())
				{
					$resultDate->add('+ 1 month');
				}
			}
			else
			{
				$resultDate = Day::calculateForWorkingDays($this->params, $resultDate, $this->monthBeginning->format('t'));
				if ($this->startDate->getTimestamp() > $resultDate->getTimestamp())
				{
					$resultDate = $this->monthBeginning->add('+ 1 month');
					$resultDate = Day::calculateForWorkingDays($this->params, $resultDate, $this->monthBeginning->format('t'));
				}
			}
		}
		elseif ($this->type === self::TYPE_WEEKDAY_OF_ALTERNATING_MONTHS)
		{
			$resultDate = $this->calculateForWeekdayType($this->params, $this->monthBeginning);
			if ($this->startDate->getTimestamp() > $resultDate->getTimestamp())
			{
				$resultDate = $this->calculateForWeekdayType($this->params, $this->monthBeginning->add("+1 months"));
			}
		}

		return $resultDate;
	}

	/**
	 * Return the date with weekday offset.
	 *
	 * @param array $params
	 * @param Date $startDate
	 *
	 * @return Date
	 */
	private function calculateForWeekdayType(array $params, Date $startDate)
	{
		$date = clone($startDate);

		$numWeekDay = (int)$date->format('N');

		if ($numWeekDay <= $params[self::FIELD_WEEKDAY_NAME])
		{
			$offset = $params[self::FIELD_WEEKDAY_NAME] - $numWeekDay;
		}
		else
		{
			$offset = 7 + $params[self::FIELD_WEEKDAY_NAME] - $numWeekDay;
		}

		$date->add("+{$offset} days");

		if ((int)$params[Week::FIELD_INTERVAL_NAME] < self::LAST_WEEK_IN_MONTH_VALUE)
		{
			$date->add("+" . (int)$params[Week::FIELD_INTERVAL_NAME] . " weeks");
		}
		else
		{
			$date->add("+3 weeks");
			$restDays = (int)(date('t', mktime(0, 0, 0, (int)$startDate->format("n"), 1, (int)$startDate->format("Y")))) - (int)($date->format('j'));
			if ($restDays >= 7)
			{
				$date->add("+1 weeks");
				return $date;
			}
		}
		return $date;
	}

	/**
	 * @return int
	 */
	private function getDayNumber()
	{
		if (!$this->monthBeginning)
		{
			$this->setMonthBeginning();
		}
		$intervalDay = (int)$this->params[Day::FIELD_INTERVAL_NAME];
		$countMonthDays = $this->monthBeginning->format('t');
		
		if ($intervalDay > $countMonthDays)
		{
			$day = self::LAST_MONTH_DAY;
		} 
		elseif ($intervalDay <= 0 || $this->isWorkdayType())
		{
			$day = self::FIRST_MONTH_DAY;
		} 
		else 
		{
			$day = $intervalDay;
		}

		return $day;
	}
}