<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\FieldContext\Model\Base;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

abstract class PrototypeItemFieldsContext extends Base
{
	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('ITEM_ID'))
				->configurePrimary()
				->configureRequired(),
			(new Fields\StringField('FIELD_NAME'))
				->configureSize(50)
				->configurePrimary()
				->configureRequired()
				->addValidator(new LengthValidator(1, 50)),
			(new Fields\StringField('VALUE_ID'))
				->configurePrimary()
				->addValidator(new LengthValidator(1, 20)),
			(new Fields\IntegerField('CONTEXT'))
				->configureRequired(),
		];
	}

	public function getIdColumnName(): string
	{
		return 'ITEM_ID';
	}
}