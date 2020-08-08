<?php
namespace Bitrix\Tasks\Access\Rule\Traits;

use Bitrix\Tasks\Access\Role\RoleDictionary;

trait SubordinateTrait
{
	private function isSubordinateTask(\Bitrix\Tasks\Access\AccessibleTask $task, bool $activeOnly = true): bool
	{
		$members = $task->getMembers();

		$requiredMembers = [];
		foreach ($members as $role => $ids)
		{
			if (
				($activeOnly && in_array($role, [RoleDictionary::ROLE_DIRECTOR, RoleDictionary::ROLE_RESPONSIBLE, RoleDictionary::ROLE_ACCOMPLICE]))
				|| !$activeOnly
			)
			{
				$requiredMembers = array_merge($requiredMembers, $ids);
			}
		}

		$subordinates = $this->user->getAllSubordinates();

		return !empty(array_intersect($requiredMembers, $subordinates));
	}
}