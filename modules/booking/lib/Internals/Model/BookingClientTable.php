<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Booking\Entity\Booking\BookingVisitStatus;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;

/**
 * Class BookingClientTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_BookingClient_Query query()
 * @method static EO_BookingClient_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_BookingClient_Result getById($id)
 * @method static EO_BookingClient_Result getList(array $parameters = [])
 * @method static EO_BookingClient_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingClient createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingClient wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_BookingClient_Collection wakeUpCollection($rows)
 */
final class BookingClientTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_booking_booking_client';
	}

	/**
	 * @throws SystemException|ArgumentException
	 */
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

			(new IntegerField('CLIENT_TYPE_ID'))
				->configureRequired(),

			(new IntegerField('CLIENT_ID'))
				->configureRequired(),

			(new BooleanField('IS_PRIMARY'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N')
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
			(new Reference(
				'CLIENT_TYPE',
				ClientTypeTable::getEntity(),
				Join::on('this.CLIENT_TYPE_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_INNER),

			(new ExpressionField(
				'IS_RETURNING',
				"
					CASE WHEN EXISTS (
						SELECT 1
						FROM b_booking_booking_client booking_client
						JOIN b_booking_booking booking on booking.ID = booking_client.BOOKING_ID
						WHERE
							booking_client.CLIENT_TYPE_ID = %s
							AND booking_client.CLIENT_ID = %s
							AND booking.DATE_FROM < " . strtotime("midnight") . "
							AND booking.DATE_TO < " . strtotime("tomorrow") - 1 . "
							AND booking.VISIT_STATUS IN (
								'" . BookingVisitStatus::Visited->value . "',
								'" . BookingVisitStatus::Unknown->value . "'
							)
						LIMIT 1
					) THEN 1 ELSE 0 END
				",
				[
					'CLIENT_TYPE_ID',
					'CLIENT_ID',
				],
				[
					'data_type' => 'boolean',
				]
			)),
		];
	}
}
