<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Volume;

/**
 * Class VolumeTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> INDICATOR_TYPE string(255) mandatory
 * <li> OWNER_ID int mandatory
 * <li> CREATE_TIME datetime optional
 * <li> TITLE string(255) optional
 * <li> FILE_SIZE float optional
 * <li> FILE_COUNT float optional
 * <li> VERSION_COUNT float optional
 * <li> PREVIEW_SIZE float optional
 * <li> PREVIEW_COUNT float optional
 * <li> ATTACHED_COUNT float optional
 * <li> LINK_COUNT float optional
 * <li> SHARING_COUNT float optional
 * <li> UNNECESSARY_VERSION_SIZE float optional
 * <li> UNNECESSARY_VERSION_COUNT float optional
 * <li> STORAGE_ID int optional
 * <li> MODULE_ID int optional
 * <li> FOLDER_ID int optional
 * <li> PARENT_ID int optional
 * <li> USER_ID int optional
 * <li> GROUP_ID int optional
 * <li> TYPE_FILE int optional
 * <li> ENTITY_TYPE string(100) optional
 * <li> ENTITY_ID string(12) optional
 * <li> IBLOCK_ID int optional
 * <li> DATA_PREPARED int optional
 * <li> COLLECTED int optional
 * <li> DATA text optional
 * <li> AGENT_LOCK int optional
 * <li> DROP_UNNECESSARY_VERSION int optional
 * <li> DROP_TRASHCAN int optional
 * <li> DROP_FOLDER int optional
 * <li> EMPTY_FOLDER int optional
 * <li> DROPPED_FILE_COUNT float optional
 * <li> DROPPED_VERSION_COUNT float optional
 * <li> DROPPED_FOLDER_COUNT float optional
 * <li> LAST_FILE_ID int optional
 * <li> FAIL_COUNT int optional
 * <li> LAST_ERROR string(255) optional
 * </ul>
 *
 * @package Bitrix\Disk
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Volume_Query query()
 * @method static EO_Volume_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Volume_Result getById($id)
 * @method static EO_Volume_Result getList(array $parameters = [])
 * @method static EO_Volume_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_Volume createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_Volume_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_Volume wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_Volume_Collection wakeUpCollection($rows)
 */


