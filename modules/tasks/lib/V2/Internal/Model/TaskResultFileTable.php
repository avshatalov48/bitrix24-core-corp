<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Model;

use Bitrix\Main\ORM\Data\AddStrategy\Trait\AddMergeTrait;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class TaskResultFileTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TaskResultFile_Query query()
 * @method static EO_TaskResultFile_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TaskResultFile_Result getById($id)
 * @method static EO_TaskResultFile_Result getList(array $parameters = [])
 * @method static EO_TaskResultFile_Entity getEntity()
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_TaskResultFile createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_TaskResultFile_Collection createCollection()
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_TaskResultFile wakeUpObject($row)
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_TaskResultFile_Collection wakeUpCollection($rows)
 */
class TaskResultFileTable extends DataManager
{
	use DeleteByFilterTrait;
	use AddMergeTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_task_result_file';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('RESULT_ID'))
				->configureRequired(),
			(new IntegerField('FILE_ID'))
				->configureRequired(),
		];
	}
}
