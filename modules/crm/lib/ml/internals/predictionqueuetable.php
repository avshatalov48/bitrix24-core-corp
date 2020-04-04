<?php

namespace Bitrix\Crm\Ml\Internals;
use Bitrix\Crm\Ml\PredictionQueue;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

class PredictionQueueTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_ml_prediction_queue';
	}

	public static function getObjectClass()
	{
		return PredictionQueue::class;
	}

	public static function getMap()
	{
		return [
			new IntegerField("ID", [
				"primary" => true,
				"autocomplete" => true
			]),
			new DatetimeField("CREATED", [
				"required" => true,
				"default_value" => function()
				{
					return new DateTime();
				}
			]),
			new DatetimeField("DELAYED_UNTIL"),
			new IntegerField("ENTITY_TYPE_ID"),
			new IntegerField("ENTITY_ID"),
			new StringField("TYPE"),
			new ArrayField("ADDITIONAL_PARAMETERS"),
			new StringField("STATE"),
			new StringField("ERROR")
		];
	}
}