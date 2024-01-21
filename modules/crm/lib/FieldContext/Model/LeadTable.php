<?php

namespace Bitrix\Crm\FieldContext\Model;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class LeadTable extends Base
{

	public static function getTableName(): string
	{
		return 'b_crm_lead_fields_context';
	}

	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('LEAD_ID'))
				->configureRequired()
				->configurePrimary(),
			(new Fields\StringField('FIELD_NAME'))
				->configureSize(50)
				->configureRequired()
				->configurePrimary()
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
		return 'LEAD_ID';
	}
}