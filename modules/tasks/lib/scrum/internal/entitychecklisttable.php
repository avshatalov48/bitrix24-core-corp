<?php
namespace Bitrix\Tasks\Scrum\Internal;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;

class EntityChecklistTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_tasks_scrum_entity_checklist_items';
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function getMap()
	{
		$id = new Fields\IntegerField('ID');
		$id->configurePrimary(true);
		$id->configureAutocomplete(true);

		$entityId = new Fields\IntegerField('ENTITY_ID');
		$entityId->configureRequired(true);

		$createdBy = new Fields\IntegerField('CREATED_BY');
		$createdBy->configureRequired(true);

		$toggledBy = new Fields\IntegerField('TOGGLED_BY');

		$toggledDate = new Fields\DatetimeField('TOGGLED_DATE');

		$title = new Fields\StringField('TITLE');
		$title->addValidator(new Validators\LengthValidator(null, 255));

		$isComplete = new Fields\BooleanField('IS_COMPLETE');
		$isComplete->configureValues('N', 'Y');
		$isComplete->configureDefaultValue('N');

		$isImportant = new Fields\BooleanField('IS_IMPORTANT');
		$isComplete->configureValues('N', 'Y');
		$isComplete->configureDefaultValue('N');

		$sortIndex = new Fields\IntegerField('SORT_INDEX');
		$sortIndex->configureRequired(true);
		$isComplete->configureDefaultValue(0);

		return [
			$id,
			$entityId,
			$createdBy,
			$toggledBy,
			$toggledDate,
			$title,
			$isComplete,
			$isImportant,
			$sortIndex
		];
	}

	public static function getSortColumnName()
	{
		return 'SORT_INDEX';
	}

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