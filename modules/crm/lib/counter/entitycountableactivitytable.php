<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;


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
}
