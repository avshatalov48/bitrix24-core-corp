<?php
namespace Bitrix\Crm\Cleaning;

use Bitrix\Main;

class CleaningManager
{
	public static function register($entityTypeID, $entityID)
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

		Entity\CleaningTable::upsert(array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID));
	}
	public static function unregister($entityTypeID, $entityID)
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

		Entity\CleaningTable::delete(array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID));
	}
	public static function getQueuedItems($limit = 10)
	{
		if(!is_int($limit))
		{
			$limit = (int)$limit;
		}

		if($limit <= 0)
		{
			$limit = 10;
		}

		$dbResult = Entity\CleaningTable::getList(
			array(
				'select' => array('ENTITY_TYPE_ID', 'ENTITY_ID'),
				'order' => array('CREATED_TIME' => 'ASC'),
				'limit' => $limit
			)
		);

		$items = array();
		while($fields = $dbResult->Fetch())
		{
			$items[] = $fields;
		}
		return $items;
	}
}