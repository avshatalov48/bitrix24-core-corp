<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

/**
 * Class ItemHistoryTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ItemHistory_Query query()
 * @method static EO_ItemHistory_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ItemHistory_Result getById($id)
 * @method static EO_ItemHistory_Result getList(array $parameters = [])
 * @method static EO_ItemHistory_Entity getEntity()
 * @method static \Bitrix\Rpa\Model\ItemHistory createObject($setDefaultValues = true)
 * @method static \Bitrix\Rpa\Model\EO_ItemHistory_Collection createCollection()
 * @method static \Bitrix\Rpa\Model\ItemHistory wakeUpObject($row)
 * @method static \Bitrix\Rpa\Model\EO_ItemHistory_Collection wakeUpCollection($rows)
 */
class ItemHistoryTable extends ORM\Data\DataManager
{
	public const SCOPE_MANUAL = 'manual';
	public const SCOPE_TASK = 'task';
	public const SCOPE_AUTOMATION = 'automation';
	public const SCOPE_REST = 'rest';

	public static function getTableName(): string
	{
		return 'b_rpa_item_history';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\IntegerField('ITEM_ID'))
				->configureRequired(),
			(new ORM\Fields\IntegerField('TYPE_ID'))
				->configureRequired(),
			(new ORM\Fields\DatetimeField('CREATED_TIME'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return new DateTime();
				}),
			(new ORM\Fields\IntegerField('STAGE_ID'))
				->configureRequired(),
			(new ORM\Fields\IntegerField('NEW_STAGE_ID')),
			(new ORM\Fields\IntegerField('USER_ID')),
			(new ORM\Fields\StringField('ACTION')),
			(new ORM\Fields\EnumField('SCOPE'))
				->configureRequired()
				->configureDefaultValue(static::SCOPE_MANUAL)
				->configureValues(static::getScopePossibleValues()),
			(new ORM\Fields\IntegerField('TASK_ID')),
			(new ORM\Fields\Relations\OneToMany(
				'FIELDS',
				ItemHistoryFieldTable::class,
				'ITEM_HISTORY')),
		];
	}

	public static function getListByItem(int $typeId, int $itemId): EO_ItemHistory_Collection
	{
		return static::getList([
			'order' => [
				'ID' => 'DESC',
			],
			'filter' => [
				'=TYPE_ID' => $typeId,
				'=ITEM_ID' => $itemId,
			],
		])->fetchCollection();
	}

	public static function removeForItem(int $typeId, int $itemId): Result
	{
		$result = new Result();

		$list = static::getListByItem($typeId, $itemId);
		foreach($list as $itemHistory)
		{
			$deleteResult = $itemHistory->delete();
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public static function removeByTypeId(int $typeId): Result
	{
		$result = new Result();

		$list = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=TYPE_ID' => $typeId,
			],
		]);
		while($item = $list->fetch())
		{
			$deleteResult = static::delete($item['ID']);
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public static function getObjectClass(): string
	{
		return ItemHistory::class;
	}

	public static function onAfterAdd(Event $event): ORM\EventResult
	{
		/** @var ItemHistory $record */
		$record = $event->getParameter('object');

		$id = $event->getParameter('id');
		if(is_array($id))
		{
			$id = $id['ID'];
		}
		$id = (int) $id;
		ItemHistoryFieldTable::deleteByItemHistory($id);

		foreach($record->getFields() as $fieldName)
		{
			ItemHistoryFieldTable::add([
				'ITEM_HISTORY_ID' => $id,
				'FIELD_NAME' => $fieldName,
			]);
		}

		return new ORM\EventResult();
	}

	public static function onBeforeUpdate(ORM\Event $event): ORM\EventResult
	{
		$result = new ORM\EventResult();

		$result->addError(new Orm\EntityError('You cannot update history records'));

		return $result;
	}

	public static function onAfterDelete(Event $event): ORM\EventResult
	{
		$id = $event->getParameter('id');
		if(is_array($id))
		{
			$id = $id['ID'];
		}
		$id = (int) $id;
		ItemHistoryFieldTable::deleteByItemHistory($id);

		return new ORM\EventResult();
	}

	public static function getScopePossibleValues(): array
	{
		return [
			static::SCOPE_MANUAL,
			static::SCOPE_TASK,
			static::SCOPE_AUTOMATION,
			static::SCOPE_REST,
		];
	}
}