<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

/**
 * Class DeletedLogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> STORAGE_ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> TYPE int mandatory
 * <li> CREATE_TIME datetime mandatory
 * </ul>
 *
 * @package Bitrix\Disk
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DeletedLog_Query query()
 * @method static EO_DeletedLog_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DeletedLog_Result getById($id)
 * @method static EO_DeletedLog_Result getList(array $parameters = [])
 * @method static EO_DeletedLog_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_DeletedLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_DeletedLog_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_DeletedLog wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_DeletedLog_Collection wakeUpCollection($rows)
 */

final class DeletedLogTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_disk_deleted_log';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'STORAGE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'OBJECT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TYPE' => array(
				'data_type' => 'enum',
				'values' => ObjectTable::getListOfTypeValues(),
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

	public static function insertBatch(array $items)
	{
		parent::insertBatch($items);
	}

	public static function deleteOldEntries()
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$quotedTableName = $helper->quote($tableName);

		$deathTime = $helper->addSecondsToDateTime(-365*24*3600);

		$connection->queryExecute("DELETE FROM {$quotedTableName} WHERE CREATE_TIME < {$deathTime}");
	}
}
