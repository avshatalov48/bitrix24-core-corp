<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\Calendar\ClosestWorkDateStrategy;

use Bitrix\Tasks\Integration\Calendar\ClosestWorkDateStrategyInterface;
use Bitrix\Tasks\Integration\Calendar\ScheduleInterface;

class StrategyFactory
{
	public static function getStrategy(ScheduleInterface $schedule, bool $matchSchedule, bool $matchWorkTime): ClosestWorkDateStrategyInterface
	{
		if ($matchSchedule)
		{
			return new MatchScheduleStrategy($schedule);
		}

		if ($matchWorkTime)
		{
			return new MatchWorkTimeStrategy($schedule);
		}

		return new NoMatchStrategy($schedule);
	}
}
