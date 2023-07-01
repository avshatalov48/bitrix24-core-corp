<?php

namespace Bitrix\Crm\Relation;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Result;

/**
 * Class EntityRelationTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_EntityRelation_Query query()
 * @method static EO_EntityRelation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_EntityRelation_Result getById($id)
 * @method static EO_EntityRelation_Result getList(array $parameters = [])
 * @method static EO_EntityRelation_Entity getEntity()
 * @method static \Bitrix\Crm\Relation\EO_EntityRelation createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Relation\EO_EntityRelation_Collection createCollection()
 * @method static \Bitrix\Crm\Relation\EO_EntityRelation wakeUpObject($row)
 * @method static \Bitrix\Crm\Relation\EO_EntityRelation_Collection wakeUpCollection($rows)
 */
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
				->configureValues(RelationType::getAll())
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

	public static function rebind(ItemIdentifier $oldItem, ItemIdentifier $newItem): Result
	{
		$childrenRows = self::query()
			->setSelect(['DST_ENTITY_TYPE_ID', 'DST_ENTITY_ID'])
			->where('SRC_ENTITY_TYPE_ID', $oldItem->getEntityTypeId())
			->where('SRC_ENTITY_ID', $oldItem->getEntityId())
			->fetchCollection()
			->getAll()
		;

		$parentsRows = self::query()
			->setSelect(['SRC_ENTITY_TYPE_ID', 'SRC_ENTITY_ID'])
			->where('DST_ENTITY_TYPE_ID', $oldItem->getEntityTypeId())
			->where('DST_ENTITY_ID', $oldItem->getEntityId())
			->fetchCollection()
			->getAll()
		;

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

		return (new Result())->setData([
			'affectedItems' => [
				'parents' => array_map(
					fn($parent) => new ItemIdentifier($parent->getSrcEntityTypeId(), $parent->getSrcEntityId()),
					$parentsRows,
				),
				'children' => array_map(
					fn($child) => new ItemIdentifier($child->getDstEntityTypeId(), $child->getDstEntityId()),
					$childrenRows,
				),
			],
		]);
	}

	public static function replaceBindings(ItemIdentifier $fromItem, ItemIdentifier $toItem): void
	{
		$entityTypeId = $toItem->getEntityTypeId();
		$fromId = $fromItem->getEntityId();
		$toId = $toItem->getEntityId();

		$connection = Application::getConnection();
		$tableName = $connection->getSqlHelper()->quote(static::getTableName());

		$srcSql = "UPDATE IGNORE {$tableName} SET  SRC_ENTITY_ID = {$toId} WHERE SRC_ENTITY_ID = {$fromId} AND SRC_ENTITY_TYPE_ID = {$entityTypeId}";
		$dstSql = "UPDATE IGNORE {$tableName} SET  DST_ENTITY_ID = {$toId} WHERE DST_ENTITY_ID = {$fromId} AND DST_ENTITY_TYPE_ID = {$entityTypeId}";
		$connection->query($srcSql);
		$connection->query($dstSql);
	}

	public static function onBeforeAdd(Event $event)
	{
		static::deleteExistingSourceElementsOfTheSameType($event->getParameter('object'));
	}

	public static function onBeforeUpdate(Event $event)
	{
		static::deleteExistingSourceElementsOfTheSameType($event->getParameter('object'));
	}

	public static function deleteExistingSourceElementsOfTheSameType(EO_EntityRelation $newBinding): void
	{
		$queryResult = static::getList([
			'select' => ['*'],
			'filter' => [
				'=SRC_ENTITY_TYPE_ID' => $newBinding->getSrcEntityTypeId(),
				'=DST_ENTITY_TYPE_ID' => $newBinding->getDstEntityTypeId(),
				'=DST_ENTITY_ID' => $newBinding->getDstEntityId(),
			],
		]);
		while ($existingElement = $queryResult->fetchObject())
		{
			$existingElement->delete();
		}
	}

	public static function initiateClearingDuplicateSourceElementsWithInterval(int $dstEntityTypeId): void
	{
		$interval = 86400;
		$optionName = 'last_time_clearing_duplicated_source_elements_' . $dstEntityTypeId;

		$lastTimeLaunchedClearing = Option::get('crm', $optionName, 0);
		if (time() - $lastTimeLaunchedClearing > $interval)
		{
			Option::set('crm', $optionName, time());

			\Bitrix\Crm\Relation\EntityRelationTable::clearDuplicateSourceElements($dstEntityTypeId);
		}
	}

	public static function clearDuplicateSourceElements(int $dstEntityTypeId, int $limit = 100): void
	{
		$connection = Application::getConnection();
		$tableName = $connection->getSqlHelper()->quote(static::getTableName());

		$srcSql = "SELECT 
       		SRC_ENTITY_ID,
			SRC_ENTITY_TYPE_ID, COUNT(SRC_ENTITY_TYPE_ID),
			DST_ENTITY_TYPE_ID, COUNT(DST_ENTITY_TYPE_ID), 
			DST_ENTITY_ID, COUNT(DST_ENTITY_ID)
			FROM ${tableName}
			WHERE DST_ENTITY_TYPE_ID = ${dstEntityTypeId}
			GROUP BY 
			SRC_ENTITY_TYPE_ID,
			DST_ENTITY_TYPE_ID,
			DST_ENTITY_ID
			HAVING
			COUNT(SRC_ENTITY_TYPE_ID) > 1 AND
			COUNT(DST_ENTITY_TYPE_ID) > 1 AND
			COUNT(DST_ENTITY_ID) > 1
			LIMIT ${limit}
		";

		$queryResult = $connection->query($srcSql);
		$helper = $connection->getSqlHelper();
		while ($record = $queryResult->fetch())
		{
			$sql = sprintf(
				'DELETE FROM %s WHERE 
					SRC_ENTITY_TYPE_ID = %d 
				    AND SRC_ENTITY_ID != %d
					AND DST_ENTITY_TYPE_ID = %d
					AND DST_ENTITY_ID = %d
				',
				$helper->quote(static::getTableName()),
				$helper->convertToDbInteger($record['SRC_ENTITY_TYPE_ID']),
				$helper->convertToDbInteger($record['SRC_ENTITY_ID']),
				$helper->convertToDbInteger($record['DST_ENTITY_TYPE_ID']),
				$helper->convertToDbInteger($record['DST_ENTITY_ID'])
			);
			$connection->query($sql);
		}
	}
}
