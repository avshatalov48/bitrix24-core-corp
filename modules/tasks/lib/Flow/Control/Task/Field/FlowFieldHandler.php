<?php

namespace Bitrix\Tasks\Flow\Control\Task\Field;

use Bitrix\Main\Localization\Loc;
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
	public function modify(array &$fields, array $taskData): array
	{
		$flow = $this->provider->getFlow($this->flowId, ['*', 'OPTIONS']);

		if (!FlowFeature::isFeatureEnabled())
		{
			throw new FlowTaskException('You cannot run a task without flow feature');
		}

		if (!$flow->isActive())
		{
			if (isset($fields['FLOW_ID']))
			{
				unset($fields['FLOW_ID']);
			}

			return $fields;
		}

		$responsible = $this->distributor->generateResponsible($flow, $fields, $taskData);
		$fields['RESPONSIBLE_ID'] = $responsible->getId();

		$isTaskAddedToFlow = false;
		if (isset($fields['FLOW_ID']) && (int)$fields['FLOW_ID'] > 0)
		{
			$isTaskAddedToFlow =
				!isset($taskData['FLOW_ID'])
				|| (int)$taskData['FLOW_ID'] !== (int)$fields['FLOW_ID']
			;
		}
		if (empty($taskData) || $isTaskAddedToFlow)
		{
			$deadline = $this->getDeadlineMatchWorkTimeWithTZOffset(
				$flow->getPlannedCompletionTime(),
				$flow->getMatchSchedule(),
				$flow->getMatchWorkTime(),
			);

			$fields['MATCH_WORK_TIME'] = $flow->getMatchWorkTime();
			$fields['DEADLINE'] = UI::formatDateTime($deadline->convertToLocalTime()->getTimestamp());
			$fields['GROUP_ID'] = $flow->getGroupId();
			$fields['TASK_CONTROL'] = $flow->getTaskControl();
			$fields['ALLOW_CHANGE_DEADLINE'] = $flow->canResponsibleChangeDeadline();
		}

		return $fields;
	}

	public function getModifiedFields(): array
	{
		return ['RESPONSIBLE_ID', 'MATCH_WORK_TIME', 'DEADLINE', 'GROUP_ID', 'TASK_CONTROL', 'ALLOW_CHANGE_DEADLINE'];
	}

	protected function getDeadlineMatchWorkTimeWithTZOffset(
		int $offsetInSeconds,
		bool $matchSchedule = false,
		bool $matchWorkTime = false,
	): DateTime
	{
		if (\Bitrix\Tasks\Integration\Calendar\Calendar::needUseCalendar('flow'))
		{
			$calendar = \Bitrix\Tasks\Integration\Calendar\Calendar::createFromPortalSchedule();

			return $calendar->getClosestDate(
				(new DateTime())->add(CTimeZone::GetOffset($this->userId) . ' seconds'),
				$offsetInSeconds,
				$matchSchedule,
				$matchWorkTime,
			);
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