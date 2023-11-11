<?php

namespace Bitrix\Tasks\Comments\Viewed;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\Forum\Task\UserTopic;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\Counter\Role;
use Bitrix\Tasks\Internals\Registry\UserRegistry;
use Bitrix\Tasks\Internals\Task\ViewedTable;

/**
 * @deprecated Use more perf service
 * @see Group
 */
class Task
{
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
	public function readAll($groupId = null, $userId = null, string $role = null): bool
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
			&& array_key_exists($role, Role::ROLE_MAP)
		)
		{
			$memberRole = Role::ROLE_MAP[$role];
		}

		if ($memberRole)
		{
			$userJoin .= " AND TM.TYPE = '". $memberRole ."'";
		}

		$this->markAsRead($currentUserId, $userJoin, $groupCondition);


		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_COMMENTS_READ_ALL,
			[
				'USER_ID' => $currentUserId,
				'GROUP_ID' => $groupId,
				'ROLE' => $memberRole
			]
		);

		PushService::addEvent($currentUserId, [
			'module_id' => 'tasks',
			'command' => PushCommand::COMMENTS_VIEWED,
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
	public function readProject($groupId = null)
	{
		$currentUserId = (int)CurrentUser::get()->getId();

		$groupId = (int)$groupId;

		if ($groupId)
		{
			// getConditionByGroupId
			$groupCondition = "AND TS.GROUP_ID = {$groupId}";
		}
		else
		{
			// getConditionByType
			$scrum = UserRegistry::getInstance($currentUserId)->getUserGroups(UserRegistry::MODE_SCRUM);
			$scrumIds = array_keys($scrum);
			$scrumIds[] = 0;
			$groupCondition = "AND TS.GROUP_ID NOT IN (". implode(',', $scrumIds) .")";
		}

		$userJoin = '';

		$this->markAsRead($currentUserId, $userJoin, $groupCondition);

		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_PROJECT_READ_ALL,
			[
				'USER_ID' => $currentUserId,
				'GROUP_ID' => $groupId
			]
		);

		PushService::addEvent($currentUserId, [
			'module_id' => 'tasks',
			'command' => PushCommand::PROJECT_COMMENTS_VIEWED,
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
	public function readScrum($groupId = null)
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

		$this->markAsRead($currentUserId, $userJoin, $groupCondition);

		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_SCRUM_READ_ALL,
			[
				'USER_ID' => $currentUserId,
				'GROUP_ID' => $groupId
			]
		);

		PushService::addEvent($currentUserId, [
			'module_id' => 'tasks',
			'command' => PushCommand::SCRUM_COMMENTS_VIEWED,
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
	private function markAsRead(int $userId, string $userJoin, string $groupCondition = ''): void
	{
		UserTopic::onReadAll($userId, $userJoin, $groupCondition);
		ViewedTable::readAll($userId, $userJoin, $groupCondition);
	}
}