<?php
namespace Bitrix\Crm\Observer;

use Bitrix\Main;

class ObserverManager
{
	public static function registerBulk(array $userIDs, $entityTypeID, $entityID, $sortOffset = 0)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		$userIDs = array_values($userIDs);
		for($i = 0, $length = count($userIDs); $i < $length; $i++)
		{
			$userID = $userIDs[$i];

			if(!is_int($userID))
			{
				$userID = (int)$userID;
			}

			if($userID <= 0)
			{
				continue;
			}

			Entity\ObserverTable::upsert(
				array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID,
					'USER_ID' => $userID,
					'SORT' => (10 * ($sortOffset + $i + 1)),
				)
			);
		}
	}
	public static function unregisterBulk(array $userIDs, $entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		foreach($userIDs as $userID)
		{
			if(!is_int($userID))
			{
				$userID = (int)$userID;
			}

			if($userID <= 0)
			{
				continue;
			}

			Entity\ObserverTable::delete(
				array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID,
					'USER_ID' => $userID
				)
			);
		}
	}
	public static function unregister($userID, $entityTypeID, $entityID)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'userID');
		}

		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		Entity\ObserverTable::delete(
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'USER_ID' => $userID
			)
		);
	}
	public static function getEntityObserverIDs($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		$dbResult = Entity\ObserverTable::getList(
			array(
				'filter' => array('=ENTITY_TYPE_ID' => $entityTypeID, '=ENTITY_ID' => $entityID),
				'select' => array('USER_ID'),
				'order' => array('SORT' => 'ASC')
			)
		);

		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = (int)$fields['USER_ID'];
		}
		return $results;
	}

	public static function prepareObserverChanges(array $origin, array $current, array &$added, array &$removed)
	{
		$added = array_diff($current, $origin);
		$removed = array_diff($origin, $current);
	}

	/**
	 * Unbind observers from old entity of one type and bind them to new entity of another type.
	 * @param integer $oldEntityTypeID Old Entity Type ID.
	 * @param integer $oldEntityID Old Old Entity ID.
	 * @param integer $newEntityTypeID New Entity Type ID.
	 * @param integer $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 */
	public static function transferOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		Entity\ObserverTable::transferOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID);
	}

	public static function deleteByOwner($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		Entity\ObserverTable::deleteByFilter(
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID
			)
		);
	}
}