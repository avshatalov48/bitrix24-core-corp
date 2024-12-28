<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\SocialNetwork\Collab\Provider;

use Bitrix\Main\Loader;

class CollabProvider
{
	public static function getInstance(): ?\Bitrix\Socialnetwork\Collab\Provider\CollabProvider
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		return \Bitrix\Socialnetwork\Collab\Provider\CollabProvider::getInstance();
	}
}
