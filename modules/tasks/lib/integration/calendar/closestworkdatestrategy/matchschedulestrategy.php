<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\Calendar\ClosestWorkDateStrategy;

use Bitrix\Tasks\Util\Type\DateTime;

class MatchScheduleStrategy extends MatchStrategy
{
	protected function processDateTime(\Bitrix\Main\Type\DateTime $date): DateTime
	{
		$date = DateTime::createFromDateTime($date);

		return $this->getNextWorkDate($date);
	}

	protected function getRestOfDay(DateTime $date): int
	{
		$shiftEnd = $this->schedule->getShiftEnd($date);
		return
			$shiftEnd->getHour() * static::SECONDS_IN_HOUR
			+ $shiftEnd->getMinute() * static::SECONDS_IN_MINUTE
			- $date->getHour() * static::SECONDS_IN_HOUR
			- $date->getMinute() * static::SECONDS_IN_MINUTE
		;
	}

	protected function getWorkDayDuration(DateTime $date): int
	{
		return $this->schedule->getWorkDayDuration($date);
	}

	protected function getShiftStart(DateTime $date): DateTime
	{
		return $this->schedule->getShiftStart($date);
	}

	protected function isWorkTime(DateTime $date): bool
	{
		return $this->schedule->isWorkTime($date) && !$this->schedule->isWeekend($date);
	}
}
