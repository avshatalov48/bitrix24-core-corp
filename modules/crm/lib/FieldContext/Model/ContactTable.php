<?php

namespace Bitrix\Crm\FieldContext\Model;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

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