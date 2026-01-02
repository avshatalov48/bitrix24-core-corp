<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Internals\TaskTable;

/**
 * Class ElapsedTimeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ElapsedTime_Query query()
 * @method static EO_ElapsedTime_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ElapsedTime_Result getById($id)
 * @method static EO_ElapsedTime_Result getList(array $parameters = [])
 * @method static EO_ElapsedTime_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ElapsedTime_Collection wakeUpCollection($rows)
 */
class ElapsedTimeTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_elapsed_time';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new DatetimeField('CREATED_DATE'))
				->configureRequired(),

			(new DatetimeField('DATE_START')),

			(new DatetimeField('DATE_STOP')),

			(new IntegerField('USER_ID'))
				->configureRequired(),

			(new IntegerField('TASK_ID'))
				->configureRequired(),

			(new IntegerField('MINUTES'))
				->configureRequired(),

			(new IntegerField('SECONDS'))
				->configureRequired(),

			(new IntegerField('SOURCE')),

			(new TextField('COMMENT_TEXT')),

			(new Reference('USER', UserTable::getEntity(), Join::on('this.USER_ID', 'ref.ID'))),

			(new Reference('TASK', TaskTable::getEntity(), Join::on('this.TASK_ID', 'ref.ID'))),
		];
	}
}