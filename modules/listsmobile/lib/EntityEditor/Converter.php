<?php

namespace Bitrix\ListsMobile\EntityEditor;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class Converter
{
	private array $originalValues;
	private array $originalFields;
	private array $convertedValues = [];
	private array $convertedFields = [];
	private bool $isConvertedToWeb = false;
	private bool $isConvertedToMobile = false;

	public function __construct(array $values, array $fields)
	{
		$this->originalValues = $values;
		$this->originalFields = $fields;

		$this->convertedValues['ID'] = $values['ID'] ?? 0;
		$this->convertedValues['IBLOCK_ID'] = (int)($values['IBLOCK_ID'] ?? 0);
		$this->convertedValues['IBLOCK_SECTION_ID'] = (int)($values['IBLOCK_SECTION_ID'] ?? 0);
	}

	public function toMobile(): static
	{
		if ($this->isConvertedToMobile)
		{
			return $this;
		}

		foreach ($this->originalFields as $fieldId => $property)
		{
			$type = $property['TYPE'];

			if ($type === 'S:ECrm')
			{
				$property['FULL_CONVERTED_VALUE'] = [];
				$property['FULL_DEFAULT_CONVERTED_VALUE'] = [];
			}
			$this->convertedFields[$fieldId] = $this->toMobileDefaultValue($property, $fieldId);

			if (!array_key_exists($fieldId, $this->originalValues))
			{
				continue;
			}

			$isMultiple = $property['MULTIPLE'] === 'Y';

			if (!str_starts_with($fieldId, 'PROPERTY_'))
			{
				if (in_array($type, ['ACTIVE_FROM', 'DATE_CREATE', 'TIMESTAMP_X', 'ACTIVE_TO'], true))
				{
					$this->convertedValues[$fieldId] = $this->toMobileDate($this->originalValues[$fieldId]);

					continue;
				}

				$this->convertedValues[$fieldId] = $this->originalValues[$fieldId];

				continue;
			}

			$this->convertedValues[$fieldId] = $isMultiple ? [] : null;
			foreach ($this->originalValues[$fieldId] as $key => $value)
			{
				if ($type === 'S:Date' || $type === 'S:DateTime')
				{
					$value = $this->toMobileDate($value);
				}

				if ($type === 'S:HTML')
				{
					$value = $this->toMobileHtml($value);
				}

				if ($type === 'S:Money')
				{
					$value = $this->toMobileMoney($value);
				}

				if ($type === 'S:ECrm')
				{
					if (!empty($value) && Loader::includeModule('crm'))
					{
						$crmTypes = array_keys($property['USER_TYPE_SETTINGS'] ?? [], 'Y', true);
						$firstType = isset($crmTypes[0]) ? \CCrmOwnerTypeAbbr::ResolveByTypeName($crmTypes[0]) : '';

						[$value, $fullValue] = $this->toMobileECrm($value, $firstType);
						$this->convertedFields[$fieldId]['FULL_CONVERTED_VALUE'][] = $fullValue;
					}
					else
					{
						$value = null;
					}
				}

				if ($type === 'S:DiskFile' && is_array($value))
				{
					$value = $value[array_key_first($value)];
				}

				if ($isMultiple)
				{
					$this->convertedValues[$fieldId][] = $value;
				}
				elseif ($type === 'F')
				{
					$this->convertedValues[$fieldId] = [$value];
					break;
				}
				else
				{
					$this->convertedValues[$fieldId] = $value;
					break;
				}
			}

			if ($isMultiple)
			{
				$this->convertedValues[$fieldId] = array_values(array_filter($this->convertedValues[$fieldId]));
			}
		}

		$this->isConvertedToWeb = false;
		$this->isConvertedToMobile = true;

		return $this;
	}

	public function getConvertedValues(): array
	{
		if ($this->isConvertedToWeb || $this->isConvertedToMobile)
		{
			return $this->convertedValues;
		}

		return $this->originalValues;
	}

	public function getConvertedFields(): array
	{
		if ($this->isConvertedToWeb || $this->isConvertedToMobile)
		{
			return $this->convertedFields;
		}

		return $this->originalFields;
	}

