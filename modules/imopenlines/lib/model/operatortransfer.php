<?php
namespace Bitrix\Imopenlines\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class OperatorTransferTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CONFIG_ID int mandatory
 * <li> SESSION_ID int mandatory
 * <li> USER_ID int mandatory
 * <li> TRANSFER_TYPE string(50) optional default 'user'
 * <li> TRANSFER_USER_ID int mandatory
 * <li> TRANSFER_LINE_ID int mandatory
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 **/

class OperatorTransferTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_operator_transfer';
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
				'title' => Loc::getMessage('OPERATOR_TRANSFER_ENTITY_ID_FIELD'),
			),
			'CONFIG_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('OPERATOR_TRANSFER_ENTITY_CONFIG_ID_FIELD'),
			),
			'SESSION_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('OPERATOR_TRANSFER_ENTITY_SESSION_ID_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('OPERATOR_TRANSFER_ENTITY_USER_ID_FIELD'),
			),
			'TRANSFER_MODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTransferMode'),
				'title' => Loc::getMessage('OPERATOR_TRANSFER_ENTITY_TRANSFER_TYPE_MODE'),
				'default_value' => 'MANUAL'
			),
			'TRANSFER_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTransferType'),
				'title' => Loc::getMessage('OPERATOR_TRANSFER_ENTITY_TRANSFER_TYPE_FIELD'),
				'default_value' => 'USER'
			),
			'TRANSFER_USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('OPERATOR_TRANSFER_ENTITY_TRANSFER_USER_ID_FIELD'),
			),
			'TRANSFER_LINE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('OPERATOR_TRANSFER_ENTITY_TRANSFER_LINE_ID_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('OPERATOR_TRANSFER_ENTITY_DATE_CREATE_FIELD'),
				'default_value' => array(__CLASS__, 'getCurrentDate'),
			),
		);
	}
	/**
	 * Returns validators for TRANSFER_MODE field.
	 *
	 * @return array
	 */
	public static function validateTransferMode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for TRANSFER_TYPE field.
	 *
	 * @return array
	 */
	public static function validateTransferType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
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