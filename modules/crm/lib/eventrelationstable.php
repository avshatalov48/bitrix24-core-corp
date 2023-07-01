<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Service\EventHistory;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Result;

/**
 * Class EventRelationsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_EventRelations_Query query()
 * @method static EO_EventRelations_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_EventRelations_Result getById($id)
 * @method static EO_EventRelations_Result getList(array $parameters = [])
 * @method static EO_EventRelations_Entity getEntity()
 * @method static \Bitrix\Crm\EO_EventRelations createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_EventRelations_Collection createCollection()
 * @method static \Bitrix\Crm\EO_EventRelations wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_EventRelations_Collection wakeUpCollection($rows)
 */
class EventRelationsTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_event_relations';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			new IntegerField('ASSIGNED_BY_ID'),
			new StringField('ENTITY_TYPE'),
			new IntegerField('ENTITY_ID'),
			new StringField('ENTITY_FIELD'),
			new IntegerField('EVENT_ID'),
			new Reference(
				'EVENT_BY',
				EventTable::class,
				[
					'=this.EVENT_ID' => 'ref.ID',
				]
			),
		];
	}

	public static function deleteByEntityType(string $entityType): Result
	{
		return static::deleteRecords($entityType);
	}

	public static function deleteByItem(int $entityTypeId, int $id): Result
	{
		return static::deleteRecords(\CCrmOwnerType::ResolveName($entityTypeId), $id);
	}

	private static function deleteRecords(string $entityTypeName, ?int $id = null): Result
	{
		$result = new Result();

		$eventIds = [];

		foreach (self::getRelationRecordsToDelete($entityTypeName, $id) as $row)
		{
			$deleteResult = $row->delete();
			if ($deleteResult->isSuccess())
			{
				$eventIds[] = $row->requireEventId();
			}
			else
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		$eventIds = array_unique($eventIds);
		if (empty($eventIds))
		{
			return $result;
		}

			$list =
				EventTable::query()
					->setSelect(['ID', 'FILES'])
					->whereIn('ID', $eventIds)
					// delete only events that have no more references in relations table
					->whereNull('EVENT_RELATION.EVENT_ID')
					->exec()
			;

		while ($item = $list->fetchObject())
		{
			$deleteResult = $item->delete();
			if ($deleteResult->isSuccess())
			{
				$serializedFileIds = $item->requireFiles();
				if (is_string($serializedFileIds) && !empty($serializedFileIds))
				{
					$fileIds = unserialize($serializedFileIds, ['allowed_classes' => false]);
					if (is_array($fileIds))
					{
						foreach ($fileIds as $fileId)
						{
							\CFile::Delete((int)$fileId);
						}
					}
				}
			}
			else
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	private static function getRelationRecordsToDelete(
		string $entityTypeName,
		?int $id = null
	): EO_EventRelations_Collection
	{
		$ownedRecordsQuery = self::query()
			->setSelect(['ID', 'EVENT_ID'])
			->where('ENTITY_TYPE', $entityTypeName)
		;
		if (!is_null($id))
		{
			$ownedRecordsQuery->where('ENTITY_ID', $id);
		}

		$mentionsInLinksQuery = self::query()
			->setSelect(['ID', 'EVENT_ID'])
			->whereIn('EVENT_BY.EVENT_TYPE', [EventHistory::EVENT_TYPE_LINK, EventHistory::EVENT_TYPE_UNLINK])
			->where('EVENT_BY.EVENT_TEXT_1', $entityTypeName)
		;
		if (!is_null($id))
		{
			$mentionsInLinksQuery->where('EVENT_BY.EVENT_TEXT_2', $id);
		}

		return
			$ownedRecordsQuery
				->union($mentionsInLinksQuery)
				->fetchCollection()
		;
	}

	public static function setAssignedByItem(ItemIdentifier $itemIdentifier, int $assignedById): Result
	{
		$collection = static::getList([
			'select' => ['ID', 'ASSIGNED_BY_ID'],
			'filter' => [
				'=ENTITY_TYPE' => \CCrmOwnerType::ResolveName($itemIdentifier->getEntityTypeId()),
				'=ENTITY_ID' => $itemIdentifier->getEntityId(),
			],
		])->fetchCollection();

		$result = new Result();

		foreach ($collection as $record)
		{
			$record->setAssignedById($assignedById);

			$saveResult = $record->save();
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
			}
		}

		return $result;
	}
}
