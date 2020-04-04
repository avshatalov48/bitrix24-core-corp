<?php

namespace Bitrix\Ml\Entity;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Ml\Model;

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