<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Display\Options;

class CrmCurrencyField extends BaseSimpleField
{
	public const TYPE = 'crm_currency';

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		if ($this->isMultiple())
		{
			$result = [];
			foreach ((array)$fieldValue as $value)
			{
				$result[] = $this->getCurrencyName($value);
			}
			return $result;
		}

		return $this->getCurrencyName($fieldValue);
	}

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		return $this->getFormattedValueForKanban($fieldValue, $itemId, $displayOptions);
	}

	protected function getCurrencyName($currency)
	{
		return (\CCrmCurrency::GetByID($currency)['FULL_NAME'] ?? $currency);
	}
}
