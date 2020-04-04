<?
namespace Bitrix\Recyclebin;

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