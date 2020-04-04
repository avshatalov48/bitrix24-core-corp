<?php

namespace Bitrix\Crm\Ml\Internals;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

class ModelTrainingTable extends DataManager
{
	public static function getTableName()
	{
		return "b_crm_ml_model_training";
	}

	public static function getMap()
	{
		return [
			new IntegerField("ID", [
				"primary" => true,
				"autocomplete" => true
			]),
			new StringField("MODEL_NAME"),
			new IntegerField("RECORDS_SUCCESS", [
				"default_value" => 0
			]),
			new IntegerField("RECORDS_FAILED", [
				"default_value" => 0
			]),
			new DatetimeField("DATE_START", [
				"default_value" => function()
				{
					return new DateTime();
				}
			]),
			new DatetimeField("DATE_FINISH"),
			new StringField("STATE"),
			new FloatField("AREA_UNDER_CURVE"),
			new IntegerField("LAST_ID")
		];
	}
}