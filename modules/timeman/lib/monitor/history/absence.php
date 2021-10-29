<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Type\DateTime;
use Bitrix\Timeman\Model\Monitor\MonitorAbsenceTable;

class Absence
{
	public static function record(array $absence): void
	{
		foreach ($absence as $absenceItem)
		{
			self::add($absenceItem['USER_LOG_ID'], $absenceItem['TIME_START'], $absenceItem['TIME_FINISH']);
		}
	}

	public static function add(int $userLogId, DateTime $timeStart, DateTime $timeFinish = null): AddResult
	{
		return MonitorAbsenceTable::add([
			'USER_LOG_ID' => $userLogId,
			'TIME_START' => $timeStart,
			'TIME_FINISH' => $timeFinish,
		]);
	}

	public static function remove(int $userId, string $dateLog, string $desktopCode): Result
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$dateLog = $sqlHelper->forSql($dateLog);
		$desktopCode = $sqlHelper->forSql($desktopCode);

		$deleteAbsenceQuery = "
			DELETE tma FROM b_timeman_monitor_absence tma WHERE tma.USER_LOG_ID IN (
				SELECT ID
				FROM b_timeman_monitor_user_log
				WHERE DATE_LOG = '{$dateLog}'
					and USER_ID = {$userId}
					and DESKTOP_CODE = '{$desktopCode}'
			);
		";

		return $connection->query($deleteAbsenceQuery);
	}
}