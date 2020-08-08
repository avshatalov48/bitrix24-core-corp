<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Timeman\Model\Worktime\Contract\WorktimeRecordIdStorable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Report\WorktimeReport;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;

class StartCustomTimeWorktimeManager extends StartWorktimeManager
{
	protected function verifyBeforeProcessUpdatingRecord()
	{
		$baseResult = parent::verifyBeforeProcessUpdatingRecord();
		if (!$baseResult->isSuccess() || $this->worktimeRecordForm->isSystem === true)
		{
			return $baseResult;
		}
		if ($this->getSchedule() && !$this->getSchedule()->isFlextime() && $this->isEmptyEventReason())
		{
			return (new WorktimeServiceResult())
				->addReasonNeededError();
		}
		return new WorktimeServiceResult();
	}

	protected function updateRecordFields($record)
	{
		if ($this->worktimeRecordForm->editedBy === null)
		{
			$this->worktimeRecordForm->editedBy = $this->worktimeRecordForm->userId;
		}
		return parent::updateRecordFields($record);
	}

	protected function checkStartGreaterThanNow()
	{
		return true;
	}

	/**
	 * @param WorktimeRecord $record
	 * @param $schedule
	 */
	public function notifyOfActionOldStyle($record, $schedule)
	{
		if (!$record->isApproved())
		{
			\CTimeManNotify::sendMessage($record->getId(), false, ['SEND_NOTIFICATIONS' => false]);
		}
	}

	/**
	 * @param $record
	 * @param $schedule
	 * @param $violationRulesList
	 * @return array
	 */
	public function buildRecordViolations($record, $schedule)
	{
		return $this->buildWorktimeViolations($record, $schedule, [
			WorktimeViolation::TYPE_LATE_START,
			WorktimeViolation::TYPE_EARLY_START,
			WorktimeViolation::TYPE_EDITED_START,
		]);
	}

	/**
	 * @param WorktimeRecord $record
	 */
	protected function setApproved($record)
	{
		$violations = $this->buildEditedViolations($record);
		$record->approve(empty($violations));
	}

	/**
	 * @param WorktimeRecord $record
	 * @return WorktimeRecordIdStorable[]
	 */
	public function buildEvents($record)
	{
		$events = parent::buildEvents($record);
		if ($this->needToSaveCompatibleReports())
		{
			$violations = $this->buildEditedViolations($record);
			if (!empty($violations))
			{
				$events[] = WorktimeReport::createErrorOpenReport(
					$record->getUserId()
				);
			}
			$events[] = WorktimeReport::createOpenReport(
				$record->getUserId(),
				$this->worktimeRecordForm->getFirstEventForm()->reason
			);
		}
		return $events;
	}

	protected function checkOverlappingRecords()
	{
		return true;
	}

	private function buildEditedViolations($record)
	{
		return $this->buildWorktimeViolations(
			$record,
			$this->getSchedule(),
			[WorktimeViolation::TYPE_EDITED_START,]
		);
	}
}