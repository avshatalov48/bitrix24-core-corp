<?
/**
 * Class implements all further interactions with "socialnetwork" module considering "task item" entity
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks;

use \Bitrix\Main\Loader;
use \Bitrix\Main\ModuleManager;

abstract class Integration
{
	public static function isInstalled()
	{
		return ModuleManager::isModuleInstalled(static::MODULE_NAME);
	}

	public static function includeModule()
	{
		return Loader::includeModule(static::MODULE_NAME);
	}

	/**
	 * @return bool
	 * @deprecated
	 */
	public static function checkModuleInstalled()
	{
		return static::isInstalled();
	}
}