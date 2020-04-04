<?php
namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Voximplant\Ivr\Item;

class IvrItemTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_voximplant_ivr_item';
	}
	
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'CODE' => new Entity\StringField('CODE', array(
				'size' => 50
			)),
			'IVR_ID' => new Entity\IntegerField('IVR_ID'),
			'NAME' => new Entity\StringField('NAME', array(
				'size' => 255
			)),
			'TYPE' => new Entity\StringField('TYPE', array(
				'size' => 10
			)),
			'URL' => new Entity\StringField('URL', array(
				'size' => 2000
			)),
			'MESSAGE' => new Entity\TextField('MESSAGE'),
			'FILE_ID' => new Entity\IntegerField('FILE_ID'),
			'TIMEOUT' => new Entity\IntegerField('TIMEOUT'),
			'TIMEOUT_ACTION' => new Entity\StringField('TIMEOUT_ACTION', array(
				'default_value' => Item::TIMEOUT_ACTION_EXIT,
			)),
			'TTS_VOICE' => new Entity\StringField('TTS_VOICE', array(
				'size' => 50
			)),
			'TTS_SPEED' => new Entity\StringField('TTS_SPEED', array(
				'size' => 20
			)),
			'TTS_VOLUME' => new Entity\StringField('TTS_VOLUME', array(
				'size' => 20
			)),
			'IVR' => new Entity\ReferenceField(
				'IVR',
				IvrTable::getEntity(),
				array('=this.IVR_ID' => 'ref.ID'),
				array('join_type' => 'inner')
			)
		);
	}
}