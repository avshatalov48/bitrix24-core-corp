<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;

Loader::requireModule('currency');

final class ConvertCurrency implements Enricher
{
	private const PRICE_PRECISION = 2;

	private $currencyId = '';

	public function __construct(string $currencyId)
	{
		$this->currencyId = $currencyId;
	}
	/**
	 * @param DocumentProductRecord[] $records
	 * @return DocumentProductRecord[]
	 */
	public function enrich(array $records): array
	{
		foreach ($records as $item)
		{
			if (!is_array($item->price))
			{
				continue;
			}

			foreach (array_keys($item->price) as $priceType)
			{
				if (
					isset($item->price[$priceType]['currency'])
					&& $item->price[$priceType]['currency'] !== $this->currencyId
				)
				{
					if ((float)$item->price[$priceType]['amount'] != 0)
					{
						$item->price[$priceType]['amount'] = $this->formatPrice(
							\CCurrencyRates::ConvertCurrency(
								$item->price[$priceType]['amount'],
								$item->price[$priceType]['currency'],
								$this->currencyId
							)
						);
					}
					$item->price[$priceType]['currency'] = $this->currencyId;
				}
			}
		}

		return $records;
	}

	private function formatPrice($price): string
	{
		return (string)number_format(
			$price,
			self::PRICE_PRECISION,
			'.',
			''
		);
	}
}
