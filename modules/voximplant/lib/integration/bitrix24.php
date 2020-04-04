<?php

namespace Bitrix\Voximplant\Integration;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class Bitrix24
{
	/**
	 * Returns array of user ids of portal admins
	 * @return array
	 */
	public static function getAdmins()
	{
		if(!Loader::includeModule('bitrix24'))
			return array();

		return \CBitrix24::getAllAdminId();
	}

	public static function getLicensePrefix()
	{
		if(!Loader::includeModule('bitrix24'))
			return false;

		return \CBitrix24::getLicensePrefix();
	}

	/**
	 * Returns true if portal's email is confirmed (or there is no need to confirm it)
	 */
	public static function isEmailConfirmed()
	{
		if(!Loader::includeModule('bitrix24'))
			return true;

		return \CBitrix24::isEmailConfirmed();
	}

	/**
	 * Returns true if Bitrix24 is installed
	 * @return bool
	 */
	public static function isInstalled()
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}
}