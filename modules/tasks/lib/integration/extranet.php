<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration;

abstract class Extranet extends \Bitrix\Tasks\Integration
{
    const MODULE_NAME = 'extranet';

	public static function isExtranetSite($siteId = '')
	{
		$siteId = trim((string) $siteId);
		if($siteId == '')
		{
			$siteId = SITE_ID;
		}

		return static::includeModule() && \CExtranet::isExtranetSite($siteId);
	}

	public static function isConfigured()
	{
		return static::includeModule() && \CExtranet::getExtranetSiteID();
	}
}