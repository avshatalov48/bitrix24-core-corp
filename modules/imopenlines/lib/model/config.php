<?php
namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ConfigTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ACTIVE bool optional default 'Y'
 * <li> LINE_NAME string(255) optional
 * <li> CRM bool optional default 'Y'
 * <li> CRM_FORWARD bool optional default 'Y'
 * <li> CRM_TRANSFER_CHANGE bool optional default 'Y'
 * <li> CRM_CREATE string(50) optional default 'none'
 * <li> CRM_SOURCE string(50) optional default 'create'
 * <li> QUEUE_TIME int optional
 * <li> QUEUE_TYPE string(50) optional default 'evenly'
 * <li> TIMEMAN bool optional default 'N'
 * <li> CHECKING_OFFLINE bool optional default 'N'
 * <li> NO_ANSWER_RULE string(50) optional default 'form'
 * <li> NO_ANSWER_FORM_ID int optional
 * <li> NO_ANSWER_BOT_ID int optional
 * <li> NO_ANSWER_TEXT string optional
 * <li> WORKTIME_ENABLE bool optional default 'N'
 * <li> WORKTIME_FROM string(5) optional
 * <li> WORKTIME_TO string(5) optional
 * <li> WORKTIME_TIMEZONE string(50) optional
 * <li> WORKTIME_HOLIDAYS string(2000) optional
 * <li> WORKTIME_DAYOFF string(20) optional
 * <li> WORKTIME_DAYOFF_RULE string(50) optional default 'form'
 * <li> WORKTIME_DAYOFF_FORM_ID int optional
 * <li> WORKTIME_DAYOFF_BOT_ID int optional
 * <li> WORKTIME_DAYOFF_TEXT string optional
 * <li> CLOSE_RULE string(50) optional default 'form'
 * <li> CLOSE_FORM_ID int optional
 * <li> CLOSE_BOT_ID int optional
 * <li> CLOSE_TEXT string optional
 * <li> AUTO_CLOSE_RULE string(50) optional default 'none'
 * <li> AUTO_CLOSE_FORM_ID int optional
 * <li> AUTO_CLOSE_BOT_ID int optional
 * <li> AUTO_CLOSE_TIME int optional
 * <li> AUTO_CLOSE_TEXT string optional
 * <li> AUTO_EXPIRE_TIME int optional
 * <li> TEMPORARY bool optional default 'Y'
 * <li> QUICK_ANSWERS_IBLOCK_ID int optional
 * <li> TYPE_MAX_CHAT string(50) optional
 * <li> MAX_CHAT int optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 **/

class ConfigTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_config';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('CONFIG_ENTITY_ID_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_FIELD'),
				'default_value' => 'Y',
			),
			'LINE_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateLineName'),
				'title' => Loc::getMessage('CONFIG_ENTITY_LINE_NAME_FIELD'),
			),
			'CRM' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_FIELD'),
				'default_value' => 'Y',
			),
			'CRM_CREATE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCrmCreateField'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_CREATE_FIELD'),
				'default_value' => 'lead',
			),
			'CRM_FORWARD' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_FORWARD_FIELD'),
				'default_value' => 'Y',
			),
			'CRM_TRANSFER_CHANGE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_TRANSFER_CHANGE_FIELD'),
				'default_value' => 'Y',
			),
			'CRM_SOURCE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCrmSource'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CRM_SOURCE_FIELD'),
				'default_value' => 'create',
			),
			'QUEUE_TIME' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_QUEUE_TIME_FIELD_NEW'),
				'default_value' => '60',
			),
			'QUEUE_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateQueueType'),
				'title' => Loc::getMessage('CONFIG_ENTITY_QUEUE_TYPE_FIELD'),
				'default_value' => 'all',
			),
			'TIMEMAN' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_TIMEMAN_FIELD'),
				'default_value' => 'N',
			),
			'CHECKING_OFFLINE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CHECKING_OFFLINE_FIELD_NEW'),
				'default_value' => 'N',
			),
			'CHECK_ONLINE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CHECK_ONLINE_FIELD_NEW'),
				'default_value' => 'Y',
			),
			'WELCOME_BOT_ENABLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_BOT_ENABLE_FIELD'),
				'default_value' => 'N',
			),
			'WELCOME_MESSAGE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_MESSAGE_FIELD'),
				'default_value' => 'Y',
			),
			'WELCOME_MESSAGE_TEXT' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_MESSAGE_TEXT_FIELD_NEW'),
			),
			'VOTE_MESSAGE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_FIELD'),
				'default_value' => 'Y',
			),
			'VOTE_CLOSING_DELAY' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_CLOSING_DELAY_FIELD_NEW'),
				'default_value' => 'N',
			),
			'VOTE_MESSAGE_1_TEXT' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_1_TEXT_FIELD'),
			),
			'VOTE_MESSAGE_1_LIKE' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_1_LIKE_FIELD'),
			),
			'VOTE_MESSAGE_1_DISLIKE' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_1_DISLIKE_FIELD'),
			),
			'VOTE_MESSAGE_2_TEXT' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_2_TEXT_FIELD'),
			),
			'VOTE_MESSAGE_2_LIKE' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_2_LIKE_FIELD'),
			),
			'VOTE_MESSAGE_2_DISLIKE' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_VOTE_MESSAGE_2_DISLIKE_FIELD'),
			),
			'AGREEMENT_MESSAGE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'AGREEMENT_ID' => array(
				'data_type' => 'integer',
				'default_value' => '0',
			),
			'CATEGORY_ENABLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CATEGORY_ENABLE_FIELD'),
				'default_value' => 'N',
			),
			'CATEGORY_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_CATEGORY_ID_FIELD'),
				'default_value' => '0',
			),
			'WELCOME_BOT_JOIN' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateQueueType'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_BOT_JOIN_FIELD'),
				'default_value' => 'first',
			),
			'WELCOME_BOT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_BOT_ID_FIELD'),
				'default_value' => '0',
			),
			'WELCOME_BOT_TIME' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_BOT_TIME_FIELD'),
				'default_value' => '600',
			),
			'WELCOME_BOT_LEFT' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateQueueType'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WELCOME_BOT_LEFT_FIELD'),
				'default_value' => 'queue',
			),
			'NO_ANSWER_RULE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNoAnswerRule'),
				'title' => Loc::getMessage('CONFIG_ENTITY_NO_ANSWER_RULE_FIELD'),
				'default_value' => 'form',
			),
			'NO_ANSWER_FORM_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_NO_ANSWER_FORM_ID_FIELD'),
				'default_value' => '0',
			),
			'NO_ANSWER_BOT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_NO_ANSWER_BOT_ID_FIELD'),
				'default_value' => '0',
			),
			'NO_ANSWER_TEXT' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_NO_ANSWER_TEXT_FIELD'),
			),
			'WORKTIME_ENABLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_ENABLE_FIELD'),
				'default_value' => 'N',
			),
			'WORKTIME_FROM' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeFrom'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_FROM_FIELD'),
				'default_value' => '9',
			),
			'WORKTIME_TO' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeTo'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_TO_FIELD'),
				'default_value' => '18.30',
			),
			'WORKTIME_TIMEZONE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeTimezone'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_TIMEZONE_FIELD'),
			),
			'WORKTIME_HOLIDAYS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeHolidays'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_HOLIDAYS_FIELD'),
			),
			'WORKTIME_DAYOFF' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeDayoff'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_FIELD'),
			),
			'WORKTIME_DAYOFF_RULE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeDayoffRule'),
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_RULE_FIELD'),
				'default_value' => 'form',
			),
			'WORKTIME_DAYOFF_FORM_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_FORM_ID_FIELD'),
				'default_value' => '0',
			),
			'WORKTIME_DAYOFF_BOT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_BOT_ID_FIELD'),
				'default_value' => '0',
			),
			'WORKTIME_DAYOFF_TEXT' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_WORKTIME_DAYOFF_TEXT_FIELD'),
			),
			'CLOSE_RULE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCloseRule'),
				'title' => Loc::getMessage('CONFIG_ENTITY_CLOSE_RULE_FIELD'),
				'default_value' => 'text',
			),
			'CLOSE_FORM_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_CLOSE_FORM_ID_FIELD'),
				'default_value' => '0',
			),
			'CLOSE_BOT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_CLOSE_BOT_ID_FIELD'),
				'default_value' => '0',
			),
			'CLOSE_TEXT' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_CLOSE_TEXT_FIELD'),
			),
			'FULL_CLOSE_TIME' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_FULL_CLOSE_TIME_FIELD'),
				'default_value' => '10',
			),
			'AUTO_CLOSE_RULE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAutoCloseRule'),
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_RULE_FIELD'),
				'default_value' => 'none',
			),
			'AUTO_CLOSE_FORM_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_FORM_ID_FIELD'),
				'default_value' => '0',
			),
			'AUTO_CLOSE_BOT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_BOT_ID_FIELD'),
				'default_value' => '0',
			),
			'AUTO_CLOSE_TIME' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_TIME_FIELD_NEW'),
				'default_value' => '14400',
			),
			'AUTO_CLOSE_TEXT' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_CLOSE_TEXT_FIELD_NEW'),
			),
			'AUTO_EXPIRE_TIME' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_ENTITY_AUTO_EXPIRE_TIME_FIELD'),
				'default_value' => '86400',
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('CONFIG_ENTITY_DATE_CREATE_FIELD'),
				'default_value' => array(__CLASS__, 'getCurrentDate'),
			),
			'DATE_MODIFY' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('CONFIG_ENTITY_DATE_MODIFY_FIELD'),
				'default_value' => array(__CLASS__, 'getCurrentDate'),
			),
			'MODIFY_USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('CONFIG_ENTITY_MODIFY_USER_ID_FIELD'),
			),
			'TEMPORARY' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CONFIG_ENTITY_TEMPORARY_FIELD'),
				'default_value' => 'Y',
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
			'LANGUAGE_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateLanguageId'),
			),
			'STATISTIC' => array(
				'data_type' => 'Bitrix\ImOpenLines\Model\ConfigStatistic',
				'reference' => array('=this.ID' => 'ref.CONFIG_ID')
			),
			'QUICK_ANSWERS_IBLOCK_ID' => array(
				'data_type' => 'integer',
			),
			'SESSION_PRIORITY' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'TYPE_MAX_CHAT' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTypeMaxChat'),
				'default_value' => 'ANSWERED_NEW',
			),
			'MAX_CHAT' => array(
				'data_type' => 'integer',
				'default_value' => '0',
			),
			'OPERATOR_DATA' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateQueueType'),
				'title' => Loc::getMessage('CONFIG_ENTITY_OPERATOR_DATA_FIELD'),
				'default_value' => 'profile',
			),
			'DEFAULT_OPERATOR_DATA' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('CONFIG_ENTITY_DEFAULT_OPERATOR_DATA_FIELD'),
				'serialized' => true
			)
		);
	}
	/**
	 * Returns validators for LINE_NAME field.
	 *
	 * @return array
	 */
	public static function validateLineName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for CRM_SOURCE field.
	 *
	 * @return array
	 */
	public static function validateCrmSource()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for CRM_CREATE field.
	 *
	 * @return array
	 */
	public static function validateCrmCreateField()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for QUEUE_TYPE field.
	 *
	 * @return array
	 */
	public static function validateQueueType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for NO_ANSWER_RULE field.
	 *
	 * @return array
	 */
	public static function validateNoAnswerRule()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for WORKTIME_FROM field.
	 *
	 * @return array
	 */
	public static function validateWorktimeFrom()
	{
		return array(
			new Main\Entity\Validator\Length(null, 5),
		);
	}
	/**
	 * Returns validators for WORKTIME_TO field.
	 *
	 * @return array
	 */
	public static function validateWorktimeTo()
	{
		return array(
			new Main\Entity\Validator\Length(null, 5),
		);
	}
	/**
	 * Returns validators for WORKTIME_TIMEZONE field.
	 *
	 * @return array
	 */
	public static function validateWorktimeTimezone()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for WORKTIME_HOLIDAYS field.
	 *
	 * @return array
	 */
	public static function validateWorktimeHolidays()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2000),
		);
	}
	/**
	 * Returns validators for WORKTIME_DAYOFF field.
	 *
	 * @return array
	 */
	public static function validateWorktimeDayoff()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20),
		);
	}
	/**
	 * Returns validators for WORKTIME_DAYOFF_RULE field.
	 *
	 * @return array
	 */
	public static function validateWorktimeDayoffRule()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for CLOSE_RULE field.
	 *
	 * @return array
	 */
	public static function validateCloseRule()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for AUTO_CLOSE_RULE field.
	 *
	 * @return array
	 */
	public static function validateAutoCloseRule()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for LANGUAGE_ID field.
	 *
	 * @return array
	 */
	public static function validateLanguageId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
		);
	}

	/**
	 * Return current date for DATE_CREATE field.
	 *
	 * @return array
	 */
	public static function getCurrentDate()
	{
		return new \Bitrix\Main\Type\DateTime();
	}

	/**
	 * Returns validators for TYPE_MAX_CHAT field.
	 *
	 * @return array
	 * @throws Main\ArgumentTypeException
	 */
	public static function validateTypeMaxChat()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
}