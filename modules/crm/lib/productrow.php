<?php

namespace Bitrix\Crm;

use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

class ProductRow extends EO_ProductRow
{
	protected const REFERENCE_FIELD_NAME = 'IBLOCK_ELEMENT';

	/** @var IBlockElementProxyTable */
	protected static $referenceDataClass = IBlockElementProxyTable::class;

	/** @var EO_IBlockElementProxy */
	protected $reference;

	/**
	 * Factory method for creation of a new instance of ProductRow
	 *
	 * @param array $productRow
	 *
	 * @return ProductRow
	 */
	public static function createFromArray(array $productRow): ProductRow
	{
		$filteredValues = array_filter(
			$productRow,
			static function (string $fieldName): bool {

				$entity = ProductRowTable::getEntity();
				if ($entity->hasField($fieldName))
				{
					$field = $entity->getField($fieldName);

					// to avoid exception when trying to set value for an expression field
					return ($field->getTypeMask() === FieldTypeMask::SCALAR);
				}

				return false;
			},
			ARRAY_FILTER_USE_KEY
		);

		// Workaround. If we explicitly set ID = 0 on a new EntityObject,
		// it will break \Bitrix\Main\ORM\Objectify\Collection logic
		if (isset($filteredValues['ID']) && ($filteredValues['ID'] <= 0))
		{
			unset($filteredValues['ID']);
		}

		return new static($filteredValues);
	}

	/**
	 * Get an array with data from this EntityObject
	 *
	 * @return array Compatible with CCrmProductRow
	 */
	public function toArray(): array
	{
		$result = $this->collectValues(Values::ALL, FieldTypeMask::SCALAR);

		foreach ($result as &$value)
		{
			if (is_bool($value))
			{
				$value = $value ? 'Y' : 'N';
			}
		}

		return $result;
	}

	public function isNew(): bool
	{
		return (!($this->getId() > 0));
	}

	/**
	 * Normalize this product
	 *
	 * @param string $currencyId - id of a currency that set in an owner Item (quote, dynamic, etc.)
	 *
	 * @return Result
	 */
	public function normalize(string $currencyId): Result
	{
		$normalizationResult = new Result();

		$this->normalizeProductName();
		$this->normalizeMeasure($normalizationResult);
		$this->normalizePriceExclusive();
		$this->normalizeDiscount($normalizationResult);
		$this->normalizePriceNetto();
		$this->normalizePriceBrutto();
		$this->normalizePriceAccount($currencyId);

		return $normalizationResult;
	}

	protected function normalizeMeasure(Result $result): void
	{
		if ($this->isCurrentMeasureValid())
		{
			return;
		}

		if (!$this->isNew())
		{
			$existingMeasureInfo = Measure::getProductMeasures($this->getId())[$this->getId()] ?? null;
			if (!empty($existingMeasureInfo))
			{
				$this->setMeasure($existingMeasureInfo);

				return;
			}
		}

		$defaultMeasureInfo = Measure::getDefaultMeasure();
		if (!empty($defaultMeasureInfo))
		{
			$this->setMeasure($defaultMeasureInfo);

			return;
		}

		$result->addError(new Error(
			"Invalid measure. Default measure or existing measure in reference was not found. ID = {$this->getId()}"
		));
	}

	protected function isCurrentMeasureValid(): bool
	{
		if (!$this->getMeasureCode() || !$this->getMeasureName())
		{
			return false;
		}

		$measure = Measure::getMeasureByCode($this->getMeasureCode());
		// Such measure doesn't exists. Code is invalid
		if (empty($measure))
		{
			return false;
		}

		return ($measure['SYMBOL'] === $this->getMeasureName());
	}

	protected function setMeasure(array $measureInfo): ProductRow
	{
		$this->setMeasureCode($measureInfo['CODE']);
		$this->setMeasureName($measureInfo['SYMBOL']);

		return $this;
	}

	protected function normalizePriceExclusive(): void
	{
		if (empty($this->getPriceExclusive()) && !empty($this->getPrice()))
		{
			$exclusivePrice = Accounting::calculatePriceWithoutTax((float)$this->getPrice(), (float)$this->getTaxRate());
			$this->setPriceExclusive($exclusivePrice);
		}
	}

