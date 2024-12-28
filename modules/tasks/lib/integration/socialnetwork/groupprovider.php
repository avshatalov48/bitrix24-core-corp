<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\SocialNetwork;

use Bitrix\Main\Loader;

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
}