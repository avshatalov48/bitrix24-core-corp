<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class CloudImportTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int optional
 * <li> VERSION_ID int optional
 * <li> TMP_FILE_ID int optional
 * <li> DOWNLOADED_CONTENT_SIZE int optional
 * <li> CONTENT_SIZE int optional
 * <li> CONTENT_URL string(255) optional
 * <li> MIME_TYPE string(255) optional
 * <li> USER_ID int mandatory
 * <li> SERVICE string(10) mandatory
 * <li> SERVICE_OBJECT_ID string(255) mandatory
 * <li> ETAG string(255) mandatory
 * <li> CREATE_TIME datetime mandatory
 * </ul>
 *
 * @package Bitrix\Disk
 **/


final class CloudImportTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_cloud_import';
	}

	/**
	 * Returns entity map definition.
	 *
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
			'TMP_FILE_ID' => array(
				'data_type' => 'integer',
			),
			'TMP_FILE' => array(
				'data_type' => 'Bitrix\Disk\Internals\TmpFileTable',
				'reference' => array(
					'=this.TMP_FILE_ID' => 'ref.ID'
				),
			),
			'DOWNLOADED_CONTENT_SIZE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'CONTENT_SIZE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'CONTENT_URL' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateContentUrl'),
			),
			'MIME_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateMimeType'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.USER_ID' => 'ref.ID'
				),
			),
			'SERVICE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateService'),
			),
			'SERVICE_OBJECT_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateServiceObjectId'),
			),
			'ETAG' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateEtag'),
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

	/**
	 * Returns validators for SERVICE field.
	 *
	 * @return array
	 */
	public static function validateService()
	{
		return array(
			new Entity\Validator\Length(null, 10),
		);
	}

	/**
	 * Returns validators for CONTENT_URL field.
	 *
	 * @return array
	 */
	public static function validateContentUrl()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for MIME_TYPE field.
	 *
	 * @return array
	 */
	public static function validateMimeType()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for SERVICE_OBJECT_ID field.
	 *
	 * @return array
	 */
	public static function validateServiceObjectId()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for ETAG field.
	 *
	 * @return array
	 */
	public static function validateEtag()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}