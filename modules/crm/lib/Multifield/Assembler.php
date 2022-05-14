<?php

namespace Bitrix\Crm\Multifield;

final class Assembler
{
	public static function extractOwnerId(array $row): ?int
	{
		if (array_key_exists('ELEMENT_ID', $row))
		{
			return (int)$row['ELEMENT_ID'];
		}

		return null;
	}

	public static function valueByDatabaseRow(array $row): Value
	{
		$value = new Value();

		if (isset($row['ID']))
		{
			$value->setId((int)$row['ID']);
		}

		if (isset($row['TYPE_ID']))
		{
			$value->setTypeId((string)$row['TYPE_ID']);
		}

		if (isset($row['VALUE_TYPE']))
		{
			$value->setValueType((string)$row['VALUE_TYPE']);
		}

		if (isset($row['VALUE']))
		{
			$value->setValue((string)$row['VALUE']);
		}

		return $value;
	}

	public static function databaseRowByValue(Value $value): array
	{
		$result = [];

		if (!is_null($value->getId()))
		{
			$result['ID'] = $value->getId();
		}

		if (!is_null($value->getTypeId()))
		{
			$result['TYPE_ID'] = $value->getTypeId();
		}

		if (!is_null($value->getValueType()))
		{
			$result['VALUE_TYPE'] = $value->getValueType();
		}

		if (!is_null($value->getValue()))
		{
			$result['VALUE'] = $value->getValue();
		}

		return $result;
	}

	public static function valueByArray(array $array): Value
	{
		$value = new Value();

		if (isset($array['ID']) && is_numeric($array['ID']))
		{
			$value->setId((int)$array['ID']);
		}

		if (!empty($array['TYPE']))
		{
			$value->setTypeId((string)$array['TYPE']);
		}

		if (!empty($array['VALUE_TYPE']))
		{
			$value->setValueType((string)$array['VALUE_TYPE']);
		}

		if (!empty($array['VALUE']))
		{
			$value->setValue((string)$array['VALUE']);
		}

		return $value;
	}

	public static function arrayByValue(Value $value): array
	{
		return [
			'ID' => $value->getId(),
			'TYPE' => $value->getTypeId(),
			'VALUE_TYPE' => $value->getValueType(),
			'VALUE' => $value->getValue(),
		];
	}

	public static function arrayByCollection(Collection $collection): array
	{
		$array = [];

		$newValuesCountByType = [];
		foreach ($collection as $value)
		{
			$valueId = $value->getId();
			if ($valueId <= 0)
			{
				$count = $newValuesCountByType[$value->getTypeId()] ?? 0;

				$valueId = 'n' . $count;

				$count++;
				$newValuesCountByType[$value->getTypeId()] = $count;
			}

			$array[$value->getTypeId()][$valueId] = self::arrayByValue($value);
		}

		return $array;
	}

	public static function updateCollectionByArray(Collection $collection, array $compatibleArray): void
	{
		foreach ($compatibleArray as $typeId => $valuesOfSameType)
		{
			foreach ($valuesOfSameType as $id => $compatibleValue)
			{
				if (!isset($compatibleValue['VALUE']) || $compatibleValue['VALUE'] === '')
				{
					if (is_numeric($id))
					{
						$collection->removeById((int)$id);
					}

					continue;
				}

				$value = null;
				if (is_numeric($id))
				{
					$value = $collection->getById((int)$id);
				}

				if (!$value)
				{
					$newValue = self::valueByArray($compatibleValue);
					$newValue->setTypeId((string)$typeId);

					$collection->add($newValue);
					continue;
				}

				if ($value->getValue() !== $compatibleValue['VALUE'])
				{
					$value->setValue((string)$compatibleValue['VALUE']);
				}

				$isValueTypeChanged =
					isset($compatibleValue['VALUE_TYPE'])
					&& ($value->getValueType() !== $compatibleValue['VALUE_TYPE'])
				;
				if ($isValueTypeChanged)
				{
					$value->setValueType((string)$compatibleValue['VALUE_TYPE']);
				}
			}
		}
	}
}
