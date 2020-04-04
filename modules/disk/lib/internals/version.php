<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class VersionTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> FILE_ID int mandatory
 * <li> NAME string(255) optional
 * <li> CREATE_TIME datetime mandatory
 * <li> CREATED_BY int mandatory
 * <li> MISC_DATA string optional
 * <li> VIEW_ID int optional
 * </ul>
 *
 * @package Bitrix\Disk
 **/

final class VersionTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_disk_version';
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
			),
			'SIZE' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'FILE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateName'),
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
				)
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

			'OBJECT_CREATE_TIME' => array(
				'data_type' => 'datetime',
			),
			'OBJECT_CREATED_BY' => array(
				'data_type' => 'integer',
			),
			'OBJECT_UPDATE_TIME' => array(
				'data_type' => 'datetime',
			),
			'OBJECT_UPDATED_BY' => array(
				'data_type' => 'integer',
			),
			'GLOBAL_CONTENT_VERSION' => array(
				'data_type' => 'integer',
			),
			'MISC_DATA' => array(
				'data_type' => 'text',
			),
			'VIEW_ID' => array(
				'data_type' => 'integer',
			),
		);
	}

	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(1, 255),
		);
	}
}
