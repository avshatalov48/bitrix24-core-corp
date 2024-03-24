<?php

namespace Bitrix\Crm\FieldContext\Model;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class QuoteTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Quote_Query query()
 * @method static EO_Quote_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Quote_Result getById($id)
 * @method static EO_Quote_Result getList(array $parameters = [])
 * @method static EO_Quote_Entity getEntity()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Quote createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Quote_Collection createCollection()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Quote wakeUpObject($row)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Quote_Collection wakeUpCollection($rows)
 */
class QuoteTable extends Base
{

	public static function getTableName(): string
	{
		return 'b_crm_quote_fields_context';
	}

	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('QUOTE_ID'))
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
		return 'QUOTE_ID';
	}
}