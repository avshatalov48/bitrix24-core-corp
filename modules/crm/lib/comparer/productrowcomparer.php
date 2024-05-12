<?php
namespace Bitrix\Crm\Comparer;

class ProductRowComparer extends ComparerBase
{
	public function areEquals(array $a, array $b): bool
	{
		return (self::areFieldsEquals($a, $b, 'PRODUCT_NAME')
			&& self::areFieldsEquals($a, $b, 'PRODUCT_ID')
			&& self::areFieldsEquals($a, $b, 'QUANTITY')
			&& self::areFieldsEquals($a, $b, 'MEASURE_CODE')
			&& self::areFieldsEquals($a, $b, 'MEASURE_NAME')
			&& self::areFieldsEquals($a, $b, 'PRICE')
			&& self::areFieldsEquals($a, $b, 'PRICE_EXCLUSIVE')
			&& self::areFieldsEquals($a, $b, 'PRICE_NETTO')
			&& self::areFieldsEquals($a, $b, 'PRICE_BRUTTO')
			&& self::areFieldsEquals($a, $b, 'DISCOUNT_TYPE_ID')
			&& self::areFieldsEquals($a, $b, 'DISCOUNT_RATE')
			&& self::areFieldsEquals($a, $b, 'DISCOUNT_SUM')
			&& self::areFieldsEquals($a, $b, 'TAX_INCLUDED')
			&& self::areFieldsEquals($a, $b, 'CUSTOMIZED')
			&& self::areFieldsEquals($a, $b, 'SORT')
			&& self::areFieldsEquals($a, $b, 'RESERVE_ID')
			&& self::areFieldsEquals($a, $b, 'RESERVE_QUANTITY')
			&& self::areFieldsEquals($a, $b, 'DATE_RESERVE_END')
			&& self::areFieldsEquals($a, $b, 'STORE_ID')
			&& self::areFieldsEquals($a, $b, 'TAX_RATE')
		);
	}

	public static function areFieldsEquals(array $left, array $right, $name): bool
	{
		if ($name === 'TAX_RATE')
		{
			// TAX_RATE is nullable, and null - is a valid value ('NO VAT') that is different from 0 ('VAT 0')

			$taxRateLeft = $left[$name] ?? null;
			if ($taxRateLeft !== null)
			{
				$taxRateLeft = (float)$taxRateLeft;
			}

			$taxRateRight = $right[$name] ?? null;
			if ($taxRateRight !== null)
			{
				$taxRateRight = (float)$taxRateRight;
			}

			return $taxRateLeft === $taxRateRight;
		}

		return parent::areFieldsEquals($left, $right, $name);
	}
}
