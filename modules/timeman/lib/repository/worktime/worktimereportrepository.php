<?php
namespace Bitrix\Timeman\Repository\Worktime;

use Bitrix\Timeman\Model\Worktime\Report\WorktimeReport;
use Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable;

class WorktimeReportRepository
{
	/**
	 * @param $id
	 * @return WorktimeReport|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findRecordReport($id)
	{
		$res = WorktimeReportTable::query()
			->addSelect('*')
			->where('ENTRY_ID', $id)
			->where('REPORT_TYPE', WorktimeReportTable::REPORT_TYPE_RECORD_REPORT)
			->exec()
			->fetchObject();
		return $res === false ? null : $res;
	}
}