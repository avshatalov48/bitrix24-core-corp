<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Type\DateTime;

class TimelineEntry
{
	/**
	 * @abstract
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	public static function create(array $params)
	{
	}

	public static function getByID($ID)
	{
		$dbResult = Entity\TimelineTable::getList(array('filter' => array('=ID' => $ID), 'limit' => 1));
		$fields = $dbResult->fetch();
		return is_array($fields) ? $fields : null;
	}

	public static function isAssociatedEntityExist($entityTypeID, $entityID)
	{
		$query = new Query(Entity\TimelineTable::getEntity());
		$query->addFilter('=ASSOCIATED_ENTITY_ID', $entityID);
		$query->addFilter('=ASSOCIATED_ENTITY_TYPE_ID', $entityTypeID);
		$query->addSelect('ID');
		$query->setLimit(1);

		$dbResult = $query->exec();
		return is_array($dbResult->fetch());
	}
	public static function synchronizeAssociatedEntityBindings($entityTypeID, $entityID, array $bindings)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Entity Type ID must be greater than zero.', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$query = new Query(Entity\TimelineTable::getEntity());
		$query->addFilter('=ASSOCIATED_ENTITY_ID', $entityID);
		$query->addFilter('=ASSOCIATED_ENTITY_TYPE_ID', $entityTypeID);
		$query->addSelect('ID');
		$dbResult = $query->exec();

		$entryIDs = array();
		while($entry = $dbResult->fetch())
		{
			$entryIDs[] = (int)$entry['ID'];
		}

		if(empty($entryIDs))
		{
			return;
		}

		$originalBindings = array();
		$query = new Query(Entity\TimelineBindingTable::getEntity());
		$query->addFilter('=OWNER_ID', $entryIDs[0]);
		$query->addSelect('ENTITY_TYPE_ID');
		$query->addSelect('ENTITY_ID');

		$dbResult = $query->exec();
		while($binding = $dbResult->fetch())
		{
			$originalBindings[] = $binding;
		}

		$added = array();
		$removed = array();

		self::prepareBindingChanges($originalBindings, $bindings, $added, $removed);

		foreach($entryIDs as $entryID)
		{
			foreach($removed as $binding)
			{
				Entity\TimelineBindingTable::delete(
					array(
						'OWNER_ID' => $entryID,
						'ENTITY_TYPE_ID' => $binding['ENTITY_TYPE_ID'],
						'ENTITY_ID' => $binding['ENTITY_ID']
					)
				);
			}

			foreach($added as $binding)
			{
				Entity\TimelineBindingTable::upsert(
					array(
						'OWNER_ID' => $entryID,
						'ENTITY_TYPE_ID' => $binding['ENTITY_TYPE_ID'],
						'ENTITY_ID' => $binding['ENTITY_ID']
					)
				);
			}
		}
	}

	/**
	 * @param int $id
	 * @return ItemIdentifier[]
	 */
	public static function getBindingItemIdentifiers(int $id): array
	{
		$result = [];

		$bindingsList = TimelineBindingTable::getList([
			'filter' => ['OWNER_ID' => $id],
			'select' => [
				'ENTITY_TYPE_ID',
				'ENTITY_ID',
			]
		]);
		while ($binding = $bindingsList->fetch())
		{
			$result[] = new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']);
		}

		return $result;
	}

