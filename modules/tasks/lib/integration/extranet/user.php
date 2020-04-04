<?
/**
 * Class implements all further interactions with "extranet" module
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Extranet;

final class User extends \Bitrix\Tasks\Integration\Extranet
{
	protected static $cache = array();

	public static function getAccessible($userId)
	{
		if(!static::includeModule())
		{
			return array();
		}

		return \CExtranet::getMyGroupsUsersSimple(\CExtranet::getExtranetSiteID(), array('userId' => $userId));
	}

	public static function clear()
	{
		static::$cache = array();
	}

	public static function isExtranet($user = 0)
	{
		if(!static::isConfigured())
		{
			return false; // no extranet - no problem, user is NOT AN EXTRANET USER
		}

		if(is_array($user) && !empty($user))
		{
			$result = !(isset($user["UF_DEPARTMENT"]) && isset($user["UF_DEPARTMENT"][0]) && $user["UF_DEPARTMENT"][0] > 0);
		}
		else
		{
			if(!$user)
			{
				$user = \Bitrix\Tasks\Util\User::getId(); // check current
			}

			if(array_key_exists($user, static::$cache))
			{
				return static::$cache[$user];
			}

			$result = false;

			$user = intval($user);
			if($user)
			{
				$result = !\CExtranet::IsIntranetUser(SITE_ID, $user);
			}

			static::$cache[$user] = $result;
		}

		return $result;
	}
}