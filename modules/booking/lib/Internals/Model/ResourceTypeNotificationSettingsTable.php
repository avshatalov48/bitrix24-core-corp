<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class ResourceTypeNotificationSettingsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ResourceTypeNotificationSettings_Query query()
 * @method static EO_ResourceTypeNotificationSettings_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ResourceTypeNotificationSettings_Result getById($id)
 * @method static EO_ResourceTypeNotificationSettings_Result getList(array $parameters = [])
 * @method static EO_ResourceTypeNotificationSettings_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceTypeNotificationSettings_Collection wakeUpCollection($rows)
 */
final class ResourceTypeNotificationSettingsTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_resource_type_notification_settings';
	}

	public static function getMap(): array
	{
		return array_merge(
			static::getScalarMap(),
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

			(new BooleanField('IS_INFO_ON'))
				->configureValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired(),

			(new StringField('TEMPLATE_TYPE_INFO'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new BooleanField('IS_CONFIRMATION_ON'))
				->configureValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired(),

			(new StringField('TEMPLATE_TYPE_CONFIRMATION'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new BooleanField('IS_REMINDER_ON'))
				->configureValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired(),

			(new StringField('TEMPLATE_TYPE_REMINDER'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new BooleanField('IS_FEEDBACK_ON'))
				->configureValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired(),

			(new StringField('TEMPLATE_TYPE_FEEDBACK'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new BooleanField('IS_DELAYED_ON'))
				->configureValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired(),

			(new StringField('TEMPLATE_TYPE_DELAYED'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),
		];
	}
}
