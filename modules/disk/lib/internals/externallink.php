<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class ExternalLinkTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> VERSION_ID int optional
 * <li> HASH string(255) optional
 * <li> PASSWORD string(255) optional
 * <li> SALT string(255) optional
 * <li> DEATH_TIME datetime optional
 * <li> DESCRIPTION string optional
 * <li> DOWNLOAD_COUNT int optional
 * <li> TYPE int optional
 * <li> CREATE_TIME datetime mandatory
 * <li> CREATED_BY int mandatory
 * </ul>
 *
 * @package Bitrix\Disk
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExternalLink_Query query()
 * @method static EO_ExternalLink_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExternalLink_Result getById($id)
 * @method static EO_ExternalLink_Result getList(array $parameters = [])
 * @method static EO_ExternalLink_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_ExternalLink createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_ExternalLink_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_ExternalLink wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_ExternalLink_Collection wakeUpCollection($rows)
 */

final class ExternalLinkTable extends DataManager
{
	public const TYPE_AUTO = 2;
	public const TYPE_MANUAL = 3;

	public const ACCESS_RIGHT_VIEW = 0;
	public const ACCESS_RIGHT_EDIT = 2;

	public static function getTableName()
	{
		return 'b_disk_external_link';
	}

	public static function getMap()
	{
		$connection = Application::getConnection();
		$now = $connection->getSqlHelper()->getCurrentDateTimeFunction();

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
				'data_type' => 'Bitrix\Disk\Internals\ObjectTable',
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
			'HASH' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateHash'),
			),
			'PASSWORD' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePassword'),
			),
			'SALT' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSalt'),
			),
			'DEATH_TIME' => array(
				'data_type' => 'datetime',
			),
			'DESCRIPTION' => array(
				'data_type' => 'text',
			),
			'DOWNLOAD_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ACCESS_RIGHT' => array(
				'data_type' => 'integer',
				'default_value' => self::ACCESS_RIGHT_VIEW,
			),
			'IS_EXPIRED' => array(
				'data_type' => 'boolean',
				'expression' => array(
					"CASE WHEN (%s IS NOT NULL AND %s > {$now} OR %s IS NULL) THEN 0 ELSE 1 END",
					'DEATH_TIME', 'DEATH_TIME', 'DEATH_TIME'
				),
				'values' => array(0, 1),
			),
			'TYPE' => array(
				'data_type' => 'enum',
				'required' => true,
				'values' => static::getListOfTypeValues(),
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

	public static function validateHash()
	{
		return array(
			new Entity\Validator\RegExp('/^[0-9a-f]{32}$/i'),
		);
	}

	public static function validatePassword()
	{
		return array(
			new Entity\Validator\Length(null, 32),
		);
	}

	public static function validateSalt()
	{
		return array(
			new Entity\Validator\Length(null, 32),
		);
	}

	public static function getListOfTypeValues()
	{
		return array(self::TYPE_MANUAL, self::TYPE_AUTO);
	}
}
