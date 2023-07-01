<?php

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\Service\Accounting;
use Bitrix\Mobile\Query;

abstract class BaseSummaryQuery extends Query
{
	protected string $currencyId;
	protected array $products;
	protected Accounting $accounting;
	protected int $precision;
	protected array $additionalConfig;

	protected function getTotalDiscount(): float
	{
		$result = 0.0;
		foreach ($this->products as $productRow)
		{
			$result += ($productRow['DISCOUNT_SUM'] * $productRow['QUANTITY']);
		}
		return round($result, $this->precision);
	}

	protected function isTaxIncluded(): bool
	{
		foreach ($this->products as $productRow)
		{
			if ($productRow['TAX_INCLUDED'] === 'Y')
			{
				return true;
			}
		}
		return false;
	}

	protected function isTaxPartlyIncluded(): bool
	{
		$hasItemsWithTaxIncluded = null;
		$hasItemsWithNoTaxIncluded = null;

		foreach ($this->products as $productRow)
		{
			if (isset($productRow['TAX_INCLUDED']) && $productRow['TAX_INCLUDED'] === 'Y')
			{
				$hasItemsWithTaxIncluded = true;
			}
			elseif (isset($productRow['TAX_RATE']) && $productRow['TAX_RATE'] > 0)
			{
				$hasItemsWithNoTaxIncluded = true;
			}
		}

		return ($hasItemsWithNoTaxIncluded && $hasItemsWithTaxIncluded);
	}
}