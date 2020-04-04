<?php
namespace Bitrix\Crm\Comparer;

class ProductRowComparer extends ComparerBase
{
	public function areEquals(array $a, array $b)
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
		);
	}
}