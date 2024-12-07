<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main;
use Bitrix\Main\Entity;

/**
 * Class TranscriptLineTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TranscriptLine_Query query()
 * @method static EO_TranscriptLine_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TranscriptLine_Result getById($id)
 * @method static EO_TranscriptLine_Result getList(array $parameters = [])
 * @method static EO_TranscriptLine_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_TranscriptLine createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_TranscriptLine_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_TranscriptLine wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_TranscriptLine_Collection wakeUpCollection($rows)
 */
class TranscriptLineTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_voximplant_transcript_line';
	}

	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'TRANSCRIPT_ID' => new Entity\StringField('TRANSCRIPT_ID'),
			'SIDE' => new Entity\StringField('SIDE'),
			'START_TIME' => new Entity\IntegerField('START_TIME'),
			'STOP_TIME' => new Entity\IntegerField('STOP_TIME'),
			'MESSAGE' => new Entity\TextField('MESSAGE'),
			'TRANSCRIPT' => new Entity\ReferenceField("TRANSCRIPT", TranscriptTable::getEntity(), array(
				"=this.TRANSCRIPT_ID" => "ref.ID"
			))
		);
	}

	public static function deleteByTranscriptId($transcriptId)
	{
		$transcriptId = (int)$transcriptId;

		if($transcriptId <= 0)
		{
			throw new Main\ArgumentException('transcriptId must be greater than zero.', 'transcriptId');
		}

		$helper = Main\Application::getConnection()->getSqlHelper();

		Main\Application::getConnection()->queryExecute("DELETE FROM ".$helper->quote(static::getTableName())." WHERE TRANSCRIPT_ID = {$transcriptId}");
	}
}