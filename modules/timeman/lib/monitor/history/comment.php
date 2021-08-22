<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Timeman\Model\Monitor\MonitorCommentTable;

class Comment
{
	public static function record(array $comments): void
	{
		foreach ($comments as $comment)
		{
			self::add($comment['USER_LOG_ID'], $comment['USER_ID'], $comment['COMMENT']);
		}
	}

	public static function add(int $userLogId, int $userId, string $comment): AddResult
	{
		return MonitorCommentTable::add([
			'USER_LOG_ID' => $userLogId,
			'USER_ID' => $userId,
			'COMMENT' => $comment,
		]);
	}

	public static function remove(int $userId, string $dateLog, string $desktopCode): Result
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$dateLog = $sqlHelper->forSql($dateLog);
		$desktopCode = $sqlHelper->forSql($desktopCode);

		$deleteCommentsQuery = "
			DELETE FROM b_timeman_monitor_comment WHERE USER_LOG_ID IN (
				SELECT ID
				FROM b_timeman_monitor_user_log
				WHERE DATE_LOG = '{$dateLog}' 
				  and USER_ID = {$userId} 
				  and DESKTOP_CODE = '{$desktopCode}'
			);
		";

		return $connection->query($deleteCommentsQuery);
	}
}