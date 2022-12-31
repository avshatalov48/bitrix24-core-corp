<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Disk\Configuration;
use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Validator\Length;
use Bitrix\Main\Type\DateTime;

/**
 * Class ObjectLockTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TOKEN string(255) mandatory
 * <li> OBJECT_ID int mandatory
 * <li> CREATED_BY int mandatory
 * <li> CREATE_TIME datetime mandatory
 * <li> EXPIRY_TIME datetime optional
 * <li> TYPE int mandatory
 * <li> IS_EXCLUSIVE int optional
 * </ul>
 *
 * @package Bitrix\Disk\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ObjectLock_Query query()
 * @method static EO_ObjectLock_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ObjectLock_Result getById($id)
 * @method static EO_ObjectLock_Result getList(array $parameters = [])
 * @method static EO_ObjectLock_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_ObjectLock createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_ObjectLock_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_ObjectLock wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_ObjectLock_Collection wakeUpCollection($rows)
 */
final class ObjectLockTable extends DataManager
{
	const TYPE_WRITE = 2;
	const TYPE_READ  = 3;

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_object_lock';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 */
	public static function getMap()
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$minutesToAutoReleaseObjectLock = Configuration::getMinutesToAutoReleaseObjectLock();
		if (!$minutesToAutoReleaseObjectLock || $minutesToAutoReleaseObjectLock < 0)
		{
			$minutesToAutoReleaseObjectLock = 0;
		}
		$seconds = (int)$minutesToAutoReleaseObjectLock * 60;
		$secondsToAutoRelease = $sqlHelper->addSecondsToDateTime($seconds, '%s');
		$now = $sqlHelper->getCurrentDateTimeFunction();

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TOKEN' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateToken'),
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
				'join_type' => 'INNER',
			),
			'CREATED_BY' => array(
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
			'IS_READY_AUTO_UNLOCK' => array(
				'data_type' => 'boolean',
				'expression' => array(
					"CASE WHEN ({$now} > {$secondsToAutoRelease}) THEN 1 ELSE 0 END",
					'CREATE_TIME'
				),
				'values' => array(0, 1),
			),
			'EXPIRY_TIME' => array(
				'data_type' => 'datetime',
			),
			'TYPE' => array(
				'data_type' => 'enum',
				'values' => static::getListOfTypeValues(),
				'default_value' => self::TYPE_WRITE,
				'required' => true,
			),
			'IS_EXCLUSIVE' => array(
				'data_type' => 'integer',
				'default_value' => 1,
			),
		);
	}

	public static function getListOfTypeValues()
	{
		return array(self::TYPE_READ, self::TYPE_WRITE);
	}

	/**
	 * Returns validators for TOKEN field.
	 *
	 * @return array
	 */
	public static function validateToken()
	{
		return array(
			new Length(null, 255),
		);
	}
}
