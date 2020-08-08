<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;

class StartWorktimeManager extends WorktimeManager
{
	/**
	 * @param WorktimeRecord|null $record
	 * @return WorktimeRecord
	 */
	protected function updateRecordFields($record)
	{
		if ($record)
		{
			return $record;
		}

		if ($this->getSchedule())
		{
			$this->worktimeRecordForm->initScheduleId($this->getSchedule()->getId());
		}
		if ($this->getShift())
		{
			$this->worktimeRecordForm->initShiftId($this->getShift()->getId());
		}
		return WorktimeRecord::startWork(
			$this->worktimeRecordForm
		);
	}

	/**
	 * @param WorktimeRecord $record
	 * @return WorktimeEvent[]
	 */
	public function buildEvents($record)
	{
		return [
			WorktimeEvent::create(
				$this->worktimeRecordForm->getFirstEventForm()->eventName,
				$record->getUserId(),
				$record->getId(),
				$record->getRecordedStartTimestamp(),
				$this->worktimeRecordForm->getFirstEventForm()->reason,
				$this->worktimeRecordForm->device
			),
		];
	}

	public function buildRecordViolations($record, $schedule)
	{
		return $this->buildWorktimeViolations($record, $schedule, [
			WorktimeViolation::TYPE_LATE_START,
			WorktimeViolation::TYPE_EARLY_START,
			WorktimeViolation::TYPE_SHIFT_LATE_START,
		]);
	}
}