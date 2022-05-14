<?php
namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields;

/**
 * Class ItemChecklistTreeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ItemChecklistTree_Query query()
 * @method static EO_ItemChecklistTree_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ItemChecklistTree_Result getById($id)
 * @method static EO_ItemChecklistTree_Result getList(array $parameters = [])
 * @method static EO_ItemChecklistTree_Entity getEntity()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree_Collection createCollection()
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree wakeUpObject($row)
 * @method static \Bitrix\Tasks\Scrum\Internal\EO_ItemChecklistTree_Collection wakeUpCollection($rows)
 */
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