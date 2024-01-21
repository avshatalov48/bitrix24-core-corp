<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;


/**
 * Class EntityCountableActivityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_EntityCountableActivity_Query query()
 * @method static EO_EntityCountableActivity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_EntityCountableActivity_Result getById($id)
 * @method static EO_EntityCountableActivity_Result getList(array $parameters = [])
 * @method static EO_EntityCountableActivity_Entity getEntity()
 * @method static \Bitrix\Crm\Counter\EO_EntityCountableActivity createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Counter\EO_EntityCountableActivity_Collection createCollection()
 * @method static \Bitrix\Crm\Counter\EO_EntityCountableActivity wakeUpObject($row)
 * @method static \Bitrix\Crm\Counter\EO_EntityCountableActivity_Collection wakeUpCollection($rows)
 */
class EntityCountableActivityTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_entity_countable_act';
	}

	public static function getMap(): array
	{
		return  [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired(),
			(new IntegerField('ENTITY_ID'))
				->configureRequired(),
			(new IntegerField('ENTITY_ASSIGNED_BY_ID'))
				->configureRequired(),
			(new IntegerField('ACTIVITY_ID'))
				->configureRequired(),
			(new IntegerField('ACTIVITY_RESPONSIBLE_ID'))
				->configureRequired(),
			(new DatetimeField('ACTIVITY_DEADLINE'))
				->configureRequired(),
			(new BooleanField('ACTIVITY_IS_INCOMING_CHANNEL'))
				->configureStorageValues('N', 'Y')
				->configureRequired(),
			new DatetimeField('LIGHT_COUNTER_AT'),
			new DatetimeField('DEADLINE_EXPIRED_AT'),
				//->configureRequired(),
		];
	}

	public static function upsert(array $fields): \Bitrix\Main\ORM\Data\Result
	{
		$existedRecordId = self::query()
			->where('ENTITY_TYPE_ID', $fields['ENTITY_TYPE_ID'])
			->where('ENTITY_ID', $fields['ENTITY_ID'])
			->where('ACTIVITY_ID', $fields['ACTIVITY_ID'])
			->setSelect(['ID'])
			->setLimit(1)
			->fetch()['ID'] ?? null
		;
		if ($existedRecordId)
		{
			return self::update($existedRecordId, $fields);
		}

		return self::add($fields);
	}

	public static function deleteByEntity(ItemIdentifier $identifier): void
	{
		$sql = 'delete from ' . self::getTableName()
			. ' where ENTITY_TYPE_ID='. $identifier->getEntityTypeId()
			. ' and ENTITY_ID=' . $identifier->getEntityId()
		;
		Application::getConnection()->query($sql);
		self::cleanCache();
	}

	public static function deleteByActivity(int $activityId): void
	{
		$sql = 'delete from ' . self::getTableName()
			. ' where ACTIVITY_ID='. $activityId
		;
		Application::getConnection()->query($sql);
		self::cleanCache();
	}

	public static function deleteByIds(array $ids): void
	{
		$ids = array_filter($ids, 'is_numeric');

		if (empty($ids))
		{
			return;
		}

		$ids = array_map(fn($val) => (int)$val, $ids);
		$sql = 'delete from b_crm_entity_countable_act where ID in ('. implode(',', $ids) . ')';

		Application::getConnection()->query($sql);
		self::cleanCache();

	}

	public static function updateEntityAssignedBy(ItemIdentifier $identifier, int $assignedById): void
	{
		$sql = 'update ' . self::getTableName()
			. ' set ENTITY_ASSIGNED_BY_ID='. $assignedById
			. ' where ENTITY_TYPE_ID='. $identifier->getEntityTypeId()
			. ' and ENTITY_ID=' . $identifier->getEntityId()
		;
		Application::getConnection()->query($sql);
		self::cleanCache();
	}

	public static function rebind(ItemIdentifier $identifierFrom, ItemIdentifier $identifierTo): void
	{
		$sql = 'update ' . self::getTableName()
			. ' set ENTITY_TYPE_ID='. $identifierTo->getEntityTypeId() . ', ENTITY_ID='. $identifierTo->getEntityId()
			. ' where ENTITY_TYPE_ID='. $identifierFrom->getEntityTypeId()
			. ' and ENTITY_ID=' . $identifierFrom->getEntityId()
		;
		Application::getConnection()->query($sql);
		self::cleanCache();
	}

	public static function getActivityResponsible(array $fields): ?int
	{
		if (CounterSettings::getInstance()->useActivityResponsible())
		{
			return (int)$fields['ACTIVITY_RESPONSIBLE_ID'] ?? null;
		}
		else
		{
			return (int)$fields['ENTITY_ASSIGNED_BY_ID'] ?? null;
		}
	}
}
