<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\CrmMobile\ProductGrid\Enricher\ConvertCurrency;
use Bitrix\Mobile\Query;

final class ConvertCurrencyQuery extends Query
{
	private string $currencyId;
	private array $products;

	public function __construct(string $currencyId, array $products = [])
	{
		$this->currencyId = $currencyId;
		$this->products = $products;
	}

	public function execute(): array
	{
		$rows = array_map(fn($fields) => ProductRowViewModel::createFromArray($fields), $this->products);

		$enrichers = [
			new ConvertCurrency($this->currencyId),
		];

		foreach ($enrichers as $enricher)
		{
			$rows = $enricher->enrich($rows);
		}

		return array_map(fn($row) => $row->toArray(), $rows);
	}
}
