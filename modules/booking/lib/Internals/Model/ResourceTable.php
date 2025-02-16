<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class ResourceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Resource_Query query()
 * @method static EO_Resource_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Resource_Result getById($id)
 * @method static EO_Resource_Result getList(array $parameters = [])
 * @method static EO_Resource_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_Resource createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_Resource_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_Resource wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_Resource_Collection wakeUpCollection($rows)
 */
class ResourceTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_resource';
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

			(new IntegerField('TYPE_ID'))
				->configureRequired(),

			(new IntegerField('EXTERNAL_ID'))
				->configureDefaultValue(null),

			(new BooleanField('IS_MAIN'))
				->configureValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired(),
		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new Reference('TYPE', ResourceTypeTable::getEntity(), Join::on('this.TYPE_ID', 'ref.ID'))),
			(new OneToMany('SETTINGS', ResourceSettingsTable::class, 'RESOURCE'))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW),
			(new Reference('DATA',  ResourceDataTable::getEntity(), Join::on('this.ID', 'ref.RESOURCE_ID'))),
			(new Reference(
				'NOTIFICATION_SETTINGS',
				ResourceNotificationSettingsTable::getEntity(),
				Join::on('this.ID', 'ref.RESOURCE_ID')
			)),
		];
	}
}
