<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Socialnetwork\Service;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Internals\Registry\GroupRegistry;

class RegistryPreloadService
{
	public function preload(array $groupIds): void
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		GroupRegistry::getInstance()->load($groupIds);
	}
}
