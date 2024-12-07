<?php

namespace Bitrix\Crm\Activity\Provider\Sms;

use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class SmsPlaceholderTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SmsPlaceholder_Query query()
 * @method static EO_SmsPlaceholder_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SmsPlaceholder_Result getById($id)
 * @method static EO_SmsPlaceholder_Result getList(array $parameters = [])
 * @method static EO_SmsPlaceholder_Entity getEntity()
 * @method static \Bitrix\Crm\Activity\Provider\Sms\EO_SmsPlaceholder createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Activity\Provider\Sms\EO_SmsPlaceholder_Collection createCollection()
 * @method static \Bitrix\Crm\Activity\Provider\Sms\EO_SmsPlaceholder wakeUpObject($row)
 * @method static \Bitrix\Crm\Activity\Provider\Sms\EO_SmsPlaceholder_Collection wakeUpCollection($rows)
 */
class SmsPlaceholderTable extends \Bitrix\Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act_sms_placeholder';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('ENTITY_TYPE_ID'))
				->addValidator(new LengthValidator(1, 32767)),
			(new IntegerField('ENTITY_CATEGORY_ID'))
				->configureNullable()
				->addValidator(new LengthValidator(1, 32767)),
			(new IntegerField('TEMPLATE_ID'))
				->configureRequired(),
			(new StringField('PLACEHOLDER_ID'))
				->configureRequired()
				->configureSize(255)
				->addValidator(new LengthValidator(1, 255)),
			(new StringField('FIELD_NAME'))
				->configureSize(255)
				->addValidator(new LengthValidator(null, 255)),
			(new StringField('FIELD_ENTITY_TYPE'))
				->configureSize(255)
				->addValidator(new LengthValidator(null, 255)),
			(new StringField('FIELD_VALUE'))
				->configureSize(255)
				->addValidator(new LengthValidator(null, 255)),
		];
	}

	public static function onBeforeAdd(Event $event): EventResult
	{
		return static::check($event);
	}

	public static function onBeforeUpdate(Event $event): EventResult
	{
		return static::check($event);
	}

	protected static function check(Event $event): EventResult
	{
		$result = new EventResult();

		$fields = $event->getParameter('fields');

		if (
			(isset($fields['FIELD_VALUE']) && (string)$fields['FIELD_VALUE'] !== '')
			|| (
				isset($fields['FIELD_NAME'], $fields['FIELD_ENTITY_TYPE'])
				&& $fields['FIELD_NAME'] !== ''
				&& $fields['FIELD_ENTITY_TYPE'] !== ''
			)
		)
		{
			return $result;
		}

		$result->addError(new EntityError('Field FIELD_VALUE or fields: FIELD_NAME and FIELD_ENTITY_TYPE must be set'));

		return $result;
	}
}