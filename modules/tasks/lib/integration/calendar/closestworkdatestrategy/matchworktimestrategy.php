<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\Calendar\ClosestWorkDateStrategy;

use Bitrix\Tasks\Util\Type\DateTime;

class MatchWorkTimeStrategy extends MatchStrategy
{
	protected function getRestOfDay(DateTime $date): int
	{
		return
			static::SECONDS_IN_DAY
			- $date->getHour() * static::SECONDS_IN_HOUR
			- $date->getMinute() * static::SECONDS_IN_MINUTE
			;
	}

	protected function getWorkDayDuration(DateTime $date): int
	{
		return static::SECONDS_IN_DAY;
	}

	protected function getShiftStart(DateTime $date): DateTime
	{
		return $date->setTime(0, 0);
	}

	protected function isWorkTime(DateTime $date): bool
	{
		return !$this->schedule->isWeekend($date);
	}
}
