<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class DeadlineChangeLogTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DeadlineChangeLog_Query query()
 * @method static EO_DeadlineChangeLog_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DeadlineChangeLog_Result getById($id)
 * @method static EO_DeadlineChangeLog_Result getList(array $parameters = [])
 * @method static EO_DeadlineChangeLog_Entity getEntity()
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_DeadlineChangeLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_DeadlineChangeLog_Collection createCollection()
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_DeadlineChangeLog wakeUpObject($row)
 * @method static \Bitrix\Tasks\V2\Internal\Model\EO_DeadlineChangeLog_Collection wakeUpCollection($rows)
 */
class DeadlineChangeLogTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_deadline_change_log';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
			(new IntegerField('TASK_ID'))->configureRequired(),
			(new IntegerField('USER_ID'))->configureRequired(),
			(new DatetimeField('NEW_DEADLINE'))->configureNullable(),
			(new TextField('REASON'))->configureNullable(),
			(new DatetimeField('CHANGED_AT'))->configureRequired(),
		];
	}
}
