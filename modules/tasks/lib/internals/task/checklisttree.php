<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Entity\DataManager;

/**
 * Class CheckListTree
 *
 * @package Bitrix\Tasks\Internals\Task
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CheckListTree_Query query()
 * @method static EO_CheckListTree_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CheckListTree_Result getById($id)
 * @method static EO_CheckListTree_Result getList(array $parameters = [])
 * @method static EO_CheckListTree_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_CheckListTree_Collection wakeUpCollection($rows)
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