<?php
namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ConfigStatisticTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CONFIG_ID int mandatory
 * <li> SESSION int optional
 * <li> CLOSED int optional
 * <li> IN_WORK int optional
 * <li> LEADS int optional
 * </ul>
 *
 * @package Bitrix\Imopenlines
 **/

class ConfigStatisticTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_imopenlines_config_statistic';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'CONFIG_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('CONFIG_STATISTIC_ENTITY_CONFIG_ID_FIELD'),
			),
			'SESSION' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_STATISTIC_ENTITY_SESSION_FIELD'),
				'default_value' => '0',
			),
			'MESSAGE' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_STATISTIC_ENTITY_MESSAGE_FIELD_NEW_NEW'),
				'default_value' => '0',
			),
			'CLOSED' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_STATISTIC_ENTITY_CLOSED_FIELD_NEW'),
				'default_value' => '0',
			),
			'IN_WORK' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_STATISTIC_ENTITY_IN_WORK_FIELD_NEW'),
				'default_value' => '0',
			),
			'LEAD' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CONFIG_STATISTIC_ENTITY_LEADS_FIELD'),
				'default_value' => '0',
			),
		);
	}
}