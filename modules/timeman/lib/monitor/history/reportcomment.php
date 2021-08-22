<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Model\Monitor\MonitorReportCommentTable;
use Bitrix\Timeman\Monitor\Utils\User;

class ReportComment
{
	public static function record($history)
	{
		$dateLog = new Date($history['dateLog'], 'Y-m-j');

		return MonitorReportCommentTable::add([
			'DATE_LOG' => $dateLog,
			'USER_ID' => User::getCurrentUserId(),
			'COMMENT' => $history['comment'],
			'DESKTOP_CODE' => $history['desktopCode'],
		]);
	}

	public static function add(int $userId, Date $dateLog, string $comment, string $desktopCode = null): AddResult
	{
		return MonitorReportCommentTable::add([
			'DATE_LOG' => $dateLog,
			'USER_ID' => $userId,
			'COMMENT' => $comment,
			'DESKTOP_CODE' => $desktopCode,
		]);
	}

	public static function remove(int $userId, string $dateLog, string $desktopCode): Result
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$dateLog = $sqlHelper->forSql($dateLog);
		$desktopCode = $sqlHelper->forSql($desktopCode);

		$deleteReportCommentQuery = "
			DELETE FROM b_timeman_monitor_report_comment 
			WHERE DATE_LOG = '{$dateLog}' 
			  and USER_ID = {$userId} 
			  and DESKTOP_CODE = '{$desktopCode}'
		";

		return $connection->query($deleteReportCommentQuery);
	}

	public static function getOnDate(int $userId, Date $date): array
	{
		return MonitorReportCommentTable::getList([
			'select' => [
				'TEXT' => 'COMMENT',
				'DESKTOP_CODE',
			],
			'filter' => [
				'=DATE_LOG' => $date,
				'=USER_ID' => $userId,
			]
		])->fetchAll();
	}
}