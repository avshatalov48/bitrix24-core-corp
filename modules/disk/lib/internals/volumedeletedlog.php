<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main;

/**
 * Class VolumeDeletedLogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> STORAGE_ID int optional
 * <li> OBJECT_ID int mandatory
 * <li> OBJECT_PARENT_ID int optional
 * <li> OBJECT_TYPE int mandatory
 * <li> OBJECT_NAME string(255) mandatory
 * <li> OBJECT_PATH string(255) mandatory
 * <li> OBJECT_SIZE int optional
 * <li> OBJECT_CREATED_BY int optional
 * <li> OBJECT_UPDATED_BY int optional
 * <li> VERSION_ID int optional
 * <li> VERSION_NAME string(255) optional
 * <li> FILE_ID int optional
 * <li> DELETED_TIME datetime mandatory
 * <li> DELETED_BY int mandatory
 * </ul>
 *
 * @package Bitrix\Disk
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_VolumeDeletedLog_Query query()
 * @method static EO_VolumeDeletedLog_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_VolumeDeletedLog_Result getById($id)
 * @method static EO_VolumeDeletedLog_Result getList(array $parameters = [])
 * @method static EO_VolumeDeletedLog_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_VolumeDeletedLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_VolumeDeletedLog wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_VolumeDeletedLog_Collection wakeUpCollection($rows)
 */


final class VolumeDeletedLogTable extends DataManager
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_volume_deleted_log';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'STORAGE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'STORAGE' => array(
				'data_type' => '\Bitrix\Disk\Internals\StorageTable',
				'reference' => array(
					'=this.STORAGE_ID' => 'ref.ID'
				),
				'join_type' => 'OUTER',
			),
			'OBJECT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'OBJECT_PARENT_ID' => array(
				'data_type' => 'integer',
			),
			'OBJECT_TYPE' => array(
				'data_type' => 'enum',
				'values' => ObjectTable::getListOfTypeValues(),
			),
			'OBJECT_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'OBJECT_PATH' => array(
				'data_type' => 'string',
			),
			'OBJECT_SIZE' => array(
				'data_type' => 'integer',
			),
			'OBJECT_CREATED_BY' => array(
				'data_type' => 'integer',
			),
			'OBJECT_CREATE_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.OBJECT_CREATED_BY' => 'ref.ID'
				),
			),
			'OBJECT_UPDATED_BY' => array(
				'data_type' => 'integer',
			),
			'OBJECT_UPDATE_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.OBJECT_UPDATED_BY' => 'ref.ID'
				),
			),
			'VERSION_ID' => array(
				'data_type' => 'integer',
			),
			'VERSION_NAME' => array(
				'data_type' => 'string',
			),
			'FILE_ID' => array(
				'data_type' => 'integer',
			),
			'DELETED_TIME' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function() {
					return new Main\Type\DateTime();
				},
			),
			'DELETED_BY' => array(
				'data_type' => 'integer',
				'required' => true,
				'default_value' => \Bitrix\Disk\SystemUser::SYSTEM_USER_ID,
			),
			'DELETED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.DELETED_BY' => 'ref.ID'
				),
			),
			'OPERATION' => array(
				'data_type' => 'string',
			),
		);
	}
}
