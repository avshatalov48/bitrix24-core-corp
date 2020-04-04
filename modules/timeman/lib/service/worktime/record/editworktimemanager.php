<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Report\WorktimeReport;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;

class EditWorktimeManager extends WorktimeManager
{
	/**
	 * @param WorktimeRecord $record
	 * @param $schedule
	 * @param $actionsList
	 */
	protected function updateRecordFields($record)
	{
		$record->updateByForm($this->worktimeRecordForm);
		return $record;
	}

	/**
	 * @param WorktimeRecord $record
	 * @param $schedule
	 */
	public function notifyOfAction($record, $schedule)
	{
		if (!$record->isApproved())
		{
			\CTimeManNotify::sendMessage($record->getId(), false, ['SEND_NOTIFICATIONS' => false]);
		}
	}

	public function buildRecordViolations($record, $schedule, $violationRulesList = [])
	{
		if (!$schedule || !$record)
		{
			return [];
		}
		return $this->buildWorktimeViolations($record, $schedule, [
			WorktimeViolation::TYPE_EDITED_START,
			WorktimeViolation::TYPE_EDITED_BREAK_LENGTH,
			WorktimeViolation::TYPE_EDITED_ENDING,
		], $violationRulesList);
	}

	/**
	 * @param WorktimeRecord $record
	 */
	protected function setApproved($record)
	{
		$rules = [];
		if ($this->getPersonalViolationRules())
		{
			$rules[] = $this->getPersonalViolationRules();
		}
		if ($this->getSchedule())
		{
			$rules[] = $this->getSchedule()->getScheduleViolationRules();
		}
		$record->approve(empty($this->buildRecordViolations(
			$record,
			$this->getSchedule(),
			$rules
		)));
	}

	/**
	 * @return WorktimeServiceResult
	 */
	protected function verifyBeforeProcessUpdatingRecord()
	{
		$baseResult = parent::verifyBeforeProcessUpdatingRecord();
		if (!$baseResult->isSuccess())
		{
			return $baseResult;
		}
		if ($this->getSchedule())
		{
			if ((!$this->getSchedule()->isFlexible() && $this->isEmptyEventReason()))
			{
				if ($this->getRecord()->isExpired($this->getSchedule(), $this->getShift()))
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
		if (!Schedule::isScheduleFlexible($this->getSchedule()) && $this->isEmptyEventReason())
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
				$this->worktimeRecordForm->getFirstEventForm()->reason
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
				$this->worktimeRecordForm->getFirstEventForm()->reason
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
				$this->worktimeRecordForm->getFirstEventForm()->reason
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

	/**
	 * @param WorktimeRecord|null $record
	 * @return WorktimeServiceResult
	 */
	protected function verifyAfterUpdatingRecord($record)
	{
		$result = parent::verifyAfterUpdatingRecord($record);
		if (!$result->isSuccess())
		{
			return $result;
		}
		if ($record && $this->worktimeRecordForm->recordedStartSeconds)
		{
			$startTimestamp = $record->getRecordedStartTimestamp();
			if ($startTimestamp > TimeHelper::getInstance()->getUtcNowTimestamp())
			{
				return $result->addError(new Error(
						Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_START_GREATER_THAN_NOW'),
						WorktimeServiceResult::ERROR_FOR_USER)
				);
			}
		}
		return $result;
	}

	protected function checkIntersectingRecords()
	{
		return true;
	}
}