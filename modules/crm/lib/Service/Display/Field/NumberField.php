<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Display\Options;

class NumberField extends StringField
{
	protected const TYPE = 'number';

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		if ($this->isMultiple())
		{
			return $this->render($displayOptions, $itemId, $fieldValue);
		}

		return $this->renderSingleValue($fieldValue, $itemId, $displayOptions);
	}
}
