<?php

namespace Bitrix\Tasks\Integration\Socialnetwork\Space;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Socialnetwork\Space\Service;

class SpaceService
{
	/**
	 * @throws LoaderException
	 */
	public static function isAvailable(bool $isPublic = false): bool
	{
		if (
			!class_exists(Service::class)
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return false;
		}

		return Service::isAvailable($isPublic);
	}
}
