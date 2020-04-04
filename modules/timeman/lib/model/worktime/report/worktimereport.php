<?php
namespace Bitrix\Timeman\Model\Worktime\Report;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\Contract\WorktimeRecordIdStorable;

class WorktimeReport extends EO_WorktimeReport implements WorktimeRecordIdStorable
{
	public static function createErrorOpenReport($userId)
	{
		return (new static())
			->setActive(true)
			->setUserId($userId)
			->setReportType(WorktimeReportTable::REPORT_TYPE_ERR_OPEN)
			->setReport('TIME_CHANGE;' . TimeHelper::getInstance()->getServerIsoDate() . ';Time was changed manually');
	}

	public static function createOpenReport($userId, $report = '')
	{
		return (new static())
			->setActive(true)
			->setUserId($userId)
			->setReportType(WorktimeReportTable::REPORT_TYPE_REPORT_OPEN)
			->setReport($report);
	}

	public static function createErrorCloseReport($userId)
	{
		return (new static())
			->setActive(true)
			->setUserId($userId)
			->setReportType(WorktimeReportTable::REPORT_TYPE_ERR_CLOSE)
			->setReport('TIME_CHANGE;' . TimeHelper::getInstance()->getServerIsoDate() . ';Time was changed manually');
	}

	public static function createCloseReport($userId, $report = '')
	{
		return (new static())
			->setActive(true)
			->setUserId($userId)
			->setReportType(WorktimeReportTable::REPORT_TYPE_REPORT_CLOSE)
			->setReport($report);
	}

	public static function createErrorDurationReport($userId)
	{
		return (new static())
			->setActive(true)
			->setUserId($userId)
			->setReportType(WorktimeReportTable::REPORT_TYPE_ERR_DURATION)
			->setReport('TIME_CHANGE;' . TimeHelper::getInstance()->getServerIsoDate() . ';Time was changed manually');
	}

	public static function createDurationReport($userId, $report = '')
	{
		return (new static())
			->setActive(true)
			->setUserId($userId)
			->setReportType(WorktimeReportTable::REPORT_TYPE_REPORT_DURATION)
			->setReport($report);
	}

	/**
	 * @param $userId
	 * @param null $entryId
	 * @return WorktimeReport
	 */
	public static function createReopenReport($userId, $entryId = null)
	{
		return (new static())
			->setActive(true)
			->setEntryId($entryId)
			->setUserId($userId)
			->setReportType(WorktimeReportTable::REPORT_TYPE_REPORT_REOPEN)
			->setReport('REOPEN;' . TimeHelper::getInstance()->getServerIsoDate() . ';Entry was reopened.');
	}

	public function setRecordId($recordId)
	{
		$this->setEntryId($recordId);
	}
}