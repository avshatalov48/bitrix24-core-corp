<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\ORM\Data\AddStrategy\Trait\AddMergeTrait;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Internals\TaskTable;

/**
 * Class ReminderTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Reminder_Query query()
 * @method static EO_Reminder_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Reminder_Result getById($id)
 * @method static EO_Reminder_Result getList(array $parameters = [])
 * @method static EO_Reminder_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Reminder_Collection wakeUpCollection($rows)
 */
class ReminderTable extends DataManager
{
	use AddMergeTrait;
	use DeleteByFilterTrait;

	public const TYPE_DEADLINE = 'D';
	public const TYPE_COMMON = 'A';
	public const TYPE_RECURRING = 'R';

	public const TRANSPORT_JABBER = 'J';
	public const TRANSPORT_EMAIL = 'E';

	public const RECIPIENT_TYPE_SELF = 'S';
	public const RECIPIENT_TYPE_RESPONSIBLE = 'R';
	public const RECIPIENT_TYPE_ORIGINATOR = 'O';
	public const RECIPIENT_TYPE_ACCOMPLICE = 'A';

	public static function getTableName(): string
	{
		return 'b_tasks_reminder';
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

			(new IntegerField('USER_ID'))
				->configureRequired(),

			(new IntegerField('TASK_ID'))
				->configureRequired(),

			(new DatetimeField('REMIND_DATE'))
				->configureRequired(),

			(new EnumField('TYPE'))
				->configureValues([static::TYPE_DEADLINE, static::TYPE_COMMON, static::TYPE_RECURRING])
				->configureRequired(),

			(new EnumField('TRANSPORT'))
				->configureValues([static::TRANSPORT_EMAIL, static::TRANSPORT_JABBER])
				->configureRequired(),

			(new EnumField('RECEPIENT_TYPE'))
				->configureValues([
					static::RECIPIENT_TYPE_SELF,
					static::RECIPIENT_TYPE_ORIGINATOR,
					static::RECIPIENT_TYPE_RESPONSIBLE,
					static::RECIPIENT_TYPE_ACCOMPLICE
				])
				->configureRequired(),

			(new IntegerField('BEFORE_DEADLINE'))
				->configureNullable()
				->configureDefaultValue(null),

			(new ArrayField('RRULE'))
				->configureNullable()
				->configureDefaultValue(null)
				->configureSerializationJson(),

			(new Reference('USER', UserTable::getEntity(), Join::on('this.USER_ID', 'ref.ID')))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference('TASK', TaskTable::getEntity(), Join::on('this.TASK_ID', 'ref.ID')))
				->configureJoinType(Join::TYPE_LEFT),
		];
	}
}