	public static function deleteByOwner($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('Entity Type ID must be greater than zero.', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		$connection = Main\Application::getConnection();

		//region Delete by entity bindings
		// collect IDs of all events that are bound to the target item
		$ownerMap = array();
		$dbResult = $connection->query(
			"SELECT OWNER_ID FROM b_crm_timeline_bind WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}"
		);
		while($fields = $dbResult->fetch())
		{
			$ownerMap[$fields['OWNER_ID']] = true;
		}

		// delete all bind records that mention the target item
		$connection->queryExecute(
			"DELETE FROM b_crm_timeline_bind WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID}"
		);

		$sliceSize = 200;
		$ownerIDs = array_keys($ownerMap);
		while(!empty($ownerIDs))
		{
			$conditionSql = implode(',', array_splice($ownerIDs, 0, $sliceSize));
			if($conditionSql === '')
			{
				break;
			}

			$dbResult = $connection->query("SELECT OWNER_ID FROM b_crm_timeline_bind WHERE OWNER_ID IN ({$conditionSql})");
			while($fields = $dbResult->fetch())
			{
				// if after deletion of bind records there are some bind records remaining, it means that this event
				// is bound to more that one owner (our target item) and therefore should not be deleted
				unset($ownerMap[$fields['OWNER_ID']]);
			}
		}

		$ownerIDs = array_keys($ownerMap);
		$fileOwnerList = array();
		$documentIds = [];
		$types = [TimelineType::COMMENT, TimelineType::DOCUMENT];
		$sqlTypesIn = implode(', ', $types);
		while(!empty($ownerIDs))
		{
			$conditionSql = implode(',', array_splice($ownerIDs, 0, $sliceSize));
			if($conditionSql === '')
			{
				break;
			}
			$dbResult = $connection->query("SELECT ID, TYPE_ID, ASSOCIATED_ENTITY_ID FROM b_crm_timeline WHERE ID IN ({$conditionSql}) AND TYPE_ID IN ({$sqlTypesIn})");
			while($fields = $dbResult->fetch())
			{
				if($fields['TYPE_ID'] == TimelineType::COMMENT)
				{
					$fileOwnerList[] = $fields['ID'];
				}
				elseif($fields['TYPE_ID'] == TimelineType::DOCUMENT)
				{
					$documentIds[] = $fields['ASSOCIATED_ENTITY_ID'];
				}
			}
		}

		foreach ($fileOwnerList as $ownerID)
		{
			$GLOBALS['USER_FIELD_MANAGER']->Delete(CommentController::UF_FIELD_NAME, $ownerID);
		}

		DocumentGeneratorManager::getInstance()->deleteDocumentsByOwner($entityTypeID, $entityID);

		$ownerIDs = array_keys($ownerMap);
		while(!empty($ownerIDs))
		{
			$conditionSql = implode(',', array_splice($ownerIDs, 0, $sliceSize));
			if($conditionSql === '')
			{
				break;
			}
			$connection->queryExecute("DELETE FROM b_crm_timeline_search WHERE OWNER_ID IN ($conditionSql)");
			$connection->queryExecute("DELETE FROM b_crm_timeline WHERE ID IN ($conditionSql)");
			$connection->queryExecute("DELETE FROM b_crm_timeline_note WHERE ITEM_ID IN ($conditionSql) AND ITEM_TYPE=" . (int)\Bitrix\Crm\Timeline\Entity\NoteTable::NOTE_TYPE_HISTORY);
		}
		//endregion

		//region Delete by entity associations
		$connection->queryExecute(
			"DELETE s.* FROM b_crm_timeline_search s INNER JOIN b_crm_timeline t ON s.OWNER_ID = t.ID AND t.ASSOCIATED_ENTITY_TYPE_ID = {$entityTypeID} AND t.ASSOCIATED_ENTITY_ID = {$entityID}"
		);
		$connection->queryExecute(
			"DELETE b.* FROM b_crm_timeline_bind b INNER JOIN b_crm_timeline t ON b.OWNER_ID = t.ID AND t.ASSOCIATED_ENTITY_TYPE_ID = {$entityTypeID} AND t.ASSOCIATED_ENTITY_ID = {$entityID}"
		);
		$connection->queryExecute(
			"DELETE FROM b_crm_timeline_note WHERE ITEM_ID IN (SELECT ID FROM b_crm_timeline WHERE ASSOCIATED_ENTITY_TYPE_ID = {$entityTypeID} AND ASSOCIATED_ENTITY_ID = {$entityID}) AND ITEM_TYPE=" . (int)\Bitrix\Crm\Timeline\Entity\NoteTable::NOTE_TYPE_HISTORY
		);
		$connection->queryExecute(
			"DELETE FROM b_crm_timeline WHERE ASSOCIATED_ENTITY_TYPE_ID = {$entityTypeID} AND ASSOCIATED_ENTITY_ID = {$entityID}"
		);
		//endregion

		Entity\TimelineTable::cleanCache();
		Entity\TimelineBindingTable::cleanCache();
	}

	public static function getEntriesIdsByAssociatedEntity(int $entityTypeId, int $entityId, int $limit)
	{
		$query = Entity\TimelineTable::query();
		$query->addFilter('=ASSOCIATED_ENTITY_ID', $entityId);
		$query->addFilter('=ASSOCIATED_ENTITY_TYPE_ID', $entityTypeId);
		$query->addSelect('ID');
		$query->setOrder(['CREATED' => 'DESC', 'ID' => 'DESC']);
		$query->setLimit($limit);
		$items = $query->exec();
		$result = [];
		while ($item = $items->fetch())
		{
			$result[] = (int)$item['ID'];
		}

		return $result;
	}

	public static function deleteByAssociatedEntity($entityTypeID, $entityID)
	{
		$query = static::prepareDeleteQuery($entityTypeID);
		$query->addFilter('=ASSOCIATED_ENTITY_ID', $entityID);
		$dbResult = $query->exec();

		while($entry = $dbResult->fetch())
		{
			$ID = (int)$entry['ID'];
			static::delete($ID);
		}
	}
	public static function deleteByAssociatedEntityType(int $entityTypeID): void
	{
		$dbResult = static::prepareDeleteQuery($entityTypeID)->exec();

		while($entry = $dbResult->fetch())
		{
			$ID = (int)$entry['ID'];
			static::delete($ID);
		}
	}
	protected static function prepareDeleteQuery(int $entityTypeID): Query
	{
		$query = new Query(Entity\TimelineTable::getEntity());
		$query->addFilter('=ASSOCIATED_ENTITY_TYPE_ID', $entityTypeID);
		$query->addSelect('ID');

		return $query;
	}

	public static function delete($ID)
	{
		if (!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if ($ID <= 0)
		{
			throw new Main\ArgumentException('Entity ID must be greater than zero.', 'ID');
		}

		Entity\TimelineBindingTable::deleteByOwner($ID);
		Entity\TimelineSearchTable::deleteByOwner($ID);

		return Entity\TimelineTable::delete($ID);
	}

	public static function prepareEntityPushTag($entityTypeID, $entityID)
	{
		$pusher = Crm\Service\Container::getInstance()->getTimelinePusher();

		return $pusher->prepareEntityPushTag((int)$entityTypeID, (int)$entityID);
	}

	protected static function prepareBindingChanges(array $origin, array $current, array &$added, array &$removed)
	{
		$originMap = array();
		foreach($origin as $binding)
		{
			$entityTypeID = isset($binding['ENTITY_TYPE_ID']) ? (int)$binding['ENTITY_TYPE_ID'] : 0;
			$entityID = isset($binding['ENTITY_ID']) ? (int)$binding['ENTITY_ID'] : 0;
			if($entityTypeID <= 0 || $entityID <= 0)
			{
				continue;
			}

			$originMap["{$entityTypeID}:{$entityID}"] = $binding;
		}

		$currentMap = array();
		foreach($current as $binding)
		{
			$entityTypeID = isset($binding['ENTITY_TYPE_ID']) ? (int)$binding['ENTITY_TYPE_ID'] : 0;
			$entityID = isset($binding['ENTITY_ID']) ? (int)$binding['ENTITY_ID'] : 0;
			if($entityTypeID <= 0 || $entityID <= 0)
			{
				continue;
			}

			$currentMap["{$entityTypeID}:{$entityID}"] = $binding;
		}

		$originKeys = array_keys($originMap);
		$currentKeys = array_keys($currentMap);

		$removed = array();
		foreach(array_diff($originKeys, $currentKeys) as $key)
		{
			$removed[] = $originMap[$key];
		}

		$added = array();
		foreach(array_diff($currentKeys, $originKeys) as $key)
		{
			$added[] = $currentMap[$key];
		}
	}
	public static function registerBindings($entryID, array $bindings)
	{
		$monitor = Crm\Service\Timeline\Monitor::getInstance();

		foreach($bindings as $binding)
		{
			$entityID = isset($binding['ENTITY_ID']) ? (int)$binding['ENTITY_ID'] : 0;
			$entityTypeID = isset($binding['ENTITY_TYPE_ID']) ? (int)$binding['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;

			if($entityID > 0 && \CCrmOwnerType::IsDefined($entityTypeID))
			{
				$parameters = [
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID,
					'OWNER_ID' => $entryID
				];
				if (isset($binding['IS_FIXED']))
				{
					$parameters['IS_FIXED'] = $binding['IS_FIXED'] ? 'Y' : 'N';
				}
				Entity\TimelineBindingTable::upsert($parameters);

				$monitor->onTimelineEntryAddIfSuitable(new Crm\ItemIdentifier($entityTypeID, $entityID), (int)$entryID);
			}
		}
	}

	public static function shift($ID, DateTime $time)
	{
		Entity\TimelineTable::update($ID, array('CREATED' => $time));
	}

	public static function buildSearchContent($ID)
	{
		$builder = new Crm\Search\TimelineSearchContentBuilder();
		$builder->build($ID);
	}

	public static function checkBindingExists(int $id, int $ownerTypeId, int $ownerId): bool
	{
		return Entity\TimelineBindingTable::checkBindingExists($id, $ownerTypeId, $ownerId);
	}

	public static function isFixed(int $id, int $ownerTypeId, int $ownerId): bool
	{
		return Entity\TimelineBindingTable::isFixed($id, $ownerTypeId, $ownerId);
	}

	public static function setIsFixed(int $id, int $ownerTypeId, int $ownerId, bool $isFixed): UpdateResult
	{
		return Entity\TimelineBindingTable::setIsFixed($id, $ownerTypeId, $ownerId, $isFixed);
	}

	protected static function getRequiredIntegerParam(string $paramName, array $params): int
	{
		$integer = (int)($params[$paramName] ?? 0);
		if ($integer <= 0)
		{
			throw new \Bitrix\Main\ArgumentException($paramName . ' must be greater than zero.', $paramName);
		}

		return $integer;
	}

	protected static function fetchEntityId(array $params): int
	{
		$entityId = $params['ENTITY_ID'] ?? 0;
		if ($entityId <= 0)
		{
			throw new ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}

		return (int)$entityId;
	}

	protected static function fetchEntityTypeId(array $params): int
	{
		$entityTypeId = $params['ENTITY_TYPE_ID'] ?? 0;
		if ($entityTypeId <= 0)
		{
			throw new ArgumentException('Entity type ID must be greater than zero.', 'entityTypeID');
		}

		return (int)$entityTypeId;
	}

	protected static function fetchAuthorId(array $params): int
	{
		$authorId = $params['AUTHOR_ID'] ?? 0;
		if ($authorId <= 0)
		{
			throw new ArgumentException('Author ID must be greater than zero.', 'authorID');
		}

		return (int)$authorId;
	}

	protected static function fetchCategoryId(array $params): int
	{
		$categoryId = $params['TYPE_CATEGORY_ID'] ?? 0;
		if ($categoryId <= 0)
		{
			throw new ArgumentException('Category Id must be greater than zero.', 'authorID');
		}

		return (int)$categoryId;
	}

	protected static function fetchParams(array $params): array
	{
		$authorId = $params['AUTHOR_ID'] ?? 0;
		$authorId = $authorId <= 0 ? \CCrmSecurityHelper::GetCurrentUserID() : (int)$authorId;

		$created = isset($params['CREATED']) && ($params['CREATED'] instanceof DateTime)
			? $params['CREATED']
			: new DateTime();

		$settings = [];
		if (isset($params['SETTINGS']) && is_array($params['SETTINGS']))
		{
			$settings = $params['SETTINGS'];
		}

		$bindings = [];
		if (isset($params['BINDINGS']) && is_array($params['BINDINGS']))
		{
			$bindings = $params['BINDINGS'];
		}

		return [$authorId, $created, $settings, $bindings];
	}
}
