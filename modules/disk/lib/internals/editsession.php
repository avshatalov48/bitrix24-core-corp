<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class EditSessionTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int optional
 * <li> VERSION_ID int optional
 * <li> USER_ID int mandatory
 * <li> OWNER_ID int mandatory
 * <li> IS_EXCLUSIVE bool optional
 * <li> SERVICE string(10) mandatory
 * <li> SERVICE_FILE_ID string(255) mandatory
 * <li> SERVICE_FILE_LINK text mandatory
 * <li> CREATE_TIME datetime mandatory
 * </ul>
 *
 * @package Bitrix\Disk
 **/

final class EditSessionTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_disk_edit_session';
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
			),
			'OBJECT' => array(
				'data_type' => 'Bitrix\Disk\Internals\FileTable',
				'reference' => array(
					'=this.OBJECT_ID' => 'ref.ID'
				),
			),
			'VERSION_ID' => array(
				'data_type' => 'integer',
			),
			'VERSION' => array(
				'data_type' => 'Bitrix\Disk\Internals\VersionTable',
				'reference' => array(
					'=this.OBJECT_ID' => 'ref.ID'
				),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'OWNER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'IS_EXCLUSIVE' => array(
				'data_type' => 'boolean',
				'values' => array(0, 1),
				'default_value' => 0,
			),
			'SERVICE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateService'),
			),
			'SERVICE_FILE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateServiceFileId'),
			),
			'SERVICE_FILE_LINK' => array(
				'data_type' => 'text',
				'required' => true,
			),
			'CREATE_TIME' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function() {
					return new DateTime();
				},
			),
		);
	}

	public static function validateService()
	{
		return array(
			new Entity\Validator\Length(null, 10),
		);
	}

	public static function validateServiceFileId()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}
