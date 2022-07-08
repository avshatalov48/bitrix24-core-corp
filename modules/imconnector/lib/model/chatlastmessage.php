<?php
namespace Bitrix\ImConnector\Model;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Entity\Validator\Length;


/**
 * Class ConnectorsInfoTable
 * @package Bitrix\ImOpenLines\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ChatLastMessage_Query query()
 * @method static EO_ChatLastMessage_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_ChatLastMessage_Result getById($id)
 * @method static EO_ChatLastMessage_Result getList(array $parameters = array())
 * @method static EO_ChatLastMessage_Entity getEntity()
 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage createObject($setDefaultValues = true)
 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage_Collection createCollection()
 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage wakeUpObject($row)
 * @method static \Bitrix\ImConnector\Model\EO_ChatLastMessage_Collection wakeUpCollection($rows)
 */
class ChatLastMessageTable extends Entity\DataManager
{
	/**
	 * Return DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imconnectors_chat_last_message';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		return array(
			new  Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new  Entity\StringField('EXTERNAL_CHAT_ID', array(
				'validation' => array(__CLASS__, 'validateVarChar')
			)),
			new  Entity\StringField('CONNECTOR', array(
				'validation' => array(__CLASS__, 'validateVarChar')
			)),
			new  Entity\StringField('EXTERNAL_MESSAGE_ID', array(
				'validation' => array(__CLASS__, 'validateVarChar')
			)),
		);
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public static function validateVarChar()
	{
		return array(
			new Length(null, 255),
		);
	}
}