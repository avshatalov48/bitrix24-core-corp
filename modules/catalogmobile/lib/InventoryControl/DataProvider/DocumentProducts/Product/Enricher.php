<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;

interface Enricher
{
	/**
	 * @param DocumentProductRecord[] $records
	 * @return DocumentProductRecord[]
	 */
	public function enrich(array $records): array;
}