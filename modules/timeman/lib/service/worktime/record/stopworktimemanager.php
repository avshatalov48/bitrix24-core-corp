<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;

class StopWorktimeManager extends WorktimeManager
{
	protected function verifyBeforeProcessUpdatingRecord()
	{
		$result = parent::verifyBeforeProcessUpdatingRecord();
		if (!$result->isSuccess() || !$this->getRecord())
		{
			return $result;
		}
		if ($this->worktimeRecordForm->isSystem)
		{
			return $result;
		}
		if ($this->isExpired() && !Schedule::isScheduleFlextime($this->getSchedule()))
		{
			if ($this->isEmptyEventReason())
			{
				return $result->addError(new Error('', WorktimeServiceResult::ERROR_EXPIRED_REASON_NEEDED));
			}
			if ($this->isEmptyTimeEnd())
			{
				return (new WorktimeServiceResult())->addError(new Error(
					Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_RECORD_EXPIRED_TIME_END_REQUIRED'),
					WorktimeServiceResult::ERROR_FOR_USER,
					['systemCode' => 'EXPIRED_TIME_END_REQUIRED']
				));
			}
		}
		return $result;
	}

	/**
	 * @param ?WorktimeRecord $record
	 * @return WorktimeRecord
	 */
	protected function updateRecordFields($record)
	{
		if (!$record)
		{
			return $record;
		}

		$record->stopWork($this->worktimeRecordForm, $this->getRecordedStopTimestamp($record));

		return $record;
	}

	/**
	 * @param WorktimeRecord $record
	 * @return array|WorktimeEvent[]
	 */
	public function buildEvents($record)
	{
		return [
			WorktimeEvent::create(
				$this->worktimeRecordForm->getFirstEventForm()->eventName,
				$record->getUserId(),
				$record->getId(),
				$record->getRecordedStopTimestamp(),
				$this->worktimeRecordForm->getFirstEventForm()->reason,
				$this->worktimeRecordForm->device
			),
		];
	}

	public function buildRecordViolations($record, $schedule)
	{
		return $this->buildWorktimeViolations($record, $schedule, [
			WorktimeViolation::TYPE_EARLY_ENDING,
			WorktimeViolation::TYPE_LATE_ENDING,
			WorktimeViolation::TYPE_MIN_DAY_DURATION,
		]);
	}

	protected function getRecordedStopTimestamp($record)
	{
		return $this->worktimeRecordForm->recordedStopTimestamp;
	}

	private function isEmptyTimeEnd()
	{
		return $this->worktimeRecordForm->recordedStopSeconds === null
			   && $this->worktimeRecordForm->recordedStopTime === null;
	}
}