<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\Item;
use Bitrix\CrmMobile\ProductGrid\Enricher\CompleteStores;
use Bitrix\Mobile\Query;

final class CompleteStoresQuery extends Query
{
	private Item $entity;
	private array $products;

	public function __construct(Item $entity, array $products = [])
	{
		$this->entity = $entity;
		$this->products = $products;
	}

	public function execute(): array
	{
		$rows = array_map(fn($fields) => ProductRowViewModel::createFromArray($fields), $this->products);
		$rows = (new CompleteStores($this->entity))->enrich($rows);

		return array_map(fn($row) => $row->toArray(), $rows);
	}
}
