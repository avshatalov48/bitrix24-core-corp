<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class NotesTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Notes_Query query()
 * @method static EO_Notes_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Notes_Result getById($id)
 * @method static EO_Notes_Result getList(array $parameters = [])
 * @method static EO_Notes_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_Notes createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_Notes_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_Notes wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_Notes_Collection wakeUpCollection($rows)
 */
final class NotesTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_booking_note';
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

			(new IntegerField('BOOKING_ID'))
				->configureRequired(),

			(new StringField('DESCRIPTION'))
				->configureRequired(),
		];
	}
}
