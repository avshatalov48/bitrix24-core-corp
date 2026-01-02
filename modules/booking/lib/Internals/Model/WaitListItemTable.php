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
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * Class WaitListItemTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_WaitListItem_Query query()
 * @method static EO_WaitListItem_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_WaitListItem_Result getById($id)
 * @method static EO_WaitListItem_Result getList(array $parameters = [])
 * @method static EO_WaitListItem_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_WaitListItem createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_WaitListItem_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_WaitListItem wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_WaitListItem_Collection wakeUpCollection($rows)
 */
class WaitListItemTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_wait_list_item';
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

			(new IntegerField('CREATED_BY'))
				->configureRequired(),

			(new DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime()),

			(new DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime()),

			(new BooleanField('IS_DELETED'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N')
				->configureRequired(),
		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new OneToMany('CLIENTS', BookingClientTable::class, 'WAIT_LIST'))
				->configureJoinType(Join::TYPE_LEFT),

			(new OneToMany('EXTERNAL_DATA', BookingExternalDataTable::class, 'WAIT_LIST'))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'NOTE',
				NotesTable::getEntity(),
				Join::on('this.ID', 'ref.ENTITY_ID')
					->where('ref.ENTITY_TYPE', EntityType::WaitList->value)
			))
				->configureJoinType(Join::TYPE_LEFT)
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW),
		];
	}
}
