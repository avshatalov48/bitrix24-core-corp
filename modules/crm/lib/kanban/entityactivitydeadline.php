<?php


namespace Bitrix\Crm\Kanban;


use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Kanban\Entity\EntityActivities;
use Bitrix\Crm\Settings\WorkTime;

class EntityActivityDeadline
{
	protected const SECONDS_IN_HOUR = 3600;

	protected WorkTime $workTime;
	protected ?DateTime $currentDeadline = null;

	public function __construct()
	{
		$this->workTime = new WorkTime();
	}

	public function setCurrentDeadline(DateTime $deadline): self
	{
		$this->currentDeadline = $deadline->toUserTime();

		return $this;
	}

	public function getDeadline(string $statusTypeId): ?DateTime
	{
		if (strpos($statusTypeId, ':'))
		{
			[$prefix, $statusTypeId] = explode(':', $statusTypeId);
		}

		if (
			$statusTypeId === EntityActivities::STAGE_IDLE
			|| $statusTypeId === EntityActivities::STAGE_OVERDUE
		)
		{
			return null;
		}

		$dateTime = $this->getUserDateTime();

		if ($statusTypeId === EntityActivities::STAGE_PENDING)
		{
			$dateTime = $this->getDateTimeForTodayActivity($dateTime);
		}
		elseif ($statusTypeId === EntityActivities::STAGE_THIS_WEEK)
		{
			if ($this->isTodayIsLastDayOfWeek($dateTime))
			{
				$dateTime = $this->getDateTimeForTodayActivity($dateTime);
			}
			else
			{
				$this->setTimeFromActivity($dateTime);
				$dateTime->add('P1D');
			}
		}
		elseif ($statusTypeId === EntityActivities::STAGE_NEXT_WEEK)
		{
			$this->setTimeFromActivity($dateTime);
			$dateTime->add('P1W');
		}
		else
		{
			$this->setTimeFromActivity($dateTime);
			$dateTime->add('P2W');
		}

		return \CCrmDateTimeHelper::getServerTime($dateTime);
	}

	protected function getUserDateTime(): DateTime
	{
		$currentDateTime = new DateTime();
		$userTimezoneDay = Datetime::createFromTimestamp($currentDateTime->getTimestamp());
		$userTimezoneDay->toUserTime();

		return DateTime::createFromTimestamp($userTimezoneDay->getTimestamp());
	}

	protected function getDateTimeForTodayActivity(DateTime $dateTime): DateTime
	{
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

	/**
	 * The first working day is taken into account,
	 * i.e. if the first day is Wednesday, then the last day of the week is Tuesday
	 */
	protected function isTodayIsLastDayOfWeek(DateTime $currentDateTime): bool
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

	protected function setTimeFromActivity(DateTime $currentDateTime): void
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
