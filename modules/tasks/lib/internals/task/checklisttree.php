<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Entity\DataManager;

/**
 * Class CheckListTree
 *
 * @package Bitrix\Tasks\Internals\Task
 */
class CheckListTreeTable extends DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_checklist_items_tree';
	}

	/**
	 * @return string
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'PARENT_ID' => [
				'data_type' => 'integer',
				'primary' => true
			],

			'CHILD_ID' => [
				'data_type' => 'integer',
				'primary' => true
			],

			'LEVEL' => [
				'data_type' => 'integer',
			]
		);
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
			WHERE PARENT_ID IN {$ids} OR CHILD_ID IN {$ids}
		");
	}
}