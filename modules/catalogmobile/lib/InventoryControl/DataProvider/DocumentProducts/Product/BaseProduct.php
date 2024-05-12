<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Random;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\StoreProductTable;

Loader::includeModule('catalog');

abstract class BaseProduct
{
	abstract static function load(int $documentId): array;

	protected static function getProductStoreInfo(array $productIds): array
	{
		if (empty($productIds))
		{
			return [];
		}

		$restrictedProductTypes = ProductTable::getStoreDocumentRestrictedProductTypes();

		$productStoreInfo = [];
		$productStoreRaw = StoreProductTable::getList([
			'filter' => [
				'=PRODUCT_ID' => $productIds,
				'!=PRODUCT.TYPE' => $restrictedProductTypes,
			],
			'select' => [
				'STORE_ID',
				'PRODUCT_ID',
				'AMOUNT',
				'QUANTITY_RESERVED',
				'STORE_TITLE' => 'STORE.TITLE',
				'PRODUCT.TYPE',
			]
		]);

		while ($productStore = $productStoreRaw->Fetch())
		{
			$productStoreInfo[$productStore['PRODUCT_ID']] = $productStoreInfo[$productStore['PRODUCT_ID']] ?? [];
			$productStoreInfo[$productStore['PRODUCT_ID']][$productStore['STORE_ID']] = $productStore;
		}

		return $productStoreInfo;
	}

	protected static function getAvailableProductAmountOnStore(array $productStoreInfo, int $productId, int $storeId): float
	{
		$amount = 0.0;

		if (
			isset(
				$productStoreInfo[$productId][$storeId]['AMOUNT'],
				$productStoreInfo[$productId][$storeId]['QUANTITY_RESERVED']
			)
		)
		{
			$amount =
				$productStoreInfo[$productId][$storeId]['AMOUNT']
				- $productStoreInfo[$productId][$storeId]['QUANTITY_RESERVED']
			;
		}

		return $amount;
	}

	protected static function hasStoreAccess(int $storeId): bool
	{
		return AccessController::getCurrent()->checkByValue(
			ActionDictionary::ACTION_STORE_VIEW,
			(string)$storeId
		);
	}

	public static function loadProductModel(int $productId, ?int $documentId = null, ?string $documentType = null): DocumentProductRecord
	{
		$storeFromAmount = 0;
		$storeFromAvailableAmount = 0;
		$storeToAmount = 0;
		$storeToAvailableAmount = 0;

		$productStoreInfo = self::getProductStoreInfo([$productId]);
		$defaultStoreId = AccessController::getCurrent()->getAllowedDefaultStoreId();
		$filledStoreId = null;
		if(isset($productStoreInfo[$productId]))
		{
			$filledStores = array_filter($productStoreInfo[$productId], static function($element) {
				return (int)$element['AMOUNT'] > 0;
			});
			$filledStore = $filledStores ? current($filledStores) : null;
			$filledStoreId = (int)$filledStore['STORE_ID'];
		}
		$storeFromId = $filledStoreId ?? $defaultStoreId;
		$storeToId = $defaultStoreId;
		if ($storeFromId)
		{
			$storeFromAvailableAmount = self::getAvailableProductAmountOnStore(
				$productStoreInfo,
				$productId,
				$storeFromId,
			);
			$storeFromAmount = (float)($productStoreInfo[$productId][$storeFromId]['AMOUNT'] ?? 0);
		}
		if ($storeToId)
		{
			$storeToAvailableAmount = self::getAvailableProductAmountOnStore(
				$productStoreInfo,
				$productId,
				$storeToId,
			);
			$storeToAmount = (float)($productStoreInfo[$productId][$storeToId]['AMOUNT'] ?? 0);
		}

		$record = DocumentProductRecord::make([
			'id' => 'unsaved_' . Random::getString(8) . '_' . $productId,
			'documentId' => $documentId,
			'productId' => $productId,
			'documentType' => $documentType,
			'amount' => $documentType === StoreDocumentTable::TYPE_SALES_ORDERS ? 1.0 : 0.0,
			'storeFromId' => $storeFromId,
			'storeFromAmount' => $storeFromAmount,
			'storeFromAvailableAmount' => $storeFromAvailableAmount,
			'storeToId' => $storeToId,
			'storeToAmount' => $storeToAmount,
			'storeToAvailableAmount' => $storeToAvailableAmount,
		]);

		$records = [$record];

		$records = self::enrich($records, [
			new CompletePrices(),
			new CompleteSku(),
			new CompleteSections(),
			new CompleteStores(),
			new CompleteBarcodes(),
		]);

		return $records[0];
	}

	/**
	 * @param DocumentProductRecord[] $records
	 * @param Enricher[] $enrichers
	 */
	protected static function enrich(array $records, array $enrichers)
	{
		foreach ($enrichers as $enricher)
		{
			$records = $enricher->enrich($records);
		}

		return $records;
	}
}
