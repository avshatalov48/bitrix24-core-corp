<?php

namespace Bitrix\Crm;

class Accounting
{
	/**
	 * Returns person type id based on data contained in the $item
	 *
	 * @param Item $item
	 *
	 * @return int - if a suitable person type was not found, returns 0
	 */
	public static function resolvePersonTypeId(Item $item): int
	{
		$personTypes = \CCrmPaySystem::getPersonTypeIDs();

		if (isset($personTypes['COMPANY']) && ($item->getCompanyId() > 0))
		{
			return (int)$personTypes['COMPANY'];
		}
		if (isset($personTypes['CONTACT']))
		{
			return (int)$personTypes['CONTACT'];
		}

		return 0;
	}

	/**
	 * Returns original price before tax
	 *
	 * @param float $priceWithTax
	 * @param float $taxRate
	 *
	 * @return float
	 */
	public static function calculatePriceWithoutTax(float $priceWithTax, float $taxRate): float
	{
		return \CCrmProductRow::CalculateExclusivePrice($priceWithTax, $taxRate);
	}

	/**
	 * Applies tax with $taxRate to the price and returns its new value
	 *
	 * @param float $priceWithoutTax
	 * @param float $taxRate
	 *
	 * @return float
	 */
	public static function calculatePriceWithTax(float $priceWithoutTax, float $taxRate): float
	{
		return \CCrmProductRow::CalculateInclusivePrice($priceWithoutTax, $taxRate);
	}
}
