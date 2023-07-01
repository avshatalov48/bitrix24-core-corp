<?php

namespace Bitrix\Crm\Ml\Internals;
use Bitrix\Crm\Ml\PredictionQueue;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * Class PredictionQueueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_PredictionQueue_Query query()
 * @method static EO_PredictionQueue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_PredictionQueue_Result getById($id)
 * @method static EO_PredictionQueue_Result getList(array $parameters = [])
 * @method static EO_PredictionQueue_Entity getEntity()
 * @method static \Bitrix\Crm\Ml\PredictionQueue createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Ml\Internals\EO_PredictionQueue_Collection createCollection()
 * @method static \Bitrix\Crm\Ml\PredictionQueue wakeUpObject($row)
 * @method static \Bitrix\Crm\Ml\Internals\EO_PredictionQueue_Collection wakeUpCollection($rows)
 */
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