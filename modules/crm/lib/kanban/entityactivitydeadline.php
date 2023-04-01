<?php


namespace Bitrix\Crm\Kanban;


use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Kanban\Entity\EntityActivities;
use Bitrix\Crm\Settings\WorkTime;

class EntityActivityDeadline
{
	private DatetimeStages $datetimeStages;

	public function __construct()
	{
		$this->datetimeStages = new DatetimeStages(new WorkTime());
	}

	public function setCurrentDeadline(DateTime $deadline): self
	{
		$this->datetimeStages->setCurrentDeadline($deadline);
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
			$dateTime = $this->datetimeStages->current($dateTime);
		}
		elseif ($statusTypeId === EntityActivities::STAGE_THIS_WEEK)
		{
			$dateTime = $this->datetimeStages->currentFromThisWeek($dateTime);
		}
		elseif ($statusTypeId === EntityActivities::STAGE_NEXT_WEEK)
		{
			$dateTime = $this->datetimeStages->nextWeek($dateTime);
		}
		else
		{
			$dateTime = $this->datetimeStages->afterTwoWeek($dateTime);
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
}
