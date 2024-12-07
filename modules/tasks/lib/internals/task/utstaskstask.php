<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class TasksTaskTable
 *
 * Fields:
 * <ul>
 * <li> VALUE_ID int mandatory
 * <li> UF_CRM_TASK text optional
 * </ul>
 *
 * @package Bitrix\Uts
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UtsTasksTask_Query query()
 * @method static EO_UtsTasksTask_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_UtsTasksTask_Result getById($id)
 * @method static EO_UtsTasksTask_Result getList(array $parameters = [])
 * @method static EO_UtsTasksTask_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_UtsTasksTask_Collection wakeUpCollection($rows)
 */
class UtsTasksTaskTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_uts_tasks_task';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('VALUE_ID'))
				->configurePrimary(),
			(new ArrayField('UF_CRM_TASK'))
				->configureUnserializeCallback(static::getUnSerializeModifier()),
			(new ArrayField('UF_TASK_WEBDAV_FILES'))
				->configureUnserializeCallback(static::getUnSerializeModifier()),
		];
	}

	private static function getUnSerializeModifier(): callable
	{
		return function (mixed $value): array
		{
			if (is_array($value))
			{
				return $value;
			}

			if (!is_string($value))
			{
				return [];
			}

			$value = unserialize($value, ['allowed_classes' => false]);

			return is_array($value) ? $value : [];
		};
	}
}