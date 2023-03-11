<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Catalog\StoreBarcodeTable;
use Bitrix\Catalog\StoreDocumentBarcodeTable;
use Bitrix\Main\Loader;
use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;

Loader::includeModule('catalog');

final class CompleteBarcodes implements Enricher
{
	/**
	 * @param DocumentProductRecord[] $records
	 * @return DocumentProductRecord[]
	 */
	public function enrich(array $records): array
	{
		$recordIds = $this->extractRecordIds($records);
		$productIds = $this->extractProductIds($records);

		$barcodesFromDocuments = $this->loadBarcodesFromDocuments($recordIds);
		$barcodesFromCatalog = $this->loadBarcodesFromCatalog($productIds);

		$result = [];

		foreach ($records as $origRecord)
		{
			$record = clone $origRecord;

			if (!empty($barcodesFromDocuments[$record->id]))
			{
				$record->barcode = $barcodesFromDocuments[$record->id];
			}
			elseif (!empty($barcodesFromCatalog[$record->productId]))
			{
				$record->barcode = $barcodesFromCatalog[$record->productId];
			}

			$result[] = $record;
		}

		return $result;
	}

	/**
	 * @param DocumentProductRecord[] $records
	 * @return int[]
	 */
	private function extractProductIds(array $records): array
	{
		$productIds = [];
		foreach ($records as $record)
		{
			if ($record->productId)
			{
				$productIds[] = (int)$record->productId;
			}
		}
		return array_unique($productIds);
	}

	/**
	 * @param DocumentProductRecord[] $records
	 * @return int[]
	 */
	private function extractRecordIds(array $records): array
	{
		$recordIds = [];
		foreach ($records as $record)
		{
			if ($record->id)
			{
				$recordIds[] = (int)$record->id;
			}
		}
		return array_unique($recordIds);
	}

	/**
	 * @param int[] $recordIds
	 * @return array<int, string>
	 */
	private function loadBarcodesFromDocuments(array $recordIds): array
	{
		if (empty($recordIds))
		{
			return [];
		}

		$barcodes = [];

		$rows = StoreDocumentBarcodeTable::getList([
			'select' => ['DOC_ELEMENT_ID', 'BARCODE'],
			'filter' => ['=DOC_ELEMENT_ID' => $recordIds]
		]);
		while ($row = $rows->fetch())
		{
			$recordId = $row['DOC_ELEMENT_ID'];
			$barcodes[$recordId] = $row['BARCODE'];
		}

		return $barcodes;
	}

	/**
	 * @param int[] $productIds
	 * @return array<int, string>
	 */
	private function loadBarcodesFromCatalog(array $productIds): array
	{
		if (empty($productIds))
		{
			return [];
		}

		$barcodes = [];

		$rows = StoreBarcodeTable::getList([
			'filter' => [
				'PRODUCT_ID' => $productIds,
			]
		]);

		while ($barcode = $rows->fetch())
		{
			$productId = $barcode['PRODUCT_ID'];
			if (!isset($barcodes[$productId]))
			{
				$barcodes[$productId] = $barcode['BARCODE'];
			}
		}

		return $barcodes;
	}
}
