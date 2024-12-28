<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\Calendar\ClosestWorkDateStrategy;

use Bitrix\Main\Application;
use Bitrix\Tasks\Integration\Calendar\ClosestWorkDateStrategyInterface;
use Bitrix\Tasks\Integration\Calendar\Exception\LoopException;
use Bitrix\Tasks\Integration\Calendar\NextWorkDateTrait;
use Bitrix\Tasks\Integration\Calendar\ScheduleInterface;
use Bitrix\Tasks\Util\Type\DateTime;

abstract class MatchStrategy implements ClosestWorkDateStrategyInterface
{
	use NextWorkDateTrait;

	protected const SECONDS_IN_DAY = 86400;
	protected const SECONDS_IN_HOUR = 3600;
	protected const SECONDS_IN_MINUTE = 60;
	protected const DAYS_IN_YEAR = 365;
	protected const MAX_ITERATIONS = 5 * self::DAYS_IN_YEAR + 2; // with leap years

	protected ScheduleInterface $schedule;

	public function __construct(ScheduleInterface $schedule)
	{
		$this->schedule = $schedule;
	}

	abstract protected function getRestOfDay(DateTime $date): int;

	abstract protected function getWorkDayDuration(DateTime $date): int;

	abstract protected function getShiftStart(DateTime $date): DateTime;

	abstract protected function isWorkTime(DateTime $date): bool;

	public function getClosestWorkDate(\Bitrix\Main\Type\DateTime $date, int $offsetInSeconds): DateTime
	{
		$closestWorkDate = $this->processDateTime($date);

		$restOfDay = $this->getRestOfDay($closestWorkDate);

		if ($this->isWorkTime($closestWorkDate))
		{
			if ($restOfDay >= $offsetInSeconds)
			{
				$closestWorkDate->add($offsetInSeconds . ' seconds');

				return $this->getNextWorkDate($closestWorkDate);
			}

			$offsetInSeconds -= $restOfDay;
		}

		$closestWorkDate->add('1 day');

		$counter = 0;
		while ($counter < static::MAX_ITERATIONS)
		{
			$restOfDay = $this->getWorkDayDuration($closestWorkDate);

			if ($this->isWorkTime($closestWorkDate))
			{
				$shiftStart = $this->getShiftStart($closestWorkDate);
				$closestWorkDate->setTime($shiftStart->getHour(), $shiftStart->getMinute());

				if ($offsetInSeconds <= $restOfDay)
				{
					$closestWorkDate->add($offsetInSeconds . ' seconds');

					return $this->getNextWorkDate($closestWorkDate);
				}

				$offsetInSeconds -= $restOfDay;
			}

			$closestWorkDate->add('1 day');

			++$counter;
		}

		$exception = new LoopException(
			"Probably infinite loop with start date {$date->toString()}, "
			. "offset {$offsetInSeconds} in seconds "
			. "and end date {$closestWorkDate->toString()}"
		);
		Application::getInstance()->getExceptionHandler()->writeToLog($exception);

		return $closestWorkDate;
	}

	protected function processDateTime(\Bitrix\Main\Type\DateTime $date): DateTime
	{
		return DateTime::createFromDateTime($date);
	}
}
