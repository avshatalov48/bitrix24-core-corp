<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;
use Bitrix\Mobile\InventoryControl;
use CCatalogGroup;

Loader::includeModule('catalog');
Loader::includeModule('crm');
Loader::includeModule('currency');

final class Catalog
{
	private static $iblockId = null;

	private static $basePrice = null;

	private static $baseCurrency = null;

	public static function load(): array
	{
		$result = [
			'id' => self::getDefaultId(),
			'base_price_id' => self::getBasePrice(),
			'currency_id' => self::getBaseCurrency(),
			'url' => [
				'create_product' => InventoryControl\UrlBuilder::getProductDetailUrl(0),
			],
		];

		return $result;
	}

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
}
