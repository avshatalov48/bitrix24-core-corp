<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class ResourceSettingsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ResourceSettings_Query query()
 * @method static EO_ResourceSettings_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ResourceSettings_Result getById($id)
 * @method static EO_ResourceSettings_Result getList(array $parameters = [])
 * @method static EO_ResourceSettings_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceSettings createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceSettings wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection wakeUpCollection($rows)
 */
final class ResourceSettingsTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_resource_settings';
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

			(new IntegerField('RESOURCE_ID'))
				->configureRequired(),

			(new StringField('WEEKDAYS'))
				->addValidator(new LengthValidator(1, 50))
				->configureRequired(),

			(new IntegerField('SLOT_SIZE')),

			(new IntegerField('TIME_FROM'))
				->configureRequired(),

			(new IntegerField('TIME_TO'))
				->configureRequired(),

			(new StringField('TIMEZONE'))
				->addValidator(new LengthValidator(1, 50))
				->configureRequired(),
		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new Reference(
				'RESOURCE',
				ResourceTable::getEntity(),
				Join::on('this.RESOURCE_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_LEFT),
		];
	}
}
