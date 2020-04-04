<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class AttachedObjectTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> VERSION_ID int optional
 * <li> IS_EDITABLE int optional
 * <li> ALLOW_EDIT int optional
 * <li> ALLOW_AUTO_COMMENT int optional
 * <li> MODULE_ID string(32) optional
 * <li> ENTITY_TYPE string(100) optional
 * <li> ENTITY_ID int optional
 * <li> CREATE_TIME datetime mandatory
 * <li> CREATED_BY int
 * </ul>
 *
 * @package Bitrix\Disk
 **/

final class AttachedObjectTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_disk_attached_object';
	}

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
			'OBJECT' => array(
				'data_type' => 'Bitrix\Disk\Internals\FileTable',
				'reference' => array(
					'=this.OBJECT_ID' => 'ref.ID'
				),
				'join_type' => 'INNER',
			),
			'VERSION_ID' => array(
				'data_type' => 'integer',
			),
			'VERSION' => array(
				'data_type' => 'Bitrix\Disk\Internals\VersionTable',
				'reference' => array(
					'=this.VERSION_ID' => 'ref.ID'
				),
			),
			'IS_EDITABLE' => array(
				'data_type' => 'enum',
				'values' => array(0, 1, 2),
				'default_value' => 0,
			),
			'ALLOW_EDIT' => array(
				'data_type' => 'enum',
				'values' => array(0, 1),
				'default_value' => 0,
			),
			'ALLOW_AUTO_COMMENT' => array(
				'data_type' => 'enum',
				'values' => array(0, 1),
				'default_value' => 1,
			),
			'MODULE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateModule'),
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateEntityType'),
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CREATE_TIME' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function() {
					return new DateTime();
				},
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
		);
	}

	public static function validateModule()
	{
		return array(
			new Entity\Validator\Length(1, 32),
		);
	}

	public static function validateEntityType()
	{
		return array(
			new Entity\Validator\Length(1, 100),
		);
	}

	/**
	 * Updates rows by filter (simple format).
	 * Filter support only column = value. Only =.
	 * @param array $fields Fields.
	 * @param array $filter Filter.
	 */
	public static function updateBatch(array $fields, array $filter)
	{
		parent::updateBatch($fields, $filter);
	}
}
