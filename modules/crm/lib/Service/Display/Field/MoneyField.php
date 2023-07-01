<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;

class MoneyField extends BaseSimpleField
{
	public const TYPE = 'money';

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		if ($this->isMultiple())
		{
			$result = [];

			if (!is_array($fieldValue))
			{
				$fieldValue = [$fieldValue];
			}

			foreach ($fieldValue as $value)
			{
				$result[] = $this->getValue($value);
			}
		}
		else
		{
			$result = $this->getValue($fieldValue);
		}

		return [
			'value' => $result,
			'config' => [
				'defaultCurrency' => \CCrmCurrency::GetBaseCurrencyID(),
			],
		];
	}

	/**
	 * @param array|string|null $fieldValue
	 * @return array
	 */
	protected function getValue($fieldValue): array
	{
		if (empty($fieldValue))
		{
			return [
				'amount' => '',
				'currency' => '',
			];
		}

		if (is_array($fieldValue))
		{
			return [
				'amount' => $fieldValue['SUM'] ?? '',
				'currency' => $fieldValue['CURRENCY'] ?? '',
			];
		}

		[$amount, $currency] = explode('|', $fieldValue);

		return [
			'amount' => $amount,
			'currency' => $currency,
		];
	}
}
