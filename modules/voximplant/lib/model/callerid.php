<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

/**
 * Class CallerIdTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallerId_Query query()
 * @method static EO_CallerId_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_CallerId_Result getById($id)
 * @method static EO_CallerId_Result getList(array $parameters = array())
 * @method static EO_CallerId_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_CallerId createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_CallerId_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_CallerId wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_CallerId_Collection wakeUpCollection($rows)
 */
class CallerIdTable extends Base
{
	public static function getTableName()
	{
		return "b_voximplant_caller_id";
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField("ID", [
				"primary" => true,
				"auto_complete" => true
			]),
			new Entity\StringField("NUMBER"),
			new Entity\BooleanField("VERIFIED", [
				"values" => ['N', 'Y']
			]),
			new Entity\DatetimeField("DATE_CREATE", [
				"default_value" => function()
				{
					return new DateTime();
				}
			]),
			new Entity\DateField("VERIFIED_UNTIL"),
			new Entity\IntegerField("CONFIG_ID"),
		];
	}

	public static function getMergeFields()
	{
		return ["NUMBER"];
	}
}