<?php


namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;
use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Main\Loader;

class AddressField extends BaseSimpleField
{
	protected const TYPE = 'address';
	protected static ?bool $filemanIncluded = null;

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		$displayOptions->setShowOnlyText(true);

		$this->prepareValueForKanban($fieldValue);

		$result = parent::getFormattedValueForKanban($fieldValue, $itemId, $displayOptions);

		if ($this->isMultiple())
		{
			$result = array_map('htmlspecialcharsback', $result);
		}
		else
		{
			$result = htmlspecialcharsback($result);
		}

		return $result;
	}

	protected function prepareValueForKanban(&$fieldValue): void
	{
		if ($this->isMultiple())
		{
			$fieldValue = array_map([$this, 'stripCoordinates'], $fieldValue);
		}
		else
		{
			$fieldValue = $this->stripCoordinates($fieldValue);
		}
	}

	protected function stripCoordinates(string $address): string
	{
		$parsedAddress = $this->prepareValue($address);
		return (empty($parsedAddress[0]) ? $address : $parsedAddress[0]);
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		if ($this->isMultiple())
		{
			$results = [];

			$fieldValue = (is_array($fieldValue) ? $fieldValue : [$fieldValue]);
			foreach ($fieldValue as $value)
			{
				$results[] = $this->prepareValue($value);
			}

			return [
				'value' => $results,
			];
		}

		return [
			'value' => $this->prepareValue($fieldValue),
		];
	}

	protected function prepareValue($value): array
	{
		if (self::$filemanIncluded === null)
		{
			self::$filemanIncluded = Loader::includeModule('fileman');
		}

		if (!self::$filemanIncluded)
		{
			return [$value];
		}

		return AddressType::parseValue($value);
	}
}
