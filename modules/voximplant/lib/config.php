<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ConfigTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SEARCH_ID string(255) optional
 * <li> PHONE_NAME string(255) optional
 * <li> CRM bool optional default 'Y'
 * <li> CRM_RULE string(50) optional
 * <li> CRM_CREATE string(50) optional
 * <li> QUEUE_TIME int optional
 * <li> DIRECT_CODE bool optional default 'N'
 * <li> DIRECT_CODE_RULE string(50) optional
 * <li> RECORDING bool optional default 'Y'
 * <li> RECORDING_TIME int optional
 * <li> VOICEMAIL bool optional default 'Y'
 * <li> NO_ANSWER_RULE string(50) optional
 * <li> MELODY_LANG string(2) optional
 * <li> MELODY_WELCOME int optional
 * <li> MELODY_WELCOME_ENABLE bool optional default 'Y'
 * <li> MELODY_VOICEMAIL int optional
 * <li> MELODY_WAIT int optional
 * <li> MELODY_HOLD int optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Config_Query query()
 * @method static EO_Config_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Config_Result getById($id)
 * @method static EO_Config_Result getList(array $parameters = array())
 * @method static EO_Config_Entity getEntity()
 * @method static \Bitrix\Voximplant\EO_Config createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\EO_Config_Collection createCollection()
 * @method static \Bitrix\Voximplant\EO_Config wakeUpObject($row)
 * @method static \Bitrix\Voximplant\EO_Config_Collection wakeUpCollection($rows)
 */

