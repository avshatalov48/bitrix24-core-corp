<?php

namespace Bitrix\Tasks\Internals\Task\CheckList;

use Bitrix\Main\Entity\DataManager;

/**
 * Class MemberTable
 */
class MemberTable extends DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_checklist_items_member';
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
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],

			'ITEM_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],

			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],

			'TYPE' => [
				'data_type' => 'string',
				'required' => true,
			],

			// references
			'USER' => [
				'data_type' => 'Bitrix\Main\User',
				'reference' => ['=this.USER_ID' => 'ref.ID'],
			],

			'CHECKLIST_ITEM' => [
				'data_type' => 'Bitrix\Tasks\Internals\Task\CheckList',
				'reference' => ['=this.ITEM_ID' => 'ref.ID'],
			],
		];
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
			WHERE ITEM_ID IN {$ids}
		");
	}
}