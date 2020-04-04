<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Crm\Integration\Bitrix24;

use Bitrix\Main\Loader;

/**
 * Class Product
 * @package Bitrix\Crm\Integration\Bitrix24
 */
class Product
{
	/**
	 * Return true if portal is cloud.
	 *
	 * @return bool
	 */
	public static function isCloud()
	{
		return Loader::includeModule('bitrix24');
	}

	/**
	 * Return true if region is Russian.
	 *
	 * @return bool
	 */
	public static function isRegionRussian()
	{
		$ruRegion = ['ru', 'kz', 'by'];
		if (self::isCloud())
		{
			return in_array(\CBitrix24::getPortalZone(), $ruRegion);
		}
		elseif (Loader::includeModule('intranet'))
		{
			return in_array(\CIntranetUtils::getPortalZone(), $ruRegion);
		}

		return true;
	}
}