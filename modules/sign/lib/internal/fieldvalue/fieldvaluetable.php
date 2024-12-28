<?php

namespace Bitrix\Sign\Internal\FieldValue;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class FieldValueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FieldValue_Query query()
 * @method static EO_FieldValue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FieldValue_Result getById($id)
 * @method static EO_FieldValue_Result getList(array $parameters = [])
 * @method static EO_FieldValue_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\FieldValue\FieldValue createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\FieldValue\FieldValueCollection createCollection()
 * @method static \Bitrix\Sign\Internal\FieldValue\FieldValue wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\FieldValue\FieldValueCollection wakeUpCollection($rows)
 */
class FieldValueTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return FieldValue::class;
	}

	public static function getCollectionClass(): string
	{
		return FieldValueCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_sign_field_value';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new IntegerField('MEMBER_ID'))
				->configureRequired()
				->configureTitle('Member ID')
			,
			(new StringField('FIELD_NAME'))
				->configureRequired()
				->configureTitle('Field name')
				->addValidator(new Entity\Validator\Length(1, 255))
			,
			(new TextField('VALUE'))
				->configureTitle('Value')
			,
			(new DatetimeField('DATE_CREATE'))
				->configureTitle('Date create')
				->configureRequired()
			,
			(new DatetimeField('DATE_MODIFY'))
				->configureTitle('Date modify')
				->configureNullable()
			,
		];
	}
}