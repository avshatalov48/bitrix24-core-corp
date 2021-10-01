<?php

namespace Bitrix\Crm;

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
 * @method static EO_EventRelations_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_EventRelations_Result getById($id)
 * @method static EO_EventRelations_Result getList(array $parameters = array())
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
		$result = new Result();

		$eventIds = [];

		$list = static::getList([
			'select' => ['ID', 'EVENT_ID'],
			'filter' => [
				'=ENTITY_TYPE' => $entityType,
			],
		]);
		while($item = $list->fetch())
		{
			$deleteResult = static::delete($item['ID']);
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
			else
			{
				$eventIds[] = $item['EVENT_ID'];
			}
		}

		if(!empty($eventIds))
		{
			$list = EventTable::getList([
				'select' => ['ID'],
				'filter' => [
					'@ID' => $eventIds,
				],
			]);
			while($item = $list->fetch())
			{
				$deleteResult = EventTable::delete($item['ID']);
				if(!$deleteResult->isSuccess())
				{
					$result->addErrors($deleteResult->getErrors());
				}
			}
		}

		return $result;
	}
}