<?php
namespace Bitrix\Controller;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CounterHistoryTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> COUNTER_ID int optional
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> USER_ID int optional
 * <li> NAME string(255) mandatory
 * <li> COMMAND_FROM string mandatory
 * <li> COMMAND_TO string mandatory
 * <li> USER reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Controller
 **/

class CounterHistoryTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_counter_history';
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
				'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_ID_FIELD'),
			),
			'COUNTER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_COUNTER_ID_FIELD'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_TIMESTAMP_X_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_COUNTER_USER_ID_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_NAME_FIELD'),
			),
			'COMMAND_FROM' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_COMMAND_FROM_FIELD'),
			),
			'COMMAND_TO' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('COUNTER_HISTORY_ENTITY_COMMAND_TO_FIELD'),
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
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
}