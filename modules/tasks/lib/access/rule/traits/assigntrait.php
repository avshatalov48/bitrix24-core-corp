<?php

namespace Bitrix\Tasks\Access\Rule\Traits;

use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Main\Access\User\UserSubordinate;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\AccessibleTask;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

trait AssignTrait
{
	private static $assignCache = [];

	/**
	 * @param AccessibleTask $oldTask
	 * @param string $role
	 * @param $responsibleId
	 * @param AccessibleTask|null $newTask
	 * @return bool
	 */
	private function canAssignTask(AccessibleTask $oldTask, string $role, AccessibleTask $newTask, array $assignsFrom = null): bool
	{
		$members = $oldTask->getMembers($role);
		$groupId = $newTask->getGroupId();

		if (!$assignsFrom)
		{
			$assignsFrom = $newTask->getMembers(RoleDictionary::ROLE_DIRECTOR);
		}
		$assignsTo = $newTask->getMembers($role);

		foreach ($assignsTo as $assignTo)
		{
			foreach ($assignsFrom as $assignFrom)
			{
				$director = UserModel::createFromId((int) $assignFrom);
				if (!$this->canAssign($director, $assignTo, $members, $groupId))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param AccessibleUser $director
	 * @param int $responsibleId
	 * @param array $members
	 * @param int $groupId
	 * @return bool
	 */
	private function canAssign(AccessibleUser $director, int $responsibleId, array $members, int $groupId = 0): bool
	{
		if (!$responsibleId)
		{
			return true;
		}

		$responsible = UserModel::createFromId($responsibleId);

		// always can assign to email users
		if ($responsible->isEmail())
		{
			return true;
		}

		// can assign to himself or responsible is not changed
		if (
			$responsibleId === $director->getUserId()
			|| in_array($responsibleId, $members)
		)
		{
			return true;
		}

		if (
			$responsible->isExtranet()
			&& !$this->isMemberOfUserGroups($director->getUserId(), $responsibleId, true)
		)
		{
			return false;
		}

		// can assign task to group members
		if (
			$groupId
			&& $this->isInGroup($director->getUserId(), $groupId, $responsibleId)
		)
		{
			return true;
		}

		// extranet user can assign tasks to any member of group which contains both users
		if (
			$director->isExtranet()
			&& $this->isMemberOfUserGroups($director->getUserId(), $responsibleId, true)
		)
		{
			return true;
		}

		$relation = $director->getSubordinate($responsibleId);

		// can assign task to subordinate
		if ($relation === UserSubordinate::RELATION_SUBORDINATE)
		{
			return true;
		}

		// can assign task to department's manager
		if (
			$relation === UserSubordinate::RELATION_DIRECTOR
			&& $director->getPermission(PermissionDictionary::TASK_DEPARTMENT_MANAGER_DIRECT)
		)
		{
			return true;
		}

		// can assign task to department
		if (
			$relation === UserSubordinate::RELATION_DEPARTMENT
			&& $director->getPermission(PermissionDictionary::TASK_DEPARTMENT_DIRECT)
		)
		{
			return true;
		}

		// can assign task to non department's manager
		if (
			$relation === UserSubordinate::RELATION_OTHER_DIRECTOR
			&& $director->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_MANAGER_DIRECT)
		)
		{
			return true;
		}

		// can assign task to non department users
		if (
			$relation === UserSubordinate::RELATION_OTHER
			&& $director->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_DIRECT)
		)
		{
			return true;
		}

		return false;
	}

	private function isMemberOfUserGroups(int $userId, int $responsibleId, bool $includeInvited = false): bool
	{
		// todo: use \Bitrix\Socialnetwork\Helper\Workgroup::isUsersHaveCommonGroups
		return Group::usersHasCommonGroup($userId, $responsibleId, $includeInvited);
	}

	private function isInGroup(int $userId, int $groupId, int $responsibleId): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$key = $userId.'_'.$groupId.'_'.$responsibleId;
		if (array_key_exists($key, self::$assignCache))
		{
			return self::$assignCache[$key];
		}

		global $DB;

		$sql = '
			SELECT count(*) as cnt
			FROM b_sonet_user2group
			WHERE
				GROUP_ID = '. $groupId .'
				AND USER_ID IN ('. $userId .', '. $responsibleId .')
				AND ROLE IN (\''. implode("','", \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember()) .'\')
		';

		$res = $DB->query($sql);

		$count = 0;
		while ($row = $res->fetch())
		{
			$count = (int) $row['cnt'];
		}

		self::$assignCache[$key] = $count === 2;

		return self::$assignCache[$key];
	}
}