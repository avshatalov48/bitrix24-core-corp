<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\SocialNetwork;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Item\Workgroup\Type;

class GroupProvider
{
	public static function getInstance(): ?\Bitrix\Socialnetwork\Provider\GroupProvider
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		return \Bitrix\Socialnetwork\Provider\GroupProvider::getInstance();
	}

	public static function isCollab(int $groupId): bool
	{
		return static::getInstance()?->getGroupType($groupId) === Type::Collab;
	}

	public static function isProject(int $groupId): bool
	{
		return static::getInstance()?->getGroupType($groupId) === Type::Project;
	}
}
