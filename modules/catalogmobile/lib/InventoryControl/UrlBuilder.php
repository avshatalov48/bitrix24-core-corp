<?php

namespace Bitrix\CatalogMobile\InventoryControl;

use Bitrix\Catalog\Url\InventoryBuilder;
use Bitrix\Main\Loader;

Loader::includeModule('crm');
Loader::includeModule('catalog');

/**
 * @method static getProductDetailUrl(int $productId): string
 */
class UrlBuilder
{
	private static $instance = null;

	public static function __callStatic($name, $arguments)
	{
		if (self::$instance === null)
		{
			self::$instance = new InventoryBuilder();
			self::$instance->setIblockId(self::getCatalogId());
		}

		return call_user_func_array([self::$instance, $name], $arguments);
	}

	private static function getCatalogId(): int
	{
		$iblockId = \CCrmCatalog::EnsureDefaultExists();
		return (int)$iblockId;
	}
}
