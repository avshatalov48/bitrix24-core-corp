<?php

namespace Bitrix\Crm\Agent\Security\DynamicTypes;

use Bitrix\Main\Config\Option;

class AttrConvertOptions
{
	public const OPTION_NAME_ITEMS_LIMIT = 'convert_dynamic_secure_attr_items_limit';

	private const DEFAULT_LIMIT = 100;

	private const OPTION_NAME_PROCESSED_ENTITY_TYPE_ID = 'convert_dynamic_secure_attr_processed_entity_type_id';

	private const OPTION_NAME_LAST_ITEM_ID= 'convert_dynamic_secure_attr_last_item_id';

	private const OPTION_NAME_NOT_CONVERTED_TYPES_IDS = 'convert_dynamic_secure_attr_not_converted_entity_types_ids';

	public static function getLimit(): int
	{
		return (int)Option::get('crm', self::OPTION_NAME_ITEMS_LIMIT, self::DEFAULT_LIMIT);
	}

	public static function getItemLastId(): int
	{
		return Option::get('crm', self::OPTION_NAME_LAST_ITEM_ID, -1);
	}

	public static function setItemLastId(int $lastId): void
	{
		Option::set('crm', self::OPTION_NAME_LAST_ITEM_ID, $lastId);
	}

	public static function deleteItemLastId(): void
	{
		Option::delete('crm', ['name' => self::OPTION_NAME_LAST_ITEM_ID]);
	}

	public static function getCurrentEntityTypeId(): int
	{
		return (int)Option::get('crm', self::OPTION_NAME_PROCESSED_ENTITY_TYPE_ID, -1);
	}

	public static function setCurrentEntityTypeId(int $id): void
	{
		Option::set('crm', self::OPTION_NAME_PROCESSED_ENTITY_TYPE_ID, $id);
	}

	public static function deleteCurrentEntityTypeId(): void
	{
		Option::delete('crm', ['name' => self::OPTION_NAME_PROCESSED_ENTITY_TYPE_ID]);
	}

	/**
	 * @return int[]
	 */
	public static function getNotConvertedEntityTypesIds(): array
	{
		$entityTypeIdsStr = Option::get('crm', self::OPTION_NAME_NOT_CONVERTED_TYPES_IDS, '');

		if (empty($entityTypeIdsStr))
		{
			return [];
		}

		return array_map(fn($id) => (int)$id, explode(',', $entityTypeIdsStr));
	}

	/**
	 * @param int[] $entityTypeIds
	 * @return void
	 */
	public static function setNotConvertedEntityTypesIds(array $entityTypeIds): void
	{
		Option::set('crm', self::OPTION_NAME_NOT_CONVERTED_TYPES_IDS, implode(',', $entityTypeIds));
	}

	public static function isEntityTypeNotConvertedYet(int $entityTypeId): bool
	{
		$currentEntityTypeId = self::getCurrentEntityTypeId();

		$list = self::getNotConvertedEntityTypesIds();
		if ($currentEntityTypeId !== -1)
		{
			$list[] = $currentEntityTypeId;
		}

		return in_array($entityTypeId, $list, true);
	}

	public static function deleteNotConvertedEntityTypesIds(): void
	{
		Option::delete('crm', ['name' => self::OPTION_NAME_NOT_CONVERTED_TYPES_IDS]);
	}
}