<?php
namespace Bitrix\Imopenlines\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class TrackerTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SESSION_ID int mandatory
 * <li> CHAT_ID int mandatory
 * <li> MESSAGE_ID int mandatory
 * <li> USER_ID int mandatory
 * <li> ACTION string(50) optional
 * <li> CRM_ENTITY_TYPE string(50) optional
 * <li> CRM_ENTITY_ID int optional
 * <li> VALUE string(255) optional
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 **/

class TrackerTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_tracker';
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
				'title' => Loc::getMessage('TRACKER_ENTITY_ID_FIELD'),
			),
			'SESSION_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_SESSION_ID_FIELD'),
			),
			'CHAT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_CHAT_ID_FIELD'),
			),
			'MESSAGE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_MESSAGE_ID_FIELD'),
			),
			'MESSAGE_ORIGIN_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_MESSAGE_ORIGIN_ID_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_USER_ID_FIELD'),
			),
			'ACTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAction'),
				'title' => Loc::getMessage('TRACKER_ENTITY_ACTION_FIELD'),
			),
			'CRM_ENTITY_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCrmEntityType'),
				'title' => Loc::getMessage('TRACKER_ENTITY_CRM_ENTITY_TYPE_FIELD'),
			),
			'CRM_ENTITY_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TRACKER_ENTITY_CRM_ENTITY_ID_FIELD'),
			),
			'FIELD_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateValue'),
				'default_value' => 'FM',
				'title' => Loc::getMessage('TRACKER_ENTITY_FIELD_ID_FIELD'),
			),
			'FIELD_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateValue'),
				'title' => Loc::getMessage('TRACKER_ENTITY_FIELD_TYPE_FIELD'),
			),
			'FIELD_VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateValue'),
				'title' => Loc::getMessage('TRACKER_ENTITY_FIELD_VALUE_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('TRACKER_ENTITY_DATE_CREATE_FIELD'),
				'default_value' => array(__CLASS__, 'getCurrentDate'),
			),
		);
	}
	/**
	 * Returns validators for ACTION field.
	 *
	 * @return array
	 */
	public static function validateAction()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for CRM_ENTITY_TYPE field.
	 *
	 * @return array
	 */
	public static function validateCrmEntityType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for VALUE field.
	 *
	 * @return array
	 */
	public static function validateValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
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
}