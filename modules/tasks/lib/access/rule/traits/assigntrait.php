<?php

namespace Bitrix\Tasks\Access\Rule\Traits;

use Bitrix\Main\Access\User\UserSubordinate;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\AccessibleTask;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;

trait AssignTrait
{
	private function canAssignTask(AccessibleTask $oldTask, string $role, $responsibleId, AccessibleTask $newTask = null): bool
	{
		// always allowed assign tasks to email users
		if (filter_var($responsibleId, FILTER_VALIDATE_EMAIL))
		{
			return true;
		}

		$responsibleId = (int) $responsibleId;

		$members = $oldTask->getMembers($role);

		// can assign to himself or responsible is not changed
		if (
			$responsibleId === $this->user->getUserId()
			|| in_array($responsibleId, $members)
		)
		{
			return true;
		}

		// can assign task to group members
		$groupId = $newTask ? $newTask->getGroupId() : 0;
		if (
			$groupId
			&& $this->isInGroup($groupId, $responsibleId)
		)
		{
			return true;
		}

		// extranet user can assign tasks to any member of group which contains both users
		if (
			\Bitrix\Tasks\Integration\Extranet\User::isExtranet($this->user->getUserId())
			&& $this->isMemberOfUserGroups($responsibleId)
		)
		{
			return true;
		}

		$relation = $this->user->getSubordinate($responsibleId);

		// can assign task to subordinate
		if ($relation === UserSubordinate::RELATION_SUBORDINATE)
		{
			return true;
		}

		// can assign task to department's manager
		if (
			$relation === UserSubordinate::RELATION_DIRECTOR
			&& $this->user->getPermission(PermissionDictionary::TASK_DEPARTMENT_MANAGER_DIRECT)
		)
		{
			return true;
		}

		// can assign task to department
		if (
			$relation === UserSubordinate::RELATION_DEPARTMENT
			&& $this->user->getPermission(PermissionDictionary::TASK_DEPARTMENT_DIRECT)
		)
		{
			return true;
		}

		// can assign task to non department's manager
		if (
			$relation === UserSubordinate::RELATION_OTHER_DIRECTOR
			&& $this->user->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_MANAGER_DIRECT)
		)
		{
			return true;
		}

		// can assign task to non department users
		if (
			$relation === UserSubordinate::RELATION_OTHER
			&& $this->user->getPermission(PermissionDictionary::TASK_NON_DEPARTMENT_DIRECT)
		)
		{
			return true;
		}

		return false;
	}

	private function isMemberOfUserGroups(int $responsibleId): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		global $DB;

		$sql = '
			SELECT count(*) as cnt
			FROM b_sonet_user2group ug
			INNER JOIN b_sonet_user2group ug2
				ON ug.GROUP_ID = ug2.GROUP_ID 
				AND ug2.USER_ID = '. $responsibleId .'
				AND ug2.ROLE IN ("'. implode('","', \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember()) .'")
			WHERE 
				ug.USER_ID = '. $this->user->getUserId() .'
				AND ug.ROLE IN ("'. implode('","', \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember()) .'")
		';

		$res = $DB->query($sql);
		$row = $res->fetch();
		if ($row && (int) $row['cnt'] > 0)
		{
			return true;
		}

		return false;
	}

	private function isInGroup(int $groupId, int $responsibleId): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		global $DB;

		$sql = '
			SELECT count(*) as cnt
			FROM b_sonet_user2group
			WHERE
				GROUP_ID = '. $groupId .'
				AND USER_ID IN ('. $this->user->getUserId() .', '. $responsibleId .')
				AND ROLE IN ("'. implode('","', \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember()) .'")
		';

		$res = $DB->query($sql);

		$count = 0;
		while ($row = $res->fetch())
		{
			$count = (int) $row['cnt'];
		}

		return $count === 2;
	}
}