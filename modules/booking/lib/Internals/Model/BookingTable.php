<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * Class BookingTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Booking_Query query()
 * @method static EO_Booking_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Booking_Result getById($id)
 * @method static EO_Booking_Result getList(array $parameters = [])
 * @method static EO_Booking_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_Booking createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_Booking_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_Booking wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_Booking_Collection wakeUpCollection($rows)
 */
final class BookingTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_booking';
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

			(new StringField('NAME'))
				->addValidator(new LengthValidator(null, 255)),

			(new IntegerField('DATE_FROM'))
				->configureRequired(),

			(new IntegerField('DATE_TO'))
				->configureRequired(),

			(new StringField('TIMEZONE_FROM'))
				->addValidator(new LengthValidator(1, 50))
				->configureRequired(),

			(new StringField('TIMEZONE_TO'))
				->addValidator(new LengthValidator(1, 50))
				->configureRequired(),

			(new IntegerField('TIMEZONE_FROM_OFFSET'))
				->configureRequired(),

			(new IntegerField('TIMEZONE_TO_OFFSET'))
				->configureRequired(),

			(new IntegerField('DATE_MAX'))
				->configureRequired(),

			(new BooleanField('IS_RECURRING'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N')
				->configureRequired(),

			(new TextField('RRULE')),

			(new IntegerField('PARENT_ID'))
				->configureDefaultValue(null),

			(new BooleanField('IS_DELETED'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N')
				->configureRequired(),

			(new BooleanField('IS_CONFIRMED'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N')
				->configureRequired(),

			(new TextField('DESCRIPTION')),

			(new StringField('VISIT_STATUS'))
				->addValidator(new LengthValidator(1, 20)),

			(new StringField('SOURCE'))
				->addValidator(new LengthValidator(1, 20)),

			(new IntegerField('CREATED_BY'))
				->configureRequired(),

			(new DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime()),

			(new DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime()),
		];
	}

	private static function getReferenceMap(): array
	{
		$map = [
			(new OneToMany('RESOURCES', BookingResourceTable::class, 'BOOKING')),

			(new OneToMany('CLIENTS', BookingClientTable::class, 'BOOKING'))
				->configureJoinType(Join::TYPE_LEFT),

			(new OneToMany('EXTERNAL_DATA', BookingExternalDataTable::class, 'BOOKING'))
				->configureJoinType(Join::TYPE_LEFT),

			(new OneToMany('MESSAGES', BookingMessageTable::class, 'BOOKING'))
				->configureJoinType(Join::TYPE_LEFT),

			(new OneToMany('FAILURE_LOG_ITEMS', BookingMessageFailureLogTable::class, 'BOOKING'))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'NOTE',
				NotesTable::getEntity(),
				Join::on('this.ID', 'ref.ENTITY_ID')
					->where('ref.ENTITY_TYPE', EntityType::Booking->value)
			))
				->configureJoinType(Join::TYPE_LEFT)
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW),
		];

		return $map;
	}
}
