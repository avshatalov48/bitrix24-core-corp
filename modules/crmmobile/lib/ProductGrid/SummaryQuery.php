<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Accounting;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Query;

Loader::requireModule('crm');

final class SummaryQuery extends Query
{
	private Item $entity;

	private string $currencyId;

	private array $products;

	private Accounting $accounting;

	private int $precision;

	public function __construct(Item $entity, ?array $products = null, ?string $currencyId = null)
	{
		$this->entity = $entity;
		if ($products === null)
		{
			$rows = $this->entity->getProductRows();
			$products = $rows ? $rows->toArray() : [];
		}
		$this->products = $products;
		$this->currencyId = $currencyId ?? $this->entity->getCurrencyId();
		$this->accounting = Container::getInstance()->getAccounting();
		$this->precision = \CCrmCurrency::GetCurrencyDecimals($this->currencyId);
	}

	public function execute(): array
	{
		$result = $this->accounting->calculate(
			$this->products,
			$this->currencyId,
			$this->getPersonTypeId(),
			$this->getLocationId()
		);

		if (!is_array($result))
		{
			$result = [];
		}

		$totalDiscount = $this->getTotalDiscount();
		$totalSum = isset($result['PRICE']) ? round((float)$result['PRICE'], $this->precision) : 0.0;
		$totalTax = isset($result['TAX_VALUE']) ? round((float)$result['TAX_VALUE'], $this->precision) : 0.0;

		$totalBeforeDiscount = round($totalSum + $totalDiscount, $this->precision);
		
		$totalDelivery = $this->entity->getId()
			? $this->accounting->calculateDeliveryTotal(ItemIdentifier::createByItem($this->entity))
			: 0.0;

		$totalSum = $totalSum + $totalDelivery;

		return [
			'totalRows' => count($this->products),
			'totalCost' => $totalSum,
			'totalDelivery' => $totalDelivery,
			'totalTax' => $totalTax,
			'totalDiscount' => $totalDiscount,
			'totalWithoutDiscount' => $totalBeforeDiscount,
			'currency' => $this->currencyId,
			'taxIncluded' => $this->isTaxIncluded(),
			'taxPartlyIncluded' => $this->isTaxPartlyIncluded(),
		];
	}

	private function getPersonTypeId(): int
	{
		return $this->accounting->resolvePersonTypeId($this->entity);
	}

	private function getLocationId(): ?string
	{
		$locationId = null;
		if ($this->entity->hasField(Item::FIELD_NAME_LOCATION_ID))
		{
			$locationId = $this->entity->get(Item::FIELD_NAME_LOCATION_ID);
		}
		return $locationId;
	}

	private function getTotalDiscount(): float
	{
		$result = 0.0;
		foreach ($this->products as $productRow)
		{
			$result += ($productRow['DISCOUNT_SUM'] * $productRow['QUANTITY']);
		}
		return round($result, $this->precision);
	}

	private function isTaxIncluded(): bool
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

	private function isTaxPartlyIncluded(): bool
	{
		$hasItemsWithTaxIncluded = null;
		$hasItemsWithNoTaxIncluded = null;

		foreach ($this->products as $productRow)
		{
			if ($productRow['TAX_INCLUDED'] === 'Y')
			{
				$hasItemsWithTaxIncluded = true;
			}
			elseif ($productRow['TAX_RATE'] > 0)
			{
				$hasItemsWithNoTaxIncluded = true;
			}
		}

		return ($hasItemsWithNoTaxIncluded && $hasItemsWithTaxIncluded);
	}
}
