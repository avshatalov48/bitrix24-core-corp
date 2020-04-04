<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;

class ApproveWorktimeManager extends WorktimeManager
{
	private $wasAlreadyApproved = false;

	protected function verifyAfterUpdatingRecord($record)
	{
		$result = parent::verifyAfterUpdatingRecord($record);
		if (!$result->isSuccess())
		{
			return $result;
		}
		if ($record && $this->worktimeRecordForm->recordedStartSeconds !== null)
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

	protected function verifyBeforeProcessUpdatingRecord()
	{
		$this->wasAlreadyApproved = $this->getRecord()->isApproved();
		return parent::verifyBeforeProcessUpdatingRecord();
	}

	/**
	 * @param WorktimeRecord $record
	 * @return WorktimeRecord
	 */
	protected function updateRecordFields($record)
	{
		if ($this->worktimeRecordForm->editedBy === null)
		{
			$this->worktimeRecordForm->editedBy = $this->worktimeRecordForm->approvedBy;
		}
		$record->updateByForm($this->worktimeRecordForm);
		$record->approve();
		return $record;
	}

	public function notifyOfAction($record, $schedule)
	{
		if ($this->wasAlreadyApproved)
		{
			return;
		}
		\CTimeManReport::approve($record->getId());
		\CTimeManReportDaily::setActive($record->getId());

		\CTimeManNotify::sendMessage($record->getId(), 'U');
	}

	/**
	 * @param WorktimeRecord $record
	 * @return mixed
	 */
	public function buildEvents($record)
	{
		return [
			WorktimeEvent::create(
				$this->worktimeRecordForm->getFirstEventForm()->eventName,
				$this->worktimeRecordForm->approvedBy,
				$record->getId()
			),
		];
	}

	protected function checkIntersectingRecords()
	{
		return true;
	}
}