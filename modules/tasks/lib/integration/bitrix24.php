<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
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

	/**
	 * Get URL for "Choose a Bitrix24 plan" page.
	 *
	 * @return string
	 */
	public static function getLicenseListPageUrl()
	{
		if (!static::includeModule())
		{
			return '';
		}

		return \CBitrix24::PATH_LICENSE_ALL;
	}

	/**
	 * Get variable value.
	 *
	 * @param $name - Name of variable
	 * @return mixed|null
	 */
	public static function getVariable($name)
	{
		if (!static::includeModule())
		{
			return null;
		}

		return Feature::getVariable($name);
	}

	/**
	 * @param array $params
	 * @return array|null
	 */
	public static function prepareStubInfo(array $params)
	{
		if (static::includeModule() && method_exists('CBitrix24', 'prepareStubInfo'))
		{
			$title = (isset($params['TITLE'])? $params['TITLE'] : '');
			$content = (isset($params['CONTENT'])? $params['CONTENT'] : '');

			$replacements = (isset($params['REPLACEMENTS']) && is_array($params['REPLACEMENTS'])
				? $params['REPLACEMENTS'] : []);

			if (!empty($replacements))
			{
				$search = array_keys($replacements);
				$replace = array_values($replacements);

				$title = str_replace($search, $replace, $title);
				$content = str_replace($search, $replace, $content);
			}

			$licenseAllButtonClass = ($params['GLOBAL_SEARCH']? 'ui-btn ui-btn-xs ui-btn-light-border' : 'success');
			$licenseDemoButtonClass = ($params['GLOBAL_SEARCH']? 'ui-btn ui-btn-xs ui-btn-light' : '');

			return \CBitrix24::prepareStubInfo(
				$title,
				$content,
				[
					['ID' => \CBitrix24::BUTTON_LICENSE_ALL, 'CLASS_NAME' => $licenseAllButtonClass],
					['ID' => \CBitrix24::BUTTON_LICENSE_DEMO, 'CLASS_NAME' => $licenseDemoButtonClass],
				]
			);
		}

		return null;
	}
}