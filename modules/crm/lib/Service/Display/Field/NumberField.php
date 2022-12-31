<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Display\Options;

class NumberField extends StringField
{
	public const TYPE = 'number';

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		if ($this->isMultiple())
		{
			return $this->render($displayOptions, $itemId, $fieldValue);
		}

		return $this->renderSingleValue($fieldValue, $itemId, $displayOptions);
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$result = parent::getFormattedValueForMobile($fieldValue, $itemId, $displayOptions);

		$onlyInteger = true;
		if (is_array($result['value']))
		{
			foreach ($result['value'] as $value)
			{
				if ($this->isFloat($value))
				{
					$onlyInteger = false;
					break;
				}
			}
		}
		elseif ($this->isFloat($result['value']))
		{
			$onlyInteger = false;
		}

		if (!$onlyInteger)
		{
			$result['config']['precision'] = 2;
		}

		return $result;
	}

	private function isFloat(string $value): bool
	{
		return (is_numeric($value) && strpos($value, '.') !== false);
	}

}
