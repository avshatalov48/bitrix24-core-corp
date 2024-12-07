<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

class MoneyField extends BaseField
{

	public function getType(): string
	{
		return 'money';
	}

	public function getConfig(): array
	{
		return [];
	}

	protected function convertToMobileType($value): array
	{
		$amount = '';
		$currency = '';

		if (is_string($value) && $value !== '')
		{
			$exploded = explode('|', $value);
			$amount = isset($exploded[0]) ? (double)$exploded[0] : '';
			$currency = $exploded[1] ?? '';
		}

		return ['amount' => $amount, 'currency' => $currency];
	}

	protected function convertToWebType($value): string
	{
		if (is_array($value) && isset($value['currency'], $value['amount']) && !\CBPHelper::isEmptyValue($value['amount']))
		{
			return (double)$value['amount'] . '|' . $value['currency'];
		}

		return '';
	}
}
