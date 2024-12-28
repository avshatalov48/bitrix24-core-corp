<?php

namespace Bitrix\Tasks\Integration\Calendar;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Integration\Calendar\ClosestWorkDateStrategy\StrategyFactory;
use Bitrix\Tasks\Integration\Calendar\Exception\LoopException;
use Bitrix\Tasks\Integration\Calendar\Schedule\PortalSchedule;
use Bitrix\Tasks\Util\Type\DateTime;

class Calendar
{
	protected const MAX_ITERATIONS = 365;
	protected const MINUTES_TO_ROUND_UP = 5;

	protected ScheduleInterface $schedule;

	protected static ?self $instance = null;

	public static function needUseSchedule(): bool
	{
		return Option::get('tasks', 'tasks_use_schedule', 'Y') === 'Y';
	}

	public static function needUseCalendar(string $feature): bool
	{
		return match($feature)
		{
			'flow' => Option::get('tasks', 'tasks_flow_use_calendar', 'Y') === 'Y',
			'regular_template' => Option::get('tasks', 'tasks_regular_template_use_calendar', 'N') === 'Y',
			default => false,
		};
	}

	public static function createFromPortalSchedule(?array $settings = null): static
	{
		$schedule = new PortalSchedule($settings);

		return new static($schedule);
	}

	public function __construct(ScheduleInterface $schedule)
	{
		$this->schedule = $schedule;
	}

	public function getSchedule(): ScheduleInterface
	{
		return $this->schedule;
	}

	public function getClosestDate(
		\Bitrix\Main\Type\DateTime $date,
		int $offsetInSeconds,
		bool $matchSchedule = false,
		bool $matchWorkTime = false,
	): DateTime
	{
		$date = DateTime::createFromDateTime($date);
		$date->stripSeconds();

		$possibleDate = clone $date;
		$possibleDate = $possibleDate->disableUserTime();

		if ($offsetInSeconds <= 0)
		{
			return $possibleDate;
		}

		$possibleDate->add($offsetInSeconds . ' seconds');

		if ($matchSchedule)
		{
			$matchWorkTime = true;
		}

		if (static::needUseSchedule())
		{
			$closestWorkDateStrategy = StrategyFactory::getStrategy(
				$this->schedule,
				$matchSchedule,
				$matchWorkTime,
			);

			$closestWorkDate = $closestWorkDateStrategy->getClosestWorkDate($date, $offsetInSeconds);

			return $this->roundDate($closestWorkDate);
		}

		if (!$matchWorkTime)
		{
			return $possibleDate;
		}

		if ($this->schedule->isWorkTime($possibleDate) && !$this->schedule->isWeekend($possibleDate))
		{
			return $possibleDate;
		}

		return $this->getNextWorkdayDate($possibleDate);
	}

	protected function roundDate(DateTime $date): DateTime
	{
		$divisionRemainder = $date->getMinute() % static::MINUTES_TO_ROUND_UP;
		if ($divisionRemainder === 0)
		{
			return $date;
		}
		$restOfMinutes = static::MINUTES_TO_ROUND_UP - $divisionRemainder;

		return $date->setTime($date->getHour(), $date->getMinute() + $restOfMinutes);
	}

	/**
	 * Will be removed soon.
	 */
	protected function getNextWorkdayDate(DateTime $start): DateTime
	{
		$currentDate = DateTime::createFromTimestamp($start->getTimestamp());

		$counter = 0;

		while ($counter < self::MAX_ITERATIONS)
		{
			if ($this->schedule->isWeekend($currentDate))
			{
				$currentDate->stripTime();
				$currentDate->addDay(1);

				++$counter;

				continue;
			}

			$intervalStart = $this->schedule->getShiftStart($currentDate);
			$intervalEnd = $this->schedule->getShiftEnd($currentDate);

			if ($intervalEnd->checkLT($start))
			{
				$currentDate->stripTime();
				$currentDate->addDay(1);

				++$counter;

				continue;
			}

			return $intervalStart->checkLT($start) ? $start : $intervalStart;
		}

		$exception = new LoopException('Probably infinite loop with date ' . $start->toString());
		Application::getInstance()->getExceptionHandler()->writeToLog($exception);

		return $currentDate;
	}
}
