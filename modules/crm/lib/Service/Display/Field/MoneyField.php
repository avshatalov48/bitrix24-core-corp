<?php


namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;

class MoneyField extends BaseSimpleField
{
	protected const TYPE = 'money';

	protected function getFormattedValueForExport($fieldValue, int $itemId, Options $displayOptions)
	{
		$fieldValue = is_array($fieldValue) ? $fieldValue : [$fieldValue];
		return implode($displayOptions->getMultipleFieldsDelimiter(), $fieldValue);
	}
}
