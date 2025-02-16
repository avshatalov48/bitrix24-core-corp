<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

/**
 * Class JournalTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Journal_Query query()
 * @method static EO_Journal_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Journal_Result getById($id)
 * @method static EO_Journal_Result getList(array $parameters = [])
 * @method static EO_Journal_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_Journal createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_Journal_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_Journal wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_Journal_Collection wakeUpCollection($rows)
 */
final class JournalTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_journal';
	}

	/**
	 * @throws ArgumentTypeException|SystemException
	 */
	public static function getMap(): array
	{
		return array_merge(
			static::getScalarMap(),
		);
	}

	/**
	 * @throws ArgumentTypeException|SystemException
	 */
	private static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('ENTITY_ID'))
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

			(new StringField('INFO'))
				->addValidator(new LengthValidator(1, 512)),

			(new DatetimeField('CREATED_AT')),
		];
	}
}
