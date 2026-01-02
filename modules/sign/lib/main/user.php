<?php
namespace Bitrix\Sign\Main;

use Bitrix\Intranet\Util;

/**
 * @deprecated
 */
class User
{
	/**
	 * Returns main module's USER instance.
	 * @return \CUser
	 */
	public static function getInstance(): \CUser
	{
		return $GLOBALS['USER'];
	}

	/**
	 * Returns current user formatted name.
	 * @return string
	 */
	public static function getCurrentUserName(): string
	{
		return self::getInstance()->getFormattedName(true, false);
	}

	/**
	 * Returns true if current user is intranet.
	 * @return bool
	 */
	public static function isIntranet(): bool
	{
		static $hasAccess = null;

		if ($hasAccess !== null)
		{
			return $hasAccess;
		}

		if (!\Bitrix\Main\Loader::includeModule('intranet'))
		{
			return false;
		}

		$hasAccess = Util::isIntranetUser(self::getInstance()->getId());

		return $hasAccess;
	}
}
