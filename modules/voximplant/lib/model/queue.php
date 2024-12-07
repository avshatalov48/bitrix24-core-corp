<?php
namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class QueueTable
 * @package Bitrix\Voximplant
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Queue_Query query()
 * @method static EO_Queue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Queue_Result getById($id)
 * @method static EO_Queue_Result getList(array $parameters = [])
 * @method static EO_Queue_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_Queue createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_Queue_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_Queue wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_Queue_Collection wakeUpCollection($rows)
 */

class QueueTable extends Entity\DataManager
{
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_voximplant_queue';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'NAME' => new Entity\StringField('NAME', array(
				'size' => 255
			)),
			'TYPE' => new Entity\StringField('TYPE', array(
				'size' => 50,
			)),
			'WAIT_TIME' => new Entity\IntegerField('WAIT_TIME'),
			'NO_ANSWER_RULE' => new Entity\StringField('NO_ANSWER_RULE', array(
				'size' => 50,
				'default_value' => 'voicemail',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 50));},
			)),
			'NEXT_QUEUE_ID' => new Entity\IntegerField('NEXT_QUEUE_ID'),
			'FORWARD_NUMBER' => new Entity\StringField('FORWARD_NUMBER'),
			'ALLOW_INTERCEPT' => new Entity\BooleanField('ALLOW_INTERCEPT', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N'
			)),
			'PHONE_NUMBER' => new Entity\StringField('PHONE_NUMBER'),
			'CNT' => new Entity\ExpressionField('CNT', 'COUNT(*)')
		);
	}
}