	protected function normalizeDiscount(Result $result): void
	{
		if (!Discount::isDefined($this->getDiscountTypeId()))
		{
			$this->setDiscountTypeId(Discount::PERCENTAGE);
			$this->setDiscountRate(0.0);
		}

		if ($this->getDiscountTypeId() === Discount::PERCENTAGE)
		{
			$this->normalizeDiscountForPercentage($result);
		}
		elseif ($this->getDiscountTypeId() === Discount::MONETARY)
		{
			$this->normalizeDiscountForMonetary($result);
		}
	}

	protected function normalizeDiscountForPercentage(Result $result): void
	{
		if (is_null($this->getDiscountRate()))
		{
			$result->addError(new Error(
				'Discount Rate (DISCOUNT_RATE) is required if '
				. "Percentage Discount Type (DISCOUNT_TYPE_ID) is used. ID = {$this->getId()}"
			));
			return;
		}

		if (is_null($this->getDiscountSum()))
		{
			$discountSum = Discount::calculateDiscountSum($this->getPriceExclusive(), $this->getDiscountRate());
			$this->setDiscountSum($discountSum);
		}
	}

	protected function normalizeDiscountForMonetary(Result $result): void
	{
		if (is_null($this->getDiscountSum()))
		{
			$result->addError(new Error(
				'Discount Sum (DISCOUNT_SUM) is required if '
				. "Monetary Discount Type (DISCOUNT_TYPE_ID) is used. ID = {$this->getId()}"
			));
			return;
		}

		if (is_null($this->getDiscountRate()))
		{
			$priceBeforeDiscount = $this->getPriceExclusive() + $this->getDiscountSum();
			$discountRate = Discount::calculateDiscountRate($priceBeforeDiscount, $this->getPriceExclusive());
			$this->setDiscountRate($discountRate);
		}
	}

	protected function normalizePriceNetto(): void
	{
		if (is_null($this->getPriceNetto()))
		{
			$this->setPriceNetto($this->getPriceExclusive() + $this->getDiscountSum());
		}
	}

	protected function normalizePriceBrutto(): void
	{
		if (is_null($this->getPriceBrutto()))
		{
			$priceBrutto = Accounting::calculatePriceWithTax((float)$this->getPriceNetto(), (float)$this->getTaxRate());
			$this->setPriceBrutto($priceBrutto);
		}
	}

	/**
	 * @param string $currencyId - currency of an owner Item (quote, dynamic, etc.)
	 */
	protected function normalizePriceAccount(string $currencyId): void
	{
		$priceAccount = Currency\Conversion::toAccountCurrency($this->getPrice(), $currencyId);
		$this->setPriceAccount($priceAccount);
	}

	protected function normalizeProductName(): void
	{
		$referenceProductName = $this->getProductNameFromReference();
		if (!empty($referenceProductName) && ($this->getProductName() === $referenceProductName))
		{
			$this->setProductName('');
		}
	}

	public function remindActualProductName(): ?string
	{
		return $this->resolveProductName(parent::remindActualProductName());
	}

	public function getProductName(): ?string
	{
		return $this->resolveProductName(parent::getProductName());
	}

	protected function resolveProductName(?string $productName): ?string
	{
		if (!empty($productName))
		{
			return $productName;
		}

		return $this->getCpProductName() ?? $this->getProductNameFromReference();
	}

	protected function getProductNameFromReference(): ?string
	{
		return ($this->getReference() ? $this->getReference()->getName() : null);
	}

	protected function getReference(): ?EntityObject
	{
		if ($this->getProductId() <= 0)
		{
			return null;
		}

		if (!is_null($this->get(static::REFERENCE_FIELD_NAME)))
		{
			return $this->get(static::REFERENCE_FIELD_NAME);
		}

		if (is_null($this->reference))
		{
			$this->reference = static::$referenceDataClass::getList([
				'select' => ['NAME'],
				'filter' => ['=ID' => $this->getProductId()],
			])->fetchObject();
		}

		return $this->reference;
	}
}
