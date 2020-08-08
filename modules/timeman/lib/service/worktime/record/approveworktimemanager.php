<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Service\Worktime\WorktimeLiveFeedManager;

class ApproveWorktimeManager extends WorktimeManager
{
	private $wasAlreadyApproved = false;

	protected function checkStartGreaterThanNow()
	{
		return true;
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

	public function onBeforeRecordSave(WorktimeRecord $record, WorktimeLiveFeedManager $liveFeedManager)
	{
		if (!$this->wasAlreadyApproved && $record->isApproved())
		{
			$liveFeedManager->stopWorkdayPostTrackingForApprover($record->getId(), $record->getApprovedBy());
		}
	}

	public function notifyOfActionOldStyle($record, $schedule)
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

	protected function checkOverlappingRecords()
	{
		return false;
	}
}