<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreDocumentElementTable;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Random;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product\CompleteBarcodes;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product\CompletePrices;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product\CompleteSections;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product\CompleteSku;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product\CompleteStores;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product\Enricher;
use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;

Loader::includeModule('catalog');

final class Product
{
	public static function loadByDocumentId(?int $documentId = null): array
	{
		if ($documentId === null)
		{
			return [];
		}

		$document = Document::load($documentId);
		$currency = $document->currency;

		$records = [];

		$order = ['ID' => 'ASC'];
		$filter = ['DOC_ID' => $documentId];
		$select = [
			'ID',
			'DOC_ID',
			'STORE_FROM',
			'STORE_TO',
			'ELEMENT_ID',
			'ELEMENT_NAME' => 'PRODUCT.IBLOCK_ELEMENT.NAME',
			'AMOUNT',
			'PURCHASING_PRICE',
			'BASE_PRICE',
		];

		$hasPurchasePriceReadAccess = AccessController::getCurrent()->check(
			ActionDictionary::ACTION_PRODUCT_PURCHASE_INFO_VIEW
		);

		$rows = StoreDocumentElementTable::getList([
			'order' => $order,
			'filter' => $filter,
			'select' => $select
		]);
		while ($row = $rows->Fetch())
		{
			$hasStoreFromAccess = $row['STORE_FROM'] ? self::hasStoreAccess((int)$row['STORE_FROM']) : true;
			$hasStoreToAccess = $row['STORE_TO'] ? self::hasStoreAccess((int)$row['STORE_TO']) : true;

			$records[] = new DocumentProductRecord([
				'id' => (int)$row['ID'],
				'documentId' => (int)$row['DOC_ID'],
				'productId' => (int)$row['ELEMENT_ID'],
				'storeFromId' => $hasStoreFromAccess ? (int)$row['STORE_FROM'] : null,
				'hasStoreFromAccess' => $hasStoreFromAccess,
				'storeToId' => $hasStoreToAccess ? (int)$row['STORE_TO'] : null,
				'storeToAccess' => self::hasStoreAccess((int)$row['STORE_TO']),
				'hasStoreToAccess' => $hasStoreToAccess,
				'name' => $row['ELEMENT_NAME'],
				/**
				 * @todo
				 * the STORE_FROM field needs to be checked instead for the following types:
				 * StoreDocumentTable::TYPE_DEDUCT
				 * StoreDocumentTable::TYPE_MOVING
				 */
				'amount' => self::hasStoreAccess((int)$row['STORE_TO'])
					? (float)$row['AMOUNT']
					: null,
				'price' => [
					'purchase' => $hasPurchasePriceReadAccess
						?
						[
							'amount' => (float)$row['PURCHASING_PRICE'],
							'currency' => $currency,
						]
						: null
					,
					'sell' => [
						'amount' => (float)$row['BASE_PRICE'],
						'currency' => $currency,
					]
				],
			]);
		}

		return self::enrich($records, [
			new CompleteSku(),
			new CompleteSections(),
			new CompleteStores(),
			new CompleteBarcodes(),
		]);
	}

	private static function hasStoreAccess(int $storeId): bool
	{
		return AccessController::getCurrent()->checkByValue(
			ActionDictionary::ACTION_STORE_VIEW,
			(string)$storeId
		);
	}

	public static function loadProductModel(int $productId, ?int $documentId = null): DocumentProductRecord
	{
		$record = new DocumentProductRecord([
			'id' => 'unsaved_' . Random::getString(8) . '_' . $productId,
			'documentId' => $documentId,
			'productId' => $productId,
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
	private static function enrich(array $records, array $enrichers)
	{
		foreach ($enrichers as $enricher)
		{
			$records = $enricher->enrich($records);
		}

		return $records;
	}
}