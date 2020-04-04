<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

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
				"auto_complete" => true
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