final class VolumeTable extends DataManager
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_volume';
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
			'INDICATOR_TYPE' => array(
				'data_type' => 'string',
			),
			'OWNER_ID' => array(
				'data_type' => 'integer',
				'default_value' => \Bitrix\Disk\SystemUser::SYSTEM_USER_ID,
			),
			'CREATE_TIME' => array(
				'data_type' => 'datetime',
				'default_value' => function()
				{
					return new Main\Type\DateTime();
				},
			),
			'TITLE' => array(
				'data_type' => 'string',
			),
			'FILE_SIZE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'FILE_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'DISK_SIZE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'DISK_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'VERSION_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'PREVIEW_SIZE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'PREVIEW_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ATTACHED_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'LINK_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'SHARING_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'UNNECESSARY_VERSION_SIZE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'UNNECESSARY_VERSION_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'PERCENT' => array(
				'data_type' => 'float',
				'default_value' => 0,
			),
			'STORAGE_ID' => array(
				'data_type' => 'integer',
			),
			'STORAGE' => array(
				'data_type' => '\Bitrix\Disk\Internals\StorageTable',
				'reference' => array(
					'=this.STORAGE_ID' => 'ref.ID'
				),
				'join_type' => 'OUTER',
			),
			'MODULE_ID' => array(
				'data_type' => 'string',
			),
			'FOLDER_ID' => array(
				'data_type' => 'integer',
			),
			'FOLDER' => array(
				'data_type' => '\Bitrix\Disk\Internals\FolderTable',
				'reference' => array(
					'=this.FOLDER_ID' => 'ref.ID'
				),
				'join_type' => 'OUTER',
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
			),
			'USER_ID' => array(
				'data_type' => 'integer',
			),
			'GROUP_ID' => array(
				'data_type' => 'integer',
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
			),
			'ENTITY_ID' => array(
				'data_type' => 'string',
			),
			'TYPE_FILE' => array(
				'data_type' => 'enum',
				'values' => TypeFile::getListOfValues(),
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
			),
			'COLLECTED' => array(
				'data_type' => 'enum',
				'values' => array(0, 1),
				'default_value' => 0,
			),
			'DATA' => array(
				'data_type' => 'text',
			),
			'AGENT_LOCK' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Task::TASK_STATUS_NONE,
					Volume\Task::TASK_STATUS_WAIT,
					Volume\Task::TASK_STATUS_RUNNING,
					Volume\Task::TASK_STATUS_DONE,
					Volume\Task::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Task::TASK_STATUS_NONE,
			),
			'DROP_UNNECESSARY_VERSION' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Task::TASK_STATUS_NONE,
					Volume\Task::TASK_STATUS_WAIT,
					Volume\Task::TASK_STATUS_RUNNING,
					Volume\Task::TASK_STATUS_DONE,
					Volume\Task::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Task::TASK_STATUS_NONE,
			),
			'DROP_TRASHCAN' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Task::TASK_STATUS_NONE,
					Volume\Task::TASK_STATUS_WAIT,
					Volume\Task::TASK_STATUS_RUNNING,
					Volume\Task::TASK_STATUS_DONE,
					Volume\Task::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Task::TASK_STATUS_NONE,
			),
			'DROP_FOLDER' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Task::TASK_STATUS_NONE,
					Volume\Task::TASK_STATUS_WAIT,
					Volume\Task::TASK_STATUS_RUNNING,
					Volume\Task::TASK_STATUS_DONE,
					Volume\Task::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Task::TASK_STATUS_NONE,
			),
			'EMPTY_FOLDER' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Task::TASK_STATUS_NONE,
					Volume\Task::TASK_STATUS_WAIT,
					Volume\Task::TASK_STATUS_RUNNING,
					Volume\Task::TASK_STATUS_DONE,
					Volume\Task::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Task::TASK_STATUS_NONE,
			),
			'DROPPED_FILE_COUNT' => array(
				'data_type' => 'integer',
			),
			'DROPPED_VERSION_COUNT' => array(
				'data_type' => 'integer',
			),
			'DROPPED_FOLDER_COUNT' => array(
				'data_type' => 'integer',
			),
			'LAST_FILE_ID' => array(
				'data_type' => 'integer',
			),
			'FAIL_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'LAST_ERROR' => array(
				'data_type' => 'string',
			),
			'FILES_LEFT' => array(
				'data_type' => 'integer',
				'expression' => array(
					"(DISK_COUNT - DROPPED_FILE_COUNT)",
				),
			),
		);
	}

	/**
	 * Delete storage event handler.
	 * @param int $storageId Storage id.
	 * @param int $deletedBy Dropped by id.
	 * @internal
	 * @return void
	 */
	public static function onStorageDelete($storageId, $deletedBy = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID)
	{
		try
		{
			if ($storageId > 0)
			{
				// remove all data relative to storage
				static::deleteBatch(array('STORAGE_ID' => $storageId));
			}
		}
		catch(\Exception $e)
		{
		}
	}

	/**
	 * Delete user event handler.
	 * @param int $userId User id.
	 * @internal
	 * @return void
	 */
	public static function onUserDelete($userId)
	{
		try
		{
			if ($userId > 0)
			{
				// remove all user data
				static::deleteBatch(array('OWNER_ID' => $userId));
			}
		}
		catch(\Exception $e)
		{
		}
	}

		/**
	 * Deletes rows by filter.
	 * @param array $filter Filter does not look like filter in getList. It depends by current implementation.
	 * @internal
	 * @return void
	 */
	public static function deleteBatch(array $filter)
	{
		$connection = Main\Application::getConnection();
		$tableName = static::getTableName();

		$filterSql = array();
		$fieldList = static::getMap();
		foreach ($filter as $key => $val)
		{
			if (isset($fieldList[$key]))
			{
				if ($fieldList[$key]['data_type'] === 'integer' || $fieldList[$key]['data_type'] === 'enum')
				{
					$val = (int)$val;
					$filterSql[] = "{$key} = {$val}";
				}
				elseif ($fieldList[$key]['data_type'] === 'string')
				{
					$val = $connection->getSqlHelper()->forSql($val);
					$filterSql[] = "{$key} = '{$val}''";
				}
			}
		}
		if (count($filterSql) > 0)
		{
			$connection->queryExecute("DELETE FROM {$tableName} WHERE ".implode(' AND ', $filterSql));
		}
		else
		{
			$connection->truncateTable($tableName);
		}
	}

	/**
	 * Removes all data
	 * @return void
	 */
	public static function purify()
	{
		Main\Application::getConnection()->truncateTable(static::getTableName());
	}

	/**
	 * Returns temporally table name.
	 * @return string
	 */
	public static function getTemporallyName()
	{
		return 'b_disk_volume_tmp';
	}

	/**
	 * Drops Temporally table
	 * @return void
	 */
	public static function dropTemporally()
	{
		$tableName = static::getTemporallyName();
		Main\Application::getConnection()->query("DROP TEMPORARY TABLE IF EXISTS {$tableName}");
	}

	/**
	 * Creates database structure
	 * @return void
	 */
	public static function createTemporally()
	{
		$tableName = static::getTemporallyName();
		$sourceTableName = static::getTableName();
		Main\Application::getConnection()->query("CREATE TEMPORARY TABLE IF NOT EXISTS {$tableName} SELECT * FROM {$sourceTableName} LIMIT 0");
	}

	/**
	 * Checks data base structure
	 * @return bool
	 */
	public static function checkTemporally()
	{
		return Main\Application::getConnection()->isTableExists(static::getTemporallyName());
	}

	/**
	 * Removes all data
	 * @return void
	 */
	public static function clearTemporally()
	{
		Main\Application::getConnection()->truncateTable(static::getTemporallyName());
	}
}
