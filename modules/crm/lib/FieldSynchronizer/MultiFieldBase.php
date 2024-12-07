<?php

namespace Bitrix\Crm\FieldSynchronizer;

abstract class MultiFieldBase
{
	protected static array $fieldStructureInfo = [];
	protected static array $fieldCaptionList = [];
	protected static array $typeList = [];
	protected static array $rawTypeInfo = [];

	protected static string $typeId;
	protected static array $availableTypeList;

	public static function getFieldsInfo(): array
	{
		if (empty(static::$fieldStructureInfo[static::$typeId]))
		{
			$typeInfo = static::getTypeInfo();

			foreach ($typeInfo as $id => $type)
			{
				static::$fieldStructureInfo[static::$typeId][$id] = [
					'TYPE' => 'crm_multifield',
					'ATTRIBUTES' => [\CCrmFieldInfoAttr::Multiple],
				];
			}
		}

		return static::$fieldStructureInfo[static::$typeId];
	}

	public static function getCaption(): array
	{
		if (empty(static::$fieldCaptionList[static::$typeId]))
		{
			$typeInfo = static::getTypeInfo();

			foreach ($typeInfo as $id => $type)
			{
				static::$fieldCaptionList[static::$typeId][$id] = $type['FULL'];
			}
		}

		return static::$fieldCaptionList[static::$typeId];
	}

	public static function getTypeList(): array
	{
		if (empty(static::$typeList[static::$typeId]))
		{
			$typeInfo = static::getTypeInfo();

			foreach ($typeInfo as $id => $type)
			{
				static::$typeList[static::$typeId][] = strtolower(static::$typeId . '_' . $id);
			}
		}

		return static::$typeList[static::$typeId];
	}

	public static function isOwnType(string $typeId): bool
	{
		return in_array(strtolower($typeId), static::getTypeList());
	}

	private static function getTypeInfo(): array
	{
		if (empty(static::$rawTypeInfo[static::$typeId]))
		{
			$typesInfo = \CCrmFieldMulti::GetEntityTypes()[static::$typeId] ?? [];

			if (empty($typesInfo))
			{
				return [];
			}

			foreach (static::$availableTypeList as $type)
			{
				if (!isset($typesInfo[$type]))
				{
					continue;
				}

				static::$rawTypeInfo[static::$typeId][$type] = $typesInfo[$type];
			}
		}

		return static::$rawTypeInfo[static::$typeId];
	}
}