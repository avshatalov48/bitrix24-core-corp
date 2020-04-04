<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Bitrix24;

use \Bitrix\Tasks\Util;

final class UI extends \Bitrix\Tasks\Integration\Bitrix24
{
	/**
	 * Includes js extension for displaying restriction popup. Dont even try to include it twice at the page, especially with different arguments :)
	 *
	 * @param string $group If feature refers to the "extended tasks", dont pass this parameter. If this is a stand-alone feature, pass empty string here
	 * @return bool
	 */
	public static function initLicensePopup($group = 'tasks')
	{
		if(!static::includeModule())
		{
			return false;
		}

		static $initialized; // you cannot put that on the page twice, despite on having $group argument changed
		if($initialized)
		{
			return true;
		}
		$initialized = true;

		\CBitrix24::initLicenseInfoPopupJS($group);
		return true;
	}

	public static function getLicenseUrl()
	{
		if(!static::includeModule())
		{
			return '';
		}

		return \CBitrix24::PATH_LICENSE_ALL;
	}
}