<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Worktime\Contract\WorktimeRecordIdStorable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Report\WorktimeReport;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;

class StopCustomTimeWorktimeManager extends StopWorktimeManager
{
	private $wasRecordExpired = false;

	protected function updateRecordFields($record)
	{
		if ($this->worktimeRecordForm->editedBy === null)
		{
			$this->worktimeRecordForm->editedBy = $this->worktimeRecordForm->userId;
		}
		return parent::updateRecordFields($record);
	}

	protected function verifyBeforeProcessUpdatingRecord()
	{
		$baseResult = parent::verifyBeforeProcessUpdatingRecord();
		if (!$baseResult->isSuccess() || $this->worktimeRecordForm->isSystem === true)
		{
			return $baseResult;
		}
		if (!Schedule::isScheduleFlexible($this->getSchedule()) && $this->isEmptyEventReason())
		{
			return (new WorktimeServiceResult())
				->addReasonNeededError();
		}
		$this->wasRecordExpired = $this->getRecord()->isExpired($this->getSchedule(), $this->getShift());
		return new WorktimeServiceResult();
	}

	/**
	 * @param WorktimeRecord $record
	 * @param $schedule
	 */
	public function notifyOfAction($record, $schedule)
	{
		if (!$record->isApproved())
		{
			$sendOldWay = false;
			if ($this->wasRecordExpired)
			{
				$violations = $this->buildEditedViolations($record);
				$sendOldWay = empty($violations);
			}
			$params = ['SEND_NOTIFICATIONS' => $sendOldWay];
			if ($this->worktimeRecordForm->editedBy > 0)
			{
				$params['SEND_FROM_USER_ID'] = $this->worktimeRecordForm->editedBy;
			}
			\CTimeManNotify::sendMessage($record->getId(), false, $params);
		}
	}

	public function buildRecordViolations($record, $schedule, $violationRulesList = [])
	{
		return $this->buildWorktimeViolations($record, $schedule, [
			WorktimeViolation::TYPE_EARLY_ENDING,
			WorktimeViolation::TYPE_LATE_ENDING,
			WorktimeViolation::TYPE_MIN_DAY_DURATION,
			WorktimeViolation::TYPE_EDITED_ENDING,
		], $violationRulesList);
	}

	/**
	 * @param WorktimeRecord $record
	 */
	protected function setApproved($record)
	{
		if (!$this->getSchedule() || $this->getSchedule()->isFlexible())
		{
			return;
		}
		$violations = $this->buildEditedViolations($record);

		if (!empty($violations) || $this->wasRecordExpired)
		{
			$record->approve(false);
		}
	}

	/**
	 * @param WorktimeRecord $record
	 * @return array|WorktimeRecordIdStorable[]
	 */
	public function buildEvents($record)
	{
		$events = parent::buildEvents($record);
		if ($this->needToSaveCompatibleReports())
		{
			$violations = $this->buildEditedViolations($record);
			if (!empty($violations))
			{
				$events[] = WorktimeReport::createErrorCloseReport(
					$record->getUserId()
				);
			}

			$events[] = WorktimeReport::createCloseReport(
				$record->getUserId(),
				$this->worktimeRecordForm->getFirstEventForm()->reason
			);
		}
		return $events;
	}

	/**
	 * @param WorktimeRecord $record
	 * @return int|void
	 */
	protected function getRecordedStopTimestamp($record)
	{
		if ($record)
		{
			return $record->buildStopTimestampBySecondsAndDate(
				$this->worktimeRecordForm->recordedStopSeconds,
				$this->worktimeRecordForm->recordedStopDateFormatted,
				$this->worktimeRecordForm->editedBy
			);
		}
		return parent::getRecordedStopTimestamp($record);
	}

	protected function checkIntersectingRecords()
	{
		return true;
	}

	/**
	 * @param WorktimeRecord $record
	 * @return array|WorktimeViolation[]
	 */
	private function buildEditedViolations($record)
	{
		if (!$this->getSchedule())
		{
			return [];
		}
		$rules = [$this->getSchedule()->getScheduleViolationRules()];
		if ($this->getPersonalViolationRules())
		{
			$rules[] = $this->getPersonalViolationRules();
		}

		return $this->buildWorktimeViolations(
			$record,
			$this->getSchedule(),
			[WorktimeViolation::TYPE_EDITED_ENDING,],
			$rules
		);
	}
}