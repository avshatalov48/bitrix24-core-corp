<?php

namespace Bitrix\Mobile\Integration\Catalog;

use Bitrix\Catalog\ProductTable;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;
use CCatalogGroup;

Loader::includeModule('catalog');
Loader::includeModule('crm');
Loader::includeModule('currency');

/**
 * Facade provides some catalog defaults.
 */
final class Catalog
{
	private static $iblockId = null;

	private static $basePrice = null;

	private static $baseCurrency = null;

	public static function getDefaultId(): int
	{
		if (!self::$iblockId)
		{
			self::$iblockId = (int)\CCrmCatalog::EnsureDefaultExists();
		}
		return self::$iblockId;
	}

	public static function getBasePrice(): ?int
	{
		if (!self::$basePrice)
		{
			$baseGroup = CCatalogGroup::GetBaseGroup();
			self::$basePrice = (is_array($baseGroup) && isset($baseGroup['ID'])) ? (int)$baseGroup['ID'] : null;
		}
		return self::$basePrice;
	}

	public static function getBaseCurrency(): string
	{
		if (!self::$baseCurrency)
		{
			self::$baseCurrency = CurrencyManager::getBaseCurrency();
		}
		return self::$baseCurrency;
	}

	public static function getStoreDocumentRestrictedProductTypes(): array
	{
		return ProductTable::getStoreDocumentRestrictedProductTypes();
	}
}
