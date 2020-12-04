<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Comments;
use Bitrix\Tasks\Integration\Forum\Task\UserTopic;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Util\User;

/**
 * Class Comment
 *
 * @package Bitrix\Tasks\Rest\Controllers\Task
 */
class Comment extends Base
{
	/**
	 * Return all DB and UF_ fields of comment
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return  [];
	}


	/**
	 * Add comment to task
	 *
	 * @param int $taskId
	 * @param array $fields
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	public function addAction($taskId, array $fields, array $params = array())
	{
		return 1;
	}

	/**
	 * Update task comment
	 *
	 * @param int $taskId
	 * @param int $commentId
	 * @param array $fields
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function updateAction($taskId, $commentId, array $fields, array $params = array())
	{
		return false;
	}

	/**
	 * Remove existing comment
	 *
	 * @param int $taskId
	 * @param int $commentId
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function deleteAction($taskId, $commentId, array $params = array())
	{
		return false;
	}

	/**
	 * Get list all task
	 *
	 * @param array $params ORM get list params
	 *
	 * @return array
	 */
	public function listAction(array $params = array())
	{
		return [];
	}

	/**
	 * @param null $groupId
	 * @param null $userId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ArgumentTypeException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function readAllAction($groupId = null, $userId = null): bool
	{
		$currentUserId = (int)CurrentUser::get()->getId();

		$userId = ((int)$userId === $currentUserId ? 0 : (int)$userId);
		$groupId = (int)$groupId;

		if ($groupId && $userId)
		{
			return false;
		}

		if ($groupId)
		{
			return $this->readAllForGroupAction($groupId);
		}

		return $this->readAllForUserAction($userId);
	}

	/**
	 * @param $userId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ArgumentTypeException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function readAllForUserAction($userId): bool
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		$userId = ((int)$userId === $currentUserId ? 0 : (int)$userId);

		if ($userId)
		{
			$userJoin = "INNER JOIN b_tasks_member TMT ON TMT.TASK_ID = T.ID AND TMT.USER_ID = {$userId}";
			if (!User::isSuper($currentUserId) && !User::isBossRecursively($currentUserId, $userId))
			{
				$userJoin .= "\nINNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$currentUserId}";
			}
		}
		else
		{
			$userJoin = "INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$currentUserId}";
		}

		$this->onBeforeReadAll($currentUserId);
		$this->readAll($currentUserId, $userJoin);
		$this->onAfterReadAll($currentUserId);

		return true;
	}

	/**
	 * @param $groupId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ArgumentTypeException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function readAllForGroupAction($groupId): bool
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		$groupId = (int)$groupId;

		if (!$groupId)
		{
			return false;
		}

		$userJoin = "";
		$groupCondition = "AND T.GROUP_ID = {$groupId}";

		if (!Group::can($groupId, Group::ACTION_VIEW_ALL_TASKS, $currentUserId))
		{
			if (!Group::can($groupId, Group::ACTION_VIEW_OWN_TASKS, $currentUserId))
			{
				return false;
			}

			$userJoin = "INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$currentUserId}";
		}

		$this->onBeforeReadAll($currentUserId, $groupId);
		$this->readAll($currentUserId, $userJoin, $groupCondition);
		$this->onAfterReadAll($currentUserId, $groupId);

		return true;
	}

	/**
	 * @param int $userId
	 * @param int $groupId
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function onBeforeReadAll(int $userId, int $groupId = 0): void
	{

	}

	/**
	 * @param int $userId
	 * @param string $userJoin
	 * @param string $groupCondition
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public function readAll(int $userId, string $userJoin, string $groupCondition = ''): void
	{
		UserTopic::onReadAll($userId, $userJoin, $groupCondition);
		$this->runReadAllSqlRequests($userId, $userJoin, $groupCondition);
	}

	/**
	 * @param int $userId
	 * @param int $groupId
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function onAfterReadAll(int $userId, int $groupId = 0): void
	{
		Comments\Task::onAfterCommentsReadAll($userId, $groupId);

		Counter\CounterService::addEvent(
			Counter\CounterDictionary::EVENT_AFTER_COMMENTS_READ_ALL,
			[
				'USER_ID' => $userId,
				'GROUP_ID' => $groupId
			]
		);
	}

	/**
	 * @param int $currentUserId
	 * @param string $userJoin
	 * @param string $groupCondition
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	private function runReadAllSqlRequests(int $currentUserId, string $userJoin, string $groupCondition = ''): void
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$viewedDate = $sqlHelper->convertToDbDateTime(new DateTime());

		$connection->query("
			INSERT INTO b_tasks_viewed (TASK_ID, USER_ID, VIEWED_DATE)
			SELECT DISTINCT T.ID, {$currentUserId}, {$viewedDate}
			FROM b_tasks T
				{$userJoin}
				LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = {$currentUserId}
				LEFT JOIN b_forum_message FM ON FM.TOPIC_ID = T.FORUM_TOPIC_ID
			WHERE
				T.ZOMBIE = 'N'
				{$groupCondition}
				AND TV.VIEWED_DATE IS NULL
				AND FM.POST_DATE >= T.CREATED_DATE
			  	AND FM.NEW_TOPIC = 'N'
			  	AND FM.AUTHOR_ID != {$currentUserId}
		");
		$connection->query("
			UPDATE b_tasks_viewed
			SET VIEWED_DATE = {$viewedDate}
			WHERE
				USER_ID = {$currentUserId} 
				AND TASK_ID IN (
					SELECT IDS.ID
					FROM (
						SELECT DISTINCT T.ID
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