<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class ResourceTypeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ResourceType_Query query()
 * @method static EO_ResourceType_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ResourceType_Result getById($id)
 * @method static EO_ResourceType_Result getList(array $parameters = [])
 * @method static EO_ResourceType_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceType createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceType wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceType_Collection wakeUpCollection($rows)
 */
final class ResourceTypeTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_resource_type';
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

			(new StringField('MODULE_ID'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new StringField('CODE'))
				->configureNullable()
				->addValidator(new LengthValidator(0, 255)),

			(new StringField('NAME'))
				->addValidator(new LengthValidator(0, 255))
				->configureDefaultValue(null),
		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new OneToMany('RESOURCES', ResourceTable::class, 'TYPE'))
				->configureJoinType(Join::TYPE_LEFT)
			,
			(new Reference(
				'NOTIFICATION_SETTINGS',
				ResourceTypeNotificationSettingsTable::getEntity(),
				Join::on('this.ID', 'ref.TYPE_ID')
			)),
		];
	}
}
