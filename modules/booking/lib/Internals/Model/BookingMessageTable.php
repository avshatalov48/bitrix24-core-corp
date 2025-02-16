<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class BookingMessageTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_BookingMessage_Query query()
 * @method static EO_BookingMessage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_BookingMessage_Result getById($id)
 * @method static EO_BookingMessage_Result getList(array $parameters = [])
 * @method static EO_BookingMessage_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingMessage createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingMessage wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingMessage_Collection wakeUpCollection($rows)
 */
final class BookingMessageTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_booking_message';
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

			(new StringField('NOTIFICATION_TYPE'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new StringField('SENDER_MODULE_ID'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new StringField('SENDER_CODE'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new IntegerField('EXTERNAL_MESSAGE_ID'))
				->configureRequired()
				->configureRequired(),

			(new DatetimeField('CREATED_AT')),
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
