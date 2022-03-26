<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Catalog\StoreDocumentElementTable;
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

		$rows = StoreDocumentElementTable::getList([
			'order' => $order,
			'filter' => $filter,
			'select' => $select
		]);
		while ($row = $rows->Fetch())
		{
			$records[] = new DocumentProductRecord([
				'id' => (int)$row['ID'],
				'documentId' => (int)$row['DOC_ID'],
				'productId' => (int)$row['ELEMENT_ID'],
				'storeFromId' => (int)$row['STORE_FROM'],
				'storeToId' => (int)$row['STORE_TO'],
				'name' => $row['ELEMENT_NAME'],
				'amount' => (float)$row['AMOUNT'],
				'price' => [
					'purchase' => [
						'amount' => (float)$row['PURCHASING_PRICE'],
						'currency' => $currency,
					],
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