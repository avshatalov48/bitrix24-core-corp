<?php
namespace Bitrix\Crm\Integration\BizProc\FieldType;

use Bitrix\Bizproc\FieldType;

class Money extends UserFieldBase
{
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		$value = parent::extractValue($fieldType, $field, $request);

		if ($value && mb_strpos($value, '|') !== false)
		{
			list($sum, $currency) = explode('|', $value);
			$value = doubleval($sum) . '|' . $currency;
		}

		return $value;
	}

	/** @inheritdoc */
	public static function compareValues($valueA, $valueB)
	{
		if (mb_strpos($valueA, '|') === false || mb_strpos($valueB, '|') === false)
		{
			return parent::compareValues($valueA, $valueB);
		}

		list($sumA, $currencyA) = explode('|', $valueA);
		list($sumB, $currencyB) = explode('|', $valueB);

		$sumA = (double) $sumA;
		$sumB = (double) $sumB;

		if (!$currencyA)
		{
			$currencyA = \CCrmCurrency::GetDefaultCurrencyID();
		}
		if (!$currencyB)
		{
			$currencyB = \CCrmCurrency::GetDefaultCurrencyID();
		}

		if ($currencyA !== $currencyB)
		{
			$sumB = \CCrmCurrency::ConvertMoney($sumB, $currencyB, $currencyA);
		}

		return parent::compareValues($sumA, $sumB);
	}

	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		$formatted = parent::formatValuePrintable($fieldType, $value);
		$formatted = str_replace('&nbsp;', ' ', $formatted);

		return $formatted;
	}
}