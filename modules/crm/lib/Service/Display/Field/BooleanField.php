<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Localization\Loc;

class BooleanField extends BaseSimpleField
{
	protected const TYPE = 'boolean';

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		$result = ($this->isMultiple() ? [] : '');

		foreach ((array)$fieldValue as $value)
		{
			$yesNoValue =
				($value === true || $value === 'Y' || $value === 1 || $value === '1')
					? Loc::getMessage('MAIN_YES')
					: Loc::getMessage('MAIN_NO');

			if (!$this->isMultiple())
			{
				return $yesNoValue;
			}

			$result[] = $yesNoValue;
		}

		return $result;
	}

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		return $this->getFormattedValueForKanban($fieldValue, $itemId, $displayOptions);
	}
}
