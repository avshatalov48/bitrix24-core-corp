<?php
namespace Bitrix\Controller;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class GroupTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> NAME string(255) mandatory
 * <li> UPDATE_PERIOD int mandatory default -1
 * <li> DISABLE_DEACTIVATED bool optional default 'N'
 * <li> DESCRIPTION string optional
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime mandatory
 * <li> CREATED_BY int optional
 * <li> INSTALL_INFO string optional
 * <li> UNINSTALL_INFO string optional
 * <li> INSTALL_PHP string optional
 * <li> UNINSTALL_PHP string optional
 * <li> TRIAL_PERIOD int optional
 * <li> COUNTER_UPDATE_PERIOD int optional
 * <li> CHECK_COUNTER_FREE_SPACE string(1) optional
 * <li> CHECK_COUNTER_SITES string(1) optional
 * <li> CHECK_COUNTER_USERS string(1) optional
 * <li> CHECK_COUNTER_LAST_AUTH string(1) optional
 * <li> CREATED reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Controller
 **/

class GroupTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_group';
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
				'title' => Loc::getMessage('GROUP_ENTITY_ID_FIELD'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('GROUP_ENTITY_TIMESTAMP_X_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('GROUP_ENTITY_NAME_FIELD'),
			),
			'UPDATE_PERIOD' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('GROUP_ENTITY_UPDATE_PERIOD_FIELD'),
			),
			'DISABLE_DEACTIVATED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('GROUP_ENTITY_DISABLE_DEACTIVATED_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('GROUP_ENTITY_DESCRIPTION_FIELD'),
			),
			'MODIFIED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('GROUP_ENTITY_MODIFIED_BY_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('GROUP_ENTITY_DATE_CREATE_FIELD'),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('GROUP_ENTITY_CREATED_BY_FIELD'),
			),
			'INSTALL_INFO' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('GROUP_ENTITY_INSTALL_INFO_FIELD'),
			),
			'UNINSTALL_INFO' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('GROUP_ENTITY_UNINSTALL_INFO_FIELD'),
			),
			'INSTALL_PHP' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('GROUP_ENTITY_INSTALL_PHP_FIELD'),
			),
			'UNINSTALL_PHP' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('GROUP_ENTITY_UNINSTALL_PHP_FIELD'),
			),
			'TRIAL_PERIOD' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('GROUP_ENTITY_TRIAL_PERIOD_FIELD'),
			),
			'COUNTER_UPDATE_PERIOD' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('GROUP_ENTITY_COUNTER_UPDATE_PERIOD_FIELD'),
			),
			'CHECK_COUNTER_FREE_SPACE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCheckCounterFreeSpace'),
				'title' => Loc::getMessage('GROUP_ENTITY_CHECK_COUNTER_FREE_SPACE_FIELD'),
			),
			'CHECK_COUNTER_SITES' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCheckCounterSites'),
				'title' => Loc::getMessage('GROUP_ENTITY_CHECK_COUNTER_SITES_FIELD'),
			),
			'CHECK_COUNTER_USERS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCheckCounterUsers'),
				'title' => Loc::getMessage('GROUP_ENTITY_CHECK_COUNTER_USERS_FIELD'),
			),
			'CHECK_COUNTER_LAST_AUTH' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCheckCounterLastAuth'),
				'title' => Loc::getMessage('GROUP_ENTITY_CHECK_COUNTER_LAST_AUTH_FIELD'),
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
	 * Returns validators for CHECK_COUNTER_FREE_SPACE field.
	 *
	 * @return array
	 */
	public static function validateCheckCounterFreeSpace()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for CHECK_COUNTER_SITES field.
	 *
	 * @return array
	 */
	public static function validateCheckCounterSites()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for CHECK_COUNTER_USERS field.
	 *
	 * @return array
	 */
	public static function validateCheckCounterUsers()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for CHECK_COUNTER_LAST_AUTH field.
	 *
	 * @return array
	 */
	public static function validateCheckCounterLastAuth()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
}