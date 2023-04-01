<?php

namespace Bitrix\Crm\Kanban;

use Bitrix\Crm\Settings\WorkTime;
use Bitrix\Main\Type\DateTime;

class DatetimeStages
{
	protected const SECONDS_IN_HOUR = 3600;

	protected WorkTime $workTime;

	protected ?DateTime $currentDeadline = null;

	public function __construct(WorkTime $workTime)
	{
		$this->workTime = $workTime;
	}

	public function setCurrentDeadline(DateTime $deadline): self
	{
		$this->currentDeadline = $deadline->toUserTime();
		return $this;
	}

	public function currentFromThisWeek(DateTime $dateTime): DateTime
	{
		$dateTime = $this->current($dateTime);

		return ($this->isTodayIsLastDayOfWeek($dateTime) ? $dateTime : $dateTime->add('P1D'));
	}

	public function current(DateTime $dateTime): DateTime
	{
		$dateTime = clone $dateTime;
		$this->setTimeFromActivity($dateTime);

		return $dateTime;
	}

	public function today(DateTime $dateTime): DateTime
	{
		$dateTime = clone $dateTime;
		$timeTo = $this->workTime->getData()['TIME_TO'];
		$endWorkDayDateTime = (clone($dateTime))->setTime($timeTo->hours, $timeTo->minutes);

		$endWorkDayTimestamp = $endWorkDayDateTime->getTimestamp();
		$currentTimestamp = $dateTime->getTimestamp();

		if (
			(
				$this->workTime->isWorkTime($dateTime)
				&& (($endWorkDayTimestamp - $currentTimestamp) / self::SECONDS_IN_HOUR) > 1
			)
			||
			(
				!$this->workTime->isWorkTime($dateTime)
				&& ($endWorkDayTimestamp - $currentTimestamp) > 0
			)
		)
		{
			$endWorkDayDateTime->add('-PT1H');
		}

		return $endWorkDayDateTime;
	}

	public function thisWeek(DateTime $dateTime): DateTime
	{
		$dateTime = clone $dateTime;
		if ($this->isTodayIsLastDayOfWeek($dateTime))
		{
			$dateTime = $this->today($dateTime);
		}
		else
		{
			$this->setTimeFromActivity($dateTime);
			$dateTime->add('P1D');
		}
		return $dateTime;
	}

	public function nextWeek(DateTime $dateTime): DateTime
	{
		$dateTime = clone $dateTime;
		$this->setTimeFromActivity($dateTime);
		$dateTime->add('P1W');
		return $dateTime;
	}

	public function afterTwoWeek(DateTime $dateTime): DateTime
	{
		$dateTime = clone $dateTime;
		$this->setTimeFromActivity($dateTime);
		$dateTime->add('P2W');
		return $dateTime;
	}

	private function isTodayIsLastDayOfWeek(DateTime $currentDateTime): bool
	{
		$days = [
			'SU' => 'sunday',
			'MO' => 'monday',
			'TU' => 'tuesday',
			'WE' => 'wednesday',
			'TH' => 'thursday',
			'FR' => 'friday',
			'SA' => 'saturday',
		];

		$flatDaysList = array_flip(array_keys($days));
		$weekStart = $this->workTime->getData()['WEEK_START'];
		$weekStartNumber = (int)$flatDaysList[$weekStart];
		$currentDayNumber = (int)($currentDateTime->format('w'));

		if ($weekStartNumber === 0)
		{
			$lastWeekDayNumber = 6;
		}
		elseif($weekStartNumber === 6)
		{
			$lastWeekDayNumber = 0;
		}
		else
		{
			$lastWeekDayNumber = $weekStartNumber - 1;
		}

		return ($currentDayNumber === $lastWeekDayNumber);
	}

	private function setTimeFromActivity(DateTime $currentDateTime): void
	{
		if ($this->currentDeadline)
		{
			$currentDateTime->setTime(
				$this->currentDeadline->format('H'),
				$this->currentDeadline->format('i')
			);
		}
	}
}