class ConfigTable extends Data\DataManager
{
	const MAX_LENGTH_NAME = 255;

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_voximplant_config';
	}

	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			'PORTAL_MODE' => new Entity\StringField('PORTAL_MODE', array(
				'size' => 50,
				'default_value' => 'RENT',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 50));},
			)),
			'SEARCH_ID' => new Entity\StringField('SEARCH_ID', array(
				'size' => 255,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_SEARCH_ID_FIELD'),
				'validation' => function (){ return array(new Entity\Validator\Length(null, 255));},
			)),
			'PHONE_NAME' => new Entity\StringField('PHONE_NAME', array(
				'size' => static::MAX_LENGTH_NAME,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_PHONE_NAME_FIELD'),
				'validation' => function (){ return array(new Entity\Validator\Length(null, static::MAX_LENGTH_NAME));},
			)),
			'PHONE_COUNTRY_CODE' => new Entity\StringField('PHONE_COUNTRY_CODE', array(
				'size' => 50,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_PHONE_COUNTRY_CODE_FIELD'),
				'validation' => function (){ return array(new Entity\Validator\Length(null, 50));},
			)),
			'PHONE_VERIFIED' => new Entity\BooleanField('PHONE_VERIFIED', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_PHONE_VERIFIED_FIELD'),
				'default_value' => 'Y',
			)),
			'CRM' => new Entity\BooleanField('CRM', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_FIELD'),
				'default_value' => 'Y',
			)),
			'CRM_RULE' => new Entity\StringField('CRM_RULE', array(
				'size' => 50,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_RULE_FIELD'),
				'default_value' => 'queue',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 50));},
			)),
			'CRM_CREATE' => new Entity\StringField('CRM_CREATE', array(
				'size' => 50,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_CREATE_FIELD'),
				'default_value' => 'lead',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 50));},
			)),
			'CRM_CREATE_CALL_TYPE' => new Entity\StringField('CRM_CREATE_CALL_TYPE', array(
				'size' => 30,
				'default_value' => 'all',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 30));},
			)),
			'CRM_SOURCE' => new Entity\StringField('CRM_SOURCE', array(
				'size' => 50,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_SOURCE_FIELD'),
				'default_value' => 'CALL',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 50));},
			)),
			'CRM_FORWARD' => new Entity\BooleanField('CRM_FORWARD', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_FORWARD_FIELD'),
				'default_value' => 'Y',
			)),
			'CRM_TRANSFER_CHANGE' => new Entity\BooleanField('CRM_TRANSFER_CHANGE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_TRANSFER_CHANGE_FIELD'),
				'default_value' => 'Y',
			)),
			'IVR' => new Entity\BooleanField('IVR', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_DIRECT_CODE_FIELD'),
				'default_value' => 'Y',
			)),
			'QUEUE_ID' => new Entity\IntegerField('QUEUE_ID'),
			'IVR_ID' => new Entity\IntegerField('IVR_ID'),
			'DIRECT_CODE' => new Entity\BooleanField('DIRECT_CODE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_DIRECT_CODE_FIELD'),
				'default_value' => 'Y',
			)),
			'DIRECT_CODE_RULE' => new Entity\StringField('DIRECT_CODE_RULE', array(
				'size' => 50,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_DIRECT_CODE_RULE_FIELD'),
				'default_value' => 'voicemail',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 50));},
			)),
			'RECORDING' => new Entity\BooleanField('RECORDING', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_RECORDING_FIELD'),
				'default_value' => 'N',
			)),
			'RECORDING_TIME' => new Entity\IntegerField('RECORDING_TIME', array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_RECORDING_TIME_FIELD'),
			)),
			'RECORDING_NOTICE' => new Entity\BooleanField('RECORDING_NOTICE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_RECORDING_NOTICE_FIELD'),
				'default_value' => 'N',
			)),
			'RECORDING_STEREO' => new Entity\BooleanField('RECORDING_STEREO', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
			'FORWARD_LINE' => new Entity\StringField('FORWARD_LINE', array(
				'default_value' => 'default',
				'size' => 255,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_FORWARD_LINE_FIELD'),
				'validation' => function (){ return array(new Entity\Validator\Length(null, 255));},
			)),
			'VOICEMAIL' => new Entity\BooleanField('VOICEMAIL', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_VOICEMAIL_FIELD'),
				'default_value' => 'Y',
			)),
			'VOTE' => new Entity\BooleanField('VOTE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_VOTE_FIELD'),
				'default_value' => 'N',
			)),
			'MELODY_LANG' => new Entity\StringField('MELODY_LANG', array(
				'size' => 2,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_LANG_FIELD'),
				'default_value' => 'EN',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 2));},
			)),
			'MELODY_WELCOME' => new Entity\IntegerField('MELODY_WELCOME', array(
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_WELCOME_FIELD'),
				'default_value' => '0',
			)),
			'MELODY_WELCOME_ENABLE' => new Entity\BooleanField('MELODY_WELCOME_ENABLE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_WELCOME_ENABLE_FIELD'),
				'default_value' => 'Y',
			)),
			'MELODY_WAIT' => new Entity\IntegerField('MELODY_WAIT', array(
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_WAIT_FIELD'),
				'default_value' => '0',
			)),
			'MELODY_ENQUEUE' => new Entity\IntegerField('MELODY_ENQUEUE', array(
				'default_value' => '0',
			)),
			'MELODY_HOLD' => new Entity\IntegerField('MELODY_HOLD', array(
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_HOLD_FIELD'),
				'default_value' => '0',
			)),
			'MELODY_RECORDING' => new Entity\IntegerField('MELODY_RECORDING', array(
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_RECORDING_FIELD'),
				'default_value' => '0',
			)),
			'MELODY_VOTE' => new Entity\IntegerField('MELODY_VOTE', array(
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_VOTE_FIELD'),
				'default_value' => '0',
			)),
			'MELODY_VOTE_END' => new Entity\IntegerField('MELODY_VOTE_END', array(
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_VOTE_END_FIELD'),
				'default_value' => '0',
			)),
			'MELODY_VOICEMAIL' => new Entity\IntegerField('MELODY_VOICEMAIL', array(
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_VOICEMAIL_FIELD'),
				'default_value' => '0',
			)),
			'TIMEMAN' => new Entity\BooleanField('TIMEMAN', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_TIMEMAN_FIELD'),
				'default_value' => 'N',
			)),
			'WORKTIME_ENABLE' => new Entity\BooleanField('WORKTIME_ENABLE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_ENABLE_FIELD'),
				'default_value' => 'N',
			)),
			'WORKTIME_FROM' => new Entity\StringField('WORKTIME_FROM', array(
				'size' => 5,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_FROM_FIELD'),
				'default_value' => '9',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 5));},
			)),
			'WORKTIME_TO' => new Entity\StringField('WORKTIME_TO', array(
				'size' => 5,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_TO_FIELD'),
				'default_value' => '18.30',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 5));},
			)),
			'WORKTIME_TIMEZONE' => new Entity\StringField('WORKTIME_TIMEZONE', array(
				'size' => 50,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_TIMEZONE_FIELD'),
				'validation' => function (){ return array(new Entity\Validator\Length(null, 50));},
			)),
			'WORKTIME_HOLIDAYS' => new Entity\StringField('WORKTIME_HOLIDAYS', array(
				'size' => 2000,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_HOLIDAYS_FIELD'),
				'validation' => function (){ return array(new Entity\Validator\Length(null, 2000));},
			)),
			'WORKTIME_DAYOFF' => new Entity\StringField('WORKTIME_DAYOFF', array(
				'size' => 20,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_DAYOFF_FIELD'),
				'validation' => function (){ return array(new Entity\Validator\Length(null, 20));},
			)),
			'WORKTIME_DAYOFF_RULE' => new Entity\StringField('WORKTIME_DAYOFF_RULE', array(
				'size' => 50,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_DAYOFF_RULE_FIELD'),
				'default_value' => 'voicemail',
				'validation' => function (){ return array(new Entity\Validator\Length(null, 50));},
			)),
			'WORKTIME_DAYOFF_NUMBER' => new Entity\StringField('WORKTIME_DAYOFF_NUMBER', array(
				'size' => 20,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_DAYOFF_NUMBER_FIELD'),
				'validation' => function (){ return array(new Entity\Validator\Length(null, 20));},
			)),
			'WORKTIME_DAYOFF_MELODY' => new Entity\IntegerField('WORKTIME_DAYOFF_MELODY', array(
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_DAYOFF_MELODY_FIELD'),
				'default_value' => '0',
			)),
			'USE_SIP_TO' => new Entity\BooleanField('USE_SIP_TO', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N'
			)),
			'WAIT_CRM' => new Entity\IntegerField('WAIT_CRM'),
			'WAIT_DIRECT' => new Entity\IntegerField('WAIT_DIRECT'),
			'TRANSCRIBE' => new Entity\BooleanField('TRANSCRIBE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N'
			)),
			'TRANSCRIBE_LANG' => new Entity\StringField('TRANSCRIBE_LANG'),
			'TRANSCRIBE_PROVIDER' => new Entity\StringField('TRANSCRIBE_PROVIDER'),
			'CALLBACK_REDIAL' => new Entity\StringField('CALLBACK_REDIAL'),
			'CALLBACK_REDIAL_ATTEMPTS' => new Entity\IntegerField('CALLBACK_REDIAL_ATTEMPTS'),
			'CALLBACK_REDIAL_PERIOD' => new Entity\IntegerField('CALLBACK_REDIAL_PERIOD'),
			'LINE_PREFIX' => new Entity\StringField('LINE_PREFIX'),
			'CAN_BE_SELECTED' => new Entity\BooleanField('CAN_BE_SELECTED', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N'
			)),
			'BACKUP_NUMBER' => new Entity\StringField('BACKUP_NUMBER'),
			'BACKUP_LINE' => new Entity\StringField('BACKUP_LINE'),
			'REDIRECT_WITH_CLIENT_NUMBER' => new Entity\BooleanField('REDIRECT_WITH_CLIENT_NUMBER', array(
				'values' => ['N', 'Y']
			)),
			'QUEUE' => new Entity\ReferenceField(
				'QUEUE',
				'\Bitrix\Voximplant\Model\Queue',
				array('=this.QUEUE_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'SIP_CONFIG' => new Entity\ReferenceField(
				'SIP_CONFIG',
				'\Bitrix\Voximplant\Sip',
				array('=this.ID' => 'ref.CONFIG_ID'),
				array('join_type' => 'LEFT')
			),
			'NUMBER' => new Entity\ReferenceField(
				'NUMBER',
				'\Bitrix\Voximplant\Model\Number',
				array('=this.ID' => 'ref.CONFIG_ID', '=this.PORTAL_MODE' => new \Bitrix\Main\DB\SqlExpression('?', \CVoxImplantConfig::MODE_RENT)),
				array('join_type' => 'LEFT')
			),
			'GROUP_NUMBER' => new Entity\ReferenceField(
				'NUMBER',
				'\Bitrix\Voximplant\Model\Number',
				array('=this.ID' => 'ref.CONFIG_ID', '=this.PORTAL_MODE' => new \Bitrix\Main\DB\SqlExpression('?', \CVoxImplantConfig::MODE_GROUP)),
				array('join_type' => 'LEFT')
			),
			'CALLER_ID' => new Entity\ReferenceField(
				'CALLER_ID',
				'\Bitrix\Voximplant\Model\CallerId',
				array('=this.ID' => 'ref.CONFIG_ID'),
				array('join_type' => 'LEFT')
			),
			'CNT' => new Entity\ExpressionField('CNT', 'COUNT(*)'),
			'HAS_NUMBER' => new Entity\ExpressionField(
				'HAS_NUMBER',
				'CASE WHEN EXISTS (SELECT ID from b_voximplant_number WHERE CONFIG_ID = %s) THEN "Y" ELSE "N" END', ['ID']
			),
			'HAS_SIP_CONNECTION' => new Entity\ExpressionField(
				'HAS_SIP_CONNECTION',
				'CASE WHEN EXISTS (SELECT ID from b_voximplant_sip WHERE CONFIG_ID = %s) THEN "Y" ELSE "N" END', ['ID']
			),
			'HAS_CALLER_ID' => new Entity\ExpressionField(
				'HAS_CALLER_ID',
				'CASE WHEN EXISTS (SELECT ID from b_voximplant_caller_id WHERE CONFIG_ID = %s) THEN "Y" ELSE "N" END', ['ID']
			),
		);
	}

	public static function getBySearchId($searchId)
	{
		return static::getList(array(
			'filter' => array(
				'=SEARCH_ID' => $searchId
			)
		));
	}
}