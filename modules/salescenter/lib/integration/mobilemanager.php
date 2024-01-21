<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Mobile\User;

class MobileManager extends Base
{
	public const MOBILE_APP_LINK = 'https://bitrix24.onelink.me/v3FJ/3079696c';

	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'mobile';
	}

	public function isMobileInstalledForUser(int $userId): bool
	{
		return User::isMobileInstalledForUser($userId);
	}
}
