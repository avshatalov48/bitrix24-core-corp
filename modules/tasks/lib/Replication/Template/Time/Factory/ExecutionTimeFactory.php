<?php

namespace Bitrix\Tasks\Replication\Template\Time\Factory;

use Bitrix\Tasks\Replication\Template\AbstractParameter;
use Bitrix\Tasks\Replication\Template\Option\Options;
use Bitrix\Tasks\Replication\Template\Time\Enum\Day;
use Bitrix\Tasks\Replication\Template\Time\Enum\DayType;
use Bitrix\Tasks\Replication\Template\Time\Enum\Ordinal;
use Bitrix\Tasks\Replication\Template\Time\Enum\Period;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Type\DateTime;

final class ExecutionTimeFactory
{
	private const MONTHS_IN_YEAR = 12;
	private const DAYS_IN_MONTH = 31;
	private const DAYS_IN_FEBRUARY = 28;
	private const DAYS_IN_LEAP_YEAR_FEBRUARY = 29;
	private const SECONDS_IN_DAY = 86400;
	private const DAYS_IN_WEEK = 7;

	private int $baseExecutionTimeTS;
	private int $creatorPreferredTimeTS;
	private array $parsedReplicateParameter;

	private function __construct()
	{
	}

	private function setBaseExecutionTimeTS(int $baseExecutionTimeTS): self
	{
		$this->baseExecutionTimeTS = $baseExecutionTimeTS;
		return $this;
	}

	private function setCreatorPreferredTimeTS(int $creatorPreferredTimeTS): self
	{
		$this->creatorPreferredTimeTS = $creatorPreferredTimeTS;
		return $this;
	}

	private function setReplicateParameter(AbstractParameter $replicateParameter): self
	{
		$this->parsedReplicateParameter = Options::validate($replicateParameter->getData());
		return $this;
	}


	public static function getNextExecutionTime(
		int $baseExecutionTimeTS,
		int $creatorPreferredTimeTS,
		AbstractParameter $parameter
	): int
	{
		$factory =
			(new self())
				->setBaseExecutionTimeTS($baseExecutionTimeTS)
				->setCreatorPreferredTimeTS($creatorPreferredTimeTS)
				->setReplicateParameter($parameter);

		$nextExecutionTimeTS = 0;

		$replicationType = $factory->getReplicationType();
		switch ($replicationType)
		{
			case Period::DAILY:
				$nextExecutionTimeTS = $factory->getTemplateDailyExecutionDate();
				break;

			case Period::WEEKLY:
				$nextExecutionTimeTS = $factory->getTemplateWeeklyExecutionDate();
				break;

			case Period::MONTHLY:
				$nextExecutionTimeTS = $factory->getTemplateMonthlyExecutionDate();
				break;

			case Period::YEARLY:
				$nextExecutionTimeTS = $factory->getTemplateYearlyExecutionDate();
				break;
		}

		return $nextExecutionTimeTS;
	}

	private function getTemplateDailyExecutionDate(): int
	{
		$num =
			(int)$this->parsedReplicateParameter['EVERY_DAY']
			+ (int)($this->parsedReplicateParameter['DAILY_MONTH_INTERVAL'] ?? 0);

		$date = $this->stripTime($this->baseExecutionTimeTS) + $this->creatorPreferredTimeTS;

		if ($date <= $this->baseExecutionTimeTS)
		{
			$date += self::SECONDS_IN_DAY * $num;
		}

		if ($this->parsedReplicateParameter['WORKDAY_ONLY'] === 'Y')
		{
			// get server datetime as string and create an utc-datetime object with this string, as Calendar works only with utc datetime object
			$dateInst = DateTime::createFromUserTimeGmt(UI::formatDateTime($date));
			$calendar = new Calendar();

			if (!$calendar->isWorkTime($dateInst))
			{
				$cwt = $calendar->getClosestWorkTime($dateInst); // get the closest time in UTC
				$cwt = $cwt->convertToLocalTime(); // change timezone to server timezone

				$date = $cwt->getTimestamp(); // set server timestamp
				$date = $this->stripTime($date) + $this->creatorPreferredTimeTS;
			}
		}

		return $date;
	}

