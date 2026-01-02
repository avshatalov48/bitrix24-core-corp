<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Booking\Internals\Model\Trait\UpdateByFilterTrait;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

/**
 * Class DelayedTaskTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DelayedTask_Query query()
 * @method static EO_DelayedTask_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DelayedTask_Result getById($id)
 * @method static EO_DelayedTask_Result getList(array $parameters = [])
 * @method static EO_DelayedTask_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_DelayedTask createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_DelayedTask_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_DelayedTask wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_DelayedTask_Collection wakeUpCollection($rows)
 */
class DelayedTaskTable extends DataManager
{
	use UpdateByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_booking_delayed_task';
	}

	public static function getMap(): array
	{
		return static::getScalarMap();
	}

	private static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new StringField('CODE'))
				->addValidator(new LengthValidator(1, 32))
				->configureRequired(),

			(new StringField('TYPE'))
				->addValidator(new LengthValidator(1, 64))
				->configureRequired(),

			(new StringField('DATA'))
				->addValidator(new LengthValidator(1))
				->configureRequired(),

			(new StringField('STATUS'))
				->addValidator(new LengthValidator(1, 10))
				->configureRequired(),

			(new DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime()),

			(new DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime()),
		];
	}
}
