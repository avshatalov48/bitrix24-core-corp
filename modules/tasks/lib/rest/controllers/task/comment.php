<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\Forum\Task\UserTopic;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Registry\UserRegistry;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Rest\Controllers\Base;

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
	public function readAllAction($groupId = null, $userId = null, string $role = null): bool
	{
		$currentUserId = (int)CurrentUser::get()->getId();

		$groupId = (int)$groupId;

		$groupCondition = '';
		if ($groupId)
		{
			$groupCondition = "AND TS.GROUP_ID = {$groupId}";
		}

		$userJoin = "INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$currentUserId}";

		$memberRole = null;
		if (
			$role
			&& array_key_exists($role, Counter\Role::ROLE_MAP)
		)
		{
			$memberRole = Counter\Role::ROLE_MAP[$role];
		}

		if ($memberRole)
		{
			$userJoin .= " AND TM.TYPE = '". $memberRole ."'";
		}

		$this->readAll($currentUserId, $userJoin, $groupCondition);

		Counter\CounterService::addEvent(
			Counter\Event\EventDictionary::EVENT_AFTER_COMMENTS_READ_ALL,
			[
				'USER_ID' => $currentUserId,
				'GROUP_ID' => $groupId,
				'ROLE' => $memberRole
			]
		);

		PushService::addEvent($currentUserId, [
			'module_id' => 'tasks',
			'command' => 'comment_read_all',
			'params' => [
				'USER_ID' => $currentUserId,
				'GROUP_ID' => $groupId,
				'ROLE' => $role,
			]
		]);

		return true;
	}

	/**
	 * @param null $groupId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ArgumentTypeException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function readProjectAction($groupId = null)
	{
		$currentUserId = (int)CurrentUser::get()->getId();

		$groupId = (int)$groupId;

		if ($groupId)
		{
			$groupCondition = "AND TS.GROUP_ID = {$groupId}";
		}
		else
		{
			$scrum = UserRegistry::getInstance($currentUserId)->getUserGroups(UserRegistry::MODE_SCRUM);
			$scrumIds = array_keys($scrum);
			$scrumIds[] = 0;
			$groupCondition = "AND TS.GROUP_ID NOT IN (". implode(',', $scrumIds) .")";
		}

		$userJoin = '';

		$this->readAll($currentUserId, $userJoin, $groupCondition);

		Counter\CounterService::addEvent(
			Counter\Event\EventDictionary::EVENT_AFTER_PROJECT_READ_ALL,
			[
				'USER_ID' => $currentUserId,
				'GROUP_ID' => $groupId
			]
		);

		PushService::addEvent($currentUserId, [
			'module_id' => 'tasks',
			'command' => 'project_read_all',
			'params' => [
				'USER_ID' => $currentUserId,
				'GROUP_ID' => $groupId,
			]
		]);

		return true;
	}

	/**
	 * @param null $groupId
	 * @return bool
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	public function readScrumAction($groupId = null)
	{
		$currentUserId = (int)CurrentUser::get()->getId();

		$groupId = (int)$groupId;

		if ($groupId)
		{
			$groupCondition = "AND TS.GROUP_ID = {$groupId}";
		}
		else
		{
			$scrum = UserRegistry::getInstance($currentUserId)->getUserGroups(UserRegistry::MODE_SCRUM);
			$scrumIds = array_keys($scrum);
			$scrumIds[] = 0;
			$groupCondition = "AND TS.GROUP_ID IN (". implode(',', $scrumIds) .")";
		}

		$userJoin = '';

		$this->readAll($currentUserId, $userJoin, $groupCondition);

		Counter\CounterService::addEvent(
			Counter\Event\EventDictionary::EVENT_AFTER_SCRUM_READ_ALL,
			[
				'USER_ID' => $currentUserId,
				'GROUP_ID' => $groupId
			]
		);

		PushService::addEvent($currentUserId, [
			'module_id' => 'tasks',
			'command' => 'scrum_read_all',
			'params' => [
				'USER_ID' => $currentUserId,
				'GROUP_ID' => $groupId,
			]
		]);

		return true;
	}

	/**
	 * @param int $userId
	 * @param string $userJoin
	 * @param string $groupCondition
	 * @throws ArgumentTypeException
	 * @throws SqlQueryException
	 */
	private function readAll(int $userId, string $userJoin, string $groupCondition = ''): void
	{
		UserTopic::onReadAll($userId, $userJoin, $groupCondition);
		ViewedTable::readAll($userId, $userJoin, $groupCondition);
	}
}