	private function getTemplateWeeklyExecutionDate(): int
	{
		$weekNumber = (int)$this->parsedReplicateParameter['EVERY_WEEK'];
		$currentDayNumber = (int)date('N', $this->baseExecutionTimeTS); // day 1 - 7
		$weekDaysNumbers =
			is_array($this->parsedReplicateParameter['WEEK_DAYS']) && count(array_filter($this->parsedReplicateParameter['WEEK_DAYS']))
			? $this->parsedReplicateParameter['WEEK_DAYS'] : [1]; // days 1 - 7

		$weekDaysNumbers = array_map('intval', $weekDaysNumbers);
		$preferredDateTime = $this->stripTime($this->baseExecutionTimeTS) + $this->creatorPreferredTimeTS;
		$date = $preferredDateTime;

		// check if we need to create task today
		if ($date > $this->baseExecutionTimeTS && in_array($currentDayNumber, $weekDaysNumbers, true))
		{
			return $date;
		}

		// check if we have "chosen day" ahead, till the end of the week
		$nextDay = false;
		for ($i = $currentDayNumber + 1; $i <= self::DAYS_IN_WEEK; $i++)
		{
			if (in_array($i, $weekDaysNumbers, true))
			{
				$nextDay = $i;
				break;
			}
		}

		if ($nextDay)
		{
			// next available day found, so just move there
			$date =
				$preferredDateTime
				+ ($nextDay - $currentDayNumber) * self::SECONDS_IN_DAY
			;
		}
		else
		{
			// we are at the end of the week, and there are no chosen days to pick,
			// so we skip $weekNumber weeks and add the first available day
			reset($weekDaysNumbers);
			$firstDay = current($weekDaysNumbers);
			$restOfWeek = self::DAYS_IN_WEEK - $currentDayNumber;

			$date =
				$preferredDateTime
				+ ($weekNumber > 1 ? ($weekNumber - 1) : 0)
				* self::DAYS_IN_WEEK
				* self::SECONDS_IN_DAY
				+ ($restOfWeek + $firstDay) * self::SECONDS_IN_DAY
			;
		}

		return $date;
	}

	private function getTemplateMonthlyExecutionDate(): int
	{
		$subType = (int)$this->parsedReplicateParameter['MONTHLY_TYPE'] === 2 ? DayType::WEEK : DayType::MONTH;
		if ($subType === DayType::WEEK)
		{
			$ordinal = $this->getOrdinal('MONTHLY_WEEK_DAY_NUM');
			$weekDay = $this->getWeekDay('MONTHLY_WEEK_DAY');
			$num = $this->getMonthlyMonthNum('MONTHLY_MONTH_NUM_2');

			$date = strtotime("{$ordinal} {$weekDay} of this month") + $this->creatorPreferredTimeTS;

			if ($date <= $this->baseExecutionTimeTS)
			{
				$date = $this->addMonthsToDate(new \DateTime(date('Y-m-d H:i:s', $date)), $num)->getTimestamp();
				$date = strtotime("{$ordinal} {$weekDay} of " . date("Y-m-d", $date)) + $this->creatorPreferredTimeTS;
			}
		}
		else
		{
			$day = $this->getDayNum('MONTHLY_DAY_NUM');
			$num = $this->getMonthlyMonthNum('MONTHLY_MONTH_NUM_1');

			$date = $this->stripTime(strtotime(date('Y-m-' . sprintf('%02d', $day), $this->baseExecutionTimeTS))) + $this->creatorPreferredTimeTS;

			if ($date <= $this->baseExecutionTimeTS)
			{
				$date = $this->addMonthsToDate(new \DateTime(date('Y-m-d H:i:s', $date)), $num)->getTimestamp();
				$date = strtotime(date('Y-m-' . sprintf('%02d', $day), $date)) + $this->creatorPreferredTimeTS;
			}
		}

		return $date;
	}

	private function getTemplateYearlyExecutionDate(): int
	{
		$subType = (int)$this->parsedReplicateParameter['YEARLY_TYPE'] === 2 ? DayType::WEEK : DayType::MONTH;
		if ($subType === DayType::WEEK)
		{
			$ordinal = $this->getOrdinal('YEARLY_WEEK_DAY_NUM');
			$weekDay = $this->getWeekDay('YEARLY_WEEK_DAY');
			$month = $this->getMonthNum('YEARLY_MONTH_2');

			$date = strtotime(
					"{$ordinal} {$weekDay} of "
					. date('Y', $this->baseExecutionTimeTS)
					. '-'
					. sprintf('%02d', $month)
					. '-01'
				) + $this->creatorPreferredTimeTS;

			if ($date <= $this->baseExecutionTimeTS)
			{
				$date = strtotime(
						"{$ordinal} {$weekDay} of "
						. (date('Y', $this->baseExecutionTimeTS) + 1)
						. '-'
						. sprintf('%02d', $month)
						. '-01'
					) + $this->creatorPreferredTimeTS;
			}
		}
		else
		{
			$day = $this->getDayNum('YEARLY_DAY_NUM');
			$month = $this->getMonthNum('YEARLY_MONTH_1');

			$date = strtotime(
					date('Y', $this->baseExecutionTimeTS)
					. '-'
					. sprintf('%02d', $month)
					. '-'
					. sprintf('%02d', $day)
				) + $this->creatorPreferredTimeTS;

			if ($date <= $this->baseExecutionTimeTS)
			{
				$date = strtotime(
						(date('Y', $this->baseExecutionTimeTS) + 1)
						. '-'
						. sprintf('%02d', $month)
						. '-'
						. sprintf('%02d', $day)
					) + $this->creatorPreferredTimeTS;
			}
		}

		return $date;
	}


