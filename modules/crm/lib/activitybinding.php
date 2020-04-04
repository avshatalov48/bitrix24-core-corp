<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;

Loc::loadMessages(__FILE__);

class ActivityBindingTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act_bind';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'ACTIVITY_ID' => array(
				'data_type' => 'integer'
			),
			'OWNER_ID' => array(
				'data_type' => 'integer'
			),
			'OWNER_TYPE_ID' => array(
				'data_type' => 'integer'
			)
		);
	}
}
