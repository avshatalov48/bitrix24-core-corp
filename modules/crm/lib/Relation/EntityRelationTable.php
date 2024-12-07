<?php

namespace Bitrix\Crm\Relation;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Result;
use Bitrix\Main\DB\SqlQueryException;

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
		return (new Result())->setData([
			'affectedItems' => [
				'parents' => self::rebindWhereItemIsParent($oldItem, $newItem),
				'children' => self::rebindWhereItemIsChild($oldItem, $newItem),
			],
		]);
	}

	/**
	 * @param ItemIdentifier $oldItem
	 * @param ItemIdentifier $newItem
	 * @return ItemIdentifier[] affected items
	 */
	public static function rebindWhereItemIsParent(ItemIdentifier $oldItem, ItemIdentifier $newItem): array
	{
		$parentsRows = self::query()
			->setSelect(['*'])
			->where('DST_ENTITY_TYPE_ID', $oldItem->getEntityTypeId())
			->where('DST_ENTITY_ID', $oldItem->getEntityId())
			->fetchCollection()
			->getAll()
		;

		$connection = Application::getConnection();

		$destinationQuery = 'UPDATE %s SET DST_ENTITY_TYPE_ID = %d, DST_ENTITY_ID = %d WHERE DST_ENTITY_TYPE_ID = %d AND DST_ENTITY_ID = %d';
		$destinationQuery = self::sprintfRebindQuery($oldItem, $newItem, $destinationQuery);

		try
		{
			$connection->query($destinationQuery);
		}
		catch (SqlQueryException $e)
		{
			self::deletePossibleDestinationRebindCollisions($newItem, $parentsRows);
			$connection->query($destinationQuery);
		}

		return array_map(
			static fn (EO_EntityRelation $parent) => new ItemIdentifier($parent->getSrcEntityTypeId(), $parent->getSrcEntityId()),
			$parentsRows,
		);
	}

	/**
	 * @param ItemIdentifier $oldItem
	 * @param ItemIdentifier $newItem
	 * @return ItemIdentifier[] affected items
	 */
	public static function rebindWhereItemIsChild(ItemIdentifier $oldItem, ItemIdentifier $newItem): array
	{
		$childrenRows = self::query()
			->setSelect(['*'])
			->where('SRC_ENTITY_TYPE_ID', $oldItem->getEntityTypeId())
			->where('SRC_ENTITY_ID', $oldItem->getEntityId())
			->fetchCollection()
			->getAll()
		;

		$connection = Application::getConnection();

		$sourceQuery = 'UPDATE %s SET SRC_ENTITY_TYPE_ID = %d, SRC_ENTITY_ID = %d WHERE SRC_ENTITY_TYPE_ID = %d AND SRC_ENTITY_ID = %d';
		$sourceQuery = self::sprintfRebindQuery($oldItem, $newItem, $sourceQuery);

		try
		{
			$connection->query($sourceQuery);
		}
		catch (SqlQueryException $e)
		{
			self::deletePossibleSourceRebindCollisions($newItem, $childrenRows);
			$connection->query($sourceQuery);
		}

		return array_map(
			static fn (EO_EntityRelation $child) => new ItemIdentifier($child->getDstEntityTypeId(), $child->getDstEntityId()),
			$childrenRows,
		);
	}

	private static function sprintfRebindQuery(ItemIdentifier $oldItem, ItemIdentifier $newItem, string $query): string
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		return sprintf(
			$query,
			$helper->quote(static::getTableName()),
			$helper->convertToDbInteger($newItem->getEntityTypeId()),
			$helper->convertToDbInteger($newItem->getEntityId()),
			$helper->convertToDbInteger($oldItem->getEntityTypeId()),
			$helper->convertToDbInteger($oldItem->getEntityId())
		);
	}

	private static function deletePossibleSourceRebindCollisions(
		ItemIdentifier $newItem,
		array $oldItemChildrenRows,
	): void
	{
		$possibleRelations = [];
		foreach ($oldItemChildrenRows as $childrenRow)
		{
			$possibleRelations[] = self::createObject(false)
				->setSrcEntityTypeId($newItem->getEntityTypeId())
				->setSrcEntityId($newItem->getEntityId())
				->setDstEntityTypeId($childrenRow->getDstEntityTypeId())
				->setDstEntityId($childrenRow->getDstEntityId())
			;
		}

		self::deleteRelations($possibleRelations);
	}

	private static function deletePossibleDestinationRebindCollisions(
		ItemIdentifier $newItem,
		array $oldItemParentRows,
	): void
	{
		$possibleRelations = [];
		foreach ($oldItemParentRows as $parentRow)
		{
			$possibleRelations[] = self::createObject(false)
				->setSrcEntityTypeId($parentRow->getSrcEntityTypeId())
				->setSrcEntityId($parentRow->getSrcEntityId())
				->setDstEntityTypeId($newItem->getEntityTypeId())
				->setDstEntityId($newItem->getEntityId())
			;
		}

		self::deleteRelations($possibleRelations);
	}

	/**
	 * @param EO_EntityRelation[] $relations
	 * @return void
	 */
	private static function deleteRelations(array $relations): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$where = [];
		foreach ($relations as $relation)
		{
			$srcEntityTypeId = $helper->convertToDbInteger($relation->getSrcEntityTypeId());
			$srcEntityId = $helper->convertToDbInteger($relation->getSrcEntityId());
			$dstEntityTypeId = $helper->convertToDbInteger($relation->getDstEntityTypeId());
			$dstEntityId = $helper->convertToDbInteger($relation->getDstEntityId());

			$where[] = "(
				(SRC_ENTITY_TYPE_ID = {$srcEntityTypeId}) 
				AND (SRC_ENTITY_ID = {$srcEntityId}) 
				AND (DST_ENTITY_TYPE_ID = {$dstEntityTypeId}) 
				AND (DST_ENTITY_ID = {$dstEntityId})
			)";
		}

		if (empty($where))
		{
			return;
		}

		$resultWhere = implode(' OR ', $where);
		$table = $helper->quote(self::getTableName());
		$query = "DELETE FROM {$table} WHERE {$resultWhere};";

		$connection->query($query);
	}

	public static function replaceBindings(ItemIdentifier $fromItem, ItemIdentifier $toItem): void
	{
		$entityTypeId = $toItem->getEntityTypeId();
		$fromId = $fromItem->getEntityId();
		$toId = $toItem->getEntityId();

		$connection = Application::getConnection();
		$tableName = $connection->getSqlHelper()->quote(static::getTableName());

		try
		{
			$srcSql = "UPDATE {$tableName} SET SRC_ENTITY_ID = {$toId} WHERE SRC_ENTITY_ID = {$fromId} AND SRC_ENTITY_TYPE_ID = {$entityTypeId}";
			$connection->query($srcSql);
		}
		catch (SqlQueryException $e) // most likely there is a duplication of unique keys, so try to update every item separately
		{
			$items = self::query()
				->setSelect(['SRC_ENTITY_TYPE_ID', 'SRC_ENTITY_ID', 'DST_ENTITY_TYPE_ID', 'DST_ENTITY_ID'])
				->where('SRC_ENTITY_ID', $fromId)
				->where('SRC_ENTITY_TYPE_ID', $entityTypeId)
				->fetchAll()
			;

			foreach ($items as $item)
			{
				try
				{
					$itemSrcEntityTypeId = (int)$item['SRC_ENTITY_TYPE_ID'];
					$itemSrcEntityId = (int)$item['SRC_ENTITY_ID'];
					$itemDstEntityTypeId = (int)$item['DST_ENTITY_TYPE_ID'];
					$itemDstEntityId = (int)$item['DST_ENTITY_ID'];
					$sql = "UPDATE {$tableName} SET SRC_ENTITY_ID = {$toId} WHERE SRC_ENTITY_TYPE_ID = {$itemSrcEntityTypeId} AND SRC_ENTITY_ID = {$itemSrcEntityId} AND DST_ENTITY_TYPE_ID = {$itemDstEntityTypeId} AND DST_ENTITY_ID={$itemDstEntityId}";
					$connection->query($sql);
				}
				catch (SqlQueryException $e)
				{
					// unique keys have been duplicated, so delete this duplicate:
					self::delete($item);
				}
			}
		}

		try
		{
			$dstSql = "UPDATE {$tableName} SET DST_ENTITY_ID = {$toId} WHERE DST_ENTITY_ID = {$fromId} AND DST_ENTITY_TYPE_ID = {$entityTypeId}";
			$connection->query($dstSql);
		}
		catch (SqlQueryException $e) // most likely there is a duplication of unique keys, so try to update every item separately
		{
			$items = self::query()
				->setSelect(['SRC_ENTITY_TYPE_ID', 'SRC_ENTITY_ID', 'DST_ENTITY_TYPE_ID', 'DST_ENTITY_ID'])
				->where('DST_ENTITY_ID', $fromId)
				->where('DST_ENTITY_TYPE_ID', $entityTypeId)
				->fetchAll()
			;

			foreach ($items as $item)
			{
				try
				{
					$itemSrcEntityTypeId = (int)$item['SRC_ENTITY_TYPE_ID'];
					$itemSrcEntityId = (int)$item['SRC_ENTITY_ID'];
					$itemDstEntityTypeId = (int)$item['DST_ENTITY_TYPE_ID'];
					$itemDstEntityId = (int)$item['DST_ENTITY_ID'];
					// orm does not support updates that change primary key parts, so plain sql:
					$sql = "UPDATE {$tableName} SET DST_ENTITY_ID = {$toId} WHERE SRC_ENTITY_TYPE_ID = {$itemSrcEntityTypeId} AND SRC_ENTITY_ID = {$itemSrcEntityId} AND DST_ENTITY_TYPE_ID = {$itemDstEntityTypeId} AND DST_ENTITY_ID={$itemDstEntityId}";
					$connection->query($sql);
				}
				catch (SqlQueryException $e)
				{
					// unique keys have been duplicated, so delete this duplicate:
					self::delete($item);
				}
			}
		}
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
		$queryResult = self::query()
			->setSelect([
				'SRC_ENTITY_TYPE_ID',
				'DST_ENTITY_TYPE_ID',
				'DST_ENTITY_ID',
			])
			->registerRuntimeField('', new ExpressionField('CNT_SRC_ENTITY_TYPE_ID', 'COUNT(%s)', 'SRC_ENTITY_TYPE_ID'))
			->registerRuntimeField('', new ExpressionField('CNT_DST_ENTITY_TYPE_ID', 'COUNT(%s)', 'DST_ENTITY_TYPE_ID'))
			->registerRuntimeField('', new ExpressionField('CNT_DST_ENTITY_ID', 'COUNT(%s)', 'DST_ENTITY_ID'))
			->where('DST_ENTITY_TYPE_ID', $dstEntityTypeId)
			->having('CNT_SRC_ENTITY_TYPE_ID', '>', 1)
			->having('CNT_DST_ENTITY_TYPE_ID', '>', 1)
			->having('CNT_DST_ENTITY_ID', '>', 1)
			->setGroup([
				'SRC_ENTITY_TYPE_ID',
				'DST_ENTITY_TYPE_ID',
				'DST_ENTITY_ID',
			])
			->setLimit($limit)
			->exec()
		;

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		while ($record = $queryResult->fetch())
		{
			$existedSrcEntityId = self::query()
				->where('SRC_ENTITY_TYPE_ID', $record['SRC_ENTITY_TYPE_ID'])
				->where('DST_ENTITY_TYPE_ID', $record['DST_ENTITY_TYPE_ID'])
				->where('DST_ENTITY_ID', $record['DST_ENTITY_ID'])
				->setSelect(['SRC_ENTITY_ID'])
				->setLimit(1)
				->fetch()['SRC_ENTITY_ID'] ?? null;

			if (!$existedSrcEntityId)
			{
				continue;
			}

			$sql = sprintf(
				'DELETE FROM %s WHERE 
					SRC_ENTITY_TYPE_ID = %d 
				    AND SRC_ENTITY_ID != %d
					AND DST_ENTITY_TYPE_ID = %d
					AND DST_ENTITY_ID = %d
				',
				$helper->quote(static::getTableName()),
				$helper->convertToDbInteger($record['SRC_ENTITY_TYPE_ID']),
				$helper->convertToDbInteger($existedSrcEntityId),
				$helper->convertToDbInteger($record['DST_ENTITY_TYPE_ID']),
				$helper->convertToDbInteger($record['DST_ENTITY_ID'])
			);
			$connection->query($sql);
		}
	}
}
