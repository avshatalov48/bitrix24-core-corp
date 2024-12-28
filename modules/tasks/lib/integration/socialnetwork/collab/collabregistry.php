<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\SocialNetwork\Collab;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\Registry;

class CollabRegistry
{
	public static function getInstance(): ?\Bitrix\Socialnetwork\Collab\Registry\CollabRegistry
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		return Registry\CollabRegistry::getInstance();
	}
}
