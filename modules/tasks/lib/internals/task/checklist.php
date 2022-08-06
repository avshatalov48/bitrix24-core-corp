<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;

/**
 * Class CheckListTable
 *
 * @package Bitrix\Tasks\Internals\Task
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CheckList_Query query()
 * @method static EO_CheckList_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CheckList_Result getById($id)
 * @method static EO_CheckList_Result getList(array $parameters = [])
 * @method static EO_CheckList_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckList_Collection wakeUpCollection($rows)
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
				'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
				'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
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