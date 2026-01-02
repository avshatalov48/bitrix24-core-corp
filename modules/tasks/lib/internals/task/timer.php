<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\ORM\Data\AddStrategy\Trait\AddInsertIgnoreTrait;
use Bitrix\Main\ORM\Data\AddStrategy\Trait\AddMergeTrait;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Tasks\Internals\UpdateByFilterTrait;

/**
 * Class TimerTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Timer_Query query()
 * @method static EO_Timer_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Timer_Result getById($id)
 * @method static EO_Timer_Result getList(array $parameters = [])
 * @method static EO_Timer_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer_Collection wakeUpCollection($rows)
 */
class TimerTable extends DataManager
{
	use UpdateByFilterTrait;
	use AddInsertIgnoreTrait;
	use AddMergeTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_timer';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('USER_ID'))
				->configurePrimary(),

			(new IntegerField('TASK_ID'))
				->configureRequired(),

			(new IntegerField('TIMER_STARTED_AT'))
				->configureRequired(),

			(new IntegerField('TIMER_ACCUMULATOR'))
				->configureRequired(),
		];
	}
}