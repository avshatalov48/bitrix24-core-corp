<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;

/**
 * Class RightTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> TASK_ID int mandatory
 * <li> ACCESS_CODE string(50) optional
 * <li> DOMAIN string(50) optional
 * <li> NEGATIVE int mandatory
 * </ul>
 *
 * @package Bitrix\Disk
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Right_Query query()
 * @method static EO_Right_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Right_Result getById($id)
 * @method static EO_Right_Result getList(array $parameters = [])
 * @method static EO_Right_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_Right createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_Right_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_Right wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_Right_Collection wakeUpCollection($rows)
 */

final class RightTable extends DataManager
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_right';
	}

	/**
	 * Returns entity map definition
	 * return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'OBJECT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'ACCESS_CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateAccessCode'),
			),
			'DOMAIN' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDomain'),
			),
			'NEGATIVE' => array(
				'data_type' => 'boolean',
				'values' => array(0, 1),
				'default_value' => 0,
			),
			'OBJECT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectTable',
				'reference' => array(
					'=this.OBJECT_ID' => 'ref.ID',
				),
				'join_type' => 'INNER',
			),
			'PATH_PARENT' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectPathTable',
				'reference' => array(
					'=this.OBJECT_ID' => 'ref.PARENT_ID'
				),
				'join_type' => 'INNER',
			),
			'PATH_CHILD' => array(
				'data_type' => '\Bitrix\Disk\Internals\ObjectPathTable',
				'reference' => array(
					'=this.OBJECT_ID' => 'ref.OBJECT_ID'
				),
				'join_type' => 'INNER',
			),
			'TASK_OPERATION' => array(
				'data_type' => '\Bitrix\Main\TaskOperationTable',
				'reference' => array(
					'=this.TASK_ID' => 'ref.TASK_ID'
				),
				'join_type' => 'INNER',
			),
			'USER_ACCESS' => array(
				'data_type' => '\Bitrix\Main\UserAccessTable',
				'reference' => array(
					'=this.ACCESS_CODE' => 'ref.ACCESS_CODE'
				),
			),
		);
	}

	/**
	 * Validates access code field.
	 * @return array
	 */
	public static function validateAccessCode()
	{
		return array(
			new Entity\Validator\Length(1, 50),
		);
	}

	/**
	 * Validates domain field.
	 * @return array
	 */
	public static function validateDomain()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Adds rows to table.
	 * @param array $items Items.
	 * @internal
	 */
	public static function insertBatch(array $items)
	{
		parent::insertBatch($items);
	}

	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter does not look like filter in getList. It depends by current implementation.
	 * @internal
	 * @return void
	 */
	public static function deleteBatch(array $filter)
	{
		parent::deleteBatch($filter);
	}
}
