<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;

class TranscriptTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_voximplant_transcript';
	}

	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'COST' => new Entity\FloatField('COST'),
			'COST_CURRENCY' => new Entity\StringField('COST_CURRENCY'),
			'SESSION_ID' => new Entity\IntegerField('SESSION_ID'),
			'CONTENT' => new Entity\TextField('CONTENT'),
			'URL' => new Entity\StringField('URL'),
		);
	}
}