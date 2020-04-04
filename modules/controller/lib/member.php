<?php
namespace Bitrix\Controller;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class MemberTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> MEMBER_ID string(32) mandatory
 * <li> SECRET_ID string(32) mandatory
 * <li> NAME string(255) mandatory
 * <li> URL string(255) mandatory
 * <li> EMAIL string(255) optional
 * <li> CONTACT_PERSON string(255) optional
 * <li> CONTROLLER_GROUP_ID int mandatory
 * <li> DISCONNECTED bool optional default 'N'
 * <li> SHARED_KERNEL bool optional default 'N'
 * <li> ACTIVE bool optional default 'Y'
 * <li> DATE_ACTIVE_FROM datetime optional
 * <li> DATE_ACTIVE_TO datetime optional
 * <li> SITE_ACTIVE bool optional default 'Y'
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime mandatory
 * <li> CREATED_BY int optional
 * <li> IN_GROUP_FROM datetime optional
 * <li> NOTES string optional
 * <li> COUNTER_FREE_SPACE double optional
 * <li> COUNTER_SITES int optional
 * <li> COUNTER_USERS int optional
 * <li> COUNTER_LAST_AUTH datetime optional
 * <li> COUNTERS_UPDATED datetime optional
 * <li> HOSTNAME string(255) mandatory
 * <li> CONTROLLER_GROUP reference to {@link \Bitrix\Controller\GroupTable}
 * <li> CREATED reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Controller
 **/

class MemberTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_member';
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
				'title' => Loc::getMessage('MEMBER_ENTITY_ID_FIELD'),
			),
			'MEMBER_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateMemberId'),
				'title' => Loc::getMessage('MEMBER_ENTITY_MEMBER_ID_FIELD'),
			),
			'SECRET_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateSecretId'),
				'title' => Loc::getMessage('MEMBER_ENTITY_SECRET_ID_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('MEMBER_ENTITY_NAME_FIELD'),
			),
			'URL' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateUrl'),
				'title' => Loc::getMessage('MEMBER_ENTITY_URL_FIELD'),
			),
			'EMAIL' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEmail'),
				'title' => Loc::getMessage('MEMBER_ENTITY_EMAIL_FIELD'),
			),
			'CONTACT_PERSON' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateContactPerson'),
				'title' => Loc::getMessage('MEMBER_ENTITY_CONTACT_PERSON_FIELD'),
			),
			'CONTROLLER_GROUP_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('MEMBER_ENTITY_CONTROLLER_GROUP_ID_FIELD'),
			),
			'DISCONNECTED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('MEMBER_ENTITY_DISCONNECTED_FIELD'),
			),
			'SHARED_KERNEL' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('MEMBER_ENTITY_SHARED_KERNEL_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('MEMBER_ENTITY_ACTIVE_FIELD'),
			),
			'DATE_ACTIVE_FROM' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('MEMBER_ENTITY_DATE_ACTIVE_FROM_FIELD'),
			),
			'DATE_ACTIVE_TO' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('MEMBER_ENTITY_DATE_ACTIVE_TO_FIELD'),
			),
			'SITE_ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('MEMBER_ENTITY_SITE_ACTIVE_FIELD'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('MEMBER_ENTITY_TIMESTAMP_X_FIELD'),
			),
			'MODIFIED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('MEMBER_ENTITY_MODIFIED_BY_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('MEMBER_ENTITY_DATE_CREATE_FIELD'),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('MEMBER_ENTITY_CREATED_BY_FIELD'),
			),
			'IN_GROUP_FROM' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('MEMBER_ENTITY_IN_GROUP_FROM_FIELD'),
			),
			'NOTES' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('MEMBER_ENTITY_NOTES_FIELD'),
			),
			'COUNTER_FREE_SPACE' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('MEMBER_ENTITY_COUNTER_FREE_SPACE_FIELD'),
			),
			'COUNTER_SITES' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('MEMBER_ENTITY_COUNTER_SITES_FIELD'),
			),
			'COUNTER_USERS' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('MEMBER_ENTITY_COUNTER_USERS_FIELD'),
			),
			'COUNTER_LAST_AUTH' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('MEMBER_ENTITY_COUNTER_LAST_AUTH_FIELD'),
			),
			'COUNTERS_UPDATED' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('MEMBER_ENTITY_COUNTERS_UPDATED_FIELD'),
			),
			'HOSTNAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateHostname'),
				'title' => Loc::getMessage('MEMBER_ENTITY_HOSTNAME_FIELD'),
			),
			'CONTROLLER_GROUP' => array(
				'data_type' => 'Bitrix\Controller\GroupTable',
				'reference' => array('=this.CONTROLLER_GROUP_ID' => 'ref.ID'),
			),
			'CREATED' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.CREATED_BY' => 'ref.ID'),
			),
			'MODIFIED' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.MODIFIED_BY' => 'ref.ID'),
			),
		);
	}
	/**
	 * Returns validators for MEMBER_ID field.
	 *
	 * @return array
	 */
	public static function validateMemberId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 32),
		);
	}
	/**
	 * Returns validators for SECRET_ID field.
	 *
	 * @return array
	 */
	public static function validateSecretId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 32),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for URL field.
	 *
	 * @return array
	 */
	public static function validateUrl()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for EMAIL field.
	 *
	 * @return array
	 */
	public static function validateEmail()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for CONTACT_PERSON field.
	 *
	 * @return array
	 */
	public static function validateContactPerson()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for HOSTNAME field.
	 *
	 * @return array
	 */
	public static function validateHostname()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}