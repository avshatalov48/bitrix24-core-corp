<?php
namespace Bitrix\Crm\Order\Matcher\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class QueueTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_order_props_form_queue';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'PERSON_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'WORK_TIME' => array(
				'data_type' => 'boolean',
				'required' => false,
				'default_value' => 'N',
				'values' => array('N','Y')
			),
		);
	}
}
