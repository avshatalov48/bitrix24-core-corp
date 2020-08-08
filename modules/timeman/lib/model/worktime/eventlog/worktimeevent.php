<?php
namespace Bitrix\Timeman\Model\Worktime\EventLog;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\Contract\WorktimeRecordIdStorable;

class WorktimeEvent extends EO_WorktimeEvent implements WorktimeRecordIdStorable
{
	/** @var TimeHelper */
	private $timeHelper;

	private function getTimeHelper()
	{
		if (!$this->timeHelper)
		{
			$this->timeHelper = TimeHelper::getInstance();
		}
		return $this->timeHelper;
	}

	public static function create($eventName, $userId, $recordId, $recordedValue = null, $reason = null, $eventSource = null)
	{
		$workTimeEvent = new static();
		$workTimeEvent->setEventType($eventName);
		$workTimeEvent->setUserId($userId);
		$workTimeEvent->setWorktimeRecordId($recordId);
		$userUtcOffset = $workTimeEvent->getTimeHelper()->getUserUtcOffset($userId);
		$workTimeEvent->setRecordedOffset($userUtcOffset);
		$workTimeEvent->setReason($reason);

		$workTimeEvent->setRecordedValue($recordedValue);
		if (!$recordedValue)
		{
			$workTimeEvent->setRecordedValue($workTimeEvent->getTimeHelper()->getUtcNowTimestamp());
		}
		$workTimeEvent->setActualTimestamp($workTimeEvent->getTimeHelper()->getUtcNowTimestamp());
		$workTimeEvent->setEventSource($eventSource);

		return $workTimeEvent;
	}

	public function setRecordId($recordId)
	{
		$this->setWorktimeRecordId($recordId);
	}
}