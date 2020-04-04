<?

namespace Bitrix\Recyclebin\Integration\Bitrix24;

final class User extends \Bitrix\Recyclebin\Integration\Bitrix24
{
	public static function isAdmin($userId = 0)
	{
		if(!static::includeModule())
		{
			return false;
		}

		if(!$userId)
		{
			$userId = \Bitrix\Recyclebin\Internals\User::getCurrentUserId();
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