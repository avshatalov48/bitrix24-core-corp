<?php
namespace Bitrix\ImConnector\Model;

use \Bitrix\Main\Entity;
use	\Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Entity\Validator\Length;

Loc::loadMessages(__FILE__);

/**
 * Class ConnectorsInfoTable
 * @package Bitrix\ImOpenLines\Model
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