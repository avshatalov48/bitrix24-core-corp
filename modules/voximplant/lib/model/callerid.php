<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

class CallerIdTable extends ORM\Data\DataManager
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
}