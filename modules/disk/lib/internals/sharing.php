<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class SharingTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> PARENT_ID int
 * <li> CREATED_BY int
 * <li> FROM_ENTITY string
 * <li> TO_ENTITY string
 * <li> LINK_STORAGE_ID int
 * <li> LINK_OBJECT_ID int
 * <li> REAL_OBJECT_ID int mandatory
 * <li> REAL_STORAGE_ID int mandatory
 * <li> DESCRIPTION string optional
 * <li> CAN_FORWARD int optional
 * <li> TYPE int mandatory
 * <li> STATUS int mandatory
 * <li> TASK_NAME string mandatory
 * </ul>
 *
 * @package Bitrix\Disk
 **/

final class SharingTable extends DataManager
{
	const STATUS_IS_UNREPLIED = 2;
	const STATUS_IS_APPROVED  = 3;
	const STATUS_IS_DECLINED  = 4;

	const TYPE_TO_USER       = 2;
	const TYPE_TO_GROUP      = 3;
	const TYPE_TO_DEPARTMENT = 4;

	public static function checkFields(Result $result, $primary, array $data)
	{
		parent::checkFields($result, $primary, $data);

		if(!$result->isSuccess())
		{
			return;
		}

		$query = new Entity\Query(static::getEntity());
		$res = $query
			->setSelect(array('ID', 'STATUS'))
			->setFilter(array(
				'=TO_ENTITY' => $data['TO_ENTITY'],
				'=REAL_OBJECT_ID' => $data['REAL_OBJECT_ID'],
			))
			->setLimit(2)
			->exec()
		;
		while ($existing = $res->fetch())
		{
			if(!isset($primary) || $primary != $existing['ID'])
			{
				if($existing['STATUS'] == self::STATUS_IS_DECLINED)
				{
					static::delete($existing['ID']);
				}
				else
				{
					$result->addError(new Entity\EntityError(
						Loc::getMessage("DISK_SHARING_ENTITY_ERROR_NON_UNIQUE")
					));
				}
			}
		}
	}

	public static function getTableName()
	{
		return 'b_disk_sharing';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
			),
			'CREATE_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.CREATED_BY' => 'ref.ID'
				),
			),
			'TO_ENTITY' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'FROM_ENTITY' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
			),
			'LINK_OBJECT_ID' => array(
				'data_type' => 'integer',
			),
			'LINK_STORAGE_ID' => array(
				'data_type' => 'integer',
			),
			'LINK_STORAGE' => array(
				'data_type' => 'Bitrix\Disk\Internals\StorageTable',
				'reference' => array(
					'=this.LINK_STORAGE_ID' => 'ref.ID'
				),
			),
			'LINK_OBJECT' => array(
				'data_type' => 'Bitrix\Disk\Internals\ObjectTable',
				'reference' => array(
					'=this.LINK_OBJECT_ID' => 'ref.ID'
				),
			),
			'REAL_STORAGE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'REAL_STORAGE' => array(
				'data_type' => 'Bitrix\Disk\Internals\StorageTable',
				'reference' => array(
					'=this.REAL_STORAGE_ID' => 'ref.ID'
				),
			),
			'REAL_OBJECT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'REAL_OBJECT' => array(
				'data_type' => 'Bitrix\Disk\Internals\ObjectTable',
				'reference' => array(
					'=this.REAL_OBJECT_ID' => 'ref.ID'
				),
			),
			'DESCRIPTION' => array(
				'data_type' => 'text',
			),
			'CAN_FORWARD' => array(
				'data_type' => 'boolean',
				'values' => array(0, 1),
				'default_value' => 0,
			),
			'TYPE' => array(
				'data_type' => 'enum',
				'required' => true,
				'values' => self::getListOfTypeValues(),
			),
			'STATUS' => array(
				'data_type' => 'enum',
				'values' => self::getListOfStatusValues(),
				'default_value' => self::STATUS_IS_UNREPLIED,
			),
			'TASK_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'PATH_PARENT_REAL_OBJECT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectPathTable',
				'reference' => array(
					'=this.REAL_OBJECT_ID' => 'ref.PARENT_ID'
				),
				'join_type' => 'INNER',
			),
			'PATH_CHILD_REAL_OBJECT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectPathTable',
				'reference' => array(
					'=this.REAL_OBJECT_ID' => 'ref.OBJECT_ID'
				),
				'join_type' => 'LEFT',
			),
			'PATH_CHILD_REAL_OBJECT_SOFT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectPathTable',
				'reference' => array(
					'=this.REAL_OBJECT_ID' => 'ref.OBJECT_ID'
				),
			),
			'PATH_PARENT_LINK_OBJECT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectPathTable',
				'reference' => array(
					'=this.LINK_OBJECT_ID' => 'ref.PARENT_ID'
				),
				'join_type' => 'INNER',
			),
			'PATH_CHILD_LINK_OBJECT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectPathTable',
				'reference' => array(
					'=this.LINK_OBJECT_ID' => 'ref.OBJECT_ID'
				),
				'join_type' => 'INNER',
			),
		);
	}

	public static function getListOfStatusValues()
	{
		return array(self::STATUS_IS_DECLINED, self::STATUS_IS_APPROVED, self::STATUS_IS_UNREPLIED);
	}

	public static function getListOfTypeValues()
	{
		return array(self::TYPE_TO_DEPARTMENT, self::TYPE_TO_GROUP, self::TYPE_TO_USER);
	}

	/**
	 * @inheritdoc
	 */
	public static function updateBatch(array $fields, array $filter)
	{
		parent::updateBatch($fields, $filter);
	}

}
