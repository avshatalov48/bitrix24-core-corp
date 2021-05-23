<?php
namespace Bitrix\Tasks\Scrum\Utility;

use Bitrix\Main\ArgumentTypeException;

class SprintRanges
{
	private $allDays = [];
	private $weekdays = [];
	private $weekendInfo = [];
	private $currentWeekDay = 0;

	public function getAllDays(): array
	{
		return $this->allDays;
	}

	public function setAllDays(array $allDays): void
	{
		$this->allDays = $allDays;
	}

	public function getWeekdays(): array
	{
		return $this->weekdays;
	}

	public function setWeekdays(array $weekdays): void
	{
		$this->weekdays = $weekdays;
	}

	public function getWeekendInfo(): array
	{
		return $this->weekendInfo;
	}

	public function setWeekendInfo(array $weekendInfo): void
	{
		foreach ($weekendInfo as $info)
		{
			if (!array_key_exists('previousWeekday', $info) || !array_key_exists('weekendNumber', $info))
			{
				throw new ArgumentTypeException('weekendInfo');
			}
		}
		$this->weekendInfo = $weekendInfo;
	}

	public function getRealDayNumber(int $dayNumber): int
	{
		return array_search($dayNumber, array_keys($this->getWeekdays())) + 1;
	}

	public function getPreviousWeekdayByDayNumber(int $dayNumber): int
	{
		if (array_key_exists($dayNumber, $this->weekendInfo))
		{
			return $this->weekendInfo[$dayNumber]['previousWeekday'];
		}
		else
		{
			return 0;
		}
	}

	public function getLastSprintDayTime(): int
	{
		return end($this->weekdays);
	}

	public function setCurrentWeekDay(int $currentWeekDay)
	{
		$this->currentWeekDay = (int)$currentWeekDay;
	}

	public function getCurrentWeekDay(): int
	{
		return $this->currentWeekDay;
	}
}