<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

/**
 * Class ResourceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ResourceData_Query query()
 * @method static EO_ResourceData_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ResourceData_Result getById($id)
 * @method static EO_ResourceData_Result getList(array $parameters = [])
 * @method static EO_ResourceData_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceData createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceData_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceData wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceData_Collection wakeUpCollection($rows)
 */
class ResourceDataTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_resource_data';
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

			(new IntegerField('RESOURCE_ID'))
				->configureRequired(),

			(new StringField('NAME'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new StringField('DESCRIPTION'))
				->addValidator(new LengthValidator(0, 255)),

			(new IntegerField('CREATED_BY'))
				->configureRequired(),

			(new DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime()),
			(new DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime()),
		];
	}
}
