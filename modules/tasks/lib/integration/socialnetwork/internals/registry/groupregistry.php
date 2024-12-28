<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\Socialnetwork\Internals\Registry;

use Bitrix\Main\Loader;

class GroupRegistry
{
	public static function getInstance(): ?\Bitrix\Socialnetwork\Internals\Registry\GroupRegistry
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		return \Bitrix\Socialnetwork\Internals\Registry\GroupRegistry::getInstance();
	}
}