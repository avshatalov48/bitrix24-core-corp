<?php

namespace Bitrix\Call\Model;

use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Call\Integration\AI\Outcome\Property;

/**
 * Class CallOutcomePropertyTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallOutcomeProperty_Query query()
 * @method static EO_CallOutcomeProperty_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CallOutcomeProperty_Result getById($id)
 * @method static EO_CallOutcomeProperty_Result getList(array $parameters = [])
 * @method static EO_CallOutcomeProperty_Entity getEntity()
 * @method static \Bitrix\Call\Integration\AI\Outcome\Property createObject($setDefaultValues = true)
 * @method static \Bitrix\Call\Model\EO_CallOutcomeProperty_Collection createCollection()
 * @method static \Bitrix\Call\Integration\AI\Outcome\Property wakeUpObject($row)
 * @method static \Bitrix\Call\Model\EO_CallOutcomeProperty_Collection wakeUpCollection($rows)
 */
class CallOutcomePropertyTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_call_outcome_property';
	}

	public static function getObjectClass(): string
	{
		return Property::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('OUTCOME_ID'))
				->configureRequired(),

			(new StringField('CODE'))
				->configureSize(100)
				->configureRequired(),

			(new TextField('CONTENT'))
				->configureLong()
				->configureNullable(),

			(new Reference('OUTCOME', CallOutcomeTable::class, Join::on('this.OUTCOME_ID', 'ref.ID'))),
		];
	}
}


