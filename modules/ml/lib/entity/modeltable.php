<?php

namespace Bitrix\Ml\Entity;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Ml\Model;

/**
 * Class ModelTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Model_Query query()
 * @method static EO_Model_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Model_Result getById($id)
 * @method static EO_Model_Result getList(array $parameters = array())
 * @method static EO_Model_Entity getEntity()
 * @method static \Bitrix\Ml\Model createObject($setDefaultValues = true)
 * @method static \Bitrix\Ml\Entity\EO_Model_Collection createCollection()
 * @method static \Bitrix\Ml\Model wakeUpObject($row)
 * @method static \Bitrix\Ml\Entity\EO_Model_Collection wakeUpCollection($rows)
 */
class ModelTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_ml_model';
	}

	public static function getObjectClass()
	{
		return Model::class;
	}

	public static function getMap()
	{
		return [
			new IntegerField("ID", [
				"primary" => true,
				"autocomplete" => true
			]),
			new StringField("NAME", [
				"required" => true
			]),
			new StringField("TYPE"),
			new IntegerField("VERSION"),
			new StringField("STATE", [
				"default_value" => ""
			])
		];
	}
}
