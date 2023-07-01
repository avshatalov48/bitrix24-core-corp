<?php

namespace Bitrix\Crm\Model;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Result;

/**
 * Class FieldContentTypeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FieldContentType_Query query()
 * @method static EO_FieldContentType_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FieldContentType_Result getById($id)
 * @method static EO_FieldContentType_Result getList(array $parameters = [])
 * @method static EO_FieldContentType_Entity getEntity()
 * @method static \Bitrix\Crm\Model\EO_FieldContentType createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Model\EO_FieldContentType_Collection createCollection()
 * @method static \Bitrix\Crm\Model\EO_FieldContentType wakeUpObject($row)
 * @method static \Bitrix\Crm\Model\EO_FieldContentType_Collection wakeUpCollection($rows)
 */
final class FieldContentTypeTable extends \Bitrix\Main\ORM\Data\DataManager
{
	/** @var Array<int, Array<int, Array<string, int>>> - [entityTypeId => [itemId => [fieldName => contentTypeId]]]*/
	private static array $cache = [];

	public static function getTableName()
	{
		return 'b_crm_field_content_type';
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired()
			,
			(new IntegerField('ENTITY_ID'))
				->configureRequired()
			,
			(new StringField('FIELD_NAME'))
				->configureRequired()
				->configureSize(255)
			,
			(new EnumField('CONTENT_TYPE_ID'))
				->configureRequired()
				->configureValues([
					\CCrmContentType::BBCode,
					\CCrmContentType::Html,
				])
				->configureDefaultValue(self::getDefaultContentTypeId())
			,
		];
	}

	public static function getContentTypeId(ItemIdentifier $item, string $fieldName): int
	{
		return self::loadForItem($item)[$fieldName] ?? \CCrmContentType::Undefined;
	}

	public static function getDefaultContentTypeId(): int
	{
		return \CCrmContentType::Html;
	}

	/**
	 * @param ItemIdentifier $item
	 * @return Array<string, int>
	 */
	public static function loadForItem(ItemIdentifier $item): array
	{
		$results = self::loadForMultipleItems($item->getEntityTypeId(), [$item->getEntityId()]);

		return $results[$item->getEntityId()] ?? [];
	}

	public static function loadForMultipleItems(int $entityTypeId, array $itemIds): array
	{
		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($itemIds);
		if (empty($itemIds))
		{
			return [];
		}

		$itemsToLoad = [];
		foreach ($itemIds as $itemId)
		{
			if (!isset(self::$cache[$entityTypeId][$itemId]))
			{
				$itemsToLoad[$itemId] = $itemId;
			}
		}

		if (!empty($itemsToLoad))
		{
			$dbResult =
				self::query()
					->setSelect(['ENTITY_ID', 'FIELD_NAME', 'CONTENT_TYPE_ID'])
					->where('ENTITY_TYPE_ID', $entityTypeId)
					->whereIn('ENTITY_ID', $itemsToLoad)
					->exec()
			;

			self::$cache[$entityTypeId] ??= [];
			while ($row = $dbResult->fetchObject())
			{
				self::$cache[$entityTypeId][$row->getEntityId()][$row->getFieldName()] = (int)$row->getContentTypeId();
			}
		}

		return array_filter(
			self::$cache[$entityTypeId],
			fn(int $itemId) => in_array($itemId, $itemIds, true),
			ARRAY_FILTER_USE_KEY,
		);
	}

	public static function cleanCache(): void
	{
		parent::cleanCache();
		self::cleanRuntimeCache();
	}

	public static function cleanRuntimeCache(): void
	{
		self::$cache = [];
	}

	public static function saveForItem(ItemIdentifier $item, array $fieldToContentTypeMap): Result
	{
		$newMap = array_filter(
			$fieldToContentTypeMap,
			static function ($contentTypeId, $fieldName): bool {
				return \CCrmContentType::IsDefined($contentTypeId) && is_string($fieldName) && !empty($fieldName);
			},
			ARRAY_FILTER_USE_BOTH,
		);
		$newMap = array_map('intval', $newMap);

		$oldObjects =
			self::query()
				->setSelect(['*'])
				->where('ENTITY_TYPE_ID', $item->getEntityTypeId())
				->where('ENTITY_ID', $item->getEntityId())
				->exec()
				->fetchCollection()
		;

		[$toSave, $toDelete] = self::prepareChangedObjects($item, $oldObjects, $newMap);

		$result = new Result();

		/** @var EO_FieldContentType $entityObject */
		foreach ($toSave as $entityObject)
		{
			$saveResult = $entityObject->save();
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
			}
		}

		/** @var EO_FieldContentType $entityObject */
		foreach ($toDelete as $entityObject)
		{
			$deleteResult = $entityObject->delete();
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param ItemIdentifier $item
	 * @param EO_FieldContentType_Collection $oldObjects
	 * @param Array<string, int> $newMap
	 * @return array
	 */
	private static function prepareChangedObjects(
		ItemIdentifier $item,
		EO_FieldContentType_Collection $oldObjects,
		array $newMap
	): array
	{
		$fieldNameToOldEntityObjectMap = [];
		foreach ($oldObjects as $entityObject)
		{
			$fieldNameToOldEntityObjectMap[$entityObject->getFieldName()] = $entityObject;
		}

		$objectsToSave = [];
		$objectsNotChanged = [];
		foreach ($newMap as $fieldName => $newContentTypeId)
		{
			if (!isset($fieldNameToOldEntityObjectMap[$fieldName]))
			{
				// add new
				$objectsToSave[$fieldName] =
					self::createObject()
						->setEntityTypeId($item->getEntityTypeId())
						->setEntityId($item->getEntityId())
						->setFieldName($fieldName)
						->setContentTypeId((int)$newContentTypeId)
				;
			}
			elseif ((int)$newContentTypeId !== (int)$fieldNameToOldEntityObjectMap[$fieldName]->getContentTypeId())
			{
				// update existing
				$objectsToSave[$fieldName] =
					$fieldNameToOldEntityObjectMap[$fieldName]
						->setContentTypeId((int)$newContentTypeId)
				;
			}
			else
			{
				$objectsNotChanged[$fieldName] = $fieldNameToOldEntityObjectMap[$fieldName];
			}
		}

		$objectsToDelete = [];
		foreach ($fieldNameToOldEntityObjectMap as $fieldName => $entityObject)
		{
			if (!isset($objectsToSave[$fieldName]) && !isset($objectsNotChanged[$fieldName]))
			{
				$objectsToDelete[] = $entityObject;
			}
		}

		return [$objectsToSave, $objectsToDelete];
	}

	public static function deleteByItem(ItemIdentifier $item): Result
	{
		$result = new Result();

		$dbResult =
			self::query()
				->setSelect(['ID'])
				->where('ENTITY_TYPE_ID', $item->getEntityTypeId())
				->where('ENTITY_ID', $item->getEntityId())
				->exec()
		;
		while ($row = $dbResult->fetchObject())
		{
			$deleteResult = $row->delete();
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public static function deleteByEntityTypeId(int $entityTypeId): Result
	{
		$result = new Result();

		$dbResult =
			self::query()
				->setSelect(['ID'])
				->where('ENTITY_TYPE_ID', $entityTypeId)
				->exec()
		;
		while ($row = $dbResult->fetchObject())
		{
			$deleteResult = $row->delete();
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public static function onAfterAdd(Event $event)
	{
		self::cleanRuntimeCache();
	}

	public static function onAfterUpdate(Event $event)
	{
		self::cleanRuntimeCache();
	}

	public static function onAfterDelete(Event $event)
	{
		self::cleanRuntimeCache();
	}
}
