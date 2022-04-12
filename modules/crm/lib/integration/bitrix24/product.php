<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Crm\Integration\Bitrix24;

use Bitrix\Main;

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
	public static function isCloud(): bool
	{
		return Main\Loader::includeModule('bitrix24');
	}

	/**
	 * Return true if region is Russian.
	 *
	 * @param bool $onlyRu Check only ru region.
	 * @return bool
	 */
	public static function isRegionRussian(bool $onlyRu = false): bool
	{
		$regions = $onlyRu ? ['ru'] : ['ru', 'kz', 'by'];
		$region = Main\Application::getInstance()->getLicense()->getRegion() ?: 'ru';

		return in_array($region, $regions);
	}
}