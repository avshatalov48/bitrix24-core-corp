<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowSynchronizer;

use Bitrix\Main\Result;
use Bitrix\Sale\BasketItem;

/**
 * An object for correctly filling the basket item.
 *
 * When filling in, it performs type casting and checks for changes in values.
 */
class BasketItemFiller
{
	private BasketItem $basketItem;
	private Result $result;
	private bool $isChanged;

	/**
	 * @param BasketItem $basketItem
	 */
	public function __construct(BasketItem $basketItem)
	{
		$this->basketItem = $basketItem;
	}

	/**
	 * Fills the basket item with the specified values.
	 *
	 * The result of the work can be obtained using the methods `getChanged` and `getResult'.
	 *
	 * @param array $fields
	 *
	 * @return void
	 */
	public function fill(array $fields): void
	{
		$this->result = new Result();
		$this->isChanged = false;

		$types = [
			'PRICE' => 'floatval',
			'BASE_PRICE' => 'floatval',
			'DISCOUNT_PRICE' => 'floatval',
			'WEIGHT' => 'floatval',
			'QUANTITY' => 'floatval',
			'VAT_RATE' => '?floatval',
			'PRODUCT_ID' => 'intval',
			'SORT' => 'intval',
			'MEASURE_CODE' => 'intval',
			'SET_PARENT_ID' => 'intval',
			'PRICE_TYPE_ID' => 'intval',
			'PRODUCT_PRICE_ID' => 'intval',
			'TYPE' => 'intval',
		];

		$changedFields = [];
		$fields = $this->clearBasketItemExtraFields($fields);
		foreach ($fields as $name => $value)
		{
			$currentValue = $this->basketItem->getField($name);
			$isFirstSetValue = is_null($currentValue) && isset($value);

			$type = $types[$name] ?? null;
			if ($type)
			{
				$isNullable = ($type[0] === '?');
				$typeFn = $isNullable ? substr($type, 1) : $type;

				$value = ($isNullable && is_null($value)) ? null : $typeFn($value);
				$currentValue = ($isNullable && is_null($currentValue)) ? null : $typeFn($currentValue);
			}

			// convert to string, for correct comparing float values
			if ((string)$value !== (string)$currentValue || $isFirstSetValue)
			{
				$changedFields[$name] = $value;
				$this->isChanged = true;
			}
		}

		if (!empty($changedFields))
		{
			$setResult = $this->basketItem->setFields($changedFields);
			if (!$setResult->isSuccess())
			{
				foreach ($changedFields as $name => $value)
				{
					if ($this->basketItem->getField($name) === $value)
					{
						continue;
					}

					$setResult = $this->basketItem->setField($name, $value);
					if (!$setResult->isSuccess())
					{
						$this->result->addErrors($setResult->getErrors());
						$this->basketItem->setFieldNoDemand($name, $value);
					}
				}
			}
		}
	}

	/**
	 * Deleting fields that are not available for the basket item.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	private function clearBasketItemExtraFields(array $fields): array
	{
		unset(
			$fields['MODULE'],
		);

		$availableFields = BasketItem::getAllFields();

		return array_filter(
			$fields,
			static fn($key) => in_array($key, $availableFields, true),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * The result of the work.
	 *
	 * Contains errors in filling the basket item.
	 *
	 * @return Result
	 */
	public function getResult(): Result
	{
		return $this->result;
	}

	/**
	 * Has the basket item changed or not?
	 *
	 * @return bool
	 */
	public function getChanged(): bool
	{
		return $this->isChanged;
	}
}
