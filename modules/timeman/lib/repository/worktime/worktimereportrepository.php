<?php
namespace Bitrix\Timeman\Repository\Worktime;

use Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable;

class WorktimeReportRepository
{
	public function findRecordReport($id)
	{
		$res = WorktimeReportTable::query()
			->addSelect('*')
			->where('ENTRY_ID', $id)
			->where('REPORT_TYPE', WorktimeReportTable::REPORT_TYPE_RECORD_REPORT)
			->exec()
			->fetch();
		return $res === false ? [] : $res;
	}
}