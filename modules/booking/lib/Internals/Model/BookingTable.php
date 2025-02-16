<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\ExpressionField;
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
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

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

			(new Reference('NOTE', NotesTable::getEntity(), Join::on('this.ID', 'ref.BOOKING_ID')))
				->configureJoinType(Join::TYPE_LEFT)
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW),
		];

		$notificationsRefMap = [
			'IS_PRIMARY_RESOURCE_INFO_ON' => 'IS_INFO_ON',
			'IS_PRIMARY_RESOURCE_CONFIRMATION_ON' => 'IS_CONFIRMATION_ON',
			'IS_PRIMARY_RESOURCE_REMINDER_ON' => 'IS_REMINDER_ON',
			'IS_PRIMARY_RESOURCE_FEEDBACK_ON' => 'IS_FEEDBACK_ON',
			'IS_PRIMARY_RESOURCE_DELAYED_ON' => 'IS_DELAYED_ON',
		];
		foreach ($notificationsRefMap as $computedColumnName => $columnName)
		{
			$map[] = (new ExpressionField(
				$computedColumnName,
				"
					CASE WHEN EXISTS (
						SELECT 1
           				FROM `b_booking_resource_notification_settings` `RNS`,
						(
							SELECT
								MIN(`ID`) `ID`,
								`BOOKING_ID`
							FROM `b_booking_booking_resource`
							GROUP BY `BOOKING_ID`
						) `PRIMARY_RESOURCES`,
                		`b_booking_booking_resource` `PRIMARY_RESOURCE` 
                    	WHERE
               				`RNS`.`"  . $columnName . "` = 'Y'
               				AND `PRIMARY_RESOURCES`.`BOOKING_ID` = `booking_internals_model_booking`.`ID`
               				AND `PRIMARY_RESOURCE`.ID = `PRIMARY_RESOURCES`.`ID`
                			AND `PRIMARY_RESOURCE`.`RESOURCE_ID` = `RNS`.`ID`
					) THEN 'Y' ELSE 'N' END
				",
				[
					'ID',
				],
				[
					'data_type' => 'string',
				]
			));
		}

		return $map;
	}
}
