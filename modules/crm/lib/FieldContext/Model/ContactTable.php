<?php

namespace Bitrix\Crm\FieldContext\Model;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class ContactTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Contact_Query query()
 * @method static EO_Contact_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Contact_Result getById($id)
 * @method static EO_Contact_Result getList(array $parameters = [])
 * @method static EO_Contact_Entity getEntity()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Contact createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Contact_Collection createCollection()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Contact wakeUpObject($row)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Contact_Collection wakeUpCollection($rows)
 */
class ContactTable extends Base
{

	public static function getTableName(): string
	{
		return 'b_crm_contact_fields_context';
	}

	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('CONTACT_ID'))
				->configurePrimary()
				->configureRequired(),
			(new Fields\StringField('FIELD_NAME'))
				->configureSize(50)
				->configurePrimary()
				->configureRequired()
				->addValidator(new LengthValidator(1, 50)),
			(new Fields\StringField('VALUE_ID'))
				->configureSize(20)
				->configurePrimary()
				->addValidator(new LengthValidator(1, 20)),
			(new Fields\IntegerField('CONTEXT'))
				->configureRequired(),
		];
	}

	public function getIdColumnName(): string
	{
		return 'CONTACT_ID';
	}
}