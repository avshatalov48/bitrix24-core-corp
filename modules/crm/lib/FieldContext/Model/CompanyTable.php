<?php

namespace Bitrix\Crm\FieldContext\Model;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class CompanyTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Company_Query query()
 * @method static EO_Company_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Company_Result getById($id)
 * @method static EO_Company_Result getList(array $parameters = [])
 * @method static EO_Company_Entity getEntity()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Company createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Company_Collection createCollection()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Company wakeUpObject($row)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Company_Collection wakeUpCollection($rows)
 */
class CompanyTable extends Base
{

	public static function getTableName(): string
	{
		return 'b_crm_company_fields_context';
	}

	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('COMPANY_ID'))
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
		return 'COMPANY_ID';
	}
}