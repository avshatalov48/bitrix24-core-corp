<?php

namespace Bitrix\Tasks\Flow\Control\Task\Field;

use Bitrix\Tasks\Flow\Control\Task\Exception\FlowTaskException;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Flow\Responsible\Distributor;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Type\DateTime;
use CTimeZone;

class FlowFieldHandler
{
	protected FlowProvider $provider;
	protected Distributor $distributor;

	protected int $flowId;
	protected int $userId;

	public function __construct(int $flowId, int $userId = 0)
	{
		$this->flowId = $flowId;
		$this->userId = $userId;

		$this->init();
	}

	/**
	 * @throws FlowNotFoundException
	 * @throws FlowTaskException
	 */
	public function modify(array &$fields): array
	{
		$flow = $this->provider->getFlow($this->flowId, ['*', 'OPTIONS']);

		if (!FlowFeature::isFeatureEnabled())
		{
			throw new FlowTaskException('You cannot run a task without flow feature');
		}

		if (!$flow->isActive())
		{
			throw new FlowTaskException('You cannot run a task on an inactive flow');
		}

		$responsible = $this->distributor->generateResponsible($flow);

		$fields['RESPONSIBLE_ID'] = $responsible->getId();

		$deadline = $this->getDeadlineMatchWorkTimeWithTZOffset(
			$flow->getPlannedCompletionTime(),
			$flow->getMatchWorkTime()
		);

		$fields['MATCH_WORK_TIME'] = $flow->getMatchWorkTime();
		$fields['DEADLINE'] = UI::formatDateTime($deadline->convertToLocalTime()->getTimestamp());
		$fields['GROUP_ID'] = $flow->getGroupId();
		$fields['TASK_CONTROL'] = $flow->getTaskControl();
		$fields['ALLOW_CHANGE_DEADLINE'] = $flow->canResponsibleChangeDeadline();

		return $fields;
	}

	protected function getDeadlineMatchWorkTimeWithTZOffset(
		int $offsetInSeconds,
		bool $matchWorkTime = false
	): DateTime
	{
		if (\Bitrix\Tasks\Integration\Calendar\Calendar::useCalendar('flow'))
		{
			$calendar = \Bitrix\Tasks\Integration\Calendar\Calendar::getInstance();
			$closestWorkTime = $calendar->getClosestDate(new DateTime(), $offsetInSeconds, $matchWorkTime);

			return $closestWorkTime->add(CTimeZone::GetOffset($this->userId) . ' seconds');
		}

		$currentDate = DateTime::createFromUserTimeGmt((new DateTime()))->disableUserTime();

		$deadline = $currentDate->add(($offsetInSeconds) . ' seconds');

		if (!$matchWorkTime)
		{
			return $deadline;
		}

		$calendar = Calendar::getInstance();
		$isWorkTime = $calendar->isWorkTime($deadline);

		if ($isWorkTime)
		{
			$closestWorkTime = $deadline;
		}
		else
		{
			$closestWorkTime = $calendar->getClosestWorkTime($deadline);

			$endTimeHour = $calendar->getEndHour();
			$endTimeMinute = $calendar->getEndMinute();

			$endDateTime = (new DateTime())
				->setDate($currentDate->getYear(), $currentDate->getMonth(), $currentDate->getDay())
				->setTime($endTimeHour, $endTimeMinute);

			$restSeconds = abs($endDateTime->getTimestamp() - $currentDate->getTimestamp());

			$closestWorkTime->add($restSeconds . ' seconds');
		}

		return $closestWorkTime;
	}

	protected function init(): void
	{
		$this->provider = new FlowProvider();
		$this->distributor = new Distributor();
	}
}