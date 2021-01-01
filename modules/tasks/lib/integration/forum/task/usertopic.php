<?php
namespace Bitrix\Tasks\Integration\Forum\Task;

use Bitrix\Forum\UserTopicTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\Forum;
use CTaskItem;
use Exception;

/**
 * Class UserTopic
 *
 * @package Bitrix\Tasks\Integration\Forum\Task
 */
class UserTopic extends Forum
{
	/**
	 * @param int $taskId
	 * @param int $userId
	 * @param DateTime|null $lastVisit
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public static function updateLastVisit(int $taskId, int $userId, DateTime $lastVisit = null): void
	{
		$task = CTaskItem::getInstance($taskId, $userId);

		if (!static::includeModule() || !$task->checkCanRead())
		{
			return;
		}

		$lastVisit = ($lastVisit ?? new DateTime());
		try
		{
			$taskData = $task->getData(false, [], false);
		}
		catch (\TasksException $e)
		{
			return;
		}

		if (!$taskData['FORUM_TOPIC_ID'])
		{
			return;
		}

		$primary = [
			'TOPIC_ID' => $taskData['FORUM_TOPIC_ID'],
			'USER_ID' => $userId
		];
		$fields = [
			'FORUM_ID' => Comment::getForumId(),
			'LAST_VISIT' => $lastVisit
		];

		if (UserTopicTable::getById($primary)->fetch())
		{
			UserTopicTable::update($primary, $fields);
		}
		else
		{
			UserTopicTable::add($primary + $fields);
		}
	}

	/**
	 * @param $currentUserId
	 * @param $userJoin
	 * @param string $groupCondition
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public static function onReadAll($currentUserId, $userJoin, $groupCondition = ''): void
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$forumId = Comment::getForumId();
		$lastVisit = $sqlHelper->convertToDbDateTime(new DateTime());

		$connection->query("
			INSERT IGNORE INTO b_forum_user_topic (TOPIC_ID, USER_ID, FORUM_ID, LAST_VISIT)
			SELECT DISTINCT T.FORUM_TOPIC_ID, {$currentUserId}, {$forumId}, {$lastVisit}
			FROM b_tasks T
				{$userJoin}
				LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$currentUserId}
				LEFT JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
			WHERE
				T.ZOMBIE = 'N'
				{$groupCondition}
				AND TV.VIEWED_DATE IS NULL
				AND FM.POST_DATE > T.CREATED_DATE
			  	AND FM.NEW_TOPIC = 'N'
			  	AND FM.AUTHOR_ID != {$currentUserId}
		");
		$connection->query("
			UPDATE b_forum_user_topic
			SET LAST_VISIT = {$lastVisit}
			WHERE
				FORUM_ID = {$forumId}
				AND USER_ID = {$currentUserId} 
				AND TOPIC_ID IN (
					SELECT IDS.FORUM_TOPIC_ID
					FROM (
						SELECT DISTINCT T.FORUM_TOPIC_ID
						FROM b_tasks T
							{$userJoin}
							INNER JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$currentUserId}
							LEFT JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
						WHERE
							T.ZOMBIE = 'N'
							{$groupCondition}
							AND FM.POST_DATE > TV.VIEWED_DATE
							AND FM.NEW_TOPIC = 'N'
							AND FM.AUTHOR_ID != {$currentUserId}
					) IDS
				)
		");
	}
}