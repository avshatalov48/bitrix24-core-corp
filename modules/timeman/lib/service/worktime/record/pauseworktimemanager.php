<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

class PauseWorktimeManager extends WorktimeManager
{
	/**
	 * @param \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord $record
	 * @return \Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord
	 */
	protected function updateRecordFields($record)
	{
		$record->pauseWork($this->worktimeRecordForm);
		return $record;
	}
}