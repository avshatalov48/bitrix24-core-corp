<?php

namespace Bitrix\Crm\Integration\Catalog\Access;

use Bitrix\Crm\Discount;
use Bitrix\Crm\Service\Container;

class Calculator
{
	private array $product;

	private const PRICE_PRECISION = 2;

	public function __construct(array $product)
	{
		$this->product = [
			'TAX_INCLUDED' => $product['TAX_INCLUDED'],
			'TAX_RATE' => (float)$product['TAX_RATE'],
			'PRICE_NETTO' => (float)$product['PRICE_NETTO'],
			'PRICE_BRUTTO' => (float)$product['PRICE_BRUTTO'],
			'PRICE' => (float)$product['PRICE'],
			'PRICE_EXCLUSIVE' => (float)$product['PRICE_EXCLUSIVE'],
			'DISCOUNT_SUM' => (float)$product['DISCOUNT_SUM'],
			'DISCOUNT_RATE' => (float)$product['DISCOUNT_RATE'],
			'DISCOUNT_TYPE_ID' => (int)$product['DISCOUNT_TYPE_ID'],
		];
	}

	private function setField(string $field, mixed $value): void
	{
		$roundFields = [
			'TAX_RATE',
			'PRICE_NETTO',
			'PRICE_BRUTTO',
			'PRICE',
			'PRICE_EXCLUSIVE',
			'DISCOUNT_SUM',
			'DISCOUNT_RATE',
		];
		$this->product[$field] = in_array($field, $roundFields, true)
			? round($value, self::PRICE_PRECISION)
			: $value
		;
	}

	public function getProduct(): array
	{
		return $this->product;
	}

	public function calculateDiscount(float $newDiscountRate): void
	{
		if ($this->product['DISCOUNT_RATE'] === $newDiscountRate)
		{
			return;
		}

		if ($newDiscountRate === 0.0)
		{
			$this->clearResultPrices();
		}
		elseif ($this->isDiscountPercentage())
		{
			$this->setField('DISCOUNT_RATE', $newDiscountRate);

			$this->updateResultPrices();

			$this->setField(
				'DISCOUNT_SUM',
				$this->product['PRICE_NETTO'] - $this->product['PRICE_EXCLUSIVE'],
			);
		}
		elseif ($this->isDiscountMonetary())
		{
			$this->setField('DISCOUNT_SUM', $newDiscountRate);

			$this->updateResultPrices();

			$this->setField(
				'DISCOUNT_RATE',
				Discount::calculateDiscountRate(
					$this->product['PRICE_NETTO'],
					$this->product['PRICE_EXCLUSIVE'],
				),
			);
		}
	}

	public function calculateDiscountType(int $newDiscountType): void
	{
		if ($this->product['DISCOUNT_TYPE_ID'] === $newDiscountType)
		{
			return;
		}

		$this->setField('DISCOUNT_TYPE_ID', $newDiscountType);

		$this->updateResultPrices();
		$this->updateDiscount();
	}

	public function calculateTaxRate(float $newTaxRate): void
	{
		if ($this->product['TAX_RATE'] === $newTaxRate)
		{
			return;
		}

		$this->setField('TAX_RATE', $newTaxRate);

		$this->updateBasePrices();
		$this->updateResultPrices();

		if ($this->product['TAX_INCLUDED'] === 'Y')
		{
			$this->updateDiscount();
		}
	}

	public function calculateTaxIncluded(string $newTaxIncluded): void
	{
		if ($this->product['TAX_INCLUDED'] === $newTaxIncluded)
		{
			return;
		}

		$this->setField('TAX_INCLUDED', $newTaxIncluded);

		if ($this->product['TAX_INCLUDED'] === 'Y')
		{
			$this->setField('PRICE_BRUTTO', $this->product['PRICE_NETTO']);
		}
		else
		{
			$this->setField('PRICE_NETTO', $this->product['PRICE_BRUTTO']);
		}

		$this->updatePrice();
	}

