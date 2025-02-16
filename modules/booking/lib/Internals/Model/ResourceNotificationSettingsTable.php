<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class ResourceNotificationSettingsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ResourceNotificationSettings_Query query()
 * @method static EO_ResourceNotificationSettings_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ResourceNotificationSettings_Result getById($id)
 * @method static EO_ResourceNotificationSettings_Result getList(array $parameters = [])
 * @method static EO_ResourceNotificationSettings_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_ResourceNotificationSettings_Collection wakeUpCollection($rows)
 */
final class ResourceNotificationSettingsTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_resource_notification_settings';
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

			(new IntegerField('RESOURCE_ID'))
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
