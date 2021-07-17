<?php
namespace Bitrix\Tasks\Comments;

use Bitrix\Forum\MessageTable;
use Bitrix\Main;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\Forum;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\TaskTable;
use COption;
use CTaskItem;

/**
 * Class Task
 *
 * @package Bitrix\Tasks\Comments
 */
class Task
{
	/**
	 * @param array $taskIds
	 * @param int $userId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getNewCommentsCountForTasks(array $taskIds, int $userId): array
	{
		if (empty($taskIds) || !Forum::includeModule())
		{
			return [];
		}

		$query = (new Query(TaskTable::class))
			->setSelect([
				'TASK_ID' => 'ID',
				new ExpressionField('CNT', 'COUNT(DISTINCT %s)', 'FM.ID'),
			])
			->registerRuntimeField('TV', new ReferenceField(
				'TV',
				ViewedTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')->where('ref.USER_ID', $userId),
				['join_type' => 'left']
			))
			->registerRuntimeField('FM', new ReferenceField(
				'FM',
				MessageTable::getEntity(),
				Join::on('this.FORUM_TOPIC_ID', 'ref.TOPIC_ID'),
				['join_type' => 'inner']
			))
			->whereIn('ID', $taskIds)
			->where(
				Query::filter()
					->logic('or')
					->where(
						Query::filter()
							->whereNotNull('TV.VIEWED_DATE')
							->whereColumn('FM.POST_DATE', '>', 'TV.VIEWED_DATE')
					)
					->where(
						Query::filter()
							->whereNull('TV.VIEWED_DATE')
							->whereColumn('FM.POST_DATE', '>=', 'CREATED_DATE')
					)
			)
			->where('FM.NEW_TOPIC', 'N')
			->where(
				Query::filter()
					->logic('or')
					->where(
						Query::filter()
							->where('FM.AUTHOR_ID', '<>', $userId)
							->where(
								Query::filter()
									->logic('or')
									->whereNull('FM.UF_TASK_COMMENT_TYPE')
									->where('FM.UF_TASK_COMMENT_TYPE', '<>', Internals\Comment::TYPE_EXPIRED)
							)
					)
					->where('FM.UF_TASK_COMMENT_TYPE', Internals\Comment::TYPE_EXPIRED_SOON)
			)
		;

		$startCounterDate = COption::GetOptionString("tasks", "tasksDropCommentCounters", null);
		if ($startCounterDate)
		{
			$query->where('FM.POST_DATE', '>', new DateTime($startCounterDate, 'Y-m-d H:i:s'));
		}

		$newComments = array_fill_keys($taskIds, 0);
		$newCommentsResult = $query->exec();
		while ($row = $newCommentsResult->fetch())
		{
			$newComments[$row['TASK_ID']] = (int)$row['CNT'];
		}

		return $newComments;
	}

	/**
	 * @param int $taskId
	 * @return false|DateTime
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getLastCommentTime(int $taskId)
	{
		if (!$taskId || !Forum::includeModule())
		{
			return false;
		}

		$query = (new Query(MessageTable::class))
			->setSelect([new ExpressionField('LAST', 'MAX(%s)', 'POST_DATE')])
			->registerRuntimeField('T', new ReferenceField(
				'T',
				TaskTable::getEntity(),
				Join::on('this.TOPIC_ID', 'ref.FORUM_TOPIC_ID'),
				['join_type' => 'inner']
			))
			->where('T.ID', $taskId)
		;

		$lastCommentTime = false;
		$lastCommentResult = $query->exec();
		if ($row = $lastCommentResult->fetch())
		{
			$lastCommentTime = ($row['LAST'] ?? $lastCommentTime);
		}

		return $lastCommentTime;
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param int $commentId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function isCommentNew(int $taskId, int $userId, int $commentId): bool
	{
		if (
			!$taskId || !$userId || !$commentId
			|| !Forum::includeModule()
		)
		{
			return false;
		}

		try
		{
			if (!(new CTaskItem($taskId, $userId))->checkCanRead())
			{
				return false;
			}
		}
		catch (\CTaskAssertException $e)
		{
			return false;
		}

		$query = (new Query(TaskTable::class))
			->setSelect(['COMMENT_ID' => 'FM.ID'])
			->registerRuntimeField('TV', new ReferenceField(
				'TV',
				ViewedTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')->where('ref.USER_ID', $userId),
				['join_type' => 'left']
			))
			->registerRuntimeField('FM', new ReferenceField(
				'FM',
				MessageTable::getEntity(),
				Join::on('this.FORUM_TOPIC_ID', 'ref.TOPIC_ID'),
				['join_type' => 'inner']
			))
			->where('ID', $taskId)
			->where('FM.ID', $commentId)
			->where(
				Query::filter()
					->logic('or')
					->where(
						Query::filter()
							->whereNotNull('TV.VIEWED_DATE')
							->whereColumn('FM.POST_DATE', '>', 'TV.VIEWED_DATE')
					)
					->where(
						Query::filter()
							->whereNull('TV.VIEWED_DATE')
							->whereColumn('FM.POST_DATE', '>=', 'CREATED_DATE')
					)
			)
			->where('FM.NEW_TOPIC', 'N')
			->where('FM.AUTHOR_ID', '<>', $userId)
			->where(
				Query::filter()
					->logic('or')
					->whereNull('FM.UF_TASK_COMMENT_TYPE')
					->where('FM.UF_TASK_COMMENT_TYPE', '<>', Internals\Comment::TYPE_EXPIRED)
			)
		;

		$startCounterDate = COption::GetOptionString("tasks", "tasksDropCommentCounters", null);
		if ($startCounterDate)
		{
			$query->where('FM.POST_DATE', '>', new DateTime($startCounterDate, 'Y-m-d H:i:s'));
		}

		$commentResult = $query->exec();

		return ($comment = $commentResult->fetch()) && (int)$comment['COMMENT_ID'] === $commentId;
	}
}