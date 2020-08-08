<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Report\WorktimeReport;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;
use Bitrix\Timeman\Service\Worktime\WorktimeLiveFeedManager;

class EditWorktimeManager extends WorktimeManager
{
	private $possibleViolationTypes = [];

	/**
	 * @param WorktimeRecord $record
	 * @param $schedule
	 * @param $actionsList
	 */
	protected function updateRecordFields($record)
	{
		$record->updateByForm($this->worktimeRecordForm);
		if ($record->isRecordedStartTimestampChanged())
		{
			$this->possibleViolationTypes[] = WorktimeViolation::TYPE_EDITED_START;
		}
		if ($record->isRecordedStopTimestampChanged())
		{
			$this->possibleViolationTypes[] = WorktimeViolation::TYPE_EDITED_ENDING;
		}
		if ($record->isRecordedBreakLengthChanged())
		{
			$this->possibleViolationTypes[] = WorktimeViolation::TYPE_EDITED_BREAK_LENGTH;
		}
		return $record;
	}

	public function onBeforeRecordSave(WorktimeRecord $record, WorktimeLiveFeedManager $liveFeedManager)
	{
		if (!$record->isApproved())
		{
			$liveFeedManager->continueWorkdayPostTrackingForApprover($record->getId(), $record->remindActualApprovedBy());
		}
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

	public function buildRecordViolations($record, $schedule)
	{
		if (!$record)
		{
			return [];
		}
		if (empty($this->possibleViolationTypes))
		{
			return [];
		}
		return $this->buildWorktimeViolations($record, $schedule, $this->possibleViolationTypes);
	}

	/**
	 * @param WorktimeRecord $record
	 */
	protected function setApproved($record)
	{
		$record->approve(empty($this->buildRecordViolations(
			$record,
			$this->getSchedule()
		)));
	}

	/**
	 * @return WorktimeServiceResult
	 */
	protected function verifyBeforeProcessUpdatingRecord()
	{
		$baseResult = parent::verifyBeforeProcessUpdatingRecord();
		if (!$baseResult->isSuccess() || $this->worktimeRecordForm->isSystem === true)
		{
			return $baseResult;
		}
		if ($this->getSchedule())
		{
			if ((!$this->getSchedule()->isFlextime() && $this->isEmptyEventReason()))
			{
				if ($this->isExpired())
				{
					$this->worktimeRecordForm->resetBreakLengthFields();
					$this->worktimeRecordForm->resetStartFields();
				}
				else
				{
					return WorktimeServiceResult::createWithErrorText('worktime editing is not allowed');
				}
			}
		}
		if (!Schedule::isScheduleFlextime($this->getSchedule()) && $this->isEmptyEventReason())
		{
			return (new WorktimeServiceResult())
				->addReasonNeededError();
		}
		return new WorktimeServiceResult();
	}

	/**
	 * @param WorktimeRecord $record
	 * @return array
	 */
	public function buildEvents($record)
	{
		$events = [];

		if ($record->isRecordedStartTimestampChanged())
		{
			$events[] = WorktimeEvent::create(
				WorktimeEventTable::EVENT_TYPE_EDIT_START,
				$this->worktimeRecordForm->editedBy,
				$record->getId(),
				$record->getRecordedStartTimestamp(),
				$this->worktimeRecordForm->getFirstEventForm()->reason,
				$this->worktimeRecordForm->device
			);
			if ($this->needToSaveCompatibleReports())
			{
				$events[] = WorktimeReport::createErrorOpenReport(
					$record->getUserId()
				);
				$events[] = WorktimeReport::createOpenReport(
					$record->getUserId(),
					$this->worktimeRecordForm->getFirstEventForm()->reason
				);
			}
		}
		if ($record->isRecordedStopTimestampChanged())
		{
			$events[] = WorktimeEvent::create(
				WorktimeEventTable::EVENT_TYPE_EDIT_STOP,
				$this->worktimeRecordForm->editedBy,
				$record->getId(),
				$record->getRecordedStopTimestamp(),
				$this->worktimeRecordForm->getFirstEventForm()->reason,
				$this->worktimeRecordForm->device
			);
			if ($this->needToSaveCompatibleReports())
			{
				$events[] = WorktimeReport::createErrorCloseReport(
					$record->getUserId()
				);
				$events[] = WorktimeReport::createCloseReport(
					$record->getUserId(),
					$this->worktimeRecordForm->getFirstEventForm()->reason
				);
			}
		}
		if ($record->isRecordedBreakLengthChanged())
		{
			$events[] = WorktimeEvent::create(
				WorktimeEventTable::EVENT_TYPE_EDIT_BREAK_LENGTH,
				$this->worktimeRecordForm->editedBy,
				$record->getId(),
				$record->getRecordedBreakLength(),
				$this->worktimeRecordForm->getFirstEventForm()->reason,
				$this->worktimeRecordForm->device
			);
			if ($this->needToSaveCompatibleReports())
			{
				$events[] = WorktimeReport::createErrorDurationReport(
					$record->getUserId()
				);
				$events[] = WorktimeReport::createDurationReport(
					$record->getUserId(),
					$this->worktimeRecordForm->getFirstEventForm()->reason
				);
			}
		}
		return $events;
	}

	protected function checkStartGreaterThanNow()
	{
		return true;
	}

	protected function checkOverlappingRecords()
	{
		return false;
	}
}