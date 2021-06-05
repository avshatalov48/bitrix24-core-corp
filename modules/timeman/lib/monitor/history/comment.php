<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Entity\AddResult;
use Bitrix\Timeman\Model\Monitor\MonitorCommentTable;

class Comment
{
	public static function record(array $comments): AddResult
	{
		return MonitorCommentTable::addMulti($comments);
	}

	public static function add(int $userLogId, int $userId, string $comment): AddResult
	{
		return MonitorCommentTable::add([
			'USER_LOG_ID' => $userLogId,
			'USER_ID' => $userId,
			'COMMENT' => $comment,
		]);
	}
}