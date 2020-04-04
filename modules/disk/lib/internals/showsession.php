<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class ShowSessionTable
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
 * <li> ETAG string(255)
 * <li> CREATE_TIME datetime mandatory
 * </ul>
 *
 * @package Bitrix\Disk\Internals
 **/

final class ShowSessionTable extends DataManager
{
	const LIFETIME_SECONDS = 10800; //3 h.

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_show_session';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 */
	public static function getMap()
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$deathTime = $sqlHelper->addSecondsToDateTime(self::LIFETIME_SECONDS, 'CREATE_TIME');
		$now = $sqlHelper->getCurrentDateTimeFunction();

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
			'ETAG' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateEtag'),
			),
			'IS_EXPIRED' => array(
				'data_type' => 'boolean',
				'expression' => array(
					"CASE WHEN ({$now} > {$deathTime}) THEN 1 ELSE 0 END"
				),
				'values' => array(0, 1),
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

	public static function validateEtag()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}
