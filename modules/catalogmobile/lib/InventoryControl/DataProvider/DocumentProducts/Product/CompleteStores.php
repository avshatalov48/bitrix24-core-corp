<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\CatalogMobile\InventoryControl\Dto\Store;

Loader::includeModule('catalog');

final class CompleteStores implements Enricher
{
	/**
	 * @param DocumentProductRecord[] $records
	 * @return DocumentProductRecord[]
	 */
	public function enrich(array $records): array
	{
		$storeIds = $this->extractStoreIds($records);

		if (empty($storeIds))
		{
			return $records;
		}

		$result = [];
		$storeInfo = [];

		$filter = ['ID' => $storeIds];
		$select = ['ID', 'TITLE', 'ADDRESS'];

		$rows = \CCatalogStore::GetList([], $filter, false, false, $select);
		while ($row = $rows->Fetch())
		{
			$storeInfo[$row['ID']] = $row;
		}

		foreach ($records as $origRecord)
		{
			$record = clone $origRecord;

			if (!empty($storeInfo[$record->storeFromId]))
			{
				$record->storeFrom = $this->buildStoreDto($storeInfo[$record->storeFromId]);
			}
			if (!empty($storeInfo[$record->storeToId]))
			{
				$record->storeTo = $this->buildStoreDto($storeInfo[$record->storeToId]);
			}

			$result[] = $record;
		}

		return $result;
	}

	private function buildStoreDto(array $fields): Store
	{
		return Store::make([
			'id' => $fields['ID'],
			'title' => ($fields['TITLE'] == '' ? $fields['ADDRESS'] : $fields['TITLE']),
		]);
	}

	/**
	 * @param DocumentProductRecord[] $records
	 * @return int[]
	 */
	private function extractStoreIds(array $records): array
	{
		$storeIds = [];
		foreach ($records as $record)
		{
			if (!empty($record->storeFromId))
			{
				$storeIds[] = (int)$record->storeFromId;
			}
			if (!empty($record->storeToId))
			{
				$storeIds[] = (int)$record->storeToId;
			}
		}
		return array_unique($storeIds);
	}
}
