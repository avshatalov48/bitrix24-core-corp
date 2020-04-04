<?php
namespace Bitrix\Crm\Timeline\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query;
use \Bitrix\Crm\Timeline\TimelineType;

class TimelineBindingTable  extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_timeline_bind';
	}
	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
            'OWNER_ID' => array('data_type' => 'integer', 'primary' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'primary' => true),
			'ENTITY_ID' => array('data_type' => 'integer', 'primary' => true),
			'IS_FIXED' => array('data_type' => 'boolean', 'values' => array('N', 'Y'), 'default_value' => 'N')
		);
	}
    /**
     * @return void
     */
    public static function upsert(array $data)
    {
		$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('The OWNER_ID field must be assigned and must be greater than zero.', 'data');
		}

		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		if($entityTypeID <= 0)
		{
			throw new Main\ArgumentException('The ENTITY_TYPE_ID field must be assigned and must be greater than zero.', 'data');
		}

		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('The ENTITY_ID field must be assigned and must be greater than zero.', 'data');
		}

		$fields = array(
			'OWNER_ID' => $ownerID,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_ID' => $entityID
		);

		if (isset($data['IS_FIXED']))
		{
			$fields['IS_FIXED'] = $data['IS_FIXED'];
		}

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_timeline_bind',
			array('OWNER_ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'),
			$fields,
			$fields
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	public static function deleteByOwner($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		Main\Application::getConnection()->queryExecute("DELETE from b_crm_timeline_bind WHERE OWNER_ID = {$ownerID}");
	}

	/**
	 * Unbind events from old entity of one type and bind them to new entity of another type.
	 * @param integer $oldEntityTypeID Old Entity Type ID.
	 * @param integer $oldEntityID Old Old Entity ID.
	 * @param integer $newEntityTypeID New Entity Type ID.
	 * @param integer $newEntityID New Entity ID.
	 * @throws Main\Db\SqlQueryException
	 */
	public static function transferOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		$connection = Main\Application::getConnection();
		$dbResult = $connection->query(
			"SELECT OWNER_ID FROM b_crm_timeline_bind WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);

		$updateMap = array();
		$deleteMap = array();

		while($fields = $dbResult->fetch())
		{
			$updateMap[$fields['OWNER_ID']] = true;
		}

		$sliceSize = 200;
		$ownerIDs = array_keys($updateMap);
		while(!empty($ownerIDs))
		{
			$conditionSql = implode(',', array_splice($ownerIDs, 0, $sliceSize));
			if($conditionSql === '')
			{
				break;
			}

			$dbResult = $connection->query("SELECT OWNER_ID FROM b_crm_timeline_bind WHERE ENTITY_TYPE_ID = {$newEntityTypeID} AND ENTITY_ID = {$newEntityID} AND OWNER_ID IN ({$conditionSql})");
			while($fields = $dbResult->fetch())
			{
				$deleteMap[$fields['OWNER_ID']] = true;
				unset($updateMap[$fields['OWNER_ID']]);
			}
		}

		if(!empty($updateMap))
		{
			$ownerIDs = array_keys($updateMap);
			while(!empty($ownerIDs))
			{
				$conditionSql = implode(',', array_splice($ownerIDs, 0, $sliceSize));
				if($conditionSql === '')
				{
					break;
				}

				$connection->queryExecute(
					"UPDATE b_crm_timeline_bind b SET b.ENTITY_TYPE_ID = {$newEntityTypeID}, b.ENTITY_ID = {$newEntityID} WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID} AND OWNER_ID IN ({$conditionSql})"
				);
			}
		}

		if(!empty($deleteMap))
		{
			$ownerIDs = array_keys($deleteMap);
			while(!empty($ownerIDs))
			{
				$conditionSql = implode(',', array_splice($ownerIDs, 0, $sliceSize));
				if($conditionSql === '')
				{
					break;
				}

				$connection->queryExecute(
					"DELETE FROM b_crm_timeline_bind WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID} AND OWNER_ID IN ({$conditionSql})"
				);
			}
		}

		$connection->queryExecute(
			"UPDATE b_crm_timeline SET ASSOCIATED_ENTITY_TYPE_ID = {$newEntityTypeID}, ASSOCIATED_ENTITY_ID = {$newEntityID} WHERE ASSOCIATED_ENTITY_TYPE_ID = {$oldEntityTypeID} AND ASSOCIATED_ENTITY_ID = {$oldEntityID}"
		);
	}
	/**
	 * Transfer events from old associated entity of one type to new entity of another type.
	 * Ownership will not be transferred. This method can be applied only for entities which does not own any events.
	 * @param integer $oldEntityTypeID Old Entity Type ID.
	 * @param integer $oldEntityID Old Old Entity ID.
	 * @param integer $newEntityTypeID New Entity Type ID.
	 * @param integer $newEntityID New Entity ID.
	 * @throws Main\Db\SqlQueryException
	 */
	public static function transferAssociation($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			"UPDATE b_crm_timeline SET ASSOCIATED_ENTITY_TYPE_ID = {$newEntityTypeID}, ASSOCIATED_ENTITY_ID = {$newEntityID} WHERE ASSOCIATED_ENTITY_TYPE_ID = {$oldEntityTypeID} AND ASSOCIATED_ENTITY_ID = {$oldEntityID}"
		);
	}
	/**
	 * Unbind events from old entity and bind them to new entity of same type.
	 * @param integer $entityTypeID Entity Type ID.
	 * @param integer $oldEntityID Old Entity ID.
	 * @param integer $newEntityID New Entity ID.
	 * @param array $typeIDs Timeline Type IDs.
	 */
	public static function rebind($entityTypeID, $oldEntityID, $newEntityID, array $typeIDs)
	{
		$connection = Main\Application::getConnection();
		$typeIDs = array_filter($typeIDs);
		if(!empty($typeIDs))
		{
			$typeSql = implode(',', $typeIDs);
			$dbResult = $connection->query(
				"SELECT b.OWNER_ID FROM b_crm_timeline_bind b INNER JOIN b_crm_timeline t ON b.OWNER_ID = t.ID AND t.TYPE_ID IN ({$typeSql}) AND b.ENTITY_TYPE_ID = {$entityTypeID} AND b.ENTITY_ID = {$oldEntityID}"
			);
		}
		else
		{
			$dbResult = $connection->query(
				"SELECT OWNER_ID FROM b_crm_timeline_bind WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$oldEntityID}"
			);
		}

		$updateMap = array();
		$deleteMap = array();

		while($fields = $dbResult->fetch())
		{
			$updateMap[$fields['OWNER_ID']] = true;
		}

		$sliceSize = 200;
		$ownerIDs = array_keys($updateMap);
		while(!empty($ownerIDs))
		{
			$conditionSql = implode(',', array_splice($ownerIDs, 0, $sliceSize));
			if($conditionSql === '')
			{
				break;
			}

			$dbResult = $connection->query("SELECT OWNER_ID FROM b_crm_timeline_bind WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$newEntityID} AND OWNER_ID IN ({$conditionSql})");
			while($fields = $dbResult->fetch())
			{
				$deleteMap[$fields['OWNER_ID']] = true;
				unset($updateMap[$fields['OWNER_ID']]);
			}

			//Skip records that are associated with old entity
			if(in_array(TimelineType::CREATION, $typeIDs, true))
			{
				$dbResult = $connection->query("SELECT ID FROM b_crm_timeline WHERE ASSOCIATED_ENTITY_TYPE_ID = {$entityTypeID} AND ASSOCIATED_ENTITY_ID = {$oldEntityID} AND ID IN ({$conditionSql})");
				while($fields = $dbResult->fetch())
				{
					unset($updateMap[$fields['ID']]);
				}
			}
		}

		if(!empty($updateMap))
		{
			$ownerIDs = array_keys($updateMap);
			while(!empty($ownerIDs))
			{
				$conditionSql = implode(',', array_splice($ownerIDs, 0, $sliceSize));
				if($conditionSql === '')
				{
					break;
				}

				$connection->queryExecute(
					"UPDATE b_crm_timeline_bind b SET b.ENTITY_ID = {$newEntityID} WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$oldEntityID} AND OWNER_ID IN ({$conditionSql})"
				);
			}
		}

		if(!empty($deleteMap))
		{
			$ownerIDs = array_keys($deleteMap);
			while(!empty($ownerIDs))
			{
				$conditionSql = implode(',', array_splice($ownerIDs, 0, $sliceSize));
				if($conditionSql === '')
				{
					break;
				}

				$connection->queryExecute(
					"DELETE FROM b_crm_timeline_bind WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$oldEntityID} AND OWNER_ID IN ({$conditionSql})"
				);
			}
		}
	}

	/**
	 * Attach target entity to source entity events.
	 * @param integer $srcEntityTypeID Source Entity Type ID.
	 * @param integer $srcEntityID Source Entity ID.
	 * @param integer $targEntityTypeID Target Entity Type ID.
	 * @param integer $targEntityID Target Entity ID.
	 * @param array $typeIDs Timeline Type IDs.
	 * @return void
	 * @throws Main\Db\SqlQueryException
	 */
	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID,  array $typeIDs)
	{
		$connection = Main\Application::getConnection();
		$typeIDs = array_filter($typeIDs);
		if(!empty($typeIDs))
		{
			$typeSql = implode(',', $typeIDs);
			$dbResult = $connection->query(
				"SELECT b.OWNER_ID FROM b_crm_timeline_bind b INNER JOIN b_crm_timeline t ON b.OWNER_ID = t.ID AND t.TYPE_ID IN ({$typeSql}) AND b.ENTITY_TYPE_ID = {$srcEntityTypeID} AND b.ENTITY_ID = {$srcEntityID}"
			);
		}
		else
		{
			$dbResult = $connection->query(
				"SELECT OWNER_ID FROM b_crm_timeline_bind WHERE ENTITY_TYPE_ID = {$srcEntityTypeID} AND ENTITY_ID = {$srcEntityID}"
			);
		}

		$ownerIDs = array();
		while($fields = $dbResult->fetch())
		{
			$ownerIDs[] = $fields['OWNER_ID'];
		}

		foreach($ownerIDs as $ownerID)
		{
			$fields = array(
				'OWNER_ID' => $ownerID,
				'ENTITY_TYPE_ID' => $targEntityTypeID,
				'ENTITY_ID' => $targEntityID
			);

			$queries = $connection->getSqlHelper()->prepareMerge(
				'b_crm_timeline_bind',
				array('OWNER_ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'),
				$fields,
				$fields
			);

			foreach($queries as $query)
			{
				$connection->queryExecute($query);
			}
		}
	}

	public static function detach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID, array $typeIDs)
	{
		$connection = Main\Application::getConnection();
		$typeIDs = array_filter($typeIDs);
		if(!empty($typeIDs))
		{
			$typeSql = implode(',', $typeIDs);
			$connection->queryExecute("
				DELETE b1 FROM b_crm_timeline_bind b1 
				INNER JOIN b_crm_timeline_bind b2 ON
					b2.OWNER_ID = b1.OWNER_ID AND 
					b2.ENTITY_TYPE_ID = {$srcEntityTypeID} AND 
					b2.ENTITY_ID = {$srcEntityID}
				INNER JOIN b_crm_timeline t ON 
					t.ID = b2.OWNER_ID AND 
					t.TYPE_ID IN ({$typeSql})	
				WHERE 
					b1.ENTITY_TYPE_ID = {$targEntityTypeID} AND 
					b1.ENTITY_ID = {$targEntityID}"
			);
		}
		else
		{
			$connection->queryExecute("
				DELETE b1 FROM b_crm_timeline_bind b1 
				INNER JOIN b_crm_timeline_bind b2 ON
					b2.OWNER_ID = b1.OWNER_ID AND 
					b2.ENTITY_TYPE_ID = {$srcEntityTypeID} AND 
					b2.ENTITY_ID = {$srcEntityID} 
				WHERE 
					b1.ENTITY_TYPE_ID = {$targEntityTypeID} AND 
					b1.ENTITY_ID = {$targEntityID}"
			);
		}

	}
}