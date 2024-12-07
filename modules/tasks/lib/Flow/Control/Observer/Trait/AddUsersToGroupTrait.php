<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Trait;

use Bitrix\Main\Loader;

trait AddUsersToGroupTrait
{
	protected function addUsersToGroup(int $groupId, array $responsibleUsers): void
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$users = \CSocNetUserToGroup::GetGroupUsers($groupId);
		$userIds = array_map('intval', array_column($users, 'USER_ID'));
		$responsibleUsers = array_map('intval', $responsibleUsers);
		$usersToAdd = array_diff($responsibleUsers, $userIds);

		\CSocNetUserToGroup::AddUsersToGroup($groupId, $usersToAdd);
	}
}
