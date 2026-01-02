<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Internals\Model;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\SystemException;

/**
 * Class DeadlineUserOptionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DeadlineUserOption_Query query()
 * @method static EO_DeadlineUserOption_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DeadlineUserOption_Result getById($id)
 * @method static EO_DeadlineUserOption_Result getList(array $parameters = [])
 * @method static EO_DeadlineUserOption_Entity getEntity()
 * @method static \Bitrix\Tasks\Deadline\Internals\Model\EO_DeadlineUserOption createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Deadline\Internals\Model\EO_DeadlineUserOption_Collection createCollection()
 * @method static \Bitrix\Tasks\Deadline\Internals\Model\EO_DeadlineUserOption wakeUpObject($row)
 * @method static \Bitrix\Tasks\Deadline\Internals\Model\EO_DeadlineUserOption_Collection wakeUpCollection($rows)
 */
class DeadlineUserOptionTable extends DataManager
{
	use MergeTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_deadline_user_option';
	}

	/**
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('USER_ID'))
				->configureRequired()
				->configureUnique()
			,
			(new IntegerField('DEFAULT_DEADLINE'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new BooleanField('IS_EXACT_DEADLINE_TIME'))
				->configureValues(0, 1)
				->configureDefaultValue(0)
			,
			(new StringField('SKIP_NOTIFICATION_PERIOD'))
				->configureDefaultValue('')
				->addValidator(new LengthValidator(0, 16))
			,
			(new DatetimeField('SKIP_NOTIFICATION_START_DATE'))
				->configureNullable()
			,
			(new BooleanField(name: 'CAN_CHANGE_DEADLINE' ))
				->configureValues(falseValue: 0, trueValue: 1)
				->configureDefaultValue(value: 0)
			,
			(new DatetimeField(name: 'MAX_DEADLINE_CHANGE_DATE'))
				->configureNullable()
			,
			(new IntegerField(name: 'MAX_DEADLINE_CHANGES'))
				->configureNullable()
			,
			(new BooleanField(name: 'REQUIRE_DEADLINE_CHANGE_REASON'))
				->configureValues(falseValue: 0, trueValue: 1)
				->configureDefaultValue(value: 0)
			,
		];
	}
}
