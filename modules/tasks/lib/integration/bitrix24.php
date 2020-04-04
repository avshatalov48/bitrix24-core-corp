<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Bitrix24\Feature;
use \Bitrix\Tasks\Util;

abstract class Bitrix24 extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'bitrix24';

	public static function getSettingsURL()
	{
		if(!static::includeModule())
		{
			return '';
		}

		return \CBitrix24::PATH_CONFIGS;
	}

	public static function checkToolAvailable($toolName)
	{
		if($GLOBALS['__TASKS_DEVEL_ENV__'])
		{
			return true;
		}

		if(!static::includeModule()) // box installation, say yes
		{
			return true;
		}

		return \CBitrix24BusinessTools::isToolAvailable(Util\User::getId(), $toolName);
	}

	public static function checkFeatureEnabled($featureName)
	{
		if($GLOBALS['__TASKS_DEVEL_ENV__'])
		{
			return true;
		}

		if(!static::includeModule()) // box installation, say yes
		{
			return true;
		}

		if(Feature::isFeatureEnabled($featureName)) // already payed, or trial is on = yes
		{
			return true;
		}

		return false;
	}

	public static function isLicensePaid()
	{
		if(!static::includeModule()) // box installation is like a free license in terms of bitrix24
		{
			return true;
		}

		return \CBitrix24::isLicensePaid();
	}

	public static function isLicenseShareware()
	{
		if(!static::includeModule()) // box installation is not a shareware, its like a "freeware" in terms of bitrix24
		{
			return false;
		}

		$type = \CBitrix24::getLicenseType();

		// todo: could be more custom licenses
		return $type == 'nfr' || $type == 'bis_inc' || $type == 'edu' || $type == 'startup';
	}
}