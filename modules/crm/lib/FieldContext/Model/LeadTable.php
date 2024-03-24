<?php

namespace Bitrix\Crm\FieldContext\Model;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class LeadTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Lead_Query query()
 * @method static EO_Lead_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Lead_Result getById($id)
 * @method static EO_Lead_Result getList(array $parameters = [])
 * @method static EO_Lead_Entity getEntity()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Lead createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Lead_Collection createCollection()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Lead wakeUpObject($row)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Lead_Collection wakeUpCollection($rows)
 */
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