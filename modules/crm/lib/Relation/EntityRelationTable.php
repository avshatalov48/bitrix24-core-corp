<?php

namespace Bitrix\Crm\Relation;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;

class EntityRelationTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_entity_relation';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('SRC_ENTITY_TYPE_ID'))
				->configurePrimary(),
			(new IntegerField('SRC_ENTITY_ID'))
				->configurePrimary(),
			(new IntegerField('DST_ENTITY_TYPE_ID'))
				->configurePrimary(),
			(new IntegerField('DST_ENTITY_ID'))
				->configurePrimary(),
			(new EnumField('RELATION_TYPE'))
				->configureRequired()
				->configureValues([RelationType::CONVERSION, RelationType::BINDING])
				->configureDefaultValue(RelationType::BINDING),
		];
	}

	public static function deleteByItem(int $entityTypeId, int $entityId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		/** @noinspection SqlResolve */
		$connection->query(sprintf(
			'DELETE FROM %s WHERE 
				(SRC_ENTITY_TYPE_ID = %d AND SRC_ENTITY_ID = %d)
				OR (DST_ENTITY_TYPE_ID = %d AND DST_ENTITY_ID = %d)',
			$helper->quote(static::getTableName()),
			$helper->convertToDbInteger($entityTypeId),
			$helper->convertToDbInteger($entityId),
			$helper->convertToDbInteger($entityTypeId),
			$helper->convertToDbInteger($entityId)
		));
	}

	public static function deleteByEntityTypeId(int $entityTypeId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		/** @noinspection SqlResolve */
		$connection->query(sprintf(
			'DELETE FROM %s WHERE SRC_ENTITY_TYPE_ID = %d OR DST_ENTITY_TYPE_ID = %d',
			$helper->quote(static::getTableName()),
			$helper->convertToDbInteger($entityTypeId),
			$helper->convertToDbInteger($entityTypeId)
		));
	}

	public static function rebind(ItemIdentifier $oldItem, ItemIdentifier $newItem): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$sourceQuery = 'UPDATE %s SET SRC_ENTITY_TYPE_ID = %d, SRC_ENTITY_ID = %d WHERE SRC_ENTITY_TYPE_ID = %d AND SRC_ENTITY_ID = %d';
		$destinationQuery = 'UPDATE %s SET DST_ENTITY_TYPE_ID = %d, DST_ENTITY_ID = %d WHERE DST_ENTITY_TYPE_ID = %d AND DST_ENTITY_ID = %d';

		foreach ([$sourceQuery, $destinationQuery] as $query)
		{
			$connection->query(sprintf(
				$query,
				$helper->quote(static::getTableName()),
				$helper->convertToDbInteger($newItem->getEntityTypeId()),
				$helper->convertToDbInteger($newItem->getEntityId()),
				$helper->convertToDbInteger($oldItem->getEntityTypeId()),
				$helper->convertToDbInteger($oldItem->getEntityId())
			));
		}
	}
}