	private function updatePrice(): void
	{
		$this->updateBasePrices();

		if ($this->isEmptyDiscount())
		{
			$this->clearResultPrices();
		}
		else if ($this->isDiscountHandmade())
		{
			$this->updateResultPrices();
		}

		$this->updateDiscount();
	}

	private function updateDiscount(): void
	{
		if ($this->isEmptyDiscount())
		{
			$this->clearResultPrices();
		}
		elseif ($this->isDiscountPercentage())
		{
			$this->setField(
				'DISCOUNT_SUM',
				$this->product['PRICE_NETTO'] - $this->product['PRICE_EXCLUSIVE'],
			);
		}
		elseif ($this->isDiscountMonetary())
		{
			$this->setField(
				'DISCOUNT_RATE',
				Discount::calculateDiscountRate(
					$this->product['PRICE_NETTO'],
					$this->product['PRICE_NETTO'] - $this->product['DISCOUNT_SUM'],
				),
			);
		}
	}

	private function isDiscountPercentage(): bool
	{
		return $this->product['DISCOUNT_TYPE_ID'] === Discount::PERCENTAGE;
	}

	private function isDiscountMonetary(): bool
	{
		return $this->product['DISCOUNT_TYPE_ID'] === Discount::MONETARY;
	}

	private function isDiscountHandmade(): bool
	{
		return $this->isDiscountPercentage() || $this->isDiscountMonetary();
	}

	private function updateResultPrices(): void
	{
		if ($this->product['DISCOUNT_TYPE_ID'] === Discount::PERCENTAGE)
		{
			$this->setField(
				'PRICE_EXCLUSIVE',
				Discount::calculatePrice($this->product['PRICE_NETTO'], $this->product['DISCOUNT_RATE']),
			);
		}
		elseif ($this->product['DISCOUNT_TYPE_ID'] === Discount::MONETARY)
		{
			$this->setField(
				'PRICE_EXCLUSIVE',
				$this->product['PRICE_NETTO'] - $this->product['DISCOUNT_SUM'],
			);
		}
		$this->setField(
			'PRICE',
			Container::getInstance()->getAccounting()->calculatePriceWithTax(
				$this->product['PRICE_EXCLUSIVE'],
				$this->product['TAX_RATE'],
			),
		);
	}

	private function clearResultPrices(): void
	{
		$this->setField('PRICE_EXCLUSIVE', $this->product['PRICE_NETTO']);
		$this->setField('PRICE', $this->product['PRICE_BRUTTO']);
		$this->setField('DISCOUNT_RATE', 0.0);
		$this->setField('DISCOUNT_SUM', 0.0);
	}

	private function updateBasePrices(): void
	{
		if ($this->product['TAX_INCLUDED'] === 'Y')
		{
			$this->setField(
				'PRICE_NETTO',
				Container::getInstance()->getAccounting()->calculatePriceWithoutTax(
					(float)$this->product['PRICE_BRUTTO'],
					$this->product['TAX_RATE'],
				),
			);
		}
		else
		{
			$this->setField(
				'PRICE_BRUTTO',
				Container::getInstance()->getAccounting()->calculatePriceWithTax(
					(float)$this->product['PRICE_NETTO'],
					$this->product['TAX_RATE'],
				),
			);
		}
	}

	private function isEmptyDiscount(): bool
	{
		if ($this->product['DISCOUNT_TYPE_ID'] === Discount::PERCENTAGE)
		{
			return $this->product['DISCOUNT_SUM'] === 0.0;
		}
		elseif ($this->product['DISCOUNT_TYPE_ID'] === Discount::MONETARY)
		{
			return $this->product['DISCOUNT_RATE'] === 0.0;
		}

		return $this->product['DISCOUNT_TYPE_ID'] === Discount::UNDEFINED;
	}
}
