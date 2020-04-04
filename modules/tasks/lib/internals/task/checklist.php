<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;

/**
 * Class CheckListTable
 *
 * @package Bitrix\Tasks\Internals\Task
 */
class CheckListTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_checklist_items';
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * @return string|null
	 */
	public static function getUfId()
	{
		return 'TASKS_TASK_CHECKLIST';
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
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TOGGLED_BY' => array(
				'data_type' => 'integer',
			),
			'TOGGLED_DATE' => array(
				'data_type' => 'datetime',
			),
			'TITLE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTitle'),
			),
			'IS_COMPLETE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'IS_IMPORTANT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SORT_INDEX' => array(
				'data_type' => 'integer',
				'required' => true,
			),
		);
	}

	/**
	 * Returns validators for TITLE field.
	 *
	 * @return array
	 * @throws Main\ArgumentTypeException
	 */
	public static function validateTitle()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * @return string
	 */
	public static function getSortColumnName()
	{
		return 'SORT_INDEX';
	}

	/**
	 * @param string $ids - string of type (1,2,...,7)
	 */
	public static function deleteByCheckListsIds($ids)
	{
		global $DB;

		$tableName = static::getTableName();

		$DB->Query("
			DELETE FROM {$tableName}
			WHERE ID IN {$ids} 
		");
	}
}