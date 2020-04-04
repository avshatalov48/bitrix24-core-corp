<?php

namespace Bitrix\DocumentGenerator\Entity\Sale;

use Bitrix\DocumentGenerator\Entity;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Fuser;

class Basket implements Entity
{
	protected $basket;
	protected $values;

	public function __construct($basketItem, array $options = [])
	{
		if(Loader::includeModule('sale'))
		{
			if($basketItem instanceof \Bitrix\Sale\BasketItem)
			{
				$this->basket = $basketItem;
			}
		}
	}

	/**
	 * Returns list of value names for this entity.
	 *
	 * @return array
	 */
	public function getFields()
	{
		$fields = [];

		if($this->isLoaded())
		{
			$fields = array_keys($this->basket->getFieldValues());
			$fields[] = 'SUM';
		}
		elseif(Loader::includeModule('sale'))
		{
			$fields = \Bitrix\Sale\BasketItem::getAvailableFields();
			$fields[] = 'SUM';
		}

		return $fields;
	}

	/**
	 * Returns value by its name.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getValue($name)
	{
		if($this->basket)
		{
			if(!$this->values)
			{
				$this->prepareValues();
			}
			return($this->values[$name]);
		}

		return '';
	}

	/**
	 * @return boolean
	 */
	public function isLoaded()
	{
		return $this->basket !== null;
	}

	protected function prepareValues()
	{
		$values = $this->basket->getFieldValues();

		if($this->basket->isVatInPrice())
		{
			$basketItemPrice = $this->basket->getPrice();
		}
		else
		{
			$basketItemPrice = $this->basket->getPrice()*(1 + $this->basket->getVatRate());
		}

		$values['QUANTITY'] = roundEx($values['QUANTITY'], SALE_VALUE_PRECISION);
		$values['MEASURE_NAME'] ? $values['MEASURE_NAME'] : Loc::getMessage('SALE_HPS_BILL_BASKET_MEASURE_DEFAULT');
		$values['PRICE'] = SaleFormatCurrency($values['PRICE'], $values['CURRENCY'], true);
		$values['VAT_RATE'] = roundEx($values['VAT_RATE'] * 100, SALE_VALUE_PRECISION).'%';
		$values['SUM'] = SaleFormatCurrency($basketItemPrice * $values['QUANTITY'], $values['CURRENCY'], true);
		$values['DISCOUNT_PRICE'] = SaleFormatCurrency($values['DISCOUNT_PRICE'], $values['CURRENCY'], true);

		$this->values = $values;
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function hasAccess($userId)
	{
		if($this->isLoaded())
		{
			return Fuser::getIdByUserId($userId) == $this->basket->getFUserId();
		}

		return false;
	}

	public function getFieldTitle($fieldName)
	{
		return $fieldName;
	}
}