<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;

interface Enricher
{
	/**
	 * @param DocumentProductRecord[] $records
	 * @return DocumentProductRecord[]
	 */
	public function enrich(array $records): array;
}