<?php
namespace Bitrix\Crm\Pseudoactivity\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;

/**
 * Class WaitTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Wait_Query query()
 * @method static EO_Wait_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Wait_Result getById($id)
 * @method static EO_Wait_Result getList(array $parameters = [])
 * @method static EO_Wait_Entity getEntity()
 * @method static \Bitrix\Crm\Pseudoactivity\Entity\EO_Wait createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Pseudoactivity\Entity\EO_Wait_Collection createCollection()
 * @method static \Bitrix\Crm\Pseudoactivity\Entity\EO_Wait wakeUpObject($row)
 * @method static \Bitrix\Crm\Pseudoactivity\Entity\EO_Wait_Collection wakeUpCollection($rows)
 */
class WaitTable  extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_wait';
	}
	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('data_type' => 'integer', 'primary' => true, 'autocomplete' => true),
			'OWNER_ID' => array('data_type' => 'integer'),
			'OWNER_TYPE_ID' => array('data_type' => 'integer'),
			'AUTHOR_ID' => array('data_type' => 'integer'),
			'START_TIME' => array('data_type' => 'datetime'),
			'END_TIME' => array('data_type' => 'datetime'),
			'CREATED' => array('data_type' => 'datetime'),
			'COMPLETED' => array('data_type' => 'boolean', 'values' => array('N', 'Y')),
			'DESCRIPTION' => array('data_type' => 'string')
		);
	}
	public static function deleteByOwner($ownerTypeID, $ownerID)
	{
		$ownerTypeID = (int)$ownerTypeID;
		$ownerID = (int)$ownerID;

		if ($ownerTypeID > 0 && $ownerID > 0)
		{
			Main\Application::getConnection()->queryExecute(
				"DELETE FROM b_crm_wait WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$ownerID}"
			);
		}
	}
	public static function transferOwnership($oldOwnerTypeID, $oldOwnerID, $newOwnerTypeID, $newOwnerID)
	{
		if($oldOwnerTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldOwnerTypeID');
		}

		if($oldOwnerID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldOwnerID');
		}

		if($newOwnerTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newOwnerTypeID');
		}

		if($newOwnerID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newOwnerID');
		}

		Main\Application::getConnection()->queryExecute(
			/** @lang text */
			"UPDATE b_crm_wait SET OWNER_TYPE_ID = {$newOwnerTypeID}, OWNER_ID = {$newOwnerID} 
					WHERE OWNER_TYPE_ID = {$oldOwnerTypeID} AND OWNER_ID = {$oldOwnerID}"
		);
	}
}
