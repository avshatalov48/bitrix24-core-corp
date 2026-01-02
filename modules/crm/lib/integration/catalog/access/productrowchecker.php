<?php

namespace Bitrix\Crm\Integration\Catalog\Access;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Crm\Discount;
use Bitrix\Crm\Order\BasketItem;
use Bitrix\Main\Result;
use Bitrix\Main\Error;

class ProductRowChecker
{
	private const PRICE_ERROR = 'Insufficient permission to change product price';
	private const DISCOUNT_ERROR = 'Insufficient permission to change product discount';

	public function checkOrderCatalogRights(
		\Bitrix\Crm\Order\Order $order
	): Result
	{
		$result = new Result();

		if (!\Bitrix\Main\Loader::includeModule('catalog'))
		{
			return $result;
		}

		$canEditPrice = $this->canEditPrice(\CCrmOwnerType::Order);
		$canEditDiscount = $this->canEditDiscount(\CCrmOwnerType::Order);
		if ($canEditPrice && $canEditDiscount)
		{
			return $result;
		}

		$basketCollection = $order->getBasket();
		$orderCurrency = $order->getCurrency();
		foreach ($basketCollection as $basketItem)
		{
			/** @var BasketItem $basketItem */
			$basketItemFields = $basketItem->getFields();
			if ($basketItemFields->get('MODULE') !== 'catalog')
			{
				continue;
			}

			if (!$canEditDiscount)
			{
				if ($basketItemFields->get('ID'))
				{
					if ($basketItemFields->isChanged('DISCOUNT_PRICE'))
					{
						return $result->addError(new Error(self::DISCOUNT_ERROR));
					}
				}
				else
				{
					if ((float)$basketItemFields->get('DISCOUNT_PRICE') !== 0.0)
					{
						return $result->addError(new Error(self::DISCOUNT_ERROR));
					}
				}
			}

			if ($canEditPrice)
			{
				continue;
			}

			if (!$basketItemFields->get('ID') || $basketItemFields->isChanged('PRODUCT_ID'))
			{
				$catalogPrice = $this->getCatalogPrice(
					$orderCurrency,
					$basketItemFields->get('PRODUCT_ID'),
					$basketItemFields->get('PRICE_TYPE_ID') ?? null,
				);
				if ($catalogPrice === null)
				{
					continue;
				}

				if (
					(float)$basketItemFields->get('BASE_PRICE') !== $catalogPrice
					|| (
						(float)$basketItemFields->get('PRICE') !==
						(float)$basketItemFields->get('BASE_PRICE')
						- (float)$basketItemFields->get('DISCOUNT_PRICE')
					)
				)
				{
					return $result->addError(new Error(self::PRICE_ERROR));
				}
			}
			else
			{
				if (
					$basketItemFields->isChanged('BASE_PRICE')
					|| (
						$basketItemFields->isChanged('PRICE')
						&& (
							(float)$basketItemFields->get('PRICE') !==
							(float)$basketItemFields->get('BASE_PRICE')
							- (float)$basketItemFields->get('DISCOUNT_PRICE')
						)
					)
				)
				{
					return $result->addError(new Error(self::PRICE_ERROR));
				}
			}
		}

		return $result;
	}

	public function checkCatalogRights(
		int $ownerTypeId,
		array $productRows,
		?string $currencyId = null,
		array $originalProductRows = [],
		?string $originalCurrencyId = null,
	): Result
	{
		$result = new Result();

		if (!\Bitrix\Main\Loader::includeModule('catalog'))
		{
			return $result;
		}

		$canEditPrice = $this->canEditPrice($ownerTypeId);
		$canEditDiscount = $this->canEditDiscount($ownerTypeId);
		if ($canEditPrice && $canEditDiscount)
		{
			return $result;
		}

		$originalProductRows = $this->mapOriginalProductRows($originalProductRows);

		foreach ($productRows as $productRow)
		{
			$originalProductRow =
				isset($originalProductRows[(int)$productRow['ID']])
				&& $originalProductRows[(int)$productRow['ID']]['PRODUCT_ID'] === $productRow['PRODUCT_ID']
					? $originalProductRows[(int)$productRow['ID']]
					: null
			;

			if (
				$originalProductRow
				&& $currencyId
				&& $originalCurrencyId
				&& $originalCurrencyId !== $currencyId
			)
			{
				$originalProductRow = $this->convertCurrency(
					$originalProductRow,
					$originalCurrencyId,
					$currencyId,
				);
			}

			if (
				!$canEditDiscount
				&& !$this->checkDiscount($productRow, $originalProductRow, $currencyId, $originalCurrencyId)
			)
			{
				return $result->addError(new Error(self::DISCOUNT_ERROR));
			}

			if (!$canEditPrice && !$this->checkPrice($productRow, $currencyId, $originalProductRow))
			{
				return $result->addError(new Error(self::PRICE_ERROR));
			}
		}

		return $result;
	}

