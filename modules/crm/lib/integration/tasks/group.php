<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;

class Group
{
	public static function isMember(int $groupId, int $userId): bool
	{
		if (!Loader::includeModule('tasks'))
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		if ($groupId <= 0)
		{
			return false;
		}

		return \Bitrix\Tasks\Integration\SocialNetwork\Group::getUserPermissionsInGroup($groupId, $userId)['UserIsMember'];
	}
}