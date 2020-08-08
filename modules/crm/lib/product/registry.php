<?php
namespace Bitrix\Crm\Product;

use Bitrix\Main\Loader;

class Registry
{
	private static $bitrix24 = null;

	/**
	 * @return string
	 */
	public static function getCatalogClassName(): string
	{
		if (self::$bitrix24 === null)
		{
			self::$bitrix24 = Loader::includeModule('bitrix24');
		}
		return (self::$bitrix24
			? '\Bitrix\Crm\Product\B24Catalog'
			: '\Bitrix\Crm\Product\Catalog'
		);
	}
}