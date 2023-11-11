<?php

namespace Bitrix\Crm\Integration\Mail;

use Bitrix\Mail\Helper\LicenseManager;
use Bitrix\Main\Loader;

class Client
{
	public static function isReadyToUse(?int $userId = null): bool
	{
		if (!(IsModuleInstalled('mail') && Loader::includeModule('mail')))
		{
			return false;
		}

		return LicenseManager::isMailClientReadyToUse($userId);
	}
}
