<?php

namespace Bitrix\Crm\FieldContext\Model;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class DealTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Deal_Query query()
 * @method static EO_Deal_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Deal_Result getById($id)
 * @method static EO_Deal_Result getList(array $parameters = [])
 * @method static EO_Deal_Entity getEntity()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Deal createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Deal_Collection createCollection()
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Deal wakeUpObject($row)
 * @method static \Bitrix\Crm\FieldContext\Model\EO_Deal_Collection wakeUpCollection($rows)
 */
class DealTable extends Base
{

	public static function getTableName(): string
	{
		return 'b_crm_deal_fields_context';
	}

	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('DEAL_ID'))
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
		return 'DEAL_ID';
	}
}