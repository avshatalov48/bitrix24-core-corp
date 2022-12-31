<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Localization\Loc;

class BooleanField extends BaseSimpleField
{
	public const TYPE = 'boolean';

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		$result = ($this->isMultiple() ? [] : '');

		foreach ((array)$fieldValue as $value)
		{
			$yesNoValue = $this->prepareValue($value);

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

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$values = [];

		foreach ((array)$fieldValue as $value)
		{
			$values[] = $this->isChecked($value);
		}

		return [
			'value' => $this->isMultiple() ? $values : reset($values),
			'config' => [
				'descriptionYes' => $this->getYesMessage(),
				'descriptionNo' => $this->getNoMessage(),
			],
		];
	}

	protected function isChecked($value): bool
	{
		return ($value === true || $value === 'Y' || $value === 1 || $value === '1');
	}

	/**
	 * @param $value
	 * @return string
	 */
	protected function prepareValue($value): string
	{
		return $this->isChecked($value) ? $this->getYesMessage() : $this->getNoMessage();
	}

	private function getYesMessage(): string
	{
		$userFieldParams = $this->getUserFieldParams();

		if (!empty($userFieldParams['SETTINGS']['LABEL']) && is_array($userFieldParams['SETTINGS']['LABEL']))
		{
			$yesMessage = (string)($userFieldParams['SETTINGS']['LABEL'][1] ?? '');
			if ($yesMessage !== '')
			{
				return $yesMessage;
			}
		}

		return Loc::getMessage('CRM_FIELD_BOOLEAN_YES');
	}

	private function getNoMessage(): string
	{
		$userFieldParams = $this->getUserFieldParams();

		if (!empty($userFieldParams['SETTINGS']['LABEL']) && is_array($userFieldParams['SETTINGS']['LABEL']))
		{
			$yesMessage = (string)($userFieldParams['SETTINGS']['LABEL'][0] ?? '');
			if ($yesMessage !== '')
			{
				return $yesMessage;
			}
		}

		return Loc::getMessage('CRM_FIELD_BOOLEAN_NO');
	}
}
