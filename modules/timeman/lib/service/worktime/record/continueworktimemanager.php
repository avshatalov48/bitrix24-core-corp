<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;

class ContinueWorktimeManager extends WorktimeManager
{
	/**
	 * @param WorktimeRecord $record
	 * @param $schedule
	 * @param $actionsList
	 * @return WorktimeRecord
	 */
	protected function updateRecordFields($record)
	{
		$record->continueWork();
		return $record;
	}

	/**
	 * @param WorktimeRecord $record
	 * @return WorktimeEvent[]
	 */
	public function buildEvents($record)
	{
		$eventForm = $this->worktimeRecordForm->getFirstEventForm();

		return [
			WorktimeEvent::create(
				$eventForm ? $eventForm->eventName : '',
				$record ? $record->getUserId() : 0,
				$record ? $record->getId() : 0,
				TimeHelper::getInstance()->getUtcNowTimestamp(),
				$eventForm ? $eventForm->reason : '',
				$this->worktimeRecordForm->device
			),
		];
	}
}