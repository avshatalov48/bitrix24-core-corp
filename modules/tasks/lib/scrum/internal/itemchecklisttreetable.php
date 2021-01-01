<?php
namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields;

class ItemChecklistTreeTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_tasks_scrum_item_checklist_tree';
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function getMap()
	{
		$parentId = new Fields\IntegerField('PARENT_ID');
		$parentId->configurePrimary(true);

		$childId = new Fields\IntegerField('CHILD_ID');
		$childId->configurePrimary(true);

		$level = new Fields\IntegerField('LEVEL');

		return [
			$parentId,
			$childId,
			$level
		];
	}

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