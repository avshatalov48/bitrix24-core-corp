<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Mapper;

use Bitrix\Crm\Entity\FieldDataProvider;
use Bitrix\Main\UserField\Types\BooleanType;
use Bitrix\Main\UserField\Types\DateType;
use Bitrix\Main\UserField\Types\DoubleType;
use Bitrix\Main\UserField\Types\EnumType;
use Bitrix\Main\UserField\Types\IntegerType;
use Bitrix\Main\UserField\Types\StringType;
use Bitrix\Main\UserField\Types\UrlType;

final class UserFieldsMapper extends AbstractFieldsMapper
{
	public const TYPE_ID = 'UF';

	private const DEFAULT_LIMIT = 50;
	private const ALLOWED_TYPES = [
		StringType::USER_TYPE_ID,
		IntegerType::USER_TYPE_ID,
		DoubleType::USER_TYPE_ID,
		BooleanType::USER_TYPE_ID,
		DateType::USER_TYPE_ID,
		DateType::USER_TYPE_ID,
		UrlType::USER_TYPE_ID,
		EnumType::USER_TYPE_ID,
		'money',
		'address',
	];

	public function map(array $item): array
	{
		$fields = $this->filterFields($item);
		if (empty($fields))
		{
			return [];
		}

		$userFieldsRaw = (new FieldDataProvider($this->entityTypeId))->getFieldData();
		if (empty($userFieldsRaw))
		{
			return [];
		}

		$counter = 0;
		$result = [];
		foreach ($fields as $key => $value)
		{
			$field = $userFieldsRaw[$key] ?? null;
			if (
				isset($field)
				&& in_array($field['TYPE'], self::ALLOWED_TYPES, true)
			)
			{

				$result[$field['NAME']] = $this->normalizeValue($field, $value);
				$counter++;
			}

			if ($counter >= self::DEFAULT_LIMIT)
			{
				break;
			}
		}

		return $result;
	}

	private function normalizeValue(array $field, mixed $input): mixed
	{
		$type = $field['TYPE'] ?? null;
		$isMultiple = $field['MULTIPLE'] ?? false;
		if ($type === null)
		{
			return $input;
		}

		if ($type === StringType::USER_TYPE_ID)
		{
			return $isMultiple
				?  array_map(fn($item) => $this->normalizeText($item), $input)
				:  $this->normalizeText($input)
			;
		}

		if ($type === BooleanType::USER_TYPE_ID)
		{
			return $this->normalizeBoolean($input);
		}

		if ($type === EnumType::USER_TYPE_ID && is_array($field['VALUES']))
		{
			return $isMultiple
				?  array_map(static fn($item) => ($field['VALUES'][$item] ?? null), $input)
				: ($field['VALUES'][$input] ?? null)
			;
		}

		if ($type === 'address')
		{
			$normalizer = static function(string $value): string
			{
				$arr = explode('|', $value);

				return $arr[0] ?? $value;
			};

			return $isMultiple
				?  array_map(static fn($item) => $normalizer($item), $input)
				: $normalizer($input)
			;
		}

		return $input;
	}
}
