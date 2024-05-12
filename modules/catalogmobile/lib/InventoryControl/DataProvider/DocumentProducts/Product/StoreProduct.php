<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreDocumentElementTable;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Document;

Loader::includeModule('catalog');

final class StoreProduct extends BaseProduct
{
	public static function load(?int $documentId = null): array
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
			'select' => $select,
		]);

		$storeDocumentElementTableRows = [];
		$productIds = [];
		while ($row = $rows->Fetch())
		{
			$storeDocumentElementTableRows[] = $row;
			$productIds[] = (int)$row['ELEMENT_ID'];
		}

		$productStoreInfo = self::getProductStoreInfo($productIds);

		foreach ($storeDocumentElementTableRows as $row)
		{
			$hasStoreFromAccess = !$row['STORE_FROM'] || self::hasStoreAccess((int)$row['STORE_FROM']);
			$hasStoreToAccess = !$row['STORE_TO'] || self::hasStoreAccess((int)$row['STORE_TO']);

			$storeFromAvailableAmount = 0;
			if ($row['ELEMENT_ID'])
			{
				$storeFromAvailableAmount = self::getAvailableProductAmountOnStore(
					$productStoreInfo,
					(int)$row['ELEMENT_ID'],
					(int)$row['STORE_FROM']
				);
			}
			$storeFromAmount = (float)($productStoreInfo[(int)$row['ELEMENT_ID']][(int)$row['STORE_FROM']]['AMOUNT'] ?? 0);

			$storeToAvailableAmount = 0;
			if ($row['ELEMENT_ID'])
			{
				$storeToAvailableAmount = self::getAvailableProductAmountOnStore(
					$productStoreInfo,
					(int)$row['ELEMENT_ID'],
					(int)$row['STORE_TO']
				);
			}
			$storeToAmount = (float)($productStoreInfo[(int)$row['ELEMENT_ID']][(int)$row['STORE_TO']]['AMOUNT'] ?? 0);

			$records[] = DocumentProductRecord::make([
				'id' => (int)$row['ID'],
				'documentId' => (int)$row['DOC_ID'],
				'productId' => (int)$row['ELEMENT_ID'],
				'storeFromId' => $hasStoreFromAccess ? (int)$row['STORE_FROM'] : null,
				'storeFromAvailableAmount' => $storeFromAvailableAmount,
				'storeFromAmount' => $storeFromAmount,
				'hasStoreFromAccess' => $hasStoreFromAccess,
				'storeToId' => $hasStoreToAccess ? (int)$row['STORE_TO'] : null,
				'storeToAvailableAmount' => $storeToAvailableAmount,
				'storeToAmount' => $storeToAmount,
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
					],
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
}
