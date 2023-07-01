<?php

namespace Bitrix\Crm;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Crm\Volume;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity;

/**
 * Class VolumeTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> INDICATOR_TYPE string(255) mandatory
 * <li> OWNER_ID int mandatory
 * <li> TIMESTAMP_X datetime optional
 * <li> DATE_CREATE date optional
 * <li> STAGE_SEMANTIC_ID string(3) optional
 * <li> ENTITY_SIZE float optional
 * <li> ENTITY_COUNT float optional
 * <li> FILE_SIZE float optional
 * <li> FILE_COUNT float optional
 * <li> DISK_SIZE float optional
 * <li> DISK_COUNT float optional
 * <li> EVENT_SIZE float optional
 * <li> EVENT_COUNT float optional
 * <li> ACTIVITY_SIZE float optional
 * <li> ACTIVITY_COUNT float optional
 * <li> AGENT_LOCK int optional
 * <li> DROP_ENTITY int optional
 * <li> DROP_FILE int optional
 * <li> DROP_EVENT int optional
 * <li> DROP_ACTIVITY int optional
 * <li> DROPPED_ENTITY_COUNT float optional
 * <li> DROPPED_FILE_COUNT float optional
 * <li> DROPPED_EVENT_COUNT float optional
 * <li> DROPPED_ACTIVITY_COUNT float optional
 * <li> LAST_ID int optional
 * <li> FAIL_COUNT int optional
 * <li> LAST_ERROR string(255) optional
 * <li> FILTER string(255) optional
 * </ul>
 *
 * @package Bitrix\Crm
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Volume_Query query()
 * @method static EO_Volume_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Volume_Result getById($id)
 * @method static EO_Volume_Result getList(array $parameters = [])
 * @method static EO_Volume_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Volume createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Volume_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Volume wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Volume_Collection wakeUpCollection($rows)
 */


class VolumeTable extends ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_volume';
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
				'default_value' => 0,
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'default_value' => function()
				{
					return new DateTime();
				},
			),
			'DATE_CREATE' => array(
				'data_type' => 'date',
			),
			'STAGE_SEMANTIC_ID' => array(
				'data_type' => 'enum',
				'values' => array(
					Crm\PhaseSemantics::UNDEFINED,
					Crm\PhaseSemantics::PROCESS,
					Crm\PhaseSemantics::SUCCESS,
					Crm\PhaseSemantics::FAILURE,
				),
				'default_value' => Crm\PhaseSemantics::UNDEFINED,
			),

			'ENTITY_SIZE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ENTITY_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
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
			'EVENT_SIZE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'EVENT_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ACTIVITY_SIZE' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ACTIVITY_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),

			'AGENT_LOCK' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Cleaner::TASK_STATUS_NONE,
					Volume\Cleaner::TASK_STATUS_WAIT,
					Volume\Cleaner::TASK_STATUS_RUNNING,
					Volume\Cleaner::TASK_STATUS_DONE,
					Volume\Cleaner::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Cleaner::TASK_STATUS_NONE,
			),
			'DROP_ENTITY' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Cleaner::TASK_STATUS_NONE,
					Volume\Cleaner::TASK_STATUS_WAIT,
					Volume\Cleaner::TASK_STATUS_RUNNING,
					Volume\Cleaner::TASK_STATUS_DONE,
					Volume\Cleaner::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Cleaner::TASK_STATUS_NONE,
			),
			'DROP_FILE' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Cleaner::TASK_STATUS_NONE,
					Volume\Cleaner::TASK_STATUS_WAIT,
					Volume\Cleaner::TASK_STATUS_RUNNING,
					Volume\Cleaner::TASK_STATUS_DONE,
					Volume\Cleaner::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Cleaner::TASK_STATUS_NONE,
			),
			'DROP_EVENT' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Cleaner::TASK_STATUS_NONE,
					Volume\Cleaner::TASK_STATUS_WAIT,
					Volume\Cleaner::TASK_STATUS_RUNNING,
					Volume\Cleaner::TASK_STATUS_DONE,
					Volume\Cleaner::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Cleaner::TASK_STATUS_NONE,
			),
			'DROP_ACTIVITY' => array(
				'data_type' => 'enum',
				'values' => array(
					Volume\Cleaner::TASK_STATUS_NONE,
					Volume\Cleaner::TASK_STATUS_WAIT,
					Volume\Cleaner::TASK_STATUS_RUNNING,
					Volume\Cleaner::TASK_STATUS_DONE,
					Volume\Cleaner::TASK_STATUS_CANCEL,
				),
				'default_value' => Volume\Cleaner::TASK_STATUS_NONE,
			),
			'DROPPED_ENTITY_COUNT' => array(
				'data_type' => 'integer',
			),
			'DROPPED_FILE_COUNT' => array(
				'data_type' => 'integer',
			),
			'DROPPED_EVENT_COUNT' => array(
				'data_type' => 'integer',
			),
			'DROPPED_ACTIVITY_COUNT' => array(
				'data_type' => 'integer',
			),
			'LAST_ID' => array(
				'data_type' => 'integer',
			),
			'FAIL_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'LAST_ERROR' => array(
				'data_type' => 'string',
			),
			'FILTER' => array(
				'data_type' => 'string',
			),
		);
	}


	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter does not look like filter in getList. It depends by current implementation.
	 * @return void
	 */
	public static function deleteBatch(array $filter)
	{
		$whereSql = Entity\Query::buildFilterSql(static::getEntity(), $filter);

		if ($whereSql <> '')
		{
			$tableName = static::getTableName();
			$connection = Main\Application::getConnection();
			$connection->queryExecute("DELETE FROM {$tableName} WHERE {$whereSql}");
		}
	}


	/**
	 * Deletes rows by filter.
	 * @param Entity\Query|string $query Query.
	 * @param array $updateField Field list to update.
	 * @param array $compareField Field list to compare in where statement.
	 * @return void
	 */
	public static function updateFromSelect($query, array $updateField, array $compareField)
	{
		if ($query instanceof Entity\Query)
		{
			$querySql = $query->getQuery();
		}
		else
		{
			$querySql = $query;
		}

		$updateStatement = array();
		foreach ($updateField as $destinationField => $sourceField)
		{
			$sourceField = str_replace(
				array('source', 'destination'),
				array('sourceQuery', 'destinationTbl'),
				$sourceField
			);
			$updateStatement[] = "destinationTbl.{$destinationField} = {$sourceField}";
		}
		$updateSql = implode(', ', $updateStatement);

		$whereStatement = array();
		foreach ($compareField as $destinationField => $sourceField)
		{
			if (is_numeric($destinationField))
			{
				$destinationField = $sourceField;
			}
			$whereStatement[] = "(
				(destinationTbl.{$destinationField} = sourceQuery.{$sourceField}) OR 
				(destinationTbl.{$destinationField} IS NULL AND sourceQuery.{$sourceField} IS NULL)
			)";
		}
		$whereSql = implode(' AND ', $whereStatement);

		if ($whereSql != '')
		{
			$tableName = static::getTableName();
			$connection = Main\Application::getConnection();

			$connection->queryExecute("UPDATE {$tableName} destinationTbl, ( {$querySql} ) sourceQuery SET {$updateSql} WHERE {$whereSql}");
		}
	}

	/**
	 * Removes all data
	 * @return void
	 */
	public static function purify()
	{
		$connection = Main\Application::getConnection();
		$connection->truncateTable(self::getTableName());
	}
}
