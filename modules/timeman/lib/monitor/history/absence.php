<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Entity\AddResult;
use Bitrix\Timeman\Model\Monitor\MonitorAbsenceTable;

class Absence
{
	public static function record(array $absence): AddResult
	{
		return MonitorAbsenceTable::addMulti($absence);
	}
}