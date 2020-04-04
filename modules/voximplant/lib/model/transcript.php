<?php

namespace Bitrix\Voximplant\Model;


use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;

class TranscriptTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_voximplant_transcript';
	}

	public static function getMap()
	{
		return array(
			'ID' => new IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'COST' => new FloatField('COST'),
			'COST_CURRENCY' => new StringField('COST_CURRENCY'),
			'SESSION_ID' => new IntegerField('SESSION_ID'),
			'CALL_ID' => new StringField('CALL_ID'),
			'CONTENT' => new TextField('CONTENT'),
			'URL' => new StringField('URL'),
		);
	}
}