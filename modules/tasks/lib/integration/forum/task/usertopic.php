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
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Task\Status;
use CTaskItem;
use Exception;

/**
 * Class UserTopic
 *
 * @package Bitrix\Tasks\Integration\Forum\Task
 */
class UserTopic extends Forum
{
	private const STEP_LIMIT = 5000;

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
			$sqlHelper = Application::getConnection()->getSqlHelper();

			$escapedLastVisit = $sqlHelper->convertToDbDateTime($fields['LAST_VISIT']);
			$sql = $sqlHelper->getInsertIgnore(
				'b_forum_user_topic',
				' (TOPIC_ID, USER_ID, FORUM_ID, LAST_VISIT)',
				" VALUES({$primary['TOPIC_ID']}, {$primary['USER_ID']}, {$fields['FORUM_ID']}, {$escapedLastVisit})",
			);

			Application::getConnection()->query($sql);
		}
	}

	/**
	 * @param $currentUserId
	 * @param $userJoin
	 * @param string $groupCondition
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public static function onReadAll($currentUserId, $userJoin = '', $groupCondition = ''): void
	{
		if (!static::includeModule())
		{
			return;
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$forumId = Comment::getForumId();
		$currentDateTime = new DateTime();
		$lastVisit = $sqlHelper->convertToDbDateTime($currentDateTime);

		$sql = "
			SELECT
					DISTINCT T.FORUM_TOPIC_ID
				FROM b_tasks T
				INNER JOIN b_tasks_scorer TS
					ON TS.TASK_ID = T.ID
					AND TS.USER_ID = {$currentUserId}
				{$userJoin}
				WHERE 
					TS.USER_ID = {$currentUserId}      
					{$groupCondition}
					AND TS.TYPE IN (
						'".CounterDictionary::COUNTER_MY_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_ACCOMPLICES_MUTED_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_AUDITOR_MUTED_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_ORIGINATOR_MUTED_NEW_COMMENTS."',
						'".CounterDictionary::COUNTER_GROUP_COMMENTS."'
					)
		";
		$res = $connection->query($sql);

		$inserts = [];
		while ($row = $res->fetch())
		{
			$inserts[] = '(' . (int)$row['FORUM_TOPIC_ID'] . ', ' . $currentUserId . ', ' . $forumId . ', ' . $lastVisit . ')';
		}

		$chunks = array_chunk($inserts, self::STEP_LIMIT);
		unset($inserts);

		foreach ($chunks as $chunk)
		{
			$values = implode(',', $chunk);
			$values = "VALUES {$values}";
			$sql = $sqlHelper->prepareMergeSelect(
				UserTopicTable::getTableName(),
				['TOPIC_ID', 'USER_ID'],
				['TOPIC_ID', 'USER_ID', 'FORUM_ID', 'LAST_VISIT'],
				$values,
				['LAST_VISIT' => $currentDateTime]
			);
			$connection->query($sql);
		}
	}

	/**
	 * @param int $userId
	 * @param array $groupIds
	 * @param bool $closedOnly
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public static function readGroups(int $userId, array $groupIds, bool $closedOnly = false): void
	{
		if (!static::includeModule())
		{
			return;
		}

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$forumId = Comment::getForumId();
		$currentDateTime = new DateTime();
		$lastVisit = $sqlHelper->convertToDbDateTime($currentDateTime);

		$intGroupIds = array_map(function($el) {
			return (int) $el;
		}, $groupIds);

		$condition = [];
		if (count($groupIds) === 1)
		{
			$condition[] = 'T.GROUP_ID = '. array_shift($groupIds);
		}
		else
		{
			$condition[] = 'T.GROUP_ID IN ('. implode(",", $intGroupIds) .')';
		}

		if ($closedOnly)
		{
			$condition[] = 'T.STATUS = '. Status::COMPLETED;
		}

		$condition[] = 'TV.VIEWED_DATE IS NULL';
		$condition[] = 'FM.POST_DATE > T.CREATED_DATE';
		$condition[] = 'FM.NEW_TOPIC = \'N\'';

		$condition = '(' . implode(') AND (', $condition) . ')';
		$sql = "
			SELECT DISTINCT T.FORUM_TOPIC_ID as ID
			FROM b_tasks T
				LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$userId}
				LEFT JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
			WHERE
				{$condition}
		";
		$res = $connection->query($sql);

		$inserts = [];
		while ($row = $res->fetch())
		{
			$inserts[] = '(' . (int)$row['ID'] . ', ' . $userId . ', ' . $forumId . ', ' . $lastVisit . ')';
		}

		$chunks = array_chunk($inserts, self::STEP_LIMIT);
		unset($inserts);

		foreach ($chunks as $chunk)
		{
			$values = implode(',', $chunk);
			$values = "VALUES {$values}";
			$sql = $sqlHelper->prepareMergeSelect(
				UserTopicTable::getTableName(),
				['TOPIC_ID', 'USER_ID'],
				['TOPIC_ID', 'USER_ID', 'FORUM_ID', 'LAST_VISIT'],
				$values,
				['LAST_VISIT' => $currentDateTime]
			);
			$connection->query($sql);
		}
	}
}
