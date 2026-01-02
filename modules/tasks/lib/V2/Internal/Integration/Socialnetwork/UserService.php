<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Socialnetwork;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Internals\Registry\UserRegistry;

class UserService
{
	public function getGroups(int $userId): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		return UserRegistry::getInstance($userId)->getUserGroups(UserRegistry::MODE_GROUP);
	}

	public function getProjects(int $userId): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		return UserRegistry::getInstance($userId)->getUserGroups(UserRegistry::MODE_PROJECT);
	}

	public function getCollabs(int $userId): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		return UserRegistry::getInstance($userId)->getUserGroups(UserRegistry::MODE_COLLAB);
	}

	public function getScrum(int $userId): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		return UserRegistry::getInstance($userId)->getUserGroups(UserRegistry::MODE_SCRUM);
	}
}