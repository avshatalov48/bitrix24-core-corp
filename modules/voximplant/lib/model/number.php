<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

/**
 * Class NumberTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Number_Query query()
 * @method static EO_Number_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Number_Result getById($id)
 * @method static EO_Number_Result getList(array $parameters = [])
 * @method static EO_Number_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_Number createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_Number_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_Number wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_Number_Collection wakeUpCollection($rows)
 */
class NumberTable extends ORM\Data\DataManager
{
	public static function getTableName()
	{
		return "b_voximplant_number";
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField("ID", [
				"primary" => true,
				"autocomplete" => true
			]),
			new Entity\StringField("NUMBER"),
			new Entity\StringField("NAME"),
			new Entity\StringField("COUNTRY_CODE"),
			new Entity\BooleanField("VERIFIED", [
				"values" => ['N', 'Y']
			]),
			new Entity\DatetimeField("DATE_CREATE", [
				"default_value" => function()
				{
					return new DateTime();
				}
			]),
			new Entity\IntegerField("SUBSCRIPTION_ID"),
			new Entity\IntegerField("CONFIG_ID"),
			new Entity\BooleanField("TO_DELETE", ["values" => ["N", "Y"]]),
			new Entity\DatetimeField("DATE_DELETE"),

			new Entity\ExpressionField('CNT', 'COUNT(*)'),
		];
	}
}