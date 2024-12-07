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

		if (!is_null($value->getValueExtra()?->getCountryCode()))
		{
			$result['VALUE_EXTRA']['VALUE_COUNTRY_CODE'] = $value->getValueExtra()?->getCountryCode();
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

		$countryCode = null;
		if (isset($array['VALUE_EXTRA']['VALUE_COUNTRY_CODE']) && !empty($array['VALUE_EXTRA']['VALUE_COUNTRY_CODE']))
		{
			$countryCode = (string)$array['VALUE_EXTRA']['VALUE_COUNTRY_CODE'];
		}
		elseif (isset($array['VALUE_COUNTRY_CODE']) && !empty($array['VALUE_COUNTRY_CODE']))
		{
			$countryCode = (string)$array['VALUE_COUNTRY_CODE'];
		}

		if (!empty($countryCode))
		{
			$value->setValueExtra(
				(new ValueExtra())
					->setCountryCode($countryCode)
			);
		}

		return $value;
	}

	public static function arrayByValue(Value $value): array
	{
		$result = [
			'ID' => $value->getId(),
			'TYPE' => $value->getTypeId(),
			'VALUE_TYPE' => $value->getValueType(),
			'VALUE' => $value->getValue(),
		];

		$extra = $value->getValueExtra();
		if ($extra instanceof ValueExtra)
		{
			$result['VALUE_EXTRA'] = $extra->toArray();
		}

		return $result;
	}

	public static function arrayByCollection(Collection $collection): array
	{
		$array = [];
		foreach (self::mapOfCompatibleShapeByCollection($collection) as $typeId => $valuesOfSameType)
		{
			foreach ($valuesOfSameType as $id => $value)
			{
				$array[$typeId][$id] = self::arrayByValue($value);
			}
		}

		return $array;
	}

	/**
	 * @param Collection $collection
	 *
	 * @return Array<string,Array<int|string, Value>> - [typeId => [id => value]]
	 */
	private static function mapOfCompatibleShapeByCollection(Collection $collection): array
	{
		$map = [];

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

			$map[$value->getTypeId()][$valueId] = $value;
		}

		return $map;
	}

	public static function updateCollectionByArray(Collection $collection, array $compatibleArray): void
	{
		$map = self::mapOfCompatibleShapeByCollection($collection);

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
				elseif (is_string($id) && str_starts_with($id, 'n'))
				{
					// maybe there is a yet unsaved value with the given id
					$value = $map[$typeId][$id] ?? null;
				}

				if ($value)
				{
					self::updateValueByArray($value, $compatibleValue);
				}
				else
				{
					if (str_starts_with($id, 'n'))
					{
						// Key is like 'n0', it's an entirely new value, ignore ID. For compatibility reasons.
						unset($compatibleValue['ID']);
					}

					$newValue = self::valueByArray($compatibleValue);
					$newValue->setTypeId((string)$typeId);

					$collection->add($newValue);
				}
			}
		}
	}

	private static function updateValueByArray(Value $value, array $compatibleValue): void
	{
		if ($value->getValue() !== $compatibleValue['VALUE'])
		{
			$value->setValue((string)$compatibleValue['VALUE']);
		}

		$compatibleValueCountryCode = null;
		if (isset($compatibleValue['VALUE_EXTRA']['VALUE_COUNTRY_CODE']))
		{
			$compatibleValueCountryCode = (string)$compatibleValue['VALUE_EXTRA']['VALUE_COUNTRY_CODE'];
		}
		elseif (isset($compatibleValue['VALUE_EXTRA']['COUNTRY_CODE']))
		{
			$compatibleValueCountryCode = (string)$compatibleValue['VALUE_EXTRA']['COUNTRY_CODE'];
		}
		elseif (isset($compatibleValue['VALUE_COUNTRY_CODE']))
		{
			$compatibleValueCountryCode = (string)$compatibleValue['VALUE_COUNTRY_CODE'];
		}

		if ($compatibleValueCountryCode === null && $value->getValueExtra())
		{
			$value->getValueExtra()->setCountryCode(null);
		}
		elseif ($compatibleValueCountryCode !== null)
		{
			if ($value->getValueExtra() && $value->getValueExtra()->getCountryCode() !== $compatibleValueCountryCode)
			{
				$value->getValueExtra()->setCountryCode($compatibleValueCountryCode);
			}
			elseif ($value->getValueExtra() === null)
			{
				$value->setValueExtra((new ValueExtra())->setCountryCode($compatibleValueCountryCode));
			}
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
