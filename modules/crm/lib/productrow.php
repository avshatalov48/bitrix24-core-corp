<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Comparer\ProductRowComparer;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Reservation;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

class ProductRow extends EO_ProductRow implements \JsonSerializable
{
	public const ERROR_CODE_NORMALIZATION_MEASURE_INVALID = 'CRM_PRODUCTROW_NORMALIZATION_MEASURE_INVALID';
	public const ERROR_CODE_NORMALIZATION_DISCOUNT_RATE_REQUIRED = 'CRM_PRODUCTROW_NORMALIZATION_DISCOUNT_RATE_REQUIRED';
	public const ERROR_CODE_NORMALIZATION_DISCOUNT_SUM_REQUIRED = 'CRM_PRODUCTROW_NORMALIZATION_DISCOUNT_SUM_REQUIRED';

	protected const REFERENCE_FIELD_NAME = 'IBLOCK_ELEMENT';
	protected const REFERENCE_PRODUCT_ROW_RESERVATION_NAME = 'PRODUCT_ROW_RESERVATION';

	/** @var IBlockElementProxyTable */
	protected static $referenceDataClass = IBlockElementProxyTable::class;

	/** @var EO_IBlockElementProxy */
	protected $reference;

	protected ?Reservation\ProductRowReservation $productRowReservation = null;

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
				return in_array($fieldName, static::getScalarFieldNames(), true);
			},
			ARRAY_FILTER_USE_KEY
		);

		// Workaround. If we explicitly set ID = 0 on a new EntityObject,
		// it will break \Bitrix\Main\ORM\Objectify\Collection logic
		if (isset($filteredValues['ID']) && ($filteredValues['ID'] <= 0))
		{
			unset($filteredValues['ID']);
		}

		$productRowObject = new static($filteredValues);
		$productRowObject->set(
			self::REFERENCE_PRODUCT_ROW_RESERVATION_NAME,
			$productRowObject->createProductReservation($productRow)
		);

		return $productRowObject;
	}

	protected static function getScalarFieldNames(): array
	{
		$entity = ProductRowTable::getEntity();

		$result = [];
		foreach ($entity->getScalarFields() as $scalarField)
		{
			$result[] = $scalarField->getName();
		}

		return $result;
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
		unset($value);

		// product name is a special field and its correct value can't be collected with collectValues
		$result['PRODUCT_NAME'] = $this->getProductName();

		// append reservations fields
		$productReservation = $this->getProductRowReservation();
		if ($productReservation)
		{
			$result += $productReservation->toArray();
		}

		return $result;
	}

	public function jsonSerialize()
	{
		return Container::getInstance()->getProductRowConverter()->toJson($this);
	}

	public function isNew(): bool
	{
		return (!($this->getId() > 0));
	}

	/**
	 * Returns true if this product row contains the same info as the $anotherProductRow.
	 * ID and reference to owner is not taken into account
	 *
	 * @param ProductRow $anotherProductRow
	 * @return bool
	 */
	public function isEqualTo(self $anotherProductRow): bool
	{
		$comparer = new ProductRowComparer();

		return $comparer->areEquals(
			$this->toArray(),
			$anotherProductRow->toArray(),
		);
	}

	/**
	 * Reset all scalar fields of this object
	 *
	 * @return $this
	 */
	public function resetAll(): self
	{
		foreach (static::getScalarFieldNames() as $fieldName)
		{
			$this->reset($fieldName);
		}

		return $this;
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
		if ($this->tryNormalizeCurrentMeasure())
		{
			return;
		}

		if ($this->getProductId() > 0)
		{
			if ($this->trySetMeasureFromReference())
			{
				return;
			}
		}

		if ($this->trySetDefaultMeasure())
		{
			return;
		}

		$result->addError(new Error(
			"Invalid measure. Default measure and measure in reference were not found. ID = {$this->getId()}",
			static::ERROR_CODE_NORMALIZATION_MEASURE_INVALID,
		));
	}

	protected function tryNormalizeCurrentMeasure(): bool
	{
		if (empty($this->getMeasureCode()))
		{
			return false;
		}

		$currentMeasure = Measure::getMeasureByCode($this->getMeasureCode());
		if (empty($currentMeasure))
		{
			//measure with such a code not found, value is invalid
			return false;
		}

		//normalize measure name
		$this->setMeasureName($currentMeasure['SYMBOL']);

		return true;
	}

	protected function trySetMeasureFromReference(): bool
	{
		$arrayOfReferenceMeasures = Measure::getProductMeasures($this->getProductId())[$this->getProductId()] ?? [];
		$referenceMeasure = reset($arrayOfReferenceMeasures);

		if (empty($referenceMeasure))
		{
			return false;
		}

		$this->setMeasureCode($referenceMeasure['CODE']);
		$this->setMeasureName($referenceMeasure['SYMBOL']);

		return true;
	}

	protected function trySetDefaultMeasure(): bool
	{
		$defaultMeasure = Measure::getDefaultMeasure();
		if (empty($defaultMeasure))
		{
			return false;
		}

		$this->setMeasureCode($defaultMeasure['CODE']);
		$this->setMeasureName($defaultMeasure['SYMBOL']);

		return true;
	}

	protected function normalizePriceExclusive(): void
	{
		$exclusivePrice = Container::getInstance()->getAccounting()->calculatePriceWithoutTax(
			(float)$this->getPrice(),
			(float)$this->getTaxRate()
		);

		$this->setPriceExclusive($exclusivePrice);
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
				. "Percentage Discount Type (DISCOUNT_TYPE_ID) is used. ID = {$this->getId()}",
				static::ERROR_CODE_NORMALIZATION_DISCOUNT_RATE_REQUIRED,
			));

			return;
		}

		if ($this->getDiscountRate() === 100.0)
		{
			$discountSum = $this->getDiscountSum();
			if ($discountSum === 0.0 || is_null($discountSum))
			{
				// impossible to calculate discount sum, since price with 100% discount is exactly zero
				$result->addError(new Error(
					'Discount Sum (DISCOUNT_SUM) is required if '
					. 'Percentage Discount Type (DISCOUNT_TYPE_ID) is used and Discount Rate (DISCOUNT_RATE) is 100%. '
					. "ID = {$this->getId()}",
					static::ERROR_CODE_NORMALIZATION_DISCOUNT_SUM_REQUIRED,
				));

				return;
			}
		}
		else
		{
			$discountSum = Discount::calculateDiscountSum($this->getPriceExclusive(), $this->getDiscountRate());
		}

		$this->setDiscountSum($discountSum);
	}

	protected function normalizeDiscountForMonetary(Result $result): void
	{
		if (is_null($this->getDiscountSum()))
		{
			$result->addError(new Error(
				'Discount Sum (DISCOUNT_SUM) is required if '
				. "Monetary Discount Type (DISCOUNT_TYPE_ID) is used. ID = {$this->getId()}",
				static::ERROR_CODE_NORMALIZATION_DISCOUNT_SUM_REQUIRED,
			));
			return;
		}

		$priceBeforeDiscount = $this->getPriceExclusive() + $this->getDiscountSum();
		$discountRate = Discount::calculateDiscountRate($priceBeforeDiscount, $this->getPriceExclusive());

		$this->setDiscountRate($discountRate);
	}

	protected function normalizePriceNetto(): void
	{
		$this->setPriceNetto($this->getPriceExclusive() + $this->getDiscountSum());
	}

	protected function normalizePriceBrutto(): void
	{
		$priceBrutto = Container::getInstance()->getAccounting()->calculatePriceWithTax(
			(float)$this->getPriceNetto(),
			(float)$this->getTaxRate()
		);

		$this->setPriceBrutto($priceBrutto);
	}

	/**
	 * @param string $currencyId - currency of an owner Item (quote, dynamic, etc.)
	 */
	protected function normalizePriceAccount(string $currencyId): void
	{
		$priceAccount = Currency\Conversion::toAccountCurrency($this->getPrice(), $currencyId);

		$this->setPriceAccount($priceAccount);
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
		if (empty($productName) && $this->hasReference())
		{
			return $this->getProductNameFromReference();
		}

		return $productName;
	}

	protected function hasReference(): bool
	{
		return !is_null($this->getReference());
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

	/**
	 * @param array $fields
	 *
	 * @return Reservation\ProductRowReservation|null
	 */
	private function createProductReservation(array $fields): ?Reservation\ProductRowReservation
	{
		$productRowReservation = Reservation\ProductRowReservationFactory::createFromArray($this, $fields);
		if (!$productRowReservation)
		{
			return null;
		}

		// mark as remove if store set empty. Don't delete if not setted, may be it is partial update of reserve.
		if ($productRowReservation->hasStoreId() && empty($productRowReservation->getStoreId()))
		{
			if (!$productRowReservation->isNew())
			{
				$productRowReservation->delete();
			}

			return null;
		}

		if ($productRowReservation->isNew())
		{
			$productRowReservation->set(Reservation\ProductRowReservation::PRODUCT_ROW_NAME, $this);
		}

		return $productRowReservation;
	}
}
