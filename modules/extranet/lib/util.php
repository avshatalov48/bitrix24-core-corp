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
		if ($authId == 'email')
		{
			return (!ModuleManager::isModuleInstalled('mail'));
		}
		elseif ($authId == 'replica')
		{
			return (!ModuleManager::isModuleInstalled('replica'));
		}
		elseif ($authId == 'bot')
		{
			return (!ModuleManager::isModuleInstalled('im'));
		}
		elseif ($authId == 'imconnector')
		{
			return (!ModuleManager::isModuleInstalled('imconnector'));
		}

		return true;
	}
}
