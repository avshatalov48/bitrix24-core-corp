<?php

namespace Bitrix\Mobile\Field\Type;

class MoneyField extends BaseField
{
	public const TYPE = 'money';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		if ($this->isMultiple())
		{
			if (!$this->value)
			{
				return $this->value;
			}

			$result = [];

			foreach ($this->value as $value)
			{
				$result[] = $this->parseValue($value);
			}

			return $result;
		}

		return $this->parseValue($this->value);
	}

	/**
	 * @param $fieldValue
	 * @return array
	 */
	protected function parseValue($fieldValue): array
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
				'amount' => $fieldValue['SUM'] ?? null,
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