	protected function canEditPrice(int $ownerTypeId): bool
	{
		return AccessController::getCurrent()->checkByValue(
			\Bitrix\Catalog\Access\ActionDictionary::ACTION_PRICE_ENTITY_EDIT,
			$ownerTypeId,
		);
	}

	protected function canEditDiscount(int $ownerTypeId): bool
	{
		return AccessController::getCurrent()->checkByValue(
			\Bitrix\Catalog\Access\ActionDictionary::ACTION_PRODUCT_DISCOUNT_SET,
			$ownerTypeId,
		);
	}

	private function mapOriginalProductRows(array $productRows): array
	{
		$mappedProductRows = [];
		foreach ($productRows as $productRow)
		{
			$mappedProductRows[(int)$productRow['ID']] = $productRow;
		}

		return $mappedProductRows;
	}

	private function convertCurrency(array $productRow, ?string $oldCurrency, ?string $newCurrency): array
	{
		$productRow['PRICE_NETTO'] = \CCrmCurrency::ConvertMoney(
			$productRow['PRICE_NETTO'],
			$oldCurrency,
			$newCurrency,
		);
		$productRow['PRICE_BRUTTO'] = \CCrmCurrency::ConvertMoney(
			$productRow['PRICE_BRUTTO'],
			$oldCurrency,
			$newCurrency,
		);
		$productRow['PRICE'] = \CCrmCurrency::ConvertMoney(
			$productRow['PRICE'],
			$oldCurrency,
			$newCurrency,
		);
		$productRow['PRICE_EXCLUSIVE'] = \CCrmCurrency::ConvertMoney(
			$productRow['PRICE_EXCLUSIVE'],
			$oldCurrency,
			$newCurrency,
		);
		$productRow['DISCOUNT_SUM'] = \CCrmCurrency::ConvertMoney(
			$productRow['DISCOUNT_SUM'],
			$oldCurrency,
			$newCurrency,
		);

		return $productRow;
	}

	private function checkPrice(array $productRow, ?string $currencyId, ?array $originalProductRow): bool
	{
		if (!$productRow['PRODUCT_ID'])
		{
			return true;
		}

		if ($originalProductRow)
		{
			return $this->comparePrices($productRow, $originalProductRow);
		}

		$catalogPrice = $this->getCatalogPrice($currencyId, $productRow['PRODUCT_ID']);
		if ($catalogPrice === null)
		{
			return true;
		}

		return $this->comparePrices($productRow, $this->getDefaultProductRow(($catalogPrice)));
	}

	private function checkDiscount(
		array $productRow,
		?array $originalProductRow,
		?string $currencyId,
		?string $originalCurrencyId
	): bool
	{
		if ($originalProductRow)
		{
			if (
				$currencyId
				&& $originalCurrencyId
				&& $originalCurrencyId !== $currencyId
				&& (int)$productRow['DISCOUNT_TYPE_ID'] !== (int)$originalProductRow['DISCOUNT_TYPE_ID']
				&& $productRow['DISCOUNT_TYPE_ID'] === Discount::MONETARY
			)
			{
				$calculator = new Calculator($originalProductRow);
				$calculator->calculateDiscountType(Discount::MONETARY);
				$calculatedOriginalProductRow = $calculator->getProduct();

				return (float)$calculatedOriginalProductRow['DISCOUNT_RATE'] === (float)$productRow['DISCOUNT_RATE'];
			}
			elseif (
				(float)$productRow['DISCOUNT_RATE'] !== (float)$originalProductRow['DISCOUNT_RATE']
				|| (int)$productRow['DISCOUNT_TYPE_ID'] !== (int)$originalProductRow['DISCOUNT_TYPE_ID']
			)
			{
				return false;
			}
		}
		else
		{
			if (
				(
					isset($productRow['DISCOUNT_RATE'])
					&& (float)$productRow['DISCOUNT_RATE'] !== 0.0
				)
				|| (
					isset($productRow['DISCOUNT_TYPE_ID'])
					&& (int)$productRow['DISCOUNT_TYPE_ID'] !== Discount::PERCENTAGE
				)
			)
			{
				return false;
			}
		}

		return true;
	}