	private function stripTime(int $timestamp): int
	{
		$month = (int)date('n', $timestamp);
		$day = (int)date('j', $timestamp);
		$year = (int)date('Y', $timestamp);

		return (int)mktime(0, 0, 0, $month, $day, $year);
	}

	private function addMonthsToDate(\DateTime $dateToAddMonths, int $monthsToAdd): \DateTime
	{
		$years = floor(abs($monthsToAdd / self::MONTHS_IN_YEAR));
		$leap = ($dateToAddMonths->format('d') >= self::DAYS_IN_LEAP_YEAR_FEBRUARY);
		$months = self::MONTHS_IN_YEAR * ($monthsToAdd >= 0 ? 1 : -1);

		for ($year = 1; $year < $years; $year++)
		{
			$dateToAddMonths = $this->addMonthsToDate($dateToAddMonths, $months);
		}
		$monthsToAdd -= ($year - 1) * $months;

		$resultDate = clone $dateToAddMonths;
		if ($monthsToAdd !== 0)
		{
			$dateToAddMonths->modify("{$monthsToAdd} months");

			$dateToAddMonthsFormattedMonth = (int)$dateToAddMonths->format('m');
			$resultDateFormattedMonth = (int)$resultDate->format('m');
			if (
				$dateToAddMonthsFormattedMonth % self::MONTHS_IN_YEAR
				!== (self::MONTHS_IN_YEAR + $monthsToAdd + $resultDateFormattedMonth) % self::MONTHS_IN_YEAR
			)
			{
				$day = $dateToAddMonths->format('d');
				$resultDate->modify("-{$day} days");
			}
			$resultDate->modify("{$monthsToAdd} months");
		}

		$resultDateFormattedYear = (int)$resultDate->format('Y');
		$resultDateFormattedDay =  (int)$resultDate->format('d');
		if (
			$leap
			&& ($resultDateFormattedYear % 4) === 0
			&& ($resultDateFormattedYear % 100) !== 0
			&& $resultDateFormattedDay === self::DAYS_IN_FEBRUARY
		)
		{
			$resultDate->modify('+1 day');
		}

		return $resultDate;
	}

	public function getReplicationType(): string
	{
		return in_array($this->parsedReplicateParameter['PERIOD'], Period::getAll(), true)
			? $this->parsedReplicateParameter['PERIOD']
			: Period::DAILY;
	}

	private function getOrdinal(string $key): string
	{
		return array_key_exists($this->parsedReplicateParameter[$key], Ordinal::getAll())
			? Ordinal::get($this->parsedReplicateParameter[$key])
			: Ordinal::FIRST;
	}

	private function getWeekDay(string $key): string
	{
		return array_key_exists($this->parsedReplicateParameter[$key], Day::getAll())
			? Day::get($this->parsedReplicateParameter[$key])
			: Day::MONDAY;
	}

	private function getDayNum(string $key): int
	{
		$dayNum = (int)$this->parsedReplicateParameter[$key];
		return $dayNum >= 1 && $dayNum <= self::DAYS_IN_MONTH
			? $dayNum
			: 1;
	}

	private function getMonthNum(string $key): int
	{
		$monthNum = (int)$this->parsedReplicateParameter[$key];
		return $monthNum >= 0 && $monthNum < self::MONTHS_IN_YEAR
			? $monthNum + 1
			: 1;
	}

	private function getMonthlyMonthNum(string $key): int
	{
		$monthlyMonthNum = (int)$this->parsedReplicateParameter[$key];
		return $monthlyMonthNum > 0 ? $monthlyMonthNum : 1;
	}
}