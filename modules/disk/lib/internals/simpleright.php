<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;

/**
 * Class SimpleRightTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> ACCESS_CODE string(50) optional
 * </ul>
 *
 * @package Bitrix\Disk
 * @internal
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SimpleRight_Query query()
 * @method static EO_SimpleRight_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SimpleRight_Result getById($id)
 * @method static EO_SimpleRight_Result getList(array $parameters = [])
 * @method static EO_SimpleRight_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_SimpleRight createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_SimpleRight_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_SimpleRight wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_SimpleRight_Collection wakeUpCollection($rows)
 */

final class SimpleRightTable extends DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_simple_right';
	}

	/**
	 * Returns entity map definition
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
				'required' => true,
			),
			'ACCESS_CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateAccessCode'),
			),
		);
	}

	/**
	 * Validates access code field.
	 * @return array
	 */
	public static function validateAccessCode()
	{
		return array(
			new Entity\Validator\Length(1, 50),
		);
	}

	/**
	 * Adds rows to table.
	 * @param array $items Items.
	 * @internal
	 */
	public static function insertBatch(array $items)
	{
		parent::insertBatch($items);
	}

	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter does not look like filter in getList. It depends by current implementation.
	 * @internal
	 */
	public static function deleteBatch(array $filter)
	{
		parent::deleteBatch($filter);
	}

	/**
	 * Fills descendants simple rights by simple rights of object.
	 * @internal
	 * @param int $objectId Id of object.
	 */
	public static function fillDescendants($objectId)
	{
		$tableName = static::getTableName();
		$pathTableName = ObjectPathTable::getTableName();
		$connection = Application::getConnection();

		$objectId = (int)$objectId;
		$connection->queryExecute("
			INSERT INTO {$tableName} (OBJECT_ID, ACCESS_CODE)
			SELECT path.OBJECT_ID, sright.ACCESS_CODE FROM {$pathTableName} path
				INNER JOIN {$tableName} sright ON sright.OBJECT_ID = path.PARENT_ID
			WHERE path.PARENT_ID = {$objectId}
		");
	}

	public static function deleteSimpleFromSelfAndChildren($objectId, $objectType)
	{
		$objectId = (int)$objectId;
		$connection = Application::getInstance()->getConnection();

		if($objectType == FileTable::TYPE_FILE)
		{
			$sql = "DELETE FROM b_disk_simple_right WHERE OBJECT_ID = {$objectId}";
		}
		else
		{
			$pathTable = ObjectPathTable::getTableName();
			$sql = "
				DELETE sr FROM b_disk_simple_right sr
					JOIN {$pathTable} path ON path.OBJECT_ID = sr.OBJECT_ID
				WHERE path.PARENT_ID = {$objectId}
			";
		}

		$connection->queryExecute($sql);
	}
}
