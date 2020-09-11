<?php
namespace Bitrix\Crm\Integration\Main;

class Site
{
	private static $portalSiteId = null;

	public static function getPortalSiteId()
	{
		if(self::$portalSiteId !== null)
		{
			return self::$portalSiteId;
		}

		$portals = \CUtil::getSitesByWizard('portal');
		if(is_array($portals) && is_array($portals[0]))
		{
			return (self::$portalSiteId = $portals[0]['LID']);
		}

		return (self::$portalSiteId = 's1');
	}
}