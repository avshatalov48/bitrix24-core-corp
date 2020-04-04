<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
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
		if (!$baseResult->isSuccess())
		{
			return $baseResult;
		}
		if ($this->getSchedule() &&
			!Schedule::isScheduleFlexible($this->getSchedule()) && $this->isEmptyEventReason())
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

	/**
	 * @param $record
	 * @param $schedule
	 * @param $violationRulesList
	 * @return array
	 */
	public function buildRecordViolations($record, $schedule, $violationRulesList = [])
	{
		return $this->buildWorktimeViolations($record, $schedule, [
			WorktimeViolation::TYPE_LATE_START,
			WorktimeViolation::TYPE_EARLY_START,
			WorktimeViolation::TYPE_EDITED_START,
		], $violationRulesList);
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

	protected function checkIntersectingRecords()
	{
		return true;
	}

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
			[WorktimeViolation::TYPE_EDITED_START,],
			$rules
		);
	}
}