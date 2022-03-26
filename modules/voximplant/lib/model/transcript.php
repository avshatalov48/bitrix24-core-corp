<?php

namespace Bitrix\Voximplant\Model;


use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class TranscriptTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Transcript_Query query()
 * @method static EO_Transcript_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Transcript_Result getById($id)
 * @method static EO_Transcript_Result getList(array $parameters = array())
 * @method static EO_Transcript_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_Transcript createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_Transcript_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_Transcript wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_Transcript_Collection wakeUpCollection($rows)
 */
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