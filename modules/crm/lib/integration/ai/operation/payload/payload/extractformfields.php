<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Payload;

use Bitrix\Crm\Entity\FieldDataProvider;
use Bitrix\Crm\Integration\AI\Operation\Payload\CalcMarkersInterface;
use Bitrix\Crm\Integration\AI\Operation\Payload\PayloadInterface;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField\Types\DateType;
use Bitrix\Main\UserField\Types\EnumType;

final class ExtractFormFields extends AbstractPayload implements CalcMarkersInterface
{
	public static function getAllSuitableFields(int $entityTypeId, string $scope = Context::SCOPE_AI): array
	{
		return (new FieldDataProvider($entityTypeId, $scope))->getFieldData();
	}

	public static function prepareValue(array $data, mixed $value): mixed
	{
		if (
			isset($value)
			&& $data['TYPE'] === EnumType::USER_TYPE_ID
			&& is_array($data['VALUES'])
		)
		{
			$values = array_map('mb_strtolower', $data['VALUES']);
			$flipped = array_flip($values);
			if ($data['MULTIPLE'] && is_array($value))
			{
				return array_map(
					static fn($item) => is_string($item)
						? $flipped[mb_strtolower($item)] ?? null
						: null,
					$value
				);
			}

			if (is_array($value))
			{
				$firstVal = array_values($value)[0];

				return is_string($firstVal)
					? $flipped[mb_strtolower($firstVal)] ?? null
					: null
				;
			}

			if (is_string($value))
			{
				return $flipped[mb_strtolower($value)] ?? null;
			}

			return null;
		}

		if (
			isset($value)
			&& $data['TYPE'] === DateType::USER_TYPE_ID
		)
		{
			$inputFormat = 'd.m.Y'; // CoPilot return value format DD.MM.YYYY (see 'extract_form_fields' prompt)
			if ($data['MULTIPLE'] && is_array($value))
			{
				return array_map(
					static fn($item) =>is_string($item)
						? DateTime::tryParse($item, $inputFormat)
						: null,
					$value
				);
			}

			return is_string($value)
				? DateTime::tryParse($value, $inputFormat)
				: null
			;
		}

		return $value;
	}

	public function getPayloadCode(): string
	{
		return 'extract_form_fields';
	}
	
	public function setMarkers(array $markers): PayloadInterface
	{
		$this->markers = array_merge($markers, $this->calcMarkers());
		
		return $this;
	}

	public function calcMarkers(): array
	{
		// sent to AI all available fields, regardless of user
		$fieldData = self::getAllSuitableFields($this->identifier->getEntityTypeId());

		return [
			'current_day' => $this->getCurrentDay(),
			'current_month' => $this->getCurrentMonth(),
			'current_year' => $this->getCurrentYear(),
			'fields' => $this->getFields($fieldData),
			'enum_fields_values' =>  $this->getEnumFieldsValues($fieldData),
		];
	}

	private function getFields(array $fieldData): array
	{
		$fields = [
			// unallocated data
			'comment' => 'list[string]',
		];

		foreach ($fieldData as $fieldDescription)
		{
			$type = $fieldDescription['MULTIPLE']
				? 'list[' . $fieldDescription['TYPE'] . ']'
				: $fieldDescription['TYPE']
			;
			
			$fields[$fieldDescription['NAME']] = "$type or null";
		}

		return $fields;
	}

	private function getEnumFieldsValues(array $fieldData): ?array
	{
		$enumFields = array_filter(
			$fieldData,
			static fn(array $data) => $data['TYPE'] === EnumType::USER_TYPE_ID
		);

		if (!$enumFields)
		{
			return null;
		}

		$result = [];
		foreach ($enumFields as $row)
		{
			$result[$row['NAME']] = array_map('mb_strtolower', array_values($row['VALUES']));
		}

		return $result;
	}
}
