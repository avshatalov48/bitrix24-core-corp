<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class BookingExternalDataTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_BookingExternalData_Query query()
 * @method static EO_BookingExternalData_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_BookingExternalData_Result getById($id)
 * @method static EO_BookingExternalData_Result getList(array $parameters = [])
 * @method static EO_BookingExternalData_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingExternalData createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingExternalData wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingExternalData_Collection wakeUpCollection($rows)
 */
final class BookingExternalDataTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_booking_booking_external_data';
	}

	public static function getMap(): array
	{
		return array_merge(
			static::getScalarMap(),
			static::getReferenceMap(),
		);
	}

	private static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('BOOKING_ID'))
				->configureRequired(),

			(new StringField('MODULE_ID'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new StringField('ENTITY_TYPE_ID'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new StringField('VALUE'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new Reference(
				'BOOKING',
				BookingTable::getEntity(),
				Join::on('this.BOOKING_ID', 'ref.ID')
			)),
		];
	}
}
