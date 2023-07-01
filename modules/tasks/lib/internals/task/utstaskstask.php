<?php

namespace Bitrix\Tasks\Internals\Task;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;

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

	/**
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			new IntegerField(
				'VALUE_ID',
				[
					'primary' => true,
				]
			),
			new TextField(
				'UF_CRM_TASK'
			),
			new TextField(
				'UF_TASK_WEBDAV_FILES'
			),
		];
	}
}