<?php

namespace Bitrix\Disk\Integration\Socialnetwork;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Socialnetwork\Space\Service;

class SpaceService
{
	/**
	 * @throws LoaderException
	 */
	public static function isAvailable(): bool
	{
		if (
			!class_exists(Service::class)
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return false;
		}

		return Service::isAvailable();
	}
}