	private function toMobileDefaultValue(array $property, $fieldId): array
	{
		$type = $property['TYPE'];

		if ($type === 'S:Money')
		{
			$property['DEFAULT_VALUE'] = $this->toMobileMoney($property['DEFAULT_VALUE'] ?? '');

			return $property;
		}

		if (empty($property['DEFAULT_VALUE']))
		{
			if ($type === 'N:Sequence' && !array_key_exists($fieldId, $this->originalValues))
			{
				$property['DEFAULT_VALUE'] =
					(new \CIBlockSequence($property['IBLOCK_ID'], $property['ID']))->GetNext()
				;
			}

			return $property;
		}

		if ($type === 'ACTIVE_FROM')
		{
			if ($property['DEFAULT_VALUE'] === '=now')
			{
				$property['DEFAULT_VALUE'] = (new DateTime())->getTimestamp();
			}
			if ($property['DEFAULT_VALUE'] === '=today')
			{
				$property['DEFAULT_VALUE'] = (new Date())->getTimestamp();
			}
		}

		if ($type === 'PREVIEW_PICTURE' || $type === 'DETAIL_PICTURE')
		{
			$property['DEFAULT_VALUE'] = '';
		}

		if ($type === 'S:Date' || $type === 'S:DateTime')
		{
			$property['DEFAULT_VALUE'] = $this->toMobileDate($property['DEFAULT_VALUE']);
		}

		if ($type === 'S:HTML')
		{
			$property['DEFAULT_VALUE'] = $this->toMobileHtml($property['DEFAULT_VALUE']);
		}

		if ($type === 'S:ECrm')
		{
			if (Loader::includeModule('crm'))
			{
				$crmTypes = array_keys($property['USER_TYPE_SETTINGS'] ?? [], 'Y', true);
				$firstType = isset($crmTypes[0]) ? \CCrmOwnerTypeAbbr::ResolveByTypeName($crmTypes[0]) : '';

				$defaultValue = (array)($property['DEFAULT_VALUE'] ?? '');
				$property['DEFAULT_VALUE'] = [];
				foreach ($defaultValue as $value)
				{
					[$value, $fullValue] = $this->toMobileECrm($value ?? '', $firstType);
					$property['DEFAULT_VALUE'][] = $value;
					$property['FULL_DEFAULT_CONVERTED_VALUE'][] = $fullValue;
				}

				$property['DEFAULT_VALUE'] = array_values(array_filter($property['DEFAULT_VALUE']));
			}
			else
			{
				$property['DEFAULT_VALUE'] = null;
			}
		}

		return $property;
	}

	private function toMobileDate($value): ?int
	{
		return (
			is_string($value) && DateTime::isCorrect($value)
				? (DateTime::createFromUserTime($value))->disableUserTime()->getTimestamp()
				: null
		);
	}

	private function toMobileMoney($value): array
	{
		$amount = '';
		$currency = '';

		if (!empty($value) && is_string($value))
		{
			[$amount, $currency] = explode('|', $value);
		}

		return [
			'amount' => $amount,
			'currency' => $currency,
		];
	}

	private function toMobileHtml($value): string
	{
		if (is_array($value) && isset($value['TEXT']))
		{
			$value = (string)$value['TEXT'];
		}

		if (is_string($value))
		{
			return HTMLToTxt(htmlspecialcharsback($value));
		}

		return '';
	}

	private function toMobileECrm($value, $defaultType): array
	{
		$fullValue = null;
		$shortValue = null;

		if (!empty($value) && Loader::includeModule('crm'))
		{
			$parts = explode('_', $value);
			$valueType = count($parts) > 1 ? $parts[0] : $defaultType;
			$valueId = count($parts) > 1 ? $parts[1] : $value;

			$valueTypeId = \CCrmOwnerTypeAbbr::ResolveTypeID($valueType);
			$caption = \CCrmOwnerType::GetCaption($valueTypeId, $valueId);

			if (!empty($caption))
			{
				$isDynamic = \CCrmOwnerType::isPossibleDynamicTypeId($valueTypeId);

				$fullValue = $valueTypeId . ':' . $valueId;
				$shortValue = $isDynamic ? $fullValue : $valueId;
			}
		}

		return [$shortValue, $fullValue];
	}
}
