<?
/**
 * Class implements all further interactions with "bitrix24" module
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Bitrix24;

final class User extends \Bitrix\Tasks\Integration\Bitrix24
{
	public static function isAdmin($userId = 0)
	{
		if(!static::includeModule())
		{
			return false;
		}

		if(!$userId)
		{
			$userId = \Bitrix\Tasks\Util\User::getId();
		}

		if($userId)
		{
			static $cache = array();

			if(!isset($cache[$userId]))
			{
				$cache[$userId] = (boolean) \CBitrix24::isPortalAdmin($userId);
			}

			return $cache[$userId];
		}

		return false;
	}
}