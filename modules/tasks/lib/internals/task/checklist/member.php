<?php

namespace Bitrix\Tasks\Internals\Task\CheckList;

use Bitrix\Main\Entity\DataManager;

/**
 * Class MemberTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Member_Query query()
 * @method static EO_Member_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Member_Result getById($id)
 * @method static EO_Member_Result getList(array $parameters = [])
 * @method static EO_Member_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\CheckList\EO_Member_Collection wakeUpCollection($rows)
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