	protected function getCatalogPrice(?string $currencyId, ?int $productId, ?int $priceTypeId = null): ?float
	{
		if (!$productId)
		{
			return null;
		}

		$filter = ['=PRODUCT_ID' => $productId];
		if ($priceTypeId)
		{
			$filter['=CATALOG_GROUP_ID'] = $priceTypeId;
		}
		else
		{
			$filter['=CATALOG_GROUP_ID'] = \Bitrix\Catalog\GroupTable::getBasePriceTypeId();
		}

		$price = \Bitrix\Catalog\PriceTable::getRow([
			'select' => [
				'PRICE',
				'CURRENCY',
			],
			'filter' => $filter,
		]);
		if (!$price)
		{
			return null;
		}

		if (!$currencyId || $price['CURRENCY'] === $currencyId)
		{
			return (float)$price['PRICE'];
		}

		return \CCrmCurrency::ConvertMoney($price['PRICE'], $price['CURRENCY'], $currencyId);
	}

	private function getDefaultProductRow(float $price): array
	{
		return [
			'TAX_INCLUDED' => 'N',
			'TAX_RATE' => null,
			'PRICE_NETTO' => $price,
			'PRICE_BRUTTO' => $price,
			'PRICE' => $price,
			'PRICE_EXCLUSIVE' => $price,
			'DISCOUNT_SUM' => 0.0,
			'DISCOUNT_RATE' => 0.0,
			'DISCOUNT_TYPE_ID' => Discount::PERCENTAGE,
		];
	}

	private function comparePrices(array $productRow, ?array $productRowForCompare): bool
	{
		if (!$productRowForCompare)
		{
			return true;
		}

		$calculator = new Calculator($productRowForCompare);
		if (isset($productRow['TAX_INCLUDED']))
		{
			$calculator->calculateTaxIncluded($productRow['TAX_INCLUDED']);
		}
		if (isset($productRow['TAX_RATE']))
		{
			$calculator->calculateTaxRate((float)$productRow['TAX_RATE']);
		}
		if (isset($productRow['DISCOUNT_TYPE_ID']))
		{
			$calculator->calculateDiscountType((int)$productRow['DISCOUNT_TYPE_ID']);
		}
		if (isset($productRow['DISCOUNT_RATE']) && (int)$productRow['DISCOUNT_TYPE_ID'] === Discount::PERCENTAGE)
		{
			$calculator->calculateDiscount((float)$productRow['DISCOUNT_RATE']);
		}
		if (isset($productRow['DISCOUNT_SUM']) && (int)$productRow['DISCOUNT_TYPE_ID'] === Discount::MONETARY)
		{
			$calculator->calculateDiscount((float)$productRow['DISCOUNT_SUM']);
		}
		$calculatedProductRowForCompare = $calculator->getProduct();

		foreach ($calculatedProductRowForCompare as $key => $value)
		{
			if (!isset($productRow[$key]))
			{
				continue;
			}
			if ($key === 'TAX_INCLUDED')
			{
				if ($productRow[$key] !== $value)
				{
					return false;
				}
			}
			elseif ($key === 'DISCOUNT_TYPE_ID')
			{
				if ((int)$productRow[$key] !== $value)
				{
					return false;
				}
			}
			elseif ((float)$productRow[$key] !== $value)
			{
				return false;
			}
		}

		return true;
	}
}
