<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\Item;
use Bitrix\CrmMobile\ProductGrid\Enricher\CompleteStores;
use Bitrix\CrmMobile\ProductGrid\Enricher\ConvertCurrency;
use Bitrix\Mobile\Query;

final class ConvertCurrencyQuery extends Query
{
	private Item $entity;
	private string $currencyId;
	private array $products;

	public function __construct(Item $entity, string $currencyId, array $products = [])
	{
		$this->entity = $entity;
		$this->currencyId = $currencyId;
		$this->products = $products;
	}

	public function execute(): array
	{
		$rows = array_map(fn($fields) => ProductRowViewModel::createFromArray($fields), $this->products);

		$enrichers = [
			new ConvertCurrency($this->currencyId),
			new CompleteStores($this->entity),
		];

		foreach ($enrichers as $enricher)
		{
			$rows = $enricher->enrich($rows);
		}

		return array_map(fn($row) => $row->toArray(), $rows);
	}
}
