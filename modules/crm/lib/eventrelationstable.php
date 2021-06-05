<?php

namespace Bitrix\Crm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Result;

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