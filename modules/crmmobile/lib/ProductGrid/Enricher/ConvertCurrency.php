<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid\Enricher;

use Bitrix\CrmMobile\ProductGrid\ProductRowViewModel;

final class ConvertCurrency implements EnricherContract
{
	private string $currencyId;

	/** @var array<string, bool> */
	private array $convertableFields;

	public function __construct(string $currencyId)
	{
		$this->currencyId = $currencyId;
		$this->convertableFields = array_fill_keys([
			'PRICE',
			'PRICE_EXCLUSIVE',
			'PRICE_NETTO',
			'PRICE_BRUTTO',
			'PRICE_ACCOUNT',
			'BASE_PRICE',
			'SUM',
			'TAX_SUM',
			'DISCOUNT_ROW',
			'DISCOUNT_SUM',
		], true);
	}

	/**
	 * @param ProductRowViewModel[] $rows
	 * @return ProductRowViewModel[]
	 */
	public function enrich(array $rows): array
	{
		return array_map(function($row) {

			return $row->currencyId === $this->currencyId ? $row : $this->convert($row);

		}, $rows);
	}

	private function convert(ProductRowViewModel $row): ProductRowViewModel
	{
		$oldCurrencyId = $row->currencyId;
		$newCurrencyId = $this->currencyId;

		$fields = $row->toArray();

		$fields['CURRENCY'] = $newCurrencyId;

		foreach ($fields as $field => $value)
		{
			if ($this->isConvertable($field))
			{
				$fields[$field] = \CCrmCurrency::ConvertMoney($value, $oldCurrencyId, $newCurrencyId);
			}
		}

		return ProductRowViewModel::createFromArray($fields);
	}

	private function isConvertable(string $field): bool
	{
		return isset($this->convertableFields[$field]);
	}
}