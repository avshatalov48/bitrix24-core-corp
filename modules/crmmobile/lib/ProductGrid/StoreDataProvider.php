<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Catalog\StoreTable;

final class StoreDataProvider
{
	private static ?int $defaultStoreId = null;
	private static array $stores = [];
	private static array $productToStores = [];

	public static function provideStoreData(array $productIds): array
	{
		$result = [];

		self::loadStores();
		self::loadProductToStores($productIds);

		foreach ($productIds as $productId)
		{
			$result[$productId] = [
				'STORES' => self::$productToStores[$productId] ?? [],
				'INITIAL_STORE' => self::getProductInitialStore($productId),
			];
		}

		return $result;
	}

	private static function loadStores(): void
	{
		self::$stores = [];

		$filter = ['=ACTIVE' => 'Y'];

		$accessFilter = AccessController::getCurrent()->getEntityFilter(
			ActionDictionary::ACTION_STORE_VIEW,
			StoreTable::class
		);
		if ($accessFilter)
		{
			$filter = [
				$accessFilter,
				$filter,
			];
		}

		$storeList = StoreTable::getList([
			'filter' => $filter,
			'select' => [
				'ID',
				'IS_DEFAULT',
			],
		]);

		while ($store = $storeList->fetch())
		{
			if ($store['IS_DEFAULT'] === 'Y')
			{
				self::$defaultStoreId = (int)$store['ID'];
			}

			self::$stores[$store['ID']] = $store;
		}
	}

	private static function loadProductToStores(array $productIds): void
	{
		self::$productToStores = [];

		if (empty($productIds))
		{
			return;
		}

		$filter = ['=PRODUCT_ID' => $productIds];
		$accessFilter = AccessController::getCurrent()->getEntityFilter(
			ActionDictionary::ACTION_STORE_VIEW,
			StoreProductTable::class
		);
		if ($accessFilter)
		{
			$filter = [
				$accessFilter,
				$filter,
			];
		}

		$storeProductList = StoreProductTable::getList([
			'filter' => $filter,
			'select' => [
				'AMOUNT',
				'QUANTITY_RESERVED',
				'STORE_ID',
				'PRODUCT_ID',
			],
		]);
		while ($storeProduct = $storeProductList->fetch())
		{
			$productId = $storeProduct['PRODUCT_ID'];

			self::$productToStores[$productId] ??= [];
			self::$productToStores[$productId][$storeProduct['STORE_ID']] = [
				'AMOUNT' => (float)$storeProduct['AMOUNT'],
				'QUANTITY_RESERVED' => (float)$storeProduct['QUANTITY_RESERVED'],
				'STORE_ID' => (int)$storeProduct['STORE_ID'],
			];
		}
	}

	private static function getProductInitialStore(int $productId): ?array
	{
		$storeId = null;
		if (
			!is_null(self::$defaultStoreId)
			&& isset(self::$productToStores[$productId][self::$defaultStoreId])
			&& (float)self::$productToStores[$productId][self::$defaultStoreId]['AMOUNT'] > 0
		)
		{
			$storeId = self::$defaultStoreId;
		}
		elseif (isset(self::$productToStores[$productId]))
		{
			foreach (self::$productToStores[$productId] as $productStoreId => $productStore)
			{
				if (!isset(self::$stores[$productStoreId]))
				{
					continue;
				}

				if ((float)self::$productToStores[$productId][$productStoreId]['AMOUNT'] > 0)
				{
					$storeId = (int)$productStoreId;
					break;
				}
			}
		}

		if (is_null($storeId) && !empty(self::$stores))
		{
			$storeId = (int)key(self::$stores);
		}

		if (!$storeId)
		{
			return null;
		}

		$store = StoreTable::getById($storeId)->fetch();
		if (!$store)
		{
			return null;
		}

		return [
			'ID' => (int)$store['ID'],
			'TITLE' => (string)$store['TITLE'],
		];
	}
}
