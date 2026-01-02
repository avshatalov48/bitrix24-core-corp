<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\ORM\Data\AddStrategy\Trait\AddInsertIgnoreTrait;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class RelatedTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Related_Query query()
 * @method static EO_Related_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Related_Result getById($id)
 * @method static EO_Related_Result getList(array $parameters = [])
 * @method static EO_Related_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Related createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Related_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Related wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Related_Collection wakeUpCollection($rows)
 */
class RelatedTable extends DataManager
{
	use AddInsertIgnoreTrait;
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_dependence';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('TASK_ID'))
				->configurePrimary(),

			(new IntegerField('DEPENDS_ON_ID'))
				->configurePrimary(),
		];
	}
}
