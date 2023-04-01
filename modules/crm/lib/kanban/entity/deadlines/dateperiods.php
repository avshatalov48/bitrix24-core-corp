<?php

namespace Bitrix\Crm\Kanban\Entity\Deadlines;

use Bitrix\Crm\Kanban\DatetimeStages;
use Bitrix\Crm\Settings\WorkTime;
use Bitrix\Main\Application;
use Bitrix\Main\Type\Datetime;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Context;

/**
 * Class for getting various dates related to the formation of terms.
 */
class DatePeriods
{
	private DateTime $userCurrentDateTime;

	private Context\Culture $culture;

	private DatetimeStages $datetimeStages;

	public function __construct(?DateTime $now = null)
	{
		$this->culture = Application::getInstance()->getContext()->getCulture();
		$this->userCurrentDateTime = $this->userCurrentDateTime($now ?: new DateTime());
		$this->datetimeStages = new DatetimeStages(new WorkTime());
	}

	private function userCurrentDateTime(DateTime $now): DateTime
	{
		$userTimezoneDay = Datetime::createFromTimestamp($now->getTimestamp());
		$userTimezoneDay->toUserTime();
		return DateTime::createFromTimestamp($userTimezoneDay->getTimestamp());
	}

	public function today(): Date
	{
		return Date::createFromTimestamp($this->userCurrentDateTime->getTimestamp());
	}

	public function tomorrow(): Date
	{
		$date = clone $this->userCurrentDateTime;
		$date->add('+1 day');

		return Date::createFromTimestamp($date->getTimestamp());
	}

	public function currentWeekLastDay(): Date
	{
		$date = clone $this->userCurrentDateTime;
		$weekStartDay = $this->culture->getWeekStart();
		$todayDay = (int)$date->format('w');
		$daysToAdd = ($todayDay >= $weekStartDay)
			? (6 - $todayDay + $weekStartDay)
			: $weekStartDay - $todayDay - 1
		;
		if ($daysToAdd > 0)
		{
			$date->add('+' . $daysToAdd . ' days');
		}
		return Date::createFromTimestamp($date->getTimestamp());
	}

	public function nextWeekFirstDay(): Date
	{
		$lastWeekDay = $this->currentWeekLastDay();
		return (clone $lastWeekDay)->add('+1 day');
	}

	public function nextWeekLastDay(): Date
	{
		$lastWeekDay = $this->currentWeekLastDay();
		return (clone $lastWeekDay)->add('+7 day');
	}

	public function afterNextWeek(): Date
	{
		$lastWeekDay = $this->currentWeekLastDay();
		return (clone $lastWeekDay)->add('+8 day');
	}

	public function stageByDate(?Date $checkDate): string
	{
		if ($checkDate === null)
		{
			return DeadlinesStageManager::STAGE_LATER;
		}

		$checkTs = $checkDate->getTimestamp();

		if ($checkTs < $this->today()->getTimestamp())
		{
			return DeadlinesStageManager::STAGE_OVERDUE;
		}

		if ($checkTs === $this->today()->getTimestamp())
		{
			return DeadlinesStageManager::STAGE_TODAY;
		}

		if (
			$checkTs >= $this->tomorrow()->getTimestamp()
			&& $checkTs <= $this->currentWeekLastDay()->getTimestamp())
		{
			return DeadlinesStageManager::STAGE_THIS_WEEK;
		}

		if (
			$checkTs >= $this->nextWeekFirstDay()->getTimestamp()
			&& $checkTs <= $this->nextWeekLastDay()->getTimestamp()
		)
		{
			return DeadlinesStageManager::STAGE_NEXT_WEEK;
		}

		return DeadlinesStageManager::STAGE_LATER;
	}

	public function calculateDateByStage(string $stage): ?Date
	{
		switch ($stage)
		{
			case DeadlinesStageManager::STAGE_TODAY:
				$result = $this->datetimeStages->today($this->userCurrentDateTime);
				break;
			case DeadlinesStageManager::STAGE_THIS_WEEK:
				$result = $this->datetimeStages->thisWeek($this->userCurrentDateTime);
				break;
			case DeadlinesStageManager::STAGE_NEXT_WEEK:
			case DeadlinesStageManager::STAGE_OVERDUE:
				$result = $this->datetimeStages->nextWeek($this->userCurrentDateTime);
				break;
			case DeadlinesStageManager::STAGE_LATER:
				$result = $this->datetimeStages->afterTwoWeek($this->userCurrentDateTime);
				break;
			default:
				return null;
		}
		return \CCrmDateTimeHelper::getServerTime($result);
	}
}
