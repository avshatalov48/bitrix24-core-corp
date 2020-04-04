<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class SipTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CONFIG_ID int mandatory
 * <li> TYPE string mandatory
 * <li> SERVER string(255) optional
 * <li> LOGIN string(255) optional
 * <li> PASSWORD string(255) optional
 * <li> INCOMING_SERVER string(255) optional
 * <li> INCOMING_LOGIN string(255) optional
 * <li> INCOMING_PASSWORD string(255) optional
 * <li> REG_ID int optional
 * <li> APP_ID string(128) optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 **/

class SipTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_voximplant_sip';
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
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_ID_FIELD'),
			),
			'TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateType'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_TYPE_FIELD'),
				'default_value' => 'office',
			),

			new Entity\ExpressionField('TITLE', '%s', 'CONFIG.PHONE_NAME'),

			'CONFIG_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_CONFIG_ID_FIELD'),
			),
			'REG_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_REG_ID_FIELD'),
				'default_value' => '0',
			),
			'APP_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAppId'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_APP_ID_FIELD'),
				'default_value' => '',
			),
			'SERVER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateServer'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_SERVER_FIELD'),
			),
			'LOGIN' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateLogin'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_LOGIN_FIELD'),
			),
			'PASSWORD' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePassword'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_PASSWORD_FIELD'),
			),
			'INCOMING_SERVER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIncomingServer'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_INCOMING_SERVER_FIELD'),
			),
			'INCOMING_LOGIN' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIncomingLogin'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_INCOMING_LOGIN_FIELD'),
			),
			'INCOMING_PASSWORD' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIncomingPassword'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_INCOMING_PASSWORD_FIELD'),
			),
			'INCOMING_PASSWORD' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIncomingPassword'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_INCOMING_PASSWORD_FIELD'),
			),
			'AUTH_USER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIncomingPassword'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_AUTH_USER_FIELD'),
			),
			'OUTBOUND_PROXY' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateIncomingPassword'),
				'title' => Loc::getMessage('SIP_CONFIG_ENTITY_OUTBOUND_PROXY_FIELD'),
			),
			'CONFIG' => array(
				'data_type' => 'Bitrix\Voximplant\Config',
				'reference' => array('=this.CONFIG_ID' => 'ref.ID'),
			),
		);
	}
	/**
	 * Returns validators for SERVER field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			new Entity\Validator\Length(1, 255),
		);
	}
	/**
	 * Returns validators for SERVER field.
	 *
	 * @return array
	 */
	public static function validateServer()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for APP_ID field.
	 *
	 * @return array
	 */
	public static function validateAppId()
	{
		return array(
			new Entity\Validator\Length(null, 128),
		);
	}
	/**
	 * Returns validators for LOGIN field.
	 *
	 * @return array
	 */
	public static function validateLogin()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for PASSWORD field.
	 *
	 * @return array
	 */
	public static function validatePassword()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for INCOMING_SERVER field.
	 *
	 * @return array
	 */
	public static function validateIncomingServer()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for INCOMING_LOGIN field.
	 *
	 * @return array
	 */
	public static function validateIncomingLogin()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for INCOMING_PASSWORD field.
	 *
	 * @return array
	 */
	public static function validateIncomingPassword()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}