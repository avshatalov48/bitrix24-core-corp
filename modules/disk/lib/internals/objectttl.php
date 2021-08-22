<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

/**
 * Class ObjectTtlTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> CREATE_TIME datetime mandatory
 * <li> DEATH_TIME datetime mandatory
 * </ul>
 *
 * @package Bitrix\Disk\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ObjectTtl_Query query()
 * @method static EO_ObjectTtl_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ObjectTtl_Result getById($id)
 * @method static EO_ObjectTtl_Result getList(array $parameters = array())
 * @method static EO_ObjectTtl_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_ObjectTtl createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_ObjectTtl_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_ObjectTtl wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_ObjectTtl_Collection wakeUpCollection($rows)
 */
final class ObjectTtlTable extends DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_object_ttl';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 */
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
				'join_type' => 'INNER',
			),
			'CREATE_TIME' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function() {
					return new DateTime();
				},
			),
			'DEATH_TIME' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function() {
					return new DateTime();
				},
			),
			'IS_EXPIRED' => array(
				'data_type' => 'boolean',
				'expression' => array(
					"CASE WHEN (%s IS NOT NULL AND %s > {$now} OR %s IS NULL) THEN 0 ELSE 1 END",
					'DEATH_TIME', 'DEATH_TIME', 'DEATH_TIME'
				),
				'values' => array(0, 1),
			),
		);
	}
}
