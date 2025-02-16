<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class BookingResourceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_BookingResource_Query query()
 * @method static EO_BookingResource_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_BookingResource_Result getById($id)
 * @method static EO_BookingResource_Result getList(array $parameters = [])
 * @method static EO_BookingResource_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingResource createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingResource wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingResource_Collection wakeUpCollection($rows)
 */
final class BookingResourceTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_booking_booking_resource';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('BOOKING_ID'))
				->configureRequired(),

			(new Reference('BOOKING', BookingTable::class,
				Join::on('this.BOOKING_ID', 'ref.ID')))
				->configureJoinType('inner'),

			(new IntegerField('RESOURCE_ID'))
				->configureRequired(),

			(new Reference('RESOURCE', ResourceTable::class,
				Join::on('this.RESOURCE_ID', 'ref.ID')))
				->configureJoinType('inner'),
		];
	}
}
