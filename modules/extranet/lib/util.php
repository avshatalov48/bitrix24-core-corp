<?

namespace Bitrix\Extranet;

use \Bitrix\Main\ModuleManager;

/**
 * Class Util
 * @package Bitrix\Extranet
 */
final class Util
{
	/**
	 * Returns 'real' extranet users by EXTERNAL_AUTH_ID
	 * @param string $authId
	 * @return bool
	*/
	public static function checkExternalAuthId($authId)
	{
		return !in_array($authId, \Bitrix\Main\UserTable::getExternalUserTypes());
	}
}
