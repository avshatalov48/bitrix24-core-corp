<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\Service\Accounting;
use Bitrix\Main\Loader;

Loader::requireModule('crm');

final class TaxCalculator
{
	private Accounting $accounting;

	private float $taxRate = 0.0;

	private bool $taxIncluded = false;

	private float $finalPrice = 0.0;

	private float $priceBeforeTax = 0.0;

	private ?int $vatId = null;

	private ?string $vatName = null;

	public function __construct(Accounting $accounting)
	{
		$this->accounting = $accounting;
	}

	public function calculate(float $basePrice, ?int $vatId = null, bool $vatIncluded = false): TaxCalculator
	{
		if ($this->accounting->isTaxMode())
		{
			$this->taxRate = 0.0;
			$this->taxIncluded = false;
			$this->finalPrice = $basePrice;
			$this->priceBeforeTax = $basePrice;
		}
		else
		{
			$vat = $this->findVatRateOrDefault($vatId);

			$this->vatId = isset($vat['ID']) ? (int)$vat['ID'] : null;
			$this->vatName = isset($vat['NAME']) ? (string)$vat['NAME'] : null;
			$this->taxRate = isset($vat['VALUE']) ? (float)$vat['VALUE'] : 0.0;
			$this->taxIncluded = $vatIncluded;

			$this->finalPrice = $vatIncluded
				? $basePrice
				: $this->accounting->calculatePriceWithTax($basePrice, $this->taxRate);

			$this->priceBeforeTax = $this->accounting->calculatePriceWithoutTax($this->finalPrice, $this->taxRate);
		}

		return $this;
	}

	public function getTaxRate(): float
	{
		return $this->taxRate;
	}

	public function isTaxIncluded(): bool
	{
		return $this->taxIncluded;
	}

	public function getFinalPrice(): float
	{
		return $this->finalPrice;
	}

	public function getPriceBeforeTax(): float
	{
		return $this->priceBeforeTax;
	}

	public function getTaxValue(): float
	{
		return $this->finalPrice - $this->priceBeforeTax;
	}

	public function getVatId(): ?int
	{
		return $this->vatId;
	}

	public function getVatName(): ?string
	{
		return $this->vatName;
	}

	private function findVatRateOrDefault(?int $vatId): array
	{
		$vat = null;
		if ($vatId)
		{
			$vatRates = $this->loadVatRates();
			foreach ($vatRates as $rate)
			{
				if ((int)$rate['ID'] === $vatId)
				{
					$vat = $rate;
					break;
				}
			}
		}

		if (empty($vat))
		{
			$vat = $this->loadDefaultVatRate();
		}

		return $vat;
	}

	private function loadVatRates(): array
	{
		static $vatRates = null;
		if ($vatRates === null)
		{
			$vatRates = \CCrmTax::GetVatRateInfos() ?? [];
		}
		return $vatRates;
	}

	private function loadDefaultVatRate(): array
	{
		static $vatRate = null;
		if ($vatRate === null)
		{
			$vatRate = \CCrmTax::GetDefaultVatRateInfo() ?? [];
		}
		return $vatRate;
	}
}
